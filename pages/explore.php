<?php
session_start();
include('config.php');
include('header.php');

if (!isset($_SESSION['username'])) {
    header('Location: ./auth/signin.php');
    exit();
}

$username = $_SESSION['username'];

// Get student info with prepared statement
$stmt = $con->prepare("SELECT * FROM students WHERE name = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$student_status = $student['status'];
if (!$student) {
    die("Student not found");
}
if ($student_status === "suspended") {
    die("<h1 style='color:red;' >Your Account is Suspended ! Please Contact To The Authority To Access Study Materials ..</h1>");
    header("location:home.php");
}

$name = htmlspecialchars($student['name'] ?? 'Guest');
$sid = htmlspecialchars($student['registration_code'] ?? '');
$img = htmlspecialchars($student['image'] ?? 'user.png');
$id = (int)$student['id'];
$student_class = $student['class'] ?? '';
$student_session = $student['session'] ?? '';

// Debug information
error_log("Student: $username, Class: $student_class, Session: $student_session");

// Get student's materials with prepared statement
// Materials specifically assigned to this student OR public materials OR 
// class materials where any of the student's classes matches the material's class
$query = "SELECT * FROM student_materials 
          WHERE (student_id = ?) 
          OR (access_level = 'public') 
          OR (access_level = 'class' AND session = ? AND (
            -- Check if any of the student's classes matches the material class
            FIND_IN_SET(class, REPLACE(?, ', ', ',')) > 0 OR
            -- Check if material class contains any of the student's classes (for comma-separated material classes)
            FIND_IN_SET(?, REPLACE(class, ', ', ',')) > 0
          ))";

$stmt = $con->prepare($query);
// Bind parameters: student_id, session, student_class, first class from student
$first_class = trim(explode(',', $student_class)[0]);
$stmt->bind_param("isss", $id, $student_session, $student_class, $first_class);
$stmt->execute();
$result = $stmt->get_result();
$materials = $result->fetch_all(MYSQLI_ASSOC);

// Debug: Check what materials were found
error_log("Found " . count($materials) . " materials for student");

// Alternative approach if the above doesn't work - get all class materials and filter in PHP
if (empty($materials)) {
    $query = "SELECT * FROM student_materials 
              WHERE (student_id = ?) 
              OR (access_level = 'public') 
              OR (access_level = 'class' AND session = ?)";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("is", $id, $student_session);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_materials = $result->fetch_all(MYSQLI_ASSOC);
    
    // Filter materials based on class matching
    $materials = array_filter($all_materials, function($material) use ($student_class) {
        if ($material['access_level'] !== 'class') {
            return true;
        }
        
        $student_classes = array_map('trim', explode(',', $student_class));
        $material_classes = array_map('trim', explode(',', $material['class']));
        
        // Check if any student class matches any material class
        return count(array_intersect($student_classes, $material_classes)) > 0;
    });
    
    error_log("PHP filtering found " . count($materials) . " materials");
}

// Get company info
$company = $con->query("SELECT * FROM company LIMIT 1")->fetch_assoc();

// Helper function
function getThumbnailForType($type, $file_path = '') {
    $type = strtolower($type);
    
    // If it's an image type, use the actual image as thumbnail
    if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'image']) && file_exists($file_path)) {
        return $file_path;
    }
    
    switch ($type) {
        case 'pdf':
            return '../assets/images/pdf-thumbnail.jpg';
        case 'doc':
        case 'docx':
            return '../assets/images/word-thumbnail.jpg';
        case 'ppt':
        case 'pptx':
            return '../assets/images/ppt-thumbnail.jpg';
        case 'video':
            return '../assets/images/video-thumbnail.jpg';
        default:
            return '../assets/images/default-thumbnail.jpg';
    }
}

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($company['name'] ?? 'Study Materials') ?></title>
  <link rel="shortcut icon" href="../admin/<?= htmlspecialchars($company['logo'] ?? '') ?>" type="image/x-icon">
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/common.css" />
  <style>
    .material-card {
      transition: transform 0.3s ease;
      margin-bottom: 20px;
      position: relative;
      border: 1px solid #eee;
      border-radius: 8px;
      overflow: hidden;
    }
    .material-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .material-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(0,0,0,0.7);
      color: white;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 12px;
      text-transform: uppercase;
    }
    .favorite-icon {
      position: absolute;
      top: 10px;
      left: 10px;
      z-index: 2;
      cursor: pointer;
      background: rgba(255,255,255,0.8);
      border-radius: 50%;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .material-image {
      height: 180px;
      width: 100%;
      object-fit: cover;
    }
    .material-details {
      padding: 15px;
    }
    .material-title {
      font-size: 1.1rem;
      margin-bottom: 8px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .material-meta {
      display: flex;
      justify-content: space-between;
      font-size: 0.8rem;
      color: #666;
    }
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: #666;
    }
    .empty-state img {
      max-width: 200px;
      margin-bottom: 20px;
    }
    .nav-pills .nav-link {
      white-space: nowrap;
      margin-right: 10px;
      border-radius: 20px;
    }
    .nav-pills {
      flex-wrap: nowrap;
      overflow-x: auto;
      padding-bottom: 10px;
    }
    .nav-pills::-webkit-scrollbar {
      display: none;
    }

    @media (max-width: 767.98px) {
      .material-image {
        height: 150px;
      }
      .material-title {
        font-size: 1rem;
      }
    }

    @media (max-width: 575.98px) {
      .material-image {
        height: 120px;
      }
      .col-6 {
        padding-left: 5px;
        padding-right: 5px;
      }
    }
  </style>
</head>
<body class="scrollbar-hidden">
  <!-- Splash Screen -->
  <section id="preloader" class="spalsh-screen">
    <div class="circle text-center">
      <div>
        <h1><?= htmlspecialchars($company['name'] ?? 'StudyHub') ?></h1>
        <p>Your Learning Resources</p>
      </div>
    </div>
    <div class="loader-spinner">
      <?php for ($i = 0; $i < 12; $i++): ?>
        <div></div>
      <?php endfor; ?>
    </div>
  </section>

  <main class="explore">
    <!-- Materials -->
    <section class="all-place py-4 container">
      <!-- Filters -->
      <ul class="nav nav-pills mb-4">
        <li class="nav-item">
          <button class="nav-link active" data-filter="all">All Materials</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-filter=".recent">Recent</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-filter=".favorite">Favorites</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-filter=".pdf">PDFs</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-filter=".video">Videos</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-filter=".image">Images</button>
        </li>
      </ul>

      <!-- Cards Grid -->
      <div id="material-cards" class="row">
        <?php if (empty($materials)): ?>
          <div class="col-12">
            <div class="empty-state">
              <img src="../assets/images/empty-folder.svg" alt="No materials">
              <h4>No Study Materials Found</h4>
              <p>You don't have any study materials yet. Check back later or contact your instructor.</p>
              <p><small>Debug: Student Class: <?= htmlspecialchars($student_class) ?>, Session: <?= htmlspecialchars($student_session) ?></small></p>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($materials as $material): 
            $is_favorite = $material['is_favorite'] ? 'favorite' : '';
            $file_type = strtolower($material['material_type']);
            $upload_date = date('M d, Y', strtotime($material['upload_date']));
            $thumbnail = getThumbnailForType($file_type, '../admin/' . $material['file_path']);
            $is_recent = (time() - strtotime($material['upload_date'])) < (7 * 24 * 60 * 60) ? 'recent' : '';
          ?>
          <div class="col-6 col-md-4 col-lg-3 mb-4 mix <?= $is_favorite ?> <?= $file_type ?> <?= $is_recent ?>">
            <div class="material-card h-100">
              <a href="material_details.php?id=<?= $material['id'] ?>" class="text-decoration-none text-dark d-block h-100">
                <div class="position-relative">
                  <img src="<?= $thumbnail ?>" class="material-image" alt="<?= htmlspecialchars($material['material_title']) ?>">
                  <span class="material-badge"><?= $file_type ?></span>
                  <?php if ($material['is_favorite']): ?>
                  <span class="favorite-icon" data-material-id="<?= $material['id'] ?>">
                    <img src="../assets/svg/heart-red.svg" width="16" alt="favorite" />
                  </span>
                  <?php else: ?>
                  <span class="favorite-icon" data-material-id="<?= $material['id'] ?>">
                    <img src="../assets/svg/heart-black.svg" width="16" alt="favorite" />
                  </span>
                  <?php endif; ?>
                </div>
                <div class="material-details">
                  <h5 class="material-title"><?= htmlspecialchars($material['material_title']) ?></h5>
                  <div class="material-meta">
                    <span><?= $upload_date ?></span>
                    <span><?= htmlspecialchars($material['subject']) ?></span>
                  </div>
                </div>
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <!-- Bottom Nav -->
  <?php include('footer.php') ?>

  <!-- Scripts -->
  <script src="../assets/js/jquery-3.6.1.min.js"></script>
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/mixitup.min.js"></script>
  <script>
  $(document).ready(function() {
  // Initialize MixItUp
  var mixer = mixitup('#material-cards', {
    selectors: { target: '.mix' },
    animation: { duration: 300 },
    load: { filter: 'all' }
  });

  // Toggle favorite - Fixed version
  $(document).on('click', '.favorite-icon', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const $icon = $(this).find('img');
    const $card = $(this).closest('.mix');
    const materialId = $(this).data('material-id');
    const isFavorite = $icon.attr('src').includes('red');
    
    // Store original state for potential rollback
    const originalSrc = $icon.attr('src');
    const originalFavoriteState = $card.hasClass('favorite');
    
    // Optimistic UI update
    $icon.attr('src', isFavorite ? '../assets/svg/heart-black.svg' : '../assets/svg/heart-red.svg');
    $card.toggleClass('favorite', !isFavorite);
    
    // Send AJAX request
    $.post('toggle_favorite.php', {
      id: materialId,
      is_favorite: isFavorite ? 0 : 1
    }).done(function(response) {
      // Optional: Handle success response if needed
      console.log('Favorite status updated successfully');
    }).fail(function(xhr, status, error) {
      // Revert UI on failure
      $icon.attr('src', originalSrc);
      $card.toggleClass('favorite', originalFavoriteState);
      
      // Show error message
      console.error('Failed to update favorite status:', error);
      alert('Failed to update favorite status. Please try again.');
    });
  });

  // Hide preloader when page loads
  $(window).on('load', function() {
    $('#preloader').fadeOut('slow');
  });

  // Add click handler for filter buttons
  $('.nav-pills .nav-link').click(function() {
    $('.nav-pills .nav-link').removeClass('active');
    $(this).addClass('active');
  });
});
  </script>
</body>
</html>