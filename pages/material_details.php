<?php
session_start();
require 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: seematerial.php");
    exit();
}

$material_id = intval($_GET['id']);

$query = "SELECT * FROM student_materials WHERE id = '$material_id'";
$result = mysqli_query($con, $query);
$material = mysqli_fetch_assoc($result);

if (!$material) {
    header("Location: seematerial.php");
    exit();
}

$subject = $material['subject'];
$related_query = "SELECT * FROM student_materials WHERE subject = '$subject' AND id != '$material_id' LIMIT 4";
$related_result = mysqli_query($con, $related_query);
$related_materials = mysqli_fetch_all($related_result, MYSQLI_ASSOC);

function material_video_embed_url($file_path, $material_type) {
    if (strtolower((string) $material_type) !== 'video' || empty($file_path)) {
        return null;
    }
    if (!filter_var($file_path, FILTER_VALIDATE_URL)) {
        return null;
    }
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)(?:/([A-Za-z0-9]+))?~i', $file_path, $matches)) {
        $embed = 'https://player.vimeo.com/video/' . $matches[1];
        $query = parse_url($file_path, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            if (!empty($params['h'])) {
                $embed .= '?h=' . $params['h'];
            }
        } elseif (!empty($matches[2])) {
            $embed .= '?h=' . $matches[2];
        }
        return $embed . (str_contains($embed, '?') ? '&' : '?') . 'title=0&byline=0&portrait=0';
    }
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]+)~i', $file_path, $matches)) {
        return 'https://www.youtube-nocookie.com/embed/' . $matches[1] . '?rel=0&modestbranding=1&playsinline=1';
    }
    if (preg_match('~player\.vimeo\.com~i', $file_path) || preg_match('~youtube\.com/embed~i', $file_path)) {
        return $file_path;
    }
    return null;
}

$video_embed = material_video_embed_url($material['file_path'], $material['material_type']);
$is_local_file = !empty($material['file_path']) && !filter_var($material['file_path'], FILTER_VALIDATE_URL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($material['material_title']); ?> - StudyHub</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    .material-header { background: #f8f9fa; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; }
    .material-thumbnail { max-width: 100%; height: auto; border-radius: 8px; }
    .file-icon { font-size: 5rem; color: #6c757d; }
    .related-material-card { transition: transform 0.3s; margin-bottom: 20px; }
    .related-material-card:hover { transform: translateY(-5px); }
    .badge-subject { background-color: #6f42c1; }
    .badge-type { background-color: #20c997; }
    iframe {
      width: 100%;
      height: 500px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    li{
        list-style:none;
    }
  </style>
</head>
<body oncontextmenu="return false" onselectstart="return false" ondragstart="return false">

<div class="container py-5">
  <div class="material-header">
    <div class="row">
      <div class="col-md-8">
        <h1><?php echo htmlspecialchars($material['material_title']); ?></h1>
        <div class="d-flex gap-2 mb-2 flex-wrap">
          <span class="badge badge-subject text-white"><?php echo htmlspecialchars($material['subject']); ?></span>
          <span class="badge badge-type text-white"><?php echo strtoupper($material['material_type']); ?></span>
          <?php if ($material['is_favorite']): ?>
            <span class="badge bg-warning text-dark">Featured</span>
          <?php endif; ?>
        </div>
        <p class="text-muted mb-1">
          Uploaded by Admin/Principal .
          <?php if (!empty($material['student_email'])): ?>
            (<?php echo htmlspecialchars($material['student_email']); ?>)
          <?php endif; ?>
          on <?php echo date('F j, Y', strtotime($material['upload_date'])); ?>
        </p>
      </div>
      <div class="col-md-4 text-center">
        <?php if (in_array($material['material_type'], ['jpg', 'jpeg', 'png', 'gif'])): ?>
          <img src="../admin/<?php echo htmlspecialchars($material['file_path']); ?>" class="material-thumbnail" alt="Material Image">
        <?php else: ?>
          <i class="file-icon fas fa-file-<?php echo get_file_icon($material['material_type']); ?>"></i>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="mb-4">
    <?php if ($video_embed): ?>
      <iframe src="<?php echo htmlspecialchars($video_embed); ?>" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
      <p class="text-danger mt-2"><strong>Note:</strong> This video plays in-app only. Sharing is disabled.</p>
    <?php elseif ($material['permission'] === 'yes' && $is_local_file): ?>
      <a href="../admin/<?php echo htmlspecialchars($material['file_path']); ?>" class="btn btn-success" download>
        <i class="fas fa-download me-1"></i> Download
      </a>
      <a href="../admin/<?php echo htmlspecialchars($material['file_path']); ?>" class="btn btn-primary" target="_blank">
        <i class="fas fa-eye me-1"></i> View
      </a>
    <?php elseif ($is_local_file): ?>
      <iframe src="../admin/<?php echo htmlspecialchars($material['file_path']); ?>#toolbar=0" allowfullscreen sandbox></iframe>
      <p class="text-danger mt-2"><strong>Note:</strong> Download is disabled for this material.</p>
    <?php else: ?>
      <p class="text-muted">Preview is not available for this material.</p>
    <?php endif; ?>
  </div>

  <div class="card mb-4">
    <div class="card-header bg-white"><h4 class="mb-0">Description</h4></div>
    <div class="card-body">
      <?php echo nl2br(htmlspecialchars($material['material_description'])); ?>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header bg-white"><h4 class="mb-0">File Info</h4></div>
    <div class="card-body">
      <ul class="list-group">
        <li class="list-group-item d-flex justify-content-between">
          <span><i class="fas fa-file me-2"></i> Type</span>
          <span class="badge bg-primary"><?php echo strtoupper($material['material_type']); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i class="fas fa-user me-2"></i> Uploaded By</span>
          <span>Admin / Principal.</span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i class="fas fa-calendar-alt me-2"></i> Upload Date</span>
          <span><?php echo date('F j, Y, g:i a', strtotime($material['upload_date'])); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between">
          <span><i class="fas fa-lock me-2"></i> Permission</span>
          <span class="text-<?php echo $material['permission'] === 'yes' ? 'success' : 'danger'; ?>">
            <?php echo ucfirst($material['permission']); ?>
          </span>
        </li>
      </ul>
    </div>
  </div>

  <?php if (!empty($related_materials)): ?>
  <!--<div class="card">-->
  <!--  <div class="card-header bg-white"><h4 class="mb-0">Related Materials</h4></div>-->
  <!--  <div class="card-body">-->
  <!--    <div class="row">-->
  <!--      <?php foreach ($related_materials as $rel): ?>-->
  <!--      <div class="col-md-3">-->
  <!--        <a href="material_details.php?id=<?php echo $rel['id']; ?>" class="text-decoration-none">-->
  <!--          <div class="card related-material-card h-100 text-center p-3">-->
  <!--            <i class="fas fa-file-<?php echo get_file_icon($rel['material_type']); ?> fa-2x mb-2 text-muted"></i>-->
  <!--            <h6 class="text-dark"><?php echo htmlspecialchars($rel['material_title']); ?></h6>-->
  <!--            <small class="text-muted"><?php echo htmlspecialchars($rel['subject']); ?></small>-->
  <!--          </div>-->
  <!--        </a>-->
  <!--      </div>-->
  <!--      <?php endforeach; ?>-->
  <!--    </div>-->
  <!--  </div>-->
  <!--</div>-->
  <?php endif; ?>
</div>

<?php include('footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function get_file_icon($type) {
  switch ($type) {
    case 'pdf': return 'pdf';
    case 'doc':
    case 'docx': return 'word';
    case 'ppt':
    case 'pptx': return 'powerpoint';
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'gif': return 'image';
    case 'mp4':
    case 'mov':
    case 'avi':
    case 'video': return 'video';
    case 'xls':
    case 'xlsx': return 'excel';
    case 'zip':
    case 'rar': return 'archive';
    default: return 'alt';
  }
}
?>
