<?php
$page_title = 'Transactions - Admin';
$current_page = 'transactions';
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where = "1=1";
if ($status_filter) {
    $where .= " AND t.status = '" . $conn->real_escape_string($status_filter) . "'";
}

$query = "
    SELECT t.*, u.full_name as user_name, v.name as vehicle_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    JOIN bookings b ON t.booking_id = b.id 
    JOIN vehicles v ON b.vehicle_id = v.id 
    WHERE $where 
    ORDER BY t.created_at DESC
";
$transactions = $conn->query($query);
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
                <h1>Transactions History</h1>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="transactions.php" class="d-flex gap-3 align-items-end">
                        <div class="form-group w-auto">
                            <label>Status:</label>
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                <option value="completed" <?php echo $status_filter=='completed'?'selected':''; ?>>Completed</option>
                                <option value="pending" <?php echo $status_filter=='pending'?'selected':''; ?>>Pending</option>
                                <option value="failed" <?php echo $status_filter=='failed'?'selected':''; ?>>Failed</option>
                                <option value="refunded" <?php echo $status_filter=='refunded'?'selected':''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="form-group w-auto">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="transactions.php" class="btn btn-secondary">Clear</a>
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
                                    <th>Code</th>
                                    <th>User</th>
                                    <th>Vehicle</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($transactions->num_rows > 0): while($t = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo sanitize($t['id']); ?></td>
                                    <td><?php echo sanitize($t['transaction_code']); ?></td>
                                    <td><a href="user-details.php?id=<?php echo $t['user_id']; ?>"><?php echo sanitize($t['user_name']); ?></a></td>
                                    <td><?php echo sanitize($t['vehicle_name']); ?></td>
                                    <td><span class="badge badge-info"><?php echo ucfirst(sanitize($t['type'])); ?></span></td>
                                    <td><?php echo formatCurrency($t['amount']); ?></td>
                                    <td><span class="badge badge-<?php echo getStatusBadgeClass($t['status']); ?>"><?php echo ucfirst(sanitize($t['status'])); ?></span></td>
                                    <td><?php echo formatDate($t['created_at']); ?></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="8">No transactions found.</td></tr>
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
