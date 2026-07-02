<?php
// invoices/delete.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';

checkAuth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0) {
    $res = mysqli_query($conn, "SELECT invoice_no FROM invoices WHERE id=$id");
    $inv = mysqli_fetch_assoc($res);
    $invoice_no = $inv ? $inv['invoice_no'] : 'Invoice';
    // Delete items handled by ON DELETE CASCADE, same for payments if configured
    mysqli_query($conn, "DELETE FROM invoices WHERE id=$id");
    if (isset($_SESSION['user_id'])) {
        logActivity($conn, $_SESSION['user_id'], 'Invoice Deleted', "Deleted invoice {$invoice_no}");
    }
    header("Location: " . BASE_URL . "/invoices/list.php?msg=" . urlencode("Invoice '{$invoice_no}' successfully deleted."));
    exit();
}

header("Location: " . BASE_URL . "/invoices/list.php");
exit();
?>
