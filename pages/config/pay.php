<?php
// Start session with secure configuration
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Verify session is active
if (session_status() !== PHP_SESSION_ACTIVE) {
    die("Session initialization failed");
}

include('../config.php'); // Adjust path as needed

// PayU credentials
$MERCHANT_KEY = "vpBPbM";
$SALT = "p878CRzEgXnrRwKx0xS9Wgq7nhVdJ2fd";
$PAYU_BASE_URL = "https://secure.payu.in"; // Production URL

// Initialize variables
$action = '';
$formError = 0;
$txnid = '';
$hash = '';
$posted = array();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate required fields
    if (empty($_POST['firstname']) || empty($_POST['email']) || empty($_POST['phone']) || empty($_POST['amount']) || empty($_POST['course_id'])) {
        $formError = 1;
        die("Required fields are missing");
    }

    // Collect and sanitize input
    $firstname = htmlspecialchars(trim($_POST['firstname']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $amount = floatval($_POST['amount']);
    $course_id = (int)$_POST['course_id'];
    $payment_reason = isset($_POST['payment_reason']) ? htmlspecialchars(trim($_POST['payment_reason'])) : 'Course Payment';

    // Generate random transaction id
    $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
    
    // Prepare payment data
    $payment_data = [
        'firstname' => $firstname,
        'email' => $email,
        'phone' => $phone,
        'amount' => $amount,
        'course_id' => $course_id,
        'payment_reason' => $payment_reason,
        'txnid' => $txnid,
        'user_id' => $_SESSION['user_id'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Store transaction details
    try {
        // 1. Store in session
        $_SESSION['payment_data'] = $payment_data;
        
        // 2. Store in donations table
        $campaign_id = NULL; // Set to NULL or appropriate value
        $image = 'event/Sample_User_Icon.png';
        $donation_date = date('Y-m-d');
        $created_at = date('Y-m-d H:i:s');
        $pan = NULL; // Set to NULL or collect from form if needed
        
        $sql = "INSERT INTO `donations` 
                (`campaign_id`, `image`, `donor_name`, `donor_email`, `amount`, 
                 `donation_date`, `created_at`, `donor_phone`, `donor_pan`, 
                 `payu_transaction_id`, `payment_reason`, `status`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $con->error);
        }

        $status = 'pending'; // Initial status
        
        if (!$stmt->bind_param("ssssdsssssss", 
            $campaign_id, $image, $firstname, $email, $amount,
            $donation_date, $created_at, $phone, $pan,
            $txnid, $payment_reason, $status
        )) {
            throw new Exception("Bind failed: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $donation_id = $stmt->insert_id;
        $stmt->close();

    } catch (Exception $e) {
        error_log("Payment record creation failed: " . $e->getMessage());
        // Continue with payment processing even if DB insert fails
    }

    // Prepare PayU payment parameters
    $success_url = 'https://sstechnoweb.com/sunrise/preview/pages/config/success.php?txnid=' . $txnid;
    $failure_url = 'https://sstechnoweb.com/sunrise/preview/pages/config/failure.php?txnid=' . $txnid;
    
    $posted = [
        'key' => $MERCHANT_KEY,
        'txnid' => $txnid,
        'amount' => $amount,
        'firstname' => $firstname,
        'email' => $email,
        'phone' => $phone,
        'productinfo' => $payment_reason,
        'surl' => $success_url,
        'furl' => $failure_url,
        'service_provider' => 'payu_paisa'
    ];
    
    // Generate hash for PayU
    $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
    $hashVarsSeq = explode('|', $hashSequence);
    $hash_string = '';	
    
    foreach($hashVarsSeq as $hash_var) {
        $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
        $hash_string .= '|';
    }
    
    $hash_string .= $SALT;
    $hash = strtolower(hash('sha512', $hash_string));
    $action = $PAYU_BASE_URL . '/_payment';
} else {
    // Not a POST request, redirect back
    header('Location: ../payment.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Processing</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background-color: #f8f9fa;
        }
        .processing-spinner {
            width: 3rem;
            height: 3rem;
        }
    </style>
    <script>
        var hash = '<?php echo $hash ?>';
        function submitPayuForm() {
            if(hash == '') {
                document.getElementById('error-message').style.display = 'block';
                return;
            }
            document.forms.payuForm.submit();
        }
    </script>
</head>
<body onLoad="submitPayuForm()">
    <div class="container">
        <div class="payment-container text-center">
            <?php if($formError) { ?>
                <div class="alert alert-danger" id="error-message">
                    <h4>Payment Error</h4>
                    <p>There was an error processing your payment. Please check your details and try again.</p>
                    <a href="../payment.php?id=<?php echo $course_id; ?>" class="btn btn-warning">Go Back</a>
                </div>
            <?php } else { ?>
                <div class="mb-4">
                    <div class="spinner-border processing-spinner text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h3 class="mt-3">Processing Payment</h3>
                    <p>You will be redirected to the secure payment gateway shortly...</p>
                    <p class="small text-muted">Transaction ID: <?php echo $txnid; ?></p>
                </div>
                
                <form action="<?php echo $action; ?>" method="post" name="payuForm">
                    <input type="hidden" name="key" value="<?php echo $MERCHANT_KEY ?>" />
                    <input type="hidden" name="hash" value="<?php echo $hash ?>"/>
                    <input type="hidden" name="txnid" value="<?php echo $txnid ?>" />
                    <input type="hidden" name="amount" value="<?php echo $amount ?>" />
                    <input type="hidden" name="firstname" value="<?php echo $firstname ?>" />
                    <input type="hidden" name="email" value="<?php echo $email ?>" />
                    <input type="hidden" name="phone" value="<?php echo $phone ?>" />
                    <input type="hidden" name="productinfo" value="<?php echo $payment_reason ?>" />
                    <input type="hidden" name="surl" value="<?php echo $success_url ?>" />
                    <input type="hidden" name="furl" value="<?php echo $failure_url ?>" />
                    <input type="hidden" name="service_provider" value="payu_paisa" />
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="manual-submit">
                            Click here if not redirected automatically
                        </button>
                    </div>
                </form>
            <?php } ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>