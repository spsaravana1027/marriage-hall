<?php
require_once '../includes/auth_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit();
    }

    try {
        // Verify booking belongs to user and is eligible for payment
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_id = ? AND user_id = ? AND status IN ('confirmed', 'processing')");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch();

        if ($booking) {
            // Update payment status only (keep status as processing for admin confirmation)
            $update = $pdo->prepare("UPDATE bookings SET payment_status = 'paid' WHERE booking_id = ?");
            if ($update->execute([$booking_id])) {
                echo json_encode(['success' => true, 'message' => 'Payment successful!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking not found or not eligible for payment']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
