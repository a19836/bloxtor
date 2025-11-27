<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

/*
Just a little note on  [P.Peyremorte]'s note.

"- openssl_private_encrypt can encrypt a maximum of 117 chars at one time."

This depends on the length of $key:

- For a 1024 bit key length => max number of chars (bytes) to encrypt = 1024/8 - 11(when padding used) = 117 chars (bytes).
- For a 2048 bit key length => max number of chars (bytes) to encrypt = 2048/8 - 11(when padding used) = 245 chars (bytes).
... and so on

By the way, if openssl_private_encrypt fails because of data size you won't get anything but just false as returned value, the same for openssl_public_decrypt() on decryption.

"- the encrypted output string is always 129 char length. If you use base64_encode on the encrypted output, it will give always 172 chars, with the last always "=" (filler)"

This again depends on the length of $key:

- For a 1024 bit key length => encrypted number of raw bytes is always a block of 128 bytes (1024 bits) by RSA design.
- For a 2048 bit key length => encrypted number of raw bytes is always a block of 256 bytes (2048 bits) by RSA design.
... and so on

About base64_encode output length, it depends on what you encode (meaning it depends on the bytes resulting after encryption), but in general the resulting encoded string will be about a 33% bigger (for 128 bytes bout 170 bytes and for 256 bytes about 340 bytes).

Note: 
- How to create private and pulic keys:
	https://en.wikibooks.org/wiki/Cryptography/Generate_a_keypair_using_OpenSSL
	Create private key:
		openssl genpkey -algorithm RSA -out private_key.pem -pkeyopt rsa_keygen_bits:2048
	Create public key:
		openssl rsa -pubout -in private_key.pem -out public_key.pem
*/
class PublicPrivateKeyHandler {
	private $encrypt_block_size;//Block size for encryption block cipher
	private $decrypt_block_size;//Block size for decryption block cipher
	
	public $error = false;
	
	public function __construct($is_2048_bits_key = false) {
		if ($is_2048_bits_key) {
			$this->encrypt_block_size = 200;// this for 2048 bit key for example, leaving some room
			$this->decrypt_block_size = 256;// this again for 2048 bit key
		}
		else {
			$this->encrypt_block_size = 117;// this for 1024 bit key for example
			$this->decrypt_block_size = 172;// this again for 1024 bit key
		}	
	}
	
	public function encryptString($string, $private_key_file, $passphrase = "") {
		if (file_exists($private_key_file)) {
			$fp = fopen($private_key_file, "r"); 
			$priv_key = fread($fp, 8192); 
			fclose($fp); 
		
			return $this->encryptRSA($string, $priv_key, $passphrase);
		}
		
		$this->error = true;
	}
	
	public function decryptString($string, $public_key_file) {
		if (file_exists($public_key_file)) {
			$fp = fopen($public_key_file, "r"); 
			$pub_key = fread($fp, 8192); 
			fclose($fp); 
			
			return $this->decryptRSA($string, $pub_key);
		}
		
		$this->error = true;
	}
	
	//For encryption we would use:
	public function encryptRSA($string, $private_pem_key, $passphrase = "") {
		$encrypted = '';
		
		$this->error = false;
			
		if (!is_resource($private_pem_key) && !is_object($private_pem_key))
			$private_pem_key = openssl_pkey_get_private($private_pem_key, $passphrase); // $passphrase is required if your key is encoded (suggested) 
		
		$string = str_split($string, $this->encrypt_block_size);
		
		foreach($string as $chunk) {
			$partial_encrypted = '';
			
			//using for example OPENSSL_PKCS1_PADDING as padding
			$encryption_ok = openssl_private_encrypt($chunk, $partial_encrypted, $private_pem_key, OPENSSL_PKCS1_PADDING);
			
			if($encryption_ok === false) {
				$this->error = true;
				return false;//also you can return and error. If too big this will be false
			}
			
			$encrypted .= $partial_encrypted;
		}
		
		return base64_encode($encrypted);//encoding the whole binary String as MIME base 64
	}

	 //For decryption we would use:
	public function decryptRSA($string, $public_pem_key) {
		$decrypted = '';
		
		$this->error = false;
			
		if (!is_resource($public_pem_key) && !is_object($public_pem_key))
			$public_pem_key = openssl_pkey_get_public($public_pem_key); 
		
		//decode must be done before spliting for getting the binary String
		$string = str_split(base64_decode($string), $this->decrypt_block_size);

		foreach($string as $chunk) {
			$partial = '';

			//be sure to match padding
			$decryption_oK = openssl_public_decrypt($chunk, $partial, $public_pem_key, OPENSSL_PKCS1_PADDING);

			if($decryption_oK === false) {
				$this->error = true;
				return false;//here also processed errors in decryption. If too big this will be false
			}
			
			$decrypted .= $partial;
		}
		
		return $decrypted;
	}
}
?>
