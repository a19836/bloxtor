<?php
//This is very important bc it protects against xss attacks
if (!empty($sanitize_html_in_post_request) && !empty($_POST)) {
	include_once get_lib("org.phpframework.util.web.html.XssSanitizer");
	
	$_POST = XssSanitizer::sanitizeVariable($_POST);
}
?>
