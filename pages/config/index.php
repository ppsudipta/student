<?php
session_start();
include('config.php');

// Check if payment data exists in session
if (!isset($_SESSION['payment_data'])) {
    header("Location: index.php");
    exit();
}
$original_amount = $payment_data['amount'];
$discounted_amount = $original_amount * 0.9; // Deduct 10%
// Retrieve payment data from session
$payment_data = $_SESSION['payment_data'];
$firstname = $payment_data['firstname'];
$email = $payment_data['email'];
$phone = $payment_data['phone'];
$pan = $payment_data['pan'];
$amount = $discounted_amount;
$payment_reason = $payment_data['payment_reason'];
$txnid = $payment_data['txnid'];

// Insert donation into database after successful payment
$campaign_id = ''; 
$image = 'event/Sample_User_Icon.png';
$donation_date = date('Y-m-d');
$created_at = date('Y-m-d H:i:s');

$sql = "INSERT INTO `donations` 
        (`campaign_id`, `image`, `donor_name`, `donor_email`, `amount`, 
         `donation_date`, `created_at`, `donor_phone`,`donor_pan`, `razorpay_order_id`, `payment_reason`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $con->prepare($sql);
$stmt->bind_param("ssssdssssss", 
    $campaign_id, $image, $firstname, $email, $amount, 
    $donation_date, $created_at, $phone,$pan, $txnid, $payment_reason
);

if ($stmt->execute()) {
    $donation_id = $stmt->insert_id;
    // Clear payment data from session
    unset($_SESSION['payment_data']);
} else {
    // Handle database error
    $error = $stmt->error;
    // You might want to log this error
}
$stmt->close();

// Display success message
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <div class="alert alert-success text-center" style="margin-top: 50px;">
            <h2>Payment Successful!</h2>
            <p>Thank you for your payment of ₹<?php echo $amount; ?>.</p>
            <p>Transaction ID: <?php echo $txnid; ?></p>
            <a href="../index.php" class="btn btn-primary">Return Home</a>
        </div>
    </div>
</body>
</html>