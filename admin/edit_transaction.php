<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: viewtransactions.php');
    exit();
}

$id = (int) $_GET['id'];
$stmt = $con->prepare('SELECT * FROM donations WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    $_SESSION['error'] = 'Transaction not found.';
    header('Location: viewtransactions.php');
    exit();
}

if (isset($_POST['update'])) {
    $name = trim((string) ($_POST['donor_name'] ?? ''));
    $email = trim((string) ($_POST['donor_email'] ?? ''));
    $phone = trim((string) ($_POST['donor_phone'] ?? ''));
    $amount = (float) ($_POST['amount'] ?? 0);
    $reason = trim((string) ($_POST['payment_reason'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'pending'));

    $upd = $con->prepare(
        'UPDATE donations SET donor_name=?, donor_email=?, donor_phone=?, amount=?, payment_reason=?, status=? WHERE id=?'
    );
    $upd->bind_param('sssdssi', $name, $email, $phone, $amount, $reason, $status, $id);
    if ($upd->execute()) {
        echo "<script>alert('Transaction updated successfully!'); window.location='viewtransactions.php';</script>";
        exit();
    }
    echo "<script>alert('Update failed');</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Transaction</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Edit Transaction</h1>
      <ol class="breadcrumb">
        <li><a href="viewtransactions.php"><i class="fa fa-dashboard"></i> Transactions</a></li>
        <li class="active">Edit</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Transaction #<?= (int) $row['id'] ?></h3>
        </div>
        <form method="post">
          <div class="box-body">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="donor_name" class="form-control" value="<?= htmlspecialchars((string) $row['donor_name']) ?>" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="donor_email" class="form-control" value="<?= htmlspecialchars((string) $row['donor_email']) ?>" required>
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="text" name="donor_phone" class="form-control" value="<?= htmlspecialchars((string) $row['donor_phone']) ?>" required>
            </div>
            <div class="form-group">
              <label>Amount</label>
              <input type="number" step="0.01" name="amount" class="form-control" value="<?= htmlspecialchars((string) $row['amount']) ?>" required>
            </div>
            <div class="form-group">
              <label>Reason</label>
              <input type="text" name="payment_reason" class="form-control" value="<?= htmlspecialchars((string) $row['payment_reason']) ?>" required>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status" class="form-control">
                <option value="success" <?= $row['status'] == 'success' ? 'selected' : '' ?>>Success</option>
                <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="failed" <?= $row['status'] == 'failed' ? 'selected' : '' ?>>Failed</option>
              </select>
            </div>
          </div>
          <div class="box-footer">
            <button type="submit" name="update" class="btn btn-primary">Update</button>
            <a href="viewtransactions.php" class="btn btn-default">Cancel</a>
          </div>
        </form>
      </div>
    </section>
  </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
</body>
</html>
