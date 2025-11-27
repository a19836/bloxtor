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

if (!defined("MAX_OVERFLOW_VALUE"))
	define("MAX_OVERFLOW_VALUE", "2147483647"); 

if (!defined("MIN_OVERFLOW_VALUE"))
	define("MIN_OVERFLOW_VALUE", "-2147483648"); 

class HashCode {
	
	public static function getHashCode($str){
		$hash = 0;
		
		for ($i = 0; $i < strlen($str); $i++){
			$hash = self::getBigInt(bcadd(bcmul(31, $hash), ord($str[$i])));	
			//echo $str[$i] . " - " . ord($str[$i]) . " - " . $hash . "<br><br>";
		}
		
		return $hash;
	}

	public static function getHashCodePositive($str){
		$hash = self::getHashCode($str);
		
		if($hash < 0) {
			$abs = bcsub(0, $hash);
			$hash = bcadd(MAX_OVERFLOW_VALUE, bcsub($abs, 1));
		}
		
		return $hash;
	}
	
	//get big integer's value, consistent with core-platform
	public static function getBigInt($num) {
		$result = $num;
		$max = MAX_OVERFLOW_VALUE;
		$min = MIN_OVERFLOW_VALUE;
		$length = bcsub($max, $min);
		$flag = bccomp($num, $min);
		
		//process smaller negative value
		if($flag == -1) {
			$result = bcsub(0, $result);
		}

		if(bccomp($result, $max) == 1) {
			$f = bcdiv($result, $length);
			$r = bcmod($result, $length);
			
			if($f == 0) {
				$r = bcsub($result, $max);
			}

			$result = bcadd($min, bcsub($r,1));

			if($flag == -1) {
				$result = bcsub(0, $result);
			}

			//echo $f . " - " . $r . " - " . $result . "--<br>";
		}

		return $result;
	}
}
?>
