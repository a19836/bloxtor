<?php
include_once get_lib("org.phpframework.util.xml.MyXML");
include_once get_lib("org.phpframework.util.xml.MyXMLArray");
include_once $EVC->getUtilPath("WorkFlowBeansConverter");

class WorkFlowTasksFileHandler {
	private $tasks_file_path;
	private $tasks;
	
	private static $task_layer_tags = array(
		"dbdriver" => "dbdriver",
		"db" => "db",
		"dataaccess" => "dataaccess",
		"businesslogic" => "businesslogic",
		"presentation" => "presentation",
	);
	
	public function __construct($tasks_file_path) {
		$this->tasks_file_path = $tasks_file_path;
	}
	
	public function init() {
		$this->tasks = array();
		
		$xml_content = file_exists($this->tasks_file_path) ? file_get_contents($this->tasks_file_path) : "";
		
		if (!empty($xml_content)) {
			$MyXML = new MyXML($xml_content);
			$arr = $MyXML->toArray();
			$new_arr = $MyXML->complexArrayToBasicArray($arr, array("lower_case_keys" => true, "trim" => true));
			$this->tasks = $new_arr["tasks"];
			
			if ($this->tasks["container"] && isset($this->tasks["container"]["id"]))
				$this->tasks["container"] = array($this->tasks["container"]);
			
			if ($this->tasks["task"] && isset($this->tasks["task"]["id"]))
				$this->tasks["task"] = array($this->tasks["task"]);
		}
	}
	
	public function getTasks() {
		return $this->tasks;
	}
	
	public static function getTaskLayerTags() {
		return self::$task_layer_tags;
	}
	
	public function getTasksByLayerTag($tag, $limit = -1) {
		$tasks = array();
		
		if (is_array($this->tasks["task"])) {
			foreach ($this->tasks["task"] as $task) {
				if ($limit == 0)
					break;
				
				if ($task["tag"] == self::$task_layer_tags[$tag]) {
					$tasks[] = $task;
					
					if ($limit > 0) {
						--$limit;
						
						if ($limit == 0)
							break;
					}
				}
			}
		}
		
		return $tasks;
	}
	
	public function getWorkflowData() {
		return self::getWorkflowDataByTasks($this->tasks);
	}
	
	public function getWorkflowDataWithInnerTasks() {
		$tasks = self::getWorkflowDataByTasks($this->tasks);
		
		if ($tasks && $tasks["tasks"])
			foreach ($tasks["tasks"] as $task_id => $task) {
				$task = self::getTaskWithInnerTasks($tasks["tasks"], $task_id);
				
				if ($task) //This if is important bc the $task can be null, which means it was previously moved inside to another task. If this happens and we don't have this if here, we are creating a new array item with null, this is: $tasks["tasks"][$task_id] = null;
					$tasks["tasks"][$task_id] = $task;
			}
		
		return $tasks;
	}
	
	private function getTaskWithInnerTasks(&$tasks, $task_id) {
		if ($tasks && $tasks[$task_id]) {
			if ($tasks[$task_id]["tasks"])
				foreach ($tasks[$task_id]["tasks"] as $inner_task_id => $html_selector) 
					if ($inner_task_id != $task_id && $tasks[$inner_task_id]) {
						$tasks[$task_id]["tasks"][$inner_task_id] = self::getTaskWithInnerTasks($tasks, $inner_task_id);
						unset($tasks[$inner_task_id]);
					}
			
			return $tasks[$task_id];
		}
		
		return null;
	}
	
	public static function getWorkflowDataByTasks($tasks) {
		$tasks["container"] = isset($tasks["container"]["id"]) ? array($tasks["container"]) : $tasks["container"];
		$tasks["task"] = isset($tasks["task"]["id"]) ? array($tasks["task"]) : $tasks["task"];
		
		$parsed_tasks = array();
		$parsed_tasks["settings"] = array();
		$parsed_tasks["containers"] = array();
		$parsed_tasks["tasks"] = array();
		
		if ($tasks["settings"])
			foreach ($tasks["settings"] as $key => $value) {
				if (
					(substr($value, 0, 1) == "{" && substr($value, -1) == "}") || 
					(substr($value, 0, 1) == "[" && substr($value, -1) == "]")
				)
					$value = json_decode($value, true);
				
				$parsed_tasks["settings"][$key] = $value;
			}
		
		foreach ($tasks as $key => $value) 
			if ($key != "container" && $key != "task" && $key != "settings")
				$parsed_tasks[$key] = $value;
		
		foreach ($tasks as $key => $value)
			if (($key == "container" || $key == "task") && is_array($value)) {
				foreach ($value as $obj) 
					$parsed_tasks[$key . "s"][ $obj["id"] ] = $obj;
			}
		
		return $parsed_tasks;
	}
	
	public static function createTasksFile($task_file_path, $data, $file_read_date = null) {
		if (!empty($task_file_path)) {
			$folder = dirname($task_file_path);
			
			if (is_dir($folder) || mkdir($folder, 0775, true)) {
				$file_write_date = file_exists($task_file_path) ? filemtime($task_file_path) : null;
				
				if ($file_read_date && $file_write_date && $file_write_date > $file_read_date) 
					return 2;
				
				$xml = self::convertTasksArrayIntoXml($data);
				//echo $xml;
				
				return file_put_contents($task_file_path, $xml) > 0;
			}
		}
		
		return false;
	}
	
	public static function convertTasksArrayIntoXml($data) {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= "<tasks>\n";
		
		if (is_array($data)) {
			if (is_array($data["settings"])) {
				$settings_xml = "";
				
				foreach ($data["settings"] as $settings_name => $settings_value)
					if ($settings_name && !is_numeric($settings_name)) {
						if (is_array($settings_value) || is_object($settings_value))
							$settings_value = json_encode($settings_value);
						
						if (!is_numeric($settings_value) && !is_bool($settings_value))
							$settings_value = "<![CDATA[$settings_value]]>";
						
						$settings_xml .= "\t\t<$settings_name>$settings_value</$settings_name>\n";
					}
				
				if ($settings_xml) {
					$xml .= "\t<settings>\n";
					$xml .= $settings_xml;
					$xml .= "\t</settings>\n";
				}
			}
			
			foreach ($data as $key => $value) 
				if ($key != "containers" && $key != "tasks" && $key != "settings") {
					if (is_array($value)) {
						$xml .= "\t<$key>\n";
						$xml .= self::getNodeToXML($value, "\t\t");
						$xml .= "\t</$key>\n";
					}
					else
						$xml .= "\t<$key>" . self::getNodeToXML($value) . "</$key>\n";
				}
			
			foreach ($data as $key => $value) 
				if (is_array($value) && ($key == "containers" || $key == "tasks")) {
					$node_name = substr($key, 0, -1); //remove plural
					
					foreach ($value as $obj_id => $obj) { //note that the $obj_id may be a numeric key, so we should not use it
						$xml .= "\t<$node_name";
						
						if ($key == "tasks") {
							if ($obj["start"] > 0) 
								$xml .= " start=\"" . $obj["start"] . "\"";
							
							unset($obj["start"]);
						}
						
						$xml .= ">\n";
						$xml .= self::getNodeToXML($obj, "\t\t");
						$xml .= "\t</$node_name>\n";
					}
				}
		}	
		
		$xml .= "</tasks>";
		
		return $xml;
	}
	
	private static function getNodeToXML($arr, $prefix = "") {
	//print_r($arr);
		$xml = "";

		if (is_array($arr)) {
			foreach ($arr as $key => $value) {
				if (is_array($value)) {
					$non_numeric_keys_arr = array();
					$numeric_keys_arr = array();
					
					foreach ($value as $i => $item) {
						if (is_numeric($i)) {
							$numeric_keys_arr[$i] = $item;
						}
						else {
							$non_numeric_keys_arr[$i] = $item;
						}
					}
					
					if (!empty($non_numeric_keys_arr) && empty($numeric_keys_arr)) {
						$numeric_keys_arr = array($value);
						$non_numeric_keys_arr = array();
					}
					
					foreach ($numeric_keys_arr as $item) {
						if (is_array($item)) {
						//echo self::getNodeToXML($item, $prefix . "\t");echo "!!!$item($is_array_keys_only_numeric)!!!";print_r($item);
							$xml .= "$prefix<$key>\n";
							$xml .= self::getNodeToXML($item, $prefix . "\t");
							$xml .= "$prefix</$key>\n";
						}
						else {
							$xml .= "$prefix<$key>" . self::getNodeToXML($item) . "</$key>\n";
						}
					}
					
					//This is for the exceptional cases, where the input array has numeric and string keys at the same time.
					if (!empty($non_numeric_keys_arr) && !empty($numeric_keys_arr)) {
						$xml .= self::getNodeToXML($non_numeric_keys_arr, $prefix);
					}
				}
				else {
					$xml .= "$prefix<$key>" . self::getNodeToXML($value) . "</$key>\n";
				}
			}
		}
		else {
			$xml .= is_numeric($arr) || is_bool($value) ? $arr : (!empty($arr) ? "<![CDATA[$arr]]>" : "");
		}

		return $xml;
	}
	
	public static function updateTaskProperties($task_file_path, $task_label, $new_properties) {
		$content = file_get_contents($task_file_path);
		$MyXML = new MyXML($content);
		$tasks = $MyXML->toArray(array("lower_case_keys" => true));

		$changed = false;
		//echo "<pre>$task_label:";print_r($tasks);die();
		
		if (is_array($tasks["tasks"][0]["childs"]["task"])) {
			$t = count($tasks["tasks"][0]["childs"]["task"]);
			for ($i = 0; $i < $t; $i++) {
				$task = $tasks["tasks"][0]["childs"]["task"][$i];
				
				if (strtolower($task["childs"]["label"][0]["value"]) == strtolower($task_label)) {
					//echo "<pre>$task_label:";print_r($new_properties);print_r($task);die();
					
					foreach ($new_properties as $var_name => $var_value) {
						$task_var_name = strtolower($var_name);
						$value = $task["childs"]["properties"][0]["childs"][$task_var_name][0]["value"];
						
						if (isset($value)) {
							//if (!self::isPHPVariable($value)) {//Edit even if it is a variable
								$task["childs"]["properties"][0]["childs"][$task_var_name][0]["value"] = $new_properties[$var_name];
				
								$changed = true;
							//}
						}
					}
		
					$tasks["tasks"][0]["childs"]["task"][$i] = $task;
		
					break;
				}
			}
		}

		if ($changed) {
			//echo "<pre>$task_label:";print_r($tasks["tasks"][0]["childs"]["task"][$i]);die();
			$MyXMLArray = new MyXMLArray($tasks);
			$xml = $MyXMLArray->toXML(array("lower_case_keys" => true));
			$xml = '<?xml version="1.0" encoding="UTF-8"?>' . str_replace("&amp;", "&", $xml);
			//echo $xml;die();
			
			return file_put_contents($task_file_path, $xml);
		}
		
		return true;
	}
	
	public static function getTaskFilePathByPath($workflow_paths_id, $path, $extra = false) {
		$path = $workflow_paths_id[$path] ? $workflow_paths_id[$path] : $path;
		
		$path_parts = pathinfo($path);
		$path = $path_parts['dirname'] . "/" . $path_parts['filename'] . $extra . ($path_parts['extension'] ? "." . $path_parts['extension'] : "");
		
		return $path;
	}
	
	public static function getDBDiagramTaskFilePath($workflow_paths_id, $path, $db_driver_label) {
		$extra = "_" . WorkFlowBeansConverter::getObjectNameFromRawLabel($db_driver_label);
		$path = self::getTaskFilePathByPath($workflow_paths_id, $path, $extra);
		
		//echo "path:$path<br>";
		return $path;
	}
	
	private static function isPHPVariable($value) {
		return strpos($value, '$') !== false;
	}
}
?>
