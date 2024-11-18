<?php
$head = '
<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<style>
.phpmyadmin .message {
	margin:20px;
	text-align:center;
}
</style>';

$main_content = '<div class="phpmyadmin">
	<div class="top_bar">
		<header>
			<div class="title">PhpMyAdmin for DB: \'' . $bean_name . '\'</div>
		</header>
	</div>
	
	<div class="message">' . ($phpmyadmin_enable ? 'Redirecting to PhpMyadmin...' : 'PhpMyAdmin is not installed or is not properly configured!<br/>Please talk to your Sysadmin.') . '</div>
	
	' . ($phpmyadmin_enable ? '<script>document.location="' . $phpmyadmin_url . '";</script>' : '') . '
</div>
';
?>
