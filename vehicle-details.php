<?php
session_start();
$current_page = 'vehicles';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect('vehicles.php');
}

$stmt = $conn->prepare("SELECT v.*, c.name as category_name, c.type as category_type FROM vehicles v LEFT JOIN categories c ON v.category_id = c.id WHERE v.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result->fetch_assoc();
$stmt->close();

if (!$vehicle) {
    redirect('vehicles.php');
}

$page_title = sanitize($vehicle['name']) . ' - Vehicle Rental';

require_once 'includes/header.php';
?>

<div class="container" style="padding: 3rem 0;">
    <div class="vehicle-detail" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
        <div>
            <?php if (!empty($vehicle['image'])): ?>
                <img src="uploads/<?php echo sanitize($vehicle['image']); ?>" alt="<?php echo sanitize($vehicle['name']); ?>" style="width: 100%; border-radius: 8px;">
            <?php else: ?>
                <div style="width: 100%; height: 400px; background: #eee; display: flex; align-items: center; justify-content: center; border-radius: 8px;">No Image</div>
            <?php endif; ?>
        </div>
        
        <div>
            <h1 style="margin-bottom: 0.5rem;"><?php echo sanitize($vehicle['name']); ?></h1>
            <p style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color, #0056b3); margin-bottom: 1.5rem;">
                <?php echo formatCurrency($vehicle['price_per_day']); ?> <small style="font-weight: normal; font-size: 1rem; color: #666;">/ day</small>
            </p>
            
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-body">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid #eee;"><strong>Category:</strong> <?php echo sanitize($vehicle['category_name']); ?> (<?php echo sanitize(ucwords(str_replace('-', ' ', $vehicle['category_type']))); ?>)</li>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid #eee;"><strong>Brand:</strong> <?php echo sanitize($vehicle['brand']); ?></li>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid #eee;"><strong>Model Year:</strong> <?php echo sanitize($vehicle['model_year']); ?></li>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid #eee;"><strong>Vehicle Number:</strong> <?php echo sanitize($vehicle['vehicle_number']); ?></li>
                        <li style="padding: 0.75rem 0;"><strong>Status:</strong> <span class="badge <?php echo getStatusBadgeClass($vehicle['status']); ?>"><?php echo ucfirst(sanitize($vehicle['status'])); ?></span></li>
                    </ul>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <h3>Description</h3>
                <p style="white-space: pre-line; color: #555;"><?php echo sanitize($vehicle['description']); ?></p>
            </div>

            <div class="booking-section">
                <?php if ($vehicle['status'] !== 'available'): ?>
                    <button class="btn btn-secondary" disabled style="width: 100%; padding: 1rem;">Not Available</button>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="login.php" class="btn btn-secondary" style="display: block; text-align: center; width: 100%; padding: 1rem;">Login to Book</a>
                <?php else: ?>
                    <a href="booking.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-primary" style="display: block; text-align: center; width: 100%; padding: 1rem;">Book Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
