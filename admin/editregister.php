<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

$id = intval($_GET['id']);
$student = $con->query("SELECT * FROM students WHERE id = $id")->fetch_assoc();
// Check registration code availability (AJAX)
if (isset($_POST['check_code']) && isset($_POST['id'])) {
    $code = mysqli_real_escape_string($con, $_POST['check_code']);
    $id   = intval($_POST['id']);

    $check_code = mysqli_query($con, "SELECT id FROM students WHERE registration_code = '$code' AND id != $id");
    echo (mysqli_num_rows($check_code) > 0) ? 'exists' : 'available';
 exit();
}

if (!$student) {
    header('location:allregister.php');
    exit();
}

// Initialize variables with existing values
$name = $student['name'];
$father_name = $student['father_name'];
$school_name = $student['school_name'];
$last_percentage = $student['last_percentage'];
$class = $student['class'];
$course = $student['course'];
$address = $student['address'];
$mobile_number = $student['mobile_number'];
$email = $student['email'];
$password = $student['password'];
$registration_code = $student['registration_code'];
$session = $student['session'];
$status = $student['status'];
$date = $student['date'];
$image_path = $student['image'];

if (isset($_POST['submit'])) {
    // Sanitize all inputs
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $father_name = mysqli_real_escape_string($con, $_POST['father_name']);
    $school_name = mysqli_real_escape_string($con, $_POST['school_name']);
    $last_percentage = mysqli_real_escape_string($con, $_POST['last_percentage']);
    
    // Handle multiselect class field
    if (isset($_POST['class']) && is_array($_POST['class'])) {
        $class = mysqli_real_escape_string($con, implode(', ', $_POST['class']));
    } else {
        $class = '';
    }
    
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $mobile_number = mysqli_real_escape_string($con, $_POST['mobile_number']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $registration_code = mysqli_real_escape_string($con, $_POST['registration_code']);
    $session = mysqli_real_escape_string($con, $_POST['session']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $date = mysqli_real_escape_string($con, $_POST['date']);

    // Handle image upload
    if ($_FILES['image1']['name'] != '') {
        $target_dir = "../img/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES["image1"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["image1"]["tmp_name"], $target_file)) {
            // Delete old image if it exists
            if ($image_path && file_exists($image_path)) {
                unlink($image_path);
            }
            $image_path = $target_file;
        }
    }

    // Get subjects from class_session table if class or session changed
    // Note: This logic might need adjustment for multiple classes
    // For now, we'll just keep the existing course logic
    if (!empty($class) && !empty($session)) {
        // For multiple classes, we might need to handle this differently
        // For simplicity, we'll just use the first class to determine subjects
        $first_class = explode(',', $class)[0];
        $first_class = trim($first_class);
        
        $subject_query = mysqli_query($con, "SELECT subject FROM class_session WHERE class = '$first_class' AND session = '$session' LIMIT 1");
        if ($subject_query && mysqli_num_rows($subject_query) > 0) {
            $subject_row = mysqli_fetch_assoc($subject_query);
            $course = $subject_row['subject'];
        }
    }

    $sql = "UPDATE students SET
            name='$name',
            father_name='$father_name',
            school_name='$school_name',
            last_percentage='$last_percentage',
            class='$class',
            course='$course',
            address='$address',
            mobile_number='$mobile_number',
            email='$email',
            password='$password',
            registration_code='$registration_code',
            session='$session',
            status='$status',
            date='$date',
            image='$image_path'
            WHERE id=$id";

    if ($con->query($sql)) {
        echo "<script>alert('Student updated successfully'); window.location.href='allregister.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error updating student: " . $con->error . "');</script>";
    }
}

// Prepare selected classes for display
$selected_classes = [];
if (!empty($class)) {
    $selected_classes = array_map('trim', explode(',', $class));
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Student</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
  <style>
    .subject-badge {
      display: inline-block;
      margin: 3px;
      padding: 5px 10px;
      background-color: #e9ecef;
      border-radius: 3px;
      font-size: 14px;
    }
    .current-image {
      max-width: 200px;
      max-height: 200px;
      margin-top: 10px;
      border: 1px solid #ddd;
      padding: 5px;
    }
    .select2-container--default .select2-selection--multiple {
      min-height: 34px;
      padding: 3px;
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
      <h1>Edit Student</h1>
      <ol class="breadcrumb">
        <li><a href="allregister.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Edit Student</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Edit Student Details</h3>
        </div>
        <div class="box-body">
          <form method="post" enctype="multipart/form-data">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Student Image</label>
                  <input type="file" name="image1" class="form-control" accept="image/*">
                  <?php if ($image_path && file_exists($image_path)): ?>
                    <div>
                      <img src="<?php echo htmlspecialchars($image_path); ?>" class="current-image">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="remove_image"> Remove current image
                        </label>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="form-group">
                  <label>Name*</label>
                  <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($name); ?>">
                </div>

                <div class="form-group">
                  <label>Father's Name*</label>
                  <input type="text" name="father_name" class="form-control" required value="<?php echo htmlspecialchars($father_name); ?>">
                </div>

                <div class="form-group">
                  <label>School Name*</label>
                  <input type="text" name="school_name" class="form-control" required value="<?php echo htmlspecialchars($school_name); ?>">
                </div>

                
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Class* (Select multiple if applicable)</label>
                  <select name="class[]" class="form-control select2" multiple="multiple" required style="width: 100%;">
                    <?php
                    $class_query = mysqli_query($con, "SELECT DISTINCT class FROM class_session WHERE status = 'active' ORDER BY class");
                    while ($row = mysqli_fetch_assoc($class_query)) {
                      $class_name = htmlspecialchars($row['class']);
                      $selected = in_array($class_name, $selected_classes) ? ' selected="selected"' : '';
                      echo "<option value='$class_name'$selected>$class_name</option>";
                    }
                    ?>
                  </select>
                </div>

                <div class="form-group">
                  <label>Session*</label>
                  <select name="session" class="form-control select2" required>
                    <option value="">-- Select Session --</option>
                    <?php
                    $session_query = mysqli_query($con, "SELECT DISTINCT session FROM class_session WHERE status = 'active' ORDER BY session DESC");
                    while ($row = mysqli_fetch_assoc($session_query)) {
                      $selected = ($row['session'] == $session) ? ' selected' : '';
                      echo "<option value='" . htmlspecialchars($row['session']) . "'$selected>" . htmlspecialchars($row['session']) . "</option>";
                    }
                    ?>
                  </select>
                </div>

               

                <div class="form-group">
                  <label>Status*</label>
                  <select name="status" class="form-control" required>
                    <option value="ongoing" <?= $status == 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                    <option value="suspended" <?= $status == 'suspended' ? 'selected' : '' ?>>Suspended</option>
                   
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Address*</label>
                  <textarea name="address" class="form-control" required><?php echo htmlspecialchars($address); ?></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Mobile Number*</label>
                  <input type="text" name="mobile_number" class="form-control" required value="<?php echo htmlspecialchars($mobile_number); ?>">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>password</label>
                  <input type="text" name="password" class="form-control" value="<?php echo htmlspecialchars($password); ?>">
                </div>
              </div>
              <div class="form-group">
  <label>Registration Code</label>
  <input type="text" name="registration_code" id="registration_code" class="form-control" 
         value="<?php echo htmlspecialchars($registration_code); ?>">
  <div id="code-status"></div>
</div>

            </div>

            <div class="form-group">
              <label>Registration Date</label>
              <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
            </div>

            <div class="text-center">
              <button type="submit" name="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Update Student
              </button>
              <a href="allregister.php" class="btn btn-default">
                <i class="fa fa-times"></i> Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer text-center">
    <strong>&copy; <?php echo date('Y'); ?> Sunrise Academy</strong> All rights reserved.
  </footer>
</div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2();

    // Registration code validation
    $('#registration_code').on('blur', function() {
        var code = $(this).val();
        var id = <?php echo $id; ?>; // current student id
        if (code.length > 0) {
            $.ajax({
                url: '', // same page
                type: 'POST',
                data: {check_code: code, id: id},
                success: function(response) {
                    if (response === 'exists') {
                        $('#registration_code').css('border-color', 'red');
                        $('#code-status').html('<span style="color:red;font-weight:bold;">This registration code already exists!</span>');
                        alert("This registration code already exists!");
                    } else {
                        $('#registration_code').css('border-color', 'green');
                        $('#code-status').html('<span style="color:green;font-weight:bold;">Registration code is available</span>');
                    }
                },
                error: function() {
                    $('#code-status').html('<span class="text-warning">Error checking code availability</span>');
                }
            });
        }
    });
});

</script>
</body>
</html>