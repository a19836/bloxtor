<?php
class OpenSSLCipherHandler {
	
	/**
	 * Encrypt a text with a salt into a ciphertext.
	 * Note that according with our tests we realized that the returned cipher text has a length of 108 chars. To be safe, the DB attribute where this cipher will be saved, should have a length of 150 chars, just in case.
	 * 
	 * @param mixed $text - decrypted text
	 * @param string $key - salt - can be any string
	 */
	public static function encryptText($text, $key) {
		if (strlen($text)) {
			$cipher = "AES-128-CBC";
			$ivlen = openssl_cipher_iv_length($cipher);
			$iv = openssl_random_pseudo_bytes($ivlen);
			
			$cipher_text_raw = openssl_encrypt($text, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
			$hmac = hash_hmac('sha256', $cipher_text_raw, $key, $as_binary = true);
			$cipher_text = base64_encode( $iv . $hmac . $cipher_text_raw );
			
			return $cipher_text;
		}
		
		return $text;
	}
	
	/**
	 * Encrypt a variable value with a salt into a ciphertext. If variable is array, encrypts all items
	 * 
	 * @param mixed $var - string or array or object
	 * @param string $key - salt - can be any string
	 */
	public static function encryptVariable($var, $key) {
		if ($var) {
			if (is_array($var) || is_object($var)) {
				foreach ($var as $k => $v)
					$var[$k] = self::encryptVariable($v, $key);
			}
			else 
				$var = self::encryptText($var, $key);
		}
		
		return $var;
	}
	
	/**
	 * Encrypt an array's items with a salt into a ciphertext
	 * 
	 * @param mixed $arr - array with decrypted items
	 * @param string $key - salt - can be any string
	 */
	public static function encryptArray($arr, $key) {
		return self::encryptVariable($arr, $key);
	}
	
	/**
	 * Decrypt a cipher text with a salt
	 * 
	 * @param string $cipher_text - encrypted text
	 * @param string $key - salt - can be any string
	 */
	public static function decryptText($cipher_text, $key) {
		if (strlen($cipher_text)) {
			$cipher = "AES-128-CBC";
			$c = base64_decode($cipher_text);
			$ivlen = openssl_cipher_iv_length($cipher);
			$iv = substr($c, 0, $ivlen);
			$hmac = substr($c, $ivlen, $sha2len = 32);
			$cipher_text_raw = substr($c, $ivlen + $sha2len);
			
			$original_plaintext = openssl_decrypt($cipher_text_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
			$calcmac = hash_hmac('sha256', $cipher_text_raw, $key, $as_binary = true);
			
			if (hash_equals($hmac, $calcmac)) //PHP 5.6+ timing attack safe comparison 
			   return $original_plaintext;
		}
	}
	
	/**
	 * Decrypt a variable with cipher text. Uses a salt string to decrypt. If variable is array, decrypts all items values
	 * 
	 * @param string $var - string or array or object
	 * @param string $key - salt - can be any string
	 */
	public static function decryptVariable($var, $key) {
		if ($var) {
			if (is_array($var) || is_object($var)) {
				foreach ($var as $k => $v)
					$var[$k] = self::decryptVariable($v, $key);
			}
			else 
				$var = self::decryptText($var, $key);
		}
		
		return $var;
	}
	
	/**
	 * Decrypt an array's items with cipher texts inside. Uses a salt string to decrypt.
	 * 
	 * @param string $arr - array with encrypted items
	 * @param string $key - salt - can be any string
	 */
	public static function decryptArray($arr, $key) {
		return self::decryptVariable($arr, $key);
	}
}
?>
