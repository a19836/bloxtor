<?php
interface IObjType {
	public function getField();
	public function setField($field);
	
	public function getData(); //simply returns the data.
	public function setData($data); //receives the data and parses it, converting it to what the user wishes.
}
?>
