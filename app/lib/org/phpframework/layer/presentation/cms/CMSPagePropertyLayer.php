<?php
include_once get_lib("org.phpframework.layer.presentation.cms.CMSLayer");

class CMSPagePropertyLayer {
	private $CMSLayer;
	
	//vars set by user
	private $parse_full_html; //false|true. Defaut: false.More info in setParseFullHtml method
	private $parse_regions_html; //false|true. Defaut: false.More info in setParseRegionsHtml method
	
	private $execute_sla; //false|true|null. Defaut: null. More info in setExecuteSLA method
	private $parse_hash_tags; //false|true|null. Defaut: null. More info in setParseHashTags method
	private $parse_ptl; //false|true|null. Defaut: null. More info in setParsePTL method
	private $add_my_js_lib; //false|true|null. Defaut: null. More info in setAddMyJSLib method
	private $add_widget_resource_lib; //false|true|null. Defaut: null. More info in setAddWidgetResourceLib method
	private $filter_by_permission; //false|true|null. Defaut: null. More info in setFilterByPermission method
	private $include_blocks_when_calling_resources; //false|true. Defaut: false. More info in setIncludeBlocksWhenCallingResources method
	private $init_user_data; //false|true. Defaut: true. More info in setInitUserData method
	
	private $maximum_usage_memory; //in bytes. More info in setMaximumUsageMemory method
	private $maximum_buffer_chunk_size; //in bytes. More info in setMaximumBufferChunkSize method
	
	public function __construct(CMSLayer $CMSLayer) {
		$this->CMSLayer = $CMSLayer;
	
		$this->parse_full_html = false; //false|true. More info in setParseFullHtml method
		$this->parse_regions_html = false; //false|true. More info in setParseRegionsHtml method
		$this->execute_sla = null; //false|true|null. More info in setExecuteSLA method
		$this->parse_hash_tags = null; //false|true|null. More info in setParseHashTags method
		$this->parse_ptl = null; //false|true|null. More info in setParsePTL method
		$this->add_my_js_lib = null; //false|true|null. More info in setAddMyJSLibLib method
		$this->add_widget_resource_lib = null; //false|true|null. More info in setAddWidgetResourceLib method
		$this->filter_by_permission = null; //false|true|null. More info in setFilterByPermission method
		$this->include_blocks_when_calling_resources = false; //false|true. Defaut: false. More info in setIncludeBlocksWhenCallingResources method
		$this->init_user_data = null; //false|true|null. More info in setInitUserData method
		
		$this->maximum_usage_memory = 100000; //in bytes: 100MB. More info in setMaximumUsageMemory method
		$this->maximum_buffer_chunk_size = 100000; //in bytes: 100MB. More info in setMaximumBufferChunkSize method
	}
	
	/*
	 * parse_full_html:
	 * - false (default): don't parse html.
	 * - true: grab and parse html.
	 */
	public function setParseFullHtml($parse_full_html) {
		$this->parse_full_html = $parse_full_html === 0 || $parse_full_html === "0" || $parse_full_html === "" ? false : $parse_full_html;
	}
	public function getParseFullHtml() {
		return $this->parse_full_html;
	}
	
	/*
	 * parse_regions_html:
	 * - false (default): don't parse html.
	 * - true: grab and parse html.
	 */
	public function setParseRegionsHtml($parse_regions_html) {
		$this->parse_regions_html = $parse_regions_html === 0 || $parse_regions_html === "0" || $parse_regions_html === "" ? false : $parse_regions_html;
	}
	public function getParseRegionsHtml() {
		return $this->parse_regions_html;
	}
	
	/*
	 * execute_sla:
	 * - null (default): same than "auto". It means that the systems will find out automatically if page needs to execute the slas.
	 * - false: don't execute slas.
	 * - true: execute slas.
	 */
	public function setExecuteSLA($execute_sla) {
		$this->execute_sla = $execute_sla === 0 || $execute_sla === "0" || $execute_sla === "" ? false : $execute_sla;
	}
	public function getExecuteSLA() {
		return $this->execute_sla;
	}
	
	/*
	 * parse_hash_tags:
	 * - null (default): same than "auto". It means that the systems will find out automatically if page needs to parse hash_tags.
	 * - false: don't parse hash_tags.
	 * - true: parse hash_tags in html.
	 */
	public function setParseHashTags($parse_hash_tags) {
		$this->parse_hash_tags = $parse_hash_tags === 0 || $parse_hash_tags === "0" || $parse_hash_tags === "" ? false : $parse_hash_tags;
	}
	public function getParseHashTags() {
		return $this->parse_hash_tags;
	}
	
	/*
	 * parse_ptl:
	 * - null (default): same than "auto". It means that the systems will find out automatically if page needs to parse ptl.
	 * - false: don't parse ptl.
	 * - true: parse ptl in html.
	 */
	public function setParsePTL($parse_ptl) {
		$this->parse_ptl = $parse_ptl === 0 || $parse_ptl === "0" || $parse_ptl === "" ? false : $parse_ptl;
	}
	public function getParsePTL() {
		return $this->parse_ptl;
	}
	
	/*
	 * add_my_js_lib:
	 * - null (default): same than "auto". It means that the systems will find out automatically if page needs to add add_my_js_lib.
	 * - false: don't add add_my_js_lib.
	 * - true: add add_my_js_lib in html.
	 */
	public function setAddMyJSLib($add_my_js_lib) {
		$this->add_my_js_lib = $add_my_js_lib === 0 || $add_my_js_lib === "0" || $add_my_js_lib === "" ? false : $add_my_js_lib;
	}
	public function getAddMyJSLib() {
		return $this->add_my_js_lib;
	}
	
	/*
	 * add_widget_resource_lib:
	 * - null (default): same than "auto". It means that the systems will find out automatically if page needs to add widget_resource_js_lib.
	 * - false: don't add widget_resource_js_lib.
	 * - true: add widget_resource_js_lib in html.
	 */
	public function setAddWidgetResourceLib($add_widget_resource_lib) {
		$this->add_widget_resource_lib = $add_widget_resource_lib === 0 || $add_widget_resource_lib === "0" || $add_widget_resource_lib === "" ? false : $add_widget_resource_lib;
	}
	public function getAddWidgetResourceLib() {
		return $this->add_widget_resource_lib;
	}
	
	/*
	 * filter_by_permission:
	 * - null (default): same than "auto". It means that the systems will find out automatically if page needs to filter html elements by permission.
	 * - false: don't filter html elements by permission.
	 * - true: filter html elements by permission.
	 */
	public function setFilterByPermission($filter_by_permission) {
		$this->filter_by_permission = $filter_by_permission === 0 || $filter_by_permission === "0" || $filter_by_permission === "" ? false : $filter_by_permission;
	}
	public function getFilterByPermission() {
		return $this->filter_by_permission;
	}
	
	/*
	 * include_blocks_when_calling_resources:
	 * - false (default): don't include blocks when calling/executing resources.
	 * - true: include blocks when calling/executing resources.
	 */
	public function setIncludeBlocksWhenCallingResources($include_blocks_when_calling_resources) {
		$this->include_blocks_when_calling_resources = $include_blocks_when_calling_resources === 0 || $include_blocks_when_calling_resources === "0" || $include_blocks_when_calling_resources === "" ? false : $include_blocks_when_calling_resources;
	}
	public function getIncludeBlocksWhenCallingResources() {
		return $this->include_blocks_when_calling_resources;
	}
	
	/*
	 * init_user_data:
	 * - null (default): same than "auto". It means that the systems will check if the user module is installed and init the logged_user_type_ids. In case the user module are NOT installed in the correspondent project DB, the system will suppress the DB exception and continue the code execution, just like it happens when this setting is false.
	 * - false: don't init logged_user_type_ids.
	 * - true: init logged_user_type_ids. The user module must be installed and active in the correspondent project DB.
	 */
	public function setInitUserData($init_user_data) {
		$this->init_user_data = $init_user_data === 0 || $init_user_data === "0" || $init_user_data === "" ? false : $init_user_data;
	}
	public function getInitUserData() {
		return $this->init_user_data;
	}
	
	/*
	 * maximum_usage_memory: in bytes
	 */
	public function setMaximumUsageMemory($maximum_usage_memory) {
		$this->maximum_usage_memory = $maximum_usage_memory >= 0 ? $maximum_usage_memory : $this->maximum_usage_memory;
	}
	public function getMaximumUsageMemory() {
		return $this->maximum_usage_memory;
	}
	
	/*
	 * maximum_buffer_chunk_size: in bytes
	 */
	public function setMaximumBufferChunkSize($maximum_buffer_chunk_size) {
		$this->maximum_buffer_chunk_size = $maximum_buffer_chunk_size >= 0 ? $maximum_buffer_chunk_size : $this->maximum_buffer_chunk_size;
	}
	public function getMaximumBufferChunkSize() {
		return $this->maximum_buffer_chunk_size;
	}
	
}
?>
