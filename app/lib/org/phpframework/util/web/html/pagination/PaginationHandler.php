<?php

class PaginationHandler {
	
	public $data;
	public $show_x_pages_at_once = 5;
	public $with_post = false; //2020-01-22: by default is GET method
	public $default_style = "design1";
	
	/**
	 * Pagination Constructor
	 */
	public function __construct($total_items, $max_items_page, $settings = array(), $page_attr_name = 'pg', $on_click_js_func = false) {
		$this->init($total_items, $max_items_page, $settings, $page_attr_name, $on_click_js_func);
	}
	
	/**
	 * init function - inits the parameters and the variables that will be used to generate the pagination HTML.
	 */
	private function init($total_items, $max_items_page, $settings, $page_attr_name = 'pg', $on_click_js_func = false) {
		$total_items = is_numeric($total_items) ? $total_items : 0; //it could be an empty string coming from the HtmlFormHandler if properly settings are not inited.
		$max_items_page = is_numeric($max_items_page) ? $max_items_page : 0; //it could be an empty string coming from the HtmlFormHandler if properly settings are not inited.
		$cur_page = isset($settings[ $page_attr_name ]) && is_numeric($settings[ $page_attr_name ]) ? $settings[ $page_attr_name ] : 1;
		
		$limit = isset($settings['limit']) ? $settings['limit'] : $max_items_page;
		if ($limit > $max_items_page) $limit = $max_items_page;
		if ($limit < 1) $limit = 1;
		
		$num_pages = ceil($total_items / $limit);
		
		//2020-01-22: Do not change the $cur_page, bc the there will be php code using $_GET[$page_attr_name] and if we change the $cur_page, the shown data will not appear correctly accordingly with the page number, this is, if $cur_page=1 or $cur_page>$num_pages the returned data is empty, so no PAGE should appear selected, which means we cannot change the $cur_page variable.
		//if ($cur_page > $num_pages) $cur_page = $num_pages;
		//if ($cur_page < 1) $cur_page = 1;
		
		$start = max($cur_page - 1, 0) * $limit;
		$end = min($start + $limit, $total_items);
		
		$this->data = array(    
			"total_items" => $total_items, 
			"max_items_page" => $max_items_page, 
			"cur_page" => $cur_page, 
			"start" => $start, 
			"end" => $end, 
			"limit" => $limit, 
			"num_pages" => $num_pages, 
			"page_attr_name" => $page_attr_name, 
			"on_click_js_func" => $on_click_js_func
		);
	}
	
	//note: $current_page cannot be 0. Must be bigger or equal than 1
	public static function getStartValue($current_page, $limit) {
		if ($current_page > 0 && $limit > 0) {
			$sv = ($current_page - 1) * $limit;
			return $sv >= 0 ? $sv : 0; //if $current_page=1 and $limit=1, $sv will be -1
		}
		return 0;
	}
	
	/**
	 * designWithStyle function - call the respective design function and draw the pagination HTML.
	 */
	public function designWithStyle($url = 1, $data = array()) {
		$with_post = isset($data["with_post"]) ? $data["with_post"] : $this->with_post;
		$style = isset($data["style"]) ? $data["style"] : $this->default_style;
		
		$fields = $this->getPaginationGetAndPostFields($url, $with_post);
		$data["url"] = isset($fields["url"]) ? $fields["url"] : null;
		$data["post"] = isset($fields["post"]) ? $fields["post"] : null;
		
		return $this->$style($data);
	}
	
	/**
	 * getPaginationGetAndPostFields function - gets the get and post fields.
	 */
	private function getPaginationGetAndPostFields($url, $with_post) {
		$new_url = "";
		$post_fields = "";
		
		if($url === 1) {
			$get_vars = $_GET;
			$page_attr_name = isset($this->data['page_attr_name']) ? $this->data['page_attr_name'] : null;
			
			if (isset($page_attr_name))
				unset($get_vars[$page_attr_name]);
			
			$new_url = "?" . http_build_query($get_vars);
		}
		else
			$new_url = strpos($url,"?") === false ? $url . "?" : $url;
		
		if($with_post)
			$post_fields = $this->getParsedPostFields($_POST);
		
		return array("url" => $new_url, "post" => $post_fields);
	}
	
	private function getParsedPostFields($arr, $prefix = "") {
		$html = "";
		
		if ($arr)
			foreach ($arr as $k => $v) {
				if (is_array($v))
					$html .= $this->getParsedPostFields($v, $prefix . $k . ($prefix ? "]" : "") . "[");
				else
					$html .= "<input type=\"hidden\" name=\"$prefix$k" . ($prefix ? "]" : "") . "\" value=\"$v\" />";
			}
		
		return $html;
	}
}
?>
