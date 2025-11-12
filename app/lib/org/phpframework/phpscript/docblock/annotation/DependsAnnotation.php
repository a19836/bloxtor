<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
