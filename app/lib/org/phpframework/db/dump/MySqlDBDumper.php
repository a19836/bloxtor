<?php
include_once get_lib("org.phpframework.db.dump.DBDumper");

class MySqlDBDumper extends DBDumper {
	const REGEX = 'DEFINER=`(?:[^`]|``)*`@`(?:[^`]|``)*`';

	public function __construct($DBDumperHandler) {
		$this->DBDumperHandler = $DBDumperHandler;
	}

	public function databases($db_name) {
		$sql = "SHOW VARIABLES LIKE 'collation_database';";
		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($sql);
		$collation = isset($rows[0]["Value"]) ? $rows[0]["Value"] : null;
		
		$sql = "SHOW VARIABLES LIKE 'character_set_database';";
		$rows = $this->DBDumperHandler->getDBDriver()->getSQL($sql);
		$character_set = isset($rows[0]["Value"]) ? $rows[0]["Value"] : null;

		$ret = "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `${db_name}`".
		  " /*!40100 DEFAULT CHARACTER SET ${character_set} ".
		  " COLLATE ${collation} */;" . PHP_EOL . PHP_EOL . 
		  "USE `${db_name}`;" . PHP_EOL . PHP_EOL;

		return $ret;
	}

	public function createTable($res, $table_name, $foreign_keys_to_ignore = false) {
		$row = isset($res[0]) ? $res[0] : null;
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();

		if (!isset($row['Create Table']))
			throw new \Exception("Error getting table code, unknown output");
		
		$create_table = $row['Create Table'];

		if (!empty($db_dumper_settings['reset-auto-increment'])) {
			$match = "/AUTO_INCREMENT=[0-9]+/s";
			$replace = "";
			$create_table = preg_replace($match, $replace, $create_table);
		}

		//prepare foreign keys to ignore
		if ($foreign_keys_to_ignore) {
			//prepare fks by columns
			$fks_to_ignore = array();
			if ($foreign_keys_to_ignore)
				foreach ($foreign_keys_to_ignore as $fk)
					if (isset($fk["child_column"]) && isset($fk["parent_table"]) && isset($fk["parent_column"]))
						$fks_to_ignore[ $fk["child_column"] ][ $fk["parent_table"] ][ $fk["parent_column"] ] = true;
		  		
		  //get all FOREIGN KEYS sql from $create_table
		  //CONSTRAINT `sub_item_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`)
		  preg_match_all("/[^,]+(\s*FOREIGN\s+KEY\s*)[^,;]+/iu", $create_table, $matches, PREG_OFFSET_CAPTURE);
		  
		  if ($matches) {
		   	  foreach ($matches[0] as $m) {
		   	  	$match = $m[0];
		   	  	$pos = $m[1];
		   	  	$may_contain_comma = true;
		   	  	
		   	  	//If FOREIGN KEY sql is the first line after "CREATE TABLE xx(", gets the open parenthesis and then remove all previous text.
		   	  	if (preg_match("/create\s+table\s+/iu", $match, $sub_matches, PREG_OFFSET_CAPTURE)) {
		   	  		$start_pos = strpos($match, "(", $sub_matches[0][1] + 1) + 1;
		   	  		$match = trim( substr($match, $start_pos) );
			   	  	$may_contain_comma = false;
		   	  	}
		   	  	
		   	  	//check if there is any close parenthesis that should not be in the $match. Here is an example.
		   	  	//	CONSTRAINT `sub_item_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`)) ENGINE=
		   	  	//In this example the ") ENGINE=" text should be removed.
		   	  	$l = strlen($match);
		   	  	$parenthesis_count = 0;
		   	  	$odq = $osq = false;
		   	  	
		   	  	for ($j = 0; $j < $l; $j++) {
		   	  		$char = $match[$j];
		   	  		
		   	  		if ($char == '"' && !$osq) //I don't need to be worry with the escape double quotes bc they won't happen in this sql.
		   	  			$odq = !$odq;
		   	  		else if ($char == "'" && !$odq) //I don't need to be worry with the escape single quotes bc they won't happen in this sql.
		   	  			$osq = !$osq;
		   	  		else if (!$odq && !$osq) {
			   	  		if ($char == "(")
			   	  			$parenthesis_count++;
			   	  		else if ($char == ")") {
			   	  			$parenthesis_count--;
			   	  			
			   	  			if ($parenthesis_count < 0) {
			   	  				$match = trim( substr($match, 0, $j) ); //remove all sql that doesn't matter.
			   	  				break;
			   	  			}
			   	  		}
			   	  	}
		   	  	}
		   	  	
		   	  	if (preg_match("/\s*FOREIGN\s+KEY\s*\(\s*`?([\w]+)`?\s*\)\s*REFERENCES\s*`?([\w]+)`?\s*\(\s*`?([\w]+)`?\s*\)/iu", $match, $sub_matches, PREG_OFFSET_CAPTURE)) {
			   	  	$child_column = $sub_matches[1][0];
			   	  	$parent_table = $sub_matches[2][0];
			   	  	$parent_column = $sub_matches[3][0];
			   	  	
			   	  	if ($child_column && $parent_table && $parent_column && !empty($fks_to_ignore[ $child_column ][ $parent_table ][ $parent_column ])) {
			   	  		$fk_sql = $match;
			   	  		$fk_sql = substr($fk_sql, 0, 1) == "," ? substr($fk_sql, 1) : $fk_sql; //remove first colon
			   	  		$fk_sql = substr($fk_sql, -1) == "," ? substr($fk_sql, 0, -1) : $fk_sql; //remove last colon
			   	  		
			  			$this->DBDumperHandler->setTableExtraSql($parent_table, "ALTER TABLE `$table_name` ADD $fk_sql;" . PHP_EOL);
			  			
			  			$match = substr($match, -1) == "," ? substr($match, 0, -1) : $match; //remove last colon
			  			
			  			$create_table = str_replace($match, "", $create_table);
			   	  		$create_table = preg_replace("/,(\s*)\)/", '$1)', $create_table); //remove comma from previous column if exists
			  		}
			  	}
		   	  }
		  }
		}

		//add create_table sql
		$ret = "/*!40101 SET @saved_cs_client     = @@character_set_client */;" . PHP_EOL . 
		  (!empty($db_dumper_settings['default-character-set']) ? "/*!40101 SET character_set_client = ".$db_dumper_settings['default-character-set']." */;" . PHP_EOL : "") . 
		  $create_table.";" . PHP_EOL . 
		  "/*!40101 SET character_set_client = @saved_cs_client */;" . PHP_EOL . 
		  PHP_EOL;
		
		return $ret;
	}

	public function createRecordsInsertStmt($table_name, $rows) {
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
		$CompressionHandler = $this->DBDumperHandler->getFileCompressionHandler();
		
		// colNames is used to get the name of the columns when using complete-insert
		if (!empty($db_dumper_settings['complete-insert']))
			$attr_names = $this->DBDumperHandler->getTableAttributesNames($table_name);

		$line_size = 0;
		$only_once = true;
		$count = 0;
		$ignore = !empty($db_dumper_settings['insert-ignore']) ? ' IGNORE' : '';
		$net_buffer_length = isset($db_dumper_settings['net_buffer_length']) ? $db_dumper_settings['net_buffer_length'] : null;
		
		foreach ($rows as $row) {
			$vals = $this->DBDumperHandler->prepareTableRowAttributes($table_name, $row);
			$vals_sql = implode(",", $vals);
			
			if (empty($db_dumper_settings['extended-insert']) || $only_once) {
				if (!empty($db_dumper_settings['complete-insert']))
					$str = "INSERT$ignore INTO " . $this->escapeTable($table_name) . " (" . implode(", ", $attr_names) . ") VALUES (" . $vals_sql . ")";
				else
					$str = "INSERT$ignore INTO " . $this->escapeTable($table_name) . " VALUES (" . $vals_sql . ")";

				$only_once = false;
			} 
			else
				$str = ",(" . $vals_sql . ")";

			$line_size += $CompressionHandler->write($str);

			if (empty($db_dumper_settings['extended-insert']) || $line_size > $net_buffer_length) {
				$only_once = true;
				$line_size = $CompressionHandler->write(";" . PHP_EOL);
			}
			
			$count++;
		}

		if (!$only_once)
			$CompressionHandler->write(";" . PHP_EOL);

		return $count;
	}
	
	public function getSqlStmtWithLimit($sql, $limit) {
		return $sql . " LIMIT {$limit}";
	}

	public function createStandInTableForView($view_name, $inner_sql) {
		return "CREATE TABLE IF NOT EXISTS " . $this->escapeTable($view_name) . " (".
		  PHP_EOL . $inner_sql . PHP_EOL . ");" . PHP_EOL;
	}

	public function getTableAttributeProperties($attr_type) {
		$db_column_numeric_types = $this->DBDumperHandler->getDBDriver()->getDBColumnNumericTypes();
		$db_column_blob_types = $this->DBDumperHandler->getDBDriver()->getDBColumnBlobTypes();
		$db_column_boolean_types = $this->DBDumperHandler->getDBDriver()->getDBColumnBooleanTypes();
		
		$attr_props = array();
		$attr_props['field'] = isset($attr_type['column_name']) ? $attr_type['column_name'] : null;
		$attr_props['type_sql'] = isset($attr_type['data_type']) ? $attr_type['data_type'] : null;
		$parts = explode(" ", $attr_props['type_sql']);
		
		if ($pos = strpos($parts[0], "(")) {
			$attr_props['type'] = substr($parts[0], 0, $pos);
			$attr_props['length'] = str_replace(")", "", substr($parts[0], $pos + 1));
			$attr_props['attributes'] = isset($parts[1]) ? $parts[1] : null;
		} 
		else {
			$attr_props['type'] = $parts[0];
			$attr_props['length'] = !empty($attr_type["character_maximum_length"]) ? $attr_type["character_maximum_length"] : (isset($attr_type["numeric_precision"]) ? $attr_type["numeric_precision"] : null);
		}

		$attr_props['is_nullable'] = !isset($attr_type['is_nullable']) || strtolower($attr_type['is_nullable']) != "no" ? false : true;
		$attr_props['is_numeric'] = is_array($db_column_numeric_types) && in_array($attr_props['type'], $db_column_numeric_types);
		$attr_props['is_blob'] = is_array($db_column_blob_types) && in_array($attr_props['type'], $db_column_blob_types);
		$attr_props['is_boolean'] = is_array($db_column_boolean_types) && in_array($attr_props['type'], $db_column_boolean_types);
		// for virtual columns that are of type 'Extra', column type
		// could by "STORED GENERATED" or "VIRTUAL GENERATED"
		// MySQL reference: https://dev.mysql.com/doc/refman/5.7/en/create-table-generated-columns.html
		$attr_props['is_virtual'] = !empty($attr_type['Extra']) && (strpos($attr_type['Extra'], "VIRTUAL GENERATED") !== false || strpos($attr_type['Extra'], "STORED GENERATED") !== false);

		return $attr_props;
	}

	public function getTableAttributesPropertiesBitHexFunc($attr_name) {
		return "LPAD(HEX($attr_name),2,'0')";
	}

	public function getTableAttributesPropertiesBlobHexFunc($attr_name) {
		return "HEX($attr_name)";
	}

	public function createView($row) {
		$ret = "";
		
		if (!isset($row['Create View']))
			throw new \Exception("Error getting view structure, unknown output");
		
		$view_stmt = $row['Create View'];

		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
		$definer_str = !empty($db_dumper_settings['skip-definer']) ? '' : '/*!50013 \2 */' . PHP_EOL;

		if ($view_stmt_replaced = preg_replace('/^(CREATE(?:\s+ALGORITHM=(?:UNDEFINED|MERGE|TEMPTABLE))?)\s+('.self::REGEX.'(?:\s+SQL SECURITY DEFINER|INVOKER)?)?\s+(VIEW .+)$/', '/*!50001 \1 */' . PHP_EOL . $definer_str . '/*!50001 \3 */', $view_stmt, 1))
			$view_stmt = $view_stmt_replaced;
		
		$ret .= $view_stmt . ';' . PHP_EOL . PHP_EOL;
		
		return $ret;
	}

	public function createTrigger($row) {
		$ret = "";
		
		if (!isset($row['SQL Original Statement']))
			throw new \Exception("Error getting trigger code, unknown output");
		
		$trigger_stmt = $row['SQL Original Statement'];
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
		$definer_str = !empty($db_dumper_settings['skip-definer']) ? '' : '/*!50017 \2*/ ';
		
		if ($trigger_stmt_replaced = preg_replace('/^(CREATE)\s+('.self::REGEX.')?\s+(TRIGGER\s.*)$/s', '/*!50003 \1*/ '.$definer_str.'/*!50003 \3 */', $trigger_stmt, 1))
			$trigger_stmt = $trigger_stmt_replaced;

		$ret .= "DELIMITER ;;" . PHP_EOL . 
		  $trigger_stmt . ";;" . PHP_EOL . 
		  "DELIMITER ;" . PHP_EOL . PHP_EOL;
		
		return $ret;
	}

	public function createProcedure($row) {
		$ret = "";
		
		if (!isset($row['Create Procedure']))
			throw new \Exception("Error getting procedure code, unknown output. ".
				 "Please check 'https://bugs.mysql.com/bug.php?id=14564'");
		
		$procedure_stmt = $row['Create Procedure'];
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
		
		if (!empty($db_dumper_settings['skip-definer']))
			if ($procedure_stmt_replaced = preg_replace('/^(CREATE)\s+('.self::REGEX.')?\s+(PROCEDURE\s.*)$/s', '\1 \3', $procedure_stmt, 1 )) 
				$procedure_stmt = $procedure_stmt_replaced;
		
		$ret .= "/*!50003 DROP PROCEDURE IF EXISTS `".(isset($row['Procedure']) ? $row['Procedure'] : null)."` */;" . PHP_EOL . 
		  "/*!40101 SET @saved_cs_client     = @@character_set_client */;" . PHP_EOL . 
		  (!empty($db_dumper_settings['default-character-set']) ? "/*!40101 SET character_set_client = ".$db_dumper_settings['default-character-set']." */;" . PHP_EOL : "") . 
		  "DELIMITER ;;" . PHP_EOL . 
		  $procedure_stmt . " ;;" . PHP_EOL . 
		  "DELIMITER ;" . PHP_EOL . 
		  "/*!40101 SET character_set_client = @saved_cs_client */;" . PHP_EOL . PHP_EOL;

		return $ret;
	}

	public function createFunction($row) {
		$ret = "";
		
		if (!isset($row['Create Function']))
			throw new \Exception("Error getting function code, unknown output. ".
			 	"Please check 'https://bugs.mysql.com/bug.php?id=14564'");
		
		$function_stmt = $row['Create Function'];
		$character_set_client = isset($row['character_set_client']) ? $row['character_set_client'] : null;
		$collation_connection = isset($row['collation_connection']) ? $row['collation_connection'] : null;
		$sql_mode = isset($row['sql_mode']) ? $row['sql_mode'] : null;
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
		
		if (!empty($db_dumper_settings['skip-definer']))
			if ($function_stmt_replaced = preg_replace('/^(CREATE)\s+('.self::REGEX.')?\s+(FUNCTION\s.*)$/s', '\1 \3', $function_stmt, 1))
				$function_stmt = $function_stmt_replaced;

		$ret .= "/*!50003 DROP FUNCTION IF EXISTS `".
		  $row['Function'] . "` */;" . PHP_EOL . 
		  "/*!40101 SET @saved_cs_client     = @@character_set_client */;" . PHP_EOL . 
		  "/*!50003 SET @saved_cs_results     = @@character_set_results */ ;" . PHP_EOL . 
		  "/*!50003 SET @saved_col_connection = @@collation_connection */ ;" . PHP_EOL . 
		  ($character_set_client ? "/*!40101 SET character_set_client = " . $character_set_client . " */;" . PHP_EOL : "") . 
		  ($character_set_client ? "/*!40101 SET character_set_results = " . $character_set_client . " */;" . PHP_EOL : "") . 
		  ($collation_connection ? "/*!50003 SET collation_connection  = " . $collation_connection . " */ ;" . PHP_EOL : "") . 
		  "/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;" . PHP_EOL . 
		  ($sql_mode ? "/*!50003 SET sql_mode              = '".$sql_mode."' */ ;;" . PHP_EOL : "") . 
		  "/*!50003 SET @saved_time_zone      = @@time_zone */ ;;" . PHP_EOL . 
		  "/*!50003 SET time_zone             = 'SYSTEM' */ ;;" . PHP_EOL . 
		  "DELIMITER ;;" . PHP_EOL . 
		  $function_stmt . " ;;" . PHP_EOL . 
		  "DELIMITER ;" . PHP_EOL . 
		  "/*!50003 SET sql_mode              = @saved_sql_mode */ ;" . PHP_EOL . 
		  "/*!50003 SET character_set_client  = @saved_cs_client */ ;" . PHP_EOL . 
		  "/*!50003 SET character_set_results = @saved_cs_results */ ;" . PHP_EOL . 
		  "/*!50003 SET collation_connection  = @saved_col_connection */ ;" . PHP_EOL . 
		  "/*!50106 SET TIME_ZONE= @saved_time_zone */ ;" . PHP_EOL . PHP_EOL;

		return $ret;
	}

	public function createEvent($row) {
		$ret = "";
		
		if (!isset($row['Create Event']))
			throw new \Exception("Error getting event code, unknown output. ".
			 	"Please check 'http://stackoverflow.com/questions/10853826/mysql-5-5-create-event-gives-syntax-error'");
		
		$event_name = isset($row['Event']) ? $row['Event'] : null;
		$event_stmt = $row['Create Event'];
		$sql_mode = isset($row['sql_mode']) ? $row['sql_mode'] : null;
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
		$definer_str = !empty($db_dumper_settings['skip-definer']) ? '' : '/*!50117 \2*/ ';

		if ($event_stmt_replaced = preg_replace('/^(CREATE)\s+('.self::REGEX.')?\s+(EVENT .*)$/', '/*!50106 \1*/ '.$definer_str.'/*!50106 \3 */', $event_stmt, 1))
		  $event_stmt = $event_stmt_replaced;
		
		$ret .= "/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;" . PHP_EOL . 
		  "/*!50106 DROP EVENT IF EXISTS `" . $event_name . "` */;" . PHP_EOL . 
		  "DELIMITER ;;" . PHP_EOL . 
		  "/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;" . PHP_EOL . 
		  "/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;" . PHP_EOL . 
		  "/*!50003 SET @saved_col_connection = @@collation_connection */ ;;" . PHP_EOL . 
		  "/*!50003 SET character_set_client  = utf8 */ ;;" . PHP_EOL . 
		  "/*!50003 SET character_set_results = utf8 */ ;;" . PHP_EOL . 
		  "/*!50003 SET collation_connection  = utf8_general_ci */ ;;" . PHP_EOL . 
		  "/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;" . PHP_EOL . 
		  ($sql_mode ? "/*!50003 SET sql_mode              = '" . $sql_mode . "' */ ;;" . PHP_EOL : "") . 
		  "/*!50003 SET @saved_time_zone      = @@time_zone */ ;;" . PHP_EOL . 
		  "/*!50003 SET time_zone             = 'SYSTEM' */ ;;" . PHP_EOL . 
		  $event_stmt . " ;;" . PHP_EOL . 
		  "/*!50003 SET time_zone             = @saved_time_zone */ ;;" . PHP_EOL . 
		  "/*!50003 SET sql_mode              = @saved_sql_mode */ ;;" . PHP_EOL . 
		  "/*!50003 SET character_set_client  = @saved_cs_client */ ;;" . PHP_EOL . 
		  "/*!50003 SET character_set_results = @saved_cs_results */ ;;" . PHP_EOL . 
		  "/*!50003 SET collation_connection  = @saved_col_connection */ ;;" . PHP_EOL . 
		  "DELIMITER ;" . PHP_EOL . 
		  "/*!50106 SET TIME_ZONE= @save_time_zone */ ;" . PHP_EOL . PHP_EOL;
		  // Commented because we are doing this in restoreParameters()
		  // "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;" . PHP_EOL . PHP_EOL;

		return $ret;
	}

	public function backupParameters() {
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();
		$ret = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;" . PHP_EOL . 
		  "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;" . PHP_EOL . 
		  "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;" . PHP_EOL . 
		  (!empty($db_dumper_settings['default-character-set']) ? "/*!40101 SET NAMES " . $db_dumper_settings['default-character-set'] . " */;" . PHP_EOL : "");

		if (isset($db_dumper_settings['skip-tz-utc']) && $db_dumper_settings['skip-tz-utc'] === false)
			$ret .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;" . PHP_EOL . 
			 "/*!40103 SET TIME_ZONE='+00:00' */;" . PHP_EOL;
		
		if (!empty($db_dumper_settings['no-autocommit']))
			$ret .= "/*!40101 SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT */;" . PHP_EOL;
		
		$ret .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;" . PHP_EOL . 
		  "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;" . PHP_EOL . 
		  "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;" . PHP_EOL . 
		  "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;" . PHP_EOL . PHP_EOL;

		return $ret;
	}

	public function restoreParameters() {
		$ret = "";
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();

		if (isset($db_dumper_settings['skip-tz-utc']) && $db_dumper_settings['skip-tz-utc'] === false)
			$ret .= "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;" . PHP_EOL;
		
		if (!empty($db_dumper_settings['no-autocommit']))
			$ret .= "/*!40101 SET AUTOCOMMIT=@OLD_AUTOCOMMIT */;" . PHP_EOL;
		
		$ret .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;" . PHP_EOL . 
		  "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;" . PHP_EOL . 
		  "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;" . PHP_EOL . 
		  "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;" . PHP_EOL . 
		  "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;" . PHP_EOL . 
		  "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;" . PHP_EOL . 
		  "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;" . PHP_EOL . PHP_EOL;

		return $ret;
	}

	public function startDisableConstraintsAndTriggersStmt($tables) {
		//Disables foreign keys for all tables
		$sql = "SET foreign_key_checks = 0;" . PHP_EOL;

		//if drop too. This is redundant, but just in case we hv the code here to be executed.
		$db_dumper_settings = $this->DBDumperHandler->getDBDumperSettings();

		if ($tables && !empty($db_dumper_settings['add-drop-table'])) {
			$tables = is_array($tables) ? $tables : array($tables);
			
			//Drops all foreign keys constraints
			$sql .= PHP_EOL . "DROP PROCEDURE IF EXISTS dropAllDBForeignKeys;
DELIMITER ;;
CREATE PROCEDURE dropAllDBForeignKeys()
BEGIN
  DECLARE bDone INT;
  DECLARE sql_str VARCHAR(1000);

  DECLARE curs CURSOR FOR SELECT 
	  CONCAT('ALTER TABLE ', TABLE_NAME, ' DROP FOREIGN KEY ', CONSTRAINT_NAME, ';') AS 'drop_sql'
	FROM information_schema.key_column_usage 
	WHERE CONSTRAINT_SCHEMA = DATABASE() AND referenced_table_name IS NOT NULL AND TABLE_NAME in ('" . implode("', '", $tables) . "');
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET bDone = 1;

  OPEN curs;

  SET bDone = 0;
  REPEAT
    FETCH curs INTO sql_str;

    IF sql_str != '' THEN
	  SET @sql = sql_str;
	  PREPARE stmt FROM @sql;
	  EXECUTE stmt;
	  DEALLOCATE PREPARE stmt;
    END IF;
  UNTIL bDone END REPEAT;

  CLOSE curs;
END;;
DELIMITER ;

CALL dropAllDBForeignKeys();
DROP PROCEDURE IF EXISTS dropAllDBForeignKeys;" . PHP_EOL;
		}

		return $sql;
	}

	public function endDisableConstraintsAndTriggersStmt($tables) {
		//Re-enables foreign keys for all tables
		return "SET foreign_key_checks = 1;" . PHP_EOL;
	}
}
?>
