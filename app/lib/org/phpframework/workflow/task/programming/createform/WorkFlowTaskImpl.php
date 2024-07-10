<?php
namespace WorkFlowTask\programming\createform;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");
include_once get_lib("org.phpframework.util.text.TextSanitizer");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	/*
	<?php
	//START: task[createform][Call HtmlFormHandler::createHtmlForm()]
	$x = HtmlFormHandler::createHtmlForm(array(
		"with_form" => 1,
		"form_id" => "",
		"form_method" => "post",
		"form_class" => "",
		"form_type" => "",
		"form_on_submit" => "",
		"form_action" => "",
		"form_containers" => array(
			0 => array(
				"container" => array(
					"class" => "edit_featured_object",
					"href" => "",
					"target" => "",
					"title" => "",
					"title_position" => "auto",
					"previous_html" => "",
					"next_html" => "",
					"elements" => array(
						0 => array(
							"field" => array(
								"type" => "hidden",
								"class" => "objects_group_id hidden",
								"label" => array(
									"value" => "",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => ""
								),
								"input" => array(
									"name" => "objects_group_id",
									"class" => "",
									"value" => "#objects_group_id#",
									"place_holder" => "",
									"href" => "",
									"target" => "",
									"src" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => "",
									"confirmation" => "",
									"confirmation_message" => "",
									"allow_null" => 1,
									"validation_message" => "",
									"validation_type" => "",
									"validation_regex" => "",
									"validation_func" => "",
									"min_length" => "",
									"max_length" => "",
									"min_value" => "",
									"max_value" => "",
									"min_words" => "",
									"max_words" => ""
								),
								"help" => array(
									"value" => "",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => ""
								)
							)
						),
						1 => array(
							"field" => array(
								"type" => "hidden",
								"class" => "hidden",
								"label" => array(
									"value" => "",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => ""
								),
								"input" => array(
									"name" => "tags",
									"class" => "",
									"value" => "Destaques",
									"place_holder" => "",
									"href" => "",
									"target" => "",
									"src" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => "",
									"confirmation" => "",
									"confirmation_message" => "",
									"allow_null" => 1,
									"validation_message" => "",
									"validation_type" => "",
									"validation_regex" => "",
									"validation_func" => "",
									"min_length" => "",
									"max_length" => "",
									"min_value" => "",
									"max_value" => "",
									"min_words" => "",
									"max_words" => ""
								),
								"help" => array(
									"value" => "",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => ""
								)
							)
						),
						2 => array(
							"field" => array(
								"type" => "text",
								"class" => "title",
								"label" => array(
									"value" => "Title: ",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => ""
								),
								"input" => array(
									"name" => "object[title]",
									"class" => "",
									"value" => "#object[title]#",
									"place_holder" => "",
									"href" => "",
									"target" => "",
									"src" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => "",
									"confirmation" => "",
									"confirmation_message" => "",
									"allow_null" => 1,
									"validation_message" => "",
									"validation_type" => "",
									"validation_regex" => "",
									"validation_func" => "",
									"min_length" => "",
									"max_length" => "",
									"min_value" => "",
									"max_value" => "",
									"min_words" => "",
									"max_words" => ""
								),
								"help" => array(
									"value" => "",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => ""
								)
							)
						),
						3 => array(
							"field" => array(
								"type" => "textarea",
								"class" => "summary",
								"label" => array(
									"value" => "Summary: ",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => ""
								),
								"input" => array(
									"name" => "object[summary]",
									"class" => "",
									"value" => "#object[summary]#",
									"place_holder" => "",
									"href" => "",
									"target" => "",
									"src" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => "",
									"confirmation" => "",
									"confirmation_message" => "",
									"allow_null" => 1,
									"validation_message" => "",
									"validation_type" => "",
									"validation_regex" => "",
									"validation_func" => "",
									"min_length" => "",
									"max_length" => "",
									"min_value" => "",
									"max_value" => "",
									"min_words" => "",
									"max_words" => ""
								),
								"help" => array(
									"value" => "",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => ""
								)
							)
						),
						4 => array(
							"field" => array(
								"type" => "text",
								"class" => "link",
								"label" => array(
									"value" => "Link:",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => ""
								),
								"input" => array(
									"name" => "object[link]",
									"class" => "",
									"value" => "#object[link]#",
									"place_holder" => "",
									"href" => "",
									"target" => "",
									"src" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => "",
									"confirmation" => "",
									"confirmation_message" => "",
									"allow_null" => 1,
									"validation_message" => "",
									"validation_type" => "",
									"validation_regex" => "",
									"validation_func" => "",
									"min_length" => "",
									"max_length" => "",
									"min_value" => "",
									"max_value" => "",
									"min_words" => "",
									"max_words" => ""
								),
								"help" => array(
									"value" => "",
									"class" => "",
									"title" => "",
									"title_position" => "",
									"width" => "",
									"height" => "",
									"offset" => "",
									"previous_html" => "",
									"next_html" => " "
								)
							)
						)
					)
				)
			)
		), $input);
	?>
	*/
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_obj = strtolower($props["method_obj"]);
			$method_name = strtolower($props["method_name"]);
			
			if ($method_obj == "htmlformhandler" && $method_name == "createhtmlform") {
				$args = $props["method_args"];
				
				$form_settings_data = $args[0]["value"];
				$form_settings_data_type = $args[0]["type"];
				$form_input_data = $args[1]["value"];
				$form_input_data_type = $args[1]["type"];
				
				if ($form_settings_data_type == "array") {
					$form_settings_data_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $form_settings_data . "\n?>");
					//print_r($form_settings_data_stmts);
					$form_settings_data = $WorkFlowTaskCodeParser->getArrayItems($form_settings_data_stmts[0]->items);
					//print_r($form_settings_data);
				}
				
				if ($form_input_data_type == "array") {
					$form_input_data_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $form_input_data . "\n?>");
					//print_r($form_input_data_stmts);
					$form_input_data = $WorkFlowTaskCodeParser->getArrayItems($form_input_data_stmts[0]->items);
				}
				
				unset($props["method_obj"]);
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["form_settings_data"] = $form_settings_data;
				$props["form_settings_data_type"] = self::getConfiguredParsedType($form_settings_data_type, array("", "string", "variable", "array", "settings", "ptl"));
				$props["form_input_data"] = $form_input_data;
				$props["form_input_data_type"] = self::getConfiguredParsedType($form_input_data_type, array("", "string", "variable", "array"));
				
				$props["label"] = "Draw a form";
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
			//print_r($props);
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		//print_r($raw_data);die();
		
		$form_settings_data_type = $raw_data["childs"]["properties"][0]["childs"]["form_settings_data_type"][0]["value"];
		if ($form_settings_data_type == "array") {
			$form_settings_data = $raw_data["childs"]["properties"][0]["childs"]["form_settings_data"];
			$form_settings_data = self::parseArrayItems($form_settings_data);
			
			//print_r($form_settings_data);die();
		}
		else if ($form_settings_data_type == "ptl") {
			$form_settings_data = $raw_data["childs"]["properties"][0]["childs"]["form_settings_data"];
			//print_r($form_settings_data);
		
			$form_settings_data = \MyXML::complexArrayToBasicArray($form_settings_data[0]["childs"], array("lower_case_keys" => true));
			if ($form_settings_data["ptl"]) {
				//print_r($form_settings_data["ptl"]);
				$external_vars = array();
				
				if (is_array($form_settings_data["ptl"]["external_vars"])) {
					if(isset($form_settings_data["ptl"]["external_vars"]["key_type"])) //we need to use the key_type, instead of the key, bc the key may be null
						$form_settings_data["ptl"]["external_vars"] = array($form_settings_data["ptl"]["external_vars"]);
				
					foreach ($form_settings_data["ptl"]["external_vars"] as $external_var)
						if ($external_var["key"] && $external_var["value"])
							$external_vars[ $external_var["key"] ] = (substr($external_var["value"], 0, 1) == '$' ? '' : '$') . $external_var["value"];
				}
				
				$form_settings_data["ptl"]["external_vars"] = $external_vars;
				//print_r($form_settings_data);
			}
		}
		else
			$form_settings_data = $raw_data["childs"]["properties"][0]["childs"]["form_settings_data"][0]["value"];
		
		$form_input_data_type = $raw_data["childs"]["properties"][0]["childs"]["form_input_data_type"][0]["value"];
		if ($form_input_data_type == "array") {
			$form_input_data = $raw_data["childs"]["properties"][0]["childs"]["form_input_data"];
			$form_input_data = self::parseArrayItems($form_input_data);
		}
		else
			$form_input_data = $raw_data["childs"]["properties"][0]["childs"]["form_input_data"][0]["value"];
		
		$properties = array(
			"form_settings_data" => $form_settings_data,
			"form_settings_data_type" => $form_settings_data_type,
			"form_input_data" => $form_input_data,
			"form_input_data_type" => $form_input_data_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		//print_r($properties);die();
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$form_settings_data_type = $properties["form_settings_data_type"];
		if ($form_settings_data_type == "array") {
			//2019-10-17: In case there was an end-line escaped (this is: "\\n" or '\n'), we must escaped the previous slash correspondent to the end-line, otherwise we will have \n which then will write a real end-line and if it is inside of javascript, it will give a javascript error.
			if ($properties["form_settings_data"])
				foreach ($properties["form_settings_data"] as $i => $prop)
					if (($prop["key"] == "form_css" || $prop["key"] == "form_js") && $prop["key_type"] == "string" && $prop["value_type"] == "string")
						$properties["form_settings_data"][$i]["value"] = \TextSanitizer::replaceIfNotEscaped('\n', '\\\n', $prop["value"]); 
			
			$form_settings_data = self::getArrayString($properties["form_settings_data"], $prefix_tab);
		}
		else if ($form_settings_data_type == "ptl")
			$form_settings_data = self::getFormSettingsPTLString($properties["form_settings_data"], $prefix_tab);
		else
			$form_settings_data = self::getVariableValueCode($properties["form_settings_data"], $form_settings_data_type);
		//print_r($form_settings_data);die();
		
		$form_input_data_type = $properties["form_input_data_type"];
		if ($form_input_data_type == "array")
			$form_input_data = self::getArrayString($properties["form_input_data"], $prefix_tab);
		else
			$form_input_data = self::getVariableValueCode($properties["form_input_data"], $form_input_data_type);
		
		$code  = $prefix_tab . $var_name;
		$code .= "HtmlFormHandler::createHtmlForm(";
		$code .= ($form_settings_data ? $form_settings_data : "null") . ", ";
		$code .= $form_input_data ? $form_input_data : "null";
		$code .= ");\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
	
	private static function getFormSettingsPTLString($form_settings_data, $prefix_tab = "") {
		$code = "";
		
		if (is_array($form_settings_data)) {
			$code .= $prefix_tab . "array(";
			$first_elm = true;
			
			foreach ($form_settings_data as $k => $v) {
				if ($k == "ptl" && is_array($v))
					$v = substr(self::getFormSettingsPTLString($v, "$prefix_tab\t"), strlen("$prefix_tab\t"));
				else if ($k == "external_vars" && is_array($v)) {
					$aux = "array(";
					$f = true;
					foreach ($v as $vk => $vv) {
						$aux .= ($f ? "" : ", ") . "\n$prefix_tab\t\t'$vk' => $vv";
						$f = false;
					}
					$aux .= "\n$prefix_tab\t)";
					$v = $aux;
				}
				else if ($k == "code" || $k == "input_data_var_name" || $k == "idx_var_name") {
					if ((substr($v, 0, 1) == '"' && substr($v, -1) == '"') || (substr($v, 0, 1) == "'" && substr($v, -1) == "'"))
						$v = $v; //is already code.
					else {
						//2019-10-17: Deprecated. 
						//$v = "\"" . str_replace('\\\\$', '\\$', addcslashes($v, '\\"')) . "\""; //must contain the escape for the slash, otherwise if exist any unescaped slash, it will get lost on the way or it will not get other slashes escaped inside of some ptl code and then will give some php error...
						
						$v = \TextSanitizer::replaceIfNotEscaped('\"', '\\\"', $v); //2019-10-17: In case there was a double quote escaped before (this is: \"), we must escaped the previous slash correspondent to the double quotes, otherwise we will have \\" which then will give a php error. IMPORTANT: replaceIfNotEscaped must execute first and only then the addcslashes. 
						
						$v = "\"" . addcslashes($v, '"') . "\""; //2019-10-17
						
						$v = \TextSanitizer::replaceIfNotEscaped('\n', '\\\n', $v); //2019-10-17: In case there was an end-line escaped (this is: "\\n" or '\n'), we must escaped the previous slash correspondent to the end-line, otherwise we will have \n which then will write a real end-line and if it is inside of javascript, it will give a javascript error.
					}
				}
				else
					$v = var_export($v, true);
				
				$code .= ($first_elm ? "" : ", ") . "\n$prefix_tab\t" . (is_numeric($k) ? $k : '"' . $k . '"') . " => " . $v;
				$first_elm = false;
			}
			$code .= "\n$prefix_tab)";
		}
		
		return $code;
	}
}
?>
