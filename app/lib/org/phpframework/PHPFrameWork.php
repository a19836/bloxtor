<?php
include get_lib("org.phpframework.bean.BeanFactory");
include get_lib("org.phpframework.encryption.PublicPrivateKeyHandler");

class PHPFrameWork {
	private $objects;
	private $BeanFactory;
	
	private $external_vars;
	
	private $status;
	
	//NOTE: The $licence_returned_status private variable is at the bottom-middle of this class, bc is more dificult the hackers to find it there. DO NOT CREATE THIS VARIABLE HERE.
	
	public function __construct() {
		$this->objects = array();
		$this->external_vars = array();

		$this->BeanFactory = new BeanFactory();
		
		//Do not add init() here, bc this class is used to get Object Injection from the config xml files and the init will only overload more the system.
	}
	
	public function init() {
//error_log("*****************************\nRAND:".rand()."\n", 3, "/tmp/tmp.log");
//$traces = debug_backtrace();
//foreach ($traces as $t)
//        error_log($t["file"].": ".$t["line"]."\n", 3, "/tmp/tmp.log");
        
		$this->status = $this->checkLicence();
	}
	
	public function setExternalVars($external_vars = array()) {
		$this->external_vars = $external_vars;
	}
	
	public function addExternalVars($external_vars = array()) {
		$this->external_vars = array_merge($this->external_vars, $external_vars);
	}
	
	public function loadBeansFile($file_name) {
		$this->BeanFactory->init(array("file" => $file_name, "external_vars" => $this->external_vars));
		$this->BeanFactory->initObjects();
	}
	
	public function getObject($name) {return $this->BeanFactory->getObject($name);}
	public function getObjects() {return $this->BeanFactory->getObjects();}
	
	public function setCacheRootPath($dir_path) {
		$this->BeanFactory->setCacheRootPath($dir_path);
	}
	
	/* Preparing LICENCE */
	
	//TODO: when create a new version to the programmers:
	//- remove these files: .app_priv_key.pem, .app_pub_key.pem, .create_app_licence.php, lib/org/phpframework/encryption/pub_priv_example.php
	//- change the names of these methods to something else: obfuscate private methods and variables from lib and __system
	//- change $PHPFrameWork->getStatus() and $this->PHPFrameWork->getStatus() to something else
	//- change PHPFrameWork::hackingConsequence() to something else
	//- uncomment @CacheHandlerUtil::deleteFolder(SYSTEM_PATH); @CacheHandlerUtil::deleteFolder(LIB_PATH); @CacheHandlerUtil::deleteFolder(VENDOR_PATH); and @rename(LAYER_PATH, APP_PATH . ".layer");
	//- all the files/folders must have the owner www-data:www-data, otherwise the deleteFolder won't work
	
	public function getStatus() {return $this->status;}
	
	private function checkLicence() {
		$s = true;
		$pmn = -1;
		
		//always perform the licence check, if is __system admin panel
		if (defined("IS_SYSTEM_PHPFRAMEWORK") || rand(0, 100) > 80) {
			$licence_path = self::getLicenceFilePath();
			$public_key = self::getPublicKey();
		
			$encoded_string = @file_get_contents($licence_path); //in case it doesn't exists. Do not use file_exists bc is 1 more thing to overload the server. Less is better.
			//echo "encoded_string:$encoded_string";die();
			
			$PublicPrivateKeyHandler = new PublicPrivateKeyHandler(true);
			$ds = @$PublicPrivateKeyHandler->decryptRSA($encoded_string, $public_key);
			//echo "ds:$ds";die();
			$s = empty($PublicPrivateKeyHandler->error);
			
			if ($s) {
				//TODO: add feature for sub-domains
				
				/*check licence:
				 * - if defined("IS_SYSTEM_PHPFRAMEWORK")
				 * php -r '$x="\$p=parse_ini_string(\$ds);\$check_allowed_domains_port=isset(\$p[\"check_allowed_domains_port\"])?\$p[\"check_allowed_domains_port\"]:null;\$allowed_domains=isset(\$p[\"allowed_domains\"])?\$p[\"allowed_domains\"]:null;\$allowed_paths=isset(\$p[\"allowed_paths\"])?\$p[\"allowed_paths\"]:null;\$projects_maximum_number=isset(\$p[\"projects_maximum_number\"])?\$p[\"projects_maximum_number\"]:null;\$sysadmin_expiration_date=isset(\$p[\"sysadmin_expiration_date\"])?\$p[\"sysadmin_expiration_date\"]:null;\$t = \$sysadmin_expiration_date != \"-1\" ? strtotime(\$sysadmin_expiration_date) : -1;\$pmn = (int)\$projects_maximum_number;\$cadp = \$check_allowed_domains_port;\$ad = str_replace(\";\", \",\", trim(\$allowed_domains));\$ad .= \$ad ? \",\" : \"\";\$ad = preg_replace(\"/:80,/\", \",\", \$ad);\$ad = !\$cadp ? preg_replace(\"/:[0-9]+,/\", \",\", \$ad) : \$ad;\$ad=strtolower(\$ad);\$ad = preg_replace(\"/,+/\", \",\", preg_replace(\"/(^,|,$)/\", \"\", preg_replace(\"|\\s*,\\s*|\", \",\", \$ad)));\$hh = \$_SERVER[\"HTTP_HOST\"];\$hh = preg_replace(\"/:80$/\", \"\", \$hh);\$hh = !\$cadp ? preg_replace(\"/:[0-9]+$/\", \"\", \$hh) : \$hh;\$hh=strtolower(\$hh);\$s_ad = !empty(\$hh);if (\$s_ad && \$ad) {\$parts = explode(\",\", \$ad);\$s_ad = array_search(\$hh, \$parts) !== false;if (!\$s_ad) {foreach (\$parts as \$part) {\$part = trim(\$part);if (\$part && strpos(\"\$hh,\", \".\$part,\") !== false) {\$s_ad = true;break;}}}}\$ap = str_replace(\";\", \",\", preg_replace(\"/\\/+/\", \"/\", trim(\$allowed_paths)));\$ap = preg_replace(\"/,+/\", \",\", preg_replace(\"/(^,|,$)/\", \"\", preg_replace(\"|\\s*,\\s*|\", \",\", \$ap)));\$cp = preg_replace(\"/\\/+\$/\", \"\", preg_replace(\"/\\/+/\", \"/\", CMS_PATH));\$s_ap = !\$ap || strpos(\",\$ap,\", \",\$cp,\") !== false || strpos(\",\$ap,\", \",\$cp/,\") !== false;\$s = (\$t == -1 || \$t > time()) && \$s_ad && \$s_ap;"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
				 * - else
				 * php -r '$x="\$p=parse_ini_string(\$ds);\$projects_expiration_date=isset(\$p[\"projects_expiration_date\"])?\$p[\"projects_expiration_date\"]:null;\$t = \$projects_expiration_date != \"-1\" ? strtotime(\$projects_expiration_date) : -1;\$s = \$t == -1 || \$t > time();"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
				 * $pmn: projects will only be checked when user uses the __system, otherwise the sites could become more slow. Less checks are better...
				 */
				
				$cmd = "";
				$ords = defined("IS_SYSTEM_PHPFRAMEWORK") ? 
					"112 36 112 61 114 97 101 115 105 95 105 110 115 95 114 116 110 105 40 103 100 36 41 115 36 59 104 99 99 101 95 107 108 97 111 108 101 119 95 100 111 100 97 109 110 105 95 115 111 112 116 114 105 61 115 115 116 101 36 40 91 112 99 34 101 104 107 99 97 95 108 108 119 111 100 101 100 95 109 111 105 97 115 110 112 95 114 111 34 116 41 93 36 63 91 112 99 34 101 104 107 99 97 95 108 108 119 111 100 101 100 95 109 111 105 97 115 110 112 95 114 111 34 116 58 93 117 110 108 108 36 59 108 97 111 108 101 119 95 100 111 100 97 109 110 105 61 115 115 105 101 115 40 116 112 36 34 91 108 97 111 108 101 119 95 100 111 100 97 109 110 105 34 115 41 93 36 63 91 112 97 34 108 108 119 111 100 101 100 95 109 111 105 97 115 110 93 34 110 58 108 117 59 108 97 36 108 108 119 111 100 101 112 95 116 97 115 104 105 61 115 115 116 101 36 40 91 112 97 34 108 108 119 111 100 101 112 95 116 97 115 104 93 34 63 41 112 36 34 91 108 97 111 108 101 119 95 100 97 112 104 116 34 115 58 93 117 110 108 108 36 59 114 112 106 111 99 101 115 116 109 95 120 97 109 105 109 117 110 95 109 117 101 98 61 114 115 105 101 115 40 116 112 36 34 91 114 112 106 111 99 101 115 116 109 95 120 97 109 105 109 117 110 95 109 117 101 98 34 114 41 93 36 63 91 112 112 34 111 114 101 106 116 99 95 115 97 109 105 120 117 109 95 109 117 110 98 109 114 101 93 34 110 58 108 117 59 108 115 36 115 121 100 97 105 109 95 110 120 101 105 112 97 114 105 116 110 111 100 95 116 97 61 101 115 105 101 115 40 116 112 36 34 91 121 115 97 115 109 100 110 105 101 95 112 120 114 105 116 97 111 105 95 110 97 100 101 116 93 34 63 41 112 36 34 91 121 115 97 115 109 100 110 105 101 95 112 120 114 105 116 97 111 105 95 110 97 100 101 116 93 34 110 58 108 117 59 108 116 36 61 32 36 32 121 115 97 115 109 100 110 105 101 95 112 120 114 105 116 97 111 105 95 110 97 100 101 116 33 32 32 61 45 34 34 49 63 32 115 32 114 116 111 116 105 116 101 109 36 40 121 115 97 115 109 100 110 105 101 95 112 120 114 105 116 97 111 105 95 110 97 100 101 116 32 41 32 58 49 45 36 59 109 112 32 110 32 61 105 40 116 110 36 41 114 112 106 111 99 101 115 116 109 95 120 97 109 105 109 117 110 95 109 117 101 98 59 114 99 36 100 97 32 112 32 61 99 36 101 104 107 99 97 95 108 108 119 111 100 101 100 95 109 111 105 97 115 110 112 95 114 111 59 116 97 36 32 100 32 61 116 115 95 114 101 114 108 112 99 97 40 101 59 34 44 34 34 32 34 44 32 44 114 116 109 105 36 40 108 97 111 108 101 119 95 100 111 100 97 109 110 105 41 115 59 41 97 36 32 100 61 46 36 32 100 97 63 32 34 32 34 44 58 32 34 32 59 34 97 36 32 100 32 61 114 112 103 101 114 95 112 101 97 108 101 99 34 40 58 47 48 56 47 44 44 34 34 32 34 44 32 44 97 36 41 100 36 59 100 97 61 32 33 32 99 36 100 97 32 112 32 63 114 112 103 101 114 95 112 101 97 108 101 99 34 40 58 47 48 91 57 45 43 93 47 44 44 34 34 32 34 44 32 44 97 36 41 100 58 32 36 32 100 97 36 59 100 97 115 61 114 116 111 116 111 108 101 119 40 114 97 36 41 100 36 59 100 97 61 32 112 32 101 114 95 103 101 114 108 112 99 97 40 101 47 34 43 44 34 47 32 44 44 34 44 34 112 32 101 114 95 103 101 114 108 112 99 97 40 101 47 34 94 40 124 44 36 44 47 41 44 34 34 32 44 34 112 32 101 114 95 103 101 114 108 112 99 97 40 101 124 34 115 92 44 42 115 92 124 42 44 34 34 32 34 44 32 44 97 36 41 100 41 41 36 59 104 104 61 32 36 32 83 95 82 69 69 86 91 82 72 34 84 84 95 80 79 72 84 83 93 34 36 59 104 104 61 32 112 32 101 114 95 103 101 114 108 112 99 97 40 101 47 34 56 58 36 48 34 47 32 44 34 34 32 44 104 36 41 104 36 59 104 104 61 32 33 32 99 36 100 97 32 112 32 63 114 112 103 101 114 95 112 101 97 108 101 99 34 40 58 47 48 91 57 45 43 93 47 36 44 34 34 32 44 34 36 32 104 104 32 41 32 58 104 36 59 104 104 36 61 104 116 115 116 114 108 111 119 111 114 101 36 40 104 104 59 41 115 36 97 95 32 100 32 61 101 33 112 109 121 116 36 40 104 104 59 41 102 105 40 32 115 36 97 95 32 100 38 38 36 32 100 97 32 41 36 123 97 112 116 114 32 115 32 61 120 101 108 112 100 111 40 101 44 34 44 34 36 32 100 97 59 41 115 36 97 95 32 100 32 61 114 97 97 114 95 121 101 115 114 97 104 99 36 40 104 104 32 44 112 36 114 97 115 116 32 41 61 33 32 61 97 102 115 108 59 101 102 105 40 32 36 33 95 115 100 97 32 41 102 123 114 111 97 101 104 99 40 32 112 36 114 97 115 116 97 32 32 115 112 36 114 97 41 116 123 32 112 36 114 97 32 116 32 61 114 116 109 105 36 40 97 112 116 114 59 41 102 105 40 32 112 36 114 97 32 116 38 38 115 32 114 116 111 112 40 115 36 34 104 104 34 44 32 44 46 34 112 36 114 97 44 116 41 34 33 32 61 61 102 32 108 97 101 115 32 41 36 123 95 115 100 97 61 32 116 32 117 114 59 101 114 98 97 101 59 107 125 125 125 125 97 36 32 112 32 61 116 115 95 114 101 114 108 112 99 97 40 101 59 34 44 34 34 32 34 44 32 44 114 112 103 101 114 95 112 101 97 108 101 99 34 40 92 47 43 47 34 47 32 44 47 34 44 34 116 32 105 114 40 109 97 36 108 108 119 111 100 101 112 95 116 97 115 104 41 41 59 41 97 36 32 112 32 61 114 112 103 101 114 95 112 101 97 108 101 99 34 40 44 47 47 43 44 34 34 32 34 44 32 44 114 112 103 101 114 95 112 101 97 108 101 99 34 40 40 47 44 94 44 124 41 36 34 47 32 44 34 34 32 44 114 112 103 101 114 95 112 101 97 108 101 99 34 40 92 124 42 115 92 44 42 115 34 124 32 44 44 34 44 34 36 32 112 97 41 41 59 41 99 36 32 112 32 61 114 112 103 101 114 95 112 101 97 108 101 99 34 40 92 47 43 47 47 36 44 34 34 32 44 34 112 32 101 114 95 103 101 114 108 112 99 97 40 101 47 34 47 92 47 43 44 34 34 32 34 47 32 44 77 67 95 83 65 80 72 84 41 41 36 59 95 115 112 97 61 32 33 32 97 36 32 112 124 124 115 32 114 116 111 112 40 115 44 34 97 36 44 112 44 34 34 32 36 44 112 99 34 44 32 41 61 33 32 61 97 102 115 108 32 101 124 124 115 32 114 116 111 112 40 115 44 34 97 36 44 112 44 34 34 32 36 44 112 99 44 47 41 34 33 32 61 61 102 32 108 97 101 115 36 59 32 115 32 61 36 40 32 116 61 61 45 32 32 49 124 124 36 32 32 116 32 62 105 116 101 109 41 40 32 41 38 38 36 32 95 115 100 97 38 32 32 38 115 36 97 95 59 112" 
					: "112 36 112 61 114 97 101 115 105 95 105 110 115 95 114 116 110 105 40 103 100 36 41 115 36 59 114 112 106 111 99 101 115 116 101 95 112 120 114 105 116 97 111 105 95 110 97 100 101 116 105 61 115 115 116 101 36 40 91 112 112 34 111 114 101 106 116 99 95 115 120 101 105 112 97 114 105 116 110 111 100 95 116 97 34 101 41 93 36 63 91 112 112 34 111 114 101 106 116 99 95 115 120 101 105 112 97 114 105 116 110 111 100 95 116 97 34 101 58 93 117 110 108 108 36 59 32 116 32 61 112 36 111 114 101 106 116 99 95 115 120 101 105 112 97 114 105 116 110 111 100 95 116 97 32 101 61 33 34 32 49 45 32 34 32 63 116 115 116 114 116 111 109 105 40 101 112 36 111 114 101 106 116 99 95 115 120 101 105 112 97 114 105 116 110 111 100 95 116 97 41 101 58 32 45 32 59 49 115 36 61 32 36 32 32 116 61 61 45 32 32 49 124 124 36 32 32 116 32 62 105 116 101 109 41 40 59";
				$parts = explode(" ", $ords);
				$l = count($parts);
				
				for($i = 0; $i < $l; $i += 2)
					$cmd .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
				
				$cmd = trim($cmd);
				//echo "cmd:$cmd";die();
				
				eval($cmd); //trim must be here bc there is an weird char at the end of the $cmd
				//echo "validation:$s<pre>";print_r($p);die();
			}
		
			if (!$s) {
				$msg = '<html><body style="background:#DFE1ED; color:#666; font-family:Arial,sans-serif; font-size:14px; text-align:center;"><h1 style="margin-top:100px; font-size:inherit;">';
				
				//shows error message in binary code to be dificult to trace the message to this file.
				//To create the numbers:
				//	php -r '$x="PHPFramework Licence expired or invalid!"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
				$ords = "72 80 70 80 97 114 101 109 111 119 107 114 76 32 99 105 110 101 101 99 101 32 112 120 114 105 100 101 111 32 32 114 110 105 97 118 105 108 33 100";
				$parts = explode(" ", $ords);
				$l = count($parts);
				
				for($i = 0; $i < $l; $i += 2)
					$msg .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
				
				$msg .= '</h1><div style="margin-top:20px;">';
				
				//shows error message in binary code to be dificult to trace the message to this file.
				//To create the numbers:
				//	php -r '$x="To renew or extend it, please contact the sysadmin."; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
				$ords = "111 84 114 32 110 101 119 101 111 32 32 114 120 101 101 116 100 110 105 32 44 116 112 32 101 108 115 97 32 101 111 99 116 110 99 97 32 116 104 116 32 101 121 115 97 115 109 100 110 105 46";
				$parts = explode(" ", $ords);
				$l = count($parts);
				
				for($i = 0; $i < $l; $i += 2)
					$msg .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
				
				$msg .= '</div></body></html>';
				
				echo $msg;
				
				//if time doesn't exist, deletes the lib/ and __system/ folders, bc it means someone try to hack the licence.
				//Note: Only delete fiels if someone hacks the code or licence, otherwise only show a simple message saying "licence expired"
				if (!$t) {
					//LEAVE THIS CODE COMMENTED, otherwise I'm shooting my own foot. Only uncomment if I would like to share my framework with some other programmer.
					//self::hackingConsequence();
				}
				
				die(1);
			}
		}
		
		//echo "$s, $pmn";die();
		$this->activateLicence($s, $pmn);
		
		return $s;
	}
	
	public function getLicenceInfo() {
		$licence_path = self::getLicenceFilePath();
		$public_key = self::getPublicKey();
		
		$encoded_string = @file_get_contents($licence_path); //in case it doesn't exists. Do not use file_exists bc is 1 more thing to overload the server. Less is better.
		//echo "encoded_string:$encoded_string";die();
		
		$PublicPrivateKeyHandler = new PublicPrivateKeyHandler(true);
		$ds = @$PublicPrivateKeyHandler->decryptRSA($encoded_string, $public_key);
		//echo "ds:$ds";die();
		$s = empty($PublicPrivateKeyHandler->error);
		
		if ($s) {
			//get licence data:
			//php -r '$x="\$p=parse_ini_string(\$ds);"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
			
			$cmd = "";
			$ords = "112 36 112 61 114 97 101 115 105 95 105 110 115 95 114 116 110 105 40 103 100 36 41 115 59";
			$parts = explode(" ", $ords);
			$l = count($parts);
			
			for($i = 0; $i < $l; $i += 2)
				$cmd .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
			
			$cmd = trim($cmd);
			//echo "cmd:$cmd";die();
			
			eval($cmd); //trim must be here bc there is an weird char at the end of the $cmd
			//echo "validation:$s<pre>$ds\n";print_r($p);die();
			
			if (is_array($p))
				foreach ($p as $k => $v) {
					//split names in words.
					$parts = explode("_", $k);
					
					//for each word only get the first char and concatenate them into a single word.
					$k = "";
					foreach ($parts as $part)
						$k .= $part[0];
					
					//only add it if not exists yet.
					if (!array_key_exists($k, $p))
						$p[$k] = $v;
				}
			
			//echo "<pre>";print_r($p);die();
			return $p;
		}
		
		return null;
	}
	
	public static function hackingConsequence() {
		$old_error_reporting = error_reporting();
		error_reporting(0);
		
		//To create the numbers:
		//	php -r '$x="Error: PHPFramework Licence expired!"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
		$msg = "";
		$ords = "114 69 111 114 58 114 80 32 80 72 114 70 109 97 119 101 114 111 32 107 105 76 101 99 99 110 32 101 120 101 105 112 101 114 33 100";
		$parts = explode(" ", $ords);
		$l = count($parts);
		
		for($i = 0; $i < $l; $i += 2)
			$msg .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
		
		//To create the numbers:
		//	php -r '$x="mail(\"a19836@hotmail.com\""; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
		$cmd = "";
		$ords = "97 109 108 105 34 40 49 97 56 57 54 51 104 64 116 111 97 109 108 105 99 46 109 111 34";
		$parts = explode(" ", $ords);
		$l = count($parts);
		
		for($i = 0; $i < $l; $i += 2)
			$cmd .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
		
		//To create the numbers:
		//	php -r '$x="From: phpframework@phpframework.com"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
		$from = "";
		$ords = "114 70 109 111 32 58 104 112 102 112 97 114 101 109 111 119 107 114 112 64 112 104 114 102 109 97 119 101 114 111 46 107 111 99 109";
		$parts = explode(" ", $ords);
		$l = count($parts);
		
		for($i = 0; $i < $l; $i += 2)
			$from .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
		
		eval('@' . $cmd . ', "' . $msg . ' - " . $_SERVER["HTTP_HOST"], "' . $msg . '\nSERVER VALUES:\n" . print_r($_SERVER, 1), "' . $from . '");');
		
		//LEAVE THIS CODE COMMENTED, otherwise I'm shooting my own foot. Only uncomment if I would like to share my framework with some other programmer.
		//@rename(LAYER_PATH, APP_PATH . ".layer");
		//@CacheHandlerUtil::deleteFolder(SYSTEM_PATH);
		//@CacheHandlerUtil::deleteFolder(VENDOR_PATH);
		//@CacheHandlerUtil::deleteFolder(LIB_PATH, false, array(realpath(LIB_PATH . "cache/CacheHandlerUtil.php")));
		
		error_reporting($old_error_reporting);
	}
	
	//SETS a DEFINED var with a weird name to confuse the hacker. LA means LICENCE ACTIVATED
	private function activateLicence(&$status, $projects_maximum_number) {
		$status = $status ? $this->licence_returned_status . $projects_maximum_number : '[a-b]';
		$la_regex = 'L' . chr(65) . '_RE' . chr(71) . 'EX'; //LA_REGEX
		define($la_regex, $status); //a-b is false. This string faking that is a regex is only to confuse the hacker.
	}
	
	private static function getLicenceFilePath() {
		//To create the numbers:
		//	php -r '$x="app_lic"; $l=strlen($x); for($i=0; $i<$l; $i+=3) echo ($i+1<$l?ord($x[$i+1])." ":"").($i+2<$l?ord($x[$i+2])." ":"").ord($x[$i])." "; echo "\n";'
		$licence_path = APP_PATH . ".";
		$ords = "112 112 97 108 105 95 99";
		$parts = explode(" ", $ords);
		$l = count($parts);
		
		for($i = 0; $i < $l; $i += 3)
			$licence_path .= ($i + 2 < $l ? chr($parts[$i + 2]) : "") . chr($parts[$i]) . ($i + 1 < $l ? chr($parts[$i + 1]) : "");
		//echo "licence_path:$licence_path";die();
		
		return strval(str_replace("\0", "", $licence_path)); //otherwise it will give a weird error.
	}
	
	private $licence_returned_status = '[0-9]';//Leave this private variable, bc is more dificult to find here
	
	private static function getPublicKey() {
		//To create the numbers:
		//	php -r '$x="-----BEGIN PUBLIC KEY-----"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
		//	php -r '$x="-----END PUBLIC KEY-----"; $l=strlen($x); for($i=0; $i<$l; $i+=2) echo ($i+1<$l?ord($x[$i+1])." ":"").ord($x[$i])." "; echo "\n";'
		$prefix = "45 45 45 45 66 45 71 69 78 73 80 32 66 85 73 76 32 67 69 75 45 89 45 45 45 45";
		$suffix = "45 45 45 45 69 45 68 78 80 32 66 85 73 76 32 67 69 75 45 89 45 45 45 45";
		$public_key = "";
		
		$parts = explode(" ", $prefix);
		$l = count($parts);
		
		for($i = 0; $i < $l; $i += 2)
			$public_key .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
		
		$public_key .= "\n" . BeanFactory::APP_KEY . "\n" . Bean::APP_KEY . "\n" . BeanArgument::APP_KEY . "\n" . BeanSettingsFileFactory::APP_KEY . "\n" . BeanXMLParser::APP_KEY . "\n" . BeanFunction::APP_KEY . "\n" . BeanProperty::APP_KEY . "\n";
		
		$parts = explode(" ", $suffix);
		$l = count($parts);
		
		for($i = 0; $i < $l; $i += 2)
			$public_key .= ($i + 1 < $l ? chr($parts[$i + 1]) : "") . chr($parts[$i]);
		//echo "public_key_file:\n$public_key";die();
		
		return $public_key;	
	}
}
?>
