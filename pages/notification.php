<?php
session_start();
include('config.php');
include('header.php');

if (!isset($_SESSION['username'])) {
    header('Location: ./auth/signin.php');
    exit();
}

$use = $_SESSION['username'];

// Get student info
$stmt = $con->prepare("SELECT * FROM students WHERE name = ?");
$stmt->bind_param("s", $use);
$stmt->execute();
$res = $stmt->get_result();
$row2 = $res->fetch_assoc();

$name = $row2['name'] ?? 'Guest';
$sid = $row2['registration_code'] ?? '';
$img = $row2['image'] ?? 'user.png';
$id = $row2['id'];

// Get company info
$sql = "SELECT * FROM company";
$res = $con->query($sql);
$rowm = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($rowm['name']) ?></title>
  <link rel="shortcut icon" href="../admin/<?= htmlspecialchars($rowm['logo'] ?? '') ?>" type="image/x-icon">
  
  <!-- bootstrap -->
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  
  <!-- swiper -->
  <link rel="stylesheet" href="../assets/css/swiper-bundle.min.css">
  
  <!-- datepicker -->
  <link rel="stylesheet" href="../assets/css/jquery.datetimepicker.css">
  
  <!-- jquery ui -->
  <link rel="stylesheet" href="../assets/css/jquery-ui.min.css">
  
  <!-- common -->
  <link rel="stylesheet" href="../assets/css/common.css">
  
  <!-- animations -->
  <link rel="stylesheet" href="../assets/css/animations.css">
  
  <!-- welcome -->
  <link rel="stylesheet" href="../assets/css/welcome.css">
  
  <!-- datetimepicker -->
  <link rel="stylesheet" href="../assets/css/datetimepicker.css">
  
  <!-- notification -->
  <link rel="stylesheet" href="../assets/css/notification.css">
</head>
<body class="scrollbar-hidden">
  <!-- splash-screen start -->
  <section id="preloader" class="spalsh-screen">
    <div class="circle text-center">
      <div>
        <h1><?= htmlspecialchars($rowm['name'] ?? 'Notices') ?></h1>
        <p>Your Notifications</p>
      </div>
    </div>
    <div class="loader-spinner">
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
      <div></div>
    </div>
  </section>
  <!-- splash-screen end -->

  <main>
    <!-- page-title -->
    <div class="page-title">
      <button type="button" class="back-btn back-page-btn d-flex align-items-center justify-content-center rounded-full">
        <img src="../assets/svg/arrow-left-black.svg" alt="arrow">
      </button>
      <h3 class="main-title">My Notices</h3>
    </div>

    <!-- notification start -->
    <section class="notification">
      <?php
      // Get notices grouped by date
      $stmt = $con->prepare("SELECT 
                              notice_type, 
                              notice_content, 
                              DATE(created_at) as notice_date,
                              created_at 
                            FROM notices 
                            WHERE student_id = ? 
                            ORDER BY created_at DESC");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      $noticesByDate = [];
      while ($row = $result->fetch_assoc()) {
          $date = $row['notice_date'];
          $noticesByDate[$date][] = $row;
      }
      
      if (empty($noticesByDate)): ?>
        <div class="text-center py-5">
          <img src="../assets/svg/empty-notification.svg" alt="No notices" class="img-fluid mb-3" style="max-width: 200px;">
          <p class="text-muted">No notices available </p>
        </div>
      <?php else: 
          $today = date('Y-m-d');
          $yesterday = date('Y-m-d', strtotime('-1 day'));
          
          foreach ($noticesByDate as $date => $notices): 
              $displayDate = ($date == $today) ? 'Today' : 
                            (($date == $yesterday) ? 'Yesterday' : 
                            date('F j, Y', strtotime($date)));
      ?>
        <div class="mb-4">
          <h3 class="mb-32"><?= $displayDate ?></h3>
          <ul>
            <?php foreach ($notices as $notice): 
                $timeAgo = time_elapsed_string($notice['created_at']);
            ?>
              <li class="d-flex gap-12">
                <div class="image d-flex align-items-center justify-content-center rounded-full overflow-hidden shrink-0">
                  <?php if ($notice['notice_type'] === 'image'): ?>
                  <a href="../admin/<?= htmlspecialchars($notice['notice_content']) ?>" >
                    <img src="../admin/<?= htmlspecialchars($notice['notice_content']) ?>" 
                         alt="Notice" class="img-fluid h-100 w-100 object-fit-cover">
                         </a>
                  <?php elseif ($notice['notice_type'] === 'video'): ?>
                  
                  
                    <!--<img src="../assets/svg/video-icon-blue.svg" alt="Video notice">-->
                    <a href="../admin/<?= htmlspecialchars($notice['notice_content']) ?>" >
                   Click To View The Video Notice
</a>
                  <?php else: ?>
                    <img src="../assets/svg/notification-blue.svg" alt="Text notice">
                  <?php endif; ?>
                </div>
                <div>
                  <?php if ($notice['notice_type'] === 'text'): ?>
                    <p class="pb-8"><?= nl2br(htmlspecialchars($notice['notice_content'])) ?></p>
                  <?php elseif ($notice['notice_type'] === 'image'): ?>
                    <p class="pb-8">You received an image notice</p>
                  <?php elseif ($notice['notice_type'] === 'video'): ?>
                    <p class="pb-8">You received a video notice</p>
                  <?php endif; ?>
                  <small class="d-block"><?= $timeAgo ?></small>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; 
      endif; ?>
    </section>
    <!-- notification end -->
  </main>

  <!-- jquery -->
  <script src="../assets/js/jquery-3.6.1.min.js"></script>
  
  <!-- bootstrap -->
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  
  <!-- jquery ui -->
  <script src="../assets/js/jquery-ui.js"></script>

  <!-- mixitup -->
  <script src="../assets/js/mixitup.min.js"></script>

  <!-- gasp -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/gsap.min.js"></script>

  <!-- draggable -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/Draggable.min.js"></script>
  
  <!-- swiper -->
  <script src="../assets/js/swiper-bundle.min.js"></script>
  
  <!-- datepicker -->
  <script src="../assets/js/jquery.datetimepicker.full.js"></script>
  
  <!-- script -->
  <script src="../assets/js/script.js"></script>
  
  <script>
    $(document).ready(function() {
      // Back button functionality
      $('.back-page-btn').on('click', function() {
        window.history.back();
      });
      
      // Preloader animation
      setTimeout(function() {
        $('#preloader').fadeOut('slow');
      }, 1500);
    });
  </script>
</body>
</html>

<?php
// Helper function to display time ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
   
}
 include('footer.php');
?>