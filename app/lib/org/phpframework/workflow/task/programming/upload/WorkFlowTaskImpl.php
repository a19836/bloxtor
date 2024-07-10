<?php
namespace WorkFlowTask\programming\upload;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");
include_once get_lib("org.phpframework.util.web.UploadHandler");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props && $props["method_name"] == "upload" && $props["method_static"] && $props["method_obj"] == "UploadHandler") {
			$args = $props["method_args"];
			
			$file = $args[0]["value"];
			$file_type = $args[0]["type"];
			$dst_folder = $args[1]["value"];
			$dst_folder_type = $args[1]["type"];
			$validation = $args[2]["value"];
			$validation_type = $args[2]["type"];
			
			$props["file"] = $file;
			$props["file_type"] = self::getConfiguredParsedType($file_type);
			$props["dst_folder"] = $dst_folder;
			$props["dst_folder_type"] = self::getConfiguredParsedType($dst_folder_type);
			
			if ($validation_type == "array") {
				$param_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $validation . "\n?>");
				//print_r($param_stmts);
				$validation = $WorkFlowTaskCodeParser->getArrayItems($param_stmts[0]->items);
			}
			
			$props["validation"] = $validation;
			$props["validation_type"] = self::getConfiguredParsedType($validation_type, array("", "string", "variable", "array"));
			
			unset($props["method_name"]);
			unset($props["method_obj"]);
			unset($props["method_args"]);
			unset($props["method_static"]);
			
			$props["label"] = "Upload " . self::prepareTaskPropertyValueLabelFromCodeStmt($file);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
			//print_r($props);die();
			
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$validation_type = $raw_data["childs"]["properties"][0]["childs"]["validation_type"][0]["value"];
		if ($validation_type == "array") {
			$validation = $raw_data["childs"]["properties"][0]["childs"]["validation"];
			$validation = self::parseArrayItems($validation);
		}
		else {
			$validation = $raw_data["childs"]["properties"][0]["childs"]["validation"][0]["value"];
		}
		
		$properties = array(
			"method" => $raw_data["childs"]["properties"][0]["childs"]["method"][0]["value"],
			"file" => $raw_data["childs"]["properties"][0]["childs"]["file"][0]["value"],
			"file_type" => $raw_data["childs"]["properties"][0]["childs"]["file_type"][0]["value"],
			"dst_folder" => $raw_data["childs"]["properties"][0]["childs"]["dst_folder"][0]["value"],
			"dst_folder_type" => $raw_data["childs"]["properties"][0]["childs"]["dst_folder_type"][0]["value"],
			"validation" => $validation,
			"validation_type" => $validation_type,
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$file = self::getVariableValueCode($properties["file"], $properties["file_type"]);
		$dst_folder = self::getVariableValueCode($properties["dst_folder"], $properties["dst_folder_type"]);
		
		$validation_type = $properties["validation_type"];
		if ($validation_type == "array")
			$validation = self::getArrayString($properties["validation"]);
		else
			$validation = self::getVariableValueCode($properties["validation"], $validation_type);
		
		$code = $prefix_tab . $var_name . "UploadHandler::upload($file, $dst_folder";
		$code .= $validation ? ", $validation" : "";
		$code .= ");\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
