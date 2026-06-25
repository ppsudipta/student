<?php
include('header.php');

// Function to check for unseen notices
function hasUnseenNotices($con, $student_id) {
    // You might need to add a 'seen' column to your notices table
    // For now, we'll check if there are any notices for this student
    // If you add a 'seen' column later, change the query to:
    // "SELECT COUNT(*) FROM notices WHERE student_id = $student_id AND seen = 0"
    
    $query = "SELECT COUNT(*) as count FROM notices WHERE student_id = $student_id";
    $result = $con->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'] > 0;
    }
    
    return false;
}

// Check if current user has unseen notices
$hasUnseen = hasUnseenNotices($con, $student_id); // Replace $student_id with your actual student ID variable
// Function to check if fees are pending for current month
function hasPendingFees($con, $registration_code) {
    $current_month = date('F'); // Get current month name (e.g., "September")
    
    // Check if there's a successful payment for current month
    $query = "SELECT COUNT(*) as count FROM donations 
              WHERE student_registration_code = '$registration_code' 
              AND payment_reason = '$current_month' 
              AND status = 'success'";
    
    $result = $con->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        return $row['count'] == 0; // No payment found for current month
    }
    
    return true; // Assume pending if there's an error
}

// Check if current user has unseen notices
$hasUnseen = hasUnseenNotices($con, $student_id);

// Check if fees are pending for current month
$hasPendingFees = hasPendingFees($con, $registration_code);
?>


    <!-- info start -->
    <section class="info d-flex align-items-start justify-content-between pb-12">
      <div class="d-flex align-items-center justify-content-between gap-14">
        
        <div>
          <h3>Hi, <?= $name ?></h3>
          <p class="d-flex align-items-center gap-04">
            <img src="../assets/svg/map-marker.svg" alt="icon">
            <?= $address ?>
          </p>
        </div>
      </div>

      <ul class="d-flex align-items-center gap-16">
      <li>
  <a href="notification.php" class="d-flex align-items-center justify-content-center rounded-full position-relative">
    <img src="../assets/svg/bell-black.svg" alt="icon">
    <?php if ($hasUnseen): ?>
      <span class="dot"></span>
    <?php endif; ?>
  </a>
</li>
        <li>
          <a href="https://wa.me/<?= $rowm['ph1'] ?>" class="d-flex align-items-center justify-content-center rounded-full position-relative">
            <img src="../assets/svg/wp.png" alt="icon">
            <!--<span class="dot"></span>-->
          </a>
        </li>
      </ul>
    </section>
    <!-- info end -->

    <!-- search start -->
    <!--<section class="search py-12">-->
    <!--  <form action="#">-->
    <!--    <div class="form-inner w-100 d-flex align-items-center gap-8 radius-24">-->
    <!--      <img src="../assets/svg/search.svg" alt="search" class="shrink-0">-->
    <!--      <input type="search" class="input-search input-field" placeholder="Search...">-->
    <!--      <div class="filter shrink-0">-->
    <!--        <button type="button" class="d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#filterModal">-->
    <!--          <img src="../assets/svg/filter-black.svg" alt="filter">-->
    <!--        </button>-->
    <!--      </div>-->
    <!--    </div>-->
    <!--  </form>-->
    <!--</section>-->
    <!-- search end -->

    <!-- service start -->
    <section class="service py-12">
      <!-- item 1 -->
      <a href="tour-guide.php">
        <figure class="item text-center">
          <div class="image rounded-full d-flex align-items-center justify-content-center m-auto">
            <img src="../assets/images/home/academy.avif" alt="airport" class="img-fluid backface-hidden">
          </div>
          <figcaption>Academy Details</figcaption>
        </figure>
      </a>

      <!-- item 2 -->
      <a href="hotel-details.php">
        <figure class="item text-center">
          <div class="image rounded-full d-flex align-items-center justify-content-center m-auto">
            <img src="../assets/images/home/about.png" alt="car" class="img-fluid backface-hidden">
          </div>
          <figcaption>About Us</figcaption>
        </figure>
      </a>

      <!-- item 3 -->
      <a href="wishlist.php">
        <figure class="item text-center">
          <div class="image rounded-full d-flex align-items-center justify-content-center m-auto">
            <img src="../assets/images/home/exam.png" alt="hotel" class="img-fluid backface-hidden">
          </div>
          <figcaption>Gallery</figcaption>
        </figure>
      </a>

      <!-- item 4 -->
      <figure class="item text-center" data-bs-toggle="modal" data-bs-target="#serviceModal">
        <div class="image rounded-full d-flex align-items-center justify-content-center m-auto">
          <img src="../assets/images/home/more.png" alt="category" class="img-fluid backface-hidden">
        </div>
        <figcaption>More</figcaption>
      </figure>
    </section>
    <!-- service end -->
<?php if ($hasPendingFees): ?>
<div class="alert-banner" style="background-color: #ffebee; color: #c62828; padding: 10px; border: 1px solid #ef9a9a; margin-bottom: 15px; overflow: hidden; position: relative;">
    <div class="scrolling-text" style="white-space: nowrap; animation: scrollText 15s linear infinite; font-weight: bold;">
        ⚠️ Your fees for <?php echo date('F'); ?> are pending. Please submit them as soon as possible. Thank you!
    </div>
    <style>
    @keyframes scrollText {
        0% { transform: translateX(100%); }
        100% { transform: translateX(-100%); }
    }
    </style>
</div>
<?php endif; ?>
    <!-- visited start -->
    
    <!-- visited end -->

    <!-- guide start -->
  <section class="guide py-12">
  <!-- title -->
  <div class="title d-flex align-items-center justify-content-between">
    <h2 class="shrink-0">Our Courses</h2>
    <!-- <a href="tour-guide.php" class="shrink-0 d-inline-block">See All</a> -->
  </div>

  <!-- cards -->
  <div class="d-flex gap-24 all-cards scrollbar-hidden">
    <?php
      $sql = "SELECT * FROM event";
      $res = $con->query($sql);
      while ($row = $res->fetch_array()) {
    ?>

    <!-- course card -->
    <a href="#" class="d-flex gap-16 item w-fit shrink-0">
      <div class="image position-relative shrink-0">
        <img src="../admin/<?= htmlspecialchars($row['image']) ?>" alt="Course Image" class="guide-img object-fit-cover img-fluid radius-12">
        <!--<div class="rating d-flex align-items-center gap-04 w-fit">-->
        <!--  <img src="../assets/svg/star-yellow.svg" alt="Star">-->
        <!--  <span class="d-inline-block">4.0</span>-->
        <!--</div>-->
      </div>

      <div class="content">
        <h4><?= htmlspecialchars($row['name']) ?></h4>
        <h5>Popular</h5>
        
      </div>
    </a>

    <?php } ?>
  </div>
</section>

    <!-- guide end -->

  <!-- Promotional start -->
<section class="guide py-12">
  <!-- title -->
  <div class="title d-flex align-items-center justify-content-between">
    <h2 class="shrink-0">Promotional Images</h2>
    <!-- <a href="tour-guide.php" class="shrink-0 d-inline-block">See All</a> -->
  </div>

  <!-- cards -->
  <div class="d-flex gap-24 all-cards scrollbar-hidden">
    <?php
    include 'admin/config.php'; // ensure this is included if not already

    $sql = "SELECT * FROM gallery WHERE type = 'Promotional'";
    $res = $con->query($sql);
    while ($row = $res->fetch_array()) {
    ?>
      <a href="#" class="d-flex gap-16 item w-fit shrink-0" style="height: 263px; width: 346px;">
        <div class="image position-relative shrink-0" style="width: 100%; height: 240px; object-fit: cover;">
          <img src="../admin/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="guide-img object-fit-cover img-fluid radius-12" style="width: 100%; height: 100%; object-fit:cover;">
          <div class="rating d-flex align-items-center gap-04 w-fit">
            <span class="d-inline-block"><?= htmlspecialchars($row['name']) ?></span>
          </div>
        </div>
        <div class="content">
          <h4></h4>
          <p class="d-flex align-items-center gap-8 location"></p>
        </div>
      </a>
    <?php } ?>
  </div>
</section>

    <!-- Promotional end -->

    <!-- budget start -->

    <!-- budget end -->
  </main>


<?php include('footer.php') ?>