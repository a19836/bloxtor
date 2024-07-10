<?php
namespace __system\businesslogic;

if (!class_exists("\__system\businesslogic\CommonService")) {
	include_once get_lib("org.phpframework.PHPFrameWorkHandler");
	
	/**
	 * @hidden
	 */
	abstract class CommonService {
		private $BusinessLogicLayer;
		private $UserCacheHandler;
		private $PHPFrameWorkHandler;
		
		private $options;
		
		private static $db_lib_included;
	
		/**
		 * @hidden
		 */
		public function __construct() {
			$this->PHPFrameWorkHandler = new \PHPFrameWorkHandler();
		}
	
		/**
		 * @hidden
		 */
		public function setPHPFrameWorkObjName($phpframework_obj_name) {$this->PHPFrameWorkHandler->setPHPFrameWorkObjName($phpframework_obj_name);}
	
		/**
		 * @hidden
		 */
		public function setUserCacheHandler($UserCacheHandler) {$this->UserCacheHandler = $UserCacheHandler;}
		/**
		 * @hidden
		 */
		public function getUserCacheHandler() {return $this->UserCacheHandler;}
	
		/**
		 * @hidden
		 */
		public function setBusinessLogicLayer($BusinessLogicLayer) {$this->BusinessLogicLayer = $BusinessLogicLayer;}
		/**
		 * @hidden
		 */
		public function getBusinessLogicLayer() {return $this->BusinessLogicLayer;}
	
		/**
		 * @hidden
		 */
		public function getBroker($broker_name_or_options = false) {
			$broker_name = is_array($broker_name_or_options) ? (isset($broker_name_or_options["dal_broker"]) ? $broker_name_or_options["dal_broker"] : null) : $broker_name_or_options;
		
			return $this->getBusinessLogicLayer()->getBroker($broker_name);
		}
	
		/**
		 * @hidden
		 * set the options of to the current called service
		 */
		public function setOptions($options) {$this->options = $options;}
		/**
		 * @hidden
		 * get the options of to the current called service
		 */
		public function getOptions() {return $this->options;}
		
		/**
		 * @hidden
		 * merge the argument $options with the correspondent options of the current called service.
		 */
		public function mergeOptionsWithBusinessLogicLayer(&$options) {
			$cso = $this->getOptions();
			
			if ($cso) { // Merge db_driver and no_cache options
				if ($options)
					$options = array_merge(is_array($options) ? $options : array($options), $cso);
				else
					$options = $cso;
			}
		}
		
		/**
		 * @hidden
		 */
		public static function getReservedSQLKeywords() {
			//return array("START_PAGINATION", "END_PAGINATION", "SIMPLE_PAGINATION", "START_SORTING", "END_SORTING", "SIMPLE_SORTING", "SEARCHING_CONDITION");
			return array("searching_condition");
		}
	
		/**
		 * @hidden
		 */
		protected static function prepareInputData(&$data, $default_table_name = "") {
			self::prepareSearch($data);
			
			if ($data && !empty($data["conditions"])) {
				if (!self::$db_lib_included)
					include_once get_lib("org.phpframework.db.DB"); //leave this here, otherwise it could be over-loading for every request to include without need it...
				
				self::$db_lib_included = true;
				
				$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
				$cond = \DB::getSQLConditions($data["conditions"], $conditions_join, $default_table_name);
				
				if ($cond)
					$data["searching_condition"] = (!empty($data["searching_condition"]) ? $data["searching_condition"] : "") . " and (" . $cond . ")";
			}
		}
	
		/**
		 * Parse the $data[conditions] according with the $parser function. This is very useful to change attributes names from conditions or add the correspondent table prefix.
		 * @hidden
		 */
		protected static function prepareInputConditionsData(&$conditions, $parser_func) {
			if (is_array($conditions) && $parser_func)	{
				foreach ($conditions as $attribute_name => $attribute_value) {
					$lower_attr_name = strtolower($attribute_name);

					if ($lower_attr_name == "or" || $lower_attr_name == "and") {
						if (is_array($attribute_value))
							foreach ($attribute_value as $sub_attribute_name => $sub_attribute_value) {
								$sub_attr_name = $sub_attribute_name;
								$sub_attr_value = $sub_attribute_value;
								
								$parser_func($sub_attr_name, $sub_attr_value);
								
								if ($sub_attr_name !== $sub_attribute_name) {
									unset($conditions[$attribute_name][$sub_attribute_name]);
									$conditions[$attribute_name][$sub_attr_name] = $sub_attr_value;
								}
								else	if ($sub_attr_value !== $sub_attribute_value)
									$conditions[$attribute_name][$sub_attr_name] = $sub_attr_value;
							}
					}
					else {
						$attr_name = $attribute_name;
						$attr_value = $attribute_value;
						
						$parser_func($attr_name, $attr_value);
						
						if ($attr_name !== $attribute_name) {
							unset($conditions[$attribute_name]);
							$conditions[$attr_name] = $attr_value;
						}
						else if ($attr_value !== $attribute_value)
							$conditions[$attr_name] = $attr_value;
					}
				}
			}
		}
	
		/**
		 * @hidden
		 */
		protected static function prepareSearch(&$data) {
			$condition = "";
		
			if ($data && !empty($data["searching"]) && is_array($data["searching"]) && !empty($data["searching"]["fields"])) {
				if (!self::$db_lib_included)
					include_once get_lib("org.phpframework.db.DB"); //leave this here, otherwise it could be over-loading for every request to include without need it...
				
				self::$db_lib_included = true;
				
				$search_fields = isset($data["searching"]["fields"]) ? $data["searching"]["fields"] : null;
				$search_values = isset($data["searching"]["values"]) ? $data["searching"]["values"] : null;
				$search_types = isset($data["searching"]["types"]) ? $data["searching"]["types"] : null;
				
				$t = count($search_fields);
				for ($i = 0; $i < $t; $i++) {
					$field = $search_fields[$i];
					$value = isset($search_values[$i]) ? strtolower($search_values[$i]) : "";
					$type = isset($search_types[$i]) ? $search_types[$i] : null;
					
					$condition .= ($condition ? " and " : "") . "lower(" . \SQLQueryHandler::getParsedSqlColumnName($field) . ")";
				
					$with_quote = !is_numeric($value) ? "'" : "";
					$value = addcslashes($value, "\\'");
				
					switch ($type) {
						case "contains": 
							$condition .= " like '%$value%'";
							break;
					
						case "starts": 
							$condition .= " like '$value%'";
							break;
					
						case "ends": 
							$condition .= " like '%$value'";
							break;
					
						case "equal": 
							$condition .= "={$with_quote}$value{$with_quote}";
							break;
					
						case "bigger": 
							$condition .= ">{$with_quote}$value{$with_quote}";
							break;
					
						case "smaller": 
							$condition .= "<{$with_quote}$value{$with_quote}";
							break;
					
						default:
							 $condition .= "={$with_quote}$value{$with_quote}";
					}
				}
			}
		
			$data["searching_condition"] = $condition ? " and " . $condition : "";
		}
	}
}
?>
