<?php
namespace DocBlockParser\Annotation;

class OtherAnnotation extends Annotation {
	
	public function parseArgs($DocBlockParser, $args) {
		$this->args = $args;
	}
	
	public function checkMethodAnnotations(&$method_params_data, $annotation_idx) {
		return false;
	}
}
?>
