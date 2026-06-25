<?php
session_start();
include('config.php');
include('header.php');

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'];

// Get student info
$stmt = $con->prepare("SELECT * FROM students WHERE name = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();

if (!$student) {
    header('Location: index.php');
    exit();
}

$student_id = $student['id'];

// Handle month filter
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$month_start = date('Y-m-01', strtotime($selected_month));
$month_end = date('Y-m-t', strtotime($selected_month));

// Get attendance records for the selected month
$attendance = [];
$stmt = $con->prepare("SELECT * FROM attendance WHERE student_id = ? AND attendance_date BETWEEN ? AND ? ORDER BY attendance_date DESC");
$stmt->bind_param("iss", $student_id, $month_start, $month_end);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}

// Calculate attendance statistics
$total_days = count($attendance);
$present_days = 0;
$absent_days = 0;

foreach ($attendance as $record) {
    if ($record['status'] == 'Present') {
        $present_days++;
    } else {
        $absent_days++;
    }
}

$attendance_percentage = $total_days > 0 ? round(($present_days / $total_days) * 100) : 0;

// Company Info
$company = $con->query("SELECT * FROM company LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($student['name']) ?> - Attendance Report</title>
  <link rel="shortcut icon" href="../admin/<?= htmlspecialchars($company['logo'] ?? '') ?>" type="image/x-icon">
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .progress-card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      padding: 20px;
      background: white;
    }
    .chart-container {
      position: relative;
      height: 300px;
      margin-bottom: 30px;
    }
    .attendance-card {
      border-left: 4px solid #4e73df;
      margin-bottom: 15px;
      padding: 15px;
      background: #f8f9fc;
    }
    .badge-present { background-color: #1cc88a; }
    .badge-absent { background-color: #e74a3b; }
    .filter-form {
      display: flex;
      gap: 10px;
      align-items: center;
    }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-0">My Attendance Report</h1>
      <p class="text-muted">Track your attendance records</p>
    </div>
    <div class="text-center">
      <strong><?= htmlspecialchars($student['name']) ?></strong><br>
      <small class="text-muted"><?= htmlspecialchars($student['registration_code']) ?></small>
    </div>
  </div>

  <!-- Month Filter -->
  <div class="progress-card mb-4">
    <form method="GET" class="filter-form">
      <label for="month" class="form-label mb-0">Filter by Month:</label>
      <input type="month" class="form-control" id="month" name="month" value="<?= $selected_month ?>" style="width: auto;">
      <button type="submit" class="btn btn-primary">Apply Filter</button>
      <a href="?month=<?= date('Y-m') ?>" class="btn btn-secondary">Current Month</a>
    </form>
  </div>

  <div class="row mb-4">
    <div class="col-md-3">
      <div class="progress-card text-center">
        <h5>Total Days</h5>
        <h2><?= $total_days ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="progress-card text-center">
        <h5>Present Days</h5>
        <h2 class="text-success"><?= $present_days ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="progress-card text-center">
        <h5>Absent Days</h5>
        <h2 class="text-danger"><?= $absent_days ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="progress-card text-center">
        <h5>Attendance %</h5>
        <h2><?= $attendance_percentage ?>%</h2>
      </div>
    </div>
  </div>

  <!-- Charts -->


  <!-- Attendance Records -->
  <div class="progress-card">
    <h5>Attendance Records for <?= date('F Y', strtotime($selected_month)) ?></h5>
    <?php if (empty($attendance)): ?>
      <div class="alert alert-info">No attendance records available for this month.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Date</th>
              <th>Day</th>
              <th>Class</th>
              <th>Title</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($attendance as $record): ?>
              <tr>
                <td><?= date('M j, Y', strtotime($record['attendance_date'])) ?></td>
                <td><?= htmlspecialchars($record['day_name']) ?></td>
                <td><?= htmlspecialchars($record['class_name']) ?></td>
                <td><?= htmlspecialchars($record['attendance_title']) ?></td>
                <td>
                  <?php if ($record['status'] == 'Present'): ?>
                    <span class="badge badge-present">Present</span>
                  <?php else: ?>
                    <span class="badge badge-absent">Absent</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif;
    
    include('footer.php');
    ?>
  </div>
</div>

<script>
// Attendance Pie Chart
new Chart(document.getElementById('attendancePieChart').getContext('2d'), {
  type: 'pie',
  data: {
    labels: ['Present', 'Absent'],
    datasets: [{
      data: [<?= $present_days ?>, <?= $absent_days ?>],
      backgroundColor: ['#1cc88a', '#e74a3b']
    }]
  },
  options: { 
    responsive: true, 
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom'
      }
    }
  }
});

// Daily Attendance Chart - Prepare data
<?php
// Group attendance by date for the chart
$dailyData = [];
for ($i = 1; $i <= date('t', strtotime($selected_month)); $i++) {
    $currentDate = date('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT);
    $dailyData[$currentDate] = null; // Initialize with null (no data)
}

// Fill in attendance data
foreach ($attendance as $record) {
    $date = $record['attendance_date'];
    $dailyData[$date] = $record['status'] == 'Present' ? 1 : 0;
}

$chartDates = [];
$chartData = [];

foreach ($dailyData as $date => $status) {
    $chartDates[] = date('j', strtotime($date));
    $chartData[] = $status; // 1 for present, 0 for absent, null for no data
}
?>

// Daily Attendance Chart
new Chart(document.getElementById('dailyAttendanceChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartDates) ?>,
    datasets: [{
      label: 'Attendance (1=Present, 0=Absent)',
      data: <?= json_encode($chartData) ?>,
      backgroundColor: function(context) {
        var value = context.dataset.data[context.dataIndex];
        if (value === null) return '#858796'; // No data
        return value === 1 ? '#1cc88a' : '#e74a3b';
      },
      borderColor: function(context) {
        var value = context.dataset.data[context.dataIndex];
        if (value === null) return '#858796'; // No data
        return value === 1 ? '#1cc88a' : '#e74a3b';
      },
      borderWidth: 1
    }]
  },
  options: { 
    responsive: true, 
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        max: 1,
        ticks: {
          stepSize: 1,
          callback: function(value) {
            if (value === 0) return 'Absent';
            if (value === 1) return 'Present';
            return '';
          }
        }
      }
    }
  }
});
</script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>