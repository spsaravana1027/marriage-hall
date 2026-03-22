<?php
// Configuration for DB Connection
$host = 'localhost';
$db   = 'hall_allocation';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // For development, we'll show the error. In production, we'd log it.
     // die("Connection failed: " . $e->getMessage());
     
     // Fallback message for now
     $error_msg = "Database connection could not be established. Please ensure MySQL is running and the 'hall_allocation' database exists.";
}

/**
 * Helper to check if a slot is available for a given hall and date.
 * Considers "Full Day" bookings as well.
 */
function isSlotAvailable($pdo, $hall_id, $date, $slot_id, $is_full_day = false) {
    if (!$pdo) return false;
    
    if ($is_full_day) {
        // For full day, no other bookings (slot or full day) should exist for that date
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE hall_id = ? AND event_date = ? AND status != 'cancelled'");
        $stmt->execute([$hall_id, $date]);
    } else {
        // For a specific slot, check if that slot is already booked OR if a full day is booked
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE hall_id = ? AND event_date = ? AND status != 'cancelled' 
            AND (slot_id = ? OR is_full_day = 1)
        ");
        $stmt->execute([$hall_id, $date, $slot_id]);
    }
    
    return $stmt->fetchColumn() == 0;
}

// Fetch global branding & footer settings
$brand_name = 'Sri Lakshmi Residency & Mahal';
$brand_logo = '';
$footer_phone = '+91 98765 43210';
$footer_email = 'slr@gmail.com';
$footer_address = '123, Main Road, Srivilliputhur, Tamil Nadu';
$social_facebook = '#';
$social_instagram = '#';
$social_youtube = '#';
$social_whatsapp = 'https://wa.me/919876543210';
$google_maps_iframe = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3934.8595558505026!2d77.63345247367901!3d9.520929281097514!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3b06dda3211bacff%3A0xb3b5f817bb93c64d!2sSri%20Lakshmi%20Residency%20And%20Mahal!5e0!3m2!1sen!2sin!4v1774067577631!5m2!1sen!2sin';

if (isset($pdo)) {
    try {
        $keys = ['brand_name', 'brand_logo', 'footer_phone', 'footer_email', 'footer_address', 'social_facebook', 'social_instagram', 'social_youtube', 'social_whatsapp', 'google_maps_iframe'];
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        $brand_stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
        $brand_stmt->execute($keys);
        $site_settings = $brand_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $brand_name = $site_settings['brand_name'] ?? $brand_name;
        $brand_logo = $site_settings['brand_logo'] ?? $brand_logo;
        $footer_phone = $site_settings['footer_phone'] ?? $footer_phone;
        $footer_email = $site_settings['footer_email'] ?? $footer_email;
        $footer_address = $site_settings['footer_address'] ?? $footer_address;
        $social_facebook = $site_settings['social_facebook'] ?? $social_facebook;
        $social_instagram = $site_settings['social_instagram'] ?? $social_instagram;
        $social_youtube = $site_settings['social_youtube'] ?? $social_youtube;
        $social_whatsapp = $site_settings['social_whatsapp'] ?? $social_whatsapp;
        $google_maps_iframe = $site_settings['google_maps_iframe'] ?? $google_maps_iframe;
    } catch (Exception $e) {
        // Fallback to defaults already set
    }
}
?>
