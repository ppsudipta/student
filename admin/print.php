<?php
date_default_timezone_set('Asia/Kolkata');
// Database connection
include('config.php');

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Get filter parameters
$class_filter = isset($_GET['class_filter']) ? $_GET['class_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

// Build SQL query with filters
$sql = "SELECT * FROM students WHERE 1=1";
$params = array();
$types = "";

if (!empty($class_filter)) {
    $sql .= " AND class LIKE ?";
    $params[] = "%" . $class_filter . "%";
    $types .= "s";
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Sort by registration code in ascending order
$sql .= " ORDER BY registration_code ASC";

// Prepare and execute query
$stmt = $con->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get unique classes for filter dropdown
$class_query = "SELECT class FROM students order by registration_code";
$class_result = $con->query($class_query);
$all_classes = array();

if ($class_result->num_rows > 0) {
    while($row = $class_result->fetch_assoc()) {
        $classes = explode(',', $row['class']);
        foreach ($classes as $class) {
            $class = trim($class);
            if (!empty($class) && !in_array($class, $all_classes)) {
                $all_classes[] = $class;
            }
        }
    }
}
sort($all_classes);

// Export to Excel functionality
if (isset($_POST['export_excel'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=student_records_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Add CSV headers with all columns
    fputcsv($output, array(
        'SL No.', 'Roll No.', 'Name', 'Class', 'Session', 
        'Total Fees', 'Mobile', 'Email', 'Address',
        'Date Of Joining', 'Father Name', 'School Name', 'Status'
    ));
    
    // Add data rows
    if ($result->num_rows > 0) {
        // Reset the result pointer
        $result->data_seek(0);
        $sl = 0;
        while ($row = $result->fetch_assoc()) {
            $sl++;
            fputcsv($output, array(
                $sl,
                $row['registration_code'],
                $row['name'],
                $row['class'],
                $row['session'],
                $row['total_fees'],
                $row['mobile_number'],
                $row['email'],
                $row['address'],
                $row['date'],
                $row['father_name'],
                $row['school_name'],
                $row['status']
                
            ));
        }
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records - Print</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }
        .print-btn, .excel-btn {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .excel-btn {
            background-color: #2196F3;
        }
        .print-btn:hover {
            background-color: #45a049;
        }
        .excel-btn:hover {
            background-color: #0b7dda;
        }
        .filters {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            margin-bottom: 5px;
            font-weight: bold;
        }
        .filter-group select, .filter-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filter-btn {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .filter-btn:hover {
            background-color: #45a049;
        }
        .reset-btn {
            padding: 8px 15px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            
        }
        a{
            text-decoration:none;
        }
        .reset-btn:hover {
            background-color: #d32f2f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            position: sticky;
            top: 0;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-ongoing {
            color: green;
            font-weight: bold;
        }
        .status-suspended {
            color: orange;
            font-weight: bold;
        }
        .status-completed {
            color: blue;
            font-weight: bold;
        }
        .status-promoted {
            color: purple;
            font-weight: bold;
        }
        @media print {
            .action-buttons, .filters {
                display: none;
            }
            th {
                background-color: #f2f2f2 !important;
                -webkit-print-color-adjust: exact;
            }
            .no-print {
                display: none;
            }
            table {
                font-size: 9px;
            }
            th, td {
                padding: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Records</h1>
        <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="action-buttons">
        <a href="allregister.php" class="print-btn">Back To All Registration</a>
        <form method="post" style="display: inline;">
            <button type="submit" name="export_excel" class="excel-btn">Export to Excel</button>
        </form>
        <button class="print-btn" onclick="window.print()">Print Records</button>
    </div>

    <!-- Filters Section -->
    <div class="filters">
        <form method="GET" action="">
            <div class="filter-group">
                <label for="class_filter">Filter by Class:</label>
                <select name="class_filter" id="class_filter">
                    <option value="">All Classes</option>
                    <?php foreach ($all_classes as $class): ?>
                        <option value="<?php echo $class; ?>" <?php echo ($class_filter == $class) ? 'selected' : ''; ?>>
                            <?php echo $class; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="status_filter">Filter by Status:</label>
                <select name="status_filter" id="status_filter">
                    <option value="">All Status</option>
                    <option value="ongoing" <?php echo ($status_filter == 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="suspended" <?php echo ($status_filter == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                   
                </select>
            </div>
            
            <div class="filter-group">
                <button type="submit" class="filter-btn">Apply Filters</button>
            </div>
            
            <div class="filter-group">
                <a href="?" class="reset-btn">Reset</a>
            </div>
        </form>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Roll No</th>
                    <th>Name</th>
                    <th>Class</th>
                   
                    <th>Session</th>
                    <th>Total Fees</th>
                   
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Date</th>
                    <th>Father Name</th>
                    <th>School Name</th>
                  
                    <th>Status</th>
                   
                </tr>
            </thead>
            <tbody>
                <?php
                $sl = 0;
                while($row = $result->fetch_assoc()):
                $sl++;
                ?>
                <tr>
                    <td><?php echo $sl; ?></td>
                    <td><?php echo htmlspecialchars($row['registration_code']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['class']); ?></td>
                  
                    <td><?php echo htmlspecialchars($row['session']); ?></td>
                    <td>₹<?php echo number_format($row['total_fees'], 2); ?></td>
                   
                    <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <td><?php echo htmlspecialchars($row['father_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['school_name']); ?></td>
                  
                    <td class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></td>
               
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No student records found.</p>
    <?php endif; ?>

    <?php 
    $stmt->close();
    $con->close(); 
    ?>
</body>
</html>