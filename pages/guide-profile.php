<?php
ob_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include('../config.php');

if (!isset($_SESSION['username'])) {
    header('Location: ../auth/signin.php');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $con->prepare("SELECT * FROM teachers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    die('Teacher not found.');
}

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
  <title><?= htmlspecialchars($teacher['name']) ?> - <?= $rowm['name'] ?></title>

  <!-- css links -->
  <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../assets/css/swiper-bundle.min.css">
  <link rel="stylesheet" href="../../assets/css/jquery.datetimepicker.css">
  <link rel="stylesheet" href="../../assets/css/jquery-ui.min.css">
  <link rel="stylesheet" href="../../assets/css/common.css">
  <link rel="stylesheet" href="../../assets/css/animations.css">
  <link rel="stylesheet" href="../../assets/css/welcome.css">
  <link rel="stylesheet" href="../../assets/css/profile.css">
</head>

<body class="scrollbar-hidden">

<main class="guide-profile">
  <!-- Banner -->
  <section class="banner position-relative">
<img src="../../admin/<?= htmlspecialchars($teacher['photo']) ?>" alt="Banner" class="w-100 img-fluid" style="max-height:700px; object-fit:cover; border-radius:12px;">
    <div class="page-title">
      <a href="javascript:history.back();" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
        <img src="../../assets/svg/arrow-left-black.svg" alt="arrow">
      </a>
      <h3 class="main-title">Profile</h3>
    </div>
  </section>

  <!-- Profile Info -->
  <section class="profile-info px-24">
    <div class="image overflow-hidden radius-10">
      <img src="../../admin/<?= htmlspecialchars($teacher['photo']) ?>" alt="profile" class="img-fluid w-100">
    </div>
    <h3><?= htmlspecialchars($teacher['name']) ?></h3>
    <p>
      Subject: <?= htmlspecialchars($teacher['subject']) ?><br>
      Phone: <?= htmlspecialchars($teacher['phone']) ?><br>
      Email: <?= htmlspecialchars($teacher['email']) ?>
    </p>
    <div class="d-flex align-items-center gap-16">
      <a href="tel:<?= htmlspecialchars($teacher['phone']) ?>" class="call-btn flex-grow d-inline-block radius-12">Call Now</a>
      <a href="mailto:<?= htmlspecialchars($teacher['email']) ?>" class="msg-btn shrink-0 d-inline-block radius-12">Send Email</a>
    </div>
  </section>
<br><br>
  <!-- About -->
  <section class="profile-about px-24 pb-24">
    <div class="title mb-8">
      <h4>Bio</h4>
    </div>
    <p>
      <?= nl2br(htmlspecialchars($teacher['about'])) ?>
    </p>
  </section>

  <!-- Location (dummy) -->
  <!--<section class="profile-location px-24 pb-24">-->
  <!--  <div class="title mb-8">-->
  <!--    <h4>Location</h4>-->
  <!--  </div>-->
  <!--  <div class="overflow-hidden radius-8 map">-->
  <!--    <iframe src="https://www.google.com/maps/embed?pb=!1m18..." style="border:0;" allowfullscreen="" loading="lazy"></iframe>-->
  <!--  </div>-->
  <!--</section>-->
</main>

<!-- JS Files -->
<script src="../../assets/js/jquery-3.6.1.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/jquery-ui.js"></script>
<script src="../../assets/js/mixitup.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/Draggable.min.js"></script>
<script src="../../assets/js/swiper-bundle.min.js"></script>
<script src="../../assets/js/jquery.datetimepicker.full.js"></script>
<script src="../../assets/js/script.js"></script>
</body>
</html>
