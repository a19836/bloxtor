<?php
class SQLException extends Exception {
	public $problem;

	public function __construct($error_num, $e, $value = array()) {
		switch($error_num) {
			case 1: 
				$options = $value;
				$opt_str = "";
				if (is_array($options))
					foreach ($options as $k => $v) {
						$opt_str .= ($opt_str ? "; " : "") . "$k=";
						
						if (strtolower($k) == "password")
							$opt_str .= strlen($v) ? "***" : "";
						else
							$opt_str .= is_array($v) ? "[" . implode(", ", $v) . "]" : (is_object($v) ? "Object(" . get_class($v) . ")" : $v);
					}
				
				$this->problem = "DB connection fail with options: $opt_str"; 
				break;
			case 2: $this->problem = "ERROR selecting DB: " . (isset($value[0]) ? $value[0] : null); break;
			case 3: $this->problem = "ERROR cosing DB connection."; break;
			case 4: $this->problem = "ERROR returning DB errno." . (isset($value[0]) ? $value[0] : null); break;
			case 5: $this->problem = "ERROR returning DB error." . (isset($value[0]) ? $value[0] : null); break;
			case 6: $this->problem = "ERROR executing query: " . (isset($value[0]) ? $value[0] : null); break;
			case 7: $this->problem = "ERROR to free result: " . (isset($value[0]) ? $value[0] : null); break;
			case 8: $this->problem = "ERROR fetching result to array. Result:" . (isset($value[0]) ? $value[0] : null) . ". Array type:" . (isset($value[1]) ? $value[1] : null); break;
			case 9: $this->problem = "ERROR fetching result to row. Result:" . (isset($value[0]) ? $value[0] : null); break;
			case 10: $this->problem = "ERROR fetching result to assoc array. Result:" . (isset($value[0]) ? $value[0] : null); break;
			case 11: $this->problem = "ERROR fetching result to object. Result:" . (isset($value[0]) ? $value[0] : null); break;
			case 12: $this->problem = "ERROR fetching field. Result:" . (isset($value[0]) ? $value[0] : null) . ". Offset:" . (isset($value[1]) ? $value[1] : null); break;
			case 13: $this->problem = "ERROR getting num rows. Result:" . (isset($value[0]) ? $value[0] : null); break;
			case 14: $this->problem = "ERROR getting num fields. Result:" . (isset($value[0]) ? $value[0] : null); break;
			case 15: $this->problem = "ERROR in DB->getData(). SQL:" . (isset($value[0]) ? $value[0] : null); break;
			case 16: $this->problem = "ERROR in DB->setData(sql). SQL:" . (isset($value[0]) ? $value[0] : null); break;
			case 17: $this->problem = "ERROR: Query result null. SQL:" . (isset($value[0]) ? $value[0] : null); break;
			case 18: $this->problem = "ERROR: DB Driver incorrect options. Host, username and db_name are mandatory! Your options were:[" . implode("', '", $value) . "]"; break;
			case 19: $this->problem = "ERROR: DB name is undefined in query: " . $value; break;
			case 20: $this->problem = "ERROR in DB->setCharset(" . $value . ")"; break;
			case 21: $this->problem = "ERROR checking getData resourcing for SQL:" . (isset($value[0]) ? $value[0] : null); break;
		}
		
		if (!empty($e)) {
			if (is_string($e)) {
				$this->problem .= "\n\n\n\n*** NATIVE ERROR ***\n\n$e";
				parent::__construct($e, $error_num, null);
			}
			else {
				if ($e->problem)
					$this->problem = $e->problem . PHP_EOL . $this->problem;
				
				parent::__construct($e->getMessage(), $error_num, $e);
			}
			//$this->problem .= "<br><br>EXCEPTION: " . $e;
		}
	}
}
?>
