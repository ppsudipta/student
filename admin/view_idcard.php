<?php
include('config.php');

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM students WHERE id = $id";
    $result = $con->query($sql);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>ID Card - <?= htmlspecialchars($student['name']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        @media print {
            .print-btn {
                display: none;
            }
            .id-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background-color: transparent !important;
            }
        }

        .id-card {
            width: 600px;
            height: 900px;
            background: url('sunrise_id.jpeg') no-repeat center center;
            background-size: cover;
            position: relative;
            margin: 20px auto;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.3);
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .id-photo {
            position: absolute;
            top: 185px;
            left: 180px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #000;
            background: #fff;
        }

        .id-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-info {
            position: absolute;
            top: 500px;
            left: 30px;
            right: 30px;
            font-size: 16px;
            line-height: 1.6;
        }

        .student-info b {
            width: 130px;
            display: inline-block;
        }

        .print-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 25px;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="id-card" id="idCard">
    <div class="id-photo">
        <?php if (!empty($student['image']) && file_exists($student['image'])): ?>
            <img src="<?= htmlspecialchars($student['image']) ?>" alt="Student Photo">
        <?php else: ?>
            <div style="text-align:center; padding-top:100px;">No Photo</div>
        <?php endif; ?>
    </div>

    <div class="student-info">
        <div style="
    position: relative;
    top: 41px;
    left: 158px;
    font-size: 37px;
"><?= htmlspecialchars($student['name']) ?></div>
        
        <div style="
    position: relative;
    top: 185px;
    left: 178px;
    font-size: 20px;
"> <?= htmlspecialchars($student['registration_code']) ?></div>
        <div style="
    position: relative;
    top: 108px;
    left: 178px;
    font-size: 20px;
"><?= htmlspecialchars($student['class']) ?></div>
        <div style="
    position: relative;
    top: 209px;
    left: 178px;
    font-size: 20px;
"> <?= htmlspecialchars($student['mobile_number']) ?></div>
        <div style="
    position: relative;
    top: 139px;
    left: 178px;
    font-size: 20px;
"> <?= htmlspecialchars($student['session']) ?></div>
    </div>
</div>

<button onclick="window.print()" class="print-btn">Print ID Card</button>

</body>
</html>
<?php
    } else {
        echo "<h3>No student found!</h3>";
    }
} else {
    echo "<h3>No student ID provided!</h3>";
}
$con->close();
?>
