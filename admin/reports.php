<?php
$page_title = 'Reports - Admin';
$current_page = 'reports';
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

// Total Revenue
$res = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
$total_revenue = $res->fetch_assoc()['total'] ?? 0;

// This month revenue
$res = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$month_revenue = $res->fetch_assoc()['total'] ?? 0;

// Bookings by status
$bookings_status = [];
$res = $conn->query("SELECT booking_status, COUNT(*) as count FROM bookings GROUP BY booking_status");
while($row = $res->fetch_assoc()) {
    $bookings_status[$row['booking_status']] = $row['count'];
}

// Top 5 most booked vehicles
$top_vehicles = $conn->query("
    SELECT v.name, v.vehicle_number, COUNT(b.id) as booking_count, SUM(b.total_amount) as total_earned
    FROM vehicles v
    LEFT JOIN bookings b ON v.id = b.vehicle_id
    GROUP BY v.id
    ORDER BY booking_count DESC
    LIMIT 5
");

// Monthly revenue (last 6 months)
$monthly_revenue = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as revenue 
    FROM payments 
    WHERE status = 'completed' AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month DESC
");

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
                <h1>System Reports</h1>
            </div>

            <div class="stats-grid mb-4">
                <div class="stat-card stat-success">
                    <h3>Total Revenue (All Time)</h3>
                    <p class="stat-value"><?php echo formatCurrency($total_revenue); ?></p>
                </div>
                <div class="stat-card stat-primary">
                    <h3>Revenue (This Month)</h3>
                    <p class="stat-value"><?php echo formatCurrency($month_revenue); ?></p>
                </div>
            </div>

            <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div class="col" style="flex: 1; min-width: 300px;">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2>Bookings by Status</h2>
                        </div>
                        <div class="card-body">
                            <ul class="list-group" style="list-style: none; padding: 0;">
                                <?php foreach(['pending', 'confirmed', 'completed', 'cancelled'] as $st): ?>
                                <li style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                                    <span class="badge badge-<?php echo getStatusBadgeClass($st); ?>"><?php echo ucfirst($st); ?></span>
                                    <strong><?php echo isset($bookings_status[$st]) ? $bookings_status[$st] : 0; ?></strong>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col" style="flex: 2; min-width: 400px;">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2>Monthly Revenue (Last 6 Months)</h2>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($monthly_revenue->num_rows > 0): while($row = $monthly_revenue->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($row['month'] . '-01')); ?></td>
                                        <td><?php echo formatCurrency($row['revenue']); ?></td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr><td colspan="2">No revenue data.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Top 5 Most Booked Vehicles</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Vehicle Name</th>
                                    <th>Number</th>
                                    <th>Total Bookings</th>
                                    <th>Total Earned</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($top_vehicles->num_rows > 0): while($v = $top_vehicles->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo sanitize($v['name']); ?></td>
                                    <td><?php echo sanitize($v['vehicle_number']); ?></td>
                                    <td><?php echo sanitize($v['booking_count']); ?></td>
                                    <td><?php echo formatCurrency($v['total_earned'] ?? 0); ?></td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="4">No booking data.</td></tr>
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
