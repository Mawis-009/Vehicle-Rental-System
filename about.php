<?php
session_start();
$page_title = 'About Us - Vehicle Rental';
$current_page = 'about';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_once 'includes/header.php';
?>

<div class="page-header" style="background: #f8f9fa; padding: 3rem 0; text-align: center;">
    <div class="container">
        <h1>About Us</h1>
    </div>
</div>

<div class="container about-content" style="padding: 3rem 0; max-width: 800px; margin: 0 auto;">
    <h2>Our Company</h2>
    <p>Welcome to Vehicle Rental System, your number one source for all your rental needs. We're dedicated to providing you the best of vehicles, with a focus on dependability, customer service, and uniqueness.</p>
    
    <h2 style="margin-top: 2rem;">Our Mission</h2>
    <p>Our mission is to simplify the vehicle rental process and provide an unparalleled experience for our customers. We strive to offer a wide range of reliable, well-maintained vehicles to suit every need and budget.</p>

    <h2 style="margin-top: 2rem;">Why Choose Us?</h2>
    <ul>
        <li>Wide selection of two-wheelers and four-wheelers.</li>
        <li>Competitive pricing with no hidden fees.</li>
        <li>Excellent customer support.</li>
        <li>Easy and secure booking process.</li>
    </ul>
</div>

<?php require_once 'includes/footer.php'; ?>
