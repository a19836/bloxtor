<?php
if ($_FILES["file"]) {
	$uploaddir = '/tmp/';
	$uploadfile = $uploaddir . basename($_FILES['file']['name']);

	echo '<pre>';
	
	if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
	    echo "File is valid, and was successfully uploaded.\n";
	} 
	else {
	    echo "Possible file upload attack!\n";
	}

	echo 'Here is some more debugging info:';
	print_r($_FILES);

	print "</pre>";
}
?>
