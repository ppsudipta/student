<?php
include('config.php');

$student_code=$_GET['student_id'];
$fees=$_GET['fees'];
$month=$_GET['month'];


		

  
	
 

	
	$sql="select * from admission where code_no='$student_code'";
	$res=$con->query($sql);
	$row=$res->fetch_array();
	
	
	
	$phone_number=$row['phone_number'];

 



$mobileNumber = "$phone_number";




$senderId = "AKI";

$message = urlencode("You paid $fees for $month month. AK INFOTECH");

$route = 4;

//Prepare you post parameters
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
    //echo $response;
	
	
	echo "<script>window.location.href='addfees.php'</script>"; 
}
?>



                        
                        

     