<?php
include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");

class SimpleHtmlFormHandler {
	
	public function getListHtml($settings) {
		//Preparing Settings
		$title = isset($settings["title"]) ? $settings["title"] : null;
		$class = isset($settings["class"]) ? $settings["class"] : null;
		$title_class = !empty($settings["title_class"]) ? $settings["title_class"] : "title";
		$data = isset($settings["data"]) ? $settings["data"] : null;
		$empty_list_message = !empty($settings["empty_list_message"]) ? $settings["empty_list_message"] : "There are no available items...";
		
		$form_settings = self::getListSettings($settings);
		
		//Preparing HTML
		$html = '<div class="' . $class . '">
				<div class="' . $title_class . '">' . $title . '</div>';
		
		$html .= HtmlFormHandler::createHtmlForm($form_settings, $data);
		
		$html .= empty($data) ? '<div class="empty_table">' . $empty_list_message . '</div>' : '';
		$html .= '</div>';
		
		return $html;
	}
	
	public function getFormHtml($settings) {
		//Preparing Settings
		$title = isset($settings["title"]) ? $settings["title"] : null;
		$class = isset($settings["class"]) ? $settings["class"] : null;
		$title_class = !empty($settings["title_class"]) ? $settings["title_class"] : "title";
		$status_message_class = !empty($settings["status_message_class"]) ? $settings["status_message_class"] : "status_message";
		$error_message_class = !empty($settings["error_message_class"]) ? $settings["error_message_class"] : "error_message";
		$data = isset($settings["data"]) ? $settings["data"] : null;
		$status_message = isset($settings["status_message"]) ? $settings["status_message"] : null;
		$error_message = isset($settings["error_message"]) ? $settings["error_message"] : null;
		
		$form_settings = self::getFormSettings($settings);
		
		//Preparing HTML
		$html = '<div class="' . $class . '">
				<div class="' . $title_class . '">' . $title . '</div>';
				
		if ($status_message) {
			$html .= '<div class="' . $status_message_class . '">' . $status_message . '</div>';
		}
		
		if ($error_message) {
			$html .= '<div class="' . $error_message_class . '">' . $error_message . '</div>';
		}
		
		if (empty($_POST["delete"]) || $error_message)
			$html .= HtmlFormHandler::createHtmlForm($form_settings, $data);
		
		$html .= '</div>';
		
		return $html;
	}
	
	public static function getListSettings($settings) {
		//Preparing Settings
		$edit_url = isset($settings["edit_url"]) ? $settings["edit_url"] : null;
		$delete_url = isset($settings["delete_url"]) ? $settings["delete_url"] : null;
		$other_urls = isset($settings["other_urls"]) ? $settings["other_urls"] : null;
		$fields = isset($settings["fields"]) ? $settings["fields"] : null;
		$data = isset($settings["data"]) ? $settings["data"] : null;
		$total = isset($settings["total"]) ? $settings["total"] : null;
		$current_page = isset($settings["current_page"]) ? $settings["current_page"] : null;
		$rows_per_page = !empty($settings["rows_per_page"]) ? $settings["rows_per_page"] : 50;
		
		//Preparing HTML
		$html = '';
		
		$elements = array();
		
		if (is_array($fields)) {
			foreach ($fields as $field_name => $field) {
				$field_name = is_string($field_name) && !is_numeric($field_name) ? $field_name : null;
				
				if (!$field_name && is_string($field) && !is_numeric($field)) {
					$field_name = $field;
					unset($field);
				}
				
				$elements[] = self::getFieldElementSettings($field, $field_name, "list_column");
			}
		}
		
		if ($edit_url) {
			$elements[] = array(
				"field" => array(
					"class" => "list_column edit_action",
					"input" => array(
						"type" => "link",
						"value" => "",
						"href" => $edit_url,
						"extra_attributes" => array(
							0 => array(
								"name" => "class",
								"value" => "glyphicon glyphicon-pencil icon edit"
							),
							1 => array(
								"name" => "title",
								"value" => "Edit"
							)
						),
					)
				)
			);
		}
		
		if ($delete_url) {
			$elements[] = array(
				"field" => array(
					"class" => "list_column delete_action",
					"input" => array(
						"type" => "link",
						"value" => "",
						"href" => "#",
						"extra_attributes" => array(
							0 => array(
								"name" => "onClick",
								"value" => "deleteItem(this, '$delete_url')"
							),
							1 => array(
								"name" => "class",
								"value" => "glyphicon glyphicon-remove icon delete"
							),
							2 => array(
								"name" => "title",
								"value" => "Remove"
							)
						),
					)
				)
			);
		}
		
		if ($other_urls) {
			foreach ($other_urls as $other_url) {
				$url = $other_url;
				$class = "";
				$label = "";
				$title = "";
				
				if (is_array($other_url)) {
					$url = isset($other_url["url"]) ? $other_url["url"] : null;
					$class = isset($other_url["class"]) ? $other_url["class"] : null;
					$label = isset($other_url["label"]) ? $other_url["label"] : null;
					$title = isset($other_url["title"]) ? $other_url["title"] : null;
				}
				
				$elements[] = array(
					"field" => array(
						"class" => "list_column other_action $class",
						"input" => array(
							"type" => "link",
							"value" => $label,
							"href" => $url,
							"extra_attributes" => array(
								0 => array(
									"name" => "class",
									"value" => "icon"
								),
								1 => array(
									"name" => "title",
									"value" => $title
								)
							),
						)
					)
				);
			}
		}
		
		$form_settings = array(
			"with_form" => 0,
			"form_containers" => array(
				0 => array(
					"container" => array(
						"class" => "list_items",
						"previous_html" => isset($settings["previous_html"]) ? $settings["previous_html"] : "",
						"next_html" => isset($settings["next_html"]) ? $settings["next_html"] : "",
						"elements" => array(
							0 => array(
								"container" => array(
									"class" => "top_pagination",
									"elements" => array(
										0 => array(
											"pagination" => array(
												"pagination_template" => "design1",
												"rows_per_page" => $rows_per_page,
												"page_number" => $current_page,
												"max_num_of_shown_pages" => "10",
												"total_rows" => $total,
												"page_attr_name" => "current_page"
											)
										)
									)
								)
							),
							1 => array(
								"container" => array(
									"class" => "list_container",
									"previous_html" => "",
									"next_html" => "",
									"elements" => array(
										0 => array(
											"table" => array(
												"table_class" => "list_table " . (isset($settings["table_class"]) ? $settings["table_class"] : ""),
												"rows_class" => isset($settings["rows_class"]) ? $settings["rows_class"] : null,
												"elements" => $elements
											)
										)
									)
								)
							),
							2 => array(
								"container" => array(
									"class" => "bottom_pagination",
									"elements" => array(
										0 => array(
											"pagination" => array(
												"pagination_template" => "design1",
								 				"rows_per_page" => $rows_per_page,
												"page_number" => $current_page,
												"max_num_of_shown_pages" => "10",
												"total_rows" => $total,
												"page_attr_name" => "current_page"
											)
										)
									)
								)
							),
						)
					)
				),
			)
		);
		
		return $form_settings;
	}
	
	public static function getFormSettings($settings) {
		$fields = isset($settings["fields"]) ? $settings["fields"] : null;
		$data = isset($settings["data"]) ? $settings["data"] : null;
		$default_data = isset($settings["default_data"]) ? $settings["default_data"] : null;
		$status_message = isset($settings["status_message"]) ? $settings["status_message"] : null;
		$error_message = isset($settings["error_message"]) ? $settings["error_message"] : null;
		
		$html = '';
		
		$buttons = array();
		$elements = array();
		
		if ($data) {
			$buttons[] = array(
				"field" => array(
					"class" => "submit_button submit_button_save",
					"input" => array(
						"type" => "submit",
						"name" => "save",
						"value" => "Save",
					)
				)
			);
			
			$buttons[] = array(
				"field" => array(
					"class" => "submit_button submit_button_delete",
					"input" => array(
						"type" => "submit",
						"name" => "delete",
						"value" => "Delete",
						"extra_attributes" => array(
							0 => array(
								"name" => "onClick",
								"value" => "return confirm('Do you wish to delete this item?');"
							),
						)
					)
				)
			);
		}	
		else {
			$buttons[] = array(
				"field" => array(
					"class" => "submit_button submit_button_add",
					"input" => array(
						"type" => "submit",
						"name" => "add",
						"value" => "Add",
					)
				)
			);
		}
		
		if (is_array($fields)) {
			foreach ($fields as $field_name => $field) {
				$field_name = is_string($field_name) && !is_numeric($field_name) ? $field_name : null;
				$element = self::getFieldElementSettings($field, $field_name, "form_field");
				
				if (!$data) {
					$default_value = is_array($default_data) && array_key_exists($field_name, $default_data) ? $default_data[$field_name] : "";
					
					if (!empty($element["container"]))
						$element["container"]["elements"][0]["field"]["input"]["value"] = $default_value;
					else
						$element["field"]["input"]["value"] = $default_value;
				}
				
				$elements[] = $element;
			}
		}
		
		$form_settings = array(
			"with_form" => isset($settings["with_form"]) ? $settings["with_form"] : 1,
			"form_id" => isset($settings["form_id"]) ? $settings["form_id"] : "",
			"form_method" => isset($settings["form_method"]) ? $settings["form_method"] : "post",
			"form_class" => isset($settings["form_class"]) ? $settings["form_class"] : "",
			"form_type" => isset($settings["form_type"]) ? $settings["form_type"] : "",
			"form_on_submit" => isset($settings["form_on_submit"]) ? $settings["form_on_submit"] : "",
			"form_action" => isset($settings["form_action"]) ? $settings["form_action"] : "",
			"form_containers" => array(
				0 => array(
					"container" => array(
						"class" => "form_fields",
						"previous_html" => isset($settings["previous_html"]) ? $settings["previous_html"] : "",
						"next_html" => isset($settings["next_html"]) ? $settings["next_html"] : "",
						"elements" => $elements
					)
				),
				1 => array(
					"container" => array(
						"class" => "buttons",
						"elements" => $buttons
					)
				),
			)
		);
		
		return $form_settings;
	}
	
	public static function getFieldElementSettings($field, $field_name, $field_default_class) {
		$type = $field;
		$class = null;
		$href = null;
		$previous_html = null;
		$next_html = null;
		$label = null;
		$input_field_name = $field_name;
		
		if (is_array($field)) {
			$type = isset($field["type"]) ? $field["type"] : null;
			$class = isset($field["class"]) ? $field["class"] : null;
			$href = isset($field["href"]) ? $field["href"] : null;
			$previous_html = isset($field["previous_html"]) ? $field["previous_html"] : null;
			$next_html = isset($field["next_html"]) ? $field["next_html"] : null;
			$label = isset($field["label"]) ? $field["label"] : null;
			
			if (!empty($field["name"])) {
				$input_field_name = $field["name"];
				
				//$field_name can be null or numeric. Do not delete this code, please.
				if (is_numeric($field_name) || empty($field_name))
					$field_name = $input_field_name;
				
				unset($field["name"]);
			}
			
			unset($field["type"]);
			unset($field["class"]);
			unset($field["href"]);
			unset($field["previous_html"]);
			unset($field["next_html"]);
			unset($field["label"]);
		}
		
		if (!$type) {
			$type = "label";
		}
		
		$is_form = strpos($field_default_class, "form_field") !== false;
		
		if ($field_name) {
			$input_value = $is_form ? "#$field_name#" : "#[\$idx][$field_name]#";
			$label = isset($label) ? $label : ucwords(strtolower(str_replace("_", " ", $field_name))) . ($is_form ? ": " : "");
		}
		
		$element = array(
			"field" => array(
				"label" => array(
					"value" => $label,
				),
				"input" => array(
					"type" => $type,
					"name" => $input_field_name,
					"value" => $input_value,
				)
			)
		);
		
		if (is_array($field)) {
			foreach ($field as $attr_name => $attr_value) {
				$element["field"]["input"][$attr_name] = $attr_value;
			}
		}
		
		if ($type == "checkbox" || $type == "radio") {
			$exists = false;
			
			if (!isset($element["field"]["input"]["extra_attributes"]) || !is_array($element["field"]["input"]["extra_attributes"]))
				$element["field"]["input"]["extra_attributes"] = array();
			
			foreach ($element["field"]["input"]["extra_attributes"] as $idx => $ea)
				if (is_array($ea) && $ea["name"] == "class") {
					$element["field"]["input"]["extra_attributes"][$idx]["value"] .= " checkbox";	
					$exists = true;
					break;
				}
			
			if (!$exists)
				$element["field"]["input"]["extra_attributes"][] = array("name" => "class", "value" => "checkbox");
		}
		
		if ($href || $previous_html || $next_html) {
			$element = array(
				"container" => array(
					"class" => "$field_default_class $field_name $class",
					"href" => $href,
					"elements" => array($element),
					"previous_html" => $previous_html,
					"next_html" => $next_html,
				)
			);
		}
		else {
			$element["field"]["class"] = "$field_default_class $field_name $class";
		}
		
		return $element;
	}
}
?>
