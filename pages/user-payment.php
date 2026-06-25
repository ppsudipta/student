<?php
session_start();
include('../config.php');

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

$name = $row2['name'] ?? 'Guest';
$sid = $row2['registration_code'] ?? '';
$img = $row2['image'] ?? 'user.png';
$id = $row2['id'];
$address = $row2['address'] ?? '';
$phone = $row2['mobile_number'] ?? '';

// Get payment methods for the student
$payment_stmt = $con->prepare("SELECT * FROM student_payments WHERE student_id = ? ORDER BY is_default DESC, is_qr_code ASC");
$payment_stmt->bind_param("i", $id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$payment_methods = [];
while ($payment_row = $payment_result->fetch_assoc()) {
    $payment_methods[] = $payment_row;
}

// Get company info
$sql = "SELECT * FROM company";
$res = $con->query($sql);
$rowm = $res->fetch_assoc();
if (isset($_POST['save_card'])) {
    $student_id = $_POST['student_id'];
    $card_type = $_POST['card_type'];
    $card_number = $_POST['card_number'];
    $card_holder_name = $_POST['card_holder_name'];
    $expiry_date = $_POST['expiry_date'];
    $last_four = substr($card_number, -4);

    $stmt = $con->prepare("INSERT INTO student_payments (student_id, payment_method, card_type, card_number, card_last_four, card_holder_name, expiry_date, is_qr_code) VALUES (?, 'card', ?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("isssss", $student_id, $card_type, $card_number, $last_four, $card_holder_name, $expiry_date);
    $stmt->execute();
    echo "<script>location.href=location.href;</script>"; // refresh to show new entry
}
if (isset($_POST['save_qr'])) {
    $student_id = $_POST['student_id'];
    $qr_code_details = $_POST['qr_code_details'] ?? '';

    // Handle file upload
    $targetDir = "../../admin/uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["qr_code_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($imageFileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["qr_code_image"]["tmp_name"], $targetFilePath)) {
            $stmt = $con->prepare("INSERT INTO student_payments (student_id, payment_method, is_qr_code, qr_code_image, qr_code_details) VALUES (?, 'qr', 1, ?, ?)");
            $stmt->bind_param("iss", $student_id, $fileName, $qr_code_details);
            $stmt->execute();
            echo "<script>location.href=location.href;</script>"; // refresh page
        } else {
            echo "<script>alert('Failed to upload QR code image.');</script>";
        }
    } else {
        echo "<script>alert('Only image files (JPG, PNG, GIF, WEBP) are allowed.');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= $rowm['name'] ?></title>

  <!-- favicon -->
  <link rel="shortcut icon" href="../../admin/<?= $rowm['logo'] ?>" type="image/x-icon">

  <!-- bootstrap -->
  <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">

  <!-- swiper -->
  <link rel="stylesheet" href="../../assets/css/swiper-bundle.min.css">

  <!-- datepicker -->
  <link rel="stylesheet" href="../../assets/css/jquery.datetimepicker.css">

  <!-- jquery ui -->
  <link rel="stylesheet" href="../../assets/css/jquery-ui.min.css">

  <!-- date-time-picker -->
  <link rel="stylesheet" href="../../assets/css/datetimepicker.css">

  <!-- common -->
  <link rel="stylesheet" href="../../assets/css/common.css">

  <!-- animations -->
  <link rel="stylesheet" href="../../assets/css/animations.css">

  <!-- welcome -->
  <link rel="stylesheet" href="../../assets/css/welcome.css">

  <!-- profile -->
  <link rel="stylesheet" href="../../assets/css/profile.css">

  <style>
    .qr-code-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 16px;
    }
    .qr-code-image {
      width: 150px;
      height: 150px;
      object-fit: contain;
      background: white;
      padding: 8px;
      border-radius: 8px;
      margin-bottom: 8px;
    }
    .qr-code-details {
      font-size: 12px;
      text-align: center;
      color: #666;
    }
  </style>
</head>
<body class="scrollbar-hidden">
  <!-- splash-screen start -->
  <section id="preloader" class="spalsh-screen">
    <div class="circle text-center">
      <div>
        <h1>Travgo</h1>
        <p>Discover Your Destinition</p>
      </div>
    </div>
    <div class="loader-spinner">
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
    </div>
  </section>
  <!-- splash-screen end -->

  <main class="profile-main user-payment">
    <!-- page-title -->
    <div class="page-title">
      <button type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
        <img src="../../assets/svg/arrow-left-black.svg" alt="arrow">
      </button>
      <h3 class="main-title">My Payment</h3>
      <div class="ms-auto d-flex gap-12">
       <!-- Change from anchor to button to open modal -->
<button type="button" class="plus-btn rounded-full" data-bs-toggle="modal" data-bs-target="#addCardModal">
  <img src="../../assets/svg/plus-outline.svg" alt="icon">
</button>

      <!-- From this -->
<!-- <a href="add-qr.php" ...> -->

<!-- To this -->
<button type="button" class="plus-btn rounded-full" data-bs-toggle="modal" data-bs-target="#addQRModal">
  <img src="../../assets/svg/qr.png" alt="QR">
</button>

      </div>
    </div>

    <!-- payment-method start -->
    <section class="payment-method px-24">
      <?php if (empty($payment_methods)): ?>
        <div class="text-center py-24">
          <p>No payment methods found. Please add a payment method.</p>
        </div>
      <?php else: ?>
        <?php foreach ($payment_methods as $index => $method): ?>
          <?php if ($method['is_qr_code']): ?>
            <!-- QR Code Payment Method -->
            <label for="payment-method-<?= $method['id'] ?>" class="custom-check-container payment-container pt-16">
              <input type="radio" name="payment" id="payment-method-<?= $method['id'] ?>" <?= $method['is_default'] ? 'checked' : '' ?>>
              <span class="checkmark"></span>
              <div class="d-flex gap-12">
                <div class="icon shrink-0 rounded-full d-flex align-items-center justify-content-center">
                  <img src="../../assets/images/profile/qr-code.png" alt="QR Code" class="img-fluid">
                </div>
                <div class="d-block text flex-grow pb-16">
                  <small class="d-block payment-method-card-title">QR Code Payment</small>
                  <div class="qr-code-container">
                    <?php if (!empty($method['qr_code_image'])): ?>
                      <img src="<?= htmlspecialchars($method['qr_code_image']) ?>" alt="QR Code" class="qr-code-image">
                    <?php endif; ?>
                    <?php if (!empty($method['qr_code_details'])): ?>
                      <div class="qr-code-details"><?= htmlspecialchars($method['qr_code_details']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </label>
          <?php else: ?>
            <!-- Regular Card Payment Method -->
            <label for="payment-method-<?= $method['id'] ?>" class="custom-check-container payment-container pt-16">
              <input type="radio" name="payment" id="payment-method-<?= $method['id'] ?>" <?= $method['is_default'] ? 'checked' : '' ?>>
              <span class="checkmark"></span>
              <span class="d-flex gap-12">
                <span class="icon shrink-0 rounded-full d-flex align-items-center justify-content-center">
                  <?php 
                    $card_image = 'credit-card.png'; // default image
                    if (strpos(strtolower($method['card_type']), 'visa') !== false) {
                      $card_image = 'visa.png';
                    } elseif (strpos(strtolower($method['card_type']), 'master') !== false) {
                      $card_image = 'master.png';
                    } elseif (strpos(strtolower($method['card_type']), 'bca') !== false) {
                      $card_image = 'bca.png';
                    }
                  ?>
                  <img src="../../assets/images/profile/<?= $card_image ?>" alt="card" class="img-fluid">
                </span>
                <span class="d-block text flex-grow pb-16">
                  <small class="d-block payment-method-card-title"><?= htmlspecialchars($method['card_type']) ?></small>
                  <small class="d-block pt-04 pb-8 payment-method-card-num">•••• •••• •••• <?= htmlspecialchars($method['card_last_four']) ?></small>
                  <small class="d-block payment-method-card-num"><?= htmlspecialchars($method['card_holder_name']) ?></small>
                </span>
              </span>
            </label>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
      <!-- Card Payment Modal -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addCardForm" method="POST" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addCardModalLabel">Add Card Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="student_id" value="<?= $id ?>">
          <div class="mb-3">
            <label>Card Type</label>
            <input type="text" name="card_type" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Card Number</label>
            <input type="text" name="card_number" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Card Holder Name</label>
            <input type="text" name="card_holder_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Expiry Date</label>
            <input type="text" name="expiry_date" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="save_card" class="btn btn-primary">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- QR Code Payment Modal -->
<div class="modal fade" id="addQRModal" tabindex="-1" aria-labelledby="addQRModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addQRForm" method="POST" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addQRModalLabel">Add QR Code Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="student_id" value="<?= $id ?>">
          <div class="mb-3">
            <label>QR Code Image</label>
            <input type="file" name="qr_code_image" class="form-control" accept="image/*" required>
          </div>
          <div class="mb-3">
            <label>QR Code Details</label>
            <textarea name="qr_code_details" class="form-control" rows="3" placeholder="e.g., UPI ID or Bank Info"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="save_qr" class="btn btn-primary">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>

    </section>
    <!-- payment-method end -->

    <!-- select-btn start -->
    <div class="select-btn bottom-btn px-24 pt-24 pb-36">
      <button type="button" class="btn-primary">Select Payment</button>
    </div>
    <!-- select-btn end -->
  </main>

  <!-- jquery -->
  <script src="../../assets/js/jquery-3.6.1.min.js"></script>

  <!-- bootstrap -->
  <script src="../../assets/js/bootstrap.bundle.min.js"></script>

  <!-- jquery ui -->
  <script src="../../assets/js/jquery-ui.js"></script>

  <!-- mixitup -->
  <script src="../../assets/js/mixitup.min.js"></script>

  <!-- gasp -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/gsap.min.js"></script>

  <!-- draggable -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/Draggable.min.js"></script>

  <!-- swiper -->
  <script src="../../assets/js/swiper-bundle.min.js"></script>

  <!-- datepicker -->
  <script src="../../assets/js/jquery.datetimepicker.full.js"></script>

  <!-- google-map api -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCodvr4TmsTJdYPjs_5PWLPTNLA9uA4iq8&callback=initMap" type="text/javascript"></script>

  <!-- script -->
  <script src="../../assets/js/script.js"></script>
</body>
</html>