<?php
/**
 * Shared Utility Functions
 * Common helper functions used throughout the application.
 */

/**
 * Sanitize output to prevent XSS attacks
 * @param string $str - The string to sanitize
 * @return string - Sanitized string
 */
function sanitize($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 * @param string $url - The URL to redirect to
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Format a number as Nepali Rupees
 * @param float $amount
 * @return string - Formatted currency string
 */
function formatCurrency($amount) {
    return 'Rs. ' . number_format((float)$amount, 2);
}

/**
 * Format a date string nicely
 * @param string $date - Date string (MySQL format)
 * @param string $format - Output format (default: 'd M Y')
 * @return string
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format a datetime string
 * @param string $datetime
 * @return string
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return 'N/A';
    return date('d M Y, h:i A', strtotime($datetime));
}

/**
 * Generate a unique transaction code
 * @return string - e.g., "TXN-20240815-A3F8K2"
 */
function generateTransactionCode() {
    return 'TXN-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

/**
 * Calculate rental days between two dates
 * @param string $startDate
 * @param string $endDate
 * @return int - Number of days
 */
function calculateRentalDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $diff = $start->diff($end);
    return max(1, $diff->days); // Minimum 1 day
}

/**
 * Handle image file upload
 * @param array $file - The $_FILES array element
 * @param string $targetDir - Directory to save the file
 * @return string|false - Filename on success, false on failure
 */
function uploadImage($file, $targetDir) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }

    // Allowed file types
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }

    // Max file size: 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'vehicle_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $targetPath = rtrim($targetDir, '/\\') . '/' . $filename;

    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }

    return false;
}

/**
 * Get CSS class for status badges
 * @param string $status
 * @return string - CSS class name
 */
function getStatusBadgeClass($status) {
    $map = [
        // Vehicle statuses
        'available'   => 'badge-success',
        'rented'      => 'badge-warning',
        'maintenance' => 'badge-info',
        'unavailable' => 'badge-danger',
        // Booking statuses
        'pending'     => 'badge-warning',
        'confirmed'   => 'badge-info',
        'completed'   => 'badge-success',
        'cancelled'   => 'badge-danger',
        // Payment statuses
        'unpaid'      => 'badge-warning',
        'paid'        => 'badge-success',
        'failed'      => 'badge-danger',
        'refunded'    => 'badge-info',
        // Transaction statuses
        'success'     => 'badge-success',
        // User statuses
        'active'      => 'badge-success',
        'disabled'    => 'badge-danger',
    ];
    return $map[strtolower($status)] ?? 'badge-secondary';
}

/**
 * Get the vehicle image URL or a placeholder
 * @param string|null $image - The image filename
 * @return string - Full URL to the image
 */
function getVehicleImage($image) {
    if ($image && file_exists(UPLOAD_PATH . $image)) {
        return UPLOAD_URL . $image;
    }
    // Return a placeholder if no image exists
    return SITE_URL . '/images/no-vehicle.png';
}

/**
 * Truncate a string to a specified length
 * @param string $str
 * @param int $length
 * @return string
 */
function truncateText($str, $length = 100) {
    if (strlen($str) <= $length) return $str;
    return substr($str, 0, $length) . '...';
}
