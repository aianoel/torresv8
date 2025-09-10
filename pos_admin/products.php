<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is pos_admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pos_admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Products Management';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $category_id = $_POST['category_id'];
                $price = $_POST['price'];
                $cost = $_POST['cost'];
                $stock_quantity = $_POST['stock_quantity'];
                $min_stock = $_POST['min_stock'];
                $description = trim($_POST['description']);
                $barcode = trim($_POST['barcode']);
                
                $stmt = $conn->prepare("INSERT INTO products (name, category_id, price, cost, stock_quantity, min_stock, description, barcode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siddiiss", $name, $category_id, $price, $cost, $stock_quantity, $min_stock, $description, $barcode);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Product added successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error adding product: ' . $conn->error . '</div>';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $category_id = $_POST['category_id'];
                $price = $_POST['price'];
                $cost = $_POST['cost'];
                $stock_quantity = $_POST['stock_quantity'];
                $min_stock = $_POST['min_stock'];
                $description = trim($_POST['description']);
                $barcode = trim($_POST['barcode']);
                
                $stmt = $conn->prepare("UPDATE products SET name=?, category_id=?, price=?, cost=?, stock_quantity=?, min_stock=?, description=?, barcode=? WHERE id=?");
                $stmt->bind_param("siddiiisi", $name, $category_id, $price, $cost, $stock_quantity, $min_stock, $description, $barcode, $id);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Product updated successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error updating product: ' . $conn->error . '</div>';
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Product deleted successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error deleting product: ' . $conn->error . '</div>';
                }
                break;
        }
    }
}

// Get all products with category names
$products_query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name";
$products_result = $conn->query($products_query);

// Get all categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
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
        .low-stock {
            background-color: #fff3cd;
        }
        .out-of-stock {
            background-color: #f8d7da;
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
                        <a class="nav-link active" href="products.php">
                            <i class="bi bi-box me-2"></i>Products
                        </a>
                        <a class="nav-link" href="categories.php">
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
                    <h1>Products Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Product
                    </button>
                </div>

                <?php echo $message; ?>

                <!-- Products Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Cost</th>
                                        <th>Stock</th>
                                        <th>Min Stock</th>
                                        <th>Barcode</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($products_result && $products_result->num_rows > 0): ?>
                                        <?php while ($product = $products_result->fetch_assoc()): ?>
                                            <?php 
                                            $row_class = '';
                                            if ($product['stock_quantity'] == 0) {
                                                $row_class = 'out-of-stock';
                                            } elseif ($product['stock_quantity'] <= $product['min_stock']) {
                                                $row_class = 'low-stock';
                                            }
                                            ?>
                                            <tr class="<?php echo $row_class; ?>">
                                                <td><?php echo $product['id']; ?></td>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                                <td>₱<?php echo number_format($product['cost'], 2); ?></td>
                                                <td><?php echo $product['stock_quantity']; ?></td>
                                                <td><?php echo $product['min_stock']; ?></td>
                                                <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="bi bi-box display-1"></i>
                                                <p class="mt-2">No products found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="category_id">
                                        <option value="">Select Category</option>
                                        <?php 
                                        if ($categories_result) {
                                            $categories_result->data_seek(0);
                                            while ($category = $categories_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endwhile; } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" class="form-control" name="price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Cost</label>
                                    <input type="number" class="form-control" name="cost" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control" name="stock_quantity" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Minimum Stock</label>
                                    <input type="number" class="form-control" name="min_stock" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" name="barcode">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editProductForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="category_id" id="edit_category_id">
                                        <option value="">Select Category</option>
                                        <?php 
                                        if ($categories_result) {
                                            $categories_result->data_seek(0);
                                            while ($category = $categories_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endwhile; } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Cost</label>
                                    <input type="number" class="form-control" name="cost" id="edit_cost" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control" name="stock_quantity" id="edit_stock_quantity" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Minimum Stock</label>
                                    <input type="number" class="form-control" name="min_stock" id="edit_min_stock" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" name="barcode" id="edit_barcode">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the product <strong id="delete_product_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_product_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_category_id').value = product.category_id || '';
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_cost').value = product.cost;
            document.getElementById('edit_stock_quantity').value = product.stock_quantity;
            document.getElementById('edit_min_stock').value = product.min_stock;
            document.getElementById('edit_barcode').value = product.barcode || '';
            document.getElementById('edit_description').value = product.description || '';
            
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        }
        
        function deleteProduct(id, name) {
            document.getElementById('delete_product_id').value = id;
            document.getElementById('delete_product_name').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
        }
    </script>
</body>
</html>