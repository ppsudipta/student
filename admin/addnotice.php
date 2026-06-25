<?php 
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin | Add Notice</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Core CSS -->
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .select2-container .select2-selection--single {
      height: 34px;
      padding: 5px 12px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php'; ?>
  <aside class="main-sidebar">
    <section class="sidebar">
      <?php include 'sidebar.php'; ?>
    </section>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Add Notice</h1>
      <ol class="breadcrumb">
        <li><a href="allsliderimg.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Add Notice</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-body">
          <?php if (!empty($message)) echo $message; ?>
          <?php if (!empty($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
          } ?>

          <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
              <label>Send Notice To *</label>
              <select name="recipient_type" id="recipient_type" class="form-control" onchange="updateRecipientOptions()" required>
                <option value="">-- Select Type --</option>
                <option value="single">Single Student</option>
                <option value="all">All Students</option>
                <option value="class">By Class</option>
              </select>
            </div>

            <div class="form-group" id="student_select" style="display: none;">
              <label>Select Student</label>
              <select name="student_id" id="student_id" class="form-control" style="width:100%">
                <option value="">-- Select Student --</option>
                <?php
                $res = $con->query("SELECT id, name, registration_code FROM students");
                while ($row = $res->fetch_assoc()) {
                  echo "<option value='{$row['id']}'>{$row['name']} (Reg: {$row['registration_code']})</option>";
                }
                ?>
              </select>
            </div>

            <div class="form-group" id="class_select" style="display: none;">
              <label>Select Class</label>
              <select name="class_name" class="form-control">
                <option value="">-- Select Class --</option>
                <?php
                // Get unique classes from students table
                $classList = [];
                $res2 = $con->query("SELECT class FROM students");
                while ($row = $res2->fetch_assoc()) {
                  $parts = array_map('trim', explode(',', $row['class']));
                  foreach ($parts as $c) {
                    if ($c != '') $classList[] = $c;
                  }
                }
                $classList = array_unique($classList);
                sort($classList);
                
                foreach($classList as $cl): ?>
                  <option value="<?php echo $cl; ?>"><?php echo $cl; ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Notice Type *</label>
              <select name="notice_type" id="notice_type" class="form-control" onchange="toggleInput()" required>
                <option value="text">Text</option>
                <option value="image">Image</option>
                <option value="video">Video</option>
              </select>
            </div>

            <div class="form-group" id="text_input">
              <label>Notice Text</label>
              <textarea name="notice_text" class="form-control" rows="4"></textarea>
            </div>

            <div class="form-group" id="file_input" style="display:none;">
              <label>Upload Image/Video</label>
              <input type="file" name="notice_file" class="form-control" accept="image/*,video/*">
            </div>

            <button type="submit" class="btn btn-primary">Add Notice</button>
          </form>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; <?= date('Y') ?> Sunrise Academy</strong> All rights reserved.
  </footer>

</div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
function toggleInput() {
  const type = document.getElementById('notice_type').value;
  document.getElementById('text_input').style.display = (type === 'text') ? 'block' : 'none';
  document.getElementById('file_input').style.display = (type === 'text') ? 'none' : 'block';
}

function updateRecipientOptions() {
  const type = document.getElementById('recipient_type').value;
  document.getElementById('student_select').style.display = (type === 'single') ? 'block' : 'none';
  document.getElementById('class_select').style.display = (type === 'class') ? 'block' : 'none';

  if (type === 'single') {
    setTimeout(() => {
      if ($.fn.select2 && !$('#student_id').hasClass("select2-hidden-accessible")) {
        $('#student_id').select2({
          width: '100%',
          placeholder: "-- Select Student --",
          allowClear: true,
          // Enable search by both name and registration code
          matcher: function(params, data) {
            // If there are no search terms, return all of the data
            if ($.trim(params.term) === '') {
              return data;
            }
            
            // Check if the option's text contains the term
            if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
              return data;
            }
            
            // Check if the option's value contains the term
            if (data.id.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
              return data;
            }
            
            // Return null if the term doesn't match
            return null;
          }
        });
      }
    }, 100);
  }
}

$(document).ready(function () {
  $('#student_id').select2({
    width: '100%',
    placeholder: "-- Select Student --",
    allowClear: true,
    // Enable search by both name and registration code
    matcher: function(params, data) {
      // If there are no search terms, return all of the data
      if ($.trim(params.term) === '') {
        return data;
      }
      
      // Check if the option's text contains the term
      if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
        return data;
      }
      
      // Check if the option's value contains the term
      if (data.id.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
        return data;
      }
      
      // Return null if the term doesn't match
      return null;
    }
  });
});
</script>
</body>
</html>

<?php
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $recipient_type = $_POST['recipient_type'];
  $notice_type = $_POST['notice_type'];
  $notice_content = '';
  $student_ids = [];
  $random_id = mt_rand(100000, 999999); // Generate random ID for this notice batch

  if ($recipient_type === 'single') {
    $student_ids[] = $_POST['student_id'];
  } elseif ($recipient_type === 'all') {
    $res = $con->query("SELECT id FROM students");
    while ($row = $res->fetch_assoc()) {
      $student_ids[] = $row['id'];
    }
  } elseif ($recipient_type === 'class') {
    $class = $_POST['class_name'];
    
    // Get students who have this class in their comma-separated class field
  $res = $con->query("SELECT id FROM students WHERE FIND_IN_SET('$class', class) > 0");

    while ($row = $res->fetch_assoc()) {
      $student_ids[] = $row['id'];
    }
  }

  if ($notice_type == 'text') {
    $notice_content = trim($_POST['notice_text']);
  } else {
    if (!empty($_FILES['notice_file']['name'])) {
      $upload_dir = 'uploads/';
      if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
      $ext = strtolower(pathinfo($_FILES['notice_file']['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm'];
      if (in_array($ext, $allowed)) {
        $new_filename = uniqid() . '.' . $ext;
        $target_file = $upload_dir . $new_filename;
        if (move_uploaded_file($_FILES['notice_file']['tmp_name'], $target_file)) {
          $notice_content = $target_file;
        } else {
          $message = '<div class="alert alert-danger">File upload failed.</div>';
        }
      } else {
        $message = '<div class="alert alert-danger">Invalid file type.</div>';
      }
    }
  }

  if (empty($message) && !empty($student_ids)) {
    $stmt = $con->prepare("INSERT INTO notices (random_id, student_id, notice_type, notice_content, created_at) VALUES (?, ?, ?, ?, NOW())");
    foreach ($student_ids as $sid) {
      $stmt->bind_param("iiss", $random_id, $sid, $notice_type, $notice_content);
      $stmt->execute();
    }
   

  
    header("Location: allnotice.php");
    exit();
  }
}
?>