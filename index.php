<?php
session_start();
require_once 'db.php';

$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
// Process Customer Feedback Submission
$feedbackMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $cName = trim($_POST['customer_name']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    if (!empty($cName) && $rating >= 1 && $rating <= 5 && !empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO feedback (customer_name, rating, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $cName, $rating, $comment);
        $stmt->execute();
        $feedbackMsg = "Thank you! Your feedback has been submitted.";
    }
}

// Fetch Feedback Stats & Reviews
$avgRatingRes = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM feedback");
$ratingData = $avgRatingRes->fetch_assoc();
$avgRating = round($ratingData['avg_rating'] ?? 0, 1);
$totalReviews = $ratingData['total_reviews'] ?? 0;

$reviewsRes = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 5");

// Fetch Menu Items
$menuData = getMenuItemsGrouped($conn);
$categories = array_keys($menuData);
$selectedCategory = $_GET['cat'] ?? 'All';
$searchQuery = trim($_GET['search'] ?? '');

// Fetch item for editing if ID is supplied
$editItem = null;
if ($isAdmin && isset($_GET['edit_id'])) {
    $editId = intval($_GET['edit_id']);
    $res = $conn->query("SELECT * FROM menu_items WHERE id = $editId");
    $editItem = $res->fetch_assoc();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amrogn Chicken - digitL Menu</title>
    <style>
        :root { --primary: #d32f2f; --accent: #fbc02d; --bg: #fdfbf7; --text: #2c3e50; }
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg); color: var(--text); margin: 0; padding-bottom: 40px; }
        
        /* Admin Bar Styles */
        .admin-bar { background: #1e1e1e; color: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; font-size: 0.9rem; }
        .admin-bar a { color: #fbc02d; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .admin-form-container { background: #fff; border: 2px solid var(--primary); padding: 20px; margin: 20px auto; max-width: 860px; border-radius: 8px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
        .form-grid input, .form-grid select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { background: #2e7d32; color: #fff; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px; }

        /* Navigation Filter */
        .category-filter { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin: 20px 0; }
        .filter-btn { padding: 8px 16px; border-radius: 20px; background: #e0e0e0; color: #333; text-decoration: none; font-size: 0.9rem; font-weight: bold; transition: 0.2s; }
        .filter-btn.active, .filter-btn:hover { background: var(--primary); color: #fff; }
 
        /* Public Menu Styles */
        .container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
        header { text-align: center; padding: 20px 0; border-bottom: 3px solid var(--primary); margin-bottom: 20px; }
        header h1 { color: var(--primary); margin: 0; font-size: 2.5rem; }
        .category-title { color: var(--primary); border-bottom: 2px solid var(--accent); padding-bottom: 5px; margin-top: 30px; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-top: 15px; }
        .card { background: #ffffff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid var(--primary); display: flex; gap: 12px; align-items: center; }
        .card-img { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; flex-shrink: 0; background: #f0f0f0; }
        .card-content { flex-grow: 1; display: flex; justify-content: space-between; align-items: center; }
        .card-info h3 { margin: 0 0 5px 0; font-size: 1.05rem; }
        .card-info p { margin: 0; font-size: 0.85rem; color: #666; }
        .price { font-weight: bold; color: var(--primary); font-size: 1.1rem; white-space: nowrap; margin-left: 10px; }
        .admin-actions { margin-top: 8px; font-size: 0.8rem; }
        .admin-actions a { color: #d32f2f; text-decoration: none; margin-right: 10px; }
        /* Search Bar */
        .search-box { margin: 20px 0; text-align: center; }
        .search-box input { width: 80%; max-width: 400px; padding: 10px; border: 2px solid var(--primary); border-radius: 20px; outline: none; }
        /* Feedback Section */
        .feedback-section { background: #fff; padding: 20px; border-radius: 8px; margin-top: 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px; margin: 10px 0; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 1.8rem; color: #ccc; cursor: pointer; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: var(--accent); }
    </style>
</head>
<body>

<?php if ($isAdmin): ?>
    <div class="admin-bar">
        <span>Logged in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
        <div>
            <a href="generate_qr.php" target="_blank">Get QR Code</a>
            <a href="index.php">Refresh View</a>
            <a href="admin.php?action=logout" style="color: #ff5252;">Logout</a>
        </div>
    </div>

   <div class="admin-form-container">
    <h3><?php echo $editItem ? "Edit Menu Item" : "Add New Menu Item"; ?></h3>
    <form action="admin.php" method="POST" enctype="multipart/form-data">
        <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editItem['image_url'] ?? ''); ?>">
        <?php endif; ?>
        
        <div class="form-grid">
            <input type="text" name="category" placeholder="Category" value="<?php echo htmlspecialchars($editItem['category'] ?? ''); ?>" required>
            <input type="text" name="name" placeholder="Item Name" value="<?php echo htmlspecialchars($editItem['name'] ?? ''); ?>" required>
            <input type="text" name="variant" placeholder="Variant (e.g. 2 pcs, Half)" value="<?php echo htmlspecialchars($editItem['variant'] ?? ''); ?>">
            <input type="number" step="0.01" name="price" placeholder="Price (ETB)" value="<?php echo htmlspecialchars($editItem['price'] ?? ''); ?>" required>
        </div>
        
        <div style="margin-top: 10px; display: flex; gap: 10px;">
            <input type="text" name="description" placeholder="Description / Side details" value="<?php echo htmlspecialchars($editItem['description'] ?? ''); ?>" style="flex-grow: 1; padding: 8px;">
            <input type="file" name="image" accept="image/*" style="padding: 5px;">
            <label><input type="checkbox" name="is_available" value="1" <?php echo (!isset($editItem) || $editItem['is_available'] == 1) ? 'checked' : ''; ?>> Available</label>
        </div>
        
        <button type="submit" class="btn-submit"><?php echo $editItem ? "Update Item" : "Save Item"; ?></button>
        <?php if ($editItem): ?>
            <a href="index.php" style="margin-left: 10px; color: #666; text-decoration: none;">Cancel</a>
        <?php endif; ?>
    </form>
</div>
<?php endif; ?>

<div class="container">
    <header>
        <h1>Amrogn Chicken</h1>
        <small style="color: #666;">We Speak Chicken!</small>
        <?php if (!$isAdmin): ?>
            <div style="margin-top: 10px;"><a href="login.php" style="color: #999; font-size: 0.8rem; text-decoration: none;">Admin Login</a></div>
        <?php endif; ?>
    </header>

    <!-- Search Bar -->
    <div class="search-box">
        <form action="index.php" method="GET">
            <input type="text" name="search" placeholder="Search menu items..." value="<?php echo htmlspecialchars($searchQuery); ?>">
        </form>
    </div>

    <!-- Category Filter Bar -->
    <div class="category-filter">
        <a href="index.php" class="filter-btn <?php echo ($selectedCategory === 'All') ? 'active' : ''; ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="index.php?cat=<?php echo urlencode($cat); ?>" class="filter-btn <?php echo ($selectedCategory === $cat) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Menu Display -->
    <?php foreach ($menuData as $category => $items): ?>
        <?php if ($selectedCategory !== 'All' && $selectedCategory !== $category) continue; ?>

        <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
        <div class="menu-grid">
            <?php foreach ($items as $item): ?>
                <div class="card">
                    <?php if (!empty($item['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="card-img">
                    <?php endif; ?>
                    <div class="card-content">
                        <div class="card-info">
                            <h3>
                                <?php echo htmlspecialchars($item['name']); ?>
                                <?php if (!empty($item['variant'])): ?>
                                    <small>(<?php echo htmlspecialchars($item['variant']); ?>)</small>
                                <?php endif; ?>
                            </h3>
                            <?php if (!empty($item['description'])): ?>
                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <?php endif; ?>

                            <?php if ($isAdmin): ?>
                                <div class="admin-actions">
                                    <a href="index.php?edit_id=<?php echo $item['id']; ?>">Edit</a>
                                    <a href="admin.php?action=delete&id=<?php echo $item['id']; ?>" onclick="return confirm('Delete this item?');">Delete</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="price">
                            <?php echo number_format($item['price'], 0); ?> ETB
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Feedback & Reviews Section -->
    <div class="feedback-section">
        <h2>Customer Reviews & Feedback</h2>
        
        <?php if ($feedbackMsg): ?>
            <p style="color: green; font-weight: bold;"><?php echo $feedbackMsg; ?></p>
        <?php endif; ?>

        <!-- Feedback Form -->
        <form action="index.php" method="POST" style="margin-bottom: 30px;">
            <input type="hidden" name="submit_feedback" value="1">
            <input type="text" name="customer_name" placeholder="Your Name" required style="width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box;">
            
            <label>Rating:</label>
            <div class="star-rating">
                <input type="radio" id="star5" name="rating" value="5" required><label for="star5">★</label>
                <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
            </div>
            
            <textarea name="comment" placeholder="Leave your review..." required style="width: 100%; height: 80px; padding: 8px; box-sizing: border-box;"></textarea>
            <button type="submit" style="background: var(--primary); color: #fff; border: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; margin-top: 10px; cursor: pointer;">Submit Feedback</button>
        </form>

        <!-- Display Recent Reviews -->
        <h3>Recent Feedback</h3>
        <?php while ($rev = $reviewsRes->fetch_assoc()): ?>
            <div style="border-bottom: 1px solid #eee; padding: 10px 0;">
                <strong><?php echo htmlspecialchars($rev['customer_name']); ?></strong>
                <span style="color: var(--accent); font-weight: bold;"> - <?php echo $rev['rating']; ?> ★</span>
                <p style="margin: 5px 0; font-size: 0.9rem;"><?php echo htmlspecialchars($rev['comment']); ?></p>
                <small style="color: #999;"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></small>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>