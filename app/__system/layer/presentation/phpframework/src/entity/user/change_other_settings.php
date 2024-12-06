<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

if (!empty($_POST)) {
	$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
	
	$username = isset($_POST["username"]) ? $_POST["username"] : null;
	$password = isset($_POST["password"]) ? $_POST["password"] : null;
	$openai_key = isset($_POST["openai_key"]) ? $_POST["openai_key"] : null;
	
	if (!$username || !$password) {
		$error_message = "You cannot have blank fields. Please fill all the fields with the correct values...";
	}
	else if ($UserAuthenticationHandler->isUserBlocked($username)) {
		$error_message = "You attempted to login multiple times.<br/>Your user is now blocked.";
	}
	else if (!$UserAuthenticationHandler->getUserByUsernameAndPassword($username, $password)) {
		$UserAuthenticationHandler->insertFailedLoginAttempt($username);
		
		$error_message = "Error: Invalid credentials! Please try again...";
	}
	else {
	   $UserAuthenticationHandler->resetFailedLoginAttempts($username);
	   	
		$authentication_config_file_path = $EVC->getConfigPath("authentication");
		
		if (file_exists($authentication_config_file_path)) {
			$code = file_get_contents($authentication_config_file_path);
			
			//CHANGING OPENAI KEY
			if (empty($error_message)) {
				if ($openai_key != $openai_encryption_key) {
					$code = preg_replace('/\$openai_encryption_key\s*=\s*("|\')' . preg_quote($openai_encryption_key) . '("|\')\s*;/', '$openai_encryption_key = "' . $openai_key . '";', $code);
					$code = preg_replace('/\$openai_encryption_key\s*=\s*(null|false)\s*;/i', '$openai_encryption_key = "' . $openai_key . '";', $code);
					
					if (file_put_contents($authentication_config_file_path, $code) === false)
						$error_message = "There was an error trying to change the OpenAI key. Please try again...";
				}
			}
			else
				$error_message = "There was an error trying to change the OpenAI key. Please try again...";
			
			if (empty($error_message))
				$status_message = "Settings changed successfully...";
		}
		else
			$error_message = "Config Authentication file doesn't exist. Please talk with the SysAdmin for further information...";
	}
}

$data = array(
	"username" => isset($username) ? $username : null,
	"password" => isset($password) ? $password : null,
	"openai_key" => isset($openai_key) ? $openai_key : null,
);
?>
