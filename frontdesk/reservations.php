<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is frontdesk
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'frontdesk') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Reservations';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_reservation'])) {
        // Create new reservation
        $guestId = $_POST['guest_id'];
        $roomId = $_POST['room_id'];
        $checkIn = $_POST['check_in'];
        $checkOut = $_POST['check_out'];
        $totalAmount = $_POST['total_amount'];
        $status = 'confirmed';
        
        $stmt = $conn->prepare("INSERT INTO bookings (guest_id, room_id, check_in_date, check_out_date, total_amount, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissds", $guestId, $roomId, $checkIn, $checkOut, $totalAmount, $status);
        
        if ($stmt->execute()) {
            // Update room status to occupied
            $updateRoom = $conn->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
            $updateRoom->bind_param("i", $roomId);
            $updateRoom->execute();
            $updateRoom->close();
            
            $success = "Reservation created successfully!";
        } else {
            $error = "Error creating reservation: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['cancel_reservation'])) {
        // Cancel reservation
        $bookingId = $_POST['booking_id'];
        
        // Get room ID before canceling
        $getRoomStmt = $conn->prepare("SELECT room_id FROM bookings WHERE id = ?");
        $getRoomStmt->bind_param("i", $bookingId);
        $getRoomStmt->execute();
        $roomResult = $getRoomStmt->get_result();
        $roomData = $roomResult->fetch_assoc();
        $getRoomStmt->close();
        
        $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $bookingId);
        
        if ($stmt->execute()) {
            // Update room status back to available
            if ($roomData) {
                $updateRoom = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
                $updateRoom->bind_param("i", $roomData['room_id']);
                $updateRoom->execute();
                $updateRoom->close();
            }
            
            $success = "Reservation cancelled successfully!";
        } else {
            $error = "Error cancelling reservation: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['check_in'])) {
        // Check in guest
        $bookingId = $_POST['booking_id'];
        
        $stmt = $conn->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?");
        $stmt->bind_param("i", $bookingId);
        
        if ($stmt->execute()) {
            $success = "Guest checked in successfully!";
        } else {
            $error = "Error checking in guest: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['check_out'])) {
        // Check out guest
        $bookingId = $_POST['booking_id'];
        
        // Get room ID before checking out
        $getRoomStmt = $conn->prepare("SELECT room_id FROM bookings WHERE id = ?");
        $getRoomStmt->bind_param("i", $bookingId);
        $getRoomStmt->execute();
        $roomResult = $getRoomStmt->get_result();
        $roomData = $roomResult->fetch_assoc();
        $getRoomStmt->close();
        
        $stmt = $conn->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
        $stmt->bind_param("i", $bookingId);
        
        if ($stmt->execute()) {
            // Update room status to needs cleaning
            if ($roomData) {
                $updateRoom = $conn->prepare("UPDATE rooms SET status = 'maintenance' WHERE id = ?");
                $updateRoom->bind_param("i", $roomData['room_id']);
                $updateRoom->execute();
                $updateRoom->close();
            }
            
            $success = "Guest checked out successfully!";
        } else {
            $error = "Error checking out guest: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get all reservations with guest and room details
$reservations = $conn->query("
    SELECT b.*, 
           CONCAT(g.first_name, ' ', g.last_name) as guest_name,
           g.email as guest_email,
           g.phone as guest_phone,
           r.room_number,
           r.room_type,
           r.price_per_night
    FROM bookings b 
    JOIN guests g ON b.guest_id = g.id 
    JOIN rooms r ON b.room_id = r.id 
    ORDER BY b.created_at DESC
");

// Get available rooms for new reservations
$availableRooms = $conn->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY room_number");

// Get all guests for new reservations
$guests = $conn->query("SELECT * FROM guests ORDER BY first_name, last_name");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: <?php echo APP_THEME_COLOR; ?>;
        }
        .sidebar {
            min-height: 100vh;
            background-color: var(--primary-color);
        }
        .sidebar .nav-link {
            color: white;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .main-content {
            margin-left: 0;
        }
        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px;
            }
        }
        .status-badge {
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="col-md-2 sidebar position-fixed">
        <div class="p-3">
            <h4 class="text-white"><?php echo APP_NAME; ?></h4>
            <ul class="nav flex-column mt-4">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="reservations.php">
                        <i class="bi bi-calendar-check"></i> Reservations
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person"></i> <?php echo $_SESSION['first_name']; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-md-10 main-content">
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Reservations</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReservationModal">
                    <i class="bi bi-plus-circle"></i> New Reservation
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($reservation = $reservations->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $reservation['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($reservation['guest_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($reservation['guest_email']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo $reservation['room_number']; ?></strong><br>
                                            <small class="text-muted"><?php echo ucfirst($reservation['room_type']); ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($reservation['check_in_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($reservation['check_out_date'])); ?></td>
                                        <td>$<?php echo number_format($reservation['total_amount'], 2); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch($reservation['status']) {
                                                case 'confirmed': $statusClass = 'bg-info'; break;
                                                case 'checked_in': $statusClass = 'bg-success'; break;
                                                case 'checked_out': $statusClass = 'bg-secondary'; break;
                                                case 'cancelled': $statusClass = 'bg-danger'; break;
                                                default: $statusClass = 'bg-primary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?> status-badge">
                                                <?php echo ucfirst(str_replace('_', ' ', $reservation['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($reservation['status'] === 'confirmed'): ?>
                                                <button class="btn btn-sm btn-success check-in-btn" 
                                                        data-booking-id="<?php echo $reservation['id']; ?>"
                                                        data-guest-name="<?php echo htmlspecialchars($reservation['guest_name']); ?>">
                                                    <i class="bi bi-box-arrow-in-right"></i> Check In
                                                </button>
                                                <button class="btn btn-sm btn-danger cancel-btn" 
                                                        data-booking-id="<?php echo $reservation['id']; ?>"
                                                        data-guest-name="<?php echo htmlspecialchars($reservation['guest_name']); ?>">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            <?php elseif ($reservation['status'] === 'checked_in'): ?>
                                                <button class="btn btn-sm btn-warning check-out-btn" 
                                                        data-booking-id="<?php echo $reservation['id']; ?>"
                                                        data-guest-name="<?php echo htmlspecialchars($reservation['guest_name']); ?>">
                                                    <i class="bi bi-box-arrow-right"></i> Check Out
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Reservation Modal -->
    <div class="modal fade" id="createReservationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">New Reservation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="guest_id" class="form-label">Guest</label>
                                <select class="form-select" id="guest_id" name="guest_id" required>
                                    <option value="">Select Guest</option>
                                    <?php 
                                    $guests->data_seek(0);
                                    while ($guest = $guests->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $guest['id']; ?>">
                                            <?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room_id" class="form-label">Room</label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">Select Room</option>
                                    <?php 
                                    $availableRooms->data_seek(0);
                                    while ($room = $availableRooms->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $room['id']; ?>" data-price="<?php echo $room['price_per_night']; ?>">
                                            <?php echo $room['room_number']; ?> - <?php echo ucfirst($room['room_type']); ?> ($<?php echo $room['price_per_night']; ?>/night)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="check_in" class="form-label">Check-in Date</label>
                                <input type="date" class="form-control" id="check_in" name="check_in" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="check_out" class="form-label">Check-out Date</label>
                                <input type="date" class="form-control" id="check_out" name="check_out" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="total_amount" class="form-label">Total Amount</label>
                            <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" required readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="create_reservation">Create Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Check In Modal -->
    <div class="modal fade" id="checkInModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="booking_id" id="checkin_booking_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Check In</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to check in <strong id="checkin_guest_name"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" name="check_in">Check In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Check Out Modal -->
    <div class="modal fade" id="checkOutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="booking_id" id="checkout_booking_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Check Out</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to check out <strong id="checkout_guest_name"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" name="check_out">Check Out</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Reservation Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="booking_id" id="cancel_booking_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel Reservation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to cancel the reservation for <strong id="cancel_guest_name"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger" name="cancel_reservation">Cancel Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate total amount based on room price and dates
        function calculateTotal() {
            const roomSelect = document.getElementById('room_id');
            const checkIn = document.getElementById('check_in');
            const checkOut = document.getElementById('check_out');
            const totalAmount = document.getElementById('total_amount');
            
            if (roomSelect.value && checkIn.value && checkOut.value) {
                const selectedOption = roomSelect.options[roomSelect.selectedIndex];
                const pricePerNight = parseFloat(selectedOption.dataset.price);
                
                const checkInDate = new Date(checkIn.value);
                const checkOutDate = new Date(checkOut.value);
                const timeDiff = checkOutDate.getTime() - checkInDate.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (nights > 0) {
                    totalAmount.value = (pricePerNight * nights).toFixed(2);
                } else {
                    totalAmount.value = '';
                }
            }
        }
        
        document.getElementById('room_id').addEventListener('change', calculateTotal);
        document.getElementById('check_in').addEventListener('change', calculateTotal);
        document.getElementById('check_out').addEventListener('change', calculateTotal);
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('check_in').min = today;
        document.getElementById('check_out').min = today;
        
        // Handle check in button clicks
        document.querySelectorAll('.check-in-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('checkInModal'));
                document.getElementById('checkin_booking_id').value = this.dataset.bookingId;
                document.getElementById('checkin_guest_name').textContent = this.dataset.guestName;
                modal.show();
            });
        });
        
        // Handle check out button clicks
        document.querySelectorAll('.check-out-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('checkOutModal'));
                document.getElementById('checkout_booking_id').value = this.dataset.bookingId;
                document.getElementById('checkout_guest_name').textContent = this.dataset.guestName;
                modal.show();
            });
        });
        
        // Handle cancel button clicks
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
                document.getElementById('cancel_booking_id').value = this.dataset.bookingId;
                document.getElementById('cancel_guest_name').textContent = this.dataset.guestName;
                modal.show();
            });
        });
    </script>
</body>
</html>