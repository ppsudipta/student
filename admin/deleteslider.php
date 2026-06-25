<?php
include('config.php');
$id=$_GET['id'];

	if($con->error)
	echo $con->error;
	else
	{
		$sql="DELETE from slider where id=$id";
		$con->query($sql);
		echo "<script>alert('slider deleted')</script>";	
		echo "<script>window.location.href='allsliderimg.php'</script>";	
	}

?>
