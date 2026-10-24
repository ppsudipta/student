<?php 
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] != true) {
    header('location:index.php');
    exit;
}
include('config.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('location:allcenters.php');
    exit;
}

$stmt = $con->prepare("SELECT * FROM centers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$center = $res->fetch_assoc();

if (!$center) {
    echo "<script>alert('Center location not found!'); window.location.href='allcenters.php';</script>";
    exit;
}

$error = '';

if (isset($_POST['submit'])) {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $map_url = trim($_POST['map_url'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Please enter the Center Name.';
    } elseif (empty($address)) {
        $error = 'Please enter the Center Address.';
    } else {
        if (empty($map_url)) {
            $map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
        }

        if ($is_primary === 1) {
            $con->query("UPDATE centers SET is_primary = 0 WHERE id != $id");
        }

        $upStmt = $con->prepare("UPDATE centers SET name = ?, address = ?, map_url = ?, phone = ?, email = ?, is_primary = ?, sort_order = ? WHERE id = ?");
        $upStmt->bind_param("sssssiii", $name, $address, $map_url, $phone, $email, $is_primary, $sort_order, $id);
        
        if ($upStmt->execute()) {
            echo "<script>alert('Center location updated successfully!'); window.location.href='allcenters.php';</script>";
            exit;
        } else {
            $error = 'Database Error: ' . $con->error;
        }
    }
} else {
    $name = $center['name'];
    $address = $center['address'];
    $map_url = $center['map_url'];
    $phone = $center['phone'];
    $email = $center['email'];
    $sort_order = $center['sort_order'];
    $is_primary = $center['is_primary'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Edit Center Location | Admin</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="css/ionicons.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/_all-skins.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
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
      <h1>
        Edit Center Location
        <small>Update branch name, address, or Google Maps link</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="allcenters.php">Center Locations</a></li>
        <li class="active">Edit Center</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-md-8 col-md-offset-2">
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
              <i class="icon fa fa-ban"></i> <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <div class="box box-warning">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-pencil text-warning"></i> Edit Details: <?= htmlspecialchars($center['name']) ?></h3>
            </div>
            <form role="form" method="post" accept-charset="utf-8">
              <div class="box-body">
                <div class="form-group">
                  <label for="centerName">Center / Branch Name <span class="text-danger">*</span></label>
                  <input class="form-control" id="centerName" name="name" required 
                         placeholder="e.g. Bonhoogly Center, Dunlop Branch" 
                         type="text" value="<?= htmlspecialchars($name) ?>">
                </div>

                <div class="form-group">
                  <label for="centerAddress">Full Address <span class="text-danger">*</span></label>
                  <textarea class="form-control" id="centerAddress" name="address" rows="3" required
                            placeholder="Enter the full street address, landmark, city, pin code"><?= htmlspecialchars($address) ?></textarea>
                </div>

                <div class="form-group">
                  <label for="centerMapUrl">Google Maps Link / URL</label>
                  <div class="input-group">
                    <input class="form-control" id="centerMapUrl" name="map_url" type="url"
                           placeholder="e.g. https://maps.app.goo.gl/xxxx or https://www.google.com/maps/..."
                           value="<?= htmlspecialchars($map_url) ?>">
                    <?php if (!empty($map_url)): ?>
                      <span class="input-group-btn">
                        <a href="<?= htmlspecialchars($map_url) ?>" target="_blank" class="btn btn-info" rel="noopener noreferrer">
                          <i class="fa fa-external-link"></i> Test Link
                        </a>
                      </span>
                    <?php endif; ?>
                  </div>
                  <small class="text-muted">
                    <i class="fa fa-info-circle text-info"></i> Share link from Google Maps app or web.
                    <em>(If left blank, auto-generates search link from address)</em>.
                  </small>
                </div>

                <div class="row">
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label for="centerPhone">Contact Phone Number (Optional)</label>
                      <input class="form-control" id="centerPhone" name="phone" type="text"
                             placeholder="e.g. 9038495748" value="<?= htmlspecialchars($phone) ?>">
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label for="centerEmail">Contact Email (Optional)</label>
                      <input class="form-control" id="centerEmail" name="email" type="email"
                             placeholder="e.g. center@deyeducation.com" value="<?= htmlspecialchars($email) ?>">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label for="centerSort">Display Sort Order</label>
                      <input class="form-control" id="centerSort" name="sort_order" type="number" min="0"
                             value="<?= htmlspecialchars($sort_order) ?>">
                      <small class="text-muted">Lower numbers appear first.</small>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group" style="padding-top: 25px;">
                      <div class="checkbox">
                        <label>
                          <input type="checkbox" name="is_primary" value="1" <?= $is_primary ? 'checked' : '' ?>>
                          <strong>Set as Main / Primary Center</strong>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="box-footer">
                <button type="submit" name="submit" class="btn btn-warning">
                  <i class="fa fa-check"></i> Update Center
                </button>
                <a href="allcenters.php" class="btn btn-default pull-right">
                  Cancel
                </a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <div class="pull-right hidden-xs"><b>Version</b> 1.0</div>
    <strong>Copyright &copy; <?= date('Y') ?> Dey Education.</strong> All rights reserved.
  </footer>
</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
</body>
</html>
