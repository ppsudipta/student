<?php
include('config.php');

if (isset($_POST['class']) && isset($_POST['session'])) {
    $class = mysqli_real_escape_string($con, $_POST['class']);
    $session = mysqli_real_escape_string($con, $_POST['session']);
    
    $query = mysqli_query($con, "SELECT subject FROM class_session WHERE class = '$class' AND session = '$session' LIMIT 1");
    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        // Format the subjects with commas and line breaks for better display
        $subjects = str_replace(', ', ',<br>', htmlspecialchars($row['subject']));
        echo $subjects;
    } else {
        echo "No subjects found for this class and session combination.";
    }
}
?>