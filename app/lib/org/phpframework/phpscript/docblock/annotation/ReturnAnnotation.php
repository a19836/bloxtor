<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

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
