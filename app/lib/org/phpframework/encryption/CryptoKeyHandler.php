<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

use \Defuse\Crypto\Crypto;
use \Defuse\Crypto\Exception as Ex;

include get_lib("lib.vendor.phpencryption.src.ExceptionHandler");
include get_lib("lib.vendor.phpencryption.src.Crypto");
include get_lib("lib.vendor.phpencryption.src.Exception.CryptoException");
include get_lib("lib.vendor.phpencryption.src.Exception.InvalidCiphertextException");
include get_lib("lib.vendor.phpencryption.src.Exception.CryptoTestFailedException");
include get_lib("lib.vendor.phpencryption.src.Exception.CannotPerformOperationException");

class CryptoKeyHandler {
	
	public static function getKey() {
		try {
			$key = Crypto::createNewRandomKey();
			// WARNING: Do NOT encode $key with bin2hex() or base64_encode(),
			// they may leak the key to the attacker through side channels.
		} catch (Ex\CryptoTestFailedException $ex) {
			echo 'Cannot safely create a key';
			die();
		} catch (Ex\CannotPerformOperationException $ex) {
			echo 'Cannot safely create a key';
			die();
		}
		
		return $key;
	}
	
	public static function getHexKey() {
		return self::binToHex(self::getKey());
	}
	
	/**
	 * Encrypt a text into ciphertext
	 * 
	 * @param mixed $text - decrypted text
	 * @param string $key - crypto key
	 */
	public static function encryptText($text, $key) {
		if (!isset($text)) {
			return null;
		}
		
		try {
			$cipher_text = Crypto::encrypt($text, $key);
		} 
		catch (Ex\CryptoTestFailedException $ex) {
			echo 'Cannot safely perform encryption';
			die();
		} 
		catch (Ex\CannotPerformOperationException $ex) {
			echo 'Cannot safely perform encryption';
			die();
		}
		
		return $cipher_text;
	}
	
	/**
	 * Decrypt a cipher text
	 * 
	 * @param string $cipher_text - encrypted text
	 * @param string $key - crypto key
	 */
	public static function decryptText($cipher_text, $key) {
		if (empty($cipher_text)) {
			return null;
		}
		
		try {
			$decrypted = Crypto::decrypt($cipher_text, $key);
		} 
		catch (Ex\InvalidCiphertextException $ex) { // VERY IMPORTANT
			// Either:
			//   1. The ciphertext was modified by the attacker,
			//   2. The key is wrong, or
			//   3. $ciphertext is not a valid ciphertext or was corrupted.
			// Assume the worst.
			echo 'DANGER! DANGER! The ciphertext has been tampered with!';
			die();
		} 
		catch (Ex\CryptoTestFailedException $ex) {
			echo 'Cannot safely perform decryption';
			die();
		} 
		catch (Ex\CannotPerformOperationException $ex) {
			echo 'Cannot safely perform decryption';
			die();
		}
		
		return $decrypted;
	}
	
	/**
	 * Encrypt an object/array into json and then into ciphertext
	 * 
	 * @param mixed $obj - object or array
	 * @param string $key - crypto key
	 */
	public static function encryptJsonObject($obj, $key) {
		if (!isset($obj)) {
			return null;
		}
		
		$text = json_encode($obj);
		
		return self::encryptText($text, $key);
	}
	
	/**
	 * Decrypt a cipher text into json and then into object/array
	 * 
	 * @param string $cipher_text - encrypted text from a json obj/array
	 * @param string $key - crypto key
	 */
	public static function decryptJsonObject($cipher_text, $key, $convert_to_array = true) {
		$decrypted = self::decryptText($cipher_text, $key);
		
		return isset($decrypted) ? json_decode($decrypted, $convert_to_array) : null;
	}
	
	/**
	 * Encrypt an object/array into serialize and then into ciphertext
	 * 
	 * @param mixed $obj - object or array
	 * @param string $key - crypto key
	 */
	public static function encryptSerializedObject($obj, $key) {
		if (!isset($obj)) {
			return null;
		}
		
		$text = serialize($obj);
		
		return self::encryptText($text, $key);
	}
	
	/**
	 * Decrypt a cipher text into serialized text and then into object/array
	 * 
	 * @param string $cipher_text - encrypted text from a serialized obj/array
	 * @param string $key - crypto key
	 */
	public static function decryptSerializedObject($cipher_text, $key) {
		$decrypted = self::decryptText($cipher_text, $key);
		
		return isset($decrypted) ? unserialize($decrypted) : null;
	}
	
	/**
	* Convert a binary string into a hexadecimal string without cache-timing 
	* leaks
	* 
	* @param string $bin_string (raw binary)
	* @return string
	*/
	public static function binToHex($bin_string) {
		return Crypto::binToHex($bin_string);
		//return bin2hex($bin_string);
	}
	
	/**
	* Convert a hexadecimal string into a binary string without cache-timing 
	* leaks
	* 
	* @param string $hex_string
	* @return string (raw binary)
	*/
	public static function hexToBin($hex_string) {
		return Crypto::hexToBin($hex_string);
		//return hex2bin($bin_string);
	}
}
?>
