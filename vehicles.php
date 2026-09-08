<?php
session_start();
$page_title = 'Our Vehicles - Vehicle Rental';
$current_page = 'vehicles';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$type = isset($_GET['type']) ? $_GET['type'] : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

require_once 'includes/header.php';
?>

<div class="page-header" style="background: #f8f9fa; padding: 2rem 0; text-align: center;">
    <div class="container">
        <h1>Our Vehicles</h1>
        <div style="margin-top: 1rem;">
            <a href="vehicles.php" class="btn <?php echo (empty($type) && empty($category)) ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
            <a href="vehicles.php?type=two-wheeler" class="btn <?php echo ($type === 'two-wheeler') ? 'btn-primary' : 'btn-secondary'; ?>">Two Wheelers</a>
            <a href="vehicles.php?type=four-wheeler" class="btn <?php echo ($type === 'four-wheeler') ? 'btn-primary' : 'btn-secondary'; ?>">Four Wheelers</a>
        </div>
    </div>
</div>

<div class="container" style="padding: 3rem 0;">
    <div class="vehicle-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
        <?php
        $query = "SELECT v.*, c.name as category_name, c.type as category_type FROM vehicles v LEFT JOIN categories c ON v.category_id = c.id WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($type)) {
            $query .= " AND c.type = ?";
            $params[] = $type;
            $types .= "s";
        }

        if ($category > 0) {
            $query .= " AND v.category_id = ?";
            $params[] = $category;
            $types .= "i";
        }

        $query .= " ORDER BY v.created_at DESC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0):
            while ($vehicle = $result->fetch_assoc()):
                $badgeClass = getStatusBadgeClass($vehicle['status']);
        ?>
        <div class="vehicle-card card">
            <?php if (!empty($vehicle['image'])): ?>
                <img src="uploads/<?php echo sanitize($vehicle['image']); ?>" alt="<?php echo sanitize($vehicle['name']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 200px; background: #eee; display: flex; align-items: center; justify-content: center;">No Image</div>
            <?php endif; ?>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                    <h3 style="margin: 0;"><?php echo sanitize($vehicle['name']); ?></h3>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst(sanitize($vehicle['status'])); ?></span>
                </div>
                <p style="color: #666; margin-bottom: 1rem;"><?php echo sanitize($vehicle['brand']); ?></p>
                <p style="font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem;"><?php echo formatCurrency($vehicle['price_per_day']); ?> <small style="font-weight: normal; font-size: 0.9rem;">/ day</small></p>
                <a href="vehicle-details.php?id=<?php echo sanitize($vehicle['id']); ?>" class="btn btn-primary" style="display: block; text-align: center;">View Details</a>
            </div>
        </div>
        <?php 
            endwhile;
        else:
        ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                <h3>No vehicles found matching your criteria.</h3>
            </div>
        <?php 
        endif;
        $stmt->close(); 
        ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
