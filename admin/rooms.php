<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Room Management';
require_once '../includes/admin_layout.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_room'])) {
        // Add new room
        $roomNumber = $_POST['room_number'];
        $roomType = $_POST['room_type'];
        $pricePerNight = $_POST['price_per_night'];
        $capacity = $_POST['capacity'];
        $description = $_POST['description'];
        $status = 'available';
        
        $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, price_per_night, capacity, description, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $roomNumber, $roomType, $pricePerNight, $capacity, $description, $status);
        
        if ($stmt->execute()) {
            $success = "Room added successfully!";
        } else {
            $error = "Error adding room: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_room'])) {
        // Update room
        $roomId = $_POST['room_id'];
        $roomNumber = $_POST['room_number'];
        $roomType = $_POST['room_type'];
        $pricePerNight = $_POST['price_per_night'];
        $capacity = $_POST['capacity'];
        $description = $_POST['description'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE rooms SET room_number = ?, room_type = ?, price_per_night = ?, capacity = ?, description = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssdissi", $roomNumber, $roomType, $pricePerNight, $capacity, $description, $status, $roomId);
        
        if ($stmt->execute()) {
            $success = "Room updated successfully!";
        } else {
            $error = "Error updating room: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_room'])) {
        // Delete room
        $roomId = $_POST['room_id'];
        
        // Check if room has active bookings
        $checkBookings = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE room_id = ? AND status IN ('confirmed', 'checked_in')");
        $checkBookings->bind_param("i", $roomId);
        $checkBookings->execute();
        $result = $checkBookings->get_result();
        $bookingCount = $result->fetch_assoc()['count'];
        $checkBookings->close();
        
        if ($bookingCount > 0) {
            $error = "Cannot delete room with active bookings!";
        } else {
            $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->bind_param("i", $roomId);
            
            if ($stmt->execute()) {
                $success = "Room deleted successfully!";
            } else {
                $error = "Error deleting room: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get all rooms
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number");

?>
<?php renderAdminHeader($pageTitle, 'rooms'); ?>
    <style>
        .status-badge {
            font-size: 0.8em;
        }
    </style>
<?php renderAdminPageHeader('Room Management', 'Manage hotel rooms and their availability'); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div></div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="bi bi-plus-circle"></i> Add Room
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
                                    <th>Room Number</th>
                                    <th>Type</th>
                                    <th>Price/Night</th>
                                    <th>Capacity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($room = $rooms->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $room['room_number']; ?></strong></td>
                                        <td><?php echo ucfirst($room['room_type']); ?></td>
                                        <td>$<?php echo number_format($room['price_per_night'], 2); ?></td>
                                        <td><?php echo $room['capacity']; ?> guests</td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch($room['status']) {
                                                case 'available': $statusClass = 'bg-success'; break;
                                                case 'occupied': $statusClass = 'bg-danger'; break;
                                                case 'maintenance': $statusClass = 'bg-warning'; break;
                                                default: $statusClass = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?> status-badge">
                                                <?php echo ucfirst($room['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary edit-room-btn" 
                                                    data-room-id="<?php echo $room['id']; ?>"
                                                    data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>"
                                                    data-room-type="<?php echo $room['room_type']; ?>"
                                                    data-price="<?php echo $room['price_per_night']; ?>"
                                                    data-capacity="<?php echo $room['capacity']; ?>"
                                                    data-description="<?php echo htmlspecialchars($room['description']); ?>"
                                                    data-status="<?php echo $room['status']; ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-room-btn" 
                                                    data-room-id="<?php echo $room['id']; ?>"
                                                    data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
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

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_number" class="form-label">Room Number</label>
                                <input type="text" class="form-control" id="room_number" name="room_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room_type" class="form-label">Room Type</label>
                                <select class="form-select" id="room_type" name="room_type" required>
                                    <option value="">Select Type</option>
                                    <option value="single">Single</option>
                                    <option value="double">Double</option>
                                    <option value="suite">Suite</option>
                                    <option value="deluxe">Deluxe</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price_per_night" class="form-label">Price per Night</label>
                                <input type="number" class="form-control" id="price_per_night" name="price_per_night" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="add_room">Add Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="room_id" id="edit_room_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_room_number" class="form-label">Room Number</label>
                                <input type="text" class="form-control" id="edit_room_number" name="room_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_room_type" class="form-label">Room Type</label>
                                <select class="form-select" id="edit_room_type" name="room_type" required>
                                    <option value="single">Single</option>
                                    <option value="double">Double</option>
                                    <option value="suite">Suite</option>
                                    <option value="deluxe">Deluxe</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_price_per_night" class="form-label">Price per Night</label>
                                <input type="number" class="form-control" id="edit_price_per_night" name="price_per_night" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status" required>
                                    <option value="available">Available</option>
                                    <option value="occupied">Occupied</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="update_room">Update Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Room Modal -->
    <div class="modal fade" id="deleteRoomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="room_id" id="delete_room_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete room <strong id="delete_room_number"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger" name="delete_room">Delete Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit room button clicks
        document.querySelectorAll('.edit-room-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('editRoomModal'));
                document.getElementById('edit_room_id').value = this.dataset.roomId;
                document.getElementById('edit_room_number').value = this.dataset.roomNumber;
                document.getElementById('edit_room_type').value = this.dataset.roomType;
                document.getElementById('edit_price_per_night').value = this.dataset.price;
                document.getElementById('edit_capacity').value = this.dataset.capacity;
                document.getElementById('edit_description').value = this.dataset.description;
                document.getElementById('edit_status').value = this.dataset.status;
                modal.show();
            });
        });
        
        // Handle delete room button clicks
        document.querySelectorAll('.delete-room-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('deleteRoomModal'));
                document.getElementById('delete_room_id').value = this.dataset.roomId;
                document.getElementById('delete_room_number').textContent = this.dataset.roomNumber;
                modal.show();
            });
        });
    </script>

<?php renderAdminFooter(); ?>