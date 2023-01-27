<?php
/*
SimpleAuth is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/

class WildFile {
	private $table;
	private $dir;
	private $dbconn;
	private $storage;

	public const NAME = 1;
	public const SIZE = 2;
	public const MIME = 3;
	public const CHECKSUM = 4;

	public function __construct($dbconn,$storage,$table){
		if(!is_int($dbconn->thread_id)) {
			$this->exception('No DB Connection');
		}
		$this->dbconn = $dbconn;
		if(!is_dir($storage)) {
			$this->exception('Invalid directory');
		}
		$this->storage = $storage;
		$table = explode('.',$table);
		$this->dir = str_replace('`','',end($table));
		foreach($table as &$value) {
			if(strpos($value,'`')===false) $value = '`'.$value.'`';
		}
		$this->table = implode('.',$table);
	}
	public function store_string($string,$field = []){
		$checksum = md5($string);
		foreach($field as &$value) {
			if(isset($value['auto'])) {
				if($value['auto']===self::SIZE) {
					$value['value'] = strlen($string);
				}
				elseif($value['auto']===self::MIME) {
					$value['value'] = $FILES['type'][$key];
				}
				elseif($value['auto']===self::CHECKSUM) {
					$value['value'] = $checksum;
				}
			}
		}
		$id = $this->db_store($field);
		$this->validate_id($id);
		$path = $this->create_path($id);
		$filename = $this->filename($id);
		if(file_put_contents($path.$filename,$string)===false) {
			$this->exception('Error store_string: '.$path);
		}
		$this->checksum_store($path,$filename,$checksum);
		$this->log('store_string: '.$id.'|'.$path.$filename);
	}
	public function store_post($FILES,$field = [],$callback = null){
		if(!is_array($FILES['tmp_name'])) {
			$this->exception('Invalid post array');
		}
		if(empty($FILES['tmp_name'][0])) {
			$this->exception('No files uploaded');
		}
		foreach($FILES['error'] as $key => $error) {
			if($callback) $callback($FILES['tmp_name'][$key]);
			if($error!==UPLOAD_ERR_OK) continue;
			$checksum = md5_file($FILES['tmp_name'][$key]);
			foreach($field as &$value) {
				if(isset($value['auto'])) {
					if($value['auto']===self::NAME) {
						$value['value'] = $FILES['name'][$key];
					}
					elseif($value['auto']===self::SIZE) {
						$value['value'] = $FILES['size'][$key];
					}
					elseif($value['auto']===self::MIME) {
						$value['value'] = $FILES['type'][$key];
					}
					elseif($value['auto']===self::CHECKSUM) {
						$value['value'] = $checksum;
					}
				}
			}
			$id = $this->db_store($field);
			$this->validate_id($id);
			$path = $this->create_path($id);
			$filename = $this->filename($id);
			move_uploaded_file($FILES['tmp_name'][$key],$path.$filename);
			$this->checksum_store($path,$filename,$checksum);
			$this->log('store_post: '.$id.'|'.$path.$filename);
		}
	}
	private function db_store($dbfield){
		$field = $value = [];

		foreach($dbfield as $key => $var) {
			$field[] = '`'.$this->dbconn->real_escape_string($key).'`';
			if(isset($var['noescape']) && $var['noescape']===true) {
				$value[] = $var['value'];
			}
			else {
				$value[] = "'".$this->dbconn->real_escape_string($var['value'])."'";
			}
		}

		$field = implode(',',$field);
		$value = implode(',',$value);
		$sql = "INSERT INTO $this->table ($field) VALUES ($value)";
		$this->db_query($sql);
		return $this->dbconn->insert_id;
	}
	private function checksum_store($path,$filename,$checksum){
		$md5 = $checksum."\t".$filename.PHP_EOL;
		if(file_put_contents($path.$filename.'.md5', $md5)===false) {
			$this->exception('Error checksum_store: '.$path.$filename.'.md5');
		}
	}
	public function get($id,$field = []){
		$this->validate_id($id);
		$path = $this->get_path($id);
		$filename = $this->filename($id);
		$out = new WildFileOut($path.$filename);
		if($field) {
			$this->db_get($out,$id,$field);
		}
		return $out;
	}
	private function db_get($out,$id,$dbfield){
		$field = [];
		foreach($dbfield as $var) {
			$field[] = '`'.$this->dbconn->real_escape_string($var).'`';
		}
		$field = implode(',',$field);
		$sql = "SELECT $field FROM $this->table WHERE `id`='$id'";
		$query = $this->db_query($sql);
		if($rs = $query->fetch_assoc()) {
			foreach($rs as $key => $value) {
				$out->$key = $value;
			}
		}
	}
	public function delete($array){
		if(!is_array($array)) $array = [$array];
		foreach($array as $id) {
			$this->validate_id($id);
			$path = $this->get_path($id);
			$filename = $this->filename($id);
			$this->file_delete($path.$filename);
			$this->db_delete($id);
			$this->log('delete: '.$id.'|'.$path.$filename);
		}
	}
	private function db_delete($id){
		$sql = "DELETE FROM $this->table WHERE `id`='$id'";
		$this->db_query($sql);
	}
	private function file_delete($file){
		if(file_exists($file)){
			if(file_exists($file.'.md5')){
				if(!unlink($file.'.md5')) {
					$this->exception('Error unlink checksum: '.$file.'.md5');
				}
			}
			if(!unlink($file)) {
				$this->exception('Error unlink: '.$file);
			}
		}
	}
	public function zip(){
		return new WildFileZip($this);
	}
	private function db_query($sql){
		$query = $this->dbconn->query($sql);
		if($this->dbconn->errno) {
			$this->exception('SQL Error: '.$this->dbconn->error);
		}
		return $query;
	}
	private function get_path($id){
		$storage = $this->storage.DIRECTORY_SEPARATOR;
		$folder = $this->folder($id);
		return $storage.$folder;
	}
	private function create_path($id){
		$storage = $this->storage.DIRECTORY_SEPARATOR;
		$folder = $this->folder($id);
		if(!is_dir($storage.$folder)) {
			$folder_arr = explode(DIRECTORY_SEPARATOR, $folder);
			$dir='';
			foreach($folder_arr as $part) {
				$dir .= $part.DIRECTORY_SEPARATOR;
				if(!is_dir($storage.$dir) && strlen($storage.$dir)>0) {
					if(!mkdir($storage.$dir)) {
						$this->exception('Error mkdir: '.$storage.$dir);
					}
				}
			}
		}
		return $storage.$folder;
	}
	private function filename($id){
		return $id.'.bin';
	}
	private function validate_id($id){
		if(empty($id) || !is_numeric($id)) {
			$this->exception('Invalid fileid');
		}
	}
	private function folder($id){
		$parts = [];
		$parts[] = $this->dir;
		$str = (string) $id;
		while(strlen($str) > 2) {
			$parts[] = substr($str, -2);
			$str = substr($str, 0, -2);
		}
		return implode(DIRECTORY_SEPARATOR, $parts).DIRECTORY_SEPARATOR;
	}
	protected function exception($message){
		$this->log($message,LOG_ERR);
		throw new \Exception($message);
	}
	protected function log($message,$priority = LOG_INFO){
		syslog($priority,$message);
	}
}

class WildFileOut {
	protected $file;
	public function __construct($file){
		$this->file = $file;
	}
	public function __toString(){
		return file_get_contents($this->file);
	}
	public function output(){
		return readfile($this->file);
	}
	public function get_path(){
		return $this->file;
	}
}

class WildFileZip extends WildFileOut {
	private $wf;
	private $archive;

	public function __construct($wf){
		$this->wf = $wf;
		$file = tempnam(sys_get_temp_dir(), 'wfzip_');
		$this->archive = new ZipArchive();
		$result = $this->archive->open($file, ZipArchive::OVERWRITE);
		if(!$result) {
			throw new \Exception('Error open ZipArchive: '.$file);
		}
		$this->file = $file;
	}
	public function add($id,$name = null){
		$file = $this->wf->get($id,$name ? [] : ['name']);
		$result = $this->archive->addFile($file->get_path(),$name ? $name : $file->name);
		if(!$result) {
			throw new \Exception('Error addFile ZipArchive: '.$file->get_path());
		}
	}
	public function close(){
		$result = $this->archive->close();
		if(!$result) {
			throw new \Exception('Error close ZipArchive');
		}
		$this->size = filesize($this->file);
	}
	public function unlink(){
		$this->archive = null;
		unlink($this->file);
	}
}
