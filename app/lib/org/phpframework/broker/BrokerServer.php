<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
