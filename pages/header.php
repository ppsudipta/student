<?php
// Show errors in development (optional)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log errors to a specific file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.log'); // log file in same folder
?>
<?php
session_start();
include('config.php');

if (!isset($_SESSION['username'])) {
    header('Location: ./auth/signin.php');
    exit();
}

$use = $_SESSION['username'];

$sql = "SELECT * FROM students WHERE name = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $use);
$stmt->execute();
$res = $stmt->get_result();
$row2 = $res->fetch_assoc();
 $student_id = $row2['id'] ?? 0;
$registration_code = $row2['registration_code'] ?? '';

$name = $row2['name'] ?? 'Guest';
$sid = $row2['registration_code'] ?? '';
$img = $row2['image'] ?? 'user.png';
$address = $row2['address'] ?? '';
$sql="select * from company";
$res=$con->query($sql);
$rowm=$res->fetch_array();
error_reporting(1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= $rowm['name'] ?></title>

  <!-- favicon -->
  <link rel="shortcut icon" href="../admin/<?= $rowm['logo'] ?>" type="image/x-icon">

  <!-- bootstrap -->
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">

  <!-- swiper -->
  <link rel="stylesheet" href="../assets/css/swiper-bundle.min.css">

  <!-- datepicker -->
  <link rel="stylesheet" href="../assets/css/jquery.datetimepicker.css">

  <!-- jquery ui -->
  <link rel="stylesheet" href="../assets/css/jquery-ui.min.css">

  <!-- common -->
  <link rel="stylesheet" href="../assets/css/common.css">

  <!-- animations -->
  <link rel="stylesheet" href="../assets/css/animations.css">

  <!-- welcome -->
  <link rel="stylesheet" href="../assets/css/welcome.css">

  <!-- home -->
  <link rel="stylesheet" href="../assets/css/home.css">
</head>
<body class="scrollbar-hidden">
  <!-- splash-screen start -->
  <!--<section id="preloader" class="spalsh-screen">-->
  <!--  <div class="circle text-center">-->
  <!--    <div>-->
  <!--      <h1><?= $rowm['name'] ?></h1>-->
  <!--      <p>Discover Your Destinition</p>-->
  <!--    </div>-->
  <!--  </div>-->
  <!--  <div class="loader-spinner">-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--    <div></div>-->
  <!--  </div>-->
  <!--</section>-->
  <!-- splash-screen end -->

  <main class="home">
    <!-- menu, side-menu start -->
    <section class="wrapper dz-mode">
      <!-- menu -->
      <div class="menu">
        <button class="toggle-btn">
          <img src="../assets/svg/menu/burger-white.svg" alt="" class="icon">
        </button>
        <div class="btn-grp d-flex align-items-center gap-16">
          <!--<label for="mode-change" class="mode-change d-flex align-items-center justify-content-center">-->
          <!--  <input type="checkbox" id="mode-change">-->
          <!--  <img src="../assets/svg/menu/sun-white.svg" alt="icon" class="sun">-->
          <!--  <img src="../assets/svg/menu/moon-white.svg" alt="icon" class="moon">-->
          <!--</label>-->
          <a href="user-profile.php">
            <img src="../assets/svg/menu/profile-white.svg" alt="icon">
          </a>
        </div>
      </div>
      <div class="m-menu__overlay"></div>
      <!-- main menu -->
      <div class="m-menu">
        <div class="m-menu__header">
          <button class="m-menu__close">
            <img src="../assets/svg/menu/close-white.svg" alt="icon">
          </button>
          <div class="menu-user">
            <img src="../admin/<?= $img ?>" alt="avatar">
            <div >
              <a href="#!"><?= $name?></a>
              <h3>
                Verified user · Membership
              </h3>
            </div>
          </div>
        </div>
        <ul>
          <li>
            <h2 class="menu-title">menu</h2>
          </li>

          <li>
              <a href="home.php">
                <div class="d-flex align-items-center gap-16">
                  <span class="icon">
                    <img src="../assets/svg/menu/pie-white.svg" alt="">
                  </span>
                  Home
                </div>
                <img src="../assets/svg/menu/chevron-right-black.svg" alt="">
              </a>
          </li>
          <li>
              <a href="notification.php">
                <div class="d-flex align-items-center gap-16">
                  <span class="icon">
                    <img src="../assets/svg/menu/page-white.svg" alt="">
                  </span>
                  Notice
                </div>
                <img src="../assets/svg/menu/chevron-right-black.svg" alt="">
              </a>
          </li>
          <li>
            <!--<h2 class="menu-title">others</h2>-->
          </li>

          <li>
            <!--<label class="a-label__chevron" for="item-4">-->
            <!--  <span class="d-flex align-items-center gap-16">-->
            <!--    <span class="icon">-->
            <!--      <img src="../assets/svg/menu/grid-white.svg" alt="">-->
            <!--    </span>-->
            <!--    Exam Zone-->
            <!--  </span>-->
            <!--  <img src="../assets/svg/menu/chevron-right-black.svg" alt="">-->
            <!--</label>-->
            <input type="checkbox" id="item-4" name="item-4" class="m-menu__checkbox">
            <div class="m-menu">
              <div class="m-menu__header">
                <label class="m-menu__toggle" for="item-4">
                  <img src="../assets/svg/menu/back-white.svg" alt="">
                </label>
                <span class="m-menu__header-title">Exam Zone</span>
              </div>
              <ul>
                <li>
                  <a href="../yuva/template/exam/">
                    <div class="d-flex align-items-center gap-16">
                      <span class="icon">
                        <img src="../assets/svg/menu/box-white.svg" alt="icon">
                      </span>
                      Online Exam
                    </div>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <!-- <li>
            <label class="a-label__chevron" for="item-5">
              <span class="d-flex align-items-center gap-16">
                <span class="icon">
                  <img src="../assets/svg/menu/gear-white.svg" alt="">
                </span>
                settings
              </span>
              <img src="../assets/svg/menu/chevron-right-black.svg" alt="">
            </label>
            <input type="checkbox" id="item-5" name="item-5" class="m-menu__checkbox">
            <div class="m-menu">
              <div class="m-menu__header">
                <label class="m-menu__toggle" for="item-5">
                  <img src="../assets/svg/menu/back-white.svg" alt="">
                </label>
                <span class="m-menu__header-title">settings</span>
              </div>
              <ul>
                <li>
                  <a href="./profile/user-address.html">
                    <div class="d-flex align-items-center gap-16">
                      <span class="icon">
                        <img src="../assets/svg/menu/box-white.svg" alt="icon">
                      </span>
                      My Address
                    </div>
                  </a>
                </li>
                <li>
                  <a href="./profile/user-payment.html">
                    <div class="d-flex align-items-center gap-16">
                      <span class="icon">
                        <img src="../assets/svg/menu/box-white.svg" alt="icon">
                      </span>
                      Payment Method
                    </div>
                  </a>
                </li>
                <li>
                  <a href="./profile/change-password.html">
                    <div class="d-flex align-items-center gap-16">
                      <span class="icon">
                        <img src="../assets/svg/menu/box-white.svg" alt="icon">
                      </span>
                      Change Password
                    </div>
                  </a>
                </li>
                <li>
                  <a href="./profile/forgot-password.html">
                    <div class="d-flex align-items-center gap-16">
                      <span class="icon">
                        <img src="../assets/svg/menu/box-white.svg" alt="icon">
                      </span>
                      Forgot Password
                    </div>
                  </a>
                </li>
                <li>
                  <a href="./profile/security.html">
                    <div class="d-flex align-items-center gap-16">
                      <span class="icon">
                        <img src="../assets/svg/menu/box-white.svg" alt="icon">
                      </span>
                      Security
                    </div>
                  </a>
                </li>
                <li>
                  <a href="./profile/user-language.html">
                    <div class="d-flex align-items-center gap-16">
                      <span class="icon">
                        <img src="../assets/svg/menu/box-white.svg" alt="icon">
                      </span>
                      Language
                    </div>
                  </a>
                </li>
                <li>
                  <a href="./profile/notifications.html">
                    <div class="d-flex align-items-center gap-16">
                      <span class="icon">
                        <img src="../assets/svg/menu/box-white.svg" alt="icon">
                      </span>
                      Notifications
                    </div>
                  </a>
                </li>
              </ul>
            </div>
          </li> -->
          <!--<li class="dz-switch">-->
          <!--  <div class="a-label__chevron">-->
          <!--    <div class="d-flex align-items-center gap-16">-->
          <!--      <span class="icon">-->
          <!--        <img src="../assets/svg/menu/moon-white.svg" alt="">-->
          <!--      </span>-->
          <!--      switch to dark mode-->
          <!--    </div>-->
          <!--    <label class="toggle-switch" for="enableMode">-->
          <!--      <input type="checkbox" id="enableMode" class="mode-switch">-->
          <!--      <span class="slider"></span>-->
          <!--    </label>-->
          <!--  </div>-->
          <!--</li>-->
        </ul>
      </div>
      <!-- end main menu -->
    </section>
    <!-- menu, side-menu end -->