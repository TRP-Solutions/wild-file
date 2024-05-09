<?php
/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
require_once('include.php');

$doc = new HealDocument();
$html = $doc->el('html');
$head = $html->el('head');
$head->el('title')->te('wild-file :: sample');
$head->el('script',['src'=>'../lib/WildFile.js']);
$body = $html->el('body')->el('center');

$body->el('h2')->te('wild-file :: filelist');
$missing_thumbnail = [];

$sql = "SELECT `id`,`name`,`created`,`address`,`thumbnail`
	FROM `files` ORDER BY `name`,`id`";
$query = $mysqli->query($sql);
if($mysqli->errno) {
	$body->el('strong')->te($mysqli->error);
}
elseif($query->num_rows) {
	$form = $body->el('form',['action'=>'zip.php','method'=>'post']);
	$table = $form->el('table');
	$tr = $table->el('tr');
	$tr->el('th');
	$tr->el('th')->te('id');
	$tr->el('th');
	$tr->el('th')->te('name');
	$tr->el('th')->te('address');
	$tr->el('th')->te('created');
	$tr->el('th')->te('status');
	$tr->el('th')->at(['colspan'=>2])->te('function');
	while($rs = $query->fetch_object()) {
		$tr = $table->el('tr');
		$tr->el('td')->el('input',['type'=>'checkbox','name'=>'zip[]','value'=>$rs->id]);
		$tr->el('td')->te('#'.$rs->id);
		if($rs->thumbnail) {
			$tr->el('td')->el('img',['src'=>'icon.php?thumbnail_id='.$rs->id,'height'=>'25px','title'=>$rs->thumbnail]);
		}
		else {
			$tr->el('td');
			$missing_thumbnail[] = $rs->id;
		}
		$tr->el('td')->te($rs->name);
		$tr->el('td')->te($rs->address);
		$tr->el('td')->te($rs->created);
		$tr->el('td')->el('font',['color'=>'green'])->te('OK');
		$onclick = "location.href='download.php?file_id=".$rs->id."'";
		$tr->el('td')->el('button',['onclick'=>$onclick,'type'=>'button'])->te('download');
		$onclick = "location.href='delete.php?file_id=".$rs->id."'";
		$tr->el('td')->el('button',['onclick'=>$onclick,'type'=>'button'])->te('delete');
	}
	$form->el('input',['type'=>'submit','value'=>'Download ZIP']);
}
else {
	$body->el('strong')->te('No files!');
}

$body->el('h2')->te('wild-file :: upload');
$form = $body->el('form',['action'=>'upload.php','method'=>'post','enctype'=>'multipart/form-data']);
$form->el('label',['for'=>'fileupload'])->te('Select file:');
$form->el('input',['type'=>'file','name'=>'fileupload[]','id'=>'fileupload','multiple','required','onchange'=>'WildFile.checksum(this);']);
$form->el('br');
$form->el('input',['type'=>'submit','value'=>'Upload']);

if($missing_thumbnail) {
	$body->el('h2')->te('wild-file :: thumbnail');
	$form = $body->el('form',['action'=>'thumbnail.php','method'=>'post','enctype'=>'multipart/form-data']);
	$form->el('label',['for'=>'thumbnail_id'])->te('Select file id:');
	$select = $form->el('select',['name'=>'thumbnail_id','id'=>'thumbnail_id','required']);
	foreach($missing_thumbnail as $id) $select->el('option',['value'=>$id])->te($id);
	$form->el('br');
	$form->el('label',['for'=>'thumbnail'])->te('Select svg:');
	$form->el('input',['type'=>'file','name'=>'thumbnail','id'=>'thumbnail','required','accept'=>'image/svg+xml','onchange'=>'WildFile.checksum(this);']);
	$form->el('br');
	$form->el('input',['type'=>'submit','value'=>'Upload']);
}

$body->el('h2')->te('wild-file :: import');
$onclick = "location.href='import.php?type=server'";
$body->el('button',['onclick'=>$onclick,'type'=>'button'])->te('$_SERVER');
$onclick = "location.href='import.php?type=phpversion'";
$body->el('button',['onclick'=>$onclick,'type'=>'button'])->te('phpversion()');

echo $doc;
