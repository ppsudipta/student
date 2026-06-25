<?php
session_start();
include('config.php');

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

// Get company info
$sql = "SELECT * FROM company";
$res = $con->query($sql);
$rowm = $res->fetch_assoc();
$sqlb = "SELECT * FROM blog";
$resb = $con->query($sqlb);
$rowb = $resb->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($rowm['name']) ?></title>
  <link rel="shortcut icon" href="../admin/<?= htmlspecialchars($rowm['logo'] ?? '') ?>" type="image/x-icon">

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

  <!-- datetime -->
  <link rel="stylesheet" href="../assets/css/datetimepicker.css">

  <!-- details -->
  <link rel="stylesheet" href="../assets/css/details.css">
</head>
<body class="scrollbar-hidden">
  <!-- splash-screen start -->
  <section id="preloader" class="spalsh-screen">
    <div class="circle text-center">
      <div>
        <h1><?= htmlspecialchars($rowm['name']) ?></h1>
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

  <main class="details hotel-details">
    <!-- banner start -->
    <section class="banner position-relative">
      <img src="../admin/<?= htmlspecialchars($rowb['image'] ?? '') ?>" alt="Banner" class="w-100 img-fluid">
      
      <!-- title -->
      <div class="page-title">
        <button type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
          <img src="../assets/svg/arrow-left-black.svg" alt="arrow">
        </button>
        <h3 class="main-title">About Academy</h3>
      </div>
    </section>
    <!-- banner end -->

    <!-- details-body start -->


      <!-- facilities -->
      <!--<section class="facilities pt-32 pb-16">-->
        <!-- title -->
      <!--  <div class="title d-flex align-items-center justify-content-between">-->
      <!--    <h4 class="shrink-0">Common Facilities</h4>-->
      <!--    <button type="button" data-bs-toggle="modal" data-bs-target="#serviceModal" class="shrink-0 d-inline-block">See All</button>-->
      <!--  </div>-->

      <!--  <div class="grid gap-24">-->
          <!-- item 1 -->
      <!--    <div class="item text-center">-->
      <!--      <div class="icon d-flex align-items-center justify-content-center rounded-full">-->
      <!--        <img src="../assets/svg/wind.svg" alt="icon">-->
      <!--      </div>-->
      <!--      <p>Ac</p>-->
      <!--    </div>-->

          <!-- item 2 -->
      <!--    <div class="item text-center">-->
      <!--      <div class="icon d-flex align-items-center justify-content-center rounded-full">-->
      <!--        <img src="../assets/svg/building.svg" alt="icon">-->
      <!--      </div>-->
      <!--      <p>Restaurant</p>-->
      <!--    </div>-->

          <!-- item 3 -->
      <!--    <div class="item text-center">-->
      <!--      <div class="icon d-flex align-items-center justify-content-center rounded-full">-->
      <!--        <img src="../assets/svg/water.svg" alt="icon">-->
      <!--      </div>-->
      <!--      <p>Swimming Pool</p>-->
      <!--    </div>-->

          <!-- item 4 -->
      <!--    <div class="item text-center">-->
      <!--      <div class="icon d-flex align-items-center justify-content-center rounded-full">-->
      <!--        <img src="../assets/svg/24-support.svg" alt="icon">-->
      <!--      </div>-->
      <!--      <p>24-Hours Front Desk</p>-->
      <!--    </div>-->

      <!--  </div>-->
      <!--</section>-->

      <!-- details-info -->
      <section class="details-info py-16">
        <div class="title">
          <h4>Details</h4>
        </div>
        <p>
         <?= $rowb['details']; ?> 
          <button type="button">More Details</button>
        </p>
      </section>

      <!-- reviews start -->

      <!-- reviews end -->

      <!-- location start -->
      <section class="details-location pt-16">
        <!-- title -->
        <div class="title">
          <h4>Location</h4>
        </div>

        <!-- map -->
        <div class="overflow-hidden radius-16 map">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d29493.841993319696!2d87.8719990743164!3d22.476769!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a02991215555555%3A0xafe01b6e1b62170a!2sPanitras%20Post%20Office!5e0!3m2!1sen!2sin!4v1747636653310!5m2!1sen!2sin" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
      </section>
      <!-- location end -->
    </section>
    <!-- details-body end -->

    <!-- details-footer start -->
   
    <!-- details-footer end -->
  </main>

  <!-- service modal start -->
  
  <!-- service modal end -->

  <!-- jquery -->
  <script src="../assets/js/jquery-3.6.1.min.js"></script>

  <!-- bootstrap -->
  <script src="../assets/js/bootstrap.bundle.min.js"></script>

  <!-- jquery ui -->
  <script src="../assets/js/jquery-ui.js"></script>

  <!-- mixitup -->
  <script src="../assets/js/mixitup.min.js"></script>

  <!-- gasp -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/gsap.min.js"></script>

  <!-- draggable -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/Draggable.min.js"></script>

  <!-- swiper -->
  <script src="../assets/js/swiper-bundle.min.js"></script>

  <!-- datepicker -->
  <script src="../assets/js/jquery.datetimepicker.full.js"></script>

  <!-- google-map api -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCodvr4TmsTJdYPjs_5PWLPTNLA9uA4iq8&callback=initMap" type="text/javascript"></script>

  <!-- script -->
  <script src="../assets/js/script.js"></script>
</body>
</html>