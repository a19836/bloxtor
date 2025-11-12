<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class WorkFlowTaskException extends Exception {
	public $problem;

	public function __construct($error_num, $value = array()) {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "Error parsing xml for task with ID: '$value'!"; break;
			case 2: $this->problem = "Task with ID: '$value' does NOT exist!"; break;
			case 3: $this->problem = "Invalid Task Class for class: $value! All the Task classes must extend the WorkFlowTask class, which is not this case."; break;
			case 4: $this->problem = "Error trying to create class object for class: $value."; break;
			case 5: $this->problem = "Error \$task[OBJ] variable is not an object from the class: $value_0. Object " . get_class($value_1) . " is incorrect! Probably this task type does NOT exist!"; break;
			case 6: $this->problem = "Error Could Not clone $value_0 obj class. Error \$task[OBJ] variable is not an object from the class: $value_0. Object " . get_class($value_1) . " is incorrect!"; break;
			case 7: $this->problem = "Workflow webroot folder path cannot be empty!"; break;
			case 8: $this->problem = "Error creating folder: '$value'!"; break;
			case 9: $this->problem = "Error copying file from: '$value_0' to '$value_1'!"; break;
			case 10: $this->problem = "Wrong namespace in file: '$value'!"; break;
			case 11: $this->problem = ""; break;//DEPRECATED. If needed in the future, you can use it
			case 12: $this->problem = "Class don't exist: '$value'!"; break;
			case 13: $this->problem = "Workflow webroot folder domain cannot be empty!"; break;
			case 14: $this->problem = "Error trying to get the URL prefix for the webroot of the task: '$value'!"; break;
			case 15: $this->problem = "Task path cannot be undefined for task: '$value'!"; break;
		}
	}
}
?>
