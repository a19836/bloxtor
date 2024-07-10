<?php
include_once get_lib("org.phpframework.layer.presentation.cms.CMSLayer");
include_once get_lib("org.phpframework.layer.presentation.PresentationLayer");
include_once get_lib("org.phpframework.layer.cache.PresentationCacheLayer");

class CMSCacheLayer {
	public $CMSLayer;
	public $settings;
	
	protected $PresentationCacheLayer;
	
	public function __construct(CMSLayer $CMSLayer, $settings) {
		$this->CMSLayer = $CMSLayer;
		$this->settings = $settings;
	}
	
	protected function initPresentationCacheLayer($settings) {
		$P = $this->CMSLayer->getEVC()->getPresentationLayer();
		$PresentationLayer = new PresentationLayer($P->settings);
		
		$this->PresentationCacheLayer = new PresentationCacheLayer($PresentationLayer, $settings);
		
		$PresentationLayer->setCacheLayer($this->PresentationCacheLayer);
		$PresentationLayer->setPHPFrameWorkObjName($P->getPHPFrameWorkObjName());
		
		$brokers = $P->getBrokers();
		foreach ($brokers as $broker_name => $broker)
			$PresentationLayer->addBroker($broker, $broker_name);
	}
	
	public function isValid($service_id, $data, $options = false) {
		return $this->PresentationCacheLayer->isValid($this->CMSLayer->getEVC()->getPresentationLayer()->getSelectedPresentationId(), $service_id, $data, $options);
	}
	
	public function get($service_id, $data, $options = false) {
		return $this->PresentationCacheLayer->get($this->CMSLayer->getEVC()->getPresentationLayer()->getSelectedPresentationId(), $service_id, $data, $options);
	}
	
	public function check($service_id, $data, &$result, $options = false) {
		return $this->PresentationCacheLayer->check($this->CMSLayer->getEVC()->getPresentationLayer()->getSelectedPresentationId(), $service_id, $data, $result, $options);
	}
	
	/*
	$searched_services sample:
		Array(
		    [private/article/article_properties] => Array
			   (
				  [key] => article_properties_<?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?>
				  [module_id] => 
			   )

		    [private/article/channel_articles] => Array
			   (
				  [type] => prefix
				  [key] => channel_articles_<?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?>
				  [module_id] => 
			   )
		)
		
	FROM XML FILE:
		<service id="private/article/article_properties" cache_handler="ServiceCacheHandler" to_cache="true" ttl="600">
			<validation_script>return $_GET["article_id"] > 0;</validation_script>
			<key>article_properties_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?&gt;</key>
		</service>
		<service id="private/article/channel_articles" cache_handler="ServiceCacheHandler" to_cache="true" ttl="600">
			<key>channel_articles_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_type_id"] . "_" . $input["object_id"] . "_" . $input["group"] . "_" . hash("crc32b", strtolower($_GET["tag"])); ?&gt;</key>
		</service>
		<service id="private/admin/article/edit_article" cache_handler="ServiceCacheHandler">
			<validation_script>return $_POST;</validation_script>
			<to_delete>
				<service>
					<key>article_properties_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $GLOBALS["condo_id"] . "_" . $_GET["article_id"]; ?&gt;</key>
				</service>
				<service type="prefix">
					<key>channel_articles_&lt;?php echo $_SERVER["HTTP_HOST"] . "_" . $input["object_to_objects"][0]["object_type_id"] . "_" . $input["object_to_objects"][0]["object_id"] . "_" . $input["object_to_objects"][0]["group"] . "_"; ?&gt;</key>
				</service>
			</to_delete>
		</service>
	*/
	public function deleteSearchedServices($searched_services, $data = array(), $options = false) {
		return $this->PresentationCacheLayer->deleteSearchedServices($this->CMSLayer->getEVC()->getPresentationLayer()->getSelectedPresentationId(), $searched_services, $data, $options);
	}
}
?>
