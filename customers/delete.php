<?php
// customers/delete.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0) {
    $res = mysqli_query($conn, "SELECT name FROM customers WHERE id=$id");
    $cust = mysqli_fetch_assoc($res);
    $name = $cust ? $cust['name'] : 'Customer';
    mysqli_query($conn, "DELETE FROM customers WHERE id=$id");
    if (isset($_SESSION['user_id'])) {
        logActivity($conn, $_SESSION['user_id'], 'Customer Deleted', "Deleted customer {$name}");
    }
    header("Location: /inv/customers/list.php?msg=" . urlencode("Customer '{$name}' successfully deleted."));
    exit();
}

header("Location: /inv/customers/list.php");
exit();
?>
