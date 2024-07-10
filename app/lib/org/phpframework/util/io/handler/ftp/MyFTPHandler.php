<?
include_once get_lib("org.phpframework.util.io.handler.MyIOHandler");

class MyFTPHandler extends MyIOHandler {
	public $file_name;
	public $settings;
	
	private $conn_id;
	private $is_connected;
	private $is_passive_mode;
	
	public function __construct($host, $username, $password, $port = false, $file_name = false, $settings = array()) {
		$this->file_name = $file_name;
		$this->settings = $settings;
		$this->is_passive_mode = isset($settings["passive_mode"]) ? $settings["passive_mode"] : null;
		
		$this->connect($host, $username, $password, $port, $this->is_passive_mode);
	}
	
	/****************** START: FTP CONNECTION FUNCTIONS *********************/
	
	/*
	 * connect: connects to a FTP server
	 */
	public function connect($host, $username, $password, $port = false, $is_passive_mode = false) {
		$host = $this->configureHost($host);
		
		if(is_numeric($port))
			$this->conn_id = ftp_connect($host, $port) or die("Couldn't connect to $host:$port"); 
		else $this->conn_id = ftp_connect($host) or die("Couldn't connect to $host"); 
	
		$this->is_connected = false;
		if (@ftp_login($this->conn_id, $username, $password)) {
			$this->setPassiveMode($is_passive_mode);
	
			$this->is_connected = true;
		}
		
	}
	
	/*
	 * setPassiveMode: turns passive mode on
	 */
	public function setPassiveMode($status) {
		return ftp_pasv($this->conn_id, $status);	
	}
	
	/*
	 * close: closes FTP connection
	 */
	public function close() {
		return $this->conn_id ? ftp_close($this->conn_id) : true;  
	}
	
	/*
	 * isConnected: checks if the connection is still active
	 */
	public function isConnected() {
		return $this->is_connected;
	}
	
	/*
	 * configureHost: configures host
	 */
	private function configureHost($host) {
		$index = strpos($host, "ftp://");
		return is_numeric($index) && $index == 0 ? substr($host, 6) : $host;
	}
	
	/****************** END: FTP CONNECTION FUNCTIONS *********************/
	
	/*
	 * getType: gets file type
	 */
	public function getType($file_path) {
		//TODO
	}
	
	/*
	 * rename: renames file
	 */
	public function rename($new_name) {
		//TODO
	}
	
	/*
	 * exists: checks if a file exists
	 */
	public function exists() {
		//TODO
	}
	
	/*
	 * getInfo: gets file info
	 */
	public function getInfo() {
		//TODO
	}
}
?>
