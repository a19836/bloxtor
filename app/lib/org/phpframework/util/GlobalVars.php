<?php
class GlobalVars {
	
	function getUserGlobalVars() {
		$globals_vars = $GLOBALS;
		$system_vars = array("GLOBALS", "_ENV", "HTTP_ENV_VARS", "_POST", "HTTP_POST_VARS", "_GET", "HTTP_GET_VARS", "_COOKIE", "HTTP_COOKIE_VARS", "_SERVER", "HTTP_SERVER_VARS", "_FILES", "HTTP_POST_FILES", "_REQUEST", "_SESSION", "HTTP_SESSION_VARS");
	
		$user_globals_vars = array();
		foreach($globals_vars as $globals_vars_key => $globals_vars_value)
			if(array_search($globals_vars_key, $system_vars) === false)
				if(!strpos($globals_vars_key, "-"))
					$user_globals_vars[$globals_vars_key] = $globals_vars_value;
			
		return $user_globals_vars;
	}
}
?>
