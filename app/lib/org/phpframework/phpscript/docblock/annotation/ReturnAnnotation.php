<?php
namespace DocBlockParser\Annotation;

class ReturnAnnotation extends Annotation {
	
	public function __construct() {
		$this->is_output = true;
		$this->vectors = array("type", "desc");
	}
	
	public function parseArgs($DocBlockParser, $args) {
		$this->args = self::getConfiguredArgs($args);
	}
	
	public function checkMethodAnnotations(&$method_params_data, $annotation_idx) {
		return $this->checkValueAnnotations($method_params_data);
	}
}
?>
