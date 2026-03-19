<?php
require_once '../includes/auth_functions.php';
require_once '../includes/send_mail.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';

// ===== HANDLE EDIT BOOKING =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    $id = (int)$_POST['id'];
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $advance = (float)$_POST['advance_amount'];
    $p_status = $_POST['payment_status'];

    try {
        // Fetch old payment status
        $old = $pdo->prepare("SELECT payment_status FROM bookings WHERE id = ?");
        $old->execute([$id]);
        $old_payment = $old->fetchColumn();

        // If payment changed to paid, auto-confirm the booking
        $new_status_sql = ($p_status === 'paid' && $old_payment !== 'paid') ? ", status = 'confirmed'" : "";

        $stmt = $pdo->prepare("UPDATE bookings SET event_name = ?, event_date = ?, advance_amount = ?, payment_status = ? {$new_status_sql} WHERE id = ?");
        if ($stmt->execute([$event_name, $event_date, $advance, $p_status, $id])) {
            $msg = "Booking updated successfully!";

            // Send confirmation mail if payment just became paid
            if ($p_status === 'paid' && $old_payment !== 'paid') {
                $bk = $pdo->prepare("
                    SELECT b.*, h.name AS hall_name, u.name AS user_name, u.email AS user_email, s.name AS slot_name
                    FROM bookings b
                    JOIN halls h ON b.hall_id = h.id
                    JOIN users u ON b.user_id = u.id
                    LEFT JOIN slots s ON b.slot_id = s.id
                    WHERE b.id = ?
                ");
                $bk->execute([$id]);
                $booking = $bk->fetch();
                if ($booking) {
                    $sent = sendBookingConfirmationMail($booking['user_email'], $booking['user_name'], $booking);
                    $msg .= $sent ? ' Confirmation email sent to ' . htmlspecialchars($booking['user_email']) . '.' : ' (Email sending failed.)';
                }
            }
        } else {
            $error = "Failed to update booking.";
        }
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

// ===== HANDLE STATUS ACTIONS =====
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $bk_id  = (int)$_GET['id'];

    $allowed = ['confirm' => 'confirmed', 'cancel' => 'cancelled', 'process' => 'processing', 'pending' => 'pending'];
    if (array_key_exists($action, $allowed)) {
        try {
            $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?")->execute([$allowed[$action], $bk_id]);
            $msg = 'Booking status updated to ' . ucfirst($allowed[$action]) . '.';
        } catch (Exception $e) { $error = 'Failed to update booking.'; }
    }
}

// ===== FILTERS =====
$filter_status = $_GET['status'] ?? '';
$filter_hall   = $_GET['hall_id'] ?? '';
$filter_search = trim($_GET['search'] ?? '');
$filter_payment = $_GET['payment'] ?? '';

$query = "
    SELECT b.*, 
           h.name AS hall_name, h.location AS hall_location,
           u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
           s.name AS slot_name, s.start_time, s.end_time
    FROM bookings b
    JOIN halls h ON b.hall_id = h.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN slots s ON b.slot_id = s.id
    WHERE 1=1
";
$params = [];

if ($filter_status) {
    $query .= " AND b.status = ?";
    $params[] = $filter_status;
}
if ($filter_hall) {
    $query .= " AND b.hall_id = ?";
    $params[] = (int)$filter_hall;
}
if ($filter_payment) {
    $query .= " AND b.payment_status = ?";
    $params[] = $filter_payment;
}
if ($filter_search) {
    $query .= " AND (b.booking_id LIKE ? OR u.name LIKE ? OR h.name LIKE ? OR b.event_name LIKE ?)";
    $params = array_merge($params, ["%$filter_search%", "%$filter_search%", "%$filter_search%", "%$filter_search%"]);
}

$query .= " ORDER BY b.created_at DESC";

$bookings = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {}

// Stats
$stats = ['total'=>0, 'pending'=>0, 'processing'=>0, 'confirmed'=>0, 'cancelled'=>0, 'advance'=>0];
try {
    $s = $pdo->query("SELECT status, COUNT(*) AS cnt, SUM(advance_amount) AS adv FROM bookings GROUP BY status")->fetchAll();
    foreach ($s as $row) {
        $stats[$row['status']] = $row['cnt'];
        $stats['advance'] += $row['adv'];
    }
    $stats['total'] = array_sum([$stats['pending'] ?? 0, $stats['processing'] ?? 0, $stats['confirmed'] ?? 0, $stats['cancelled'] ?? 0]);
} catch (Exception $e) {}

// Halls for filter dropdown
$all_halls = [];
try {
    $all_halls = $pdo->query("SELECT id, name FROM halls ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .filter-bar { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr auto; gap: 0.75rem; }
        .status-select { 
            border: 1px solid transparent; 
            appearance: none; 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 0.8em;
            padding-right: 1.8rem !important;
            transition: all 0.2s;
        }
        .status-select:focus { border-color: var(--primary) !important; outline: none; box-shadow: 0 0 0 3px var(--primary-light); }
        .status-select option { background: white; color: var(--dark); } /* Reset options so they are readable */
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div>
                <div style="font-weight:700;font-size:1rem;">Manage Bookings</div>
                <div style="font-size:0.78rem;color:var(--gray);"><?php echo count($bookings); ?> bookings found</div>
            </div>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
                <?php foreach ([
                    ['Total', $stats['total'], '#7c3aed', '#ede9fe', 'fas fa-list'],
                    ['Pending', $stats['pending'] ?? 0, '#f59e0b', '#fef3c7', 'fas fa-hourglass-half'],
                    ['Processing', $stats['processing'] ?? 0, '#3b82f6', '#dbeafe', 'fas fa-sync fa-spin'],
                    ['Confirmed', $stats['confirmed'] ?? 0, '#10b981', '#d1fae5', 'fas fa-check-circle'],
                    ['Cancelled', $stats['cancelled'] ?? 0, '#ef4444', '#fee2e2', 'fas fa-times-circle'],
                ] as [$lbl, $val, $col, $bg, $ic]): ?>
                    <div class="admin-stat-card">
                        <div class="stat-icon" style="background:<?php echo $bg; ?>;color:<?php echo $col; ?>;width:44px;height:44px;font-size:1.1rem;">
                            <i class="<?php echo $ic; ?>"></i>
                        </div>
                        <div>
                            <div style="font-size:0.7rem;color:var(--gray);text-transform:uppercase;"><?php echo $lbl; ?></div>
                            <div style="font-size:1.2rem;font-weight:800;font-family:'Poppins',sans-serif;"><?php echo $val; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Filters -->
            <div class="search-section" style="margin-bottom:1.5rem;padding:1.25rem;">
                <form method="GET">
                    <div class="filter-bar">
                        <div class="input-icon-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search by ID, user, hall, event..." value="<?php echo htmlspecialchars($filter_search); ?>">
                        </div>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $filter_status==='pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $filter_status==='processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="confirmed" <?php echo $filter_status==='confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $filter_status==='cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <select name="hall_id" class="form-control">
                            <option value="">All Halls</option>
                            <?php foreach ($all_halls as $h): ?>
                                <option value="<?php echo $h['id']; ?>" <?php echo $filter_hall == $h['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($h['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="payment" class="form-control">
                            <option value="">All Payment</option>
                            <option value="paid" <?php echo $filter_payment === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="unpaid" <?php echo $filter_payment === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                        </select>
                        <div style="display:flex;gap:0.5rem;">
                            <button type="submit" class="btn btn-primary" style="height:46px;padding:0 1rem;"><i class="fas fa-filter"></i></button>
                            <a href="manage_bookings.php" class="btn btn-outline" style="height:46px;padding:0 1rem;"><i class="fas fa-times"></i></a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Quick Status Tabs -->
            <div style="display:flex;gap:0.4rem;margin-bottom:1.25rem;flex-wrap:wrap;">
                <?php foreach ([['','All'],['pending','Pending'],['processing','Processing'],['confirmed','Confirmed'],['cancelled','Cancelled']] as [$v,$l]): ?>
                    <a href="manage_bookings.php?status=<?php echo $v; ?>" style="padding:0.4rem 1rem;border-radius:var(--radius-full);font-size:0.78rem;font-weight:600;border:1px solid var(--border);color:var(--gray);transition:var(--transition);<?php echo $filter_status===$v ? 'background:var(--primary);color:white;border-color:var(--primary);' : ''; ?>"><?php echo $l; ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Bookings Table -->
            <div class="admin-table-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Hall</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Slot</th>
                                <th>Advance</th>
                                 <th>Status</th>
                                 <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr><td colspan="9" style="text-align:center;color:var(--gray-light);padding:4rem;">No bookings match your filter.</td></tr>
                            <?php else: foreach ($bookings as $b): ?>
                                <tr>
                                    <td>
                                        <span style="font-weight:700;font-size:0.75rem;color:var(--primary);"><?php echo $b['booking_id']; ?></span>
                                        <div style="font-size:0.68rem;color:var(--gray-light);"><?php echo date('d M y', strtotime($b['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;font-size:0.875rem;"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                        <div style="font-size:0.72rem;color:var(--gray);"><?php echo htmlspecialchars($b['user_phone']); ?></div>
                                        <div style="font-size:0.68rem;color:var(--gray-light);"><?php echo htmlspecialchars($b['user_email']); ?></div>
                                    </td>
                                    <td style="font-size:0.875rem;">
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($b['hall_name']); ?></div>
                                        <div style="font-size:0.72rem;color:var(--gray);"><?php echo htmlspecialchars($b['hall_location']); ?></div>
                                    </td>
                                    <td style="font-size:0.875rem;"><?php echo htmlspecialchars($b['event_name']); ?></td>
                                    <td style="font-size:0.875rem;white-space:nowrap;">
                                        <?php echo date('d M Y', strtotime($b['event_date'])); ?>
                                        <br><span style="font-size:0.7rem;color:var(--gray);"><?php echo date('D', strtotime($b['event_date'])); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $b['is_full_day'] ? 'primary' : 'info'; ?>" style="font-size:0.68rem;">
                                            <?php if ($b['is_full_day']): ?>
                                                Full Day
                                            <?php elseif ($b['slot_name']): ?>
                                                <?php echo htmlspecialchars($b['slot_name']); ?>
                                            <?php else: echo '-'; endif; ?>
                                        </span>
                                    </td>
                                    <td style="font-weight:700;color:var(--primary);font-size:0.9rem;white-space:nowrap;">Rs. <?php echo number_format($b['advance_amount']); ?></td>

                                    <td>
                                        <?php 
                                        $status_styles = [
                                            'pending'    => ['color' => '#92400e', 'bg' => '#fef3c7', 'label' => 'Pending'],
                                            'processing' => ['color' => '#1e40af', 'bg' => '#dbeafe', 'label' => 'Processing'],
                                            'confirmed'  => ['color' => '#065f46', 'bg' => '#d1fae5', 'label' => 'Confirmed'],
                                            'cancelled'  => ['color' => '#991b1b', 'bg' => '#fee2e2', 'label' => 'Cancelled']
                                        ];
                                        $cur_style = $status_styles[$b['status']] ?? $status_styles['pending'];
                                        ?>
                                        <select class="form-control status-select" data-id="<?php echo $b['id']; ?>" 
                                                style="font-size:0.7rem; padding:0.35rem 0.6rem; height:auto; width:auto; min-width:115px; font-weight:700; border-radius:30px; cursor:pointer; 
                                                       background-color:<?php echo $cur_style['bg']; ?>; color:<?php echo $cur_style['color']; ?>; border:1px solid <?php echo $cur_style['color']; ?>30;">
                                            <?php foreach($status_styles as $val => $info): ?>
                                                <option value="<?php echo $val; ?>" <?php echo $b['status'] === $val ? 'selected' : ''; ?>>
                                                    <?php echo $info['label']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                     <td>
                                         <?php $pc = $b['payment_status'] === 'paid' ? 'success' : 'danger'; ?>
                                         <span class="badge badge-<?php echo $pc; ?>" style="font-size: 0.65rem;">
                                             <i class="fas <?php echo $b['payment_status'] === 'paid' ? 'fa-check' : 'fa-clock'; ?>"></i>
                                             <?php echo ucfirst($b['payment_status']); ?>
                                         </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:0.35rem;justify-content:center;">
                                            <button class="btn btn-primary btn-sm" title="Edit Details" 
                                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($b)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        const id = this.getAttribute('data-id');
        const status = this.value;
        const currentParams = new URLSearchParams(window.location.search);
        
        // Map selective values back to action names used in the existing handler
        const actionMap = {
            'pending': 'pending',
            'processing': 'process',
            'confirmed': 'confirm',
            'cancelled': 'cancel'
        };
        
        const action = actionMap[status];
        if (action) {
            if (confirm(`Change booking status to ${status.charAt(0).toUpperCase() + status.slice(1)}?`)) {
                currentParams.set('action', action);
                currentParams.set('id', id);
                window.location.search = currentParams.toString();
            } else {
                // Reset to previous value if cancelled
                window.location.reload();
            }
        }
    });
});
</script>

<!-- Edit Booking Modal -->
<div id="editModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; overflow-y:auto;">
    <div style="background:white; max-width:500px; margin:2rem auto; border-radius:12px; position:relative; padding:0;">
        <div style="padding:1.25rem 1.5rem; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem;">Edit Booking <span id="modal-bk-id" style="color:var(--primary);"></span></h3>
            <button onclick="closeEditModal()" style="background:none; border:none; color:var(--gray); cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" style="padding:1.5rem;">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group" style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.4rem;">Event Name</label>
                <input type="text" name="event_name" id="edit-event" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.4rem;">Event Date</label>
                <input type="date" name="event_date" id="edit-date" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.4rem;">Advance Amount (Rs.)</label>
                <input type="number" name="advance_amount" id="edit-advance" class="form-control" required min="0">
            </div>
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.4rem;">Payment Status</label>
                <select name="payment_status" id="edit-payment" class="form-control">
                    <option value="unpaid">Unpaid</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn btn-outline" style="padding:0.6rem 1.25rem;">Cancel</button>
                <button type="submit" name="update_booking" class="btn btn-primary" style="padding:0.6rem 1.25rem;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(booking) {
    document.getElementById('edit-id').value = booking.id;
    document.getElementById('modal-bk-id').innerText = booking.booking_id;
    document.getElementById('edit-event').value = booking.event_name;
    document.getElementById('edit-date').value = booking.event_date;
    document.getElementById('edit-advance').value = booking.advance_amount;
    document.getElementById('edit-payment').value = booking.payment_status;
    document.getElementById('editModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close on outside click
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    if (event.target == modal) closeEditModal();
}
</script>
</body>
</html>


