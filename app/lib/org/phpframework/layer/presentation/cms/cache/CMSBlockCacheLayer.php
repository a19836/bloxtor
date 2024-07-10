<?php
include_once get_lib("org.phpframework.layer.presentation.cms.cache.CMSCacheLayer");

class CMSBlockCacheLayer extends CMSCacheLayer {
	
	public function __construct($CMSLayer, $settings) {
		parent::__construct($CMSLayer, $settings);
		
		//Preparing PresentationCacheLayer
		$presentation_cache_settings = array(
			"presentation_caches_path" => isset($settings["presentation_cms_block_caches_path"]) ? $settings["presentation_cms_block_caches_path"] : null,
			"presentations_cache_file_name" => isset($settings["presentations_cms_block_cache_file_name"]) ? $settings["presentations_cms_block_cache_file_name"] : null,
			"presentations_cache_path" => isset($settings["presentations_cms_block_cache_path"]) ? $settings["presentations_cms_block_cache_path"] : null,
			"presentations_default_cache_ttl" => isset($settings["presentations_cms_block_default_cache_ttl"]) ? $settings["presentations_cms_block_default_cache_ttl"] : null,
			"presentations_default_cache_type" => isset($settings["presentations_cms_block_default_cache_type"]) ? $settings["presentations_cms_block_default_cache_type"] : null,
			"presentations_module_cache_maximum_size" => isset($settings["presentations_cms_block_module_cache_maximum_size"]) ? $settings["presentations_cms_block_module_cache_maximum_size"] : null,
		);
		
		$this->initPresentationCacheLayer($presentation_cache_settings);
	}
}
?>
