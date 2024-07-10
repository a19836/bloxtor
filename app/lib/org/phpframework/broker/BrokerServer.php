<?php
include_once get_lib("org.phpframework.broker.Broker");
include_once get_lib("org.phpframework.layer.Layer");

abstract class BrokerServer extends Broker {
	protected $Layer;
	
	public function __construct(Layer $Layer) {
		$this->Layer = $Layer;
		
		parent::__construct();
	}
	
	public function getBrokerLayer() {
		return $this->Layer;
	}
}
?>
