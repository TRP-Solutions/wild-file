<?php
/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
declare(strict_types=1);
require_once('include.php');

$wf = new WildFile($mysqli,STORAGE,'files','thumbnail');

$fields = [];
$fields['thumbnail'] = ['value'=>'NULL','noescape'=>true];
$wf->evict($_GET['file_id'],$fields);

$wf = new WildFile($mysqli,STORAGE,'files');
$wf->delete($_GET['file_id']);

header('Location: .');
