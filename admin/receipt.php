<?php
session_start();
include('config.php');

if (!isset($_GET['id'])) {
    die("<h3>No receipt ID provided!</h3>");
}

$id = intval($_GET['id']);
$stmt = $con->prepare("SELECT * FROM donations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h3>No donation found!</h3>");
}
$data = $result->fetch_assoc();
$receipt_no = 'R-' . str_pad($data['id'], 4, '0', STR_PAD_LEFT);
$filename = "Receipt_" . $receipt_no . "_" . date('Ymd');
$donation_date = date('F d, Y', strtotime($data['created_at']));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt <?php echo $receipt_no; ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .container {
            max-width: 800px;
            background: #fff;
            padding: 20px;
            margin: 20px auto;
            border: 1px solid #ccc;
        }
        .logo {
            width: 120px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="container" id="invoice-content">
    <div class="text-center">
        <!--<img src="SUNRISE ACADEMY.jpg" alt="Logo" class="logo mb-2">-->
        <h4 class="mb-0">SUNRISE ACADEMY</h4>
        <small>Kanktia Panitras Bagnan Howrah -711303</small><br>
        <small>Phone: 9609535629 / 9609535629 | Email: sunriseacademy.sradmy@gmail.com</small>
        <hr>
        <h5 class="text-primary"><strong> FEE RECEIPT</strong></h5>
    </div>

    <table class="table table-borderless">
        <tr>
            <th>Receipt No:</th>
            <td><?php echo $receipt_no; ?></td>
            <th>Date:</th>
            <td><?php echo $donation_date; ?></td>
        </tr>
        <tr>
            <th>Received From:</th>
            <td colspan="3"><?php echo htmlspecialchars($data['donor_name']); ?></td>
        </tr>
        <tr>
            <th>Purpose:</th>
            <td colspan="3"><?php echo htmlspecialchars($data['purpose']); ?></td>
        </tr>
        <tr>
            <th>Amount:</th>
            <td><strong>₹<?php echo number_format($data['amount'], 2); ?></strong></td>
            <th>Payment Mode:</th>
            <td><?php echo htmlspecialchars(ucfirst($data['payment_mode'])); ?></td>
        </tr>
        <?php if (!empty($data['transaction_id'])): ?>
        <tr>
            <th>Transaction ID:</th>
            <td colspan="3"><?php echo htmlspecialchars($data['transaction_id']); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <p class="mt-4">Authorized Signature: ______________________</p>
</div>

<div class="text-center no-print mt-3">
    <button class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
    <button class="btn btn-success" onclick="downloadPDF()"><i class="fa fa-download"></i> Download PDF</button>
</div>

<script>
function downloadPDF() {
    const element = document.getElementById('invoice-content');
    html2pdf().set({
        margin: 10,
        filename: '<?php echo $filename; ?>.pdf',
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(element).save();
}
</script>

</body>
</html>
