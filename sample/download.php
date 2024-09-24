<?php
/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
declare(strict_types=1);
require_once('include.php');

$wf = new WildFile($mysqli,STORAGE,'files');

$file = $wf->get($_GET['file_id'],['mime','name']);

header('Content-Type: '.$file->mime);
header('Content-Disposition: inline; filename="'.$file->name.'"');

$file->output();
