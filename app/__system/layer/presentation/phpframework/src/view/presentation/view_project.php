<?php
$post_vars = $_GET["post_vars"];

$main_content = '
<form method="' . ($post_vars ? "post" : "get") . '" action="' . str_replace('"', '%22', $url) . '" style="display:none">';

if ($post_vars)
	foreach ($post_vars as $k => $v)
		$main_content .= '<input type="hidden" name="' . htmlentities($k) . '" value="' . htmlentities($v) . '" />';

$main_content .= '
	<input type="submit" value="go"/>
</form>
<script>
	document.forms[0].submit();
</script>';
?>
