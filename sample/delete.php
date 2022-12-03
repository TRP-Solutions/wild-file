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

$sql = "DELETE FROM `files` WHERE `id`='$file_id'";
$mysqli->query($sql);

header('location: .');
