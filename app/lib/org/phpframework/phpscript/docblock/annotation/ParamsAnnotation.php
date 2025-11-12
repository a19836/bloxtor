<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

namespace DocBlockParser\Annotation;

class ParamsAnnotation extends Annotation {
	
	public function __construct() {
		$this->is_input = true;
	}
	
	public function parseArgs($DocBlockParser, $args) {
		$comment = "/**\n" . implode("\n", $args) . "\n*/";
		$DBP = new \DocBlockParser();
		$DBP->ofComment($comment);
		$objs = $DBP->getObjects();
		
		$DocBlockParser->setIncludedTag("param");
		$this->args = !empty($objs["param"]) ? $objs["param"] : null;
	}
	
	public function checkMethodAnnotations(&$method_params_data, $annotation_idx) {
		$status = true;
		
		if ($this->args) {			
			$t = count($this->args);
			for ($i = 0; $i < $t; $i++) {
				$obj = $this->args[$i];
				
				if (!$obj->checkMethodAnnotations($method_params_data, $i))
					$status = false;
			}
		}
		
		return $status;
	}
}
?>
