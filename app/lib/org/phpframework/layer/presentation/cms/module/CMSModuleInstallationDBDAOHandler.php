<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.util.xml.MyXML");
include_once get_lib("org.phpframework.util.xml.MyXMLArray");

class CMSModuleInstallationDBDAOHandler {
	
	public static function createModuleDBDAOUtilFilesFromHibernateFile($hibernate_xml_files, $layers_paths, $module_id, &$messages = array()) {
		$status = true;
		
		if ($hibernate_xml_files)
			foreach ($hibernate_xml_files as $hibernate_xml_file)
				foreach ($layers_paths as $layer_type => $layer_paths) {
					if ($layer_type == "businesslogic" && !self::createBusinessLogicModuleDBDAOUtilFileFromHibernateFile($hibernate_xml_file, $layer_paths, $module_id, $messages))
						$status = false;
					else if (($layer_type == "presentation" || $layer_type == "system_settings") && !self::createPresentationModuleDBDAOUtilFileFromHibernateFile($hibernate_xml_file, $layer_paths, $messages))
						$status = false;
				}
		
		return $status;
	}
	
	//create businesslogic XXXDBDAOServiceUtil class
	public static function createBusinessLogicModuleDBDAOUtilFileFromHibernateFile($hibernate_xml_file, $paths, $module_id, &$messages = array()) {
		$status = true;
		
		if ($paths) {
			$file_name = pathinfo($hibernate_xml_file, PATHINFO_FILENAME);
			$obj_name_prefix = str_replace(" ", "", ucwords(str_replace(array("_", "-"), " ", strtolower($file_name))));
			$ns = str_replace(" ", "", ucwords(str_replace(array("_", "-"), " ", strtolower($module_id))));
			$class_name = "{$obj_name_prefix}DBDAOServiceUtil";
			
			$code = self::getModuleDBDAOUtilCodeFromHibernateFile($hibernate_xml_file, $class_name, "Module\\$ns");
			
			foreach ($paths as $path) {
				$fp = "$path/$class_name.php";
				
				if (file_put_contents($fp, $code) === false) {
					$messages[] = "Error trying to create file: " . str_replace(LAYER_PATH, "", $fp);
					$status = false;
				}
			}
		}
		
		return $status;
	}
	
	//create presentation XXXDBDAOUtil class
	public static function createPresentationModuleDBDAOUtilFileFromHibernateFile($hibernate_xml_file, $paths, &$messages = array()) {
		$status = true;
		
		if ($paths) {
			$file_name = pathinfo($hibernate_xml_file, PATHINFO_FILENAME);
			$obj_name_prefix = str_replace(" ", "", ucwords(str_replace(array("_", "-"), " ", strtolower($file_name))));
			$class_name = "{$obj_name_prefix}DBDAOUtil";
			
			$code = self::getModuleDBDAOUtilCodeFromHibernateFile($hibernate_xml_file, $class_name);
			
			foreach ($paths as $path) {
				$fp = "$path/$class_name.php";
				
				if (file_put_contents($fp, $code) === false) {
					$messages[] = "Error trying to create file: " . str_replace(LAYER_PATH, "", $fp);
					$status = false;
				}
			}
		}
		
		return $status;
	}
	
	private static function getModuleDBDAOUtilCodeFromHibernateFile($hibernate_xml_file, $class_name, $namespace = null) {
		$queries = self::getHibernateFileQueries($hibernate_xml_file);
		
		$code = '<?php';
		
		if ($namespace)
			$code .= '
namespace ' . $namespace . ';
';
		
		$code .= '
if (!class_exists("' . $class_name . '")) {
	class ' . $class_name . ' {
		';
	
		if ($queries)
			foreach ($queries as $query_id => $query) {
				$query_id = str_replace(" ", "_", $query_id);
				$query_code = self::getQueryCode($query);
				$query_code = str_replace("\n", "\n\t", $query_code);
				
				$code .= '
		public static function ' . $query_id . '($data = array()) {
			' . $query_code . '
		}
	';
			}
		
		$code .= '
	}
}
?>';
		return $code;
	}
	
	private static function getHibernateFileQueries($hibernate_xml_file) {
		$queries = array();
		
		if (file_exists($hibernate_xml_file)) {
			$content = file_get_contents($hibernate_xml_file);
			$MyXML = new MyXML($content);
			$arr = $MyXML->toArray(array("simple" => false, "lower_case_keys" => true, "xml_order_id_prefix" => false));
			$MyXMLArray = new MyXMLArray($arr);
			$nodes = $MyXMLArray->getNodes("sql_mapping/class/queries");
			$nodes = isset($nodes[0]["childs"]) ? $nodes[0]["childs"] : null; //queries with type
			
			if ($nodes)
				foreach ($nodes as $type => $type_nodes) 
					if ($type_nodes)
						foreach ($type_nodes as $query_node) {
							$query_id = isset($query_node["@"]["id"]) ? $query_node["@"]["id"] : null;
							$sql = isset($query_node["value"]) ? $query_node["value"] : null;
							
							if (isset($query_id))
								$queries[$query_id] = $sql;
						}
				
			//echo "<pre>";print_r($queries);die();
		}
		
		return $queries;
	}
	
	private static function getQueryCode($query) {
		$query = trim($query);
		$query = addcslashes($query, '\\"');
		preg_match_all("/#(\w+)#/", $query, $matches, PREG_SET_ORDER);
		//echo "<pre>$query<br>";print_r($matches);die();
		
		if ($matches)
			foreach ($matches as $m) 
				if (!empty($m[1]))
					$query = str_replace($m[0], '" . $data["' . $m[1] . '"] . "', $query);
		
		$code = 'return "' . $query . '";';
		$code = str_replace(' . "";', ';', $code);
		
		return $code;
	}
}
?>
