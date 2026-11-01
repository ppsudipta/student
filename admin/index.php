<?php
session_start();
require_once('config.php');

if (isset($_SESSION['username'])) {
    header('Location: allregister.php');
    exit();
}

$login_error = '';

if (isset($_POST['submit'])) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $login_error = 'Username and password are required.';
    } else {
        $stmt = $con->prepare('SELECT username, password FROM admin WHERE username = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            $valid = false;
            if ($row) {
                $stored = (string) $row['password'];
                if ($stored !== '' && password_verify($password, $stored)) {
                    $valid = true;
                } elseif ($stored !== '' && !str_starts_with($stored, '$2y$') && !str_starts_with($stored, '$2a$') && !str_starts_with($stored, '$argon') && hash_equals($stored, $password)) {
                    // Legacy plaintext password — accept once, then upgrade to hash
                    $valid = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $con->prepare('UPDATE admin SET password = ? WHERE username = ?');
                    if ($upd) {
                        $upd->bind_param('ss', $newHash, $row['username']);
                        $upd->execute();
                        $upd->close();
                    }
                }
            }

            if ($valid) {
                session_regenerate_id(true);
                $_SESSION['username'] = $row['username'];
                header('Location: allregister.php');
                exit();
            }
        }
        $login_error = 'Invalid username or password.';
    }
}

$sqlc = "SELECT * FROM company";
$resc = $con->query($sqlc);
$rowm = $resc->fetch_assoc();
?>

<script type="text/javascript">
function checkForm(form) {
    if (form.username.value == "") {
        alert("Error: Username cannot be blank!");
        form.username.focus();
        return false;
    }
    if (form.password.value == "") {
        alert("Error: Please enter your password.");
        form.password.focus();
        return false;
    }
}

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Styles -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: #f2f2f2;
    }

    .login-container {
      display: flex;
      height: 100vh;
      flex-wrap: wrap;
    }

    .login-left, .login-right {
      flex: 1;
      min-width: 300px;
    }

    .login-left {
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 40px;
    }

    .login-left h2 {
      color: #cc6600;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .login-left p {
      font-size: 14px;
      color: #666;
    }

    .login-form {
      width: 100%;
      max-width: 360px;
      margin-top: 20px;
    }

    .form-group {
      position: relative;
      margin-bottom: 20px;
    }

    .form-control {
      padding-left: 40px;
      height: 45px;
      border-radius: 6px;
    }

    .input-icon {
      position: absolute;
      left: 12px;
      top: 12px;
      color: #cc6600;
      font-size: 16px;
    }

    .btn-primary {
      background-color: #cc6600;
      border: none;
      height: 45px;
      font-weight: 500;
    }

    .btn-primary:hover {
      background-color: #a64d00;
    }

    .login-right {
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #fff;
      padding: 20px;
    }

    .login-right img {
      max-width: 100%;
      max-height: 90vh;
      object-fit: contain;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    @media (max-width: 768px) {
      .login-container {
        flex-direction: column;
      }
      .login-right {
        height: 250px;
        padding: 10px;
      }
    }
  </style>
</head>
<body>

<div class="login-container">
  <!-- Left side: Form -->
  <div class="login-left">
    <h2>Admin Login</h2>
    <p>Welcome back! Please sign in.</p>

    <form class="login-form" action="" method="post" onsubmit="return checkForm(this);">
      <?php if ($login_error !== ''): ?>
        <div class="alert alert-danger" style="padding:8px 12px;margin-bottom:15px;"><?= htmlspecialchars($login_error) ?></div>
      <?php endif; ?>
      <div class="form-group">
        <i class="fa fa-user input-icon"></i>
        <input type="text" class="form-control" name="username" id="username" placeholder="Username" autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <i class="fa fa-lock input-icon"></i>
        <input type="password" class="form-control" name="password" id="password" placeholder="Password" autocomplete="current-password">
      </div>
      <button type="submit" name="submit" class="btn btn-primary btn-block">Sign In</button>
    </form>
  </div>

  <!-- Right side: Image -->
  <div class="login-right">
    <img src="<?= $rowm['logo']; ?>" alt="Sunrise Academy">
  </div>
</div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
