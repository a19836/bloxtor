<?php
namespace DocBlockParser\Annotation;

class DependsAnnotation extends Annotation {
	
	public function __construct() {
		$this->vectors = array("path", "desc");
	}
	
	public function parseArgs($DocBlockParser, $args) {
		$this->args = $args;
	}
	
	public function checkMethodAnnotations(&$method_params_data, $annotation_idx) {
		return true;
	}
}
?>
