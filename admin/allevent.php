<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

function delete_material_by_id(mysqli $con, int $id): bool
{
    $stmt = $con->prepare('SELECT file_path, material_type FROM student_materials WHERE id = ?');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return false;
    }

    $filePath = (string) ($row['file_path'] ?? '');
    $type = strtolower((string) ($row['material_type'] ?? ''));
    // Only unlink local uploaded files, not remote video URLs.
    if ($filePath !== '' && $type !== 'video' && strpos($filePath, 'http') !== 0 && is_file($filePath)) {
        @unlink($filePath);
    }

    $del = $con->prepare('DELETE FROM student_materials WHERE id = ?');
    if (!$del) {
        return false;
    }
    $del->bind_param('i', $id);
    $ok = $del->execute();
    $del->close();
    return $ok;
}

// Single delete (legacy link)
if (isset($_GET['id']) && !isset($_POST['bulk_delete'])) {
    $id = (int) $_GET['id'];
    if ($id > 0 && delete_material_by_id($con, $id)) {
        echo "<script>alert('Material deleted successfully'); window.location='allevent.php';</script>";
    } else {
        echo "<script>alert('Error deleting material'); window.location='allevent.php';</script>";
    }
    exit;
}

// Bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $ids = isset($_POST['selected_ids']) && is_array($_POST['selected_ids'])
        ? array_values(array_unique(array_filter(array_map('intval', $_POST['selected_ids']))))
        : [];

    if ($ids === []) {
        echo "<script>alert('Please select at least one material'); history.back();</script>";
        exit;
    }

    $deleted = 0;
    foreach ($ids as $id) {
        if ($id > 0 && delete_material_by_id($con, $id)) {
            $deleted++;
        }
    }

    $qs = http_build_query(array_filter([
        'class' => $_GET['class'] ?? '',
        'category' => $_GET['category'] ?? '',
        'session' => $_GET['session'] ?? '',
        'title' => $_GET['title'] ?? '',
        'type' => $_GET['type'] ?? '',
        'page' => $_GET['page'] ?? '',
    ], static function ($v) {
        return $v !== '' && $v !== null;
    }));
    $redirect = 'allevent.php' . ($qs !== '' ? ('?' . $qs) : '');
    echo "<script>alert('Deleted {$deleted} material(s)'); window.location=" . json_encode($redirect) . ";</script>";
    exit;
}

// Filter values
$filterClass = trim((string) ($_GET['class'] ?? ''));
$filterCategory = trim((string) ($_GET['category'] ?? ''));
$filterSession = trim((string) ($_GET['session'] ?? ''));
$filterTitle = trim((string) ($_GET['title'] ?? ''));
$filterType = trim((string) ($_GET['type'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;

// Dropdown options
$classOptions = [];
$resClass = $con->query("SELECT DISTINCT class FROM student_materials WHERE class != '' ORDER BY class ASC");
if ($resClass) {
    while ($r = $resClass->fetch_assoc()) {
        $classOptions[] = $r['class'];
    }
}

$categoryOptions = [];
$resCat = $con->query("SELECT DISTINCT material_category FROM student_materials WHERE material_category != '' ORDER BY material_category ASC");
if ($resCat) {
    while ($r = $resCat->fetch_assoc()) {
        $categoryOptions[] = $r['material_category'];
    }
}

$sessionOptions = [];
$resSes = $con->query("SELECT DISTINCT session FROM student_materials WHERE session != '' ORDER BY session DESC");
if ($resSes) {
    while ($r = $resSes->fetch_assoc()) {
        $sessionOptions[] = $r['session'];
    }
}

$where = ['1=1'];
$params = [];
$types = '';

if ($filterClass !== '') {
    $where[] = 'class = ?';
    $params[] = $filterClass;
    $types .= 's';
}
if ($filterCategory !== '') {
    $where[] = 'material_category = ?';
    $params[] = $filterCategory;
    $types .= 's';
}
if ($filterSession !== '') {
    $where[] = 'session = ?';
    $params[] = $filterSession;
    $types .= 's';
}
if ($filterTitle !== '') {
    $where[] = 'material_title LIKE ?';
    $params[] = '%' . $filterTitle . '%';
    $types .= 's';
}
if ($filterType !== '') {
    $where[] = 'material_type = ?';
    $params[] = $filterType;
    $types .= 's';
}

$whereSql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) AS total FROM student_materials WHERE {$whereSql}";
$countStmt = $con->prepare($countSql);
$totalRows = 0;
if ($countStmt) {
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $totalRows = (int) ($countRes->fetch_assoc()['total'] ?? 0);
    $countStmt->close();
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$listSql = "SELECT * FROM student_materials WHERE {$whereSql} ORDER BY id DESC LIMIT ? OFFSET ?";
$listStmt = $con->prepare($listSql);
$materials = [];
if ($listStmt) {
    $listTypes = $types . 'ii';
    $listParams = array_merge($params, [$perPage, $offset]);
    $listStmt->bind_param($listTypes, ...$listParams);
    $listStmt->execute();
    $listRes = $listStmt->get_result();
    while ($row = $listRes->fetch_assoc()) {
        $materials[] = $row;
    }
    $listStmt->close();
}

function filter_query(array $overrides = []): string
{
    $q = [
        'class' => $_GET['class'] ?? '',
        'category' => $_GET['category'] ?? '',
        'session' => $_GET['session'] ?? '',
        'title' => $_GET['title'] ?? '',
        'type' => $_GET['type'] ?? '',
        'page' => $_GET['page'] ?? 1,
    ];
    foreach ($overrides as $k => $v) {
        $q[$k] = $v;
    }
    return 'allevent.php?' . http_build_query(array_filter($q, static function ($v) {
        return $v !== '' && $v !== null;
    }));
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Manage Study Materials</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1" name="viewport">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/font-awesome.min.css">
  <link rel="stylesheet" href="css/AdminLTE.min.css">
  <link rel="stylesheet" href="css/skins/_all-skins.min.css">
  <style>
    .filters-box .form-group { margin-bottom: 10px; }
    .materials-actions { margin: 12px 0; }
    .select-col { width: 36px; text-align: center; }
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
      <h1>Manage Study Materials</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
        <li class="active">Study Materials</li>
      </ol>
    </section>

    <section class="content">
      <div class="box box-primary filters-box">
        <div class="box-header with-border">
          <h3 class="box-title">Filters</h3>
        </div>
        <div class="box-body">
          <form method="get" class="form-horizontal">
            <div class="row">
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label>Class</label>
                  <select name="class" class="form-control">
                    <option value="">All</option>
                    <?php foreach ($classOptions as $opt): ?>
                      <option value="<?= htmlspecialchars($opt) ?>" <?= $filterClass === $opt ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label>Category</label>
                  <select name="category" class="form-control">
                    <option value="">All</option>
                    <?php foreach ($categoryOptions as $opt): ?>
                      <option value="<?= htmlspecialchars($opt) ?>" <?= $filterCategory === $opt ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label>Session</label>
                  <select name="session" class="form-control">
                    <option value="">All</option>
                    <?php foreach ($sessionOptions as $opt): ?>
                      <option value="<?= htmlspecialchars($opt) ?>" <?= $filterSession === $opt ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label>Search Title</label>
                  <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($filterTitle) ?>" placeholder="Search by title">
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="form-group">
                  <label>Type</label>
                  <select name="type" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['pdf', 'doc', 'ppt', 'video', 'image', 'other'] as $typeOpt): ?>
                      <option value="<?= $typeOpt ?>" <?= $filterType === $typeOpt ? 'selected' : '' ?>>
                        <?= strtoupper($typeOpt) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply Filter</button>
            <a href="allevent.php" class="btn btn-default">Reset</a>
            <a href="addevent.php" class="btn btn-success pull-right"><i class="fa fa-plus"></i> Add Material</a>
          </form>
        </div>
      </div>

      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">All Uploaded Materials (<?= (int) $totalRows ?>)</h3>
        </div>
        <div class="box-body table-responsive">
          <form method="post" action="<?= htmlspecialchars(filter_query(['page' => $page])) ?>" id="bulkDeleteForm" onsubmit="return confirmBulkDelete();">
            <input type="hidden" name="bulk_delete" value="1">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <th class="select-col">
                    <input type="checkbox" id="selectAll" title="Select all on this page">
                  </th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Class</th>
                  <th>Session</th>
                  <th>Type</th>
                  <th>Upload Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($materials) === 0): ?>
                  <tr>
                    <td colspan="8" class="text-center">No materials found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($materials as $row): ?>
                    <tr>
                      <td class="select-col">
                        <input type="checkbox" class="row-check" name="selected_ids[]" value="<?= (int) $row['id'] ?>">
                      </td>
                      <td><?= htmlspecialchars($row['material_title']) ?></td>
                      <td><?= htmlspecialchars($row['material_category']) ?></td>
                      <td><?= htmlspecialchars($row['class']) ?></td>
                      <td><?= htmlspecialchars($row['session']) ?></td>
                      <td><?= strtoupper(htmlspecialchars($row['material_type'])) ?></td>
                      <td><?= date('d M Y', strtotime($row['upload_date'])) ?></td>
                      <td>
                        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-success btn-xs" title="View"><i class="fa fa-eye"></i></a>
                        <a href="edit_material.php?id=<?= (int) $row['id'] ?>" class="btn btn-warning btn-xs" title="Edit"><i class="fa fa-pencil"></i></a>
                        <a onclick="return confirm('Delete this material?');" href="?id=<?= (int) $row['id'] ?>" class="btn btn-danger btn-xs" title="Delete"><i class="fa fa-trash"></i></a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>

            <div class="materials-actions">
              <button type="submit" class="btn btn-danger" id="btnDeleteSelected">
                <i class="fa fa-trash"></i> Delete Selected
              </button>
            </div>
          </form>

          <?php if ($totalPages > 1): ?>
            <div class="text-center">
              <ul class="pagination">
                <?php if ($page > 1): ?>
                  <li><a href="<?= htmlspecialchars(filter_query(['page' => $page - 1])) ?>">&laquo;</a></li>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 3);
                $end = min($totalPages, $page + 3);
                for ($i = $start; $i <= $end; $i++):
                ?>
                  <li class="<?= $i === $page ? 'active' : '' ?>">
                    <a href="<?= htmlspecialchars(filter_query(['page' => $i])) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                  <li><a href="<?= htmlspecialchars(filter_query(['page' => $page + 1])) ?>">&raquo;</a></li>
                <?php endif; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>

</div>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script>
  function confirmBulkDelete() {
    var checked = $('.row-check:checked').length;
    if (checked === 0) {
      alert('Please select at least one material');
      return false;
    }
    return confirm('Delete ' + checked + ' selected material(s)? This cannot be undone.');
  }

  $(function () {
    $('#selectAll').on('change', function () {
      $('.row-check').prop('checked', this.checked);
    });
    $(document).on('change', '.row-check', function () {
      var all = $('.row-check').length;
      var checked = $('.row-check:checked').length;
      $('#selectAll').prop('checked', all > 0 && all === checked);
    });
  });
</script>
</body>
</html>
