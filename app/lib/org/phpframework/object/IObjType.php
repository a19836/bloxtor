<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

interface IObjType {
	public function getField();
	public function setField($field);
	
	public function getData(); //simply returns the data.
	public function setData($data); //receives the data and parses it, converting it to what the user wishes.
}
?>
