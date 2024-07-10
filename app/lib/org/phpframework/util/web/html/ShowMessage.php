<?php
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
