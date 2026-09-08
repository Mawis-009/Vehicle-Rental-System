<?php
$page_title = 'Payment Verification - Vehicle Rental';
$current_page = 'payment-verify';
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();

$method = $_GET['method'] ?? '';
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$booking_id || !$method) {
    setFlash('error', 'Invalid payment verification request.');
    redirect('my-bookings.php');
}

// Fetch booking
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    setFlash('error', 'Booking not found.');
    redirect('my-bookings.php');
}

if ($booking['payment_status'] === 'paid') {
    setFlash('info', 'This booking is already paid.');
    redirect('my-bookings.php');
}

// Helper to update after successful payment
function processSuccessfulPayment($conn, $booking, $user_id, $payment_method, $amount, $gateway_response, $transaction_id) {
    $conn->begin_transaction();
    try {
        // Insert payment
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, user_id, transaction_id, payment_method, amount, status, gateway_response, payment_date, created_at) VALUES (?, ?, ?, ?, ?, 'completed', ?, NOW(), NOW())");
        $stmt->bind_param("iissss", $booking['id'], $user_id, $transaction_id, $payment_method, $amount, $gateway_response);
        $stmt->execute();
        $payment_id = $stmt->insert_id;
        $stmt->close();

        // Update booking
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'confirmed', payment_status = 'paid', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $booking['id']);
        $stmt->execute();
        $stmt->close();

        // Update vehicle status
        $stmt = $conn->prepare("UPDATE vehicles SET status = 'rented', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $booking['vehicle_id']);
        $stmt->execute();
        $stmt->close();

        // Insert transaction
        $txn_code = generateTransactionCode();
        $stmt = $conn->prepare("INSERT INTO transactions (booking_id, user_id, payment_id, transaction_code, type, amount, status, remarks, created_at) VALUES (?, ?, ?, ?, 'payment', ?, 'success', 'Payment successful', NOW())");
        $stmt->bind_param("iiisd", $booking['id'], $user_id, $payment_id, $txn_code, $amount);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Luhn check for cards
function luhnCheck($number) {
    $number = preg_replace('/\D/', '', $number);
    $sum = 0;
    $alt = false;
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $n = substr($number, $i, 1);
        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $sum += $n;
        $alt = !$alt;
    }
    return ($sum % 10 == 0);
}

// Handle different methods
if ($method === 'esewa') {
    if (isset($_GET['data'])) {
        $data = json_decode(base64_decode($_GET['data']), true);
        if ($data && isset($data['transaction_uuid']) && isset($data['total_amount'])) {
            // Verify with eSewa status API
            $url = ESEWA_VERIFY_URL . "?product_code=" . ESEWA_MERCHANT_CODE . "&total_amount=" . $data['total_amount'] . "&transaction_uuid=" . $data['transaction_uuid'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $res_data = json_decode($response, true);
            if ($res_data && isset($res_data['status']) && $res_data['status'] === 'COMPLETE') {
                if (processSuccessfulPayment($conn, $booking, $user_id, 'esewa', $booking['total_amount'], $response, $data['transaction_code'] ?? 'ESW-'.time())) {
                    setFlash('success', 'Payment successful via eSewa.');
                } else {
                    setFlash('error', 'Database error during payment processing.');
                }
            } else {
                setFlash('error', 'eSewa payment verification failed.');
            }
        } else {
            setFlash('error', 'Invalid data from eSewa.');
        }
    } else {
        setFlash('error', 'No data received from eSewa.');
    }
    redirect('my-bookings.php');

} elseif ($method === 'esewa_fail') {
    setFlash('error', 'eSewa payment was cancelled or failed.');
    redirect('payment.php?booking_id=' . $booking_id);

} elseif ($method === 'khalti') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initiate'])) {
        // Initiate Khalti Payment
        $payload = [
            "return_url" => SITE_URL . "/payment-verify.php?method=khalti&booking_id=" . $booking_id,
            "website_url" => SITE_URL,
            "amount" => $booking['total_amount'] * 100, // Amount in paisa
            "purchase_order_id" => "BOOK-" . $booking_id,
            "purchase_order_name" => "Vehicle Booking ID " . $booking_id,
            "customer_info" => [
                "name" => $_SESSION['user_name'] ?? 'User',
                "email" => $_SESSION['user_email'] ?? 'test@example.com',
                "phone" => "9800000000"
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, KHALTI_INITIATE_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Key ' . KHALTI_SECRET_KEY,
            'Content-Type: application/json',
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($response, true);

        if (isset($res['payment_url'])) {
            header('Location: ' . $res['payment_url']);
            exit;
        } else {
            setFlash('error', 'Failed to initiate Khalti payment.');
            redirect('payment.php?booking_id=' . $booking_id);
        }
    } elseif (isset($_GET['pidx'])) {
        // Verify Khalti Payment
        $pidx = $_GET['pidx'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, KHALTI_LOOKUP_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['pidx' => $pidx]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Key ' . KHALTI_SECRET_KEY,
            'Content-Type: application/json',
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($response, true);

        if (isset($res['status']) && $res['status'] === 'Completed') {
            if (processSuccessfulPayment($conn, $booking, $user_id, 'khalti', $booking['total_amount'], $response, $res['transaction_id'] ?? 'KHL-'.time())) {
                setFlash('success', 'Payment successful via Khalti.');
            } else {
                setFlash('error', 'Database error during payment processing.');
            }
        } else {
            setFlash('error', 'Khalti payment verification failed or was cancelled.');
        }
        redirect('my-bookings.php');
    }

} elseif ($method === 'card') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $card_number = $_POST['card_number'] ?? '';
        
        if (empty($card_number) || !luhnCheck($card_number)) {
            setFlash('error', 'Invalid card number. Please try again.');
            redirect('payment.php?booking_id=' . $booking_id);
        }

        // Simulate success
        $simulated_response = json_encode(['status' => 'success', 'message' => 'Simulated card payment']);
        $transaction_id = 'CARD-' . time() . '-' . rand(1000,9999);
        
        if (processSuccessfulPayment($conn, $booking, $user_id, 'card', $booking['total_amount'], $simulated_response, $transaction_id)) {
            setFlash('success', 'Payment successful via Credit/Debit Card.');
        } else {
            setFlash('error', 'Database error during payment processing.');
        }
        redirect('my-bookings.php');
    }
}

// Fallback
setFlash('error', 'Invalid request.');
redirect('my-bookings.php');
