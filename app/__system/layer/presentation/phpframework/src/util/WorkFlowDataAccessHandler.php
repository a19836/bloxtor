<?php
include_once get_lib("org.phpframework.util.xml.MyXML");
include_once get_lib("org.phpframework.util.xml.MyXMLArray");
include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include_once get_lib("org.phpframework.object.ObjTypeHandler");
include_once get_lib("org.phpframework.db.DB");
include_once get_lib("org.phpframework.util.HashTagParameter");
include_once $EVC->getUtilPath("WorkFlowDBHandler");
include_once $EVC->getUtilPath("AdminMenuHandler");

class WorkFlowDataAccessHandler {
	private $tasks;
	private $foreign_keys;
	
	public function setTasksFilePath($tasks_file_path) {
		$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
		$WorkFlowTasksFileHandler->init();
		$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
		
		$this->tasks = array("containers" => $tasks["containers"]);
		
		foreach ($tasks["tasks"] as $task_id => $task)
			$this->tasks["tasks"][ $task["label"] ] = $task;
		
		$this->foreign_keys = WorkFlowDBHandler::getTablesForeignKeys($this->tasks["tasks"]);
		//print_r($this->foreign_keys);
	}
	
	public function setTasks($tasks) {
		$this->tasks = $tasks;
		
		$tasks = array();
		foreach ($this->tasks["tasks"] as $task_id => $task) {
			$tasks[ $task["label"] ] = $task;
		}
		$this->tasks["tasks"] = $tasks;
		
		$this->foreign_keys = WorkFlowDBHandler::getTablesForeignKeys($this->tasks["tasks"]);
		//print_r($this->foreign_keys);
	}
	
	public function getTasks() {
		return $this->tasks;
	}
	
	public function getForeignKeys() {
		return $this->foreign_keys;
	}
	
	public function getTasksAsTables() {
		$tasks = $this->tasks && $this->tasks["tasks"] ? $this->tasks["tasks"] : array();
		
		return WorkFlowDBHandler::getTasksAsTables($tasks);
	}
	
	private static function getSQLStatementProps($sql) {
		$props = array();
		
		preg_match_all("/([\"']?)" . HashTagParameter::SQL_HASH_TAG_PARAMETER_PARTIAL_REGEX . "([\"']?)/u", $sql, $out); //'\w' means all words with '_' and '/u' means with accents and ç too. '/u' converts unicode to accents chars.
		$out = $out[0];
		
		$t = count($out);
		for ($i = 0; $i < $t; $i++) {
			$o = $out[$i];
			
			$name = str_replace(array("'", '"', "#"), "", $o);
			$type = strpos($o, "'") !== false || strpos($o, '"') !== false ? "string" : "";
			
			$props[$name] = isset($props[$name]) && empty($props[$name]) ? $props[$name] : $type;
		}
		//echo "props:";print_r($props);die();
		
		return $props;
	}
	
	public static function getXmlHibernateImportsData($obj_path) {
		$xml_content = file_exists($obj_path) ? file_get_contents($obj_path) : "";

		if (!empty($xml_content)) {
			$arr = self::getXmlContentArray($xml_content);
			
			return $arr["sql_mapping"][0]["childs"]["import"];
		}
		
		return false;
	}
	
	public static function getXmlHibernateObjData($obj_path, $obj_id) {
		if ($obj_path) {
			if (is_dir($obj_path) && $obj_id)
				$obj_path .= "/$obj_id.xml";
			
			$xml_content = file_exists($obj_path) ? file_get_contents($obj_path) : "";
			
			if (!empty($xml_content)) {
				$arr = self::getXmlContentArray($xml_content);
				
				$classes = $arr["sql_mapping"][0]["childs"]["class"];
				
				if ($classes) {
					$t = count($classes);
					for ($i = 0; $i < $t; $i++)
						if ($classes[$i]["@"]["name"] == $obj_id) {
							$obj_data = $classes[$i];
							
							$obj_data["childs"]["relationships"] = XMLFileParser::combineMultipleNodesInASingleNode($obj_data["childs"]["relationships"]);
							$obj_data["childs"]["queries"] = XMLFileParser::combineMultipleNodesInASingleNode($obj_data["childs"]["queries"]);
							
							return $obj_data;
						}
				}
			}
		}
		
		return false;
	}
	
	public static function getXmlHibernateObjQueryOrMapData($obj_path, $obj_id, $query_or_map_id, $available_types, $relationship_type = false) {
		$xml_content = file_exists($obj_path) ? file_get_contents($obj_path) : "";
		
		if (!empty($xml_content)) {
			$relationship_type = $relationship_type ? $relationship_type : "queries";
			
			$arr = self::getXmlContentArray($xml_content);
			
			$classes = $arr["sql_mapping"][0]["childs"]["class"];
			
			if ($classes) {
				$t = count($classes);
				for ($i = 0; $i < $t; $i++)
					if ($classes[$i]["@"]["name"] == $obj_id) {
						$obj_data = $classes[$i];
						
						$nodes = XMLFileParser::combineMultipleNodesInASingleNode($obj_data["childs"][$relationship_type]);
						$nodes_types = $nodes[0]["childs"];
						
						$available_types = $available_types ? $available_types : array("insert", "update", "delete", "select", "procedure", "parameter_map", "result_map", "one_to_one", "one_to_many", "many_to_one", "many_to_many");
						
						return self::getDataAccessObjFromData($nodes_types, $query_or_map_id, $available_types);
					}
			}
		}
		
		return false;
	}
	
	public static function getXmlQueryOrMapData($obj_path, $query_or_map_id, $available_types) {
		$xml_content = file_exists($obj_path) ? file_get_contents($obj_path) : "";

		if (!empty($xml_content)) {
			$arr = self::getXmlContentArray($xml_content);
			
			$keys = array_keys($arr);
			$first_key = $keys[0];
			
			$nodes_types = $arr[$first_key][0]["childs"];
			
			$available_types = $available_types ? $available_types : array("insert", "update", "delete", "select", "procedure", "parameter_map", "result_map", "one_to_one", "one_to_many", "many_to_one", "many_to_many");
					
			return self::getDataAccessObjFromData($nodes_types, $query_or_map_id, $available_types);
		}
		
		return false;
	}
	
	private static function getDataAccessObjFromData($nodes_types, $obj_id, $available_types = array()) {
		if (is_array($nodes_types)) {
			foreach ($nodes_types as $type => $items) {
				if ($items && (empty($available_types) || in_array($type, $available_types))) {
					$t = count($items);
					for ($j = 0; $j < $t; $j++) {
						$id = $items[$j]["@"]["id"] ? $items[$j]["@"]["id"] : $items[$j]["@"]["name"];
						
						if ($id == $obj_id) {
							return $items[$j];
						}
					}
				}
			}
		}
		
		return null;
	}
	
	public static function getDAOObjectsLibPath($type) {
		$daos = AdminMenuHandler::getDaoObjs();
		
		return self::getDAOObjectsLibPathAux($daos, $type);
	}
	
	private static function getDAOObjectsLibPathAux($nodes, $type) {
		$daos = array();
		
		if ($nodes) {
			foreach ($nodes as $node_id => $node) {
				if ($node_id != "properties") {
					if ($node["properties"]["item_type"] == $type) {
						$path_parts = pathinfo($node["properties"]["path"]);
						
						$daos[] = "vendor.dao." . str_replace("/", ".", $path_parts["dirname"] . "/" . $path_parts["filename"]);
					}
					else {
						$daos = array_merge($daos, self::getDAOObjectsLibPathAux($node, $type));
					}
				}
			}
		}
		
		return $daos;
	}
	
	public static function getMapDBTypes() {
		return ObjTypeHandler::getDBTypesPaths();
	}
	
	public static function getMapPHPTypes() {
		return ObjTypeHandler::getPHPTypesPaths();
	}

	public static function getNodeValue($node, $attr_name, $attr_type = "value") {
		$value = XMLFileParser::getAttribute($node, $attr_name);
		
		if (!isset($value) && isset($node["childs"]) && isset($node["childs"][$attr_name])) {
			$value = XMLFileParser::getAttribute($node["childs"][$attr_name][0], $attr_type);
		}
		
		//in case of the Hibernate object:
		//	<condition column="object_type"><![CDATA[#object_type#]]></condition>
		if ($attr_name == "value" && $node["value"]) {
			return $node["value"];
		}
		
		return $value;
	}
	
	public static function createIncludesFromObjectData($file_path, $data, $layer_type) {
		$status = false;
		
		if ($file_path && file_exists($file_path)) {
			$xml_content = file_get_contents($file_path);
			$arr = self::getXmlContentArray($xml_content);
			
			$keys = array_keys($arr);
			$first_key = $keys[0];
			
			$imports = $data["queries"][0]["childs"]["import"];
			if ($imports) {
				$arr[$first_key][0]["childs"]["import"] = $imports;
			}
			else {
				unset($arr[$first_key][0]["childs"]["import"]);
			}
			
			$doc_type_xml_tag = $layer_type == "hibernate" ? '<!DOCTYPE hibernate-mapping PUBLIC "-//Hibernate/Hibernate Mapping DTD 3.0//EN" "http://hibernate.sourceforge.net/hibernate-mapping-3.0.dtd">' : '<!DOCTYPE sqlMap PUBLIC "-//iBATIS.com//DTD SQL Map 2.0//EN" "http://www.ibatis.com/dtd/sql-map-2.dtd">';
			
			$new_xml = self::getArrayXml($arr, "\n$doc_type_xml_tag\n");
			
			$status = self::saveNewXMLToFile($file_path, $new_xml);
		}
		
		return $status ? true : false;
	}
	
	public function createHibernateObjectFromObjectData($file_path, $data, $overwrite = false, $hbn_obj_id = false) {
		$status = false;
		
		if ($file_path) {
			$obj_id = $hbn_obj_id ? $hbn_obj_id : $data["class"][0]["@"]["name"];
			$obj_exists = self::getXmlHibernateObjData($file_path, $obj_id);
			
			$obj_id = $obj_exists && !$overwrite ? $obj_id . "_" . rand(0, 1000) : $obj_id;
			
			if (is_dir($file_path))
				$file_path .= "/$obj_id.xml";
			
			if ($obj_exists && $overwrite) {
				$xml_content = file_get_contents($file_path);
				$arr = self::getXmlContentArray($xml_content);
				
				$classes = $arr["sql_mapping"][0]["childs"]["class"];
				$new_classes = array();
				
				if ($classes) {
					$t = count($classes);
					for ($i = 0; $i < $t; $i++) {
						if ($classes[$i]["@"]["name"] == $obj_id) {
							$new_class = $data["class"][0];
							
							if ($new_class) {
								$new_classes[] = $new_class;
							}
						}
						else {
							$new_classes[] = $classes[$i];
						}
					}
				}
				
				$arr["sql_mapping"][0]["childs"]["class"] = $new_classes;
				
				//echo "<pre>";print_r($arr);die();
				$new_xml = self::getArrayXml($arr, "\n" . '<!DOCTYPE hibernate-mapping PUBLIC "-//Hibernate/Hibernate Mapping DTD 3.0//EN" "http://hibernate.sourceforge.net/hibernate-mapping-3.0.dtd">' . "\n");
		
				$status = self::saveNewXMLToFile($file_path, $new_xml);
			}
			else {
				$data = $data ? array("sql_mapping" => array(0 => array("childs" => $data))) : array();
				$MyXMLArray = new MyXMLArray($data);
				$xml = $data ? $MyXMLArray->toXML(array("lower_case_keys" => true, "to_decimal" => true)) : ""; //to_decimal bc of the accents
				
				if (file_exists($file_path)) {
					$content = file_get_contents($file_path);
					
					if (strpos($content, "<sql_mapping>") !== false) {
						$xml = trim(str_replace("<sql_mapping>", "", $xml, $c = 1));
						$content = str_replace("</sql_mapping>", "\n$xml", $content);
					}
					else 
						$content = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE hibernate-mapping PUBLIC "-//Hibernate/Hibernate Mapping DTD 3.0//EN" "http://hibernate.sourceforge.net/hibernate-mapping-3.0.dtd">
' . $xml;
				}
				else {
					$content = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE hibernate-mapping PUBLIC "-//Hibernate/Hibernate Mapping DTD 3.0//EN" "http://hibernate.sourceforge.net/hibernate-mapping-3.0.dtd">
' . $xml;
				}
				
				$status = self::saveNewXMLToFile($file_path, $content);
			}
		}
		
		return $status ? true : false;
	}
	
	public function createHibernateObjectFromDBTaskFlow($table_name, $file_path, $overwrite = false, $hbn_obj_id = false, $with_maps = false) {
		if ($file_path && $table_name) {
			$task = WorkFlowDBHandler::getTableFromTables($this->tasks["tasks"], $table_name);
			
			if ($task) {
				$obj_id = empty($hbn_obj_id) ? self::getVarName($task["alias"] ? $task["alias"] : $table_name) : $hbn_obj_id;
				$xml = $this->getHibernateObjectFromDBTaskFlow($task, $obj_id, $with_maps);
				$data = self::getXmlContentArray($xml);
				
				return $this->createHibernateObjectFromObjectData($file_path, $data, $overwrite, $obj_id);
			}
		}
		return false;
	}
	
	public function createHibernateQueriesFromObjectData($file_path, $hbn_obj_id, $data, $nodes_ids = false, $relationship_type = false) {
		$status = false;
		
		if ($file_path && file_exists($file_path) && $hbn_obj_id) {
			$relationship_type = $relationship_type ? $relationship_type : "queries";
			
			$oarr = $data["queries"][0]["childs"] ? $data["queries"][0]["childs"] : array();
			
			if (!$nodes_ids) {
				$nodes_ids = array();
				foreach ($oarr as $node_type => $nodes) 
					if ($nodes) {
						$t = count($nodes);
						for ($i = 0; $i < $t; $i++) {
							$node_id = $nodes[$i]["@"]["id"] ? $nodes[$i]["@"]["id"] : $nodes[$i]["@"]["name"];
							$nodes_ids[$node_type][$node_id] = $i;
						}
					}
			}
			
			if ($nodes_ids) {
				$xml_content = file_get_contents($file_path);
				$arr = self::getXmlContentArray($xml_content);
				
				$classes = $arr["sql_mapping"][0]["childs"]["class"];
				
				if ($classes) {
					$t = count($classes);
					for ($i = 0; $i < $t; $i++) {
						if ($classes[$i]["@"]["name"] == $hbn_obj_id) {
							$hbn_obj = $arr["sql_mapping"][0]["childs"]["class"][$i];
							$hbn_obj_nodes = $hbn_obj["childs"][$relationship_type][0]["childs"];
					
							if ($hbn_obj_nodes) {
								foreach ($hbn_obj_nodes as $node_type => $nodes) {
									$new_nodes = array();
									
									if ($nodes) {
										$t2 = count($nodes);
										for ($j = 0; $j < $t2; $j++) {
											$node = $nodes[$j];
											$node_id = $node["@"]["id"] ? $node["@"]["id"] : $node["@"]["name"];
											
											if ($node_id && isset($nodes_ids[$node_type][$node_id])) {
												$idx = $nodes_ids[$node_type][$node_id];
												
												$new_node = $oarr[$node_type][$idx];
												if ($new_node)
													$new_nodes[] = $new_node;
												
												unset($nodes_ids[$node_type][$node_id]);
											}
											else
												$new_nodes[] = $node;
										}
									}
									
									$hbn_obj_nodes[$node_type] = $new_nodes;
								}
							}
							
							foreach ($nodes_ids as $node_type => $rs)
								foreach ($rs as $node_id => $idx) {
									$new_node = $oarr[$node_type][$idx];
									if ($new_node)
										$hbn_obj_nodes[$node_type][] = $new_node;
								}
							
							$hbn_obj["childs"][$relationship_type][0]["childs"] = $hbn_obj_nodes;
							$arr["sql_mapping"][0]["childs"]["class"][$i] = $hbn_obj;
							
							break;
						}
					}
				}
				
				$new_xml = self::getArrayXml($arr, "\n" . '<!DOCTYPE hibernate-mapping PUBLIC "-//Hibernate/Hibernate Mapping DTD 3.0//EN" "http://hibernate.sourceforge.net/hibernate-mapping-3.0.dtd">'. "\n");
			
				$status = self::saveNewXMLToFile($file_path, $new_xml);
			}
		}
		
		return $status ? true : false;
	}
	
	public function createTableQueriesFromObjectData($file_path, $data, $overwrite = false, $nodes_ids = false, $import_tag = false) {
		$status = false;
		
		if ($file_path) {
			$oarr = $data["queries"][0]["childs"] ? $data["queries"][0]["childs"] : array();
			
			$first_key = $import_tag ? "import" : "sql_mapping";//This works for the SQL_MAPPING and IMPORT tags. This works for the import files or the ibatis files
			
			if (is_dir($file_path))
				$file_path .= "/queries.xml";
			
			if ($overwrite && file_exists($file_path)) {
				if (!$nodes_ids) {
					$nodes_ids = array();
					foreach ($oarr as $node_type => $nodes) 
						if ($nodes) {
							$t = count($nodes);
							for ($i = 0; $i < $t; $i++) {
								$node_id = $nodes[$i]["@"]["id"] ? $nodes[$i]["@"]["id"] : $nodes[$i]["@"]["name"];
								$nodes_ids[$node_type][$node_id] = $i;
							}
						}
				}
				
				if ($nodes_ids) {
					$xml_content = file_get_contents($file_path);
					$arr = self::getXmlContentArray($xml_content);
					
					if ($arr[$first_key][0]["childs"]) 
						foreach ($arr[$first_key][0]["childs"] as $node_type => $nodes) {
							$new_nodes = array();
							
							if ($nodes) {
								$t = count($nodes);
								for ($i = 0; $i < $t; $i++) {
									$node = $nodes[$i];
									$node_id = $node["@"]["id"] ? $node["@"]["id"] : $node["@"]["name"];
							
									if ($node_id && isset($nodes_ids[$node_type][$node_id])) {
										$idx = $nodes_ids[$node_type][$node_id];
									
										$new_node = $oarr[$node_type][$idx];
										if ($new_node) 
											$new_nodes[] = $new_node;
									
										unset($nodes_ids[$node_type][$node_id]);
									}
									else
										$new_nodes[] = $node;
								}
							}
							
							$arr[$first_key][0]["childs"][$node_type] = $new_nodes;
						}
					
					foreach ($nodes_ids as $node_type => $rs)
						foreach ($rs as $node_id => $idx) {
							$new_node = $oarr[$node_type][$idx];
							if ($new_node)
								$arr[$first_key][0]["childs"][$node_type][] = $new_node;
						}
				
					$new_xml = self::getArrayXml($arr, "\n" . '<!DOCTYPE sqlMap PUBLIC "-//iBATIS.com//DTD SQL Map 2.0//EN" "http://www.ibatis.com/dtd/sql-map-2.dtd">'. "\n");
					
					$status = self::saveNewXMLToFile($file_path, $new_xml);
				}
			}
			else {
				$first_key = strtolower($first_key);
				
				$oarr = $oarr ? array($first_key => array(0 => array("childs" => $oarr))) : array();
				$MyXMLArray = new MyXMLArray($oarr);
				$xml = $oarr ? $MyXMLArray->toXML(array("lower_case_keys" => true, "to_decimal" => true)) : ""; //to_decimal bc of the accents
				
				if (file_exists($file_path)) {
					$xml = trim(str_replace("<$first_key>", "", $xml, $c = 1));
					
					$content = file_get_contents($file_path);
					$content = str_replace("</$first_key>", "\n$xml", $content);
				}
				else
					$content = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE sqlMap PUBLIC "-//iBATIS.com//DTD SQL Map 2.0//EN" "http://www.ibatis.com/dtd/sql-map-2.dtd">
' . $xml;
			
				$status = self::saveNewXMLToFile($file_path, $content);
			}
		}
	
		return $status ? true : false;
	}
	
	public function createTableQueriesFromDBTaskFlow($table_name, $file_path, $overwrite = false, $with_maps = false) {
		if ($file_path) {
			$task = WorkFlowDBHandler::getTableFromTables($this->tasks["tasks"], $table_name);
			
			$xml = $this->getTableQueriesFromDBTaskFlow($task, $with_maps);
			$data = self::getXmlContentArray("<queries>$xml</queries>");
			
			return $this->createTableQueriesFromObjectData($file_path, $data, $overwrite);
		}
		return false;
	}
	
	public function getHibernateObjectArrayFromDBTaskFlow($table_name, $obj_id = false, $with_maps = false) {
		$task = WorkFlowDBHandler::getTableFromTables($this->tasks["tasks"], $table_name);
		
		if ($task) {
			$obj_id = empty($obj_id) ? self::getVarName($table_name) : $obj_id;
			
			$xml = $this->getHibernateObjectFromDBTaskFlow($task, $obj_id, $with_maps);
			$arr = self::getXmlContentArray($xml);
			
			return $arr;
		}
		
		return null;
	}
	
	public function getQueryObjectsArrayFromDBTaskFlow($table_name, $with_maps = false) {
		$task = WorkFlowDBHandler::getTableFromTables($this->tasks["tasks"], $table_name);
		
		if ($task) {
			$xml = $this->getTableQueriesFromDBTaskFlow($task, $with_maps, "\t");
			$arr = self::getXmlContentArray("<queries>$xml</queries>");
			
			return $arr;
		}
		
		return null;
	}
	
	private function getHibernateObjectFromDBTaskFlow($task, $obj_id, $with_maps) {
		$xml = '';
		
		if (!empty($task)) {
			if (strtolower($task["tag"] == "table")) {
				$properties = $task["properties"];
				
				$table_name = $task["label"];
				
				$table_attr_primary_keys = $properties["table_attr_primary_keys"];
				$table_attr_names = $properties["table_attr_names"];
				$table_attr_types = $properties["table_attr_types"];
				$table_attr_lengths = $properties["table_attr_lengths"];
				$table_attr_nulls = $properties["table_attr_nulls"];
				$table_attr_unsigneds = $properties["table_attr_unsigneds"];
				$table_attr_uniques = $properties["table_attr_uniques"];
				$table_attr_auto_increments = $properties["table_attr_auto_increments"];
				$table_attr_has_defaults = $properties["table_attr_has_defaults"];
				$table_attr_defaults = $properties["table_attr_defaults"];
				$table_attr_extras = $properties["table_attr_extras"];
				$table_attr_charsets = $properties["table_attr_charsets"];
				$table_attr_comments = $properties["table_attr_comments"];
				
				$foreign_keys = WorkFlowDBHandler::getTableFromTables($this->foreign_keys, $table_name);
				
				$xml .= '
	<class name="' . $obj_id . '" table="' . $table_name . '">';
				
				if ($table_attr_names) {
					$t = count($table_attr_names);
					for ($i = 0; $i < $t; $i++) {
						$attr_name = $table_attr_names[$i];
						$attr_type = strtolower($table_attr_types[$i]);
						
						$is_pk = strtolower($table_attr_primary_keys[$i]) == "true" || $table_attr_primary_keys[$i] == "1";
						
						if ($is_pk) {
							$is_auto_increment = strtolower($table_attr_auto_increments[$i]) == "true" || $table_attr_auto_increments[$i] == "1";
							$is_auto_increment = self::isAutoIncrementedAttribute(array("type" => $attr_type, "extra" => $table_attr_extras[$i], "auto_increment" => $is_auto_increment));
							
							if ($is_auto_increment)
								$xml .= '
			<id column="' . $attr_name . '" />
			';
							else
								$xml .= '
			<id column="' . $attr_name . '">
				<generator type="increment" />
			</id>
			';
						}
					}
				}
				
				if ($with_maps) {
					$xml .= self::getTableParameterMap($table_attr_names, $table_attr_types, self::getVarName($table_name) . "ParameterMap", "\t");
					$xml .= self::getTableResultMap($table_attr_names, $table_attr_types, self::getVarName($table_name) . "ResultMap", "\t");
				}
				
				if (!empty($foreign_keys)) {
					$xml .= '
		<relationships>';
					
					$types = array_flip(WorkFlowDBHandler::getTablesConnectionTypes());
					
					if ($with_maps) {
						$foreign_table_names = array();
						$t = count($foreign_keys);
						for ($j = 0; $j < $t; $j++) {
							$relationship = $foreign_keys[$j];
						
							$foreign_table_names[] = $relationship["child_table"] == $table_name ? $relationship["parent_table"] : $relationship["child_table"];
						}
					
						$foreign_table_names = array_unique($foreign_table_names);
						$t = count($foreign_table_names);
						for ($j = 0; $j < $t; $j++) {
							$foreign_table_name = $foreign_table_names[$j];
						
							$result_map_id = self::getVarName($foreign_table_name) . "ResultMap";
							
							$foreign_task = $this->tasks["tasks"][$foreign_table_name];
							
							$xml .= self::getTableResultMap($foreign_task["properties"]["table_attr_names"], $foreign_task["properties"]["table_attr_types"], $result_map_id, "\t\t");
						}
					}
								
					$t = count($foreign_keys);	
					for ($j = 0; $j < $t; $j++) {
						$relationship = $foreign_keys[$j];
						
						$type = $relationship["type"];
						$relationship_node_name = $types[$type];
						
						if ($relationship_node_name) {
							$relationship_node_name = str_replace(" ", "_", strtolower($relationship_node_name));
							
							$foreign_table_name = $relationship["child_table"] == $table_name ? $relationship["parent_table"] : $relationship["child_table"];
							
							$result_map_id = $with_maps ? self::getVarName($foreign_table_name) . "ResultMap" : null;
							
							$table_alias = $task["alias"];
							$foreign_table_alias = $this->tasks["tasks"][$foreign_table_name]["alias"];
							$name = self::getForeignTableQueryName($table_alias ? $table_alias : $table_name, $foreign_table_alias ? $foreign_table_alias : $foreign_table_name, $type);
							$name = $name ? substr($name, strlen("get_")) : $name;//remove get_
							
							$xml_pks = '';
							
							if ($relationship["keys"]) {
								$t2 = count($relationship["keys"]);
								for ($w = 0; $w < $t2; $w++) {
									$r = $relationship["keys"][$w];
									$ftable = $foreign_table_name;
									
									if ($relationship["child_table"] == $table_name) {
										$pcolumn = $r["child"];
										$fcolumn = $r["parent"];
									}
									else {
										$pcolumn = $r["parent"];
										$fcolumn = $r["child"];
									}
									
									$xml_pks .= '
					<key pcolumn="' . $pcolumn . '" fcolumn="' . $fcolumn . '" ftable="' . $ftable . '" />';
								}
							}
								
							$attrs = WorkFlowDBHandler::getTableAttributes($this->tasks["tasks"], $foreign_table_name);
							
							$xml_attrs = '';
							
							if ($attrs) {
								$t2 = count($attrs);
								for ($w = 0; $w < $t2; $w++)
									$xml_attrs .= '
					<attribute column="' . $attrs[$w] . '" table="' . $foreign_table_name . '" />';
							}
							
							$result_map_xml = $result_map_id ? " result_map=\"" . $result_map_id . "\"" : "";
							
							$xml .= "
			<$relationship_node_name name=\"$name\"$result_map_xml>$xml_pks\n$xml_attrs
			</$relationship_node_name>\n";
						} 
					}
					
					$xml .= '
		</relationships>
		';
				}
				
				$xml .= "
		<queries>
			<!-- You can insert here new sql queries... -->
		" . /*$this->getTableQueriesFromDBTaskFlow($task, $with_maps, "\t\t") .*/ "
		</queries>";
				
				$xml .= '
	</class>';
			}
		}
		
		return $xml;
	}
	
	private function getTableQueriesFromDBTaskFlow($task, $with_maps, $prefix_tab = false) {
		$xml = '';
		
		if (!empty($task)) {
			if (strtolower($task["tag"] == "table")) {
				$properties = $task["properties"];
				
				$table_name = $task["label"];
				$table_alias = $task["alias"];
				$query_id = str_replace(array(" ", "."), "_", strtolower($table_alias ? $table_alias : $table_name)); //"." bc the table_name can have the schema
				
				$table_attr_names = $properties["table_attr_names"];
				if ($table_attr_names) {
					$numeric_types = ObjTypeHandler::getDBNumericTypes();
					$table_attr_primary_keys = $properties["table_attr_primary_keys"];
					$table_attr_types = $properties["table_attr_types"];
					$table_attr_auto_increments = $properties["table_attr_auto_increments"];
					$table_attr_extras = $properties["table_attr_extras"];
					
					//check if table contains any pk
					$no_pks = true;
					$t = count($table_attr_primary_keys);
					
					for ($i = 0; $i < $t; $i++) {
						$is_pk = $table_attr_primary_keys[$i];
						$is_pk = $is_pk && ($is_pk == "1" || strtolower($is_pk) == "true");
						
						if ($is_pk) {
							$no_pks = false;
							break;
						}
					}
					
					//prepare sqls
					$insert_attributes = $insert_with_ai_pk_attributes = $update_attributes = $update_all_attributes = $update_pks_attributes = $update_pks_conditions = $update_conditions = $conditions = $columns = array();
					$t = count($table_attr_names);
					
					for ($i = 0; $i < $t; $i++) {
						$attr_name = $table_attr_names[$i];
						$is_pk = $table_attr_primary_keys[$i];
						$is_pk = $is_pk && ($is_pk == "1" || strtolower($is_pk) == "true");
						
						$is_auto_increment = $table_attr_auto_increments[$i];
						$is_auto_increment = $is_auto_increment == "1" || strtolower($is_auto_increment) == "true";
						
						$columns[$attr_name] = $attr_name;
						
						$insert_with_ai_pk_attributes[$attr_name] = "#$attr_name#"; //includes all attributes, including the auto_increment keys. This gives the change to the user to hard code the primary keys.
						
						if (!$is_pk || !self::isAutoIncrementedAttribute(array("type" => $table_attr_types[$i], "extra" => $table_attr_extras[$i], "auto_increment" => $is_auto_increment))) //This will not include the auto_increment keys, bc the DB will take care then automatically.
							$insert_attributes[$attr_name] = "#$attr_name#";
						
						if ($is_pk) {
							$conditions[$attr_name] = "#$attr_name#";
							$update_conditions[$attr_name] = "#$attr_name#";
							$update_pks_attributes[$attr_name] = "#new_$attr_name#";
							$update_pks_conditions[$attr_name] = "#old_$attr_name#";
						}
						else if (!ObjTypeHandler::isDBAttributeNameACreatedDate($attr_name) && !ObjTypeHandler::isDBAttributeNameACreatedUserId($attr_name)) { //if attr_name == created_date, ignore it, because we don't want to update this attr. The attr created_date is only changed in the insert query.
							$update_attributes[$attr_name] = "#$attr_name#";
							$update_all_attributes[$attr_name] = "#$attr_name#";
							
							if ($no_pks && !ObjTypeHandler::isDBAttributeNameAModifiedDate($attr_name) && !ObjTypeHandler::isDBAttributeNameAModifiedUserId($attr_name)) {
								$conditions[$attr_name] = "#$attr_name#";
								$update_conditions[$attr_name] = "#old_$attr_name#";
								$update_attributes[$attr_name] = "#new_$attr_name#";
								$update_pks_attributes[$attr_name] = "#new_$attr_name#";
								$update_pks_conditions[$attr_name] = "#old_$attr_name#";
							}
						}
					}
					
					$insert_sql = DB::buildDefaultTableInsertSQL($table_name, $insert_attributes);
					$insert_with_ai_pk_sql = DB::buildDefaultTableInsertSQL($table_name, $insert_with_ai_pk_attributes);
					$update_sql = DB::buildDefaultTableUpdateSQL($table_name, $update_attributes, $update_conditions);
					$update_all_sql = DB::buildDefaultTableUpdateSQL($table_name, $update_all_attributes, null, array("all" => true));
					$update_pks_sql = DB::buildDefaultTableUpdateSQL($table_name, $update_pks_attributes, $update_pks_conditions);
					$delete_sql = DB::buildDefaultTableDeleteSQL($table_name, $conditions);
					$delete_all_sql = DB::buildDefaultTableDeleteSQL($table_name, null, array("all" => true));
					$get_sql = DB::buildDefaultTableFindSQL($table_name, $columns, $conditions);
					$get_all_sql = DB::buildDefaultTableFindSQL($table_name, $columns);
					$count_sql = DB::buildDefaultTableCountSQL($table_name);
					
					//remove single quotes in sqls for numeric attributes, this is, replace "'#attr_name#'" by "#attr_name#"
					for ($i = 0; $i < $t; $i++) {
						$attr_name = $table_attr_names[$i];
						$attr_type = $table_attr_types[$i];
						
						if (in_array($attr_type, $numeric_types)) {
							$insert_sql = str_replace("'#$attr_name#'", "#$attr_name#", $insert_sql);
							$insert_with_ai_pk_sql = str_replace("'#$attr_name#'", "#$attr_name#", $insert_with_ai_pk_sql);
							$update_sql = str_replace("'#$attr_name#'", "#$attr_name#", $update_sql);
							$update_all_sql = str_replace("'#$attr_name#'", "#$attr_name#", $update_all_sql);
							$delete_sql = str_replace("'#$attr_name#'", "#$attr_name#", $delete_sql);
							$get_sql = str_replace("'#$attr_name#'", "#$attr_name#", $get_sql);
							
							$is_pk = $table_attr_primary_keys[$i];
							$is_pk = $is_pk == "1" || strtolower($is_pk) == "true";
							
							if ($is_pk) {
								$update_pks_sql = str_replace("'#new_$attr_name#'", "#new_$attr_name#", $update_pks_sql);
								$update_pks_sql = str_replace("'#old_$attr_name#'", "#old_$attr_name#", $update_pks_sql);
							}
							else if ($no_pks) {
								$update_sql = str_replace("'#new_$attr_name#'", "#new_$attr_name#", $update_sql);
								$update_sql = str_replace("'#old_$attr_name#'", "#old_$attr_name#", $update_sql);
								$update_pks_sql = str_replace("'#new_$attr_name#'", "#new_$attr_name#", $update_pks_sql);
								$update_pks_sql = str_replace("'#old_$attr_name#'", "#old_$attr_name#", $update_pks_sql);
							}
						}
					}
					
					//prepare maps
					if ($with_maps) {
						$parameter_map_id = self::getVarName($table_name) . "ParameterMap";
						$parameter_pks_map_id = self::getVarName($table_name) . "PksParameterMap";
						$result_map_id = self::getVarName($table_name) . "ResultMap";
					
						$table_pks_attr_names = $table_pks_attr_types = array();
						
						for ($i = 0; $i < $t; $i++) {
							$is_pk = $table_attr_primary_keys[$i];
							$is_pk = $is_pk == "1" || strtolower($is_pk) == "true";
							
							if ($is_pk) {
								$table_pks_attr_names[] = $table_attr_names[$i];
								$table_pks_attr_types[] = $table_attr_types[$i];
							}
						}
						
						$xml .= self::getTableParameterMap($table_attr_names, $table_attr_types, $parameter_map_id, $prefix_tab);
						$xml .= self::getTableParameterMap($table_pks_attr_names, $table_pks_attr_types, $parameter_pks_map_id, $prefix_tab);
						$xml .= self::getTableResultMap($table_attr_names, $table_attr_types, $result_map_id, $prefix_tab);
					}
					
					$parameter_map_xml = $parameter_map_id ? " parameter_map=\"" . $parameter_map_id . "\"" : "";
					$parameter_pks_map_xml = $parameter_pks_map_id ? " parameter_map=\"" . $parameter_pks_map_id . "\"" : "";
					$result_map_xml = $result_map_id ? " result_map=\"" . $result_map_id . "\"" : "";
					
					//prepare xml
					$xml .= "
	$prefix_tab<insert id=\"insert_" . $query_id . "\">
	$prefix_tab	$insert_sql
	$prefix_tab</insert>
	$prefix_tab
	$prefix_tab<insert id=\"insert_" . $query_id . "_with_ai_pk\" hard_coded_ai_pk=\"1\">
	$prefix_tab	$insert_with_ai_pk_sql
	$prefix_tab</insert>
	$prefix_tab";
	
					if ($update_sql) {
						$xml .= "
	$prefix_tab<update id=\"update_" . $query_id . "\">
	$prefix_tab	$update_sql
	$prefix_tab</update>
	
	$prefix_tab<update id=\"update_" . $query_id . "_primary_keys\">
	$prefix_tab	$update_pks_sql
	$prefix_tab</update>
	$prefix_tab";
					}
			
					$xml .= "
	$prefix_tab<update id=\"update_all_" . $query_id . "_items\">
	$prefix_tab	$update_all_sql WHERE 1=1 #searching_condition#
	$prefix_tab</update>
	$prefix_tab";
					
					if ($delete_sql) {
						$xml .= "
	$prefix_tab<delete id=\"delete_" . $query_id . "\">
	$prefix_tab	$delete_sql
	$prefix_tab</delete>
	$prefix_tab";
					}
					
					$xml .= "
	$prefix_tab<delete id=\"delete_all_" . $query_id . "_items\">
	$prefix_tab	$delete_all_sql WHERE 1=1 #searching_condition#
	$prefix_tab</delete>
	$prefix_tab";
	
					if ($get_sql) {
						$xml .= "
	$prefix_tab<select id=\"get_" . $query_id . "\"$parameter_pks_map_xml$result_map_xml>
	$prefix_tab	$get_sql
	$prefix_tab</select>
	$prefix_tab";
					}
					
					$xml .= "
	$prefix_tab<select id=\"get_" . $query_id . "_items\"$parameter_map_xml$result_map_xml>
	$prefix_tab	$get_all_sql WHERE 1=1 #searching_condition#
	$prefix_tab</select>
	$prefix_tab";
					
					$xml .= "
	$prefix_tab<select id=\"count_" . $query_id . "_items\">
	$prefix_tab	$count_sql WHERE 1=1 #searching_condition#
	$prefix_tab</select>
	$prefix_tab";
					
					//PREPARING FOREIGN QUERIES
					$xml .= $this->getTableForeignQueriesFromDBTaskFlow($task, $with_maps, $prefix_tab);
				}
			}
		}
		
		return $xml;
	}
	
	private function isForeignAtribute($table_name, $attr_name) {
		$foreign_keys = WorkFlowDBHandler::getTableFromTables($this->foreign_keys, $table_name);
		
		if ($foreign_keys) {
			$t = count($foreign_keys);
			for ($i = 0; $i < $t; $i++) {
				$fk = $foreign_keys[$i];
				
				if ($fk["keys"]) {
					$key = $fk["child_table"] == $table_name ? "child" : "parent";
					
					$t2 = count($fk["keys"]);
					for ($j = 0; $j < $t2; $j++)
						if ($fk["keys"][$j][$key] == $attr_name)
							return true;
				}
			}
		}
		
		return false;
	}
	
	private function getTableForeignQueriesFromDBTaskFlow($task, $with_maps = false, $prefix_tab = false) {
		$xml = "";
		
		if (!empty($task)) {
			if (strtolower($task["tag"] == "table")) {
				$properties = $task["properties"];
				
				$table_name = $task["label"];
				$table_attr_names = $properties["table_attr_names"];
				
				if ($table_attr_names) {
					$numeric_types = ObjTypeHandler::getDBNumericTypes();
					$table_attr_primary_keys = $properties["table_attr_primary_keys"];
					$table_attr_types = $properties["table_attr_types"];
					$foreign_keys = WorkFlowDBHandler::getTableFromTables($this->foreign_keys, $table_name);
					
					$parent_conditions = array();
					$numeric_parent_conditions_attrs_name = array();
					$t = count($table_attr_names);
					for ($j = 0; $j < $t; $j++) {
						$attr_name = $table_attr_names[$j];
						$attr_type = $table_attr_types[$j];
						$is_pk = $table_attr_primary_keys[$j];

						if ($is_pk == "1" || strtolower($is_pk) == "true") {
							$parent_conditions[$attr_name] = "#$attr_name#";
							
							if (in_array($attr_type, $numeric_types))
								$numeric_parent_conditions_attrs_name[] = $attr_name;
						}
					}
					
					if (count($parent_conditions) && !empty($foreign_keys)) {
						//PREPARING FOREIGN PARAMETER/RESULT MAPS
						if ($with_maps) {
							$foreign_table_names = array();
							$t = count($foreign_keys);
							for ($j = 0; $j < $t; $j++) {
								$relationship = $foreign_keys[$j];
					
								$foreign_table_names[] = $relationship["child_table"] == $table_name ? $relationship["parent_table"] : $relationship["child_table"];
							}
				
							$foreign_table_names = array_unique($foreign_table_names);
							$t = count($foreign_table_names);
							for ($j = 0; $j < $t; $j++) {
								$foreign_table_name = $foreign_table_names[$j];
							
								$parameter_map_id = self::getVarName($foreign_table_name) . "ParameterMap";
								$result_map_id = self::getVarName($foreign_table_name) . "ResultMap";
								
								$foreign_task = $this->tasks["tasks"][$foreign_table_name];
								
								$xml .= self::getTableParameterMap($foreign_task["properties"]["table_attr_names"], $foreign_task["properties"]["table_attr_types"], $parameter_map_id, $prefix_tab);
								$xml .= self::getTableResultMap($foreign_task["properties"]["table_attr_names"], $foreign_task["properties"]["table_attr_types"], $result_map_id, $prefix_tab);
							}
						}
						
						//PREPARING FOREIGN SQL
						$t = count($foreign_keys);
						for ($j = 0; $j < $t; $j++) {
							$relationship = $foreign_keys[$j];
							
							$type = $relationship["type"];
							$foreign_table_name = $relationship["child_table"] == $table_name ? $relationship["parent_table"] : $relationship["child_table"];
							
							$parameter_map_id = $with_maps ? self::getVarName($foreign_table_name) . "ParameterMap" : null;
							$result_map_id = $with_maps ? self::getVarName($foreign_table_name) . "ResultMap" : null;
							
							$table_alias = $task["alias"];
							$foreign_table_alias = $this->tasks["tasks"][$foreign_table_name]["alias"];
							$name = self::getForeignTableQueryName($table_alias ? $table_alias : $table_name, $foreign_table_alias ? $foreign_table_alias : $foreign_table_name, $type);
							
							$attrs = WorkFlowDBHandler::getTableAttributes($this->tasks["tasks"], $foreign_table_name);
							
							if ($attrs) {
								//prepare sqls
								$attrs = is_array($attrs) ? $attrs : array($attrs); //if only one attribute, then $attrs is the attr_name, so we need to convert it to an array
								$attributes = $keys = array();
								
								$t2 = count($attrs);
								for ($w = 0; $w < $t2; $w++) {
									$attributes[] = array(
										"table" => $foreign_table_name,
										"column" => $attrs[$w],
									);
								}
								
								if ($relationship["keys"]) {
									$t2 = count($relationship["keys"]);
									
									for ($w = 0; $w < $t2; $w++) {
										$r = $relationship["keys"][$w];
									
										if ($relationship["child_table"] == $table_name) {
											$pcolumn = $r["child"];
											$fcolumn = $r["parent"];
										}
										else {
											$pcolumn = $r["parent"];
											$fcolumn = $r["child"];
										}
										
										$keys[] = array(
											"ptable" => $table_name,
											"pcolumn" => $pcolumn,
											"ftable" => $foreign_table_name,
											"fcolumn" => $fcolumn,
										);
									}
								}
								
								$get_all_relationship_sql = DB::buildDefaultTableFindRelationshipSQL($table_name, array(
									"keys" => $keys,
									"attributes" => $attributes,
								), $parent_conditions);
								$count_relationship_sql = DB::buildDefaultTableCountRelationshipSQL($table_name, array(
									"keys" => $keys,
								), $parent_conditions);
								
								//remove single quotes in sqls for numeric attributes, this is, replace "'#attr_name#'" by "#attr_name#"
								foreach ($numeric_parent_conditions_attrs_name as $attr_name) {
									$get_all_relationship_sql = str_replace("'#$attr_name#'", "#$attr_name#", $get_all_relationship_sql);
									$count_relationship_sql = str_replace("'#$attr_name#'", "#$attr_name#", $count_relationship_sql);
								}
								
								//prepare xml
								$parameter_map_xml = $parameter_map_id ? " parameter_map=\"" . $parameter_map_id . "\"" : "";
								$result_map_xml = $result_map_id ? " result_map=\"" . $result_map_id . "\"" : "";
								
								$xml .= "
	$prefix_tab<select id=\"$name\"$parameter_map_xml$result_map_xml>
	$prefix_tab	$get_all_relationship_sql #searching_condition#
	$prefix_tab</select>
	$prefix_tab
	$prefix_tab<select id=\"" . self::getForeignTableQueryCountName($table_alias ? $table_alias : $table_name, $foreign_table_alias ? $foreign_table_alias : $foreign_table_name, $type) . "\"$parameter_map_xml>
	$prefix_tab	$count_relationship_sql #searching_condition#
	$prefix_tab</select>
	$prefix_tab";
							}
						}
					}
				}
			}
		}
		
		return $xml;
	}
	
	public static function getTableParameterMap($table_attr_names, $table_attr_types, $map_id = false, $prefix_tab = false) {
		if ($table_attr_names) {
			$parameter_map = '
	' . $prefix_tab . '<!--parameter_class' . ($map_id ? ' id="' . $map_id . '"' : '') . '>org.phpframework.object.php.HashMap</parameter_class-->
	' . $prefix_tab . '<parameter_map' . ($map_id ? ' id="' . $map_id . '"' : '') . '>';
		
			$t = count($table_attr_names);
			for ($i = 0; $i < $t; $i++) {
				$attr_name = $table_attr_names[$i];
				$attr_type = strtolower($table_attr_types[$i]);
			
				$parameter_map .= '
		' . $prefix_tab . '<parameter input_name="' . $attr_name . '" output_name="' . $attr_name . '" input_type="org.phpframework.object.php.Primitive(' . ObjTypeHandler::convertDBToPHPType($attr_type) . ')" output_type="org.phpframework.object.db.DBPrimitive(' . $attr_type . ')" mandatory="0" />'; 
				//input_name=self::getVarName($attr_name) is deprecated, otherwise the automatic interfaces creation wil not work with parameter and result maps. The correct is: input_name=$attr_name, this is: country_id instead of CountryId!
			}
		
			$parameter_map .= '
	' . $prefix_tab . '</parameter_map>
			';
			
			return $parameter_map;
		}
		return "";
	}
	
	public static function getTableResultMap($table_attr_names, $table_attr_types, $map_id = false, $prefix_tab = false) {
		if ($table_attr_names) {
			$result_map = '
	' . $prefix_tab . '<!--result_class' . ($map_id ? ' id="' . $map_id . '"' : '') . '>org.phpframework.object.php.HashMap</result_class-->
	' . $prefix_tab . '<result_map' . ($map_id ? ' id="' . $map_id . '"' : '') . '>';
		
			$t = count($table_attr_names);
			for ($i = 0; $i < $t; $i++) {
				$attr_name = $table_attr_names[$i];
				$attr_type = strtolower($table_attr_types[$i]);
			
				$result_map .= '
		' . $prefix_tab . '<result output_name="' . $attr_name . '" input_name="' . $attr_name . '" output_type="org.phpframework.object.php.Primitive(' . ObjTypeHandler::convertDBToPHPType($attr_type) . ')" input_type="org.phpframework.object.db.DBPrimitive(' . $attr_type . ')" mandatory="0" />';
				//output_name=self::getVarName($attr_name) is deprecated, otherwise the automatic interfaces creation wil not work with parameter and result maps. The correct is: output_name=$attr_name, this is: country_id instead of CountryId!
			}
		
			$result_map .= '
	' . $prefix_tab . '</result_map>
			';
		
			return $result_map;
		}
		return "";
	}
	
	public static function getForeignTableQueryName($table_name, $foreign_table_name, $type) {
		$ltn = str_replace(".", "_", strtolower($table_name)); //bc table_name can have the schema
		$lftn = str_replace(".", "_", strtolower($foreign_table_name)); //bc foreign_table_name can have the schema
		
		if ($type == "1-*")
			$name = "get_" . $ltn . "_" . $lftn . "_childs";
		else if ($type == "*-1")
			$name = "get_" . $ltn . "_" . $lftn . "_parent";
		else if ($type == "1-1")
			$name = "get_" . $ltn . "_" . $lftn . "_brother";
		else if ($type == "*-*")
			$name = "get_" . $ltn . "_" . $lftn . "_relatives";
		
		return $name;
	}
	
	public static function getForeignTableQueryCountName($table_name, $foreign_table_name, $type) {
		$name = self::getForeignTableQueryName($table_name, $foreign_table_name, $type);
		return $name ? "count_" . substr($name, 4) : "";//remove the get_ and replace it with count_
	}
	
	private static function getVarName($var_name) {
		return str_replace(" ", "", ucwords(strtolower(trim(str_replace(array("_", "-", "."), " ", $var_name))))); //"." bc the table_name can have the schema
	}
	
	private static function getXmlContentArray($xml_content) {
		if ($xml_content) {
			$xml_content = str_replace("&", "&amp;", $xml_content);
			$xml_content = str_replace("<?php", "&lt;?php", $xml_content);
			$xml_content = str_replace('&lt;?xml', '<?xml', $xml_content);

			$MyXML = new MyXML($xml_content);
			$arr = $MyXML->toArray(array("lower_case_keys" => true, "from_decimal" => true));
			
			return $arr;
		}
	}
	
	private static function getArrayXml($arr, $xml_prefix = "") {
		if ($arr) {
			$MyXMLArray = new MyXMLArray($arr);
			$new_xml = $MyXMLArray->toXML(array("lower_case_keys" => true, "to_decimal" => true)); //to_decimal bc of the accents
		
			$new_xml = str_replace("&lt;?php", "<?php", $new_xml);
			$new_xml = str_replace("&amp;", "&", $new_xml);
			$new_xml = '<?xml version="1.0" encoding="UTF-8"?>' . $xml_prefix . $new_xml;
		
			return $new_xml;
		}
	}
	
	//Only save xml if is valid
	private static function saveNewXMLToFile($file_path, $xml) {
		return MyXML::isXMLContentValid($xml) && file_put_contents($file_path, $xml) !== false;
	}
	
	/* CONVERT TO BUSINESS LOGIC FUNCTIONS */
	
	public static function getClassName($name) {
		return str_replace(" ", "", ucwords(strtolower( (str_replace(array("_", "-", "."), " ", $name) )) )); //"." bc it may contain the table's schema
	}
	
	public static function getHbnObjParameters($data_access_obj, $db_broker, $db_driver, $tasks_file_path, $obj_data, &$tables_props = null) {
		$parameters = array();
		
		if ($obj_data["childs"]["parameter_map"][0]["parameter"]) {
			$t = count($obj_data["childs"]["parameter_map"][0]["parameter"]);
			
			for ($i = 0; $i < $t; $i++) {
				$p = $obj_data["childs"]["parameter_map"][0]["parameter"][$i];
				
				if ($p["output_name"] && $p["input_name"])
					$parameters[ $p["output_name"] ] = array(
						"name" => $p["input_name"], 
						"type" => $p["input_type"]
					);
			}
		}
		
		$table_name = trim($obj_data["@"]["table"]);
		if ($table_name) {
			self::prepareTableProps($data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, array($table_name));
			$table_name = strpos($table_name, " ") !== false ? strstr($table_name, " ", true) : $table_name;
			$tn = strtolower($table_name);
			$attrs = $tables_props[$tn];
			
			foreach ($attrs as $attr_name => $attr) {
				$parameters[$attr_name] = isset($parameters[$attr_name]) ? array_merge($attr, $parameters[$attr_name]) : $attr;
				
				if ($parameters[$attr_name]["type"])
					$parameters[$attr_name]["type"] = "org.phpframework.object.db.DBPrimitive(" . $parameters[$attr_name]["type"] . ")";
			}
		}
		
		$parameter_class = XMLFileParser::getAttribute($obj_data, "parameter_class");
		self::prepareParametersFromClass($parameter_class, $parameters);
	
		return $parameters;
	}
	
	public static function prepareParametersFromClass($parameter_class, &$parameters) {
		if ($parameter_class) {
			$file_path = get_lib($parameter_class);
			
			if (file_exists($file_path)) {
				include_once $file_path;
		
				$class = explode(".", $parameter_class);
				$class = $class[ count($class) - 1 ];
		
				eval ("\$obj = new $class();");
				if ($obj && method_exists($obj, "getData")) {
					$data = $obj->getData();
					$keys = is_array($data) ? array_keys($data) : array();
			
					$params = array();
					
					$t = count($keys);
					for ($i = 0; $i < $t; $i++) {
						$k = $keys[$i];
					
						$params[$k] = array(
							"name" => $k,
						);
						
						if ($parameters[$k])
							$params[$k] = array_merge($parameters[$k], $params[$k]);
					}
					
					$parameters = $params;
				}
			}
		}
	}

	public static function addPrimaryKeysToParameters($hbn_obj_parameters, &$parameters) {
		foreach ($hbn_obj_parameters as $name => $param) {
			if ($param["primary_key"] && !isset($parameters[$name])) {
				$parameters[$name] = $param;
			}
		}
		//echo "<pre>";print_r($parameters);echo "</pre>";die();
	}

	public static function removePrimaryKeysFromParameters($hbn_obj_parameters, &$parameters) {
		foreach ($hbn_obj_parameters as $name => $param) {
			if ($param["primary_key"] && isset($parameters[$name])) {
				unset($parameters[$name]);
			}
		}
		//echo "<pre>";print_r($parameters);echo "</pre>";die();
	}

	public static function getPrimaryKeysFromParameters($hbn_obj_parameters, $parameters) {
		$pks = array();
		foreach ($hbn_obj_parameters as $name => $param) {
			if ($param["primary_key"] && isset($parameters[$name])) {
				$pks[$name] = $parameters[$name];
			}
		}
		//echo "<pre>";print_r($pks);echo "</pre>";die();
		
		return $pks;
	}

	public static function prepareRelationshipParameters(&$rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, &$tables_props, $hbn_obj_data, &$parameters) {
		$parameters = array();
		$tables = array();
		$table_name = $hbn_obj_data["@"]["table"];
		
		if ($table_name)
			$tables[$table_name] = true;
		
		self::prepareSQLColumnProps($rel_data["attribute"], $tables, $parameters, "table", "column");
		self::prepareSQLColumnProps($rel_data["key"], $tables, $parameters, "ptable", "pcolumn", "value");
		self::prepareSQLColumnProps($rel_data["key"], $tables, $parameters, "ftable", "fcolumn", "value");
		self::prepareSQLColumnProps($rel_data["condition"], $tables, $parameters, "table", "column", "value");
		self::prepareSQLColumnProps($rel_data["group_by"], $tables, $parameters, "table", "column");
		self::prepareSQLColumnProps($rel_data["sort"], $tables, $parameters, "table", "column");
	
		//PREPARING PARAMETER MAP/CLASS
		$rel_data["@"]["parameter_map"] = $hbn_obj_data["childs"]["parameter_map"][0]["attrib"]["id"];
		
		self::prepareParameters($rel_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $tables, $parameters, $table_name);
		//echo "<pre>";print_r($parameters);die();
	
		$limit = $rel_data["limit"];
		if (strpos($limit, "#") !== false) {
			$name = str_replace("#", "", $limit);
			$parameters[$name] = array(
				"type" => "org.phpframework.object.php.Primitive(int)", 
				"mandatory" => false
			);
		}
	
		$start = $rel_data["start"];
		if (strpos($start, "#") !== false) {
			$name = str_replace("#", "", $start);
			$parameters[$name] = array(
				"type" => "org.phpframework.object.php.Primitive(int)", 
				"mandatory" => false
			);
		}
		
		$rel_data["@"]["parameter_class"] = XMLFileParser::getAttribute($hbn_obj_data, "parameter_class");
		self::prepareParametersFromClass($rel_data["@"]["parameter_class"], $parameters);
	}
	
	public static function getSQLStatementTable($query_data, $data_access_obj, $db_broker, $db_driver) {
		$sql = $query_data["value"];
		if ($sql) {
			$data = $data_access_obj ? $data_access_obj->getBroker($db_broker)->getFunction("convertSQLToObject", $sql, array("db_driver" => $db_driver)) : DB::convertDefaultSQLToObject($sql);
			$sql_type = $data["type"];
			
			if ($sql_type == "insert" || $sql_type == "update" || $sql_type == "delete")
				return strpos($data["table"], "#") === false ? $data["table"] : null;
			else if ($sql_type == "select")
				return $data["attributes"][0]["table"] ? $data["attributes"][0]["table"] : $data["table"];
		}
	}

	public static function prepareSQLStatementParameters($query_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, &$tables_props, $reserved_sql_keywords, &$parameters) {
		$parameters = array();
	
		$sql = $query_data["value"];
		if ($sql) {
			//PREPARING SQL
			if (is_array($reserved_sql_keywords))
				foreach ($reserved_sql_keywords as $reserved_name)
					$sql = str_replace("#$reserved_name#", "", $sql);
			
			//PARSEING SQL
			$data = $data_access_obj ? $data_access_obj->getBroker($db_broker)->getFunction("convertSQLToObject", $sql, array("db_driver" => $db_driver)) : DB::convertDefaultSQLToObject($sql);
			$sql_type = $data["type"];
			//echo $sql;echo "<pre>";print_r($data);echo "</pre>";
			
			//PREPARING SQL TYPES
			$parameters = self::getSQLStatementProps($sql);
			foreach ($parameters as $name => $type) {
				$type = $type ? $type : "no_string";
				$parameters[$name] = array(
					"type" => "org.phpframework.object.php.Primitive($type)", 
					"mandatory" => $type == "no_string" ? true : null
				);
			}
			//echo "<pre>";print_r($parameters);echo "</pre>";die();
			
			$tables = array();
			$table_name = "";
		
			if ($sql_type == "insert" || $sql_type == "update" || $sql_type == "delete") {
				$table_name = $data["table"];
				if (strpos($table_name, "#") !== false) {
					$table_name = str_replace("#", "", $table_name);
					$parameters[$table_name] = array(
						"type" => "org.phpframework.object.php.Primitive(name)", 
						"mandatory" => true,
					);
				}
				else
					$tables[$table_name] = true;
		
				self::prepareSQLColumnProps($data["attributes"], $tables, $parameters, "table", "column", "value");
				self::prepareSQLColumnProps($data["conditions"], $tables, $parameters, "table", "column", "value");
			}
			else if ($sql_type == "select") {
				$table_name = $data["attributes"][0]["table"] ? $data["attributes"][0]["table"] : $data["table"];
				$tables[$table_name] = true;
				
				self::prepareSQLColumnProps($data["attributes"], $tables, $parameters, "table", "column");
				self::prepareSQLColumnProps($data["keys"], $tables, $parameters, "ptable", "pcolumn", "value");
				self::prepareSQLColumnProps($data["keys"], $tables, $parameters, "ftable", "fcolumn", "value");
				self::prepareSQLColumnProps($data["conditions"], $tables, $parameters, "table", "column", "value");
				self::prepareSQLColumnProps($data["groups_by"], $tables, $parameters, "table", "column");
				self::prepareSQLColumnProps($data["sorts"], $tables, $parameters, "table", "column");
			}
			
			self::prepareParameters($query_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, $tables, $parameters, $table_name);
			
			self::prepareParametersFromClass($query_data["@"]["parameter_class"], $parameters);
		}
	}
	
	private static function prepareTableProps($data_access_obj, $db_broker, $db_driver, $tasks_file_path, &$tables_props, $tables) {
		if ($tables) {
			foreach ($tables as $table_name) 
				if ($table_name) {
					$table_name = strpos($table_name, " ") !== false ? strstr($table_name, " ", true) : $table_name;
					$tn = strtolower($table_name);
					
					if (!$tables_props[$tn])
						$tables_props[$tn] = $data_access_obj->getFunction("listTableFields", $table_name, array("db_broker" => $db_broker, "db_driver" => $db_driver));
				}
			
			if ($tasks_file_path) {
				$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
				$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
				$tasks_tables = $WorkFlowDataAccessHandler->getTasksAsTables();
				
				if ($tasks_tables) 
					foreach ($tasks_tables as $table_name => $attrs) {
						$tn = strtolower($table_name);
						
						if (!$tables_props[$tn])
							$tables_props[$tn] = $attrs;
						else
							foreach ($attrs as $attr_name => $attr) 
								if (!$tables_props[$tn][$attr_name])
									$tables_props[$tn][$attr_name] = $attr;
								else if ($attr)
									foreach ($attr as $k => $v)
										$tables_props[$tn][$attr_name][$k] = $v;
					}
			}
		}
		//echo "<pre>";print_r($tables_props);echo "</pre>";
	}
	
	private static function prepareParameters($query_data, $rels, $data_access_obj, $db_broker, $db_driver, $tasks_file_path, &$tables_props, $tables, &$parameters, $default_table_name = "") {
		//PREPARING DB TYPES
		self::prepareTableProps($data_access_obj, $db_broker, $db_driver, $tasks_file_path, $tables_props, array_keys($tables));

		$numeric_types = ObjTypeHandler::getDBNumericTypes();

		//echo "<pre>";print_r($parameters);echo "</pre>";
		
		//PREPARING PARAMETERS WITH NEW ATTRS FROM DB
		$default_table_name = strpos($default_table_name, " ") !== false ? strstr($default_table_name, " ", true) : $default_table_name;
		$dtn = strtolower($default_table_name);
		
		foreach ($parameters as $name => $param) {
			if ($param["parameter_type"] == "table_column") {
				$tn = strtolower($param["parameter_table"]);
				$cn = strtolower($param["parameter_column"]);
				
				$db_attr = null;
				if ($tables_props[$tn][$cn])
					$db_attr = $tables_props[$tn][$cn];
				else if ($dtn && $tables_props[$dtn][$cn])
					$db_attr = $tables_props[$dtn][$cn];
				else 
					foreach ($tables_props as $table_name => $fields) {
						foreach ($fields as $attr_name => $attr) {
							if (strtolower($attr_name) == $cn) {
								$db_attr = $attr;
								break;
							}
						}
						
						if ($db_attr)
							break;
					}
				
				if ($db_attr) {
					$db_type = $db_attr["type"];
			
					$db_attr["name"] = $name;
					$db_attr["type"] = $param["type"];
					$db_attr["mandatory"] = $param["mandatory"];
			
					if (strpos($param["type"], "(no_string)") !== false && in_array($db_type, $numeric_types)) 
						$db_attr["type"] = "org.phpframework.object.db.DBPrimitive($db_type)";
					else if (strpos($param["type"], "(no_string)") === false)
					//else if (!$param["type"] || strpos($param["type"], "(string)"))
						$db_attr["type"] = "org.phpframework.object.db.DBPrimitive($db_type)";
			
					$parameters[$name] = $db_attr;
				}
			}
	
			unset($parameters[$name]["parameter_type"]);
			unset($parameters[$name]["parameter_column"]);
			unset($parameters[$name]["parameter_table"]);
		}

		//PREPARING PARAMETER MAP TYPES
		$parameter_map = $query_data["@"]["parameter_map"];
		$map_entries = $parameter_map ? $rels["parameter_map"][$parameter_map]["parameter"] : null;
		//echo "<pre>";print_r($map_entries);echo "</pre>";

		if ($map_entries) {
			$t = count($map_entries);
			for ($i = 0; $i < $t; $i++) {
				$entry = $map_entries[$i];
				$on = $entry["output_name"];
		
				$parameters[$on]["name"] = $entry["input_name"];
		
				if (isset($entry["input_type"]))
					$parameters[$on]["type"] = $entry["input_type"];
		
				if (isset($entry["mandatory"]))
					$parameters[$on]["mandatory"] = $entry["mandatory"];
			}
		}

		//echo "<pre>";print_r($parameters);echo "</pre>";
	}
	
	private static function prepareSQLColumnProps($items, &$tables, &$parameters, $table_attr_name, $column_attr_name, $value_attr_name = "") {
		if (is_array($items)) {
			$t = count($items);
			for ($i = 0; $i < $t; $i++) {
				$attr = $items[$i];
				//echo "<pre>";print_r($attr);echo "</pre>";
			
				if ($table_attr_name && isset($attr[$table_attr_name])) {
					$table = $attr[$table_attr_name];
					if (strpos($table, "#") !== false) {
						$table = str_replace("#", "", $table);
						$parameters[$table] = array(
							"type" => "org.phpframework.object.php.Primitive(name)", 
							"mandatory" => true
						);
					}
					else {
						$tables[$table] = true;
					}
				}
			
				if ($column_attr_name && isset($attr[$column_attr_name]) && strpos($attr[$column_attr_name], "#") !== false) {
					$op = strtolower($attr["operator"]);
					$type = $op == "in" || $op == "not in" ? "array_str" : "name";
					$column = str_replace("#", "", $attr[$column_attr_name]);
					$table = $attr[$table_attr_name];
				
					$parameters[$column] = array(
						"type" =>"org.phpframework.object.php.Primitive($type)", 
						"mandatory" => true
					);
				
					if ($op != "in" && $op != "not in") {
						$parameters[$column]["parameter_type"] = "table_column";
						$parameters[$column]["parameter_column"] = $column;
						$parameters[$column]["parameter_table"] = $table;
					}
				}
			
				if ($value_attr_name && isset($attr[$value_attr_name]) && strpos($attr[$value_attr_name], "#") !== false) {
					$op = strtolower($attr["operator"]);
					$value = $attr[$value_attr_name];
					$value = str_replace(array("'", '"', "#"), "", $value);
					$value_type = $op == "in" || $op == "not in" ? "org.phpframework.object.php.Primitive(array_str)" : $parameters[$value]["type"];
					$column = $parameters[$value]["parameter_column"] ? $parameters[$value]["parameter_column"] : $attr[$column_attr_name];//Because of the PCOLUMN and FCOLUMN, otherwise it will overwrite with empty values
					$table = $parameters[$value]["parameter_column"] ? $parameters[$value]["parameter_table"] : $attr[$table_attr_name];
				
					$parameters[$value] = array(
						"type" => $value_type, 
						"mandatory" => strpos($value_type, "(no_string)") !== false || strpos($value_type, "(array_str)") !== false ? true : null,
					);
				
					if ($op != "in" && $op != "not in") {
						$parameters[$value]["parameter_type"] = "table_column";
						$parameters[$value]["parameter_column"] = $column;
						$parameters[$value]["parameter_table"] = $table;
					}
				}
			}
		}
	}
	
	//used here and in the create_business_logic_objs_automatically.php too
	public static function isAutoIncrementedAttribute($att) {
		return $att["auto_increment"] || stripos($att["extra"], "auto_increment") !== false || stripos($att["extra"], "nextval") !== false || in_array($att["type"], DB::getAllColumnAutoIncrementTypes());
	}
	
	public static function getTableAttrTitle($attrs, $table_name = false) {
		$title_attr = null;
		$ltn = $table_name ? strtolower($table_name) : $table_name;
		$available_attr_names = ObjTypeHandler::getDBAttributeNameTitleAvailableValues();
		
		foreach ($attrs as $attr_name => $attr) {
			$lan = strtolower(trim($attr_name));
			
			if (preg_match("/^(" . implode("|", $available_attr_names) . ")$/iu", $lan) || ($ltn && preg_match("/^(" . implode("|", $available_attr_names) . ")([ \-_]*)$ltn$/iu", $lan)) || ($ltn && preg_match("/^$ltn([ \-_]*)(" . implode("|", $available_attr_names) . ")$/iu", $lan)))
				$title_attr = $attr_name;
			else if (empty($title_attr) && strpos($attr["type"], "char") !== false && empty($attr["primary_key"]))
				$title_attr = $attr_name;
		}
		
		return $title_attr;
	}
	
	public static function getTableAttributeFKTable($attr_fks, $tables) {
		if (is_array($attr_fks)) {
			$selected_fk_table = null;
			$minimum_pks_count = -1;
			
			foreach ($attr_fks as $i => $attr_fk) {
				$fk_table = $attr_fk["table"];
				$attrs = WorkFlowDBHandler::getTableFromTables($tables, $fk_table);
				
				if (self::getTableAttrTitle($attrs, $fk_table)) {
					$pks_count = 0;
					foreach ($attrs as $attr)
						if ($attr["primary_key"])
							$pks_count++;
					
					if ($minimum_pks_count == -1 || $minimum_pks_count > $pks_count) {
						$selected_fk_table = $attr_fk;
						$minimum_pks_count = $pks_count;
					}
				}
			}
			
			return $selected_fk_table ? $selected_fk_table : $attr_fks[0];
		}
	}
}
?>
