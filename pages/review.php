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
$email = $row2['email'];
$phone = $row2['mobile_number'];

// Get company info
$sql = "SELECT * FROM company";
$res = $con->query($sql);
$rowm = $res->fetch_assoc();

// Get previous enquiries for this student
$enquiries_stmt = $con->prepare("SELECT * FROM enquiries WHERE student_id = ? ORDER BY created_at DESC");
$enquiries_stmt->bind_param("s", $sid);
$enquiries_stmt->execute();
$enquiries_result = $enquiries_stmt->get_result();
$enquiries = [];
while($row = $enquiries_result->fetch_assoc()) {
    $enquiries[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $enquiry_type = $_POST['enquiry_type'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $created_at = date("Y-m-d H:i:s");

    // Handle file upload if any
    $attachment = '';
    if (!empty($_FILES['attachment']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = basename($_FILES["attachment"]["name"]);
        $targetFilePath = $targetDir . time() . '_' . $fileName;
        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $targetFilePath)) {
            $attachment = $targetFilePath;
        }
    }

    // Insert into database
    $stmt = $con->prepare("INSERT INTO enquiries (student_id, name, email, phone, enquiry_type, subject, message, attachment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $student_id, $name, $email, $phone, $enquiry_type, $subject, $message, $attachment, $created_at);

    if ($stmt->execute()) {
        echo "<script>alert('Enquiry submitted successfully.'); window.location.href='review.php';</script>";
    } else {
        echo "<script>alert('Failed to submit enquiry.'); window.history.back();</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Enquiry Form - <?= htmlspecialchars($rowm['name']) ?></title>
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
  <link rel="stylesheet" href="../assets/css/details.css">

  <style>
    .enquiry-form {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .form-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .form-header img {
      max-width: 150px;
      margin-bottom: 15px;
    }
    .form-section {
      margin-bottom: 25px;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 8px;
    }
    .form-section h4 {
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }
    .media-container {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 20px;
    }
    .media-item {
      flex: 1 1 200px;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 10px;
      text-align: center;
    }
    .media-item img {
      max-width: 100%;
      height: auto;
      border-radius: 5px;
    }
    .video-container {
      position: relative;
      padding-bottom: 56.25%;
      height: 0;
      overflow: hidden;
    }
    .video-container iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }
    .reply-section {
      margin-top: 40px;
    }
    .enquiry-item {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      background: #fff;
    }
    .enquiry-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .enquiry-type {
      background: #f0f0f0;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
    }
    .enquiry-date {
      color: #666;
      font-size: 0.9rem;
    }
    .reply-item {
      background: #f9f9f9;
      border-left: 4px solid #007bff;
      padding: 15px;
      margin-top: 15px;
      border-radius: 4px;
    }
    .reply-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      font-weight: bold;
    }
    .no-replies {
      color: #666;
      font-style: italic;
    }
  </style>
</head>
<body class="scrollbar-hidden">
  <!-- splash-screen start -->
  <section id="preloader" class="spalsh-screen">
    <div class="circle text-center">
      <div>
        <h1><?= htmlspecialchars($rowm['name']) ?></h1>
        <p>Student Enquiry System</p>
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

  <main>
    <!-- page-title -->
    <div class="page-title">
      <button type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
        <img src="../assets/svg/arrow-left-black.svg" alt="arrow">
      </button>
      <h3 class="main-title">Student Enquiry Form</h3>
    </div>

    <section class="enquiry-form px-24 pb-24">
      <div class="form-header">
        <img src="../admin/<?= htmlspecialchars($rowm['logo'] ?? '') ?>" alt="School Logo">
        <h2>Student Enquiry Form</h2>
        <p>Please fill out this form to submit your enquiry</p>
      </div>

      <form action="" method="post" enctype="multipart/form-data">
        <!-- Student Information Section -->
        <div class="form-section">
          <h4>Student Information</h4>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($name) ?>" readonly>
            </div>
            <div class="col-md-6 mb-3">
              <label for="student_id" class="form-label">Roll Number</label>
              <input type="text" class="form-control" id="student_id" name="student_id" value="<?= htmlspecialchars($sid) ?>" readonly>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
            </div>
          </div>
        </div>

        <!-- Enquiry Details Section -->
        <div class="form-section">
          <h4>Enquiry Details</h4>
          <div class="mb-3">
            <label for="enquiry_type" class="form-label">Enquiry Type</label>
            <select class="form-select" id="enquiry_type" name="enquiry_type" required>
              <option value="" selected disabled>Select enquiry type</option>
              <option value="academic">Academic</option>
              <option value="financial">Financial</option>
              <option value="technical">Technical Support</option>
              <option value="facilities">Facilities</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="subject" class="form-label">Subject</label>
            <input type="text" class="form-control" id="subject" name="subject" required>
          </div>
          <div class="mb-3">
            <label for="message" class="form-label">Detailed Message</label>
            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
          </div>
          <div class="mb-3">
            <label for="attachment" class="form-label">Supporting Documents (if any)</label>
            <input type="file" class="form-control" id="attachment" name="attachment">
          </div>
        </div>

        <!-- Submission Section -->
        <div class="form-section">
          <h4>Submit Your Enquiry</h4>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="consent" required>
            <label class="form-check-label" for="consent">
              I confirm that the information provided is accurate and I consent to the processing of my personal data for the purpose of handling this enquiry.
            </label>
          </div>
          <button type="submit" class="btn btn-primary w-100">Submit Enquiry</button>
        </div>
      </form>

      <!-- Previous Enquiries and Replies Section -->
      <?php if (!empty($enquiries)): ?>
      <div class="reply-section">
        <h3>Your Previous Enquiries and Replies</h3>
        
        <?php foreach ($enquiries as $enquiry): ?>
        <div class="enquiry-item">
          <div class="enquiry-header">
            <div>
              <strong><?= htmlspecialchars($enquiry['subject']) ?></strong>
              <span class="enquiry-type"><?= htmlspecialchars(ucfirst($enquiry['enquiry_type'])) ?></span>
            </div>
            <div class="enquiry-date">
              <?= date('M j, Y g:i A', strtotime($enquiry['created_at'])) ?>
            </div>
          </div>
          
          <p><?= nl2br(htmlspecialchars($enquiry['message'])) ?></p>
          
          <?php if ($enquiry['attachment']): ?>
          <div class="mt-2">
            <strong>Attachment:</strong> 
            <a href="../admin/<?= htmlspecialchars($enquiry['attachment']) ?>" target="_blank">View File</a>
          </div>
          <?php endif; ?>
          
          <?php if (!empty($enquiry['reply_message'])): ?>
          <div class="reply-item">
            <div class="reply-header">
              <span>Admin Reply</span>
              <span class="enquiry-date">
                <?= date('M j, Y g:i A', strtotime($enquiry['replied_at'])) ?>
              </span>
            </div>
            <p><?= nl2br(htmlspecialchars($enquiry['reply_message'])) ?></p>
          </div>
          <?php else: ?>
          <div class="reply-item no-replies">
            <p>No reply yet. We'll get back to you soon.</p>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
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
  
  <script>
    // Additional form validation if needed
    $(document).ready(function() {
      $('form').submit(function(e) {
        // Add any additional validation here
        if (!$('#consent').is(':checked')) {
          alert('Please confirm your consent before submitting the form');
          e.preventDefault();
        }
      });
    });
  </script>
</body>
</html>