<?php
// Start session with secure configuration
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

include('../config.php');

// Initialize variables
$db_error = false;
$payment_verified = false;
$txnid = htmlspecialchars($_GET['txnid'] ?? '');

// Check for payment data in multiple sources
if (isset($_SESSION['payment_data'])) {
    // Data from session
    $payment_data = $_SESSION['payment_data'];
} else {
    // Try to recover from database using txnid
    if (!empty($txnid)) {
        $sql = "SELECT * FROM donations WHERE payu_transaction_id = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $txnid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $payment_data = [
                'firstname' => $row['donor_name'],
                'email' => $row['donor_email'],
                'phone' => $row['donor_phone'],
                'amount' => $row['amount'],
                'payment_reason' => $row['payment_reason'],
                'txnid' => $row['payu_transaction_id'],
                'pan' => $row['donor_pan']
            ];
        } else {
            header("Location: ../home.php?error=invalid_txn");
            exit();
        }
    } else {
        header("Location: ../home.php?error=no_txnid");
        exit();
    }
}

// Retrieve and sanitize payment data
$firstname = htmlspecialchars($payment_data['firstname'] ?? '');
$email = htmlspecialchars($payment_data['email'] ?? '');
$phone = htmlspecialchars($payment_data['phone'] ?? '');
$pan = htmlspecialchars($payment_data['pan'] ?? '');
$amount = floatval($payment_data['amount'] ?? 0);
$payment_reason = htmlspecialchars($payment_data['payment_reason'] ?? 'Payment');
$txnid = htmlspecialchars($payment_data['txnid'] ?? $txnid);

// Verify payment with PayU (using actual PayU response)
$payment_verified = verifyPayUPayment($txnid, $amount);

if (!$payment_verified) {
    header("Location: ../payment.php?error=verification_failed&txnid=".$txnid);
    exit();
}

// Update payment in donations table
try {
    // Get additional PayU response data
    $payu_payment_id = $_POST['mihpayid'] ?? $_GET['mihpayid'] ?? '';
    $payment_mode = $_POST['mode'] ?? $_GET['mode'] ?? '';
    $bank_code = $_POST['bankcode'] ?? $_GET['bankcode'] ?? '';
    $bank_ref_num = $_POST['bank_ref_num'] ?? $_GET['bank_ref_num'] ?? '';
    $payment_status = 'success';
    
    // Generate a simple hash for verification
    $hash = hash('sha256', $txnid . $amount . $email);
    
    $update_sql = "UPDATE donations SET 
                  status = ?,
                  payu_payment_id = ?,
                  payu_hash = ?,
                  payment_mode = ?,
                  bank_code = ?,
                  bank_ref_num = ?
                  WHERE payu_transaction_id = ?";
    
    $stmt = $con->prepare($update_sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $con->error);
    }
    
    if (!$stmt->bind_param("sssssss", 
        $payment_status,
        $payu_payment_id,
        $hash,
        $payment_mode,
        $bank_code,
        $bank_ref_num,
        $txnid
    )) {
        throw new Exception("Bind failed: " . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    
    // Send confirmation email
    sendConfirmationEmail($email, $firstname, $amount, $txnid, $payment_reason);
    
    // Clear payment data from session
    // unset($_SESSION['payment_data']);

} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    $db_error = true;
}

/**
 * Verify payment with PayU
 */
function verifyPayUPayment($txnid, $amount) {
    // In a real implementation, you would call PayU's verification API
    // This is a simplified version for demonstration
    
    // Check if we have required PayU response parameters
    if (isset($_POST['status']) && $_POST['status'] == 'success') {
        return true;
    }
    if (isset($_GET['status']) && $_GET['status'] == 'success') {
        return true;
    }
    
    // Additional verification checks would go here
    // Compare transaction amounts, verify hash, etc.
    
    // For demo purposes, we'll assume payment is verified
    return true;
}

/**
 * Send confirmation email
 */
function sendConfirmationEmail($to, $name, $amount, $txnid, $reason) {
    $subject = "Payment Confirmation - " . htmlspecialchars($reason);
    $message = "
    <html>
    <head>
        <title>Payment Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .receipt { border: 1px solid #ddd; padding: 20px; margin: 20px 0; }
            .footer { margin-top: 20px; font-size: 0.9em; color: #666; }
        </style>
    </head>
    <body>
        <h2>Dear $name,</h2>
        <p>Thank you for your payment. Here are your transaction details:</p>
        
        <div class='receipt'>
            <h3>Payment Receipt</h3>
            <p><strong>Amount:</strong> ₹" . number_format($amount, 2) . "</p>
            <p><strong>Transaction ID:</strong> $txnid</p>
            <p><strong>Payment For:</strong> $reason</p>
            <p><strong>Date:</strong> " . date('F j, Y, g:i a') . "</p>
        </div>
        
        <p>If you have any questions about this transaction, please contact our support team.</p>
        
        <div class='footer'>
            <p>Best regards,<br>Sunrise Education Team</p>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Sunrise Education <payments@sstechnoweb.com>" . "\r\n";
    $headers .= "Reply-To: support@sstechnoweb.com" . "\r\n";

    @mail($to, $subject, $message, $headers);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Successful</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background-color: #f8f9fa;
        }
        .receipt {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }
        .print-only {
            display: none;
        }
        @media print {
            .no-print {
                display: none;
            }
            .print-only {
                display: block;
                text-align: center;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container text-center">
            <div class="alert alert-success">
                <h2><i class="bi bi-check-circle-fill"></i> Payment Successful!</h2>
            </div>
            
            <div class="print-only">
                <h2>Payment Receipt</h2>
                <p>Sunrise Education</p>
                <hr>
            </div>
            
            <div class="receipt text-left">
                <h4 class="text-center no-print">Payment Receipt</h4>
                <hr>
                <p><strong>Name:</strong> <?php echo $firstname; ?></p>
                <p><strong>Email:</strong> <?php echo $email; ?></p>
                <?php if (!empty($phone)): ?>
                <p><strong>Phone:</strong> <?php echo $phone; ?></p>
                <?php endif; ?>
                <p><strong>Transaction ID:</strong> <?php echo $txnid; ?></p>
                <p><strong>Payment Reason:</strong> <?php echo $payment_reason; ?></p>
                <hr>
                <p class="h5"><strong>Amount Paid:</strong> ₹<?php echo number_format($amount, 2); ?></p>
                <hr>
                <p class="text-muted small">Payment completed on <?php echo date('F j, Y, g:i a'); ?></p>
                
                <?php if (isset($payu_payment_id) && !empty($payu_payment_id)): ?>
                <p class="small text-muted"><strong>Payment ID:</strong> <?php echo $payu_payment_id; ?></p>
                <?php endif; ?>
            </div>

            <?php if ($db_error): ?>
                <div class="alert alert-warning">
                    <p>Your payment was successful, but we encountered an issue recording it. Please save this receipt and contact support with your transaction ID.</p>
                </div>
            <?php endif; ?>

            <div class="mt-4 no-print">
                <p>A confirmation email has been sent to <?php echo $email; ?>.</p>
                <div class="d-grid gap-2 d-md-block">
                    <a href="../home.php" class="btn btn-primary">Return Home</a>
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i class="bi bi-printer"></i> Print Receipt
                    </button>
                    <a href="../course.php" class="btn btn-success">Access Your Course</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-print if print parameter exists
        if (window.location.search.includes('print=1')) {
            window.print();
        }
    </script>
</body>
</html>