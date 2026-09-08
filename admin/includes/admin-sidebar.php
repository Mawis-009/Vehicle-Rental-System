<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🚗</span> Admin Panel
    </div>
    <nav class="sidebar-menu">
        <ul>
            <li><a href="index.php" class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>"><span class="menu-icon">📊</span> Dashboard</a></li>
            <li><a href="users.php" class="<?php echo ($current_page == 'users') ? 'active' : ''; ?>"><span class="menu-icon">👥</span> Users</a></li>
            <li><a href="vehicles.php" class="<?php echo ($current_page == 'vehicles') ? 'active' : ''; ?>"><span class="menu-icon">🚗</span> Vehicles</a></li>
            <li><a href="categories.php" class="<?php echo ($current_page == 'categories') ? 'active' : ''; ?>"><span class="menu-icon">📁</span> Categories</a></li>
            <li><a href="bookings.php" class="<?php echo ($current_page == 'bookings') ? 'active' : ''; ?>"><span class="menu-icon">📋</span> Bookings</a></li>
            <li><a href="payments.php" class="<?php echo ($current_page == 'payments') ? 'active' : ''; ?>"><span class="menu-icon">💳</span> Payments</a></li>
            <li><a href="transactions.php" class="<?php echo ($current_page == 'transactions') ? 'active' : ''; ?>"><span class="menu-icon">🔄</span> Transactions</a></li>
            <li><a href="reports.php" class="<?php echo ($current_page == 'reports') ? 'active' : ''; ?>"><span class="menu-icon">📈</span> Reports</a></li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="<?php echo SITE_URL ?? '..'; ?>/index.php" target="_blank"><span class="menu-icon">🌐</span> View Site</a>
        <a href="<?php echo SITE_URL ?? '..'; ?>/logout.php"><span class="menu-icon">🚪</span> Logout</a>
    </div>
</aside>
