<?php
session_start();
include('./config.php');

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

  <!-- profile -->
  <link rel="stylesheet" href="../assets/css/profile.css">
  <style>
     .highlight {
    height: 45px;
    width: 217px;
    display: flex;
    border-radius: 2.5rem;
    padding: 10px;
  
    transition: 0.5s;
    font-family: "Montserrat", sans-serif;
    font-size: 2rem;
    background-image: linear-gradient(
        to right,
        #8081cf,
        #847dc9,
        #8778c3,
        #8a74bd,
        #8d70b7,
        #8f6db2,
        #9169ac,
        #9266a7,
        #9362a1,
        #935e9a,
        #935b93,
        #93578d
    );
}
  </style>
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

  <main class="user-profile">
    <!-- profile-heading start -->
    <section class="user-profile-heading d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-12">
        <div class="image rounded-full overflow-hidden shrink-0">
          <img src="../admin/<?= $img ? $img :'user.png';  ?>" alt="avatar" class="img-fluid w-100 h-100 object-fit-cover">
        </div>
        <div>
          <h3> <?= $name ?></h3>
          <p class="d-flex align-items-center gap-04 location mt-04">
            <img src="../assets/svg/map-marker.svg" alt="icon">
             <?= $address ?>
          </p>
        </div>
      </div>

      <!--<a href="user-info.php" class="edit-info">-->
      <!--  <img src="../assets/svg/edit.svg" alt="icon">-->
      <!--</a>-->
    </section>
    <!-- profile-heading end -->

    <!-- user-personal start -->
    <section class="user-personal">
      <!-- Personal Info -->
      <div class="mt-32">
        <h4 class="mb-16">Personal Info</h4>
        <ul class="setting-list">
          <!--<li>-->
          <!--  <a href="user-address.php" class="d-flex align-items-center justify-content-between">-->
          <!--    <div class="d-flex align-items-center gap-12 shrink-0">-->
          <!--      <img src="../assets/svg/location.svg" alt="icon">-->
          <!--      <p>My Address</p>-->
          <!--    </div>-->
  
          <!--    <img src="../assets/svg/chevron-right.svg" alt="Icon">-->
          <!--  </a>-->
          <!--</li>-->
          <li>
            <a href="user-info.php" class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-12 shrink-0">
                <img src="../assets/svg/edit.svg" alt="icon">
                <p>My Account Details</p>
              </div>
  
              <img src="../assets/svg/chevron-right.svg" alt="Icon">
            </a>
          </li>
          <li class="highlight">
            <a href="./payment.php" class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-12 shrink-0">
                <img src="../assets/svg/work.svg" alt="icon">
                <p style="color:white;" >Payment Method</p>
              </div>
  
              <img src="../assets/svg/chevron-right.svg" alt="Icon">
            </a>
          </li>
        </ul>
      </div>

      <!-- Security -->
      <div class="mt-32">
        <h4 class="mb-16">Security</h4>
        <ul class="setting-list">
          <li>
            <a href="change-password.php" class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-12 shrink-0">
                <img src="../assets/svg/lock-close.svg" alt="icon">
                <p>Change Password</p>
              </div>
  
              <img src="../assets/svg/chevron-right.svg" alt="Icon">
            </a>
          </li>
          <!-- <li>
            <a href="forgot-password.php" class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-12 shrink-0">
                <img src="../assets/svg/lock-open.svg" alt="icon">
                <p>Forgot Password</p>
              </div>
  
              <img src="../assets/svg/chevron-right.svg" alt="Icon">
            </a>
          </li> -->
          <!--<li>-->
          <!--  <a href="#" class="d-flex align-items-center justify-content-between">-->
          <!--    <div class="d-flex align-items-center gap-12 shrink-0">-->
          <!--      <img src="../assets/svg/shield.svg" alt="icon">-->
          <!--      <p>Security</p>-->
          <!--    </div>-->
  
          <!--    <img src="../assets/svg/chevron-right.svg" alt="Icon">-->
          <!--  </a>-->
          <!--</li>-->
          <li>
            <a href="./notification.php" class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-12 shrink-0">
                <img src="../assets/svg/bell-black.svg" alt="icon">
                <p>Notifications</p>
              </div>
  
              <img src="../assets/svg/chevron-right.svg" alt="Icon">
            </a>
          </li>
        </ul>
      </div>

      <!-- General -->
      <div class="mt-32">
        <h4 class="mb-16">General</h4>
        <ul class="setting-list">
          <li>
            <a href="./progress.php" class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-12 shrink-0">
                <img src="../assets/svg/globe.svg" alt="icon">
                <p>Progress Report</p>
              </div>
  
              <img src="../assets/svg/chevron-right.svg" alt="Icon">
            </a>
          </li>
          <!--<li>-->
          <!--  <a href="#" class="d-flex align-items-center justify-content-between">-->
          <!--    <div class="d-flex align-items-center gap-12 shrink-0">-->
          <!--      <img src="../assets/svg/trash.svg" alt="icon">-->
          <!--      <p>Clear Cache</p>-->
          <!--    </div>-->
  
          <!--    <small>88 MB</small>-->
          <!--  </a>-->
          <!--</li>-->
        </ul>
      </div>

      <!-- About -->
      <div class="mt-32">
        <h4 class="mb-16">About</h4>
        <ul class="setting-list">
          <li>
            <a href="./terms.php" class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-12 shrink-0">
                <img src="../assets/svg/shield-round.svg" alt="icon">
                <p>Legal and Policies</p>
              </div>
  
              <img src="../assets/svg/chevron-right.svg" alt="Icon">
            </a>
          </li>
          <!--<li>-->
          <!--  <a href="#" class="d-flex align-items-center justify-content-between">-->
          <!--    <div class="d-flex align-items-center gap-12 shrink-0">-->
          <!--      <img src="../assets/svg/question.svg" alt="icon">-->
          <!--      <p>Help & Support</p>-->
          <!--    </div>-->
  
          <!--    <img src="../assets/svg/chevron-right.svg" alt="Icon">-->
          <!--  </a>-->
          <!--</li>-->
          <!--<li>-->
          <!--  <div class="d-flex align-items-center justify-content-between">-->
          <!--    <div class="d-flex align-items-center gap-12 shrink-0">-->
          <!--      <img src="../assets/svg/activity.svg" alt="icon">-->
          <!--      <p class="mode-text">Dark Mode</p>-->
          <!--    </div>-->
  
          <!--    <label class="toggle-switch">-->
          <!--      <input type="checkbox" class="mode-switch" id="check-mode">-->
          <!--      <span class="slider"></span>-->
          <!--    </label>-->
          <!--  </div>-->
          <!--</li>-->
        </ul>
      </div>
    </section>
    <!-- user-personal end -->

    <!-- logout button start -->
    <div class="py-32">
      <a href="./logout.php" type="button" class="btn-primary-outline" data-bs-toggle="" data-bs-target="">Log Out</a>
    </div>
    <!-- logout button end -->
  </main>

  <!-- bottom navigation start -->
  <footer class="bottom-nav">
    <ul class="d-flex align-items-center justify-content-around w-100 h-100">
      <li>
        <a href="./home.php">
          <img src="../assets/svg/bottom-nav/home.svg" alt="home">
        </a>
      </li>
      <li>
        <a href="./explore.php">
          <img src="../assets/svg/bottom-nav/category.svg" alt="category">
        </a>
      </li>
      <li>
        <a href="./ticket-booked.php">
          <img src="../assets/svg/bottom-nav/ticket.svg" alt="ticket">
        </a>
      </li>
      <li>
        <a href="./wishlist.php">
          <img src="../assets/svg/bottom-nav/heart.svg" alt="heart">
        </a>
      </li>
      <li>
        <a href="user-profile.php">
          <img src="../assets/svg/bottom-nav/profile-active.svg" alt="profile">
        </a>
      </li>
    </ul>
  </footer>
  <!-- bottom navigation end -->

  <!-- edit-profile modal start -->
  <div class="modal fade logOutModal modalBg" id="logOutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header justify-content-end">
          <button type="button" class="close-btn d-flex align-items-center justify-content-center rounded-full" data-bs-dismiss="modal" aria-label="Close">
            <img src="../assets/svg/close-black.svg" alt="icon">
          </button>
        </div>
        <div class="modal-body text-center">
          <h4 class="mb-32">Are you sure you want to logout?</h4>
          <ul>
            <li class="mb-04">
              <button type="button" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
            </li>
            <li>
              <button type="button" class="log-out" data-bs-dismiss="modal" aria-label="Close">Log Out</button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <!-- edit-profile modal end -->

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