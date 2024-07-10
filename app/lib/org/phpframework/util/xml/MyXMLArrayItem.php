<?php
class MyXMLArrayItem {
	private $data;
	
	public function __construct($data) {
		$this->data = is_array($data) ? $data : array();
	}
	
	public function getChildNodes($nodes_path = "", $conditions = false, $item_obj = true) {
		$childs = $this->getChilds();
		$MyXMLArray = new MyXMLArray($childs);
		return $MyXMLArray->getNodes($nodes_path, $conditions, $item_obj);
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function getName() {
		return isset($this->data["name"]) ? $this->data["name"] : null;
	}
	
	public function getValue() {
		return isset($this->data["value"]) ? $this->data["value"] : null;
	}
	
	public function getChilds() {
		return isset($this->data["childs"]) && is_array($this->data["childs"]) ? $this->data["childs"] : array();
	}
	
	public function getChildsCount() {
		$childs = $this->getChilds();
		$keys = array_keys($childs);
		return count($keys);
	}
	
	public function attributeExists($name) {
		return isset($this->data["@"][$name]);
	}
	
	public function getAttribute($name) {
		return isset($this->data["@"][$name]) ? $this->data["@"][$name] : false;
	}

	public function getAttributes() {
		return isset($this->data["@"]) && is_array($this->data["@"]) ? $this->data["@"] : array();
	}
    
	public function getAttributesName() {
		$attrs = $this->getAttributes();
		return array_keys($attrs);
	}
	
	public function getAttributesCount() {
		$attrs = $this->getAttributesName();
		return count($attrs);
	}
	
	public function checkAttributes($conditions = false) {
		if($conditions) {
			$condition_keys = is_array($conditions) && count($conditions) ? array_keys($conditions) : false;
			if($condition_keys) {
				if($this->getAttributesCount()) {
					$t = count($condition_keys);
					for($i = 0; $i < $t; $i++) {
						$attr_name = $condition_keys[$i];
						
						if(!$this->attributeExists($attr_name) || $this->getAttribute($attr_name) != $conditions[$attr_name]) {
							return false;
						}
					}
					return true;
				}
				return false;
			}
		}
		return true;
	}
}
?>
