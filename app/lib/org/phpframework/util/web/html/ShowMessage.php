<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class ShowMessage {
	
	public static function printStatus($msg, $status) {
		$html = '<div class="msg">'; 	
		
		if($status === true) {
			$html .= '<div class="ok">'.$msg.'</div>';
		}
		elseif(is_array($msg) && count($msg)) {
			$html .= '<div class="error">
				ERRORS: <br/><ul><li>- 
				'.implode("</li><li>- ", $msg).'
				</li></ul></div>';
		}
		else {
			$html .= '<div class="error">'.$msg.'</div>';
		}
	
		$html .= '</div>';
		return $html;
	}
}
?>
