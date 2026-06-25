<?php
session_start();
include('config.php');

function normalize_login_text($value) {
    $value = trim((string) $value);
    // Remove invisible Unicode formatting/control marks often introduced by copy/paste.
    $value = preg_replace('/[\p{Cf}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u', '', $value);
    return trim($value);
}

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    header("Location: ../home.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = normalize_login_text($_POST['username'] ?? '');
    $password = normalize_login_text($_POST['password'] ?? '');

    // Phone values are stored as digits in the database, so normalize pasted input.
    $username = preg_replace('/\D+/', '', $username);

    // Check student
    $stmt = $con->prepare("SELECT id, name, status FROM students WHERE mobile_number = ? AND password = ?");
    $stmt->bind_param("ss", $username,$password);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Validate student status
        if ($user['status'] === 'completed') {
            $error = 'Thank you for visiting. You have already completed your course.';
        } else if ($user['status'] === 'ongoing') {
            // Allow login only if status is ongoing
            $_SESSION['username'] = $user['name'];
            $_SESSION['user_id'] = $user['id'];
            header("Location: ../home.php");
            exit();
        } else {
            // Handle other statuses (if any)
            $error = 'Your account status is not active. Please contact to the admin/principal';
        }
    } else {
        $error = 'Invalid mobile number or Password';
    }
}

// Fetch company details
$sql = "SELECT * FROM company";
$res = $con->query($sql);
$rowm = $res->fetch_array();
error_reporting(0);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?= $rowm['name'] ?></title>
  <link rel="shortcut icon" href="../../admin/<?= $rowm['logo'] ?>" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../assets/css/swiper-bundle.min.css">
  <link rel="stylesheet" href="../../assets/css/jquery.datetimepicker.css">
  <link rel="stylesheet" href="../../assets/css/jquery-ui.min.css">
  <link rel="stylesheet" href="../../assets/css/common.css">
  <link rel="stylesheet" href="../../assets/css/animations.css">
  <link rel="stylesheet" href="../../assets/css/welcome.css">
  <link rel="stylesheet" href="../../assets/css/auth.css">
</head>
<body>

<!-- signin start -->
<section class="auth signin">
  <div class="heading">
    <h2>Hi, Welcome Back!</h2>
    <p>Thank you For Visit Again..</p>
  </div>

  <div class="form-area auth-form">
    <?php if ($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
      <div>
        <label for="mobile_number">Mobile Number</label>
        <input name="username" type="text" id="mobile_number" placeholder="Enter your Mobile Number" class="input-field" required>
      </div>
      <div>
        <label for="password">Enter Password</label>
        <input name="password" type="password" id="password" placeholder="Enter Password" class="input-field" required>
      </div>
      <button type="submit" class="btn-primary">Login</button>
    </form>

    <div class="divider d-flex align-items-center justify-content-center gap-12">
      <span class="d-inline-block"></span>
      <span class="d-inline-block"></span>
    </div>

    <h6>Don't have an account? <a href="../register.php">Sign Up</a></h6>
  </div>
</section>
<!-- signin end -->

<!-- Optional: Modal for success login (not triggered here, but kept from your original code) -->
<div class="modal fade loginSuccessModal modalBg" id="loginSuccess" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center">
        <img src="assets/svg/check-green.svg" alt="Check">
        <h3>You have logged in successfully</h3>
        <p class="mb-32">You will be redirected to your dashboard shortly.</p>
        <a href="index.php" class="btn-primary">Continue</a>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>
</body>
</html>
