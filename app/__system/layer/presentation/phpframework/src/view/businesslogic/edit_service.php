<?php
include $EVC->getViewPath("admin/edit_file_class");

$head .= '<script>
	save_object_url = save_object_url.replace("/admin/save_file_class?", "/businesslogic/save_service?");
</script>';
?>
