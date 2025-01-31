<?php
/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
declare(strict_types=1);
require_once('../lib/WildFileChunkedUpload.php');

$upload = WildFileChunkedUpload::from_input();

if($upload->complete()){
	require_once('include.php');
	$fields = [];
	$fields['name'] = ['value'=>$upload->name];
	$fields['size'] = ['value'=>$upload->file_size];
	$fields['mime'] = ['value'=>$upload->mime];
	$fields['checksum'] = ['value'=>$upload->checksum];
	$fields['address'] = ['value'=>$_SERVER['REMOTE_ADDR']];
	$fields['created'] = ['value'=>'NOW()','noescape'=>true];

	$wf = new WildFile($mysqli,STORAGE,'files');
	$file_id = $wf->store_file($upload->file_uri, $fields);
} else {
	$file_id = null;
}

$result = $upload->to_output($file_id);

echo json_encode($result);
