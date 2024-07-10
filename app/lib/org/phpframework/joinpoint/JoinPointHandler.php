<?php
include_once get_lib("org.phpframework.joinpoint.exception.JoinPointHandlerException");

class JoinPointHandler {
	private $some_global_variables;
	
	public function __construct($some_global_variables) {
		$this->some_global_variables = $some_global_variables;
	}
	
	public function executeJoinPoint($join_point_properties, &$input) {
		//echo "<pre>";print_r($join_point_properties);
		//print_r($input);die();
		
		if ($this->some_global_variables)
			foreach ($this->some_global_variables as $var_name => $var_value)
				if ($var_name)
					eval('$' . $var_name . ' = $var_value;');
		
		$method_file = isset($join_point_properties["method_file"]) ? $join_point_properties["method_file"] : null;
		$method_type = isset($join_point_properties["method_type"]) ? $join_point_properties["method_type"] : null;
		$method_obj = isset($join_point_properties["method_obj"]) ? $join_point_properties["method_obj"] : null;
		$method_name = isset($join_point_properties["method_name"]) ? $join_point_properties["method_name"] : null;
		$method_static = isset($join_point_properties["method_static"]) ? $join_point_properties["method_static"] : null;
		$input_mapping = isset($join_point_properties["input_mapping"]) ? $join_point_properties["input_mapping"] : null;
		$method_args = isset($join_point_properties["method_args"]) ? $join_point_properties["method_args"] : null;
		$output_mapping = isset($join_point_properties["output_mapping"]) ? $join_point_properties["output_mapping"] : null;
		
		if (!$method_name) 
			return false;
		else if ($method_type == "method" && !$method_obj) 
			return false;
		
		if ($method_file && !file_exists($method_file)) {
			launch_exception(new JoinPointHandlerException(2, null, $method_file));
			return false;
		}
		
		//Preparing input mapping
		if (is_array($input_mapping)) {
			foreach ($input_mapping as $item) {
				$join_point_input = isset($item["join_point_input"]) ? $item["join_point_input"] : null;
				$method_input = isset($item["method_input"]) ? $item["method_input"] : null;
				
				$input[$method_input] = &$input[$join_point_input];
				if (!empty($item["erase_from_input"]))
					unset($input[$join_point_input]);
			}
		}
		//echo "<pre>";print_r($input);die("asd");
		
		//Preparing method call code
		$code = '$status = ';
		if ($method_type == "method") {
			if ($method_static)
				$code .= $method_obj . '::';
			else
				$code .= (substr($method_obj, 0, 1) != '$' ? '$' : '') . $method_obj . '->';
		}
		$code .= $method_name . '(';
		
		//Preparing method args
		if (is_array($method_args)) {
			$args = '';
			foreach ($method_args as $item) {
				$value = $item["value"];
				$type = $item["type"];
				
				if (!isset($value))
					$value = $type == "string" || $type == "date" ? "''" : (!$type ? "null" : "");
				else
					$value = $type == "variable" && $value ? ((substr(trim($value), 0, 1) != '$' ? '$' : '') . trim($value)) : ($type == "string" || $type == "date" ? "\"" . addcslashes($value, '"') . "\"" : (!$type && strlen(trim($value)) == 0 ? "null" : trim($value)) );//Please do not add the addcslashes($value, '\\"') otherwise it will create an extra \\. The correct is without the \\, because we are editing php code directly.
				
				$value = strlen($value) ? $value : "null";
			
				$args .= ($args ? ", " : "") . $value;
			}
			$code .= $args;
		}
		
		$code .= ');';
		//echo "code:$code<br>";die();
		
		try {
			if ($method_file) 
				include_once $method_file;
			
			eval($code);
		}
		catch(Exception $e) {
			launch_exception(new JoinPointHandlerException(1, $e, $code));
			return false;
		}
		//echo "<pre>";print_r($input);die("asd");
		
		//Preparing output mapping
		if (is_array($output_mapping)) {
			foreach ($output_mapping as $item) {
				$method_output = isset($item["method_output"]) ? $item["method_output"] : null;
				$join_point_output = isset($item["join_point_output"]) ? $item["join_point_output"] : null;
				
				$input[$join_point_output] = &$input[$method_output];
				if (!empty($item["erase_from_output"]))
					unset($input[$method_output]);
			}
		}
		//echo "<pre>";print_r($input);die("asd");
		
		return $status;
	}
}


/*
Example in: 
	- http://jplpinto.localhost/__system/phpframework/presentation/edit_block?bean_name=Presentation&bean_file_name=presentation_pl.xml&path=parquedasconchas/src/block/admin/edit_article.php&t=1456151737022&edit_block_type=simple
	- http://jplpinto.localhost/parquedasconchas/admin/edit_article?article_id=9
*/

//Module Article - Join Point: On successfull article saving action
/*function bar($input = null, $nome = "") {
	//echo "Name: $nome<pre>";print_r($input);
	
	if ($input) {
		$EVC = $input["EVC"];
		include_once $EVC->getModulePath("attachment/AttachmentUI", $EVC->getCommonProjectName());
		
		return AttachmentUtil::saveObjectAttachments($EVC, $input["article_type_id"], $input["article_id"], $input["group_id"], $input["error_message"]);
	}
			
	return false;
}

//Module Article - Join Point: New Article bottom fields
function foo($input = null, $nome = "") {
	//echo "Name: $nome<pre>";print_r($input);
	
	if ($input) {
		$EVC = $input["EVC"];
		
		if ($input["settings"]["show_article_attachments"]) {
			include_once $EVC->getModulePath("attachment/AttachmentUI", $EVC->getCommonProjectName());
			
			$attachments_settings = array(
				"class" => $input["settings"]["fields"]["article_attachments"]["field"]["class"],
				"title" => $input["settings"]["fields"]["article_attachments"]["field"]["label"]["value"],
			);
			
			unset($input["settings"]["fields"]["article_attachments"]["field"]);
			
			$input["settings"]["fields"]["article_attachments"]["container"]["next_html"] = AttachmentUI::getEditObjectAttachmentsHtml($EVC, $attachments_settings, $input["article_type_id"], $input["article_id"], $input["group_id"]);
		}
		
		//echo "<textarea>" . $input["settings"]["fields"]["article_attachments"]["container"]["previous_html"] . "</textarea>";die();
	}
}*/
?>
