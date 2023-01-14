<?php
/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
require_once('include.php');

$doc = new HealDocument();
$html = $doc->el('html');
$html->el('head')->el('title')->te('wild-file :: sample');
$body = $html->el('center')->el('body');

$body->el('h2')->te('wild-file :: filelist');

$sql = "SELECT `id`,`name`,`created`,`address`
	FROM `files` ORDER BY `name`,`id`";
$query = $mysqli->query($sql);
if($mysqli->errno) {
	$body->el('strong')->te($mysqli->error);
}
elseif($query->num_rows) {
	$table = $body->el('table');
	$tr = $table->el('tr');
	$tr->el('th')->te('id');
	$tr->el('th')->te('name');
	$tr->el('th')->te('address');
	$tr->el('th')->te('created');
	$tr->el('th')->te('status');
	$tr->el('th')->at(['colspan'=>2])->te('function');
	while($rs = $query->fetch_object()) {
		$tr = $table->el('tr');
		$tr->el('td')->te('#'.$rs->id);
		$tr->el('td')->te($rs->name);
		$tr->el('td')->te($rs->created);
		$tr->el('td')->te($rs->address);
		$tr->el('td')->el('font',['color'=>'green'])->te('OK');
		$onclick = "location.href='download.php?file_id=".$rs->id."'";
		$tr->el('td')->el('button',['onclick'=>$onclick,'type'=>'button'])->te('download');
		$onclick = "location.href='delete.php?file_id=".$rs->id."'";
		$tr->el('td')->el('button',['onclick'=>$onclick,'type'=>'button'])->te('delete');
	}
}
else {
	$body->el('strong')->te('No files!');
}

$body->el('h2')->te('wild-file :: upload');

$form = $body->el('form',['action'=>'upload.php','method'=>'post','enctype'=>'multipart/form-data']);
$form->el('label',['for'=>'fileupload'])->te('Select file:');
$form->el('input',['type'=>'file','name'=>'fileupload[]','id'=>'fileupload','multiple','required']);
$form->el('br');
$form->el('input',['type'=>'submit','value'=>'Upload']);

$body->el('h2')->te('wild-file :: import');
$onclick = "location.href='import.php?type=server'";
$body->el('button',['onclick'=>$onclick,'type'=>'button'])->te('$_SERVER');
$onclick = "location.href='import.php?type=phpversion'";
$body->el('button',['onclick'=>$onclick,'type'=>'button'])->te('phpversion()');


echo $doc;
