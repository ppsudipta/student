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
$enquiries_stmt = $con->prepare("SELECT * FROM enquiries WHERE student_id = ? OR student_id = ? ORDER BY created_at DESC");
$enquiries_stmt->bind_param("ss", $sid, $id);
$enquiries_stmt->execute();
$enquiries_result = $enquiries_stmt->get_result();
$enquiries = [];
while($row = $enquiries_result->fetch_assoc()) {
    $enquiries[] = $row;
}

function load_enquiry_messages($con, $enquiry_id) {
    $messages = [];
    $tableCheck = $con->query("SHOW TABLES LIKE 'enquiry_messages'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $con->prepare("SELECT * FROM enquiry_messages WHERE enquiry_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $enquiry_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    }
    return $messages;
}

function save_enquiry_attachment_file($file, &$errorMessage) {
    $maxBytes = 5 * 1024 * 1024;
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

    if (!isset($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'File upload failed.';
        return false;
    }
    if ($file['size'] > $maxBytes) {
        $errorMessage = 'File is too large. Maximum size is 5 MB.';
        return false;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) {
        $errorMessage = 'File type not allowed. Use JPG, PNG, PDF, DOC, DOCX, XLS, or XLSX.';
        return false;
    }

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $storedName = uniqid('api_', true) . '.' . $extension;
    $targetPath = $uploadDir . '/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $errorMessage = 'Unable to save uploaded file.';
        return false;
    }

    return $storedName;
}

function enquiry_attachment_url($attachment) {
    if (empty($attachment)) {
        return '';
    }
    if (strpos($attachment, 'uploads/') === 0) {
        return htmlspecialchars($attachment);
    }
    if (strpos($attachment, '/') === false) {
        return 'uploads/' . htmlspecialchars($attachment);
    }
    return htmlspecialchars($attachment);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['followup_enquiry_id'])) {
        $enquiry_id = intval($_POST['followup_enquiry_id']);
        $followup_message = trim($_POST['followup_message'] ?? '');
        if ($enquiry_id > 0 && $followup_message !== '') {
            $check = $con->prepare("SELECT id FROM enquiries WHERE id = ? AND (student_id = ? OR student_id = ?)");
            $check->bind_param("iss", $enquiry_id, $sid, $id);
            $check->execute();
            $owned = $check->get_result()->fetch_assoc();
            $check->close();
            if ($owned) {
                $uploadError = '';
                $followupAttachment = null;
                if (!empty($_FILES['followup_attachment']['name'])) {
                    $saved = save_enquiry_attachment_file($_FILES['followup_attachment'], $uploadError);
                    if ($saved === false) {
                        echo "<script>alert('" . addslashes($uploadError) . "'); window.history.back();</script>";
                        exit();
                    }
                    $followupAttachment = $saved;
                }
                $tableCheck = $con->query("SHOW TABLES LIKE 'enquiry_messages'");
                if ($tableCheck && $tableCheck->num_rows > 0) {
                    $msgStmt = $con->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_type, sender_name, message, attachment, created_at) VALUES (?, 'student', ?, ?, ?, NOW())");
                    $msgStmt->bind_param("isss", $enquiry_id, $name, $followup_message, $followupAttachment);
                    $msgStmt->execute();
                    $msgStmt->close();
                }
                echo "<script>alert('Message sent.'); window.location.href='review.php';</script>";
                exit();
            }
        }
        echo "<script>alert('Unable to send message.'); window.history.back();</script>";
        exit();
    }

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
    $uploadError = '';
    if (!empty($_FILES['attachment']['name'])) {
        $saved = save_enquiry_attachment_file($_FILES['attachment'], $uploadError);
        if ($saved === false) {
            echo "<script>alert('" . addslashes($uploadError) . "'); window.history.back();</script>";
            exit();
        }
        $attachment = $saved;
    }

    // Insert into database
    $stmt = $con->prepare("INSERT INTO enquiries (student_id, name, email, phone, enquiry_type, subject, message, attachment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $student_id, $name, $email, $phone, $enquiry_type, $subject, $message, $attachment, $created_at);

    if ($stmt->execute()) {
        $newId = $con->insert_id;
        $tableCheck = $con->query("SHOW TABLES LIKE 'enquiry_messages'");
        if ($tableCheck && $tableCheck->num_rows > 0 && $newId) {
            $msgStmt = $con->prepare("INSERT INTO enquiry_messages (enquiry_id, sender_type, sender_name, message, attachment, created_at) VALUES (?, 'student', ?, ?, ?, ?)");
            $msgStmt->bind_param("issss", $newId, $name, $message, $attachment, $created_at);
            $msgStmt->execute();
            $msgStmt->close();
        }
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
            <label for="attachment" class="form-label">Supporting Documents (optional)</label>
            <input type="file" class="form-control" id="attachment" name="attachment"
                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,image/*,application/pdf">
            <small class="text-muted">Max 5 MB. JPG, PNG, PDF, DOC, DOCX, XLS, XLSX.</small>
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
        
        <?php foreach ($enquiries as $enquiry):
          $thread = load_enquiry_messages($con, (int)$enquiry['id']);
          if (empty($thread)) {
              $thread = [['sender_type' => 'student', 'sender_name' => $enquiry['name'], 'message' => $enquiry['message'], 'created_at' => $enquiry['created_at']]];
              if (!empty($enquiry['reply_message'])) {
                  $thread[] = ['sender_type' => 'admin', 'sender_name' => $enquiry['replied_by'] ?? 'Admin', 'message' => $enquiry['reply_message'], 'created_at' => $enquiry['replied_at']];
              }
          }
        ?>
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

          <?php foreach ($thread as $msg): ?>
          <div class="reply-item <?= ($msg['sender_type'] ?? '') === 'admin' ? '' : 'student-msg' ?>">
            <div class="reply-header">
              <span><?= ($msg['sender_type'] ?? '') === 'admin' ? 'Admin' : 'You' ?><?= !empty($msg['sender_name']) ? ' · ' . htmlspecialchars($msg['sender_name']) : '' ?></span>
              <span class="enquiry-date"><?= date('M j, Y g:i A', strtotime($msg['created_at'])) ?></span>
            </div>
            <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
            <?php if (!empty($msg['attachment'])): ?>
              <p><a href="<?= enquiry_attachment_url($msg['attachment']) ?>" target="_blank">View attachment</a></p>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>

          <form method="POST" class="mt-3" enctype="multipart/form-data">
            <input type="hidden" name="followup_enquiry_id" value="<?= (int)$enquiry['id'] ?>">
            <div class="mb-2">
              <label class="form-label">Reply to this thread</label>
              <textarea class="form-control" name="followup_message" rows="3" required placeholder="Type your follow-up message..."></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">Attach file (optional)</label>
              <input type="file" class="form-control" name="followup_attachment"
                     accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,image/*,application/pdf">
              <small class="text-muted">Max 5 MB. JPG, PNG, PDF, DOC, DOCX, XLS, XLSX.</small>
            </div>
            <button type="submit" class="btn btn-outline-primary btn-sm">Send Reply</button>
          </form>
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