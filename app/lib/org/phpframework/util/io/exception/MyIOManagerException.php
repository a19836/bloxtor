<?php
class MyIOManagerException extends Exception {
    public $problem;
	
    function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "FILEMANAGER_DATA cannot be undefined."; break;
			case 2: $this->problem = "FileManager cannot be undefined. Current filemanager type is:'$value'!"; break;
		}
    }
}
?>
