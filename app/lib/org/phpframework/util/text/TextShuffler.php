<?php
//include_once __DIR__ . "/TextSanitizer.php";
include_once get_lib("org.phpframework.util.text.TextSanitizer");

//Note: WE must use the mb_ (mb_strlen, mb_substr) methods instead of strlen or substr, otherwise this won't work for words with accents. Chars with accents are multi-bytes characters and must be taken care with the mb_ methods.
class TextShuffler {
	
	public static function shuffle($string, $type = 1, $options = false) {
		switch ($type) {
			case 2:
				return self::shuffle2($string, $options);
			case 3:
				return self::shuffle3($string, $options);
			case 4:
				return self::shuffle4($string, $options);
			case 5:
				return self::shuffle5($string, $options);
		}
		
		return self::shuffle1($string, $options);
	}
	
	public static function unshuffle($string, $type = 1, $options = false) {
		switch ($type) {
			case 2:
				return self::unshuffle2($string, $options);
			case 3:
				return self::unshuffle3($string, $options);
			case 4:
				return self::unshuffle4($string, $options);
			case 5:
				return self::unshuffle5($string, $options);
		}
		
		return self::unshuffle1($string, $options);
	}
	
	//IMPORTANT: DO NOT change this code, because it's being used by the UserUtil and is saving encoded strings to the DB. If you change this code, then the old DB saved string won't be decoded correctly.
	//Note: if disable_email == 1, treat string as a normal string, otherwise only encode substring before @
	public static function autoShuffle($string, $options = false) {
		$str = "";
		
		if (mb_strlen($string)) {
			if (empty($options["disable_email"])) {
				$pos = mb_strpos($string, "@");
				
				if ($pos !== false) {
					$domain = mb_substr($string, $pos);
					$string = mb_substr($string, 0, $pos);
				}
			}
			
			$first_char = mb_substr($string, 0, 1);
			$string = mb_substr($string, 1);
			$num = ord(strtolower($first_char));
			
			//if first char between A - I
			if ($num >= 97 && $num <= 105)
				$str = self::shuffle1($string, $options);
			//if first char between J - T
			else if ($num >= 106 && $num <= 116)
				$str = self::shuffle2($string, $options);
			//if first char between U - Z
			else if ($num >= 117 && $num <= 122)
				$str = self::shuffle3($string, $options);
			//if first char is number (48 - 57) or some symbols like <>+-*/, etc...
			else if ($num <= 96)
				$str = self::shuffle4($string, $options);
			//if first char are other symbols, etc...
			else
				$str = self::shuffle5($string, $options);
			
			$str = $first_char . $str;
		
			if (empty($options["disable_email"]) && !empty($domain))
				$str .= $domain;
		}
		
		return $str;
	}

	//IMPORTANT: DO NOT change this code, because it's being used by the UserUtil and is saving encoded strings to the DB. If you change this code, then the old DB saved string won't be decoded correctly.
	//Note: if disable_email == 1, treat string as a normal string, otherwise only decode substring before @
	public static function autoUnshuffle($string, $options = false) {
		$str = "";
		
		if (mb_strlen($string)) {
			if (empty($options["disable_email"])) {
				$pos = mb_strpos($string, "@");
				
				if ($pos !== false) {
					$domain = mb_substr($string, $pos);
					$string = mb_substr($string, 0, $pos);
				}
			}
			
			$first_char = mb_substr($string, 0, 1);
			$string = mb_substr($string, 1);
			$num = ord(strtolower($first_char));
			
			//if first char between A to I
			if ($num >= 97 && $num <= 105)
				$str = self::unshuffle1($string, $options);
			//if first char between J to T
			else if ($num >= 106 && $num <= 116)
				$str = self::unshuffle2($string, $options);
			//if first char between U - Z
			else if ($num >= 117 && $num <= 122)
				$str = self::unshuffle3($string, $options);
			//if first char is number (48 - 57) or some symbols like <>+-*/, etc...
			else if ($num <= 96)
				$str = self::unshuffle4($string, $options);
			//if first char are other symbols, etc...
			else
				$str = self::unshuffle5($string, $options);
			
			$str = $first_char . $str;
		
			if (empty($options["disable_email"]) && !empty($domain))
				$str .= $domain;
		}
		
		return $str;
	}
	
	//Splits string in half and create a new one with the 2nd half and then the 1st half. But for the 2nd half, splits it in chunks of 2 chars in a descending loop and create a new string prepending the next chunk to the previous one. And for the 1st half, splits it in chunks of 3 chars in a ascending loop and create a new string with [+1] [+2] [0] but each previous chunk will be appended to the next chunk.
	//To understand this better, suffle 'est123@gmail.com'
	public static function shuffle1($string, $options = false) {
		$str = "";
		$string_chars = TextSanitizer::mbStrSplit($string);
		$t = count($string_chars);
		
		if ($t) {
			$middle = ceil($t / 2);
			$f = array_slice($string_chars, 0, $middle);
			$s = array_slice($string_chars, $middle);
			$t = count($s);
			
			$aux = "";
			for ($i = $t - 1; $i >= 0; $i -= 2)
				$aux .= ($i - 1 >= 0 ? $s[$i - 1] : "") . $s[$i];
			
			for ($i = 0; $i < $middle; $i += 3)
				$str = ($i + 1 < $middle ? $f[$i + 1] : "") . ($i + 2 < $middle ? $f[$i + 2] : "") . $f[$i] . $str;
			
			$str = $aux . $str;
		}
		
		return $str;
	}
	//2020-09-14: I should avoid use loops with mb_substr function, bc is much more slow than the substr function. But bc this loop is short, it's OK! However if I decide to use this method with long values in the $string var, I may need to change this code...
	/*public static function shuffle1($string, $options = false) {
		$str = "";
		$t = mb_strlen($string);
		
		if ($t) {
			$middle = ceil($t / 2);
			$f = mb_substr($string, 0, $middle);
			$s = mb_substr($string, $middle);
			$t = mb_strlen($s);
			
			$aux = "";
			for ($i = $t - 1; $i >= 0; $i -= 2)
				$aux .= ($i - 1 >= 0 ? mb_substr($s, $i - 1, 1) : "") . mb_substr($s, $i, 1);
			
			for ($i = 0; $i < $middle; $i += 3)
				$str = mb_substr($f, $i + 1, 1) . mb_substr($f, $i + 2, 1) . mb_substr($f, $i, 1) . $str;
		
			$str = $aux . $str;
		}
	
		return $str;
	}*/
	
	//Splits string in half (counting from the last char) and create a new one with the 2nd half and then the 1st half. But for the first half splits it in chunks of 3 chars in a loop on a descending order and create a new string [0] [-2] [-1]. And for the 2nd half splits it in chunks of 2 chars and create a new string prepending the next chunk to the previous one.
	//To understand this better, unsuffle 'om.cilmag@231ste' which corresponds to 'est123@gmail.com'
	public static function unshuffle1($string, $options = false) {
		$str = "";
		$string_chars = TextSanitizer::mbStrSplit($string);
		$t = count($string_chars);
		
		if ($t) {
			$middle = ceil($t / 2);
			$f = array_slice($string_chars, $t - $middle);
			$s = array_slice($string_chars, 0, $t - $middle);
			$t = count($s);
		
			for ($i = $middle - 1; $i >= 0; $i -= 3)
				$str .= $f[$i] . ($i - 2 >= 0 ? $f[$i - 2] : "") . ($i - 1 >= 0 ? $f[$i - 1] : "");
		
			$aux = "";
			for ($i = 0; $i < $t; $i += 2)
				$aux = $s[$i] . ($i + 1 < $t ? $s[$i + 1] : "") . $aux;
		
			$str .= $aux;
		}
	
		return $str;
	}
	//2020-09-14: I should avoid use loops with mb_substr function, bc is much more slow than the substr function. But bc this loop is short, it's OK! However if I decide to use this method with long values in the $string var, I may need to change this code...
	/*public static function unshuffle1($string, $options = false) {
		$str = "";
		$t = mb_strlen($string);
		
		if ($t) {
			$middle = ceil($t / 2);
			$f = mb_substr($string, -$middle);
			$s = mb_substr($string, 0, $t - $middle);
			$t = mb_strlen($s);
		
			for ($i = $middle - 1; $i >= 0; $i -= 3)
				$str .= mb_substr($f, $i, 1) . ($i - 2 >= 0 ? mb_substr($f, $i - 2, 1) : "") . ($i - 1 >= 0 ? mb_substr($f, $i - 1, 1) : "");
		
			$aux = "";
			for ($i = 0; $i < $t; $i += 2)			
				$aux = mb_substr($s, $i, 1) . mb_substr($s, $i + 1, 1) . $aux;
		
			$str .= $aux;
		}
	
		return $str;
	}*/
	
	//Splits the string in chunks of 3 starting from the last char in a descending loop. For each chunk create a string with [-1] [-2] [0] and then add the next chunk.
	//To understand this better, suffle 'est1234@gmail.com'
	public static function shuffle2($string, $options = false) {
		$str = "";
		$string_chars = TextSanitizer::mbStrSplit($string);
		$t = count($string_chars);
		
		for ($i = $t - 1; $i >= 0; $i -= 3)
			$str .= ($i - 1 >= 0 ? $string_chars[$i - 1] : "") . ($i - 2 >= 0 ? $string_chars[$i - 2] : "") . $string_chars[$i];
		
		return $str;
	}
	//2020-09-14: I should avoid use loops with mb_substr function, bc is much more slow than the substr function. But bc this loop is short, it's OK! However if I decide to use this method with long values in the $string var, I may need to change this code...
	/*public static function shuffle2($string, $options = false) {
		$str = "";
		$t = mb_strlen($string);
	
		//2020-09-14: I should avoid use loops with mb_substr function, bc is much more slow than the substr function. But bc this loop is short, it's OK! However if I decide to use this method with long values in the $string var, I may need to change this code...
		for ($i = $t - 1; $i >= 0; $i -= 3)
			$str .= ($i - 1 >= 0 ? mb_substr($string, $i - 1, 1) : "") . ($i - 2 >= 0 ? mb_substr($string, $i - 2, 1) : "") . mb_substr($string, $i, 1);
		
		return $str;
	}*/
	
	//Splits the string in chunks of 3 starting from the first char in a ascending loop. For each chunk create a string with [+1] [0] [+2] and then pre-add the next chunk. However if the last chunk doesn't have 3 chars, just return the chunk as it is.
	//To understand this better, unsuffle 'ocmli.mga43@1t2es' which corresponds to 'est1234@gmail.com'
	public static function unshuffle2($string, $options = false) {
		$str = "";
		$string_chars = TextSanitizer::mbStrSplit($string);
		$t = count($string_chars);
		
		for ($i = 0; $i < $t; $i += 3) {
			if ($i + 3 <= $t)
				$str = ($i + 1 < $t ? $string_chars[$i + 1] : "") . $string_chars[$i] . ($i + 2 < $t ? $string_chars[$i + 2] : "") . $str;
			else
				$str = $string_chars[$i] . $str;
		}
		
		return $str;
	}
	//2020-09-14: I should avoid use loops with mb_substr function, bc is much more slow than the substr function. But bc this loop is short, it's OK! However if I decide to use this method with long values in the $string var, I may need to change this code...
	/*public static function unshuffle2($string, $options = false) {
		$str = "";
		$t = mb_strlen($string);
	
		for ($i = 0; $i < $t; $i += 3) {
			if ($i + 3 <= $t)
				$str = mb_substr($string, $i + 1, 1) . mb_substr($string, $i, 1) . mb_substr($string, $i + 2, 1) . $str;
			else
				$str = mb_substr($string, $i) . $str;
		}
		
		return $str;
	}*/
	
	//Splits string in chunks of 2 characters and for each chunk flips both chars.
	//To understand this better, suffle 'est1234@gmail.com'
	public static function shuffle3($string, $options = false) {
		$str = "";
		$string_chars = TextSanitizer::mbStrSplit($string);
		$t = count($string_chars);
		
		for ($i = 0; $i < $t; $i += 2)
			$str .= ($i + 1 < $t ? $string_chars[$i + 1] : "") . $string_chars[$i];
		
		return $str;
	}
	//2020-09-14: I should avoid use loops with mb_substr function, bc is much more slow than the substr function. But bc this loop is short, it's OK! However if I decide to use this method with long values in the $string var, I may need to change this code...
	/*public static function shuffle3($string, $options = false) {
		$str = "";
		$t = mb_strlen($string);
	
		for ($i = 0; $i < $t; $i += 2)
			$str .= mb_substr($string, $i + 1, 1) . mb_substr($string, $i, 1);
		
		return $str;
	}*/
	
	//Call it again to unshuffle
	//To understand this better, unsuffle 'se1t32@4mgia.locm' which corresponds to 'est1234@gmail.com'
	public static function unshuffle3($string, $options = false) {
		return self::shuffle3($string, $options);
	}
	
	//Reverse
	public static function shuffle4($string, $options = false) {
		$str = "";
		$string_chars = TextSanitizer::mbStrSplit($string);
		$t = count($string_chars);
		
		for ($i = $t - 1; $i >= 0; $i--)
			$str .= $string_chars[$i];
		
		return $str;
	}
	//2020-09-14: I should avoid use loops with mb_substr function, bc is much more slow than the substr function. But bc this loop is short, it's OK! However if I decide to use this method with long values in the $string var, I may need to change this code...
	/*public static function shuffle4($string, $options = false) {
		$str = "";
		$t = mb_strlen($string);
		
		for ($i = $t - 1; $i >= 0; $i--)
			$str .= mb_substr($string, $i, 1);
		
		return $str;
	}*/
	
	//Reverse again
	public static function unshuffle4($string, $options = false) {
		return self::shuffle4($string, $options);
	}
	
	//Splits in half and reverse first half
	public static function shuffle5($string, $options = false) {
		$str = "";
		$string_chars = TextSanitizer::mbStrSplit($string);
		$t = count($string_chars);
		
		if ($t) {
			$middle = ceil($t / 2);
			$f = array_slice($string_chars, 0, $middle);
			$s = array_slice($string_chars, $middle);
			
			for ($i = $middle - 1; $i >= 0; $i--)
				$str .= $f[$i];
			
			$str .= implode("", $s);
		}
	
		return $str;
	}
	//2020-09-14: I should avoid use loops with mb_substr function, bc is much more slow than the substr function. But bc this loop is short, it's OK! However if I decide to use this method with long values in the $string var, I may need to change this code...
	/*public static function shuffle5($string, $options = false) {
		$str = "";
		$t = mb_strlen($string);
		
		if ($t) {
			$middle = ceil($t / 2);
			$f = mb_substr($string, 0, $middle);
			$s = mb_substr($string, $middle);
			
			for ($i = $middle - 1; $i >= 0; $i--)
				$str .= mb_substr($f, $i, 1);
			
			$str .= $s;
		}
	
		return $str;
	}*/
	
	//Splits in half and reverse first half
	public static function unshuffle5($string, $options = false) {
		return self::shuffle5($string, $options);
	}
}

//To test uncomment this lines:
/*$s1 = TextShuffler::autoShuffle("joao pinto");
$s2 = TextShuffler::autoShuffle("ana pinto");
$s3 = TextShuffler::autoShuffle("xana pinto");
$s4 = TextShuffler::autoShuffle("aThis is a big test where my name is João António Lopes Pinto. What the fuck...");
$s5 = TextShuffler::autoShuffle("jThis is a big test where my name is João António Lopes Pinto. What the fuck...");
$s6 = TextShuffler::autoShuffle("zThis is a big test where my name is João António Lopes Pinto. What the fuck...");
$s7 = TextShuffler::autoShuffle("aJoão António");
$s8 = TextShuffler::autoShuffle("jJoão António");
$s9 = TextShuffler::autoShuffle("zJoão António");
$s10 = TextShuffler::autoShuffle("+João António");
$s11 = TextShuffler::autoShuffle("{João António");
$s12 = TextShuffler::autoShuffle("admin");

echo "\n";
echo "!$s1:".TextShuffler::autoUnshuffle($s1)."!\n";
echo "!$s2:".TextShuffler::autoUnshuffle($s2)."!\n";
echo "!$s3:".TextShuffler::autoUnshuffle($s3)."!\n";
echo "!$s4:".TextShuffler::autoUnshuffle($s4)."!\n";
echo "!$s5:".TextShuffler::autoUnshuffle($s5)."!\n";
echo "!$s6:".TextShuffler::autoUnshuffle($s6)."!\n";
echo "!$s7:".TextShuffler::autoUnshuffle($s7)."!\n";
echo "!$s8:".TextShuffler::autoUnshuffle($s8)."!\n";
echo "!$s9:".TextShuffler::autoUnshuffle($s9)."!\n";
echo "!$s10:".TextShuffler::autoUnshuffle($s10)."!\n";
echo "!$s11:".TextShuffler::autoUnshuffle($s11)."!\n";
echo "!$s12:".TextShuffler::autoUnshuffle($s12)."!\n";*/

/*echo "test123@gmail.com:t".TextShuffler::shuffle1("est123@gmail.com")."!\n";
echo "tom.cilmag@231ste:t".TextShuffler::unshuffle1("om.cilmag@231ste")."!\n\n";
$s12 = TextShuffler::shuffle2("ofia Andrade");
echo "!S$s12:S".TextShuffler::unshuffle2($s12)."!\n";
$s12 = TextShuffler::autoShuffle("Test");
echo "!$s12:".TextShuffler::autoUnshuffle($s12)."!\n";*/

/*$s12 = TextShuffler::autoShuffle("test1234@gmail.com");
echo "!$s12:".TextShuffler::autoUnshuffle($s12)."!\n";
$s12 = TextShuffler::autoShuffle("JPLP4686@gmail.com");
echo "!$s12:".TextShuffler::autoUnshuffle($s12)."!\n";
$s12 = TextShuffler::autoShuffle("jplp4686@gmail.com");
echo "!$s12:".TextShuffler::autoUnshuffle($s12)."!\n\n";

$s12 = TextShuffler::autoShuffle("test1234@gmail.com", array("disable_email" => 1));
echo "!$s12:".TextShuffler::autoUnshuffle($s12, array("disable_email" => 1))."!\n";
$s12 = TextShuffler::autoShuffle("JPLP4686@gmail.com", array("disable_email" => 1));
echo "!$s12:".TextShuffler::autoUnshuffle($s12, array("disable_email" => 1))."!\n";
$s12 = TextShuffler::autoShuffle("jplp4686@gmail.com", array("disable_email" => 1));
echo "!$s12:".TextShuffler::autoUnshuffle($s12, array("disable_email" => 1))."!\n";
$s12 = TextShuffler::autoShuffle("test123@gmail.com", array("disable_email" => 1));
echo "!$s12:".TextShuffler::autoUnshuffle($s12, array("disable_email" => 1))."!\n";
$s12 = TextShuffler::autoShuffle("t@gmail.com", array("disable_email" => 1));
echo "!$s12:".TextShuffler::autoUnshuffle($s12, array("disable_email" => 1))."!\n";
$s12 = TextShuffler::autoShuffle("@gmail.com", array("disable_email" => 1));
echo "!$s12:".TextShuffler::autoUnshuffle($s12, array("disable_email" => 1))."!\n";*/
/*
$s12 = TextShuffler::autoShuffle("+987654321153");
echo "!$s12:".TextShuffler::autoUnshuffle($s12)."!\n";*/
//echo "!$s12:".TextShuffler::autoUnshuffle("jtnopaiam@gmail.com")."!\n";
?>
