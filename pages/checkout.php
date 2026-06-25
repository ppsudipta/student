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

$name = $row2['name'] ?? 'Guest';
$sid = $row2['registration_code'] ?? '';
$img = $row2['image'] ?? 'user.png';
$address = $row2['address'] ?? '';
$sql="select * from company";
$res=$con->query($sql);
$rowm=$res->fetch_array();
error_reporting(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $rowm['name'] ?></title>

  <!-- favicon -->
  <link rel="shortcut icon" href="../admin/<?= $rowm['logo'] ?>" type="image/x-icon" />

  <!-- Stylesheets -->
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/common.css" />
  <link rel="stylesheet" href="../assets/css/welcome.css" />
</head>
<body class="scrollbar-hidden">

<!-- Splash screen -->
<section id="preloader" class="spalsh-screen">
  <div class="circle text-center">
    <div>
      <h1><?= $rowm['name'] ?></h1>
      <p>Empowering Education</p>
    </div>
  </div>
  <div class="loader-spinner">
    <!-- spinner animation -->
    <div></div><div></div><div></div><div></div><div></div>
    <div></div><div></div><div></div><div></div><div></div>
    <div></div><div></div>
  </div>
</section>

<main class="booking-main checkout-hotel">
  <!-- Page Title -->
  <div class="page-title">
     <a href="home.php" type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
        <img src="../assets/svg/arrow-left-black.svg" alt="arrow">
</a>
    <h3 class="main-title">Company Details</h3>
  </div>

  <div class="details-body">
    <!-- Company Logo and Info -->
    <section class="order-info pb-12">
      <div class="item d-flex align-items-center gap-16 w-100">
        <div class="image shrink-0 radius-8 overflow-hidden" style="width: 140px; height: 100px;">
          <img src="../admin/<?= $rowm['logo'] ?>" alt="Logo" class="img-fluid object-fit-cover w-100 h-100">
        </div>
        <div class="content flex-grow">
          <h4><?= $rowm['name'] ?></h4>
          <p class="d-flex align-items-center gap-04 location mt-04">
            <img src="../assets/svg/map-marker.svg" alt="icon">
            <?= $rowm['address'] ?>
          </p>
          <!-- <p class="mt-04">Established: 17-12-2021</p> -->
        </div>
      </div>
    </section>

    <!-- Contact Info -->
    <section class="customer-info py-12">
      <div class="title mb-16">
        <h4>Contact Information</h4>
      </div>
      <ul>
        <li class="d-flex align-items-center justify-content-between">
          <p>Phone 1</p>
          <p><?= $rowm['ph1'] ?></p>
        </li>
        <li class="d-flex align-items-center justify-content-between">
          <p>Phone 2</p>
          <p><?= $rowm['ph2'] ?></p>
        </li>
        <li class="d-flex align-items-center justify-content-between">
          <p>WhatsApp</p>
          <p><?= $rowm['wp'] ?></p>
        </li>
        <li class="d-flex align-items-center justify-content-between">
          <p>Email</p>
          <p><?= $rowm['email'] ?></p>
        </li>
      </ul>
    </section>

    <!-- Social Links -->
    <section class="customer-info py-12">
      <div class="title mb-16">
        <h4>Social Media</h4>
      </div>
      <ul>
        <li class="d-flex align-items-center justify-content-between">
          <p>Facebook</p>
          <a href="<?= $rowm['fb'] ?>" target="_blank">View</a>
        </li>
        <li class="d-flex align-items-center justify-content-between">
          <p>YouTube</p>
          <a href="<?= $rowm['you'] ?>" target="_blank">View</a>
        </li>
        <li class="d-flex align-items-center justify-content-between">
          <p>Instagram</p>
          <a href="<?= $rowm['insta'] ?>" target="_blank">View</a>
        </li>
      </ul>
    </section>

    <!-- Extra Image -->
    <!--<section class="order-info py-12">-->
    <!--  <div class="title mb-16">-->
    <!--    <h4>Promotional Image</h4>-->
    <!--  </div>-->
    <!--  <div class="image radius-8 overflow-hidden w-100">-->
    <!--    <img src="Unacademy_Logo.png" alt="Promotional" class="img-fluid w-100">-->
    <!--  </div>-->
    <!--</section>-->
  </div>
</main>

<!-- JS -->
<script src="../assets/js/jquery-3.6.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>

</body>
</html>
