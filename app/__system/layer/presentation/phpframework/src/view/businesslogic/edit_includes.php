<?php
include $EVC->getViewPath("admin/edit_file_includes");

$head .= '<script>
	save_object_url = save_object_url.replace("/admin/save_file_includes?", "/businesslogic/save_includes?");
</script>';
?>
