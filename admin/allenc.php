<?php
session_start();
error_log('POST data: ' . print_r($_POST, true));
ini_set('display_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.log');

date_default_timezone_set("Asia/Kolkata");

if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

// Handle delete
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $con->prepare("DELETE FROM enquiries WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Enquiry deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting enquiry";
    }
    header('Location: allenc.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>All Enquiries</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  
  <!-- Styles -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="css/AdminLTE.min.css">
<link rel="stylesheet" href="css/skins/_all-skins.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  
  <style>
    .message-col {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .attachment-col {
        width: 100px;
    }
    .replied {
        background-color: #f0fff0;
    }
    .badge-replied {
        background-color: #5cb85c;
        color: white;
        padding: 3px 6px;
        border-radius: 4px;
        font-size: 11px;
    }
    .badge-pending {
        background-color: #f0ad4e;
        color: white;
        padding: 3px 6px;
        border-radius: 4px;
        font-size: 11px;
    }
    #loading {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        text-align: center;
        padding-top: 20%;
    }
    .spinner {
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .reply-message-col {
        max-width: 150px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .original-message {
        background-color: #f9f9f9;
        border-left: 4px solid #3c8dbc;
        padding: 10px;
        margin-bottom: 15px;
        font-style: italic;
    }
  </style>
</head>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <!-- Header and Sidebar -->
  <?php include 'header.php'; ?>
  <aside class="main-sidebar">
    <section class="sidebar">
      <?php include 'sidebar.php'; ?>
    </section>
  </aside>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content-header">
      <h1>All Enquiries</h1>
      <ol class="breadcrumb">
        <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
        <li class="active">Enquiries</li>
      </ol>
    </section>

    <section class="content">
      <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
          <h4><i class="icon fa fa-check"></i> Success!</h4>
          <?= $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
          <h4><i class="icon fa fa-ban"></i> Error!</h4>
          <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <div class="box">
        <div class="box-body table-responsive">
          <table id="enquiriesTable" class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Subject</th>
                <th class="message-col">Message</th>
                <th class="reply-message-col">Reply Message</th>
                <th class="attachment-col">Attachment</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT * FROM enquiries ORDER BY created_at DESC";
              $res = $con->query($sql);
              $sn = 1;
              while($row = $res->fetch_assoc()) {
                $hasReply = !empty($row['reply_message']);
              ?>
              <tr class="<?= $hasReply ? 'replied' : '' ?>" id="enquiry-<?= $row['id'] ?>">
                <td><?= $sn++; ?></td>
                <td><?= htmlspecialchars($row['student_id'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><a href="mailto:<?= htmlspecialchars($row['email']) ?>"><?= htmlspecialchars($row['email']) ?></a></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars(ucfirst($row['enquiry_type'])) ?></td>
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td class="message-col" title="<?= htmlspecialchars($row['message']) ?>">
                  <?= htmlspecialchars(mb_strimwidth($row['message'], 0, 50, '...')) ?>
                </td>
                <td class="reply-message-col" title="<?= htmlspecialchars($row['reply_message'] ?? '') ?>">
                  <?= !empty($row['reply_message']) ? htmlspecialchars(mb_strimwidth($row['reply_message'], 0, 50, '...')) : 'No reply yet' ?>
                </td>
                <td class="attachment-col text-center">
                  <?php if (!empty($row['attachment'])): ?>
                    <a href="../pages/<?= htmlspecialchars($row['attachment']) ?>" target="_blank" class="btn btn-xs btn-info" title="View Attachment">
                      <i class="fas fa-paperclip"></i>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">None</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($hasReply): ?>
                    <span class="badge-replied">Replied</span>
                    <br><small><?= !empty($row['replied_at']) ? date('d M Y', strtotime($row['replied_at'])) : '' ?></small>
                  <?php else: ?>
                    <span class="badge-pending">Pending</span>
                  <?php endif; ?>
                </td>
                <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                <td>
                  <div class="btn-group">
                    <button type="button" class="btn btn-xs btn-info view-enquiry" 
                            data-id="<?= $row['id'] ?>" 
                            data-name="<?= htmlspecialchars($row['name']) ?>"
                            data-email="<?= htmlspecialchars($row['email']) ?>"
                            data-phone="<?= htmlspecialchars($row['phone']) ?>"
                            data-student_id="<?= htmlspecialchars($row['student_id'] ?? 'N/A') ?>"
                            data-type="<?= htmlspecialchars(ucfirst($row['enquiry_type'])) ?>"
                            data-subject="<?= htmlspecialchars($row['subject']) ?>"
                            data-date="<?= date('d M Y H:i', strtotime($row['created_at'])) ?>"
                            data-message="<?= htmlspecialchars($row['message']) ?>"
                            data-reply_message="<?= htmlspecialchars($row['reply_message'] ?? '') ?>"
                            data-replied_date="<?= !empty($row['replied_at']) ? date('d M Y H:i', strtotime($row['replied_at'])) : '' ?>"
                            data-replied_by="<?= htmlspecialchars($row['replied_by'] ?? '') ?>"
                            data-toggle="modal" data-target="#viewEnquiryModal" title="View Details">
                      <i class="fas fa-eye"></i>
                    </button>
                    <!-- ALWAYS show reply button regardless of reply status -->
                    <button type="button" class="btn btn-xs btn-success reply-enquiry" 
                            data-id="<?= $row['id'] ?>" 
                            data-name="<?= htmlspecialchars($row['name']) ?>"
                            data-message="<?= htmlspecialchars($row['message']) ?>"
                            data-subject="<?= htmlspecialchars($row['subject']) ?>"
                            data-toggle="modal" data-target="#replyModal" title="Reply">
                      <i class="fas fa-reply"></i>
                    </button>
                    <a href="allenc.php?id=<?= $row['id'] ?>" 
                       onclick="return confirm('Are you sure you want to delete this enquiry?');"
                       class="btn btn-xs btn-danger" title="Delete">
                      <i class="fas fa-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>

  <!-- Loading indicator -->
  <div id="loading">
    <div class="spinner"></div>
    <p>Processing your request...</p>
  </div>

  <!-- Reply Modal -->
  <div class="modal fade" id="replyModal" tabindex="-1" role="dialog" aria-labelledby="replyModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="replyModalLabel">Reply to Enquiry</h4>
        </div>
      <form id="replyForm" method="POST" action="javascript:void(0);">
          <div class="modal-body">
            <input type="hidden" name="enquiry_id" id="reply_enquiry_id">
            
            <div class="form-group">
              <label for="reply_to">Reply To:</label>
              <input type="text" class="form-control" id="reply_to" readonly>
            </div>
            
            <div class="form-group">
              <label>Original Subject:</label>
              <input type="text" class="form-control" id="original_subject" readonly>
            </div>
            
            <div class="form-group">
              <label>Original Message:</label>
              <div class="original-message" id="original_message"></div>
            </div>
            
            <div class="form-group">
              <label for="reply_message">Your Reply:</label>
              <textarea class="form-control" name="reply_message" id="reply_message" rows="6" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" name="reply_submit" class="btn btn-primary">Send Reply</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- View Enquiry Modal -->
  <div class="modal fade" id="viewEnquiryModal" tabindex="-1" role="dialog" aria-labelledby="viewEnquiryModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="viewEnquiryModalLabel">Enquiry Details</h4>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <h4>Sender Information</h4>
              <p><strong>Name:</strong> <span id="view_name"></span></p>
              <p><strong>Email:</strong> <span id="view_email"></span></p>
              <p><strong>Phone:</strong> <span id="view_phone"></span></p>
              <p><strong>Student ID:</strong> <span id="view_student_id"></span></p>
            </div>
            <div class="col-md-6">
              <h4>Enquiry Details</h4>
              <p><strong>Type:</strong> <span id="view_type"></span></p>
              <p><strong>Subject:</strong> <span id="view_subject"></span></p>
              <p><strong>Date:</strong> <span id="view_date"></span></p>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <h4>Message</h4>
              <div class="well well-sm" id="view_message"></div>
            </div>
          </div>
          <div class="row" id="reply_section">
            <div class="col-md-12">
              <h4>Your Reply</h4>
              <div class="well well-sm" id="view_reply">No reply yet</div>
              <p><small>Replied on: <span id="view_replied_date"></span></small></p>
              <p><small>Replied by: <span id="view_replied_by"></span></small></p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap.min.js"></script>
<script src="js/adminlte.min.js"></script>
<script>
$(function () {
    // Initialize DataTables
    var table = $('#enquiriesTable').DataTable({
      responsive: true,
      autoWidth: false,
      order: [[11, 'desc']], // Changed to date column index
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search enquiries...",
        lengthMenu: "Show _MENU_",
        zeroRecords: "No enquiries found",
        info: "Showing _START_ to _END_ of _TOTAL_",
        infoEmpty: "No enquiries",
        infoFiltered: "(filtered from _MAX_ total)"
      }
    });
    
    // Store the current enquiry ID in a variable
    let currentEnquiryId = null;
    
    // Handle reply button click using event delegation
    $('#enquiriesTable').on('click', '.reply-enquiry', function() {
      var id = $(this).data('id');
      var name = $(this).data('name');
      var message = $(this).data('message');
      var subject = $(this).data('subject');
      
      console.log('Reply button clicked. ID:', id, 'Name:', name, 'Type:', typeof id);
      
      // Convert to integer and store
      currentEnquiryId = parseInt(id);
      $('#reply_enquiry_id').val(currentEnquiryId);
      $('#reply_to').val(name + ' (ID: ' + currentEnquiryId + ')');
      $('#original_subject').val(subject);
      $('#original_message').text(message);
      $('#reply_message').val('');
      
      // Debug: Show the hidden field value
      console.log('Hidden field value:', $('#reply_enquiry_id').val());
    });
    
    // Handle view button click using event delegation
    $('#enquiriesTable').on('click', '.view-enquiry', function() {
      var id = $(this).data('id');
      var name = $(this).data('name');
      var email = $(this).data('email');
      var phone = $(this).data('phone');
      var studentId = $(this).data('student_id');
      var type = $(this).data('type');
      var subject = $(this).data('subject');
      var date = $(this).data('date');
      var message = $(this).data('message');
      var replyMessage = $(this).data('reply_message');
      var repliedDate = $(this).data('replied_date');
      var repliedBy = $(this).data('replied_by');
      
      $('#view_name').text(name);
      $('#view_email').text(email);
      $('#view_phone').text(phone);
      $('#view_student_id').text(studentId);
      $('#view_type').text(type);
      $('#view_subject').text(subject);
      $('#view_date').text(date);
      $('#view_message').text(message);
      
      if (replyMessage) {
        $('#view_reply').text(replyMessage);
        $('#view_replied_date').text(repliedDate);
        $('#view_replied_by').text(repliedBy || 'Admin');
      } else {
        $('#view_reply').text('No reply yet');
        $('#view_replied_date').text('');
        $('#view_replied_by').text('');
      }
      
      $('#viewEnquiryModalLabel').text('Enquiry Details #' + id);
    });
    
    // Handle reply form submission with AJAX
    $('#replyForm').on('submit', function(e) {
      e.preventDefault();
      
      // Use the stored enquiry ID
      var enquiryId = currentEnquiryId || $('#reply_enquiry_id').val();
      var replyMessage = $('#reply_message').val();
      
      console.log('Form submitted with ID:', enquiryId, 'Type:', typeof enquiryId, 'Message:', replyMessage);
      
      // Convert to integer if it's a string
      enquiryId = parseInt(enquiryId);
      
      if (!enquiryId || enquiryId <= 0) {
        alert('Enquiry ID is missing or invalid. Please click the reply button again.');
        console.error('Invalid enquiry ID:', enquiryId);
        return;
      }
      
      if (!replyMessage.trim()) {
        alert('Reply message cannot be empty');
        return;
      }
      
      // Show loading indicator
      $('#loading').show();
      
      // Create FormData object to properly handle form submission
      var formData = new FormData();
      formData.append('enquiry_id', enquiryId);
      formData.append('reply_message', replyMessage);
      formData.append('reply_submit', '1');
      
      // Log FormData contents for debugging
      for (var pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
      }
      
      // Send AJAX request
      $.ajax({
        url: 'reply_enquiry.php',
        type: 'POST',
        data: formData,
        processData: false,  // Don't process the data
        contentType: false,  // Don't set contentType
        success: function(response) {
          $('#loading').hide();
          console.log('Server response:', response);
          
          try {
            var result = typeof response === 'string' ? JSON.parse(response) : response;
            
            if (result.success) {
              // Show success message
              alert(result.message);
              // Close modal
              $('#replyModal').modal('hide');
              // Reload page to see changes
              setTimeout(function() {
                location.reload();
              }, 500);
            } else {
              alert('Error: ' + result.message);
            }
          } catch (e) {
            console.error('JSON Parse Error:', e, 'Response:', response);
            alert('Invalid response from server. Check console for details.');
          }
        },
        error: function(xhr, status, error) {
          $('#loading').hide();
          console.error('AJAX Error:', status, error, xhr.responseText);
          alert('An error occurred: ' + error + '. Check console for details.');
        }
      });
    });
  });
</script>

</body>
</html>