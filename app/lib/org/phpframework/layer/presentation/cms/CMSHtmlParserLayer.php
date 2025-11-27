<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

include_once get_lib("org.phpframework.layer.presentation.cms.CMSLayer");

class CMSHtmlParserLayer {
	const CACHE_DIR_NAME = "html_parser/";
	
	private $CMSLayer;
	
	//internal vars
	private $resources_url;
	private $my_js_lib_url;
	private $widget_resource_lib_js_url;
	private $widget_resource_lib_css_url;
	private $chart_js_url;
	private $calendar_js_url;
	
	private $parse_hash_tags;
	private $parse_ptl;
	private $add_my_js_lib;
	private $add_widget_resource_lib;
	private $filter_by_permission;
	private $exists_sla_to_be_always_executed;
	
	private $grab_template_html;
	private $buffer_exceeded;
	private $buffer_exceeded_flags;
	private $buffer_exceeded_html;
	private $entities_sla_settings;
	private $my_js_lib_included;
	private $widget_resource_js_code_included; //used only in parseRenderedRegionHtml
	private $widget_resource_lib_js_included;
	private $widget_resource_lib_css_included;
	private $template_file_contents;
	private $template_file_props;
	private $entities_html_props;
	private $public_user_type_id;
	private $logged_user_type_ids;
	
	private $entity_code;
	private $project_url_prefix;
	private $project_common_url_prefix;
	
	public function __construct(CMSLayer $CMSLayer) {
		$this->CMSLayer = $CMSLayer;
		
		$this->resources_url = null;
		$this->my_js_lib_url = null;
		$this->widget_resource_lib_js_url = null;
		$this->widget_resource_lib_css_url = null;
		$this->chart_js_url = null;
		$this->calendar_js_url = null;
		
		$this->parse_hash_tags = null;
		$this->parse_ptl = null;
		$this->add_my_js_lib = null;
		$this->add_widget_resource_lib = null;
		$this->filter_by_permission = null;
		$this->exists_sla_to_be_always_executed = null;
		
		$this->grab_template_html = false;
		$this->buffer_exceeded = false;
		$this->buffer_exceeded_flags = array();
		$this->buffer_exceeded_html = null;
		$this->entities_sla_settings = null;
		$this->my_js_lib_included = false;
		$this->widget_resource_js_code_included = false;
		$this->widget_resource_lib_js_included = false;
		$this->widget_resource_lib_css_included = false;
		$this->template_file_contents = null;
		$this->template_file_props = null;
		$this->entities_html_props = null;
		$this->public_user_type_id = null;
		$this->logged_user_type_ids = null;
	}
	
	/* WIDGETS FUNCTIONS */
	
	public function init($entity_code, $project_url_prefix, $project_common_url_prefix) {
		$this->entity_code = $entity_code;
		$this->project_url_prefix = $project_url_prefix;
		$this->project_common_url_prefix = $project_common_url_prefix;
		$this->resources_url = $entity_code && $project_url_prefix ? $project_url_prefix . "resource/" . $entity_code : null;
		$this->my_js_lib_url = $project_common_url_prefix ? $project_common_url_prefix . "js/MyJSLib.js" : null;
		$this->widget_resource_lib_js_url = $project_common_url_prefix ? $project_common_url_prefix . "js/MyWidgetResourceLib.js" : null;
		$this->widget_resource_lib_css_url = $project_common_url_prefix ? $project_common_url_prefix . "css/MyWidgetResourceLib.css" : null;
		$this->chart_js_url = $project_common_url_prefix /*&& file_exists($this->CMSLayer->getEVC()->getWebrootPath( $this->CMSLayer->getEVC()->getCommonProjectName() ) . "vendor/chartjs/chart.js")*/ ? $project_common_url_prefix . "vendor/chartjs/chart.js" : null; //getWebrootPath is commented to avoid extra load. This will be taken care from the browser side.
		$this->calendar_js_url = $project_common_url_prefix /*&& file_exists($this->CMSLayer->getEVC()->getWebrootPath( $this->CMSLayer->getEVC()->getCommonProjectName() ) . "vendor/fullcalendar/dist/index.global.js")*/ ? $project_common_url_prefix . "vendor/fullcalendar/dist/index.global.min.js" : null; //getWebrootPath is commented to avoid extra load. This will be taken care from the browser side.
		
		//check if parse_full_html is active and if it is and the parse_regions_html is active too, disable the parse_regions_html, otherwise wil be redundant, bc the parse_full_html already includes the parse_regions_html.
		$parse_full_html = $this->CMSLayer->getCMSPagePropertyLayer()->getParseFullHtml();
		$parse_regions_html = $this->CMSLayer->getCMSPagePropertyLayer()->getParseRegionsHtml();
		
		if ($parse_full_html && $parse_regions_html)
			$this->CMSLayer->getCMSPagePropertyLayer()->setParseRegionsHtml(false);
	}
	
	//Note that this method will be called multiple times (one time for each rendered region) in CMSTemplateLayer->renderRegion(..)
	public function parseRenderedRegionHtml(&$html) {
		$parse_html = $this->CMSLayer->getCMSPagePropertyLayer()->getParseRegionsHtml();
		
		//if the user forces the html to don't be parsed, we need to ignore the code below.
		if (!$parse_html)
			return;
		
		$execute_sla = $this->CMSLayer->getCMSPagePropertyLayer()->getExecuteSLA();
		
		if ($execute_sla && !$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->isSLAExecuted())
			$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->executeSequentialLogicalActivities(); //This method only executes the sla_settings once, if they were not executed yet.
		
		//if entities output is too big, even if user intends to parse the html or any other action, the system won't let him. This avoids the system to parse huge data that will make the request too slow. If the user really wants this to be parsed, he needs to increase the maximum_usage_memory for this page.
		$maximum_usage_memory = $this->CMSLayer->getCMSPagePropertyLayer()->getMaximumUsageMemory();
		
		if ($maximum_usage_memory > 0 && ($mem = $this->getRenderedRegionHtmlUsageMemory($html)) > $maximum_usage_memory) {
			$this->debug_log("Page region cannot be parsed because the defined memory size in the page advanced settings was overloaded. If you really wants this page region to be parsed, you need to increase the maximum_usage_memory for this page. The current Rendered-Region-Html-Usage-Memory is: " . $mem . " bytes.");
			
			$this->CMSLayer->getCMSPagePropertyLayer()->setParseRegionsHtml(false); //set parse_html to false for the next time we call this method in the same request, we don't need to compare the usage memory anymore and the server will return a faster response.
			return;
		}
		
		//get other page properties
		$parse_hash_tags = $this->CMSLayer->getCMSPagePropertyLayer()->getParseHashTags();
		$parse_ptl = $this->CMSLayer->getCMSPagePropertyLayer()->getParsePTL();
		$add_my_js_lib = $this->CMSLayer->getCMSPagePropertyLayer()->getAddMyJSLib();
		$add_widget_resource_lib = $this->CMSLayer->getCMSPagePropertyLayer()->getAddWidgetResourceLib();
		$filter_by_permission = $this->CMSLayer->getCMSPagePropertyLayer()->getFilterByPermission();
		
		//get latest sla settings and save it to use below
		if ($this->entities_sla_settings === null)
			$this->entities_sla_settings = $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->getSLASettings();
		
		//prepare html according with page properties
		//Note that property === null means it is "auto", which means the system will figure out if it should be true
		if ($parse_hash_tags === null && 
			(!empty($this->entities_sla_settings) || $this->existsSuperGlobalHashTagInHtml($html)) && 
			$this->existsHashTagInHtml($html)
		)
			$parse_hash_tags = true;
		
		if ($parse_ptl === null && $this->existsPTLInHtml($html))
			$parse_ptl = true;
		
		$is_sla_executed = $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->isSLAExecuted();
		$exists_sla_to_be_always_executed = ($execute_sla || $execute_sla === null) && !$is_sla_executed && !$parse_hash_tags && !$parse_ptl ? $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->existSequentialLogicalActivitiesToBeAlwaysExecuted() : false;
		
		//check if sla settings was already executed and if not, execute it, then compare the getMaximumUsageMemory and only if OK parse html.	
		if (($execute_sla || $execute_sla === null) && !$is_sla_executed && ($parse_hash_tags || $parse_ptl || $exists_sla_to_be_always_executed)) {
			$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->executeSequentialLogicalActivities(); //This method only executes the sla_settings once, if they were not executed yet.
			
			if ($maximum_usage_memory > 0 && ($mem = $this->getRenderedRegionHtmlUsageMemory($html)) > $maximum_usage_memory) {
				$this->debug_log("Page region cannot be parsed because the defined memory size in the page advanced settings was overloaded. If you really wants this page region to be parsed, you need to increase the maximum_usage_memory for this page. The current Rendered-Region-Html-Usage-Memory is: " . $mem . " bytes.");
				
				$this->CMSLayer->getCMSPagePropertyLayer()->setParseRegionsHtml(false); //set parse_html to false for the next time we call this method in the same request, we don't need to compare the usage memory anymore and the server will return a faster response.
				return;
			}
		}
		
		//if parse hash tags is true or null and hash tags really exists in html and exists slas.
		//paseing html executing the widget-resources and replacing the html accordingly. Basically replaces the #...var name...# tags.
		if ($parse_hash_tags)
			$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->prepareHTMLHashTagsWithSequentialLogicalActivities($html);
		
		//if parse ptl is true or null and ptl code really exists in html. Even if no sla, still check ptl bc it could have static data.
		//paseing html executing the widget-resources and replacing the html accordingly. Basically replaces the ptl code.
		if ($parse_ptl && $this->existsPTLInHtml($html))
			$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->prepareHTMLPTLWithSequentialLogicalActivities($html);
		
		//if filter_by_permission is true or null and data-widget-permissions really exists in html.
		//paseing html with user permission and remove or hide elements accordingly.
		if (($filter_by_permission || $filter_by_permission === null) && $this->existsHtmlWidgetsPermissionsInHtml($html))
			$this->filterHtmlWidgetsPermissionsInHtml($html); 
		
		//if add_widget_resource_lib is true or null and data-widget really exists in html.
		//adding MyWidgetResourceLib javascript code to html.
		if (($add_widget_resource_lib || $add_widget_resource_lib === null) && $this->existsHtmlWidgetsInHtml($html)) {
			//prepare code
			$code = "";
			
			if (!$this->widget_resource_js_code_included) { //Note that this method will be called multiple times (one time for each rendered region), and this piece of javascript code below only needs to be included once.
				$code .= $this->getJavascriptCodeWithWidgetsVars();
				$code .= $this->getJavascriptCodeWithSLAResults(); //This already checks the memory usage of the sla results
				$this->widget_resource_js_code_included = true;
			}
			
			//checks if html already contains the widget lib file. Maybe was hard-coded by the user. 
			if (!$this->widget_resource_lib_js_included && $this->existsWidgetResourceLibJSUrlInHtml($html))
				$this->widget_resource_lib_js_included = true;
			
			if (!$this->widget_resource_lib_css_included && $this->existsWidgetResourceLibCSSUrlInHtml($html))
				$this->widget_resource_lib_css_included = true;
			
			if (!$this->widget_resource_lib_js_included) {
				$code .= $this->getJavascriptCodeWithWidgetResourceLibJS();
				$this->widget_resource_lib_js_included = true;
			}
			//error_log("parseRenderedRegionHtml widget_resource_lib_js_included:".$this->widget_resource_lib_js_included."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			if (!$this->widget_resource_lib_css_included) {
				$code .= $this->getJavascriptCodeWithWidgetResourceLibCSS();
				$this->widget_resource_lib_css_included = true;
			}
			
			//start the MyWidgetResourceLib by calling the initWidgets method
			$code .= $this->getJavascriptCodeWithWidgetResourceLibJSInit();
			
			//add code to the end of html
			$html .= $code;
		}
		
		//if add_my_js_lib is true or null and data-xxx really exists in html.
		//adding MyJSLib javascript code to html.
		if ($add_my_js_lib || $add_my_js_lib === null) {
			//checks if html already contains the widget lib file. Maybe was hard-coded by the user. 
			if (!$this->my_js_lib_included && $this->existsMyJSLibUrlInHtml($html))
				$this->my_js_lib_included = true;
			
			if (!$this->my_js_lib_included && $this->existsMyJSLibHtmlAttributesInHtml($html)) {
				$this->my_js_lib_included = true;
				
				//prepare code
				$code = $this->getJavascriptCodeWithMyJSLib();
				
				//add code to the end of html
				$html .= $code;
			}
		}
	}
	
	//to be called before the 'include template file'
	//note that when we call this method all entities were already executed.
	public function beforeIncludeTemplate($template_path) {
		$parse_html = $this->CMSLayer->getCMSPagePropertyLayer()->getParseFullHtml();
		
		//if the user forces the html to don't be parsed, we need to ignore the code below.
		if (!$parse_html)
			return;
		
		$execute_sla = $this->CMSLayer->getCMSPagePropertyLayer()->getExecuteSLA();
		
		if ($execute_sla && !$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->isSLAExecuted())
			$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->executeSequentialLogicalActivities(); //This method only executes the sla_settings once, if they were not executed yet.
		
		//if entities output is too big, even if user intends to parse the html or any other action, the system won't let him. This avoids the system to parse huge data that will make the request too slow. If the user really wants this to beparsed, he needs to increase the maximum_usage_memory for this page.
		$maximum_usage_memory = $this->CMSLayer->getCMSPagePropertyLayer()->getMaximumUsageMemory();
		
		if ($maximum_usage_memory > 0 && ($mem = $this->getEntitiesUsageMemory()) > $maximum_usage_memory) {
			$this->debug_log("Page full html cannot be parsed because the defined memory size in the page advanced settings was overloaded. If you really wants this page full html to be parsed, you need to increase the maximum_usage_memory for this page. The current Entities-Usage-Memory is: " . $mem . " bytes.");
			
			$this->CMSLayer->getCMSPagePropertyLayer()->setParseFullHtml(false); //set parse_html to false for the next time we call this method in the same request, we don't need to compare the usage memory anymore and the server will return a faster response.
			return;
		}
		
		//resets template vars bc we are starting a new process for a new template
		$this->template_file_contents = null;
		$this->template_file_props = null;
		$this->buffer_exceeded = false;
		$this->buffer_exceeded_flags = array();
		$this->buffer_exceeded_html = null;
		
		//get other page properties
		$parse_hash_tags = $this->CMSLayer->getCMSPagePropertyLayer()->getParseHashTags();
		$parse_ptl = $this->CMSLayer->getCMSPagePropertyLayer()->getParsePTL();
		$add_my_js_lib = $this->CMSLayer->getCMSPagePropertyLayer()->getAddMyJSLib();
		$add_widget_resource_lib = $this->CMSLayer->getCMSPagePropertyLayer()->getAddWidgetResourceLib();
		$filter_by_permission = $this->CMSLayer->getCMSPagePropertyLayer()->getFilterByPermission();
		
		//Note that this method can be called multiple times (for multiple templates), which means we need to be carefull when we get the original properties, so they don't overwrite the loaded values.
		$this->parse_hash_tags = $parse_hash_tags !== null ? $parse_hash_tags : $this->parse_hash_tags;
		$this->parse_ptl = $parse_ptl !== null ? $parse_ptl : $this->parse_ptl;
		$this->add_my_js_lib = $add_my_js_lib !== null ? $add_my_js_lib : $this->add_my_js_lib;
		$this->add_widget_resource_lib = $add_widget_resource_lib !== null ? $add_widget_resource_lib : $this->add_widget_resource_lib;
		$this->filter_by_permission = $filter_by_permission !== null ? $filter_by_permission : $this->filter_by_permission;
		
		//get latest sla settings and save it to use below
		if ($this->entities_sla_settings === null)
			$this->entities_sla_settings = $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->getSLASettings();
		
		//prepare page properties with "auto" value
		//$xxx === null means that is "auto", which means the system will figure out if it should be true
		if ($this->parse_hash_tags === null && (
			(!empty($this->entities_sla_settings) || $this->containsSLAInFile($template_path) || $this->existsSuperGlobalHashTagInEntitiesHtml()) &&
			($this->existsHashTagInEntitiesHtml() || $this->containsHashTagInFile($template_path))
		))
			$this->parse_hash_tags = true;
		
		if ($this->parse_ptl === null && (
			$this->existsPTLInEntitiesHtml() || $this->containsPTLInFile($template_path)
		))
			$this->parse_ptl = true;
		
		if (($this->add_my_js_lib || $this->add_my_js_lib === null) && ( //checks first if MyJSLib.js file is already included
			$this->existsMyJSLibInEntitiesHtml() || $this->existsMyJSLibInFile($template_path)
		)) {
			$this->add_my_js_lib = false;
			$this->my_js_lib_included = true;
		}
		
		if ($this->add_my_js_lib === null && ( //and only if MyJSLib.js file is not yet included, check if it needs to be included
			$this->existsMyJSLibHtmlAttributesInEntitiesHtml() || $this->existsMyJSLibHtmlAttributesInFile($template_path)
		))
			$this->add_my_js_lib = true;
		
		if ($this->add_widget_resource_lib === null && (
			$this->existsHtmlWidgetsInEntitiesHtml() || $this->existsHtmlWidgetsInFile($template_path)
		))
			$this->add_widget_resource_lib = true;
		
		if ($this->filter_by_permission === null && (
			$this->existsHtmlWidgetsPermissionsInEntitiesHtml() || $this->existsHtmlWidgetsPermissionsInFile($template_path)
		))
			$this->filter_by_permission = true;
		
		$is_sla_executed = $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->isSLAExecuted();
		
		if ($this->exists_sla_to_be_always_executed == null && ($execute_sla || $execute_sla === null) && !$is_sla_executed && !$this->parse_hash_tags && !$this->parse_ptl)
			$this->exists_sla_to_be_always_executed = $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->existSequentialLogicalActivitiesToBeAlwaysExecuted();
		
		//check if sla settings was already executed and if not, execute it, then compare the getMaximumUsageMemory and only if OK parse html.	
		if (($execute_sla || $execute_sla === null) && !$is_sla_executed && ($this->parse_hash_tags || $this->parse_ptl || $this->exists_sla_to_be_always_executed)) {
			$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->executeSequentialLogicalActivities(); //This method only executes the sla_settings once, if they were not executed yet.
			
			if ($maximum_usage_memory > 0 && ($mem = $this->getEntitiesUsageMemory()) > $maximum_usage_memory) {
				$this->debug_log("Page full html cannot be parsed because the defined memory size in the page advanced settings was overloaded. If you really wants this page full html to be parsed, you need to increase the maximum_usage_memory for this page. The current Entities-Usage-Memory is: " . $mem . " bytes.");
				
				$this->CMSLayer->getCMSPagePropertyLayer()->setParseFullHtml(false); //set parse_html to false for the next time we call this method in the same request, we don't need to compare the usage memory anymore and the server will return a faster response.
				return;
			}
		}
		
		//prepare grab_template_html, if not yet called
		if (!$this->grab_template_html)
			$this->grab_template_html = $this->parse_hash_tags || $this->parse_ptl || $this->add_my_js_lib || $this->add_widget_resource_lib || $this->filter_by_permission;
		
		//catch html output
		if ($this->grab_template_html) {
			$maximum_buffer_chunk_size = $this->CMSLayer->getCMSPagePropertyLayer()->getMaximumBufferChunkSize();
			
			//if template file contains SLAs or PTL code, then grab the full template html
			$parse_template_full_html = !$maximum_buffer_chunk_size || //if maximum_buffer_chunk_size is 0 (unlimited)
									($this->parse_hash_tags && $this->containsHashTagInFile($template_path) && (
										!empty($this->entities_sla_settings) || $this->containsSLAInFile($template_path) || $this->existsSuperGlobalHashTagInEntitiesHtml()
									)) || //if parse hash tags is true and hash tags really exists in html and exists slas.
									($this->parse_ptl && $this->containsPTLInFile($template_path)); //if parse ptl is true and ptl code really exists in template. Even if no sla, still check ptl bc it could have static data.
			
			//prepare ob start with or without buffer maximum length
			if ($parse_template_full_html) //if parse_template_full_html, then try to get the full template html
				ob_start(null, 0); //no-callback and no-limit-buffer
			else {
				//cache logged_user_type_ids bc we cannot left the code: '$GLOBALS["UserSessionActivitiesHandler"]->getUserData();' to be executed inside of the bufferLengthCallback, bc it will call a business logic service, which will init the Business Logic Layer, which will call the PHPScriptHandler::parseContent, which calls another ob_start. And we cannot call the ob_start inside of the bufferLengthCallback method. 
				//Note from php site: ob_end_clean(), ob_end_flush(), ob_clean(), ob_flush() and ob_start() may not be called from a ob_start callback function. If you call them from callback function, the behavior is undefined. Read more at https://www.php.net/ob_start
				$this->getLoggedUserTypeIds(); //Very important! Do not touch this!
				
				//IMPORTANT: IF THE parseTemplateHtml METHOD CALLS ANY BROKER LAYER THAT CALLS THE ob_start METHOD, WE MUST CALL THAT CODE HERE FIRST AND THEN CACHE IT, SO WE CAN USE THE CACHE INSTEAD.
				
				//error_log("maximum_buffer_chunk_size:$maximum_buffer_chunk_size\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				
				//starts ob_start, but checks the size of the output html and if it is bigger than x, flush the output to browser. But still tries to find the body tag and add it the $code
				ob_start(array(&$this, "bufferLengthCallback"), $maximum_buffer_chunk_size); //$maximum_buffer_chunk_size: 10MB (an example)
			}
		}
	}
	
	public function afterIncludeTemplate($template_path) {
		$parse_html = $this->CMSLayer->getCMSPagePropertyLayer()->getParseFullHtml();
		
		//if the user forces the html to don't be parsed, we need to ignore the code below.
		if (!$parse_html)
			return;
		
		if ($this->grab_template_html) {
			$html = ob_get_contents();
			ob_end_clean();
			
			//This means that the bufferLengthCallback was called from the ob_end_clean
			if (!empty($this->buffer_exceeded_flags["PHP_OUTPUT_HANDLER_END"])) {
				//error_log("buffer_exceeded_flags:".print_r($this->buffer_exceeded_flags, 1)."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				//error_log("is buffer the same:".($html==$this->buffer_exceeded_html)."!\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				
				$html = $this->buffer_exceeded_html;
			}
			//error_log("afterIncludeTemplate->parseTemplateHtml\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			//parse template html (if bufferLengthCallback was called before, then parses the last sub-string of the html, otherwise parses the full html)
			$this->parseTemplateHtml($html);
			
			echo $html;
		}
	}
	
	/* PRIVATE FUNCTIONS - PARSE */
	
	//this function will be inside of EVC or CMSTemplateHandler, or etc...
	private function bufferLengthCallback($buffer, $flags) {
		//error_log("\n\n\nbufferLengthCallback->parseTemplateHtml($flags|".strlen($buffer)."):$buffer\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		//Warning from php.net: Some web servers (e.g. Apache) change the working directory of a script when calling the callback function. You can change it back by e.g. chdir(dirname($_SERVER['SCRIPT_FILENAME'])) in the callback function.
		if (isset($_SERVER['SCRIPT_FILENAME']))
			chdir(dirname($_SERVER['SCRIPT_FILENAME']));
		
		//prepare internal vars
		//more flags in https://www.php.net/manual/en/outcontrol.constants.php
		$is_output_handler_end = ($flags & PHP_OUTPUT_HANDLER_END) || ($flags & PHP_OUTPUT_HANDLER_FINAL);
		
		if ($is_output_handler_end) { //it means it comes from the ob_end_clean or ob_end_flush
			$this->buffer_exceeded_flags["PHP_OUTPUT_HANDLER_END"] = true;
			$this->buffer_exceeded_html = $buffer;
		}
		else {
			$this->buffer_exceeded = true;
			
			//$this->debug_log("Page full html cannot be parsed because the defined memory size in the page advanced settings was overloaded. If you really wants this page full html to be parsed, you need to increase the maximum_usage_memory for this page.");
			
			//parse template html but only a specific sub-string
			$this->parseTemplateHtml($buffer);
		}
		
		return $buffer;
	}
	
	//Note that when this function gets called from the bufferLengthCallback, it cannot call any business logic service or any other function that will execute an ob_start method. PHP site says ob_end_clean(), ob_end_flush(), ob_clean(), ob_flush() and ob_start() may not be called from a ob_start callback function. If you call them from callback function, the behavior is undefined. Read more at https://www.php.net/ob_start
	private function parseTemplateHtml(&$html) {
		//if buffer not exceeded, this is, full html
		if (!$this->buffer_exceeded) {
			//error_log("\n\n\nparseTemplateHtml:buffer_exceeded:".$this->buffer_exceeded."\n\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			//if $html is the full template html and template contains hash tags or ptl code.
			//Note that is very important to parse the full template html (without any buffer), bc if parseTemplateHtml is called from bufferLengthCallback and the $html contain something like '#...' and the next html will contain '...#', then the html will not correctly parsed. The same will happen if PTL tags get break accross multiple html buffers.
			
			//prepare parse_hash_tags and parse_ptl
			$parse_hash_tags = $this->parse_hash_tags && $this->existsHashTagInHtml($html) && (
				!empty($this->CMSLayer->getCMSSequentialLogicalActivityLayer()->getSLASettings()) || $this->existsSuperGlobalHashTagInHtml($html)
			);
			$parse_ptl = $this->parse_ptl && $this->existsPTLInHtml($html);
			$is_sla_executed = $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->isSLAExecuted();
			$exists_sla_to_be_always_executed = $this->exists_sla_to_be_always_executed == null && !$is_sla_executed && !$parse_hash_tags && !$parse_ptl ? $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->existSequentialLogicalActivitiesToBeAlwaysExecuted() : false;
			
			//check if sla settings was already executed and if not, execute it, then compare the getMaximumUsageMemory and only if OK parse html.
			if (!$is_sla_executed && ($parse_hash_tags || $parse_ptl || $exists_sla_to_be_always_executed)) {
				$execute_sla = $this->CMSLayer->getCMSPagePropertyLayer()->getExecuteSLA();
				
				if ($execute_sla || $execute_sla === null) {
					$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->executeSequentialLogicalActivities(); //This method only executes the sla_settings once, if they were not executed yet.
					$maximum_usage_memory = $this->CMSLayer->getCMSPagePropertyLayer()->getMaximumUsageMemory();
					
					if ($maximum_usage_memory > 0 && ($mem = $this->getEntitiesUsageMemory()) > $maximum_usage_memory) {
						$this->debug_log("Page full html cannot be parsed because the defined memory size in the page advanced settings was overloaded. If you really wants this page full html to be parsed, you need to increase the maximum_usage_memory for this page. The current Entities-Usage-Memory is: " . $mem . " bytes.");
						
						$this->CMSLayer->getCMSPagePropertyLayer()->setParseFullHtml(false); //set parse_html to false for the next time we call this method in the same request, we don't need to compare the usage memory anymore and the server will return a faster response.
						return;
					}
				}
			}
			
			//if parse hash tags is true and hash tags really exists in html and exists slas.
			//paseing html executing the widget-resources and replacing the html accordingly. Basically replaces the #...var name...# tags.
			if ($parse_hash_tags)
				$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->prepareHTMLHashTagsWithSequentialLogicalActivities($html);
			
			//if parse ptl is true and ptl code really exists in html. Even if no sla, still check ptl bc it could have static data.
			//paseing html executing the widget-resources and replacing the html accordingly. Basically replaces the ptl code.
			if ($parse_ptl)
				$this->CMSLayer->getCMSSequentialLogicalActivityLayer()->prepareHTMLPTLWithSequentialLogicalActivities($html);
			
			//if filter_by_permission is true, parse html with user permission and remove or hide elements accordingly.
			//Note that the filter_by_permission could still be null bc the html didn't contained before any data-widgets-permissions. However since the html might be changed by the ptl or hash_tags, then it may contain now data-widgets-permissions.
			if (($this->filter_by_permission || $this->filter_by_permission === null) && $this->existsHtmlWidgetsPermissionsInHtml($html)) {
				$this->filter_by_permission = true;
				$this->filterHtmlWidgetsPermissionsInHtml($html); //parse html with user permission and remove or hide elements accordingly.
			}
		}
		//error_log("$html\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		//error_log("add_widget_resource_lib:".$this->add_widget_resource_lib . "\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
		
		if ($this->add_widget_resource_lib) {
			//error_log("widget_resource_lib_js_included:".$this->widget_resource_lib_js_included."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			//checks if html already contains the widget lib file. Maybe was hard-coded by the user. 
			//Do not put this code inside of the 'if ($contains_body_end_tag)', bc this must runs everytime we call this function.
			if (!$this->widget_resource_lib_js_included && $this->existsWidgetResourceLibJSUrlInHtml($html))
				$this->widget_resource_lib_js_included = true;
			
			if (!$this->widget_resource_lib_css_included && $this->existsWidgetResourceLibCSSUrlInHtml($html))
				$this->widget_resource_lib_css_included = true;
			
			//check if </body> exists in html
			$contains_head_end_tag = stripos($html, "</head>") !== false || preg_match("/<\/head([^>]*)>/i", $html);
			$contains_body_end_tag = stripos($html, "</body>") !== false || preg_match("/<\/body([^>]*)>/i", $html);
			//error_log("contains_body_end_tag:$contains_body_end_tag\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
			
			//prepare js/css code to be added to html, if not previously added, and only if body/head end-tag exists. Note that if we have an entity which is a json response, we do NOT want to add any js/css code.
			if ($contains_head_end_tag) {
				$code = "";
				
				if (!$this->widget_resource_lib_css_included) {
					$code .= $this->getJavascriptCodeWithWidgetResourceLibCSS();
					$this->widget_resource_lib_css_included = true;
				}
				
				//add code to the body end-tag
				$html = preg_replace("/<\/(head)([^>]*)>/i", $code . "</\$1\$2>", $html);
			}
			
			if ($contains_body_end_tag) {
				//prepare code
				$code = $this->getJavascriptCodeWithWidgetsVars();
				$code .= $this->getJavascriptCodeWithSLAResults(); //This already checks the memory usage of the sla results
				
				//error_log("widget_resource_lib_js_included:".$this->widget_resource_lib_js_included."\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				
				if (!$this->widget_resource_lib_js_included) {
					$code .= $this->getJavascriptCodeWithWidgetResourceLibJS();
					$this->widget_resource_lib_js_included = true;
					//error_log("code inside:$code\n", 3, $GLOBALS["log_file_path"] ? $GLOBALS["log_file_path"] : "/var/www/html/livingroop/default/tmp/phpframework.log");
				}
				
				if (!$this->widget_resource_lib_css_included) {
					$code .= $this->getJavascriptCodeWithWidgetResourceLibCSS();
					$this->widget_resource_lib_css_included = true;
				}
				
				//start the MyWidgetResourceLib by calling the initWidgets method
				$code .= $this->getJavascriptCodeWithWidgetResourceLibJSInit();
				
				//add code to the body end-tag
				$html = preg_replace("/<\/(body)([^>]*)>/i", $code . "</\$1\$2>", $html);
			}
		}
		
		if ($this->add_my_js_lib) {
			//checks if html already contains the my js lib file. Maybe was hard-coded by the user. 
			//Do not put this code inside of the 'if ($contains_body_end_tag)', bc this must runs everytime we call this function.
			if (!$this->my_js_lib_included && $this->existsMyJSLibUrlInHtml($html))
				$this->my_js_lib_included = true;
			
			if (!$this->my_js_lib_included) {
				//check if </body> exists in html
				$contains_body_end_tag = stripos($html, "</body>") !== false || preg_match("/<\/body([^>]*)>/i", $html);
				
				//prepare js code to be added to html, if not previously added, and only if body end-tag exists. Note that if we have an entity which is a json response, we do NOT want to add any js code.
				if ($contains_body_end_tag) {
					$this->my_js_lib_included = true;
					
					//prepare code
					$code = $this->getJavascriptCodeWithMyJSLib();
					
					//add code to the body end-tag
					$html = preg_replace("/<\/(body)([^>]*)>/i", $code . "</\$1\$2>", $html);
				}
			}
		}
	}
	
	/* PRIVATE FUNCTIONS - ENTITIES */
	
	//loop for all loaded blocks and check if there is any with a string: "data-widget-" 
	private function existsHtmlWidgetsInEntitiesHtml() {
		$props = $this->getEntitiesHtmlProps();
		
		return isset($props["has_widget"]) ? $props["has_widget"] : null;
	}
	
	//loop for all loaded blocks and check if there is any with a string: "data-widget-permissions" 
	private function existsHtmlWidgetsPermissionsInEntitiesHtml() {
		$props = $this->getEntitiesHtmlProps();
		
		return isset($props["has_widget_permission"]) ? $props["has_widget_permission"] : null;
	}
	
	//loop for all loaded blocks and check if html already contains the html attributes used in the MyJSLib.js file. Maybe was hard-coded by the user.
	private function existsMyJSLibHtmlAttributesInEntitiesHtml() {
		$props = $this->getEntitiesHtmlProps();
		
		return isset($props["has_my_js_lib_html_attributes"]) ? $props["has_my_js_lib_html_attributes"] : null;
	}
	
	//loop for all loaded blocks and check if html already contains the javascript MyJSLib.js file. Maybe was hard-coded by the user.
	private function existsMyJSLibInEntitiesHtml() {
		$props = $this->getEntitiesHtmlProps();
		
		return isset($props["has_my_js_lib_url"]) ? $props["has_my_js_lib_url"] : null;
	}
	
	//loop for all loaded blocks and check if html already contains the javascript widget lib file. Maybe was hard-coded by the user.
	private function existsWidgetResourceLibJSInEntitiesHtml() {
		$props = $this->getEntitiesHtmlProps();
		
		return isset($props["has_widget_resource_lib_js_url"]) ? $props["has_widget_resource_lib_js_url"] : null;
	}
	
	//loop for all loaded blocks and check if html already contains the css widget lib file. Maybe was hard-coded by the user.
	private function existsWidgetResourceLibCSSInEntitiesHtml() {
		$props = $this->getEntitiesHtmlProps();
		
		return isset($props["has_widget_resource_lib_css_url"]) ? $props["has_widget_resource_lib_css_url"] : null;
	}
	
	//loop for all loaded blocks and check if there is any with a string: "#...#" 
	private function existsHashTagInEntitiesHtml() {
		$props = $this->getEntitiesHtmlProps();
		
		return isset($props["has_hash_tag"]) ? $props["has_hash_tag"] : null;
	}
	
	//loop for all loaded blocks and check if there is any with a string: "#...#" 
	private function existsSuperGlobalHashTagInEntitiesHtml() {
		$props = $this->getEntitiesHtmlProps();
		
		return isset($props["has_global_hash_tag"]) ? $props["has_global_hash_tag"] : null;
	}
	
	//loop for all loaded blocks and check if there is any with a string: "<ptl:" 
	private function existsPTLInEntitiesHtml() {
		$props = $this->getEntitiesHtmlProps();
		
		return isset($props["has_ptl"]) ? $props["has_ptl"] : null;
	}
	
	private function getEntitiesHtmlProps() {
		if ($this->entities_html_props)
			return $this->entities_html_props;
		
		$EVC = $this->CMSLayer->getEVC();
		$UserCacheHandler = $EVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler");
		
		if ($UserCacheHandler) {
			$UserCacheHandler->config(false, true);
			
			$cache_key = self::CACHE_DIR_NAME . $EVC->getPresentationLayer()->getSelectedPresentationId() . "/entities_props_" . md5($this->entity_code) . "_" . md5($this->project_url_prefix); //put the project_url_prefix at the end so we can delete the cache easily based in the entity_code.
			
			if ($UserCacheHandler->isValid($cache_key)) {
				$this->entities_html_props = $UserCacheHandler->read($cache_key);
				
				if (is_array($this->entities_html_props))
					return $this->entities_html_props;
			}
		}
		
		$this->entities_html_props = array();
		
		//check if "data-widget-" exists in blocks output
		$blocks = $this->CMSLayer->getCMSBlockLayer()->getBlocks();
		$views = $this->CMSLayer->getCMSViewLayer()->getViews();
		$blocks = array_merge($blocks, $views);
		
		if ($blocks)
			foreach ($blocks as $block_id => $block_htmls) 
				foreach ($block_htmls as $block_html)
					if (is_string($block_html)) {
						if ($this->existsHtmlWidgetsInHtml($block_html))
							$this->entities_html_props["has_widget"] = true;
						
						if ($this->existsHtmlWidgetsPermissionsInHtml($block_html))
							$this->entities_html_props["has_widget_permission"] = true;
						
						if ($this->existsMyJSLibHtmlAttributesInHtml($block_html))
							$this->entities_html_props["has_my_js_lib_html_attributes"] = true;
						
						if ($this->existsMyJSLibUrlInHtml($block_html))
							$this->entities_html_props["has_my_js_lib_url"] = true;
						
						if ($this->existsWidgetResourceLibJSUrlInHtml($block_html))
							$this->entities_html_props["has_widget_resource_lib_js_url"] = true;
						
						if ($this->existsWidgetResourceLibCSSUrlInHtml($block_html))
							$this->entities_html_props["has_widget_resource_lib_css_url"] = true;
						
						if ($this->existsHashTagInHtml($block_html))
							$this->entities_html_props["has_hash_tag"] = true;
						
						if ($this->existsSuperGlobalHashTagInHtml($block_html))
							$this->entities_html_props["has_global_hash_tag"] = true;
						
						if ($this->existsPTLInHtml($block_html))
							$this->entities_html_props["has_ptl"] = true;
						
						if (
							!empty($this->entities_html_props["has_widget"]) && 
							!empty($this->entities_html_props["has_hash_tag"]) && 
							!empty($this->entities_html_props["has_ptl"]) && 
							!empty($this->entities_html_props["has_widget_permission"]) && 
							!empty($this->entities_html_props["has_my_js_lib_html_attributes"]) && 
							(
								!$this->my_js_lib_url || 
								!empty($this->entities_html_props["has_my_js_lib_url"])
							) && 
							(
								!$this->widget_resource_lib_js_url || 
								!empty($this->entities_html_props["has_widget_resource_lib_js_url"])
							) && 
							(
								!$this->widget_resource_lib_css_url || 
								!empty($this->entities_html_props["has_widget_resource_lib_css_url"])
							)
						)
							break;
					}
		
		if (
			empty($this->entities_html_props["has_widget"]) || 
			empty($this->entities_html_props["has_hash_tag"]) || 
			empty($this->entities_html_props["has_ptl"]) || 
			empty($this->entities_html_props["has_widget_permission"]) || 
			empty($this->entities_html_props["has_my_js_lib_html_attributes"]) || 
			(
				$this->my_js_lib_url && 
				empty($this->entities_html_props["has_my_js_lib_url"])
			) || 
			(
				$this->widget_resource_lib_js_url && 
				empty($this->entities_html_props["has_widget_resource_lib_js_url"])
			) || 
			(
				$this->widget_resource_lib_css_url && 
				empty($this->entities_html_props["has_widget_resource_lib_css_url"])
			)
		) {
			//check if "data-widget-" exists in regions output
			$regions = $this->CMSLayer->getCMSTemplateLayer()->getRegions();
			
			if ($regions)
				foreach ($regions as $region_id => $region_blocks)
					if ($region_blocks)
						foreach ($region_blocks as $block)
							if (isset($block[0]) && isset($block[1]) && $block[0] == 1 && is_string($block[1])) {
								if ($this->existsHtmlWidgetsInHtml($block[1]))
									$this->entities_html_props["has_widget"] = true;
								
								if ($this->existsHtmlWidgetsPermissionsInHtml($block[1]))
									$this->entities_html_props["has_widget_permission"] = true;
								
								if ($this->existsMyJSLibHtmlAttributesInHtml($block[1]))
									$this->entities_html_props["has_my_js_lib_html_attributes"] = true;
								
								if ($this->existsMyJSLibUrlInHtml($block[1]))
									$this->entities_html_props["has_my_js_lib_url"] = true;
								
								if ($this->existsWidgetResourceLibJSUrlInHtml($block[1]))
									$this->entities_html_props["has_widget_resource_lib_js_url"] = true;
								
								if ($this->existsWidgetResourceLibCSSUrlInHtml($block[1]))
									$this->entities_html_props["has_widget_resource_lib_css_url"] = true;
								
								if ($this->existsHashTagInHtml($block[1]))
									$this->entities_html_props["has_hash_tag"] = true;
								
								if ($this->existsSuperGlobalHashTagInHtml($block[1]))
									$this->entities_html_props["has_global_hash_tag"] = true;
								
								if ($this->existsPTLInHtml($block[1]))
									$this->entities_html_props["has_ptl"] = true;
								
								if (
									!empty($this->entities_html_props["has_widget"]) && 
									!empty($this->entities_html_props["has_hash_tag"]) && 
									!empty($this->entities_html_props["has_ptl"]) && 
									!empty($this->entities_html_props["has_widget_permission"]) && 
									!empty($this->entities_html_props["has_my_js_lib_html_attributes"]) && 
									(
										!$this->my_js_lib_url || 
										!empty($this->entities_html_props["has_my_js_lib_url"])
									) && 
									(
										!$this->widget_resource_lib_js_url || 
										!empty($this->entities_html_props["has_widget_resource_lib_js_url"])
									) && 
									(
										!$this->widget_resource_lib_css_url || 
										!empty($this->entities_html_props["has_widget_resource_lib_css_url"])
									)
								)
									break;
							}
		}
		
		if ($UserCacheHandler)
			$UserCacheHandler->write($cache_key, $this->entities_html_props);
		
		return $this->entities_html_props;
	}
	
	/* PRIVATE FUNCTIONS - TEMPLATE/FILE */
	
	//read the template file contents and check if there is any "data-widget-"
	private function existsHtmlWidgetsInFile($file_path) {
		$props = $this->getTemplateFileProps($file_path);
		
		return isset($props["has_widget"]) ? $props["has_widget"] : null;
	}
	
	//read the template file contents and check if there is any "data-widget-permissions"
	private function existsHtmlWidgetsPermissionsInFile($file_path) {
		$props = $this->getTemplateFileProps($file_path);
		
		return isset($props["has_widget_permission"]) ? $props["has_widget_permission"] : null;
	}
	
	//read the template file contents and check if there is any html attributes used in the MyJSLib.js file. Maybe was hard-coded by the user.
	private function existsMyJSLibHtmlAttributesInFile($file_path) {
		$props = $this->getTemplateFileProps($file_path);
		
		return isset($props["has_my_js_lib_html_attributes"]) ? $props["has_my_js_lib_html_attributes"] : null;
	}
	
	//read the template file contents and check if there is any javascript MyJSLib.js file. Maybe was hard-coded by the user.
	private function existsMyJSLibInFile($file_path) {
		$props = $this->getTemplateFileProps($file_path);
		
		return isset($props["has_my_js_lib_url"]) ? $props["has_my_js_lib_url"] : null;
	}
	
	//read the template file contents and check if there is any javascript widget lib file. Maybe was hard-coded by the user.
	private function existsWidgetResourceLibJSInFile($file_path) {
		$props = $this->getTemplateFileProps($file_path);
		
		return isset($props["has_widget_resource_lib_js_url"]) ? $props["has_widget_resource_lib_js_url"] : null;
	}
	
	//read the template file contents and check if there is any css widget lib file. Maybe was hard-coded by the user.
	private function existsWidgetResourceLibCSSInFile($file_path) {
		$props = $this->getTemplateFileProps($file_path);
		
		return isset($props["has_widget_resource_lib_css_url"]) ? $props["has_widget_resource_lib_css_url"] : null;
	}
	
	//read the template file contents and check if there is any "#...#"
	private function containsHashTagInFile($file_path) {
		$props = $this->getTemplateFileProps($file_path);
		
		return isset($props["has_hash_tag"]) ? $props["has_hash_tag"] : null;;
	}
	
	//read the template file contents and check if there is any "<ptl:"
	private function containsPTLInFile($file_path) {
		$props = $this->getTemplateFileProps($file_path);
		
		return isset($props["has_ptl"]) ? $props["has_ptl"] : null;;
	}
	
	//read the template file contents and check if there is any "addSequentialLogicalActivities"
	private function containsSLAInFile($file_path) {
		$props = $this->getTemplateFileProps($file_path);
		
		return isset($props["has_sla"]) ? $props["has_sla"] : null;;
	}
	
	/*//read the template file contents and check if there is any "->renderRegion(..."
	private function isRegionRenderedInFile($file_path, $region_id) {
		$props = $this->getTemplateFileProps($file_path);
		
		return !empty($props["rendered_regions"]) && in_array($region_id, $props["rendered_regions"]);
	}*/
	
	private function getTemplateFileProps($file_path) {
		if ($this->template_file_props)
			return $this->template_file_props;
		
		$EVC = $this->CMSLayer->getEVC();
		$UserCacheHandler = $EVC->getPresentationLayer()->getPHPFrameWork()->getObject("UserCacheHandler");
		
		if ($UserCacheHandler) {
			$UserCacheHandler->config(false, true);
			$cache_key = self::CACHE_DIR_NAME . $EVC->getPresentationLayer()->getSelectedPresentationId() . "/template_props_" . md5($file_path);
			
			if ($UserCacheHandler->isValid($cache_key)) {
				$this->template_file_props = $UserCacheHandler->read($cache_key);
				
				if (is_array($this->template_file_props))
					return $this->template_file_props;
			}
		}
		
		$this->template_file_props = array();
		
		$html = $this->template_file_contents ? $this->template_file_contents : (file_exists($file_path) ? file_get_contents($file_path) : "");
		
		if ($html) {
			$this->template_file_props["has_widget"] = $this->existsHtmlWidgetsInHtml($html);
			$this->template_file_props["has_widget_permission"] = $this->existsHtmlWidgetsPermissionsInHtml($html);
			$this->template_file_props["has_my_js_lib_html_attribtues"] = $this->existsMyJSLibHtmlAttributesInHtml($html);
			$this->template_file_props["has_my_js_lib_url"] = $this->existsMyJSLibUrlInHtml($html);
			$this->template_file_props["has_widget_resource_lib_js_url"] = $this->existsWidgetResourceLibJSUrlInHtml($html);
			$this->template_file_props["has_widget_resource_lib_css_url"] = $this->existsWidgetResourceLibCSSUrlInHtml($html);
			$this->template_file_props["has_hash_tag"] = $this->existsHashTagInHtml($html);
			$this->template_file_props["has_global_hash_tag"] = $this->existsSuperGlobalHashTagInHtml($html);
			$this->template_file_props["has_ptl"] = $this->existsPTLInHtml($html);
			$this->template_file_props["has_sla"] = $this->existsSLAInHtml($html);
			//$this->template_file_props["rendered_regions"] = $this->getRenderedRegionsInHtml($html);
		}
		
		if ($UserCacheHandler)
			$UserCacheHandler->write($cache_key, $this->template_file_props);
		
		return $this->template_file_props;
	}
	
	/* PRIVATE FUNCTIONS - HTML */
	
	private function getRenderedRegionsInHtml($html) {
		$rendered_regions = array();
		
		if (preg_match_all("/->\s*renderRegion\s*\(\s*(\"|')(\w+)(\"|')\s*\)/i", $html, $matches, PREG_PATTERN_ORDER) && $matches)
			foreach ($matches as $match)
				$rendered_regions[] = $match[2];
		
		return $rendered_regions;
	}
	
	private function existsHashTagInHtml($html) {
		return HashTagParameter::existsHTMLHashTagParameters($html);
	}
	
	private function existsSuperGlobalHashTagInHtml($html) {
		return HashTagParameter::existsHTMLSuperGlobalHashTagParameters($html);
	}
	
	private function existsPTLInHtml($html) {
		return stripos($html, "<ptl:") !== false;
	}
	
	private function existsSLAInHtml($html) {
		return stripos($html, 'addSequentialLogicalActivities') !== false && preg_match("/->\s*addSequentialLogicalActivities\s*\(/i", $html);
	}
	
	private function existsHtmlWidgetsInHtml($html) {
		return stripos($html, "data-widget-") !== false;
	}
	
	private function existsHtmlWidgetsPermissionsInHtml($html) {
		return stripos($html, "data-widget-permissions") !== false;
	}
	
	private function existsMyJSLibHtmlAttributesInHtml($html) {
		$attributes_to_search = array("data-confirmation", "confirmation", "data-confirmation-message", "confirmationmessage", "data-validation-label", "validationlabel", "data-validation-message", "validationmessage", "data-allow-null", "allownull", "data-allow-javascript", "allowjavascript", "data-validation-type", "validationtype", "data-validation-regex", "validationregex", "minlength", "maxlength", "min", "max", "data-mandatory-checkbox", "mandatorycheckbox", "data-min-words", "minwords", "data-max-words", "maxwords", "data-ajax", "ajax", "data-security-code", "securitycode");
		
		return preg_match("/\s(" . implode("|", $attributes_to_search) . ")\s*=/i", $html);
	}
	
	private function existsMyJSLibUrlInHtml($html) {
		return $this->my_js_lib_url && stripos($html, $this->my_js_lib_url) !== false && preg_match("/<script\s([^>]*)src\s*=\s*(\"|')" . preg_quote($this->my_js_lib_url, "/") . "(\"|')/i", $html);
	}
	
	private function existsWidgetResourceLibJSUrlInHtml($html) {
		return $this->widget_resource_lib_js_url && stripos($html, $this->widget_resource_lib_js_url) !== false && preg_match("/<script\s([^>]*)src\s*=\s*(\"|')" . preg_quote($this->widget_resource_lib_js_url, "/") . "(\"|')/i", $html);
	}
	
	private function existsWidgetResourceLibCSSUrlInHtml($html) {
		return $this->widget_resource_lib_css_url && stripos($html, $this->widget_resource_lib_css_url) !== false && preg_match("/<link\s([^>]*)href\s*=\s*(\"|')" . preg_quote($this->widget_resource_lib_css_url, "/") . "(\"|')/i", $html);
	}
	
	/* PRIVATE FUNCTIONS - JAVASCRIPT CODE */
	
	private function getJavascriptCodeWithMyJSLib() {
		return $this->my_js_lib_url ? '<script type="text/javascript">
			if (typeof MyJSLib == "undefined")
				document.write(\'<script src="' . $this->my_js_lib_url . '" type="text/javascript"></scr\' + \'ipt>\');
		</script>' : "";
	}
	
	private function getJavascriptCodeWithWidgetResourceLibJSInit() {
		return '<script>typeof MyWidgetResourceLib == "function" && MyWidgetResourceLib.fn.initWidgets();</script>';
	}
	
	private function getJavascriptCodeWithWidgetResourceLibJS() {
		return $this->widget_resource_lib_js_url ? '<script type="text/javascript">
			if (typeof MyWidgetResourceLib == "undefined")
				document.write(\'<script src="' . $this->widget_resource_lib_js_url . '" type="text/javascript"></scr\' + \'ipt>\');
		</script>' : "";
	}
	
	private function getJavascriptCodeWithWidgetResourceLibCSS() {
		return $this->widget_resource_lib_css_url ? '<link href="' . $this->widget_resource_lib_css_url . '" rel="stylesheet" type="text/css" charset="utf-8" />' : "";
	}
	
	private function getJavascriptCodeWithWidgetsVars() {
		$javascript_code = '';
		
		//prepare public_user_type_id and logged_user_type_ids javascript code
		$public_user_type_id = $this->getPublicUserTypeId();
		$logged_user_type_ids = $this->getLoggedUserTypeIds();
		
		if (is_numeric($public_user_type_id))
			$javascript_code .= 'if (typeof window.public_user_type_id == "undefined") window.public_user_type_id = ' . $public_user_type_id . ';';
		
		if ($logged_user_type_ids)
			$javascript_code .= 'if (typeof window.logged_user_type_ids == "undefined") window.logged_user_type_ids = ' . json_encode($logged_user_type_ids) . ';';
		
		//prepare get_resource_url javascript code
		$sla_exists = $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->getSLASettings();
		
		if ($sla_exists)
			$javascript_code .= 'if (typeof window.get_resource_url == "undefined") window.get_resource_url = "' . $this->resources_url . '";';
		
		if ($this->chart_js_url)
			$javascript_code .= 'if (typeof window.chart_js_url == "undefined") window.chart_js_url = "' . $this->chart_js_url . '";';
		
		if ($this->calendar_js_url)
			$javascript_code .= 'if (typeof window.calendar_js_url == "undefined") window.calendar_js_url = "' . $this->calendar_js_url . '";';
		
		//prepare main javascript code
		if ($javascript_code)
			$javascript_code = '<script>' . $javascript_code . '</script>';

		return $javascript_code;
	}
	
	private function getJavascriptCodeWithSLAResults($sla_results = null) {
		$sla_results_code = '';
		
		//checks if the sla results are bigger than x MB (like 10MB). This avoids that when we have big data in resources, we create huge javascript codes that may break the client request
		$maximum_usage_memory = $this->CMSLayer->getCMSPagePropertyLayer()->getMaximumUsageMemory();
		$usage_memory = $this->getSLAResultsUsageMemory();
		$sla_results = $sla_results ? $sla_results : $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->getSLAResults();
		//echo "sla_results:";print_r($sla_results);die();
		
		if ($usage_memory <= $maximum_usage_memory) {
			$sla_results_code = json_encode($sla_results);
		}
		else if ($sla_results) { //check for each individual resource, which means only the big resources will be ignored.
			foreach ($sla_results as $k => $v) {
				$usage_memory = $this->getSLAResultUsageMemory($v);
				
				if ($usage_memory <= $maximum_usage_memory)
					$sla_results_code .= '"' . $k . '": ' . json_encode($v) . ',';
			}
			
			if ($sla_results_code)
				$sla_results_code = '{' . $sla_results_code . '}';
		}
		
		if ($sla_results_code)
			$sla_results_code = '<script>if (typeof window.sla_results == "undefined") window.sla_results = ' . $sla_results_code . ';</script>';
		
		return $sla_results_code;
	}
	
	/* PRIVATE FUNCTIONS - USER */
	
	//get public_user_type_id
	private function getPublicUserTypeId() {
		if (is_numeric($this->public_user_type_id))
			return $this->public_user_type_id;
		
		$this->public_user_type_id = null;
		$EVC = $this->CMSLayer->getEVC();
		$common_project_name = $EVC->getCommonProjectName();
		
		@include_once $EVC->getModulePath("user/UserUtil", $common_project_name);
		
		if (class_exists("UserUtil"))
			$this->public_user_type_id = UserUtil::PUBLIC_USER_TYPE_ID;
		
		return $this->public_user_type_id;
	}
	
	//get logged_user_type_ids
	private function getLoggedUserTypeIds() {
		if ($this->logged_user_type_ids)
			return $this->logged_user_type_ids;
		
		$this->logged_user_type_ids = null;
		$EVC = $this->CMSLayer->getEVC();
		
		if (empty($GLOBALS["UserSessionActivitiesHandler"])) {
			@include_once $EVC->getUtilPath("user_session_activities_handler", $EVC->getCommonProjectName());
			@initUserSessionActivitiesHandler($EVC);
		}
		
		if (!empty($GLOBALS["UserSessionActivitiesHandler"])) {
			$user_data = $GLOBALS["UserSessionActivitiesHandler"]->getUserData();
			
			if ($user_data && isset($user_data["user_type_ids"]))
				$this->logged_user_type_ids = $user_data["user_type_ids"];
		}
		
		return $this->logged_user_type_ids;
	}
	
	/* PRIVATE FUNCTIONS - USAGE MEMORY */
	
	//return number of bytes usage by the called entities
	private function getEntitiesUsageMemory() {
		$mem = memory_get_usage();
		
		$arr_1 = $this->CMSLayer->getCMSBlockLayer()->getBlocks();
		$arr_1["_"] = 1; //Be sure that the $arr is cloned
		
		$arr_2 = $this->CMSLayer->getCMSViewLayer()->getViews();
		$arr_2["_"] = 1; //Be sure that the $arr is cloned
		
		$arr_3 = $this->CMSLayer->getCMSTemplateLayer()->getRegions();
		$arr_3["_"] = 1; //Be sure that the $arr is cloned
		
		$mem = memory_get_usage() - $mem;
		
		unset($arr_1);
		unset($arr_2);
		unset($arr_3);
		
		return $mem + $this->getSLAResultsUsageMemory();
	}
	
	//return number of bytes usage by a rendered region html
	private function getRenderedRegionHtmlUsageMemory($html) {
		return strlen($html) + $this->getSLAResultsUsageMemory();
	}
	
	//return number of bytes usage by the called sla results
	private function getSLAResultsUsageMemory() {
		$mem = memory_get_usage();
		
		$arr_1 = $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->getSLAResults();
		$arr_1["_"] = 1; //Be sure that the $arr is cloned
		
		$mem = memory_get_usage() - $mem;
		
		unset($arr_1);
		
		return $mem;
	}
	
	//return number of bytes usage by a sla result item
	private function getSLAResultUsageMemory($sla_result) {
		if (is_object($sla_result))
			return $this->getObjectUsageMemory($sla_result);
		
		$mem = memory_get_usage();
		
		$aux = $sla_result;
		
		if (is_array($aux))
			$aux["_"] = 1; //Be sure that the $aux is cloned
		else if (is_string($aux))
			$aux .= 1;
		
		$mem = memory_get_usage() - $mem;
		
		unset($aux);
		
		return $mem;
	}
	
	//return number of bytes usage by an object
	private function getObjectUsageMemory($obj) {
		if (is_object($obj)) {
			$mem = memory_get_usage();
			$obj_tmp = clone $obj;
			$mem = memory_get_usage() - $mem;
			unset($obj_tmp);
			
			return $mem;
		}
	}
	
	/* PRIVATE FUNCTIONS - WIDGET PERMISSIONS */
	
	//parse html with user permission and remove or hide elements accordingly.
	private function filterHtmlWidgetsPermissionsInHtml(&$html) {
		include_once get_lib("org.phpframework.util.web.html.HtmlDomHandler");
		
		$HtmlDomHandler = new HtmlDomHandler($html);
		$DOMDocument = $HtmlDomHandler->getDOMDocument();
		
		$DOMXPath = new DOMXPath($DOMDocument);
		$nodes = $DOMXPath->query("//*[@data-widget-permissions]");
		
		if ($nodes)
			foreach($nodes as $node) {
				$perms = $this->getWidgetPermissions($node);
				$show = isset($perms["show"]) ? $perms["show"] : null;
				$hide = isset($perms["hide"]) ? $perms["hide"] : null;
				$remove = isset($perms["remove"]) ? $perms["remove"] : null;
				//print_r($perms);die();
				
				if ($remove) {
					$parent_node = $node->parentNode;
					$parent_node->removeChild($node);
				}
				else if ($hide)
					$HtmlDomHandler->setElementStyle($node, "display", "none");
				else if ($show && strtolower($HtmlDomHandler->getElementStyle($node, "display")) == "none")
					$HtmlDomHandler->setElementStyle($node, "display", "");
			}
		
		$html = $HtmlDomHandler->getHtmlExact();
	}
	
	//according with the logged_user_type_ids, check the data-widget-permissions attribute and set the new actions for the node.
	/*
	 * data-widget-permissions possible values:
	 *	user_type_id
	 *	[user_type_id_x, user_type_id_y]
	 *	user_type_ids:
	 *		user_type_id
	 *		[user_type_id_x, user_type_id_y]
	 *	access/view/show/hide/remove: user_type_id
	 *	access/view/show/hide/remove: [user_type_id_x, user_type_id_y]
	 *	access/view/show/hide/remove:
	 *		resources: 
	 *			resource_name
	 *			[resource_name, resource_name]
	 *			{name: xxx, ...}
	 *			[resource_name, {name: xxx, ...}]
	 *		values:
	 *			value
	 *			[value_x, value_y]
	 *		user_type_ids:
	 *			user_type_id
	 *			[user_type_id_x, user_type_id_y]
	 */
	private function getWidgetPermissions(DOMElement $node) {
		$show = true;
		$hide = false;
		$remove = false;
		
		if ($node) {
			$permissions = $node->getAttribute("data-widget-permissions");
			
			if ($permissions) {
				$permissions = substr($permissions, 0, 1) == "{" ? json_decode($permissions) : $permissions; //convert to object. Do not convert to array
				//print_r($permissions);die();
				
				if ($permissions) {
					if (is_string($permissions) || is_numeric($permissions) || is_array($permissions)) {
						$p = new stdClass();
						$p->user_type_ids = $permissions;
						
						$obj = new stdClass();
						$obj->view = $p;
					}
					else
						$obj = $permissions;
					
					if (is_object($obj)) {
						if (property_exists($obj, "access") || property_exists($obj, "view") || property_exists($obj, "show"))
							$show = false;
						
						$public_user_type_id = $this->getPublicUserTypeId();
						$logged_user_type_ids = $this->getLoggedUserTypeIds();
						$is_logged = $logged_user_type_ids && is_array($logged_user_type_ids) && count($logged_user_type_ids) > 0;
						$sla_results = $this->CMSLayer->getCMSSequentialLogicalActivityLayer()->getSLAResults();
						
						foreach ($obj as $k => $v) {
							if (is_string($v) || is_numeric($v) || is_array($v)) {
								$p = new stdClass();
								$p->user_type_ids = $v;
							}
							else
								$p = $v;
							
							if (is_object($p)) {
								$resources = isset($p->resources) ? $p->resources : null;
								$values = isset($p->values) ? $p->values : null;
								$user_type_ids = isset($p->user_type_ids) ? $p->user_type_ids : null;
								
								$resources = is_array($resources) ? $resources : array($resources);
								$values = is_array($values) ? $values : array($values);
								$user_type_ids = is_array($user_type_ids) ? $user_type_ids : array($user_type_ids);
								
								//check if resources exist and are valid
								$is_valid_resource = true; //true in case there are no resources defined
								$is_valid_value = count($values) ? false : true; //true in case there are no values defined, otherwise is false
								$is_valid_permission = true; //true in case there are no permissions defined
								$there_is_a_valid_resource = false;
								$validate_resource_later = false;
								
								for ($i = 0; $i < count($resources); $i++) {
									$resource = $resources[$i];
									
									//if is string
									if (!is_object($resource)) {
										$r = new stdClass();
										$r->name = $resource;
									}
									else
										$r = $resource;
									
									//Note that the $r->name may be a string like: "[x][v]" or "x[u]"
									if (isset($r->name) && strlen($r->name)) {
										//if resource name exists but was not yet executed
										if (!self::existsSLAResult($sla_results, $r->name))
											$validate_resource_later = true;
										//if resource name exists and was already executed and is true, sets the is_valid_resource to true.
										else if (self::getSLAResult($sla_results, $r->name)) {
											$is_valid_resource = true; //in case some previous resource set this var to false
											$there_is_a_valid_resource = true;
											break;
										}
										//if there isn't any resource valid yet, sets the is_valid_resource to false.
										else if (!$there_is_a_valid_resource)
											$is_valid_resource = false;
									}
								}
								
								//if there is a resource that was not yet executed and there are no other valid resources, then stop this process, so we can filter it later... Additionally reset the show var to true, so we can parse it later too...
								if ($validate_resource_later && !$there_is_a_valid_resource) {
									$is_valid_resource = false;
									
									if ($k == "access" || $k == "view" || $k == "show")
										$show = true;
								}
								
								//check values, but only if resource is valid, otherwise stops this process for the correspondent permission and set the correspondent default value.
								if ($is_valid_resource)
									for ($i = 0; $i < count($values); $i++) {
										$value = $values[$i];
										
										if (is_numeric($value) && ($value === 0 || $value === "0"))
											$value = false;
										
										if ($value) {
											$is_valid_value = true;
											break;
										}
									}
									
								//check user types, but only if resource and value are valid, otherwise stops this process for the correspondent permission and set the correspondent default value.
								if ($is_valid_resource && $is_valid_value)
									for ($i = 0; $i < count($user_type_ids); $i++) {
										$user_type_id = $user_type_ids[$i];
										
										if (is_numeric($user_type_id)) {
											$is_public_user_permission = !$is_logged && ($user_type_id === 0 || $user_type_id === "0" || $user_type_id == $public_user_type_id);
											$is_logged_user_permission = $is_logged && in_array($user_type_id, $logged_user_type_ids);
											
											//echo "user_type_id:$user_type_id<br/>";
											//echo "is_public_user_permission:$is_public_user_permission<br/>";
											//echo "is_logged_user_permission:$is_logged_user_permission<br/>";
											
											if ($is_public_user_permission || $is_logged_user_permission) {
												$is_valid_permission = true; //in case some previous resource set this var to false
												break;
											}
											else
												$is_valid_permission = false;
										}
									}
								
								if ($is_valid_resource && $is_valid_value && $is_valid_permission) {
									if ($k == "access" || $k == "view" || $k == "show")
										$show = true;
									else if ($k == "hide")
										$hide = true;
									else if ($k == "remove")
										$remove = true;
								}
							}
						}
					}
					
					if ($hide || $remove)
						$show = false;
					
					if (!$show)
						$hide = true;
				}
			}
		}
		
		return array(
			"show" => $show,
			"hide" => $hide,
			"remove" => $remove,
		);
	}
	
	private static function getSLAResult($sla_results, $resource_name) {
		if (strpos($resource_name, "[") !== false || strpos($resource_name, "]") !== false) {
			preg_match_all("/([^\[\]]+)/u", trim($resource_name), $sub_matches, PREG_PATTERN_ORDER);
			
			if (!empty($sub_matches[1])) {
				$sub_matches = $sub_matches[1];
				//print_r($sub_matches);
				
				$t = count($sub_matches);
				$keys = array();
				
				for ($i = 0; $i < $t; $i++) {
					$sub_match = $sub_matches[$i];
					
					if (strlen($sub_match)) {
						if (strpos($sub_match, "'") === false && strpos($sub_match, '"') === false) //avoid php errors because one of the keys is a RESERVED PHP CODE string.
							$sub_matches[$i] = '"' . $sub_match . '"';
						
						$keys[] = $sub_matches[$i];
					}
				}
				
				eval('return $sla_results[' . implode('][', $keys) . '];');
			}
		}
		
		return isset($sla_results[$resource_name]) ? $sla_results[$resource_name] : null;
	}
	
	private static function existsSLAResult($sla_results, $resource_name) {
		if (preg_match_all("/([^\[\]]+)/u", trim($resource_name), $sub_matches, PREG_PATTERN_ORDER)) {
			if (!empty($sub_matches[1])) {
				$sub_matches = $sub_matches[1];
				//print_r($sub_matches);
				
				$t = count($sub_matches);
				$keys = array();
				
				for ($i = 0; $i < $t; $i++) {
					$sub_match = trim($sub_matches[$i]);
					
					if (strlen($sub_match)) {
						if (strpos($sub_match, "'") === false && strpos($sub_match, '"') === false) //avoid php errors because one of the keys is a RESERVED PHP CODE string.
							$sub_matches[$i] = '"' . $sub_match . '"';
						
						$keys[] = $sub_matches[$i];
					}
				}
				
				$last_key = array_pop($keys);
				
				if (count($keys))
					eval('return is_array($sla_results[' . implode('][', $keys) . ']) && array_key_exists($last_key, $sla_results[' . implode('][', $keys) . ']);');
				else
					eval('return is_array($sla_results) && array_key_exists($last_key, $sla_results);');
			}
		}
		
		return is_array($sla_results) && array_key_exists($resource_name, $sla_results);
	}
	
	private function debug_log($msg, $log_type = "error") {
		$url = (isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "") . (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null);
		debug_log("[Page: '{$this->entity_code}' from url: $url] $msg", $log_type);
	}
}
?>
