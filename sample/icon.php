<?php
/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
declare(strict_types=1);
require_once('include.php');

$wf = new WildFile($mysqli,STORAGE,'files','thumbnail');
$file = $wf->get($_GET['thumbnail_id']);

header('Content-Type: image/svg+xml');

$file->output();
