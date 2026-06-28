<?php 
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

// Validate ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "Invalid material ID";
    header('Location: allevent.php');
    exit();
}

$id = intval($_GET['id']);

// Get material with prepared statement
$stmt = $con->prepare("SELECT * FROM student_materials WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$material = $result->fetch_assoc();

if (!$material) {
    $_SESSION['error'] = "Material not found";
    header('Location: allevent.php');
    exit();
}

// Get distinct values from students table
$courses = [];
$classes = [];
$sessions = [];

$query = "SELECT DISTINCT course FROM students WHERE course IS NOT NULL AND course != ''";
$result = $con->query($query);
while ($row = $result->fetch_assoc()) {
    $courses[] = $row['course'];
}

$query = "SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != ''";
$result = $con->query($query);
while ($row = $result->fetch_assoc()) {
    $classes[] = $row['class'];
}

$query = "SELECT DISTINCT session FROM students WHERE session IS NOT NULL AND session != ''";
$result = $con->query($query);
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row['session'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Validate and sanitize inputs
    $title = mysqli_real_escape_string($con, $_POST['material_title'] ?? '');
    $subject = "no subject";
    $class = mysqli_real_escape_string($con, $_POST['class'] ?? '');
    $session = mysqli_real_escape_string($con, $_POST['session'] ?? '');
    $description = mysqli_real_escape_string($con, $_POST['material_description'] ?? '');
    $type = mysqli_real_escape_string($con, $_POST['material_type'] ?? '');
    $access = mysqli_real_escape_string($con, $_POST['access_level'] ?? 'class');
    $permission = mysqli_real_escape_string($con, $_POST['permission'] ?? 'no');
    $is_fav = isset($_POST['is_favorite']) ? 1 : 0;
    $file_path = $material['file_path'];
    $material_url = trim($_POST['material_url'] ?? '');

    // Handle Vimeo video link or file replacement
    if ($type === 'video') {
        if ($material_url === '') {
            $_SESSION['error'] = "Please enter a Vimeo video link.";
            header("Location: edit_material.php?id=$id");
            exit();
        }
        if (!filter_var($material_url, FILTER_VALIDATE_URL) || !preg_match('/(vimeo\.com|player\.vimeo\.com)/i', $material_url)) {
            $_SESSION['error'] = "Please enter a valid Vimeo video link.";
            header("Location: edit_material.php?id=$id");
            exit();
        }
        if (!empty($_FILES['new_file']['name'])) {
            $_SESSION['error'] = "Video materials use Vimeo links only. Do not upload a file.";
            header("Location: edit_material.php?id=$id");
            exit();
        }
        $file_path = $material_url;
    } elseif (!empty($_FILES['new_file']['name'])) {
        $file = $_FILES['new_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
        $max_size = 10 * 1024 * 1024; // 10MB

        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Invalid file type. Allowed types: PDF, DOC, PPT, JPG, PNG";
            header("Location: edit_material.php?id=$id");
            exit();
        }

        if ($file['size'] > $max_size) {
            $_SESSION['error'] = "File too large. Maximum 10MB allowed.";
            header("Location: edit_material.php?id=$id");
            exit();
        }

        // Create upload directory if it doesn't exist
        $upload_dir = 'uploads/materials/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $new_name = uniqid() . '_' . preg_replace('/[^a-z0-9\.]/i', '_', $file['name']);
        $destination = $upload_dir . $new_name;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Delete old file if it's not the default one
            if ($file_path && file_exists($file_path) && strpos($file_path, 'uploads/') !== false) {
                unlink($file_path);
            }
            $file_path = $destination;
        } else {
            $_SESSION['error'] = "File upload failed. Please try again.";
            header("Location: edit_material.php?id=$id");
            exit();
        }
    }

    // Update material with prepared statement
    $stmt = $con->prepare("UPDATE student_materials SET 
        material_title = ?,
        material_description = ?,
        material_type = ?,
        file_path = ?,
        subject = ?,
        class = ?,
        session = ?,
        is_favorite = ?,
        access_level = ?,
        permission = ?,
        upload_date = NOW()
        WHERE id = ?");

    $stmt->bind_param("sssssssisss", 
        $title, 
        $description, 
        $type, 
        $file_path, 
        $subject, 
        $class, 
        $session, 
        $is_fav, 
        $access, 
        $permission, 
        $id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Material updated successfully";
        header('Location: allevent.php');
        exit();
    } else {
        $_SESSION['error'] = "Update failed: " . $con->error;
        header("Location: edit_material.php?id=$id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Study Material</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    .file-info {
      background: #f8f9fa;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
    }
    .file-preview {
      max-width: 100%;
      max-height: 200px;
      margin-top: 10px;
    }
    .select2-container--default .select2-selection--single {
      height: 34px;
      border-radius: 0;
      border-color: #d2d6de;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 32px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 32px;
    }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'header.php'; ?>
  <aside class="main-sidebar"><section class="sidebar"><?php include 'sidebar.php'; ?></section></aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Edit Study Material</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allevent.php">Study Materials</a></li>
        <li class="active">Edit Material</li>
      </ol>
    </section>

    <section class="content">
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
          <h4><i class="icon fa fa-ban"></i> Error!</h4>
          <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
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
                  <input type="text" name="material_title" class="form-control" required 
                         value="<?php echo htmlspecialchars($material['material_title']); ?>">
                </div>

               

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Class*</label>
                      <select name="class" class="form-control select2" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $class): ?>
                          <option value="<?= htmlspecialchars($class) ?>" 
                            <?= $material['class'] === $class ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Session*</label>
                      <select name="session" class="form-control select2" required>
                        <option value="">-- Select Session --</option>
                        <?php foreach ($sessions as $session): ?>
                          <option value="<?= htmlspecialchars($session) ?>" 
                            <?= $material['session'] === $session ? 'selected' : '' ?>>
                            <?= htmlspecialchars($session) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Material Type*</label>
                      <select name="material_type" class="form-control select2" required>
                        <?php
                          $types = [
                            'pdf' => 'PDF Document',
                            'doc' => 'Word Document',
                            'docx' => 'Word Document',
                            'ppt' => 'PowerPoint',
                            'pptx' => 'PowerPoint',
                            'video' => 'Video',
                            'image' => 'Image',
                            'other' => 'Other'
                          ];
                          foreach ($types as $key => $label) {
                            $selected = $material['material_type'] === $key ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($key) . "' $selected>" . 
                                 htmlspecialchars($label) . "</option>";
                          }
                        ?>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Description</label>
                  <textarea name="material_description" class="form-control" rows="4"><?php 
                    echo htmlspecialchars($material['material_description']); 
                  ?></textarea>
                </div>

                <div class="form-group">
                  <label>Current File</label>
                  <div class="file-info">
                    <?php if ($material['file_path']): ?>
                      <a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank" class="btn btn-default btn-sm">
                        <i class="fa fa-download"></i> Download Current File
                      </a>
                      <?php if (strpos($material['file_path'], 'image') !== false): ?>
                        <div class="mt-2">
                          <img src="<?php echo htmlspecialchars($material['file_path']); ?>" class="file-preview">
                        </div>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted">No file uploaded</span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="form-group" id="video_url_group">
                  <label>Vimeo Video Link*</label>
                  <input type="url" name="material_url" id="material_url" class="form-control" placeholder="https://vimeo.com/123456789"
                         value="<?php echo filter_var($material['file_path'], FILTER_VALIDATE_URL) ? htmlspecialchars($material['file_path']) : ''; ?>">
                  <p class="help-block">Video materials must use a Vimeo link. File upload is not used for videos.</p>
                </div>

                <div class="form-group" id="file_upload_group">
                  <label>Replace File (optional)</label>
                  <input type="file" name="new_file" id="new_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png">
                  <p class="help-block">Allowed: PDF, Word, PowerPoint, JPG, PNG (Max 10MB). Not for video materials.</p>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Access Level*</label>
                      <select name="access_level" class="form-control select2" required>
                        <option value="public" <?= $material['access_level'] === 'public' ? 'selected' : '' ?>>Public (All students)</option>
                        <option value="private" <?= $material['access_level'] === 'private' ? 'selected' : '' ?>>Private (Only selected students)</option>
                        <option value="class" <?= $material['access_level'] === 'class' ? 'selected' : '' ?>>Class (Students in same class)</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Permission*</label>
                      <select name="permission" class="form-control select2" required>
                        <option value="yes" <?= $material['permission'] === 'yes' ? 'selected' : '' ?>>Yes (Students can download)</option>
                        <option value="no" <?= $material['permission'] === 'no' ? 'selected' : '' ?>>No (View only)</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="checkbox">
                  <label>
                    <input type="checkbox" name="is_favorite" value="1" <?= $material['is_favorite'] ? 'checked' : '' ?>>
                    Mark as Favorite
                  </label>
                </div>
              </div>

              <div class="box-footer">
                <button type="submit" name="update" class="btn btn-primary">
                  <i class="fa fa-save"></i> Update Material
                </button>
                <a href="allevent.php" class="btn btn-default">
                  <i class="fa fa-times"></i> Cancel
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; <?php echo date('Y'); ?> StudyHub</strong> All rights reserved.
  </footer>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
  // Initialize Select2
  $('.select2').select2({
    width: '100%',
    theme: 'classic'
  });

  // Show file preview for image uploads
  $('input[name="new_file"]').change(function() {
    var file = this.files[0];
    if (file && file.type.match('image.*')) {
      var reader = new FileReader();
      reader.onload = function(e) {
        $('.file-preview').remove();
        $('.file-info').append('<img src="' + e.target.result + '" class="file-preview">');
      }
      reader.readAsDataURL(file);
    }
  });

  function updateMaterialTypeFields() {
    var type = $('select[name="material_type"]').val();
    var isVideo = type === 'video';
    $('#video_url_group').toggle(isVideo);
    $('#file_upload_group').toggle(!isVideo);
    $('#material_url').prop('disabled', !isVideo).prop('required', isVideo);
    if (isVideo) {
      $('#new_file').val('');
    }
  }

  $('select[name="material_type"]').on('change', updateMaterialTypeFields);
  updateMaterialTypeFields();
});
</script>
</body>
</html>
