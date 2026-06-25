<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $registration_code = mysqli_real_escape_string($con, $_POST['student_registration_code']);
    $amount = mysqli_real_escape_string($con, $_POST['amount']);
    $payment_reason = mysqli_real_escape_string($con, $_POST['payment_reason']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $date = date('Y-m-d');
    
    // Get student details
    $student_query = mysqli_query($con, "SELECT name, email, mobile_number FROM students WHERE registration_code = '$registration_code'");
    
    if (mysqli_num_rows($student_query) > 0) {
        $student = mysqli_fetch_assoc($student_query);
        
        $donor_name = mysqli_real_escape_string($con, $student['name']);
        $donor_email = mysqli_real_escape_string($con, $student['email']);
        $donor_phone = mysqli_real_escape_string($con, $student['mobile_number']);
        
        // Check if a fee record already exists for this student today
        $check_query = mysqli_query($con, "SELECT id, amount FROM donations 
                                          WHERE student_registration_code = '$registration_code' 
                                          AND donation_date = '$date' 
                                          AND transaction_type = 'fee'");
        
        if (mysqli_num_rows($check_query) > 0) {
            // Update existing record
            $existing_record = mysqli_fetch_assoc($check_query);
            $existing_id = $existing_record['id'];
            $existing_amount = $existing_record['amount'];
            
            // Calculate the difference to update the student's paid_fees correctly
            $amount_difference = $amount - $existing_amount;
            
            $update_sql = "UPDATE donations SET 
                          donor_name = '$donor_name',
                          donor_email = '$donor_email', 
                          donor_phone = '$donor_phone',
                          amount = '$amount',
                          payment_reason = '$payment_reason',
                          status = '$status'
                          WHERE id = '$existing_id'";
            
            if (mysqli_query($con, $update_sql)) {
                // Update student's paid fees with the difference
                $update_student_sql = "UPDATE students SET paid_fees = paid_fees + $amount_difference 
                                      WHERE registration_code = '$registration_code'";
                mysqli_query($con, $update_student_sql);
                
                $_SESSION['success_msg'] = "Existing fee record updated successfully!";
            } else {
                $_SESSION['error_msg'] = "Error updating record: " . mysqli_error($con);
            }
        } else {
            // Insert new fee record
            $sql = "INSERT INTO donations (donor_name, donor_email, donor_phone, amount, payment_reason, status, donation_date, student_registration_code, transaction_type, payment_mode) 
                    VALUES ('$donor_name', '$donor_email', '$donor_phone', '$amount', '$payment_reason', '$status', '$date', '$registration_code', 'fee', 'Cash')";
            
            if (mysqli_query($con, $sql)) {
                // Update student's paid fees
                $update_sql = "UPDATE students SET paid_fees = paid_fees + $amount WHERE registration_code = '$registration_code'";
                mysqli_query($con, $update_sql);
                
                $_SESSION['success_msg'] = "Fee record added successfully!";
            } else {
                $_SESSION['error_msg'] = "Error: " . mysqli_error($con);
            }
        }
    } else {
        $_SESSION['error_msg'] = "Student not found!";
    }
    
    // Redirect back to the fee management page
    $_SESSION['active_fee_tab'] = 'addfees';
    header('location:viewtransactions.php?tab=addfees');
    exit();
} else {
    // If someone tries to access this page directly
    header('location:viewtransactions.php');
    exit();
}
?>