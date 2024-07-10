<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="icon" href="data:;base64,=" />
	
	<?
		$default_html_head = '
	<link rel="stylesheet" href="' . $project_common_url_prefix . 'css/global.css" type="text/css" charset="utf-8" />
	<link rel="stylesheet" href="' . $project_url_prefix. 'phpframework/css/global.css" type="text/css" charset="utf-8" />
	<link rel="stylesheet" href="' . $project_url_prefix. 'phpframework/css/message.css" type="text/css" charset="utf-8" />
	
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'js/global.js"></script>
	
	<!-- Colors -->
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'js/color.js"></script>
	
	<!-- Json -->
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/json/js/json2.js"></script>
	
	<!-- Jquery -->
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery-1.8.1.min.js"></script>
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.center.js"></script>
	
	<!-- Add Jquery UI JS and CSS files -->
	<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jqueryui/css/jquery-ui-1.11.4.css" type="text/css" />
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jqueryui/js/jquery-ui-1.11.4.min.js"></script>
	
	<!-- MyJSLib -->
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'js/MyJSLib.js"></script>
	
	<!-- Fancy LighBox -->
	<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymyfancylightbox/css/style.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymyfancylightbox/js/jquery.myfancybox.js"></script>
	
	<!-- Message -->
	<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/jquerymystatusmessage/css/style.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymystatusmessage/js/statusmessage.js"></script>';
	
		//echo $default_html_head;
		echo $CssAndJSFilesOptimizer->prepareHtmlWithOptimizedCssAndJSFiles($default_html_head);
	?>
	
	<script>
	$(function () {
		var is_iframe = (window.location != window.parent.location) ? true : false;
		if (isMSIE() && $.browser.version > 0 && $.browser.version < 11 && !is_iframe)
			alert("Please upgrade your IE to a version equal or bigger than 11 or use other browser like: Firefox, Chrome... Otherwise this application can be buggy!");
		
		StatusMessageHandler.init();
	});
	</script>
	
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-9SW8102J7B"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'G-9SW8102J7B');
	</script>
	
	<? echo $head; ?>
</head>
<body<?= ' class="' . (isset($_COOKIE["theme_layout"]) ? $_COOKIE["theme_layout"] : "") . ' ' . (isset($_COOKIE["main_navigator_side"]) ? $_COOKIE["main_navigator_side"] : "") . (!empty($_GET["popup"]) ? " in_popup" : "") . '"'; ?>>
	<div id="main_column"><? 
		include $EVC->getTemplatePath("message");
		
		echo $main_content;
	?></div>
</body>
</html>

