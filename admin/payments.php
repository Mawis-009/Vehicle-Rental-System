<?php
$page_title = 'Payments Management - Admin';
$current_page = 'payments';
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$method_filter = isset($_GET['method']) ? $_GET['method'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$where = "1=1";
if ($method_filter) {
    $where .= " AND p.payment_method = '" . $conn->real_escape_string($method_filter) . "'";
}
if ($status_filter) {
    $where .= " AND p.status = '" . $conn->real_escape_string($status_filter) . "'";
}

$query = "
    SELECT p.*, u.full_name as user_name 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    WHERE $where 
    ORDER BY p.created_at DESC
";
$payments = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <?php require_once 'includes/admin-sidebar.php'; ?>
        <main class="admin-content">
            <?php $flash = getFlash(); if($flash): ?>
            <div class="alert alert-<?php echo sanitize($flash['type']); ?>">
                <?php echo sanitize($flash['message']); ?>
                <button class="alert-close" onclick="this.parentElement.remove();">&times;</button>
            </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>Payments History</h1>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="payments.php" class="d-flex gap-3 align-items-end">
                        <div class="form-group w-auto">
                            <label>Method:</label>
                            <select name="method" class="form-control">
                                <option value="">All</option>
                                <option value="esewa" <?php echo $method_filter=='esewa'?'selected':''; ?>>eSewa</option>
                                <option value="khalti" <?php echo $method_filter=='khalti'?'selected':''; ?>>Khalti</option>
                                <option value="cash" <?php echo $method_filter=='cash'?'selected':''; ?>>Cash</option>
                            </select>
                        </div>
                        <div class="form-group w-auto">
                            <label>Status:</label>
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                <option value="pending" <?php echo $status_filter=='pending'?'selected':''; ?>>Pending</option>
                                <option value="completed" <?php echo $status_filter=='completed'?'selected':''; ?>>Completed</option>
                                <option value="failed" <?php echo $status_filter=='failed'?'selected':''; ?>>Failed</option>
                                <option value="refunded" <?php echo $status_filter=='refunded'?'selected':''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="form-group w-auto">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="payments.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Transaction ID</th>
                                    <th>User</th>
                                    <th>Booking ID</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($payments->num_rows > 0): while($p = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo sanitize($p['id']); ?></td>
                                    <td><?php echo sanitize($p['transaction_id']); ?></td>
                                    <td><a href="user-details.php?id=<?php echo $p['user_id']; ?>"><?php echo sanitize($p['user_name']); ?></a></td>
                                    <td><a href="bookings.php?id=<?php echo $p['booking_id']; ?>">#<?php echo sanitize($p['booking_id']); ?></a></td>
                                    <td><span class="badge badge-info"><?php echo ucfirst(sanitize($p['payment_method'])); ?></span></td>
                                    <td><?php echo formatCurrency($p['amount']); ?></td>
                                    <td><span class="badge badge-<?php echo getStatusBadgeClass($p['status']); ?>"><?php echo ucfirst(sanitize($p['status'])); ?></span></td>
                                    <td><?php echo formatDate($p['created_at']); ?></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="8">No payments found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
    <script src="../js/script.js"></script>
</body>
</html>
