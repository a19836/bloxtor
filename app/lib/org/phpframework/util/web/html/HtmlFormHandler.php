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

include_once get_lib("org.phpframework.util.web.html.pagination.PaginationLayout");
include_once get_lib("org.phpframework.util.web.html.XssSanitizer");
include_once get_lib("org.phpframework.util.text.TextValidator");
include_once get_lib("org.phpframework.util.HashTagParameter");
include_once get_lib("org.phpframework.ptl.PHPTemplateLanguage");
//include_once get_lib("org.phpframework.phpscript.PHPUICodeExpressionHandler");

/*
<?php
//START: task[createform][Call HtmlFormHandler::createHtmlForm()]
$x = HtmlFormHandler::createHtmlForm(array(
	"with_form" => 1,
	"form_id" => "",
	"form_method" => "post",
	"form_class" => "",
	"form_type" => "",
	"form_on_submit" => "",
	"form_action" => "",
	"form_containers" => array(
		0 => array(
			"container" => array(
				"class" => "edit_featured_object",
				"href" => "",
				"target" => "",
				"title" => "",
				"title_position" => "auto",
				"previous_html" => "",
				"next_html" => "",
				"elements" => array(
					0 => array(
						"field" => array(
							"class" => "form-group username",
							"label" => array(
								"value" => "Username",
								"class" => "form-label control-label ",
								"title" => "",
								"title_position" => "",
								"width" => "",
								"height" => "",
								"offset" => "",
								"previous_html" => "",
								"next_html" => "",
							),
							"input" => array(
								"type" => "text",
								"class" => "",
								"place_holder" => "",
								"href" => "",
								"target" => "",
								"src" => "",
								"title" => "",
								"title_position" => "",
								"width" => "",
								"height" => "",
								"offset" => "",
								"previous_html" => "",
								"next_html" => "",
								"confirmation" => "",
								"confirmation_message" => "",
								"allow_null" => "",
								"validation_label" => "",
								"validation_message" => "Username cannot be undefined.",
								"validation_type" => "",
								"validation_regex" => "",
								"validation_func" => "",
								"min_length" => "",
								"max_length" => "",
								"min_value" => "",
								"max_value" => "",
								"min_words" => "",
								"max_words" => "",
							),
						),
					),
					1 => array(
						"field" => array(
							"class" => "form-group password",
							"label" => array(
								"value" => "Password",
								"class" => "form-label control-label ",
								"title" => "",
								"title_position" => "",
								"width" => "",
								"height" => "",
								"offset" => "",
								"previous_html" => "",
								"next_html" => "",
							),
							"input" => array(
								"type" => "password",
								"class" => "",
								"place_holder" => "",
								"href" => "",
								"target" => "",
								"src" => "",
								"title" => "",
								"title_position" => "",
								"width" => "",
								"height" => "",
								"offset" => "",
								"previous_html" => "",
								"next_html" => "",
								"confirmation" => "",
								"confirmation_message" => "",
								"allow_null" => "",
								"validation_label" => "",
								"validation_message" => "Password cannot be undefined.",
								"validation_type" => "",
								"validation_regex" => "",
								"validation_func" => "",
								"min_length" => "",
								"max_length" => "",
								"min_value" => "",
								"max_value" => "",
								"min_words" => "",
								"max_words" => "",
							),
						),
					),
				)
			)
		)
	), $input);
?>
*/
class HtmlFormHandler {
	public $settings;
	public $PHPTemplateLanguage;
	
	public static $INVALID_VALUE_FOR_ATTRIBUTE_MESSAGE = "Invalid value for";
	
	private $original_input_data; //it will be used in the parseOptions, parseExtraAttributes and parseAvailableValues methods
	private $default_input_data_var_name = "input";
	private $default_idx_var_name = "i";
	
	public function __construct($settings = false) {
		if (!empty($settings["ptl"])) {
			$settings["ptl"] = !is_array($settings["ptl"]) ? array() : $settings["ptl"];
		
			if (empty($settings["ptl"]["input_data_var_name"]))
				$settings["ptl"]["input_data_var_name"] = $this->default_input_data_var_name;
		
			if (empty($settings["ptl"]["idx_var_name"]))
				$settings["ptl"]["idx_var_name"] = $this->default_idx_var_name;
		}
		
		$settings["parse_values"] = isset($settings["parse_values"]) ? $settings["parse_values"] : true; //"parse_values" means that the html will be created by replacing the values with the correspondent $input parameter.
		
		$this->settings = $settings;
		$this->PHPTemplateLanguage = new PHPTemplateLanguage();
		
		if (!empty($settings["CacheHandler"]))
			$this->PHPTemplateLanguage->setCacheHandler($settings["CacheHandler"]);
	}
	
	public static function validateData($field_settings, $field_value, $field_label = false, &$message = false) {
		$status = true;
		
		if (is_array($field_value)) {
			if (empty($field_settings["input"]["allow_null"]) && !count($field_value))
				$status = false;
			
			if ($status && empty($field_settings["input"]["allow_javascript"]) && $field_value)
				if ($field_value != XssSanitizer::sanitizeVariable($field_value))
					$status = false;
			
			$status = $status && isset($field_settings["input"]["min_length"]) && is_numeric($field_settings["input"]["min_length"]) ? count($field_value) >= $field_settings["input"]["min_length"] : $status;
				$status = $status && isset($field_settings["input"]["max_length"]) && is_numeric($field_settings["input"]["max_length"]) ? count($field_value) <= $field_settings["input"]["max_length"] : $status;
		}
		else {
			if (empty($field_settings["input"]["allow_null"]) && !strlen($field_value))
				$status = false;
			
			//https://www.acunetix.com/websitesecurity/cross-site-scripting/
			//Change this code to prevent XSS attacks
			if ($status && empty($field_settings["input"]["allow_javascript"]) && $field_value) {
				if (strpos($field_value, "<script") !== FALSE) //checks if there is javascript code, and if yes, returns false
					$status = false;
				else if ($field_value != XssSanitizer::sanitizeVariable($field_value))
					$status = false;
			}
			
			if ($status && $field_value) { //only checks if $field_value exists, bc if it doesn't it means that this field can be empty, otherwise was already checked in the conditions before and the $status is false and will never enter here.
				$type = isset($field_settings["input"]["validation_type"]) ? $field_settings["input"]["validation_type"] : null;
				
				switch ($type) {
					case "int":
					case "bigint":
					case "number":
						 $status = TextValidator::isNumber($field_value); break;
					case "decimal":
					case "float":
					case "double":
						 $status = TextValidator::isDecimal($field_value); break;
					case "smallint": $status = TextValidator::isSmallInt($field_value); break;
					case "phone": $status = TextValidator::isPhone($field_value); break;
					case "fax": $status = TextValidator::isPhone($field_value); break;
					case "email": $status = TextValidator::isEmail($field_value); break;
					case "date": $status = TextValidator::isDate($field_value); break;
					case "datetime": 
					case "timestamp": 
						$status = TextValidator::isDateTime($field_value); break;
					case "time": $status = TextValidator::isTime($field_value); break;
					case "ipaddress": $status = TextValidator::isIPAddress($field_value); break;
					case "filename": $status = TextValidator::isFileName($field_value); break;
				}
				
				if ($status && !empty($field_settings["input"]["validation_regex"]))
					$status = @preg_match($field_settings["input"]["validation_regex"], $field_value); //@ in case the regex be invalid
				
				$status = $status && isset($field_settings["input"]["min_length"]) && is_numeric($field_settings["input"]["min_length"]) ? TextValidator::checkMinLength($field_value, $field_settings["input"]["min_length"]) : $status;
				$status = $status && isset($field_settings["input"]["max_length"]) && is_numeric($field_settings["input"]["max_length"]) ? TextValidator::checkMaxLength($field_value, $field_settings["input"]["max_length"]) : $status;
				
				if ($type == "date" || $type == "datetime" || $type == "datetime-local") { //if date
					$status = $status && !empty($field_settings["input"]["min_value"]) ? TextValidator::checkMinDate($field_value, $field_settings["input"]["min_value"]) : $status;
					$status = $status && !empty($field_settings["input"]["max_value"]) ? TextValidator::checkMaxDate($field_value, $field_settings["input"]["max_value"]) : $status;
				}
				else {
					$status = $status && isset($field_settings["input"]["min_value"]) && is_numeric($field_settings["input"]["min_value"]) ? TextValidator::checkMinValue($field_value, $field_settings["input"]["min_value"]) : $status;
					$status = $status && isset($field_settings["input"]["max_value"]) && is_numeric($field_settings["input"]["max_value"]) ? TextValidator::checkMaxValue($field_value, $field_settings["input"]["max_value"]) : $status;
				}
				
				$status = $status && isset($field_settings["input"]["min_words"]) && is_numeric($field_settings["input"]["min_words"]) ? TextValidator::checkMinWords($field_value, $field_settings["input"]["min_words"]) : $status;
				$status = $status && isset($field_settings["input"]["max_words"]) && is_numeric($field_settings["input"]["max_words"]) ? TextValidator::checkMaxWords($field_value, $field_settings["input"]["max_words"]) : $status;
			}
		}
			
		if (!$status) {
			$message = !empty($field_settings["input"]["validation_message"]) ? $field_settings["input"]["validation_message"] : self::$INVALID_VALUE_FOR_ATTRIBUTE_MESSAGE . " " . $field_label . ".";
			return false;
		}
		
		return true;
	}
	
	public static function createHtmlForm($settings, $input_data = false) {
		//echo "<pre>";print_r($input_data);print_r($settings);echo"</pre>";die();
		$HtmlFormHandler = new HtmlFormHandler($settings);
		
		if (!empty($settings["ptl"])) {
			$input_data_var_name = isset($HtmlFormHandler->settings["ptl"]["input_data_var_name"]) ? $HtmlFormHandler->settings["ptl"]["input_data_var_name"] : null;
			$ptl_external_vars = array($input_data_var_name => $input_data);
			$ptl_external_vars = isset($HtmlFormHandler->settings["ptl"]["external_vars"]) && is_array($HtmlFormHandler->settings["ptl"]["external_vars"]) ? array_merge($HtmlFormHandler->settings["ptl"]["external_vars"], $ptl_external_vars) : $ptl_external_vars;
			
			$input_data = false;
		}
		
		if (!empty($settings["ptl"]["code"])) {
			$settings["ptl"]["code"] = $HtmlFormHandler->getPTLParsedValueFromData($settings["ptl"]["code"], $input_data); 
			
			return $HtmlFormHandler->PHPTemplateLanguage->parseTemplate($settings["ptl"]["code"], $ptl_external_vars);
		}
		
		$html = $HtmlFormHandler->getHtmlForm($input_data);
		
		if (!empty($settings["ptl"]))
			return $HtmlFormHandler->PHPTemplateLanguage->parseTemplate($html, $ptl_external_vars);
		
		return $html;
	}
	
	public function getHtmlForm($input_data = false) {
		$html = '';
		
		$this->original_input_data = $input_data;
		$settings = $this->settings;
		
		if (!empty($settings["form_css"]))
			$html .= '<style>' . str_replace('\n', "\n", $settings["form_css"]) . '</style>';
		
		if (!empty($settings["with_form"])) {
			$html .= '<form';
		
			$form_id = !empty($settings["form_id"]) ? $this->parseSettingsAttributeValue($settings["form_id"], $input_data) : "form_" . rand(0, 10000);
			
			if ($form_id) 
				$html .= ' id="' . $form_id . '"';
			
			if (!empty($settings["form_method"]))
				$html .= ' method="' . $this->parseSettingsAttributeValue($settings["form_method"], $input_data) . '"';
			
			$html .= ' class="';
			
			if (!empty($settings["form_type"])) {
				//this only works with boostrap. More info in: http://getbootstrap.com/css/#forms
				switch ($settings["form_type"]) {
					case "horizontal": $html .= 'form-horizontal'; break;
					case "inline": $html .= 'form-inline'; break;
				}
			}
			
			if (!empty($settings["form_class"]))
				$html .= ' ' . $this->parseSettingsAttributeValue($settings["form_class"], $input_data, false, false, " ");
			
			$html .= '"';
			
			if (!empty($settings["form_action"]))
				$html .= ' action="' . $this->parseSettingsAttributeValue($settings["form_action"], $input_data) . '"';
			
			//Note that the $settings["form_on_submit"] must be before the MyJSLib.FormHandler.formCheck, bc in case exists an html editor, we need to populate first the field with the text from the editor.
			$html .= ' onSubmit="return ' . (!empty($settings["form_on_submit"]) ? $settings["form_on_submit"] . ' && ' : '') . '(typeof MyJSLib == \'undefined\' || MyJSLib.FormHandler.formCheck(this));"';
			
			if (!empty($settings["extra_attributes"])) {
				$settings["extra_attributes"] = $this->parseExtraAttributes($settings["extra_attributes"], $input_data);
				$attrs = array("extra_attributes");
				$html .= $this->getFieldAttributes($settings, $attrs, $input_data);
			}
			
			$html .= ' enctype="multipart/form-data">';
		}
		
		$html .= isset($settings["form_containers"]) ? $this->createElements($settings["form_containers"], $input_data) : "";
		
		if (!empty($settings["with_form"]))
			$html .= '</form>
			<script>if (typeof MyJSLib != "undefined") { MyJSLib.FormHandler.initForm( $("#' . $form_id . '")[0] ) }</script>';
			//Note: we need to have the 'typeof MyJSLib != "undefined"', otherwise we will have a javascript error. This was well tested and it's correct! Please do NOT change it!
		
		if (!empty($settings["form_js"]))
			$html .= '<script>' . str_replace('\n', "\n", $settings["form_js"]) . '</script>';
	
		return $html;
	}
	
	public function getParsedValueFromData($value, $input_data, $idx = false, $available_values = false) {
		$available_values = $this->parseAvailableValues($available_values, $input_data, $idx);
		
		if ($value && $input_data && empty($this->settings["ptl"]) && is_string($value) && substr($value, 0, 1) == "#" && substr($value, -1, 1) == "#" && strpos(substr($value, 1, -1), "#") === false) { //if ptl simply calls the parseSettingsValue bellow bc the result will be the same and avoids calling the parseSettingsValue twice unecesssary
			$value = $this->parseNewInputData($value, $input_data, $idx);
			
			if (!is_string($value) || empty($value))
				return $value;
			//otherwise calls parseSettingsValue with the available_values bellow
		}
		
		return $this->parseSettingsValue($value, $input_data, $idx, $available_values);
	}
	
	/* 
	 * Note: When the $settings["ptl"]["code"] contains html mixed with ptl tags and hashtags (#something#), the method parseSettingsValue will try to replace the hashtag '#something#' with '\$input[\$idx][something]' and then encapsulate this result in a new ptl echo tag: '<ptl:echo \$input[\$idx][something]/>' ptl tag. 
	 * If the html does not contains any ptl tag before, it works fine, but otherwise gives a php error, this is:
		If the html contains other ptl tags like 
			'<ptl:if \$x>#something#</ptl:if>'
		...this code will give an error, bc it will return ptl tags inside of the main ptl echo tag which, this is it will return the html:
			'<ptl:echo "<ptl:if \$x&gt;" \$input[\$idx][something] "</ptl:if&gt;"/>'
	 * 
	 * This means that we can only call the method parseSettingsValue if there is no ptl tags before, this is, only normal html.
	 *
	 * TODO: In the future try to find a solution for this problem...
	 */
	public function getPTLParsedValueFromData($value, $input_data, $idx = false, $available_values = false) {
		if (strpos($value, '#') !== false && strpos($value, '<ptl:') === false) {
			$available_values = $this->parseAvailableValues($available_values, $input_data, $idx);
			
			return $this->parseSettingsValue($value, $input_data, $idx, $available_values);
		}
		
		return $value;
	}
	
	public function getFieldHtml($field, $input_data = false, $idx = false) {
		return $this->createField($field, $input_data, $idx);
	}
	
	public function getFieldLabelHtml($field, $input_data = false, $idx = false) {
		return $this->createFieldSettingsLabel($field, $input_data, $idx);
	}
	
	public function getFieldInputHtml($field, $input_data = false, $idx = false) {
		return $this->createFieldSettingsInput($field, $input_data, $idx);
	}
	
	public function createElements($elements, $input_data = false, $idx = false) {
		$html = '';
		
		if (is_array($elements))
			foreach ($elements as $element)
				$html .= $this->createElement($element, $input_data, $idx);
		
		return $html;
	}
	
	/* CREATE ELEMENTS */
	public function createElement($element, $input_data = false, $idx = false) {
		$html = '';
		
		if (!empty($element["ptl"])) {
			$input_data_var_name = !empty($element["ptl"]["input_data_var_name"]) ? $element["ptl"]["input_data_var_name"] : (
				!empty($this->settings["ptl"]["input_data_var_name"]) ? $this->settings["ptl"]["input_data_var_name"] : $this->default_input_data_var_name
			);
			$idx_var_name = !empty($element["ptl"]["idx_var_name"]) ? $element["ptl"]["idx_var_name"] : (
				!empty($this->settings["ptl"]["idx_var_name"]) ? $this->settings["ptl"]["idx_var_name"] : $this->default_idx_var_name
			);
			//print_r($element);die();
			//print_r(array($input_data_var_name => $input_data, $idx_var_name => $idx));die();
			
			if (!empty($element["ptl"]["code"])) {
				$element["ptl"]["code"] = $this->getPTLParsedValueFromData($element["ptl"]["code"], $input_data, $idx); 
				//echo "input_data_var_name:$input_data_var_name<textarea>".$element["ptl"]["code"]."</textarea><pre>";print_r($input_data);die();
				
				return $this->PHPTemplateLanguage->parseTemplate($element["ptl"]["code"], array($input_data_var_name => $input_data, $idx_var_name => $idx));
			}
			
			return "";
		}
		else if (!empty($element["container"]))
			$html .= $this->createContainer($element["container"], $input_data, $idx);
		else if (!empty($element["field"]))
			$html .= $this->createField($element["field"], $input_data, $idx);
		else if (!empty($element["pagination"]))
			$html .= $this->createPagination($element["pagination"], $input_data, $idx);
		else if (!empty($element["table"]) || !empty($element["tree"])) {
			if (!empty($this->settings["ptl"]) && isset($idx) && $idx !== false) {
				$old_input_data_var_name = $this->settings["ptl"]["input_data_var_name"];
				$this->settings["ptl"]["input_data_var_name"] = $old_input_data_var_name . "_" . rand(1, 1000);
				
				$html .= '<ptl:var:' . $this->settings["ptl"]["input_data_var_name"] . ' @$' . $old_input_data_var_name . '[$' . $this->settings["ptl"]["idx_var_name"] . '] />';
				$items = null;
			}
			else 
				$items = isset($idx) && $idx !== false ? $input_data[$idx] : $input_data;
			
			$html .= !empty($element["table"]) ? $this->createTable($element["table"], $items) : $this->createTree($element["tree"], $items);
			
			if (!empty($this->settings["ptl"]) && isset($idx) && $idx !== false)
				$this->settings["ptl"]["input_data_var_name"] = $old_input_data_var_name;
		}
		
		return $html;
	}
	
	private function createContainer($container, $input_data = false, $idx = false) {
		$html = '<div';
		
		if (!empty($container["id"]))
			$html .= ' id="' . $this->parseSettingsAttributeValue($container["id"], $input_data, $idx) . '"';
		
		if (!empty($container["class"]))
			$html .= ' class="' . $this->parseSettingsAttributeValue($container["class"], $input_data, $idx, false, " ") . '"';
		
		$container["extra_attributes"] = isset($container["extra_attributes"]) ? $this->parseExtraAttributes($container["extra_attributes"], $input_data, $idx) : null;
		$attrs = array("extra_attributes");
		$html .= $this->getFieldAttributes($container, $attrs, $input_data, $idx);
		
		$html .= '>';
		
		$href = !empty($container["href"]) && (!empty($this->settings["ptl"]) || trim($this->parseSettingsAttributeValue($container["href"], $input_data, $idx)));
		if ($href) {
			$html .= '<a';
			
			$attrs = array("href", "title", "target");
			$html .= $this->getFieldAttributes($container, $attrs, $input_data, $idx);
			
			$html .= '>';
		}
		
		$html .= isset($container["previous_html"]) ? $this->parseSettingsValue($container["previous_html"], $input_data, $idx) : "";
		$html .= isset($container["elements"]) ? $this->createElements($container["elements"], $input_data, $idx) : "";
		$html .= isset($container["next_html"]) ? $this->parseSettingsValue($container["next_html"], $input_data, $idx) : "";
		
		if ($href)
			$html .= '</a>';
		
		$html .= '</div>';
		
		return $html;
	}
	
	private function createField($field, $input_data = false, $idx = false) {
		$html = '';
		//echo "<pre>";print_r($input_data);die();
		
		$field_type = isset($field["input"]["type"]) ? $field["input"]["type"] : $field["type"]; //The $field["type"] is for the old files that still contain this attribute.
		$field_type = $this->parseSettingsAttributeValue($field_type, $input_data, $idx);
		$field_type = strtolower($field_type);
		$field["input"]["type"] = $field_type;
		
		if (empty($field["disable_field_group"])) {
			$c = !empty($field["class"]) ? $this->parseSettingsAttributeValue($field["class"], $input_data, $idx, false, " ") : "";
			$c .= $field_type == "hidden" ? " hidden" : "";
			
			$field["extra_attributes"] = isset($field["extra_attributes"]) ? $this->parseExtraAttributes($field["extra_attributes"], $input_data, $idx) : null;
			$attrs = array("extra_attributes");
			$html .= $this->getFieldAttributes($field, $attrs, $input_data, $idx);
			
			$html .= '<div' . (trim($c) ? ' class="' . $c . '"' : '') . '>';
		}
		
		foreach ($field as $k => $v)
			switch ($k) {
				case "label":
					$html .= $this->createFieldSettingsLabel($field, $input_data, $idx);
					break;
				case "input":
					$html .= $this->createFieldSettingsInput($field, $input_data, $idx);
					break;
			}
		
		if (empty($field["disable_field_group"]))
			$html .= '</div>';
		
		return $html;
	}
	
	private function createPagination($pagination, $input_data = false, $idx = false) {
		if (!empty($this->settings["ptl"]))
			return $this->createPTLPagination($pagination);
		
		$page_attr_name = !empty($pagination["page_attr_name"]) ? $this->getParsedValue($pagination["page_attr_name"], $input_data, $idx) : 'pg';
		$max_num_of_shown_pages = !empty($pagination["max_num_of_shown_pages"]) ? $this->getParsedValue($pagination["max_num_of_shown_pages"], $input_data, $idx) : 5;
		$total_rows = isset($pagination["total_rows"]) ? $this->getParsedValue($pagination["total_rows"], $input_data, $idx) : null;
		$rows_per_page = isset($pagination["rows_per_page"]) ? $this->getParsedValue($pagination["rows_per_page"], $input_data, $idx) : null;
		$page_number = isset($pagination["page_number"]) ? $this->getParsedValue($pagination["page_number"], $input_data, $idx) : null;
		$pagination_template = isset($pagination["pagination_template"]) ? $this->getParsedValue($pagination["pagination_template"], $input_data, $idx) : null;
		$on_click_js_func = isset($pagination["on_click_js_func"]) ? $this->getParsedValue($pagination["on_click_js_func"], $input_data, $idx) : null;
		//echo "<pre>page_attr_name:$page_attr_name\nmax_num_of_shown_pages:$max_num_of_shown_pages\ntotal_rows:$total_rows\nrows_per_page:$rows_per_page\npage_number:$page_number\npagination_template:$pagination_template\non_click_js_func:$on_click_js_func\n";print_r($pagination);die();
		
		$settings = array(
			$page_attr_name => $page_number,
		);
		
		$PaginationLayout = new PaginationLayout($total_rows, $rows_per_page, $settings, $page_attr_name, $on_click_js_func);
		$PaginationLayout->show_x_pages_at_once = $max_num_of_shown_pages;
		
		$data = $PaginationLayout->data;
		$data["style"] = $pagination_template;
		
		$html = $PaginationLayout->designWithStyle(1, $data);
		
		return $html;
	}
	
	private function createPTLPagination($pagination) {
		$page_attr_name = !empty($pagination["page_attr_name"]) ? $this->getParsedValue($pagination["page_attr_name"]) : 'pg';
		$max_num_of_shown_pages = !empty($pagination["max_num_of_shown_pages"]) ? $this->getParsedValue($pagination["max_num_of_shown_pages"]) : 5;
		$total_rows = isset($pagination["total_rows"]) ? $this->getParsedValue($pagination["total_rows"]) : null;
		$rows_per_page = isset($pagination["rows_per_page"]) ? $this->getParsedValue($pagination["rows_per_page"]) : null;
		$page_number = isset($pagination["page_number"]) ? $this->getParsedValue($pagination["page_number"]) : null;
		$pagination_template = isset($pagination["pagination_template"]) ? $this->getParsedValue($pagination["pagination_template"]) : null;
		$on_click_js_func = isset($pagination["on_click_js_func"]) ? $this->getParsedValue($pagination["on_click_js_func"]) : null;
		
		//prepare integers:
		$total_rows = empty($total_rows) ? 0 : $total_rows;
		$rows_per_page = empty($rows_per_page) ? 0 : $rows_per_page;
		$page_number = empty($page_number) ? 0 : $page_number;
		$max_num_of_shown_pages = empty($max_num_of_shown_pages) ? 0 : $max_num_of_shown_pages;
		
		$html = '<ptl:var:PaginationLayout new PaginationLayout(' . $total_rows . ', ' . $rows_per_page . ', array("' . $page_attr_name . '" =&gt; ' . $page_number . '), "' . $page_attr_name . '"' . ($on_click_js_func ? ', "' . $on_click_js_func . '"' : "") . ') />
		<ptl:var:PaginationLayout-&gt;show_x_pages_at_once ' . $max_num_of_shown_pages . ' />
		<ptl:var:pagination_data $PaginationLayout-&gt;data />
		<ptl:var:pagination_data["style"] "' . $pagination_template . '" />
		<ptl:echo $PaginationLayout-&gt;designWithStyle(1, $pagination_data) />';
		
		return $html;
	}
	
	private function createTable($table, $input_data = false) {
		if (!empty($this->settings["ptl"]))
			return $this->createPTLTable($table);
		
		//echo "<pre>";print_r($input_data);
		if (!empty($table["default_input_data"]))
			$input_data = $this->parseNewInputData($table["default_input_data"], $input_data);
		
		$html = '<table';
		if (!empty($table["table_class"]))
			$html .= ' class="' . $this->parseSettingsAttributeValue($table["table_class"], $input_data, false, false, " ") . '"';
		$html .= '>';
		
		$elements = isset($table["elements"]) ? $table["elements"] : null;
		//echo "<pre>";print_r($input_data);die();
		
		if ($elements) {
			$rows_class = !empty($table["rows_class"]) ? ' class="' . $this->parseSettingsAttributeValue($table["rows_class"], $input_data, false, false, " ") . '"' : '';
			
			$html .= '
			<thead>
				<tr' . $rows_class . '>';
			foreach ($elements as $idx => $element) 
				if (is_array($element) && !empty($element)) {
					reset($element);
					$first_key = key($element);
					$class = !empty($element[$first_key]["class"]) ? ' class="' . $this->parseSettingsAttributeValue($element[$first_key]["class"], $input_data, false, false, " ") . '"' : '';
					
					$html .= '<th' . $class . '>' . $this->createFieldSettingsLabel($element[$first_key], $input_data) . '</th>';
					
					unset($elements[$idx]["field"]["label"]);//in case of the element be a FIELD element. This will be used bellow in the createElement function, if the element is a field. This avoids creating labels when call the createElement function.
				}
			$html .= '</tr>
			</thead>
			<tbody>';
			
			if (is_array($input_data) || is_object($input_data)) {
				foreach ($input_data as $i => $aux) {
					$rows_class = !empty($table["rows_class"]) ? ' class="' . $this->parseSettingsAttributeValue($table["rows_class"], $input_data, $i, false, " ") . '"' : '';
					
					$html .= '<tr' . $rows_class . '>';
					foreach ($elements as $element) 
						if (is_array($element) && !empty($element)) {
							reset($element);
							$first_key = key($element); //first_key == field or container or etc...
							$class = !empty($element[$first_key]["class"]) ? ' class="' . $this->parseSettingsAttributeValue($element[$first_key]["class"], $input_data, $i, false, " ") . '"' : '';
							
							$element[$first_key]["class"] = "";
							$element[$first_key]["disable_field_group"] = 1;
							
							$html .= '<td' . $class . '>' . $this->createElement($element, $input_data, $i)  . '</td>';
						}
					$html .= '</tr>';
				}
			}
			
			$html .= '</tbody>';
		}
		
		$html .= '</table>';
		
		return $html;
	}
	
	private function createPTLTable($table) {
		$html = '';
		$rand = rand(1, 1000);
		$old_input_data_var_name = isset($this->settings["ptl"]["input_data_var_name"]) ? $this->settings["ptl"]["input_data_var_name"] : null;
		$input_data_var_name = $old_input_data_var_name . "_$rand";
		
		if (!empty($table["default_input_data"]))
			$html .= '<ptl:var:' . $input_data_var_name . ' ' . str_replace(">", "&gt;", var_export($this->parseNewInputData($table["default_input_data"]), true)) . '/>';
		else
			$html .= '<ptl:var:' . $input_data_var_name . ' @$' . $old_input_data_var_name . '/>';
		
		$this->settings["ptl"]["input_data_var_name"] = $input_data_var_name;
		
		$html .= '<table';
		if (!empty($table["table_class"]))
			$html .= ' class="' . $this->parseSettingsAttributeValue($table["table_class"], false, false, false, " ") . '"';
		$html .= '>';
		
		$elements = isset($table["elements"]) ? $table["elements"] : null;
		
		if ($elements) {
			$old_idx_var_name = isset($this->settings["ptl"]["idx_var_name"]) ? $this->settings["ptl"]["idx_var_name"] : null;
			$this->settings["ptl"]["idx_var_name"] = 'idx_' . $rand;
			
			$rows_class = !empty($table["rows_class"]) ? ' class="' . $this->parseSettingsAttributeValue($table["rows_class"], false, false, false, " ") . '"' : '';
			
			$html .= '
			<thead>
				<tr' . $rows_class . '>';
			foreach ($elements as $idx => $element) 
				if (is_array($element) && !empty($element)) {
					reset($element);
					$first_key = key($element);
					$class = !empty($element[$first_key]["class"]) ? ' class="' . $this->parseSettingsAttributeValue($element[$first_key]["class"], false, false, false, " ") . '"' : '';
					
					$html .= '<th' . $class . '>' . $this->createFieldSettingsLabel($element[$first_key]) . '</th>';
					
					unset($elements[$idx]["field"]["label"]);//in case of the element be a FIELD element. This will be used bellow in the createElement function, if the element is a field. This avoids creating labels when call the createElement function.
				}
			
			$html .= '</tr>
			</thead>
			<tbody>
				<ptl:if is_array(@$' . $input_data_var_name . ')>
					<ptl:foreach $' . $input_data_var_name . ' ' . $this->settings["ptl"]["idx_var_name"] . ' item>
						<tr' . $rows_class . '>';
			foreach ($elements as $element) 
				if (is_array($element) && !empty($element)) {
					reset($element);
					$first_key = key($element);
					$class = !empty($element[$first_key]["class"]) ? ' class="' . $this->parseSettingsAttributeValue($element[$first_key]["class"], false, false, false, " ") . '"' : '';
					$element[$first_key]["class"] = "";
					$element[$first_key]["disable_field_group"] = 1;
					
					$html .= '		<td' . $class . '>' . $this->createElement($element, null, true)  . '</td>';
					//$this->createElement($element, null, true): true here is very important so the inner trees or tables have the right input_data. Otherwise it won't be correct.
				}
			$html .= '		</tr>			
					</ptl:foreach>
				</ptl:if>
			</tbody>';
			
			$this->settings["ptl"]["idx_var_name"] = $old_idx_var_name;
		}
		
		$this->settings["ptl"]["input_data_var_name"] = $old_input_data_var_name;
		
		$html .= '</table>';
		
		return $html;
	}
	
	private function createTree($tree, $input_data = false) {
		if (!empty($this->settings["ptl"]))
			return $this->createPTLTree($tree);
		
		$html = '';
		$html_tag = !empty($tree["ordered"]) ? 'ol' : 'ul';
		
		if (!empty($tree["default_input_data"]))
			$input_data = $this->parseNewInputData($tree["default_input_data"], $input_data);		
		
		$html .= '<' . $html_tag;
		if (!empty($tree["tree_class"]))
			$html .= ' class="' . $this->parseSettingsAttributeValue($tree["tree_class"], $input_data, false, false, " ") . '"';
		$html .= '>';
		
		$elements = isset($tree["elements"]) ? $tree["elements"] : null;
		
		if ($elements) {
			if (is_array($input_data) || is_object($input_data)) {
				foreach ($input_data as $i => $item) {
					$fields = "";
					foreach ($elements as $element)
						$fields .= $this->createElement($element, $input_data, $i);
				
					$lis_class = !empty($tree["lis_class"]) ? ' class="' . $this->parseSettingsAttributeValue($tree["lis_class"], $input_data, $i, false, " ") . '"' : '';
				
					$html .= '<li' . $lis_class . '>' . $fields;
				
					//Preparing input_data item's childs
					if (!empty($tree["recursive"]) && !empty($tree["recursive_input_data"])) {
						$sub_items = $this->parseNewInputData($tree["recursive_input_data"], $item);
					
						if (!empty($sub_items) && (is_array($sub_items) || is_object($sub_items)))
							$html .= $this->createTree($tree, $sub_items);
					}
				
					$html .= '</li>';
				}
			}
		}
		
		$html .= '</' . $html_tag . '>';
		
		return $html;
	}
	
	private function createPTLTree($tree) {
		$html = '';
		$html_tag = !empty($tree["ordered"]) ? 'ol' : 'ul';
		$rand = rand(1, 1000);
		$old_input_data_var_name = isset($this->settings["ptl"]["input_data_var_name"]) ? $this->settings["ptl"]["input_data_var_name"] : null;
		$input_data_var_name = $old_input_data_var_name . "_$rand";
		
		if (!empty($tree["default_input_data"]))
			$html .= '<ptl:var:' . $input_data_var_name . ' ' . str_replace(">", "&gt;", var_export($this->parseNewInputData($tree["default_input_data"]), true)) . '/>';
		else
			$html .= '<ptl:var:' . $input_data_var_name . ' @$' . $old_input_data_var_name . '/>';
		
		$this->settings["ptl"]["input_data_var_name"] = $input_data_var_name;
		
		$html .= '<' . $html_tag;
		if (!empty($tree["tree_class"]))
			$html .= ' class="' . $this->parseSettingsAttributeValue($tree["tree_class"], false, false, false, " ") . '"';
		$html .= '>';
		
		$elements = isset($tree["elements"]) ? $tree["elements"] : null;
		
		if ($elements) {
			$didvn = $this->default_input_data_var_name;
			$this->settings["ptl"]["input_data_var_name"] = $didvn;
			$old_idx_var_name = isset($this->settings["ptl"]["idx_var_name"]) ? $this->settings["ptl"]["idx_var_name"] : null;
			$this->settings["ptl"]["idx_var_name"] = 'i';
			
			$lis_class = !empty($tree["lis_class"]) ? ' class="' . $this->parseSettingsAttributeValue($tree["lis_class"], false, false, false, " ") . '"' : '';
			
			$fields = "";
			foreach ($elements as $element)
				$fields .= $this->createElement($element, null, true); //true here is very important so the inner trees or tables have the right input data. Otherwise it won't be correct.
			
			//Get inner functions and put them outside of main function
			do {
				preg_match("/<ptl:function:([\w]+) $didvn>/iu", $fields, $matches, PREG_OFFSET_CAPTURE); //'\w' means all words with '_' and 'u' means with accents and ç too. '/u' converts unicode to accents chars.
				//print_r($matches);
				
				if (!empty($matches[0])) {
					$tag = "</ptl:function:" . $matches[1][0] . ">";
					$start = $matches[0][1];
					$end = strpos($fields, $tag, $start) + strlen($tag);
			
					$html .= substr($fields, $start, $end - $start);
					$fields = substr($fields, 0, $start) . substr($fields, $end);
				}
			} while ($matches);
			
			//Create Tree function
			$html .= '
			<ptl:function:createTree_' . $rand . ' ' . $didvn . '>
				<ptl:if !empty($' . $didvn . ') && (is_array($' . $didvn . ') || is_object($' . $didvn . '))>
					<ptl:foreach $' . $didvn . ' i item>
						<li' . $lis_class . '>' . $fields;
						
			
			//Preparing input_data item's childs
			if (!empty($tree["recursive"]) && !empty($tree["recursive_input_data"])) {
				$this->settings["ptl"]["input_data_var_name"] = 'item';
				$html .= '		<ptl:var:sub_items ' . str_replace(">", "&gt;", var_export($this->parseNewInputData($tree["recursive_input_data"]), true)) . '/>
							<ptl:createTree_' . $rand . ' $sub_items />';
			}
			
			$html .= '		</li>
					</ptl:foreach>
				</ptl:if>
			</ptl:function:createTree_' . $rand . '>';
			
			//Call Tree function
			$html .= '<ptl:createTree_' . $rand . ' @$' . $input_data_var_name . ' />';
			
			$this->settings["ptl"]["idx_var_name"] = $old_idx_var_name;
		}
		
		$this->settings["ptl"]["input_data_var_name"] = $old_input_data_var_name;
		
		$html .= '</' . $html_tag . '>';
		
		return $html;
	}
	
	/* CREATE FIELDS */
	private function createFieldSettingsLabel($field, $input_data = false, $idx = false) {
		$label_field = isset($field["label"]) ? $field["label"] : null;
		
		if ($label_field) {
			$html = isset($label_field["previous_html"]) ? $this->parseSettingsValue($label_field["previous_html"], $input_data, $idx) : "";
			
			if (!empty($label_field["value"])) {
				$label_type = !empty($label_field["type"]) ? $label_field["type"] : "label";
				
				$html .= '<' . $label_type . ' class="' . $this->getSettingsClass($label_field, $input_data, $idx) . '"';
				
				$label_field["extra_attributes"] = isset($label_field["extra_attributes"]) ? $this->parseExtraAttributes($label_field["extra_attributes"], $input_data, $idx) : null;
				$attrs = array("title", "extra_attributes");
				$html .= $this->getFieldAttributes($label_field, $attrs, $input_data, $idx);
			
				$html .= '>';
				$html .= isset($label_field["value"]) ? $this->parseSettingsValue($label_field["value"], $input_data, $idx) : "";
				$html .= '</' . $label_type . '>';
			}
			
			$html .= isset($label_field["next_html"]) ? $this->parseSettingsValue($label_field["next_html"], $input_data, $idx) : "";
			
			return $html;
		}
	}
	
	private function createFieldSettingsInput($field, $input_data = false, $idx = false) {
		//Preparing available_values according with settings from form task
		if (isset($field["input"]["available_values"]))
			$field["input"]["available_values"] = $this->parseAvailableValues($field["input"]["available_values"], $input_data, $idx);
		
		if (isset($field["input"]["extra_attributes"]))
			$field["input"]["extra_attributes"] = $this->parseExtraAttributes($field["input"]["extra_attributes"], $input_data, $idx);
		
		$html = isset($field["input"]["previous_html"]) ? $this->parseSettingsValue($field["input"]["previous_html"], $input_data, $idx) : ""; //do not add the available values here, bc the availabel values is only for the input field
		
		if (isset($field["input"]["type"]))
			switch($field["input"]["type"]) {
				case "text":
				case "password":
				case "file":
				case "search":
				case "url":
				case "email":
				case "tel":
				case "number":
				case "range":
				case "date":
				case "month":
				case "week":
				case "time":
				case "datetime":
				case "datetime-local":
				case "color":
				case "hidden":
				case "button":
				case "submit":
				case "button_img": 
					$html .= $this->createFieldInput($field, $input_data, $idx); 
					break;
				case "checkbox":
				case "radio":
					$html .= $this->createFieldRadioOrCheckbox($field, $input_data, $idx);
					break;
				case "select": $html .= $this->createFieldSelect($field, $input_data, $idx); break;
				case "textarea": $html .= $this->createFieldTextarea($field, $input_data, $idx); break;
				case "link": $html .= $this->createFieldLink($field, $input_data, $idx); break;
				case "image": $html .= $this->createFieldImage($field, $input_data, $idx); break;
				case "label": 
				case "h1": 
				case "h2": 
				case "h3": 
				case "h4": 
				case "h5": 
					$html .= $this->createFieldLabel($field, $input_data, $idx); 
					break;
			}
		
		$html .= isset($field["input"]["next_html"]) ? $this->parseSettingsValue($field["input"]["next_html"], $input_data, $idx) : ""; //do not add the available values here, bc the availabel values is only for the input field
		
		return $html;
	}
	
	private function createFieldInput($field, $input_data = false, $idx = false) {
		$input_field = isset($field["input"]) ? $field["input"] : null;
		$field_type = isset($input_field["type"]) ? $input_field["type"] : null;
		$field_type = $field_type == "button_img" ? "image" : $field_type;
		$available_values = isset($input_field["available_values"]) ? $input_field["available_values"] : null;
		$input_field_value = isset($input_field["value"]) ? $input_field["value"] : null;
		
		
		//echo "label:".$input_field["name"]."\nvalue:".$input_field["value"]."\ninput_data:".print_r($input_data, 1)."idx:$idx\navailable_values:".print_r($input_field["available_values"], 1)."<br>";
		$html = '<input type="' . $field_type . '" class="' . $this->getSettingsClass($input_field, $input_data, $idx) . '" value="' . $this->parseSettingsAttributeValue($input_field_value, $input_data, $idx, $available_values) . '"';
		
		$attrs = array("extra_attributes", "name", "title", "confirmation", "place_holder");
		
		if ($field_type == "image")
			$attrs[] = "src";
		
		if ($field_type != "hidden") {
			$attrs[] = "confirmation_message";
			$attrs[] = "allow_null";
			$attrs[] = "allow_javascript";
			$attrs[] = "validation_label";
			$attrs[] = "validation_message";
			$attrs[] = "validation_type";
			$attrs[] = "validation_regex";
			$attrs[] = "validation_func";
			$attrs[] = "min_length";
			$attrs[] = "max_length";
			$attrs[] = "min_value";
			$attrs[] = "max_value";
			$attrs[] = "min_words";
			$attrs[] = "max_words";
		}
		
		$html .= $this->getFieldAttributes($input_field, $attrs, $input_data, $idx);
		
		$html .= ' />';
		
		return $html;
	}
	
	/*
	<div class="form-group">
		<label class="control-label">XXX</label>
		<!-- starts here the possible outputs of createFieldRadioOrCheckbox:
		<!-- If $option array has only 1 item and no option's label -->
		
			<input type="checkbox"  class="some class specified bu the user" ... /> <!-- shows directly the input with user class -->
			
		<!-- OR if $option array has multiple items -->
		
			<div class="some class specified bu the user">
				<label>Yes: </label>
				<input type="checkbox" ... /> <!-- To add a class to the output, do it inside of the each option: other_attributes -->
			</div>
			<div class="some class specified bu the user">
				<input type="checkbox" ... /> <!-- if there is no option's label -->
			</div>
			
		<!-- ends here the output of createFieldRadioOrCheckbox.
	</div>
	*/
	private function createFieldRadioOrCheckbox($field, $input_data = false, $idx = false) {
		$input_field = isset($field["input"]) ? $field["input"] : null;
		
		$class = $this->getSettingsClass($input_field, $input_data, $idx);
		
		$attrs = array("extra_attributes", "name", "title", "allow_null", "validation_label", "validation_message");
		$extra_html = $this->getFieldAttributes($input_field, $attrs, $input_data, $idx);
		
		$options = isset($input_field["options"]) ? $this->parseOptions($input_field["options"], $input_data, $idx) : null;
		
		if(empty($options))
			$options = array(array("value" => 1));
		
		$html = '';
		
		if(is_array($options)) {
			$field_type = isset($input_field["type"]) ? $input_field["type"] : null;
			$available_values = isset($input_field["available_values"]) ? $input_field["available_values"] : null;
			$input_field_value = isset($input_field["value"]) ? $input_field["value"] : null;
			$selected_value = $this->parseSettingsValue($input_field_value, $input_data, $idx, $available_values);
			
			if (!empty($this->settings["ptl"])) {
				if (is_array($selected_value)) 
					$selected_value = '<ptl:var:selected_value ' . str_replace(">", "&gt;", var_export($selected_value, true)) . '/>';
				else if (strpos($selected_value, "<ptl:echo ") !== false)
					$selected_value = str_replace("<ptl:echo ", "<ptl:var:selected_value ", $selected_value);
				else {
					$selected_value = strpos($selected_value, '"') !== false || strpos($selected_value, "'") !== false || is_numeric($selected_value) ? $selected_value : '"' . addcslashes($selected_value, '\\"') . '"';
					$selected_value = "<ptl:var:selected_value " . $selected_value . ">";
				}
			
				$html .= $selected_value;
			}
			
			$t = count($options);
			foreach ($options as $opt) {
				$opt_label = null;
				if (isset($opt["label"]))
					$opt_label = $this->parseSettingsValue($opt["label"], $input_data, $idx);
				
				$opt_extra_html = $extra_html;
				
				if ($t > 1 || $opt_label)
					$html .= '
				<div class="' . $class . '">
					' . ($opt_label ? '<label>' . $opt_label . '</label>' : '');
				else if ($class)
					$opt_extra_html = strpos($opt_extra_html, 'class="') !== false ? $this->strReplace('class="', 'class="' . $class . ' ', $opt_extra_html) : ' class="' . $class . '" ' . $opt_extra_html;
				
				$html .= '<input type="' . $field_type . '"' . $opt_extra_html;
				
				$opt_value = "";
				if (isset($opt["value"])) {
					$opt_value = !empty($this->settings["ptl"]) ? $opt["value"] : $this->parseSettingsValue($opt["value"], $input_data, $idx);
					$html .= ' value="' . $this->parseSettingsAttributeValue($opt["value"], $input_data, $idx) . '"';
				}
				else if (isset($opt_label))
					$opt_value = !empty($this->settings["ptl"]) ? $opt["label"] : $opt_label;
				
				if (!empty($opt["other_attributes"]))
					$html .= ' ' . $this->parseSettingsValue($opt["other_attributes"], $input_data, $idx);
				
				//Add checked if selected
				if (!empty($this->settings["ptl"])) {
					if (strpos($opt_value, "<ptl:echo ") !== false)
						$opt_value = str_replace("<ptl:echo ", "<ptl:var:opt_value ", $opt_value);
					else {
						$opt_value = strpos($opt_value, '"') !== false || strpos($opt_value, "'") !== false || is_numeric($opt_value) ? $opt_value : '"' . addcslashes($opt_value, '\\"') . '"';
						$opt_value = "<ptl:var:opt_value " . $opt_value . ">";
					}
					
					$html .= $opt_value . '<ptl:if (is_array($selected_value) && in_array($opt_value, $selected_value)) || (!is_array($selected_value) && $opt_value == $selected_value)><ptl:echo checked/></ptl:if>';
				}
				else if (is_array($selected_value) && in_array($opt_value, $selected_value))
					$html .= ' checked';
				else if ($opt_value == $selected_value)
					$html .= ' checked';
				
				$html .= '/>';
				
				if ($t > 1 || $opt_label)
					$html .= '</div>';
			}
		}
	
		return $html;
	}
	
	private function createFieldSelect($field, $input_data = false, $idx = false) {
		$input_field = isset($field["input"]) ? $field["input"] : null;
		
		$html = '<select class="' . $this->getSettingsClass($input_field, $input_data, $idx) . '"';
	
		$attrs = array("extra_attributes", "name", "title", "allow_null", "validation_label", "validation_message");
		$html .= $this->getFieldAttributes($input_field, $attrs, $input_data, $idx);
		
		$html .= '>';
		
		$available_values = isset($input_field["available_values"]) ? $input_field["available_values"] : null;
		$input_field_value = isset($input_field["value"]) ? $input_field["value"] : null;
		$selected_value = $this->parseSettingsValue($input_field_value, $input_data, $idx, $available_values);
		$selected_value_exists = false;
		
		if (!empty($this->settings["ptl"])) {
			if (is_array($selected_value))
				$selected_value = '<ptl:var:selected_value ' . str_replace(">", "&gt;", var_export($selected_value, true)) . '/>';
			else if (strpos($selected_value, "<ptl:echo ") !== false)
				$selected_value = str_replace("<ptl:echo ", "<ptl:var:selected_value ", $selected_value);
			else {
				$selected_value = strpos($selected_value, '"') !== false || strpos($selected_value, "'") !== false || is_numeric($selected_value) ? $selected_value : '"' . addcslashes($selected_value, '\\"') . '"';
				$selected_value = "<ptl:var:selected_value " . $selected_value . ">";
			}
			
			$html .= $selected_value;
			$html .= "<ptl:var:selected_value_exists false/>";
		}
		
		$options = isset($input_field["options"]) ? $this->parseOptions($input_field["options"], $input_data, $idx) : null;
		
		if(is_array($options))
			foreach ($options as $opt) {
				$opt_label = null;
				if (isset($opt["label"]))
					$opt_label = $this->parseSettingsValue($opt["label"], $input_data, $idx);
				
				$html .= '<option';
			
				$opt_value = "";
				if (isset($opt["value"])) {
					$opt_value = $this->parseSettingsValue($opt["value"], $input_data, $idx);
					$html .= ' value="' . $this->parseSettingsAttributeValue($opt["value"], $input_data, $idx) . '"';
				}
				else if (isset($opt_label))
					$opt_value = $opt_label;
				
				if (!empty($opt["other_attributes"]))
					$html .= ' ' . $this->parseSettingsValue($opt["other_attributes"], $input_data, $idx);
				
				if (!empty($this->settings["ptl"])) {
					if (strpos($opt_value, "<ptl:echo ") !== false)
						$opt_value = str_replace("<ptl:echo ", "<ptl:var:opt_value ", $opt_value);
					else {
						$opt_value = strpos($opt_value, '"') !== false || strpos($opt_value, "'") !== false || is_numeric($opt_value) ? $opt_value : '"' . addcslashes($opt_value, '\\"') . '"';
						$opt_value = "<ptl:var:opt_value " . $opt_value . ">";
					}
					
					$html .= $opt_value . '<ptl:if (is_array($selected_value) && in_array($opt_value, $selected_value)) || (!is_array($selected_value) && $opt_value == $selected_value)> <ptl:echo selected/><ptl:var:selected_value_exists true/></ptl:if>';
				}
				else if (is_array($selected_value) && in_array($opt_value, $selected_value)) {
					$html .= ' selected';
					$selected_value_exists = true;
				}
				else if ($opt_value == $selected_value) {
					$html .= ' selected';
					$selected_value_exists = true;
				}
				
				$html .= '>' . $opt_label . '</option>';
			}
		
		if (!empty($this->settings["ptl"])) //prepare unexistent selected_value option
			$html .= '<ptl:if is_string($selected_value) && strlen($selected_value) && !$selected_value_exists><option class="option-non-existent" value="<ptl:echo $selected_value/>" selected><ptl:echo $selected_value/></option></ptl:if>'; 
		else if (is_string($selected_value) && strlen($selected_value) && !$selected_value_exists) //prepare unexistent selected_value option
			$html .= '<option class="option-non-existent" value="' . $selected_value . '" selected>' . $selected_value . '</option>';
		
		$html .= '</select>';
		
		return $html;
	}
	
	private function createFieldTextarea($field, $input_data = false, $idx = false) {
		$input_field = isset($field["input"]) ? $field["input"] : null;
		
		$html = '<textarea class="' . $this->getSettingsClass($input_field, $input_data, $idx) . '"';
	
		$attrs = array("extra_attributes", "name", "title", "allow_null", "allow_javascript", "validation_label", "validation_message", "validation_type", "validation_regex", "validation_func", "min_length", "max_length", "min_value", "max_value", "min_words", "max_words", "place_holder");
		$html .= $this->getFieldAttributes($input_field, $attrs, $input_data, $idx);
		
		$available_values = isset($input_field["available_values"]) ? $input_field["available_values"] : null;
		$input_field_value = isset($input_field["value"]) ? $input_field["value"] : null;
		$value = $this->parseSettingsValue($input_field_value, $input_data, $idx, $available_values);
		//avoids to close the </textarea> by mistake
		$html .= '>' . $this->strReplace('</textarea', '&lt/textarea', $value) . '</textarea>';
	
		return $html;
	}
	
	private function createFieldLabel($field, $input_data = false, $idx = false) {
		$input_field = isset($field["input"]) ? $field["input"] : null;
		
		$field_type = isset($input_field["type"]) ? $input_field["type"] : null;
		$html_tag = $field_type == "h1" || $field_type == "h2" || $field_type == "h3" || $field_type == "h4" || $field_type == "h5" ? $field_type : "span";
		
		$html = '<' . $html_tag . ' class="' . $this->getSettingsClass($input_field, $input_data, $idx) . '"';
		
		$attrs = array("extra_attributes", "title");
		$html .= $this->getFieldAttributes($input_field, $attrs, $input_data, $idx);
		
		$available_values = isset($input_field["available_values"]) ? $input_field["available_values"] : null;
		$input_field_value = isset($input_field["value"]) ? $input_field["value"] : null;
		$html .= '>' . $this->parseSettingsValue($input_field_value, $input_data, $idx, $available_values) . '</' . $html_tag . '>';
		
		return $html;
	}
	
	private function createFieldLink($field, $input_data = false, $idx = false) {
		$input_field = isset($field["input"]) ? $field["input"] : null;
		
		$html = '<a class="' . $this->getSettingsClass($input_field, $input_data, $idx) . '"';
		
		$attrs = array("extra_attributes", "href", "title", "target");
		$html .= $this->getFieldAttributes($input_field, $attrs, $input_data, $idx);
		
		$available_values = isset($input_field["available_values"]) ? $input_field["available_values"] : null;
		$input_field_value = isset($input_field["value"]) ? $input_field["value"] : null;
		$html .= '>' . $this->parseSettingsValue($input_field_value, $input_data, $idx, $available_values) . '</a>';
		
		return $html;
	}
	
	private function createFieldImage($field, $input_data = false, $idx = false) {
		$input_field = isset($field["input"]) ? $field["input"] : null;
		
		$html = '<img class="' . $this->getSettingsClass($input_field, $input_data, $idx) . '"';
		
		if (!empty($input_field["src"]))
			$html .= ' src="' . $this->parseSettingsAttributeValue($input_field["src"], $input_data, $idx) . '"';
		else if (!empty($input_field["value"])) {
			$available_values = isset($input_field["available_values"]) ? $input_field["available_values"] : null;
			$html .= ' src="' . $this->parseSettingsAttributeValue($input_field["value"], $input_data, $idx, $available_values) . '"';
		}
		
		$attrs = array("extra_attributes", "title");
		$html .= $this->getFieldAttributes($input_field, $attrs, $input_data, $idx);
		
		$html .= ' />';
		
		return $html;
	}
	
	/* UTILS */
	private function getSettingsClass(&$field, $input_data = false, $idx = false, $attrs = false) {
		$class = "";
		
		if (is_array($attrs))
			foreach ($attrs as $attr)
				if (isset($field[$attr]) && strlen($field[$attr]))
					$class .= $this->parseSettingsAttributeValue($field[$attr], $input_data, $idx, false, " ") . ' ';
		
		$class .= !empty($field["class"]) ? $this->parseSettingsAttributeValue($field["class"], $input_data, $idx, false, " ") : "";
		
		if(isset($field["extra_attributes"]) && is_array($field["extra_attributes"]))
			foreach ($field["extra_attributes"] as $k => $f)
				if (isset($f["name"]) && strtolower($this->parseSettingsValue($f["name"], $input_data, $idx)) == "class") {
					$class .= ($class ? ' ' : '') . (isset($f["value"]) ? $this->parseSettingsAttributeValue($f["value"], $input_data, $idx, false, " ") : "");
					unset($field["extra_attributes"][$k]);
				}
		
		return $class;
	}
	
	private function getFieldAttributes($field, $attrs, $input_data = false, $idx = false) {
		$code = "";
		$field_type = isset($field["type"]) ? $field["type"] : null;
		
		if (is_array($attrs))
			foreach ($attrs as $attr)
				if (isset($field[$attr]))
					switch ($attr) {
						case "extra_attributes":
							if ($field["extra_attributes"]) {
								if(is_array($field["extra_attributes"])) {
									foreach ($field["extra_attributes"] as $f)
										if (!empty($f["name"])) {
											$extra_attributes_value = isset($f["value"]) ? $f["value"] : null;
											
											$code .= ' ' . $this->parseSettingsValue($f["name"], $input_data, $idx) . '="' . (strtolower($f["name"]) == "class" ? $this->parseSettingsAttributeValue($extra_attributes_value, $input_data, $idx, false, " ") : $this->parseSettingsAttributeValue($extra_attributes_value, $input_data, $idx)) . '"';
										}
								}
								else
									$code .= ' ' . $field["extra_attributes"];
							}
							break;
						case "href":
							if ($field["href"]) {
								$href = $this->parseSettingsAttributeValue($field["href"], $input_data, $idx);
								$code .= ' href="' . $this->strReplace(' ', '%20', $href) . '"';
							}
							break;
						case "allow_null":
							//Do not add strlen($field["allow_null"]) in this if bc, if the allow_null exists but is empty, it means that empty values are not allowed!
							//if (strlen($field["allow_null"]) || is_bool($field["allow_null"]))
							$code .= ' data-allow-null="' . ($field["allow_null"] ? $field["allow_null"] : 0) . '"';
							break;
						case "allow_javascript":
							//only add the allow-javascript if is == 1, otherwise do not add it to the html for security reasons.
							if ($field["allow_javascript"] && ($field["allow_javascript"] == "1" || strtolower($field["allow_javascript"]) == "true"))
								$code .= ' data-allow-javascript="1"';
							
							break;
						case "validation_regex":
							$code .= $field["validation_regex"] ? ' data-validation-regex="' . $field["validation_regex"] . '"' : "";
							break;
						case "validation_func":
							$code .= $field["validation_func"] ? ' data-validation-func="' . $field["validation_func"] . '"' : "";
							break;
						case "validation_label":
						case "validation_message":
						case "validation_type":
						case "confirmation":
						case "confirmation_message":
							$code .= $field[$attr] ? ' data-' . str_replace("_", "-", $attr) . '="' . $this->parseSettingsAttributeValue($field[$attr], $input_data, $idx) . '"' : "";
							break;
						case "min_words":
						case "max_words":
							$code .= is_numeric($field[$attr]) ? ' data-' . str_replace("_", "-", $attr) . '="' . $field[$attr] . '"' : '';
							break;
						case "min_length":
							$code .= is_numeric($field["min_length"]) ? ' minLength="' . $field["min_length"] . '"' : '';
							break;
						case "max_length":
							$code .= is_numeric($field["max_length"]) ? ' maxLength="' . $field["max_length"] . '"' : '';
							break;
						case "min_value": 
							$is_date = $field_type == "date" || $field_type == "datetime" || $field_type == "datetime-local";
							$code .= ($is_date ? $field["min_value"] : is_numeric($field["min_value"])) ? ' min="' . $field["min_value"] . '"' : ''; //Note that the min value can be numeric or a date.
							break;
						case "max_value":
							$is_date = $field_type == "date" || $field_type == "datetime" || $field_type == "datetime-local";
							$code .= ($is_date ? $field["max_value"] : is_numeric($field["max_value"])) ? ' max="' . $field["max_value"] . '"' : ''; //Note that the min value can be numeric or a date.
							break;
						case "place_holder":
							$code .= $field["place_holder"] ? ' placeHolder="' . $this->parseSettingsAttributeValue($field["place_holder"], $input_data, $idx) . '"' : '';
							break;
						default:
							if (strlen($field[$attr]))
								$code .= ' ' . $attr . '="' . (strtolower($attr) == "class" ? $this->parseSettingsAttributeValue($field[$attr], $input_data, $idx, false, " ") : $this->parseSettingsAttributeValue($field[$attr], $input_data, $idx)) . '"';
					}
		
		return $code;
	}
	
	private function parseOptions($options, $input_data = false, $idx = false) {
		if ($options) {
			if (is_string($options)) { //options could be an item inside of the input_data, something like #other_table_options#
				//must execute/eval the variable if exists, so ptl must be disabled!
				$ptl_bkp = isset($this->settings["ptl"]) ? $this->settings["ptl"] : null;
				$this->settings["ptl"] = false;
				
				$opts = $input_data ? $this->parseNewInputData($options, $input_data, $idx) : null; //first gives priority of the $input_data in case there is a table and the options string real exists in the table items data...
				if (!is_array($opts) && $this->original_input_data)
					$opts = $this->parseNewInputData($options, $this->original_input_data, $idx);
				
				$options = $opts;
				
				$this->settings["ptl"] = $ptl_bkp;
			}
			
			if (is_array($options)) { //check if options is an associative array and if it is, change it according with settings from form task
				//2020-02-10: we cannot use the is_associative_array bc the $options can have a mixed of correct values and others associative values that should be converted to the right format.
				//$is_associative_array = array_keys($options) !== range(0, count($options) - 1);
				
				$opts = array();
				foreach ($options as $k => $v) {
					if (!is_array($v)) {
						$opts[] = array(
							"value" => $k,
							"label" => $v
						);
					}
					else if ($v) { //if array and if exists. Discard empty arrays
						if (!isset($v["value"]) && !isset($v["label"]) && !isset($v["other_attributes"])) {
							$opts[] = array(
								"value" => $k,
								"label" => $v
							);
						}
						else //is already a correct array
							$opts[] = $v;
					}
				}
				
				$options = $opts;
			}
		}
		
		return $options;
	}
	
	private function parseExtraAttributes($extra_attributes, $input_data = false, $idx = false) {
		if ($extra_attributes) {
			if (is_string($extra_attributes)) { //extra_attributes could be an item inside of the input_data, something like #other_table_extra_attributes#
				//must execute/eval the variable if exists, so ptl must be disabled!
				$ptl_bkp = isset($this->settings["ptl"]) ? $this->settings["ptl"] : null;
				$this->settings["ptl"] = false;
				
				$eas = $input_data ? $this->parseNewInputData($extra_attributes, $input_data, $idx) : null; //first gives priority of the $input_data in case there is a table and the extra_attributes string real exists in the table items data...
				if (!is_array($eas) && $this->original_input_data)
					$eas = $this->parseNewInputData($extra_attributes, $this->original_input_data, $idx);
				
				$extra_attributes = $eas;
				
				$this->settings["ptl"] = $ptl_bkp;
			}
			
			if (is_array($extra_attributes)) { //check if extra_attributes is an associative array and if it is, change it according with settings from form task
				//2020-02-10: we cannot use the is_associative_array bc the $extra_attributes can have a mixed of correct values and others associative values that should be converted to the right format.
				//$is_associative_array = array_keys($extra_attributes) !== range(0, count($extra_attributes) - 1);
				
				$eas = array();
				foreach ($extra_attributes as $k => $v) {
					if (!is_array($v)) {
						$eas[] = array(
							"name" => $k,
							"value" => $v
						);
					}
					else if ($v) { //if array and if exists. Discard empty arrays
						if (!isset($v["name"]) && !isset($v["value"])) {
							$eas[] = array(
								"name" => $k,
								"value" => $v
							);
						}
						else //is already a correct array
							$eas[] = $v;
					}
				}
				
				$extra_attributes = $eas;
			}
		}
		
		return $extra_attributes;
	}
	
	private function parseAvailableValues($available_values, $input_data = false, $idx = false) {
		if ($available_values) {
			if (is_string($available_values)) { //available_values could be an item inside of the input_data, something like #other_table_available_values#
				//must execute/eval the variable if exists, so ptl must be disabled!
				$ptl_bkp = isset($this->settings["ptl"]) ? $this->settings["ptl"] : null;
				$this->settings["ptl"] = false;
				
				$avs = $input_data ? $this->parseNewInputData($available_values, $input_data, $idx) : null; //first gives priority of the $input_data in case there is a table and the available_values string real exists in the table items data...
				if (!is_array($avs) && $this->original_input_data)
					$avs = $this->parseNewInputData($available_values, $this->original_input_data, $idx);
				
				$available_values = $avs;
				
				$this->settings["ptl"] = $ptl_bkp;
			}
			
			if (is_array($available_values)) { //Preparing available_values according with settings from form task
				$keys = array_keys($available_values);
				$first_key = isset($keys[0]) ? $keys[0] : null;
				$has_old_structure = isset($available_values[$first_key]["old_value"]) || isset($available_values[$first_key]["new_value"]);
				
				if ($has_old_structure) {
					$avs = array();
					foreach ($available_values as $av) {
						$old_value = isset($av["old_value"]) ? $av["old_value"] : null;
						$avs[$old_value] = isset($av["new_value"]) ? $av["new_value"] : null;
					}
					
					$available_values = $avs;
				}
			}
		}
		
		return $available_values;
	}
	
	private function strReplaceForPTL($search, $replacement, $string) {
		$offset = 0;
		$length = strlen($string);
		$str = '';
		
		do {
			preg_match("/<(php|ptl|\?):([^ ]+) ([^>]*)>/iu", $string, $matches, PREG_OFFSET_CAPTURE, $offset); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
			
			if (!empty($matches[0])) {
				$v = substr($matches[3][0], -1) == "/" ? substr($matches[3][0], 0, -1) : $matches[3][0];// substr($matches[3][0], 0, -1) is to remove the last char /, correspondent to />
				
				$str .= str_replace($search, $replacement, substr($string, $offset, $matches[0][1] - $offset)) . '<' . $matches[1][0] . ':' . $matches[2][0] . ' ';
				if ($matches[2][0] == "echo")
					$str .= 'str_replace(\'' . addcslashes($search, "'") . '\', \'' . addcslashes($replacement, "'") . '\', (' . $v . '))';
				else
					$str .= $v;
				
				$str .= (substr($str, -1) != " " ? " " : "") . '/>'; 
				$offset = $matches[0][1] + strlen($matches[0][0]);
			}
		}
		while ($matches && $offset < $length);
		
		$str .= str_replace($search, $replacement, substr($string, $offset, $length));
		
		//echo "str:$str\n";
		return $str;
	}
	
	private function strReplace($search, $replacement, $string) {
		return !empty($this->settings["ptl"]) ? $this->strReplaceForPTL($search, $replacement, $string) : str_replace($search, $replacement, $string);
	}
	
	private function parseSettingsAttributeValue($value, $input_data = false, $idx = false, $available_values = false, $delimiter = ",") {
		$value = $this->parseSettingsValue($value, $input_data, $idx, $available_values);
		
		//convert value array to a string: This is useful for the "class" and "value" attributes. Basically this is a replication of the jquery.val method that sets the "value" attribute when we pass an array as value.
		//this is very useful when we try to show multiple values in an input field. Example: Show the tags from an article in  an input field, where the tags comes in an array format. Note that the select field, already allows the value to be an array, but all the other fields, don't allow. So we need to do this convertion automatically, but only if not PTL.
		if (empty($this->settings["ptl"]) && is_array($value) && $delimiter)
			$value = implode($delimiter, $value);
		
		return $this->strReplace('"', '&quot;', $value);
	}
	
	private function parseSettingsValue($value, $input_data = false, $idx = false, $available_values = false) {
		$orig_value = $value;
		$value = $this->getParsedValue($value, $input_data, $idx);
		
		//in case of a field be a "html select with multiple options", the $value[0] can be an array with multiple options. In this case we only need to return the $value.
		if (is_array($value))
			return $value;
		else { //if (isset($value) && strlen($value)) { //JP 2021-01-15: if there is no value I may want to add a default value through $available_values, setting the $available_values to array("" => "some default value") or array(null => "some default value")
			if ($available_values && is_array($available_values)) {
				//echo "<br><br>$value:".print_r($available_values, 1).print_r($input_data, 1)."<br>";
				
				if (!empty($this->settings["ptl"])) {
					$rand = rand(0, 1000);
					$value = '
					<ptl:var:av_exists_' . $rand . ' false/>
					<ptl:var:var_aux_' . $rand . ' ' . str_replace(">", "&gt;", $value) . '/>
					<ptl:var:avs_' . $rand . ' ' . str_replace(">", "&gt;", var_export($available_values, true)) . '/>
					<ptl:if is_array($avs_' . $rand . ') />
						<ptl:foreach $avs_' . $rand . ' k v>
							<ptl:if $k == $var_aux_' . $rand . '>
								<ptl:echo $v/>
								<ptl:var:av_exists_' . $rand . ' true/>
								<ptl:break/>
							</ptl:if>
						</ptl:foreach>
					</ptl:if>
					<ptl:if !$av_exists_' . $rand . '>
						<ptl:echo $var_aux_' . $rand . '/>
					</ptl:if>';
				}
				else if (!empty($this->settings["parse_values"]) && isset($available_values[$value]))
					$value = $available_values[$value];
			}
			else if (!empty($this->settings["ptl"]) && $orig_value != $value)
				$value = '<ptl:echo ' . str_replace(">", "&gt;", $value) . '/>';
		}
		
		return $value;
	}
	
	//any change here should be replicated in the PTLFieldUtilObj.js::parseNewInputData and MyWidgetResourceLib.js.HashTagHandler::parseNewInputData methods too
	private function parseNewInputData($value, $input_data = false, $idx = false) {
		if (is_array($value) || is_object($value))
			return $value;
		
		if ($value && ($input_data || !empty($this->settings["ptl"]))) {
			$value = trim($value);
			
			//be sure that $value is something like: #foo#. "#bar##foo#" will not be allowed, bc it doesn't make sense here!
			if (substr($value, 0, 1) == "#" && substr($value, -1, 1) == "#" && strpos(substr($value, 1, -1), "#") === false) {
				$results = $this->getParsedValue($value, $input_data, $idx, true);
				$value = isset($results[0]) ? $results[0] : null;
			}
			
			return $value;
		}
		
		return $input_data; //return $input_data in case there isn't value or input_data.
	}
	
	//any change here should be replicated in the PTLFieldUtilObj.js::getParsedValue and MyWidgetResourceLib.js.HashTagHandler::parseNewInputData methods too
	private function getParsedValue($value, $input_data = false, $idx = false, $result_in_array = false) {
		$results = array();
		$is_ptl = !empty($this->settings["ptl"]);
		
		if ($value && strpos($value, "#") !== false && ($is_ptl || !empty($this->settings["parse_values"]))) {
			$regex = HashTagParameter::HTML_HASH_TAG_PARAMETER_FULL_REGEX;
			
			if (!$is_ptl && (!isset($input_data) || $input_data === false))
				$results[] = preg_replace($regex, "", $value);
			else {
				preg_match_all($regex, $value, $matches, PREG_OFFSET_CAPTURE);//PREG_PATTERN_ORDER
				//print_r($matches);
				
				if (empty($matches[1]))
					$results[] = $value;
				else {
					$offset = 0;
					$t = count($matches[1]);
					
					$input_data_var_name = !empty($this->settings["ptl"]["input_data_var_name"]) ? $this->settings["ptl"]["input_data_var_name"] : $this->default_input_data_var_name;
					$idx_var_name = !empty($this->settings["ptl"]["idx_var_name"]) ? $this->settings["ptl"]["idx_var_name"] : $this->default_idx_var_name;
					
					for ($i = 0; $i < $t; $i++) {
						$m = $matches[1][$i][0];
						$replacement = "";
						
						//echo "m($value):$m<br>";
						if (strpos($m, "[") !== false || strpos($m, "]") !== false) { //if value == #[0]name# or #[$idx - 1][name]#, returns $input[0]["name"] or $input[$idx - 1]["name"]
							preg_match_all("/([^\[\]]+)/u", trim($m), $sub_matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
							$sub_matches = isset($sub_matches[1]) ? $sub_matches[1] : null;
							
							if ($sub_matches) {
								try {
									$t2 = count($sub_matches);
									//echo "1:";print_r($sub_matches);
									$idx_replacemente = $idx ? (is_numeric($idx) ? $idx : '"' . $idx . '"') : 0;
									
									for ($j = 0; $j < $t2; $j++) {
										$sml = strtolower($sub_matches[$j]);
										
										if ($sml == 'idx' || $sml == '$idx')
											$sub_matches[$j] = $is_ptl ? '$' . $idx_var_name : $idx_replacemente;
										else if ($sml == '\\$idx')
											$sub_matches[$j] = $is_ptl ? '\\$' . $idx_var_name : $idx_replacemente;
										else if (preg_match("/(^|[^a-z\_])idx[^a-z\_]/i", $sml) && !preg_match("/[a-z\_]/i", $sml)) //fix the cases like: #[$idx - 1]# where there is not alphabethic characters.
											$sub_matches[$j] = preg_replace("/(^|[^a-z\_])\\?\$?idx[^a-z\_]/i", $is_ptl ? '\\$' . $idx_var_name : $idx_replacemente, $sub_matches[$j]);
										else if (preg_match("/\$idx[^a-z\_]/i", $sml) && preg_match("/[a-z\_]/i", $sml)) { //fix the cases like: #[attribute_name_$idx]# where there are alphabethic characters.
											$aux = $is_ptl ? '\\$' . $idx_var_name : $idx_replacemente;
											$sub_matches[$j] = '"' . preg_replace("/\\?\$idx[^a-z\_]/i", '" . ' . $aux . ' . "', $sub_matches[$j]) . '"';
											$sub_matches[$j] = str_replace(array('"" . ', ' . ""'), "", $sub_matches[$j]);
										}
										else if ($j == 0 && preg_match(HashTagParameter::HTML_SUPER_GLOBAL_VAR_NAME_FULL_REGEX, $sub_matches[$j])) //if global var, simply continue. Do not add quotes if is global var
											continue;
										else if (!is_numeric($sml) && strpos($sml, "'") === false && strpos($sml, '"') === false) { //avoid php errors because one of the keys is a RESERVED PHP CODE string.
											//$sml_type = PHPUICodeExpressionHandler::getValueType($sml, array("non_set_type" => "string", "empty_string_type" => "string"));
											//$sub_matches[$j] = PHPUICodeExpressionHandler::getArgumentCode($sub_matches[$j], $sml_type);
											$sub_matches[$j] = $is_ptl ? $sub_matches[$j] : '"' . $sub_matches[$j] . '"';
										}
									}
									//echo "<pre>2:";echo $idx;print_r($sub_matches);echo "</pre>";
									//var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20));die();
									
									//if global var
									if (preg_match(HashTagParameter::HTML_SUPER_GLOBAL_VAR_NAME_FULL_REGEX, $sub_matches[0])) {
										$found_global_var_name = strtoupper($sub_matches[0]);
										array_shift($sub_matches);
										
										if (count($sub_matches)) {
											if ($is_ptl)
												$replacement = '$' . $found_global_var_name . '[' . str_replace('$idx', '$' . $idx_var_name, implode("][", $sub_matches)) . ']';
											else
												eval('$replacement = isset($' . $found_global_var_name . '[' . implode("][", $sub_matches) . ']) ? $' . $found_global_var_name . '[' . implode("][", $sub_matches) . '] : null;');
											
											//error_log("\n\nIS GLOBAL {$found_global_var_name}[" . implode("][", $sub_matches) . "]:".print_r($replacement,1)."\n\n", 3, "/var/www/html/livingroop/default/tmp/phpframework.log");
										}
										else {
											if ($is_ptl)
												$replacement = '$' . $found_global_var_name;
											else
												eval('$replacement = isset($' . $found_global_var_name . ') ? $' . $found_global_var_name . ' : null;');
										}
									}
									else { //if value inside of $input_data var
										if ($is_ptl)
											$replacement = '$' . $input_data_var_name . '[' . str_replace('$idx', '$' . $idx_var_name, implode("][", $sub_matches)) . ']';
										else
											eval('$replacement = isset($input_data[' . implode("][", $sub_matches) . ']) ? $input_data[' . implode("][", $sub_matches) . '] : null;');
										//echo "<pre>3: \$input_data[" . implode("][", $sub_matches) . "]: ";print_r($replacement);echo "</pre><br>";
									}
								}
								catch (Exception $e) {
									$replacement = "#ERROR REPLACING '$m'#";
								}
							}
						}
						else if ($is_ptl && $m == '$' . $input_data_var_name) //#$input#, returns $input - Returns it-self. Note that $input_data_var_name only exists if PTL is active
							$replacement = '$' . $input_data_var_name;
						else if ($is_ptl && $m == '@$' . $input_data_var_name) //#@$input#, returns $input - Returns it-self. Note that $input_data_var_name only exists if PTL is active
							$replacement = '@$' . $input_data_var_name;
						else if ($m == '$' . $this->default_input_data_var_name) //#$input#, returns $input - Returns it-self.
							$replacement = $is_ptl ? '$' . $this->default_input_data_var_name : $input_data;
						else if ($m == '@$' . $this->default_input_data_var_name) //#@$input#, returns $input - Returns it-self.
							$replacement = $is_ptl ? '@$' . $this->default_input_data_var_name : $input_data;
						else if ($m == '$input' || $m == '$input_data') { //$this->default_input_data_var_name or $input_data_var_name should have this already covered, otherwise something is wrong with the above code.
							echo "MAJOR ERROR in getParsedValue method in HTMLFormHandler.php and PTLFieldsUtilObj.js. Is missing here something... I should re-check the code of this method.";
							//die();
						}
						else if ($m == 'idx' || $m == '$idx') //#idx#, returns $idx
							$replacement = $is_ptl ? '$' . $idx_var_name : $idx;//replace by the correspondent key
						else if ($m == '\\$idx') //#\\$idx#, returns $idx
							$replacement = $is_ptl ? '\\$' . $idx_var_name : $idx;
						else if (preg_match(HashTagParameter::HTML_SUPER_GLOBAL_VAR_NAME_FULL_REGEX, $m)) { //if is global var
							$found_global_var_name = $m;
							
							try {
								eval('$replacement = $is_ptl ? \'$\' . $found_global_var_name : $' . $found_global_var_name . ';');
							}
							catch (Exception $e) {
								$replacement = "#ERROR REPLACING '$m'#";
							}
						}
						else //if $value == #name#, returns $input["name"]
							$replacement = $is_ptl ? '$' . $input_data_var_name . '[' . $m . ']' : (isset($input_data[$m]) ? $input_data[$m] : null);
						
						$aux = substr($value, $offset, $matches[0][$i][1] - $offset);
						
						if ($is_ptl && strlen($aux))
							$results[] = '"' . addcslashes($aux, '"') . '"';
						else if (strlen($aux))
							$results[] = $aux;
						
						$results[] = $replacement; //in case the $replacemente be an array, the array is safetly save in the $results variable.
						$offset = $matches[0][$i][1] + strlen($matches[0][$i][0]);
					}
					
					$aux = substr($value, $offset);
					
					if ($is_ptl && strlen($aux))
						$results[] = '"' . addcslashes($aux, '"') . '"';
					else if (strlen($aux))
						$results[] = $aux;
				}
			}
		}
		else
			$results[] = $value;
		
		//$result_in_array is used to save the correct types of objects inside of the $results varialble, otherwise if any replacemente is an array (like Array X), when we concatenate with a string, this array (the Array X) will be converted to a string and lose his items...
		if ($result_in_array)
			return $results;
		else if (count($results) == 1 && is_array($results[0])) //in case of a field be a "html select with multiple options", the $results[0] can be an array with multiple options. In this case we must return the array it self, but only if the $value contains only '#something#', which makes the count($results) == 1.
			return $results[0];
		else
			return implode($is_ptl ? " " : "", $results);
	}
	
	/*
	private function oLDParseSettingsValue($value, $input_data = false, $idx = false, $available_values = false) {
		$orig_value = $value;
		
		if ($value && strpos($value, "#") !== false) {
			if (!$this->settings["ptl"] && (!isset($input_data) || $input_data === false))
				$value = preg_replace("/#([^#]+)#/u", "", $value);
			else {
				preg_match_all("/#([^#]+)#/u", $value, $matches, PREG_OFFSET_CAPTURE);//PREG_PATTERN_ORDER //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
				//print_r($matches);
				
				if ($matches[1]) {
					$v = "";
					$offset = 0;
					$t = count($matches[1]);
					
					$idx_var_name = $this->settings["ptl"]["idx_var_name"];
					$input_data_var_name = $this->settings["ptl"]["input_data_var_name"];
					
					for ($i = 0; $i < $t; $i++) {
						$m = $matches[1][$i][0];
						$replacement = "";
						
						//echo "m($value):$m<br>";
						if (strpos($m, "[") !== false) {//#[0]name# or #[$idx - 1][name]#
							preg_match_all("/([^\[\]]+)/u", trim($m), $sub_matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
							$sub_matches = $sub_matches[1];
					
							if ($sub_matches) {
								try {
									$t2 = count($sub_matches);
									for ($j = 0; $j < $t2; $j++) {
										if (strtolower($sub_matches[$j]) == "idx")
											$sub_matches[$j] = $this->settings["ptl"] ? '$' . $idx_var_name : ($idx ? $idx : 0);
										else if (strpos($sub_matches[$j], "'") === false && strpos($sub_matches[$j], '"') === false) //avoid php errors because one of the keys is a RESERVED PHP CODE string.
											$sub_matches[$j] = $this->settings["ptl"] ? $sub_matches[$j] : '"' . $sub_matches[$j] . '"';
									}
									
									if ($this->settings["ptl"])
										$replacement = '$' . $input_data_var_name . '[' . implode("][", str_replace('$idx', '$' . $idx_var_name, $sub_matches)) . ']';
									else
										eval('$replacement = $input_data[' . implode("][", $sub_matches) . '];');
								}
								catch (Exception $e) {
									$replacement = "#ERROR REPLACING '$m'#";
								}
							}
						}
						else if ($m == "\$input") //#$input#
							$replacement = $this->settings["ptl"] ? '$' . $input_data_var_name : $input_data;
						else if ($m == "\$idx") //#$idx#
							$replacement = $this->settings["ptl"] ? '$' . $input_data_var_name . '[$' . $idx_var_name . ']' : $input_data[$idx];
						else if ($m == "idx") 
							$replacement = $this->settings["ptl"] ? '$' . $idx_var_name : $idx;//replace by the correspondent key
						else if (isset($input_data[$m]) || $this->settings["ptl"]) //#name#
							$replacement = $this->settings["ptl"] ? '$' . $input_data_var_name . '[' . $m . ']' : $input_data[$m];
						
						if ($this->settings["ptl"]) {
							$aux = substr($value, $offset, $matches[0][$i][1] - $offset);
							$v .= ($v ? " " : "") . ($aux ? '"' . addcslashes($aux, '"') . '" ' : "") . $replacement;
						}
						else
							$v .= substr($value, $offset, $matches[0][$i][1] - $offset) . $replacement;
						
						$offset = $matches[0][$i][1] + strlen($matches[0][$i][0]);
					}
					
					if ($this->settings["ptl"]) {
						$aux = substr($value, $offset);
						$v .= ($v ? " " : "") . ($aux ? '"' . addcslashes($aux, '"') . '"' : "");
					}
					else
						$v .= substr($value, $offset);
					
					$value = $v;
				}
			}
		}
		
		if (isset($value) && strlen($value)) {
			if ($available_values && is_array($available_values)) {
				if ($this->settings["ptl"]) {
					$rand = rand(0, 1000);
					$value = '
					<ptl:var:av_exists_' . $rand . ' false/>
					<ptl:var:var_aux_' . $rand . ' ' . str_replace(">", "&gt;", $value) . '/>
					<ptl:var:avs_' . $rand . ' ' . str_replace(">", "&gt;", var_export($available_values, true)) . '/>
					<ptl:foreach $avs_' . $rand . ' k v>
						<ptl:if $k == $var_aux_' . $rand . '>
							<ptl:echo $v/>
							<ptl:var:av_exists_' . $rand . ' true/>
							<ptl:break/>
						</ptl:if>
					</ptl:foreach>
					<ptl:if !$av_exists_' . $rand . '>
						<ptl:echo $var_aux_' . $rand . '/>
					</ptl:if>';
				}
				else if (isset($available_values[$value]))
					$value = $available_values[$value];
			}
			else if ($this->settings["ptl"] && $orig_value != $value)
				$value = '<ptl:echo ' . str_replace(">", "&gt;", $value) . '/>';
		}
		
		return $value;
	}
	
	private function oLDParseNewInputData($value, $input_data = false, $idx = false) {
		if (is_array($value) || is_object($value))
			return $value;
		
		if ($value && ($input_data || $this->settings["ptl"])) {
			$value = trim($value);
			
			if (substr($value, 0, 1) == "#" && substr($value, -1, 1) == "#") {
				$m = substr($value, 1, -1);
				$idx_var_name = $this->settings["ptl"]["idx_var_name"];
				$input_data_var_name = $this->settings["ptl"]["input_data_var_name"];
				
				if (strpos($m, "[") !== false) {//if value == #[0]name# or #[$idx - 1][name]#, returns $input_data[0]["name"] or $input_data[$idx - 1]["name"]
					preg_match_all("/([^\[\]]+)/u", trim($m), $sub_matches, PREG_PATTERN_ORDER); //'/u' means with accents and ç too. '/u' converts unicode to accents chars.
					$sub_matches = $sub_matches[1];
				
					if ($sub_matches) {
						try {
							$t2 = count($sub_matches);
							for ($j = 0; $j < $t2; $j++) {
								if (strtolower($sub_matches[$j]) == "idx")
									$sub_matches[$j] = $this->settings["ptl"] ? '$' . $idx_var_name : ($idx ? $idx : 0);
								else if (strpos($sub_matches[$j], "'") === false && strpos($sub_matches[$j], '"') === false) //avoid php errors because one of the keys is a RESERVED PHP CODE string.
									$sub_matches[$j] = $this->settings["ptl"] ? $sub_matches[$j] : '"' . $sub_matches[$j] . '"';
							}
							
							if ($this->settings["ptl"])
								return '$' . $input_data_var_name . '[' . implode("][", str_replace('$idx', '$' . $idx_var_name, $sub_matches)) . ']';
								
							eval('$input_data = $input_data[' . implode("][", $sub_matches) . '];');
							return $input_data;
						}
						catch (Exception $e) {
							return null;
						}
					}
				}
				else if ($m == '$input') //#$input#, returns $input - Returns it-self.
					return $this->settings["ptl"] ? '$' . $input_data_var_name : $input_data;
				else if ($m == '$idx') //#$idx#, returns $input[$idx]
					return $this->settings["ptl"] ? '$' . $input_data_var_name . '[$' . $idx_var_name . ']' : $input_data[$idx]; //Not sure about this: $input[' . $idx . ']
				else if (isset($input_data[$m]) || $this->settings["ptl"]) //if $value == #name#, returns $input["name"]
					return $this->settings["ptl"] ? '$' . $input_data_var_name . '[' . $m . ']' : $input_data[$m];
				
				return null;//returns null in case #...# doesn't exists inside of $input_data or if there isn't #...#
			}
			
			return $value;//return string value
		}
		
		return $input_data;//return $input_data in case there isn't value or input_data.
	}
	*/
}
?>
