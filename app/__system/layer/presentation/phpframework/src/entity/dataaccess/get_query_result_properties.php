<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = $_GET["bean_name"];
$bean_file_name = $_GET["bean_file_name"];
$db_driver = $_GET["db_driver"];
$module_id = $_GET["module_id"];
$hbn_obj_id = $_GET["obj"];
$query_id = $_GET["query"];
$query_type = $_GET["query_type"];
$relationship_type = $_GET["relationship_type"];
$rel_name = $_GET["rel_name"];

$PHPVariablesFileHandler = new PHPVariablesFileHandler($user_global_variables_file_path);
$PHPVariablesFileHandler->startUserGlobalVariables();

$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);

if ($obj && is_a($obj, "DataAccessLayer")) {
	$props = array();
	
	if ($query_id) {
		if ($obj->getType() == "ibatis") {
			//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=IormIDALayer&bean_file_name=iorm_dal.xml&module_id=sample.test&query_type=select&query=get_item
			$sql = $obj->callQuerySQL($module_id, $query_type, $query_id);
			
			if ($sql) {
				$data = $obj->getFunction("convertSQLToObject", $sql);
				
				if ($data)
					$props = array(
						"attributes" => $data["attributes"],
						"is_multiple" => true,
						"is_single" => true,
					);
			}
		}
		else if ($hbn_obj_id) {
			$options = $db_driver ? array("db_driver" => $db_driver) : array();
			$hbn_obj = $obj->callObject($module_id, $hbn_obj_id, $options);
			
			if ($hbn_obj) {
				if ($relationship_type == "queries") {
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=HormHDALayer&bean_file_name=horm_dal.xml&db_driver=test&module_id=sample.test&query_type=select&query=get_items&obj=Item&relationship_type=queries
					$sql = $hbn_obj->callQuerySQL($query_type, $query_id);
					
					if ($sql) {
						$data = $obj->getFunction("convertSQLToObject", $sql);
						
						if ($data)
							$props = array(
								"attributes" => $data["attributes"],
								"is_multiple" => true,
								"is_single" => true,
							);
					}
				}
				else if ($relationship_type == "relationships") {
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=HormHDALayer&bean_file_name=horm_dal.xml&db_driver=test&module_id=sample.test&query_type=one_to_many&query=item_sub_item_childs&obj=Item&relationship_type=relationships
					$hbn_obj_relationships = null;
					
					switch ($query_type) {
						case "many_to_one":
							$hbn_obj_relationships = $hbn_obj->getManyToOne();
							break;
						case "many_to_many":
							$hbn_obj_relationships = $hbn_obj->getManyToMany();
							break;
						case "one_to_many":
							$hbn_obj_relationships = $hbn_obj->getOneToMany();
							break;
						case "one_to_one":
							$hbn_obj_relationships = $hbn_obj->getOneToOne();
							break;
					}
					
					$data = $hbn_obj_relationships ? $hbn_obj_relationships[$query_id] : null;
					
					if ($data)
						$props = array(
							"attributes" => $data["attribute"],
							"is_multiple" => true,
							"is_single" => true,
						);
				}
				else if ($relationship_type == "native") {
					//For Native functions of the Hibernate Objects
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=HormHDALayer&bean_file_name=horm_dal.xml&db_driver=test&module_id=sample.test&query=findById&obj=Item&relationship_type=native
					//http://jplpinto.localhost/__system/phpframework/dataaccess/get_query_attributes?bean_name=HormHDALayer&bean_file_name=horm_dal.xml&db_driver=test&module_id=sample.test&query=findrelationship&rel_name=item_sub_item_childs&obj=Item&relationship_type=native
					$qidl = strtolower($query_id);
					
					switch ($qidl) {
						case "find": 
						case "findbyid": 
							$attrs = $hbn_obj->getTableAttributes();
							$table_name = $hbn_obj->getTableName();
							
							if ($attrs) {
								$props = array(
									"attributes" => array(),
									"is_multiple" => $qidl == "find",
									"is_single" => $qidl == "findbyid",
								);
								
								foreach ($attrs as $attr) {
									$attr["table"] = $table_name;
									$attr["column"] = $attr["name"];
									$props["attributes"][] = $attr;
								}
							}
							break;
							
						case "findrelationship":
							$hbn_obj_relationships = $hbn_obj->getManyToOne();
							
							if (isset($hbn_obj_relationships[$rel_name]))
								$data = $hbn_obj_relationships[$rel_name];
							else {
								$hbn_obj_relationships = $hbn_obj->getManyToMany();
								
								if (isset($hbn_obj_relationships[$rel_name]))
									$data = $hbn_obj_relationships[$rel_name];
								else {
									$hbn_obj_relationships = $hbn_obj->getOneToMany();
									
									if (isset($hbn_obj_relationships[$rel_name]))
										$data = $hbn_obj_relationships[$rel_name];
									else {
										$hbn_obj_relationships = $hbn_obj->getOneToOne();
										$data = $hbn_obj_relationships[$rel_name];
									}
								}
							}
							
							if ($data)
								$props = array(
									"attributes" => $data["attribute"],
									"is_multiple" => true,
									"is_single" => true,
								);
							break;
					}
				}
				else {
					//INVALID TYPE
				}
			}
		}
	}
	//echo "<pre>";print_r($props);die();
	//attributes name are in $props["attributes"][$i]["column"]
}

$PHPVariablesFileHandler->endUserGlobalVariables();
?>
