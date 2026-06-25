<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Course Details</title>

  <!-- favicon -->
  <link rel="shortcut icon" href="../assets/images/favicon.png" type="image/x-icon">

  <!-- bootstrap -->
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">

  <!-- common -->
  <link rel="stylesheet" href="../assets/css/common.css">

  <!-- animations -->
  <link rel="stylesheet" href="../assets/css/animations.css">

  <!-- details -->
  <link rel="stylesheet" href="../assets/css/details.css">

  <style>
    .course-price {
      font-size: 24px;
      font-weight: bold;
      color: #2a52be;
    }
    .course-price span {
      font-size: 16px;
      font-weight: normal;
      color: #666;
    }
    .course-description {
      white-space: pre-line;
    }
    .course-meta {
      display: flex;
      gap: 20px;
      margin: 15px 0;
    }
    .course-meta-item {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .course-meta-item img {
      width: 18px;
      height: 18px;
    }
  </style>
</head>
<body class="scrollbar-hidden">
  <!-- splash-screen start -->
  <section id="preloader" class="spalsh-screen">
    <div class="circle text-center">
      <div>
        <h1>Course Details</h1>
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

  <main class="details course-details">
    <?php
    // Database connection
    require_once './config.php';
    
    // Get course ID from URL and sanitize it
    $course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Fetch course details
    $sql = "SELECT * FROM event WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Course not found
        echo '<section class="banner position-relative">
                <div class="page-title">
                  <button type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
                    <img src="../assets/svg/arrow-left-black.svg" alt="arrow">
                  </button>
                  <h3 class="main-title">Course Not Found</h3>
                </div>
              </section>
              <section class="details-body text-center py-5">
                <p>The requested course could not be found.</p>
                <a href="javascript:history.back()" class="btn btn-primary mt-3">Go Back</a>
              </section>';
        exit();
    }
    
    $course = $result->fetch_assoc();
    ?>

    <!-- banner start -->
    <section class="banner position-relative">
      <img src="../admin/<?= htmlspecialchars($course['image']) ?>" alt="Course Banner" class="w-100 img-fluid" style="max-height: 300px; object-fit: cover;">
      
      <!-- title -->
      <div class="page-title">
        <button type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
          <img src="../assets/svg/arrow-left-black.svg" alt="arrow">
        </button>
        <h3 class="main-title"><?= htmlspecialchars($course['name']) ?></h3>
      </div>
    </section>
    <!-- banner end -->

    <!-- details-body start -->
    <section class="details-body">
      <!-- details-title -->
      <section class="d-flex align-items-center gap-8 details-title">
        <div class="flex-grow">
          <h3><?= htmlspecialchars($course['name']) ?></h3>
          <div class="course-meta">
            <div class="course-meta-item">
              <img src="../assets/svg/calendar.svg" alt="Date">
              <span><?= htmlspecialchars($course['date']) ?></span>
            </div>
            <div class="course-meta-item">
              <img src="../assets/svg/star-yellow.svg" alt="Rating">
              <span>4.0 (25 Reviews)</span>
            </div>
          </div>
        </div>
      </section>

      <!-- details-info -->
      <section class="details-info pt-32 pb-16">
        <div class="title">
          <h4>Course Details</h4>
        </div>
        <p class="course-description"><?= htmlspecialchars($course['description']) ?></p>
      </section>

      <!-- pricing section -->
      <section class="details-info pt-16 pb-16">
        <div class="title">
          <h4>Pricing</h4>
        </div>
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h5>Course Fee</h5>
            <p>Includes all study materials and resources</p>
          </div>
          <div class="course-price">
            ৳<?= number_format($course['price'], 2) ?> <span>per month</span>
          </div>
        </div>
      </section>

      <!-- enrollment section -->
      <section class="details-info pt-16 pb-16">
        <div class="title">
          <h4>Enrollment</h4>
        </div>
        <p>To enroll in this course or for more information, please contact our admissions office or visit our campus.</p>
        <div class="mt-3">
          <!--<a href="contact.php" class="btn btn-outline-primary mr-2">Contact Us</a>-->
          <a href="payment.php" class="btn btn-primary">Enroll Now</a>
        </div>
      </section>
    </section>
    <!-- details-body end -->

    <!-- details-footer start -->
    <section class="details-footer d-flex align-items-center justify-content-between gap-8 w-100">
      <p>৳<?= number_format($course['price'], 2) ?> <span>/month</span></p>
      <a href="payment.php">Enroll Now</a>
    </section>
    <!-- details-footer end -->
  </main>

  <!-- jquery -->
  <script src="../assets/js/jquery-3.6.1.min.js"></script>

  <!-- bootstrap -->
  <script src="../assets/js/bootstrap.bundle.min.js"></script>

  <!-- script -->
  <script src="../assets/js/script.js"></script>

  <script>
    // Back button functionality
    $(document).on('click', '.back-page-btn', function() {
      window.history.back();
    });
    
    // Preloader
    $(window).on('load', function() {
      $('#preloader').fadeOut('slow');
    });
  </script>
</body>
</html>