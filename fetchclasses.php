<?php
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Credentials: true');

error_reporting(0);
$_POST = json_decode(file_get_contents('php://input'), true);

/*******************
  0. DOCUMENTATION
********************/
/*
	Version: 1.0
	Admin API: YES
	Brief: To fetch the class list

	Author: Shafeef
	First Created: 19-09-2017
	Last Modified: 29-11-2017 @Shafeef
*/


/**********************************
  1.1 AUTHENTICATION STANDARD PART
***********************************/

//Encryption Credentials
define('SECURE_CHECK', true);
require 'secure.php';


//Encryption Validation
if(!isset($_POST['token'])){
	$output = array(
			"status" => false,
			"error" => "Access Token Missing",
			"errorCode" => 103,
			"response" => ""
	);
	die(json_encode($output));
}

$token = $_POST['token'];
$decryptedtoken = openssl_decrypt($token, $encryptionMethod, $secretHash);
$tokenid = json_decode($decryptedtoken, true);

//Expiry Validation
date_default_timezone_set('Asia/Calcutta');
$dateStamp = date_create($tokenid['date']);
$today = date_create(date("Y-m-j"));
$interval = date_diff($dateStamp, $today);
$interval = $interval->format('%a');

if($interval > $tokenExpiryDays){
	$output = array(
			"status" => false,
			"error" => "Login Expired",
			"errorCode" => 401,
			"response" => ""
	);
	die(json_encode($output));
}

/**********************************
  1.2 AUTHENTICATION CUSTOM PART
***********************************/

//Check if the token is valid
if(!($tokenid['schoolCode'] == "")){
	$schoolCode = $tokenid['schoolCode'];
	$admin_mobile = $tokenid['mobile'];
	$admin_role = $tokenid['role'];
}
else{
	$output = array(
		"status" => false,
		"error" => "Invalid Token",
		"errorCode" => 402,
		"response" => ""		
	);
	die(json_encode($output));
}

//Check if the user has permission to access this API
if($admin_role != "ADMIN" && $admin_role != "TEACHER"){
	$output = array(
			"status" => false,
			"error" => "Access Restricted",
			"errorCode" => 403,
			"response" => ""
		);
	die(json_encode($output));
}


//Validations


//REQUIRED PARAMETERS

date_default_timezone_set('Asia/Kolkata');
$date = date('m/d/Y h:i:s a', time());

$class = $_POST['classSelect'];


/****************
  3. MAIN LOGIC
*****************/

//3.1 CONNECTION TO CUSTOM DATABASE
define('INCLUDE_CHECK', true);
require 'connect_'.$schoolCode.'.php';

if($class == ""){
	$list = mysql_query("SELECT * FROM `d_classmasterlist` WHERE `class` = '12'");
}
else{
	$list = mysql_query("SELECT * FROM `d_classmasterlist` WHERE `class` = '{$class}'");	
}


while($class = mysql_fetch_assoc($list)){
	 	
	 $teacherInfo = mysql_fetch_assoc(mysql_query("SELECT * FROM `d_teachersmasterlist` WHERE `teacherId` = '{$class['classTeacherId']}' "));

	 $studentCount = mysql_fetch_assoc(mysql_query("SELECT COUNT(`roll`) AS total FROM `d_studentsmasterlist` WHERE `class`='{$class['class']}' && `division`='{$class['division']}'"));

	 //Include Absentee Count too

		$response[] = array(
				"class"=>$class['class'],
				"division"=>$class['division'],
				"classTeacherfName"=>$teacherInfo['fName'],
				"classTeacherlName"=>$teacherInfo['lName'],
				"classTeacherGender"=>$teacherInfo['gender'],
				"studentCount"=>$studentCount['total'],
				"type"=>$class['type'],
				"stream"=>$class['stream']
		);
}

$output = array(
	"status" => true,
	"error" => "",
	"errorCode" => "",
	"response" => $response
);
die(json_encode($output));



if(!$status){
  $output = array(
			"status" => false,
			"error" => "",
			"errorCode" => "402",
			"posts" => ""
		);
		die(json_encode($output));
	}

		
?>