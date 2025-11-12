<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("OpenAIActionHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$action = isset($_GET["action"]) ? $_GET["action"] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $openai_encryption_key) {
	$input = file_get_contents("php://input");
	
	if ($action == "chat" && !empty($_POST)) {
		$convert_to_html = isset($_GET["convert_to_html"]) ? $_GET["convert_to_html"] : null;
		
		$system_message = isset($_POST["system_message"]) ? $_POST["system_message"] : null;
		$user_message = isset($_POST["user_message"]) ? $_POST["user_message"] : null;
		$session = isset($_POST["session"]) ? $_POST["session"] : null;
		
		$res = OpenAIActionHandler::chat($openai_encryption_key, $system_message, $user_message, $session);
		
		if ($convert_to_html && !empty($res["reply"])) {
			include_once get_lib("lib.vendor.parsedown.Parsedown");
			
			$Parsedown = new Parsedown();
			$res["reply_html"] = $Parsedown->text($res["reply"]);	
		}
		
		echo json_encode($res);
	}
	elseif ($action == "explain_logs" && $input)
		echo OpenAIActionHandler::explainLogs($openai_encryption_key, $input); //this returns a string
	else if ($action == "explain_sql" && $input)
		echo OpenAIActionHandler::explainSQL($openai_encryption_key, $input); //this returns a string
	else if ($action == "describe_image" && !empty($_POST)) {
		$instructions = isset($_POST["instructions"]) ? $_POST["instructions"] : null;
		$file = isset($_FILES["file"]) ? $_FILES["file"] : null;
		
		if ($file && file_exists($file["tmp_name"]))
			echo OpenAIActionHandler::describeImage($openai_encryption_key, array($file), $instructions); //this returns a string
	}
	else if ($action == "generate_sql" && !empty($_POST)) {
		$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
		$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
		$global_default_db_driver_broker = isset($_GET["global_default_db_driver_broker"]) ? $_GET["global_default_db_driver_broker"] : null; //in case of form module in the presentation layers
		
		$db_broker = isset($_POST["db_broker"]) ? $_POST["db_broker"] : null; //if bean name is a presentation layer, the db_broker becomes a dal_broker
		$db_driver = isset($_POST["db_driver"]) ? $_POST["db_driver"] : null;
		$type = isset($_POST["type"]) ? $_POST["type"] : null;
		$db_table = isset($_POST["db_table"]) ? $_POST["db_table"] : null;
		$instructions = isset($_POST["instructions"]) ? $_POST["instructions"] : null;
		$system_instructions = isset($_POST["system_instructions"]) ? $_POST["system_instructions"] : null;
		
		$res = OpenAIActionHandler::generateSQL($openai_encryption_key, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $bean_name, $bean_file_name, $db_broker, $db_driver, $type, $db_table, $global_default_db_driver_broker, $instructions, $system_instructions);
		echo json_encode($res);
	}
	else if ($action == "generate_table_creation_sql") {
		$instructions = isset($_POST["instructions"]) ? $_POST["instructions"] : null;
		$table_name = isset($_POST["table_name"]) ? $_POST["table_name"] : null;
		$driver_type = isset($_POST["driver_type"]) ? $_POST["driver_type"] : null;
		
		$res = OpenAIActionHandler::generateTableCreationSQL($openai_encryption_key, $instructions, $table_name, $driver_type);
		echo json_encode($res);
	}
	else if ($action == "comment_php_code" && $input)
		echo OpenAIActionHandler::commentPHPCode($openai_encryption_key, $input); //this returns a string
	else if ($action == "generate_php_code" && $input) {
		$res = OpenAIActionHandler::generatePHPCode($openai_encryption_key, $input);
		echo json_encode($res);
	}
	else if ($action == "generate_html_code") {
		$instructions = isset($_POST["instructions"]) ? $_POST["instructions"] : null;
		$image = isset($_FILES["image"]) ? $_FILES["image"] : null;
		
		if ($image && file_exists($image["tmp_name"])) {
			/*$reply = OpenAIActionHandler::describeImage($openai_encryption_key, array($image), $instructions);
			
			if ($reply)
				$instructions .= "\n\n" . $reply;
			*/
			$res = OpenAIActionHandler::convertImageToHTML($openai_encryption_key, array($image), $instructions);
		}
		else
			$res = OpenAIActionHandler::generateHTMLCode($openai_encryption_key, $instructions);
		
		echo json_encode($res);
	}
	else if ($action == "generate_html_image" && !empty($_POST)) {
		$instructions = isset($_POST["instructions"]) ? $_POST["instructions"] : null;
		$images_total = isset($_POST["images_total"]) ? $_POST["images_total"] : null;
		$images_size = isset($_POST["images_size"]) ? $_POST["images_size"] : null;
		$images_quality = isset($_POST["images_quality"]) ? $_POST["images_quality"] : null;
		
		$res = OpenAIActionHandler::generateHTMLImage($openai_encryption_key, $instructions, $images_total, $images_size, $images_quality);
		echo json_encode($res);
	}
	else if ($action == "generate_html_template" && !empty($_POST)) {
		$template_name = isset($_POST["template_name"]) ? $_POST["template_name"] : null;
		$layout_name = isset($_POST["layout_name"]) ? $_POST["layout_name"] : null;
		$instructions = isset($_POST["instructions"]) ? $_POST["instructions"] : null;
		$regions = isset($_POST["regions"]) ? $_POST["regions"] : null;
		
		$res = OpenAIActionHandler::generateHTMLTemplate($openai_encryption_key, $instructions, $regions);
		echo json_encode($res);
	}
	else if ($action == "generate_html_page" && !empty($_POST)) {
		$instructions = isset($_POST["instructions"]) ? $_POST["instructions"] : null;
		$image = isset($_FILES["image"]) ? $_FILES["image"] : null;
		
		if ($image && file_exists($image["tmp_name"])) {
			/*$reply = OpenAIActionHandler::describeImage($openai_encryption_key, array($image), $instructions);
			
			if ($reply)
				$instructions .= "\n\n" . $reply;
			*/
			$res = OpenAIActionHandler::convertImageToHTML($openai_encryption_key, array($image), $instructions);
		}
		else
			$res = OpenAIActionHandler::generateHTMLPage($openai_encryption_key, $instructions);
		
		echo json_encode($res);
	}
	else if ($action == "generate_inline_code" && $input) {
		$post_data = json_decode($input, true);
		
		$lang = isset($post_data["lang"]) ? $post_data["lang"] : null;
		$instructions = isset($post_data["instructions"]) ? $post_data["instructions"] : null;
		$system_instructions = isset($post_data["system_instructions"]) ? $post_data["system_instructions"] : null;
		$code = isset($post_data["code"]) ? $post_data["code"] : null;
		$selected_code = isset($post_data["selected_code"]) ? $post_data["selected_code"] : null;
		$selected_range = isset($post_data["selected_range"]) ? $post_data["selected_range"] : null;
		
		$res = OpenAIActionHandler::generateInlineCode($openai_encryption_key, $lang, $instructions, $system_instructions, $code, $selected_code, $selected_range);
		echo json_encode($res);
	}
}

die();
?>
