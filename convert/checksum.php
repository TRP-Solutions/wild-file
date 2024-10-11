<?php
/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
require_once __DIR__.'/../lib/WildFile.php';

$mysqli = new mysqli('localhost','wildfile','Pa55w0rd','wildfile');
$mysqli->set_charset('utf8mb4');

$table = 'files';
$idfield = 'id';
$oldfield = 'checksum_old';
$newfield = 'checksum';
$dir = 'files';
$storage = __DIR__.'/storage';

if(empty($argv[1]) || !in_array($argv[1],['show','convert','purge'])) {
	output("Invalid mode! ['show','convert','purge']");
	exit;
}
$mode = (string) $argv[1];
if(empty($argv[2]) || !is_numeric($argv[2])) {
	output("Invalid limit!");
	exit;
}
$limit = (int) $argv[2];

$sql = "SELECT `$idfield` as id FROM $table
	WHERE `$oldfield` != '' LIMIT $limit";
$query = $mysqli->query($sql);

$wf = new WildFile($mysqli,$storage,$table,$dir);
$wf->set_idfield($idfield);

while($rs = $query->fetch_object()) {
	$file = $wf->get($rs->id);
	$filename = $file->get_path();
	output("Found ID: ".$rs->id.' ('.$filename.')');
	$checksum = hash_file('sha256',$filename);
	output("\t New checksum: ".$checksum);

	if($mode=='convert' || $mode=='purge') {
		$id = $mysqli->real_escape_string($rs->id);
		$sql = "UPDATE $table SET `$newfield` = '$checksum'
			WHERE `$idfield`='$id'
			LIMIT 1";
		$mysqli->query($sql);
		$content = 'SHA256 ('.$id.'.bin) = '.$checksum.PHP_EOL;
		file_put_contents($filename.'.sha256', $content);
		output("\t Stored");
	}

	if($mode=='purge') {
		unlink($filename.'.md5');
		$id = $mysqli->real_escape_string($rs->id);
		$sql = "UPDATE $table SET `$oldfield` = ''
			WHERE `$idfield`='$id'
			LIMIT 1";
		$mysqli->query($sql);
		output("\t Purged");
	}
}

function output($str) {
	echo date('H:i:s').' '.$str.PHP_EOL;
}
