<?php
session_start();
include('./config.php');
include('./header.php');

if (!isset($_SESSION['username'])) {
    header('Location: ./auth/signin.php');
    exit();
}

$use = $_SESSION['username'];

// Get student info
$stmt = $con->prepare("SELECT * FROM students WHERE name = ?");
$stmt->bind_param("s", $use);
$stmt->execute();
$res = $stmt->get_result();
$row2 = $res->fetch_assoc();
if (!$row2) {
    die("Student profile not found.");
}
$id = $row2['id'];
$name = $row2['name'] ?? '';
$email = $row2['email'] ?? '';
$image = $row2['image'] ?? '../admin/user.png';
$dob = $row2['date'] ?? '';
$gender = $row2['gender'] ?? '';
$mobile_number = $row2['mobile_number'] ?? '';
$address = $row2['address'] ?? '';
$registration_code = $row2['registration_code'] ?? '';
$course = $row2['course'] ?? '';
$class = $row2['class'] ?? '';
$session = $row2['session'] ?? '';
$father_name = $row2['father_name'] ?? '';
$school_name = $row2['school_name'] ?? '';
$last_percentage = $row2['last_percentage'] ?? '';
$total_fees = $row2['total_fees'] ?? 0;

// Get total paid fees from donations table (only successful payments)
$stmt = $con->prepare("SELECT SUM(amount) as total_paid FROM donations WHERE donor_email = ? AND status = 'success'");
$stmt->bind_param("s", $email);
$stmt->execute();
$paid_result = $stmt->get_result();
$paid_row = $paid_result->fetch_assoc();
$paid_fees = $paid_row['total_paid'] ?? 0;

// Calculate due fees
$due_fees = $total_fees - $paid_fees;

// Get payment history
$stmt = $con->prepare("SELECT * FROM donations WHERE donor_email = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $email);
$stmt->execute();
$payment_history = $stmt->get_result();

// Get company info
$sql = "SELECT * FROM company";
$res = $con->query($sql);
$rowm = $res->fetch_assoc();
if (isset($_POST['upload_image']) && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $newName = $id . "_" . time() . "." . $ext;
        $uploadPath = "../img/" . basename($newName);

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Save relative path in DB
            $stmt = $con->prepare("UPDATE students SET image = ? WHERE id = ?");
            $stmt->bind_param("si", $uploadPath, $id);
            $stmt->execute();

            // Refresh page to show new image
            header("Location:user-info.php");
            
        } else {
            echo "<script>alert('Upload failed! Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Only JPG, PNG, GIF files are allowed!');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $rowm['name'] ?> - Student Profile</title>
  <link rel="shortcut icon" href="../admin/<?= $rowm['logo'] ?>" type="image/x-icon" />
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/common.css" />
  <style>
    .profile-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    .profile-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .profile-image {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      margin: 0 auto 20px;
      display: block;
      border: 3px solid #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .profile-details {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 30px;
    }
    .detail-row {
      display: flex;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }
    .detail-label {
      font-weight: bold;
      width: 200px;
      color: #555;
    }
    .detail-value {
      flex: 1;
    }
    .fees-summary {
    
      padding: 20px;
      border-radius: 8px;
      margin-top: 30px;
    }
    .fees-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .fees-total {
      font-weight: bold;
      border-top: 1px solid #ddd;
      padding-top: 10px;
      margin-top: 10px;
    }
    .payment-history {
      margin-top: 30px;
    }
    .payment-item {
    
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .payment-status {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 4px;
      font-size: 0.9em;
    }
    .status-success {
      background-color: #d4edda;
      color: #155724;
    }
    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }
    .status-failed {
      background-color: #f8d7da;
      color: #721c24;
    }
  </style>
</head>
<body>

<div class="profile-container">
  <div class="profile-header">
    <h1>Student Profile</h1>
    <img src="<?= $image ?>" alt="Profile Image" class="profile-image">

<form action="" method="post" enctype="multipart/form-data" style="margin-top:15px;">
  <input type="file" name="profile_image" accept="image/*" required>
  <button type="submit" name="upload_image" class="btn btn-primary btn-sm mt-2">Upload Image</button>
</form>

    <h2><?= htmlspecialchars($name) ?></h2>
    <p class="text-muted"><?= htmlspecialchars($registration_code) ?></p>
  </div>

  <div class="profile-details">
    <h3 class="mb-4">Personal Information</h3>
    
    <div class="detail-row">
      <div class="detail-label">Full Name:</div>
      <div class="detail-value"><?= htmlspecialchars($name) ?></div>
    </div>
    
    <div class="detail-row">
      <div class="detail-label">Email:</div>
      <div class="detail-value"><?= htmlspecialchars($email) ?></div>
    </div>
    
    <div class="detail-row">
      <div class="detail-label">Mobile Number:</div>
      <div class="detail-value"><?= htmlspecialchars($mobile_number) ?></div>
    </div>
    
    <div class="detail-row">
      <div class="detail-label">Date of Birth:</div>
      <div class="detail-value"><?= htmlspecialchars($dob) ?></div>
    </div>
    
   
    
    <div class="detail-row">
      <div class="detail-label">Address:</div>
      <div class="detail-value"><?= htmlspecialchars($address) ?></div>
    </div>
    
    <div class="detail-row">
      <div class="detail-label">Father's Name:</div>
      <div class="detail-value"><?= htmlspecialchars($father_name) ?></div>
    </div>
    
    <div class="detail-row">
      <div class="detail-label">School Name:</div>
      <div class="detail-value"><?= htmlspecialchars($school_name) ?></div>
    </div>
    
    <div class="detail-row">
      <div class="detail-label">Last Percentage:</div>
      <div class="detail-value"><?= htmlspecialchars($last_percentage) ?>%</div>
    </div>

    <h3 class="mb-4 mt-5">Academic Information</h3>
    
    <div class="detail-row">
      <div class="detail-label">Registration Code:</div>
      <div class="detail-value"><?= htmlspecialchars($registration_code) ?></div>
    </div>
    
   
    
    <div class="detail-row">
      <div class="detail-label">Class:</div>
      <div class="detail-value"><?= htmlspecialchars($class) ?></div>
    </div>
    
    <div class="detail-row">
      <div class="detail-label">Session:</div>
      <div class="detail-value"><?= htmlspecialchars($session) ?></div>
    </div>

    <div class="fees-summary">
      <h3 class="mb-4">Fees Information</h3>
      
      <div class="fees-item">
        <span>Total Fees:</span>
        <span>₹<?= number_format($total_fees, 2) ?></span>
      </div>
      
      <div class="fees-item">
        <span>Paid Fees:</span>
        <span>₹<?= number_format($paid_fees, 2) ?></span>
      </div>
      
      <div class="fees-item fees-total">
        <span>Due Fees:</span>
        <span>₹<?= number_format(max($due_fees, 0), 2) ?></span>
      </div>
    </div>

    <?php if ($payment_history->num_rows > 0): ?>
    <div class="payment-history">
      <h3 class="mb-4">Payment History</h3>
      
      <?php while ($payment = $payment_history->fetch_assoc()): ?>
        <div class="payment-item">
          <div class="d-flex justify-content-between mb-2">
            <strong>₹<?= number_format($payment['amount'], 2) ?></strong>
            <span class="payment-status status-<?= $payment['status'] ?>">
              <?= ucfirst($payment['status']) ?>
            </span>
          </div>
          <div class="text-muted small mb-2">
            <?= date('d M Y, h:i A', strtotime($payment['created_at'])) ?>
          </div>
          <div class="mb-1">
            <strong>Reason:</strong> <?= htmlspecialchars($payment['payment_reason']) ?>
          </div>
          <?php if (!empty($payment['payu_transaction_id'])): ?>
            <div class="small">
              <strong>Transaction ID:</strong> <?= htmlspecialchars($payment['payu_transaction_id']) ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($payment['payment_mode'])): ?>
            <div class="small">
              <strong>Payment Mode:</strong> <?= htmlspecialchars($payment['payment_mode']) ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>
  </div>
  <a class="btn btn-primary btn-sm mt-2" href="../admin/view_idcard.php?id=<?= $id ?>">View Id Card</a>
</div>
<?php include('./footer.php');?>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>