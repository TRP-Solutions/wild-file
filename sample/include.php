<?php
/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
declare(strict_types=1);
require_once('../../heal-document/lib/HealDocument.php'); // https://github.com/TRP-Solutions/heal-document
require_once('../lib/WildFile.php');

define('STORAGE',__DIR__.'/../storage');

$mysqli = new mysqli('localhost','wildfile','Pa55w0rd','wildfile');
$mysqli->set_charset('utf8mb4');
