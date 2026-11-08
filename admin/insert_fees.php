<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('location:index.php');
    exit();
}
include('config.php');
include_once __DIR__ . '/fees_helper.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $registration_code = mysqli_real_escape_string($con, $_POST['student_registration_code']);
    $amount = mysqli_real_escape_string($con, $_POST['amount']);
    $status = mysqli_real_escape_string($con, $_POST['status']);

    $fee_month = trim((string) ($_POST['fee_month'] ?? $_POST['payment_reason'] ?? ''));
    $fee_year = trim((string) ($_POST['fee_year'] ?? ''));
    $months = fees_month_names();

    if (!in_array($fee_month, $months, true)) {
        $_SESSION['error_msg'] = 'Please select a valid fee month.';
        $_SESSION['active_fee_tab'] = 'addfees';
        header('location:viewtransactions.php?tab=addfees');
        exit();
    }
    if ($fee_year === '' || !preg_match('/^\d{4}$/', $fee_year)) {
        $fee_year = (string) date('Y');
    }

    $payment_reason = mysqli_real_escape_string($con, fees_format_period($fee_month, $fee_year));

    $dateRaw = trim((string) ($_POST['donation_date'] ?? ''));
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRaw) ? $dateRaw : date('Y-m-d');
    $date = mysqli_real_escape_string($con, $date);

    // Get student details
    $student_query = mysqli_query($con, "SELECT name, email, mobile_number FROM students WHERE registration_code = '$registration_code'");

    if (mysqli_num_rows($student_query) > 0) {
        $student = mysqli_fetch_assoc($student_query);

        $donor_name = mysqli_real_escape_string($con, $student['name']);
        $donor_email = mysqli_real_escape_string($con, $student['email']);
        $donor_phone = mysqli_real_escape_string($con, $student['mobile_number']);

        // Check if a fee record already exists for this student + fee period
        $check_query = mysqli_query($con, "SELECT id, amount FROM donations
                                          WHERE student_registration_code = '$registration_code'
                                          AND payment_reason = '$payment_reason'
                                          AND transaction_type = 'fee'");

        if (mysqli_num_rows($check_query) > 0) {
            // Update existing record for same month/year
            $existing_record = mysqli_fetch_assoc($check_query);
            $existing_id = $existing_record['id'];
            $existing_amount = $existing_record['amount'];

            $amount_difference = $amount - $existing_amount;

            $update_sql = "UPDATE donations SET
                          donor_name = '$donor_name',
                          donor_email = '$donor_email',
                          donor_phone = '$donor_phone',
                          amount = '$amount',
                          payment_reason = '$payment_reason',
                          status = '$status',
                          donation_date = '$date'
                          WHERE id = '$existing_id'";

            if (mysqli_query($con, $update_sql)) {
                $update_student_sql = "UPDATE students SET paid_fees = paid_fees + $amount_difference
                                      WHERE registration_code = '$registration_code'";
                mysqli_query($con, $update_student_sql);

                $_SESSION['success_msg'] = "Existing fee record for $payment_reason updated successfully!";
            } else {
                $_SESSION['error_msg'] = "Error updating record: " . mysqli_error($con);
            }
        } else {
            $sql = "INSERT INTO donations (donor_name, donor_email, donor_phone, amount, payment_reason, status, donation_date, student_registration_code, transaction_type, payment_mode)
                    VALUES ('$donor_name', '$donor_email', '$donor_phone', '$amount', '$payment_reason', '$status', '$date', '$registration_code', 'fee', 'Cash')";

            if (mysqli_query($con, $sql)) {
                $update_sql = "UPDATE students SET paid_fees = paid_fees + $amount WHERE registration_code = '$registration_code'";
                mysqli_query($con, $update_sql);

                $_SESSION['success_msg'] = "Fee record added successfully for $payment_reason!";
            } else {
                $_SESSION['error_msg'] = "Error: " . mysqli_error($con);
            }
        }
    } else {
        $_SESSION['error_msg'] = "Student not found!";
    }

    $_SESSION['active_fee_tab'] = 'addfees';
    header('location:viewtransactions.php?tab=addfees');
    exit();
}

header('location:viewtransactions.php');
exit();
?>
