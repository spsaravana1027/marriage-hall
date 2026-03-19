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

// Fetch global branding settings
$brand_name = 'Sri Lakshmi Residency & Mahal';
$brand_logo = '';

if (isset($pdo)) {
    try {
        $brand_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('brand_name', 'brand_logo')");
        $site_settings = $brand_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $brand_name = $site_settings['brand_name'] ?? $brand_name;
        $brand_logo = $site_settings['brand_logo'] ?? $brand_logo;
    } catch (Exception $e) {
        // Fallback to defaults already set
    }
}
?>
