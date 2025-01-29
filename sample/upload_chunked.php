<?php
/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
declare(strict_types=1);
require_once('include.php');

$upload = WildFileChunked::from_input(STORAGE,'files');

$fields = [];
$fields['name'] = ['auto'=>WildFile::NAME];
$fields['size'] = ['auto'=>WildFile::SIZE];
$fields['mime'] = ['auto'=>WildFile::MIME];
$fields['checksum'] = ['auto'=>WildFile::CHECKSUM];
$fields['address'] = ['value'=>$_SERVER['REMOTE_ADDR']];
$fields['created'] = ['value'=>'NOW()','noescape'=>true];

if($upload->complete()){
	// initialize database here
	$wf = new WildFile($mysqli,STORAGE,'files');
	$wf->store_chunked_upload($upload, $fields);
}

$result = $upload->to_output();

echo json_encode($result);
