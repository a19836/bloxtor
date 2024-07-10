<?php
include get_lib("org.phpframework.sqlmap.hibernate.HibernateModelBase");
include get_lib("org.phpframework.sqlmap.hibernate.IHibernateModel");
include_once get_lib("org.phpframework.object.ObjTypeHandler");

class HibernateModel extends HibernateModelBase implements IHibernateModel {
	private $default_options = array();
	
	public function __construct() {
		parent::__construct();
	}
	
	/*********** HIBERNATE ************/
	
	public function insert($data, &$ids = false, $options = false) {
		$this->prepareOptions($options);
		$cached_data = $data;
		
		$this->QueryHandler->transformData($data, $this->parameter_class, $this->parameter_map);
		$this->filterInvalidAttributes($data);
		
		$status = false;
		
		if (is_array($data)) {
			$this->prepareIdsToInsert($data, $options);
			
			/*$data_ids = array();
			foreach ($this->ids as $key => $value)
				if (isset($data[ $value["output_name"] ]))
					$data_ids[] = $value["output_name"];
			
			foreach($data as $key => $value)
				if (!isset($this->table_attributes[$key]))
					unset($data[$key]);
			*/
			
			if ($this->getErrorHandler()->ok()) {
				//if data doesn't contain any attribute called created_date or modified_date, sets it with current date
				/* This is done from the business logic level
				foreach ($this->table_attributes as $attr_name => $attr_props)
					if ((ObjTypeHandler::isDBAttributeNameACreatedDate($attr_name) || ObjTypeHandler::isDBAttributeNameAModifiedDate($attr_name)) && !array_key_exists($attr_name, $data))
						$data[$attr_name] = date("Y-m-d H:i:s");
				*/
				
				$data = $this->convertNumericAttributesStringValues($data); //convert numeric string into numeric fields if apply
				
				$status = $this->getFunction("insertObject", array($this->table_name, $data, $options), $options);
				
				if ($status === true) {
					$ids = array();
					foreach ($this->ids as $key => $value) {
						$ids[$key] = !empty($data[$key]) ? $data[$key] : $this->getInsertedId($options);//postgres needs the sequence name, so this function will not work in here
					}
				}
			}
		}
		
		if($this->isCacheActive())
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".insert", $cached_data, $status, $options);
		
		return $status;
	}
	
	public function insertAll($data, &$statuses = false, &$ids = false, $options = false) {
		$this->prepareOptions($options);
		
		$status = true;
		$statuses = array();
		$ids = array();
		$t = $data ? count($data) : 0;
		for($i = 0; $i < $t; $i++) {
			$statuses[$i] = $this->insert($data[$i], $ids_i, $options);
			$ids[$i] = $ids_i;
			if($statuses[$i] !== true)
				$status = false;
		}
		
		if($this->isCacheActive())
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".insertAll", $data, $status, $options);
		
		return $status;
	}
	
	public function update($data, $options = false) {
		$this->prepareOptions($options);
		$cached_data = $data;
		
		$status = $this->ids ? $this->updateWithPKs($data, $options) : $this->updateWithoutPKs($data, $options);
		
		if ($this->isCacheActive()) {
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".update", $cached_data, $status, $options);
		}
		
		return $status;
	}
	
	private function updateWithPKs($data, $options = false) {
		$status = false;
		
		$this->QueryHandler->transformData($data, $this->parameter_class, $this->parameter_map);
		$this->filterInvalidAttributes($data);
		
		if (is_array($data)) {
			$data_ids = array();
			foreach ($this->ids as $key => $value) {
				$output_name = isset($value["output_name"]) ? $value["output_name"] : null;
				
				if (isset($data[$output_name])) 
					$data_ids[] = $output_name;
			}
			
			if (count($data_ids) > 0) {
				$conditions = array();
				foreach ($data as $key => $value)
					if (in_array($key, $data_ids))
						$conditions[$key] = $value;
				
				$attributes = array();
				foreach ($data as $key => $value)
					if (!in_array($key, $data_ids))
						$attributes[$key] = $value;
					
				if ($this->getErrorHandler()->ok()) {
					//if data doesn't contain any attribute called modified_date, sets it with current date
					/* This is done from the business logic level
					foreach ($this->table_attributes as $attr_name => $attr_props)
						if (ObjTypeHandler::isDBAttributeNameAModifiedDate($attr_name) && !array_key_exists($attr_name, $attributes))
							$attributes[$attr_name] = date("Y-m-d H:i:s");
					*/
					
					$attributes = $this->convertNumericAttributesStringValues($attributes); //convert numeric string into numeric fields if apply
					
					$opts = is_array($options) ? $options : array();
					$opts["conditions_join"] = "and";
					$opts["all"] = true;
					
					$status = $this->getFunction("updateObject", array($this->table_name, $attributes, $conditions, $opts), $options);
				}
			}
		}
		
		return $status;
	}
	
	private function updateWithoutPKs($data, $options = false) {
		$status = false;
		
		if (is_array($data)) {
			$attributes = array();
			$conditions = array();
			
			foreach ($data as $key => $value) {
				if (substr($key, 0, 4) == "old_")
					$conditions[ substr($key, 4) ] = $value;
				else if (substr($key, 0, 4) == "new_")
					$attributes[ substr($key, 4) ] = $value;
				else if (!array_key_exists($key, $attributes))
					$attributes[$key] = $value;
			}
			
			$this->QueryHandler->transformData($attributes, $this->parameter_class, $this->parameter_map);
			$this->filterInvalidAttributes($attributes);
			
			$this->QueryHandler->transformData($conditions, $this->parameter_class, $this->parameter_map);
			$this->filterInvalidAttributes($conditions);
			
			if(count($attributes) > 0 && count($conditions) > 0 && $this->getErrorHandler()->ok()) {
				//if data doesn't contain any attribute called modified_date, sets it with current date
				/* This is done from the business logic level
				foreach ($this->table_attributes as $attr_name => $attr_props)
					if (ObjTypeHandler::isDBAttributeNameAModifiedDate($attr_name) && !array_key_exists($attr_name, $attributes))
						$attributes[$attr_name] = date("Y-m-d H:i:s");
				*/
				
				$attributes = $this->convertNumericAttributesStringValues($attributes); //convert numeric string into numeric fields if apply
				
				$opts = is_array($options) ? $options : array();
				$opts["conditions_join"] = "and";
				$opts["all"] = true;
				
				$status = $this->getFunction("updateObject", array($this->table_name, $attributes, $conditions, $opts), $options);
			}
		}
		
		return $status;
	}
	
	public function updateAll($data, &$statuses = false, $options = false) {
		$this->prepareOptions($options);
		
		$status = true;
		$statuses = array();
		$t = $data ? count($data) : 0;
		for ($i = 0; $i < $t; $i++) {
			$statuses[$i] = $this->update($data[$i], $options);
			
			if ($statuses[$i] !== true) 
				$status = false;
		}
		
		if ($this->isCacheActive()) {
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".updateAll", $data, $status, $options);
		}
		
		return $status;
	}
	
	public function insertOrUpdate($data, &$ids = false, $options = false) {
		$this->prepareOptions($options);
		$data_aux = $data;
		
		$this->QueryHandler->transformData($data_aux, $this->parameter_class, $this->parameter_map);
		$this->filterInvalidAttributes($data_aux);
		
		$status = false;
		if (is_array($data_aux)) {
			$pk_ids_exists = false;
			
			if ($this->ids) {
				foreach ($this->ids as $key => $value) {
					$on = isset($value["output_name"]) ? $value["output_name"] : null;
					
					if (isset($data_aux[$on]) && strlen($data_aux[$on]) > 0) {
						$pk_ids_exists = true;
						break;
					}
				}
			}
			else { //if table has no pks
				$old_data = array();
				
				foreach ($data as $key => $value) {
					if (substr($key, 0, 4) == "old_")
						$old_data[ substr($key, 4) ] = $value;
				}
				
				$this->QueryHandler->transformData($old_data, $this->parameter_class, $this->parameter_map);
				$this->filterInvalidAttributes($old_data);
				
				if (count($old_data))
					$pk_ids_exists = true;
			}
			
			$status = $pk_ids_exists ? $this->update($data, $options) : $this->insert($data, $ids, $options);
			
			//add the update items PKs
			foreach ($this->ids as $key => $value) {
				$on = isset($value["output_name"]) ? $value["output_name"] : null;
				
				if (isset($data_aux[$on]) && strlen($data_aux[$on]) > 0 && (!isset($ids[$key]) || !strlen($ids[$key])) ) {
					$ids[$key] = $data_aux[$on];
				}
			}
		}
		
		if($this->isCacheActive()) {
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".insertOrUpdate", $data, $status, $options);
		}
		
		return $status;
	}
	
	public function insertOrUpdateAll($data, &$statuses = false, &$ids = false, $options = false) {
		$this->prepareOptions($options);
		
		$status = true;
		$statuses = array();
		$ids = array();
		$t = $data ? count($data) : 0;
		for($i = 0; $i < $t; $i++) {
			$statuses[$i] = $this->insertOrUpdate($data[$i], $id_i, $options);
			$ids[$i] = $id_i;
			if($statuses[$i] !== true)
				$status = false;
		}
		
		if($this->isCacheActive()) {
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".insertOrUpdateAll", $data, $status, $options);
		}
		
		return $status;
	}
	
	public function updateByConditions($data, $options = false) {
		$this->prepareOptions($options);
		$cached_data = $data;
		
		$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$all = isset($data["all"]) ? $data["all"] : null;
		
		$this->QueryHandler->transformData($attributes, $this->parameter_class, $this->parameter_map);
		$this->filterInvalidAttributes($attributes);
		
		$this->configureAttributeConditions($conditions);
		
		$status = false;
		if(is_array($attributes)) {
			/*foreach($attributes as $key => $value)
				if (!isset($this->table_attributes[$key]))
					unset($attributes[$key]);*/
			
			if($this->getErrorHandler()->ok()) {
				$attributes = $this->convertNumericAttributesStringValues($attributes); //convert numeric string into numeric fields if apply
				
				$opts = is_array($options) ? $options : array();
				$opts["conditions_join"] = $conditions_join;
				$opts["all"] = $all;
				
				$status = $this->getFunction("updateObject", array($this->table_name, $attributes, $conditions, $opts), $options);
			}
		}
		
		if($this->isCacheActive()) {
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".updateByConditions", $cached_data, $status, $options);
		}
		
		return $status;
	}
	
	public function updatePrimaryKeys($data, $options = false) {
		$this->prepareOptions($options);
		$cached_data = $data;
		
		$old_data = array();
		$new_data = array();
		$other_data = array();
		foreach ($data as $key => $value) {
			if (substr($key, 0, 4) == "old_")
				$old_data[ substr($key, 4) ] = $value;
			else if (substr($key, 0, 4) == "new_")
				$new_data[ substr($key, 4) ] = $value;
			else 
				$other_data[$key] = $value;
		}
		
		$this->QueryHandler->transformData($old_data, $this->parameter_class, $this->parameter_map);
		$this->filterInvalidAttributes($old_data);
		
		$this->QueryHandler->transformData($new_data, $this->parameter_class, $this->parameter_map);
		$this->filterInvalidAttributes($new_data);
		
		$this->QueryHandler->transformData($other_data, $this->parameter_class, $this->parameter_map);
		$this->filterInvalidAttributes($other_data);
		
		$status = false;
		if(is_array($old_data) && is_array($new_data)) {
			$old_data_ids = array();
			$new_data_ids = array();
			foreach($this->ids as $key => $value) {
				$on = isset($value["output_name"]) ? $value["output_name"] : null;
				
				if (isset($old_data[$on]))
					$old_data_ids[] = $on;
				
				if (isset($new_data[$on]))
					$new_data_ids[] = $on;
				
				if (array_key_exists($on, $other_data)) //2020-01-24: Do not use isset here bc if null, the key will continue to exist
					unset($other_data[$on]);
			}
			
			if(count($old_data_ids) > 0 && count($new_data_ids) > 0) {
				$attributes = array();
				foreach($new_data as $key => $value)
					if (/*isset($this->table_attributes[$key]) && */in_array($key, $new_data_ids))
						$attributes[$key] = $value;
				
				//add other attributes that may exist...
				foreach($other_data as $key => $value)
					if (/*isset($this->table_attributes[$key]) && */isset($other_data[$key]))
						$attributes[$key] = $value;
				
				$conditions = array();
				foreach($old_data as $key => $value)
					if (/*isset($this->table_attributes[$key]) && */in_array($key, $old_data_ids))
						$conditions[$key] = $value;
				
				if($this->getErrorHandler()->ok()) {
					$attributes = $this->convertNumericAttributesStringValues($attributes); //convert numeric string into numeric fields if apply
					
					$opts = is_array($options) ? $options : array();
					$opts["conditions_join"] = "and";
					$opts["all"] = false;
					
					$status = $this->getFunction("updateObject", array($this->table_name, $attributes, $conditions, $opts), $options);
				}
			}
		}
		
		if($this->isCacheActive()) {
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".updatePrimaryKeys", $cached_data, $status, $options);
		}
		
		return $status;
	}
	
	public function delete($data, $options = false) {
		$this->prepareOptions($options);
		$cached_data = $data;
		
		/************************************* START: PREPARE DATA *****************************************/
		if (is_array($data) || is_object($data)) {
			$this->QueryHandler->transformData($data, $this->parameter_class, $this->parameter_map);
			$this->filterInvalidAttributes($data);
		}
		
		$conditions = $this->getConfiguredIdsParameter($data);
		/************************************* END: PREPARE DATA *****************************************/
		
		$status = false;
		if (count(array_keys($conditions)) && $this->getErrorHandler()->ok()) {
			$opts = is_array($options) ? $options : array();
			$opts["conditions_join"] = "and";
			$opts["all"] = false;
			
			$status = $this->getFunction("deleteObject", array($this->table_name, $conditions, $opts), $options);
		}
		
		if($this->isCacheActive()) {
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".delete", $cached_data, $status, $options);
		}
		
		return $status;
	}
	
	public function deleteAll($data, &$statuses = false, $options = false) {
		$this->prepareOptions($options);
		
		$status = true;
		$statuses = array();
		$t = $data ? count($data) : 0;
		for ($i = 0; $i < $t; $i++) {
			$statuses[$i] = $this->delete($data[$i], $options);
			
			if ($statuses[$i] !== true) {
				$status = false;
			}
		}
		
		if($this->isCacheActive()) {
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".deleteAll", $data, $status, $options);
		}
		
		return $status;
	}
	
	public function deleteByConditions($data, $options = false) {
		$this->prepareOptions($options);
		$cached_data = $data;
		
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$all = isset($data["all"]) ? $data["all"] : null;
		
		$this->configureAttributeConditions($conditions);
		
		$status = false;
		
		if ($this->getErrorHandler()->ok() && $sql) {
			$opts = is_array($options) ? $options : array();
			$opts["conditions_join"] = $conditions_join;
			$opts["all"] = $all;
			
			$status = $this->getFunction("deleteObject", array($this->table_name, $conditions, $opts), $options);
		}
		
		if($this->isCacheActive()) {
			$this->getCacheLayer()->check($this->module_id, $this->service_id.".deleteByConditions", $cached_data, $status, $options);
		}
		
		return $status;
	}
	
	public function findById($ids, $data = array(), $options = false) {
		$this->prepareOptions($options);
		
		$data = is_array($data) ? $data : array();
		MyArray::arrKeysToLowerCase($data);
		$relationships = isset($data["relationships"]) ? $data["relationships"] : null;
		
		$service_id = $this->service_id.".findById";
		$cache_conditions = array($ids, $data);
		
		$is_cache_active = $this->isCacheActive();
		
		if($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($this->module_id, $service_id, $cache_conditions, $options)) {
			$includes = SQLMapIncludesHandler::getLibsOfResultClassAndMap($this->result_class, $this->result_map);
			if($relationships) {
				$includes_otm = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->one_to_many);
				$includes_mtm = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->many_to_many);
				$includes_mto = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->many_to_one);
				$includes_oto = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->one_to_one);
				$includes = array_merge($includes, $includes_otm, $includes_mtm, $includes_mto, $includes_oto);
			}
			SQLMapIncludesHandler::includeLibsOfResultClassAndMap($includes);
			
			return $this->getCacheLayer()->get($this->module_id, $service_id, $cache_conditions, $options);
		}
		
		
		/************************************* START: PREPARE CONDITIONS *****************************************/
		if (is_array($ids) || is_object($ids)) {
			$this->QueryHandler->transformData($ids, $this->parameter_class, $this->parameter_map);
			$this->filterInvalidAttributes($ids);
		}
		
		$conditions = $this->getConfiguredIdsParameter($ids);
		/************************************* END: PREPARE CONDITIONS *****************************************/
		
		$result = false;
		if(is_array($conditions) && count(array_keys($conditions))) {
			$data["conditions"] = $conditions;
			$data["start"] = 0;
			$data["limit"] = 1;
			$results = $this->findAux($data, $options);
			$result = isset($results[0]) ? $results[0] : null;
		}
		
		if($is_cache_active) {
			$this->getCacheLayer()->check($this->module_id, $service_id, $cache_conditions, $result, $options);
		}
		
		return $result;
	}
	
	public function find($data = array(), $options = false) {
		$this->prepareOptions($options);
		
		$data = is_array($data) ? $data : array();
		MyArray::arrKeysToLowerCase($data);
		$relationships = isset($data["relationships"]) ? $data["relationships"] : null;
		
		$service_id = $this->service_id.".find";
		$cache_conditions = $data;
		
		$is_cache_active = $this->isCacheActive();
			
		if($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($this->module_id, $service_id, $cache_conditions, $options)) {
			$includes = SQLMapIncludesHandler::getLibsOfResultClassAndMap($this->result_class, $this->result_map);
			if($relationships) {
				$includes_otm = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->one_to_many);
				$includes_mtm = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->many_to_many);
				$includes_mto = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->many_to_one);
				$includes_oto = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->one_to_one);
				$includes = array_merge($includes, $includes_otm, $includes_mtm, $includes_mto, $includes_oto);
			}
			SQLMapIncludesHandler::includeLibsOfResultClassAndMap($includes);
			
			return $this->getCacheLayer()->get($this->module_id, $service_id, $cache_conditions, $options);
		}
		
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		if($conditions) {
			if(is_array($conditions) || is_object($conditions)) {
				/* This logic allows the conditions to have multiple ids with the same name, this is: 
				     $result = $ItemObj->find(array(
						"conditions" => array("id" => array(1, 2)),
						"conditions_join" => "or",
					    )
				     );
				*/
				$this->configureAttributeConditions($conditions);
			}
			
			if(!is_array($conditions)) {
				$conditions = $this->getConfiguredIdsParameter($conditions);
			}
		}
		
		$data["conditions"] = $conditions;
		$results = $this->findAux($data, $options);
		
		if($is_cache_active) {
			$this->getCacheLayer()->check($this->module_id, $service_id, $cache_conditions, $results, $options);
		}
		
		return $results;
	}
	
	public function count($data = array(), $options = false) {
		$this->prepareOptions($options);
		
		$data = is_array($data) ? $data : array();
		MyArray::arrKeysToLowerCase($data);
		unset($data["relationships"]);
		unset($data["attributes"]);
		unset($data["sort"]);
		
		$service_id = $this->service_id.".count";
		$cache_conditions = $data;
		
		$is_cache_active = $this->isCacheActive();
			
		if($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($this->module_id, $service_id, $cache_conditions, $options)) {
			return $this->getCacheLayer()->get($this->module_id, $service_id, $cache_conditions, $options);
		}
		
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		if($conditions) {
			if(is_array($conditions) || is_object($conditions)) {
				$this->configureAttributeConditions($conditions);
			}
			
			if(!is_array($conditions)) {
				$conditions = $this->getConfiguredIdsParameter($conditions);
			}
		}
		
		$data["conditions"] = $conditions;
		$total = $this->countAux($data, $options);
		
		if($is_cache_active) {
			$this->getCacheLayer()->check($this->module_id, $service_id, $cache_conditions, $total, $options);
		}
		
		return $total;
	}
	
	public function findRelationships($parent_ids, $options = false) {
		$this->prepareOptions($options);
		
		$service_id = $this->service_id.".findRelationships";
		$cache_conditions = $parent_ids;
		
		$is_cache_active = $this->isCacheActive();
			
		if($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($this->module_id, $service_id, $cache_conditions, $options)) {
			$includes_otm = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->one_to_many);
			$includes_mtm = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->many_to_many);
			$includes_mto = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->many_to_one);
			$includes_oto = SQLMapIncludesHandler::getRelationshipsLibsOfResultClassAndMap($this->one_to_one);
			$includes = array_merge($includes_otm, $includes_mtm, $includes_mto, $includes_oto);
			SQLMapIncludesHandler::includeLibsOfResultClassAndMap($includes);
			
			return $this->getCacheLayer()->get($this->module_id, $service_id, $cache_conditions, $options);
		}
		
		if($parent_ids) {
			if(is_array($parent_ids) || is_object($parent_ids)) {
				$this->QueryHandler->transformData($parent_ids, $this->parameter_class, $this->parameter_map);
				$this->filterInvalidAttributes($parent_ids);
			}
			
			if(!is_array($parent_ids)) {
				$parent_ids = $this->getConfiguredIdsParameter($parent_ids);
			}
		}
		
		$rels = $this->getRelationships($parent_ids, $options);
		
		if($is_cache_active) {
			$this->getCacheLayer()->check($this->module_id, $service_id, $cache_conditions, $rels, $options);
		}
		return $rels;
	}
	
	public function findRelationship($rel_name, $parent_ids, $options = false) {
		$rel_elm = false;
		if (isset($this->one_to_many[$rel_name])) {
			$rel_elm = $this->one_to_many[$rel_name];
		}
		else if (isset($this->many_to_many[$rel_name])) {
			$rel_elm = $this->many_to_many[$rel_name];
		}
		else if (isset($this->many_to_one[$rel_name])) {
			$rel_elm = $this->many_to_one[$rel_name];
		}
		else if (isset($this->one_to_one[$rel_name])) {
			$rel_elm = $this->one_to_one[$rel_name];
		}
		
		if ($rel_elm) {
			$this->prepareOptions($options);
			
			$service_id = $this->service_id.".findRelationship.".$rel_name;
			$cache_conditions = array($rel_name, $parent_ids);
			
			$is_cache_active = $this->isCacheActive();
			
			if($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($this->module_id, $service_id, $cache_conditions, $options)) {
				$result_class = isset($rel_elm["result_class"]) ? $rel_elm["result_class"] : null;
				$result_map = isset($rel_elm["result_map"]) ? $rel_elm["result_map"] : null;
				
				$includes = SQLMapIncludesHandler::getLibsOfResultClassAndMap($result_class, $result_map);
				SQLMapIncludesHandler::includeLibsOfResultClassAndMap($includes);
				
				return $this->getCacheLayer()->get($this->module_id, $service_id, $cache_conditions, $options);
			}
			
			if($parent_ids) {
				if(is_array($parent_ids) || is_object($parent_ids)) {
					$this->QueryHandler->transformData($parent_ids, $this->parameter_class, $this->parameter_map);
					$this->filterInvalidAttributes($parent_ids);
				}
				
				if(!is_array($parent_ids)) {
					$parent_ids = $this->getConfiguredIdsParameter($parent_ids);
				}
			}
			
			$result = $this->getRelationship($rel_name, $rel_elm, $parent_ids, $options);
			
			if($is_cache_active) {
				$this->getCacheLayer()->check($this->module_id, $service_id, $cache_conditions, $result, $options);
			}
			return $result;
		}
		return false;
	}
	
	public function countRelationships($parent_ids, $options = false) {
		$this->prepareOptions($options);
		
		$service_id = $this->service_id.".countRelationships";
		$cache_conditions = $parent_ids;
		
		$is_cache_active = $this->isCacheActive();
			
		if($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($this->module_id, $service_id, $cache_conditions, $options)) {
			return $this->getCacheLayer()->get($this->module_id, $service_id, $cache_conditions, $options);
		}
		
		if($parent_ids) {
			if(is_array($parent_ids) || is_object($parent_ids)) {
				$this->QueryHandler->transformData($parent_ids, $this->parameter_class, $this->parameter_map);
				$this->filterInvalidAttributes($parent_ids);
			}
			
			if(!is_array($parent_ids)) {
				$parent_ids = $this->getConfiguredIdsParameter($parent_ids);
			}
		}
		
		$rels = $this->getRelationshipsCount($parent_ids, $options);
		
		if($is_cache_active) {
			$this->getCacheLayer()->check($this->module_id, $service_id, $cache_conditions, $rels, $options);
		}
		return $rels;
	}
	
	public function countRelationship($rel_name, $parent_ids, $options = false) {
		$rel_elm = false;
		if (isset($this->one_to_many[$rel_name])) {
			$rel_elm = $this->one_to_many[$rel_name];
		}
		else if (isset($this->many_to_many[$rel_name])) {
			$rel_elm = $this->many_to_many[$rel_name];
		}
		else if (isset($this->many_to_one[$rel_name])) {
			$rel_elm = $this->many_to_one[$rel_name];
		}
		else if (isset($this->one_to_one[$rel_name])) {
			$rel_elm = $this->one_to_one[$rel_name];
		}
		
		if ($rel_elm) {
			$this->prepareOptions($options);
			
			$service_id = $this->service_id.".countRelationship.".$rel_name;
			$cache_conditions = array($rel_name, $parent_ids);
			
			$is_cache_active = $this->isCacheActive();
			
			if($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($this->module_id, $service_id, $cache_conditions, $options)) {
				return $this->getCacheLayer()->get($this->module_id, $service_id, $cache_conditions, $options);
			}
			
			if($parent_ids) {
				if(is_array($parent_ids) || is_object($parent_ids)) {
					$this->QueryHandler->transformData($parent_ids, $this->parameter_class, $this->parameter_map);
					$this->filterInvalidAttributes($parent_ids);
				}
				
				if(!is_array($parent_ids)) {
					$parent_ids = $this->getConfiguredIdsParameter($parent_ids);
				}
			}
			
			$total = $this->getRelationshipCount($rel_name, $rel_elm, $parent_ids, $options);
			
			if($is_cache_active) {
				$this->getCacheLayer()->check($this->module_id, $service_id, $cache_conditions, $total, $options);
			}
			return $total;
		}
		return false;
	}
	
	/**************************** PRIVATE - AUXILIARES ***************************/
	
	private function getConfiguredIdsParameter($data) {
		$conditions = array();
		$data = is_array($data) ? $data : array($data);
		$ids_name = array();
		
		if ($this->ids) {
			foreach($this->ids as $value) {
				$ids_name[] = isset($value["output_name"]) ? $value["output_name"] : null;
			}
		}
		else { //if table has no pks
			foreach($this->properties_to_attributes as $key => $value) {
				if (!ObjTypeHandler::isDBAttributeNameACreatedDate($value) && !ObjTypeHandler::isDBAttributeNameAModifiedDate($value) && !ObjTypeHandler::isDBAttributeNameACreatedUserId($value) && !ObjTypeHandler::isDBAttributeNameAModifiedUserId($value)) //if attr_name == created_date or modified_date or created_user_id or modified_user_id, ignore it, because we don't want to use this attr as pks, since it changes automatically
				$ids_name[] = $value;
			}
		}
		
		foreach($data as $key => $value) {
			if(is_numeric($key)) {
				$key = isset($ids_name[$key]) ? $key : 0;
				if(!empty($ids_name[$key])) {
					$conditions[ $ids_name[$key] ] = $value;
				}
			}
			else if(in_array($key, $ids_name)) {
				$conditions[$key] = $value;
			}
			unset($ids_name[$key]);
		}
		
		return $conditions;
	}
		
	private function findAux($data = array(), $options = false) {
		$relationships = isset($data["relationships"]) ? $data["relationships"] : null;
		$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$sorts = !empty($options["sort"]) && empty($data["sort"]) ? $options["sort"] : (isset($data["sort"]) ? $data["sort"] : null);
		$start = !empty($options["start"]) && (!isset($data["start"]) || !is_numeric($data["start"])) ? $options["start"] : (isset($data["start"]) ? $data["start"] : null);
		$limit = !empty($options["limit"]) && (!isset($data["limit"]) || !is_numeric($data["limit"])) ? $options["limit"] : (isset($data["limit"]) ? $data["limit"] : null);
		
		//if relationships, add pks to attributes if not there yet.
		if($relationships) {
			//add ids to the sql query
			$ids_not_in_attributes = self::getIdsNotInAttributes($attributes, $this->ids);
			$attributes = is_array($attributes) ? array_merge($attributes, $ids_not_in_attributes) : $ids_not_in_attributes;
		}
		
		//prepare attributes
		$attributes = self::convertPropertiesToAttributes($attributes, $this->properties_to_attributes);
		
		//prepare sorts
		$sorts = self::convertSortPropertiesToAttributes($sorts, $this->properties_to_attributes);
		
		//get select sql
		$sql = $this->getFunction("buildTableFindSQL", array($this->table_name, $attributes, $conditions, array(
			"conditions_join" => $conditions_join,
			"sorts" => $sorts,
		)), $options);
		
		if($this->getErrorHandler()->ok() && $sql) {
			$options_aux = !empty($options) ? $options : array();
			$options_aux["limit"] = $limit;
			$options_aux["start"] = $start;
			unset($options_aux["sort"]);
			
			$db_data = $this->getData($sql, $options_aux);
			
			if($this->getErrorHandler()->ok()) {
				$results = array();
				$t = count($db_data["result"]);
				
				for($i = 0; $i < $t; $i++) {
					$item_data = $db_data["result"][$i];
					
					$result = array();
					if($relationships) {
						//get ids from the sql result
						$obj_pk_ids = self::getObjDataIds($item_data, $this->ids);
						if($obj_pk_ids)
							$result = $this->getRelationships($obj_pk_ids, $options);
						
						//remove ids from the sql result
						$result_data = self::deleteIdsNotInAttributesFromObjData($item_data, $ids_not_in_attributes);
					}
					else 
						$result_data = $item_data;
					
					$db_data_aux = array("fields" => $db_data["fields"], "result" => array($result_data));
					$this->ResultHandler->transformData($db_data_aux, $this->result_class, $this->result_map);
					$result_data = $db_data_aux[0];
					
					if (!empty($options["separated_by_objects"]))
						$result[$this->obj_name] = $result_data;
					else if ($relationships)
						$result = array_merge($result_data, $result);
					else
						$result = $result_data;
					
					$results[] = $result;
				}
				
				return $results;
			}
		}
		
		return false;
	}
		
	private function countAux($data = array(), $options = false) {
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$conditions_join = isset($data["conditions_join"]) ? $data["conditions_join"] : null;
		$start = !empty($options["start"]) && (!isset($data["start"]) || !is_numeric($data["start"])) ? $options["start"] : (isset($data["start"]) ? $data["start"] : null);
		$limit = !empty($options["limit"]) && (!isset($data["limit"]) || !is_numeric($data["limit"])) ? $options["limit"] : (isset($data["limit"]) ? $data["limit"] : null);
		
		if($this->getErrorHandler()->ok()) {
			$opts = is_array($options) ? $options : array();
			$opts["limit"] = $limit;
			$opts["start"] = $start;
			$opts["conditions_join"] = $conditions_join;
			unset($opts["sort"]);
			
			return $this->getFunction("countObjects", array($this->table_name, $conditions, $opts), $options);
		}
		return false;
	}
	
	private function getRelationships($parent_ids, $options = false) {
		$rels = array();
		
		$items = array($this->one_to_many, $this->many_to_many, $this->many_to_one, $this->one_to_one);
		
		$t = count($items);
		for ($i = 0; $i < $t; $i++) {
			$item = $items[$i];
			
			if (is_array($item)) {
				foreach($item as $rel_name => $rel_elm) {
					$rel_obj = $this->getRelationship($rel_name, $rel_elm, $parent_ids, $options);
			
					if($rel_obj && count($rel_obj) && $rel_obj[0]) {
						$rels[$rel_name] = $rel_obj;
					}
				}
			}
		}
		
		return $rels;
	}
	
	/*
	   getRelationship: parses the following:
	   	<many_to_many name="computers" result_class="vendor.dao.MyComputer">
			<attribute name="name" column="title" table="computer" />
			<attribute column="*" table="item" />
			
			<key pcolumn="user_id" fcolumn="employee_id" ftable="employee_computer" join="left" /><!-- join can have the following values: inner, left, right -->
			<key pcolumn="id" ptable="employee_computer" fcolumn="computer_id" ftable="computer" />
			<key pcolumn="title" ptable="employee_computer">jp</key>
			<key pcolumn="title" ptable="employee_computer" join="left">jp left</key>
			<key fcolumn="computer_model" ftable="computer" value="hp" />
			<key pcolumn="id" ptable="employee_computer" fcolumn="computer_id" ftable="computer" value="1" />
			<key pcolumn="yyy" ptable="item" fcolumn="yyy" ftable="item" />
			<key pcolumn="xxx" value="xxx" />
			<key pcolumn="www" ptable="item" value="www" />
			<key fcolumn="ttt" ftable="item" value="ttt" />
			
			<condition column="user_id" operator="!=">10</condition><!-- user_id belongs to the employee table -->
			<condition column="type"><table value="computer" /><operator><![CDATA[<=]]></operator><value>100</value></condition><!-- type belongs to the computer table -->
			<condition column="xxx" table="ttt" operator="&gt;" refcolumn="yyy" reftable="www" />
			<condition><![CDATA[length(item.title) > 0 and item.status=1]]></condition>
			
			<group_by column="id" table="item" />
			<group_by column="id" table="item" having="max(item.id) = 1" />
			<group_by column="id" table="item">
				<having><![CDATA[max(item.id) > 1 and min(item.id)<10]]></having>
			</group_by>
			
			<sort column="title" table="computer" order="asc" />
			<sort column="id" order="desc" />
			
			<limit>100</limit>
			<start value="10" />
		</many_to_many>
	*/
	private function getRelationship($rel_name, $rel_elm, $parent_conditions = array(), $options = false) {
		if($rel_elm) {
			$sql_cache_name = $this->HibernateModelCache->getCachedSQLName(str_replace("/", "_", $this->module_id).".".$this->service_id.".".$rel_name."_relationships", $parent_conditions, $options);
			
			$sql = "";
			if($this->HibernateModelCache->cachedSQLExists($sql_cache_name)) {
				$sql = $this->HibernateModelCache->getCachedSQL($sql_cache_name);
			}
			
			if(!$sql) {
				$keys = isset($rel_elm["key"]) ? $rel_elm["key"] : null;
				$attributes = isset($rel_elm["attribute"]) ? $rel_elm["attribute"] : null;
				$conditions = isset($rel_elm["condition"]) ? $rel_elm["condition"] : null;
				$groups_by = isset($rel_elm["group_by"]) ? $rel_elm["group_by"] : null;
				$sorts = !empty($options["sort"]) && empty($rel_elm["sort"]) ? $options["sort"] : (isset($rel_elm["sort"]) ? $rel_elm["sort"] : null);
				
				$parent_conditions_aux = array();
				
				if(is_array($parent_conditions))
					foreach ($parent_conditions as $k => $v)
						$parent_conditions_aux[$k] = "#$k#";
				
				$sql = $this->getFunction("buildTableFindRelationshipSQL", array($this->table_name, array(
					"keys" => $keys,
					"attributes" => $attributes,
					"conditions" => $conditions,
					"groups_by" => $groups_by,
					"sorts" => $sorts,
				), $parent_conditions_aux), $options);
				
				$this->HibernateModelCache->setCachedSQL($sql_cache_name, $sql);
			}
			
			if(is_array($parent_conditions))
				foreach($parent_conditions as $parent_key => $parent_value)
					$sql = str_replace(array("'#$parent_key#'", "#$parent_key#"), DBSQLConverter::createBaseExprValue($parent_value), $sql);
			
		//echo "\n<br/>$sql\n<br/>";
			
			$options_aux = !empty($options) ? $options : array();
			$options_aux["limit"] = !empty($options["limit"]) && (!isset($rel_elm["limit"]) || !is_numeric($rel_elm["limit"])) ? $options["limit"] : (isset($rel_elm["limit"]) ? $rel_elm["limit"] : null);
			$options_aux["start"] = !empty($options["start"]) && (!isset($rel_elm["start"]) || !is_numeric($rel_elm["start"])) ? $options["start"] : (isset($rel_elm["start"]) ? $rel_elm["start"] : null);
			unset($options_aux["sort"]);
			
			$db_data = $this->getData($sql, $options_aux);
			
			if($this->getErrorHandler()->ok()) {
				$result_class = isset($rel_elm["result_class"]) ? $rel_elm["result_class"] : null;
				$result_map = isset($rel_elm["result_map"]) ? $rel_elm["result_map"] : null;
				$this->ResultHandler->transformData($db_data, $result_class, $result_map);
				
				return $db_data;
			}
		}
		return false;
	}
	
	private function getRelationshipsCount($parent_ids, $options = false) {
		$rels = array();
		
		$items = array($this->one_to_many, $this->many_to_many, $this->many_to_one, $this->one_to_one);
		
		$t = count($items);
		for ($i = 0; $i < $t; $i++) {
			$item = $items[$i];
			
			if (is_array($item)) {
				foreach($item as $rel_name => $rel_elm) {
					$rel_obj = $this->getRelationshipCount($rel_name, $rel_elm, $parent_ids, $options);
			
					if($rel_obj && count($rel_obj) && $rel_obj[0]) {
						$rels[$rel_name] = $rel_obj;
					}
				}
			}
		}
		
		return $rels;
	}
	
	private function getRelationshipCount($rel_name, $rel_elm, $parent_conditions = array(), $options = false) {
		if($rel_elm) {
			$sql_cache_name = $this->HibernateModelCache->getCachedSQLName(str_replace("/", "_", $this->module_id).".".$this->service_id.".".$rel_name."_count_relationships", $parent_conditions, $options);
			
			$sql = "";
			if($this->HibernateModelCache->cachedSQLExists($sql_cache_name)) {
				$sql = $this->HibernateModelCache->getCachedSQL($sql_cache_name);
			}
			
			if(!$sql) {
				$keys = isset($rel_elm["key"]) ? $rel_elm["key"] : null;
				$attributes = isset($rel_elm["attribute"]) ? $rel_elm["attribute"] : null;
				$conditions = isset($rel_elm["condition"]) ? $rel_elm["condition"] : null;
				$groups_by = isset($rel_elm["group_by"]) ? $rel_elm["group_by"] : null;
				
				$parent_conditions_aux = array();
				
				if(is_array($parent_conditions))
					foreach ($parent_conditions as $k => $v)
						$parent_conditions_aux[$k] = "#$k#";
				
				$sql = $this->getFunction("buildTableCountRelationshipSQL", array($this->table_name, array(
					"keys" => $keys,
					"attributes" => $attributes,
					"conditions" => $conditions,
					"groups_by" => $groups_by,
				), $parent_conditions_aux), $options);
				
				$this->HibernateModelCache->setCachedSQL($sql_cache_name, $sql);
			}
			
			if(is_array($parent_conditions))
				foreach($parent_conditions as $parent_key => $parent_value)
					$sql = str_replace(array("'#$parent_key#'", "#$parent_key#"), DBSQLConverter::createBaseExprValue($parent_value), $sql);
			
		//echo "\n<br/>$sql\n<br/>";
		
			$options_aux = !empty($options) ? $options : array();
			$options_aux["limit"] = !empty($options["limit"]) && (!isset($rel_elm["limit"]) || !is_numeric($rel_elm["limit"])) ? $options["limit"] : (isset($rel_elm["limit"]) ? $rel_elm["limit"] : null);
			$options_aux["start"] = !empty($options["start"]) && (!isset($rel_elm["start"]) || !is_numeric($rel_elm["start"])) ? $options["start"] : (isset($rel_elm["start"]) ? $rel_elm["start"] : null);
			unset($options_aux["sort"]);
			
			$db_data = $this->getData($sql, $options_aux);
			
			if($this->getErrorHandler()->ok()) {
				return isset($db_data["result"][0]["total"]) ? $db_data["result"][0]["total"] : null;
			}
		}
		return false;
	}
	
	/*********** IBATIS ************/
	
	public function callQuerySQL($query_type, $query_id, $parameters = false, $options = false) {
		$query = $this->SQLClient->getQuery($query_type, $query_id);
		
		return $this->SQLClient->getQuerySQL($query, $parameters, $options);
	}
	
	public function callQuery($query_type, $query_id, $parameters = false, $options = false) {
		$this->prepareOptions($options);
		
		$service_id = $this->service_id . "." . $query_id;
		
		$query = $this->SQLClient->getQuery($query_type, $query_id);
		
		$is_cache_active = $this->isCacheActive();
			
		if($is_cache_active && empty($options["no_cache"]) && $this->getCacheLayer()->isValid($this->module_id, $service_id, $parameters, $options)) {
			$includes = $this->SQLClient->getLibsOfResultClassAndMap($query);
			SQLMapIncludesHandler::includeLibsOfResultClassAndMap($includes);

			return $this->getCacheLayer()->get($this->module_id, $service_id, $parameters, $options);
		}
		
		$result = $this->SQLClient->execQuery($query, $parameters, $options);
		if($this->getErrorHandler()->ok()) {
			if($is_cache_active) {
				$this->getCacheLayer()->check($this->module_id, $service_id, $parameters, $result, $options);
			}
		}
		return $result;
	}
	
	public function callInsertSQL($query_id, $parameters = false, $options = false) {
		return $this->callQuerySQL("insert", $query_id, $parameters, $options);
	}
	public function callInsert($query_id, $parameters = false, $options = false) {
		return $this->callQuery("insert", $query_id, $parameters, $options);
	}
	
	public function callUpdateSQL($query_id, $parameters = false, $options = false) {
		return $this->callQuerySQL("update", $query_id, $parameters, $options);
	}
	public function callUpdate($query_id, $parameters = false, $options = false) {
		return $this->callQuery("update", $query_id, $parameters, $options);
	}
	
	public function callDeleteSQL($query_id, $parameters = false, $options = false) {
		return $this->callQuerySQL("delete", $query_id, $parameters, $options);
	}
	public function callDelete($query_id, $parameters = false, $options = false) {
		return $this->callQuery("delete", $query_id, $parameters, $options);
	}
	
	public function callSelectSQL($query_id, $parameters = false, $options = false) {
		return $this->callQuerySQL("select", $query_id, $parameters, $options);
	}
	public function callSelect($query_id, $parameters = false, $options = false) {
		return $this->callQuery("select", $query_id, $parameters, $options);
	}
	
	public function callProcedureSQL($query_id, $parameters = false, $options = false) {
		return $this->callQuerySQL("procedure", $query_id, $parameters, $options);
	}
	public function callProcedure($query_id, $parameters = false, $options = false) {
		return $this->callQuery("procedure", $query_id, $parameters, $options);
	}
	
	/*********** BROKER ************/
	
	public function getFunction($function_name, $parameters = false, $options = false) {
		$this->prepareOptions($options);
		return $this->RDBBroker->getFunction($function_name, $parameters, $options);
	}
	
	public function getData($sql, $options = false) {
		$this->prepareOptions($options);
		return $this->RDBBroker->getData($sql, $options);
	}
	
	public function setData($sql, $options = false) {
		$this->prepareOptions($options);
		return $this->RDBBroker->setData($sql, $options);
	}

    	public function getInsertedId($options = false) {
    		$this->prepareOptions($options);
		return $this->RDBBroker->getInsertedId($options);
	}
	
	/*********** OTHERS ************/
	private function prepareOptions(&$options) {
		$options = $options ? $options : array();
		$options = array_merge($this->default_options, $options);
	}
	
	public function setDefaultOptions($options) {
		if (array_key_exists("limit", $options)) //2020-01-24: Do not use isset here bc if null, the key will continue to exist
			unset($options["limit"]);
		
		if (array_key_exists("start", $options)) //2020-01-24: Do not use isset here bc if null, the key will continue to exist
			unset($options["start"]);
		
		$this->default_options = $options ? $options : array();
	}
	
	public function getDefaultOptions() {
		return $this->default_options;
	}
	
	private function isCacheActive() {
		return $is_cache_active = $this->getCacheLayer() ? true : false;
	}
	
	/**************************** IDS ***************************/
	
	protected static function deleteIdsNotInAttributesFromObjData($data, $ids_not_in_attributes) {
		foreach($ids_not_in_attributes as $id_name) {
			if (array_key_exists($id_name, $data)) //2020-01-24: Do not use isset here bc if null, the key will continue to exist
				unset($data[$id_name]);
		}
		
		return $data;
	}
	
	protected static function getIdsNotInAttributes($attributes, $ids) {
		$ids_not_in_attributes = array();
		if($attributes) {
			foreach($ids as $id_name => $id) {
				if(!in_array($id_name, $attributes)) {
					$ids_not_in_attributes[] = $id_name;
				}
			}
		}
		return $ids_not_in_attributes;
	}
	
	protected static function getObjDataIds($data, $ids) {
		$data_ids = array();
		foreach($ids as $key => $id) {
			if (array_key_exists($key, $data)) { //2020-01-24: Do not use isset here bc if null, the key will continue to exist
				$on = isset($id["output_name"]) ? $id["output_name"] : null;
				$data_ids[$on] = $data[$key];
			}
		}
		return $data_ids;
	}
	
	/**************************** CONDITIONS ***************************/
	
	protected function filterInvalidAttributes(&$attributes) {
		if(is_array($attributes)) {
			foreach($attributes as $key => $value) {
				if(!isset($this->table_attributes[$key])) //2020-01-24: isset here is correct. Do not change it to array_key_exists
					unset($attributes[$key]);
			}
		}
	}
	
	//Based on DBSQLConverter::getSQLConditions
	protected function configureAttributeConditions(&$conditions) {
		$this->QueryHandler->transformData($conditions, $this->parameter_class, $this->parameter_map, false, true);
		
		if(is_array($conditions)) {
			$new_conditions = array();
			
			foreach($conditions as $key => $value) {
				$k = strtolower($key);
				$exists = false;
				
				if (($k == "or" || $k == "and") && is_array($value)) {
					$this->configureAttributeConditions($conditions[$key]);
					$exists = true;
				}
				else if (strpos($key, "(") !== false) { //check for attribute name inside of functions
					$start_pos = strrpos($key, "(") + 1;
					$end_pos = strpos($key, ")", $start_pos);
					$end_pos = $end_pos >= $start_pos ? $end_pos : strlen($key);
					
					$prev = substr($key, 0, $start_pos);
					$real_key = substr($key, $start_pos, $end_pos - $start_pos);
					$next = substr($key, $end_pos);
					
					if (!empty($this->properties_to_attributes[$real_key])) {
						$key = $prev . $this->properties_to_attributes[$real_key] . $next;
						$exists = isset($this->table_attributes[ $this->properties_to_attributes[$real_key] ]); //2020-01-24: isset here is correct. Do not change it to array_key_exists
					}
					else
						$exists = isset($this->table_attributes[$real_key]); //2020-01-24: isset here is correct. Do not change it to array_key_exists
				}
				else if (!empty($this->properties_to_attributes[$key])) {
					$key = $this->properties_to_attributes[$key];
					$exists = isset($this->table_attributes[$key]); //2020-01-24: isset here is correct. Do not change it to array_key_exists
				}
				
				if ($exists)
					$new_conditions[$key] = $value;
			}
			
			$conditions = $new_conditions;
		}
	}
	
	//convert string to real numeric value. This is very important, bc in the insert and update primitive actions of the DBSQLConverter, the sql must be created with numeric values and without quotes, otherwise the DB server gives a sql error.
	protected function convertNumericAttributesStringValues($attributes) {
		if ($attributes)
			foreach($attributes as $key => $value) 
				if (isset($value) && is_string($value) && is_numeric($value) && !empty($this->table_attributes[$key])) {
					$attr = $this->table_attributes[$key];
					
					if ($attr["type"] && ObjTypeHandler::isDBTypeNumeric($attr["type"]))
						$attributes[$key] += 0; 
				}
		
		return $attributes;
	}
	
	protected static function convertPropertiesToAttributes($attributes, $properties_to_attributes) {
		if ($properties_to_attributes) {
			if (is_array($attributes) && count($attributes)) {
				$new_attributes = array();
				
				foreach ($attributes as $attr_name)
					if (!empty($properties_to_attributes[$attr_name])) 
						$new_attributes[ $properties_to_attributes[$attr_name] ] = $attr_name;
				
				$attributes = $new_attributes;
			}
			
			if (!is_array($attributes) || !count($attributes))
				$attributes = array_flip($properties_to_attributes);
		}
		
		return $attributes;
	}
	
	protected static function convertSortPropertiesToAttributes($sorts, $properties_to_attributes) {
		if ($properties_to_attributes && is_array($sorts) && count($sorts)) {
			$new_sorts = array();
			
			foreach ($sorts as $idx => $sort_item) {
				if (is_array($sort_item)) {
					$sort_column = "";
					
					foreach ($sort_item as $key => $value)
						if (strtolower($key) == "column" && $value && !empty($properties_to_attributes[$value])) {
							$sort_item[$key] = $properties_to_attributes[$value];
							$new_sorts[] = $sort_item;
							break;
						}
				}
			}
			
			$sorts = $new_sorts;
		}
		
		return $sorts;
	}
}
?>
