<?php
session_start();
include('config.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: viewtransactions.php");
  exit();
}

$id = intval($_GET['id']);
$result = mysqli_query($con, "SELECT * FROM donations WHERE id = $id");
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {
  $name = $_POST['donor_name'];
  $email = $_POST['donor_email'];
  $phone = $_POST['donor_phone'];
  $amount = $_POST['amount'];
  $reason = $_POST['payment_reason'];
  $status = $_POST['status'];

  $update = "UPDATE donations SET 
    donor_name='$name',
    donor_email='$email',
    donor_phone='$phone',
    amount='$amount',
    payment_reason='$reason',
    status='$status'
    WHERE id=$id";

  if (mysqli_query($con, $update)) {
    echo "<script>alert('Transaction updated successfully!'); window.location='viewtransactions.php';</script>";
  } else {
    echo "<script>alert('Update failed');</script>";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Transaction</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
<div class="container">
  <h3 class="text-center mt-4">Edit Transaction</h3>
  <form method="post">
    <div class="form-group">
      <label>Name:</label>
      <input type="text" name="donor_name" class="form-control" value="<?= $row['donor_name'] ?>" required>
    </div>
    <div class="form-group">
      <label>Email:</label>
      <input type="email" name="donor_email" class="form-control" value="<?= $row['donor_email'] ?>" required>
    </div>
    <div class="form-group">
      <label>Phone:</label>
      <input type="text" name="donor_phone" class="form-control" value="<?= $row['donor_phone'] ?>" required>
    </div>
    <div class="form-group">
      <label>Amount:</label>
      <input type="number" name="amount" class="form-control" value="<?= $row['amount'] ?>" required>
    </div>
    <div class="form-group">
      <label>Reason:</label>
      <input type="text" name="payment_reason" class="form-control" value="<?= $row['payment_reason'] ?>" required>
    </div>
    <div class="form-group">
      <label>Status:</label>
      <select name="status" class="form-control">
        <option value="success" <?= $row['status'] == 'success' ? 'selected' : '' ?>>Success</option>
        <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="failed" <?= $row['status'] == 'failed' ? 'selected' : '' ?>>Failed</option>
      </select>
    </div>
    <button type="submit" name="update" class="btn btn-primary">Update</button>
    <a href="viewtransactions.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
</body>
</html>
