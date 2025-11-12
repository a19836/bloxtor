<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getViewPath("admin/terminal_console");

if ($layer_path) {
	$head .= '<script>
on_load_shell_func = setDefaultDir;

function setDefaultDir() {
	var f = $(".terminal_console > .input > form");
	var i = f.find(".input_text");
	
	i.val("cd ' . $folder_path . '");
	f.submit();
}
</script>';
}
?>
