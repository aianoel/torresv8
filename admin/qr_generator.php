<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/admin_layout.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'QR Generator';
$message = '';
$error = '';

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'get_qr') {
    $qr_id = $_GET['id'] ?? 0;
    
    if ($qr_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM qr_codes WHERE id = ?");
        $stmt->bind_param("i", $qr_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $qr = $result->fetch_assoc();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'qr_data' => $qr['data'],
                'created_at' => date('M d, Y H:i', strtotime($qr['created_at'])),
                'expires_at' => $qr['expires_at'] ? date('M d, Y', strtotime($qr['expires_at'])) : null
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'QR code not found']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid QR code ID']);
    }
    exit;
}

// Handle toggle display on landing AJAX request
if (isset($_GET['action']) && $_GET['action'] === 'toggle_display') {
    $qr_id = $_GET['id'] ?? 0;
    $display_status = $_GET['status'] === 'true' ? 1 : 0;
    
    if ($qr_id > 0) {
        $stmt = $conn->prepare("UPDATE qr_codes SET display_on_landing = ? WHERE id = ?");
        $stmt->bind_param("ii", $display_status, $qr_id);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Display status updated successfully'
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to update display status']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid QR code ID']);
    }
    exit;
}

// Handle QR code operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    
    if ($action === 'delete') {
        // Handle QR code deletion
        $qr_id = $_POST['qr_id'] ?? 0;
        
        if ($qr_id > 0) {
            $stmt = $conn->prepare("DELETE FROM qr_codes WHERE id = ?");
            $stmt->bind_param("i", $qr_id);
            
            if ($stmt->execute()) {
                $message = 'QR code deleted successfully!';
            } else {
                $error = 'Failed to delete QR code: ' . $conn->error;
            }
        } else {
            $error = 'Invalid QR code ID.';
        }
    } else {
        // Handle QR code generation
        $qr_type = $_POST['qr_type'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $discount_percentage = $_POST['discount_percentage'] ?? 0;
        $valid_until = $_POST['valid_until'] ?? '';
    
    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } else {
        // Generate unique QR code
        $qr_code = 'QR' . time() . rand(1000, 9999);
        
        // Prepare data as JSON
        if ($qr_type === 'feedback') {
            // For feedback QR codes, content should be the feedback URL
            $feedback_url = 'http://localhost:8000/public/feedback.php?qr_id=';
            $qr_data = json_encode([
                'type' => $qr_type,
                'title' => $title,
                'content' => $feedback_url, // Will be completed after QR ID is generated
                'discount_percentage' => 0,
                'valid_until' => $valid_until
            ]);
        } else {
            $qr_data = json_encode([
                'type' => $qr_type,
                'title' => $title,
                'content' => $content,
                'discount_percentage' => $discount_percentage,
                'valid_until' => $valid_until
            ]);
        }
        
        // Set expiration date
        $expires_at = !empty($valid_until) ? $valid_until : NULL;
        
        // Insert QR code into database
        $stmt = $conn->prepare("INSERT INTO qr_codes (code, purpose, data, created_by, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $qr_code, $qr_type, $qr_data, $_SESSION['user_id'], $expires_at);
        
        if ($stmt->execute()) {
            $qr_id = $conn->insert_id;
            
            // Update feedback QR codes with complete URL
            if ($qr_type === 'feedback') {
                $complete_feedback_url = 'http://localhost:8000/public/feedback.php?qr_id=' . $qr_id;
                $updated_qr_data = json_encode([
                    'type' => $qr_type,
                    'title' => $title,
                    'content' => $complete_feedback_url,
                    'discount_percentage' => 0,
                    'valid_until' => $valid_until
                ]);
                
                $update_stmt = $conn->prepare("UPDATE qr_codes SET data = ? WHERE id = ?");
                $update_stmt->bind_param("si", $updated_qr_data, $qr_id);
                $update_stmt->execute();
            }
            
            $message = 'QR code generated successfully! ID: ' . $qr_id;
        } else {
            $error = 'Failed to generate QR code: ' . $conn->error;
        }
    }
    }
}

// Get existing QR codes
$qrCodesQuery = $conn->prepare("
    SELECT qr.*, u.first_name, u.last_name 
    FROM qr_codes qr 
    LEFT JOIN users u ON qr.created_by = u.id 
    ORDER BY qr.created_at DESC
");
$qrCodesQuery->execute();
$qrCodes = $qrCodesQuery->get_result();

?>

<?php renderAdminHeader($pageTitle); ?>

<style>
    .qr-preview {
        border: 2px dashed #dee2e6;
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
    }
    .qr-code-display {
        max-width: 200px;
        max-height: 200px;
    }
</style>

<?php renderAdminPageHeader($pageTitle); ?>

<!-- QR Generator Content Section -->
<div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>QR Code Generator</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateQRModal">
                    <i class="bi bi-plus-circle"></i> Generate New QR
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- QR Codes List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Generated QR Codes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Content</th>
                                    <th>Discount</th>
                                    <th>Valid Until</th>
                                    <th>Display on Landing</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($qr = $qrCodes->fetch_assoc()): ?>
                                    <tr>
                                        <?php 
                                        $qr_data = json_decode($qr['data'], true);
                                        $qr_type = $qr_data['type'] ?? $qr['purpose'];
                                        $qr_title = $qr_data['title'] ?? 'N/A';
                                        $qr_content = $qr_data['content'] ?? $qr['data'];
                                        $discount_percentage = $qr_data['discount_percentage'] ?? 0;
                                        $valid_until = $qr_data['valid_until'] ?? $qr['expires_at'];
                                        ?>
                                        <td><?php echo $qr['id']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $qr_type === 'promotion' ? 'success' : ($qr_type === 'contact' ? 'info' : 'secondary'); ?>">
                                                <?php echo ucfirst($qr_type); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($qr_title); ?></td>
                                        <td><?php echo htmlspecialchars(substr($qr_content, 0, 50)) . (strlen($qr_content) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <?php if ($discount_percentage > 0): ?>
                                                <span class="badge bg-warning"><?php echo $discount_percentage; ?>% OFF</span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($valid_until): ?>
                                                <?php echo date('M d, Y', strtotime($valid_until)); ?>
                                            <?php else: ?>
                                                No expiry
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="displayToggle<?php echo $qr['id']; ?>" 
                                                       <?php echo $qr['display_on_landing'] ? 'checked' : ''; ?>
                                                       onchange="toggleDisplayOnLanding(<?php echo $qr['id']; ?>, this.checked)">
                                                <label class="form-check-label" for="displayToggle<?php echo $qr['id']; ?>">
                                                    <?php echo $qr['display_on_landing'] ? 'Enabled' : 'Disabled'; ?>
                                                </label>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($qr['first_name'] . ' ' . $qr['last_name']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($qr['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewQR(<?php echo $qr['id']; ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteQR(<?php echo $qr['id']; ?>)">
                                                <i class="bi bi-trash"></i>
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

    <!-- Generate QR Modal -->
    <div class="modal fade" id="generateQRModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate New QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="qr_type" class="form-label">QR Code Type</label>
                                    <select class="form-select" id="qr_type" name="qr_type" required>
                                        <option value="promotion">Promotion</option>
                                        <option value="contact">Contact Info</option>
                                        <option value="website">Website URL</option>
                                        <option value="text">Plain Text</option>
                                        <option value="feedback">Customer Feedback</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="content" class="form-label">Content</label>
                                    <textarea class="form-control" id="content" name="content" rows="4" required placeholder="Enter the content for your QR code..."></textarea>
                                </div>
                                <div class="mb-3" id="discountField" style="display: none;">
                                    <label for="discount_percentage" class="form-label">Discount Percentage</label>
                                    <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" value="0">
                                </div>
                                <div class="mb-3">
                                    <label for="valid_until" class="form-label">Valid Until (Optional)</label>
                                    <input type="date" class="form-control" id="valid_until" name="valid_until">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">QR Code Preview</label>
                                    <div class="qr-preview" id="qrPreview">
                                        <div class="text-muted">
                                            <i class="bi bi-qr-code fs-1"></i>
                                            <p>QR code will appear here</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="generatePreview()">
                                        <i class="bi bi-eye"></i> Preview QR Code
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-qr-code"></i> Generate QR Code
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View QR Modal -->
    <div class="modal fade" id="viewQRModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="qrModalBody">
                    <!-- QR code details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="downloadQR()">Download QR</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>
        // Show/hide discount field based on QR type
        document.getElementById('qr_type').addEventListener('change', function() {
            const discountField = document.getElementById('discountField');
            if (this.value === 'promotion') {
                discountField.style.display = 'block';
            } else {
                discountField.style.display = 'none';
            }
        });

        // Generate QR code preview
        function generatePreview() {
            const content = document.getElementById('content').value;
            const qrPreview = document.getElementById('qrPreview');
            
            if (!content.trim()) {
                alert('Please enter content first');
                return;
            }
            
            qrPreview.innerHTML = '';
            try {
                const canvas = document.createElement('canvas');
                const qr = new QRious({
                    element: canvas,
                    value: content,
                    size: 200
                });
                qrPreview.appendChild(canvas);
            } catch (error) {
                qrPreview.innerHTML = '<div class="text-danger">Error generating QR code</div>';
            }
        }

        // View QR code details
        function viewQR(qrId) {
            // Fetch QR code data via AJAX
            fetch(`qr_generator.php?action=get_qr&id=${qrId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modalBody = document.getElementById('qrModalBody');
                        const qrData = JSON.parse(data.qr_data);
                        
                        modalBody.innerHTML = `
                            <div class="mb-3">
                                <canvas id="viewQRCanvas"></canvas>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Title:</strong> ${qrData.title || 'N/A'}</p>
                                    <p><strong>Type:</strong> ${qrData.type || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Created:</strong> ${data.created_at}</p>
                                    <p><strong>Expires:</strong> ${data.expires_at || 'Never'}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <p><strong>Content:</strong></p>
                                <div class="bg-light p-2 rounded" style="word-break: break-all;">
                                    ${qrData.content}
                                </div>
                            </div>
                        `;
                        
                        // Generate QR code with actual content
                        const viewCanvas = document.getElementById('viewQRCanvas');
                        const qr = new QRious({
                            element: viewCanvas,
                            value: qrData.content,
                            size: 200
                        });
                        
                        new bootstrap.Modal(document.getElementById('viewQRModal')).show();
                    } else {
                        alert('Error loading QR code details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading QR code details');
                });
        }

        // Delete QR code
        function deleteQR(qrId) {
            if (confirm('Are you sure you want to delete this QR code?')) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                
                const qrIdInput = document.createElement('input');
                qrIdInput.type = 'hidden';
                qrIdInput.name = 'qr_id';
                qrIdInput.value = qrId;
                
                form.appendChild(actionInput);
                form.appendChild(qrIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Toggle display on landing page
        function toggleDisplayOnLanding(qrId, isEnabled) {
            fetch(`qr_generator.php?action=toggle_display&id=${qrId}&status=${isEnabled}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the label text
                        const label = document.querySelector(`label[for="displayToggle${qrId}"]`);
                        if (label) {
                            label.textContent = isEnabled ? 'Enabled' : 'Disabled';
                        }
                        
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = `
                            ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        
                        // Insert the alert at the top of the container
                        const container = document.querySelector('.container-fluid');
                        const firstChild = container.firstElementChild;
                        container.insertBefore(alertDiv, firstChild.nextSibling);
                        
                        // Auto-dismiss after 3 seconds
                        setTimeout(() => {
                            if (alertDiv.parentNode) {
                                alertDiv.remove();
                            }
                        }, 3000);
                    } else {
                        // Revert the checkbox state on error
                        const checkbox = document.getElementById(`displayToggle${qrId}`);
                        if (checkbox) {
                            checkbox.checked = !isEnabled;
                        }
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert the checkbox state on error
                    const checkbox = document.getElementById(`displayToggle${qrId}`);
                    if (checkbox) {
                        checkbox.checked = !isEnabled;
                    }
                    alert('Error updating display status');
                });
        }

        // Download QR code
        function downloadQR() {
            const canvas = document.getElementById('viewQRCanvas');
            if (canvas) {
                const link = document.createElement('a');
                link.download = 'qr-code.png';
                link.href = canvas.toDataURL();
                link.click();
            }
        }
    </script>

<?php renderAdminFooter(); ?>