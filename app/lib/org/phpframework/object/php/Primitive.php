<?php
include_once get_lib("org.phpframework.object.ObjType");
include_once get_lib("org.phpframework.object.exception.ObjTypeException");
include_once get_lib("org.phpframework.object.ObjTypeHandler");

class Primitive extends ObjType {
	private $type;
	public $is_primitive = true;
	
	public function __construct($type, $data = false) {
		$this->setType($type);
		
		if ($data !== false)
			$this->setData($data);
	}
	
	public static function getTypes() {
		$types = ObjTypeHandler::getDBTypes();
		$types["mixed"] = "Mixed";
		$types["numeric"] = "Numeric";
		$types["bool"] = "Boolean";
		$types["array"] = "Array";
		$types["object"] = "Object";
		$types["string"] = "String";
		$types["uuid"] = "UUID";
		$types["cidr"] = "CIDR";
		$types["inet"] = "INET";
		$types["mac addr"] = "Mac Addr";
		
		return $types;
	}
	public static function getNumericTypes() {
		$types = ObjTypeHandler::getDBNumericTypes();
		$types[] = "numeric";
		$types[] = "bool";
		
		return $types;
	}
	
	public function getType() {return $this->type;}
	public function setType($type) {$this->type = strtolower($type);}
	
	public function setData($data) {
		$ok = false;
		
		switch ($this->type) {
			case "smallserial":
			case "intserial":
			case "bigserial":
				$ok = is_numeric($data) && $data > 0;
				break;
			case "numeric":
			case "bit":
			case "tinyint":
			case "smallint":
			case "int":
			case "bigint":
			case "decimal":
			case "double":
			case "float":
			case "money":
			case "coordinate":
				$ok = is_numeric($data);
				break;
			case "bool":
			case "boolean":
				$ok = $data === TRUE || $data === false || $data === 0 || $data === 1 || $data === "0" || $data === "1" || ObjTypeHandler::isDBTypeBoolean($data);
				
				if (!$ok) {
					$v = strtolower($data);
					$ok = $v === "true" || $v === "false";
				}
				break;
			case "array":
				$ok = is_array($data);
				break;
			case "object":
				$ok = is_object($data);
				break;
			case "string":
			case "char":
			case "varchar":
			case "mediumtext":
			case "text":
			case "longtext":
			case "blob":
			case "longblob":
				$ok = !is_object($data) && !is_array($data);
				break;
			case "date":
			case "datetime":
			case "timestamp":
			case "time":
				$ok = !is_object($data) && !is_array($data);
				break;
			case "varchar(36)":
			case "uuid":
			case "varchar(44)":
			case "cidr":
			case "varchar(43)":
			case "inet":
			case "varchar(17)":
			case "mac addr":
				$ok = !is_object($data) && !is_array($data);
				break;
			case "name":
				//if (preg_match("/^[a-z]+/i", $data))
				if (preg_match("/[\w]+/iu", $data)) //'\w' means all words with '_' and '/u' means with accents and ç too.
					$ok = true;
				break;
			
			default: $ok = true;//in the case of mixed, no_string, not_string
		}
		
		if ($ok) {
			$this->data = $data;
			return true;
		}
		
		launch_exception(new ObjTypeException($this->type, $data));
		return false;
	}
}
?>
