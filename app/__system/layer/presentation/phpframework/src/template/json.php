<?php
if (!empty($obj)) {
	header("Content-type: application/json");
	
	echo json_encode($obj);
}
?>
