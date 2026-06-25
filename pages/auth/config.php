<?php
	$db_host = "localhost";
	$db_user = "root";
	$db_pass = "";
	$db_name = "a1773756_app";
	
	$con =  mysqli_connect($db_host,$db_user,$db_pass,$db_name);
	if(mysqli_connect_error()){
		echo 'connect to database failed';
	}
?>