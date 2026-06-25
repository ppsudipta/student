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
// Get company info
$sql = "SELECT * FROM company";
$res = $con->query($sql);
$rowm = $res->fetch_assoc();
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

  <main class="profile-main user-address">
    <!-- page-title -->
    <div class="page-title">
      <button type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
        <img src="../../assets/svg/arrow-left-black.svg" alt="arrow">
      </button>
      <h3 class="main-title">My Address</h3>
      <!-- <a href="add-address.php" class="plus-btn d-flex align-items-center justify-content-center ms-auto">
        <img src="../../assets/svg/plus-square.svg" alt="icon">
      </a> -->
    </div>

    <!-- all-address start -->
    <section class="all-address p-24">
      <!-- card 1 -->
      <div class="address-card">
        <label for="address-1" class="custom-check-container address-container">
          <input type="radio" name="address" id="address-1" checked>
          <span class="checkmark"></span>
          <small class="text d-block">
            <small class="address-card-title d-block"><?= $name ?></small>
            <small class="address-card-phn pt-04 pb-8 d-block"><?= $phone ?></small>
            <small class="address-card-phn d-block"><?= $address ?></small>
          </small>
        </label>
        <button type="button" class="change-add">Change Address</button>
      </div>

      <!-- card 2 -->
      <!-- <div class="address-card mt-16">
        <label for="address-2" class="custom-check-container address-container">
          <input type="radio" name="address" id="address-2">
          <span class="checkmark"></span>
          <small class="text d-block">
            <small class="address-card-title d-block">Elevenia Kalia</small>
            <small class="address-card-phn pt-04 pb-8 d-block">+1 234 567 890</small>
            <small class="address-card-phn d-block">1234 Your Road No #6789 <br> Your City, Country</small>
          </small>
        </label>
        <button type="button" class="change-add">Change Address</button>
      </div> -->
    </section>
    <!-- all-address end -->

    <!-- select-btn start -->
    <div class="select-btn bottom-btn px-24 pt-24 pb-36">
      <button type="button" class="btn-primary">Select Address</button>
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