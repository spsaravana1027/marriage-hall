<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../halls.php');
    exit();
}

$user_id    = $_SESSION['user_id'];
$hall_id    = (int)($_POST['hall_id'] ?? 0);
$event_name = trim($_POST['event_name'] ?? '');
$event_date = trim($_POST['event_date'] ?? '');
$is_full_day = isset($_POST['is_full_day']) && $_POST['is_full_day'] == '1' ? 1 : 0;
$slot_id    = (!$is_full_day && !empty($_POST['slot_id'])) ? (int)$_POST['slot_id'] : null;
$advance_amount = (float)($_POST['advance_amount'] ?? 0);

// ===== VALIDATION =====
$errors = [];

if (!$hall_id) { $errors[] = 'Invalid hall.'; }
if (empty($event_name)) { $errors[] = 'Event name is required.'; }
if (empty($event_date)) {
    $errors[] = 'Event date is required.';
} elseif (strtotime($event_date) <= strtotime('today')) {
    $errors[] = 'Event date must be in the future.';
}
if (!$is_full_day && !$slot_id) {
    $errors[] = 'Please select a time slot.';
}

if (!empty($errors)) {
    $err_str = urlencode(implode(' ', $errors));
    header("Location: ../halls.php?id=$hall_id&error=" . $err_str);
    exit();
}

// ===== CHECK HALL EXISTS =====
try {
    $hall_check = $pdo->prepare("SELECT id FROM halls WHERE id = ?");
    $hall_check->execute([$hall_id]);
    if (!$hall_check->fetch()) {
        header('Location: ../halls.php?error=invalid_hall');
        exit();
    }
} catch (Exception $e) {
    header('Location: ../halls.php?error=db_error');
    exit();
}

// ===== CHECK SLOT AVAILABILITY =====
if (!isSlotAvailable($pdo, $hall_id, $event_date, $slot_id, $is_full_day)) {
    header("Location: ../halls.php?id=$hall_id&error=double_booking");
    exit();
}

// ===== INSERT BOOKING =====
try {
    $booking_id = 'BK-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    $insert = $pdo->prepare("
        INSERT INTO bookings 
            (booking_id, user_id, hall_id, event_name, event_date, slot_id, is_full_day, advance_amount, status, created_at) 
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $insert->execute([
        $booking_id,
        $user_id,
        $hall_id,
        $event_name,
        $event_date,
        $slot_id,
        $is_full_day,
        $advance_amount
    ]);

    header('Location: ../my_bookings.php?success=1');
    exit();

} catch (PDOException $e) {
    error_log('Booking error: ' . $e->getMessage());
    header("Location: ../halls.php?id=$hall_id&error=db_error");
    exit();
}
?>
