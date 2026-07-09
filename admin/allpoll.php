<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_poll'])) {
    $poll_id = (int) $_POST['poll_id'];
    $con->query("DELETE FROM polls WHERE id = $poll_id");
    $_SESSION['success_message'] = 'Poll deleted successfully.';
    header('Location: allpoll.php');
    exit();
}

$polls = $con->query("
    SELECT p.*,
           (SELECT COUNT(*) FROM poll_votes pv WHERE pv.poll_id = p.id) AS total_votes,
           (SELECT COUNT(*) FROM poll_options po WHERE po.poll_id = p.id) AS option_count
    FROM polls p
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin | All Polls</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
  <?php include 'header.php'; ?>
  <aside class="main-sidebar"><section class="sidebar"><?php include 'sidebar.php'; ?></section></aside>

  <div class="content-wrapper">
    <section class="content-header">
      <h1>All Polls</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Polls</li>
      </ol>
    </section>

    <section class="content">
      <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
      <?php endif; ?>

      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Poll List</h3>
          <div class="box-tools">
            <a href="addpoll.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Poll</a>
          </div>
        </div>
        <div class="box-body table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Question</th>
                <th>Audience</th>
                <th>Options</th>
                <th>Votes</th>
                <th>Expiry</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($polls && $polls->num_rows > 0): ?>
                <?php while ($poll = $polls->fetch_assoc()): ?>
                  <tr>
                    <td><?php echo (int) $poll['id']; ?></td>
                    <td><?php echo htmlspecialchars($poll['question']); ?></td>
                    <td>
                      <?php
                      if ($poll['send_type'] === 'all') {
                          echo 'All Students';
                      } elseif ($poll['send_type'] === 'single') {
                          echo 'Single Student #' . (int) $poll['student_id'];
                      } else {
                          $parts = [];
                          if (!empty($poll['class_name'])) {
                              $parts[] = 'Class: ' . htmlspecialchars($poll['class_name']);
                          }
                          if (!empty($poll['session'])) {
                              $parts[] = 'Session: ' . htmlspecialchars($poll['session']);
                          }
                          echo $parts ? implode('<br>', $parts) : 'Class / Session';
                      }
                      ?>
                    </td>
                    <td><?php echo (int) $poll['option_count']; ?></td>
                    <td><?php echo (int) $poll['total_votes']; ?></td>
                    <td><?php echo $poll['expiry_date'] ? htmlspecialchars($poll['expiry_date']) : 'No expiry'; ?></td>
                    <td><?php echo htmlspecialchars($poll['created_at']); ?></td>
                    <td>
                      <a href="view_poll.php?id=<?php echo (int) $poll['id']; ?>" class="btn btn-info btn-xs">
                        <i class="fa fa-bar-chart"></i> Results
                      </a>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this poll and all votes?');">
                        <input type="hidden" name="poll_id" value="<?php echo (int) $poll['id']; ?>">
                        <button type="submit" name="delete_poll" class="btn btn-danger btn-xs">
                          <i class="fa fa-trash"></i> Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="8" class="text-center">No polls found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</div>
</body>
</html>
