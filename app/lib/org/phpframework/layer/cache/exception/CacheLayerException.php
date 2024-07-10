<?php
class CacheLayerException extends Exception {
	public $problem;

	public function __construct($error_num, $value) {
		switch($error_num) {
			case 1: $this->problem = "Cache service '{$value}' needs to have the CACHE_HANDLER defined!"; break;
			case 2: $this->problem = "Cache service constructor doesn't exists: '{$value}'!"; break;
			case 3: $this->problem = "'$value' variable must be an array"; break;
			case 4: $this->problem = "'$value' variable cannot be empty"; break;
		}
	}
}
?>
