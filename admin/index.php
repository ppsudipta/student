<?php 
session_start();
if (isset($_SESSION['username'])) {
    header("location:allregister.php");
}
?>
<?php require_once('config.php');
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
</script>

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
      <div class="form-group">
        <i class="fa fa-user input-icon"></i>
        <input type="text" class="form-control" name="username" id="username" placeholder="Username">
      </div>
      <div class="form-group">
        <i class="fa fa-lock input-icon"></i>
        <input type="password" class="form-control" name="password" id="password" placeholder="Password">
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

<?php
if (isset($_POST['submit'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  if ($con->error) {
    echo $con->error;
  } else {
    $sql = "SELECT * FROM admin WHERE username='$username'";
    $res = $con->query($sql);
    $c = $res->num_rows;
    if ($c >= 1) {
      $row = $res->fetch_array();
    //   $hashed_password = $row['password'];
      $hashed_password = $row['password'];

      if ($password) {
        $_SESSION['username'] = $username;
        echo "<script>window.location.href='allregister.php'</script>";
      } else {
        echo "<script>alert('Invalid password')</script>";
      }
    } else {
      echo "<script>alert('Invalid username')</script>";
      echo "<script>window.location.href='index.php'</script>";
    }
  }
}
?>
