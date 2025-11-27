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

include_once get_lib("org.phpframework.util.web.html.pagination.PaginationHandler");

class PaginationLayout extends PaginationHandler {
	
	public static $PAGINATION_CURRENT_PAGE_TITLE = "Page #current_page# of #num_pages#";
	public static $PAGINATION_GOTO_PAGE_TITLE = "Page";
	public static $PAGINATION_GOTO_PREVIOUS_PAGE_TITLE = "Prev";
	public static $PAGINATION_GOTO_NEXT_PAGE_TITLE = "Next";
	public static $PAGINATION_GO_BUTTON_TITLE = "GO";
	
	/**
	 * design1 function - designs the pagination HTML.
	 */
	public function design1($data) {
		$html = "";
		
		$url = isset($data["url"]) ? $data["url"] : null;
		$post_fields = isset($data["post"]) ? $data["post"] : null;
		
		//$current_page_title = isset($data["pagination_current_page_title"]) ? $data["pagination_current_page_title"] : self::$PAGINATION_CURRENT_PAGE_TITLE;
		$goto_page_title = isset($data["pagination_goto_page_title"]) ? $data["pagination_goto_page_title"] : self::$PAGINATION_GOTO_PAGE_TITLE;
		$goto_previous_page_title = isset($data["pagination_goto_previous_page_title"]) ? $data["pagination_goto_previous_page_title"] : self::$PAGINATION_GOTO_PREVIOUS_PAGE_TITLE;
		$goto_next_page_title = isset($data["pagination_goto_next_page_title"]) ? $data["pagination_goto_next_page_title"] : self::$PAGINATION_GOTO_NEXT_PAGE_TITLE;
		$go_button_title = isset($data["pagination_go_button_title"]) ? $data["pagination_go_button_title"] : self::$PAGINATION_GO_BUTTON_TITLE;
		
		$num_pages = isset($this->data['num_pages']) ? $this->data['num_pages'] : null;
		$page_attr_name = isset($this->data['page_attr_name']) ? $this->data['page_attr_name'] : null;
		$cur_page = isset($this->data['cur_page']) ? $this->data['cur_page'] : null;
		
		if ($num_pages > 1) {       
			$form_id = "form_".md5(rand());
			
			if (!empty($data["with_css"]))
				$html .= $this->getDesign1Css();
			
			//Preparing form with pages
			$html .= '
			<!-----  Start of Pagination Code ----->
			<div class="paging">' . $this->getFormSubmitNewPageJavascriptFunction($form_id, $url, $post_fields);
			
			if ($num_pages >= $this->show_x_pages_at_once) {
				//$current_page_title = str_replace("#current_page#", $cur_page, $current_page_title);
				//$current_page_title = str_replace("#num_pages#", $num_pages, $current_page_title);
				
				$html .= '
				<div class="go_to_page">
					' . $goto_page_title . ' <select name="' . $page_attr_name . '" class="pgs_id">';
				
				for ($j = 1; $j <= $num_pages; $j++)       
					$html .= '<option' . ($j == $cur_page ? " selected" : "") . '>' . $j . '</option>';
				
				$html .= '</select>
					<button class="go_bt" type="button" value="' . $go_button_title . '" onclick="submitNewPageIn_' . $form_id . '( $(this).parent().children(\'select\').first().val(), \'number\', this );return false;">' . $go_button_title . '</button>
				</div>';
			}
			
			//Preparing start and end indexes' pages
			$half_x_pages_at_once = ceil($this->show_x_pages_at_once / 2);
			$block_number = ceil($cur_page / $half_x_pages_at_once);
			$start_page = ($block_number * $half_x_pages_at_once) - $half_x_pages_at_once + 1;
			$end_page = $start_page + $half_x_pages_at_once;
			
			$diff = $this->show_x_pages_at_once - ($end_page - $start_page + 1);
			if ($diff > 0) {
				$new_diff = $end_page + $diff < $num_pages ? $diff : $num_pages - $end_page;
			
				$start_page -= $diff - $new_diff > 0 ? $diff - $new_diff : 0;
				$end_page += $new_diff > 0 ? $new_diff : 0;
			}
			
			$start_page = $start_page < 1 ? 1 : $start_page;
			$end_page = $end_page > $num_pages ? $num_pages : $end_page;
			
			$html .= '<div class="page_numbers">';
			
			if ($cur_page > $half_x_pages_at_once) { 
				$z = $start_page - $half_x_pages_at_once;
				$z = $z < 1 ? 1 : $z;
				
				$html .= '<a class="prev" href="' . $url . '&' . $page_attr_name . '=' . $z . '" onClick="submitNewPageIn_' . $form_id . '(' . $z . ', \'prev\', this);return false;"> &lt; ' . $goto_previous_page_title . ' ' . $half_x_pages_at_once . '</a>';
			}
			
			for ($i = $start_page; $i <= $end_page; $i++) {
				if ($cur_page == $i) {
					$html .= ' <a class="selected" href="' . $url . '&' . $page_attr_name . '=' . $i. '" onClick="submitNewPageIn_' . $form_id . '(' . $i . ', \'number\', this);return false;"><b>' . $i . '</b></a> ';
				} 
				else { 
					$html .= ' <a class="unselected" href="' . $url . '&' . $page_attr_name . '=' . $i . '" onClick="submitNewPageIn_' . $form_id . '(' . $i . ', \'number\', this);return false;" style="color:#000;text-decoration:none;">' . $i . '</a> ';
				}
			}
			
			if ($end_page < $num_pages) {
				$z = $end_page + $half_x_pages_at_once;
				$z = $z > $num_pages ? $num_pages : $z;
				
				$html .= '<a class="next" href="' . $url . '&' . $page_attr_name . '=' . $z . '" onClick="submitNewPageIn_' . $form_id . '(' . $z . ', \'next\', this);return false;">' . $goto_next_page_title . ' ' . $half_x_pages_at_once . ' &gt;</a>';
			}
			
			$html .= '</div>
			</div>
			<!-----  End of Pagination Code ----->';
		}
		
		return $html;
	}
	
	public function getDesign1Css() {
		return '.paging {margin:0 auto; font-family:Arial, Helvetica, sans-serif; font-size:12px; text-align:center; color:#666; display:table;}
.paging .cur_page {border: solid 2px #FFFFFF; border-radius:.15rem; background-color:#F7F7F7; font-size:11px; font-family:Arial, Helvetica, sans-serif;}
.paging .go_to_page {margin:0 5px; display:inline-block;}
.paging .go_to_page form {font-family:Arial, Helvetica, sans-serif;}
.paging .go_to_page select {height:25px; display:inline-block; box-sizing:border-box; font-size:12px; font-weight:bold; font-family:Arial, Helvetica, sans-serif; color:inherit; border:1px solid #ccc; border-radius:.15rem; vertical-align:middle; background:transparent;}
.paging .go_to_page button {height:25px; display:inline-block; box-sizing:border-box; font-weight:bold; font-family:Arial, Helvetica, sans-serif; color:inherit; border:1px solid #ccc; border-radius:.15rem; vertical-align:middle; cursor:pointer;}
.paging .go_to_page button:hover {text-decoration:underline;}
.paging .go_to_page div {}
.paging .page_numbers {margin:0 5px; display:inline-block; clear:both;}
.paging .page_numbers a {height:25px; padding:2px 7px; box-sizing:border-box; display:inline-block; vertical-align:middle; border:1px solid #ccc; border-radius:.15rem; color:inherit;}
.paging .prev, .paging .next {color:inherit; text-decoration:none; font-weight:bold;}
.paging .selected {color:#333; text-decoration:none; background-color:#CCCCCC; cursor:default;}
.paging .unselected {color:#333; text-decoration:none;}
.paging .unselected:hover, .paging .prev:hover, .paging .next:hover {color:#000; text-decoration:underline !important;}';
	}
	
	/**
	 * bootstrap1 function - designs the pagination HTML for bootstrap styles.
	 */
	public function bootstrap1($data) {
		$html = "";
		
		$url = isset($data["url"]) ? $data["url"] : null;
		$post_fields = isset($data["post"]) ? $data["post"] : null;
		
		$goto_previous_page_title = isset($data["pagination_goto_previous_page_title"]) ? $data["pagination_goto_previous_page_title"] : self::$PAGINATION_GOTO_PREVIOUS_PAGE_TITLE;
		$goto_next_page_title = isset($data["pagination_goto_next_page_title"]) ? $data["pagination_goto_next_page_title"] : self::$PAGINATION_GOTO_NEXT_PAGE_TITLE;
		
		$num_pages = isset($this->data['num_pages']) ? $this->data['num_pages'] : null;
		$page_attr_name = isset($this->data['page_attr_name']) ? $this->data['page_attr_name'] : null;
		$cur_page = isset($this->data['cur_page']) ? $this->data['cur_page'] : null;
		
		if ($num_pages > 1) {       
			$form_id = "form_".md5(rand());
			
			if (!empty($data["with_css"]))
				$html .= $this->getBootstrap1Css();
			
			//Preparing form with pages
			$html .= '
			<!-----  Start of Pagination Code ----->
			<nav class="paging">' . $this->getFormSubmitNewPageJavascriptFunction($form_id, $url, $post_fields);
			
			//Preparing start and end indexes' pages
			$half_x_pages_at_once = ceil($this->show_x_pages_at_once / 2);
			$block_number = ceil($cur_page / $half_x_pages_at_once);
			$start_page = ($block_number * $half_x_pages_at_once) - $half_x_pages_at_once + 1;
			$end_page = $start_page + $half_x_pages_at_once;
			
			$diff = $this->show_x_pages_at_once - ($end_page - $start_page + 1);
			if ($diff > 0) {
				$new_diff = $end_page + $diff < $num_pages ? $diff : $num_pages - $end_page;
			
				$start_page -= $diff - $new_diff > 0 ? $diff - $new_diff : 0;
				$end_page += $new_diff > 0 ? $new_diff : 0;
			}
			
			$start_page = $start_page < 1 ? 1 : $start_page;
			$end_page = $end_page > $num_pages ? $num_pages : $end_page;
			
			$html .= '<ul class="pagination">';
			
			if ($cur_page > $half_x_pages_at_once) { 
				$z = $start_page - $half_x_pages_at_once;
				$z = $z < 1 ? 1 : $z;
				
				$html .= '<li class="page-item"><a class="page-link prev" href="' . $url . '&' . $page_attr_name . '=' . $z . '" onClick="submitNewPageIn_' . $form_id . '(' . $z . ', \'prev\', this);return false;"> &lt; ' . $goto_previous_page_title . ' ' . $half_x_pages_at_once . '</a></li>';
			}
			
			for ($i = $start_page; $i <= $end_page; $i++) {
				$html .= '<li class="page-item">';
				
				if ($cur_page == $i) {
					if ($num_pages >= $this->show_x_pages_at_once) {
						$html .= ' <select name="' . $page_attr_name . '" class="page-link pr-0 pgs_id" onChange="submitNewPageIn_' . $form_id . '( $(this).val(), \'number\', this );return false;" style="height:100%; height:calc(100% - 1px);">';
						
						for ($j = 1; $j <= $num_pages; $j++)       
							$html .= '<option' . ($j == $cur_page ? " selected" : "") . '>' . $j . '</option>';
						
						$html .= '</select>';
					}
					else
						$html .= ' <a class="page-link selected" href="' . $url . '&' . $page_attr_name . '=' . $i. '" onClick="submitNewPageIn_' . $form_id . '(' . $i . ', \'number\', this);return false;"><b>' . $i . '</b></a> ';
				}
				else 
					$html .= ' <a class="page-link unselected" href="' . $url . '&' . $page_attr_name . '=' . $i . '" onClick="submitNewPageIn_' . $form_id . '(' . $i . ', \'number\', this);return false;" style="color:#000;text-decoration:none;">' . $i . '</a> ';
				
				$html .= '</li>';
			}
			
			if ($end_page < $num_pages) {
				$z = $end_page + $half_x_pages_at_once;
				$z = $z > $num_pages ? $num_pages : $z;
				
				$html .= '<li class="page-item"><a class="page-link next" href="' . $url . '&' . $page_attr_name . '=' . $z . '" onClick="submitNewPageIn_' . $form_id . '(' . $z . ', \'next\', this);return false;">' . $goto_next_page_title . ' ' . $half_x_pages_at_once . ' &gt;</a></li>';
			}
			
			$html .= '</ul>
			</nav>
			<!-----  End of Pagination Code ----->';
		}
		
		return $html;
	}
	
	public function getBootstrap1Css() {
		return '.paging > .pagination {margin:0; display:block; text-align:center;}
.paging > .pagination .page-item {display:inline-block;}
.paging > .pagination .page-link {height:2.5em !important; position: relative; display: inline-block; padding: 0.5rem 0.75rem; margin-left: -1px; line-height: 1.25em; color: #007bff; background-color: #fff; border: 1px solid #dee2e6; white-space:nowrap; text-decoration: none; box-sizing: border-box; font-size: 1em; font-family:Verdana, Arial, sans-serif; vertical-align: middle; border-radius:0;}
.paging > .pagination select.page-link {padding-top:0.45rem; padding-right:0.3rem;}
.paging > .pagination .page-item:first-child .page-link {border-top-left-radius:.25rem; border-bottom-left-radius:.25rem;}
.paging > .pagination .page-item:last-child .page-link {border-top-right-radius:.25rem; border-bottom-right-radius:.25rem;}';
	}
	
	private function getFormSubmitNewPageJavascriptFunction($form_id, $url, $post_fields) {
		$page_attr_name = isset($this->data['page_attr_name']) ? $this->data['page_attr_name'] : null;
		
		$code = '<script>
		function submitNewPageIn_' . $form_id . '(page_num, type, elm) {
			var pg_status = true;
			var func = ' . (!empty($this->data['on_click_js_func']) ? $this->data['on_click_js_func'] : "null") . ';
			
			if (typeof func == "function")
				pg_status = func("' . $page_attr_name . '", page_num, type, "' . $form_id . '", elm);
			
			if (pg_status) {
				var url = \'' . addcslashes($url, "\\'") . '&' . $page_attr_name . '=\' + page_num;';
		
		if ($post_fields)
			$code .= '
				var oForm = document.createElement("FORM");
				oForm.setAttribute("name", "' . $form_id . '");
				oForm.setAttribute("method", "post");
				oForm.setAttribute("action", url);
				$(oForm).html(\'' . addcslashes($post_fields, "\\'") . '\');
				$("body").append(oForm);
				
				return oForm.submit();';
		else //2020-01-22: by default is get
			$code .= '
				document.location = url;';
		
		$code .= '
			}
			
			return false;
		}
		</script>';
		
		return $code;
	}
}
?>
