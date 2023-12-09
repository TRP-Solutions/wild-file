<?php
require_once __DIR__.'/../lib/WildFile.php';

$mysqli = new mysqli('localhost','wildfile','Pa55w0rd','wildfile');
$mysqli->set_charset('utf8mb4');

$table = 'files';
$idfield = 'id';
$blobfield = 'thumbnail_legacy';
$dir = 'thumbnail';
$storage = __DIR__.'/../storage';

$field = [
	'thumbnail' => ['value'=>'legacy.svg'],
	//'thumbnail_crc' => ['auto'=>WildFile::CHECKSUM]
];

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

$sql = "SELECT `$idfield` as id,`$blobfield` as datastring FROM $table
	WHERE `$blobfield` IS NOT NULL AND `$blobfield` != ''
	LIMIT $limit";
$query = $mysqli->query($sql);

$wf = new WildFile($mysqli,$storage,$table,$dir);
$wf->set_idfield($idfield);

while($rs = $query->fetch_object()) {
	output("Found ID: ".$rs->id);

	if($mode=='convert' || $mode=='purge') {
		$wf->replace_string($rs->id,$rs->datastring,$field);
		output("\t Stored");

		if($mode=='purge') {
			$id = $mysqli->real_escape_string($rs->id);
			$sql = "UPDATE $table SET `$blobfield` = NULL
				WHERE `$idfield`='$id' AND `$blobfield` IS NOT NULL AND `$blobfield` != ''
				LIMIT 1";
			$mysqli->query($sql);
			output("\t Purged");
		}
	}
}

function output($str) {
	echo date('H:i:s').' '.$str.PHP_EOL;
}
