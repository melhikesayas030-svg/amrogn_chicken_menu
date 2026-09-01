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

// Handle Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $category = $_POST['category'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $variant = $_POST['variant'];
    $price = $_POST['price'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE menu_items SET category=?, name=?, description=?, variant=?, price=? WHERE id=?");
        $stmt->bind_param("ssssdi", $category, $name, $description, $variant, $price, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO menu_items (category, name, description, variant, price) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $category, $name, $description, $variant, $price);
    }
    $stmt->execute();
    header("Location: index.php");
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: index.php");
    exit;
}
?>