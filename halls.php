<?php
require_once 'includes/auth_functions.php';

$hall_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error_msg = '';
$success_msg = '';

if (isset($_GET['error']) && $_GET['error'] === 'double_booking') {
    $error_msg = 'That slot is already booked for the selected date. Please choose a different date or slot.';
}

// Fetch all active slots
$slots = [];
try {
    $slots = $pdo->query("SELECT * FROM slots WHERE status='active' ORDER BY id ASC")->fetchAll();
} catch (Exception $e) {
}

// Month filter
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
// Validate format
if (!preg_match('/^\d{4}-\d{2}$/', $selected_month)) $selected_month = date('Y-m');
$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// ===== INLINE AJAX HANDLER - returns ONLY schedule HTML =====
if (isset($_GET['ajax']) && $hall_id > 0 && isset($pdo)) {
    ob_start();
    $booked_dates_aj = [];
    try {
        $s = $pdo->prepare("
            SELECT b.event_date, b.is_full_day, s.name AS slot_name, b.event_name, b.status, u.name AS user_name
            FROM bookings b JOIN users u ON b.user_id=u.id
            LEFT JOIN slots s ON b.slot_id=s.id
            WHERE b.hall_id=? AND b.event_date>=? AND b.event_date<=? AND b.status != 'cancelled'
            ORDER BY b.event_date ASC
        ");
        $s->execute([$hall_id, $month_start, $month_end]);
        $booked_dates_aj = $s->fetchAll();
    } catch (Exception $e) {
    }
    if (!empty($booked_dates_aj)):
        echo '<div class="table-responsive"><table class="occupancy-table"><thead><tr><th>Date</th><th>Event</th><th>Slot</th><th>Booked By</th><th>Status</th></tr></thead><tbody>';
        foreach ($booked_dates_aj as $bk):
            $slotLabel = $bk['is_full_day'] ? '<span class="badge badge-primary">Full Day</span>' : '<span class="badge badge-info">' . htmlspecialchars($bk['slot_name'] ?? '-') . '</span>';
            $statusLabel = '<span class="badge badge-' . ($bk['status'] === 'confirmed' ? 'success' : 'warning') . '">' . ucfirst($bk['status']) . '</span>';
            echo '<tr><td><strong>' . date('d M Y (D)', strtotime($bk['event_date'])) . '</strong></td><td>' . htmlspecialchars($bk['event_name'] ?? '-') . '</td><td>' . $slotLabel . '</td><td style="color:#64748b;">' . htmlspecialchars(substr($bk['user_name'], 0, 1) . '***') . '</td><td>' . $statusLabel . '</td></tr>';
        endforeach;
        echo '</tbody></table></div>';
    else:
        echo '<div style="text-align:center;padding:3rem;"><i class="fas fa-calendar-check" style="font-size:2.5rem;margin-bottom:1rem;color:#10b981;display:block;"></i><p style="font-size:1rem;font-weight:600;color:#10b981;">Fully Available</p><p style="font-size:0.875rem;color:#94a3b8;">No bookings this month. All slots are free!</p></div>';
    endif;
    echo ob_get_clean();
    exit();
}

// ===================================================
//  MODE 1: HALL DETAIL + BOOKING FORM
// ===================================================
if ($hall_id > 0) {
    $current_hall = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM halls WHERE id = ?");
        $stmt->execute([$hall_id]);
        $current_hall = $stmt->fetch();
    } catch (Exception $e) {
    }

    if (!$current_hall) {
        header('Location: halls.php');
        exit();
    }


    // Confirmed bookings for this hall in selected month
    $booked_dates = [];
    try {
        $bstmt = $pdo->prepare("
            SELECT b.event_date, b.is_full_day, s.name AS slot_name, b.event_name, b.status, u.name AS user_name
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            LEFT JOIN slots s ON b.slot_id = s.id
            WHERE b.hall_id = ? AND b.event_date >= ? AND b.event_date <= ? AND b.status != 'cancelled'
            ORDER BY b.event_date ASC
        ");
        $bstmt->execute([$hall_id, $month_start, $month_end]);
        $booked_dates = $bstmt->fetchAll();
    } catch (Exception $e) {
    }


    // User info prefill
    $user_name = $user_phone = $user_email = '';
    if (isLoggedIn()) {
        try {
            $u = $pdo->prepare("SELECT name, phone, email FROM users WHERE id = ?");
            $u->execute([$_SESSION['user_id']]);
            $info = $u->fetch();
            if ($info) {
                $user_name  = $info['name'];
                $user_phone = $info['phone'];
                $user_email = $info['email'];
            }
        } catch (Exception $e) {
        }
    }

    // Parse specialties/amenities from facilities field
    $facilities = [];
    if (!empty($current_hall['facilities'])) {
        $facilities = array_map('trim', explode(',', $current_hall['facilities']));
    }
}

// ===================================================
//  MODE 2: HALL GALLERY LISTING
// ===================================================
else {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $location_filter = isset($_GET['location']) ? trim($_GET['location']) : '';
    $capacity_filter = isset($_GET['capacity']) ? (int)$_GET['capacity'] : 0;

    $query = "SELECT * FROM halls WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND (name LIKE ? OR description LIKE ? OR location LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($location_filter) {
        $query .= " AND location = ?";
        $params[] = $location_filter;
    }
    if ($capacity_filter > 0) {
        $query .= " AND capacity >= ?";
        $params[] = $capacity_filter;
    }

    $query .= " ORDER BY name ASC";

    $all_halls = [];
    $locations = [];
    try {
        $halls_stmt = $pdo->prepare($query);
        $halls_stmt->execute($params);
        $all_halls = $halls_stmt->fetchAll();
        $locations = $pdo->query("SELECT DISTINCT location FROM halls WHERE location != '' ORDER BY location ASC")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
    }

    // Calendar-view bookings (next 30 days across all halls)
    $today = date('Y-m-d');
    $next30 = date('Y-m-d', strtotime('+30 days'));
    $global_bookings = [];
    try {
        $g = $pdo->prepare("
            SELECT b.event_date, h.name AS hall_name, s.name AS slot_name, b.is_full_day, b.event_name
            FROM bookings b
            JOIN halls h ON b.hall_id = h.id
            LEFT JOIN slots s ON b.slot_id = s.id
            WHERE b.event_date >= ? AND b.event_date <= ? AND b.status = 'confirmed'
            ORDER BY b.event_date ASC, h.name ASC
        ");
        $g->execute([$today, $next30]);
        $global_bookings = $g->fetchAll();
    } catch (Exception $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hall_id > 0 ? htmlspecialchars($current_hall['name']) . ' - Book Now' : 'Browse Halls'; ?> | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding-top: 75px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2.5rem;
            align-items: start;
        }

        @media(max-width:1100px) {
            .detail-grid {
                grid-template-columns: 1fr 350px;
                gap: 1.5rem;
            }
        }

        @media(max-width:992px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        .amenity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        @media(max-width:576px) {
            .amenity-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .slot-choice {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.75rem;
        }

        .slot-btn {
            width: 100%;
            padding: 0.85rem;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            background: white;
            cursor: pointer;
            text-align: center;
            transition: var(--transition);
            font-size: 0.85rem;
            font-weight: 600;
        }

        .slot-btn:hover,
        .slot-btn.active {
            border-color: var(--primary);
            background: var(--primary-light);
            color: var(--primary);
        }

        .slot-btn.active {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.15);
        }

        .price-breakdown {
            background: #f8fafc;
            border-radius: var(--radius);
            padding: 1.25rem;
            border: 1px solid var(--border);
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            font-size: 0.875rem;
        }

        .price-row.total {
            font-weight: 700;
            font-size: 1rem;
            border-top: 2px solid var(--border);
            padding-top: 0.75rem;
            margin-top: 0.25rem;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .month-nav a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .month-nav a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
    </style>
</head>

<body>
    <!-- SHARED NAVBAR -->
    <?php include 'includes/navbar.php'; ?>

    <?php if ($hall_id > 0): // ===== HALL DETAIL VIEW ===== 
    ?>
        <div class="container" style="padding-top:2rem;padding-bottom:4rem;">
            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:0.5rem;color:var(--gray);font-size:0.875rem;margin-bottom:2rem;">
                <a href="halls.php" style="color:var(--primary);">Halls</a>
                <i class="fas fa-chevron-right" style="font-size:0.65rem;"></i>
                <span><?php echo htmlspecialchars($current_hall['name']); ?></span>
            </div>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="detail-grid">
                <!-- LEFT: HALL INFO -->
                <div>
                    <!-- Hero Image -->
                    <div class="hall-detail-hero">
                        <?php if ($current_hall['main_image']): ?>
                            <img src="assets/images/halls/<?php echo htmlspecialchars($current_hall['main_image']); ?>" alt="<?php echo htmlspecialchars($current_hall['name']); ?>">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--gradient-hero);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-building-columns" style="font-size:5rem;color:rgba(255,255,255,0.15);"></i>
                            </div>
                        <?php endif; ?>
                        <div class="hall-detail-overlay">
                            <div>
                                <span class="badge badge-success" style="margin-bottom:0.75rem;"><i class="fas fa-circle" style="font-size:0.45rem;"></i> Available for Booking</span>
                                <h1 style="color:white;font-size:2.2rem;"><?php echo htmlspecialchars($current_hall['name']); ?></h1>
                                <div style="display:flex;gap:1.5rem;color:rgba(255,255,255,0.8);font-size:0.9rem;margin-top:0.5rem;flex-wrap:wrap;">
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($current_hall['location']); ?></span>
                                    <span><i class="fas fa-users"></i> Capacity: <?php echo number_format($current_hall['capacity']); ?> guests</span>
                                    <?php if ($current_hall['price_per_day'] > 0): ?>
                                        <span><i class="fas fa-tag"></i> Rs. <?php echo number_format($current_hall['price_per_day']); ?>/day</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="card" style="margin-top:1.5rem;padding:1.75rem;border-radius:var(--radius-lg);">
                        <h3 style="margin-bottom:1rem;">About This Hall</h3>
                        <p style="color:var(--gray);line-height:1.8;"><?php echo nl2br(htmlspecialchars($current_hall['description'] ?? 'Premium hall available for all types of events including weddings, receptions, birthday parties, corporate events, and more.')); ?></p>
                    </div>

                    <!-- Amenities / Specialties -->
                    <?php if (!empty($facilities)): ?>
                        <div class="card" style="margin-top:1.25rem;padding:1.75rem;border-radius:var(--radius-lg);">
                            <h3 style="margin-bottom:1rem;"><i class="fas fa-star" style="color:var(--accent);"></i> Hall Specialties & Amenities</h3>
                            <div class="amenity-grid">
                                <?php
                                $amenity_icons = [
                                    'AC' => 'fas fa-snowflake',
                                    'Air Conditioning' => 'fas fa-snowflake',
                                    'Parking' => 'fas fa-parking',
                                    'Catering' => 'fas fa-utensils',
                                    'Stage' => 'fas fa-theater-masks',
                                    'Generator' => 'fas fa-bolt',
                                    'WiFi' => 'fas fa-wifi',
                                    'Music' => 'fas fa-music',
                                    'Decoration' => 'fas fa-flower',
                                    'CCTV' => 'fas fa-camera',
                                    'Kitchen' => 'fas fa-kitchen-set',
                                    'Restrooms' => 'fas fa-restroom',
                                    'Projector' => 'fas fa-film',
                                    'Lift' => 'fas fa-elevator',
                                ];
                                foreach ($facilities as $fac):
                                    $icon = 'fas fa-check-circle';
                                    foreach ($amenity_icons as $key => $ico) {
                                        if (stripos($fac, $key) !== false) {
                                            $icon = $ico;
                                            break;
                                        }
                                    }
                                ?>
                                    <div class="hall-amenity">
                                        <i class="<?php echo $icon; ?>"></i>
                                        <span><?php echo htmlspecialchars($fac); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>


                    <!-- Occupancy Schedule - PHP Server-side rendered -->
                    <?php
                    $prev_m = date('Y-m', strtotime($selected_month . '-01 -1 month'));
                    $next_m = date('Y-m', strtotime($selected_month . '-01 +1 month'));
                    $month_label = date('F Y', strtotime($selected_month . '-01'));
                    ?>
                    <div class="card" id="booking-schedule" style="margin-top:1.25rem;padding:1.75rem;border-radius:var(--radius-lg);scroll-margin-top:90px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
                            <h3><i class="fas fa-calendar-alt" style="color:var(--primary);"></i> Booking Schedule</h3>
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <a href="?id=<?php echo $hall_id; ?>&month=<?php echo $prev_m; ?>#booking-schedule" style="width:34px;height:34px;border-radius:50%;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;text-decoration:none;background:white;transition:var(--transition);" onmouseover="this.style.background='var(--primary-light)'" onmouseout="this.style.background='white'">
                                    <i class="fas fa-chevron-left" style="font-size:0.8rem;color:var(--primary);"></i>
                                </a>
                                <strong style="font-size:0.95rem;min-width:110px;text-align:center;"><?php echo $month_label; ?></strong>
                                <a href="?id=<?php echo $hall_id; ?>&month=<?php echo $next_m; ?>#booking-schedule" style="width:34px;height:34px;border-radius:50%;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;text-decoration:none;background:white;transition:var(--transition);" onmouseover="this.style.background='var(--primary-light)'" onmouseout="this.style.background='white'">
                                    <i class="fas fa-chevron-right" style="font-size:0.8rem;color:var(--primary);"></i>
                                </a>
                            </div>
                        </div>

                    <?php if (!empty($booked_dates)): ?>
                        <div style="overflow-x:auto;">
                            <table class="occupancy-table">
                                <thead>
                                    <tr><th>Date</th><th>Event</th><th>Slot</th><th>Booked By</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($booked_dates as $bk): ?>
                                    <tr>
                                        <td><strong><?php echo date('d M Y (D)', strtotime($bk['event_date'])); ?></strong></td>
                                        <td><?php echo htmlspecialchars($bk['event_name'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $bk['is_full_day'] ? 'primary' : 'info'; ?>">
                                                <?php echo $bk['is_full_day'] ? 'Full Day' : htmlspecialchars($bk['slot_name'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td style="color:var(--gray);"><?php echo htmlspecialchars(substr($bk['user_name'], 0, 1) . '***'); ?></td>
                                        <td><span class="badge badge-<?php echo $bk['status'] === 'confirmed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($bk['status']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center;padding:3rem;">
                            <i class="fas fa-calendar-check" style="font-size:2.5rem;margin-bottom:1rem;color:#10b981;display:block;"></i>
                            <p style="font-size:1rem;font-weight:600;color:#10b981;">Fully Available</p>
                            <p style="font-size:0.875rem;color:#94a3b8;">No bookings this month. All slots are free!</p>
                        </div>
                    <?php endif; ?>
                </div>



                </div>

                <!-- RIGHT: BOOKING FORM -->
                <div>
                    <div class="booking-form-card" style="position:sticky;top:90px;">
                        <div class="booking-form-header">
                            <h3 style="color:white;margin-bottom:0.25rem;"><i class="fas fa-calendar-plus"></i> Book This Hall</h3>
                            <p style="color:rgba(255,255,255,0.8);font-size:0.875rem;margin:0;">Fill the form below to reserve</p>
                        </div>

                        <?php if (!isLoggedIn()): ?>
                            <div class="booking-form-body" style="text-align:center;">
                                <div style="width:70px;height:70px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                                    <i class="fas fa-user-lock" style="font-size:1.75rem;color:var(--primary);"></i>
                                </div>
                                <h4>Login Required</h4>
                                <p style="color:var(--gray);font-size:0.875rem;margin:0.75rem 0 1.5rem;">Please login to book this hall.</p>
                                <a href="login.php" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Login to Book</a>
                                <p style="margin-top:1rem;font-size:0.8rem;color:var(--gray);">New user? <a href="register.php" style="color:var(--primary);">Register free</a></p>
                            </div>
                        <?php else: ?>
                            <div class="booking-form-body">
                                <form id="bookingForm" action="actions/book_hall.php" method="POST">
                                    <input type="hidden" name="hall_id" value="<?php echo $hall_id; ?>">

                                    <div class="form-group">
                                        <label><i class="fas fa-tag"></i> Event Name</label>
                                        <input type="text" name="event_name" class="form-control" placeholder="e.g. Wedding Reception" required>
                                    </div>

                                    <div class="form-group">
                                        <label><i class="fas fa-user"></i> Your Name</label>
                                        <input type="text" name="booker_name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" readonly style="background:#f8fafc;">
                                    </div>

                                    <div class="form-group">
                                        <label><i class="fas fa-phone"></i> Contact Number</label>
                                        <input type="tel" name="booker_phone" class="form-control" value="<?php echo htmlspecialchars($user_phone); ?>" readonly style="background:#f8fafc;">
                                    </div>

                                    <div class="form-group">
                                        <label><i class="fas fa-envelope"></i> Email</label>
                                        <input type="email" name="booker_email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly style="background:#f8fafc;">
                                    </div>

                                    <div class="form-group">
                                        <label><i class="fas fa-calendar-alt"></i> Event Date</label>
                                        <input type="date" name="event_date" id="event_date" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                    </div>

                                <!-- Slot Selection - Dropdown -->
                                <div class="form-group">
                                    <label><i class="fas fa-clock"></i> Booking Type</label>
                                    <select id="bookingTypeSelect" name="booking_type_display" class="form-control" onchange="handleSlotChange(this)" required>
                                        <option value="" disabled selected>- Select booking type -</option>
                                        <option value="fullday" data-fullday="1" data-slotid="">
                                            [Full Day] Per Day - All Day
                                        </option>
                                        <?php foreach ($slots as $slot):
                                            $time_label = '';
                                            if ($slot['start_time'] && $slot['end_time']) {
                                                $time_label = ' - ' . date('g:ia', strtotime($slot['start_time'])) . ' - ' . date('g:ia', strtotime($slot['end_time']));
                                            }
                                            $icon = stripos($slot['name'], 'morning') !== false ? '[Morning]' : '[Evening]';
                                        ?>
                                        <option value="slot_<?php echo $slot['id']; ?>" data-fullday="0" data-slotid="<?php echo $slot['id']; ?>">
                                            <?php echo $icon . ' ' . htmlspecialchars($slot['name']) . $time_label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="is_full_day" id="is_full_day" value="0">
                                    <input type="hidden" name="slot_id" id="slot_id_input" value="">
                                </div>

                                    <!-- Price Breakdown -->

                                    <div class="price-row total">
                                        <span>Total Hall Rate</span>
                                        <span id="hallRate">Rs. <?php echo number_format($current_hall['price_per_day']); ?></span>
                                    </div>
                                </div>
                                <input type="hidden" name="advance_amount" id="advance_amount_input" value="0">

                            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:1.25rem;">
                                <i class="fas fa-check-circle"></i> Confirm Booking
                            </button>

                            <p style="text-align:center;font-size:0.75rem;color:var(--gray-light);margin-top:0.75rem;">
                                <i class="fas fa-shield-alt"></i> Your booking is secure and protected
                            </p>
                            </form>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
        </div>

<?php else: // ===== HALL GALLERY LISTING ===== ?>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container reveal" style="position:relative;z-index:1;">
            <div class="section-label"><i class="fas fa-building"></i> Our Venues</div>
            <h1 style="color:white;font-size:2.5rem;margin-bottom:0.5rem;">Browse All <span style="color:var(--secondary);">Halls & Venues</span></h1>
            <p style="color:rgba(255,255,255,0.7);">Find your perfect venue from our collection of premium halls.</p>
        </div>
    </div>

        <!-- <div class="container" style="padding-top:2.5rem;padding-bottom:4rem;display:flex;flex-direction:column;gap:2.5rem;"> -->
        <!-- Search / Filter -->
        <!-- <div class="search-section reveal" style="margin-bottom:2.5rem;">
            <form method="GET">
                <div class="search-grid">
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.8rem;">Search Halls</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Hall name, location..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.8rem;">Location</label>
                        <select name="location" class="form-control">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $location_filter === $loc ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($loc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.8rem;">Min. Capacity</label>
                        <select name="capacity" class="form-control">
                            <option value="0">Any Capacity</option>
                            <?php foreach ([50, 100, 200, 300, 500, 1000] as $cap): ?>
                                <option value="<?php echo $cap; ?>" <?php echo $capacity_filter === $cap ? 'selected' : ''; ?>>
                                    <?php echo $cap; ?>+ guests
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:0.5rem;align-items:flex-end;">
                        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;height:46px;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if ($search || $location_filter || $capacity_filter): ?>
                            <a href="halls.php" class="btn btn-outline" style="height:46px;justify-content:center;"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div> -->

        <!-- Hall Grid -->
        <div class="container" style="padding-top:2rem;padding-bottom:4rem;display:flex;flex-direction:row;gap:2.5rem;">
            <div>
                <?php if (!empty($all_halls)): ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:0.5rem;">
                        <p style="color:var(--gray);font-size:0.875rem;"><strong><?php echo count($all_halls); ?></strong> halls found</p>
                    </div>

                    <div class="halls-grid stagger-children">
                        <?php foreach ($all_halls as $hall): ?>
                            <div class="hall-card glass-card reveal">
                                <div class="hall-card-img">
                                    <?php if ($hall['main_image']): ?>
                                        <img src="assets/images/halls/<?php echo htmlspecialchars($hall['main_image']); ?>" alt="<?php echo htmlspecialchars($hall['name']); ?>">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;background:var(--gradient-hero);display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-building-columns" style="font-size:3rem;color:rgba(255,255,255,0.25);"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="hall-price">Rs. <?php echo number_format($hall['price_per_day']); ?>/day</div>
                                    <div class="hall-badge"><span class="badge badge-success"><i class="fas fa-circle" style="font-size:0.45rem;"></i> Available</span></div>
                                </div>
                                <div class="hall-card-body">
                                    <h3 class="hall-card-title"><?php echo htmlspecialchars($hall['name']); ?></h3>
                                    <div class="hall-card-meta">
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hall['location']); ?></span>
                                        <span><i class="fas fa-users"></i> <?php echo number_format($hall['capacity']); ?></span>
                                    </div>
                                    <?php if ($hall['description']): ?>
                                        <p style="font-size:0.8rem;color:var(--gray);margin-bottom:1.25rem;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?php echo htmlspecialchars($hall['description']); ?></p>
                                    <?php endif; ?>

                                    <?php if ($hall['facilities']): ?>
                                        <div style="display:flex;flex-wrap:wrap;gap:0.35rem;margin-bottom:1.25rem;">
                                            <?php
                                            $facs = array_slice(array_map('trim', explode(',', $hall['facilities'])), 0, 3);
                                            foreach ($facs as $f): ?>
                                                <span style="background:#f1f5f9;color:var(--gray);font-size:0.7rem;padding:0.2rem 0.6rem;border-radius:20px;"><?php echo htmlspecialchars($f); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <a href="halls.php?id=<?php echo $hall['id']; ?>" class="btn btn-primary" style="width:100%;justify-content:center;">
                                        View & Book <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

        <?php else: ?>
            <div style="text-align:center;padding:5rem 2rem;">
                <div style="width:80px;height:80px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                    <i class="fas fa-building" style="font-size:2rem;color:var(--primary);"></i>
                </div>
                <h3 style="margin-bottom:0.5rem;">No Halls Found</h3>
                <p style="color:var(--gray);">Try adjusting your search or filter.</p>
                <a href="halls.php" class="btn btn-primary" style="margin-top:1.5rem;">View All Halls</a>
            </div>
        <?php endif; ?>

        <!-- Upcoming Occupancy Schedule -->
        <?php if (!empty($global_bookings)): ?>
        <div style="margin-top:3.5rem;" class="reveal">
            <h3 style="margin-bottom:1.5rem;display:flex;align-items:center;gap:0.75rem;">
                <i class="fas fa-calendar-check" style="color:var(--primary);"></i>
                Upcoming Confirmed Bookings (Next 30 Days)
            </h3>
            <div class="admin-table-card">
                <div style="overflow-x:auto;">
                    <table class="occupancy-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Hall Name</th>
                                <th>Event</th>
                                <th>Slot</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($global_bookings as $gb): ?>
                                <tr>
                                    <td><strong><?php echo date('d M Y (D)', strtotime($gb['event_date'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($gb['hall_name']); ?></td>
                                    <td style="color:var(--gray);"><?php echo htmlspecialchars($gb['event_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $gb['is_full_day'] ? 'primary' : 'info'; ?>">
                                            <?php echo $gb['is_full_day'] ? 'Full Day' : htmlspecialchars($gb['slot_name']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

        <?php include 'includes/footer.php'; ?>
        <?php include 'includes/modals.php'; ?>
        <?php include 'includes/chatbot.php'; ?>

        <script>
            // Reveal on scroll
            const reveal = () => {
                const reveals = document.querySelectorAll('.reveal');
                reveals.forEach(el => {
                    const windowHeight = window.innerHeight;
                    const elementTop = el.getBoundingClientRect().top;
                    const elementVisible = 100;
                    if (elementTop < windowHeight - elementVisible) {
                        el.classList.add('active');
                    }
                });
            };
            window.addEventListener('scroll', reveal);
            // Initial check
            reveal();

            // Navbar scroll
            window.addEventListener('scroll', () => {
                document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
            });

            <?php if ($hall_id > 0): ?>
                // Slot selection logic
                const hallPricePerDay = <?php echo (float)$current_hall['price_per_day']; ?>;
                const hallAdvanceFull = <?php echo (float)($current_hall['advance_amount'] ?? ($current_hall['price_per_day'] * 0.3)); ?>;
                const slotAdvance = hallAdvanceFull * 0.5;

                function handleSlotChange(select) {
                    const opt = select.options[select.selectedIndex];
                    const isFullDay = opt.dataset.fullday === '1';
                    const slotId = opt.dataset.slotid || '';

                    document.getElementById('is_full_day').value = isFullDay ? '1' : '0';
                    document.getElementById('slot_id_input').value = slotId;

            const slotPrice = isFullDay ? hallPricePerDay : hallPricePerDay * 0.55;
            document.getElementById('hallRate').textContent = 'Rs. ' + slotPrice.toLocaleString('en-IN', {maximumFractionDigits:0});
        }

                document.getElementById('bookingForm').addEventListener('submit', function(e) {
                    const select = document.getElementById('bookingTypeSelect');
                    if (!select.value) {
                        e.preventDefault();
                        alert('Please select a booking type.');
                        return;
                    }
                    // Ensure hidden inputs are set from current selection
                    handleSlotChange(select);
                });

                // Date: prevent past dates
                document.getElementById('event_date').addEventListener('change', function() {
                    const selected = new Date(this.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (selected <= today) {
                        alert('Please select a future date for your event.');
                        this.value = '';
                    }
                });
            <?php endif; ?>
        </script>
</body>

</html>