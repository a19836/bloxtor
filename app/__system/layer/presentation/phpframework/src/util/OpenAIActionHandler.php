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

include_once get_lib("org.phpframework.util.web.OpenAIHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");

class OpenAIActionHandler {
	
	public static function chat($openai_encryption_key, $system_message, $user_message, $session = null) {
		$session_histories = null;
		
		if ($session) {
			// Get all sessions histories
			$sessions_histories = array(); //TODO
			
			// get current session history
			$session_histories = isset($sessions_histories[$session]) ? $sessions_histories[$session] : null;
			
			// Truncate some previous $session_histories (FIFO), so it doesn't exceed the openai maximum tokens
			//TODO
		}
		
		$OpenAIHandler = new OpenAIHandler($openai_encryption_key, null, "gpt-4o");
		$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
		$system_tokens = $OpenAIHandler->estimateTextTokens($system_message);
		$user_tokens = $OpenAIHandler->estimateTextTokens($user_message);
		$max_tokens = floor($model_tokens_limits - $system_tokens - $sql_tokens - 10); //margin of 10 tokens
		
		//if max_tokens is less than minimum tokens, then cut the system message accordingly. minimum 1000 tokens for a good response.
		$minimum_tokens = 11600;
		
		if ($max_tokens < $minimum_tokens) {
			$diff = $max_tokens > 0 ? $minimum_tokens - $max_tokens : $minimum_tokens + abs($max_tokens);
			$max_tokens = $minimum_tokens;
			
			if ($diff > $system_tokens)
				$system_message = null;
			else if ($diff > 0) {
				$chunks = $OpenAIHandler->splitTextByTokens($system_message, $system_tokens - $diff);
				$system_message = $chunks[0] . "\nThis message has been truncated...";
			}
		}
		
		$reply = $OpenAIHandler->generateRawMessage($system_message, $user_message, $session_histories);
		$errors = $OpenAIHandler->getErrors();
		
		if (empty($errors)) {
			if ($session) {
				// Initialize session history if it doesn't exist
				if (!isset($sessions_histories[$session]))
					$sessions_histories[$session] = array();
				
				// Add the user message to the session history
				$session_histories[$session][] = array("role" => "user", "content" => $user_message);
				
				// Add the OpenAI response to the session history
				$session_histories[$session][] = array("role" => "assistant", "content" => $reply);
				
				// Save session_histories for future user messages
				//TODO
			}
			
			return array(
				"reply" => $reply
			);
		}
		else {
			$error_msg = "Error explaning logs. Errors:\n- " . implode("\n- ", $errors);
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function explainLogs($openai_encryption_key, $input) {
		$system_content = "You are sysadmin and programmer that explains complex server logs. Continue explaining according with previous logs. The reply will be concatenate with previous replies and shown to user.";
		
		$OpenAIHandler = new OpenAIHandler($openai_encryption_key);
		$reply = $OpenAIHandler->generateMessage($system_content, $input);
		$errors = $OpenAIHandler->getErrors();
		
		if (empty($errors))
			return $reply;
		else {
			$error_msg = "Error explaning logs. Errors:\n- " . implode("\n- ", $errors);
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function explainSQL($openai_encryption_key, $input) {
		$system_content = "You are a expert that explains SQL statements.";
		
		//get max_tokens, based in lines with code - a comment per line of code
		$OpenAIHandler = new OpenAIHandler($openai_encryption_key);
		$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
		$system_tokens = $OpenAIHandler->estimateTextTokens($system_content);
		$sql_tokens = $OpenAIHandler->estimateTextTokens($input);
		$max_tokens = floor($model_tokens_limits - $system_tokens - $sql_tokens - 10); //margin of 10 tokens
		//echo "$model_tokens_limits|$system_tokens|$sql_tokens|$max_tokens";die();
		
		$reply = $OpenAIHandler->generateMessage($system_content, $input, null, array("max_tokens" => $max_tokens));
		$errors = $OpenAIHandler->getErrors();
		
		if (empty($errors))
			return $reply;
		else {
			$error_msg = "Error explaning sql. Errors:\n- " . implode("\n- ", $errors);
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function generateSQL($openai_encryption_key, $user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $bean_name, $bean_file_name, $db_broker, $db_driver, $type, $db_table, $global_default_db_driver_broker, $instructions, $system_instructions = null) {
		if ($instructions) {
			$system_content = "1. Instructions: You are a SQL expert that creates SQL. Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `sql`, or other symbols. Your response should consist solely of sql statements, with no additional formatting or text.
2. What to do: Return a sql statement that complies with user instructions" . ($system_instructions ? " and following:\n" . $system_instructions : ".");
			
			$tables = self::getTablesDetails($user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $bean_name, $bean_file_name, $db_broker, $db_driver, $type, $db_table, $global_default_db_driver_broker);
			
			if ($tables)
				$instructions .= "\nGenerate SQL based in the following tables details: " . json_encode($tables);
			
			$OpenAIHandler = new OpenAIHandler($openai_encryption_key);
			$reply = $OpenAIHandler->generateRawMessage($system_content, $instructions);
			
			return array(
				"sql" => $reply //ignore errors because by including the $tables in the user message, the tokens will be exceeded which will give an error.
			);
		}
		else {
			$error_msg = "No sql description to be generated or wrong input format.";
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function generateTableCreationSQL($openai_encryption_key, $instructions, $table_name = null, $db_driver_type = null) {
		if ($instructions) {
			$system_content = "1. Instructions: You are a SQL expert that creates SQL" . ($db_driver_type ? " for $db_driver_type" : "") . ". Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `sql`, or other symbols. Your response should consist solely of sql statements, with no additional formatting or text.
2. What to do: Return a sql statement to create a table that complies with the user message.
3. Append the following attributes to the end of the table, unless the user explicitly states otherwise:
- `created_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
- `created_user_id` bigint(20) unsigned NULL,
- `modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
- `modified_user_id` bigint(20) unsigned NULL,";
			
			if ($table_name)
				$system_content .= "\n4. The table MUST be named '$table_name'!";
			
			$OpenAIHandler = new OpenAIHandler($openai_encryption_key);
			$reply = $OpenAIHandler->generateRawMessage($system_content, $instructions);
			
			return array(
				"sql" => $reply //ignore errors because by including the $tables in the user message, the tokens will be exceeded which will give an error.
			);
		}
		else {
			$error_msg = "No sql description to be generated or wrong input format.";
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function commentPHPCode($openai_encryption_key, $input) {
		$system_content = "1. Instructions: You are a PHP and HTML expert. Do not change, fix or indent the code. Only comment the code. Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `php`, or other symbols. Your response should consist solely of PHP and HTML code and correspondent comments inside of php code, with no additional formatting or text. If code is the same, give different comments. The reply will be concatenated with previous replies and displayed directly in a code editor.
2. What to do: Comment the code in the user message.";
		
		//get max_tokens, based in lines with code - a comment per line of code
		$OpenAIHandler = new OpenAIHandler($openai_encryption_key);
		$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
		$system_tokens = $OpenAIHandler->estimateTextTokens($system_content);
		$max_tokens = floor( ($model_tokens_limits - $system_tokens) / 4 * 3 ); //give 3 times more commenting words than the code.
		//echo "$model_tokens_limits|$system_tokens|$max_tokens";die();
		
		$code_without_comments = PHPCodePrintingHandler::getCodeWithoutComments($input);
		$reply = $OpenAIHandler->generateMessage($system_content, $code_without_comments, null, array("max_tokens" => $max_tokens));
		$errors = $OpenAIHandler->getErrors();
		
		if (empty($errors)) {
			//remove ``` format, added by openai
			$reply = preg_replace('/(^|\n)```(php)?\s*/', '', $reply);
			$reply = preg_replace('/\n```\s*(\n|$)/', '', $reply);
			//echo $reply;die();
			
			$input = self::mergeCommentsIntoCode($input, $reply);
			
			return $input;
		}
		else {
			$error_msg = "Error commenting php code. Errors:\n- " . implode("\n- ", $errors);
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function generatePHPCode($openai_encryption_key, $input) {
		$system_content = "1. Instructions: You are a PHP expert. Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `php`, or other symbols. Your response should consist solely of PHP code, with no additional formatting or text. If request is the same, create a different PHP. The reply will be concatenated with previous replies and displayed directly in a PHP editor.
2. What to do: Create PHP code based on the user message.";
		
		//get max_tokens, based in lines with code - a comment per line of code
		$OpenAIHandler = new OpenAIHandler($openai_encryption_key, null, "gpt-4o");
		$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
		$system_tokens = $OpenAIHandler->estimateTextTokens($system_content);
		$user_request_tokens = $OpenAIHandler->estimateTextTokens($input);
		$max_tokens = floor($model_tokens_limits - $system_tokens - $user_request_tokens - 10); //margin of 10 tokens
		//echo "$model_tokens_limits|$system_tokens|$user_request_tokens|$max_tokens";die();
		
		$reply = $OpenAIHandler->generateMessage($system_content, $input, null, array("max_tokens" => $max_tokens));
		$errors = $OpenAIHandler->getErrors();
		
		if (empty($errors)) {
			//remove ``` format, added by openai
			$reply = preg_replace('/(^|\n)```(php)?\s*/', '', $reply);
			$reply = preg_replace('/\n```\s*(\n|$)/', '', $reply);
			//echo $reply;die();
			
			return array(
				"code" => $reply
			);
		}
		else {
			$error_msg = "Error creating php. Errors:\n- " . implode("\n- ", $errors);
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function generateHTMLCode($openai_encryption_key, $input) {
		$system_content = "1. Instructions: You are a HTML expert. Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `html`, or other symbols. Your response should consist solely of HTML code, with no additional formatting or text or other javascript or css files to download, since this code should be inside of HTML. If request is the same, create a different HTML. The reply will be concatenated with previous replies and displayed directly in a HTML editor. Add placeholder URLs to the images unless other instructions are provided in the user message.
2. What to do: Create HTML code based on the user message.";
		
		//get max_tokens, based in lines with code - a comment per line of code
		$OpenAIHandler = new OpenAIHandler($openai_encryption_key, null, "gpt-4o");
		$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
		$system_tokens = $OpenAIHandler->estimateTextTokens($system_content);
		$user_request_tokens = $OpenAIHandler->estimateTextTokens($input);
		$max_tokens = floor($model_tokens_limits - $system_tokens - $user_request_tokens - 10); //margin of 10 tokens
		//echo "$model_tokens_limits|$system_tokens|$user_request_tokens|$max_tokens";die();
		
		$reply = $OpenAIHandler->generateMessage($system_content, $input, null, array("max_tokens" => $max_tokens));
		$errors = $OpenAIHandler->getErrors();
		
		if (empty($errors)) {
			//remove ``` format, added by openai
			$reply = preg_replace('/(^|\n)```(html)?\s*/', '', $reply);
			$reply = preg_replace('/\n```\s*(\n|$)/', '', $reply);
			//echo $reply;die();
			
			return array(
				"html" => $reply
			);
		}
		else {
			$error_msg = "Error creating html. Errors:\n- " . implode("\n- ", $errors);
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function generateHTMLImage($openai_encryption_key, $instructions, $images_total, $images_size, $images_quality) {
		if ($instructions) {
			$system_content = "1. Instructions: You are a designer expert in creating images. Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `html`, or other symbols. Your response should consist solely of urls list, with no additional formatting or text.
2. What to do: Create images and send the correspondent urls in a json format.";
			
			$OpenAIHandler = new OpenAIHandler($openai_encryption_key);
			$items = $OpenAIHandler->generateImage($system_content . "\n" . $instructions, $images_total, $images_size, $images_quality, array("url" => "https://api.openai.com/v1/images/generations", "model" => "dall-e-3"));
			$errors = $OpenAIHandler->getErrors();
			
			if (empty($errors)) {
				//echo $reply;die();
				
				return array(
					"items" => $items
				);
			}
			else {
				$error_msg = "Error generating image(s). Errors:\n- " . implode("\n- ", $errors);
				debug_log($error_msg, "error");
			}
		}
		else {
			$error_msg = "No image description to be generated or wrong input format.";
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function generateHTMLPage($openai_encryption_key, $instructions) {
		if ($instructions) {
			$system_content = "1. Instructions: You are a WebDesigner and Designer UX expert in HTML, CSS and Javascript building beautiful pages. Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `html`, or other symbols. Your response should consist solely of HTML code, with no additional formatting or text. The generated HTML should contain the doctype, html, head and body tags. It can also include the necessary `<link>` and `<script>` tags for external ui libraries, like bootstrap, jquery-ui or other libraries you wish to add. Add placeholder URLs to the images unless other instructions are provided in the user message.
2. What to do: Create HTML code based on the user message.";
			
			//get max_tokens, based in lines with code - a comment per line of code
			$OpenAIHandler = new OpenAIHandler($openai_encryption_key, null, "gpt-4o");
			$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
			$system_tokens = $OpenAIHandler->estimateTextTokens($system_content);
			$user_request_tokens = $OpenAIHandler->estimateTextTokens($instructions);
			$max_tokens = floor($model_tokens_limits - $system_tokens - $user_request_tokens - 10); //margin of 10 tokens
			//echo "$model_tokens_limits|$system_tokens|$user_request_tokens|$max_tokens";die();
			
			$html = $OpenAIHandler->generateRawMessage($system_content, $instructions, null, array("max_tokens" => $max_tokens));
			$errors = $OpenAIHandler->getErrors();
			
			if (empty($errors)) {
				//remove ``` format, added by openai
				$html = preg_replace('/(^|\n)```(html)?\s*/', '', $html);
				$html = preg_replace('/\n```\s*(\n|$)/', '', $html);
				
				return array(
					"html" => $html
				);
			}
			else {
				$error_msg = "Error creating html. Errors:\n- " . implode("\n- ", $errors);
				debug_log($error_msg, "error");
			}
		}
		else {
			$error_msg = "No page description to be generated or wrong input format.";
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function generateHTMLTemplate($openai_encryption_key, $instructions, $regions = null) {
		if ($instructions) {
			$system_content = "1. Instructions: You are a WebDesigner and Designer UX expert in HTML, CSS and Javascript building beautiful layouts. Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `html`, or other symbols. Your response should consist solely of HTML code, with no additional formatting or text. The generated HTML should contain the doctype, html, head and body tags. It can also include the necessary `<link>` and `<script>` tags for external ui libraries, like bootstrap, jquery-ui or other libraries you wish to add. Add placeholder URLs to the images unless other instructions are provided in the user message.
2. What to do: Create HTML code based on the user message.";
			
			//add regions information
			if ($regions) {
				$instructions .= "Additionally identify each block html correspondent to the regions defined below, by adding the html `<!-- START REGION: #region_name# -->` before the correspondent html block and `<!-- END REGION: #region_name# -->` after the last html element. The string `#region_name#` should be replaced by the real region name. Then we will parse your HTML and get the correspondent region blocks. Regions details:";
				
				$regions = array_values($regions);
				
				foreach ($regions as $i => $region)
					$instructions .= ($i + 1) . ". Region '" . $region["name"] . "': " . $region["description"] . "\n";
			}
			
			//get max_tokens, based in lines with code - a comment per line of code
			$OpenAIHandler = new OpenAIHandler($openai_encryption_key, null, "gpt-4o");
			$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
			$system_tokens = $OpenAIHandler->estimateTextTokens($system_content);
			$user_request_tokens = $OpenAIHandler->estimateTextTokens($instructions);
			$max_tokens = floor($model_tokens_limits - $system_tokens - $user_request_tokens - 10); //margin of 10 tokens
			//echo "$model_tokens_limits|$system_tokens|$user_request_tokens|$max_tokens";die();
			
			$html = $OpenAIHandler->generateRawMessage($system_content, $instructions, null, array("max_tokens" => $max_tokens));
			$errors = $OpenAIHandler->getErrors();
			
			if (empty($errors)) {
				//remove ``` format, added by openai
				$html = preg_replace('/(^|\n)```(html)?\s*/', '', $html);
				$html = preg_replace('/\n```\s*(\n|$)/', '', $html);
				$original_html = $html;
				$regions_html = array();
				//echo $html;die();
				
				if ($regions) {
					foreach ($regions as $region) {
						$region_name = $region["name"];
						
						$start_str = "<!-- START REGION: $region_name -->";
						$start_pos = strpos($original_html, $start_str);
						
						if (is_numeric($start_pos)) {
							$end_str = "<!-- END REGION: $region_name -->";
							$end_pos = strpos($original_html, $end_str, $start_pos);
							
							if (is_numeric($end_pos)) {
								$inner_start_pos = $start_pos + strlen($start_str);
								$inner_end_pos = $end_pos;
								$end_pos += strlen($end_str);
								
								$region_inner_html = substr($original_html, $inner_start_pos, $inner_end_pos - $inner_start_pos);
								$region_outer_html = substr($original_html, $start_pos, $end_pos - $start_pos);
								
								$code = '<? echo $EVC->getCMSLayer()->getCMSTemplateLayer()->renderRegion("' . $region_name . '"); ?>';
								$html = str_replace($region_outer_html, $code, $html);
								
								$regions_html[$region_name] = $region_inner_html;
							}
						}
					}
				}
				
				return array(
					"html" => $html,
					"original_html" => $original_html,
					"regions" => $regions_html
				);
			}
			else {
				$error_msg = "Error creating html. Errors:\n- " . implode("\n- ", $errors);
				debug_log($error_msg, "error");
			}
		}
		else {
			$error_msg = "No template description to be generated or wrong input format.";
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function generateInlineCode($openai_encryption_key, $lang, $instructions, $system_instructions = null, $code = null, $selected_code = null, $selected_range = null) {
		$system_content = "1. Instructions: You are a $lang expert. Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `$lang`, or other symbols. Your code will be concatenated with previous replies and displayed directly in a $lang editor.

2. Your response must be a JSON object containing exactly the following two properties:
2.1. `code`: This property must contain ONLY the returned code as a string.

2.2. `replacement_type`: This property must contain only one of the following four strings:
2.2.1. `\"replace\"`: Indicates that the user's entire code should be replaced with the new code.
2.2.2. `\"replace_selection\"`: Indicates that specific user's code should be replaced with the new code.
2.2.3. `\"append_to_selection\"`: Indicates that the new code should be appended after a specific range.
2.2.4. `\"append\"`: Indicates that the new code should be appended at the current cursor position. If this property is chosen, the `replacement_range` should be set to `null` or to the same value than the range specified in the 4.4 instruction.

If the user wishes to replace his code, there are two scenarios:
- Replace entire code (mentioned explicitly in the user message): set `replacement_type` to `\"replace\"` and `\"replacement_range\"` set `null`.
- Replace existing code (mentioned explicitly in the user message where user wishes to add comments to his code or change or modify it, such as optimize or fix bugs, comment code): set `replacement_type` to `\"replace_selection\"` and `replacement_range` to the range correspondent to the user's code that should be replaced.

If the user wishes to add new code (instead of replacing existing code), there are two scenarios:
- No specific range mentioned (either in the user message or no selected code in 4.3): set `replacement_type` to `\"append\"` and `\"replacement_range\"` set `null`, as the user wants to append new code at the current cursor position.
- Specific range mentioned (lines or columns mentioned in the user message): set `replacement_type` to `\"append_to_selection\"` and `replacement_range` should contain where the user wants the code to be appended.

2.3. `\"replacement_range\"`: This property specifies the range of text that should be replaced or where the code should be appended, depending on the `replacement_type`. 
The value must be a JSON object with the following structure (using zero-based indexing - meaning the row with 0 index corresponds to the first line):
2.3.1. `start`: A JSON object representing the starting position of the range, containing:
2.3.1.1. `row`: A numeric property indicating the row number (zero-based index).
2.3.1.2. `column`: A numeric property indicating the column number (zero-based index).
2.3.2. `end`: A JSON object representing the ending position of the range (inclusive). This means the end position includes the last character that will be replaced or appended after. The object contains:
2.3.2.1. `row`: A numeric property indicating the row number (zero-based index).
2.3.2.2. `column`: A numeric property indicating the column number (zero-based index).

Note that the user message is using 1-based indexing, but the `replacement_range` range uses zero-based index!

3. No additional text, explanations, or formatting should be included outside the JSON object.

4. What to do: Create $lang code based on:
4.1. The user message (using 1-based indexing)" . ($system_instructions ? " and the following context:\n" . $system_instructions : "") . ";";
		
		if ($code) {
			$system_content .= "
4.2. The following code below;";
			
			if (is_array($selected_range) && $selected_range) {
				$start_row = isset($selected_range["start"]["row"]) ? $selected_range["start"]["row"] : 0;
				$start_column = isset($selected_range["start"]["column"]) ? $selected_range["start"]["column"] : 0;
				$end_row = isset($selected_range["end"]["row"]) ? $selected_range["end"]["row"] : substr_count($text, "\n") + 1;
				$end_column = isset($selected_range["end"]["column"]) ? $selected_range["end"]["column"] : 0;
				
				$system_content .= "
4.3. " . ($selected_code ? "The selected code referred to the range of instruction 4.4, this code takes priority over other code" : "There is NO selected code, but the cursor is at the position referred to the range of instruction 4.4") . ";
4.4. Current selected range in the editor:
- start row: " . $start_row . ",
- start column: " . $start_column . ",
- end row: " . $end_row . ",
- end column: " . $end_column . ";";
			}
			
			$system_content .= "

Code:
" . $code;
		}
		
		//get max_tokens, based in lines with code - a comment per line of code
		$OpenAIHandler = new OpenAIHandler($openai_encryption_key, null, "gpt-4o");
		$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
		$system_tokens = $OpenAIHandler->estimateTextTokens($system_content);
		$user_request_tokens = $OpenAIHandler->estimateTextTokens($instructions);
		$max_tokens = floor($model_tokens_limits - $system_tokens - $user_request_tokens - 10); //margin of 10 tokens
		//echo "$model_tokens_limits|$system_tokens|$user_request_tokens|$max_tokens";die();
		
		$reply = $OpenAIHandler->generateMessage($system_content, $instructions, null, array("max_tokens" => $max_tokens));
		$errors = $OpenAIHandler->getErrors();
		
		if (empty($errors)) {
			$json_reply = json_decode($reply, true);
			//echo "max_tokens:$max_tokens\n";print_r($json_reply);echo "reply:\n$reply";die();
			
			if ($json_reply) {
				$new_code = isset($json_reply["code"]) ? $json_reply["code"] : null;
				$replacement_type = isset($json_reply["replacement_type"]) ? $json_reply["replacement_type"] : null;
				$replacement_range = isset($json_reply["replacement_range"]) ? $json_reply["replacement_range"] : null;
				
				//remove ``` format, added by openai
				$new_code = preg_replace('/(^|\n)```(' . $lang . ')?\s*/', '', $new_code);
				$new_code = preg_replace('/\n```\s*(\n|$)/', '', $new_code);
				//echo "replacement_type:$replacement_type\nnew_code:\n$new_code\n\nreply:\n$reply";die();
				
				return array(
					"status" => true,
					"code" => $new_code,
					"replacement_type" => $replacement_type,
					"replacement_range" => $replacement_range
				);
			}
			else {
				$error_msg = "Error creating $lang because AI response is not a valid json format. Reply:\n$reply";
				debug_log($error_msg, "error");
			}
		}
		else {
			$error_msg = "Error creating $lang. Errors:\n- " . implode("\n- ", $errors);
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	//although this allows multiple images, its recomended to only pass 1 image, so it returns a longer description.
	public static function describeImage($openai_encryption_key, $files, $instructions = null) {
		$system_content = "You are an assistant describing images for blind people.";
		$user_content = array(
			array(
				"type" => "text", 
				"text" => "Analyze and describe the following image in detail including colors, spacing and all details. The file name is : `" . $file["name"] . "`." . ($instructions ? "\n" . $instructions : null)
			)
		);
		
		if ($files)
			foreach ($files as $file)
				$user_content[] = array(
					"type" => "image_url",
					"image_url" => array(
						"url" =>  "data:" . $file["type"] . ";base64," . utf8_decode(base64_encode(file_get_contents($file["tmp_name"]))), // either url (not local) or base64. file id is used only in assistants api.
					),
				);
		
		//get max_tokens, based in lines with code - a comment per line of code
		$OpenAIHandler = new OpenAIHandler($openai_encryption_key, null, "gpt-4o");
		$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
		$system_tokens = $OpenAIHandler->estimateTextTokens($system_content);
		$user_tokens = $OpenAIHandler->estimateTextTokens($user_content);
		$max_tokens = floor($model_tokens_limits - $system_tokens - $user_tokens - 10); //margin of 10 tokens
		//echo "$model_tokens_limits|$system_tokens|$sql_tokens|$max_tokens";die();
		
		if ($max_tokens < 500)
			$max_tokens = 500;
		
		$reply = $OpenAIHandler->generateRawMessage($system_content, $user_content, null, array("model" => "gpt-4o", "max_tokens" => $max_tokens));
		
		if (empty($errors))
			return $reply;
		else {
			$error_msg = "Error describing image. Errors:\n- " . implode("\n- ", $errors);
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function convertImageToHTML($openai_encryption_key, $files, $instructions = null) {
		$system_content = "1. Instructions: You are a WebDesigner and Designer UX expert in converting images into HTML, CSS and Javascript building beautiful layouts. Do not add any extra sentences, explanations, or code formatting such as backticks, '```', `html`, or other symbols. Your response should consist solely of HTML code, with no additional formatting or text. The generated HTML should contain the doctype, html, head and body tags. It can also include the necessary `<link>` and `<script>` tags for external ui libraries, like bootstrap, jquery-ui or other libraries you wish to add. Add placeholder URLs to the images unless other instructions are provided in the user message.
2. What to do: Create HTML code based on the user images.";
		$user_content = array();
		
		if ($instructions)
			$user_content[] = array(
				"type" => "text", 
				"text" => $instructions
			);
		
		if ($files)
			foreach ($files as $file)
				$user_content[] = array(
					"type" => "image_url",
					"image_url" => array(
						"url" =>  "data:" . $file["type"] . ";base64," . utf8_decode(base64_encode(file_get_contents($file["tmp_name"]))), // either url (not local) or base64. file id is used only in assistants api.
					),
				);
		
		//get max_tokens, based in lines with code - a comment per line of code
		$OpenAIHandler = new OpenAIHandler($openai_encryption_key, null, "gpt-4o");
		$model_tokens_limits = $OpenAIHandler->getDefaultModelTokensLimits();
		
		/*$system_tokens = $OpenAIHandler->estimateTextTokens($system_content);
		$user_tokens = $OpenAIHandler->estimateTextTokens($user_content);
		$max_tokens = floor($model_tokens_limits - $system_tokens - $user_tokens - 10); //margin of 10 tokens
		//echo "$model_tokens_limits|$system_tokens|$user_tokens|$max_tokens";die();
		
		if ($max_tokens < 500)
			$max_tokens = 500;
		*/
		$max_tokens = $model_tokens_limits; //for some reason, the model_tokens_limits works fine
		
		$html = $OpenAIHandler->generateRawMessage($system_content, $user_content, null, array("model" => "gpt-4o", "max_tokens" => $max_tokens));
		
		if (empty($errors)) {
			//remove ``` format, added by openai
			$html = preg_replace('/(^|\n)```(html)?\s*/', '', $html);
			$html = preg_replace('/\n```\s*(\n|$)/', '', $html);
				
			return array(
				"html" => $html
			);
		}
		else {
			$error_msg = "Error converting image to HTML. Errors:\n- " . implode("\n- ", $errors);
			debug_log($error_msg, "error");
		}
		
		return null;
	}
	
	public static function mergeCommentsIntoCode($input_code, $reply_code) {
		$input_lines = explode("\n", $input_code);  // Break the input code into lines
		$reply_lines = explode("\n", $reply_code);  // Break the reply code into lines
		$output_lines = [];  // Array to store the output code with comments
		$reply_lines_total = count($reply_lines);
		
		// Create an index of the lines in reply_code with their respective comments
		$reply_index_for_comments = [];
		$comment_buffer = '';
		
		foreach ($reply_lines as $reply_index_line => $reply_line) {
			// If it's a comment, buffer it
			if (preg_match('/^(\/\/|#|\/\*.*\*\/)/', trim($reply_line)))
				$comment_buffer .= "\n" . $reply_line;
			else if ($comment_buffer !== '') { // If it's code, store the comment buffer for that line
				$trimmed_reply_line_hash = trim($reply_line);
				
				if (!is_array($reply_index_for_comments[$trimmed_reply_line_hash]))
					$reply_index_for_comments[$trimmed_reply_line_hash] = array();
				
				$reply_index_for_comments[$trimmed_reply_line_hash][] = trim($comment_buffer);
				$comment_buffer = '';  // Reset the comment buffer
			}
		}

		// Now process input_code and find the corresponding comments from reply_code
		$repeated_lines_index = array();
		
		foreach ($input_lines as $input_index => $input_line) {
			$trimmed_input_line_hash = trim($input_line);
			
			if ($trimmed_input_line_hash) { //avoid searching of blank lines
				// Append the comment buffer to the output
				if (isset($reply_index_for_comments[$trimmed_input_line_hash])) {
					$i = isset($repeated_lines_index[$trimmed_input_line_hash]) ? $repeated_lines_index[$trimmed_input_line_hash] : 0;
					
					if (isset($reply_index_for_comments[$trimmed_input_line_hash][$i])) {
						//get indentation from input_line
						preg_match("/^(\s+)/", $input_line, $match, PREG_OFFSET_CAPTURE);
						$indentation = $match && isset($match[1][0]) ? $match[1][0] : "";
						
						//add comment
						$comment = $reply_index_for_comments[$trimmed_input_line_hash][$i];
						$comment = $indentation . str_replace("\n", "\n" . $indentation, $comment);
						$output_lines[] = $comment;
						
						//update index for next repeated line
						$repeated_lines_index[$trimmed_input_line_hash] = $i + 1;
					}
				}
			}
			
			// Add the current input line to the output
			$output_lines[] = $input_line;
		}

		// Convert the output_lines array back to a string
		return implode("\n", $output_lines);
	}

	public static function getTablesDetails($user_global_variables_file_path, $user_beans_folder_path, $workflow_paths_id, $bean_name, $bean_file_name, $db_broker, $db_driver, $type, $db_table, $global_default_db_driver_broker) {
		$items = array();
		
		$user_global_variables_files_path = array($user_global_variables_file_path);
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_files_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();

		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_files_path);
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
		
		if ($obj && (is_a($obj, "Layer") || is_a($obj, "DB"))) {
			$tables = $db_driver_props = null;
			
			if ($type == "diagram") {
				$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $db_driver);
				$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
				$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
				
				$tables = $WorkFlowDataAccessHandler->getTasksAsTables();
			}
			else if (!$db_broker || 
				(!is_a($obj, "DataAccessLayer") && !is_a($obj, "DBLayer") && !is_a($obj, "DB") && !$obj->getBroker($db_broker, true))
			) {
				$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_files_path);
				$db_driver_props = WorkFlowBeansFileHandler::getLayerDBDriverProps($user_global_variables_files_path, $user_beans_folder_path, $obj, $db_driver);
			}
			
			$db_options = array("db_driver" => $db_driver);
			
			if (!is_a($obj, "DataAccessLayer") && !is_a($obj, "DBLayer") && !is_a($obj, "DB")) { //in case of form module
				$db_driver_db_broker = WorkFlowBeansFileHandler::getLayerLocalDBBrokerNameForChildBrokerDBDriver($user_global_variables_file_path, $user_beans_folder_path, $obj, $db_driver);
				$db_options["db_broker"] = $db_driver_db_broker ? $db_driver_db_broker : $global_default_db_driver_broker;
			}
			
			if ($type == "diagram") {
				$items = $tables;
				
				if ($db_table)
					$items = array(
						$db_table => $tables[$db_table]
					);
			}
			else {
				if (is_a($obj, "DB"))
					$tables = $obj->listTables();
				else if (is_a($obj, "DBLayer"))
					$tables = $obj->getFunction("listTables", null, $db_options);
				else if ($db_broker && $obj->getBroker($db_broker, true))
					$tables = $obj->getBroker($db_broker)->getFunction("listTables", null, $db_options);
				else if ($db_driver_props)
					$tables = $WorkFlowDBHandler->getDBTables($db_driver_props[1], $db_driver_props[2]);
				
				if ($tables)
					foreach ($tables as $table) 
						if (isset($table["name"])) {
							$table_name = $table["name"];
							
							if (!$db_table || $table_name == $db_table) {
								$fks = null;
								
								if (is_a($obj, "DB")) {
									$attributes = $obj->listTableFields($db_table);
									$fks = $obj->listForeignKeys($db_table);
								}
								else if (is_a($obj, "DBLayer")) {
									$attributes = $obj->getFunction("listTableFields", $db_table, $db_options);
									$fks = $obj->getFunction("listForeignKeys", $db_table, $db_options);
								}
								else if ($db_broker && $obj->getBroker($db_broker, true)) {
									$attributes = $obj->getBroker($db_broker)->getFunction("listTableFields", $db_table, $db_options);
									$fks = $obj->getBroker($db_broker)->getFunction("listForeignKeys", $db_table, $db_options);
								}
								else if ($db_driver_props) {
									$attributes = $WorkFlowDBHandler->getDBTableAttributes($db_driver_props[1], $db_driver_props[2], $db_table);
									
									$DBDriver = $WorkFlowDBHandler->getBeanObject($db_driver_props[1], $db_driver_props[2]);
									$fks = $DBDriver ? $DBDriver->listForeignKeys($db_table) : null;
								}
								
								if (is_array($fks))
									foreach ($fks as $fk)
										if ($fk && $fk["child_column"]) {
											$child_column = $fk["child_column"];
											
											if ($attributes[$child_column]) {
												if (empty($attributes[$child_column]["fk"]))
													$attributes[$child_column]["fk"] = array();
												
												$attributes[$child_column]["fk"][] = array(
													"attribute" => isset($fk["parent_column"]) ? $fk["parent_column"] : null,
													"table" => isset($fk["parent_table"]) ? $fk["parent_table"] : null
												);
											}
										}
								
								$items[$table_name] = $attributes;
							}
						}
			}
		}

		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return $items;
	}
}
?>
