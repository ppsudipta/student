<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $students = $_POST['students'];
    $payment_reason = mysqli_real_escape_string($con, $_POST['payment_reason']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $date = date('Y-m-d');
    
    foreach ($students as $registration_code) {
        // Get student details including total_fees
        $student_query = mysqli_query($con, "SELECT name, email, mobile_number, total_fees FROM students WHERE registration_code = '$registration_code'");
        $student = mysqli_fetch_assoc($student_query);
        
        $donor_name = mysqli_real_escape_string($con, $student['name']);
        $donor_email = mysqli_real_escape_string($con, $student['email']);
        $donor_phone = mysqli_real_escape_string($con, $student['mobile_number']);
        $amount = $student['total_fees']; // Use the student's total_fees as amount
        
        // Insert fee record
        $sql = "INSERT INTO donations (donor_name, donor_email, donor_phone, amount, payment_reason, status, donation_date, student_registration_code, transaction_type, payment_mode) 
                VALUES ('$donor_name', '$donor_email', '$donor_phone', '$amount', '$payment_reason', '$status', '$date', '$registration_code', 'fee', 'Cash')";
        
        mysqli_query($con, $sql);
        
        // Update student's paid fees
        $update_sql = "UPDATE students SET paid_fees = paid_fees + $amount WHERE registration_code = '$registration_code'";
        mysqli_query($con, $update_sql);
    }
    
    // At the end of your insert files, add:
    $_SESSION['active_fee_tab'] = 'addfees';
    header('location:viewtransactions.php?tab=addfees');
    exit();
}
?>