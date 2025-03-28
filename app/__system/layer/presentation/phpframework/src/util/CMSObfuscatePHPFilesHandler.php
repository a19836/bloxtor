<?php
include_once get_lib("org.phpframework.phpscript.PHPCodeObfuscator");

class CMSObfuscatePHPFilesHandler {
	private $cms_path;
	
	public function __construct($cms_path) {
		$this->cms_path = $cms_path;
	}
	
	public function obfuscate($opts, $files_settings, $serialized_files, $avoid_warnings_for_files) {
		//remove files that don't exists
		$files_settings = $this->removeUnexistentFilesSettings($files_settings);
		
		//obfuscate files
		$PHPCodeObfuscator = new PHPCodeObfuscator($files_settings, $serialized_files);
		$status = $PHPCodeObfuscator->obfuscateFiles($opts);
		$warning_msg = $PHPCodeObfuscator->getIncludesWarningMessage($avoid_warnings_for_files);
		$errors = $PHPCodeObfuscator->getErrors();
		
		return array(
			"status" => $status,
			"errors" => $errors,
			"warning_msg" => $warning_msg,
		);
	}
	
	public function getConfiguredOptions($options) {
		$opts = array();
		
		if (!is_array($options))
			parse_str($options, $opts);
		else
			$opts = $options;
		
		if (!isset($opts["strip_comments"]))
			$opts["strip_comments"] = 1;
		
		if (!isset($opts["strip_doc_comments"]))
			$opts["strip_doc_comments"] = 1;
		
		if (!isset($opts["copyright"]))
			$opts["copyright"] = '/*
 * Copyright (c) 2024 Bloxtor - http://bloxtor.com
 * 
 * Please note that this code belongs to the Bloxtor framework and must comply with the Bloxtor license.
 * If you do not accept these provisions, or if the Bloxtor License is not present or cannot be found, you are not entitled to use this code and must stop and delete it immediately.
 */';
	 	
	 	return $opts;
	}
	
	public function getDefaultSerializedFiles() {
		$serialized_files = array(
			$this->cms_path . "/app/layer/presentation/common/src/module/translator/translations/"
		);
		
		return $serialized_files;
	}
	
	private function removeUnexistentFilesSettings($files_settings) {
		if ($files_settings)
			foreach ($files_settings as $file_path => $file_settings)
				if (!file_exists($file_path))
					unset($files_settings[$file_path]);
		
		return $files_settings;
	}
	
	public function getDefaultFilesSettings($dest) {
		$files_settings = array(
			//LIB
			$this->cms_path . "/app/lib/org/phpframework/" => array(
				1 => array(
					"save_path" => "$dest/app/lib/org/phpframework/",
					"all_functions" => array("obfuscate_code" => 1),
					"all_properties" => array("obfuscate_name_private" => 1),
					"all_methods" => array("obfuscate_code" => 1, "obfuscate_name_private" => 1),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/bean/BeanFactory.php" => array(
				"BeanFactory" => array(
					"methods" => array(
						"initObject" => array("obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$objs', '$vars')),
						"initFunction" => array("obfuscate_encapsed_string" => 1),
						"getArgumentStr" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/bean/BeanSettingsFileFactory.php" => array(
				"BeanSettingsFileFactory" => array(
					"methods" => array(
						"executeSettingsWithPHPCode" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/broker/server/rest/RESTHibernateDataAccessBrokerServer.php" => array(
				"RESTHibernateDataAccessBrokerServer" => array(
					"methods" => array(
						"callWebService" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/cms/wordpress/WordPressCMSBlockHandler.php" => array(
				"WordPressCMSBlockHandler" => array(
					"methods" => array(
						"getBlockContentDirectly" => array("obfuscate_code" => 0),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/cms/wordpress/WordPressInstallationHandler.php" => array(
				"WordPressInstallationHandler" => array(
					"methods" => array(
						"hackWordPress" => array("ignore_local_variables" => array('$wpdb')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/cms/laravel/LaravelProjectHandler.php" => array(
				"LaravelProjectHandler" => array(
					"methods" => array(
						"callController" => array("ignore_local_variables" => array('$controller')),
						"getSQLResults" => array("ignore_local_variables" => array('$ret')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/driver/MySqlDB.php" => array(
				"MySqlDB" => array(
					"all_methods" => array("ignore_local_variables" => array('$default_schema', '$default_odbc_data_source', '$default_odbc_driver', '$available_php_extension_types', '$ignore_connection_options', '$ignore_connection_options_by_extension', '$db_connection_encodings', '$db_table_charsets', '$db_table_column_collations', '$charsets_to_collations', '$collations_to_charsets', '$storage_engines', '$php_to_db_column_types', '$db_to_php_column_types', '$db_column_types', '$db_column_simple_types', '$db_column_default_values_by_type', '$db_column_types_ignored_props', '$db_column_numeric_types', '$db_column_date_types', '$db_column_text_types', '$db_column_blob_types', '$db_column_boolean_types', '$db_column_mandatory_length_types', '$db_column_auto_increment_types', '$db_boolean_type_available_values', '$db_current_timestamp_available_values', '$attribute_value_reserved_words', '$mysqli_data_types', '$mysqli_flags', '$reserved_words')),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/driver/PostgresDB.php" => array(
				"PostgresDB" => array(
					"all_methods" => array("ignore_local_variables" => array('$default_schema', '$default_odbc_data_source', '$default_odbc_driver', '$available_php_extension_types', '$ignore_connection_options', '$ignore_connection_options_by_extension', '$db_connection_encodings', '$column_collations', '$php_to_db_column_types', '$db_to_php_column_types', '$db_column_types', '$db_column_simple_types', '$db_column_default_values_by_type', '$db_column_types_ignored_props', '$db_column_numeric_types', '$db_column_date_types', '$db_column_text_types', '$db_column_blob_types', '$db_column_boolean_types', '$db_column_mandatory_length_types', '$db_column_auto_increment_types', '$db_boolean_type_available_values', '$db_current_timestamp_available_values', '$attribute_value_reserved_words', '$reserved_words')),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/driver/MSSqlDB.php" => array(
				"MSSqlDB" => array(
					"all_methods" => array("ignore_local_variables" => array('$default_schema', '$default_odbc_data_source', '$default_odbc_driver', '$available_php_extension_types', '$ignore_connection_options', '$ignore_connection_options_by_extension', '$db_connection_encodings', '$db_table_column_collations', '$db_connection_encodings_to_collations', '$php_to_db_column_types', '$db_to_php_column_types', '$db_column_types', '$db_column_simple_types', '$db_column_default_values_by_type', '$db_column_types_ignored_props', '$db_column_numeric_types', '$db_column_date_types', '$db_column_text_types', '$db_column_blob_types', '$db_column_boolean_types', '$db_column_mandatory_length_types', '$db_column_auto_increment_types', '$db_boolean_type_available_values', '$db_current_timestamp_available_values', '$attribute_value_reserved_words', '$mssqlserver_data_types', '$reserved_words')),
				),
			),	
			$this->cms_path . "/app/lib/org/phpframework/db/property/MySqlDBProperty.php" => array(
				"MySqlDBProperty" => array(
					"all_properties" => array("obfuscate_name_private" => 0),
				),
			),	
			$this->cms_path . "/app/lib/org/phpframework/db/property/PostgresDBProperty.php" => array(
				"PostgresDBProperty" => array(
					"all_properties" => array("obfuscate_name_private" => 0),
				),
			),	
			$this->cms_path . "/app/lib/org/phpframework/db/property/MSSqlDBProperty.php" => array(
				"MSSqlDBProperty" => array(
					"all_properties" => array("obfuscate_name_private" => 0),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/static/MySqlDBStatic.php" => array(
				"MySqlDBStatic" => array(
					"all_methods" => array("ignore_local_variables" => array('$default_schema', '$default_odbc_data_source', '$default_odbc_driver', '$available_php_extension_types', '$ignore_connection_options', '$ignore_connection_options_by_extension', '$db_connection_encodings', '$db_table_charsets', '$db_table_column_collations', '$charsets_to_collations', '$storage_engines', '$php_to_db_column_types', '$db_to_php_column_types', '$db_column_types', '$db_column_simple_types', '$db_column_default_values_by_type', '$db_column_types_ignored_props', '$db_column_numeric_types', '$db_column_date_types', '$db_column_text_types', '$db_column_blob_types', '$db_column_boolean_types', '$db_column_mandatory_length_types', '$db_column_auto_increment_types', '$db_boolean_type_available_values', '$db_current_timestamp_available_values', '$attribute_value_reserved_words', '$mysqli_data_types', '$mysqli_flags', '$reserved_words')),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/static/PostgresDBStatic.php" => array(
				"PostgresDBStatic" => array(
					"all_methods" => array("ignore_local_variables" => array('$default_schema', '$default_odbc_data_source', '$default_odbc_driver', '$available_php_extension_types', '$ignore_connection_options', '$ignore_connection_options_by_extension', '$db_connection_encodings', '$column_collations', '$php_to_db_column_types', '$db_to_php_column_types', '$db_column_types', '$db_column_simple_types', '$db_column_default_values_by_type', '$db_column_types_ignored_props', '$db_column_numeric_types', '$db_column_date_types', '$db_column_text_types', '$db_column_blob_types', '$db_column_boolean_types', '$db_column_mandatory_length_types', '$db_column_auto_increment_types', '$db_boolean_type_available_values', '$db_current_timestamp_available_values', '$attribute_value_reserved_words', '$reserved_words')),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/static/MSSqlDBStatic.php" => array(
				"MSSqlDBStatic" => array(
					"all_methods" => array("ignore_local_variables" => array('$default_schema', '$default_odbc_data_source', '$default_odbc_driver', '$available_php_extension_types', '$ignore_connection_options', '$ignore_connection_options_by_extension', '$db_connection_encodings', '$db_table_column_collations', '$db_connection_encodings_to_collations', '$php_to_db_column_types', '$db_to_php_column_types', '$db_column_types', '$db_column_simple_types', '$db_column_default_values_by_type', '$db_column_types_ignored_props', '$db_column_numeric_types', '$db_column_date_types', '$db_column_text_types', '$db_column_blob_types', '$db_column_boolean_types', '$db_column_mandatory_length_types', '$db_column_auto_increment_types', '$db_boolean_type_available_values', '$db_current_timestamp_available_values', '$attribute_value_reserved_words', '$mssqlserver_data_types', '$reserved_words')),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/statement/MySqlDBStatement.php" => array(
				"MySqlDBStatement" => array(
					"methods" => array(
						"getCreateTableAttributeStatement" => array("obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$name', '$type', '$length', '$pk', '$auto_increment', '$unsigned', '$unique', '$null', '$default', '$default_type', '$extra', '$charset', '$collation', '$comment')),
						//"getCreateTableAttributeStatement" => array("obfuscate_code" => 0),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/statement/PostgresDBStatement.php" => array(
				"PostgresDBStatement" => array(
					"methods" => array(
						"getCreateTableAttributeStatement" => array("obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$name', '$type', '$length', '$pk', '$auto_increment', '$unsigned', '$unique', '$null', '$default', '$default_type', '$extra', '$charset', '$collation', '$comment')),
						//"getCreateTableAttributeStatement" => array("obfuscate_code" => 0),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/statement/MSSqlDBStatement.php" => array(
				"MSSqlDBStatement" => array(
					"methods" => array(
						"getCreateTableAttributeStatement" => array("obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$name', '$type', '$length', '$pk', '$auto_increment', '$unsigned', '$unique', '$null', '$default', '$default_type', '$extra', '$charset', '$collation', '$comment')),
						//"getCreateTableAttributeStatement" => array("obfuscate_code" => 0),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/db/DBFileImporter.php" => array(
				"DBFileImporter" => array(
					"methods" => array(
						"parseLine" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/joinpoint/JoinPointHandler.php" => array(
				"JoinPointHandler" => array(
					"methods" => array(
						"executeJoinPoint" => array("obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$input')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/businesslogic/BusinessLogicLayer.php" => array(
				"BusinessLogicLayer" => array(
					"methods" => array(
						"callService" => array("ignore_local_variables" => array('$module', '$obj')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/cache/CacheLayer.php" => array(
				"CacheLayer" => array(
					"methods" => array(
						"executeScript" => array("ignore_local_variables" => array('$input', '$output')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/dataaccess/DataAccessLayer.php" => array(
				"DataAccessLayer" => array(
					"methods" => array(
						"initModuleServices" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/module/CMSModuleEnableHandler.php" => array(
				"CMSModuleEnableHandler" => array(
					"methods" => array(
						"createCMSModuleEnableHandlerObject" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/module/CMSModuleInstallationHandler.php" => array(
				"CMSModuleInstallationHandler" => array(
					"methods" => array(
						"createCMSModuleInstallationHandlerObject" => array("obfuscate_encapsed_string" => 1),
					),

				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/module/CMSProgramInstallationHandler.php" => array(
				"CMSProgramInstallationHandler" => array(
					"methods" => array(
						"createCMSProgramInstallationHandlerObject" => array("obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$projects', '$projects_evcs', '$layers')), //ignore $projects_evcs bc it being replaced with the hashcode of the variable $projects and is messing the code
						"getUnzippedProgramSettingsHtml" => array("obfuscate_encapsed_string" => 1),
						"includeUserUtilClass" => array("ignore_local_variables" => array('$EVC')),
						"includeAttachmentUtilClass" => array("ignore_local_variables" => array('$EVC')),
						"includeUserSessionActivitiesHandlerClass" => array("ignore_local_variables" => array('$EVC')),
					),

				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/module/CMSProgramExtraTableInstallationUtil.php" => array(
				"CMSProgramExtraTableInstallationUtil" => array(
					"methods" => array(
						"copyTableExtraAttributesSettings" => array("ignore_local_variables" => array('$table_extra_attributes_settings')),
						"mergeTableExtraAttributesSettingsFiles" => array("ignore_local_variables" => array('$table_extra_attributes_settings')),
					),

				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/CMSModuleLayer.php" => array(
				"CMSModuleLayer" => array(
					"methods" => array(
						"getModuleObj" => array("ignore_local_variables" => array('$CMSModuleHandler')),
						"getModuleSimulatorObj" => array("ignore_local_variables" => array('$CMSModuleSimulatorHandler')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/CMSExternalTemplateLayer.php" => array(
				"CMSExternalTemplateLayer" => array(
					"methods" => array(
						"getExternalVarsFromProjectTemplateContents" => array("ignore_local_variables" => array('$EVC', '$current_project_id', '$before_defined_vars')),
						"getTemplateCodeFromBlockContents" => array("ignore_local_variables" => array('$EVC', '$block_local_variables')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/SequentialLogicalActivity.php" => array(
				"SequentialLogicalActivity" => array(
					"methods" => array(
						"execute" => array("ignore_local_variables" => array('$EVC')),
						"existActionsValidCondition" => array("ignore_local_variables" => array('$EVC')),
						"initResults" => array("ignore_local_variables" => array('$EVC')),
						"executeActions" => array("obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$results')),
						"executeAction" => array("ignore_local_variables" => array('$EVC')),
						"includeActionFile" => array("ignore_local_variables" => array('$EVC')),
						"getBlockHtml" => array("ignore_local_variables" => array('$EVC')),
						"getViewHtml" => array("ignore_local_variables" => array('$EVC')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/CMSSequentialLogicalActivityLayer.php" => array(
				"CMSSequentialLogicalActivityLayer" => array(
					"methods" => array(
						"prepareHTMLHashTagsWithSequentialLogicalActivities" => array("ignore_local_variables" => array('$external_vars', '$replacement')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/CMSHtmlParserLayer.php" => array(
				"CMSHtmlParserLayer" => array(
					"methods" => array(
						"getPublicUserTypeId" => array("ignore_local_variables" => array('$EVC')),
						"getLoggedUserTypeIds" => array("ignore_local_variables" => array('$EVC')),
						"bufferLengthCallback" => array("obfuscate_name" => 0),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/CMSPagePropertyLayer.php" => array(
				"CMSPagePropertyLayer" => array(
					"methods" => array(
						"setParseFullHtml" => array("ignore_local_variables" => array('$parse_full_html')),
						"setParseRegionsHtml" => array("ignore_local_variables" => array('$parse_regions_html')),
						"setExecuteSLA" => array("ignore_local_variables" => array('$execute_sla')),
						"setParseHashTags" => array("ignore_local_variables" => array('$parse_hash_tags')),
						"setParsePTL" => array("ignore_local_variables" => array('$parse_ptl')),
						"setAddMyJSLib" => array("ignore_local_variables" => array('$add_my_js_lib')),
						"setAddWidgetResourceLib" => array("ignore_local_variables" => array('$add_widget_resource_lib')),
						"setFilterByPermission" => array("ignore_local_variables" => array('$filter_by_permission')),
						"setIncludeBlocksWhenCallingResources" => array("ignore_local_variables" => array('$include_blocks_when_calling_resources')),
						"setInitUserData" => array("ignore_local_variables" => array('$init_user_data')),
						"setMaximumUsageMemory" => array("ignore_local_variables" => array('$maximum_usage_memory')),
						"setMaximumBufferChunkSize" => array("ignore_local_variables" => array('$maximum_buffer_chunk_size')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/PresentationLayer.php" => array(
				"PresentationLayer" => array(
					"methods" => array(
						"callPage" => array("obfuscate_code" => 0, "ignore_local_variables" => array('$EVC', '$url', '$parameters')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/object/php/MyObj.php" => array(
				"MyObj" => array(
					"methods" => array(
						"setData" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/object/ObjectHandler.php" => array(
				"ObjectHandler" => array(
					"methods" => array(
						"createInstance" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/phpscript/PHPScriptHandler.php" => array(
				"PHPScriptHandler" => array(
					"methods" => array(
						"parseContent" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/phpscript/docblock/annotation/Annotation.php" => array(
				"Annotation" => array(
					"methods" => array(
						"parseValue" => array("obfuscate_encapsed_string" => 1),
						"getDefaultValue" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/phpscript/docblock/annotation/ParamAnnotation.php" => array(
				"ParamAnnotation" => array(
					"methods" => array(
						"checkMethodAnnotations" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/phpscript/docblock/DocBlockParser.php" => array(
				"DocBlockParser" => array(
					"methods" => array(
						"getTagObject" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/sqlmap/hibernate/HibernateClient.php" => array(
				"HibernateClient" => array(
					"methods" => array(
						"getHbnObj" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/util/HashTagParameter.php" => array(
				"HashTagParameter" => array(
					"methods" => array(
						"replaceHTMLHashTagParametersWithValues" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/util/MyArray.php" => array(
				"MyArray" => array(
					"methods" => array(
						"multisort" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/util/xml/MyXMLArray.php" => array(
				"MyXMLArray" => array(
					"methods" => array(
						"checkNodeConditions" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/util/web/MyCurl.php" => array(
				"MyCurl" => array(
					"methods" => array(
						"setCurlOpts" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/util/web/html/HtmlFormHandler.php" => array(
				"HtmlFormHandler" => array(
					"methods" => array(
						"parseSettingsValue" => array("ignore_local_variables" => array('$replacement', '$input_data', '$idx')),
						"parseNewInputData" => array("ignore_local_variables" => array('$input_data', '$idx')),
						"getParsedValue" => array("ignore_local_variables" => array('$replacement', '$input_data', '$idx', '$found_global_var_name')),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/workflow/WorkFlowTask.php" => array(
				"WorkFlowTask" => array(
					"methods" => array(
						"cloneTask" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/workflow/WorkFlowTaskHandler.php" => array(
				"WorkFlowTaskHandler" => array(
					"methods" => array(
						"prepareWorkFlowTasks" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/lib/org/phpframework/PHPFrameWork.php" => array(
				"PHPFrameWork" => array(
					"methods" => array(
						"checkLicence" => array("ignore_local_variables" => array('$ds', '$t', '$s', '$pmn')),
						"getLicenceInfo" => array("ignore_local_variables" => array('$ds', '$p')),
						"gLI" => array("ignore_local_variables" => array('$ds', '$p')),
					),
				),
			),
			
			//__SYSTEM/CONFIG
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/config/" => array(
				1 => array(
					"save_path" => "$dest/app/__system/layer/presentation/phpframework/src/config/",
					"all_functions" => array("obfuscate_code" => 1),
				),
			),
			
			//__SYSTEM/CONFIG
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/controller/" => array(
				1 => array(
					"save_path" => "$dest/app/__system/layer/presentation/phpframework/src/controller/",
					"all_functions" => array("obfuscate_code" => 1),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/controller/index.php" => array(
				"0" => array(
					"checkLicenceProjects" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$user_global_variables_file_path', '$user_beans_folder_path')),
				),
			),
			
			//__SYSTEM/UTIL
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/" => array(
				1 => array(
					"save_path" => "$dest/app/__system/layer/presentation/phpframework/src/util/",
					"all_properties" => array("obfuscate_name_private" => 1),
					"all_methods" => array("obfuscate_code" => 1, "obfuscate_name_private" => 1),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/PHPVariablesFileHandler.php" => array(
				"PHPVariablesFileHandler" => array(
					"methods" => array(
						"startUserGlobalVariables" => array("obfuscate_encapsed_string" => 1),
						"endUserGlobalVariables" => array("obfuscate_encapsed_string" => 1),
						"getVarsFromFileCode" => array("ignore_local_variables" => array('$file_path', '$old_defined_vars')),
					),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/WorkFlowDataAccessHandler.php" => array(
				"WorkFlowDataAccessHandler" => array(
					"methods" => array(
						"prepareParametersFromClass" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/WorkFlowBeansFileHandler.php" => array(
				"WorkFlowBeansFileHandler" => array(
					"methods" => array(
						"prepareValue" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/CMSPresentationLayerHandler.php" => array(
				"CMSPresentationLayerHandler" => array(
					"methods" => array(
						"getSetTemplateCode" => array("ignore_local_variables" => array('$EVC')),
						"getAvailableTemplatesProps" => array("ignore_local_variables" => array('$EVC', '$project_url_prefix')),
						"getPresentationLayerProjectUrl" => array("ignore_local_variables" => array('$EVC', '$project_url_prefix')),
						"getPresentationLayerProjectLogo" => array("ignore_local_variables" => array('$EVC', '$project_url_prefix')),
					),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/CMSPresentationUIAutomaticFilesHandler.php" => array(
				"CMSPresentationUIAutomaticFilesHandler" => array(
					"methods" => array(
						"removeAllUserTypeActivitySessionsCache" => array(/*"obfuscate_code" => 0, */"ignore_local_variables" => array('$EVC')),
					),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/CMSPresentationFormSettingsUIHandler.php" => array(
				"CMSPresentationFormSettingsUIHandler" => array(
					"methods" => array(
						"arraySortBasedInItemLength" => array("obfuscate_name" => 0),
					),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/CMSDeploymentSecurityHandler.php" => array(
				"CMSDeploymentSecurityHandler" => array(
					"methods" => array(
						"getAppLicenceKeys" => array("ignore_local_variables" => array('$keys')),
					),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/UserAuthenticationHandler.php" => array(
				"UserAuthenticationHandler" => array(
					"methods" => array(
						"checkUsersMaxNum" => array("ignore_local_variables" => array('$UserAuthenticationHandler', '$exceeded', '$msg')),
						"checkActionsMaxNum" => array("ignore_local_variables" => array('$UserAuthenticationHandler', '$reached', '$msg')),
					),
				),
			),
			/*$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/SequentialLogicalActivityUIHandler.php" => array(
				"SequentialLogicalActivityUIHandler" => array(
					"methods" => array(
						"getHeader" => array("obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$EVC')),
						"getWorkflowHeader" => array("obfuscate_encapsed_string" => 1),
					),
				),
			),*/
			
			//__SYSTEM/ENTITY
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/" => array(
				1 => array(
					"save_path" => "$dest/app/__system/layer/presentation/phpframework/src/entity/",
					"all_functions" => array("obfuscate_code" => 1),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/admin/index.php" => array(
				"0" => array(
					"validateLicence" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$user_global_variables_file_path', '$user_beans_folder_path')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/admin/manage_file.php" => array(
				"0" => array(
					"getRootPath" => array("obfuscate_name" => 1),
					"isProjectCreationAllowed" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$user_global_variables_file_path', '$user_beans_folder_path')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/admin/edit_task_source.php" => array(
				"0" => array(
					"parseCode" => array("ignore_local_variables" => array('$getBrokerDummyFunc', '$result')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/cms/wordpress/admin_login.php" => array(
				"0" => array(
					"getProjectCommonUrlPrefix" => array("obfuscate_code" => 0),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/cms/wordpress/install.php" => array(
				"0" => array(
					"getProjectCommonUrlPrefix" => array("obfuscate_code" => 0),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/get_module_handler_source_code.php" => array(
				"0" => array(
					"getProjectCommonUrlPrefix" => array("obfuscate_code" => 0),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/get_page_block_join_points_html.php" => array(
				"0" => array(
					"getProjectCommonUrlPrefix" => array("obfuscate_code" => 0),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/get_page_block_simulated_html.php" => array(
				"0" => array(
					"getPageIncludeFiles" => array("obfuscate_code" => 0),
					"getProjectJoinPointSettings" => array("obfuscate_code" => 0),
					"getProjectBlockSettings" => array("obfuscate_code" => 0),
					"getProjectBlockHtml" => array("obfuscate_code" => 0),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/create_presentation_uis_diagram.php" => array(
				"0" => array(
					"ConsequenceOfHackingTheLicenceInUisDiagram" => array("obfuscate_name" => 1),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/edit.php" => array(
				"0" => array(
					//"validateProjectWebrootUrls" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$project_url_prefix', '$project_common_url_prefix')),
					"getProjectUrlPrefix" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$project_url_prefix')),
					"getProjectCommonUrlPrefix" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$project_common_url_prefix')),
					"ConsequenceOfHackingTheLicence" => array("obfuscate_name" => 1),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/view_project.php" => array(
				"0" => array(
					"getProjectUrlPrefix" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$project_url_prefix')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/open_file.php" => array(
				"0" => array(
					"getProjectUrlPrefix" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$project_url_prefix')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/edit_simple_template_layout.php" => array(
				"0" => array(
					"getProjectTemplateHtml" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/template_preview.php" => array(
				"0" => array(
					"getProjectTemplateHtml" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/template_region_sample.php" => array(
				"0" => array(
					"getProjectTemplateHtml" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$user_global_variables_file_path')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/template_samples.php" => array(
				"0" => array(
					"getProjectUrlPrefix" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$project_url_prefix')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/manage_references.php" => array(
				"0" => array(
					"getLayers" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$UserAuthenticationHandler', '$user_global_variables_file_path', '$user_beans_folder_path', '$layers')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/save.php" => array(
				"0" => array(
					"validateProjectWebrootUrls" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$project_url_prefix', '$project_common_url_prefix')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/testunit/edit_test.php" => array(
				"0" => array(
					"getProjectUrlPrefix" => array("obfuscate_name" => 1, "ignore_local_variables" => array('$EVC', '$selected_project_id', '$project_url_prefix')),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/sequentiallogicalactivity/get_sla_action_result_properties.php" => array(
				"0" => array(
					"getValueBasedInValueType" => array("obfuscate_encapsed_string" => 1),
				),
			),
			
			//__SYSTEM/VIEW
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/view/" => array(
				1 => array(
					"save_path" => "$dest/app/__system/layer/presentation/phpframework/src/view/",
					"all_functions" => array("obfuscate_code" => 1),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/view/presentation/edit_block_simple.php" => array(
				"0" => array(
					"getPresentationProjectWebrootUrl" => array("obfuscate_code" => 0),
					"getPresentationProjectCommonWebrootUrl" => array("obfuscate_code" => 0),
				),
			),
			
			//__SYSTEM COMMON MODULES
			$this->cms_path . "/app/__system/layer/presentation/common/src/module/" => array(
				1 => array(
					"skip" => true,
					/*"save_path" => "$dest/app/__system/layer/presentation/common/src/module/",
					"all_properties" => array("obfuscate_name_private" => 1),
					"all_methods" => array("obfuscate_code" => 1, "obfuscate_name_private" => 1, "ignore_local_variables" => array('$EVC', '$project_url_prefix', '$project_common_url_prefix', '$default_db_driver')),*/
				),
			),
			/*$this->cms_path . "/app/__system/layer/presentation/common/src/module/common/admin/CommonModuleAdminUtil.php" => array(
				"CommonModuleAdminUtil" => array(
					"methods" => array(
						"printTemplate" => array("obfuscate_code" => 0),
					),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/common/src/module/common/admin/CommonModuleAdminTableExtraAttributesUtil.php" => array(
				"CommonModuleAdminTableExtraAttributesUtil" => array(
					"methods" => array(
						"flushCache" => array("obfuscate_code" => 0),
						"initUrlPrefixes" => array("obfuscate_code" => 0),
					),
				),
			),
			$this->cms_path . "/app/__system/layer/presentation/common/src/module/common/CommonModuleSettingsUtil.php" => array(
				"CommonModuleSettingsUtil" => array(
					"methods" => array(
						"getAllObjectTypes" => array("obfuscate_code" => 0),
					),
				),
			),*/
			
			//LAYER PRESENTATION COMMON MODULES
			$this->cms_path . "/app/layer/presentation/common/src/module/" => array(
				1 => array(
					"skip" => true,
					/*"save_path" => "$dest/app/layer/presentation/common/src/module/",
					"all_properties" => array("obfuscate_name_private" => 1),
					"all_methods" => array("obfuscate_code" => 1, "obfuscate_name_private" => 1, "ignore_local_variables" => array('$EVC', '$project_url_prefix', '$project_common_url_prefix', '$default_db_driver')),*/
				),
			),
			
			//LAYER SOA MODULES
			$this->cms_path . "/app/layer/soa/module/" => array(
				1 => array(
					"skip" => true,
					/*"save_path" => "$dest/app/layer/soa/module/",
					"all_properties" => array("obfuscate_name_private" => 1),
					"all_methods" => array("obfuscate_code" => 1, "obfuscate_name_private" => 1, "ignore_local_variables" => array('$data')),
					"strip_doc_comments" => 0,*/
				),
			),
			
			//BUSINESS LOGIC - AUTH
			$this->cms_path . "/app/__system/layer/businesslogic/auth/" => array(
				1 => array(
					"save_path" => "$dest/app/__system/layer/businesslogic/auth/",
					"all_properties" => array("obfuscate_name_private" => 1),
					"all_methods" => array("obfuscate_code" => 1, "obfuscate_name_private" => 1, "ignore_local_variables" => array('$data')),
					"strip_doc_comments" => 0,
				),
			),
			
			//APP.PHP
			$this->cms_path . "/app/app.php" => array(
				1 => array(
					"save_path" => "$dest/app/app.php",
					"all_functions" => array("obfuscate_code" => 1),
				),
			),
			
			//SETUP.PHP
			$this->cms_path . "/app/setup.php" => array(
				1 => array(
					"save_path" => "$dest/app/setup.php",
				),
			),
		);
		
		return $files_settings;
	}
	
	public function getDefaultFilesToAvoidWarnings() {
		$avoid_warnings_for_files = array(
			$this->cms_path . "/app/lib/org/phpframework/bean/BeanFactory.php",
			$this->cms_path . "/app/lib/org/phpframework/bean/BeanSettingsFileFactory.php",
			$this->cms_path . "/app/lib/org/phpframework/broker/client/rest/RESTBrokerClient.php",
			$this->cms_path . "/app/lib/org/phpframework/broker/server/rest/RESTHibernateDataAccessBrokerServer.php",
			$this->cms_path . "/app/lib/org/phpframework/cms/wordpress/WordPressCMSBlockHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/cms/wordpress/WordPressHacker.php",
			$this->cms_path . "/app/lib/org/phpframework/cms/wordpress/WordPressInstallationHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/cms/wordpress/WordPressCMSBlockSettings.php",
			$this->cms_path . "/app/lib/org/phpframework/cms/laravel/LaravelProjectHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/compression/FileCompressionFactory.php",
			$this->cms_path . "/app/lib/org/phpframework/db/DB.php",
			$this->cms_path . "/app/lib/org/phpframework/db/DBStatic.php",
			$this->cms_path . "/app/lib/org/phpframework/db/dump/DBDumper.php",
			$this->cms_path . "/app/lib/org/phpframework/db/statement/MySqlDBStatement.php",
			$this->cms_path . "/app/lib/org/phpframework/db/statement/PostgresDBStatement.php",
			$this->cms_path . "/app/lib/org/phpframework/db/statement/MSSqlDBStatement.php",
			$this->cms_path . "/app/lib/org/phpframework/joinpoint/JoinPointHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/businesslogic/BusinessLogicLayer.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/cache/CacheLayer.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/dataaccess/DataAccessLayer.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/module/CMSModuleEnableHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/module/CMSModuleInstallationHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/module/CMSProgramInstallationHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/module/CMSProgramExtraTableInstallationUtil.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/CMSModuleLayer.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/CMSExternalTemplateLayer.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/SequentialLogicalActivity.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/CMSSequentialLogicalActivityLayer.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/cms/CMSHtmlParserLayer.php",
			$this->cms_path . "/app/lib/org/phpframework/layer/presentation/PresentationLayer.php",
			$this->cms_path . "/app/lib/org/phpframework/object/php/MyObj.php",
			$this->cms_path . "/app/lib/org/phpframework/object/ObjectHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/phpscript/docblock/annotation/Annotation.php",
			$this->cms_path . "/app/lib/org/phpframework/phpscript/docblock/annotation/ParamAnnotation.php",
			$this->cms_path . "/app/lib/org/phpframework/phpscript/docblock/DocBlockParser.php",
			$this->cms_path . "/app/lib/org/phpframework/phpscript/PHPScriptHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/phpscript/sample_php_code_obfuscator.php",
			$this->cms_path . "/app/lib/org/phpframework/ptl/PHPTemplateLanguage.php",
			$this->cms_path . "/app/lib/org/phpframework/sqlmap/hibernate/HibernateClient.php",
			$this->cms_path . "/app/lib/org/phpframework/sqlmap/SQLMapIncludesHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/util/xml/MyXMLArray.php",
			$this->cms_path . "/app/lib/org/phpframework/util/xml/XMLSerializer.php",
			$this->cms_path . "/app/lib/org/phpframework/util/HashTagParameter.php",
			$this->cms_path . "/app/lib/org/phpframework/util/MyArray.php",
			$this->cms_path . "/app/lib/org/phpframework/util/web/MyCurl.php",
			$this->cms_path . "/app/lib/org/phpframework/util/web/html/HtmlFormHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/webservice/layer/PresentationLayerWebService.php", //it contains a commented eval, but that will be uncommented when the package manager runs
			$this->cms_path . "/app/lib/org/phpframework/webservice/layer/LayerWebService.php", //it contains a include_once of CryptoKeyHandler
			$this->cms_path . "/app/lib/org/phpframework/workflow/WorkFlowTask.php",
			$this->cms_path . "/app/lib/org/phpframework/workflow/WorkFlowTaskHandler.php",
			$this->cms_path . "/app/lib/org/phpframework/PHPFrameWork.php",
			$this->cms_path . "/app/lib/org/phpframework/PHPFrameWorkHandler.php",
			
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/AdminMenuHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/PHPVariablesFileHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/WorkFlowDataAccessHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/WorkFlowBeansFileHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/WorkFlowBeansFolderHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/WorkFlowPresentationHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/CMSPresentationLayerHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/CMSPresentationUIAutomaticFilesHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/CMSDeploymentSecurityHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/WorkFlowTestUnitHandler.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/util/UserAuthenticationHandler.php",
			
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/config/authentication.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/controller/index.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/admin/index.php", //it contains a commented eval, but that will be uncommented when the package manager runs
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/admin/manage_file.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/admin/edit_task_source.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/cms/wordpress/admin_login.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/cms/wordpress/install.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/edit.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/view_project.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/open_file.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/get_module_handler_source_code.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/get_page_block_join_points_html.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/get_page_block_simulated_html.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/edit_simple_template_layout.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/template_preview.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/template_region_sample.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/template_samples.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/manage_references.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/presentation/save.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/dao/create_file.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/testunit/edit_test.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/testunit/create_test.php",
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/entity/sequentiallogicalactivity/get_sla_action_result_properties.php",
			
			$this->cms_path . "/app/__system/layer/presentation/phpframework/src/view/presentation/edit_block_simple.php",
			
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/attachment/AttachmentUI.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/attachment/AttachmentUtil.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/comment/CommentUI.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/common/admin/CommonModuleAdminUtil.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/common/CommonModuleTableExtraAttributesSettingsUtil.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/common/admin/CommonModuleAdminTableExtraAttributesUtil.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/common/CommonModuleSettingsUI.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/common/CommonModuleSettingsUtil.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/common/CommonSettings.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/form/get_form_action_result_properties.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/user/ManageUserTypeActivityObjectsUtil.php",
			//$this->cms_path . "/app/__system/layer/presentation/common/src/module/wordpress/get_html_contens/CMSModuleSettingsHtml.php",
			
			//$this->cms_path . "/app/layer/presentation/common/src/module/*/CMSModuleHandlerImpl.php",
			//$this->cms_path . "/app/layer/presentation/common/src/module/*/*UI.php",
			//$this->cms_path . "/app/layer/presentation/common/src/module/*/*Util.php",
			//$this->cms_path . "/app/layer/presentation/common/src/module/common/CommonSettings.php",
			//$this->cms_path . "/app/layer/presentation/common/src/module/common/ObjectToObjectValidationHandler.php",
			//$this->cms_path . "/app/layer/presentation/common/src/module/translator/include_text_translator_handler.php",
			//$this->cms_path . "/app/layer/presentation/common/src/module/workerpool/WorkerPoolHandler.php",
		);
		
		return $avoid_warnings_for_files;
	}
}
?>
