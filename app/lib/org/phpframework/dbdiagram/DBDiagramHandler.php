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

include_once get_lib("org.phpframework.xmlfile.XMLFileParser");
include_once get_lib("org.phpframework.dbdiagram.TableDiagram");

//THIS METHOD IS DEPRECATED AND IS NOT USED ANYMORE, besides in the file: app/__system/layer/presentation/test/src/entity/tests/dbdiagram.php. THIS IS DONE NOW IN EACH DB DRIVER.
class DBDiagramHandler {
	
	public static function parseFile($file_url) {
		$arr = XMLFileParser::parseXMLFileToArray($file_url);
		
		$tables = array();
		
		if (!empty($arr["tables"][0]["childs"]["table"]) && is_array($arr["tables"][0]["childs"]["table"])) {
			foreach ($arr["tables"][0]["childs"]["table"] as $table_data) {
				$TableDiagram = new TableDiagram();
				$TableDiagram->parse($table_data);
				
				if ($TableDiagram->isValid()) {
					$tables[] = $TableDiagram;
				}
				else {
					launch_exception(new TableDiagramException(11, $TableDiagram));
				}
			}
		}
		
		$sql = "";
		
		$total = count($tables);
		for ($i = 0; $i < $total; $i++) {
			$TableDiagram = $tables[$i];
			
			$sql .= $TableDiagram->printSQL();
		}
		
		return $sql;
	}
}
?>
