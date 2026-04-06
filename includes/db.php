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
function isSlotAvailable($pdo, $hall_id, $date, $slot_id, $is_full_day = false, $room_id = null) {
    if (!$pdo) return false;
    
    // For Rooms: Check count against total_rooms
    if ($room_id) {
        $r_stmt = $pdo->prepare("SELECT total_rooms FROM rooms WHERE id = ?");
        $r_stmt->execute([$room_id]);
        $total_allowed = (int)$r_stmt->fetchColumn();

        $query = "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND event_date = ? AND status != 'cancelled'";
        if (!$is_full_day) {
            $query .= " AND (slot_id = ? OR is_full_day = 1)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$room_id, $date, $slot_id]);
        } else {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$room_id, $date]);
        }
        $booked_count = (int)$stmt->fetchColumn();
        return $booked_count < $total_allowed;
    }

    // For Halls: Preserve single-availability logic
    if ($is_full_day) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE hall_id = ? AND event_date = ? AND status != 'cancelled'");
        $stmt->execute([$hall_id, $date]);
    } else {
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
$explore_title = 'Explore Our Grand Hall';
$explore_subtitle = 'Experience Luxury & Tradition in Every Detail';

if (isset($pdo)) {
    try {
        $keys = ['brand_name', 'brand_logo', 'footer_phone', 'footer_email', 'footer_address', 'social_facebook', 'social_instagram', 'social_youtube', 'social_whatsapp', 'google_maps_iframe', 'explore_title', 'explore_subtitle'];
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
        $explore_title = $site_settings['explore_title'] ?? $explore_title;
        $explore_subtitle = $site_settings['explore_subtitle'] ?? $explore_subtitle;
    } catch (Exception $e) {
        // Fallback to defaults already set
    }
}
?>
