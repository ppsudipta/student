<?php
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');

// Handle form submission
if (isset($_POST['submit'])) {
    // Validate and sanitize inputs
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $material_type = mysqli_real_escape_string($con, $_POST['material_type']);
    $subject = "no subject";
    $permission = mysqli_real_escape_string($con, $_POST['permission']);
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
    $access_level = 'class'; // Default access level
    
    // Handle recipient selection
    $recipient_type = $_POST['recipient_type'];
    $student_id = 0;
    $class = '';
    $session = '';
    
    if ($recipient_type === 'single' && !empty($_POST['student_id'])) {
        $student_id = (int)$_POST['student_id'];
        // Get student details
        $student_query = $con->query("SELECT name, email, class, session FROM students WHERE id = $student_id");
        if ($student_query->num_rows > 0) {
            $student = $student_query->fetch_assoc();
            $student_name = $student['name'];
            $student_email = $student['email'];
            $class = $student['class'];
            $session = $student['session'];
        }
    } elseif ($recipient_type === 'class') {
        $class = isset($_POST['class']) ? mysqli_real_escape_string($con, $_POST['class']) : '';
        $session = isset($_POST['session']) ? mysqli_real_escape_string($con, $_POST['session']) : '';
        $student_name = 'Multiple Students';
        $student_email = 'class@example.com';
        
        // For class-wise distribution, we need to send to all students in the selected class
        // We'll handle this after the main insert by creating individual records
        $class_wise = true;
    } else { // All students
        $student_name = 'All Students';
        $student_email = 'all@example.com';
        $access_level = 'public';
    }

    // Handle file upload or hosted video link
    $file_path = '';
    $material_url = trim($_POST['material_url'] ?? '');
    if ($material_type === 'video' && $material_url !== '') {
        if (!filter_var($material_url, FILTER_VALIDATE_URL) || !preg_match('/(vimeo\.com|player\.vimeo\.com|youtube\.com|youtu\.be)/i', $material_url)) {
            $_SESSION['error'] = "Please enter a valid Vimeo or YouTube video link.";
            header("Location: addevent.php");
            exit();
        }

        $file_path = $material_url;
    } elseif (isset($_FILES['material_file']) && $_FILES['material_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file_name = $_FILES['material_file']['name'];
        $file_tmp = $_FILES['material_file']['tmp_name'];
        $file_size = $_FILES['material_file']['size'];
        $file_error = $_FILES['material_file']['error'];
        
        // Validate file
        $allowed_types = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'mp4' => 'video/mp4'
        ];
        
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_mime = mime_content_type($file_tmp);
        
        // Double validation - extension and MIME type
        if (!array_key_exists($file_ext, $allowed_types) || 
            $allowed_types[$file_ext] !== $file_mime ||
            $file_error !== 0 || 
            $file_size > 5000000) {
            
            $_SESSION['error'] = "Invalid file type or size (max 5MB allowed). Detected: $file_ext ($file_mime)";
            header("Location: addevent.php");
            exit();
        }
        
        $upload_dir = 'uploads/materials/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $new_file_name = uniqid() . '_' . preg_replace('/[^a-z0-9\.]/i', '_', $file_name);
        $file_path = $upload_dir . $new_file_name;
        
        if (!move_uploaded_file($file_tmp, $file_path)) {
            $_SESSION['error'] = "File upload failed - check folder permissions";
            header("Location: addevent.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Please upload a file or enter a hosted video link.";
        header("Location: addevent.php");
        exit();
    }

    // Insert into database
    $query = "INSERT INTO student_materials (
        student_id, student_name, student_email, material_title, 
        material_description, material_type, file_path, subject, 
        class, session, is_favorite, access_level, permission
    ) VALUES (
        '$student_id', '$student_name', '$student_email', '$title', 
        '$description', '$material_type', '$file_path', '$subject', 
        '$class', '$session', '$is_favorite', '$access_level', '$permission'
    )";
    
    if ($con->query($query)) {
        $material_id = $con->insert_id;
        
        // If sending to a class, create individual records for each student in that class
        if ($recipient_type === 'class' && !empty($class)) {
            // Find all students in the selected class (handling comma-separated classes)
            $class_condition = "FIND_IN_SET('$class', class) > 0";
            if (!empty($session)) {
                $class_condition .= " AND session = '$session'";
            }
            
            $students_query = $con->query("SELECT id, name, email, class, session FROM students WHERE $class_condition");
            
            if ($students_query->num_rows > 0) {
                while ($student = $students_query->fetch_assoc()) {
                    $student_id = $student['id'];
                    $student_name = $student['name'];
                    $student_email = $student['email'];
                    $student_class = $student['class'];
                    $student_session = $student['session'];
                    
                    // Insert individual record for each student
                    $individual_query = "INSERT INTO student_materials (
                        student_id, student_name, student_email, material_title, 
                        material_description, material_type, file_path, subject, 
                        class, session, is_favorite, access_level, permission, parent_material_id
                    ) VALUES (
                        '$student_id', '$student_name', '$student_email', '$title', 
                        '$description', '$material_type', '$file_path', '$subject', 
                        '$student_class', '$student_session', '$is_favorite', 'individual', '$permission', '$material_id'
                    )";
                    
                    $con->query($individual_query);
                }
            }
        }
        
        $_SESSION['success'] = "Study material added successfully";
        header("Location: allevent.php");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . $con->error;
        header("Location: addevent.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add Study Material</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="css/summernote.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

<?php include 'header.php'; ?>
<aside class="main-sidebar"><section class="sidebar"><?php include 'sidebar.php'; ?></section></aside>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Add Study Material</h1>
    <ol class="breadcrumb">
      <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Add Study Material</li>
    </ol>
  </section>

  <section class="content">
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-ban"></i> Error!</h4>
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>
    
    <div class="row">
      <div class="col-md-10 col-md-offset-1">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Material Details</h3>
          </div>

          <form method="post" enctype="multipart/form-data">
            <div class="box-body">

              <div class="form-group">
                <label>Material Title*</label>
                <input type="text" class="form-control" name="title" required>
              </div>

              <div class="form-group">
                <label>Send Material To *</label>
                <select name="recipient_type" id="recipient_type" class="form-control" onchange="updateRecipientOptions()" required>
                  <option value="">-- Select Type --</option>
                  <option value="single">Single Student</option>
                  <option value="all">All Students</option>
                  <option value="class">By Class</option>
                </select>
              </div>

              <div class="form-group" id="student_select" style="display: none;">
                <label>Select Student</label>
                <select name="student_id" id="student_id" class="form-control select2" style="width:100%">
                  <option value="">-- Select Student --</option>
                  <?php
                  $res = $con->query("SELECT id, name FROM students");
                  while ($row = $res->fetch_assoc()) {
                    echo "<option value='".htmlspecialchars($row['id'])."'>".htmlspecialchars($row['name'])." (ID: ".htmlspecialchars($row['id']).")</option>";
                  }
                  ?>
                </select>
              </div>

              <div id="class_session_group" style="display:none;">
                <div class="form-group">
                  <label>Select Class</label>
                  <select class="form-control select2" name="class" id="class_select" style="width:100%;">
                    <option value="">-- Select Class --</option>
                    <?php
                    // Get all unique classes from comma-separated values
                    $q = mysqli_query($con, "SELECT class FROM students WHERE class IS NOT NULL AND class != ''");
                    $classes = [];
                    while ($r = mysqli_fetch_assoc($q)) {
                        if (!empty($r['class'])) {
                            // Explode comma-separated classes and add to array
                            $class_list = explode(',', $r['class']);
                            foreach ($class_list as $cls) {
                                $cls = trim($cls);
                                if (!empty($cls) && !in_array($cls, $classes)) {
                                    $classes[] = $cls;
                                    echo "<option value='".htmlspecialchars($cls)."'>".htmlspecialchars($cls)."</option>";
                                }
                            }
                        }
                    }
                    // Sort classes numerically if possible, otherwise alphabetically
                    usort($classes, function($a, $b) {
                        if (is_numeric($a) && is_numeric($b)) {
                            return $a - $b;
                        }
                        return strcmp($a, $b);
                    });
                    ?>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Select Session</label>
                  <select class="form-control select2" name="session" style="width:100%;">
                    <option value="">-- Select Session --</option>
                    <?php
                    $q = mysqli_query($con, "SELECT DISTINCT session FROM students WHERE session IS NOT NULL AND session != ''");
                    while ($r = mysqli_fetch_assoc($q)) {
                      echo "<option value='".htmlspecialchars($r['session'])."'>".htmlspecialchars($r['session'])."</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label>Description*</label>
                <textarea class="form-control" name="description" id="summernote" required></textarea>
              </div>

              <div class="form-group">
                <label>Material Type*</label>
                <select class="form-control" name="material_type" required>
                  <option value="">-- Select Type --</option>
                  <option value="pdf">PDF</option>
                  <option value="doc">Word</option>
                  <option value="ppt">PowerPoint</option>
                  <option value="video">Video</option>
                  <option value="image">Image</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div class="form-group" id="video_url_group">
                <label>Hosted Video Link (Vimeo / YouTube)</label>
                <input type="url" name="material_url" id="material_url" class="form-control" placeholder="https://vimeo.com/123456789 or https://youtu.be/...">
                <p class="help-block">Select <strong>Video</strong> type, paste a Vimeo or YouTube link here, and leave file upload empty.</p>
              </div>

              <div class="form-group" id="file_upload_group">
                <label>Upload File (Max 5MB)</label>
                <input type="file" name="material_file" id="material_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.mp4">
                <p class="help-block">For PDF/documents/images upload a file. For video you can upload MP4 instead of using a link.</p>
              </div>

              <div class="form-group">
                <label>Permission*</label>
                <select class="form-control" name="permission" required>
                  <option value="yes">Yes</option>
                  <option value="no" selected>No</option>
                </select>
              </div>

              <div class="form-group">
                <label><input type="checkbox" name="is_favorite"> Mark as favorite</label>
              </div>
            </div>

            <div class="box-footer">
              <button type="submit" name="submit" class="btn btn-primary">Add Material</button>
              <button type="reset" class="btn btn-default">Reset</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>



</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="js/summernote.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
  $(document).ready(function() {
    // Initialize summernote
    $('#summernote').summernote({
      height: 200,
      toolbar: [
        ['style', ['bold', 'italic', 'underline', 'clear']],
        ['font', ['strikethrough', 'superscript', 'subscript']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link', 'picture', 'video']],
        ['view', ['fullscreen', 'codeview', 'help']]
      ]
    });
    
    // Initialize select2
    $('.select2').select2();

    function updateMaterialTypeFields() {
      const type = $('select[name="material_type"]').val();
      const isVideo = type === 'video';
      $('#video_url_group').toggle(isVideo);
      $('#material_file').prop('required', false);
    }

    $('select[name="material_type"]').on('change', updateMaterialTypeFields);
    updateMaterialTypeFields();
  });

  function updateRecipientOptions() {
    const type = document.getElementById('recipient_type').value;
    document.getElementById('student_select').style.display = type === 'single' ? 'block' : 'none';
    document.getElementById('class_session_group').style.display = type === 'class' ? 'block' : 'none';
    
    // Reset values when changing type
    if (type !== 'single') {
      $('#student_id').val('').trigger('change');
    }
    if (type !== 'class') {
      $('select[name="class"]').val('').trigger('change');
      $('select[name="session"]').val('').trigger('change');
    }
  }
</script>
</body>
</html>
