<?php
session_start();
include('config.php');

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: ./auth/signin.php');
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$username = $_SESSION['username'];

// Fetch student data
$stmt = $con->prepare("SELECT * FROM students WHERE name = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Student record not found");
}

$name = $user['name'] ?? 'Guest';
$student_id = $user['id'] ?? 0;
$student_img = $user['image'] ?? 'user.png';
$reg_code = $user['registration_code'] ?? '';
$class = (string)$user['class']; // Ensure class is string type
$session = trim($user['session']); // Trim whitespace from session

// Fetch company data
$company = $con->query("SELECT * FROM company")->fetch_assoc();

// Initialize message variables
$error = $success = '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission";
    } else {
        $homework_id = filter_input(INPUT_POST, 'homework_id', FILTER_VALIDATE_INT);
        $comments = trim(filter_input(INPUT_POST, 'comments', FILTER_SANITIZE_STRING));

        if (isset($_FILES['homework_file']) && $_FILES['homework_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['homework_file'];
            
            // Validate file
            $allowed = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'ppt', 'pptx'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime_types = [
                'pdf'  => 'application/pdf',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt'  => 'text/plain',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'ppt'  => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ];

            if (!in_array($ext, $allowed)) {
                $error = "Invalid file type. Allowed types: " . implode(', ', $allowed);
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = "File exceeds 5MB size limit.";
            } elseif (!in_array(mime_content_type($file['tmp_name']), $mime_types)) {
                $error = "File type doesn't match extension.";
            } else {
                // Create uploads directory if it doesn't exist
                $uploadDir = "uploads/";
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Sanitize filename
                $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
                $uniqueName = time() . '_' . $filename;
                $targetPath = $uploadDir . $uniqueName;

                if (move_uploaded_file($file["tmp_name"], $targetPath)) {
                    $stmt = $con->prepare("INSERT INTO homework_submissions (student_id, homework_id, file_path, comments, submission_date) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("iiss", $student_id, $homework_id, $uniqueName, $comments);
                    if ($stmt->execute()) {
                        $success = "Homework submitted successfully!";
                        // Regenerate CSRF token
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $error = "Failed to save submission: " . $stmt->error;
                    }
                } else {
                    $error = "File upload failed. Please try again.";
                }
            }
        } else {
            $upload_error = $_FILES['homework_file']['error'] ?? 0;
            $error = "File upload error: " . match($upload_error) {
                UPLOAD_ERR_INI_SIZE => "File exceeds server size limit",
                UPLOAD_ERR_FORM_SIZE => "File exceeds form size limit",
                UPLOAD_ERR_PARTIAL => "File only partially uploaded",
                UPLOAD_ERR_NO_FILE => "No file selected",
                default => "Unknown error"
            };
        }
    }
}

// Debug output - show student's class and session
// echo "<script>alert('$class' + '$session');</script>" ;

// Fetch active assignments for student's class and session
// Fetch active assignments without prepared statements
$query = "SELECT * FROM homework_assignments where class = '$class' AND session = '$session'";

$homework_list = $con->query($query);

// Debug output
error_log("Homework Query: " . $query);
error_log("Found " . $homework_list->num_rows . " assignments");

if ($homework_list === false) {
    error_log("Homework query failed: " . $con->error);
    die("Query error: " . $con->error);
}

// Debug query and results
if ($homework_list === false) {
    error_log("Homework query failed: " . $stmt_hw->error);
    die("Query error: " . $stmt_hw->error);
}

error_log("Found " . $homework_list->num_rows . " assignments matching class '$class' and session like '$session'");

// Prepare to fetch submissions for each homework
$submissions = [];
if ($homework_list->num_rows > 0) {
    $homework_ids = [];
    while ($hw = $homework_list->fetch_assoc()) {
        $homework_ids[] = $hw['id'];
        error_log("Assignment Found - ID: {$hw['id']}, Title: {$hw['title']}, Class: {$hw['class']}, Session: {$hw['session']}");
    }
    
    if (!empty($homework_ids)) {
        $id_list = implode(",", array_map('intval', $homework_ids));
        $submission_query = $con->query("SELECT hs.* FROM homework_submissions hs 
                                       INNER JOIN (
                                           SELECT homework_id, MAX(submission_date) as latest_date 
                                           FROM homework_submissions 
                                           WHERE student_id = $student_id 
                                           AND homework_id IN ($id_list)
                                           GROUP BY homework_id
                                       ) latest ON hs.homework_id = latest.homework_id AND hs.submission_date = latest.latest_date
                                       WHERE hs.student_id = $student_id");
        
        while ($sub = $submission_query->fetch_assoc()) {
            $submissions[$sub['homework_id']] = $sub;
            error_log("Submission Found - HW ID: {$sub['homework_id']}, File: {$sub['file_path']}");
        }
    }
    
    // Reset pointer for homework_list
    $homework_list->data_seek(0);
}

// Temporary debug query to show all assignments in system
$debug_query = $con->query("SELECT id, title, class, session, deadline FROM homework_assignments ORDER BY deadline");
$all_assignments = [];
while ($row = $debug_query->fetch_assoc()) {
    $all_assignments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Submit Homework - <?= htmlspecialchars($company['name'] ?? 'Homework System') ?></title>
  <link rel="icon" href="../admin/<?= htmlspecialchars($company['logo'] ?? '') ?>" type="image/x-icon"/>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/common.css" />
  <link rel="stylesheet" href="../assets/css/animations.css" />
  <style>
    .homework-card { 
      background: #fff; 
      border-radius: 12px; 
      padding: 20px; 
      margin-bottom: 20px; 
      box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
      border-left: 4px solid #3498db;
    }
    .deadline { 
      color: #e74c3c; 
      font-weight: bold; 
    }
    .deadline.near {
      color: #f39c12;
    }
    .deadline.urgent {
      color: #e74c3c;
      animation: pulse 1.5s infinite;
    }
    .file-upload { 
      border: 2px dashed #ccc; 
      padding: 20px; 
      border-radius: 8px; 
      cursor: pointer; 
      text-align: center; 
      transition: all 0.3s ease;
    }
    .file-upload:hover { 
      border-color: #3498db;
      background: #f8f9fa; 
    }
    .file-upload.active {
      border-color: #2ecc71;
      background: #f0fff4;
    }
    .alert { 
      margin: 15px 0; 
    }
    .submission-info { 
      background: #f8f9fa; 
      border-radius: 8px; 
      padding: 15px; 
      margin-top: 15px; 
      border-left: 4px solid #2ecc71;
    }
    .file-preview {
      max-width: 100%;
      max-height: 200px;
      margin-top: 10px;
      display: none;
    }
    .debug-panel {
      background: #f8f9fa;
      border: 1px solid #ddd;
      border-radius: 5px;
      padding: 15px;
      margin-top: 20px;
    }
    .debug-table {
      font-size: 0.9rem;
    }
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.6; }
      100% { opacity: 1; }
    }
  </style>
</head>
<body class="scrollbar-hidden">

<!-- Preloader -->
<section id="preloader" class="spalsh-screen">
  <div class="circle text-center"><div><h1>Homework Submission</h1><p>Submit your assignments</p></div></div>
  <div class="loader-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
</section>

<main class="search-result">
  <div class="page-title">
    <button class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
      <img src="../assets/svg/arrow-left-black.svg" alt="back">
    </button>
    <h3 class="main-title">Submit Homework</h3>
    
  </div>

  <div class="container py-4">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <h4 class="mb-4">Available Assignments</h4>
    
    <?php if ($homework_list->num_rows > 0): ?>
      <?php while ($hw = $homework_list->fetch_assoc()): 
        $deadline_class = '';
        $deadline = strtotime($hw['deadline']);
        $now = time();
        $diff = $deadline - $now;
        
        if ($diff < 86400) { // Less than 24 hours
          $deadline_class = 'urgent';
        } elseif ($diff < 3 * 86400) { // Less than 3 days
          $deadline_class = 'near';
        }
      ?>
        <div class="homework-card">
          <h4><?= htmlspecialchars($hw['title']) ?></h4>
          <p><strong>Subject:</strong> <?= htmlspecialchars($hw['subject']) ?></p>
          <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($hw['description'])) ?></p>
          <p class="deadline <?= $deadline_class ?>">
            <strong>Deadline:</strong> <?= date('M d, Y H:i', $deadline) ?>
            <?php if ($diff < 0): ?>
              <span class="badge bg-danger ms-2">Expired</span>
            <?php endif; ?>
          </p>
          
          <?php if (isset($submissions[$hw['id']])): ?>
          <div class="submission-info">
            <h5>Your Submission</h5>
            <p><strong>Submitted on:</strong> <?= date('M d, Y H:i', strtotime($submissions[$hw['id']]['submission_date'])) ?></p>
            <?php if ($submissions[$hw['id']]['comments']): ?>
            <p><strong>Your comments:</strong> <?= nl2br(htmlspecialchars($submissions[$hw['id']]['comments'])) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($submissions[$hw['id']]['file_path'])): ?>
              <p>
                <strong>File:</strong>
                <a href="uploads/<?= htmlspecialchars($submissions[$hw['id']]['file_path']) ?>" 
                   target="_blank" 
                   class="btn btn-sm btn-outline-primary ms-2">
                    View Submitted File
                </a>
              </p>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          
          <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#submitModal<?= $hw['id'] ?>">
            <?= isset($submissions[$hw['id']]) ? 'Resubmit Assignment' : 'Submit Assignment' ?>
          </button>
        </div>

        <!-- Submission Modal -->
        <div class="modal fade" id="submitModal<?= $hw['id'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <form class="modal-content" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <input type="hidden" name="homework_id" value="<?= $hw['id'] ?>">
              
              <div class="modal-header">
                <h5 class="modal-title">Submit: <?= htmlspecialchars($hw['title']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label">Comments (Optional)</label>
                  <textarea class="form-control" name="comments" rows="3"><?= isset($submissions[$hw['id']]) ? htmlspecialchars($submissions[$hw['id']]['comments']) : '' ?></textarea>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Upload File <span class="text-danger">*</span></label>
                  <div class="file-upload" id="uploadArea<?= $hw['id'] ?>">
                    <p>Click or drag to upload</p>
                    <p class="small text-muted">PDF, DOC, DOCX, TXT, JPG, PNG, PPT, PPTX (Max 5MB)</p>
                    <input type="file" name="homework_file" class="d-none" required 
                           accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.ppt,.pptx">
                    <div class="file-name mt-2 small text-success"></div>
                    <img src="" class="file-preview img-thumbnail" id="preview<?= $hw['id'] ?>">
                  </div>
                </div>
              </div>
              
              <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                <button class="btn btn-primary" type="submit">Submit Homework</button>
              </div>
            </form>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="alert alert-info">No current homework assignments for your class.</div>
      
      <!-- Debug Panel -->
      <!--<div class="debug-panel mt-4">-->
      <!--  <h5>Debug Information</h5>-->
      <!--  <p><strong>Your Class:</strong> <?= htmlspecialchars($class) ?> (Type: <?= gettype($class) ?>)</p>-->
      <!--  <p><strong>Your Session:</strong> <?= htmlspecialchars($session) ?> (Type: <?= gettype($session) ?>)</p>-->
        
      <!--  <h6 class="mt-3">All Assignments in System:</h6>-->
      <!--  <div class="table-responsive">-->
      <!--    <table class="table table-sm debug-table">-->
      <!--      <thead>-->
      <!--        <tr>-->
      <!--          <th>ID</th>-->
      <!--          <th>Title</th>-->
      <!--          <th>Class</th>-->
      <!--          <th>Session</th>-->
      <!--          <th>Deadline</th>-->
      <!--        </tr>-->
      <!--      </thead>-->
      <!--      <tbody>-->
      <!--        <?php foreach ($all_assignments as $row): ?>-->
      <!--        <tr>-->
      <!--          <td><?= htmlspecialchars($row['id']) ?></td>-->
      <!--          <td><?= htmlspecialchars($row['title']) ?></td>-->
      <!--          <td><?= htmlspecialchars($row['class']) ?></td>-->
      <!--          <td><?= htmlspecialchars($row['session']) ?></td>-->
      <!--          <td><?= htmlspecialchars($row['deadline']) ?></td>-->
      <!--        </tr>-->
      <!--        <?php endforeach; ?>-->
      <!--      </tbody>-->
      <!--    </table>-->
      <!--  </div>-->
      <!--</div>-->
    <?php endif; ?>
  </div>
</main>

<!-- Scripts -->
<script src="../assets/js/jquery-3.6.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
  // Hide preloader when page loads
  window.addEventListener('load', () => document.getElementById('preloader').style.display = 'none');

  // Back button functionality
  document.querySelector('.back-page-btn').addEventListener('click', () => history.back());

  // File upload handling for each modal
  document.querySelectorAll('.file-upload input[type="file"]').forEach(input => {
    const uploadArea = input.closest('.file-upload');
    const fileNameDisplay = uploadArea.querySelector('.file-name');
    const preview = uploadArea.querySelector('.file-preview');
    
    // Handle file selection
    input.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        fileNameDisplay.textContent = 'Selected: ' + file.name;
        uploadArea.classList.add('active');
        
        // Show preview for images
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
          };
          reader.readAsDataURL(file);
        } else {
          preview.style.display = 'none';
        }
      }
    });
    
    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
      e.preventDefault();
      e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
      uploadArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
      uploadArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
      uploadArea.classList.add('active');
    }
    
    function unhighlight() {
      uploadArea.classList.remove('active');
    }
    
    // Handle dropped files
    uploadArea.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      const files = dt.files;
      input.files = files;
      input.dispatchEvent(new Event('change'));
    });
  });
</script>
</body>
</html>