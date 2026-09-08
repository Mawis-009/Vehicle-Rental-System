<?php
session_start();
$page_title = 'Home - Vehicle Rental';
$current_page = 'home';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_once 'includes/header.php';
?>

<div class="hero-section" style="padding: 4rem 2rem; text-align: center;">
    <div class="container">
        <h1>Rent Your Perfect Vehicle</h1>
        <p>Find the best deals on two-wheelers and four-wheelers for your next journey.</p>
        <div style="margin-top: 2rem;">
            <a href="vehicles.php" class="btn btn-primary">Browse Vehicles</a>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-secondary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container" style="padding: 3rem 0;">
    <h2 style="text-align: center; margin-bottom: 2rem;">How It Works</h2>
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; text-align: center;">
        <div class="stat-card card">
            <div class="card-body">
                <h3>1. Browse</h3>
                <p>Choose from our wide range of vehicles.</p>
            </div>
        </div>
        <div class="stat-card card">
            <div class="card-body">
                <h3>2. Book</h3>
                <p>Select your dates and book instantly.</p>
            </div>
        </div>
        <div class="stat-card card">
            <div class="card-body">
                <h3>3. Drive</h3>
                <p>Pick up your vehicle and enjoy the ride.</p>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding: 3rem 0;">
    <h2 style="text-align: center; margin-bottom: 2rem;">Categories</h2>
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; text-align: center;">
        <div class="card">
            <div class="card-body">
                <h3>Two Wheelers</h3>
                <p>Scooters and Motorcycles</p>
                <a href="vehicles.php?type=two-wheeler" class="btn btn-primary">View Two Wheelers</a>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h3>Four Wheelers</h3>
                <p>Cars and SUVs</p>
                <a href="vehicles.php?type=four-wheeler" class="btn btn-primary">View Four Wheelers</a>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding: 3rem 0;">
    <h2 style="text-align: center; margin-bottom: 2rem;">Featured Vehicles</h2>
    <div class="vehicle-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
        <?php
        $stmt = $conn->prepare("SELECT v.*, c.name as category_name FROM vehicles v LEFT JOIN categories c ON v.category_id = c.id WHERE v.status = 'available' ORDER BY v.created_at DESC LIMIT 6");
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0):
            while ($vehicle = $result->fetch_assoc()):
        ?>
        <div class="vehicle-card card">
            <?php if (!empty($vehicle['image'])): ?>
                <img src="uploads/<?php echo sanitize($vehicle['image']); ?>" alt="<?php echo sanitize($vehicle['name']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 200px; background: #eee; display: flex; align-items: center; justify-content: center;">No Image</div>
            <?php endif; ?>
            <div class="card-body">
                <h3><?php echo sanitize($vehicle['name']); ?></h3>
                <p style="color: #666;"><?php echo sanitize($vehicle['brand']); ?></p>
                <p style="font-size: 1.25rem; font-weight: bold; margin: 1rem 0;"><?php echo formatCurrency($vehicle['price_per_day']); ?> <small style="font-weight: normal; font-size: 0.9rem;">/ day</small></p>
                <a href="vehicle-details.php?id=<?php echo sanitize($vehicle['id']); ?>" class="btn btn-primary" style="display: block; text-align: center;">View Details</a>
            </div>
        </div>
        <?php 
            endwhile;
        else:
        ?>
            <p style="grid-column: 1 / -1; text-align: center;">No vehicles currently available.</p>
        <?php
        endif;
        $stmt->close(); 
        ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
