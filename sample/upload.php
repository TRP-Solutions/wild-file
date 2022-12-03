<?php
/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
require_once('include.php');

if(empty($_FILES['fileupload']['tmp_name'][0])) {
	throw new \Exception('No files uploaded');
}

foreach($_FILES['fileupload']['error'] as $key => $error) {
	if($error!==0) continue;
	
	$filename = $_FILES['fileupload']['name'][$key];
	$filename = $mysqli->real_escape_string($filename);
	$sql = "INSERT INTO `files` (`filename`,`checksum`) VALUES ('$filename','TBD')";
	$mysqli->query($sql);
	
	$tmp_name = $_FILES['fileupload']['tmp_name'][$key];
}

header('location: .');
