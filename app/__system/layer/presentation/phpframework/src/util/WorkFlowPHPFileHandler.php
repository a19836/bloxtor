<?php
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");
//include_once get_lib("lib.vendor.phpparser.lib.bootstrap");
include_once $EVC->getUtilPath("WorkFlowQueryHandler");

class WorkFlowPHPFileHandler {
	
	private static function isCachedFileValid($CacheHandler, $cached_file_name, $file_path) {
		if ($CacheHandler) {
			$cache_root_path = $CacheHandler->getRootPath();
			$cache_file_path = CacheHandlerUtil::getCacheFilePath($cache_root_path . $cached_file_name);
			
			if (file_exists($cache_file_path)) {
				$cached_modified_date = filemtime($cache_file_path);
				
				return $CacheHandler->isValid($cached_file_name) && $cached_modified_date > filemtime($file_path);
			}
		}
		return false;
	}
	
	public static function getClassData($file_path, $class_id, $CacheHandler = null) {
		$cached_file_name = "php_files_code_reader/" . md5("$file_path/class:$class_id");
		
		if (self::isCachedFileValid($CacheHandler, $cached_file_name, $file_path)) 
			$obj_data = $CacheHandler->read($cached_file_name);
		else {
			$obj_data = PHPCodePrintingHandler::getClassFromFile($file_path, $class_id);
			
			if ($obj_data) {
				$obj_data["includes"] = PHPCodePrintingHandler::getIncludesFromFile($file_path);
				$obj_data["uses"] = PHPCodePrintingHandler::getUsesFromFile($file_path);
				$obj_data["properties"] = PHPCodePrintingHandler::getClassPropertiesFromFile($file_path, $class_id);
				
				if ($CacheHandler) 
					$CacheHandler->write($cached_file_name, $obj_data);
			}
		}
		
		return $obj_data;
	}
	
	public static function getClassMethodData($file_path, $class_id, $method_id, $CacheHandler = null) {
		$cached_file_name = "php_files_code_reader/" . md5("$file_path/method:$class_id-$method_id");
		
		if (self::isCachedFileValid($CacheHandler, $cached_file_name, $file_path))
			$obj_data = $CacheHandler->read($cached_file_name);
		else {
			$obj_data = PHPCodePrintingHandler::getFunctionFromFile($file_path, $method_id, $class_id);
			if ($obj_data) {
				$obj_data["code"] = PHPCodePrintingHandler::getFunctionCodeFromFile($file_path, $method_id, $class_id);
				
				if ($CacheHandler) 
					$CacheHandler->write($cached_file_name, $obj_data);
			}
		}
		
		return $obj_data;
	}
	
	public static function getFunctionData($file_path, $function_id, $CacheHandler = null) {
		$cached_file_name = "php_files_code_reader/" . md5("$file_path/func:$function_id");
		
		if (self::isCachedFileValid($CacheHandler, $cached_file_name, $file_path)) {
			$obj_data = $CacheHandler->read($cached_file_name);
		}
		else {
			$obj_data = PHPCodePrintingHandler::getFunctionFromFile($file_path, $function_id);
			if ($obj_data) {
				$obj_data["code"] = PHPCodePrintingHandler::getFunctionCodeFromFile($file_path, $function_id);
				
				if ($CacheHandler)
					$CacheHandler->write($cached_file_name, $obj_data);
			}
		}
		
		return $obj_data;
	}
	
	public static function getIncludesAndNamespacesAndUsesData($file_path, $CacheHandler = null) {
		$cached_file_name = "php_files_code_reader/" . md5("$file_path/includes");
		
		if (self::isCachedFileValid($CacheHandler, $cached_file_name, $file_path))
			$obj_data = $CacheHandler->read($cached_file_name);
		else {
			$obj_data = array(
				"includes" => PHPCodePrintingHandler::getIncludesFromFile($file_path),
				"namespaces" => PHPCodePrintingHandler::getNamespacesFromFile($file_path),
				"uses" => PHPCodePrintingHandler::getUsesFromFile($file_path),
			);
			
			if ($CacheHandler) 
				$CacheHandler->write($cached_file_name, $obj_data);
		}
		
		return $obj_data;
	}
	
	public static function saveClass($file_path, $object, $class_id = false, $rename_file_with_class = true) {
		$new_class_id = $object["name"];
		
		if ($file_path && $new_class_id) {
			$obj_id = $class_id ? $class_id : $new_class_id;
			$file_path = is_file($file_path) ? $file_path : "$file_path/$obj_id.php";
			
			//PREPARING PROPERTIES CODE
			if ($object["properties"]) {
				$props = empty($object["properties"]) || is_array($object["properties"]) ? $object["properties"] : array($object["properties"]);
				
				$code = "";
				$t = $props ? count($props) : 0;
				for ($i = 0; $i < $t; $i++)
					$code .= PHPCodePrintingHandler::getClassPropertyString($props[$i]) . "\n";
				
				if (!empty($code))
					$object["code"] = $code;
			}
			
			if (!isset($object["code"])) //to remove properties in case they exists before
				$object["code"] = "";
			
			//PREPARING INCLUDES
			$object["includes"] = empty($object["includes"]) ? array() : $object["includes"];
			$object["includes"] = is_array($object["includes"]) ? $object["includes"] : array($object["includes"]);
			
			$new_includes = array();
			$t = count($object["includes"]);
			for ($i = 0; $i < $t; $i++) {
				$include = $object["includes"][$i];
				$include_path_type = $include["var_type"];
				$include_path = $include_path_type == "string" ? "\"" . addcslashes($include["path"], '"') . "\"" : $include["path"];
				
				$new_includes[] = array($include_path, $include["once"]);
			}
			
			$object["includes"] = $new_includes;
			
			//SAVE CLASS
			if ($class_id) {
				$class_settings = PHPCodePrintingHandler::decoupleClassNameWithNameSpace($class_id);
				
				//Don't allow to add multiple classes with the same name, otherwise it will give a php error. Check if $new_class_id already exists and if it does returns false, but only if $new_class_id is a new class name
				$existent_class_data = $class_settings["name"] != $new_class_id ? self::getClassData($file_path, $new_class_id) : null;
				
				//Note that we must compare again the name, bc the getClassData returns the classes by comparing the lower name and in this case we need check the case sensitive, bc the user may want to change the name to another case letter, like this example: from "Aservice" to "AService"
				if ($existent_class_data && $existent_class_data["name"] == $new_class_id && $existent_class_data["namespace"] == $object["namespace"]) //check case (sensitive)
					return false;
				
				//Note: PHPCodePrintingHandler::editClassFromFile function already contains the replacement of the namespaces. So do not add the PHPCodePrintingHandler::removeNamespacesFromFile function here.
				PHPCodePrintingHandler::removeUsesFromFile($file_path);
				PHPCodePrintingHandler::removeIncludesFromFile($file_path);
				PHPCodePrintingHandler::editClassCommentsFromFile($file_path, $class_id, "");
				
				$status = PHPCodePrintingHandler::editClassFromFile($file_path, array("name" => $class_settings["name"], "namespace" => $class_settings["namespace"]), $object);
			}
			else {
				//Don't allow to add multiple classes with the same name, otherwise it will give a php error. Check if $new_class_id already exists and if it does returns false
				$existent_class_data = self::getClassData($file_path, $new_class_id);
				
				if ($existent_class_data)
					return false;
				
				//discard includes that already exists in file
				if ($object["includes"]) {
					$file_includes = PHPCodePrintingHandler::getIncludesFromFile($file_path);
					
					if ($file_includes) {
						$file_includes_path = array();
						
						foreach ($file_includes as $inc)
							$file_includes_path[] = $inc[0];
						
						foreach ($object["includes"] as $idx => $inc)
							if (in_array($inc[0], $file_includes_path))
								unset($object["includes"][$idx]);
					}
				}
				
				//save class
				$status = PHPCodePrintingHandler::addClassToFile($file_path, $object);
			}
			
			//PREPARING FILE PATH: set the same file name than $new_class_id
			if ($status && $rename_file_with_class) {
				$path_info = pathinfo($file_path);
				
				if ($path_info["filename"] != $new_class_id) 
					$status = rename($file_path, $path_info["dirname"] . "/$new_class_id." . $path_info["extension"]);
			}
		}
		
		return $status;
	}
	
	public static function removeClass($file_path, $class_id, $remove_file_if_no_class = true) {
		if ($file_path && $class_id && PHPCodePrintingHandler::removeClassFromFile($file_path, $class_id)) {
			$data = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
			
			if ($data) {
				unset($data[0]["namespaces"]);
				unset($data[0]["uses"]);
				unset($data[0]["includes"]);
				
				if (isset($data[0]) && empty($data[0]))
					unset($data[0]);
			}
			
			if (empty($data) && $remove_file_if_no_class) 
				return unlink($file_path);
			else
				return true;
		}
		return false;
	}
	
	public static function saveClassMethod($file_path, $object, $class_id, $method_id = false) {
		$new_method_id = $object["name"];
		
		if ($file_path && is_file($file_path) && $class_id && $new_method_id) {
			self::prepareObjectArguments($object);
			self::prepareObjectComments($object);
			
			if ($method_id) {
				//Don't allow to add multiple methods with the same name, otherwise it will give a php error. Check if $new_method_id already exists and if it does returns false, but only if $new_method_id is a new method name
				$existent_method_data = $method_id != $new_method_id ? self::getClassMethodData($file_path, $class_id, $new_method_id) : null;
				
				if ($existent_method_data)
					return false;
				
				PHPCodePrintingHandler::editFunctionCommentsFromFile($file_path, $method_id, "", $class_id);
				$status = PHPCodePrintingHandler::editFunctionFromFile($file_path, array("name" => $method_id), $object, $class_id);
			}
			else {
				//Don't allow to add multiple methods with the same name, otherwise it will give a php error. Check if $new_method_id already exists and if it does returns false
				$existent_method_data = self::getClassMethodData($file_path, $class_id, $new_method_id);
				
				if ($existent_method_data)
					return false;
				
				$status = PHPCodePrintingHandler::addFunctionToFile($file_path, $object, $class_id);
			}
		}
		
		return $status;
	}
	
	public static function removeClassMethod($file_path, $class_id, $method_id) {
		return $file_path && $class_id && $method_id ? PHPCodePrintingHandler::removeFunctionFromFile($file_path, $method_id, $class_id) : false;
	}
	
	public static function saveFunction($file_path, $object, $function_id = false) {
		$new_function_id = $object["name"];
		
		if ($file_path && $new_function_id) {
			self::prepareObjectArguments($object);
			self::prepareObjectComments($object);
			
			$path_info = pathinfo($file_path);
			
			if (is_file($file_path)) {
				if ($object["file_name"] && strtolower($path_info["filename"]) != strtolower($object["file_name"])) {
					$extension = $path_info["extension"] ? $path_info["extension"] : "php";
					$new_file_path = $path_info["dirname"] . "/" . ($object["file_name"] ? $object["file_name"] : "functions") . "." . $extension;
					
					//Don't allow to add multiple functions with the same name, otherwise it will give a php error. Check if $new_function_id already exists and if it does returns false, but only if $new_function_id is a new function name
					$existent_function_data = self::getFunctionData($new_file_path, $new_function_id);
					
					if ($existent_function_data)
						return false;
					
					PHPCodePrintingHandler::removeFunctionFromFile($file_path, $function_id);
					$status = PHPCodePrintingHandler::addFunctionToFile($new_file_path, $object);
				}
				else if ($function_id) {
					//Don't allow to add multiple functions with the same name, otherwise it will give a php error. Check if $new_function_id already exists and if it does returns false, but only if $new_function_id is a new function name
					$existent_function_data = $function_id != $new_function_id ? self::getFunctionData($file_path, $new_function_id) : null;
					
					if ($existent_function_data)
						return false;
					
					PHPCodePrintingHandler::editFunctionCommentsFromFile($file_path, $function_id, "");
					
					$status = PHPCodePrintingHandler::editFunctionFromFile($file_path, array("name" => $function_id), $object);
				}
				else {
					//Don't allow to add multiple functions with the same name, otherwise it will give a php error. Check if $new_function_id already exists and if it does returns false
					$existent_function_data = self::getFunctionData($file_path, $new_function_id);
					
					if ($existent_function_data)
						return false;
					
					$status = PHPCodePrintingHandler::addFunctionToFile($file_path, $object);
				}
			}
			else {
				$new_file_path = $file_path . "/" . ($object["file_name"] ? $object["file_name"] : "functions") . ".php";
				
				//Don't allow to add multiple functions with the same name, otherwise it will give a php error. Check if $new_function_id already exists and if it does returns false, but only if $new_function_id is a new function name
				$existent_function_data = self::getFunctionData($new_file_path, $new_function_id);
				
				if ($existent_function_data)
					return false;
				
				$status = PHPCodePrintingHandler::addFunctionToFile($new_file_path, $object);
			}
		}
		
		return $status;
	}
	
	public static function removeFunction($file_path, $function_id) {
		return $file_path && $function_id ? PHPCodePrintingHandler::removeFunctionFromFile($file_path, $function_id) : false;
	}
	
	public static function saveIncludesAndNamespacesAndUses($file_path, $object) {
		return self::saveUses($file_path, $object["uses"]) &&
			  self::saveIncludes($file_path, $object["includes"]) && 
			  self::saveNamespaces($file_path, $object["namespaces"]);
	}
	
	private static function saveNamespaces($file_path, $namespaces) {
		$status = PHPCodePrintingHandler::removeNamespacesFromFile($file_path);
		
		if ($status && !empty($namespaces)) {
			$namespaces = is_array($namespaces) ? $namespaces : array($namespaces);
			$status = PHPCodePrintingHandler::addNamespacesToFile($file_path, $namespaces);
		}
		
		return $status;
	}
	
	private static function saveUses($file_path, $uses) {
		$status = PHPCodePrintingHandler::removeUsesFromFile($file_path);
		
		if ($status && !empty($uses)) {
			$uses = is_array($uses) ? $uses : array($uses);
			$status = PHPCodePrintingHandler::addUsesToFile($file_path, $uses);
		}
		
		return $status;
	}
	
	private static function saveIncludes($file_path, $includes) {
		$status = PHPCodePrintingHandler::removeIncludesFromFile($file_path);
		
		if ($status && !empty($includes)) {
			$includes = is_array($includes) ? $includes : array($includes);
			
			$new_includes = array();
			$t = count($includes);
			for ($i = 0; $i < $t; $i++) {
				$include = $includes[$i];
				$include_path_type = $include["var_type"];
				$include_path = $include_path_type == "string" ? "\"" . addcslashes($include["path"], '"') . "\"" : $include["path"];
				
				$new_includes[] = array($include_path, $include["once"]);
			}
			
			$status = PHPCodePrintingHandler::addIncludesToFile($file_path, $new_includes);
		}
		
		return $status;
	}
	
	private static function prepareObjectArguments(&$object) {
		if (isset($object["arguments"])) {
			$new_arguments = array();
			
			$t = $object["arguments"] ? count($object["arguments"]) : 0;
			for ($i = 0; $i < $t; $i++) {
				$arg = $object["arguments"][$i];
				
				$name = $arg["name"];
				$value = $arg["value"];
				$var_type = $arg["var_type"];
				
				$new_arguments[$name] = $var_type != "string" && empty($value) && !is_numeric($value) ? null : ($var_type == "string" ? "\"" . addcslashes($value, '"') . "\"" : $value);
			}
			
			$object["arguments"] = $new_arguments;
		}
	}
	
	private static function prepareObjectComments(&$object) {
		$comments = isset($object["comments"]) && trim($object["comments"]) ? " * " . str_replace("\n", "\n * ", trim($object["comments"])) . "\n" : "";
		
		if (isset($object["annotations"]) && is_array($object["annotations"])) {
			$comments .= $comments ? " * \n" : "";
			
			foreach ($object["annotations"] as $annotation_type => $annotations) {
				$at = strtolower($annotation_type);
				
				$t = $annotations ? count($annotations) : 0;
				for ($i = 0; $i < $t; $i++) {
					$annotation = $annotations[$i];
					$name = trim($annotation["name"]);
					
					$args = "";
					$args .= $name ? ($args ? ", " : "") . "name=" . $name : "";
					$args .= trim($annotation["type"]) ? ($args ? ", " : "") . "type=" . $annotation["type"] : "";
					$args .= trim($annotation["not_null"]) ? ($args ? ", " : "") . "not_null=1" : "";
					$args .= strlen(trim($annotation["default"])) ? ($args ? ", " : "") . "default=" . $annotation["default"] : "";
					$args .= trim($annotation["others"]) ? ($args ? ", " : "") . $annotation["others"] : "";
					
					if ($args || trim($annotation["desc"])) {
						$comments .= " * @$at ($args) " . addcslashes($annotation["desc"], '"') . "\n";
					}
				}
			}
		}
		
		$object["comments"] = $comments ? "/**\n$comments */" : "";
	}
	
	public static function getChoosePHPClassFromFileManagerHtml($get_includes_sub_files_url) {
		$html = '<div id="choose_php_class_from_file_manager" class="myfancypopup choose_php_class with_title">
			<div class="title">Choose a Class</div>
			<ul class="mytree">
				<li>
					<label>Root</label>
					<ul url="' . str_replace("#path#", "", $get_includes_sub_files_url) . '"></ul>
				</li>
			</ul>
			<div class="button">
				<input type="button" value="Update" onClick="MyFancyPopup.settings.updateFunction(this)" />
			</div>
		</div>';
		
		return $html;
	}
	
	public static function getUseHTML($use = false, $alias = false) {
		return '
			<div class="use">
				<label>Use </label>
				<input class="use_name" type="text" value="' . $use . '" placeHolder="namespace" />
				<label class="use_as"> as </label>
				<input class="use_alias" type="text" value="' . $alias . '" placeHolder="alias" />
				<span class="icon delete" onClick="$(this).parent().remove();" title="Delete">Remove</span>
			</div>';
	}
	
	public static function getInludeHTML($include = false) {
		$include_value = $include ? $include[0] : "";
		$include_once = $include && $include[1];
		$include_type = substr($include_value, 0, 1) == '"' || substr($include_value, 0, 1) == '"' ? "string" : "";
		
		$include_value = trim($include_value);
		$first_char = substr($include_value, 0, 1);
		if ($first_char == '"' || $first_char == "'") {
			$include_value = substr($include_value, 1, -1);
			$include_value = $first_char == '"' ? str_replace('\\"', '"', $include_value) : str_replace("\\'", "'", $include_value);
		}
		
		return '
			<div class="include">
				<label>Path:</label>
				<input class="include_path" type="text" value="' . str_replace('"', "&quot;", $include_value) . '" />
				<select class="include_type">
					<option value="string">string</option>
					<option value=""' . ($include_type == "" ? ' selected' : '') . '>default</option>
				</select>
				<input class="include_once" type="checkbox" value="1" title="Check this if include/require once"' . ($include_once ? ' checked' : '') . '/>
				<span class="icon search" onClick="getIncludePathFromFileManager(this, \'input\')" title="Get file from File Manager">Search</span>
				<span class="icon delete" onClick="$(this).parent().remove();" title="Delete">Remove</span>
			</div>';
	}
	
	public static function getPropertyHTML($property = false) {
		$prop_name = $property["name"];
		$prop_value = $property["value"];
		$prop_var_type = $property["var_type"];
		$comments = "";
		
		if ($property["comments"] || $property["doc_comments"]) {
			$doc_comments = $property["doc_comments"] ? implode("\n", $property["doc_comments"]) : "";
			$doc_comments = trim($doc_comments);
			$doc_comments = str_replace("\r", "", $doc_comments);
			$doc_comments = preg_replace("/^\/[*]+\s*/", "", $doc_comments);
			$doc_comments = preg_replace("/\s*[*]+\/\s*$/", "", $doc_comments);
			$doc_comments = preg_replace("/\s*\n\s*[*]*\s*/", "\n", $doc_comments);
			$doc_comments = preg_replace("/^\s*[*]*\s*/", "", $doc_comments);
			$doc_comments = trim($doc_comments);
			
			$comments = is_array($property["comments"]) ? trim(implode("\n", $property["comments"])) : "";
			$comments .= $doc_comments ? "\n" . trim($doc_comments) : "";
			$comments = str_replace(array("/*", "*/", "//"), "", $comments);
			$comments = trim($comments);
		}
		
		$prop_var_type = $prop_var_type ? strtolower($prop_var_type) : (isset($prop_value) && (substr($prop_value, 0, 1) == '"' || substr($prop_value, 0, 1) == '"') ? "string" : "");
		$prop_var_type = empty($prop_value) ? "string" : $prop_var_type;
		
		$prop_name = trim($prop_name);
		$prop_name = substr($prop_name, 0, 1) == "\$" ? substr($prop_name, 1) : $prop_name;
		
		$prop_value = trim($prop_value);
		$first_char = substr($prop_value, 0, 1);
		if ($first_char == '"' || $first_char == "'") {
			$prop_value = substr($prop_value, 1, -1);
			$prop_value = $first_char == '"' ? str_replace('\\"', '"', $prop_value) : str_replace("\\'", "'", $prop_value);
		}
		
		$types = array("public", "private", "protected", "const");
		$var_types = array("string" => "string", "" => "default");
		
		$html = '
			<tr class="property">
				<td class="name">
					<input type="text" value="' . str_replace('"', "&quot;", $prop_name) . '" />
				</td>
				<td class="value">
					<input type="text" value="' . str_replace('"', "&quot;", $prop_value) . '" />
				</td>
				<td class="type">
					<select>';
		
		$t = count($types);
		for ($i = 0; $i < $t; $i++) 
			$html .= '<option' . (strtolower($types[$i]) == $property["type"] || ($types[$i] == "const" && $property["const"]) ? " selected" : "") . '>' . $types[$i] . '</option>';
				
		$html .= '
					</select>
				</td>
				<td class="static">
					<input type="checkbox" value="1" ' . ($property["static"] ? "checked" : "" ) . ' />
				</td>
				<td class="var_type">
					<select>';
		
		foreach ($var_types as $v => $k) 
			$html .= '<option' . ($v == $prop_var_type ? " selected" : "") . '>' . $k . '</option>';
				
		$html .= '
					</select>
				</td>
				<td class="comments">
					<input type="text" value="' . str_replace('"', "&quot;", $comments) . '" />
				</td>
				<td class="icon_cell table_header"><span class="icon delete" onClick="$(this).parent().parent().remove();" title="Delete">Remove</span></td>
			</tr>';
			
		return $html;
	}
	
	public static function getArgumentHTML($arg_name = false, $arg_value = false, $arg_type = false) {
		$var_types = array("default", "string");
		
		$arg_type = $arg_type ? strtolower($arg_type) : (isset($arg_value) && (substr($arg_value, 0, 1) == '"' || substr($arg_value, 0, 1) == "'") ? "string" : "");
		
		$arg_name = trim($arg_name);
		$arg_name = substr($arg_name, 0, 1) == "\$" ? substr($arg_name, 1) : $arg_name;
		
		$arg_value = trim($arg_value);
		$first_char = substr($arg_value, 0, 1);
		if ($first_char == '"' || $first_char == "'") {
			$arg_value = substr($arg_value, 1, -1);
			$arg_value = $first_char == '"' ? str_replace('\\"', '"', $arg_value) : str_replace("\\'", "'", $arg_value);
		}
		
		$html = '<tr class="argument">
				<td class="name">
					<input type="text" value="' . str_replace('"', "&quot;", $arg_name) . '" onBlur="onBlurArgumentName(this)" />
				</td>
				<td class="value">
					<input type="text" value="' . str_replace('"', "&quot;", $arg_value) . '" />
				</td>
				<td class="var_type">
					<select>';
		
		$t = count($var_types);
		for ($i = 0; $i < $t; $i++) {
			$html .= '<option' . (strtolower($var_types[$i]) == $arg_type ? " selected" : "") . '>' . $var_types[$i] . '</option>';
		}			
		$html .= '
					</select>
				</td>
				<td class="icon_cell table_header"><span class="icon delete" onClick="removeArgument(this)" title="Delete">Remove</span></td>
			</tr>';
		
		return $html;
	}
	
	public static function getAnnotationHTML($attrs = false, $annotation_type = false) {
		$name = $type = $not_null = $default = $description = $others = "";
		
		if (is_array($attrs)) {
			$name = $attrs["name"];
			$type = $attrs["type"];
			$not_null = !empty($attrs["not_null"]);
			$default = $attrs["default"];
			$description = str_replace('\\"', '"', $attrs["desc"]);
		
			foreach ($attrs as $k => $v) {
				if ($k != "name" && $k != "type" && $k != "not_null" && $k != "default" && $k != "desc" && $k != "sub_name") {
					$others .= ($others ? ", " : "") . "$k=$v";
				}
			}
		}
		
		$php_map_types = WorkFlowDataAccessHandler::getMapPHPTypes();
		$db_map_types = WorkFlowDataAccessHandler::getMapDBTypes();
		
		$type = ObjTypeHandler::convertSimpleTypeIntoCompositeType($type);
		
		$html = '
		<tr class="annotation">
			<td class="annotation_type">
				<select>
					<option value="param"' . ($annotation_type == "param" ? ' selected' : '') . '>Param</option>
					<option value="return"' . ($annotation_type == "return" ? ' selected' : '') . '>Return</option>
				</select>
			</td>
			<td class="name">
				<input type="text" value="' . str_replace('"', "&quot;", $name) . '" onBlur="onBlurAnnotationName(this)" />
			</td>
			<td class="type">
				<select' . (strpos($type, "|") !== false ? ' style="display:none"' : '') . '>
					<option></option>
					<optgroup label="PHP Types" class="main_optgroup">
					' . WorkFlowQueryHandler::getMapSelectOptions($php_map_types, $type, "org.phpframework.object.php.Primitive", false, true) . '
					</optgroup>
					<optgroup label="DB Types" class="main_optgroup">
					' . WorkFlowQueryHandler::getMapSelectOptions($db_map_types, $type, "org.phpframework.object.db.DBPrimitive", false, false) . '
					</optgroup>
				</select>
				<input type="text" value="' . str_replace('"', "&quot;", $type) . '"' . (strpos($type, "|") !== false ? '' : ' style="display:none"') . ' />
				<span class="icon switch textfield" onClick="swapTypeTextField(this)" title="Swap text field type">Swap text field type</span>
				<span class="icon search" onClick="geAnnotationTypeFromFileManager(this)" title="Get type from File Manager">Search</span>
			</td>
			<td class="not_null">
				<input type="checkbox" value="1"' . ($not_null ? ' checked' : '') . ' />
			</td>
			<td class="default">
				<input type="text" value="' . str_replace('"', "&quot;", $default) . '" />
			</td>
			<td class="description">
				<input type="text" value="' . str_replace('"', "&quot;", $description) . '" />
			</td>
			<td class="others">
				<input type="text" value="' . str_replace('"', "&quot;", $others) . '" />
			</td>
			<td class="icon_cell table_header"><span class="icon delete" onClick="removeAnnotation(this)" title="Delete">Remove</span></td>
		</tr>';
		
		return $html;
	}
}
?>
