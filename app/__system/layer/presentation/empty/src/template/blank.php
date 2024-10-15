<!DOCTYPE html>
<html>
<head>
	<?php if (!$EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("are_main_head_tags_included_in_page_level")) { ?>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="keywords" content="<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Page Keywords"); ?>" />
		<meta name="description" content="<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Page Description"); ?>" />
		<meta name="author" content="<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Page Author"); ?>">
		<link href="<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Icon Url"); ?>" rel="shortcut icon" type="image/x-icon" />

		<title><?= translateProjectLabel($EVC, $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Page Title")); ?></title>
	<?php } ?>
	
	<?php if (!$EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("is_bootstrap_lib_included_in_page_level")) { ?>
		<link href="<?php echo $original_project_url_prefix; ?>vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<script src="<?php echo $original_project_url_prefix; ?>vendor/bootstrap/js/bootstrap.min.js"></script>
	<?php } ?>
	
	<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->renderRegion("Head"); ?>
</head>
<body <?= $EVC->getCMSLayer()->getCMSTemplateLayer()->getParam("Body Attributes"); ?>>
	<?= $EVC->getCMSLayer()->getCMSTemplateLayer()->renderRegion("Content"); ?>
</body>
</html>
