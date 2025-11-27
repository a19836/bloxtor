<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once $EVC->getUtilPath("CMSPresentationLayerHandler");
include_once $EVC->getUtilPath("CMSPresentationLayerUIHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$no_php_erros = isset($_GET["no_php_erros"]) ? $_GET["no_php_erros"] : null;
$include_jquery = isset($_GET["include_jquery"]) ? $_GET["include_jquery"] : null;
$is_edit_template = isset($_GET["is_edit_template"]) ? $_GET["is_edit_template"] : null;
$is_edit_view = isset($_GET["is_edit_view"]) ? $_GET["is_edit_view"] : null;

/*The ENT_NOQUOTES will avoid converting the &quot; to ". If this is not here and if we have some form settings with PTL code like: 
	$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '&quot;', \$var_aux_910) />"));
...it will give a php error, because it will convert &quot; into ", which will be:
	$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '"', \$var_aux_910) />"));
Note that " is not escaped. It should be:
	$form_settings = array("ptl" => array("code" => "<ptl:echo str_replace('\"', '\"', \$var_aux_910) />"));

This ENT_NOQUOTES option was added in 2018-01-09, and I did not tested it for other cases
*/
/*
The htmlspecialchars_decode is DEPRECATED bc I may want to have some code with &lt; and &gt;, so I should not convert this chars. The Onlineitframework project is using the &lt; and &gt; in the framework_documentation to show some examples how to code in this framework. If we add the htmlspecialchars_decode here, it can break that code, if it would be done directly through the edit_entity_simple. So please do NOT add the htmlspecialchars_decode method here. */
//$data = json_decode( htmlspecialchars_decode( file_get_contents("php://input"), ENT_NOQUOTES), true);
$data = json_decode( file_get_contents("php://input"), true);
//echo "<pre>";print_r($_GET);print_r($data);die();

$html_to_parse = isset($data["html_to_parse"]) ? $data["html_to_parse"] : null;
$template = !empty($data["template"]) ? $data["template"] : (isset($_GET["template"]) ? $_GET["template"] : null);
//$template_regions = isset($data["template_regions"]) ? $data["template_regions"] : null;
//$template_params = isset($data["template_params"]) ? $data["template_params"] : null;
//$template_includes = isset($data["template_includes"]) ? $data["template_includes"] : null;
//$is_external_template = isset($data["is_external_template"]) ? $data["is_external_template"] : null;
//$external_template_params = isset($data["external_template_params"]) ? $data["external_template_params"] : null;

$path = str_replace("../", "", $path);//for security reasons

if ($path && $template) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);

	if ($PEVC) {
		//echo "<textarea>".print_r($data, 1)."</textarea>";die();
		//echo "<textarea>".print_r(json_decode($_GET["data"], true), 1)."</textarea>";die();
		
		unset($data["html_to_parse"]);
		$json_data = json_encode($data);
		
		$get_page_block_simulated_html_url = "{$project_url_prefix}phpframework/presentation/get_page_block_simulated_html?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path&project=#project#&block=#block#";
		$save_page_block_simulated_html_setting_url = "{$project_url_prefix}phpframework/presentation/save_page_block_simulated_html_setting?bean_name=$bean_name&bean_file_name=$bean_file_name&project=#project#&block=#block#";
		
		$html = getProjectTemplateHtml($PEVC, $user_global_variables_file_path, $template, $data, $html_to_parse, $no_php_erros, $include_jquery, $is_edit_template, $is_edit_view, $project_url_prefix, $project_common_url_prefix, $get_page_block_simulated_html_url, $save_page_block_simulated_html_setting_url);
		//error_log($html, 3, '/var/www/html/livingroop/default/tmp/test.log');
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
else {
	launch_exception(new Exception("Undefined template!"));
	die();
}

function getProjectTemplateHtml($EVC, $user_global_variables_file_path, $template, $data, $html_to_parse, $no_php_erros, $include_jquery, $is_edit_template, $is_edit_view, $system_project_url_prefix, $system_project_common_url_prefix, $system_get_page_block_simulated_html_url, $system_save_page_block_simulated_html_setting_url) {
	//set some default vars from the index controller that might be used in the template html
	$entity = null;
	
	//get data vars
	$template_regions = isset($data["template_regions"]) ? $data["template_regions"] : null;
	$template_params = isset($data["template_params"]) ? $data["template_params"] : null;
	$template_includes = isset($data["template_includes"]) ? $data["template_includes"] : null;
	$is_external_template = isset($data["is_external_template"]) ? $data["is_external_template"] : null;
	$external_template_params = isset($data["external_template_params"]) ? $data["external_template_params"] : null;
	
	$PHPVariablesFileHandler = new PHPVariablesFileHandler(array($user_global_variables_file_path, $EVC->getConfigPath("pre_init_config")));
	$PHPVariablesFileHandler->startUserGlobalVariables();
	
	//gets template html
	if ($html_to_parse)
		$html = $html_to_parse;
	else {
		if ($is_edit_view) {
			$template_path = $EVC->getViewPath($template);
			$html = CMSFileHandler::getFileContents($template_path);
		}
		else
			$html = CMSPresentationLayerHandler::getSetTemplateCode($EVC, $is_external_template, $template, $external_template_params, $template_includes);
		
		if (!$html && !$is_external_template) {
			$template_path = $is_edit_view ? $EVC->getViewPath($template) : $EVC->getTemplatePath($template);
			
			if (!file_exists($template_path)) {
				launch_exception(new Exception("Template doesn't exists!"));
				die();
			}
		}
		
		if (!$html) {
			if ($is_edit_template || $is_edit_view) //set the default html
				$html = '<!DOCTYPE html><html><head></head><body></body></html>';
			else //set the default html with a disabled droppable, otherwise the LayoutUIEditor will create a default droppable, which will allow full edit in the body canvas, but then when we save, will not save anything, bc the edit_entity_simple saves the template-regions and not the body canvas.
				$html = '<!DOCTYPE html><html><head></head><body class="edit_entity_body_droppable_disabled">There are no template-regions to drop widgets.<br/>Please choose another template.<div class="edit_entity_droppable_disabled droppable main-droppable"></div></body></html>';
		}
	}
	
	if ($html) {
		include $EVC->getConfigPath("config");
		include_once $EVC->getUtilPath("include_text_translator_handler", $EVC->getCommonProjectName());
		
		//remove php comments from $html, bc if the html was generated from the workflow, it will create comments that will mess up with the prepareEditableTemplate method
		$html = PHPCodePrintingHandler::getCodeWithoutComments($html);
		
		//remove Sequential Logical Activities from php code
		$html = removeSequentialLogicalActivitiesPHPCode($html);
		
		if ($template_includes) {
			$html_inc = "";
			
			foreach ($template_includes as $include) 
				if (!empty($include["path"]))
					$html_inc .= "\n" . "include" . (!empty($include["once"]) ? "_once" : "") . " " . $include["path"] . ";";
			
			if ($html_inc) {
				$html_inc = "<?$html_inc\n?>\n";
				//echo $html_inc;die();
				$html = $html_inc . $html;
			}
		}
		//echo "<textarea>$html</textarea>";die();
		
		/*if (!$is_edit_template && !$is_edit_view) 
			$html = replacePHPIncludeRegionViewPathOutputWithComments($html, $suffix_id);*/
		
		//get head and body to be parsed
		$head_props = WorkFlowPresentationHandler::getHtmlTagProps($html, "head", array("get_inline_code" => true));
		$body_props = WorkFlowPresentationHandler::getHtmlTagProps($html, "body", array("get_inline_code" => true));
		$html_props = WorkFlowPresentationHandler::getHtmlTagProps($html, "html");
		$code_exists = !empty(trim($html));
		$non_standard_code = $code_exists && empty($html_props["inline_code"]) && empty($head_props["inline_code"]) && empty($body_props["inline_code"]);
		
		//if non standard html file, like the template/ajax.php, then sets the body with the html.
		if ($non_standard_code)
			$body_props["inline_code"] = $html;
		
		$head_html = isset($head_props["inline_code"]) ? $head_props["inline_code"] : null;
		$body_html = isset($body_props["inline_code"]) ? $body_props["inline_code"] : null;
		
		//find the code echo $EVC->getCMSLayer()->getCMSBlockLayer()->getBlock("xxx"); in body_html
		$hard_coded_blocks = CMSFileHandler::getHardCodedRegionsBlocks($body_html);
		
		//treats html - regions and params
		$is_empty_canvas_body_with_one_template_region = isEmptyCanvasBodyWithOneTemplateRegion($body_html, $is_edit_template, $is_edit_view);
		$suffix_id = "_xxx_" . rand(0, 10000);
		$new_body_html = prepareEditableTemplate($EVC, $body_html, $template_regions, $template_params, $suffix_id, $is_edit_template, $is_edit_view, $is_empty_canvas_body_with_one_template_region);
		//$new_body_html = prepareEditableTemplate2($EVC, $body_html, $template_regions, $template_params, $suffix_id, $is_edit_template, $is_edit_view, $is_empty_canvas_body_with_one_template_region);
		//echo $html;die();
		
		//replaces php tags with comments inside of head, so it doesn't get executed below
		$head_php_code = $html_to_parse ? getHeadHtmlPHPCode($EVC, $head_html, $template_regions, $template_params) : (
			!$is_edit_template && !$is_edit_view ? getHeadHtmlPHPCode($EVC, $head_html, $template_regions, $template_params) : "" //if edit_entity, it gets the head code also, so we can include the css files added in the page. This is very important so the html elements can be shown with the proper styling.
		);
		
		if ($is_edit_template || $is_edit_view) {
			$new_head_html = replacePHPWithComments($head_html, $suffix_id, true);
			$new_head_html = $head_php_code ? $head_php_code . $new_head_html : $new_head_html;
		}
		else if ($head_php_code)
			$new_head_html = $head_php_code . $head_html;
		else
			$new_head_html = $head_html;
		
		//replaces php tags with comments inside of body, so it doesn't get executed below
		if ($is_edit_template || $is_edit_view)
			$new_body_html = replacePHPWithComments($new_body_html, $suffix_id, false, $hard_coded_blocks);
		
		//prepare html
		/*echo "html: $html\n";
		echo "head_html: $head_html\n";
		echo "body_html: $body_html\n";
		echo "new_head_html: $new_head_html\n";
		echo "new_body_html: $new_body_html\n";
		die();*/
		
		if ($head_html)
			$html = str_replace($head_html, $new_head_html, $html);
		else if ($new_head_html)
			$html = $new_head_html . $html;
		
		if ($body_html)
			$html = str_replace($body_html, $new_body_html, $html);
		else if ($new_body_html)
			$html .= $new_body_html;
		
		//error_log($html . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		//echo "html: $html";die();
		//echo "<textarea>$html</textarea>";die();
		
		//saves html to temp file to be executed as php
		$fhandle = tmpfile();
		$md = stream_get_meta_data($fhandle);
		$tmp_file_path = isset($md['uri']) ? $md['uri'] : null;
		
		$pieces = str_split($html, 1024 * 4);
		foreach ($pieces as $piece)
			fwrite($fhandle, $piece, strlen($piece));
		
		$error_reporting = error_reporting();
		
		//for testing only...
		//echo file_get_contents($tmp_file_path);
		
		//executes php
		ob_start(null, 0);
		
		if ($no_php_erros)
			error_reporting(0); //disable errors
		
		include $tmp_file_path;
			
		if ($no_php_erros)
			error_reporting($error_reporting);
		
		$html = ob_get_contents();
		ob_end_clean();
		
		//closes and removes temp file
		fclose($fhandle); 
		
		//replaces the php comments with real php code
		if ($is_edit_template || $is_edit_view)
			$html = replaceCommentsWithPHP($html, $suffix_id);
		
		//adds default css and js
		$extra = '';
		
		if (stripos($html, "jquery") === false || $include_jquery)
			$extra .= '<script class="layout-ui-editor-reserved" type="text/javascript" src="' . $system_project_common_url_prefix . 'vendor/jquery/js/jquery-1.8.1.min.js"></script>';
		else
			$extra .= '
		<script class="layout-ui-editor-reserved">
		if(!window.jQuery) {
		   var win = this;
		   var doc = win.document;
		   doc.write(\'<\' + \'script class="layout-ui-editor-reserved" type="text/javascript" src="' . $system_project_common_url_prefix . 'vendor/jquery/js/jquery-1.8.1.min.js"><\' + \'/script>\');
		   
		   //cannot use the location anymore, bc the document.location is now the same than the parent.location because we are using an ajax request to get this file.
		   /*if (win.location != win.parent.location) {
		     var url = "" + doc.location;
		     url += (url.indexOf("?") != -1 ? "&" : "?") + "include_jquery=1";
		     document.location = url;
		   }
		   else
		   	doc.write(\'<\' + \'script class="layout-ui-editor-reserved" type="text/javascript" src="' . $system_project_common_url_prefix . 'vendor/jquery/js/jquery-1.8.1.min.js"><\' + \'/script>\');*/
		}
		</script>';
		
		$extra .= '
		<!--layout-ui-editor-reserved: Global script with some native javascript functions -->
		<script class="layout-ui-editor-reserved" src="' . $system_project_common_url_prefix . 'js/global.js"></script>
		
		<!--layout-ui-editor-reserved: Layout UI Editor - Add ACE-Editor -->
		<script class="layout-ui-editor-reserved">
	   		var head = document.querySelector("head");
	   		var head_old_nodes = Array.from(head.childNodes);
	   	</script>
		<script class="layout-ui-editor-reserved" type="text/javascript" src="' . $system_project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ace.js"></script>
		<script class="layout-ui-editor-reserved" type="text/javascript" src="' . $system_project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/ext-language_tools.js"></script>
		' . getAceEditorDynamicHtml($system_project_common_url_prefix) . '
	   	<script class="layout-ui-editor-reserved">
	   		//Bc the ace editor adds some styles to the head, we need to have this code here
	   		var head_new_nodes = Array.from(head.childNodes);
	   		var length = head_new_nodes.length;
	   		
	   		for (var i = 0; i < length; i++) {
	   			var node = head_new_nodes[i];
	   			
	   			if (node.nodeType == Node.ELEMENT_NODE && head_old_nodes.indexOf(node) == -1)
	   				node.classList.add("layout-ui-editor-reserved");
	   		}
	   	</script>
	   	
		<!--layout-ui-editor-reserved: Add Fontawsome Icons CSS -->
		<link class="layout-ui-editor-reserved" rel="stylesheet" href="' . $system_project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">
		
		<!--layout-ui-editor-reserved: Add Icons CSS -->
		<link class="layout-ui-editor-reserved" rel="stylesheet" href="' . $system_project_url_prefix . 'css/icons.css">
		
		<!--layout-ui-editor-reserved: Add Simple Template Layout CSS and JS Files  -->
		<link class="layout-ui-editor-reserved" rel="stylesheet" href="' . $system_project_url_prefix . 'css/presentation/edit_simple_template_layout.css" type="text/css" charset="utf-8" />
		<script class="layout-ui-editor-reserved" language="javascript" type="text/javascript" src="' . $system_project_url_prefix . 'js/presentation/edit_simple_template_layout.js"></script>
		
		<script class="layout-ui-editor-reserved">
			var selected_project_id = \'' . $EVC->getpresentationLayer()->getSelectedPresentationId() . '\';
			var system_get_page_block_simulated_html_data = ' . json_encode($data) . ';
			var system_get_page_block_simulated_html_url = \'' . $system_get_page_block_simulated_html_url . '\';
			var system_save_page_block_simulated_html_setting_url = \'' . $system_save_page_block_simulated_html_setting_url . '\';
		</script>';
		
		//add "droppable main-droppable" to body class. Only if is a standard html file with the body tag.
		if (($is_edit_template || $is_edit_view) && preg_match("/<body([^>]*)>/i", $html, $m, PREG_OFFSET_CAPTURE)) {
			$str = $m[0][0];
			$separator = null;
			
			if (preg_match("/(\s)class(\s*)=(\s*)\"([^\"]*)\"/i", $str, $m2, PREG_OFFSET_CAPTURE)) //if is: <body class="...">
				$separator = '"';
			else if (preg_match("/(\s)class(\s*)=(\s*)'([^']*)'/i", $str, $m2, PREG_OFFSET_CAPTURE)) //if is: <body class='...'>
				$separator = "'";
			else if (preg_match("/(\s)class(\s*)=(\s*)([0-9a-z_\-]+)/i", $str, $m2, PREG_OFFSET_CAPTURE)) //if is: <body class=...>. No quotes
				$separator = "";
			
			if ($m2 && $m2[0]) {
				$class_str = $m2[0][0];
				$class_replacement = substr($class_str, 0, -1) . " droppable main-droppable" . $separator;
				
				if (!$separator)
					$class_replacement = preg_replace("/(\s)class(\s*)=(\s*)([0-9a-z_\-]+)/i", '$1class$2=$3"$4 droppable main-droppable"', $class_str);
				
				$replacement = str_replace($class_str, $class_replacement, $str);
			}
			else //if is: <body>
				$replacement = substr($str, 0, -1) . ' class="droppable main-droppable">';
			
			$html = str_replace($str, $replacement, $html);
		}
		//if there is no droppable in html, set the default html with a disabled droppable, otherwise the LayoutUIEditor will create a default droppable, which will allow full edit in the body canvas, but then when we save, will not save anything, bc the edit_entity_simple saves the template-regions and not the body canvas.
		else if (!$is_edit_template && !$is_edit_view && !preg_match('/\sclass\s*=\s*"([^"]+\s|\s|)droppable(\s[^"]+|\s|)"/i', $html)) {
			if (preg_match("/<\/body([^>]*)>/i", $html))
				$html = preg_replace("/(<\/body)/i", '<div class="edit_entity_droppable_disabled droppable main-droppable"></div>$1', $html);
			else
				$html .= '<div class="edit_entity_droppable_disabled droppable main-droppable"></div>';
		}
		
		//put the $extra code at the end of the body in order to be the last thing to load. Note that this has the jquery dependency
		if (preg_match("/<\/body([^>]*)>/i", $html, $m, PREG_OFFSET_CAPTURE)) 
			$html = str_ireplace($m[0][0], $extra. $m[0][0], $html);
		//else if (preg_match("/<\/head([^>]*)>/i", $html, $m, PREG_OFFSET_CAPTURE)) //Do not put at the head, bc if the jquey is loaded at the end of the body, the tinymce will give an error bc it depends of the jquery that wasn't loaded yet!
		//	$html = str_ireplace($m[0][0], $extra. $m[0][0], $html);
		else //if non standard html file, like the template/ajax.php, then sets the body with the html.
			$html .= $extra;
		
		//prepare head html by adding a script code that avoids showing errors, before anything runs in the html. Note that although the edit_simple_template_layout.js already contains the window.onerror already defined, it will only be loaded after some html runs first, and if this html contains javascript errors, they won't be cached by the edit_simple_template_layout.js. So we need to add the following lines of code to run before anything.
		//show an alert message saying that the html may not be loaded correctly and this files should be edited via code Layout.
		$catch_js_errors_code = '<script class="layout-ui-editor-reserved">
window.onerror = function(msg, url, line, col, error) {
	' . ($is_edit_template || $is_edit_view ? 'if (window.parent && window.parent.$) window.parent.$(".template_loaded_with_errors").removeClass("hidden");' : '') . '
	
	if (console && console.log)
		console.log("[edit_page_and_template.js:reloadLayoutIframeFromSettings()] Layout Iframe error:" + "\n- message: " + msg + "\n- line " + line + "\n- column " + col + "\n- url: " + url + "\n- error: " + error);
	
	return true; //return true, avoids the error to be shown and other scripts to stop.
};
</script>';
		
		if (preg_match("/<head([^>]*)>/i", $html, $m, PREG_OFFSET_CAPTURE))
			$html = str_ireplace($m[0][0], $m[0][0] . $catch_js_errors_code, $html);
		else //if non standard html file, like the template/ajax.php, then sets the body with the html.
			$html = $catch_js_errors_code . $html;
		
		//remove all header in case exists any header with Location
		header_remove(); 
		
		if (substr("" . http_response_code(), 0, 1) != "2") //if is not a 200 code or 2 hundred and something...
			http_response_code(200); //header_remove does not remove the HTTP headers, so we need to overwrite this header in case it was changed by the template! A real example is when we call a block with wordpress module and the wordpress returns a page not found with the 404 Not Found code. So we need to overwrite this header with: 200 OK.
	}
	
	$PHPVariablesFileHandler->endUserGlobalVariables();
	
	//error_log($html, 3, '/var/www/html/livingroop/default/tmp/test.log');
	return $html;
}
/*
function replacePHPIncludeRegionViewPathOutputWithComments($html, $suffix_id) {
	//include $EVC->getCMSLayer()->getCMSTemplateLayer()->includeRegionViewPathOutput("Content", "echostr");
	preg_match_all('/(include_once|include|require_once|require)(\s*)\$(\w+\(?\)?\->)+(\s*)includeRegionViewPathOutput(\s*)\((\s*)([^,]*)(\s*),(\s*)([^\),]*)(\s*),?(\s*)([^\)]*)(\s*)\)([^;]*);/u', $html, $matches, PREG_PATTERN_ORDER);
	print_r($matches);
	
	if ($matches)
		for ($i = 0, $t = count($matches[0]); $i < $t; $i++) {
			$code = $matches[0][$i];
			$region = $matches[7][$i];
			$html = str_replace($code, '$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionHtml(' . $region . ', \'<? ' . addcslashes($code, "'") . ' ?>\');', $html);
		}
	
	echo "JPLPINTO;".$html;die();
	return $html;
}*/

function replaceCommentsWithPHP($html, $suffix_id) {
	//replace dummy php comments, with real php code, so it be parsed by the LayoutUIEditor
	return str_replace('<!--?' . $suffix_id, '<?', str_replace($suffix_id . '?-->', '?>', $html));
}

//replaces php tags with comments, so it doesn't get executed below - only if is edit_template
function replacePHPWithComments($html, $suffix_id, $is_head_html = false, $hard_coded_blocks = false) {
	//search for all php tags and add a comment below with the same code
	$html_chars = TextSanitizer::mbStrSplit($html);
	$l = count($html_chars);
	$new_text = "";
	$extra_text = "";
	
	for ($i = 0; $i < $l; $i++) {
		$c = $html_chars[$i];
		
		if ($c == "<") {
			$next_char = $html_chars[$i + 1];
			
			if ($next_char == "?") { //start of php tag
				$dqo = $sqo = false;
				$sub_text = $c . $next_char;
				
				for ($j = $i + 2; $j < $l; $j++) {
					$sub_c = $html_chars[$j];
					
					if ($sub_c == '"' && !$sqo && !TextSanitizer::isMBCharEscaped($html, $j, $html_chars))
						$dqo = !$dqo;
					else if ($sub_c == "'" && !$dqo && !TextSanitizer::isMBCharEscaped($html, $j, $html_chars))
						$sqo = !$sqo;
					else if ($sub_c == "?" && $html_chars[$j + 1] == ">" && !$dqo && !$sqo) { //end of php tab
						$sub_text .= $sub_c . $html_chars[$j + 1];
						break;
					}
					
					$sub_text .= $sub_c;
				}
				
				$i = $j + 1;
				$is_template_region = strpos($sub_text, '<div class="template_region"') !== false;
				$is_render_region = preg_match('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->renderRegion\(/u', $sub_text);
				$is_get_param = preg_match('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->getParam\(/u', $sub_text);
				$is_auto_generated = strpos($sub_text, ' . "' . $suffix_id . '"') !== false;
				
				//If is some php code, that is not a template_region, converts php code to html comment
				if (!$is_template_region && 
					!$is_auto_generated && 
					!$is_render_region && 
					!$is_get_param
				) {
					//prepare php->comment to be later converted to a template-widget by the LayoutUIEditor
					if ($is_head_html) 
						$c = $sub_text; //add php code so it executes the code. This will show the template region and correspodennt blocks.
					else { //if is body
						$sub_text = replacePHPWithCommentsWithHardCodedBlocks($sub_text, $suffix_id, $hard_coded_blocks);
						
						$c = str_replace('<?', '<!--?' . $suffix_id, str_replace('?>', $suffix_id . '?-->', $sub_text)); //this will later be converted to php code by the replaceCommentsWithPHP method.
					}
				}
				else if (!$is_auto_generated && ($is_render_region || $is_get_param)) { //If is is_auto_generated and is renderRedion or getParam, converts php to comment but still leave the php code to be execute (only if inside the head). When we are in the head html, the render region must be a comment so we can detect the region in the code, in the edit_template_simple and edit_entity_simple. If inside of body, only convert the php code to comment, so it doesn't get executed. By default the body should detect the renderRegion and getParam, so there shouldn't be any renderRedion or getParam inside the body.
					//prepare php->comment to be later converted to a template-widget by the LayoutUIEditor
					if ($is_head_html) {
						$c = $sub_text; //add php code so it executes the code in the head
						
						//must be added to the extra_text bc if a region or a param is inside of a html attribute, it will mess the html inside of the head tag. So we add it to the end of the head tag, bc we are only useing this comments to keep track of the regions and params that exists in the file. If the user makes some changes to any of this regions or params, the system will call again the edit_simple_template_layout.php and this changes will be updated via PHP.
						$extra_text .= "\n\t" . str_replace('<?', '<!--?', str_replace('?>', '?-->', $sub_text)); //Do not add prefix, so the replaceCommentsWithPHP method dont convert this line to code, otherwise the the browser will add it to the body by default in the LayoutUIEditor Layout tab. ;
					}
					else //if is body
						$c = str_replace('<?', '<!--?' . $suffix_id, str_replace('?>', $suffix_id . '?-->', $sub_text)); //this will later be converted to php code by the replaceCommentsWithPHP method.
				}
				else //if is auto generated template_region or param
					$c = $sub_text; //add php code so it executes the code. This will show the template region and correspodennt blocks.
			}
		}
		
		$new_text .= $c;
	}
	
	return $new_text . $extra_text;
}

function replacePHPWithCommentsWithHardCodedBlocks($sub_text, $suffix_id, $hard_coded_blocks) {
	//find the code echo $EVC->getCMSLayer()->getCMSBlockLayer()->getBlock("echostr_2");
	if ($hard_coded_blocks)
		foreach ($hard_coded_blocks as $b) {
			$match = isset($b["match"]) ? $b["match"] : null;
			
			if ($match && strpos($sub_text, $match) !== false) {
				$type = isset($b["type"]) ? $b["type"] : null;
				$block = isset($b["block"]) ? $b["block"] : null;
				$block_type = isset($b["block_type"]) ? $b["block_type"] : null;
				$block_project = isset($b["block_project"]) ? $b["block_project"] : null;
				$block_project_type = isset($b["block_project_type"]) ? $b["block_project_type"] : null;
				
				$block_str = PHPUICodeExpressionHandler::getArgumentCode($block, $block_type);
				$block_project_str = PHPUICodeExpressionHandler::getArgumentCode($block_project, $block_project_type);
				
				//delete previous include of the block path and empty block_local_variables
				if ($type == 4) {
					$sub_text = preg_replace('/include \$EVC->getViewPath\(\s*' . preg_quote($block_str, "/") . ($block_project_str ? "\s*,\s*" . preg_quote($block_project_str, "/") : "") . '\s*\);/', '', $sub_text);
					
					if (!$block_project)
						$sub_text = preg_replace('/include \$EVC->getViewPath\(\s*' . preg_quote($block_str, "/") . '\s*\);/', '', $sub_text);
				}
				else {
					$sub_text = preg_replace('/\$block_local_variables\s*=\s*array\s*\(\s*\)\s*;/', '', $sub_text);
					
					$sub_text = preg_replace('/include \$EVC->getBlockPath\(\s*' . preg_quote($block_str, "/") . ($block_project_str ? "\s*,\s*" . preg_quote($block_project_str, "/") : "") . '\s*\);/', '', $sub_text);
					
					if (!$block_project)
						$sub_text = preg_replace('/include \$EVC->getBlockPath\(\s*' . preg_quote($block_str, "/") . '\s*\);/', '', $sub_text);
				}
				
				/* Separates the getBlock/getView code from the rest of the php code, replacing it with "".
				   Then remove the following code:
					echo\s*""\s*;
					<\?(php|=|)\s*\?>
				*/
				$replacement = "#hard_coded_html_$suffix_id#";
				$prefix_code = '""; ?>';
				$suffix_code = '<? echo ""';
				$sub_text = str_replace($match, $prefix_code . $replacement . $suffix_code, $sub_text);
				$sub_text = preg_replace('/\s*echo\s*""\s*;/', "", $sub_text); //remove added prefix code
				$sub_text = preg_replace('/<?\s*echo\s*""\s*\?>/', "", $sub_text); //remove added suffix code
				$sub_text = preg_replace('/\s*\?>/', " ?>", $sub_text); //remove multiple last spaces from php code
				$sub_text = preg_replace('/<\?(php|=|)\s*\?>/', "", $sub_text); //remove empty php code
				
				if ($type == 4) 
					$block_html = '
<div class="template_view_item">
	<div class="template_view_item_header">
		Call view <span class="view_name">' . $block_str . '</span><span class="view_project"> in "<span>' . ($block_project ? $block_project_str : "") . '</span>" project.</span>
	</div>
	
	<input class="view hidden" type="text" value="' . $block . '" />
	<select class="region_view_type hidden">
		<option value>default</option>
		<option' . ($block_type == "string" ? " selected" : "") . '>string</option>
		<option' . ($block_type == "variable" ? " selected" : "") . '>variable</option>
	</select>
</div>';
				else
					$block_html = '
<div class="template_block_item">
	<div class="template_block_item_header">
		Call block <span class="block_name">' . $block_str . '</span><span class="block_project"> in "<span>' . ($block_project ? $block_project_str : "") . '</span>" project.</span>
	</div>
	
	<input class="block hidden" type="text" value="' . $block . '" />
	<select class="region_block_type hidden">
		<option value>default</option>
		<option' . ($block_type == "string" ? " selected" : "") . '>string</option>
		<option' . ($block_type == "variable" ? " selected" : "") . '>variable</option>
	</select>
	<div class="block_simulated_html"></div>
</div>';
				
				$sub_text = str_replace($replacement, $block_html, $sub_text);
				//echo "sub_text:$sub_text\n\n";
			}
		}
	
	return $sub_text;
}

function removeSequentialLogicalActivitiesPHPCode($html) {
	$html_chars = TextSanitizer::mbStrSplit($html);
	$l = count($html_chars);
	$new_html = $html;
	
	for ($i = 0; $i < $l; $i++) {
		$c = $html_chars[$i];
		
		if ($c == "<") {
			$next_char = $i + 1 < $l ? $html_chars[$i + 1] : null;
			
			if ($next_char == "?") { //start of php tag
				$dqo = $sqo = false;
				$php_code = $c . $next_char;
				
				for ($j = $i + 2; $j < $l; $j++) {
					$sub_c = $html_chars[$j];
					
					if ($sub_c == '"' && !$sqo && !TextSanitizer::isMBCharEscaped($html, $j, $html_chars))
						$dqo = !$dqo;
					else if ($sub_c == "'" && !$dqo && !TextSanitizer::isMBCharEscaped($html, $j, $html_chars))
						$sqo = !$sqo;
					else if ($sub_c == "?" && $j + 1 < $l && $html_chars[$j + 1] == ">" && !$dqo && !$sqo) { //end of php tab
						$php_code .= $sub_c . $html_chars[$j + 1];
						break;
					}
					
					$php_code .= $sub_c;
				}
				
				$php_code_clean = preg_replace("/^<\?(=|php|)/i", "", $php_code); //remove open php tag
				$php_code_clean = preg_replace("/\?>$/i", "", $php_code_clean); //remove close php tag
				$old_php_code_clean = $php_code_clean;
				
				while (preg_match("/\s*\->\s*addSequentialLogicalActivities\s*\(/", $php_code_clean, $matches, PREG_OFFSET_CAPTURE) && $matches && $matches[0]) {
					$str = $matches[0][0];
					$offset = $matches[0][1];
					$start_offset = $end_offset = null;
					
					$php_code_clean_chars = TextSanitizer::mbStrSplit($php_code_clean);
					$php_code_clean_l = count($php_code_clean_chars);
					$dqo = $sqo = false;
					
					for ($j = $offset - 1; $j >= 0; $j--) {
						$c = $php_code_clean_chars[$j];
						
						if ($c == '"' && !$sqo && !TextSanitizer::isMBCharEscaped($php_code_clean, $j, $php_code_clean_chars))
							$dqo = !$dqo;
						else if ($c == "'" && !$dqo && !TextSanitizer::isMBCharEscaped($php_code_clean, $j, $php_code_clean_chars))
							$sqo = !$sqo;
						else if ($c == ";" && !$dqo && !$sqo) { //end of php statement
							$start_offset = $j + 1;
							break;
						}
					}
					
					if (!$start_offset)
						$start_offset = 0;
					
					$dqo = $sqo = false;
					
					for ($j = $offset + 1; $j < $php_code_clean_l; $j++) {
						$c = $php_code_clean_chars[$j];
						
						if ($c == '"' && !$sqo && !TextSanitizer::isMBCharEscaped($php_code_clean, $j, $php_code_clean_chars))
							$dqo = !$dqo;
						else if ($c == "'" && !$dqo && !TextSanitizer::isMBCharEscaped($php_code_clean, $j, $php_code_clean_chars))
							$sqo = !$sqo;
						else if ($c == ";" && !$dqo && !$sqo) { //end of php statement
							$end_offset = $j + 1;
							break;
						}
					}
					
					if (!$end_offset)
						$end_offset = $php_code_clean_l;
					
					$php_code_clean = substr($php_code_clean, 0, $start_offset) . substr($php_code_clean, $end_offset);
				}
				
				if (trim($php_code_clean)) //replace php code with new code without the addSequentialLogicalActivities
					$new_php_code = str_replace($old_php_code_clean, $php_code_clean, $php_code);
				else //remove code bc this php code is empty and only contains open and close php tags
					$new_php_code = "";
				
				$new_html = str_replace($php_code, $new_php_code, $new_html);
			}
		}
	}
	
	//echo $new_html;die();
	return $new_html;
}

function getHeadHtmlPHPCode($EVC, $head_html, $template_regions, $template_params) {
	$P = $EVC->getpresentationLayer();
	$selected_project_id = $P->getSelectedPresentationId();
	$default_extension = $P->getPresentationFileExtension();
	$regions = array();
	$params = array();
	
	//prepare regions
	if ($template_regions) {
		$available_regions_list = CMSPresentationLayerHandler::getAvailableRegionsListFromCode($head_html, $selected_project_id);
		//print_r($available_regions_list);print_r($template_regions);die();
		
		$t = count($available_regions_list);
		for ($i = 0; $i < $t; $i++) {
			$region = $available_regions_list[$i];
			$region_blocks = isset($template_regions[$region]) ? $template_regions[$region] : null;
			
			if ($region_blocks) {
				$t1 = count($region_blocks);
				for ($j = 0; $j < $t1; $j++) {
					$region_block = $region_blocks[$j];
					$region = isset($region_block[0]) ? $region_block[0] : null;
					$block = isset($region_block[1]) ? $region_block[1] : null;
					$proj = isset($region_block[2]) ? $region_block[2] : null;
					$type = isset($region_block[3]) ? $region_block[3] : null;
					$is_block = $type == 2 || $type == 3;
					
					$is_valid = !$is_block || !preg_match("/(^(\"|')?|\/)validate([^\/])*$/", $block); //ignore the blocks that are "validate" blocks, because this blocks usually redirect the page to another page. And the edit_simple_template_layout.php should show the current page independent if is validated or not.
					
					if ($is_valid)
						$regions[] = array(
							"type" => $type,
							"region" => $region,
							"region_type" => "",
							"block" => $block,
							"block_type" => "",
							"block_project" => $proj,
							"block_project_type" => "",
						);
				}
			}
		}
		//print_r($regions);die();
	}
	
	//prepare params
	if ($template_params) {
		$params_list = CMSPresentationLayerHandler::getAvailableTemplateParamsListFromCode($head_html);
		$available_params_list = isset($params_list[0]) ? $params_list[0] : null;
		//print_r($available_params_list);print_r($template_params);die();
		
		$t = count($available_params_list);
		for ($i = 0; $i < $t; $i++) {
			$param_name = $available_params_list[$i];
			
			if (array_key_exists($param_name, $template_params)) {
				$param_value = isset($template_params[$param_name]) ? $template_params[$param_name] : null;
				
				$params[] = array(
					"param" => $param_name,
					"param_type" => "",
					"value" => $param_value,
					"value_type" => "",
				);
			}
		}
		//print_r($params);die();
	}
	
	//prepare php code
	$tp_code = CMSPresentationLayerHandler::createCMSLayerCodeForTemplateParams($params);
	$rb_code = CMSPresentationLayerHandler::createCMSLayerCodeForRegionsBlocks($selected_project_id, $default_extension, $regions);
	
	if (trim($tp_code) || trim($rb_code)) {
		$php_code = "<?";
		$php_code .= trim($tp_code) ? "\n" . $tp_code : "";
		$php_code .= trim($rb_code) ? "\n" . $rb_code : "";
		$php_code .= "?>";
		//echo $php_code;die();
		
		return $php_code;
	}
	
	return "";
}

//based in setting the region-blocks and params
function prepareEditableTemplate($EVC, $html, $template_regions, $template_params, $suffix_id, $is_edit_template, $is_edit_view, $is_empty_canvas_body_with_one_template_region = false) {
	//treats html - regions
	$regions_blocks_list = array();
	if ($template_regions)
		foreach ($template_regions as $region_name => $region_blocks)
			if ($region_blocks)
				$regions_blocks_list = array_merge($regions_blocks_list, $region_blocks);
	
	$selected_project_id = $EVC->getPresentationLayer()->getSelectedPresentationId();
	$available_blocks_list = CMSPresentationLayerHandler::initBlocksListThroughRegionBlocks($EVC, $regions_blocks_list, $selected_project_id);
	
	$php_code = "";
	//print_r($template_regions);die();
	
	//delete previous regions, set params and sequential-logical-activities
	$html = preg_replace('/\$block_local_variables\s*=\s*\$region_block_local_variables([^;]+);/', '', $html);
	$html = preg_replace('/\$region_block_local_variables\[[^\]]+\]\[[^\]]+\]\[[^\]]+\]\s*=([^;]+);/', '', $html);
	$html = preg_replace('/\$region_block_local_variables([^;]+);/', '', $html);
	$html = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSJoinPointLayer\(\)->resetRegionBlockJoinPoints\([^\)]*\);/', '', $html);
	$html = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->addRegionBlock\([^\)]*\);/', '', $html);
	$html = preg_replace('/include \$EVC->getBlockPath\([^\)]*\);/', '', $html);
	$html = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->includeRegionBlockPathOutput\([^\)]*\);/', '', $html);
	
	$html = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->setParam\([^\)]*\);/', '', $html);
	
	$html = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->addRegionView\([^\)]*\);/', '', $html);
	$html = preg_replace('/include \$EVC->getViewPath\([^\)]*\);/', '', $html);
	$html = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->includeRegionViewPathOutput\([^\)]*\);/', '', $html);
	
	//error_log($html . "\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
	
	if ($template_regions)
		foreach ($template_regions as $region_name => $region_blocks) {
			$region_blocks_html = getProjectTemplateRegionBlocksHtml($region_name, $region_blocks, $available_blocks_list, $is_edit_template, $is_edit_view, $is_empty_canvas_body_with_one_template_region);
			
			$new_region_name = $region_name . ' . "' . $suffix_id . '"'; // change the name of the regions because if there is any block set for this region already in the html, it will not show it.
			$php_code .= "\n" . '$EVC->getCMSLayer()->getCMSTemplateLayer()->addRegionHtml(' . $new_region_name . ', \'' . addcslashes($region_blocks_html, "'") . '\');';
			
			$aux = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->renderRegion\(' . preg_quote($region_name, "/") . '\)/u', '$EVC->getCMSLayer()->getCMSTemplateLayer()->renderRegion(' . $new_region_name . ')', $html); //'/u' means converts to unicode.
			
			if ($aux !== null)
				$html = $aux;
		}
	
	//treats html - params but only if is not edit_template
	if (!$is_edit_template && !$is_edit_view && $template_params)
		foreach ($template_params as $param_name => $param_value) {
			$new_param_name = $param_name . ' . "' . $suffix_id . '"'; // change the name of the params because if there is any param set already in the html, it will not show it.
			$param_value = strlen($param_value) ? $param_value : "''";
			
			$php_code .= "\n" . '$EVC->getCMSLayer()->getCMSTemplateLayer()->setParam(' . $new_param_name . ', ' . $param_value . ');';
			
			$aux = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->getParam\(' . preg_quote($param_name, "/") . '\)/u', '$EVC->getCMSLayer()->getCMSTemplateLayer()->getParam(' . $new_param_name . ')', $html); //'/u' means converts to unicode.
			
			if ($aux !== null)
				$html = $aux;
		}
	
	if ($php_code)
		$php_code = "<?$php_code\n?>";
	
	return $php_code . $html;
}
//based in regex
function prepareEditableTemplate2($EVC, $html, $template_regions, $template_params, $suffix_id, $is_edit_template, $is_edit_view, $is_empty_canvas_body_with_one_template_region = false) {
	//treats html - regions
	$selected_project_id = $EVC->getPresentationLayer()->getSelectedPresentationId();
	$available_blocks_list = CMSPresentationLayerHandler::getAvailableBlocksList($EVC, $selected_project_id);
	
	if ($template_regions)
		foreach ($template_regions as $k => $v)
			$html = prepareProjectTemplateRegionHtml($k, $v, $html, $available_blocks_list, $is_edit_template, $is_edit_view, $is_empty_canvas_body_with_one_template_region);
	
	//treats html - params but only if is not edit_template
	if (!$is_edit_template && !$is_edit_view && $template_params)
		foreach ($template_params as $k => $v)
			$html = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->getParam\(' . preg_quote($k, "/") . '\)/u', '"Param: ' . addcslashes($k, '"') . '"', $html); //'/u' means converts to unicode.
	
	return $html;
}

function prepareProjectTemplateRegionHtml($region_name, $region_blocks, $html, $available_blocks_list, $is_edit_template, $is_edit_view, $is_empty_canvas_body_with_one_template_region = false) {
	$region_blocks_html = getProjectTemplateRegionBlocksHtml($region_name, $region_blocks, $available_blocks_list, $is_edit_template, $is_edit_view, $is_empty_canvas_body_with_one_template_region);
	
	//Note that $region_name already contains quotes.
	$pos = strpos($html, '$EVC->getCMSLayer()->getCMSTemplateLayer()->renderRegion(' . $region_name . ')');
	$pos = strpos($html, '?>', $pos) + 2;
	$html = substr($html, 0, $pos) . $region_blocks_html . substr($html, $pos);
	
	$html = preg_replace('/\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->renderRegion\(' . preg_quote($region_name, "/") . '\)/u', '""', $html); //'/u' means converts to unicode.
	
	return $html;
}

function isEmptyCanvasBodyWithOneTemplateRegion($html, $is_edit_template, $is_edit_view) {
	if (!$is_edit_template && !$is_edit_view) {
		$regex_1 = '/\s*<\?((php\s+|\s*)?echo\s+|=\s*)\$EVC->getCMSLayer\(\)->getCMSTemplateLayer\(\)->renderRegion\("([^"]*)"\);\s*\?>\s*/';
		$regex_2 = str_replace('"', "'", $regex_1);
		preg_match_all($regex_1, $html, $matches_1, PREG_PATTERN_ORDER);
		preg_match_all($regex_2, $html, $matches_2, PREG_PATTERN_ORDER);
		
		$regex_count = ($matches_1 ? count($matches_1[0]) : 0) + ($matches_2 ? count($matches_2[0]) : 0);
		
		if ($regex_count == 1) {
			$html = preg_replace($regex_1, "", $html);
			$html = preg_replace($regex_2, "", $html);
			$html = trim($html);
			
			return empty($html);
		}
	}
	
	return false;
}

function getProjectTemplateRegionBlocksHtml($region_name, $region_blocks, $available_blocks_list, $is_edit_template, $is_edit_view, $is_empty_canvas_body_with_one_template_region = false) {
	$region_html = "";
	$rb_indexes = array();
	
	//prepare region html
	if ($region_blocks && is_array($region_blocks)) {
		foreach ($region_blocks as $region_block) {
			$block = isset($region_block[1]) ? $region_block[1] : null;
			
			if ($block) {
				$proj = isset($region_block[2]) ? $region_block[2] : null;
				$type = isset($region_block[3]) ? $region_block[3] : null;
				$rb_index = isset($region_block[4]) ? $region_block[4] : null;
				$block_hash = $type == 1 ? md5($block) : ($type == 2 || $type == 3 ? "block_" : "view_") . $block; //if html set md5
				
				if (is_numeric($rb_index) && (!isset($rb_indexes["$region_name-$block_hash-$proj"]) || $rb_index > $rb_indexes["$region_name-$block_hash-$proj"]))
					$rb_indexes["$region_name-$block_hash-$proj"] = $rb_index;
			}
		}
		
		foreach ($region_blocks as $region_block) {
			$block = isset($region_block[1]) ? $region_block[1] : null;
			$exists = false;
			//print_r($region_block);
			
			if ($block) {
				$proj = isset($region_block[2]) ? $region_block[2] : null;
				$type = isset($region_block[3]) ? $region_block[3] : null;
				$rb_index = isset($region_block[4]) ? $region_block[4] : null;
				$block_hash = $type == 1 ? md5($block) : ($type == 2 || $type == 3 ? "block_" : "view_") . $block; //if html set md5
				$is_block = $type == 2 || $type == 3;
				$is_view = $type == 4 || $type == 5;
				
				if (!is_numeric($rb_index)) {
					if (isset($rb_indexes["$region_name-$block_hash-$proj"]))
						$rb_indexes["$region_name-$block_hash-$proj"]++;
					else
						$rb_indexes["$region_name-$block_hash-$proj"] = 0;
					
					$rb_index = $rb_indexes["$region_name-$block_hash-$proj"];
				}
				
				$region_block_html = CMSPresentationLayerUIHandler::getRegionBlockHtml($region_name, $block, $proj, $type, $available_blocks_list, array(), array(), $rb_index);
				
				if ($is_block || $is_view) { //if not html
					$sb = substr($block, 0, 1) == '"' ? str_replace('"', '', $block) : $block;
					$sbp = substr($proj, 0, 1) == '"' ? str_replace('"', '', $proj) : $proj;
					$exists = false;
					
					if ($is_block) {
						$apbl = isset($available_blocks_list[$sbp]) ? $available_blocks_list[$sbp] : null;
						$exists = !$sb || ($apbl && in_array($sb, $apbl));
					}
					
					$class = $exists ? " active" : ($sb ? " invalid" : "");
					
					if ($class)
						$region_block_html = str_replace('<div class="template_region_item', '<div class="template_region_item' . $class, $region_block_html);
				}
				
				$region_html .= $region_block_html;
			}
			//else //Add default region block - DEPRECATED. No need for this anymore, bc now we have the template_region_intro
			//	$region_html .= CMSPresentationLayerUIHandler::getRegionBlockHtml($region_name, null, null, false, $available_blocks_list);
		}
	}
	//else //Add default region block - DEPRECATED. No need for this anymore, bc now we have the template_region_intro
	//	$region_html = CMSPresentationLayerUIHandler::getRegionBlockHtml($region_name, null, null, false, $available_blocks_list);
	
	$r = str_replace('"', '&quot;', $region_name);
	
	return '<div class="template_region' . ($is_empty_canvas_body_with_one_template_region ? " full_body" : "") . '" region="' . $r . '">
			<div class="template_region_name">
				<span class="icon info template_region_name_icon" onClick="openTemplateRegionInfoPopup(this)" title="View region samples">View region samples</span>
				<span class="template_region_name_label">Region ' . $region_name . '.</span>
				<a class="template_region_name_link" href="javascript:void(0)" onClick="addFirstRegionBlock(this)" title="Add a new box so you can choose an existent block file"><i class="icon add"></i> Add Block File</a>
			</div>
			<div class="template_region_items droppable' . ($is_edit_template || $is_edit_view ? '' : ' main-droppable" data-main-droppable-name="' . $r) . '">' . $region_html . '</div>
			<div class="template_region_intro">
				<div class="template_region_intro_title">Drag&Drop Widgets Here!</div>
				<div class="template_region_intro_text">Or click in the "Add" button above to add a block file.<br/>Otherwise click me, to edit text...</div>
			</div>
		</div>';
}

//In edit_entity_simple.php: adding this dynamic styles and scripts are very important, because for some reason when we change something in the settings panel and this iframe get refreshed, then if we add a new block html, when the editor gets creator for that block's textarea, is messing up the editor and is not getting created correctly.
//In edit_template_simple.php: when we create a new block html, when the editor gets creator for that block's textarea, the ace library is adding the following scripts and styles to the HEAD tag, but without the "layout-ui-editor-reserved" class, which will save this new html to the template. And we don't want this!
function getAceEditorDynamicHtml($system_project_common_url_prefix) {
	return '<style id="ace_editor" class="layout-ui-editor-reserved">.ace_editor {position: relative;overflow: hidden;font: 12px/normal \'Monaco\', \'Menlo\', \'Ubuntu Mono\', \'Consolas\', \'source-code-pro\', monospace;direction: ltr;}.ace_scroller {position: absolute;overflow: hidden;top: 0;bottom: 0;background-color: inherit;-ms-user-select: none;-moz-user-select: none;-webkit-user-select: none;user-select: none;cursor: text;}.ace_content {position: absolute;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;min-width: 100%;}.ace_dragging .ace_scroller:before{position: absolute;top: 0;left: 0;right: 0;bottom: 0;content: \'\';background: rgba(250, 250, 250, 0.01);z-index: 1000;}.ace_dragging.ace_dark .ace_scroller:before{background: rgba(0, 0, 0, 0.01);}.ace_selecting, .ace_selecting * {cursor: text !important;}.ace_gutter {position: absolute;overflow : hidden;width: auto;top: 0;bottom: 0;left: 0;cursor: default;z-index: 4;-ms-user-select: none;-moz-user-select: none;-webkit-user-select: none;user-select: none;}.ace_gutter-active-line {position: absolute;left: 0;right: 0;}.ace_scroller.ace_scroll-left {box-shadow: 17px 0 16px -16px rgba(0, 0, 0, 0.4) inset;}.ace_gutter-cell {padding-left: 19px;padding-right: 6px;background-repeat: no-repeat;}.ace_gutter-cell.ace_error {background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAABOFBMVEX/////////QRswFAb/Ui4wFAYwFAYwFAaWGAfDRymzOSH/PxswFAb/SiUwFAYwFAbUPRvjQiDllog5HhHdRybsTi3/Tyv9Tir+Syj/UC3////XurebMBIwFAb/RSHbPx/gUzfdwL3kzMivKBAwFAbbvbnhPx66NhowFAYwFAaZJg8wFAaxKBDZurf/RB6mMxb/SCMwFAYwFAbxQB3+RB4wFAb/Qhy4Oh+4QifbNRcwFAYwFAYwFAb/QRzdNhgwFAYwFAbav7v/Uy7oaE68MBK5LxLewr/r2NXewLswFAaxJw4wFAbkPRy2PyYwFAaxKhLm1tMwFAazPiQwFAaUGAb/QBrfOx3bvrv/VC/maE4wFAbRPBq6MRO8Qynew8Dp2tjfwb0wFAbx6eju5+by6uns4uH9/f36+vr/GkHjAAAAYnRSTlMAGt+64rnWu/bo8eAA4InH3+DwoN7j4eLi4xP99Nfg4+b+/u9B/eDs1MD1mO7+4PHg2MXa347g7vDizMLN4eG+Pv7i5evs/v79yu7S3/DV7/498Yv24eH+4ufQ3Ozu/v7+y13sRqwAAADLSURBVHjaZc/XDsFgGIBhtDrshlitmk2IrbHFqL2pvXf/+78DPokj7+Fz9qpU/9UXJIlhmPaTaQ6QPaz0mm+5gwkgovcV6GZzd5JtCQwgsxoHOvJO15kleRLAnMgHFIESUEPmawB9ngmelTtipwwfASilxOLyiV5UVUyVAfbG0cCPHig+GBkzAENHS0AstVF6bacZIOzgLmxsHbt2OecNgJC83JERmePUYq8ARGkJx6XtFsdddBQgZE2nPR6CICZhawjA4Fb/chv+399kfR+MMMDGOQAAAABJRU5ErkJggg==");background-repeat: no-repeat;background-position: 2px center;}.ace_gutter-cell.ace_warning {background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAAmVBMVEX///8AAAD///8AAAAAAABPSzb/5sAAAAB/blH/73z/ulkAAAAAAAD85pkAAAAAAAACAgP/vGz/rkDerGbGrV7/pkQICAf////e0IsAAAD/oED/qTvhrnUAAAD/yHD/njcAAADuv2r/nz//oTj/p064oGf/zHAAAAA9Nir/tFIAAAD/tlTiuWf/tkIAAACynXEAAAAAAAAtIRW7zBpBAAAAM3RSTlMAABR1m7RXO8Ln31Z36zT+neXe5OzooRDfn+TZ4p3h2hTf4t3k3ucyrN1K5+Xaks52Sfs9CXgrAAAAjklEQVR42o3PbQ+CIBQFYEwboPhSYgoYunIqqLn6/z8uYdH8Vmdnu9vz4WwXgN/xTPRD2+sgOcZjsge/whXZgUaYYvT8QnuJaUrjrHUQreGczuEafQCO/SJTufTbroWsPgsllVhq3wJEk2jUSzX3CUEDJC84707djRc5MTAQxoLgupWRwW6UB5fS++NV8AbOZgnsC7BpEAAAAABJRU5ErkJggg==");background-position: 2px center;}.ace_gutter-cell.ace_info {background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAAAAAA6mKC9AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAAJ0Uk5TAAB2k804AAAAPklEQVQY02NgIB68QuO3tiLznjAwpKTgNyDbMegwisCHZUETUZV0ZqOquBpXj2rtnpSJT1AEnnRmL2OgGgAAIKkRQap2htgAAAAASUVORK5CYII=");background-position: 2px center;}.ace_dark .ace_gutter-cell.ace_info {background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQBAMAAADt3eJSAAAAJFBMVEUAAAChoaGAgIAqKiq+vr6tra1ZWVmUlJSbm5s8PDxubm56enrdgzg3AAAAAXRSTlMAQObYZgAAAClJREFUeNpjYMAPdsMYHegyJZFQBlsUlMFVCWUYKkAZMxZAGdxlDMQBAG+TBP4B6RyJAAAAAElFTkSuQmCC");}.ace_scrollbar {position: absolute;right: 0;bottom: 0;z-index: 6;}.ace_scrollbar-inner {position: absolute;cursor: text;left: 0;top: 0;}.ace_scrollbar-v{overflow-x: hidden;overflow-y: scroll;top: 0;}.ace_scrollbar-h {overflow-x: scroll;overflow-y: hidden;left: 0;}.ace_print-margin {position: absolute;height: 100%;}.ace_text-input {position: absolute;z-index: 0;width: 0.5em;height: 1em;opacity: 0;background: transparent;-moz-appearance: none;appearance: none;border: none;resize: none;outline: none;overflow: hidden;font: inherit;padding: 0 1px;margin: 0 -1px;text-indent: -1em;-ms-user-select: text;-moz-user-select: text;-webkit-user-select: text;user-select: text;}.ace_text-input.ace_composition {background: inherit;color: inherit;z-index: 1000;opacity: 1;text-indent: 0;}.ace_layer {z-index: 1;position: absolute;overflow: hidden;word-wrap: normal;white-space: pre;height: 100%;width: 100%;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;pointer-events: none;}.ace_gutter-layer {position: relative;width: auto;text-align: right;pointer-events: auto;}.ace_text-layer {font: inherit !important;}.ace_cjk {display: inline-block;text-align: center;}.ace_cursor-layer {z-index: 4;}.ace_cursor {z-index: 4;position: absolute;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;border-left: 2px solid}.ace_slim-cursors .ace_cursor {border-left-width: 1px;}.ace_overwrite-cursors .ace_cursor {border-left-width: 0;border-bottom: 1px solid;}.ace_hidden-cursors .ace_cursor {opacity: 0.2;}.ace_smooth-blinking .ace_cursor {-webkit-transition: opacity 0.18s;transition: opacity 0.18s;}.ace_editor.ace_multiselect .ace_cursor {border-left-width: 1px;}.ace_marker-layer .ace_step, .ace_marker-layer .ace_stack {position: absolute;z-index: 3;}.ace_marker-layer .ace_selection {position: absolute;z-index: 5;}.ace_marker-layer .ace_bracket {position: absolute;z-index: 6;}.ace_marker-layer .ace_active-line {position: absolute;z-index: 2;}.ace_marker-layer .ace_selected-word {position: absolute;z-index: 4;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;}.ace_line .ace_fold {-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;display: inline-block;height: 11px;margin-top: -2px;vertical-align: middle;background-image:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABEAAAAJCAYAAADU6McMAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAJpJREFUeNpi/P//PwOlgAXGYGRklAVSokD8GmjwY1wasKljQpYACtpCFeADcHVQfQyMQAwzwAZI3wJKvCLkfKBaMSClBlR7BOQikCFGQEErIH0VqkabiGCAqwUadAzZJRxQr/0gwiXIal8zQQPnNVTgJ1TdawL0T5gBIP1MUJNhBv2HKoQHHjqNrA4WO4zY0glyNKLT2KIfIMAAQsdgGiXvgnYAAAAASUVORK5CYII="),url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAA3CAYAAADNNiA5AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAACJJREFUeNpi+P//fxgTAwPDBxDxD078RSX+YeEyDFMCIMAAI3INmXiwf2YAAAAASUVORK5CYII=");background-repeat: no-repeat, repeat-x;background-position: center center, top left;color: transparent;border: 1px solid black;border-radius: 2px;cursor: pointer;pointer-events: auto;}.ace_dark .ace_fold {}.ace_fold:hover{background-image:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABEAAAAJCAYAAADU6McMAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAJpJREFUeNpi/P//PwOlgAXGYGRklAVSokD8GmjwY1wasKljQpYACtpCFeADcHVQfQyMQAwzwAZI3wJKvCLkfKBaMSClBlR7BOQikCFGQEErIH0VqkabiGCAqwUadAzZJRxQr/0gwiXIal8zQQPnNVTgJ1TdawL0T5gBIP1MUJNhBv2HKoQHHjqNrA4WO4zY0glyNKLT2KIfIMAAQsdgGiXvgnYAAAAASUVORK5CYII="),url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAA3CAYAAADNNiA5AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAACBJREFUeNpi+P//fz4TAwPDZxDxD5X4i5fLMEwJgAADAEPVDbjNw87ZAAAAAElFTkSuQmCC");}.ace_tooltip {background-color: #FFF;background-image: -webkit-linear-gradient(top, transparent, rgba(0, 0, 0, 0.1));background-image: linear-gradient(to bottom, transparent, rgba(0, 0, 0, 0.1));border: 1px solid gray;border-radius: 1px;box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);color: black;max-width: 100%;padding: 3px 4px;position: fixed;z-index: 999999;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;cursor: default;white-space: pre;word-wrap: break-word;line-height: normal;font-style: normal;font-weight: normal;letter-spacing: normal;pointer-events: none;}.ace_folding-enabled > .ace_gutter-cell {padding-right: 13px;}.ace_fold-widget {-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;margin: 0 -12px 0 1px;display: none;width: 11px;vertical-align: top;background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAANElEQVR42mWKsQ0AMAzC8ixLlrzQjzmBiEjp0A6WwBCSPgKAXoLkqSot7nN3yMwR7pZ32NzpKkVoDBUxKAAAAABJRU5ErkJggg==");background-repeat: no-repeat;background-position: center;border-radius: 3px;border: 1px solid transparent;cursor: pointer;}.ace_folding-enabled .ace_fold-widget {display: inline-block;   }.ace_fold-widget.ace_end {background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAANElEQVR42m3HwQkAMAhD0YzsRchFKI7sAikeWkrxwScEB0nh5e7KTPWimZki4tYfVbX+MNl4pyZXejUO1QAAAABJRU5ErkJggg==");}.ace_fold-widget.ace_closed {background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAMAAAAGCAYAAAAG5SQMAAAAOUlEQVR42jXKwQkAMAgDwKwqKD4EwQ26sSOkVWjgIIHAzPiCgaqiqnJHZnKICBERHN194O5b9vbLuAVRL+l0YWnZAAAAAElFTkSuQmCCXA==");}.ace_fold-widget:hover {border: 1px solid rgba(0, 0, 0, 0.3);background-color: rgba(255, 255, 255, 0.2);box-shadow: 0 1px 1px rgba(255, 255, 255, 0.7);}.ace_fold-widget:active {border: 1px solid rgba(0, 0, 0, 0.4);background-color: rgba(0, 0, 0, 0.05);box-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);}.ace_dark .ace_fold-widget {background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHklEQVQIW2P4//8/AzoGEQ7oGCaLLAhWiSwB146BAQCSTPYocqT0AAAAAElFTkSuQmCC");}.ace_dark .ace_fold-widget.ace_end {background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAH0lEQVQIW2P4//8/AxQ7wNjIAjDMgC4AxjCVKBirIAAF0kz2rlhxpAAAAABJRU5ErkJggg==");}.ace_dark .ace_fold-widget.ace_closed {background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAMAAAAFCAYAAACAcVaiAAAAHElEQVQIW2P4//+/AxAzgDADlOOAznHAKgPWAwARji8UIDTfQQAAAABJRU5ErkJggg==");}.ace_dark .ace_fold-widget:hover {box-shadow: 0 1px 1px rgba(255, 255, 255, 0.2);background-color: rgba(255, 255, 255, 0.1);}.ace_dark .ace_fold-widget:active {box-shadow: 0 1px 1px rgba(255, 255, 255, 0.2);}.ace_fold-widget.ace_invalid {background-color: #FFB4B4;border-color: #DE5555;}.ace_fade-fold-widgets .ace_fold-widget {-webkit-transition: opacity 0.4s ease 0.05s;transition: opacity 0.4s ease 0.05s;opacity: 0;}.ace_fade-fold-widgets:hover .ace_fold-widget {-webkit-transition: opacity 0.05s ease 0.05s;transition: opacity 0.05s ease 0.05s;opacity:1;}.ace_underline {text-decoration: underline;}.ace_bold {font-weight: bold;}.ace_nobold .ace_bold {font-weight: normal;}.ace_italic {font-style: italic;}.ace_error-marker {background-color: rgba(255, 0, 0,0.2);position: absolute;z-index: 9;}.ace_highlight-marker {background-color: rgba(255, 255, 0,0.2);position: absolute;z-index: 8;}
	</style>

<style id="ace-tm" class="layout-ui-editor-reserved">
.ace-tm .ace_gutter {background: #f0f0f0;color: #333;}.ace-tm .ace_print-margin {width: 1px;background: #e8e8e8;}.ace-tm .ace_fold {background-color: #6B72E6;}.ace-tm {background-color: #FFFFFF;color: black;}.ace-tm .ace_cursor {color: black;}.ace-tm .ace_invisible {color: rgb(191, 191, 191);}.ace-tm .ace_storage,.ace-tm .ace_keyword {color: blue;}.ace-tm .ace_constant {color: rgb(197, 6, 11);}.ace-tm .ace_constant.ace_buildin {color: rgb(88, 72, 246);}.ace-tm .ace_constant.ace_language {color: rgb(88, 92, 246);}.ace-tm .ace_constant.ace_library {color: rgb(6, 150, 14);}.ace-tm .ace_invalid {background-color: rgba(255, 0, 0, 0.1);color: red;}.ace-tm .ace_support.ace_function {color: rgb(60, 76, 114);}.ace-tm .ace_support.ace_constant {color: rgb(6, 150, 14);}.ace-tm .ace_support.ace_type,.ace-tm .ace_support.ace_class {color: rgb(109, 121, 222);}.ace-tm .ace_keyword.ace_operator {color: rgb(104, 118, 135);}.ace-tm .ace_string {color: rgb(3, 106, 7);}.ace-tm .ace_comment {color: rgb(76, 136, 107);}.ace-tm .ace_comment.ace_doc {color: rgb(0, 102, 255);}.ace-tm .ace_comment.ace_doc.ace_tag {color: rgb(128, 159, 191);}.ace-tm .ace_constant.ace_numeric {color: rgb(0, 0, 205);}.ace-tm .ace_variable {color: rgb(49, 132, 149);}.ace-tm .ace_xml-pe {color: rgb(104, 104, 91);}.ace-tm .ace_entity.ace_name.ace_function {color: #0000A2;}.ace-tm .ace_heading {color: rgb(12, 7, 255);}.ace-tm .ace_list {color:rgb(185, 6, 144);}.ace-tm .ace_meta.ace_tag {color:rgb(0, 22, 142);}.ace-tm .ace_string.ace_regex {color: rgb(255, 0, 0)}.ace-tm .ace_marker-layer .ace_selection {background: rgb(181, 213, 255);}.ace-tm.ace_multiselect .ace_selection.ace_start {box-shadow: 0 0 3px 0px white;border-radius: 2px;}.ace-tm .ace_marker-layer .ace_step {background: rgb(252, 255, 0);}.ace-tm .ace_marker-layer .ace_stack {background: rgb(164, 229, 101);}.ace-tm .ace_marker-layer .ace_bracket {margin: -1px 0 0 -1px;border: 1px solid rgb(192, 192, 192);}.ace-tm .ace_marker-layer .ace_active-line {background: rgba(0, 0, 0, 0.07);}.ace-tm .ace_gutter-active-line {background-color : #dcdcdc;}.ace-tm .ace_marker-layer .ace_selected-word {background: rgb(250, 250, 255);border: 1px solid rgb(200, 200, 250);}.ace-tm .ace_indent-guide {background: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAYAAACZgbYnAAAAE0lEQVQImWP4////f4bLly//BwAmVgd1/w11/gAAAABJRU5ErkJggg==") right repeat-y;}
</style>

<style class="layout-ui-editor-reserved">
.error_widget_wrapper {        background: inherit;        color: inherit;        border:none    }    .error_widget {        border-top: solid 2px;        border-bottom: solid 2px;        margin: 5px 0;        padding: 10px 40px;        white-space: pre-wrap;    }    .error_widget.ace_error, .error_widget_arrow.ace_error{        border-color: #ff5a5a    }    .error_widget.ace_warning, .error_widget_arrow.ace_warning{        border-color: #F1D817    }    .error_widget.ace_info, .error_widget_arrow.ace_info{        border-color: #5a5a5a    }    .error_widget.ace_ok, .error_widget_arrow.ace_ok{        border-color: #5aaa5a    }    .error_widget_arrow {        position: absolute;        border: solid 5px;        border-top-color: transparent!important;        border-right-color: transparent!important;        border-left-color: transparent!important;        top: -5px;    }
</style>

<style class="layout-ui-editor-reserved">
.ace_snippet-marker {    -moz-box-sizing: border-box;    box-sizing: border-box;    background: rgba(194, 193, 208, 0.09);    border: 1px dotted rgba(211, 208, 235, 0.62);    position: absolute;}</style>
<style class="layout-ui-editor-reserved">
.ace_editor.ace_autocomplete .ace_marker-layer .ace_active-line {    background-color: #CAD6FA;    z-index: 1;}.ace_editor.ace_autocomplete .ace_line-hover {    border: 1px solid #abbffe;    margin-top: -1px;    background: rgba(233,233,253,0.4);}.ace_editor.ace_autocomplete .ace_line-hover {    position: absolute;    z-index: 2;}.ace_editor.ace_autocomplete .ace_scroller {   background: none;   border: none;   box-shadow: none;}.ace_rightAlignedText {    color: gray;    display: inline-block;    position: absolute;    right: 4px;    text-align: right;    z-index: -1;}.ace_editor.ace_autocomplete .ace_completion-highlight{    color: #000;    text-shadow: 0 0 0.01em;}.ace_editor.ace_autocomplete {    width: 280px;    z-index: 200000;    background: #fbfbfb;    color: #444;    border: 1px lightgray solid;    position: fixed;    box-shadow: 2px 3px 5px rgba(0,0,0,.2);    line-height: 1.4;}
</style>

<style id="ace-chrome" class="layout-ui-editor-reserved">
.ace-chrome .ace_gutter {background: #ebebeb;color: #333;overflow : hidden;}.ace-chrome .ace_print-margin {width: 1px;background: #e8e8e8;}.ace-chrome {background-color: #FFFFFF;color: black;}.ace-chrome .ace_cursor {color: black;}.ace-chrome .ace_invisible {color: rgb(191, 191, 191);}.ace-chrome .ace_constant.ace_buildin {color: rgb(88, 72, 246);}.ace-chrome .ace_constant.ace_language {color: rgb(88, 92, 246);}.ace-chrome .ace_constant.ace_library {color: rgb(6, 150, 14);}.ace-chrome .ace_invalid {background-color: rgb(153, 0, 0);color: white;}.ace-chrome .ace_fold {}.ace-chrome .ace_support.ace_function {color: rgb(60, 76, 114);}.ace-chrome .ace_support.ace_constant {color: rgb(6, 150, 14);}.ace-chrome .ace_support.ace_type,.ace-chrome .ace_support.ace_class.ace-chrome .ace_support.ace_other {color: rgb(109, 121, 222);}.ace-chrome .ace_variable.ace_parameter {font-style:italic;color:#FD971F;}.ace-chrome .ace_keyword.ace_operator {color: rgb(104, 118, 135);}.ace-chrome .ace_comment {color: #236e24;}.ace-chrome .ace_comment.ace_doc {color: #236e24;}.ace-chrome .ace_comment.ace_doc.ace_tag {color: #236e24;}.ace-chrome .ace_constant.ace_numeric {color: rgb(0, 0, 205);}.ace-chrome .ace_variable {color: rgb(49, 132, 149);}.ace-chrome .ace_xml-pe {color: rgb(104, 104, 91);}.ace-chrome .ace_entity.ace_name.ace_function {color: #0000A2;}.ace-chrome .ace_heading {color: rgb(12, 7, 255);}.ace-chrome .ace_list {color:rgb(185, 6, 144);}.ace-chrome .ace_marker-layer .ace_selection {background: rgb(181, 213, 255);}.ace-chrome .ace_marker-layer .ace_step {background: rgb(252, 255, 0);}.ace-chrome .ace_marker-layer .ace_stack {background: rgb(164, 229, 101);}.ace-chrome .ace_marker-layer .ace_bracket {margin: -1px 0 0 -1px;border: 1px solid rgb(192, 192, 192);}.ace-chrome .ace_marker-layer .ace_active-line {background: rgba(0, 0, 0, 0.07);}.ace-chrome .ace_gutter-active-line {background-color : #dcdcdc;}.ace-chrome .ace_marker-layer .ace_selected-word {background: rgb(250, 250, 255);border: 1px solid rgb(200, 200, 250);}.ace-chrome .ace_storage,.ace-chrome .ace_keyword,.ace-chrome .ace_meta.ace_tag {color: rgb(147, 15, 128);}.ace-chrome .ace_string.ace_regex {color: rgb(255, 0, 0)}.ace-chrome .ace_string {color: #1A1AA6;}.ace-chrome .ace_entity.ace_other.ace_attribute-name {color: #994409;}.ace-chrome .ace_indent-guide {background: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAYAAACZgbYnAAAAE0lEQVQImWP4////f4bLly//BwAmVgd1/w11/gAAAABJRU5ErkJggg==") right repeat-y;}
</style>

<script class="layout-ui-editor-reserved" src="' . $system_project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/theme-chrome.js"></script>
<script class="layout-ui-editor-reserved" src="' . $system_project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/mode-html.js"></script>
<script class="layout-ui-editor-reserved" src="' . $system_project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/snippets/text.js"></script>
<script class="layout-ui-editor-reserved" src="' . $system_project_common_url_prefix . 'vendor/acecodeeditor/src-min-noconflict/snippets/html.js"></script>
';
}
?>
