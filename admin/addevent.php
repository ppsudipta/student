<?php
session_start();
if (!isset($_SESSION['username'])) {
  header('location:index.php');
  exit();
}
include('config.php');
include_once __DIR__ . '/students_helper.php';

// Handle form submission
if (isset($_POST['submit'])) {
    // Validate and sanitize inputs
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $upload_kind = strtolower(trim((string) ($_POST['upload_kind'] ?? '')));
    $subject = "no subject";
    $permission = mysqli_real_escape_string($con, $_POST['permission']);
    $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
    $access_level = 'class'; // Default access level
    $allowed_categories = ['Worksheet', 'Question Paper', 'RPS', 'Others'];
    $material_category = '';
    $material_type = 'other';

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
            // Keep class/session for admin reference only — do NOT use access_level=class
            // or this material will appear for every student in that class.
            $class = $student['class'];
            $session = $student['session'];
            $access_level = 'individual';
        } else {
            $_SESSION['error'] = "Selected student not found.";
            header("Location: addevent.php");
            exit();
        }
    } elseif ($recipient_type === 'class') {
        $class = isset($_POST['class']) ? mysqli_real_escape_string($con, $_POST['class']) : '';
        $session = isset($_POST['session']) ? mysqli_real_escape_string($con, $_POST['session']) : '';
        $student_name = 'Multiple Students';
        $student_email = 'class@example.com';
        $access_level = 'class';
        
        // For class-wise distribution, we need to send to all students in the selected class
        // We'll handle this after the main insert by creating individual records
        $class_wise = true;
    } else { // All students
        $student_name = 'All Students';
        $student_email = 'all@example.com';
        $access_level = 'public';
    }

    // Handle file upload or Vimeo video link
    $file_path = '';
    $material_url = trim($_POST['material_url'] ?? '');
    if ($upload_kind === 'video') {
        $material_type = 'video';
        $material_category = '';
        if ($material_url === '') {
            $_SESSION['error'] = "Please enter a Vimeo video link.";
            header("Location: addevent.php");
            exit();
        }
        if (!filter_var($material_url, FILTER_VALIDATE_URL) || !preg_match('/(vimeo\.com|player\.vimeo\.com)/i', $material_url)) {
            $_SESSION['error'] = "Please enter a valid Vimeo video link.";
            header("Location: addevent.php");
            exit();
        }
        if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $_SESSION['error'] = "Video materials use Vimeo links only. Do not upload a file.";
            header("Location: addevent.php");
            exit();
        }
        $file_path = $material_url;
    } elseif ($upload_kind === 'other') {
        $selected_category = trim((string) ($_POST['material_category'] ?? ''));
        if (!in_array($selected_category, $allowed_categories, true)) {
            $_SESSION['error'] = "Please select sub category: Worksheet, Question Paper, RPS, or Others.";
            header("Location: addevent.php");
            exit();
        }
        $material_category = mysqli_real_escape_string($con, $selected_category);

        if (!(isset($_FILES['material_file']) && $_FILES['material_file']['error'] !== UPLOAD_ERR_NO_FILE)) {
            $_SESSION['error'] = "Please upload a file for this material.";
            header("Location: addevent.php");
            exit();
        }

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
            'png' => 'image/png'
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

        if (in_array($file_ext, ['pdf'], true)) {
            $material_type = 'pdf';
        } elseif (in_array($file_ext, ['doc', 'docx'], true)) {
            $material_type = 'doc';
        } elseif (in_array($file_ext, ['ppt', 'pptx'], true)) {
            $material_type = 'ppt';
        } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png'], true)) {
            $material_type = 'image';
        } else {
            $material_type = 'other';
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
        $_SESSION['error'] = "Please select Video or Other.";
        header("Location: addevent.php");
        exit();
    }

    // Insert into database
    $query = "INSERT INTO student_materials (
        student_id, student_name, student_email, material_title, material_category,
        material_description, material_type, file_path, subject, 
        class, session, is_favorite, access_level, permission
    ) VALUES (
        '$student_id', '$student_name', '$student_email', '$title', '$material_category',
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
                        student_id, student_name, student_email, material_title, material_category,
                        material_description, material_type, file_path, subject, 
                        class, session, is_favorite, access_level, permission, parent_material_id
                    ) VALUES (
                        '$student_id', '$student_name', '$student_email', '$title', '$material_category',
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
  <?php include 'sidebar.php'; ?>

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
                    foreach (students_unique_classes($con) as $cls) {
                        echo "<option value='".htmlspecialchars($cls)."'>".htmlspecialchars($cls)."</option>";
                    }
                    ?>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Select Session</label>
                  <select class="form-control select2" name="session" style="width:100%;">
                    <option value="">-- Select Session --</option>
                    <?php
                    foreach (students_unique_sessions($con) as $ses) {
                      echo "<option value='".htmlspecialchars($ses)."'>".htmlspecialchars($ses)."</option>";
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
                <label>Upload Type*</label>
                <select class="form-control" name="upload_kind" id="upload_kind" required onchange="updateMaterialTypeFields()">
                  <option value="">-- Select Type --</option>
                  <option value="video">Video</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div class="form-group" id="video_url_group" style="display:none;">
                <label>Vimeo Video Link*</label>
                <input type="url" name="material_url" id="material_url" class="form-control" placeholder="https://vimeo.com/123456789">
                <p class="help-block">Paste Vimeo link only for Video.</p>
              </div>

              <div id="other_fields_group" style="display:none;">
                <div class="form-group">
                  <label>Sub Category*</label>
                  <select class="form-control" name="material_category" id="material_category">
                    <option value="">-- Select Sub Category --</option>
                    <option value="Worksheet">1. Worksheet</option>
                    <option value="Question Paper">2. Question Paper</option>
                    <option value="RPS">3. RPS</option>
                    <option value="Others">4. Others</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Upload File (Max 5MB)*</label>
                  <input type="file" name="material_file" id="material_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                  <p class="help-block">PDF, Word, PowerPoint, or image.</p>
                </div>
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
  function updateMaterialTypeFields() {
    var kindEl = document.getElementById('upload_kind');
    var kind = kindEl ? kindEl.value : '';
    var isVideo = kind === 'video';
    var isOther = kind === 'other';

    var videoGroup = document.getElementById('video_url_group');
    var otherGroup = document.getElementById('other_fields_group');
    var urlInput = document.getElementById('material_url');
    var fileInput = document.getElementById('material_file');
    var categoryInput = document.getElementById('material_category');

    if (videoGroup) {
      videoGroup.style.display = isVideo ? 'block' : 'none';
    }
    if (otherGroup) {
      otherGroup.style.display = isOther ? 'block' : 'none';
    }

    if (urlInput) {
      urlInput.disabled = !isVideo;
      urlInput.required = isVideo;
      if (!isVideo) {
        urlInput.value = '';
      }
    }
    if (categoryInput) {
      categoryInput.required = isOther;
      categoryInput.disabled = !isOther;
      if (!isOther) {
        categoryInput.value = '';
      }
    }
    if (fileInput) {
      fileInput.required = isOther;
      fileInput.disabled = !isOther;
      if (!isOther) {
        fileInput.value = '';
      }
    }
  }

  function updateRecipientOptions() {
    var type = document.getElementById('recipient_type').value;
    document.getElementById('student_select').style.display = type === 'single' ? 'block' : 'none';
    document.getElementById('class_session_group').style.display = type === 'class' ? 'block' : 'none';

    if (window.jQuery) {
      if (type !== 'single') {
        $('#student_id').val('').trigger('change');
      }
      if (type !== 'class') {
        $('select[name="class"]').val('').trigger('change');
        $('select[name="session"]').val('').trigger('change');
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var uploadKind = document.getElementById('upload_kind');
    if (uploadKind) {
      uploadKind.addEventListener('change', updateMaterialTypeFields);
      updateMaterialTypeFields();
    }

    if (window.jQuery) {
      try {
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
      } catch (e) {}

      try {
        $('.select2').select2();
      } catch (e) {}
    }
  });
</script>
</body>
</html>
