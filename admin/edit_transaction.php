<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');
include_once __DIR__ . '/fees_helper.php';

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

$fee_months = fees_month_names();
$year_options = fees_year_options();
$parsed = fees_parse_period((string) $row['payment_reason'], (string) $row['donation_date']);
$edit_month = $parsed['month'] ?? '';
$edit_year = $parsed['year'] ?? (int) date('Y', strtotime((string) $row['donation_date']));

if (isset($_POST['update'])) {
    $name = trim((string) ($_POST['donor_name'] ?? ''));
    $email = trim((string) ($_POST['donor_email'] ?? ''));
    $phone = trim((string) ($_POST['donor_phone'] ?? ''));
    $amount = (float) ($_POST['amount'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? 'pending'));
    $fee_month = trim((string) ($_POST['fee_month'] ?? ''));
    $fee_year = trim((string) ($_POST['fee_year'] ?? ''));

    if (!in_array($fee_month, $fee_months, true) || !preg_match('/^\d{4}$/', $fee_year)) {
        echo "<script>alert('Please select a valid fee month and year');</script>";
    } else {
        $reason = fees_format_period($fee_month, $fee_year);
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
              <label>Payment For Month</label>
              <div class="row">
                <div class="col-sm-6">
                  <select name="fee_month" class="form-control" required>
                    <?php foreach ($fee_months as $month): ?>
                      <option value="<?= $month ?>" <?= $edit_month === $month ? 'selected' : '' ?>><?= $month ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-sm-6">
                  <select name="fee_year" class="form-control" required>
                    <?php
                    if ($edit_year && !in_array((int) $edit_year, $year_options, true)) {
                        array_unshift($year_options, (int) $edit_year);
                    }
                    foreach ($year_options as $y): ?>
                      <option value="<?= $y ?>" <?= (int) $edit_year === (int) $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
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
