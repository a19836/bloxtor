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

$post_vars = isset($_GET["post_vars"]) ? $_GET["post_vars"] : null;

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
