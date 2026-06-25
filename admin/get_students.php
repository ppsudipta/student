<?php
include('config.php');

if (!empty($_GET['class'])) {
    $class = mysqli_real_escape_string($con, $_GET['class']);
    $result = mysqli_query($con, "SELECT registration_code, name, email, total_fees 
                                  FROM students 
                                  WHERE class LIKE '%$class%' 
                                  ORDER BY registration_code ASC");

    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }

    echo json_encode($students);
}
?>
