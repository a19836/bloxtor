<?php
include_once $EVC->getUtilPath("CMSPresentationLayerUIHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$path = $_GET["path"];

$path = str_replace("../", "", $path);//for security reasons

if ($path) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
	
	if ($PEVC) {
		/*The ENT_NOQUOTES will avoid converting the &quot; to ". If this is not here and if we have some form settings with PTL code like: 
			$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '&quot;', \$var_aux_910) />"));
		...it will give a php error, because it will convert &quot; into ", which will be:
			$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '"', \$var_aux_910) />"));
		Note that " is not escaped. It should be:
			$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '\"', \$var_aux_910) />"));

		This ENT_NOQUOTES option was added in 2018-01-09, and I did not tested it for other cases
		*/
		$html = htmlspecialchars_decode( file_get_contents("php://input"), ENT_NOQUOTES);
		
		if ($html)
			$html = getProjectTemplateHtml($PEVC, $user_global_variables_file_path, $html);
	}
	else {
		launch_exception(new Exception("PEVC doesn't exists!"));
		die();
	}
}
else if (!$path) {
	launch_exception(new Exception("Undefined path!"));
	die();
}

function getProjectTemplateHtml($EVC, $user_global_variables_file_path, $html) {
	$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $EVC->getConfigPath("pre_init_config")));
	$PHPVariablesFileHandler->startUserGlobalVariables();
	
	include $EVC->getConfigPath("config");
	include_once $EVC->getUtilPath("include_text_translator_handler", $EVC->getCommonProjectName());
	
	//error_log($html . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
	//echo $html;die();
	
	//saves html to temp file to be executed as php
	$fhandle = tmpfile();
	$md = stream_get_meta_data($fhandle);
	$tmp_file_path = $md['uri'];
	
	$pieces = str_split($html, 1024 * 4);
	foreach ($pieces as $piece)
		fwrite($fhandle, $piece, strlen($piece));
	
	$error_reporting = error_reporting();
	
	//executes php
	ob_start(null, 0);
	error_reporting(0); //disable errors
	include $tmp_file_path;
	error_reporting($error_reporting);
	$html = ob_get_contents();
	ob_end_clean();
	
	//closes and removes temp file
	fclose($fhandle); 
	
	$PHPVariablesFileHandler->endUserGlobalVariables();
	
	return $html;
}
?>
