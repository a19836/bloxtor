<?php
class CMSModuleLayerException extends Exception {
	public $problem;
	public $file_not_found = false;

	public function __construct($error_num, $value = "") {
		$value_0 = $value_1 = null;
		
		if (is_array($value)) {
			$value_0 = isset($value[0]) ? $value[0] : null;
			$value_1 = isset($value[1]) ? $value[1] : null;
		}
		
		switch($error_num) {
			case 1: $this->problem = "Modules Path is undefined or doesn't exist: $value"; break;
			case 2: $this->problem = "CMSModuleHandlerImpl class is not a subclass of CMSModuleHandler in the file: $value"; break;
			case 3: $this->problem = "Couldn't create CMSModuleHandler obj for module: $value"; break;
			case 4: $this->problem = "Module File doesn't exist: $value"; $this->file_not_found = true; break;
			case 5: $this->problem = "Module '$value_0' doesn't exist or is disabled. Undefined file path: $value_1"; break;
			case 6: $this->problem = "$value file doesn't exist!"; $this->file_not_found = true; break;
			
			case 7: $this->problem = "CMSModuleSimulatorHandlerImpl class is not a subclass of CMSModuleSimulatorHandler in the file: $value"; break;
			case 8: $this->problem = "Couldn't create CMSModuleSimulatorHandler obj for module: $value"; break;
		}
	}
}
?>
