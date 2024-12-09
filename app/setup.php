<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="icon" href="data:;base64,=" />
	<style>
		body {width:100%; margin:0; font-family:verdana,courier,arial; font-size:14px; overflow:overlay;}
		
		.setup {width:100%; margin:0 auto;}
		.setup .title {text-align:center; width:100%; font-size:20px; color:#333; margin-top:20px;}
		.setup .title svg {margin-bottom:5px; width:200px; height:36; }
		.setup > ol {margin:0 20px; padding:0 0 0 5px; box-sizing:border-box;}
		.setup ul {padding-left:0px !important;}
		.setup li {margin-top:10px; margin-left:20px !important; word-wrap:break-word;}
		.setup ol {margin-left:20px !important;}
		.setup ol li {list-style:number !important; margin-top:20px;}
		.setup ul {margin-left:20px !important; margin-top:10px;}
		.setup ul li {list-style:square !important; margin-top:0px; font-style:italic;}
		.setup ul li.for_git_repo_creation {display:none;}
		.writeable {color:#009900; font-weight:bold;}
		.non_writeable {color:#CC0000; font-weight:bold;}
		.looks_ok {color:#009900; font-weight:bold;}
		.looks_non_ok {color:#ffaa00; font-weight:bold;}
		.continue , .continue a {font-weight:bold; font-style:italic;}
		.button { margin-top:50px; text-align:center;}
		.enjoy {width:100%; text-align:center; margin-top:50px; margin-bottom:20px;}
		.disable, .disable .writeable, .disable .continue {color:#999;}
		
		/* setup_already_executed */
		.setup_already_executed {
			padding: .75rem 1.25rem;
			margin: 2rem;
			position: relative;
			border: 1px solid transparent;
			border-radius: .25rem;
			text-align:center;
			color: #856404;
			background-color: #fff3cd;
			border-color: #ffeeba;
		}
		
		/* button */
		button {
			font-weight: 400;
			text-align: center;
			white-space: nowrap;
			vertical-align: middle;
			border: 1px solid transparent;
			padding: 10px 15px;
			letter-spacing: 0.2px;
			border-radius: 0.2rem;
			transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
			background-color: #4070FF;
			color: #fff;
			cursor:pointer;
		}
		button:hover {
			background-color: #4070ffdd;
			border-color: #4070ff;
		}
		button[disabled] {
			background-color:#999;
			border-color:#aaa;
			cursor:not-allowed;
		}
		button[disabled]:hover {
			background-color: #aaa;
			border-color:#bbb;
		}
		
		/* md file */
		.md_file {
			height:300px;
			max-height:80vh;
			margin:10px 0 0;
			padding:20px;
			resize:vertical;
			background:#ddd;
			border:1px solid #ccc;
			border-radius:5px;
			overflow:auto;
		}
		.md_file h2 {
			margin-top:40px;
		}
		.md_file h3 {
			margin-top:40px;
		}
		.md_file blockquote {
			font: 14px/22px normal helvetica, sans-serif;
			margin-top: 10px;
			margin-bottom: 10px;
			margin-left: 0px;
			padding-left: 15px;
			border-left: 3px solid #FC3C44;
		}
		.md_file code {
			width:100%;
			padding:5px;
			display:block;
			background:#eee;
			font-family:"Times new Roman";
			overflow:auto;
		}
		
		/* SCROLLBARS */
		::-webkit-scrollbar {
			width:10px;
			height:10px;
			background:transparent;
		}
		::-webkit-scrollbar-track {
			/*-webkit-border-radius:5px;
			border-radius:5px;
			-webkit-box-shadow:inset 0 0 6px rgba(0,0,0, 0);*/
			background:transparent;
		}
		::-webkit-scrollbar-thumb {
			background:#83889E;
			/*-webkit-box-shadow:inset 0 0 6px rgba(250,250,250,0.8);*/
			
			background-clip:padding-box;
			border:2px solid transparent;
			border-radius:9999px;
			/*-webkit-box-shadow:0 0px 1px var(--main-scrollbar-thumb-shadow-color);*/
		}
		::-webkit-scrollbar-thumb:window-inactive {
			/*background:rgba(0,0,0,0.35);*/
		}
	</style>
</head>
<body>
	<div class="setup">
		<h1 class="title">
			<svg viewBox="0 0 475 86" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M222.6 30.7H192C185.9 30.7 181 36 181 42.5V73.7C181 80.2 185.9 85.5 192 85.5H222.6C229.1 85.5 234.4 80.2 234.4 73.7V42.5C234.4 36 229.1 30.7 222.6 30.7ZM224.9 68C224.9 72.1 221.5 75.5 217.4 75.5H197.9C194 75.5 190.9 72.1 190.9 68V48.2C190.9 44.1 194 40.7 197.9 40.7H217.4C221.5 40.7 224.9 44.1 224.9 48.2V68Z" fill="#FC3C44"/>
				<path d="M404.3 30.7H373.7C367.6 30.7 362.7 36 362.7 42.5V73.7C362.7 80.2 367.6 85.5 373.7 85.5H404.3C410.8 85.5 416.1 80.2 416.1 73.7V42.5C416 36 410.8 30.7 404.3 30.7ZM406.5 68C406.5 72.1 403.1 75.5 399 75.5H379.5C375.6 75.5 372.5 72.1 372.5 68V48.2C372.5 44.1 375.6 40.7 379.5 40.7H399C403.1 40.7 406.5 44.1 406.5 48.2V68Z" fill="#2C2D34"/>
				<path d="M158.1 85.4C155.3 85.4 153.1 83.2 153.1 80.4V5C153.1 2.2 155.3 0 158.1 0C160.9 0 163.1 2.2 163.1 5V80.4C163.1 83.1 160.9 85.4 158.1 85.4Z" fill="#2C2D34"/>
				<path d="M284.5 58L303.4 39.1C305.4 37.1 305.4 34 303.4 32C301.4 30 298.3 30 296.3 32L277.5 51L258.6 32C256.6 30 253.5 30 251.5 32C249.5 34 249.5 37.1 251.5 39.1L270.4 58L251.5 76.9C249.5 78.9 249.5 82 251.5 84C252.5 85 253.8 85.5 255 85.5C256.2 85.5 257.6 85 258.5 84L277.4 65.1L296.4 84C297.4 85 298.7 85.5 299.9 85.5C301.1 85.5 302.5 85 303.4 84C305.4 82 305.4 78.9 303.4 76.9L284.5 58Z" fill="#2C2D34"/>
				<path d="M347.5 31.1H330.1V5.10001C330.1 2.30001 327.9 0.100006 325.1 0.100006C322.3 0.100006 320.1 2.30001 320.1 5.10001V80.5C320.1 83.3 322.3 85.5 325.1 85.5C327.9 85.5 330.1 83.3 330.1 80.5V41.1H347.5C350.3 41.1 352.5 38.9 352.5 36.1C352.5 33.3 350.3 31.1 347.5 31.1Z" fill="#2C2D34"/>
				<path d="M436.7 85.5C433.9 85.5 431.7 83.3 431.7 80.5V46.5C431.7 38.2 438.4 31.5 446.7 31.5H469.3C472.1 31.5 474.3 33.7 474.3 36.5C474.3 39.3 472.1 41.5 469.3 41.5H446.7C443.9 41.5 441.7 43.7 441.7 46.5V80.5C441.7 83.2 439.5 85.5 436.7 85.5Z" fill="#2C2D34"/>
				<path d="M35.1002 61.2C32.4002 61.2 30.2002 63.5 30.2002 66.4V80.2C30.2002 83.1 32.4002 85.4 35.1002 85.4H48.6002C51.5002 85.4 53.8002 83.1 53.8002 80.2V66.4C53.8002 63.5 51.5002 61.2 48.6002 61.2H35.1002Z" fill="#FC3C44"/>
				<path d="M35.1002 30.6C32.4002 30.6 30.2002 32.9 30.2002 35.8V49.6C30.2002 52.5 32.4002 54.8 35.1002 54.8H48.6002C51.5002 54.8 53.8002 52.5 53.8002 49.6V35.8C53.8002 32.9 51.5002 30.6 48.6002 30.6H35.1002V30.6Z" fill="#FC3C44"/>
				<path d="M5.10019 61.2C2.40019 61.2 0.200195 63.5 0.200195 66.4V80.2C0.200195 83.1 2.40019 85.4 5.10019 85.4H18.6002C21.5002 85.4 23.8002 83.1 23.8002 80.2V66.4C23.8002 63.5 21.5002 61.2 18.6002 61.2H5.10019Z" fill="#FC3C44"/>
				<path d="M5.10019 30.6C2.40019 30.6 0.200195 32.9 0.200195 35.8V49.6C0.200195 52.5 2.40019 54.8 5.10019 54.8H18.6002C21.5002 54.8 23.8002 52.5 23.8002 49.6V35.8C23.8002 32.9 21.5002 30.6 18.6002 30.6H5.10019V30.6Z" fill="#FC3C44"/>
				<path d="M5.10019 0C2.40019 0 0.200195 2.3 0.200195 5.2V19C0.200195 21.9 2.40019 24.2 5.10019 24.2H18.6002C21.5002 24.2 23.8002 21.9 23.8002 19V5.2C23.8002 2.3 21.5002 0 18.6002 0H5.10019Z" fill="#FC3C44"/>
				<path d="M125.4 30.6H114.7H108.3H93.8003V5C93.8003 2.2 91.6003 0 88.8003 0C86.0003 0 83.8003 2.2 83.8003 5V80.4C83.8003 83.2 86.0003 85.4 88.8003 85.4H108.3H114.7H125.4C131.9 85.4 137.2 80.1 137.2 73.6V42.4C137.1 35.9 131.9 30.6 125.4 30.6ZM127.6 67.9C127.6 72 124.2 75.4 120.1 75.4H114.6H108.2H93.7003V40.6H108.2H114.6H120.1C124.2 40.6 127.6 44 127.6 48.1V67.9Z" fill="#2C2D34"/>
			</svg>
			<div>SETUP</div>
		</h1>
<?php
function checkFilesPermission($files, $optional_files, $check_folder_sub_files, &$main_status) {
	$file_statuses = array();
	
	foreach ($files as $file) {
		$optional = in_array($file, $optional_files);
		$exists = file_exists($file);
		
		if ($exists || !$optional) {
			$is_writable = $exists ? is_writable($file) : false;
			$file_statuses[$file] = array($is_writable, null);
			
			if (!$is_writable)
				$main_status = false;
		}
	}
	
	foreach ($file_statuses as $file => $file_props) {
		$is_writable = $file_props[0];
		
		if ($is_writable) {
			$check_sub_files = in_array($file, $check_folder_sub_files);
			
			if ($check_sub_files) {
				$incorrect_sub_files = checkSubFilesPermission($file, $optional_files, $main_status);
				
				if (count($incorrect_sub_files) > 0)
					$file_statuses[$file][1] = $incorrect_sub_files;
			}
		}
	}
	
	$html = "<ul>";
	
	foreach ($file_statuses as $file => $file_props) {
		$is_writable = $file_props[0];
		$incorrect_sub_files = $file_props[1];
		$path = $exists && !empty(realpath($file)) ? realpath($file) : $file;
		
		$html .= "<li>" . $path . ": " . printWritableStatus($is_writable);
		
		if ($incorrect_sub_files) {
			$html .= "<ul>";
			
			foreach ($incorrect_sub_files as $sub_file)
				if (!in_array($sub_file, $files))
					$html .= "<li>" . $sub_file . ": " . printWritableStatus(false) . "</li>";
			
			$html .= "</ul>";
		}
		
		$html .= "</li>\n";
	}
	
	$html .= "</ul>";
	
	return $html;
}

function checkSubFilesPermission($file, $optional_files, &$main_status) {
	return array();//TODO remove this line
	$incorrect_files = array();
	
	if (is_dir($file)) {
		$file .= substr($file, -1) != "/" ? "/" : "";
		$sub_files = array_diff(scandir($file), array("..", ".", ".gitkeep", ".htaccess", ".htpasswd", ".git"));
		
		if ($sub_files) {
			foreach ($sub_files as $i => $sub_file) {
				$sub_file = $file . $sub_file;
				$optional = in_array($sub_file, $optional_files);
				
				if (!$optional) {
					$is_writable = is_writable($sub_file);
					
					if (!$is_writable) {
						$incorrect_files[] = $sub_file;
						$main_status = false;
					}
					else if (is_dir($sub_file)) {
						$incorrect_sub_files = checkSubFilesPermission($sub_file, $optional_files, $main_status);
						
						if (count($incorrect_sub_files) > 0)
							$incorrect_files = array_merge($incorrect_files, $incorrect_sub_files);
					}
				}
			}
		}
	}
	
	return $incorrect_files;
}

function printWritableStatus($status) {
	return $status ? '<span class="writeable">OK</span>' : '<span class="non_writeable">NON WRITEABLE</span>';
}

function printOptionalStatus($status) {
	return $status ? '<span class="looks_ok">LOOKS OK</span>' : '<span class="looks_non_ok">PLEASE CHECK</span>';
}

$dir_path = str_replace(DIRECTORY_SEPARATOR, "/", __DIR__) . "/";
$installation_dir = dirname($dir_path) . "/";
$main_status = true;

//prepare TMP_PATH - This code must be the exactly the same that in the app.php file.
$script_name = isset($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : null;
$local_installation_name = strstr($script_name, "/" . basename(__DIR__) . "/", true);
$document_root = (!empty($_SERVER["CONTEXT_DOCUMENT_ROOT"]) ? $_SERVER["CONTEXT_DOCUMENT_ROOT"] : (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] : null) ) . "/"; //Use CONTEXT_DOCUMENT_ROOT if exist, instead of DOCUMENT_ROOT, bc if a virtual host has an alias to this folder, the DOCUMENT_ROOT will be the folder of the virtual host and not this folder. Here is an example: Imagine that you have a Virtual host with a DOCUMENT_ROOT /var/www/html/livingroop/ and an Alias: /test/ pointing to /var/www/html/test/. Additionally this file (app.php) is in /var/www/html/test/. According with this requirements the DOCUMENT_ROOT is /var/www/html/livingroop/, but we would like to get /var/www/html/test/. So we must use the CONTEXT_DOCUMENT_ROOT to get the right document root.
$document_root = preg_replace("/[\/]+/", "/", $document_root);

//Settings the $tmp_path if the DOCUMENT_ROOT is based in specific domain and the DOCUMENT_ROOT folder contains the app/ and tmp/ folders. 
//This means, we can have multiple installations with independent $tmp_path, this is: /var/www/html/installation1/app/ /var/www/html/installation2/trunk/app/ /var/www/html/installation3/app/, etc...
if ($local_installation_name && is_dir($document_root . $local_installation_name . "/tmp/"))
	$tmp_path = $document_root . $local_installation_name . "/tmp/";
else if (is_dir($document_root . "/tmp/"))
	$tmp_path = $document_root . "/tmp/";
else //Settings $tmp_path with default system temp folder
	$tmp_path = (sys_get_temp_dir() ? sys_get_temp_dir() : "/tmp") . "/phpframework/";

$tmp_path = preg_replace("/\/\/+/", "/", $tmp_path);

//create TMP_PATH if not created already:
@mkdir($tmp_path, 0755, true);

$files = array(
	$tmp_path,
	$installation_dir . "files/",
	$installation_dir . "vendor/", //This must have write permission too bc of the hacking solution and bc the user can create sub-files too.
	$installation_dir . "vendor/dao/",
	$installation_dir . "vendor/codeworkfloweditor/",
	$installation_dir . "vendor/codeworkfloweditor/task/",
	$installation_dir . "vendor/layoutuieditor/",
	$installation_dir . "vendor/layoutuieditor/widget/",
	$installation_dir . "vendor/testunit/",
	
	$installation_dir . "other/authdb/",
	$installation_dir . "other/authdb/permission.tbl",
	$installation_dir . "other/authdb/user.tbl",
	$installation_dir . "other/authdb/user_type.tbl",
	$installation_dir . "other/authdb/user_type_permission.tbl",
	$installation_dir . "other/authdb/user_stats.tbl",
	$installation_dir . "other/authdb/user_user_type.tbl",
	$installation_dir . "other/authdb/login_control.tbl",
	$installation_dir . "other/authdb/layout_type.tbl",
	$installation_dir . "other/authdb/layout_type_permission.tbl",
	$installation_dir . "other/authdb/module_db_table_name.tbl",
	$installation_dir . "other/authdb/object_type.tbl",
	$installation_dir . "other/authdb/reserved_db_table_name.tbl",
	$installation_dir . "other/workflow/",
	
	$installation_dir . "app/config/",
	$installation_dir . "app/layer/", 
	$installation_dir . "app/layer/.htaccess",
	$installation_dir . "app/lib/vendor/", 
	
	$installation_dir . "app/__system/config/global_settings.php", 
	$installation_dir . "app/__system/config/global_variables.php", 
	
	$installation_dir . "app/__system/layer/presentation/phpframework/src/config/authentication.php", 
	$installation_dir . "app/__system/layer/presentation/phpframework/webroot/vendor/", 
	$installation_dir . "app/__system/layer/presentation/phpframework/webroot/__system/", 
	
	$installation_dir . "app/__system/layer/presentation/test/webroot/__system/", 
	
	$installation_dir . "app/__system/layer/presentation/common/webroot/cms/",
	$installation_dir . "app/__system/layer/presentation/common/webroot/__system/",
	$installation_dir . "app/__system/layer/presentation/common/src/module/",
	$installation_dir . "app/__system/layer/presentation/common/webroot/module/",
	$installation_dir . "app/__system/layer/presentation/common/webroot/vendor/", 
	
	//dependecies
	$installation_dir . "app/__system/layer/presentation/common/webroot/vendor/ckeditor/", 
	$installation_dir . "app/__system/layer/presentation/common/webroot/vendor/tinymce/", 
	$installation_dir . "app/lib/vendor/phpjavascriptpacker/", 
	$installation_dir . "app/lib/vendor/phpmailer/", 
	$installation_dir . "app/lib/vendor/xsssanitizer/", 
	
	//paths for the Licence hacking consequence - if client doesn't pay licence, we will delete these folders and so, they must have write permission. Check if all sub-folders have the same permission too.
	//$installation_dir . "app/",
	//$installation_dir . "app/lib/",
	//$installation_dir . "app/__system/",
);

//These files may not exist in the beginning
$optional_files = array(
	$installation_dir . "files/",
	
	$installation_dir . "other/authdb/login_control.tbl",
	$installation_dir . "other/authdb/user_stats.tbl",
	$installation_dir . "other/authdb/layout_type.tbl",
	$installation_dir . "other/authdb/layout_type_permission.tbl",
	
	$installation_dir . "app/__system/layer/presentation/test/webroot/__system/",
	$installation_dir . "app/__system/layer/presentation/common/webroot/__system/",
	
	//dependecies
	$installation_dir . "app/__system/layer/presentation/common/webroot/vendor/ckeditor/", 
	$installation_dir . "app/__system/layer/presentation/common/webroot/vendor/tinymce/", 
	$installation_dir . "app/lib/vendor/phpjavascriptpacker/", 
	$installation_dir . "app/lib/vendor/phpmailer/", 
	$installation_dir . "app/lib/vendor/xsssanitizer/", 
);

$check_folder_sub_files = array(
	$tmp_path,
	$installation_dir . "files/",
	$installation_dir . "vendor/",
	
	$installation_dir . "other/authdb/",
	$installation_dir . "other/workflow/",
	
	$installation_dir . "app/config/",
	$installation_dir . "app/layer/",  
	
	$installation_dir . "app/__system/layer/presentation/phpframework/webroot/__system/", 
	$installation_dir . "app/__system/layer/presentation/test/webroot/__system/", 
	
	$installation_dir . "app/__system/layer/presentation/common/webroot/cms/",
	$installation_dir . "app/__system/layer/presentation/common/webroot/__system/",
	$installation_dir . "app/__system/layer/presentation/common/src/module/",
	$installation_dir . "app/__system/layer/presentation/common/webroot/module/",
	
	//dependecies
	$installation_dir . "app/__system/layer/presentation/common/webroot/vendor/ckeditor/", 
	$installation_dir . "app/__system/layer/presentation/common/webroot/vendor/tinymce/", 
	$installation_dir . "app/lib/vendor/phpjavascriptpacker/", 
	$installation_dir . "app/lib/vendor/phpmailer/", 
	$installation_dir . "app/lib/vendor/xsssanitizer/", 
);

if ($tmp_path != $installation_dir . "tmp/") {
	array_unshift($files, $installation_dir . "tmp/");
	$optional_files[] = $installation_dir . "tmp/";
	$check_folder_sub_files[] = $installation_dir . "tmp/";
}

if ($tmp_path != $installation_dir . "app/tmp/") {
	array_unshift($files, $installation_dir . "app/tmp/");
	$optional_files[] = $installation_dir . "app/tmp/";
	$check_folder_sub_files[] = $installation_dir . "app/tmp/";
}

$setup_already_executed = is_dir($installation_dir . "app/layer/") && count(array_diff(scandir($installation_dir . "app/layer/"), array("..", ".", ".gitkeep", ".htaccess", ".htpasswd", ".git"))) > 0;
$document_root_status = $installation_dir == $document_root;
$php_version_status = version_compare(PHP_VERSION, '5.6', '>=');
$loaded_extensions = array_map("strtolower", get_loaded_extensions());
$current_user_info = function_exists("posix_getpwuid") ? posix_getpwuid(posix_getuid()) : null; //posix_getpwuid does not exists in windows
$is_apache_user = isset($current_user_info["name"]) && $current_user_info["name"] == "www-data";

$parsedown_path = $installation_dir . "app/lib/vendor/parsedown/Parsedown.php";
$md_contents = file_get_contents($installation_dir . "INSTALL.md");

if (file_exists($parsedown_path)) {
	include_once $parsedown_path;

	$Parsedown = new Parsedown();
	$inner_html = $Parsedown->text($md_contents);
	$inner_html = str_replace("<p>.</p>", "<p>&nbsp;</p>", $inner_html);
}
else {
	$inner_html = '<pre>' . str_replace("<br/>", "\n", str_replace("\n.\n", "\n\n", $md_contents)) . '</pre>';
}

//Important: do not remove these comments because it will be enabled by the other/scripts/create_git_repo.sh when creating the git repo

$gitignore_contents = file_get_contents($installation_dir . ".gitignore");
$is_gitignore_ok = preg_match("/(\n|\n\r)other\/authdb\/\s*$/", $gitignore_contents);

if (!$is_gitignore_ok) 
	$main_status = false;

echo '<style>.setup ul li.for_git_repo_creation {display:block !important;}</style>';


$html = "";

if ($setup_already_executed)
	$html .= '<div class="setup_already_executed">It seems this setup was already executed.<br/>If you continue, it may overwrite some of your changes...</div>';

$html .= "<ol>
	<li>Follow instructions from the INSTALL.md, this is:
		<div class=\"md_file\">
			$inner_html
		</div>
		<br/>
		<h3>Continue below but only if you have followed the instructions above correctly!</h3>
		<br/>
	</li>
	<li>Confirm if the Document Root of your web-server vhost conf file points to the folder: $installation_dir. " . printOptionalStatus($document_root_status) . "</li>
	<li>Confirm if your PHP version is 5.6 or higher (Bloxtor is tested until PHP 8.4). " . printOptionalStatus($php_version_status) . "</li>
	<li>Please be sure that you have PHP installed and all the following modules:
		<ul>
			<li>bcmath " . printOptionalStatus(in_array("bcmath", $loaded_extensions)) . "</li>
			<li>bz2 (optional)</li>
			<li>ctype (optional)</li>
			<li>curl " . printOptionalStatus(in_array("curl", $loaded_extensions)) . "</li>
			<li>dom " . printOptionalStatus(in_array("dom", $loaded_extensions)) . "</li>
			<li>date " . printOptionalStatus(in_array("date", $loaded_extensions)) . "</li>
			<li>exif " . printOptionalStatus(in_array("exif", $loaded_extensions)) . "</li>
			<li>fileinfo " . printOptionalStatus(in_array("fileinfo", $loaded_extensions)) . "</li>
			<li>filter " . printOptionalStatus(in_array("filter", $loaded_extensions)) . "</li>
			<li>ftp " . printOptionalStatus(in_array("ftp", $loaded_extensions)) . "</li>
			<li>gd " . printOptionalStatus(in_array("gd", $loaded_extensions)) . "</li>
			<li>hash " . printOptionalStatus(in_array("hash", $loaded_extensions)) . "</li>
			<li>imap " . printOptionalStatus(in_array("imap", $loaded_extensions)) . "</li>
			<li>intl (optional)</li>
			<li>json " . printOptionalStatus(in_array("json", $loaded_extensions)) . "</li>
			<li>libxml " . printOptionalStatus(in_array("libxml", $loaded_extensions)) . "</li>
			<li>mbstring " . printOptionalStatus(in_array("mbstring", $loaded_extensions)) . "</li>
			<li>memcache (optional)</li>
			<li>mongodb (optional)</li>
			<li>mysqli (or mysql or mysqlnd - optional)</li>
			<li>odbc (optional)</li>
			<li>openssl " . printOptionalStatus(in_array("openssl", $loaded_extensions)) . "</li>
			<li>pcre " . printOptionalStatus(in_array("pcre", $loaded_extensions)) . "</li>
			<li>pdo (optional)</li>
			<li>pdo_mysql (optional)</li>
			<li>pdo_odbc (optional)</li>
			<li>pdo_pgsql (optional)</li>
			<li>pdo_sqlite (optional)</li>
			<li>pgsql " . printOptionalStatus(in_array("pgsql", $loaded_extensions)) . "</li>
			<li>posix " . printOptionalStatus(in_array("posix", $loaded_extensions)) . "</li>
			<li>reflection " . printOptionalStatus(in_array("reflection", $loaded_extensions)) . "</li>
			<li>session " . printOptionalStatus(in_array("session", $loaded_extensions)) . "</li>
			<li>simplexml " . printOptionalStatus(in_array("simplexml", $loaded_extensions)) . "</li>
			<li>sqlite3 (optional)</li>
			<li>soap (optional)</li>
			<li>ssh2 " . printOptionalStatus(in_array("ssh2", $loaded_extensions)) . "</li>
			<li>tokenizer " . printOptionalStatus(in_array("tokenizer", $loaded_extensions)) . "</li>
			<li>xml " . printOptionalStatus(in_array("xml", $loaded_extensions)) . "</li>
			<li>xmlreader (optional)</li>
			<li>xmlrpc (optional)</li>
			<li>xmlwriter (optional)</li>
			<li>xsl " . printOptionalStatus(in_array("xsl", $loaded_extensions)) . "</li>
			<li>zend (op)cache (optional)</li>
			<li>zip " . printOptionalStatus(in_array("zip", $loaded_extensions)) . "</li>
			<li>zlib " . printOptionalStatus(in_array("zlib", $loaded_extensions)) . "</li>
		</ul>
	</li>
	<li>Confirm if your web-server user is www-data or you updated the correct user in the set_perms.sh script " . printOptionalStatus($is_apache_user) . "</li>
	<li>Confirm if your web-server has the mod_rewrite enable " . printOptionalStatus($document_root == dirname(__DIR__) . "/") . "</li>
	<li>If web-server modsecurity is enabled, confirm if /etc/modsecurity/modsecurity.conf is well configured according with our recomendations in INSTALL.md, but only if you get request body limit exceed errors.</li>
	<li>If web-server modsecurity is enabled, confirm if /usr/share/modsecurity-crs/modsecurity_crs_10_setup.conf and /usr/share/modsecurity-crs/base_rules/modsecurity_crs_30_http_policy.conf are well configured according with our recomendations in INSTALL.md, but only if you get denied requests.</li>
	<li>Confirm if php.ini files are well configured according with the recomendations in INSTALL.md:
		<ul>
			<li>short_open_tag = On " . printOptionalStatus(ini_get("short_open_tag") == "1") . "</li>
			<li>variables_order = \"EGPCS\" " . printOptionalStatus(ini_get("variables_order") == "EGPCS") . "</li>
			<li>date.timezone = Europe/Lisbon " . printOptionalStatus(ini_get("date.timezone") == "Europe/Lisbon") . "</li>
			<li>error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT " . printOptionalStatus(ini_get("error_reporting") == "22519") . "</li>
			
			<li>upload_max_filesize = 150M " . printOptionalStatus(ini_get("upload_max_filesize") == "150M") . "</li>
			<li>post_max_size = 150M " . printOptionalStatus(ini_get("post_max_size") == "150M") . "</li>

			<li>max_execution_time = 1000 " . printOptionalStatus(ini_get("max_execution_time") == "1000") . "</li>
			<li>max_input_time = 360 " . printOptionalStatus(ini_get("max_input_time") == "360") . "</li>
			<li>max_input_vars = 10000 " . printOptionalStatus(ini_get("max_input_vars") == "10000") . "</li>
			
			<li>memory_limit = 1024M " . printOptionalStatus(ini_get("memory_limit") == "1024M") . "</li>
		</ul>
	</li>
	<li class=\"for_git_repo_creation\">Add the line 'other/authdb/' at the end of the $installation_dir.gitignore file. " . printOptionalStatus(!empty($is_gitignore_ok)) . "</li>
	<li>If mysql server is installed, confirm if /etc/mysql/my.cnf is well configured according with the recomendations in INSTALL.md</li>
	<li>On CentOS, confirm if web-server can make external connections to mysql servers.</li>
	<li>Please be sure that your web-server has write permissions to the following files:
		" . checkFilesPermission($files, $optional_files, $check_folder_sub_files, $main_status) . "
	<br/>If some of the above files are <span class=\"non_writeable\">NON WRITEABLE</span>, please change their permissions or owner and refresh this page.</li>
	<li class=\"" . ($main_status ? "" : "disable") . "\">If all the files above are <span class=\"writeable\">OK</span>, please click in the button below \"<strong>Proceed with Setup</strong>\" to login and continue with setup...<br/>(<i style=\"opacity:.7\">To login please use the username: \"admin\" and the password: \"admin\".</i>)</li>
	<li>Then after you finish the setup, please go to \"User Management\" panel and change your login password...</li>
	<li>(optional) Delete the setup.php and INSTALL.md files.</li>
	</ol>
	
	<div class=\"button\"><button onClick=\"document.location='__system/setup/'\"" . ($main_status ? "" : 'disabled title="Something above is not OK"') . ">Proceed with Setup</button></div>";

echo $html;
?>
		<div class="enjoy">Enjoy...</div>
	</div>
</body>
</html>
