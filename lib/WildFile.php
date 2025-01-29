<?php
/*
WildFile is licensed under the Apache License 2.0 license
https://github.com/TRP-Solutions/wild-file/blob/master/LICENSE
*/
declare(strict_types=1);

class WildFile {
	private $table;
	private $dir;
	private $dbconn;
	private $storage;
	private string $idfield = 'id';
	private $callback = [];

	public const NAME = 1;
	public const SIZE = 2;
	public const MIME = 3;
	public const CHECKSUM = 4;

	public function __construct($dbconn,$storage,$table,$dir = null){
		if(!is_int($dbconn->thread_id)) {
			$this->exception('No DB Connection');
		}
		$this->dbconn = $dbconn;
		if(!is_dir($storage)) {
			$this->exception('Invalid directory');
		}
		$this->storage = $storage;
		$table = explode('.',$table);
		$this->dir = empty($dir) ? str_replace('`','',end($table)) : $dir;
		foreach($table as &$value) {
			if(strpos($value,'`')===false) $value = '`'.$value.'`';
		}
		$this->table = implode('.',$table);
	}
	public function set_file_chunk_table($table_chunked = null){
		if(!isset($table_chunked)){
			$this->table_chunked = preg_replace("/`$/", "_chunked_upload`", $this->table);
		} else {
			$table_chunked = explode('.',$table_chunked);
			foreach($table_chunked as &$value) {
				if(strpos($value,'`')===false) $value = '`'.$value.'`';
			}
			$this->table_chunked = implode('.',$table_chunked);
		}
		if($this->table == $this->table_chunked){
			throw new \Exception("Database table for chunked upload can't be the same as file table.");
		}
	}
	public function set_idfield(string $idfield){
		$this->idfield = $idfield;
	}
	public function set_callback($type,$function = null){
		if($function) {
			$this->callback[$type] = $function;
		}
		else {
			unset($this->callback[$type]);
		}
	}
	public function store_string($string,$field = [],$checksum_input = null){
		$checksum = hash('sha256',$string);
		if(func_num_args()===3) $this->checksum_check($checksum,$checksum_input);
		$this->auto_value($field, [
			self::SIZE => strlen($string),
			self::CHECKSUM => $checksum
		]);
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
	public function store_post($FILES,$field = [],$checksum_input = null){
		if(!isset($FILES['tmp_name']) || !is_array($FILES['tmp_name'])) {
			$this->exception('Invalid post array');
		}
		if(empty($FILES['tmp_name'][0])) {
			$this->exception('No files uploaded');
		}
		foreach($FILES['error'] as $key => $error) {
			$this->callback_execute('store',$FILES['tmp_name'][$key]);
			if($error!==UPLOAD_ERR_OK) continue;
			$checksum = hash_file('sha256',$FILES['tmp_name'][$key]);
			if(func_num_args()===3) $this->checksum_check($checksum,$checksum_input[$FILES['name'][$key]]);
			$this->auto_value($field, [
				self::NAME => $FILES['name'][$key],
				self::SIZE => $FILES['size'][$key],
				self::MIME => $FILES['type'][$key],
				self::CHECKSUM => $checksum
			]);
			$id = $this->db_store($field);
			$this->validate_id($id);
			$path = $this->create_path($id);
			$filename = $this->filename($id);
			move_uploaded_file($FILES['tmp_name'][$key],$path.$filename);
			$this->checksum_store($path,$filename,$checksum);
			$this->log('store_post: '.$id.'|'.$path.$filename);
		}
	}
	public function store_chunked_upload(WildFileChunked $upload, $field = []){
		if($upload->complete()){
			$this->auto_value($field, [
				self::SIZE => $upload->file_size,
				self::CHECKSUM => $upload->checksum,
				self::NAME => $upload->name,
				self::MIME => $upload->mime,
			]);

			$id = $this->db_store($field);
			$this->validate_id($id);
			$path = $this->create_path($id);
			$filename = $this->filename($id);
			if(rename($upload->file_uri, $path.$filename)===false) {
				$this->exception('Error store_chunked_upload: '.$path);
			}
			$this->checksum_store($path,$filename,$upload->checksum);
			$this->log('store_chunked_upload: '.$upload->transfer_id.'->'.$id.'|'.$path.$filename);

			$upload->stored_file_id = $field[$this->idfield]['value'] ?? $id;
		}
	}
	private function auto_value(&$field, $auto){
		foreach($field as &$value) {
			if(isset($value['auto']) && isset($auto[$value['auto']])) {
				$value['value'] = $auto[$value['auto']];
			}
		}
	}
	private function db_store($dbfield){
		$fieldset = $this->fieldset($dbfield);
		$sql = "INSERT INTO $this->table SET $fieldset";
		$this->db_query($sql);
		return $this->dbconn->insert_id;
	}
	private function checksum_store($path,$filename,$checksum){
		$content = 'SHA256 ('.$filename.') = '.$checksum.PHP_EOL;
		if(file_put_contents($path.$filename.'.sha256', $content)===false) {
			$this->exception('Error checksum_store: '.$path.$filename.'.sha256');
		}
	}
	private function checksum_check($checksum,$checksum_input){
		if($checksum!==$checksum_input) {
			$this->exception('Error checksum_check');
		}
	}
	public function replace_string($id,$string,$field = [],$checksum_input = null){
		$checksum = hash('sha256',$string);
		if(func_num_args()===4) $this->checksum_check($checksum,$checksum_input);
		$this->auto_value($field, [
			self::SIZE => strlen($string),
			self::CHECKSUM => $checksum,
		]);
		$this->validate_id($id);
		$this->db_replace($id,$field);
		$path = $this->create_path($id);
		$filename = $this->filename($id);
		if(file_put_contents($path.$filename,$string)===false) {
			$this->exception('Error store_string: '.$path);
		}
		$this->checksum_store($path,$filename,$checksum);
		$this->log('replace_string: '.$id.'|'.$path.$filename);
	}
	public function replace_post($id,$FILES,$field = [],$checksum_input = null){
		if($FILES['error']!==UPLOAD_ERR_OK) $this->exception('Upload error');
		$this->callback_execute('store',$FILES['tmp_name']);
		$checksum = hash_file('sha256',$FILES['tmp_name']);
		if(func_num_args()===4) $this->checksum_check($checksum,$checksum_input[$FILES['name']]);
		$this->auto_value($field, [
			self::NAME => $FILES['name'][$key],
			self::SIZE => $FILES['size'][$key],
			self::MIME => $FILES['type'][$key],
			self::CHECKSUM => $checksum
		]);
		$this->validate_id($id);
		$this->db_replace($id,$field);
		$path = $this->create_path($id);
		$filename = $this->filename($id);
		move_uploaded_file($FILES['tmp_name'],$path.$filename);
		$this->checksum_store($path,$filename,$checksum);
		$this->log('replace_post: '.$id.'|'.$path.$filename);
	}
	private function db_replace($id,$dbfield){
		if(empty($dbfield)) return;
		$fieldset = $this->fieldset($dbfield);
		$sql = "UPDATE $this->table SET $fieldset WHERE `$this->idfield`='$id'";
		$this->db_query($sql);
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
		$sql = "SELECT $field FROM $this->table WHERE `$this->idfield`='$id'";
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
		$sql = "DELETE FROM $this->table WHERE `$this->idfield`='$id'";
		$this->db_query($sql);
	}
	private function file_delete($file){
		if(file_exists($file)){
			if(file_exists($file.'.sha256')){
				if(!unlink($file.'.sha256')) {
					$this->exception('Error unlink checksum: '.$file.'.sha256');
				}
			}
			if(!unlink($file)) {
				$this->exception('Error unlink: '.$file);
			}
		}
	}
	public function evict($array,$field = []){
		if(!is_array($array)) $array = [$array];
		foreach($array as $id) {
			$this->validate_id($id);
			$path = $this->get_path($id);
			$filename = $this->filename($id);
			$this->file_delete($path.$filename);
			$this->db_replace($id,$field);
			$this->log('evict: '.$id.'|'.$path.$filename);
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
	private function fieldset($dbfield){
		$fieldset = [];
		foreach($dbfield as $key => $var) {
			if(!isset($var['value'])) $this->exception('Missing value: '.json_encode([$key=>$var]));
			$field = '`'.$this->dbconn->real_escape_string((string) $key).'`=';
			if(isset($var['noescape']) && $var['noescape']===true) {
				$field .= $var['value'];
			}
			else {
				$field .= "'".$this->dbconn->real_escape_string((string) $var['value'])."'";
			}
			$fieldset[] = $field;
		}
		return implode(',',$fieldset);
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
	private function get_path_chunks(){
		$path = $this->storage.DIRECTORY_SEPARATOR.$this->dir.DIRECTORY_SEPARATOR.$this->chunks_subfolder.DIRECTORY_SEPARATOR;
		if(!is_dir($path)){
			if(!mkdir($path)) {
				$this->exception('Error mkdir: '.$path);
			}
		}
		return $path;
	}
	private function filename($id){
		return $id.'.bin';
	}
	private function validate_id($id){
		if(empty($id)) {
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
	private function callback_execute($type,$param) {
		if(!empty($this->callback[$type])) {
			$this->callback[$type]($param);
		}
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
	protected $property = [];
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
	public function __set(string $name, string $value): void {
		$this->property[$name] = $value;
	}
	public function __get(string $name): string {
		return $this->property[$name];
	}
}

class WildFileHeader {
	public static function type($str){
		header('Content-Type: '.$str);
	}
	public static function filename($filename,$download = false){
		$download = $download ? 'attachment' : 'inline';
		header("Content-Disposition: ".$download."; filename*=UTF-8''".rawurlencode($filename));
	}
	public static function expires($datetime = null) {
		if(!$datetime) {
			$datetime = new DateTime('1 month');
		}
		$datetime->setTimezone(new DateTimeZone('UTC'));
		$seconds = (new DateTime())->diff($datetime)->format('%a') * 86400;
		header('Expires: '.$datetime->format('D, d M Y H:i:s \G\M\T'));
		header('Cache-Control: max-age='.$seconds);
		header_remove('Pragma');
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
		$this->size = (string) filesize($this->file);
	}
	public function unlink(){
		$this->archive = null;
		unlink($this->file);
	}
}

class WildFileChunked {
	const STATUS_INCOMPLETE = 0;
	const STATUS_COMPLETE = 1;

	public readonly string $transfer_id;
	public readonly string $file_uri;
	public readonly int $file_size;
	public readonly string $checksum;
	public $stored_file_id;

	private readonly string $progress_uri;
	private $progress_file;
	private int $status;
	private int $progress;
	private int $chunk_size;


	public static function read_input_headers(){
		$range_match = preg_match("/^bytes ([0-9]+)-([0-9]+)\/([0-9]+)$/",$_SERVER['HTTP_CONTENT_RANGE'],$range_values);
		if($range_match !== 1){
			self::exception("Invalid Content-Range header");
		}
		$disposition_match = preg_match("/^attachment; filename=\"([^\"]+)\"$/",$_SERVER['HTTP_CONTENT_DISPOSITION'],$disposition_values);
		if($disposition_match !== 1){
			self::exception("Invalid Content-Disposition header");
		}
		return [
			'checksum' => $_SERVER['HTTP_X_WILDFILE_CHECKSUM'] ?? null,
			'transfer' => $_SERVER['HTTP_X_WILDFILE_TRANSFER'] ?? null,
			'mime' => $_SERVER['CONTENT_TYPE'],
			'name' => $disposition_values[1],
			'range_start' => intval($range_values[1]),
			'range_end' => intval($range_values[2]),
			'file_size' => intval($range_values[3]),
		];
	}
	public static function from_input($storage, $dir, $metadata = null, $subfolder = null){
		if(!isset($metadata)){
			$metadata = self::read_input_headers();
		}
		$chunk = file_get_contents('php://input');
		return new self(
			$chunk,
			$metadata['range_start'],
			$metadata['range_end'],
			$metadata['file_size'],
			$metadata['checksum'],
			$storage,
			$dir,
			$subfolder,
			$metadata['transfer'],
			$metadata['name'],
			$metadata['mime']
		);
	}

	public function complete(){
		return $this->status == self::STATUS_COMPLETE;
	}

	public function to_output(){
		if($this->status == self::STATUS_COMPLETE && isset($this->stored_file_id)){
			return ['status'=>'complete', 'transfer'=>$this->transfer_id, 'file'=>$this->stored_file_id];
		} else {
			return ['status'=>'incomplete', 'transfer'=>$this->transfer_id];
		}
	}

	public function __construct(
		string $chunk,
		int $range_start,
		int $range_end,
		private int $size_input,
		private string $checksum_input,
		string $storage,
		string $dir,
		string $subfolder = null,
		private ?string $transfer_input = null,
		public readonly ?string $name = null,
		public readonly ?string $mime = null,
	){
		$this->chunk_size = strlen($chunk);
		if($this->chunk_size == 0 || $range_start + $this->chunk_size - 1 != $range_end){
			self::exception("Error in WildFileChunked: Invalid file chunk size ($this->chunk_size bytes, $range_start to $range_end)");
		}
		if(!preg_match('/^[0-9A-Fa-f]+$/', $checksum_input)){
			self::exception("Error in WildFileChunked: Invalid checksum input (expected hexadecimal string)");
		}
		$this->transfer_id = strtoupper(substr($checksum_input, 0, 20));

		$path_chunks = self::get_chunk_path($storage, $dir, $subfolder ?? '.chunked_upload');
		$this->file_uri = $path_chunks.self::buffer_filename($this->transfer_id);
		$this->progress_uri = $path_chunks.self::progress_filename($this->transfer_id);

		$this->progress_lock();

		$file = fopen($this->file_uri, 'c');
		if($file === false){
			self::exception("Error in WildFileChunked [fopen]");
		}
		self::file_write($file, $range_start, $chunk);

		$this->update_progress();

		self::log('WildFileChunked: '.$this->transfer_id."($range_start-$range_end/$size_input)|".$path_chunks);

		if($this->progress < $size_input){
			$this->status = self::STATUS_INCOMPLETE;
		} else {
			$this->validate_file();
			$this->status = self::STATUS_COMPLETE;
			if(unlink($this->progress_uri) === false){
				self::log("Warning in WildFileChunked: Failed unlinking obsolete progress file");
			}
		}
	}

	private function progress_lock() {
		// Open with mode 'c+' (read & write) so we can request advisory lock
		$file = fopen($this->progress_uri, 'c+');
		if($file === false){
			self::exception("Error progress_lock [fopen]");
		}
		// lock the progress file with an advisory lock, so we can avoid race conditions
		if(flock($file, LOCK_EX) === false){
			self::exception("Error progress_lock [flock]");
		}
		$this->progress_file = $file;
	}

	private function update_progress(){
		// read up to 64 bits of data
		$bytes = fread($this->progress_file, 8);
		if($bytes === false){
			self::exception('Error update_progress [fread]');
		} elseif($bytes === ''){
			$previous_progress = 0;
		} else {
			// J: unsigned long long (always 64 bit, big endian byte order)
			$progress_unpacked = unpack('J', $bytes);
			if($progress_unpacked === false){
				self::exception('Error update_progress [unpack]');
			} else {
				$previous_progress = $progress_unpacked[1];
			}
		}

		$this->progress = $previous_progress + $this->chunk_size;

		$bytes = pack('J', $this->progress);

		// closing the progress file also releases the advisory lock
		self::file_write($this->progress_file, 0, $bytes);
		$this->progress_file = null;
	}
	private function validate_file(){
		$this->file_size = filesize($this->file_uri);
		if($this->file_size > $this->size_input){
			self::exception("Error in WildFileChunked: $this->file_uri (file size doesn't match expected size, $file_size > $this->size_input)");
		}
		$this->checksum = hash_file('sha256',$this->file_uri);
		if($this->checksum !== $this->checksum_input){
			self::exception("Error in WildFileChunked: Checksum doesn't match input\n$this->checksum !== $this->checksum_input\n$this->size_input $this->file_size");
		}
		return true;
	}
	private static function file_write($file, int $seek, string $data) {
		if(fseek($file, $seek) === -1){
			self::exception("Error file_write [fseek]");
		}
		if(fwrite($file, $data) === false){
			self::exception("Error file_write [fwrite]");
		}
		if(fclose($file) === false){
			self::exception("Error file_write [fclose]");
		}
	}
	private static function buffer_filename($transfer_id){
		return $transfer_id.'.bin';
	}
	private static function progress_filename($transfer_id){
		return $transfer_id.'_progress.bin';
	}
	private static function get_chunk_path($storage, $dir, $storage_subfolder){
		$path = $storage.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$storage_subfolder.DIRECTORY_SEPARATOR;
		if(!is_dir($path)){
			if(!mkdir($path)) {
				self::exception('Error mkdir: '.$path);
			}
		}
		return $path;
	}
	protected static function exception($message){
		self::log($message,LOG_ERR);
		throw new \Exception($message);
	}
	protected static function log($message,$priority = LOG_INFO){
		syslog($priority,$message);
	}
}
