<?php
include_once get_lib("org.phpframework.object.ObjTypeHandler");

class WorkFlowBusinessLogicHandler {
	
	public static function renameServiceObjectFile($file_path, $class_path) {
		$rename = false;
		
		if (file_exists($file_path)) {
			$classes = PHPCodePrintingHandler::getPHPClassesFromFile($file_path);
			unset($classes[0]);
			
			$aux = PHPCodePrintingHandler::decoupleClassNameWithNameSpace($class_path);
			$class_name = isset($aux["name"]) ? $aux["name"] : null;
			
			if (count($classes) == 1 && !empty($classes[$class_path]) && $class_name == pathinfo($file_path, PATHINFO_FILENAME))
				$rename = true;
		}
		
		return $rename;
	}
	
	public static function prepareServiceObjectForsaving(&$object, $options = null) {
		$default_include = $options && !empty($options["default_include"]) ? $options["default_include"] : "";
		$default_extend = $options && !empty($options["default_extend"]) ? $options["default_extend"] : "";
		
		//PREPARING INCLUDES
		$object["includes"] = empty($object["includes"]) ? array() : $object["includes"];
		$object["includes"] = is_array($object["includes"]) ? $object["includes"] : array($object["includes"]);
		
		$exists_business_logic_modules_service_common_file_path_include = false;
		$t = count($object["includes"]);
		
		for ($i = 0; $i < $t; $i++) {
			$include = $object["includes"][$i];
			$value = isset($include["path"]) ? $include["path"] : null;
			
			if ($default_include && str_replace("'", '"', $value) == $default_include) 
				$exists_business_logic_modules_service_common_file_path_include = true;
			else if (isset($include["var_type"]) && $include["var_type"] == "string") {
				$include["path"] = '$vars["business_logic_path"] . "/' . $value . '"';
				$include["var_type"] = "";
				
				$object["includes"][$i] = $include;
			}
		}
		
		if ($default_include && !$exists_business_logic_modules_service_common_file_path_include)
			$object["includes"][] = array(
				"path" => $default_include, 
				"var_type" => "", 
				"once" => true
			);
		
		//PREPARING EXTENDS
		if ($default_extend && empty($object["extends"]))
			$object["extends"] = (!empty($object["namespace"]) && substr($default_extend, 0, 1) != '\\' ? '\\' : '') . $default_extend;
	}
	
	public static function isBusinessLogicService($object) {
		return isset($object["type"]) && strtolower($object["type"]) == "public" && empty($object["abstract"]) && empty($object["static"]) && isset($object["arguments"]) && is_array($object["arguments"]) && count($object["arguments"]) == 1 && ( array_key_exists('data', $object["arguments"]) || array_key_exists('$data', $object["arguments"]) );
	}
	
	public static function prepareObjectIfIsBusinessLogicService(&$object) {
		if (!empty($object["is_business_logic_service"])) {
			$object["type"] = "public";
			$object["abstract"] = 0;
			$object["static"] = 0;
			$object["arguments"] = array(array(
				"name" => "data",
				"value" => null,
				"var_type" => "",
			));
			
			if (isset($object["annotations"]) && is_array($object["annotations"]))
				foreach ($object["annotations"] as $annotation_type => $annotations) {
					$t = $annotations ? count($annotations) : 0;
					for ($i = 0; $i < $t; $i++) {
						$annotation = $annotations[$i];
						$name = isset($annotation["name"]) ? trim($annotation["name"]) : "";
						
						//for the cases: article[id]
						$name = str_replace(array('"', "'"), "", $name);
						preg_match_all("/([^\[\]]+)/u", $name, $matches, PREG_PATTERN_ORDER); //'/u' means converts to unicode.
						$name = "data[" . (isset($matches[1]) ? implode('][', $matches[1]) : "") . "]";
						
						$object["annotations"][$annotation_type][$i]["name"] = $name;
					}
				}
		}
	}
	
	//This is used by the create_business_logic_objs_automatically.php and module/common/system_settings/admin/CommonModuleAdminTableExtraAttributesUtil.php
	public static function getAnnotationsFromParameters($parameters, $with_ids = false, $with_attrs = false, $with_not_nulls = false, $with_defaults = false, $with_max_length = false, $is_update = false, $is_conditions = false, $prefix = "\t") {
		//echo "<pre>";print_r($parameters);echo "</pre>";
		$annotations = "";
		
		if (is_array($parameters)) {
			$logged_user_id_annotation = null;
			//echo "<pre>";print_r($parameters);die();
			
			foreach ($parameters as $name => $parameter) {
				$parameter["name"] = !empty($parameter["name"]) ? $parameter["name"] : $name;
				
				$annotations .= self::getAnnotationsFromParameter($parameter, $with_ids, $with_attrs, $with_not_nulls, $with_defaults, $with_max_length, $is_update, $is_conditions, $prefix, $logged_user_id_annotation);
			}
			
			if ($logged_user_id_annotation)
				$annotations .= $logged_user_id_annotation;
		}
		
		return $annotations ? "$prefix/**\n$annotations$prefix */" : "";
	}
	
	public static function getAnnotationsFromParameter($parameter, $with_ids = false, $with_attrs = false, $with_not_nulls = false, $with_defaults = false, $with_max_length = false, $is_update = false, $is_conditions = false, $prefix = "\t", &$logged_user_id_annotation = null) {
		$annotations = "";
		
		if ( (!empty($parameter["primary_key"]) && $with_ids) || (empty($parameter["primary_key"]) && $with_attrs) ) {
			$name = isset($parameter["name"]) ? $parameter["name"] : null;
			$type = isset($parameter["type"]) ? $parameter["type"] : null;
			
			if ($name) {
				if ($is_update && empty($parameter["primary_key"]) && (ObjTypeHandler::isDBAttributeNameACreatedDate($name) || ObjTypeHandler::isDBAttributeNameACreatedUserId($name))) //if is an update action and is a create_date or create_by attribute, ignore attribute
					return "";
				
				$is_numeric = ObjTypeHandler::isPHPTypeNumeric($type);
				$is_logged_user_id_attribute = (ObjTypeHandler::isDBAttributeNameACreatedUserId($name) || ObjTypeHandler::isDBAttributeNameAModifiedUserId($name)) && $is_numeric;
				
				$conditions_prefix_name = $is_conditions ? "[conditions]" : "";
				$annotations .= "$prefix * @param (name=data{$conditions_prefix_name}[" . $name . "], type=" . ($is_conditions ? 'array|' : '') . $type;
				
				$default = isset($parameter["default"]) ? $parameter["default"] : null;
				
				if (empty($parameter["primary_key"]) || $with_ids !== 2) {
					$allow_null = !isset($parameter["null"]) || $parameter["null"];
					
					$annotations .= $with_not_nulls && !isset($parameter["mandatory"]) && !$allow_null ? ", not_null=1" : "";
					$annotations .= $with_not_nulls && !empty($parameter["mandatory"]) && (!empty($parameter["primary_key"]) || (!strlen($default) && !$allow_null)) ? ", not_null=1" : "";
				}
				
				if (trim(strtolower($default)) == "null")
					$default = "@null";
				else if (ObjTypeHandler::isDBAttributeValueACurrentTimestamp( trim($default) ))
					$default = "@date('Y-m-d H:i:s')";
				else if ((!$default || trim($default) == "0000-00-00 00:00:00" || ObjTypeHandler::isDBAttributeValueACurrentTimestamp( trim($default) )) && (ObjTypeHandler::isDBAttributeNameACreatedDate($name) || ObjTypeHandler::isDBAttributeNameAModifiedDate($name)))
					$default = "@date('Y-m-d H:i:s')";
				
				$exists_min = !empty($parameter["primary_key"]) && !$is_numeric;
				$is_default_func = substr($default, 0, 1) == "@";
				$annotations .= $with_defaults && (!$is_numeric || is_numeric($default)) ? ", default=" . ($is_default_func || is_numeric($default) ? '' : '"') . $default . ($is_default_func || is_numeric($default) ? '' : '"') : "";
				$annotations .= $exists_min ? ", min_length=1" : "";
				$annotations .= $with_max_length && !empty($parameter["length"]) && is_numeric($parameter["length"]) ? ", " . ($exists_min ? "max_" : "") . "length=" . $parameter["length"] : "";
				
				$add_sql_slashes = !isset($parameter["add_sql_slashes"]) || $parameter["add_sql_slashes"] ? true : false; //This is used by the module/common/system_settings/admin/CommonModuleAdminTableExtraAttributesUtil.php. All other cases and by default always includes the add_sql_slashes.
				$annotations .= $add_sql_slashes && ObjTypeHandler::convertCompositeTypeIntoSimpleType($type) != "no_string" && !ObjTypeHandler::isPHPTypeNumeric($type) ? ", add_sql_slashes=1" : "";
				
				$sanitize_html = !isset($parameter["sanitize_html"]) || $parameter["sanitize_html"] ? true : false; //This is used by the module/common/system_settings/admin/CommonModuleAdminTableExtraAttributesUtil.php. All other cases and by default always includes the sanitize_html.
				$annotations .= $sanitize_html && ObjTypeHandler::isDBTypeText($type) ? ", sanitize_html=1" : "";
				
				$annotations .= ") " . (isset($parameter["comment"]) ? $parameter["comment"] : "") . " \n";
				
				if ($is_logged_user_id_attribute && !$is_conditions)
					$logged_user_id_annotation = "$prefix * @param (name=data[logged_user_id], type=" . $type . ($with_max_length && !empty($parameter["length"]) && is_numeric($parameter["length"]) ? ", " . ($exists_min ? "max_" : "") . "length=" . $parameter["length"] : "") . ") \n";
			}
		}
		
		return $annotations;
	}
	
	public static function disableAddSqlSlashesInParameters(&$parameters) {
		if ($parameters)
			foreach ($parameters as $param_name => $param_props)
				$parameters[$param_name]["add_sql_slashes"] = false;
		
		return $parameters;
	}
	
	//This is used by the create_business_logic_objs_automatically.php and module/common/system_settings/admin/CommonModuleAdminTableExtraAttributesUtil.php
	public static function prepareAddcslashesCode($parameters) {
		if ($parameters) {
			$code = "";
			
			foreach ($parameters as $attr_name => $attr) {
				$name = !empty($attr["name"]) ? $attr["name"] : $attr_name;
				$type = isset($attr["type"]) ? $attr["type"] : null;
				
				if (ObjTypeHandler::convertCompositeTypeIntoSimpleType($type) != "no_string" && !ObjTypeHandler::isPHPTypeNumeric($type)) {
					$code .= '
			if (isset($data["' . $name . '"])';
					
					/*if ($attr["mandatory"]) {
						$code .= ' && $data["' . $name . '"]';
					}*/
					
					$code .= ') $data["' . $name . '"] = addcslashes($data["' . $name . '"], "\\\\\'");';
				}
			}
		
			return $code;
		}
		
		return "";
	}
	
	//This is used by the create_business_logic_objs_automatically.php and module/common/system_settings/admin/CommonModuleAdminTableExtraAttributesUtil.php
	public static function prepareAttributesDefaultValueCode($parameters, $is_ibatis, $is_update = false) {
		$code = '';
		//echo "<pre>";print_r($parameters);die();
		
		if (is_array($parameters))
			foreach ($parameters as $name => $parameter)
				if (empty($parameter["primary_key"])) {
					$type = isset($parameter["type"]) ? $parameter["type"] : null;
					$allow_null = !isset($parameter["null"]) || $parameter["null"];
					$is_numeric = ObjTypeHandler::isDBTypeNumeric($type);
					
					if ($allow_null && (!empty($parameter["mandatory"]) || ObjTypeHandler::isDBTypeDate($type) || $is_numeric)) {
						//if is an update action and is a create_date or create_by attribute, ignore attribute
						if ($is_update && (ObjTypeHandler::isDBAttributeNameACreatedDate($name) || ObjTypeHandler::isDBAttributeNameACreatedUserId($name))) 
							continue;
						
						$is_logged_user_id_attribute = (ObjTypeHandler::isDBAttributeNameACreatedUserId($name) || ObjTypeHandler::isDBAttributeNameAModifiedUserId($name)) && $is_numeric;
						$default = isset($parameter["default"]) ? $parameter["default"] : null;
						
						if (ObjTypeHandler::isDBAttributeValueACurrentTimestamp($default))
							$default = 'date("Y-m-d H:i:s")';
						else if (strlen($default))
							$default = is_numeric($default) ? $default : '"' .$default . '"';
						
						$code .= 'if (!isset($data["' . $name . '"]) || (is_string($data["' . $name . '"]) && !strlen(trim($data["' . $name . '"])))) $data["' . $name . '"] = ';
						
						if ((ObjTypeHandler::isDBAttributeNameACreatedDate($name) || ObjTypeHandler::isDBAttributeNameAModifiedDate($name)) && ObjTypeHandler::isDBTypeDate($type))
							$code .= $type == "date" ? 'date("Y-m-d")' : 'date("Y-m-d H:i:s")';
						else {
							if ($is_ibatis)
								$default = strlen($default) ? $default : '"null"';
							else //is hibernate or DAO
								$default = strlen($default) ? $default : 'null';
							
							if ($is_logged_user_id_attribute)
								$default = '$data["logged_user_id"] ? $data["logged_user_id"] : ' . $default;
							
							$code .= $default;
						}
						
						$code .= ';
			';
					}
				}
		
		return $code;
	}
	
	//This is used by the create_business_logic_objs_automatically.php and module/common/system_settings/admin/CommonModuleAdminTableExtraAttributesUtil.php
	//Only used if the action is DBDAO. ibatis doesn't need bc it converts the values to a sql string and hibernate already has this features included.
	public static function prepareNumericAttributesStringValueCode($parameters) {
		$code = '';
		//echo "<pre>";print_r($parameters);die();
		
		if (is_array($parameters))
			foreach ($parameters as $name => $parameter) {
				$type = isset($parameter["type"]) ? $parameter["type"] : null;
				$is_numeric = ObjTypeHandler::isDBTypeNumeric($type);
				
				if ($is_numeric) { //convert string to real numeric value. This is very important, bc in the insert and update primitive actions of the DBSQLConverter, the sql must be created with numeric values and without quotes, otherwise the DB server gives a sql error.
					$code .= 'if (isset($data["' . $name . '"]) && is_string($data["' . $name . '"]) && is_numeric($data["' . $name . '"])) $data["' . $name . '"] += 0;
			';
				}
			}
		
		return $code;
	}
}
?>
