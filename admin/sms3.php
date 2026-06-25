<?php
include('config.php');


$course=$_POST['course'];
$msg=$_POST['msg'];



$date=date('d-m-Y');
  
	

 
 
 
 
 

 if($con->error)
	echo $con->error;
	else
	{
	$sql="select * from admission where course='$course'";
	
	$res=$con->query($sql);
	while($row=$res->fetch_array())
	{
		
		
   $student=$row['student_name'];
	$number=$row['phone'];
    $student_code=$row['code_no'];
	//echo  $number;
	
	 $sql2="insert into exam_reminder(id,student_name,course,student_code,msg) values('','$student','$course','$student_code','$msg')";
	 
	 $con->query($sql2); 
	//echo $sql2."hdfh";
 
 
 
//post
$url="https://www.way2sms.com/api/v1/sendCampaign";
$message =urlencode($msg);// urlencode your message
$curl = curl_init();
curl_setopt($curl, CURLOPT_POST, 1);// set post data to true
curl_setopt($curl, CURLOPT_POSTFIELDS, "apikey=NSFJ68AGRL0B642020OVPTHIBTWWCGWW&secret=AQLIEYUH7ERPEGR5&usetype=stage&phone=$number&senderid=AK-INFOTECH&message=$message");// post data
// query parameter values must be given without squarebrackets.
 // Optional Authentication:
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($curl);
curl_close($curl);
echo $result;


	}}
	
	
	
 
//echo "<script>window.location.href='exam_reminder.php'</script>"; 

?>



                        
                        

     