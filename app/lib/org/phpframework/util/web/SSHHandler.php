<?php
//Note that this class needs the php-ssh2 mod installed. Please check https://www.php.net/manual/en/ssh2.installation.php url.
class SSHHandler {
	
	private $ssh_host; // SSH Host 'myserver.example.com'
	private $ssh_port; // SSH Port 22
	private $ssh_server_fp; // SSH Server Fingerprint 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
	private $ssh_auth_user; // SSH Username
	private $ssh_auth_pass; // SSH Password
	private $ssh_auth_pub_file; // SSH Public Key File '/home/username/.ssh/id_rsa.pub'
	private $ssh_auth_priv_file; // SSH Private Key File '/home/username/.ssh/id_rsa'
	private $ssh_auth_pub_string; // SSH Public Key String
	private $ssh_auth_priv_string; // SSH Private Key String
	private $ssh_auth_passphrase; // SSH Private Key Passphrase (null == no passphrase)
	
	private $ssh_auth_key_tmp_folder; //folder path that will be used to create the temporary .pub and id_rsa files based in the $ssh_auth_pub_string and $ssh_auth_priv_string. If this is not set the default PHP tmp folder will be used.
	private $ssh_auth_key_temp_files = array();
	
	private $connection; // SSH Connection
	private $authenticated;
	
	/* GETTERS AND SETTERS */
	
	public function setSetting($key, $value) {
		$key = strtolower($key);
		
		switch ($key) {
			case "host": $this->ssh_host = $value; break;
			case "port": $this->ssh_port = $value ? $value : 22; break;
			case "server_fp": 
			case "fp": 
			case "fingerprint": 
				$this->ssh_server_fp = $value; break;
			case "user": 
			case "username": 
				$this->ssh_auth_user = $value; break;
			case "pass": 
			case "password": 
				$this->ssh_auth_pass = $value; break;
			case "pub_file": 
			case "ssh_auth_pub_file": 
				$this->ssh_auth_pub_file = $value; break;
			case "priv_file": 
			case "ssh_auth_priv_file": 
				$this->ssh_auth_priv_file = $value; break;
			case "pub_string": 
			case "ssh_auth_pub_string": 
				$this->ssh_auth_pub_string = $value; break;
			case "priv_string": 
			case "ssh_auth_priv_string": 
				$this->ssh_auth_priv_string = $value; break;
			case "passphrase": 
			case "ssh_auth_passphrase": 
				$this->ssh_auth_passphrase = $value ? $value : null; break;
		}
	}
	
	public function setSettings($settings) {
		if ($settings)
			foreach ($settings as $key => $value)
				$this->setSetting($key, $value);
	}
	
	public function setSSHAuthKeyTmpFolderPath($ssh_auth_key_tmp_folder) {
		$this->ssh_auth_key_tmp_folder = $ssh_auth_key_tmp_folder;
	}

	/* HANDLERS */
	
	public function connect($settings = null) {
		//set settings
		$this->setSettings($settings);
		
		$this->connection = null;
		$this->authenticated = false;
		
		//connect to server
		if (!$this->ssh_host)
			launch_exception(new Exception('SSH Host cannot be undefined!'));
		else if (!($this->connection = ssh2_connect($this->ssh_host, $this->ssh_port))) 
			launch_exception(new Exception('Cannot connect to server!'));
		
		//check server finger print
		if ($this->ssh_server_fp) {
			$fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
			
			if (strcmp($this->ssh_server_fp, $fingerprint) !== 0)
				launch_exception(new Exception('Unable to verify server identity!'));
		}
		
		//authenticate user in server
		if (!$this->ssh_auth_user)
			launch_exception(new Exception('User cannot be undefined!'));
		else if ($this->ssh_auth_pass) {
			if (ssh2_auth_password($this->connection, $this->ssh_auth_user, $this->ssh_auth_pass))
				$this->authenticated = true;
			else
				launch_exception(new Exception('Autentication rejected by server!'));
		}
		else if (($this->ssh_auth_pub_file || $this->ssh_auth_pub_string) && ($this->ssh_auth_priv_file || $this->ssh_auth_priv_string)) {
			$ssh_auth_pub_file = $ssh_auth_priv_file = null;
			
			if ($this->ssh_auth_pub_file)
				$ssh_auth_pub_file = $this->ssh_auth_pub_file;
			else if ($this->ssh_auth_pub_string)
				$ssh_auth_pub_file = $this->createSSHAuthKeyFile($this->ssh_auth_pub_string); //create temp file with $this->ssh_auth_pub_string
			
			if ($this->ssh_auth_priv_file)
				$ssh_auth_priv_file = $this->ssh_auth_priv_file;
			else if ($this->ssh_auth_priv_string)
				$ssh_auth_priv_file = $this->createSSHAuthKeyFile($this->ssh_auth_priv_string); //create temp file with $this->ssh_auth_priv_string
			
			if (!$ssh_auth_pub_file || !$ssh_auth_priv_file)
				launch_exception(new Exception('The variables $ssh_auth_pub_file and $ssh_auth_priv_file must be existent files!'));
			else if (ssh2_auth_pubkey_file($this->connection, $this->ssh_auth_user, $ssh_auth_pub_file, $ssh_auth_priv_file, $this->ssh_auth_passphrase))
				$this->authenticated = true;
			else
				launch_exception(new Exception('Autentication rejected by server!'));
		}
		else
			launch_exception(new Exception('Autentication must be done through username/password or pub/priv key!'));
		
		return $this->isConnected();
	}
	
	public function isConnected() {
		return $this->connection && $this->authenticated;
	}
	
	public function exec($cmd) {
		if (!$this->isConnected())
			launch_exception(new Exception('SSH not connected!'));
		else if (!($stream = ssh2_exec($this->connection, $cmd))) 
			launch_exception(new Exception('SSH command failed!'));
		else {
			$error_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

			stream_set_blocking($error_stream, true);
			stream_set_blocking($stream, true);
			
			$data = stream_get_contents($stream); // The command may not finish properly if the stream is not read to end
			$error = stream_get_contents($error_stream);
			
			fclose($error_stream);
			fclose($stream);
			
			if ($error)
				launch_exception(new Exception('SSH command failed with error: ' . $error));
			
			return $data;
		}
	}
	
	public function disconnect() {
		if ($this->isConnected())
			$this->exec('echo "EXITING" && exit;');
		
		$this->connection = null;
		$this->authenticated = false;
		
		//delete temp files
		if ($this->ssh_auth_key_temp_files)
			foreach ($this->ssh_auth_key_temp_files as $idx => $fp)
				if (file_exists($fp) && @unlink($fp))
					unset($this->ssh_auth_key_temp_files[$idx]);
	}
	
	public function __destruct() {
		$this->disconnect();
	}
	
	public function getConnection() {
		return $this->connection;
	}
	
	public function sshToSftp() {
		return $this->isConnected() ? ssh2_sftp($this->connection) : null;
	}
	
	public function sshToShell($term_type = "vanilla", $env = null, $width = 80, $height = 25, $width_height_type = SSH2_TERM_UNIT_CHARS) {
		return $this->isConnected() ? ssh2_shell($this->connection, $term_type, $env, $width, $height, $width_height_type) : null;
	}
	
	public function sshToTunnel($host, $port) {
		return $this->isConnected() ? ssh2_tunnel($this->connection, $host, $port) : null;
	}
	
	public function createSSHAuthKeyFile($string) {
		$tmp_folder = ($this->ssh_auth_key_tmp_folder ? $this->ssh_auth_key_tmp_folder : sys_get_temp_dir()) . "/";
		$path = tempnam($tmp_folder, "rsa_");
		
		if ($path) {
			$status = file_put_contents($path, $string) !== false;
			
			if (!$status) {
				$path = null;
				
				if (file_exists($path))
					@unlink($path);
			}
			
			$this->ssh_auth_key_temp_files[] = $path;
		}
		
		return $path;
	}
	
	public function exists($path) {
		return !empty($this->getFileInfo($path));
	}
	
	public function getFileInfo($path) {
		$sftp = $this->sshToSftp();
		
		$info = @ssh2_sftp_lstat($sftp, $path);
		
		$ts = array(
			0140000 => 'ssocket',
			0120000 => 'llink',
			0100000 => '-file',
			0060000 => 'bblock',
			0040000 => 'ddir',
			0020000 => 'cchar',
			0010000 => 'pfifo'
		);
		
		if ($info) {
			$info_mode = isset($info['mode']) ? $info['mode'] : null;
			$t = decoct($info_mode & 0170000);
			$type = isset($ts[octdec($t)]) ? substr($ts[octdec($t)], 1) : null;
			$is_link = $type == "link";
			
			if ($is_link) {
				$real_info = @ssh2_sftp_stat($sftp, $path);
				$real_info_mode = isset($real_info['mode']) ? $real_info['mode'] : null;
				$t = decoct($real_info_mode & 0170000);
				$type = isset($ts[octdec($t)]) ? substr($ts[octdec($t)], 1) : null;
				
				//getting permission for the link
				$info["mode_oct"] = "0" . decoct($info_mode & 000777); //0755
				$info["mode_dec"] = octdec($info["mode_oct"]); //493
				
				//getting link real target path
				$info["target"] = ssh2_sftp_readlink($sftp, $path);
				
				//prioritizing real folder or file information and add link info to $info["link_info"]
				$real_info["link_info"] = $info;
				$info = $real_info;
			}
			
			$info["type"] = $type;
			$info["is_dir"] = $type == "dir";
			$info["is_link"] = $is_link;
			$info["mode_oct"] = "0" . decoct($info_mode & 000777); //0755
			$info["mode_dec"] = octdec($info["mode_oct"]); //493
		}
		
		return $info; //if $info is null, it means the file does not exists
	}
	public function isDir($path) {
		$info = $this->getFileInfo($path);
		return isset($info["is_dir"]) ? $info["is_dir"] : null;
	}
	
	//$remote_file must be a file too. Cannot be a folder!
	public function copyLocalToRemoteFile($local_file, $remote_file, $create_parents = false, $file_create_mode = 0644, $folder_create_mode = 0755) {
		if (file_exists($local_file)) {
			if (is_dir($local_file)) {
				if (!$this->createRemoteFolder($remote_file, $folder_create_mode ? $folder_create_mode : 0755, $create_parents))
					return false;
				
				$files = scandir($local_file);
				
				if ($files) 
					foreach ($files as $file) 
						if ($file != "." && $file != ".." && !$this->copyLocalToRemoteFile("$local_file/$file", "$remote_file/$file", $create_parents, $file_create_mode, $folder_create_mode))
							return false;
			}
			else {
				//check if parent_dir exists and if not create it
				if ($create_parents) {
					$parent_path = dirname($remote_file);
					$info = $this->getFileInfo($parent_path);
					
					//remote file exists and is not a folder
					if ($info && empty($info["is_dir"]))
						return false;
					
					//remote file does not exists
					if (!$info && !$this->createRemoteFolder($parent_path, $folder_create_mode ? $folder_create_mode : 0755, $create_parents)) 
						return false;
				}
				
				return ssh2_scp_send($this->getConnection(), $local_file, $remote_file, $file_create_mode ? $file_create_mode : 0644); //if no parent dir, returns scp error on purpose!
			}
		}
	}
	
	//$remote_file must be a file too. Cannot be a folder!
	public function copyRemoteToLocalFile($remote_file, $local_file, $create_parents = false, $file_create_mode = 0644, $folder_create_mode = 0755) {
		$info = $this->getFileInfo($remote_file);
		
		//checks if file exists
		if ($info) {
			if (!empty($info["is_dir"])) {
				if (!is_dir($local_file))
					mkdir($local_file, $folder_create_mode, $create_parents);
				
				if (is_dir($local_file)) {
					$files = $this->scanRemoteDir($remote_file);
					$all_copied = true;
					
					if ($files) {
						$files = array_diff($files, array(".", ".."));
						
						foreach ($files as $file)
							if (!$this->copyRemoteToLocalFile("$remote_file/$file, $local_file/$file", $create_parents, $file_create_mode, $folder_create_mode))
								$all_copied = false;
					}
					
					return $all_copied;
				}
			}
			else {
				$parent = dirname($local_file);
				
				if ($create_parents && !is_dir($parent))
					mkdir($parent, $folder_create_mode, $create_parents);
				
				return ssh2_scp_recv($this->connection, $remote_file, $local_file) && chmod($local_file, $file_create_mode); //if no parent dir, returns scp error on purpose!
			}
		}
		
		return false;
	}
	
	public function renameRemoteFile($remote_file, $new_name) {
		if ($this->exists($remote_file)) {
			$new_remote_file = dirname($remote_file) . "/" . $new_name;
			$sftp = $this->sshToSftp();
			
			return ssh2_sftp_rename($sftp, $remote_file, $new_remote_file);
		}
		
		return false;
	}
	
	public function moveRemoteFile($remote_file_src, $remote_file_dst) {
		if ($this->exists($remote_file_src)) {
			$remote_file_dst_dir = dirname($remote_file_dst);
			$info = $this->getFileInfo($remote_file_dst_dir);
			
			if ($info && empty($info["is_dir"]))
				return false;
			
			if (!$info) {
				$remote_file_src_dir = dirname($remote_file_src);
				$info = $this->getFileInfo($remote_file_src_dir);
				
				if (!$this->createRemoteFolder($remote_file_dst_dir, !empty($info["mode_dec"]) ? $info["mode_dec"] : 0755, true))
					return false;
			}
			
			$sftp = $this->sshToSftp();
			
			return ssh2_sftp_rename($sftp, $remote_file_src, $remote_file_dst);
		}
		
		return false;
	}
	
	public function createRemoteFolder($remote_folder, $mode = 0777, $create_parents = false) {
		$info = $this->getFileInfo($remote_folder);
		$sftp = $this->sshToSftp();
		
		if ($info && empty($info["is_dir"]))
			ssh2_sftp_unlink($sftp, $remote_folder);
		
		$exists = isset($info["is_dir"]) ? $info["is_dir"] : null;
		$info_mode = isset($info["mode_dec"]) ? $info["mode_dec"] : null;
		
		if (!$exists) 
			$exists = ssh2_sftp_mkdir($sftp, $remote_folder, $mode, $create_parents);
		else if ($info_mode != $mode)
			$exists = ssh2_sftp_chmod($sftp, $remote_folder, $mode);
		
		return $exists && $this->isDir($remote_folder);
	}
	
	public function removeRemoteFile($remote_file) {
		$remote_file = substr($remote_file, -1) == "/" ? preg_replace("/\\/+$/", "", $remote_file) : $remote_file; //bc of symbolic links. Paths should ot contain the / at the end.
		
		//first tries to remove file via command line
		try {
			/*
			This is important, bc avoids in some cases where the code bellow returns some warning and exceptions when there is too much recursively folders... We couldn't find out why these exceptions happenned, but here are the warnings:
			- Warning: ssh2_sftp(): Unable to startup SFTP subsystem: Unable to startup channel in /var/www/html/phpframework/trunk/app/lib/org/phpframework/util/web/SSHHandler.php on line 175
			- Warning: ssh2_exec(): Unable to request a channel from remote host in /var/www/html/phpframework/trunk/app/lib/org/phpframework/util/web/SSHHandler.php on line 131
			
			The code bellow avoids the errors above:
			*/
			$this->exec("rm -rf '" . addcslashes($remote_file, "'") . "';");
		}
		catch (Exception $e) {}
		
		//then removes it via php code
		$info = $this->getFileInfo($remote_file);
		$sftp = $this->sshToSftp();
		
		if (!$info) //it means no file
			return true;
		
		if (!empty($info["is_dir"]) && empty($info["is_link"])) {
			$files = $this->scanRemoteDir($remote_file);
			$all_removed = true;
			
			if ($files) {
				$files = array_diff($files, array(".", ".."));
				
				foreach ($files as $file)
					if (!$this->removeRemoteFile("$remote_file/$file"))
						$all_removed = false;
			}
			
			return $all_removed ? ssh2_sftp_rmdir($sftp, $remote_file) : false;
		}
		
		return ssh2_sftp_unlink($sftp, $remote_file);
	}
	
	public function scanRemoteDir($remote_folder) {
		if ($this->isDir($remote_folder)) {
			$sftp = $this->sshToSftp();
			$files = array();
			
			$abs_path = ssh2_sftp_realpath($sftp, $remote_folder);
			
			if (ini_get('allow_url_fopen')) {
				$sftp_fd = intval($sftp);
				$sftp_path = "ssh2.sftp://$sftp_fd$abs_path/";
				return scandir($sftp_path);
			}
			else {
				$response = trim( $this->exec("ls -a -1 '" . addcslashes($abs_path, "'") . "';") );
				$files = $response ? explode("\n", $response) : array();
				return $files;
			}
		}
		
		return false;
	}
}
?>
