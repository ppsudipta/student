<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('header.php');
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'];

// Get student info
$stmt = $con->prepare("SELECT name, mobile_number, email, registration_code FROM students WHERE name = ?");
if (!$stmt) {
    die("Database error: " . $con->error);
}
$stmt->bind_param("s", $username);
if (!$stmt->execute()) {
    die("Execution failed: " . $stmt->error);
}
$res = $stmt->get_result();
$student = $res->fetch_assoc();

if (!$student) {
    die("Student not found");
}

// Get all payment records for this student
$registration_code = $student['registration_code'];
$payment_stmt = $con->prepare("SELECT * FROM donations WHERE student_registration_code = ? ORDER BY donation_date DESC");
if (!$payment_stmt) {
    die("Database error: " . $con->error);
}
$payment_stmt->bind_param("s", $registration_code);
if (!$payment_stmt->execute()) {
    die("Execution failed: " . $payment_stmt->error);
}
$payment_result = $payment_stmt->get_result();
$all_payments = [];
while ($row = $payment_result->fetch_assoc()) {
    $all_payments[] = $row;
}

// Get unique months for filter
$months = [];
foreach ($all_payments as $payment) {
    if (!in_array($payment['payment_reason'], $months)) {
        $months[] = $payment['payment_reason'];
    }
}
sort($months);

// Filter payments by month if selected
$selected_month = isset($_GET['month']) ? $_GET['month'] : '';
$filtered_payments = $all_payments;

if (!empty($selected_month)) {
    $filtered_payments = array_filter($all_payments, function($payment) use ($selected_month) {
        return $payment['payment_reason'] === $selected_month;
    });
}

// Calculate totals
$total_amount = 0;
foreach ($filtered_payments as $payment) {
    $total_amount += $payment['amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fee-card {
            max-width: 1000px;
            margin: 30px auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .fee-header {
            border-radius: 10px 10px 0 0 !important;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .total-row {
            font-weight: bold;
            
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .status-success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card fee-card">
            <div class="card-header fee-header bg-primary text-white">
                <h4 class="mb-0 text-center"><i class="fas fa-receipt me-2"></i>Fee Payment Records</h4>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Student Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Name:</strong> <?= htmlspecialchars($student['name']) ?></p>
                                <p><strong>Registration Code:</strong> <?= htmlspecialchars($student['registration_code']) ?></p>
                                <p><strong>Mobile:</strong> <?= htmlspecialchars($student['mobile_number']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Payment Summary</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Total Records:</strong> <?= count($filtered_payments) ?></p>
                                <p><strong>Total Amount:</strong> ₹<?= number_format($total_amount, 2) ?></p>
                                <p><strong>Filtered By:</strong> <?= empty($selected_month) ? 'All Months' : $selected_month ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="filter-section">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="month" class="form-label">Filter by Month</label>
                            <select class="form-select" id="month" name="month">
                                <option value="">All Months</option>
                                <?php foreach ($months as $month): ?>
                                    <option value="<?= $month ?>" <?= $selected_month === $month ? 'selected' : '' ?>>
                                        <?= $month ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Apply Filter</button>
                            <a href="payment.php" class="btn btn-secondary ms-2"><i class="fas fa-times me-1"></i> Clear</a>
                        </div>
                    </form>
                </div>

                <?php if (count($filtered_payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Month</th>
                                <th>Amount</th>
                               
                              
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_payments as $payment): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($payment['donation_date'])) ?></td>
                                <td><?= htmlspecialchars($payment['payment_reason']) ?></td>
                                <td>₹<?= number_format($payment['amount'], 2) ?></td>
                              
                             
                                <td>
                                    <span class="status-badge status-success">
                                        <i class="fas fa-check-circle me-1"></i> <?= ucfirst($payment['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="2" class="text-end">Total:</td>
                                <td>₹<?= number_format($total_amount, 2) ?></td>
                                <td colspan="3"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php if (empty($selected_month)): ?>
                        No payment records found for this student.
                    <?php else: ?>
                        No payment records found for the month of <?= $selected_month ?>.
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
       <a href="dey-education-qr.jpeg" download ><img style="height:250px;" src="dey-education-qr.jpeg" ></a> 
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include('footer.php'); ?>
</body>
</html>