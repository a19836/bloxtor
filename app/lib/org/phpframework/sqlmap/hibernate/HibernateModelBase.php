<?php
include_once get_lib("org.phpframework.sqlmap.ibatis.IBatisClient");
include_once get_lib("org.phpframework.sqlmap.SQLMapIncludesHandler");
include_once get_lib("org.phpframework.sqlmap.hibernate.HibernateModelCache");

class HibernateModelBase extends SQLMap {
	protected $RDBBroker;
	protected $QueryHandler;
	protected $ResultHandler;
	protected $SQLClient;
	
	protected $CacheLayer;
	protected $HibernateModelCache;
	
	protected $obj_name;
	protected $table_name;
	protected $extend_class_name;
	protected $extend_class_path;
	
	protected $parameter_class;
	protected $parameter_map;
	protected $result_class;
	protected $result_map;
	
	protected $ids = array();
	protected $table_attributes = array();
	protected $many_to_one = array();
	protected $many_to_many = array();
	protected $one_to_many = array();
	protected $one_to_one = array();
	protected $queries = array();
	protected $properties_to_attributes = array();
	
	protected $module_id;
	protected $service_id;
			
	public function __construct() {
		parent::__construct();
		
		$this->QueryHandler = new SQLMapQueryHandler();
		$this->ResultHandler = new SQLMapResultHandler();
		$this->SQLClient = new IBatisClient();
		
		$this->HibernateModelCache = new HibernateModelCache();
	}
	
	/*********** HIBERNATE ************/
	
	protected function prepareIdsToInsert(&$data, $options = false) {
		foreach($this->ids as $key => $value) {
			$attr_name = isset($value["output_name"]) ? $value["output_name"] : null;
			$type = isset($value["generator"]["type"]) ? $value["generator"]["type"] : "";
			$id_default_value = isset($data[$attr_name]) ? $data[$attr_name] : null;
			//echo "id_default_value:$id_default_value<br>";
			//echo "type:$type<br>";
		
			if($type == "hidden") {
				if (array_key_exists($attr_name, $data)) //2020-01-24: Do not use isset here bc if null, the key will continue to exist
					unset($data[$attr_name]);
			}
			else if($type) {
				$id = false;
				switch($type) {
					case "assign": 
						$id = $id_default_value; 
						break;
					case "increment": 
					case "select": 
					case "procedure": 
						if ($type == "increment")
							$id = $this->RDBBroker->findObjectsColumnMax($this->table_name, $attr_name) + 1;
						else {
							$sql = isset($value["generator"]["value"]) ? $value["generator"]["value"] : null;
							
							if ($sql) {
								$result = $this->RDBBroker->getData($sql, $options);
								$result = isset($result["result"][0]) ? $result["result"][0] : null;
								
								if (is_array($result)) {
									$keys = array_keys($result);
									$id = count($keys) > 0 ? $result[ $keys[0] ] : null;
								}
							}
						}
						break;
					case "md5": 
						$id = md5(microtime(true));
						break;
				}
				
				$data[$attr_name] = $id;
			}
			else if (!empty($this->table_attributes[$attr_name]) && 
					!empty($this->table_attributes[$attr_name]["primary_key"]) && 
					!empty($this->table_attributes[$attr_name]["auto_increment"]) && 
					( !isset($data[$attr_name]) || !strlen("" . $data[$attr_name]) )
			) { //2021-08-12: check if is auto_increment PK and there is no value passed, and if it is unset($data[$attr_name]); bc the DB will take care of it
				
				if (array_key_exists($attr_name, $data)) //2021-08-12: Do not use isset here bc if null, the key will continue to exist
					unset($data[$attr_name]);
			}
			else
				$data[$attr_name] = $id_default_value;
		}
	} 
	
	/*********** IBATIS ************/
	
	public function setNodesData($nodes_data) {
		$this->SQLClient->setNodesData($nodes_data);
	}
	
	/*********** OPERATORS ************/
	
	public function setRDBBroker($RDBBroker) {
		$this->RDBBroker = $RDBBroker;
		$this->SQLClient->setRDBBroker($RDBBroker);
	}
	public function getRDBBroker() {return $this->RDBBroker;}
	
	public function setCacheLayer($CacheLayer) {
		$this->CacheLayer = $CacheLayer;
		
		$this->HibernateModelCache->initCacheDirPath($this->CacheLayer->getCachedDirPath());
	}
	public function getCacheLayer() {return $this->CacheLayer;}

	public function setObjName($obj_name) {$this->obj_name = $obj_name;}
	public function getObjName() {return $this->obj_name;}
	
	public function setTableName($table_name) {$this->table_name = $table_name;}
	public function getTableName() {return $this->table_name;}
	
	public function setExtendClassName($extend_class_name) {$this->extend_class_name = $extend_class_name;}
	public function getExtendClassName() {return $this->extend_class_name;}
	
	public function setExtendClassPath($extend_class_path) {$this->extend_class_path = $extend_class_path;}
	public function getExtendClassPath() {return $this->extend_class_path;}
	
	public function setIds($ids) {$this->ids = is_array($ids) ? $ids : array();}
	public function getIds() {return $this->ids;}
	
	public function setParameterClass($parameter_class) {$this->parameter_class = $parameter_class;}
	public function getParameterClass() {return $this->parameter_class;}
	
	public function setParameterMap($parameter_map) {$this->parameter_map = $parameter_map;}
	public function getParameterMap() {return $this->parameter_map;}
	
	public function setResultClass($result_class) {$this->result_class = $result_class;}
	public function getResultClass() {return $this->result_class;}
	
	public function setResultMap($result_map) {$this->result_map = $result_map;}
	public function getResultMap() {return $this->result_map;}
	
	public function setTableAttributes($table_attributes) {$this->table_attributes = is_array($table_attributes) ? $table_attributes : array();}//get attributes from the DB
	public function getTableAttributes() {return $this->table_attributes;}//get attributes from the DB
	
	public function setManyToOne($many_to_one) {$this->many_to_one = is_array($many_to_one) ? $many_to_one : array();}
	public function getManyToOne() {return $this->many_to_one;}
	
	public function setManyToMany($many_to_many) {$this->many_to_many = is_array($many_to_many) ? $many_to_many : array();}
	public function getManyToMany() {return $this->many_to_many;}
	
	public function setOneToMany($one_to_many) {$this->one_to_many = is_array($one_to_many) ? $one_to_many : array();}
	public function getOneToMany() {return $this->one_to_many;}
	
	public function setOneToOne($one_to_one) {$this->one_to_one = is_array($one_to_one) ? $one_to_one : array();}
	public function getOneToOne() {return $this->one_to_one;}
	
	public function setQueries($queries) {$this->queries = is_array($queries) ? $queries : array();}
	public function getQueries() {return $this->queries;}
	
	public function setPropertiesToAttributes($properties_to_attributes) {$this->properties_to_attributes = is_array($properties_to_attributes) ? $properties_to_attributes : array();}
	public function getPropertiesToAttributes() {return $this->properties_to_attributes;}
	
	public function setModuleId($module_id) {$this->module_id = $module_id;}
	public function getModuleId() {return $this->module_id;}
	
	public function setServiceId($service_id) {$this->service_id = $service_id;}
	public function getServiceId() {return $this->service_id;}
}
?>
