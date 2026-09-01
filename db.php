<?php
$host = 'sqlXXX.infinityfree.com';
$user = 'if0_42755876';            
$pass = 'your_account_password';  
$db   = 'if0_42755876_amrogn_db'; 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

function getMenuItemsGrouped($conn) {
    $sql = "SELECT * FROM menu_items ORDER BY category, id";
    $result = $conn->query($sql);
    $menu = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $menu[$row['category']][] = $row;
        }
    }
    return $menu;
}
?>