<?php
$page_title = 'Transaction History - Vehicle Rental';
$current_page = 'history';
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();
$user_id = $_SESSION['user_id'];

// Fetch Transactions
$stmt = $conn->prepare("
    SELECT t.*, b.id as booking_ref, b.booking_status, b.payment_status as b_payment_status, v.name as vehicle_name, p.payment_method
    FROM transactions t
    JOIN bookings b ON t.booking_id = b.id
    JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN payments p ON t.payment_id = p.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="page-header mb-4">
        <h1>Transaction History</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (count($transactions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Transaction Code</th>
                                <th>Booking ID</th>
                                <th>Vehicle</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Txn Status</th>
                                <th>Booking Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><strong><?php echo sanitize($txn['transaction_code']); ?></strong></td>
                                    <td>#<?php echo $txn['booking_ref']; ?></td>
                                    <td><?php echo sanitize($txn['vehicle_name']); ?></td>
                                    <td><?php echo ucfirst(sanitize($txn['payment_method'] ?? 'N/A')); ?></td>
                                    <td><?php echo formatCurrency($txn['amount']); ?></td>
                                    <td><?php echo formatDateTime($txn['created_at']); ?></td>
                                    <td><span class="badge <?php echo getStatusBadgeClass($txn['status']); ?>"><?php echo ucfirst($txn['status']); ?></span></td>
                                    <td><span class="badge <?php echo getStatusBadgeClass($txn['booking_status']); ?>"><?php echo ucfirst($txn['booking_status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <p class="text-muted">No transactions found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
