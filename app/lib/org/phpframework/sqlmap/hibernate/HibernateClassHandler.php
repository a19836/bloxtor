<?php
include_once get_lib("org.phpframework.sqlmap.SQLMap");
include_once get_lib("org.phpframework.util.MyArray");

class HibernateClassHandler extends SQLMap {
	private $HibernateClientCache;
	private $RDBBroker;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function setRDBBroker($RDBBroker) {
		$this->RDBBroker = $RDBBroker;
	}
	
	public function setHibernateClientCache($HibernateClientCache) {
		$this->HibernateClientCache = $HibernateClientCache;
	}
	
	public function getClassFilePath($class_file_obj_name, $obj_data, $options = false) {
		$file_path = $this->HibernateClientCache->getCachedPHPClassPath($class_file_obj_name);
		
		if ($this->HibernateClientCache->cachedPHPClassExists($class_file_obj_name))
			return $file_path;
		else {
			$content = $this->getClassFileContent($class_file_obj_name, $obj_data, $options);
			
			if ($this->HibernateClientCache->setCachedPHPClass($class_file_obj_name, $content))
				return $file_path;
		}
		
		return false;
	}
	
	private function getClassFileContent($class_file_obj_name, $obj_data, $options = false) {
		$extends = XMLFileParser::getAttribute($obj_data, "extends");
		$table = XMLFileParser::getAttribute($obj_data, "table");
		$obj_name = XMLFileParser::getAttribute($obj_data, "name");
		$obj_name = $obj_name ? $obj_name : $class_file_obj_name;
		
		if($extends) {
			$extend_class_path = $extends;
			$extend_class_name = explode(".", $extends);
			$extend_class_name = $extend_class_name[count($extend_class_name) - 1];
			$extend_class_name = explode("(", $extend_class_name);
			$extend_class_name = $extend_class_name[0];
		}
		else {
			$extend_class_path = "org.phpframework.sqlmap.hibernate.HibernateModel";
			$extend_class_name = "HibernateModel";
		}
	
		$parameter_class = XMLFileParser::getAttribute($obj_data, "parameter_class");
		$parameter_map = isset($obj_data["childs"]["parameter_map"][0]) ? $obj_data["childs"]["parameter_map"][0] : null;
		
		$result_class = XMLFileParser::getAttribute($obj_data, "result_class");
		$result_map = isset($obj_data["childs"]["result_map"][0]) ? $obj_data["childs"]["result_map"][0] : null;
		
		$ids = isset($obj_data["childs"]["id"]) ? $obj_data["childs"]["id"] : null;
		$properties = !empty($parameter_map["parameter"]) ? $parameter_map["parameter"] : array();
		$many_to_one = isset($obj_data["childs"]["relationships"]["many_to_one"]) ? $obj_data["childs"]["relationships"]["many_to_one"] : null;
		$many_to_many = isset($obj_data["childs"]["relationships"]["many_to_many"]) ? $obj_data["childs"]["relationships"]["many_to_many"] : null;
		$one_to_many = isset($obj_data["childs"]["relationships"]["one_to_many"]) ? $obj_data["childs"]["relationships"]["one_to_many"] : null;
		$one_to_one = isset($obj_data["childs"]["relationships"]["one_to_one"]) ? $obj_data["childs"]["relationships"]["one_to_one"] : null;
		$queries = isset($obj_data["childs"]["queries"]) ? $obj_data["childs"]["queries"] : null;
		
		$obj_table_attributes = $this->RDBBroker->getFunction("listTableFields", $table, $options);
		
		$obj_ids = $ids;
		$obj_properties = $this->convertProperties($properties);
		$obj_many_to_one = self::assignResultMapToRelations($many_to_one, $obj_data);
		$obj_many_to_many = self::assignResultMapToRelations($many_to_many, $obj_data);
		$obj_one_to_many = self::assignResultMapToRelations($one_to_many, $obj_data);
		$obj_one_to_one = self::assignResultMapToRelations($one_to_one, $obj_data);
		$obj_queries = $queries;
		
		$obj_ids = $this->checkIds($obj_ids, $obj_table_attributes, $obj_properties);
		$obj_properties = $this->checkProperties($obj_properties, $obj_ids);
		
		$obj_properties_to_attributes = $this->convertPropertiesToAttributes($obj_properties, $obj_table_attributes);
		
		$obj_ids = is_array($obj_ids) ? $obj_ids : array();
		$obj_table_attributes = is_array($obj_table_attributes) ? $obj_table_attributes : array();
		$obj_many_to_one = is_array($obj_many_to_one) ? $obj_many_to_one : array();
		$obj_many_to_many = is_array($obj_many_to_many) ? $obj_many_to_many : array();
		$obj_one_to_many = is_array($obj_one_to_many) ? $obj_one_to_many : array();
		$obj_one_to_one = is_array($obj_one_to_one) ? $obj_one_to_one : array();
		$obj_queries = is_array($obj_queries) ? $obj_queries : array();
		$obj_properties_to_attributes = is_array($obj_properties_to_attributes) ? $obj_properties_to_attributes : array();
		
	/*echo "\n<br>parameter_class:";echo($parameter_class);
	echo "\n<br>parameter_map:";print_r($parameter_map);
	echo "\n<br>result_class:";echo($result_class);
	echo "\n<br>result_map:";print_r($result_map);
	echo "\n<br>obj_ids:";print_r($obj_ids);
	echo "\n<br>obj_properties:";print_r($obj_properties);
	echo "\n<br>obj_properties_to_attributes:";print_r($obj_properties_to_attributes);
	echo "\n<br>obj_many_to_one:";print_r($obj_many_to_one);
	echo "\n<br>obj_many_to_many:";print_r($obj_many_to_many);
	echo "\n<br>obj_one_to_many:";print_r($obj_one_to_many);
	echo "\n<br>obj_one_to_one:";print_r($obj_one_to_one);
	echo "\n<br>obj_queries:";print_r($obj_queries);
	die();*/
		
		if($parameter_class && $parameter_map) {
			launch_exception(new HibernateException(4, XMLFileParser::getAttribute($obj_data, "name")));
		}
		
		if($result_class && $result_map) {
			launch_exception(new HibernateException(5, XMLFileParser::getAttribute($obj_data, "name")));
		}
		
		$code = "<?php
include_once get_lib('{$extend_class_path}');

class $class_file_obj_name extends $extend_class_name {
	
	public function __construct() {
		parent::__construct();
		
		\$this->setDefaultOptions(".MyArray::arrayToString( $options ).");
		
		\$this->setObjName('".$obj_name."');
		\$this->setTableName('".$table."');
		\$this->setExtendClassName('".$extend_class_name."');
		\$this->setExtendClassPath('".$extend_class_path."');
		
		\$this->setParameterClass('".$parameter_class."');
		\$this->setParameterMap(".MyArray::arrayToString( $parameter_map ).");
		\$this->setResultClass('".$result_class."');
		\$this->setResultMap(".MyArray::arrayToString( $result_map ).");
		
		\$this->setIds(".MyArray::arrayToString( $obj_ids ).");
		\$this->setTableAttributes(".MyArray::arrayToString( $obj_table_attributes ).");
		\$this->setManyToOne(".MyArray::arrayToString( $obj_many_to_one ).");
		\$this->setManyToMany(".MyArray::arrayToString( $obj_many_to_many ).");
		\$this->setOneToMany(".MyArray::arrayToString( $obj_one_to_many ).");
		\$this->setOneToOne(".MyArray::arrayToString( $obj_one_to_one ).");
		\$this->setQueries(".MyArray::arrayToString( $obj_queries ).");
		\$this->setPropertiesToAttributes(".MyArray::arrayToString( $obj_properties_to_attributes ).");
		
		\$this->setNodesData( \$this->getQueries() );
	}
}
?>";
		return $code;
	}
	
	public static function convertIds($items) {
		$id_generator_types = array("hidden", "assign", "increment", "select", "procedure", "md5");
		
		$new_items = array();
		$t = $items ? count($items) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = $items[$i];
			
			$id_name = XMLFileParser::getAttribute($item, "column");
			if ($id_name) {
				$id_data = array("output_name" => $id_name);
				$generator = isset($item["childs"]["generator"][0]) ? $item["childs"]["generator"][0] : null;
				
				if ($generator) {
					$type = strtolower(XMLFileParser::getAttribute($generator, "type"));
					
					if (!in_array($type, $id_generator_types)) 
						launch_exception(new HibernateException(3, array($type, $id_generator_types)));
					
					$id_data["generator"] = array("type" => $type);
					
					$value = XMLFileParser::getValue($generator);
					if (strlen($value)) 
						$id_data["generator"]["value"] = $value;
				}
				$new_items[$id_name] = $id_data;
			}
		}
		return $new_items;
	}
	
	private function checkIds($obj_ids, $obj_table_attributes, $obj_properties) {
		if (is_array($obj_table_attributes) && is_array($obj_ids)) {
			foreach ($obj_table_attributes as $akey => $avalue) {
				if (!empty($avalue["primary_key"])) {
					if (empty($obj_ids[$akey]))
						$obj_ids[$akey] = array("output_name" => $akey);
				}
			}
		}
		
		if (is_array($obj_properties) && is_array($obj_ids)) {
			$obj_ids_keys = array_keys($obj_ids);
			$t = count($obj_ids_keys);
			
			for ($i = 0; $i < $t; $i++) {
				$pkey = $obj_ids_keys[$i];
				$pvalue = isset($obj_ids[$pkey]) ? $obj_ids[$pkey] : null;
				$pon = isset($pvalue["output_name"]) ? $pvalue["output_name"] : null;
				
				foreach ($obj_properties as $okey => $ovalue) {
					$oon = isset($ovalue["output_name"]) ? $ovalue["output_name"] : null;
					
					if ($oon == $pon) {
						unset($obj_ids[$pkey]);
						$obj_ids[$okey] = $pvalue;
						break;
					}
				}
			}
		}
		
		return $obj_ids;
	}
	
	private function convertProperties($items) {
		$new_items = array();
		$t = $items ? count($items) : 0;
		for ($i = 0; $i < $t; $i++) {
			$item = $items[$i];
			
			if (is_array($item)) {
				$item_data = array();
				$item_name = "";
				foreach ($item as $key => $value) {
					$key = strtolower($key);
					
					if ($key == "input_name") {
						$item_name = $value;
					}
					else {
						$item_data[$key] = $value;
					}
				}
				
				if ($item_name) {
					$new_items[ $item_name ] = $item_data;
				}
			}
		}
		return $new_items;
	}
	
	private function checkProperties($obj_properties, $obj_ids) {
		if (is_array($obj_properties) && is_array($obj_ids)) {
			foreach ($obj_ids as $pkey => $pvalue) {
				if ($pkey) {
					$exists = false;
					foreach ($obj_properties as $okey => $ovalue) {
						$oon = isset($ovalue["output_name"]) ? $ovalue["output_name"] : null;
						
						if ($oon == $pkey) {
							$exists = true;
							break;
						}
					}
				
					if (!$exists)
						$obj_properties[ $pkey ] = array(
							"output_name" => isset($pvalue["output_name"]) ? $pvalue["output_name"] : null
						);
				}
			}
		}
		return $obj_properties;
	}
	
	public static function assignResultMapToRelations($items, $obj_data) {
		if (is_array($items)) {
			foreach ($items as $item_id => $item) {
				if (!empty($item["result_map"])) {
					$result_map_id = $item["result_map"];
					$items[$item_id]["result_map"] = isset($obj_data["childs"]["relationships"]["result_map"][$result_map_id]) ? $obj_data["childs"]["relationships"]["result_map"][$result_map_id] : null;
				}	
			}
		}
		return $items;
	}
	
	public static function convertRelations($items, $obj_data) {
		$new_items = array();
		
		$is_assoc = array_keys($items) !== range(0, count($items) - 1);
		if ($is_assoc)
			$items = array($items);
		
		$t = count($items);
		for ($i = 0; $i < $t; $i++) {
			$item = $items[$i];
			
			$rel_name = XMLFileParser::getAttribute($item, "name");
			if ($rel_name) {
				$result_class = XMLFileParser::getAttribute($item, "result_class");
				$result_map_id = XMLFileParser::getAttribute($item, "result_map");
				
				if ($result_class && $result_map_id) {
					launch_exception(new HibernateException(9, $rel_name));
				}
			
				$new_item = array();
				
				if ($result_class)
					$new_item["result_class"] = $result_class;
				
				if ($result_map_id)
					$new_item["result_map"] = $result_map_id;
				
				$nodes = isset($item["childs"]["attribute"]) ? $item["childs"]["attribute"] : null;
				$t1 = $nodes ? count($nodes) : 0;
				if ($t1) {
					$attrs = array();
					for ($j = 0; $j < $t1; $j++) {
						$node = $nodes[$j];
					
						$attrs[] = XMLFileParser::getAttributes($node, array("name", "column", "table"));
					}
					$new_item["attribute"] = $attrs;
				}
			
				$nodes = isset($item["childs"]["key"]) ? $item["childs"]["key"] : null;
				$t1 = $nodes ? count($nodes) : 0;
				if ($t1) {
					$keys = array();
					for ($j = 0; $j < $t1; $j++) {
						$node = $nodes[$j];
					
						$keys[] = XMLFileParser::getAttributes($node, array("pcolumn", "ptable", "fcolumn", "ftable", "join", "value"));
					}
					$new_item["key"] = $keys;
				}
			
				$nodes = isset($item["childs"]["condition"]) ? $item["childs"]["condition"] : null;
				$t1 = $nodes ? count($nodes) : 0;
				if ($t1) {
					$conditions = array();
					for ($j = 0; $j < $t1; $j++) {
						$node = $nodes[$j];
					
						$conditions[] = XMLFileParser::getAttributes($node, array("column", "operator", "table", "refcolumn", "reftable", "value"));
					}
					$new_item["condition"] = $conditions;
				}
			
				$nodes = isset($item["childs"]["sort"]) ? $item["childs"]["sort"] : null;
				$t1 = $nodes ? count($nodes) : 0;
				if ($t1) {
					$sorts = array();
					for ($j = 0; $j < $t1; $j++) {
						$node = $nodes[$j];
					
						$sorts[] = XMLFileParser::getAttributes($node, array("column", "table", "order"));
					}
					$new_item["sort"] = $sorts;
				}
			
				$new_item["limit"] = XMLFileParser::getAttribute($item, "limit");
				$new_item["start"] = XMLFileParser::getAttribute($item, "start");
			
				$new_items[$rel_name] = $new_item;
			}
			else
				launch_exception(new HibernateException(8));
		}
	
		return $new_items;
	}
	
	private function convertPropertiesToAttributes($obj_properties, $obj_table_attributes) {
		$properties_to_attributes = array();
		
		if (is_array($obj_table_attributes)) {
			if (is_array($obj_properties)) {
				$obj_table_attribute_keys = array_keys($obj_table_attributes);
				
				foreach ($obj_properties as $key => $value) {
					$output_name = isset($value["output_name"]) ? $value["output_name"] : null;
					
					if (in_array($output_name, $obj_table_attribute_keys)) {
						$properties_to_attributes[$key] = $output_name;
					}
				}
			}
		
			$pa_flipped = array_flip($properties_to_attributes);
			foreach ($obj_table_attributes as $key => $value) {
				if (!isset($pa_flipped[$key])) {
					$properties_to_attributes[$key] = $key;
				}
			}
		}
		
		return $properties_to_attributes;
	}
}
?>
