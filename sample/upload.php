<?php
/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
require_once('include.php');

$wf = new WildFile($mysqli,STORAGE,'files');

$fields = [];
$fields['name'] = ['auto'=>WildFile::NAME];
$fields['size'] = ['auto'=>WildFile::SIZE];
$fields['mime'] = ['auto'=>WildFile::MIME];
$fields['checksum'] = ['auto'=>WildFile::CHECKSUM];
$fields['address'] = ['value'=>$_SERVER['REMOTE_ADDR']];
$fields['created'] = ['value'=>'NOW()','noescape'=>true];

$sizecheck = function($tmp_name) {
	$info = getimagesize($tmp_name);
	if($info[0]!==256 || $info[1]!==256 || $info['mime']!=='image/jpeg') {
		throw new \Exception('Wrong format! - Must be 256x256px image/jpeg');
	}
};

$wf->store_post($_FILES['fileupload'],$fields); // ,$sizecheck

header('Location: .');
