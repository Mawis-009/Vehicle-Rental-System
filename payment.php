<?php
$page_title = 'Payment - Vehicle Rental';
$current_page = 'payment';
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$booking_id) {
    setFlash('error', 'Invalid booking reference.');
    redirect('my-bookings.php');
}

// Fetch booking details
$stmt = $conn->prepare("SELECT b.*, v.name as vehicle_name FROM bookings b JOIN vehicles v ON b.vehicle_id = v.id WHERE b.id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    setFlash('error', 'Booking not found or access denied.');
    redirect('my-bookings.php');
}

if ($booking['payment_status'] === 'paid') {
    setFlash('info', 'This booking is already paid.');
    redirect('my-bookings.php');
}

// Prepare eSewa variables
$transaction_uuid = generateTransactionCode();
$total_amount = $booking['total_amount'];
$message = "{$total_amount},{$transaction_uuid}," . ESEWA_MERCHANT_CODE;
$signature = base64_encode(hash_hmac('sha256', $message, ESEWA_SECRET_KEY, true));
$esewa_success_url = SITE_URL . '/payment-verify.php?method=esewa&booking_id=' . $booking_id;
$esewa_failure_url = SITE_URL . '/payment-verify.php?method=esewa_fail&booking_id=' . $booking_id;

require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="page-header">
        <h1>Complete Payment</h1>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Booking Summary</h4>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Vehicle:</th>
                            <td><?php echo sanitize($booking['vehicle_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Start Date:</th>
                            <td><?php echo formatDate($booking['start_date']); ?></td>
                        </tr>
                        <tr>
                            <th>End Date:</th>
                            <td><?php echo formatDate($booking['end_date']); ?></td>
                        </tr>
                        <tr>
                            <th>Rental Days:</th>
                            <td><?php echo $booking['rental_days']; ?> days</td>
                        </tr>
                        <tr>
                            <th>Price per Day:</th>
                            <td><?php echo formatCurrency($booking['price_per_day']); ?></td>
                        </tr>
                        <tr class="table-active font-weight-bold">
                            <th>Total Amount:</th>
                            <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h4>Select Payment Method</h4>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs payment-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link payment-tab active" href="#" onclick="switchPaymentTab(event, 'esewa')">eSewa</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link payment-tab" href="#" onclick="switchPaymentTab(event, 'khalti')">Khalti</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link payment-tab" href="#" onclick="switchPaymentTab(event, 'card')">Credit/Debit Card</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- eSewa Tab -->
                        <div id="esewa" class="payment-pane tab-pane active d-block">
                            <div class="text-center py-3">
                                <img src="images/esewa-logo.png" alt="eSewa" style="max-height:50px; margin-bottom:15px;">
                                <p>Pay securely via your eSewa wallet.</p>
                                <form action="<?php echo ESEWA_PAYMENT_URL; ?>" method="POST">
                                    <input type="hidden" name="amount" value="<?php echo $total_amount; ?>">
                                    <input type="hidden" name="tax_amount" value="0">
                                    <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                                    <input type="hidden" name="transaction_uuid" value="<?php echo $transaction_uuid; ?>">
                                    <input type="hidden" name="product_code" value="<?php echo ESEWA_MERCHANT_CODE; ?>">
                                    <input type="hidden" name="product_service_charge" value="0">
                                    <input type="hidden" name="product_delivery_charge" value="0">
                                    <input type="hidden" name="success_url" value="<?php echo $esewa_success_url; ?>">
                                    <input type="hidden" name="failure_url" value="<?php echo $esewa_failure_url; ?>">
                                    <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
                                    <input type="hidden" name="signature" value="<?php echo $signature; ?>">
                                    <button type="submit" class="btn btn-success btn-lg px-5">Pay with eSewa</button>
                                </form>
                            </div>
                        </div>

                        <!-- Khalti Tab -->
                        <div id="khalti" class="payment-pane tab-pane" style="display:none;">
                            <div class="text-center py-3">
                                <img src="images/khalti-logo.png" alt="Khalti" style="max-height:50px; margin-bottom:15px;">
                                <p>Pay securely via your Khalti wallet.</p>
                                <form action="payment-verify.php?method=khalti&booking_id=<?php echo $booking_id; ?>" method="POST">
                                    <input type="hidden" name="initiate" value="1">
                                    <button type="submit" class="btn" style="background-color:#5C2D91; color:white; padding:10px 30px; font-size:1.1rem; border-radius:5px;">Pay with Khalti</button>
                                </form>
                            </div>
                        </div>

                        <!-- Card Tab -->
                        <div id="card" class="payment-pane tab-pane" style="display:none;">
                            <div class="py-3">
                                <h5 class="mb-3">Credit / Debit Card Details</h5>
                                <form id="card_payment_form" action="payment-verify.php?method=card&booking_id=<?php echo $booking_id; ?>" method="POST">
                                    <input type="hidden" name="payment_method" value="card">
                                    
                                    <div class="form-group mb-3">
                                        <label for="card_name" class="form-label">Name on Card</label>
                                        <input type="text" id="card_name" name="card_name" class="form-control" required placeholder="John Doe">
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="card_number" class="form-label">Card Number</label>
                                        <div class="input-group">
                                            <input type="text" id="card_number" name="card_number" class="form-control" required placeholder="XXXX XXXX XXXX XXXX">
                                            <span class="input-group-text" id="card_type"><i class="fas fa-credit-card"></i></span>
                                        </div>
                                        <span id="card_feedback" class="text-danger small mt-1" style="display:block;"></span>
                                    </div>
                                    
                                    <div class="form-group mb-4 w-50">
                                        <label for="card_expiry" class="form-label">Expiry Date</label>
                                        <input type="text" id="card_expiry" name="card_expiry" class="form-control" required placeholder="MM/YY">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">Pay <?php echo formatCurrency($booking['total_amount']); ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/card-validation.js"></script>
<script>
function switchPaymentTab(e, tabId) {
    e.preventDefault();
    
    document.querySelectorAll('.payment-tab').forEach(t => t.classList.remove('active'));
    e.target.classList.add('active');
    
    document.querySelectorAll('.payment-pane').forEach(p => {
        p.classList.remove('active', 'd-block');
        p.style.display = 'none';
    });
    
    const activePane = document.getElementById(tabId);
    activePane.classList.add('active', 'd-block');
    activePane.style.display = 'block';
}
</script>

<?php require_once 'includes/footer.php'; ?>
