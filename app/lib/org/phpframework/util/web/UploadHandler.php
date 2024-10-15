<?php
include_once get_lib("org.phpframework.util.web.ImageHandler");

class UploadHandler {
	
	public static function upload($files, $dst_folder, $validation_type = null) {
		$status = false;
		
		if (is_array($files) && array_key_exists("name", $files) && array_key_exists("tmp_name", $files) && array_key_exists("type", $files) && array_key_exists("size", $files)) {
			if (!is_array($files["name"])) {
				$files["name"] = array($files["name"]);
				$files["tmp_name"] = array($files["tmp_name"]);
				$files["type"] = array($files["type"]);
				$files["size"] = array($files["size"]);
				$files["error"] = array(isset($files["error"]) ? $files["error"] : null);
			}
			
			$status = true;
			
			if (!is_dir($dst_folder))
				$status = mkdir($dst_folder, 0755, true);
			
			if ($status) {
				if ($validation_type) {
					if (!is_array($validation_type))
						$validation_type = array($validation_type);
					
					$ImageHandler = new ImageHandler();
				}
				
				foreach ($files["name"] as $idx => $name) 
					if ($name && !empty($files["tmp_name"][$idx])) {
						$tmp_name = $files["tmp_name"][$idx];
						$type = isset($files["type"][$idx]) ? $files["type"][$idx] : null;
						$size = isset($files["size"][$idx]) ? $files["size"][$idx] : null;
						$error = isset($files["error"][$idx]) ? $files["error"][$idx] : null;
						
						if ($error == UPLOAD_ERR_OK && is_uploaded_file($tmp_name)) {
							$validated = true;
							
							if ($validation_type)
								foreach ($validation_type as $vt)
									switch ($vt) {
										case "image":
											$validated = $ImageHandler->isImageValid($tmp_name);
											break;
										default: 
											$mime_type = MimeTypeHandler::getFileMimeType($tmp_name);
											$validated = MimeTypeHandler::checkMimeType($mime_type, $vt);
									}
							
							if ($validated) {
								$name = basename($name);
				  				
				  				if (!move_uploaded_file($tmp_name, "$dst_folder/$name"))
				  					$status = false;
				  			}
				  			else
				  				$status = false;
						}
					}
			}
		}
		
		return $status;
	}
}
?>
