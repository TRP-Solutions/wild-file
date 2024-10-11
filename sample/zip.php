<?php
/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
declare(strict_types=1);
require_once('include.php');

if(empty($_POST['zip'])) {
	echo 'No files selected!';
	exit;
}

$wf = new WildFile($mysqli,STORAGE,'files');

$zip = $wf->zip();
foreach($_POST['zip'] as $id) {
	$zip->add($id);
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Length: '.$zip->size);
header('Content-Disposition: attachment; filename="wf-download.zip"');

$zip->output();
$zip->unlink();
