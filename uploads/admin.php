<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle Toggle Availability
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $id = intval($_GET['id']);
    $status = intval($_GET['status']) === 1 ? 0 : 1;
    $stmt = $conn->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// Handle Delete Feedback
if (isset($_GET['action']) && $_GET['action'] === 'delete_feedback') {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin.php#feedback-section");
    exit;
}

// Handle Delete Menu Item
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: index.php");
    exit;
}

// Handle Create / Update Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_item'])) {
    $id = $_POST['id'] ?? null;
    $category = trim($_POST['category']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $variant = trim($_POST['variant']);
    $price = $_POST['price'];
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    
    $imagePath = $_POST['existing_image'] ?? '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

            $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $imagePath = $destPath;
            }
        }
    }

    if ($id) {
        $stmt = $conn->prepare("UPDATE menu_items SET category=?, name=?, description=?, variant=?, price=?, image=?, is_available=? WHERE id=?");
        $stmt->bind_param("ssssdsii", $category, $name, $description, $variant, $price, $imagePath, $isAvailable, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO menu_items (category, name, description, variant, price, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdsi", $category, $name, $description, $variant, $price, $imagePath, $isAvailable);
    }
    $stmt->execute();
    header("Location: index.php");
    exit;
}

// Fetch all feedback for admin viewing
$feedbackRes = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC");
$allItemsRes = $conn->query("SELECT * FROM menu_items ORDER BY category, id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Management Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #1e1e1e; color: #fff; padding: 15px 20px; border-radius: 8px; }
        .header a { color: #fbc02d; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .section { background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; color: #fff; }
        .badge-success { background: #2e7d32; }
        .badge-danger { background: #c62828; }
        .btn { padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 0.85rem; }
        .btn-toggle { background: #0288d1; color: #fff; }
        .btn-delete { background: #d32f2f; color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Admin Dashboard</h2>
        <div>
            <a href="generate_qr.php" target="_blank">View/Print QR Code</a>
            <a href="index.php">Go to Menu</a>
            <a href="admin.php?action=logout" style="color: #ff5252;">Logout</a>
        </div>
    </div>

    <!-- Availability & Item Management -->
    <div class="section">
        <h3>Menu Availability Control</h3>
        <table>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while ($item = $allItemsRes->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($item['category']); ?></td>
                <td><?php echo number_format($item['price'], 0); ?> ETB</td>
                <td>
                    <?php if ($item['is_available']): ?>
                        <span class="badge badge-success">Available</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Out of Stock</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="admin.php?action=toggle_status&id=<?php echo $item['id']; ?>&status=<?php echo $item['is_available']; ?>" class="btn btn-toggle">Toggle Status</a>
                    <a href="index.php?edit_id=<?php echo $item['id']; ?>" class="btn" style="background:#fbc02d; color:#000;">Edit</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Feedback Management -->
    <div class="section" id="feedback-section">
        <h3>Customer Feedback</h3>
        <table>
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Action</th>
            </tr>
            <?php while ($fb = $feedbackRes->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></td>
                <td><strong><?php echo htmlspecialchars($fb['customer_name']); ?></strong></td>
                <td><span style="color: #fbc02d; font-weight:bold;"><?php echo $fb['rating']; ?> ★</span></td>
                <td><?php echo htmlspecialchars($fb['comment']); ?></td>
                <td>
                    <a href="admin.php?action=delete_feedback&id=<?php echo $fb['id']; ?>" onclick="return confirm('Delete feedback?')" class="btn btn-delete">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
</body>
</html>