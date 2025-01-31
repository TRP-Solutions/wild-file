<?php
/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
declare(strict_types=1);
require_once('include.php');

$wf = new WildFile($mysqli,STORAGE,'files');

$file = $wf->get($_GET['file_id'],['mime','name','size']);

WildFileHeader::type($file->mime);
WildFileHeader::size($file->size);
WildFileHeader::filename($file->name,true);
WildFileHeader::expires();

$file->output();
