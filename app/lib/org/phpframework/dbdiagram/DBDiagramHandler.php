<?php
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
