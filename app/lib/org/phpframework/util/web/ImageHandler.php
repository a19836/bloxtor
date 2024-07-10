<?php
include_once get_lib("org.phpframework.util.MimeTypeHandler");

class ImageHandler {
	const IMAGE_DOES_NOT_EXIST = 1001;//Unsupported picture type!
	const UNSUPPORTED_IMAGE_TYPE = 1002;//Unsupported picture type!
	const IMAGE_IS_TOO_SMALL = 1003;//Picture is too small!
	const IMAGE_WITH_WRONG_ASPECT_RATIO = 1004;//Picture has wrong aspect ratio
	
	private $errors = array();
	private $allowed_extensions = array('bmp', 'gif', 'jpeg', 'jpg', 'png');
	
	public function __construct() {
		
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function isImageValid($src_path) {
		$this->errors = array();
		
		if (!$src_path || !file_exists($src_path)) {
			$this->errors[] = self::IMAGE_DOES_NOT_EXIST;
			return false;	
		}
		
		$mime_type = MimeTypeHandler::getFileMimeType($src_path);
		
		if (!MimeTypeHandler::isImageMimeType($mime_type)) {
			$this->errors[] = self::UNSUPPORTED_IMAGE_TYPE;
			return false;
		}
		
		$type = MimeTypeHandler::getTypeByMimeType($mime_type);
       	$extension = $type && isset($type["extension"]) ? $type["extension"] : substr(strrchr($src_path, "."), 1);
       	$parts = explode(",", $extension);
       	$extension = trim(strtolower($parts[0]));
       	
       	$allowed_extensions = MimeTypeHandler::getAvailableFileExtensions("image");
       	$allowed_extensions = is_array($allowed_extensions) && $allowed_extensions ? $allowed_extensions : $this->allowed_extensions;
       	
       	return in_array($extension, $allowed_extensions);
	}
	
	public function isImageBinaryValid($src_path) {
		$status = $this->isImageValid($src_path);
		
		if ($status && getimagesize($src_path) === false) {
			$this->errors[] = self::UNSUPPORTED_IMAGE_TYPE;
			$status = false;
		}
		
		return $status;
	}
	
	public function imageResize($src_path, $dst_path, $width, $height, $crop = false, $force_resize = false) {
		if (!$this->isImageBinaryValid($src_path))
			return false;
		
		$extension = $this->getFileExtension($src_path);
		
		switch($extension){
			case 'bmp': 
				$img = imagecreatefromwbmp($src_path); 
				break;
			case 'gif': 
				$img = imagecreatefromgif($src_path); 
				break;
			case 'jpeg': 
			case 'jpg': 
				$img = imagecreatefromjpeg($src_path); 
				break;
			case 'png': 
				$img = imagecreatefrompng($src_path); 
				break;
			default : 
				$this->errors[] = self::UNSUPPORTED_IMAGE_TYPE;
				return false;
		}

		if ($img) {
			list($w, $h) = getimagesize($src_path);
		
			if ($w != $width || $h != $height) {
				if (($width > $height && $w < $h) || ($width < $height && $w > $h))
					$this->errors[] = self::IMAGE_WITH_WRONG_ASPECT_RATIO;
		
				// resize
				if($crop) {
					if($w < $width || $h < $height) 
						$this->errors[] = self::IMAGE_IS_TOO_SMALL;
			
					$ratio = max($width / $w, $height / $h);
					$h = $height / $ratio;
					$x = ($w - $width / $ratio) / 2;
					$w = $width / $ratio;
				}
				else {
					if($w < $width && $h < $height) 
						$this->errors[] = self::IMAGE_IS_TOO_SMALL;
			
					$ratio = min($width / $w, $height / $h);
					$width = $w * $ratio;
					$height = $h * $ratio;
					$x = 0;
				}
				
				//only resize if image is not too small, otherwise is not worth it.
				if (!in_array(self::IMAGE_IS_TOO_SMALL, $this->errors) || $force_resize) {
					$new = imagecreatetruecolor($width, $height);

					if ($new === false)
						return false;

					// preserve transparency
					if($extension == "gif" || $extension == "png") {
						imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
						imagealphablending($new, false);
						imagesavealpha($new, true);
					}
		
					if (imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h))
						switch($extension){
							case 'bmp': 
								$status = imagewbmp($new, $dst_path);
								break; 
							case 'gif': 
								$status = imagegif($new, $dst_path); 
								break; 
							case 'jpeg': 
							case 'jpg': 
								$status = imagejpeg($new, $dst_path); 
								break; 
							case 'png': 
								$status = imagepng($new, $dst_path);
								break; 
						}
				      
			   		@imagedestroy($new);
			   	}
			   	else //if is smaller than the user width/height, simply copy image to $dst_path
			   		$status = copy($src_path, $dst_path);
		   	}	
			else //if width and height are the same, simply copy image to $dst_path
				$status = copy($src_path, $dst_path);
			
			@imagedestroy($img); 
		} 
	   	
		return $status;
	}
	
	public function areImageMeasuresValid($src_path, $width, $height, $crop = false) {
		if (!$this->isImageBinaryValid($src_path))
			return false;
		
		list($w, $h) = getimagesize($src_path);
		
		if($crop && ($w < $width || $h < $height)) {
			$this->errors[] = self::IMAGE_IS_TOO_SMALL;
			return false;
		}
		else if(!$crop && $w < $width && $h < $height) {
			$this->errors[] = self::IMAGE_IS_TOO_SMALL;
			return false;
		}
		
		if (($width > $height && $w < $h) || ($width < $height && $w > $h)) {
			$this->errors[] = self::IMAGE_WITH_WRONG_ASPECT_RATIO;
			return false;
		}
		
		return true;
	}
	
	private function getFileExtension($src_path) {
		$mime_type = MimeTypeHandler::getFileMimeType($src_path);
		$type = MimeTypeHandler::getTypeByMimeType($mime_type);
            	$extension = $type && isset($type["extension"]) ? $type["extension"] : substr(strrchr($src_path, "."), 1);
            	$parts = explode(",", $extension);
            	return trim(strtolower($parts[0]));
	}
}

/*
$pic_type = strtolower(strrchr($picture['name'],"."));
$pic_name = "original$pic_type";
move_uploaded_file($picture['tmp_name'], $pic_name);
if (true !== ($pic_error = @ImageHandler::imageResize($pic_name, "100x100$pic_type", 100, 100, 1))) {
echo $pic_error;
unlink($pic_name);
}
else echo "OK!";
*/
?>
