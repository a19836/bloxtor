<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("WorkFlowDataAccessHandler");
include_once $EVC->getUtilPath("WorkFlowBrokersSelectedDBVarsHandler");

class WorkFlowQueryHandler {
	
	const TASK_QUERY_TYPE = "d0d250c0";
	const TASK_QUERY_TAG = "query";
	
	private $WorkFlowUIHandler;
	private $webroot_url;
	private $common_webroot_url;
	private $db_drivers;
	private $selected_db_broker;
	private $selected_db_driver;
	private $selected_type;
	private $selected_table;
	private $selected_tables_name;
	private $selected_table_attrs;
	private $map_php_types;
	private $map_db_types;
	
	public function __construct($WorkFlowUIHandler, $webroot_url, $common_webroot_url, $db_drivers, $selected_db_broker, $selected_db_driver, $selected_type, $selected_table, $selected_tables_name, $selected_table_attrs, $map_php_types, $map_db_types) {
		$this->WorkFlowUIHandler = $WorkFlowUIHandler;
		$this->webroot_url = $webroot_url;
		$this->common_webroot_url = $common_webroot_url;
		$this->db_drivers = $db_drivers;
		$this->selected_db_broker = $selected_db_broker;
		$this->selected_db_driver = $selected_db_driver;
		$this->selected_type = $selected_type;
		$this->selected_table = $selected_table;
		$this->selected_tables_name = $selected_tables_name;
		$this->selected_table_attrs = $selected_table_attrs;
		$this->map_php_types = $map_php_types;
		$this->map_db_types = $map_db_types;
	}
	
	public static function getSelectedDBBrokersDriversTablesAndAttributes($DataAccessObj, $workflow_paths_id, $selected_default_table = false, $selected_default_db_driver = false, $filter_by_layout = false, $LayoutTypeProjectHandler = null) {
		$brokers = $DataAccessObj->getBrokers();
		$db_drivers = array();
		
		$selected_db_broker = $selected_db_driver = $bkp_selected_db_broker = $bkp_selected_db_driver = $selected_type = $selected_table = $selected_tables_name = $selected_table_attrs = null;
		
		if ($brokers) {
			foreach ($brokers as $broker_name => $broker) {
				$db_drivers[$broker_name] = $broker->getDBDriversName();
				
				//filter db_drivers by $filter_by_layout
				if ($filter_by_layout && $LayoutTypeProjectHandler)
					$LayoutTypeProjectHandler->filterLayerBrokersDBDriversNamesFromLayoutName($DataAccessObj, $db_drivers[$broker_name], $filter_by_layout); 
				
				if ($selected_default_db_driver && empty($selected_db_broker) && !empty($db_drivers[$selected_default_db_driver])) {
					$selected_db_broker = $broker_name;
					$selected_db_driver = isset($db_drivers[$selected_default_db_driver][0]) ? $db_drivers[$selected_default_db_driver][0] : null;
				}
				else if (empty($bkp_selected_db_broker) && $db_drivers[$broker_name]) { //only execute one time
					$bkp_selected_db_broker = $broker_name;
					$bkp_selected_db_driver = isset($db_drivers[$broker_name][0]) ? $db_drivers[$broker_name][0] : null;
				}
			}
			
			if (empty($selected_db_broker) && $bkp_selected_db_broker) {
				$selected_db_broker = $bkp_selected_db_broker;
				$selected_db_driver = $bkp_selected_db_driver;
			}
		}
		
		if ($selected_db_broker && $selected_db_driver) {
			$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $selected_db_driver);
			$selected_type = file_exists($tasks_file_path) ? "diagram" : "db";
			
			if ($selected_type == "diagram") {
				$WorkFlowDataAccessHandler = new WorkFlowDataAccessHandler();
				$WorkFlowDataAccessHandler->setTasksFilePath($tasks_file_path);
				$tasks = $WorkFlowDataAccessHandler->getTasks();
				
				$selected_tables_name = !empty($tasks["tasks"]) ? array_keys($tasks["tasks"]) : array();
				
				$selected_table = !empty($selected_default_table) ? $selected_default_table : $selected_tables_name[0];
				
				$selected_table_attrs = isset($tasks["tasks"][$selected_table]) ? $tasks["tasks"][$selected_table] : null;
				$selected_table_attrs = isset($selected_table_attrs["properties"]["table_attr_names"]) ? $selected_table_attrs["properties"]["table_attr_names"] : null;
			}
			else {
				$selected_tables = $DataAccessObj->getBroker($selected_db_broker)->getFunction("listTables", null, array("db_driver" => $selected_db_driver));
				$selected_tables_name = array();
				if ($selected_tables)
					foreach ($selected_tables as $st)
						$selected_tables_name[] = isset($st["name"]) ? $st["name"] : null;
				
				if ($selected_default_table) {
					$selected_table_exists = $DataAccessObj->getBroker($selected_db_broker)->getFunction("isTableInNamesList", array($selected_tables_name, $selected_default_table), array("db_driver" => $selected_db_driver));
					$selected_table = $selected_table_exists ? $selected_default_table : (isset($selected_tables_name[0]) ? $selected_tables_name[0] : null);
				}
				else
					$selected_table = isset($selected_tables_name[0]) ? $selected_tables_name[0] : null;
				
				$selected_table_attrs = $DataAccessObj->getBroker($selected_db_broker)->getFunction("listTableFields", $selected_table, array("db_driver" => $selected_db_driver));
				$selected_table_attrs = array_keys($selected_table_attrs);
			}
		}
		
		return array(
			"brokers" => $brokers,
			"db_drivers" => $db_drivers,
			"selected_db_broker" => $selected_db_broker,
			"selected_db_driver" => $selected_db_driver,
			"selected_type" => $selected_type,
			"selected_table" => $selected_table,
			"selected_tables_name" => $selected_tables_name,
			"selected_table_attrs" => $selected_table_attrs,
		);
	}
	
	public function getDataAccessObjHtml($relationships, $is_hbn_relationship = false, $settings = null) {
		$html = '
			<div class="relationships">
				<div class="description">
					"Relationships" are links or dependecies between objects or tables.
				</div>
				<span class="icon update_automatically" onClick="updateDataAccessObjectRelationshipsAutomatically(this)" title="Create Relationships Automatically">Update Automatically</span>
				
				<div class="relationships_tabs">
					 <ul class="tabs tabs_transparent">
						<li><a href="#relationships_tabs-1">' . ($is_hbn_relationship ? 'Foreign Tables' : 'Rules') . '</a></li>
						' . ($is_hbn_relationship ? '' : '<li><a href="#relationships_tabs-2">Parameters Maps</a></li>') . '
						<li><a href="#relationships_tabs-3">Results Maps</a></li>
						<li><a href="#relationships_tabs-4">Includes</a></li>
					</ul>';
	
		$html .= '
					<div id="relationships_tabs-1">
						<div class="description">
							' . ($is_hbn_relationship ? 'The purpose for the "Foreign Tables" is to create relationships between objects, so we can call specific methods according with these relationships. Here is an example:<br/>
							- if the current object is the CAR object<br/>
							- and cars have doors<br/>
							...You should create the relationship between CAR and DOOR, in order to get the doors for specific CAR\'s objects, or the correspondent doors\' details or other information related this relationship...' : 'The purpose for the "Rules" is to create specific queries, not included by the native methods...') . '
						</div>
						<span class="icon add add_relationship" onClick="addRelationshipBlock(this, ' . ($is_hbn_relationship ? '1' : '0') . ')">Add</span>
						<div class="rels">';
	
		if ($relationships) {
			foreach ($relationships as $rel_type => $rels) {
				if ($rel_type == "one_to_one" || $rel_type == "one_to_many" || $rel_type == "many_to_one" || $rel_type == "many_to_many" || $rel_type == "insert" || $rel_type == "update" || $rel_type == "delete" || $rel_type == "select" || $rel_type == "procedure") {
					$html .= $this->getQueriesBlockHtml($rels, $is_hbn_relationship, $rel_type, true, $settings);
				}
			}
		}
		
		$html .= '	
						</div>
					</div>';
	
		if (!$is_hbn_relationship) {
			$html .= '
						<div id="relationships_tabs-2">
							<div class="parameters_maps">
								<div class="description">
									The purpose of a "Parameters Map/Class" is to convert and validate an input data object. This is:<br/>
									- let\'s say that a specific method receives an argument, which is an object with a "name", "age" and "country" attributes. Something like: {"name" => "...", "age" => "...", "country" => "..."}. <br/>
									- but the real input object passed to this method only contains the attributes "n", "a" and "c". Something like: {"n" => "David", "a" => "35", "c" => "Portugal"}. <br/>
									<br/>
									So we can create a "Parameters Map/Class" to convert this input object to the right one, transforming the attribute "n" to "name", "a" to "age" and "c" to "country". This is, to something like: {"name" => "David", "age" => "35", "country" => "Portugal"}<br/>
									Additionally we can refer that the "age" attribute is a numeric field, and the system will check and convert the correspondent value to that type.
								</div>
								<span class="icon add add_parameter" onClick="addParameterMap(this)" title="Add new Map">Add</span>
								<div class="parameters">';
			
			if (!empty($relationships["parameter_map"])) {
				$t = count($relationships["parameter_map"]);
				for ($i = 0; $i < $t; $i++)
					$html .= $this->getParameterMapHTML("map", $relationships["parameter_map"][$i], $this->map_php_types, $this->map_db_types, true);
			}
	
			$html .= '
								</div>
							</div>
						</div>';
		}
		
		$html .= '
					<div id="relationships_tabs-3">
						<div class="results_maps">
							<div class="description">
								The purpose of a "Result Map/Class" is to convert and validate an output data object. This is:<br/>
								- let\'s say that a specific method returns a result, which is an object with a "name", "age" and "country" attributes. Something like: {"name" => "David", "age" => "35", "country" => "Portugal"}. <br/>
								- but the real output object that we would like to return should contain the attributes "n", "a" and "c". Something like: {"n" => "...", "a" => "...", "c" => "..."}. <br/>
								<br/>
								So we can create a "Result Map/Class" to convert this result to the right output object, transforming the attribute "name" to "n", "age" to "a" and "country" to "c". This is, to something like: {"n" => "David", "a" => "35", "c" => "Portugal"}<br/>
								Additionally we can refer that the "a" attribute is a numeric field, and the system will check and convert the correspondent value to that type.
							</div>
							<span class="icon add add_result" onClick="addResultMap(this)" title="Add new Map">Add</span>
							<div class="results">';
		
		if (!empty($relationships["result_map"])) {
			$t = count($relationships["result_map"]);
			for ($i = 0; $i < $t; $i++)
				$html .= $this->getResultMapHTML("map", $relationships["result_map"][$i], $this->map_php_types, $this->map_db_types, true);
		}
	
		$html .= '
							</div>
						</div>
					</div>';
	
		$html .= '
					<div id="relationships_tabs-4">' . $this->getInludeHTMLBlock(isset($relationships["import"]) ? $relationships["import"] : null) . '</div>';
	
		//CLOSE RELATIONSHIP SUB_TABS AND MAIN RELATIONSHIP DIV AND RELATIONSHIP TAB
		$html .= '	
				</div>
			</div>';
			
		return $html;
	}
	
	public function getHeader() {
		return '
			<script src="' . $this->common_webroot_url . 'vendor/jquery/js/jquery.md5.js"></script>
			<script src="' . $this->common_webroot_url . 'vendor/acecodeeditor/src/ace.js"></script>
			<script src="' . $this->common_webroot_url . 'vendor/acecodeeditor/src/ext-language_tools.js"></script>
		';
	}
	
	public function getDataAccessJavascript($bean_name, $bean_file_name, $path, $item_type, $hbn_obj_id, $get_layer_sub_files_url) {
		$html = '<script>
			var create_hbn_object_relationships_automatically_url = "' . $this->webroot_url . 'phpframework/dataaccess/create_hbn_obj_relationships_automatically?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '";
			var get_map_fields_url = "' . $this->webroot_url . 'phpframework/dataaccess/get_broker_table_hbn_map?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&item_type=' . $item_type . '";
			var get_available_map_ids_url = "' . $this->webroot_url . 'phpframework/dataaccess/get_available_hbn_maps?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&obj=' . $hbn_obj_id . '&query_type=#query_type#&map_type=#map_type#";
			var get_sql_from_query_obj = "' . $this->webroot_url . 'phpframework/dataaccess/get_sql_from_query_obj?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&db_broker=#db_broker#&db_driver=#db_driver#";
			var get_query_obj_from_sql = "' . $this->webroot_url . 'phpframework/dataaccess/get_query_obj_from_sql?bean_name=' . $bean_name . '&bean_file_name=' . $bean_file_name . '&path=' . $path . '&item_type=' . $item_type . '&db_broker=#db_broker#&db_driver=#db_driver#";
			
			var relative_file_path = "' . $path . '";
			relative_file_path = relative_file_path.substr(0, 1) == "/" ? relative_file_path.substr(1, relative_file_path.length - 1) : relative_file_path;
			relative_file_path = relative_file_path.substr(relative_file_path.length - 1, 1) == "/" ? relative_file_path.substr(0, relative_file_path.length - 1) : relative_file_path;
			';
		
		if ($bean_name) 
			$html .= '
			main_layers_properties.' . $bean_name . ' = {ui: {
				folder: {
					get_sub_files_url: "' . $get_layer_sub_files_url . '",
				},
				cms_common: {
					get_sub_files_url: "' . $get_layer_sub_files_url . '",
				},
				cms_module: {
					get_sub_files_url: "' . $get_layer_sub_files_url . '",
				},
				cms_program: {
					get_sub_files_url: "' . $get_layer_sub_files_url . '",
				},
				cms_resource: {
					get_sub_files_url: "' . $get_layer_sub_files_url . '",
				},
				file: {
					attributes: {
						file_path: "#path#"
					}
				},
				import: {
					attributes: {
						file_path: "#path#"
					}
				},
				referenced_folder: {
					get_sub_files_url: "' . $get_layer_sub_files_url . '",
				},
			}};
			';
		
		$dao_get_layer_sub_files_url = $this->webroot_url . 'admin/get_sub_files?item_type=dao&path=#path#';
		
		$html .= '
			main_layers_properties.dao = {ui: {
				folder: {
					get_sub_files_url: "' . $dao_get_layer_sub_files_url . '",
				},
				cms_common: {
					get_sub_files_url: "' . $dao_get_layer_sub_files_url . '",
				},
				cms_module: {
					get_sub_files_url: "' . $dao_get_layer_sub_files_url . '",
				},
				cms_program: {
					get_sub_files_url: "' . $dao_get_layer_sub_files_url . '",
				},
				cms_resource: {
					get_sub_files_url: "' . $dao_get_layer_sub_files_url . '",
				},
				hibernatemodel: {
					attributes: {
						file_path: "#path#"
					}
				},
				objtype: {
					attributes: {
						file_path: "#path#"
					}
				},
			}};


			var new_include_html = \'' . str_replace("'", "\\'", str_replace("\n", "", $this->getInludeHTML())) .'\';
			var new_parameter_html = \'' . str_replace("'", "\\'", str_replace("\n", "", $this->getParameterHTML($this->map_php_types, $this->map_db_types))) .'\';
			var new_result_html = \'' . str_replace("'", "\\'", str_replace("\n", "", $this->getResultHTML($this->map_php_types, $this->map_db_types))) .'\';
			var new_parameter_map_html = \'' . str_replace("'", "\\'", str_replace("\n", "", $this->getParameterMapHTML("map", false, $this->map_php_types, $this->map_db_types, true))) . '\';
			var new_result_map_html = \'' . str_replace("'", "\\'", str_replace("\n", "", $this->getResultMapHTML("map", false, $this->map_php_types, $this->map_db_types, true))) . '\';

			var new_relationship_block_html = \'' . str_replace("'", "\\'", str_replace("\n", "", $this->getQueryBlockHtml(true))) . '\';
			var new_relationship_query_block_html = \'' . str_replace("'", "\\'", str_replace("\n", "", $this->getQueryBlockHtml())) . '\';
			var new_relationship_attribute1_html = \'' . str_replace("'", "\\'", str_replace("\n", "", self::getQueryAttributeHtml1())) . '\';
			var new_relationship_attribute2_html = \'' . str_replace("'", "\\'", str_replace("\n", "", self::getQueryAttributeHtml2())) . '\';
			var new_relationship_key_html = \'' . str_replace("'", "\\'", str_replace("\n", "", self::getQueryKeyHtml())) . '\';
			var new_relationship_condition1_html = \'' . str_replace("'", "\\'", str_replace("\n", "", self::getQueryConditionHtml1())) . '\';
			var new_relationship_condition2_html = \'' . str_replace("'", "\\'", str_replace("\n", "", self::getQueryConditionHtml2())) . '\';
			var new_relationship_group_by_html = \'' . str_replace("'", "\\'", str_replace("\n", "", self::getQueryGroupByHtml())) . '\';
			var new_relationship_sort_html = \'' . str_replace("'", "\\'", str_replace("\n", "", self::getQuerySortHtml())) . '\';
					
			var task_table_type_id = "' . self::TASK_QUERY_TYPE . '";
			var tasks_settings = ' . $this->WorkFlowUIHandler->getTasksSettingsObj() . ';
			
			DBQueryTaskPropertyObj.on_click_checkbox = onClickQueryAtributeCheckBox;
			DBQueryTaskPropertyObj.on_delete_table = onDeleteQueryTable;
			DBQueryTaskPropertyObj.on_complete_table_label = prepareTableLabelSettings;
			DBQueryTaskPropertyObj.on_complete_connection_properties = prepareTablesRelationshipKeys;
			DBQueryTaskPropertyObj.on_complete_select_start_task = prepareTableStartTask;
			
			' . WorkFlowBrokersSelectedDBVarsHandler::printSelectedDBVarsJavascriptCode($this->webroot_url, $bean_name, $bean_file_name, array(
				"dal_broker" => $this->selected_db_broker,
				"db_driver" => $this->selected_db_driver,
				"type" => $this->selected_type,
				"db_table" => $this->selected_table,
				"db_brokers_drivers" => $this->db_drivers
			));
			
		if ($this->selected_db_broker && $this->selected_db_driver && $this->selected_type && $this->selected_tables_name) {
			$t = count($this->selected_tables_name);
			for ($i = 0; $i < $t; $i++) {
				if ($this->selected_tables_name[$i] == $this->selected_table) {
					$html .= 'db_brokers_drivers_tables_attributes["' . $this->selected_db_broker . '"]["' . $this->selected_db_driver . '"]["' . $this->selected_type . '"]["' . $this->selected_table . '"] = ' . json_encode($this->selected_table_attrs) . ';';
				}
				else {
					$html .= 'db_brokers_drivers_tables_attributes["' . $this->selected_db_broker . '"]["' . $this->selected_db_driver . '"]["' . $this->selected_type . '"]["' . $this->selected_tables_name[$i] . '"] = [];';
				}
			}
		}
		
		$html .= '</script>';
		
		return $html;
	}
	
	public function getGlobalTaskFlowChar() {
		$html = '
		<div id="taskflowchart_global">
			<div class="tasks_menu scroll">
				' . $this->WorkFlowUIHandler->printTasksList() . '
			</div>
			<div class="tasks_properties hidden">
				' . $this->WorkFlowUIHandler->printTasksProperties() . '
			</div>

			<div class="connections_properties hidden">
				' . $this->WorkFlowUIHandler->printConnectionsProperties() . '
			</div>
		</div>';
		
		return $html;
	}

	/* START: POPUP FUNCTIONS */
	public function getChooseQueryTableOrAttributeHtml($id = false, $my_fancy_popup_obj = "MyFancyPopup") {
		$html = '<div id="' . $id . '" class="myfancypopup choose_table_or_attribute with_title">
				<div class="title">Choose a DB Table or Attribute</div>
				<div class="contents">
					<div class="db_broker' . (count($this->db_drivers) == 1 ? " single_broker" : "") . '">
						<label>DB Broker:</label>
						<select onChange="updateDBDrivers(this)">
							<option></option>';
	
		foreach ($this->db_drivers as $db_broker => $db_driver_names) {
			$html .= '		<option ' . ($this->selected_db_broker == $db_broker ? 'selected' : '') . '>' . $db_broker . '</option>';
		}
			
		$html .= '		</select>
					</div>
					<div class="db_driver" ' . ($this->selected_db_broker ? '' : 'style="display:none"') . '>
						<label>DB Driver:</label>
						<select onChange="updateDBTables(this)">
							<option></option>';
	
		if (!empty($this->db_drivers[$this->selected_db_broker])) {
			$t = count($this->db_drivers[$this->selected_db_broker]);
			for ($i = 0; $i < $t; $i++) 
				$html .= '	<option ' . ($this->selected_db_driver == $this->db_drivers[$this->selected_db_broker][$i] ? 'selected' : '') . '>' . $this->db_drivers[$this->selected_db_broker][$i] . '</option>';
		}
		
		$html .= '		</select>
					</div>
					<div class="type" ' . ($this->selected_db_broker ? '' : 'style="display:none"') . '>
						<label>Type:</label>
						<select onChange="updateDBTables(this)">
							<option value="db" ' . ($this->selected_type == "db" ? 'selected' : '') . '>From DB Server</option>
							<option value="diagram" ' . ($this->selected_type == "diagram" ? 'selected' : '') . '>From DB Diagram</option>
						</select>
					</div>
					<div class="db_table" ' . ($this->selected_db_driver ? '' : 'style="display:none"') . '>
						<label>DB Table:</label>
						<select onChange="updateDBAttributes(this)">';
		
		if ($this->selected_tables_name) {
			$t = count($this->selected_tables_name);
			for ($i = 0; $i < $t; $i++)
				$html .= '	<option ' . ($this->selected_tables_name[$i] == $this->selected_table ? 'selected' : '') . '>' . $this->selected_tables_name[$i] . '</option>';
		}
			
		$html .= '		</select>
						<span class="icon refresh" onClick="refreshDBTables(this)"></span>
					</div>
					<div class="db_attribute" ' . ($this->selected_table ? '' : 'style="display:none"') . '>
						<label>DB Attribute:</label>
						<select onChange="syncChooseTableOrAttributePopups(this)">';
		
		if ($this->selected_table_attrs) {
			$t = count($this->selected_table_attrs);
			for ($i = 0; $i < $t; $i++)
				$html .= '	<option>' . $this->selected_table_attrs[$i] . '</option>';
		}
			
		$html .= '		</select>
						<span class="icon refresh" onClick="refreshDBAttributes(this)"></span>
					</div>
				</div>
				<div class="button">
					<input type="button" value="update" onClick="' . $my_fancy_popup_obj . '.settings.updateFunction(this)" />
				</div>
			</div>';
		
		return $html;
	}
	
	public function getChooseIncludeFromFileManagerHtml($get_sub_files_url, $id = false) {
		$html = '<div id="' . $id . '" class="myfancypopup choose_include_file with_title">
			<div class="title">Choose a Include File</div>
			<ul class="mytree">
				<li>
					<label>Root</label>
					<ul url="' . str_replace("#path#", "", $get_sub_files_url) . '"></ul>
				</li>
			</ul>
			<div class="button">
				<input type="button" value="update" onClick="MyFancyPopup.settings.updateFunction(this)" />
			</div>
		</div>';
		
		return $html;
	}
	
	public function getChooseDAOObjectFromFileManagerHtml($id = false) {
		$html = '<div id="' . $id . '" class="myfancypopup choose_dao_object with_title">
			<div class="title">Choose a DAO</div>
			<ul class="mytree">
				<li>
					<label>External Lib - "dao" Folder</label>
					<ul url="' . $this->webroot_url . 'admin/get_sub_files?item_type=dao&path="></ul>
				</li>
			</ul>
			<div class="button">
				<input type="button" value="update" onClick="MyFancyPopup.settings.updateFunction(this)" />
			</div>
		</div>';
		
		return $html;
	}
	
	public function getChooseAvailableMapIdHtml($id = false) {
		$html = '<div id="' . $id . '" class="myfancypopup choose_map_id with_title">
			<div class="title">Choose a Map</div>
			<div class="contents">
				<div class="map">
					<label>Available Maps:</label>
					<select></select>
					<span class="icon refresh" onClick="updateAvailableMapsOptions()">Refresh</span>
				</div>
			</div>
			<div class="button">
				<input type="button" value="update" onClick="MyFancyPopup.settings.updateFunction(this)" />
			</div>
		</div>';
		
		return $html;
	}
	/* END: POPUP FUNCTIONS */
	
	/* START: INCLUDES FUNCTIONS */
	public function getInludeHTML($include = false, $is_relative = false) {
		return '
			<div class="include">
				<label class="include_path">Path:</label>
				<input class="include_path" type="text" value="' . $include . '" onFocus="disableTemporaryAutoSaveOnInputFocus(this)" onBlur="undoDisableTemporaryAutoSaveOnInputBlur(this)" />
				<label class="is_include_relative">Relative:</label>
				<input class="is_include_relative" type="checkbox" value="1" ' . ($is_relative ? 'checked="checked"' : '') . ' />
				<span class="icon search" onClick="getIncludePathFromFileManager(this, \'input.include_path\')" title="Get file from File Manager">Search</span>
				<span class="icon delete" onClick="removeInclude(this);" title="Delete">Remove</span>
			</div>';
	}

	public function getInludeHTMLBlock($imports) {
		$html = '
		<div class="includes">
			<div class="description">
				"includes" are files that you can add and that contains specific configurations, this is, an include file can have parameteres or result maps, queries, relationships, etc...
			</div>
			<span class="icon add" onClick="addNewInclude(this)" title="Add new include">Add</span>
			<div class="fields">';
		
		if ($imports) {
			$t = count($imports);
			for ($i = 0; $i < $t; $i++) {
				$include = isset($imports[$i]["value"]) ? $imports[$i]["value"] : null;
				$is_relative = isset($imports[$i]["@"]["relative"]) ? $imports[$i]["@"]["relative"] : null;
		
				$html .= $this->getInludeHTML($include, $is_relative);
			}
		}
		else
			$html .= '<div class="no_includes">There are includes...</div>';
		
		$html .= '
			</div>
		</div>';
	
		return $html;
	}
	/* END: INCLUDES FUNCTIONS */
	
	/* START: MAPS/CLASSES FUNCTIONS */
	public static function getMapSelectOptions($map_types, $data_type, $primitive_type = "org.phpframework.object.php.Primitive", $add_empty_option = true, $add_if_not_exists = true) {
		$data_type = ObjTypeHandler::convertSimpleTypeIntoCompositeType($data_type, $primitive_type);
		
		$html = $add_empty_option ? '<option></option>' : '';
		$html .= '<optgroup label="Primitive Types">';
		
		if ($map_types)
			foreach ($map_types as $type_id => $type_title) {
				if (strpos($type_id, $primitive_type) === 0) {
					$html .= '<option value="' . $type_id . '" ' . ($data_type == $type_id ? 'selected' : '') . '>' . $type_title . '</option>';
				}
			}
	
		$html .= '</optgroup>
			<optgroup label="Composite Types">';
		
		if ($map_types)
			foreach ($map_types as $type_id => $type_title) {
				if (strpos($type_id, $primitive_type) === false) {
					$html .= '<option value="' . $type_id . '" ' . ($data_type == $type_id ? 'selected' : '') . '>' . $type_title . '</option>';
				}
			}
		
		$html .= '</optgroup>
			<optgroup label="Other Types">';
	
		if ($add_if_not_exists && $data_type && !isset($map_types[$data_type])) {
			$pos = strrpos($data_type, ".");
			$aux = $pos !== false ? substr($data_type, $pos + 1) : $data_type;
			$html .= '<option value="' . $data_type . '" selected>' . $aux . '</option>';
		}
	
		$html .= '</optgroup>';
	
		return $html;
	}

	public function getParameterHTML($map_php_types, $map_db_types, $input_name = false, $input_type = false, $output_name = false, $output_type = false, $mandatory = false) {
		$html = '
		<tr class="parameter field">
			<td class="input_name">
				<input type="text" value="' . $input_name . '" />
			</td>
			<td class="input_type">
				<select>
		 			' . self::getMapSelectOptions($map_php_types, $input_type) . '
				</select>
				<span class="icon search" onClick="geMapPHPTypeFromFileManager(this, \'select\')" title="Get type from File Manager">Search</span>
			</td>
			<td class="output_name">
				<input type="text" value="' . $output_name . '" />
				<span class="icon search" onClick="getTableAttributeFromDB(this, \'input\')" title="Get attribute from DB">Search</span>
			</td>
			<td class="output_type">
				<select>
					' . self::getMapSelectOptions($map_db_types, $output_type, "org.phpframework.object.db.DBPrimitive") . '
				</select>
			</td>
			<td class="mandatory">
				<input type="checkbox" value="1" ' . ($mandatory ? 'checked="checked"' : '') . ' />
			</td>
			<td class="icon_cell"><span class="icon delete" onClick="$(this).parent().parent().remove();" title="Delete">Remove</span></td>
		</tr>';
	
		return $html;
	}

	public function getParameterMapHTML($parameter_type, $parameter_map, $map_php_types, $map_db_types, $allow_remove = false) {
		$html = '
		<div class="map" ' . ($parameter_type == "map" ? 'style="display:block;"' : 'style="display:none;"') . '>
			<label>Parameter Map:</label>
			<span class="icon delete" onClick="$(this).parent().remove();" title="Delete" ' . ($allow_remove ? '' : 'style="display:none;"') . '>Remove</span>
			<span class="icon update_automatically" onClick="createParameterOrResultMapAutomatically(this, \'parameter\')" title="Create Map Automatically">Update Automatically</span>
			<div class="map_id">
				<label>ID:</label>
				<input type="text" value="' . (isset($parameter_map["@"]["id"]) ? $parameter_map["@"]["id"] : "") . '" placeHolder="Id/Name" onBlur="validateMapId(this, \'parameter\');" />
			</div>
			<div class="map_class">
				<label>Class:</label>
				<input type="text" value="' . (isset($parameter_map["@"]["class"]) ? $parameter_map["@"]["class"] : "") . '" />
				<span class="icon search" onClick="getParameterClassFromFileManager(this)">Search</span>
			</div>
			<table>
				<thead class="fields_title">
					<tr>
						<th class="input_name table_header">Logical/Input Attribute Name</th>
						<th class="input_type table_header">Logical/Input Attribute Type</th>
						<th class="output_name table_header">DB/Output Attribute Name</th>
						<th class="output_type table_header">DB/Output Attribute Type</th>
						<th class="mandatory table_header">Mandatory</th>
						<th class="icon_cell"><span class="icon add" onClick="addNewParameter(this)" title="Add">Add</span></th>
					</tr>
				</thead>
				<tbody class="fields">';
	
		$parameters = isset($parameter_map["childs"]["parameter"]) ? $parameter_map["childs"]["parameter"] : null;
		
		if ($parameters) {
			$t = count($parameters);
			for ($i = 0; $i < $t; $i++) {
				$parameter = isset($parameters[$i]["@"]) ? $parameters[$i]["@"] : null;
				$parameter_input_name = isset($parameter["input_name"]) ? $parameter["input_name"] : null;
				$parameter_input_type = isset($parameter["input_type"]) ? $parameter["input_type"] : null;
				$parameter_output_name = isset($parameter["output_name"]) ? $parameter["output_name"] : null;
				$parameter_output_type = isset($parameter["output_type"]) ? $parameter["output_type"] : null;
				$parameter_mandatory = isset($parameter["mandatory"]) ? $parameter["mandatory"] : null;
				
				$html .= $this->getParameterHTML($map_php_types, $map_db_types, $parameter_input_name, $parameter_input_type, $parameter_output_name, $parameter_output_type, $parameter_mandatory);
			}
		}
		else
			$html .= $this->getParameterHTML($map_php_types, $map_db_types);
	
		$html .= '	</tbody>
			</table>
		</div>';
	
		return $html;
	}

	public function getResultHTML($map_php_types, $map_db_types, $input_name = false, $input_type = false, $output_name = false, $output_type = false, $mandatory = false) {
		return '
		<tr class="result field">
			<td class="input_name">
				<input type="text" value="' . $input_name . '" />
				<span class="icon search" onClick="getTableAttributeFromDB(this, \'input\')" title="Get attribute from DB">Search</span>
			</td>
			<td class="input_type">
				<select>
					' . self::getMapSelectOptions($map_db_types, $input_type, "org.phpframework.object.db.DBPrimitive") . '
				</select>
			</td>
			<td class="output_name">
				<input type="text" value="' . $output_name . '" />
			</td>
			<td class="output_type">
				<select>
					' . self::getMapSelectOptions($map_php_types, $output_type) . '
				</select>
				<span class="icon search" onClick="geMapPHPTypeFromFileManager(this, \'select\')" title="Get type from File Manager">Search</span>
			</td>
			<td class="mandatory">
				<input type="checkbox" value="1" ' . ($mandatory ? 'checked="checked"' : '') . ' />
			</td>
			<td class="icon_cell"><span class="icon delete" onClick="$(this).parent().parent().remove();" title="Delete">Remove</span></td>
		</tr>';
	}

	public function getResultMapHTML($result_type, $result_map, $map_php_types, $map_db_types, $allow_remove = false) {
		$html = '
		<div class="map" ' . ($result_type == "map" ? 'style="display:block;"' : 'style="display:none;"') . '>
			<label>Result Map:</label>
			<span class="icon delete" onClick="$(this).parent().remove();" title="Delete" ' . ($allow_remove ? '' : 'style="display:none;"') . '>Remove</span>
			<span class="icon update_automatically" onClick="createParameterOrResultMapAutomatically(this, \'result\')" title="Create Map Automatically">Update Automatically</span>
			<div class="map_id">
				<label>ID:</label>
				<input type="text" value="' . (isset($result_map["@"]["id"]) ? $result_map["@"]["id"] : "") . '" placeHolder="Id/Name" onFocus="disableTemporaryAutoSaveOnInputFocus(this)" onBlur="undoDisableTemporaryAutoSaveOnInputBlur(this); validateMapId(this, \'result\');" />
			</div>
			<div class="map_class">
				<label>Class:</label>
				<input type="text" value="' . (isset($result_map["@"]["class"]) ? $result_map["@"]["class"] : "") . '" />
				<span class="icon search" onClick="getParameterClassFromFileManager(this)">Search</span>
			</div>
			<table>
				<thead class="fields_title">
					<tr>
						<th class="input_name table_header">DB/Input Attribute Name</th>
						<th class="input_type table_header">DB/Input Attribute Type</th>
						<th class="output_name table_header">Logical/Output Attribute Name</th>
						<th class="output_type table_header">Logical/Output Attribute Type</th>
						<th class="mandatory table_header">Mandatory</th>
						<th class="icon_cell"><span class="icon add" onClick="addNewResult(this)" title="Add">Add</span></th>
					</tr>
				</thead>
				<tbody class="fields">';
	
		$results = isset($result_map["childs"]["result"]) ? $result_map["childs"]["result"] : null;
		
		if ($results) {
			$t = count($results);
			for ($i = 0; $i < $t; $i++) {
				$result = isset($results[$i]["@"]) ? $results[$i]["@"] : null;
				$result_input_name = isset($result["input_name"]) ? $result["input_name"] : null;
				$result_input_type = isset($result["input_type"]) ? $result["input_type"] : null;
				$result_output_name = isset($result["output_name"]) ? $result["output_name"] : null;
				$result_output_type = isset($result["output_type"]) ? $result["output_type"] : null;
				$result_mandatory = isset($result["mandatory"]) ? $result["mandatory"] : null;
				
				$html .= $this->getResultHTML($map_php_types, $map_db_types, $result_input_name, $result_input_type, $result_output_name, $result_output_type, $result_mandatory);
			}
		}
		else
			$html .= $this->getResultHTML($map_php_types, $map_db_types);
	
		$html .= '	</tbody>
			</table>
		</div>';
	
		return $html;
	}
	/* END: MAPS/CLASSES FUNCTIONS */
	
	/* START: QUERIES FUNCTIONS */
	public function getQueriesBlockHtml($rels, $is_hbn_relationship = false, $rel_type = false, $minimize = true, $settings = null) {
		$html = "";
		
		if ($rels) {
			$t = count($rels);
			for ($i = 0; $i < $t; $i++) {
				$rel = $rels[$i];
				
				$id = WorkFlowDataAccessHandler::getNodeValue($rel, "id");
				$name = WorkFlowDataAccessHandler::getNodeValue($rel, "name");
				$parameter_class = WorkFlowDataAccessHandler::getNodeValue($rel, "parameter_class");
				$parameter_map = WorkFlowDataAccessHandler::getNodeValue($rel, "parameter_map");
				$result_class = WorkFlowDataAccessHandler::getNodeValue($rel, "result_class");
				$result_map = WorkFlowDataAccessHandler::getNodeValue($rel, "result_map");

				$attributes = isset($rel["childs"]["attribute"]) ? $rel["childs"]["attribute"] : null;
				$keys = isset($rel["childs"]["key"]) ? $rel["childs"]["key"] : null;
				$conditions = isset($rel["childs"]["condition"]) ? $rel["childs"]["condition"] : null;
				$groups_by = isset($rel["childs"]["group_by"]) ? $rel["childs"]["group_by"] : null;
				$sorts = isset($rel["childs"]["sort"]) ? $rel["childs"]["sort"] : null;
				$limit = WorkFlowDataAccessHandler::getNodeValue($rel, "limit");
				$start = WorkFlowDataAccessHandler::getNodeValue($rel, "start");
				$sql = isset($rel["value"]) ? $rel["value"] : null;
				
				$rand = rand(0, 1000);
				
				$rel_name = $is_hbn_relationship ? $name : $id;
				
				$data = array(
					"type" => $rel_type, 
					"name" => $rel_name, 
					"parameter_class" => $parameter_class, 
					"parameter_map" => $parameter_map, 
					"result_class" => $result_class, 
					"result_map" => $result_map, 
					"attributes" => $attributes, 
					"keys" => $keys, 
					"conditions" => $conditions, 
					"groups_by" => $groups_by, 
					"sorts" => $sorts, 
					"limit" => $limit, 
					"start" => $start, 
					"sql" => $sql
				);
				
				$default_settings = array(
					"init_ui" => true,
					"init_workflow" => true,
					"minimize" => $minimize,
				);
				$settings = $settings ? array_merge($default_settings, $settings) : $default_settings;
				
				$relationship_block_html = $this->getQueryBlockHtml($is_hbn_relationship, $settings, $data);
				$relationship_block_html = str_replace("#rand#", $rand, $relationship_block_html);
				
				$html .= $relationship_block_html;
			}
		}
			
		return $html;
	}
	
	public function getQueryBlockHtml($is_hbn_relationship = false, $settings = false, $data = false) {
		$init_ui = isset($settings["init_ui"]) ? $settings["init_ui"] : null;
		$minimize = isset($settings["minimize"]) ? $settings["minimize"] : null;
		$encapsulate_parameter_and_result_settings = isset($settings["encapsulate_parameter_and_result_settings"]) ? $settings["encapsulate_parameter_and_result_settings"] : null;
		
		$rel_type = isset($data["type"]) ? $data["type"] : null;
		$name = isset($data["name"]) ? $data["name"] : null;
		$parameter_class = isset($data["parameter_class"]) ? $data["parameter_class"] : null;
		$parameter_map = isset($data["parameter_map"]) ? $data["parameter_map"] : null;
		$result_class = isset($data["result_class"]) ? $data["result_class"] : null;
		$result_map = isset($data["result_map"]) ? $data["result_map"] : null;
		$attributes = isset($data["attributes"]) ? $data["attributes"] : null;
		$keys = isset($data["keys"]) ? $data["keys"] : null;
		$conditions = isset($data["conditions"]) ? $data["conditions"] : null;
		$groups_by = isset($data["groups_by"]) ? $data["groups_by"] : null;
		$sorts = isset($data["sorts"]) ? $data["sorts"] : null;
		$limit = isset($data["limit"]) ? $data["limit"] : null;
		$start = isset($data["start"]) ? $data["start"] : null;
		$sql = isset($data["sql"]) ? $data["sql"] : null;
		
		if ($is_hbn_relationship) {
			$rel_types = array("one_to_one", "one_to_many", "many_to_one", "many_to_many");
			$show_select_sql_html = true;
		}
		else {
			$rel_types = array("insert", "update", "delete", "select", "procedure");
			$show_select_sql_html = $rel_type == "select";
		}
		
		$html = '
		<div class="relationship">
			<div class="header_buttons">
				<span class="icon delete" onClick="$(this).parent().parent().remove();" title="Remove Query">Remove</span>
				<span class="icon toggle" onClick="toggleQuery(this);" title="Toggle Query">Toggle</span>
			</div>
			<div style="float:none; clear:both;"></div>
			<div class="rel_type">
				<label>Relationship Type:</label>
				<select onChange="updateRelationshipType(this, #rand#);">';
		
		$t = count($rel_types);
		for ($i = 0; $i < $t; $i++) {
			$html .= '	<option ' . ($rel_types[$i] == $rel_type ? 'selected' : '') . '>' . $rel_types[$i] . '</option>';
		}
			
		$html .= '	</select>
			</div>
			<div class="rel_name">
				<label>Name:</label>
				<input type="text" value="' . $name . '" placeHolder="Name" onFocus="disableTemporaryAutoSaveOnInputFocus(this)" onBlur="undoDisableTemporaryAutoSaveOnInputBlur(this); validateRelationshipName(this);" />
			</div>
			<div style="float:none; clear:both;"></div>';
		
		if ($encapsulate_parameter_and_result_settings)
			$html .= '
			<div class="settings collapsed">
				<div class="settings_header">
					Main Settings
					<div class="icon maximize" onClick="toggleMainSettingsPanel(this)">Toggle</div>
				</div>';
		
		if (!$is_hbn_relationship) {
			$html .= '
			<div class="parameter_class_id">
				<label>Parameters Class Id:</label>
				<input type="text" value="' . $parameter_class . '" />
				<span class="icon search" onClick="getParameterClassFromFileManager(this)">Search</span>
			</div>
			<div class="parameter_map_id">
				<label>Parameter Map Id:</label>
				<input type="text" value="' . $parameter_map . '" />
				<span class="icon search" onClick="getAvailableParameterMap(this, \'' . ($is_hbn_relationship ? 'relationships' : 'queries') . '\')">Search</span>
			</div>';
		}
		
		//display:none is already done through the css in edit_query.css
		$html .= '
			<div class="result_class_id"' . /*(!$show_select_sql_html ? ' style="display:none"' : '') .*/ '>
				<label>Result Class Id:</label>
				<input type="text" value="' . $result_class . '" />
				<span class="icon search" onClick="getResultClassFromFileManager(this)">Search</span>
			</div>
			<div class="result_map_id"' . /*(!$show_select_sql_html ? ' style="display:none"' : '') .*/ '>
				<label>Result Map Id:</label>
				<input type="text" value="' . $result_map . '" />
				<span class="icon search" onClick="getAvailableResultMap(this, \'' . ($is_hbn_relationship ? 'relationships' : 'queries') . '\')">Search</span>
			</div>
			<div style="float:none; clear:both;"></div>
		';
	
		if ($encapsulate_parameter_and_result_settings)
			$html .= '</div>';
		
		$html .= $this->getQueryHtml($is_hbn_relationship, $rel_type, $show_select_sql_html, $attributes, $keys, $conditions, $groups_by, $sorts, $limit, $start, $sql, $settings);
	
		$html .= '</div>';
		
		if ($init_ui && $minimize) {
			$html .= '<script>
			$(function () {
				$("#" + taskFlowChartObj_#rand#.TaskFlow.main_tasks_flow_obj_id).parent().parent().parent().parent().parent().parent().children(".header_buttons").children(".minimize").first().click();
			});
			</script>';
		}
	
		return $html;
	}
	
	public function getQueryHtml($is_hbn_relationship = false, $rel_type = false, $show_select_sql_html = true, $attributes = false, $keys = false, $conditions = false, $groups_by = false, $sorts = false, $limit = false, $start = false, $sql = false, $settings = null) {
		$html = '
		<div rand_number="#rand#" class="query">
			<ul class="tabs tabs_transparent query_tabs">
				<li class="query_design_tab"><a href="#query_obj_tabs_#rand#-1" onClick="initQueryDesign(this, #rand#)">UI</a></li>
				<li class="query_sql_tab"><a href="#query_obj_tabs_#rand#-2" onClick="initQuerySql(this, #rand#)">SQL</a></li>
			</ul>
					
			<div id="query_obj_tabs_#rand#-1">
				<div class="query_insert_update_delete"' . ($show_select_sql_html ? ' style="display:none"' : '') . '>
					' . self::getQueryInsertUpdateDeleteHtml($attributes, $conditions) . '
				</div>
				
				<div class="query_select"' . (!$show_select_sql_html ? ' style="display:none"' : '') . '>
					' . self::getQuerySelectHtml($is_hbn_relationship, $attributes, $keys, $conditions, $groups_by, $sorts, $limit, $start, $settings) . '
				</div>
			</div>
			
			<div id="query_obj_tabs_#rand#-2" class="sql_text_area">
				<textarea>' . trim(str_replace("\t", "", htmlspecialchars($sql, ENT_NOQUOTES))) . '</textarea>
			</div>
		</div>';

		return $html;
	}
	
	public function getQueryInsertUpdateDeleteHtml($attributes = false, $conditions = false) {
		$table = isset($attributes[0]) ? WorkFlowDataAccessHandler::getNodeValue($attributes[0], "table") : "";
		$table = $table ? $table : ($conditions ? WorkFlowDataAccessHandler::getNodeValue($conditions[0], "table") : "");
		
		$html = '
		<div class="query_table">
			<label>Table:</label>
			<input type="text" value="' . $table .'" onBlur="onBlurQueryInputField(this, #rand#)" />
			<span class="icon search" onClick="getTableFromDB(this, #rand#)">Search</span>	
		</div>
		
		<ul class="tabs tabs_transparent query_insert_update_delete_tabs">
			<li class="query_insert_update_delete_tabs_attributes"><a href="#query_insert_update_delete_tabs_#rand#-1">Attributes</a></li>
			<li class="query_insert_update_delete_tabs_conditions"><a href="#query_insert_update_delete_tabs_#rand#-2">Conditions</a></li>
		</ul> 
			
		<div id="query_insert_update_delete_tabs_#rand#-1" class="attributes query_insert_update_delete_tab">
			<table>
				<thead class="fields_title">
					<tr>
						<th class="column table_header">Column</th>
						<th class="value table_header">Value</th>
						<th class="icon_cell table_header"><span class="icon add" onClick="addQueryAttribute2(this, #rand#)">Add</span></span></th>
					</tr>
				</thead>
				<tbody class="fields">';
		
		if ($attributes) {
			$t = count($attributes);
			for ($j = 0; $j < $t; $j++) {
				$attribute = $attributes[$j];
		
				if (!empty($attribute["@"]) || !empty($attribute["childs"])) {
					$column = WorkFlowDataAccessHandler::getNodeValue($attribute, "column");
					$value = WorkFlowDataAccessHandler::getNodeValue($attribute, "value");

					$html .= self::getQueryAttributeHtml2($column, $value);
				}
			}
		}
		
		$html .= '
				</tbody>
			</table>
		</div>
		
		<div id="query_insert_update_delete_tabs_#rand#-2" class="conditions query_insert_update_delete_tab">
			<table>
				<thead class="fields_title">
					<tr>
						<th class="column table_header">Column</th>
						<th class="operator table_header">Operator</th>
						<th class="value table_header">Value</th>
						<th class="icon_cell table_header"><span class="icon add" onClick="addQueryCondition2(this, #rand#)">Add</span></span></th>
					</tr>
				</thead>
				<tbody class="fields">';
		
		if ($conditions) {
			$t = count($conditions);
			for ($j = 0; $j < $t; $j++) {
				$condition = $conditions[$j];

				if (!empty($condition["@"]) || !empty($condition["childs"])) {
					$column = WorkFlowDataAccessHandler::getNodeValue($condition, "column");
					$operator = WorkFlowDataAccessHandler::getNodeValue($condition, "operator");
					$value = WorkFlowDataAccessHandler::getNodeValue($condition, "value");

					$html .= self::getQueryConditionHtml2($column, $operator, $value);
				}
			}
		}
		
		$html .= '
				</tbody>
			</table>
		</div>';
		
		return $html;
	}
	
	public function getQuerySelectHtml($is_hbn_relationship = false, $attributes = false, $keys = false, $conditions = false, $groups_by = false, $sorts = false, $limit = false, $start = false, $settings = null) {
		$html = '
			<div class="query_ui">
			' . $this->getQueryWorkFlow($is_hbn_relationship, $attributes, $keys, $conditions, $settings) . '
			</div>
			<div class="query_settings">
				<ul class="tabs tabs_transparent query_settings_tabs">
					<li class="query_settings_tabs_attributes"><a href="#query_settings_tabs_#rand#-1">Attributes</a></li>
					<li class="query_settings_tabs_keys"><a href="#query_settings_tabs_#rand#-2">Keys</a></li>
					<li class="query_settings_tabs_conditions"><a href="#query_settings_tabs_#rand#-3">Conditions</a></li>
					<li class="query_settings_tabs_group_by"><a href="#query_settings_tabs_#rand#-4">Group By</a></li>
					<li class="query_settings_tabs_sorting"><a href="#query_settings_tabs_#rand#-5">Sorting</a></li>
					<li class="query_settings_tabs_limit"><a href="#query_settings_tabs_#rand#-6">Limit/Start</a></li>
				</ul> 
			
				' . $this->getChooseQueryTableOrAttributeHtml(false, "taskFlowChartObj_#rand#.getMyFancyPopupObj()") . '
			
				<span class="icon view advanced_query_settings" onClick="showOrHideExtraQuerySettings(this, #rand#)">Toggle Advanced Settings</span>
				<div id="query_settings_tabs_#rand#-1" class="attributes query_settings_tab">
					<table>
						<thead class="fields_title">
							<tr>
								<th class="table table_header">Table</th>
								<th class="column table_header">Column</th>
								<th class="name table_header">Name</th>
								<th class="icon_cell table_header"><span class="icon add" onClick="addQueryAttribute1(this, #rand#)">Add</span></th>
							</tr>
						</thead>
						<tbody class="fields">';
			
			if ($attributes) {
				$t = count($attributes);
				for ($j = 0; $j < $t; $j++) {
					$attribute = $attributes[$j];
				
					if (!empty($attribute["@"]) || !empty($attribute["childs"])) {
						$table = WorkFlowDataAccessHandler::getNodeValue($attribute, "table");
						$column = WorkFlowDataAccessHandler::getNodeValue($attribute, "column");
						$name = WorkFlowDataAccessHandler::getNodeValue($attribute, "name");
			
						$table = empty($table) && !empty($column) ? $this->selected_table : $table;
			
						$html .= self::getQueryAttributeHtml1($table, $column, $name);
					}
				}
			}
			
			$html .= '
						</tbody>
					</table>
				</div>

				<div id="query_settings_tabs_#rand#-2" class="keys query_settings_tab">
					<table>
						<thead class="fields_title">
							<tr>
								<th class="ptable table_header">Primary Table</th>
								<th class="pcolumn table_header">Primary Column</th>
								<th class="operator table_header">Operator</th>
								<th class="ftable table_header">Foreign Table</th>
								<th class="fcolumn table_header">Foreign Column</th>
								<th class="value table_header">Value</th>
								<th class="join table_header">Join</th>
								<th class="icon_cell table_header"><span class="icon add" onClick="addQueryKey(this, #rand#)">Add</span></th>
							</tr>
						</thead>
						<tbody class="fields">';
			
			if ($keys) {
				$t = count($keys);
				for ($j = 0; $j < $t; $j++) {
					$key = $keys[$j];

					if (!empty($key["@"]) || !empty($key["childs"])) {
						$ptable = WorkFlowDataAccessHandler::getNodeValue($key, "ptable");
						$pcolumn = WorkFlowDataAccessHandler::getNodeValue($key, "pcolumn");
						$ftable = WorkFlowDataAccessHandler::getNodeValue($key, "ftable");
						$fcolumn = WorkFlowDataAccessHandler::getNodeValue($key, "fcolumn");
						$value = WorkFlowDataAccessHandler::getNodeValue($key, "value");
						$join = WorkFlowDataAccessHandler::getNodeValue($key, "join");
						$operator = WorkFlowDataAccessHandler::getNodeValue($key, "operator");
			
						$ptable = empty($ptable) && !empty($pcolumn) ? $this->selected_table : $ptable;
						$ftable = empty($ftable) && !empty($fcolumn) ? $this->selected_table : $ftable;
			
						$html .= self::getQueryKeyHtml($ptable, $pcolumn, $ftable, $fcolumn, $value, $join, $operator);
					}
				}
			}
			
			$html .= '
						</tbody>
					</table>
				</div>

				<div id="query_settings_tabs_#rand#-3" class="conditions query_settings_tab">
					<table>
						<thead class="fields_title">
							<tr>
								<th class="table table_header">Table</th>
								<th class="column table_header">Column</th>
								<th class="operator table_header">Operator</th>
								<th class="value table_header">Value</th>
								<th class="icon_cell table_header"><span class="icon add" onClick="addQueryCondition1(this, #rand#)">Add</span></th>
							</tr>
						</thead>
						<tbody class="fields">';
			
			if ($conditions) {
				$t = count($conditions);
				for ($j = 0; $j < $t; $j++) {
					$condition = $conditions[$j];

					if (!empty($condition["@"]) || !empty($condition["childs"])) {
						$table = WorkFlowDataAccessHandler::getNodeValue($condition, "table");
						$column = WorkFlowDataAccessHandler::getNodeValue($condition, "column");
						$operator = WorkFlowDataAccessHandler::getNodeValue($condition, "operator");
						$value = WorkFlowDataAccessHandler::getNodeValue($condition, "value");
			
						$table = empty($table) && !empty($column) ? $this->selected_table : $table;
			
						$html .= self::getQueryConditionHtml1($table, $column, $operator, $value);
					}
				}
			}
			
			$html .= '
						</tbody>
					</table>
				</div>

				<div id="query_settings_tabs_#rand#-4" class="groups_by query_settings_tab">
					<table>
						<thead class="fields_title">
							<tr>
								<th class="table table_header">Table</th>
								<th class="column table_header">Column</th>
								<th class="icon_cell table_header"><span class="icon add" onClick="addQueryGroupBy(this, #rand#)">Add</span></th>
							</tr>
						</thead>
						<tbody class="fields">';
			
			if ($groups_by) {
				$t = count($groups_by);
				for ($j = 0; $j < $t; $j++) {
					$group_by = $groups_by[$j];

					if (!empty($group_by["@"]) || !empty($group_by["childs"])) {
						$table = WorkFlowDataAccessHandler::getNodeValue($group_by, "table");
						$column = WorkFlowDataAccessHandler::getNodeValue($group_by, "column");
			
						$table = empty($table) && !empty($column) ? $this->selected_table : $table;
			
						$html .= self::getQueryGroupByHtml($table, $column);
					}
				}
			}
			
			$html .= '
						</tbody>
					</table>
				</div>

				<div id="query_settings_tabs_#rand#-5" class="sorts query_settings_tab">
					<table>
						<thead class="fields_title">
							<tr>
								<th class="table table_header">Table</th>
								<th class="column table_header">Column</th>
								<th class="order table_header">Order</th>
								<th class="icon_cell table_header"><span class="icon add" onClick="addQuerySort(this, #rand#)">Add</span></th>
							</tr>
						</thead>
						<tbody class="fields">';
			
			if ($sorts) {
				$t = count($sorts);
				for ($j = 0; $j < $t; $j++) {
					$sort = $sorts[$j];

					if (!empty($sort["@"]) || !empty($sort["childs"])) {
						$table = WorkFlowDataAccessHandler::getNodeValue($sort, "table");
						$column = WorkFlowDataAccessHandler::getNodeValue($sort, "column");
						$order = strtolower( WorkFlowDataAccessHandler::getNodeValue($sort, "order") );
			
						$table = empty($table) && !empty($column) ? $this->selected_table : $table;
			
						$html .= self::getQuerySortHtml($table, $column, $order);
					}
				}
			}
			
			$html .= '
						</tbody>
					</table>
				</div>

				<div id="query_settings_tabs_#rand#-6" class="limit_start query_settings_tab">
					<div class="sub_limit_start">
						<div class="start">
							<label>Start:</label>
							<input type="text" value="' . $start . '" onBlur="onBlurQueryInputField(this, #rand#)" />
						</div>
						<div class="limit">
							<label>Limit:</label>
							<input type="text" value="' . $limit . '" onBlur="onBlurQueryInputField(this, #rand#)" />
						</div>
					</div>
				</div>
			</div>';
		
		return $html;
	}
	
	public function getQueryWorkFlow($is_hbn_relationship = false, $attributes = false, $keys = false, $conditions = false, $settings = null) {
		$init_ui = isset($settings["init_ui"]) ? $settings["init_ui"] : null;
		$init_workflow = isset($settings["init_workflow"]) ? $settings["init_workflow"] : null;
		
		$menus = array(
			"Add new Table" => array(
				"class" => "add_new_table", 
				"html" => '<a class="icon" onClick="return addNewTask(#rand#);">Add new Table</a>'
			),
			"Update Tables' Attributes" => array(
				"class" => "update_tables_attributes", 
				"html" => '<a class="icon" onClick="return updateQueryDBBroker(#rand#, false);">Update Tables\' Attributes</a>'
			),
			"Toggle UI" => array(
				"class" => "toggle_ui", 
				"html" => '<a class="icon" onClick="return showOrHideQueryUI(this, #rand#);">Toggle UI</a>'
			),
			"Toggle Settings" => array(
				"class" => "toggle_settings", 
				"html" => '<a class="icon" onClick="return showOrHideQuerySettings(this, #rand#);">Toggle Settings</a>'
			),
		);
		
		$this->WorkFlowUIHandler->setMenus($menus);
		
		$html = '<div id="taskflowchart_#rand#" class="taskflowchart">
			' . $this->WorkFlowUIHandler->getMenusContent() . '
			<div class="tasks_flow" sync_ui_and_settings="' . ($is_hbn_relationship ? 0 : 1) . '">
				' . $this->getChooseQueryTableOrAttributeHtml(false, "taskFlowChartObj_#rand#.getMyFancyPopupObj()") . '
			</div>
		</div>';
		
		if ($init_ui) {
			$html .= '
			<script>
				;(function() {
					addTaskFlowChart(#rand#, ' . ($init_workflow ? "true" : "false") . ');
					
					' . ($init_workflow ? '
					setTimeout(function() {//wait until taskFlowChartObj is initialized.
						updateQueryUITableFromQuerySettings(#rand#);
					}, 1000);' : '') . '
				})();
			</script>';
		}
		
		return $html;
	}

	public static function getQueryAttributeHtml1($table = false, $column = false, $name = false) {
		return '
		<tr class="field">
			<td class="table">
				<input type="text" value="' . $table .'" onFocus="onFocusTableField(this)" onBlur="onBlurQueryTableField(this, #rand#)" />
				<span class="icon search" onClick="getQueryTableFromDB(this, #rand#)">Search</span>
			</td>
			<td class="column">
				<input type="text" value="' . $column .'" onFocus="onFocusAttributeField(this)" onBlur="onBlurQueryAttributeField(this, #rand#)" />
				<span class="icon search" onClick="getQueryTableAttributeFromDB(this, \'input\', #rand#)">Search</span>
			</td>
			<td class="name">
				<input type="text" value="' . $name .'" onBlur="onBlurQueryInputField(this, #rand#)" />
			</td>
			<td class="icon_cell table_header"><span class="icon delete" onClick="deleteQueryAttribute(this, #rand#);">Remove</span></td>
		</tr>';
	}

	public static function getQueryAttributeHtml2($column = false, $value = false) {
		return '
		<tr class="field">
			<td class="column">
				<input type="text" value="' . $column .'" onBlur="onBlurQueryInputField(this, #rand#)" />
				<span class="icon search" onClick="getTableAttributeFromDB(this, \'input\', #rand#)">Search</span>
			</td>
			<td class="value">
				<input type="text" value="' . $value .'" onBlur="onBlurQueryInputField(this, #rand#)" />
			</td>
			<td class="icon_cell table_header"><span class="icon delete" onClick="deleteQueryField(this, #rand#);">Remove</span></td>
		</tr>';
	}

	public static function getQueryKeyHtml($ptable = false, $pcolumn = false, $ftable = false, $fcolumn = false, $value = false, $join = false, $operator = false) {
		$joins = array("inner", "left", "right");
		$operators = array("=", "!=", ">", ">=", "<=", "like", "not like", "in", "not in", "is", "is not");

		$operator = strtolower($operator);
		$join = strtolower($join);
		
		if ($operator == "in") {
			$value = trim($value);
			$value = substr($value, 0, 1) == "(" && substr($value, strlen($value) - 1) == ")" ? substr($value, 1, strlen($value) - 2) : $value;
		}
		else if (!$operator && ($pcolumn || $fcolumn)) {
			$operator = "=";
		}
		
		$html = '
			<tr class="field">
				<td class="ptable">
					<input type="text" value="' . $ptable .'" onFocus="onFocusQueryKey(this);" onBlur="onBlurQueryKey(this, #rand#);" />
					<span class="icon search" onClick="getQueryTableFromDB(this, #rand#)">Search</span>
				</td>
				<td class="pcolumn">
					<input type="text" value="' . $pcolumn .'" onFocus="onFocusQueryKey(this);" onBlur="onBlurQueryKey(this, #rand#);" />
					<span class="icon search" onClick="getQueryTableAttributeFromDB(this, \'input\', #rand#)">Search</span>
				</td>
				<td class="operator">
					<select onFocus="onFocusQueryKey(this);" onChange="onBlurQueryKey(this, #rand#);">
						<option></option>';
		
		if ($operators) {
			$t = count($operators);
			for ($w = 0; $w < $t; $w++)
				$html .= '	<option ' . ($operator == $operators[$w] ? 'selected' : '') . '>' . $operators[$w] . '</option>';
		}			

		$html .= '		</select>
				</td>
				<td class="ftable">
					<input type="text" value="' . $ftable .'" onFocus="onFocusQueryKey(this);" onBlur="onBlurQueryKey(this, #rand#);" />
					<span class="icon search" onClick="getQueryTableFromDB(this, #rand#)">Search</span>
				</td>
				<td class="fcolumn">
					<input type="text" value="' . $fcolumn .'" onFocus="onFocusQueryKey(this);" onBlur="onBlurQueryKey(this, #rand#);" />
					<span class="icon search" onClick="getQueryTableAttributeFromDB(this, \'input\', #rand#)">Search</span>
				</td>
				<td class="value">
					<input type="text" value="' . $value .'" onFocus="onFocusQueryKey(this);" onBlur="onBlurQueryKey(this, #rand#);" />
				</td>
				<td class="join">
					<select onFocus="onFocusQueryKey(this);" onChange="onBlurQueryKey(this, #rand#);">';
		
		if ($joins) {
			$t = count($joins);
			for ($w = 0; $w < $t; $w++)
				$html .= '	<option ' . ($join == $joins[$w] ? 'selected' : '') . '>' . $joins[$w] . '</option>';
		}			

		$html .= '	</select>
				</td>
				<td class="icon_cell table_header"><span class="icon delete" onClick="deleteQueryKey(this, #rand#);">Remove</span></td>
			</tr>';
		
		return $html;
	}

	public static function getQueryConditionHtml1($table = false, $column = false, $operator = false, $value = false) {
		$operators = array("=", "!=", ">", ">=", "<=", "like", "not like", "in", "not in", "is", "is not");

		$operator = strtolower($operator);
		
		if ($operator == "in") {
			$value = trim($value);
			$value = substr($value, 0, 1) == "(" && substr($value, strlen($value) - 1) == ")" ? substr($value, 1, strlen($value) - 2) : $value;
		}
		else if (!$operator && $column) {
			$operator = "=";
		}
		
		$html = '
			<tr class="field">
				<td class="table">
					<input type="text" value="' . $table .'" onBlur="onBlurQueryInputField(this, #rand#)" />
					<span class="icon search" onClick="getQueryTableFromDB(this, #rand#)">Search</span>
				</td>
				<td class="column">
					<input type="text" value="' . $column .'" onBlur="onBlurQueryInputField(this, #rand#)" />
					<span class="icon search" onClick="getQueryTableAttributeFromDB(this, \'input\', #rand#)">Search</span>
				</td>
				<td class="operator">
					<select onChange="onBlurQueryInputField(this, #rand#)">
						<option></option>';
		
		if ($operators) {
			$t = count($operators);
			for ($w = 0; $w < $t; $w++)
				$html .= '	<option ' . ($operator == $operators[$w] ? 'selected' : '') . '>' . $operators[$w] . '</option>';
		}			

		$html .= '	</select>
				</td>
				<td class="value">
					<input type="text" value="' . $value .'" onBlur="onBlurQueryInputField(this, #rand#)" />
				</td>
				<td class="icon_cell table_header"><span class="icon delete" onClick="deleteQueryField(this, #rand#);">Remove</span></td>
			</tr>';
	
		return $html;
	}
	
	public static function getQueryConditionHtml2($column = false, $operator = false, $value = false) {
		$operators = array("=", "!=", ">", ">=", "<=", "like", "not like", "in", "not in", "is", "is not");
		
		$operator = strtolower($operator);
		
		if ($operator == "in") {
			$value = trim($value);
			$value = substr($value, 0, 1) == "(" && substr($value, strlen($value) - 1) == ")" ? substr($value, 1, strlen($value) - 2) : $value;
		}
		else if (!$operator && $column)
			$operator = "=";
		
		$html = '
			<tr class="field">
				<td class="column">
					<input type="text" value="' . $column .'" onBlur="onBlurQueryInputField(this, #rand#)" />
					<span class="icon search" onClick="getTableAttributeFromDB(this, \'input\', #rand#)">Search</span>
				</td>
				<td class="operator">
					<select onChange="onBlurQueryInputField(this, #rand#)">
						<option></option>';
		
		if ($operators) {
			$t = count($operators);
			for ($w = 0; $w < $t; $w++)
				$html .= '	<option ' . ($operator == $operators[$w] ? 'selected' : '') . '>' . $operators[$w] . '</option>';
		}			

		$html .= '	</select>
				</td>
				<td class="value">
					<input type="text" value="' . $value .'" onBlur="onBlurQueryInputField(this, #rand#)" />
				</td>
				<td class="icon_cell table_header"><span class="icon delete" onClick="deleteQueryField(this, #rand#);">Remove</span></td>
			</tr>';
	
		return $html;
	}

	public static function getQueryGroupByHtml($table = false, $column = false) {
		return '
		<tr class="field">
			<td class="table">
				<input type="text" value="' . $table .'" onBlur="onBlurQueryInputField(this, #rand#)" />
				<span class="icon search" onClick="getQueryTableFromDB(this, #rand#)">Search</span>
			</td>
			<td class="column">
				<input type="text" value="' . $column .'" onBlur="onBlurQueryInputField(this, #rand#)" />
				<span class="icon search" onClick="getQueryTableAttributeFromDB(this, \'input\', #rand#)">Search</span>
			</td>
			<td class="icon_cell table_header"><span class="icon delete" onClick="deleteQueryField(this, #rand#);">Remove</span></td>
		</tr>';
	}

	public static function getQuerySortHtml($table = false, $column = false, $order = false) {
		$orders = array("ASC", "DESC");

		$html = '
			<tr class="field">
				<td class="table">
					<input type="text" value="' . $table .'" onBlur="onBlurQueryInputField(this, #rand#)" />
					<span class="icon search" onClick="getQueryTableFromDB(this, #rand#)">Search</span>
				</td>
				<td class="column">
					<input type="text" value="' . $column .'" onBlur="onBlurQueryInputField(this, #rand#)" />
					<span class="icon search" onClick="getQueryTableAttributeFromDB(this, \'input\', #rand#)">Search</span>
				</td>
				<td class="order">
					<select onChange="onBlurQueryInputField(this, #rand#)">';
		
		if ($orders) {
			$t = count($orders);
			for ($w = 0; $w < $t; $w++)
				$html .= '	<option ' . ($order == $orders[$w] ? 'selected' : '') . '>' . $orders[$w] . '</option>';
		}

		$html .= '	</select>
				</td>
				<td class="icon_cell table_header"><span class="icon delete" onClick="deleteQueryField(this, #rand#);">Remove</span></td>
			</tr>';
		
		return $html;
	}
	/* END: QUERIES FUNCTIONS */
}
?>
