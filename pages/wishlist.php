<?php 
session_start();
include('config.php');
// include('header.php');

if (!isset($_SESSION['username'])) {
    header('Location: ./auth/signin.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!--<title><?= htmlspecialchars($rowm['name']) ?></title>-->
    <title>Sunrise Academy</title>


  <!-- favicon -->
  <link rel="shortcut icon" href="../admin/<?= htmlspecialchars($rowm['logo'] ?? '') ?>" type="image/x-icon">
  <!-- stylesheets -->
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/swiper-bundle.min.css">
  <link rel="stylesheet" href="../assets/css/jquery.datetimepicker.css">
  <link rel="stylesheet" href="../assets/css/jquery-ui.min.css">
  <link rel="stylesheet" href="../assets/css/common.css">
  <link rel="stylesheet" href="../assets/css/animations.css">
  <link rel="stylesheet" href="../assets/css/welcome.css">
  <link rel="stylesheet" href="../assets/css/explore.css">
</head>
<body class="scrollbar-hidden">

<!-- Splash screen -->
<section id="preloader" class="spalsh-screen">
  <div class="circle text-center">
    <div>
      <h1>Sunrise Academy</h1>
      <p>Discover Great Learning</p>
    </div>
  </div>
  <div class="loader-spinner">
    <?php for ($i = 0; $i < 12; $i++) echo "<div></div>"; ?>
  </div>
</section>

<main class="explore wishlist">
  <div class="page-title">
    <h3 class="main-title">My Gallery</h3>
  </div>

  <!-- Gallery Section -->
  <section class="all-place">
    <div class="grid">

    <?php
    $sql = "SELECT * FROM gallery WHERE type = 'promotional'";
    $res = $con->query($sql);
    while ($row = $res->fetch_array()) {
    ?>
      <div class="place-card">
        <a href="javascript:void(0);">
          <div class="image position-relative">
            <img src="../admin/<?= htmlspecialchars($row['image']) ?>" onclick="showImage(this.src)" alt="gallery" class="img-fluid w-100 overflow-hidden radius-8">
            <!--<span class="d-flex align-items-center justify-content-center rounded-full">-->
            <!--  <img src="../assets/svg/heart-red.svg" alt="icon">-->
            <!--</span>-->
          </div>
          <div class="content">
            <h4><?= htmlspecialchars($row['name']) ?></h4>
          </div>
        </a>
      </div>
    <?php } ?>

    </div>
  </section>

  <!-- Image Modal -->
  <div id="imageModal" class="modal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,0.8);">
    <span onclick="closeModal()" style="position:absolute;top:20px;right:35px;color:aqua;font-size:40px;font-weight:bold;cursor:pointer;">&times;</span>
    <img id="modalImage" style="margin:auto;display:block;max-width:90%;max-height:90%;">
  </div>
  <script>
    function showImage(src) {
      document.getElementById("modalImage").src = src;
      document.getElementById("imageModal").style.display = "block";
    }
    function closeModal() {
      document.getElementById("imageModal").style.display = "none";
    }
  </script>

</main>

<?php include('footer.php') ?>

<!-- Scripts -->
<script src="../assets/js/jquery-3.6.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/jquery-ui.js"></script>
<script src="../assets/js/mixitup.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/Draggable.min.js"></script>
<script src="../assets/js/swiper-bundle.min.js"></script>
<script src="../assets/js/jquery.datetimepicker.full.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCodvr4TmsTJdYPjs_5PWLPTNLA9uA4iq8&callback=initMap" type="text/javascript"></script>
<script src="../assets/js/script.js"></script>
</body>
</html>
