<?php
session_start();
include('config.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: viewtransactions.php");
  exit();
}

$id = intval($_GET['id']);
mysqli_query($con, "DELETE FROM donations WHERE id = $id");

echo "<script>alert('Transaction deleted successfully.'); window.location='viewtransactions.php';</script>";
?>
