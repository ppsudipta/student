<?php
include('config.php');

if (isset($_POST['code'])) {
    $code = mysqli_real_escape_string($con, $_POST['code']);
    
    $query = "SELECT id FROM students WHERE registration_code = '$code'";
    $result = mysqli_query($con, $query);
    
    if (mysqli_num_rows($result) > 0) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
}
?>