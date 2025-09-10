<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is pos_admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos_admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Categories Management';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                
                $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $description);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Category added successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error adding category: ' . $conn->error . '</div>';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                
                $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
                $stmt->bind_param("ssi", $name, $description, $id);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Category updated successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error updating category: ' . $conn->error . '</div>';
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Check if category has products
                $check_stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM products WHERE category_id = ?");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['product_count'] > 0) {
                    $message = '<div class="alert alert-warning">Cannot delete category: It has ' . $row['product_count'] . ' products assigned to it.</div>';
                } else {
                    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">Category deleted successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error deleting category: ' . $conn->error . '</div>';
                    }
                }
                break;
        }
    }
}

// Get all categories with product count
$categories_query = "SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name";
$categories_result = $conn->query($categories_query);
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
            height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .sidebar .nav-link {
            color: #333;
        }
        .sidebar .nav-link:hover {
            color: var(--primary-color);
        }
        .sidebar .nav-link.active {
            color: var(--primary-color);
            font-weight: bold;
        }
        .main-content {
            padding: 20px;
        }
        .category-card {
            transition: transform 0.2s;
        }
        .category-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <div class="d-flex align-items-center mb-3">
                        <img src="logo.png" alt="Logo" class="me-2" style="height: 40px;">
                        <h5 class="mb-0">POS Admin</h5>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box me-2"></i>Products
                        </a>
                        <a class="nav-link active" href="categories.php">
                            <i class="bi bi-tags me-2"></i>Categories
                        </a>
                        <a class="nav-link" href="sales.php">
                            <i class="bi bi-graph-up me-2"></i>Sales
                        </a>
                        <a class="nav-link" href="inventory.php">
                            <i class="bi bi-boxes me-2"></i>Inventory
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-earmark-text me-2"></i>Reports
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear me-2"></i>Settings
                        </a>
                        <hr>
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Categories Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Category
                    </button>
                </div>

                <?php echo $message; ?>

                <!-- Categories Grid -->
                <div class="row">
                    <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card category-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($category['name']); ?></h5>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', <?php echo $category['product_count']; ?>)"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($category['description'] ?: 'No description'); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-box me-1"></i>
                                                <?php echo $category['product_count']; ?> product<?php echo $category['product_count'] != 1 ? 's' : ''; ?>
                                            </small>
                                            <small class="text-muted">ID: <?php echo $category['id']; ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="bi bi-tags display-1 text-muted"></i>
                                <h3 class="mt-3 text-muted">No Categories Found</h3>
                                <p class="text-muted">Start by adding your first product category.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="bi bi-plus-circle me-2"></i>Add Category
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Categories Table View -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">All Categories (Table View)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($categories_result) {
                                        $categories_result->data_seek(0);
                                        while ($category = $categories_result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $category['id']; ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['description'] ?: 'No description'); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $category['product_count']; ?></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', <?php echo $category['product_count']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Optional description for this category"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" placeholder="Optional description for this category"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category <strong id="delete_category_name"></strong>?</p>
                    <div id="delete_warning" class="alert alert-warning" style="display: none;">
                        <strong>Warning:</strong> This category has <span id="delete_product_count"></span> product(s) assigned to it. You cannot delete this category until you reassign or delete those products.
                    </div>
                    <p class="text-danger" id="delete_confirm_text">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_category_id">
                        <button type="submit" class="btn btn-danger" id="delete_button">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description || '';
            
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }
        
        function deleteCategory(id, name, productCount) {
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = name;
            document.getElementById('delete_product_count').textContent = productCount;
            
            const warningDiv = document.getElementById('delete_warning');
            const deleteButton = document.getElementById('delete_button');
            const confirmText = document.getElementById('delete_confirm_text');
            
            if (productCount > 0) {
                warningDiv.style.display = 'block';
                deleteButton.disabled = true;
                deleteButton.textContent = 'Cannot Delete';
                confirmText.style.display = 'none';
            } else {
                warningDiv.style.display = 'none';
                deleteButton.disabled = false;
                deleteButton.textContent = 'Delete';
                confirmText.style.display = 'block';
            }
            
            new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
        }
    </script>
</body>
</html>