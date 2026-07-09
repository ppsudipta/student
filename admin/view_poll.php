<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

$poll_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($poll_id <= 0) {
    header('Location: allpoll.php');
    exit();
}

$poll_stmt = $con->prepare("SELECT * FROM polls WHERE id = ?");
$poll_stmt->bind_param('i', $poll_id);
$poll_stmt->execute();
$poll = $poll_stmt->get_result()->fetch_assoc();

if (!$poll) {
    header('Location: allpoll.php');
    exit();
}

$options = $con->query("
    SELECT po.*,
           COUNT(pv.id) AS vote_count
    FROM poll_options po
    LEFT JOIN poll_votes pv ON pv.option_id = po.id
    WHERE po.poll_id = $poll_id
    GROUP BY po.id
    ORDER BY po.id
");

$total_votes = 0;
$rows = [];
while ($row = $options->fetch_assoc()) {
    $total_votes += (int) $row['vote_count'];
    $rows[] = $row;
}

$voters = $con->query("
    SELECT s.name, s.registration_code, po.option_text, pv.voted_at
    FROM poll_votes pv
    JOIN students s ON s.id = pv.student_id
    JOIN poll_options po ON po.id = pv.option_id
    WHERE pv.poll_id = $poll_id
    ORDER BY pv.voted_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin | Poll Results</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <style>
    .result-bar { height: 24px; background: #3c8dbc; border-radius: 4px; min-width: 2%; }
    .result-row { margin-bottom: 16px; }
  </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'header.php'; ?>
  <aside class="main-sidebar"><section class="sidebar"><?php include 'sidebar.php'; ?></section></aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>Poll Results</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allpoll.php">Polls</a></li>
        <li class="active">Results</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><?php echo htmlspecialchars($poll['question']); ?></h3>
        </div>
        <div class="box-body">
          <p><strong>Total votes:</strong> <?php echo $total_votes; ?></p>

          <?php foreach ($rows as $row):
              $percent = $total_votes > 0 ? round(((int) $row['vote_count'] / $total_votes) * 100) : 0;
          ?>
            <div class="result-row">
              <div class="clearfix">
                <strong><?php echo htmlspecialchars($row['option_text']); ?></strong>
                <span class="pull-right"><?php echo (int) $row['vote_count']; ?> votes (<?php echo $percent; ?>%)</span>
              </div>
              <div class="progress" style="margin-top:6px;">
                <div class="progress-bar progress-bar-primary result-bar" style="width: <?php echo $percent; ?>%;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title">Voter Details</h3>
        </div>
        <div class="box-body table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Student</th>
                <th>Registration</th>
                <th>Selected Option</th>
                <th>Voted At</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($voters && $voters->num_rows > 0): ?>
                <?php while ($vote = $voters->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($vote['name']); ?></td>
                    <td><?php echo htmlspecialchars($vote['registration_code']); ?></td>
                    <td><?php echo htmlspecialchars($vote['option_text']); ?></td>
                    <td><?php echo htmlspecialchars($vote['voted_at']); ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="4" class="text-center">No votes yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="box-footer">
          <a href="allpoll.php" class="btn btn-default">Back to Polls</a>
        </div>
      </div>
    </section>
  </div>
</div>
</body>
</html>
