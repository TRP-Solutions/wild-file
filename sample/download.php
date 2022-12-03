<?php
/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
require_once('include.php');

$file_id = (int) $_GET['file_id'];
if(!$file_id) {
	throw new \Exception('Missing id');
}

$sql = "SELECT `filename` FROM `files` WHERE `id`='$file_id'";
$query = $mysqli->query($sql);
if($rs = $query->fetch_object()) {
	echo $rs->filename;
}
else {
	throw new \Exception('File not found');
}
