<?php
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
