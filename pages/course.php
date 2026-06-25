<?php
ob_start();
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
$id = $row2['id'] ?? '';

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

  <title><?= $rowm['name'] ?></title>

  <!-- favicon -->
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

  <!-- details -->
  <link rel="stylesheet" href="../assets/css/home.css">
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

  <main class="tour-guide">
    <!-- page-title -->
    <div class="page-title">
      <button type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
        <img src="../assets/svg/arrow-left-black.svg" alt="arrow">
      </button>
      <h3 class="main-title">Our Courses</h3>
    </div>

    <section class="guide px-24 pb-24">
      <ul>
        <!-- guide card 1 -->
        
         <?php
      $sql = "SELECT * FROM event";
      $res = $con->query($sql);
      while ($row = $res->fetch_array()) {
      ?>
        
        <li>
          <a href="course_details.php?id=<?= $row['id'] ?>" class="d-flex gap-16 item w-fit shrink-0">
            <div class="image position-relative shrink-0">
              <img src="../admin/<?= $row['image'] ?>" alt="guide" class="guide-img object-fit-cover img-fluid radius-12">
              <div class="rating d-flex align-items-center gap-04 w-fit">
                <img src="../assets/svg/star-yellow.svg" alt="Star">
                <span class="d-inline-block">4.0</span>
              </div>
            </div>
    
            <div class="content">
              <h4><?= $row['name'] ?></h4>
              <!--<h5>Best Teachers</h5>-->
              <p class="d-flex align-items-center gap-8 location">
                <img src="../assets/svg/globe.svg" alt="icon">
               <?= $row['description'] ?>  
              </p>
            </div>
          </a>
        </li>
<?php } ?>
        <!-- guide card 2 -->
        
        
        

        <!-- guide card 6 -->
    
    
    
      </ul>
     

    </section>
  </main>

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