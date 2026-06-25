<?php 
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

$msg = '';
if (isset($_POST['check_code'])) {
    $code = mysqli_real_escape_string($con, $_POST['check_code']);
    $check_code = mysqli_query($con, "SELECT id FROM students WHERE registration_code = '$code'");
    echo (mysqli_num_rows($check_code) > 0) ? 'exists' : 'available';
    exit;
}

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $father_name = mysqli_real_escape_string($con, $_POST['father_name']);
    $school_name = mysqli_real_escape_string($con, $_POST['school_name']);
    $last_percentage = "00";
    
    // Handle multi-select for class
    $class_array = $_POST['class'];
    $class = implode(", ", $class_array);
    
    // Handle multi-select for session
    $session_array = $_POST['session'];
    $session = implode(", ", $session_array);
    
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $mobile_number = mysqli_real_escape_string($con, $_POST['mobile_number']);
    $pass = mysqli_real_escape_string($con, $_POST['pass']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $total_fees = mysqli_real_escape_string($con, $_POST['total_fees']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $registration_code = mysqli_real_escape_string($con, $_POST['registration_code']);
    $date = date('Y-m-d');

    // Check if registration code already exists
    $check_code = mysqli_query($con, "SELECT id FROM students WHERE registration_code = '$registration_code'");
    if (mysqli_num_rows($check_code) > 0) {
        $msg = "Error: Registration code already exists!";
    } else {
        // Get subjects from class_session table (using the first class and session for subject lookup)
        $first_class = $class_array[0];
        $first_session = $session_array[0];
        $subject_query = mysqli_query($con, "SELECT subject FROM class_session WHERE class = '$first_class' AND session = '$first_session' LIMIT 1");
        $subject_row = mysqli_fetch_assoc($subject_query);
        $course = 'no';

        // Image upload
        $image_path = '';
        if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
            $target_dir = "../img/";
            $file_name = time() . '_' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            }
        }

        $sql = "INSERT INTO students (name, father_name, school_name, last_percentage, class, course, address, mobile_number, email,total_fees, session, status, date, password, registration_code, image) 
                VALUES ('$name', '$father_name', '$school_name', '$last_percentage', '$class', '$course', '$address', '$mobile_number', '$email', '$total_fees', '$session', '$status', '$date', '$pass', '$registration_code', '$image_path')";

        if ($con->query($sql) === TRUE) {
            echo "<script>alert('Student Registered Successfully! Registration Code: $registration_code'); window.location.href='allregister.php';</script>";
            exit();
        } else {
            $msg = "Error: " . $con->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Add Student</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/skin-blue.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
  <style>
      .select2-container--default .select2-selection--multiple .select2-selection__choice{
          color:black !important;
      }
      .registration-code-exists {
          border-color: #ff0000 !important;
          color: #ff0000 !important;
      }
      .registration-code-available {
          border-color: #00cc00 !important;
      }
      #code-status {
          font-size: 12px;
          margin-top: 5px;
      }
      .status-exists {
          color: #ff0000;
          font-weight: bold;
      }
      .status-available {
          color: #00cc00;
          font-weight: bold;
      }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'header.php'; ?>
  <aside class="main-sidebar">
    <section class="sidebar">
      <div class="user-panel">
        <div class="pull-left image">
          <img src="upload/924Koala.jpg" class="img-circle" alt="User Image">
        </div>
        <div class="pull-left info">
          <p>Admin</p>
          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
        </div>
      </div>
      <?php include 'sidebar.php'; ?>
    </section>
  </aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Add New Student</h1>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Student Registration Form</h3>
        </div>
        <form method="POST" enctype="multipart/form-data" id="studentForm">
          <div class="box-body">
            <?php if ($msg != ''): ?>
              <div class="alert alert-danger"><?php echo $msg; ?></div>
            <?php endif; ?>
            <div class="form-group">
              <label>Registration Code:</label>
              <input type="text" name="registration_code" id="registration_code" class="form-control" required>
              <div id="code-status"></div>
            </div>
            <div class="form-group">
              <label>Name:</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Father's Name:</label>
              <input type="text" name="father_name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>School Name:</label>
              <input type="text" name="school_name" class="form-control" required>
            </div>
            
            <div class="form-group">
              <label>Select Class*</label>
              <select class="form-control select2" name="class[]" id="class" multiple="multiple" required style="width: 100%;">
                <?php
                $class_query = mysqli_query($con, "SELECT DISTINCT class FROM class_session WHERE status = 'active' ORDER BY class");
                while ($row = mysqli_fetch_assoc($class_query)) {
                  echo "<option value='" . htmlspecialchars($row['class']) . "'>" . htmlspecialchars($row['class']) . "</option>";
                }
                ?>
              </select>
            </div>
            
            <div class="form-group">
              <label>Select Session*</label>
              <select class="form-control select2" name="session[]" id="session" multiple="multiple" required style="width: 100%;">
                <?php
                $session_query = mysqli_query($con, "SELECT DISTINCT session FROM class_session WHERE status = 'active' ORDER BY session DESC");
                while ($row = mysqli_fetch_assoc($session_query)) {
                  echo "<option value='" . htmlspecialchars($row['session']) . "'>" . htmlspecialchars($row['session']) . "</option>";
                }
                ?>
              </select>
            </div>
            
            <div class="form-group">
              <label>Address:</label>
              <textarea name="address" class="form-control" required></textarea>
            </div>
            <div class="form-group">
              <label>Mobile:</label>
              <input type="text" name="mobile_number" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Password:</label>
              <input type="text" name="pass" class="form-control" required>
            </div>
            
            <div class="form-group">
              <label>Email:</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Total Fees:</label>
              <input type="number" name="total_fees" class="form-control" required>
            </div>
        

            <div class="form-group">
              <label>Status:</label>
              <select name="status" class="form-control" required>
                <option value="ongoing">Ongoing</option>
                <option value="suspended">Suspended</option>
                
              </select>
            </div>
            <div class="form-group">
              <label>Student Photo:</label>
              <input type="file" name="image" class="form-control" accept="image/*" required>
            </div>
          </div>
          <div class="box-footer">
            <button type="submit" name="submit" class="btn btn-primary">Register Student</button>
            <a href="allregister.php" class="btn btn-default">Back to List</a>
          </div>
        </form>
      </div>
    </section>
  </div>


</div>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "-- Select Options --",
        allowClear: true
    });
    
    // Add validation to ensure at least one option is selected
    $('form').on('submit', function(e) {
        var classSelected = $('#class').val();
        var sessionSelected = $('#session').val();
        
        if (!classSelected || classSelected.length === 0) {
            alert('Please select at least one class');
            e.preventDefault();
            return false;
        }
        
        if (!sessionSelected || sessionSelected.length === 0) {
            alert('Please select at least one session');
            e.preventDefault();
            return false;
        }
    });
    
    // Registration code validation
    $('#registration_code').on('blur', function() {
        var code = $(this).val();
        if (code.length > 0) {
            checkRegistrationCode(code);
        }
    });
    
 function checkRegistrationCode(code) {
    $.ajax({
        url: '', // same page
        type: 'POST',
        data: {check_code: code},
        success: function(response) {
            if (response === 'exists') {
                $('#registration_code').addClass('registration-code-exists')
                                       .removeClass('registration-code-available');
                $('#code-status').html('<span class="status-exists">This registration code already exists!</span>');
                alert("This registration code already exists!");
            } else {
                $('#registration_code').removeClass('registration-code-exists')
                                       .addClass('registration-code-available');
                $('#code-status').html('<span class="status-available">Registration code is available</span>');
            }
        },
        error: function() {
            $('#code-status').html('<span class="text-warning">Error checking code availability</span>');
        }
    });
}

});
</script>

</body>
</html>