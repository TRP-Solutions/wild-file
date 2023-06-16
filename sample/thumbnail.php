<?php
/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
require_once('include.php');

$wf = new WildFile($mysqli,STORAGE,'`wildfile`.`files`','thumbnail');

$fields = [];
$fields['thumbnail'] = ['auto'=>WildFile::NAME];

$wf->replace_post($_POST['thumbnail_id'],$_FILES['thumbnail'],$fields);

header('location: .');
