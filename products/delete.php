<?php
// products/delete.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0) {
    $res = mysqli_query($conn, "SELECT name FROM products WHERE id=$id");
    $prod = mysqli_fetch_assoc($res);
    $name = $prod ? $prod['name'] : 'Product';
    mysqli_query($conn, "DELETE FROM products WHERE id=$id");
    if (isset($_SESSION['user_id'])) {
        logActivity($conn, $_SESSION['user_id'], 'Product Deleted', "Deleted product {$name}");
    }
    header("Location: /inv/products/list.php?msg=" . urlencode("Product '{$name}' successfully deleted."));
    exit();
}

header("Location: /inv/products/list.php");
exit();
?>
