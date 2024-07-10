<?php
class WordPressCMSBlockSettings {
	//used in the CMSExternalTemplateLayer::getTemplateCodeFromWordPressContents
	const WORDPRESS_REQUEST_CONTENT_ENCRYPTION_KEY_HEX = "039586fb0dfce863c79de454c557f466"; //hexadecimal key created through CryptoKeyHandler::getKey()
	const WORDPRESS_REQUEST_CONTENT_CONNECTION_TIMEOUT = 120; //in seconds
	
	//This method is exactly the same than the CommonSettings::getConstantVariable in the "common" module.
	public static function getSetting($const_name) {
		#echo("return isset(\$GLOBALS['$const_name']) ? \$GLOBALS['$const_name'] : self::$const_name;\n");
		return eval("return isset(\$GLOBALS['$const_name']) ? \$GLOBALS['$const_name'] : static::$const_name;");
	}
}
?>
