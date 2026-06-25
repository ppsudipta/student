<?php
session_start();
include('./config.php');
include('./header.php');

// Redirect if user not logged in
if (!isset($_SESSION['username'])) {
    header('Location: ./auth/signin.php');
    exit();
}

$use = $_SESSION['username'];

// Fetch student info including current password
$stmt = $con->prepare("SELECT * FROM students WHERE name = ?");
$stmt->bind_param("s", $use);
$stmt->execute();
$res = $stmt->get_result();
$row2 = $res->fetch_assoc();

$name = $row2['name'] ?? 'Guest';
$sid = $row2['registration_code'] ?? '';
$img = $row2['image'] ?? 'user.png';
$id = $row2['id'] ?? 0;
$has_password = !empty($row2['password']); // Check if password exists

// Fetch company info
$sql = "SELECT * FROM company";
$res = $con->query($sql);
$rowm = $res->fetch_assoc();

$msg = '';

// Handle password change form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass_input = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // Verify current password first (only if they have a password set)
    if ($has_password && $current_pass_input !== $row2['password']) {
        $msg = "Current password is incorrect.";
    } elseif (strlen($new_pass) < 8) {
        $msg = "Password must be at least 8 characters.";
    } elseif ($has_password && $new_pass === $row2['password']) {
        $msg = "New password must be different from current password.";
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "Passwords do not match.";
    } elseif (!preg_match('/[@!#]/', $new_pass)) {
        $msg = "Password must contain at least one special character like @, !, or #.";
    } else {
        $update = $con->prepare("UPDATE students SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_pass, $id);
        if ($update->execute()) {
            $msg = "Password changed successfully.";
            $has_password = true; // Update flag
        } else {
            $msg = "Failed to change password.";
        }
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
  <link rel="shortcut icon" href="../admin/<?= $rowm['logo'] ?>" type="image/x-icon">

  <!-- CSS -->
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/swiper-bundle.min.css">
  <link rel="stylesheet" href="../assets/css/jquery.datetimepicker.css">
  <link rel="stylesheet" href="../assets/css/jquery-ui.min.css">
  <link rel="stylesheet" href="../assets/css/datetimepicker.css">
  <link rel="stylesheet" href="../assets/css/common.css">
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/welcome.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body class="scrollbar-hidden">

<!-- splash-screen start -->
<section id="preloader" class="spalsh-screen">
  <div class="circle text-center">
    <div>
      <h1><?= $rowm['name'] ?></h1>
      <p>Discover Your Destinition</p>
    </div>
  </div>
  <div class="loader-spinner">
    <?php for ($i = 0; $i < 12; $i++): ?>
      <div></div>
    <?php endfor; ?>
  </div>
</section>
<!-- splash-screen end -->

<main class="profile-main">

  <!-- page-title -->
  <div class="page-title">
    <button type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
      <img src="../assets/svg/arrow-left-black.svg" alt="arrow">
    </button>
    <h3 class="main-title">Change Password</h3>
  </div>

  <!-- change-password section -->
  <section class="change-password px-24">
    <?php if ($msg): ?>
      <div class="alert alert-info text-center"><?= $msg ?></div>
    <?php endif; ?>

    <div class="current-password-info mb-24">
      <p>You <?= $has_password ? 'have' : 'don\'t have' ?> a password set</p>
      <?php if ($has_password): ?>
        <p><?= $row2['password'] ; ?></p>
      <?php endif; ?>
    </div>

    <form method="POST">
      <?php if ($has_password): ?>
        <h4 class="mb-24">Current Password</h4>
        <div class="mb-16">
          <label for="current_pass">Current Password</label>
          <div class="position-relative">
            <input type="password" name="current_password" id="current_pass" placeholder="Enter your current password" required class="input-psswd input-field d-block">
            <button type="button" class="eye-btn">
              <span class="eye-off"><img src="../assets/svg/eye-off.svg" alt="Eye Off"></span>
              <span class="eye-on d-none"><img src="../assets/svg/eye-on.svg" alt="Eye On"></span>
            </button>
          </div>
        </div>
      <?php endif; ?>

      <h4 class="mb-24">New Password</h4>
      <p class="mb-16"><?= $has_password ? 'The new password must be different from your current password' : 'Set a new password' ?></p>

      <div class="mb-16">
        <label for="new_pass">New Password</label>
        <div class="position-relative">
          <input type="password" name="new_password" id="new_pass" placeholder="Enter your new password" required class="input-psswd input-field d-block">
          <button type="button" class="eye-btn">
            <span class="eye-off"><img src="../assets/svg/eye-off.svg" alt="Eye Off"></span>
            <span class="eye-on d-none"><img src="../assets/svg/eye-on.svg" alt="Eye On"></span>
          </button>
        </div>
      </div>

      <ul class="mb-16">
        <li class="d-flex gap-04"><img src="../assets/svg/check-green-outline.svg" alt="icon"><p>At least 8 characters</p></li>
        <li class="d-flex gap-04"><img src="../assets/svg/check-green-outline.svg" alt="icon"><p>Include a symbol like @!#</p></li>
      </ul>

      <div class="mb-16">
        <label for="confirm_pass">Confirm New Password</label>
        <div class="position-relative">
          <input type="password" name="confirm_password" id="confirm_pass" placeholder="Confirm your new password" required class="input-psswd input-field d-block">
          <button type="button" class="eye-btn">
            <span class="eye-off"><img src="../assets/svg/eye-off.svg" alt="Eye Off"></span>
            <span class="eye-on d-none"><img src="../assets/svg/eye-on.svg" alt="Eye On"></span>
          </button>
        </div>
      </div>

      <div class="submit-btn pt-24 pb-36">
        <button type="submit" class="btn-primary"><?= $has_password ? 'Change Password' : 'Set Password' ?></button>
      </div>
    </form>
  </section>
</main>

<!-- JS -->
<script src="../assets/js/jquery-3.6.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/jquery-ui.js"></script>
<script src="../assets/js/mixitup.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/Draggable.min.js"></script>
<script src="../assets/js/swiper-bundle.min.js"></script>
<script src="../assets/js/jquery.datetimepicker.full.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCodvr4TmsTJdYPjs_5PWLPTNLA9uA4iq8&callback=initMap"></script>
<script src="../assets/js/script.js"></script>
<?php include('./footer.php'); ?>
</body>
</html>