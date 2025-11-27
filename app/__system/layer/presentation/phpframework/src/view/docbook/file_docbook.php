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

include $EVC->getUtilPath("BreadCrumbsUIHandler");

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/docbook/file_docbook.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/docbook/file_docbook.js"></script>
';

$main_content = '
	<div class="top_bar">
		<header>
			<div class="title" title="' . $path . '">Docbook in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, null) . '</div>
		</header>
	</div>';
	
if ($file_exists) {
	$main_content .= '<div class="file_docbook with_top_bar_section">';
	
	//show file info
	if (!empty($is_docbook_allowed)) {
		if (!empty($classes_properties)) {
			foreach ($classes_properties as $class_name => $class) {
				$is_class = $class_name !== 0;
				$props = isset($class["props"]) ? $class["props"] : null;
				$methods = isset($class["methods"]) ? $class["methods"] : null;
				
				if ($is_class)
					$main_content .= '<div class="class">
						<label>' . $class_name . '</label>';
				
				if ($props) {
					$main_content .= '<ul class="props">
						<label>Properties</label>';
					
					foreach ($props as $prop_name => $prop) {
						$value = !empty($prop["value"]) ? (isset($prop["var_type"]) && $prop["var_type"] == "string" ? '"' . addcslashes($prop["value"], '"') . '"' : $prop["value"]) : "";
						
						$comments = "";
						if (!empty($prop["doc_comments"]) || !empty($prop["comments"])) {
							$comments = trim((!empty($prop["comments"]) ? implode("", $prop["comments"]) : "") . (!empty($prop["doc_comments"]) ? "\n" . implode("", $prop["doc_comments"]) : ""));
							$main_content .= '<li class="comments"><pre>' . $comments . '</pre></li>';
						}
						
						$str = (!empty($prop["type"]) && empty($prop["const"]) ? $prop["type"] . " " : "") . (!empty($prop["const"]) ? "const " : "") . (!empty($prop["static"]) ? "static " : "") . (empty($prop["const"]) && strlen($prop_name) && $prop_name[0] != '$' ? '$' : '') . $prop_name . ($value ? " = " . $value : "");
						
						$main_content .= '<li class="prop">' . $str . '</li>';
					}
					
					$main_content .= '</ul>';
				}
				
				if ($methods) {
					$main_content .= '<ul class="methods">
						<label>' . ($is_class ? 'Methods' : 'Functions') . '</label>';
					
					foreach ($methods as $method_name => $method) {
						$args = "";
						if (!empty($method["arguments"]))
							foreach ($method["arguments"] as $arg_var => $arg_value) 
								$args .= ($args ? ", " : "") . $arg_var . ($arg_value ? ' = ' . $arg_value : "");
						
						$comments = "";
						if (!empty($method["doc_comments"]) || !empty($method["comments"])) {
							$comments = trim((!empty($method["comments"]) ? implode("", $method["comments"]) : "") . (!empty($method["doc_comments"]) ? "\n" . implode("", $method["doc_comments"]) : ""));
							$main_content .= '<li class="comments"><pre>' . $comments . '</pre></li>';
						}
						
						$str = $method_name . " ( " . $args . " )";
						
						if ($is_class)
							$str = (!empty($method["abstract"]) ? "abstract " : "") . (!empty($method["type"]) ? $method["type"] . " " : "") . (!empty($method["static"]) ? "static " : "") . $str;
						
						$main_content .= '<li class="method">' . $str . '</li>';
					}
					
					$main_content .= '</ul>';
				}
				
				if ($is_class)
					$main_content .= '</div>';
			}
		}
		else
			$main_content .= '<div class="error">No data for file: "' . substr($file_path, strlen(APP_PATH)) . '"</div>';
	}
	else 
		$main_content .= '<div class="code"><textarea readonly>' . (isset($contents) ? htmlspecialchars($contents, ENT_NOQUOTES) : "") . '</textarea></div>';
		
	$main_content .= '</div>';
}
else
	$main_content .= '<div class="error">Error: File does not exists! File path: "' . substr($file_path, strlen(APP_PATH)) . '"</div>';
?>
