<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/admin_layout.php';

$pageTitle = 'Content Management';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_content':
                $content_key = $_POST['content_key'];
                $content_value = $_POST['content_value'];
                
                $stmt = $pdo->prepare("UPDATE landing_content SET content_value = ? WHERE content_key = ?");
                if ($stmt->execute([$content_value, $content_key])) {
                    $success_message = "Content updated successfully!";
                } else {
                    $error_message = "Error updating content.";
                }
                break;
                
            case 'add_content':
                $content_key = $_POST['new_content_key'];
                $content_value = $_POST['new_content_value'];
                $content_type = $_POST['content_type'];
                $section = $_POST['section'];
                
                $stmt = $pdo->prepare("INSERT INTO landing_content (content_key, content_value, content_type, section) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$content_key, $content_value, $content_type, $section])) {
                    $success_message = "Content added successfully!";
                } else {
                    $error_message = "Error adding content.";
                }
                break;
        }
    }
}

// Fetch all content
$stmt = $pdo->query("SELECT * FROM landing_content ORDER BY section, content_key");
$content_items = $stmt->fetchAll();

// Group content by section
$content_by_section = [];
foreach ($content_items as $item) {
    $content_by_section[$item['section']][] = $item;
}
?>
<?php renderAdminHeader($pageTitle, 'content'); ?>
    <style>
        .content-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-header {
            background: var(--gold-color);
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            font-weight: 600;
        }
        
        .content-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem;
        }
        
        .content-item:last-child {
            border-bottom: none;
        }
        
        .btn-edit {
            background: var(--gold-color);
            border: none;
            color: white;
        }
        
        .btn-edit:hover {
            background: #b8941f;
            color: white;
        }
    </style>
<?php renderAdminPageHeader('Content Management', 'Manage website content and landing page information'); ?>

<!-- Content Management Section -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Landing Page Content Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContentModal">
        <i class="bi bi-plus-circle me-2"></i>Add New Content
    </button>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
                    
<!-- Content Sections -->
<?php 
$section_order = ['hero', 'features', 'about', 'testimonials', 'footer'];
foreach ($section_order as $section): 
    if (isset($content_by_section[$section])):
        $items = $content_by_section[$section];
?>
    <div class="content-section">
        <div class="section-header">
            <h5 class="mb-0"><?php echo ucfirst(str_replace('_', ' ', $section)); ?> Section</h5>
        </div>
        <div class="section-content">
            <?php foreach ($items as $item): ?>
                <div class="content-item">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <strong><?php echo ucfirst(str_replace('_', ' ', $item['content_key'])); ?></strong>
                            <br><small class="text-muted"><?php echo $item['content_type']; ?></small>
                        </div>
                        <div class="col-md-6">
                            <div class="content-preview">
                                <?php if ($item['content_type'] === 'image'): ?>
                                    <img src="<?php echo $item['content_value']; ?>" alt="Preview" style="max-width: 100px; max-height: 60px; object-fit: cover;">
                                    <br><small><?php echo $item['content_value']; ?></small>
                                <?php else: ?>
                                    <?php echo strlen($item['content_value']) > 100 ? substr($item['content_value'], 0, 100) . '...' : $item['content_value']; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-edit btn-sm" onclick="editContent('<?php echo $item['content_key']; ?>', '<?php echo addslashes($item['content_value']); ?>', '<?php echo $item['content_type']; ?>')">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php 
    endif;
endforeach; 
?>
    
    <!-- Edit Content Modal -->
    <div class="modal fade" id="editContentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Content</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_content">
                        <input type="hidden" name="content_key" id="edit_content_key">
                        
                        <div class="mb-3">
                            <label class="form-label">Content Key</label>
                            <input type="text" class="form-control" id="edit_key_display" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Content Value</label>
                            <textarea class="form-control" name="content_value" id="edit_content_value" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Content</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Content Modal -->
    <div class="modal fade" id="addContentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Content</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_content">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Section</label>
                                    <select class="form-control" name="section" required>
                                        <option value="hero">Hero Section</option>
                                        <option value="features">Features Section</option>
                                        <option value="about">About Section</option>
                                        <option value="testimonials">Testimonials Section</option>
                                        <option value="footer">Footer Section</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Content Type</label>
                                    <select class="form-control" name="content_type" required>
                                        <option value="text">Text</option>
                                        <option value="html">HTML</option>
                                        <option value="image">Image URL</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Content Key</label>
                            <input type="text" class="form-control" name="new_content_key" placeholder="e.g., hero_title, about_description" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Content Value</label>
                            <textarea class="form-control" name="new_content_value" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Content</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editContent(key, value, type) {
            document.getElementById('edit_content_key').value = key;
            document.getElementById('edit_key_display').value = key;
            document.getElementById('edit_content_value').value = value;
            
            const modal = new bootstrap.Modal(document.getElementById('editContentModal'));
            modal.show();
        }
    </script>

<?php renderAdminFooter(); ?>