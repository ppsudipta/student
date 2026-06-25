<?php
include('config.php');


$code=$_POST['code'];
$msg=$_POST['msg'];
//$month=$_POST['month'];
$date=date('d-m-Y');
  

 if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from admission where code_no='$code'";
	$res=$con->query($sql);
	$row=$res->fetch_array();
	
		$s_name=$row['student_name'];
		$student_code=$row['code_no'];
		$course=$row['course'];
		$phone_number=$row['phone'];
    
	
	
	 $sql2="insert into fees_reminder(id,student_name,course,student_code,msg,date) values('','$s_name','$course','$student_code','$msg','$date')";
	 $con->query($sql2); 
	
 
 //echo exit;
 $mobileNumber = "$phone_number";
$senderId = "AKI";
$message = urlencode("$msg. Date: $date. For more info Ak Infotech  9051525611.");
$route = 4;
$postData = array(
    'mobiles' => $mobileNumber,
    'message' => $message,
    'sender' => $senderId,
    'route' => $route
);

$url="http://sms.indcomsoftweb.com/api/v2/sendsms";
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "$url",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => array(
        "authkey: 13438A57SeUqbWPz5d88b7fb",
        "content-type: multipart/form-data"
    ),
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
if ($err) {
    echo "cURL Error #:" . $err;
} else {

	
}
 

	
	
	
	
 
echo "<script>window.location.href='all_fees_reminder.php'</script>"; 
	}
?>



                        
                        

     