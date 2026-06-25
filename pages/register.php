<?php
session_start();
include('config.php');

// Check if user is already logged in
if (isset($_SESSION['username'])) {
    header("Location: home.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $father_name = trim($_POST['father_name']);
    $school_name = trim($_POST['school_name']);
    $last_percentage = '00';
    $course = 'no';
    
    // Handle multiple class selection
    if (isset($_POST['class']) && is_array($_POST['class'])) {
        $class = implode(', ', $_POST['class']);
    } else {
        $class = '';
        $error = "Please select at least one class";
    }
    
    $total_fees = '0';
    $session = trim($_POST['session']);
    $status = 'Suspended'; 

    // Validate inputs
    if (empty($name) || empty($phone) || empty($email) || empty($address) || empty($father_name) || empty($password) || empty($school_name)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Phone number must be 10 digits";
    } elseif (empty($class)) {
        $error = "Please select at least one class";
    } else {
        $check_stmt = $con->prepare("SELECT id FROM students WHERE mobile_number = ? OR email = ?");
        $check_stmt->bind_param("ss", $phone, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Phone number or email already registered";
        } else {
            // Handle image upload
            $image_name = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $img_tmp = $_FILES['image']['tmp_name'];
                $img_name = basename($_FILES['image']['name']);
                $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
                $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($img_ext, $allowed_exts)) {
                    $image_name = '../img/' . uniqid('img_') . '.' . $img_ext;
                    move_uploaded_file($img_tmp, $image_name);
                } else {
                    $error = "Invalid image file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                }
            } else {
                $error = "Please upload an image file.";
            }

            if (empty($error)) {
                $registration_code = rand(10000, 99999);
                $total_fees = 5000;
                $paid_fees = 0;
                $date = date('Y-m-d');
                
                // Fix the bind_param types - 'class' is text, so use 's' not 'd'
                $stmt = $con->prepare("INSERT INTO students (name, mobile_number, email, address, father_name, school_name, last_percentage, course, class, total_fees, session, date, paid_fees, registration_code, image, status, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                // Corrected bind_param types: s=string, i=integer, d=double
                // name, mobile, email, address, father_name, school_name, last_percentage, course, class, total_fees, session, date, paid_fees, registration_code, image, status, password
                $stmt->bind_param("sssssssssisdsisss", $name, $phone, $email, $address, $father_name, $school_name, $last_percentage, $course, $class, $total_fees, $session, $date, $paid_fees, $registration_code, $image_name, $status, $password);

                if ($stmt->execute()) {
                    $success = "Registration successful! Your code: $registration_code";
                    header('location:./auth/signin.php');
                    exit();
                } else {
                    $error = "Registration failed. Please try again. Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AICTS Registration</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/common.css">
  <link rel="stylesheet" href="../assets/css/auth.css">

  <style>
    .form-group { margin-bottom: 1rem; }
    .form-control {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 8px;
    }
    select.form-control {
      appearance: none;
      background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><polyline points='6 9 12 15 18 9'/></svg>");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      background-size: 1em;
    }
    .alert { padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 8px; }
    .alert-danger { background-color: #f8d7da; color: #721c24; }
    .alert-success { background-color: #d4edda; color: #155724; }
    .checkbox-group {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 10px;
      max-height: 200px;
      overflow-y: auto;
    }
    .checkbox-item {
      display: flex;
      margin-bottom: 5px;
    }
  </style>
</head>

<body class="scrollbar-hidden">
  <main class="auth-main">
    <section class="auth signin" style="margin-top: -55px;">
      <div class="heading">
        <h2>Create Your Account</h2>
        <p>Register to access the App</p>
      </div>

      <div class="form-area auth-form">
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input class="form-control" type="text" name="name" id="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
          </div>

          <div class="form-group">
            <label for="phone">Mobile Number</label>
            <input class="form-control" type="tel" name="phone" id="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input class="form-control" type="text" name="password" id="password" required value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>">
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <input class="form-control" type="email" name="email" id="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
          </div>

          <div class="form-group">
            <label for="address">Full Address</label>
            <input class="form-control" type="text" name="address" id="address" required value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
          </div>

          <div class="form-group">
            <label for="father_name">Father's Name</label>
            <input class="form-control" type="text" name="father_name" id="father_name" required value="<?php echo isset($_POST['father_name']) ? htmlspecialchars($_POST['father_name']) : ''; ?>">
          </div>

          <div class="form-group">
            <label for="school_name">Ongoing School Name</label>
            <input class="form-control" type="text" name="school_name" id="school_name" required value="<?php echo isset($_POST['school_name']) ? htmlspecialchars($_POST['school_name']) : ''; ?>">
          </div>

          <div class="form-group">
            <label for="class">Admission For Class (Select one or more)</label>
            <div class="checkbox-group">
              <?php
              $class_q = $con->query("SELECT DISTINCT class FROM class_session WHERE class != '' ORDER BY class ASC");
              while ($row = $class_q->fetch_assoc()) {
                $isChecked = isset($_POST['class']) && in_array($row['class'], $_POST['class']) ? 'checked' : '';
                echo "<div class='checkbox-item'>";
                echo "<input type='checkbox' name='class[]' id='class_".htmlspecialchars($row['class'])."' value='".htmlspecialchars($row['class'])."' $isChecked>";
                echo "<label for='class_".htmlspecialchars($row['class'])."' style='margin-left: 8px;'>".htmlspecialchars($row['class'])."</label>";
                echo "</div>";
              }
              ?>
            </div>
          </div>
          
          <div class="form-group">
            <label for="session">Session</label>
            <input class="form-control" type="text" name="session" id="session" value="2025-2026" required readonly>
          </div>

          <div class="form-group">
            <label for="image">Choose Photo</label>
            <input class="form-control" type="file" name="image" id="image" accept="image/*" required>
          </div>

          <button type="submit" class="btn-primary w-100 mt-3">Register Now</button>
        </form>
      </div>
    </section>
  </main>

  <script src="../assets/js/jquery-3.6.1.min.js"></script>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script>
    $('#phone').on('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
    });
  </script>
</body>
</html>