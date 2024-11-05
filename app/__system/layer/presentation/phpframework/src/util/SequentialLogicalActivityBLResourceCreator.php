<?php
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");
include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");
include_once $EVC->getUtilPath("CMSPresentationFormSettingsUIHandler");

class SequentialLogicalActivityBLResourceCreator {
	private $file_path;
	private $class_name;
	private $tables;
	private $db_table;
	private $db_attrs;
	
	public function __construct($file_path, $class_name, $tables, $db_table) {
		$this->file_path = $file_path;
		$this->class_name = $class_name;
		$this->db_table = $db_table;
		$this->tables = $tables;
		
		$this->db_attrs = WorkFlowDBHandler::getTableFromTables($tables, $db_table);
	}
	
	public function createBLResourceServiceFile($service_file_path, $service_class_name, &$error_message = null) {
		$file_exists = file_exists($this->file_path) && PHPCodePrintingHandler::getClassFromFile($this->file_path, $this->class_name);
		
		if (!$file_exists) {
			$obj_data = PHPCodePrintingHandler::getClassFromFile($service_file_path, $service_class_name);
			
			if ($obj_data) {
				$obj_data["includes"] = PHPCodePrintingHandler::getIncludesFromFile($service_file_path);
				
				$file_exists = PHPCodePrintingHandler::addClassToFile($this->file_path, array(
					"name" => $this->class_name,
					"extends" => isset($obj_data["extends"]) ? $obj_data["extends"] : null,
					"includes" => isset($obj_data["includes"]) ? $obj_data["includes"] : null
				));
			}
		}
		
		return $file_exists;
	}
	
	public function createInsertMethod($module_id, $insert_service_id, &$error_message = null) {
		if ($this->db_attrs) {
			$attributes_name = array_keys($this->db_attrs);
			
			$attributes_previous_code = self::getInsertActionPreviousCode($this->tables, $this->db_table, $attributes_name, '$attributes');
			$attributes_previous_code = str_replace("\n", "\n\t", $attributes_previous_code);
			
			$code = '$options = isset($data["options"]) ? $data["options"] : null;
$this->mergeOptionsWithBusinessLogicLayer($options);

$attributes = isset($data["attributes"]) ? $data["attributes"] : null;

if ($attributes) {
	' . $attributes_previous_code . '
	
	$result = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $insert_service_id . '", $attributes, $options);
	
	return $result;
}';
			
			//save resource method
			$method_comments = "Insert parsed resource data into table: " . $this->db_table . ".";
			
			return self::addFunctionToFile($this->file_path, array(
				"name" => "insert",
				"arguments" => array("data" => null),
				"code" => $code,
				"comments" => $method_comments
			), $this->class_name);
		}
	}
	
	public function createUpdateMethod($module_id, $get_service_id, $update_service_id, $update_pks_service_id, &$error_message = null) {
		if ($this->db_attrs) {
			//prepare pks_name
			$attributes_name = array_keys($this->db_attrs);
			$pks_name = array();
			
			foreach ($this->db_attrs as $attr_name => $attr)
				if (!empty($attr["primary_key"]))
					$pks_name[] = $attr_name;
			
			$no_pks = empty($pks_name);
			
			$attributes_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $attributes_name, '$attributes');
			$attributes_previous_code = str_replace("\n", "\n\t", $attributes_previous_code);
			
			$pks_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $pks_name, '$pks');
			$pks_previous_code = str_replace("\n", "\n\t", $pks_previous_code);
			
			//prepare code
			$code = '$options = isset($data["options"]) ? $data["options"] : null;
$this->mergeOptionsWithBusinessLogicLayer($options);

$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
$pks = isset($data["pks"]) ? $data["pks"] : null;

';

			if ($no_pks) { //if table has no pks
				$code .= 'if ($attributes && $pks) {
	$status = true;

	' . $attributes_previous_code . '
	' . $pks_previous_code . '

	$filtered_attributes = $pks;

	if ($status) {
		//get the record from DB bc the $attributes may only have a few attributes, so we need to populate the other ones in order to call the broker->update method.
		';
				
				if ($get_service_id)
					$code .= '$data = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $get_service_id . '", $filtered_attributes, $options);';
				else
					$code .= '//because there is no get service, we join the attributes and pks variables.
				$data = array_merge($attributes, $pks);
				//$data = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "REPLACE WITH THE CORRECT SERVICE THAT GETS THE ITEM DATA", $filtered_attributes, $options);';
				
				$code .= '
		
		if (!$data || !is_array($data))
			return false;
		
		foreach ($attributes as $attr_name => $attr_value)
			$data[$attr_name] = $attr_value;
		
		foreach ($pks as $pk_name => $pk_value) {
			$data["old_" . $pk_name] = $pk_value;
			$data["new_" . $pk_name] = $attributes[$pk_name];
			unset($data[$pk_name]);
		}
		
		$status = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $update_service_id . '", $data, $options);
	}

	return $status;
}';
			}
			else {
				//prepare code for empty files
				$code_for_empty_files = '';
				
				foreach ($this->db_attrs as $attr_name => $attr)
					if (empty($attr["primary_key"]) && isset($attr["type"]) && ObjTypeHandler::isDBTypeBlob($attr["type"]))
						$code_for_empty_files .= '
			if (isset($filtered_attributes["' . $attr_name . '"]) && isset($data["' . $attr_name . '"]) && empty($_FILES["' . $attr_name . '"]["tmp_name"])) $filtered_attributes["' . $attr_name . '"] = $data["' . $attr_name . '"];';
				
				//prepare code
				$code .= 'if ($attributes && $pks) {
	$status = true;

	' . $attributes_previous_code . '
	' . $pks_previous_code . '

	if ($status) {
		//get new pks from $attributes and get $attributes without pks
		$update_pks = false;
		$filtered_pks = array();
		$filtered_attributes = array();
		
		foreach ($attributes as $attribute_name => $attribute_value) {
			if (array_key_exists($attribute_name, $pks)) {
				$filtered_pks["new_" . $attribute_name] = $attribute_value;
				
				if ($attribute_value != $pks[$attribute_name])
					$update_pks = true;
			}
			else
				$filtered_attributes[$attribute_name] = $attribute_value;
		}
		
		$status = $update_pks || $filtered_attributes;
		
		if ($status) {
			foreach ($pks as $pk_name => $pk_value) {
				if ($update_pks)
					$filtered_pks["old_" . $pk_name] = $pk_value;
				
				if ($filtered_attributes)
					$filtered_attributes[$pk_name] = $pk_value;
			}
			
			if ($filtered_attributes) {
				//get the record from DB bc the $attributes may only have a few attributes, so we need to populate the other ones in order to call the broker->update method.
				';
				
				if ($get_service_id)
					$code .= '$data = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $get_service_id . '", $filtered_attributes, $options);';
				else
					$code .= '//because there is no get service, we join the attributes and pks variables.
				$data = array_merge($attributes, $pks);
				//$data = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "REPLACE WITH THE CORRECT SERVICE THAT GETS THE ITEM DATA", $filtered_attributes, $options);';
				
				$code .= '
				
				if (!$data || !is_array($data))
					return false;
				' . $code_for_empty_files . '
				
				foreach ($filtered_attributes as $attr_name => $attr_value)
					$data[$attr_name] = $attr_value;
				
				$status = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $update_service_id . '", $data, $options);
			}
			
			if ($status && $update_pks) {
				';
				
				if ($update_pks_service_id)
					$code .= '$status = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $update_pks_service_id . '", $filtered_pks, $options);';
				else
					$code .= '//$status = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "REPLACE WITH THE CORRECT SERVICE THAT UPDATES PKS", $filtered_pks, $options);';
				
				$code .= '
			}
		}
	}

	return $status;
}';
			}
			
			//save resource method
			$method_comments = "Update data into table: " . $this->db_table . ".";
			
			return self::addFunctionToFile($this->file_path, array(
				"name" => "update",
				"arguments" => array("data" => null),
				"code" => $code,
				"comments" => $method_comments
			), $this->class_name);
		}
	}
	
	public function createUpdateAttributeMethod($module_id, $get_service_id, $update_service_id, &$error_message = null) {
		if ($this->db_attrs) {
			//prepare pks_name
			$attributes_name = array_keys($this->db_attrs);
			$pks_name = array();
			
			foreach ($this->db_attrs as $attr_name => $attr)
				if (!empty($attr["primary_key"]))
					$pks_name[] = $attr_name;
			
			$no_pks = empty($pks_name);
			
			//prepare code
			$attributes_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $attributes_name, '$attributes', false);
			$attributes_previous_code = str_replace("\n", "\n\t", $attributes_previous_code);
			
			$pks_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $pks_name, '$pks', false);
			$pks_previous_code = str_replace("\n", "\n\t", $pks_previous_code);
			
			//prepare code for empty files
			$code_for_empty_files = '';
			
			foreach ($this->db_attrs as $attr_name => $attr)
				if (empty($attr["primary_key"]) && isset($attr["type"]) && ObjTypeHandler::isDBTypeBlob($attr["type"]))
					$code_for_empty_files .= '
	if (isset($attributes["' . $attr_name . '"]) && isset($data["' . $attr_name . '"]) && empty($_FILES["' . $attr_name . '"]["tmp_name"])) $attributes["' . $attr_name . '"] = $data["' . $attr_name . '"];';
			
			
			$code = '$options = isset($data["options"]) ? $data["options"] : null;
$this->mergeOptionsWithBusinessLogicLayer($options);

$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
$pks = isset($data["pks"]) ? $data["pks"] : null;

if ($attributes && $pks) {
	$status = true;
	
	' . $attributes_previous_code . '
	' . $pks_previous_code . '
	
	if ($status) {
		$data = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $get_service_id . '", $pks, $options);
		
		if (!$data || !is_array($data))
			return false;
		' . $code_for_empty_files . '
		
		foreach ($attributes as $attribute_name => $attribute_value)
			$data[$attribute_name] = $attribute_value;
	
		if ($status) {
			';
				
				if ($no_pks)
					$code .= 'foreach ($pks as $pk_name => $pk_value) {
				$data["old_" . $pk_name] = $pk_value;
				$data["new_" . $pk_name] = isset($attributes[$pk_name]) ? $attributes[$pk_name] : null;
				unset($data[$pk_name]);
			}
			
			';
			
			$code .= '$status = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $update_service_id . '", $data, $options);
		}
	}
	
	return $status;
}';
			
			//save resource method
			$method_comments = "Update an attribute from table: " . $this->db_table . ".";
			
			return self::addFunctionToFile($this->file_path, array(
				"name" => "updateAttribute",
				"arguments" => array("data" => null),
				"code" => $code,
				"comments" => $method_comments
			), $this->class_name);
		}
	}
	
	public function createMultipleSaveMethod($module_id, $insert_service_id, $update_service_id, &$error_message = null) {
		$insert_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "insert", $this->class_name);
		$update_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "update", $this->class_name);
		
		if (!$insert_method_exists)
			$insert_method_exists = self::createInsertMethod($module_id, $insert_service_id, $error_message);
		
		if (!$update_method_exists)
			$update_method_exists = self::createUpdateMethod($module_id, $update_service_id, $error_message);
		
		if (!$insert_method_exists)
			$error_message = "Error: Couldn't find any resource service for insert action.";
		else if (!$update_method_exists)
			$error_message = "Error: Couldn't find any resource service for update action.";
		else {
			$code = '$status = true;

$pks = isset($data["pks"]) ? $data["pks"] : null;
$attributes = isset($data["attributes"]) ? $data["attributes"] : null;

if ($attributes)
	for ($i = 0, $t = count($attributes); $i < $t; $i++) {
		$data["attributes"] = $attributes[$i];
		$data["pks"] = isset($pks[$i]) ? $pks[$i] : null;
		$is_insert = empty($pks[$i]);
		
		if ($is_insert && !$this->insert($data))
			$status = false;
		else if (!$is_insert && !$this->update($data))
			$status = false;
	}

return $status;';
			
			//save resource method
			$method_comments = "Update multiple records at once parsed resource record into table: " . $this->db_table . ".";
			
			return self::addFunctionToFile($this->file_path, array(
				"name" => "multipleSave",
				"arguments" => array("data" => null),
				"code" => $code,
				"comments" => $method_comments
			), $this->class_name);
		}
	}
	
	public function createInsertUpdateAttributeMethod($module_id, $get_service_id, $insert_service_id, $update_attribute_service_id, &$error_message = null) {
		$get_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "get", $this->class_name);
		$insert_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "insert", $this->class_name);
		$update_attribute_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "updateAttribute", $this->class_name);
		
		if (!$get_method_exists)
			$get_method_exists = self::createGetMethod($module_id, $get_service_id, $error_message);
		
		if (!$insert_method_exists)
			$insert_method_exists = self::createInsertMethod($module_id, $insert_service_id, $error_message);
		
		if (!$update_attribute_method_exists)
			$update_attribute_method_exists = self::createUpdateAttributeMethod($module_id, $update_attribute_service_id, $error_message);
		
		if (!$get_method_exists)
			$error_message = "Error: Couldn't find any resource service for get action.";
		else if (!$insert_method_exists)
			$error_message = "Error: Couldn't find any resource service for insert action.";
		else if (!$update_attribute_method_exists)
			$error_message = "Error: Couldn't find any resource service for update_attribute action.";
		else {
			$code = '$item_data = $this->get($data);

if (!empty($item_data))
	return $this->updateAttribute($data);

if (isset($data["pks"]) && is_array($data["pks"]))
	$data["attributes"] = isset($data["attributes"]) && is_array($data["attributes"]) ? array_merge($data["attributes"], $data["pks"]) : $data["pks"];

return $this->insert($data);';
			
			//save resource method
			$method_comments = "Insert or update an attribute from table: " . $this->db_table . ".";
			
			return self::addFunctionToFile($this->file_path, array(
				"name" => "insertUpdateAttribute",
				"arguments" => array("data" => null),
				"code" => $code,
				"comments" => $method_comments
			), $this->class_name);
		}
	}
	
	public function createInsertDeleteAttributeMethod($module_id, $get_service_id, $insert_service_id, $delete_service_id, &$error_message = null) {
		$get_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "get", $this->class_name);
		$insert_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "insert", $this->class_name);
		$delete_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "delete", $this->class_name);
		
		if (!$get_method_exists)
			$get_method_exists = self::createGetMethod($module_id, $get_service_id, $error_message);
		
		if (!$insert_method_exists)
			$insert_method_exists = self::createInsertMethod($module_id, $insert_service_id, $error_message);
		
		if (!$delete_method_exists)
			$delete_method_exists = self::createUpdateAttributeMethod($module_id, $delete_service_id, $error_message);
		
		if (!$get_method_exists)
			$error_message = "Error: Couldn't find any resource service for get action.";
		else if (!$insert_method_exists)
			$error_message = "Error: Couldn't find any resource service for insert action.";
		else if (!$delete_method_exists)
			$error_message = "Error: Couldn't find any resource service for delete action.";
		else {
			$code = '$exists = false;

//note that the $data["attributes"] should only have 1 attribute name based in the html element that this action was called.
if (isset($data["attributes"]) && is_array($data["attributes"]))
	foreach ($data["attributes"] as $attr_name => $attr_value)
		if ($attr_value) {
			$exists = true;
			break;
		}
	
$item_data = $this->get($data);

if (!empty($item_data))
	return $exists || $this->delete($data);

if (isset($data["pks"]) && is_array($data["pks"]))
	$data["attributes"] = isset($data["attributes"]) && is_array($data["attributes"]) ? array_merge($data["attributes"], $data["pks"]) : $data["pks"];

return !$exists || $this->insert($data);';
			
			//save resource method
			$method_comments = "Insert or delete a record based if a value from an attribute, from table: " . $this->db_table . ", exists or not.";
			
			return self::addFunctionToFile($this->file_path, array(
				"name" => "insertDeleteAttribute",
				"arguments" => array("data" => null),
				"code" => $code,
				"comments" => $method_comments
			), $this->class_name);
		}
	}
	
	public function createDeleteMethod($module_id, $delete_service_id, &$error_message = null) {
		if ($this->db_attrs) {
			$pks_name = array();
			
			foreach ($this->db_attrs as $attr_name => $attr)
				if (!empty($attr["primary_key"]))
					$pks_name[] = $attr_name;
			
			$pks_previous_code = self::getUpdateActionPreviousCode($this->tables, $this->db_table, $pks_name, '$pks', false);
			$pks_previous_code = str_replace("\n", "\n\t", $pks_previous_code);
			
			$code = '$options = isset($data["options"]) ? $data["options"] : null;
$this->mergeOptionsWithBusinessLogicLayer($options);

$status = false;
$pks = isset($data["pks"]) ? $data["pks"] : null;

if ($pks) {
	$status = true;
	
	' . $pks_previous_code . '
	
	if ($status)
		$status = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $delete_service_id . '", $pks, $options);
}

return $status;';
			
			//save resource method
			$method_comments = "Delete record from table: " . $this->db_table . ".";
			
			return self::addFunctionToFile($this->file_path, array(
				"name" => "delete",
				"arguments" => array("data" => null),
				"code" => $code,
				"comments" => $method_comments
			), $this->class_name);
		}
	}
	
	public function createMultipleDeleteMethod($module_id, $delete_service_id, &$error_message = null) {
		$delete_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "delete", $this->class_name);
		
		if (!$delete_method_exists)
			$delete_method_exists = self::createDeleteMethod($module_id, $delete_service_id, $error_message);
		
		if (!$delete_method_exists)
			$error_message = "Error: Couldn't find any resource service for delete action.";
		else {
			$code = '$status = true;
$pks = isset($data["pks"]) ? $data["pks"] : null;

if ($pks)
for ($i = 0, $t = count($pks); $i < $t; $i++) {
	$data["pks"] = $pks[$i];
	
	if (!$this->delete($data))
		$status = false;
}

return $status;';
			
			//save resource method
			$method_comments = "Delete multiple records at once parsed resource record from table: " . $this->db_table . ".";
			
			return self::addFunctionToFile($this->file_path, array(
				"name" => "multipleDelete",
				"arguments" => array("data" => null),
				"code" => $code,
				"comments" => $method_comments
			), $this->class_name);
		}
	}
	
	public function createMultipleInsertDeleteAttributeMethod($module_id, $delete_all_service_id, $insert_service_id = null, &$error_message = null) {
		$insert_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "insert", $this->class_name);
		
		if (!$insert_method_exists && $insert_service_id)
			$insert_method_exists = self::createInsertMethod($module_id, $insert_service_id, $error_message);
		
		if (!$insert_method_exists)
			$error_message = "Error: Couldn't find any resource service for insert action.";
		else {
			$code = '$options = isset($data["options"]) ? $data["options"] : null;
$this->mergeOptionsWithBusinessLogicLayer($options);

$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
$pks = isset($data["pks"]) ? $data["pks"] : null;

$delete_data = array(
	"conditions" => $pks
);
$this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $delete_all_service_id . '", $delete_data, $options);
//print_r($delete_data);die();

$items = array();

//note that the $attributes should only have 1 attribute name based in the html element that this action was called.
if (is_array($attributes))
	foreach ($attributes as $attr_name => $attr_value)
		if ($attr_value) {
			$item = $pks;
			
			if (is_array($attr_value)) {
				for ($i = 0, $t = count($attr_value); $i < $t; $i++) 
				    if ($attr_value[$i] || is_numeric($attr_value[$i])) {
						$item[$attr_name] = $attr_value[$i];
						$items[] = $item;
					}
			}
			else if ($attr_value || is_numeric($attr_value)) {
				$item[$attr_name] = $attr_value;
				$items[] = $item;
			}
		}
//print_r($items);var_dump($items);die();

$status = true;

if (!empty($items))
	for ($i = 0, $t = count($items); $i < $t; $i++)
		if (!$this->insert(array("attributes" => $items[$i], "options" => $options)))
			$status = false;

return $status;';
			
			//save resource method
			$method_comments = "Delete all records and insert new ones, based in an attribute, from table: " . $this->db_table . ", exists or not.";
			
			return self::addFunctionToFile($this->file_path, array(
				"name" => "multipleInsertDeleteAttribute",
				"arguments" => array("data" => null),
				"code" => $code,
				"comments" => $method_comments
			), $this->class_name);
		}
	}
	
	public function createGetMethod($module_id, $get_service_id, &$error_message = null) {
		$code = '$options = isset($data["options"]) ? $data["options"] : null;
$this->mergeOptionsWithBusinessLogicLayer($options);

$result = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $get_service_id . '", $data["pks"], $options);

' . self::getSelectItemActionNextCode($this->db_attrs, '$result') . '

return $result;';
		
		//save resource method
		$method_comments = "Get a parsed resource record from table: " . $this->db_table . ".";
		
		return self::addFunctionToFile($this->file_path, array(
			"name" => "get",
			"arguments" => array("data" => null),
			"code" => $code,
			"comments" => $method_comments
		), $this->class_name);
	}
	
	public function createGetAllMethod($module_id, $get_all_service_id, &$error_message = null) {
		$code = self::getCodeForGetAllOrCountOrGetAllOptionsMethod($module_id, $get_all_service_id);
		$code .= '
' . self::getSelectItemsActionNextCode($this->db_attrs, '$result');
		$code .= '
return $result;';
		
		//save resource method
		$method_comments = "Get records from table: " . $this->db_table . ".";
		
		return self::addFunctionToFile($this->file_path, array(
			"name" => "getAll",
			"arguments" => array("data" => null),
			"code" => $code,
			"comments" => $method_comments
		), $this->class_name);
	}
	
	public function createCountMethod($module_id, $count_service_id, &$error_message = null) {
		$code = self::getCodeForGetAllOrCountOrGetAllOptionsMethod($module_id, $count_service_id);
		$code .= '
return $result;';
		
		//save resource method
		$method_comments = "Count records from table: " . $this->db_table . ".";
		
		return self::addFunctionToFile($this->file_path, array(
			"name" => "count",
			"arguments" => array("data" => null),
			"code" => $code,
			"comments" => $method_comments
		), $this->class_name);
	}
	
	public function createGetAllOptionsMethod($module_id, $get_all_service_id, &$error_message = null) {
		$get_all_method_exists = PHPCodePrintingHandler::getFunctionFromFile($this->file_path, "getAll", $this->class_name);
		
		if (!$get_all_method_exists)
			$get_all_method_exists = self::createGetAllMethod($module_id, $get_all_service_id, $error_message);
		
		if (!$get_all_method_exists)
			$error_message = "Error: Couldn't find any resource service for get_all action.";
		else {
			$options_settings = self::getTableOptionsSettings($this->db_table, $this->tables);
			
			if (!$options_settings)
				$error_message = "Error: No primary attribute or no valid attributes when trying to create the getAllOptions method.";
			else {
				$keys = isset($options_settings["keys"]) ? $options_settings["keys"] : null;
				$values = isset($options_settings["values"]) ? $options_settings["values"] : null;
				
				//prepare code
				$code = '$result = $this->getAll($data);

$options = array();

if ($result) 
	for ($i = 0, $t = count($result); $i < $t; $i++) {
		$item = $result[$i];
		$key = ';
			
				for ($i = 0, $t = count($keys); $i < $t; $i++)
					$code .= ($i > 0 ? ' . "_" . ' : "") . '$item["' . $keys[$i] . '"]';
				
				$code .= ';
		$value = ';
				
				for ($i = 0, $t = count($values); $i < $t; $i++)
					$code .= ($i > 0 ? ' . "_" . ' : "") . '$item["' . $values[$i] . '"]';
				
				$code .= ';
		$options[$key] = $value;
	}

return $options;';
			
				//save resource method
				$method_comments = "Get key-value pair list from table: " . $this->db_table . ", where the key is the table primary key and the value is the table attribute label.";
				
				return self::addFunctionToFile($this->file_path, array(
					"name" => "getAllOptions",
					"arguments" => array("data" => null),
					"code" => $code,
					"comments" => $method_comments
				), $this->class_name);
			}
		}
	}
	
	//copied from CMSPresentationFormSettingsUIHandler::getSelectItemActionNextCode, so if you change this method, please mae the correspodnent changes in this other method too.
	//used in SequentialLogicalActivityResourceCreator::getSelectItemActionNextCode
	public static function getSelectItemActionNextCode($attrs, $var_prefix, $attrs_name_to_filter = null) {
		$code = "";
		$var_prefix = substr($var_prefix, 0, 1) == '$' || substr($var_prefix, 0, 2) == '@$' ? $var_prefix : '$' . $var_prefix;
		
		if ($attrs)
			foreach ($attrs as $attr_name => $attr)
				if (!is_array($attrs_name_to_filter) || in_array($attr_name, $attrs_name_to_filter))
					if (isset($attr["type"]) && ObjTypeHandler::isDBTypeDate($attr["type"]))
						$code .= 'if (' . $var_prefix . '["' . $attr_name . '"] == "0000-00-00 00:00:00" || ' . $var_prefix . '["' . $attr_name . '"] == "0000-00-00") ' . $var_prefix . '["' . $attr_name . '"] = "";' . "\n";
		
		return $code;
	}
	
	//copied from CMSPresentationFormSettingsUIHandler::getSelectItemsActionNextCode, so if you change this method, please mae the correspodnent changes in this other method too.
	//used in SequentialLogicalActivityResourceCreator::getSelectItemsActionNextCode
	public static function getSelectItemsActionNextCode($attrs, $var_prefix) {
		$db_date_attrs = array();
		$var_prefix = substr($var_prefix, 0, 1) == '$' || substr($var_prefix, 0, 2) == '@$' ? $var_prefix : '$' . $var_prefix;
		
		if ($attrs)
			foreach ($attrs as $attr_name => $attr) 
				if (isset($attr["type"]) && ObjTypeHandler::isDBTypeDate($attr["type"]))
					$db_date_attrs[] = $attr_name;
		
		if ($db_date_attrs) {
			$code = 'if (is_array(' . $var_prefix . '))
	foreach (' . $var_prefix . ' as $k => &$v) {' . "\n";
			
			foreach ($db_date_attrs as $attr_name)
				$code .= "\t\t" . 'if (isset($v["' . $attr_name . '"]) && ($v["' . $attr_name . '"] == "0000-00-00 00:00:00" || $v["' . $attr_name . '"] == "0000-00-00")) $v["' . $attr_name . '"] = "";' . "\n";
			
			$code .= "\t}\n";
			
			return $code;
		}
		
		return null;
	}
	
	public static function getCodeForGetAllOrCountOrGetAllOptionsMethod($module_id, $service_id) {
		$code = '$options = isset($data["options"]) ? $data["options"] : null;
$this->mergeOptionsWithBusinessLogicLayer($options);

$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
$conditions_type = isset($data["conditions_type"]) ? $data["conditions_type"] : null;
$conditions_case = isset($data["conditions_case"]) ? $data["conditions_case"] : null;
$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;

';
		$code .= self::getDefaultConditionsCode();
		$code .= '
$data = array(
	"conditions" => $conditions,
	"conditions_join" => $conditions_join
);

$result = $this->getBusinessLogicLayer()->callBusinessLogic("' . $module_id . '", "' . $service_id . '", $data, $options);
';
		return $code;
	}
	
	//used in the SequentialLogicalActivityResourceCreator::getTaskConditionsCode()
	public static function getDefaultConditionsCode() {
		$code = '//prepare $conditions based in $conditions_type: starts_with or ends_with
if ($conditions)
	foreach ($conditions as $attribute_name => $attribute_value) {
		$attribute_condition_type = is_array($conditions_type) ? (isset($conditions_type[$attribute_name]) ? $conditions_type[$attribute_name] : null) : $conditions_type;
		$attribute_operator = $attribute_condition_type == "starts_with" || $attribute_condition_type == "ends_with" || $attribute_condition_type == "contains" ? "like" : $attribute_condition_type;
		$attribute_case = is_array($conditions_case) ? (isset($conditions_case[$attribute_name]) ? $conditions_case[$attribute_name] : null) : $conditions_case;
		$attribute_join = is_array($conditions_join) ? (isset($conditions_join[$attribute_name]) ? $conditions_join[$attribute_name] : null) : $conditions_join;
		
		if ($attribute_operator && $attribute_operator != "=" && $attribute_operator != "equal") {
			if (is_array($attribute_value) && $attribute_operator != "in" && $attribute_operator != "not in") {
				$conditions[$attribute_name] = array();
				
				foreach ($attribute_value as $v)
					$conditions[$attribute_name][] = array(
						"operator" => $attribute_operator,
						"value" => ($attribute_condition_type == "starts_with" || $attribute_condition_type == "contains" ? "%" : "") . ($attribute_case == "insensitive" ? strtolower($v) : $v) . ($attribute_condition_type == "ends_with" || $attribute_condition_type == "contains" ? "%" : ""),
					);
			}
			else {
				if (($attribute_operator == "in" || $attribute_operator == "not in") && $attribute_case == "insensitive" && is_array($attribute_value))
					foreach ($attribute_value as $k => $v)
						if (is_string($v))
							$attribute_value[$k] = strtolower($v);
				
	    			$conditions[$attribute_name] = array(
					"operator" => $attribute_operator,
					"value" => $attribute_operator == "in" || $attribute_operator == "not in" ? $attribute_value : (
						($attribute_condition_type == "starts_with" || $attribute_condition_type == "contains" ? "%" : "") . ($attribute_case == "insensitive" ? strtolower($attribute_value) : $attribute_value) . ($attribute_condition_type == "ends_with" || $attribute_condition_type == "contains" ? "%" : "")
					),
				);
			}
			
			if ($attribute_case == "insensitive") {
				$conditions["lower($attribute_name)"] = $conditions[$attribute_name];
				unset($conditions[$attribute_name]);
				$attribute_name = "lower($attribute_name)";
			}
		}
		
		if (strtolower($attribute_join) == "or") {
			$conditions[$attribute_join][$attribute_name] = $conditions[$attribute_name];
			unset($conditions[$attribute_name]);
	    	}
	}
	
$conditions_join = "and";
';
		return $code;
	}
	
	public static function getTableOptionsSettings($db_table, $tables, $resource_data = null) {
		if (!is_array($resource_data))
			$resource_data = array(array("table" => $db_table));
		else if (array_key_exists("table", $resource_data))
			$resource_data = array($resource_data);
		
		$attr_fk = WorkFlowDataAccessHandler::getTableAttributeFKTable($resource_data, $tables);
		$fk_table = isset($attr_fk["table"]) ? $attr_fk["table"] : null;
		$fk_attr = isset($attr_fk["attribute"]) ? $attr_fk["attribute"] : null;
		$fk_attrs = $fk_table ? WorkFlowDBHandler::getTableFromTables($tables, $fk_table) : null;
		
		//get the pks name $attribute_name
		if (!$fk_attr && $fk_attrs) {
			$fk_attr = array();
			$no_pks = true;
			$first_attr_name = null;
			
			foreach ($fk_attrs as $attr_name => $attr) {
				if (!empty($attr["primary_key"])) {
					$fk_attr[] = $attr_name;
					$no_pks = false;
				}
				
				if (!$first_attr_name)
					$first_attr_name = $attr_name;
			}
			
			if ($no_pks && !$fk_attr) {
				$title_attr = WorkFlowDataAccessHandler::getTableAttrTitle($fk_attrs, $fk_table);
				$fk_attr[] = $title_attr ? $title_attr : $first_attr_name;
			}
		}
		
		if ($fk_attr) {
			$title_attr = $fk_attrs ? WorkFlowDataAccessHandler::getTableAttrTitle($fk_attrs, $fk_table) : null;
			$title_attr = $title_attr ? $title_attr : $fk_attr; //set $title_attr to $fk_attr if not exist. In this case the getAllOptions will simply return the a list with key/value pair like: 'primary key/primary key'.
			
			$keys = is_array($fk_attr) ? $fk_attr : array($fk_attr);
			$values = is_array($title_attr) ? $title_attr : array($title_attr);
			
			return array(
				"keys" => $keys,
				"values" => $values,
			);
		}
		
		return null;
	}
	
	//This method is called inside of the getUpdateActionPreviousCode too
	//copied from CMSPresentationFormSettingsUIHandler::getInsertActionPreviousCode and SequentialLogicalActivityResourceCreator::getInsertActionPreviousCode, so if you change this method, please make the correspodnent changes in this other method too.
	public static function getInsertActionPreviousCode($tables, $table_name, $attributes, $var_prefix, $is_insert_task = true, $is_update_task = false) {
		$code = "";
		$var_prefix = substr($var_prefix, 0, 1) == '$' || substr($var_prefix, 0, 2) == '@$' ? $var_prefix : '$' . $var_prefix;
		
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		$logged_user_id_code = null;
		
		foreach ($attributes as $attr_name) {
			$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
			$is_created_attribute = ObjTypeHandler::isDBAttributeNameACreatedDate($attr_name) || ObjTypeHandler::isDBAttributeNameACreatedUserId($attr_name);
			
			//if is an update action and is a create_date or create_by attribute, ignore attribute
			if ($is_update_task && empty($attr["primary_key"]) && $is_created_attribute) 
				continue;
			
			$type = isset($attr["type"]) ? $attr["type"] : null;
			$allow_null = !isset($attr["null"]) || $attr["null"];
			$is_numeric_type = ObjTypeHandler::isDBTypeNumeric($type) || ObjTypeHandler::isPHPTypeNumeric($type);
			$is_blob_type = ObjTypeHandler::isDBTypeBlob($type);
			
			$is_logged_user_id_attribute = (ObjTypeHandler::isDBAttributeNameACreatedUserId($attr_name) || ObjTypeHandler::isDBAttributeNameAModifiedUserId($attr_name)) && $is_numeric_type;
			
			//Note that the array_key_exists is very important bc of the update_attribute action, otherwisse we are adding attributes when the user only ask us to save another attribute. Is important too for the business logic services where we only want to check the values if they exists, bc the default value is already set inside of the business logic service.
			$array_key_exists = 'array_key_exists("' . $attr_name . '", ' . $var_prefix . ') && ';
			
			//check if field is checkbox/boolean and if yes the default should be replaced by 0, bc it means the user set the checkbox to unchcekd which makes the browser to not include this attribute in the requests...
			//Note that this must happens if strlen($attr["default"]) > 0 or if there is no $attr["default"]. In both cases this must happen! Unless it allows NULL, which in this case we don't need to set the default to 0, bc we can set it to null, as shown in the code in this function.
			$input_type = null;
			CMSPresentationFormSettingsUIHandler::prepareFormInputParameters($attr, $input_type);
			$attr_default = isset($attr["default"]) ? $attr["default"] : null;
			$is_checkbox = (strlen($attr_default) || !$allow_null) && ($input_type == "checkbox" || $input_type == "radio") && $is_numeric_type;
			
			if ($is_checkbox) 
				$attr["default"] = 0; //discart on purpose the $attr["default"], bc the default value may be 1, and we want to set it to 0 instead, since if the user doesn't check the checkbox, it means the browser will return an empty string and we want to save his choice in the DB. If we leave the original $attr["default"] (that could be 1) than is the same that the user check the checkbox, which doesn't make sense.  
			
			//prepare code
			if ($is_insert_task && !empty($attr["primary_key"]) && WorkFlowDataAccessHandler::isAutoIncrementedAttribute($attr)) {
				$code .= ""; //don't do anything in BL
			}
			else if ($allow_null && ($is_numeric_type || ObjTypeHandler::isDBTypeDate($type))) {
				//reset the values to default, bc if they are a boolean or a tinyint (with length 1) and have an empty string, they need to be set to null, otherwise they will give an error in the business logic services, bc will not be set correctly with the default values. Note that the default values will be set by the business logic services.
				$default = 'null';
				
				//init the $var_prefix["logged_user_id"] var with $data["logged_user_id"] to be passed to the business logic service
				if ($is_logged_user_id_attribute)
					$logged_user_id_code = $var_prefix . '["logged_user_id"] = isset($data["logged_user_id"]) ? $data["logged_user_id"] : null;' . "\n";
				else if ($is_checkbox) //set checkbox value to 0, bc by default the browser will return an empty string. if we don't set this value to 0, then the business logic will set the default value that could be 1, and we don't want this.
					$default = isset($attr["default"]) ? $attr["default"] : null; //Note that the $attr["default"] was already changed to 0 above.
				
				//note that here will be always with array_key_exists 
				$code .= 'if (' . $array_key_exists . '!strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) ' . $var_prefix . '["' . $attr_name . '"] = ' . $default . ';' . "\n";
			}
			else if ($is_numeric_type || ObjTypeHandler::isDBTypeBoolean($type)) {
				//reset the values to default, bc if they are a boolean or a tinyint (with length 1) and have an empty string, they need to be set to null, otherwise they will give an error in the business logic services, bc will not be set correctly with the default values. Note that the default values will be set by the business logic services.
				//$default = strlen($attr["default"]) ? (is_numeric($attr["default"]) ? $attr["default"] : '"' . $attr["default"] . '"') : 'null';
				$default = 'null';
				
				//init the $var_prefix["logged_user_id"] var with $data["logged_user_id"] to be passed to the business logic service
				if ($is_logged_user_id_attribute) {
					$logged_user_id_code = $var_prefix . '["logged_user_id"] = isset($data["logged_user_id"]) ? $data["logged_user_id"] : null;' . "\n";
					
					//If this attribute has an empty value and has no default and cannot be null, them it will give an error in the business logic service, bc this attribute cannot be null. So we set the logged_user_id as default value.
					if (empty($attr["default"]) && !strlen($attr["default"]) && !$allow_null)
						$default = $var_prefix . '["logged_user_id"] > 0 ? ' . $var_prefix . '["logged_user_id"] : ' . $default;
				}
				else if ($is_checkbox) //set checkbox value to 0, bc by default the browser will return an empty string. if we don't set this value to 0, then the business logic will set the default value that could be 1, and we don't want this.
					$default = isset($attr["default"]) ? $attr["default"] : null; //Note that the $attr["default"] was already changed to 0 above.
				
				//note that here will be always with array_key_exists 
				$code .= 'if (' . $array_key_exists . '!is_numeric(' . $var_prefix . '["' . $attr_name . '"])) ' . $var_prefix . '["' . $attr_name . '"] = ' . $default . ';' . "\n";
			}
			
			if ($is_blob_type)
				$code .= 'if (!empty($_FILES["' . $attr_name . '"]["tmp_name"]) && file_exists($_FILES["' . $attr_name . '"]["tmp_name"])) ' . $var_prefix . '["' . $attr_name . '"] = file_get_contents($_FILES["' . $attr_name . '"]["tmp_name"]);' . "\n";
		}
		
		if ($logged_user_id_code)
			$code = $logged_user_id_code . "\n" . $code;
		
		return $code;
	}
	
	//copied from CMSPresentationFormSettingsUIHandler::getUpdateActionPreviousCode and SequentialLogicalActivityResourceCreator::getUpdateActionPreviousCode, so if you change this method, please make the correspodnent changes in this other method too.
	public static function getUpdateActionPreviousCode($tables, $table_name, $attributes, $var_prefix, $is_update_task = true) {
		$code = self::getInsertActionPreviousCode($tables, $table_name, $attributes, $var_prefix, false, $is_update_task);
		//Note that we have more code in CMSPresentationFormSettingsUIHandler::getUpdateActionPreviousCode, but that do the same than the code in the createUpdateMethod method, this is, the code in the CMSPresentationFormSettingsUIHandler::getUpdateActionPreviousCode prepare the pks to be replaced by new pks, which is what we already do in the createUpdateMethod method.
		
		$attrs = WorkFlowDBHandler::getTableFromTables($tables, $table_name);
		
		foreach ($attributes as $attr_name) {
			$attr = isset($attrs[$attr_name]) ? $attrs[$attr_name] : null;
			
			if (!empty($attr["primary_key"])) {
				$attr_type = isset($attr["type"]) ? $attr["type"] : null;
				
				if (ObjTypeHandler::isDBTypeNumeric($attr_type) || ObjTypeHandler::isPHPTypeNumeric($attr_type))
					$code .= 'if (array_key_exists("' . $attr_name . '", ' . $var_prefix . ') && !is_numeric(' . $var_prefix . '["' . $attr_name . '"])) $status = false;' . "\n";
				else
					$code .= 'if (array_key_exists("' . $attr_name . '", ' . $var_prefix . ') && !strlen(trim(' . $var_prefix . '["' . $attr_name . '"]))) $status = false;' . "\n";
			}
		}
		
		return $code;
	}
	
	private static function addFunctionToFile($file_path, $method_data, $class_name) {
		//check if method doesn't exist already, bc meanwhile it may was created before. Note that it is possible to happen multiple concurrent calls of this function with the same method name. So just in case we check if exists again...
		$file_method_exists = PHPCodePrintingHandler::getFunctionFromFile($file_path, $method_data["name"], $class_name);
		
		if (!$file_method_exists)
			return PHPCodePrintingHandler::addFunctionToFile($file_path, $method_data, $class_name);
		
		return true;
	}
}
?>
