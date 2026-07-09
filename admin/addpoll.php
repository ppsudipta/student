<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

$classes = $con->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class");
$sessions = $con->query("SELECT DISTINCT session FROM students WHERE session IS NOT NULL AND session != '' ORDER BY session DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $send_type = $_POST['send_type'] ?? '';
    $student_id = !empty($_POST['student_id']) ? (int) $_POST['student_id'] : null;
    $class_name = !empty($_POST['class_name']) ? trim($_POST['class_name']) : null;
    $session = !empty($_POST['session']) ? trim($_POST['session']) : null;
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $options = array_values(array_filter(array_map('trim', $_POST['options'] ?? []), fn ($opt) => $opt !== ''));

    if ($question === '' || $send_type === '' || count($options) < 2) {
        $error_message = 'Question, audience, and at least two options are required.';
    } elseif ($send_type === 'single' && empty($student_id)) {
        $error_message = 'Please select a student for a single-student poll.';
    } elseif ($send_type === 'class' && empty($class_name) && empty($session)) {
        $error_message = 'Please select at least a class or a session for class-wise polls.';
    } else {
        if ($send_type !== 'single') {
            $student_id = null;
        }
        if ($send_type !== 'class') {
            $class_name = null;
            $session = null;
        }

        if ($student_id === null) {
            $stmt = $con->prepare(
                "INSERT INTO polls (question, send_type, student_id, class_name, session, expiry_date, created_at)
                 VALUES (?, ?, NULL, ?, ?, ?, NOW())"
            );
            $stmt->bind_param('sssss', $question, $send_type, $class_name, $session, $expiry_date);
        } else {
            $stmt = $con->prepare(
                "INSERT INTO polls (question, send_type, student_id, class_name, session, expiry_date, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param('ssisss', $question, $send_type, $student_id, $class_name, $session, $expiry_date);
        }

        if ($stmt->execute()) {
            $poll_id = $con->insert_id;
            $opt_stmt = $con->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
            foreach ($options as $option_text) {
                $opt_stmt->bind_param('is', $poll_id, $option_text);
                $opt_stmt->execute();
            }
            $_SESSION['success_message'] = 'Poll created successfully.';
            header('Location: allpoll.php');
            exit();
        }
        $error_message = 'Error creating poll: ' . $con->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin | Add Poll</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'header.php'; ?>
  <aside class="main-sidebar"><section class="sidebar"><?php include 'sidebar.php'; ?></section></aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Add Poll</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allpoll.php">Polls</a></li>
        <li class="active">Add Poll</li>
      </ol>
    </section>

    <section class="content">
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>

      <div class="box box-primary">
        <form method="POST">
          <div class="box-body">
            <div class="form-group">
              <label>Poll Question *</label>
              <input type="text" name="question" class="form-control" maxlength="255" required>
            </div>

            <div class="form-group">
              <label>Send Poll To *</label>
              <select name="send_type" id="send_type" class="form-control" onchange="updateAudienceFields()" required>
                <option value="">-- Select Audience --</option>
                <option value="all">All Students</option>
                <option value="class">By Class / Session</option>
                <option value="single">Single Student</option>
              </select>
            </div>

            <div id="class_fields" style="display:none;">
              <div class="form-group">
                <label>Class</label>
                <select name="class_name" class="form-control">
                  <option value="">All Classes</option>
                  <?php while ($row = $classes->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['class']); ?>">
                      <?php echo htmlspecialchars($row['class']); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Session</label>
                <select name="session" class="form-control">
                  <option value="">All Sessions</option>
                  <?php while ($row = $sessions->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($row['session']); ?>">
                      <?php echo htmlspecialchars($row['session']); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <p class="help-block">Choose class only, session only, or both to target students.</p>
            </div>

            <div class="form-group" id="student_field" style="display:none;">
              <label>Select Student</label>
              <select name="student_id" id="student_id" class="form-control" style="width:100%">
                <option value="">-- Select Student --</option>
                <?php
                $students = $con->query("SELECT id, name, registration_code FROM students ORDER BY name");
                while ($row = $students->fetch_assoc()):
                ?>
                  <option value="<?php echo (int) $row['id']; ?>">
                    <?php echo htmlspecialchars($row['name'] . ' (' . $row['registration_code'] . ')'); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Expiry Date</label>
              <input type="date" name="expiry_date" class="form-control">
              <p class="help-block">Leave blank to keep the poll open.</p>
            </div>

            <div class="form-group">
              <label>Poll Options *</label>
              <div id="options_wrap">
                <div class="input-group" style="margin-bottom:8px;">
                  <input type="text" name="options[]" class="form-control" placeholder="Option 1" required>
                </div>
                <div class="input-group" style="margin-bottom:8px;">
                  <input type="text" name="options[]" class="form-control" placeholder="Option 2" required>
                </div>
              </div>
              <button type="button" class="btn btn-default btn-sm" onclick="addOption()">
                <i class="fa fa-plus"></i> Add Option
              </button>
            </div>
          </div>
          <div class="box-footer">
            <button type="submit" class="btn btn-primary">Create Poll</button>
            <a href="allpoll.php" class="btn btn-default">Cancel</a>
          </div>
        </form>
      </div>
    </section>
  </div>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
function updateAudienceFields() {
  var type = document.getElementById('send_type').value;
  document.getElementById('class_fields').style.display = (type === 'class') ? 'block' : 'none';
  document.getElementById('student_field').style.display = (type === 'single') ? 'block' : 'none';
  if (type === 'single' && !$('#student_id').hasClass('select2-hidden-accessible')) {
    $('#student_id').select2({ width: '100%' });
  }
}

function addOption() {
  var wrap = document.getElementById('options_wrap');
  var count = wrap.querySelectorAll('input').length + 1;
  var div = document.createElement('div');
  div.className = 'input-group';
  div.style.marginBottom = '8px';
  div.innerHTML = '<input type="text" name="options[]" class="form-control" placeholder="Option ' + count + '">';
  wrap.appendChild(div);
}
</script>
</body>
</html>
