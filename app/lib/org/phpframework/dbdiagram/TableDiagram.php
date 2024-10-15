<?php
include_once get_lib("org.phpframework.dbdiagram.ITableDiagram");
include_once get_lib("org.phpframework.dbdiagram.exception.TableDiagramException");

/*
DROP TABLE IF EXISTS zip; 
CREATE TABLE zip (
  zip_id VARCHAR(15) NOT NULL DEFAULT '0' COMMENT '',
  zone_id BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
  created_date TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  modified_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (zip_id, zone_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE INDEX idx_index_zip_id ON zip (zip_id);
CREATE INDEX idx_index_zone_id ON zip (zone_id);

DROP TABLE IF EXISTS company; 
CREATE TABLE company (
  company_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '',
  corporate_name varchar(100) NOT NULL DEFAULT '' COMMENT 'nome corporativo',
  marketing_name varchar(100) NOT NULL DEFAULT '' COMMENT 'nome da empresa',
  social_security_number varchar(50) NOT NULL DEFAULT '' COMMENT 'nif',
  web_site_url varchar(2048) NOT NULL DEFAULT '' COMMENT 'Url of the company homepage',
  created_date TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  modified_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (company_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
*/
//THIS METHOD IS DEPRECATED AND IS NOT USED ANYMORE, besides in the file: app/__system/layer/presentation/test/src/entity/tests/dbdiagram.php. THIS IS DONE NOW IN EACH DB DRIVER.
class TableDiagram implements ITableDiagram { 
	public $data;
	
	public function printSQL() {
		$sql = "";
		
		$data = $this->data;
		$table_name = isset($data["name"]) ? $data["name"] : null;
		
		if (!empty($data["drop_before_create"]))
			$sql .= "DROP TABLE IF EXISTS " . $table_name . ";\n";
		
		$sql .= "CREATE TABLE " . $table_name . " ( \n";
		
		if (!empty($data["attributes"]))
			foreach ($data["attributes"] as $attribute)
				if (!empty($attribute["name"]) && !empty($attribute["type"])) {
					$length = !empty($attribute["length"]) ? "(" . $attribute["length"] . ")" : "";
					$unsigned = !empty($attribute["unsigned"]) ? "UNSIGNED" : "";
					$not_null = !empty($attribute["not_null"]) ? "NOT NULL" : "";
					$auto_increment = !empty($attribute["auto_increment"]) ? "AUTO_INCREMENT" : "";
					$default = !empty($attribute["default"]) ? "DEFAULT " . $attribute["default"] : "";
					$on_update = !empty($attribute["on_update"]) ? "ON UPDATE " . $attribute["on_update"] : "";
					$comment = !empty($attribute["comment"]) ? "COMMENT '" . $attribute["comment"] . "'" : "";
					
					$attr_sql = $attribute["name"] . " " . $attribute["type"] . "$length $unsigned $not_null $auto_increment $default $on_update $comment";
					
					$sql .= "   " . trim($attr_sql) . ",\n";
				}
		
		$sql .= !empty($data["keys"]["primary_keys"][0]["attribute"]) ? "   PRIMARY KEY (" . $data["keys"]["primary_keys"][0]["attribute"] . "),\n" : "";
		
		if (!empty($data["keys"]["unique_keys"]) && is_array($data["keys"]["unique_keys"]))
			foreach ($data["keys"]["unique_keys"] as $key) {
				$type = isset($key["type"]) && $key["type"] ? "USING " . $key["type"] : "";
				$key_name = isset($key["name"]) ? $key["name"] : null;
				$key_attribute = isset($key["attribute"]) ? $key["attribute"] : null;
				
				$sql .= "   UNIQUE " . $key_name . " $type (" . $key_attribute . "),\n";
			}
		
		if (!empty($data["keys"]["foreign_keys"]) && is_array($data["keys"]["foreign_keys"]))
			foreach ($data["keys"]["foreign_keys"] as $key) {
				$on_delete = isset($key["on_delete"]) && $key["on_delete"] ? "ON DELETE " . $key["on_delete"] : "";
				$on_update = isset($key["on_update"]) && $key["on_update"] ? "ON UPDATE " . $key["on_update"] : "";
				$key_attribute = isset($key["attribute"]) ? $key["attribute"] : null;
				$key_reference_table = isset($key["reference_table"]) ? $key["reference_table"] : null;
				$key_reference_attribute = isset($key["reference_attribute"]) ? $key["reference_attribute"] : null;
				
				$sql .= "   FOREIGN KEY (" . $key_attribute . ") REFERENCES " . $key_reference_table . " (" . $key_reference_attribute . ") $on_delete $on_update,\n";
			}
		
		if (!empty($data["keys"]["index_keys"]) && is_array($data["keys"]["index_keys"]))
			foreach ($data["keys"]["index_keys"] as $key) {
				$type = isset($key["type"]) && $key["type"] ? "USING " . $key["type"] : "";
				$key_name = isset($key["name"]) ? $key["name"] : null;
				$key_attribute = isset($key["attribute"]) ? $key["attribute"] : null;
				
				$sql .= "   INDEX " . $key_name . " $type (" . $key_attribute . "),\n";
			}
		
		$engine = !empty($data["engine"]) ? "ENGINE=" . $data["engine"] : "";
		$default = !empty($data["charset"]) || !empty($data["collate"]) ? ("DEFAULT " . (!empty($data["charset"]) ? "CHARSET=" . $data["charset"] : "") . " " . (!empty($data["collate"]) ? "COLLATE=" . $data["collate"] : "")) : "";
		
		$sql = trim($sql);
		$sql = substr($sql, strlen($sql) - 1) == "," ? substr($sql, 0, strlen($sql) - 1) . "\n" : $sql;
		
		$sql .= ") $engine $default;\n\n";
		
		return $sql;
	}
	
	public function parse($table_data) {
		$data = array();
		
		if (isset($table_data["childs"]) && is_array($table_data["childs"])) {
			$data["name"] = isset($table_data["childs"]["name"][0]["value"]) ? $table_data["childs"]["name"][0]["value"] : null;
			$data["drop_before_create"] = isset($table_data["childs"]["drop_before_create"][0]["value"]) ? $table_data["childs"]["drop_before_create"][0]["value"] : null;
			$data["drop_before_create"] = !empty($data["drop_before_create"]) && ($data["drop_before_create"] == "1" || $data["drop_before_create"] == "true");
			
			$data["engine"] = isset($table_data["childs"]["engine"][0]["value"]) ? $table_data["childs"]["engine"][0]["value"] : null;
			$data["charset"] = isset($table_data["childs"]["charset"][0]["value"]) ? $table_data["childs"]["charset"][0]["value"] : null;
			$data["collate"] = isset($table_data["childs"]["collate"][0]["value"]) ? $table_data["childs"]["collate"][0]["value"] : null;
			
			$data["attributes"] = array();
			
			if (!empty($table_data["childs"]["attributes"][0]["childs"]["attribute"]) && is_array($table_data["childs"]["attributes"][0]["childs"]["attribute"]))
				foreach ($table_data["childs"]["attributes"][0]["childs"]["attribute"] as $attribute) 
					if (!empty($attribute["@"])) {
						$prop = $attribute["@"];
						
						if (isset($prop["unsigned"]))
							$prop["unsigned"] = $prop["unsigned"] == "1" || $prop["unsigned"] == "true";
						
						if (isset($prop["not_null"]))
							$prop["not_null"] = $prop["not_null"] == "1" || $prop["not_null"] == "true";
						
						if (isset($prop["auto_increment"]))
							$prop["auto_increment"] = $prop["auto_increment"] == "1" || $prop["auto_increment"] == "true";
						
						$data["attributes"][] = $prop;
					}
			
			$data["keys"] = array();
			
			if (!empty($table_data["childs"]["keys"][0]["childs"]) && is_array($table_data["childs"]["keys"][0]["childs"]))
				foreach ($table_data["childs"]["keys"][0]["childs"] as $key_type => $keys) {
					$child_keys = !empty($keys[0]["childs"]) && is_array($keys[0]["childs"]) ? array_keys($keys[0]["childs"]) : array();
					$first_child_key = isset($child_keys[0]) ? $child_keys[0] : null;
					
					if ($first_child_key && !empty($keys[0]["childs"][$first_child_key]) && is_array($keys[0]["childs"][$first_child_key]))
						foreach ($keys[0]["childs"][$first_child_key] as $key)
							if (!empty($key["@"]))
								$data["keys"][$key_type][] = $key["@"];
				}
		}
		
		$this->data = $data;
	}
	
	public function isValid() {
		$data = $this->data;
		
		if (empty($data["name"])) {
			launch_exception(new TableDiagramException(1, null));
			return false;
		}
		
		if (!empty($data["attributes"]))
			foreach ($data["attributes"] as $attribute) {
				if (empty($attribute["name"])) {
					launch_exception(new TableDiagramException(2, array($data["name"], $attribute)));
					return false;
				}
				
				if (empty($attribute["type"])) {
					launch_exception(new TableDiagramException(3, array($data["name"], $attribute)));
					return false;
				}
			}
		
		if (!empty($data["keys"]["unique_keys"]) && is_array($data["keys"]["unique_keys"]))
			foreach ($data["keys"]["unique_keys"] as $key) {
				if (empty($key["name"])) {
					launch_exception(new TableDiagramException(4, array($data["name"], $key)));
					return false;
				}
				
				if (empty($key["attribute"])) {
					launch_exception(new TableDiagramException(5, array($data["name"], $key)));
					return false;
				}
			}
		
		if (!empty($data["keys"]["foreign_keys"]) && is_array($data["keys"]["foreign_keys"]))
			foreach ($data["keys"]["foreign_keys"] as $key) {
				if (empty($key["attribute"])) {
					launch_exception(new TableDiagramException(6, array($data["name"], $key)));
					return false;
				}
				
				if (empty($key["reference_table"])) {
					launch_exception(new TableDiagramException(7, array($data["name"], $key)));
					return false;
				}
				
				if (empty($key["reference_attribute"])) {
					launch_exception(new TableDiagramException(8, array($data["name"], $key)));
					return false;
				}
			}
		
		if (!empty($data["keys"]["index_keys"]) && is_array($data["keys"]["index_keys"]))
			foreach ($data["keys"]["index_keys"] as $key) {
				if (empty($key["name"])) {
					launch_exception(new TableDiagramException(9, array($data["name"], $key)));
					return false;
				}
				
				if (empty($key["attribute"])) {
					launch_exception(new TableDiagramException(10, array($data["name"], $key)));
					return false;
				}
			}
		
		return true;
	}
}
?>
