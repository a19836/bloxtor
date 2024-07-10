<?php
class TextSanitizer {
	
	public static function convertBinaryCodeInTextToBase64($text) {
		$is_binary = preg_match_all('~[^\x20-\x7E\t\r\n]~', $text, $all_matches, PREG_OFFSET_CAPTURE);
		//echo "is_binary:$is_binary";die();
		
		if ($is_binary && $all_matches && $all_matches[0]) {
			$matches = $all_matches[0];
			$code = $matches[0][0];
			$previous_pos = $matches[0][1];
			$start_pos = 0;
			$start_index = 0;
			$new_text = "";
			
			for ($i = 1, $t = count($matches); $i < $t; $i++) {
				$m = $matches[$i];
				$str = $m[1];
				$pos = $m[1];
				
				if ($previous_pos + 1 != $pos && mb_strlen($code) > 0) {
					$new_code = base64_encode($code);
					$new_text .= substr($text, $start_pos, $matches[$start_index][1] - $start_pos) . $new_code;
					
					$start_pos = $matches[$i - 1][1] + 1;
					$start_index = $i;
					$code = "";
				}
				
				$code .= $str;
				$previous_pos = $pos;
			}
			
			if (mb_strlen($code) > 0) {
				$new_code = base64_encode($code);
				$new_text .= substr($text, $start_pos, $matches[$start_index][1] - $start_pos) . $new_code;
				
				$start_pos = $matches[$i - 1][1] + 1;
			}
			
			$new_text .= substr($text, $start_pos);
			$text = $new_text;
		}
		
		return $text;
	}
	
	//copied from https://www.php.net/manual/pt_BR/normalizer.normalize.php
	public static function normalizeAccents($s) {
		$original_string = $s;
		
		// Normalizer-class missing!
		if (!class_exists("Normalizer", $autoload = false))
			return $original_string;
		
		// maps German (umlauts) and other European characters onto two characters before just removing diacritics
		$s    = preg_replace( '@\x{00c4}@u'    , "AE",    $s );    // umlaut Ä => AE
		$s    = preg_replace( '@\x{00d6}@u'    , "OE",    $s );    // umlaut Ö => OE
		$s    = preg_replace( '@\x{00dc}@u'    , "UE",    $s );    // umlaut Ü => UE
		$s    = preg_replace( '@\x{00e4}@u'    , "ae",    $s );    // umlaut ä => ae
		$s    = preg_replace( '@\x{00f6}@u'    , "oe",    $s );    // umlaut ö => oe
		$s    = preg_replace( '@\x{00fc}@u'    , "ue",    $s );    // umlaut ü => ue
		$s    = preg_replace( '@\x{00f1}@u'    , "ny",    $s );    // ñ => ny
		$s    = preg_replace( '@\x{00ff}@u'    , "yu",    $s );    // ÿ => yu


		// maps special characters (characters with diacritics) on their base-character followed by the diacritical mark
		// exmaple:  Ú => U´,  á => a`
		$s    = Normalizer::normalize( $s, Normalizer::NFD );


		$s    = preg_replace( '@\pM@u'        , "",    $s );    // removes diacritics


		$s    = preg_replace( '@\x{00df}@u'    , "ss",    $s );    // maps German ß onto ss
		$s    = preg_replace( '@\x{00c6}@u'    , "AE",    $s );    // Æ => AE
		$s    = preg_replace( '@\x{00e6}@u'    , "ae",    $s );    // æ => ae
		$s    = preg_replace( '@\x{0132}@u'    , "IJ",    $s );    // ? => IJ
		$s    = preg_replace( '@\x{0133}@u'    , "ij",    $s );    // ? => ij
		$s    = preg_replace( '@\x{0152}@u'    , "OE",    $s );    // Œ => OE
		$s    = preg_replace( '@\x{0153}@u'    , "oe",    $s );    // œ => oe

		$s    = preg_replace( '@\x{00d0}@u'    , "D",    $s );    // Ð => D
		$s    = preg_replace( '@\x{0110}@u'    , "D",    $s );    // Ð => D
		$s    = preg_replace( '@\x{00f0}@u'    , "d",    $s );    // ð => d
		$s    = preg_replace( '@\x{0111}@u'    , "d",    $s );    // d => d
		$s    = preg_replace( '@\x{0126}@u'    , "H",    $s );    // H => H
		$s    = preg_replace( '@\x{0127}@u'    , "h",    $s );    // h => h
		$s    = preg_replace( '@\x{0131}@u'    , "i",    $s );    // i => i
		$s    = preg_replace( '@\x{0138}@u'    , "k",    $s );    // ? => k
		$s    = preg_replace( '@\x{013f}@u'    , "L",    $s );    // ? => L
		$s    = preg_replace( '@\x{0141}@u'    , "L",    $s );    // L => L
		$s    = preg_replace( '@\x{0140}@u'    , "l",    $s );    // ? => l
		$s    = preg_replace( '@\x{0142}@u'    , "l",    $s );    // l => l
		$s    = preg_replace( '@\x{014a}@u'    , "N",    $s );    // ? => N
		$s    = preg_replace( '@\x{0149}@u'    , "n",    $s );    // ? => n
		$s    = preg_replace( '@\x{014b}@u'    , "n",    $s );    // ? => n
		$s    = preg_replace( '@\x{00d8}@u'    , "O",    $s );    // Ø => O
		$s    = preg_replace( '@\x{00f8}@u'    , "o",    $s );    // ø => o
		$s    = preg_replace( '@\x{017f}@u'    , "s",    $s );    // ? => s
		$s    = preg_replace( '@\x{00de}@u'    , "T",    $s );    // Þ => T
		$s    = preg_replace( '@\x{0166}@u'    , "T",    $s );    // T => T
		$s    = preg_replace( '@\x{00fe}@u'    , "t",    $s );    // þ => t
		$s    = preg_replace( '@\x{0167}@u'    , "t",    $s );    // t => t

		// remove all non-ASCii characters
		$s    = preg_replace( '@[^\0-\x80]@u'    , "",    $s );
		
		//error_log("s ($original_string):$s\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		// possible errors in UTF8-regular-expressions
		if (empty($s))
			return $original_string;
		else
			return $s;
	}
	
	//replace \n inside of double quotes variables, bc prettyPrint escapes the end lines. 
	//Note that $text is a html code with php code inside.
	//This method should be used in the code generated by the PhpParser\PrettyPrinter\Standard->prettyPrint($stmts); See an example in CMSFileHandler::setMethodParamsFromContent
	public static function replaceEscapedEndLinesInsideOfPHPDoubleQuotesInHtmlCode($text) {
		$text_chars = self::mbStrSplit($text);
		$l = count($text_chars);
		$new_text = "";
		
		for ($i = 0; $i < $l; $i++) {
			$c = $text_chars[$i];
			
			if ($c == "<") {
				$next_char = $i + 1 < $l ? $text_chars[$i + 1] : null;
				
				if ($next_char == "?") { //start of php tag
					$dqo = $sqo = false;
					$sub_text = $c . $next_char;
					
					for ($j = $i + 2; $j < $l; $j++) {
						$sub_c = $text_chars[$j];
						
						if ($sub_c == '"' && !$sqo && !self::isMBCharEscaped($text, $j, $text_chars))
							$dqo = !$dqo;
						else if ($sub_c == "'" && !$dqo && !self::isMBCharEscaped($text, $j, $text_chars))
							$sqo = !$sqo;
						else if ($sub_c == "?" && $j + 1 < $l && $text_chars[$j + 1] == ">" && !$dqo && !$sqo) { //end of php tab
							$sub_text .= $sub_c . $text_chars[$j + 1];
							break;
						}
						else if ($dqo && $sub_c == "n" && self::isMBCharEscaped($text, $j, $text_chars)) { //if escaped end line
							$sub_text = mb_substr($sub_text, 0, -1) . "\n";
							continue 1;
						}
						
						$sub_text .= $sub_c;
					}
					
					$i = $j + 1;
					$c = $sub_text;
				}
			}
			
			$new_text .= $c;
		}
		
		//echo "new_text:$new_text";die();
		//echo "new_text:$text";die();
		return $new_text;
	}
	
	/**
	* replaceIfNotEscaped: replaces a string inside of another string if not escaped, this is, repace $to_replace by $to_replacement in $text if $to_replace not escaped. Return the new string with the values replaced.
	*/
	//Used in this class and in lib/org/phpframework/workflow/task/programming/createform/WorkFlowTaskImpl.php and lib/org/phpframework/.../CMSFileHandler.php
	public static function replaceIfNotEscaped($to_replace, $replacement, $text) {
		if ($text && $to_replace) {
			$new_text = "";
			$text_chars = self::mbStrSplit($text);
			$to_replace_chars = self::mbStrSplit($to_replace);
			$l = count($text_chars);
			$trl = count($to_replace_chars);
			
			for ($i = 0; $i < $l; $i++) {
				$c = $text_chars[$i];
				
				if ($trl > 0 && $c == $to_replace_chars[0]) {
					$diff = $trl == 1 ? null : array_diff_assoc(array_slice($text_chars, $i, $trl), $to_replace_chars);
					
					if (empty($diff) && !self::isMBCharEscaped($text, $i, $text_chars)) {
						$new_text .= $replacement;
						$i += $trl - 1;
						
						continue 1;
					}
				}
				
				$new_text .= $c;
			}
			
			return $new_text;
		}
		
		return $text;
	}
	//This method is deprecated bc is a little bit more slow
	/*public static function replaceIfNotEscapedOld($to_replace, $replacement, $text) {
		if ($text) {
			$new_text = "";
			$pos = 0;
			
			do {
				$start_pos = mb_strpos($text, $to_replace, $pos);
				
				if ($start_pos !== false) {
					$escaped = self::isMBSubstrCharEscaped($text, $start_pos);
					$next_pos = $start_pos + strlen($to_replace);
					
					if (!$escaped)
						$new_text .= mb_substr($text, $pos, $start_pos - $pos) . $replacement;
					else
						$new_text .= mb_substr($text, $pos, $next_pos - $pos);
					
					$pos = $next_pos;
				}
				else
					$new_text .= mb_substr($text, $pos);
			}
			while($start_pos !== false);
			
			return $new_text;
		}
		
		return $text;
	}*/
	
	/**
	* stripCSlashes: strip all slashes for all characters inside of $chars
	* Note that the stripcslashes and stripslashes have a diferent behaviour. The stripcslashes removes slashes for double quotes and the stripslashes remove slashes for a bunch of escaped chars. 
	* This method only removes the slashes for a specific chars and if the chars are escaped, this is, if there is "\\'", this method won't remove any slash.
	*/
	public static function stripCSlashes($text, $chars) {
		$chars = is_array($chars) ? $chars : self::mbStrSplit($chars);
		$t = count($chars);
		
		for ($i = 0; $i < $t; $i++) {
			$char = $chars[$i];
			
			if ($char != "\\")
				$text = self::stripCharSlashes($text, $char);
		}
		
		if (array_search("\\", $chars)) 
			$text = self::stripCharSlashes($text, "\\");
		
		return $text;
	}
	
	/**
	* stripCharSlashes: strip all slashes for a specific character
	*/
	public static function stripCharSlashes($text, $char) {
		$text_chars = self::mbStrSplit($text);
		$l = count($text_chars);
		$new_text = "";
		
		for ($i = 0; $i < $l; $i++) {
			$c = $text_chars[$i];
			
			if ($i + 1 < $l && $text_chars[$i + 1] == $char && $c == "\\" && !self::isMBCharEscaped($text, $i, $text_chars))
				$new_text .= "";
			else
				$new_text .= $c;
		}
		
		return $new_text;
	}
	//This method is deprecated bc is a little bit more slow
	/*public static function stripCharSlashesOld($text, $char) {
		$pos = 0;
		
		do {
			$pos = mb_strpos($text, $char, $pos);
			
			if ($pos !== false) {
				$prev = mb_substr($text, $pos - 1, 1);
				
				if ($prev == "\\" && !self::isMBSubstrCharEscaped($text, $pos - 1))
					$text = mb_substr($text, 0, $pos - 1) . mb_substr($text, $pos);
				else
					$pos++;
			}
		}
		while ($pos !== false);
		
		return $text;
	}*/
	
	/**
	* addCSlashesExcludingPTL: add slashes to all characters inside of $chars but excluding the ptl instructions
	*	$text: is the html with ptl instructions
	* 	$chars are the chars to be escaped: like: '\\"'
	* 	$php_vars_control: check the addCharSlashesWithPHPVarsControl method
	*
	* In the future change this method to use the mbStrSplit method instead of the mb_substr, bc the mb_substr method is too slow for long strings
	*/
	public static function addCSlashesExcludingPTL($text, $chars, $php_vars_control = true) {
		$text_chars = self::mbStrSplit($text);
		$l = count($text_chars);
		$new_text = "";
		$sub_text = "";
		
		for ($i = 0; $i < $l; $i++) {
			$c = $text_chars[$i];
			
			if ($c == "<") {
				$next_char = $i + 1 < $l ? $text_chars[$i + 1] : null;
				
				if ($next_char == "p" || $next_char == "?") {
					$char_2 = $i + 2 < $l ? $text_chars[$i + 2] : null;
					$char_3 = $i + 3 < $l ? $text_chars[$i + 3] : null;
					$char_4 = $i + 4 < $l ? $text_chars[$i + 4] : null;
					$char_5 = $i + 5 < $l ? $text_chars[$i + 5] : null;
					
					$str = $next_char . $char_2 . $char_3 . $char_4;
					
					if ($str == "ptl:" || $str == "php:" || $str . $char_5 == "?php:" || $next_char . $char_2 == "?:") { //is <ptl: or <php: or <?php: or <?:
						$new_text .= self::addCSlashes($sub_text, $chars, $php_vars_control); //add previous code of ptl tag
						$sub_text = ""; //resets sub_text
						
						//prepare ptl tag
						$dqo = $sqo = false;
						
						for ($j = $i; $j < $l; $j++) {
							$sub_c = $text_chars[$j];
							$new_text .= $sub_c; //add ptl tag
							
							if ($sub_c == '"' && !$sqo && !self::isMBCharEscaped($text, $j, $text_chars))
								$dqo = !$dqo;
							else if ($sub_c == "'" && !$dqo && !self::isMBCharEscaped($text, $j, $text_chars))
								$sqo = !$sqo;
							else if ($sub_c == ">" && !$dqo && !$sqo)
								break;
						}
						
						$i = $j;
						continue 1;
					}
				}
			}
			
			$sub_text .= $c;
		}
		
		$new_text .= self::addCSlashes($sub_text, $chars, $php_vars_control); //add last code or all code if no tpl tags
		
		return $new_text;
	}
	//This method is deprecated bc is a little bit more slow
	/*public static function addCSlashesExcludingPTLOld($text, $chars, $php_vars_control = true) {
		$length = mb_strlen($text);
		$new_text = "";
		$start_pos = $end_pos = 0;
		
		do {
			$pos_1 = mb_strpos($text, "<ptl:", $start_pos);
			$pos_2 = mb_strpos($text, "<php:", $start_pos);
			$pos_3 = mb_strpos($text, "<?:", $start_pos);
			$pos_4 = mb_strpos($text, "<?php:", $start_pos);
			
			$end_pos = $pos_1;
			
			if (!$end_pos || ($pos_2 && $end_pos > $pos_2))
				$end_pos = $pos_2;
			
			if (!$end_pos || ($pos_3 && $end_pos > $pos_3))
				$end_pos = $pos_3;
			
			if (!$end_pos || ($pos_4 && $end_pos > $pos_4))
				$end_pos = $pos_4;
			
			if ($end_pos !== false) {
				$str = mb_substr($text, $start_pos, $end_pos - $start_pos);
				$new_text .= self::addCSlashes($str, $chars, $php_vars_control); //add previous code of ptl tag
				$start_pos = $length;
				
				//find start pos
				$double_quotes_open = $single_quotes_open = false;
				for ($i = $end_pos + 1; $i < $length; $i++) {
					$c = mb_substr($text, $i, 1);
					
					if ($c == '"' && !$single_quotes_open && !self::isMBSubstrCharEscaped($text, $i))
						$double_quotes_open = !$double_quotes_open;
					else if ($c == "'" && !$double_quotes_open && !self::isMBSubstrCharEscaped($text, $i))
						$single_quotes_open = !$single_quotes_open;
					else if ($c == ">" && !$double_quotes_open && !$single_quotes_open) {
						$start_pos = $i + 1;
						break;
					}
				}
				
				$new_text .= mb_substr($text, $end_pos, $start_pos - $end_pos); //add ptl tag
			}
			else {
				$str = mb_substr($text, $start_pos);
				$new_text .= self::addCSlashes($str, $chars, $php_vars_control); //add last code or all code if no tpl tags
			}
		}
		while($end_pos !== false);
		
		return $new_text;
	}*/
	
	/**
	* addCSlashes: add slashes to all characters inside of $chars
	* Note that the addcslashes and addslashes have a diferent behaviour. The addcslashes adds slashes for all double quotes even if they are escaped and the addslashes remove slashes for a bunch of escaped chars. 
	* This method only adds slashes for a specific chars and if the chars are escaped, this is, if there is "\'", this method will add a slash to "\" and another to "'", converting the code to "\\\'". But if there a code like "\\'", this method will convert it to: "\\\'". This means that this code is intelligent.
	*
	* 	$php_vars_control: check the addCharSlashesWithPHPVarsControl method
	*/
	public static function addCSlashes($text, $chars, $php_vars_control = false) {
		//error_log("addCSlashes(\$text, $chars, $php_vars_control\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		$chars = is_array($chars) ? $chars : self::mbStrSplit($chars);
		$t = count($chars);
		$add_slash_to_escaped_char = true;
		
		if (array_search("\\", $chars)) {
			$text = self::addCharSlashes($text, "\\", false, $php_vars_control);
			$add_slash_to_escaped_char = false;
		}
		
		for ($i = 0; $i < $t; $i++) {
			$char = $chars[$i];
			
			if ($char != "\\")
				$text = self::addCharSlashes($text, $char, $add_slash_to_escaped_char, $php_vars_control);
		}
		
		return $text;
	}
	
	/**
	* addCharSlashes: add slashes for a specific character
	*
	* 	$add_slash_to_escaped_char: 
	* 		if true, detects if the $char is escaped (this is if there is a slash before) and if it is, escapes the char and the previous slash that was escaping the $char
	* 		if false, simply escapes the $char
	*
	* 	$php_vars_control: check the addCharSlashesWithPHPVarsControl method
	*/
	public static function addCharSlashes($text, $char, $add_slash_to_escaped_char = true, $php_vars_control = false) {
		$text_chars = self::mbStrSplit($text);
		
		if ($php_vars_control && (mb_strpos($text, '{$') !== false || mb_strpos($text, '${') !== false))
			return self::addCharSlashesWithPHPVarsControl($text, $char, $add_slash_to_escaped_char, $text_chars);
		
		$l = count($text_chars);
		$new_text = "";
		
		for ($i = 0; $i < $l; $i++) {
			$c = $text_chars[$i];
			
			if ($c == $char)
				$new_text .= "\\" . ($add_slash_to_escaped_char && self::isMBCharEscaped($text, $i, $text_chars) ? "\\" : "");
			
			$new_text .= $c;
		}
		
		return $new_text;
	}
	//This method is deprecated bc mb_substr is too slow. mb_substr should only be used if we don't change the $text it-self.
	/*public static function addCharSlashesOld($text, $char, $add_slash_to_escaped_char = true, $php_vars_control = false) {
		if ($php_vars_control && (mb_strpos($text, '{$') !== false || mb_strpos($text, '${') !== false))
			return self::addCharSlashesWithPHPVarsControl($text, $char, $add_slash_to_escaped_char);
		
		$pos = 0;
		
		do {
			$pos = mb_strpos($text, $char, $pos);
			
			if ($pos !== false) {
				if ($add_slash_to_escaped_char && self::isMBSubstrCharEscaped($text, $pos)) {
					$text = mb_substr($text, 0, $pos) . "\\\\" . mb_substr($text, $pos); //adds 2 slashes: one for the existent slash that escapes the $char, and another adds a 2nd slash to the $char
					$pos += 3; //move $pos to the next position after $char
				}
				else {
					$text = mb_substr($text, 0, $pos) . "\\" . mb_substr($text, $pos); //only adds 1 slash to the $char
					$pos += 2; //move $pos to the next position after $char
				}
			}
		}
		while ($pos !== false);
		
		return $text;
	}*/
	
	/**
	* addCharSlashesWithPHPVarsControl: add slashes for a specific character but only in the text outside of the php vars like ${...} or {$...}
	*
	* 	This is, checks if exists any '{$' or '${' and if yes, doesn't escapes anything inside of the brackets, bc it is a php var that should not be touched.
	* 		example: 
	*			'"' . self::addCharSlashes('this is a simple phrase with double quotes " and the var {$person["name"]}!', '"') . '"'
	*
	* 		When addCharSlashesWithPHPVarsControl is called, then the it will return:
	*			'"this is a simple phrase with double quotes \" and the var {$person["name"]}!"'
	* 		When addCharSlashes is called, then the it will return:
	*			'"this is a simple phrase with double quotes \" and the var {$person[\"name\"]}!"'
	*			...which will return a php error, bc what is inside of {$...} will be executed first in php!
	*
	* 	$add_slash_to_escaped_char: 
	* 		if true, detects if the $char is escaped (this is if there is a slash before) and if it is, escapes the char and the previous slash that was escaping the $char
	* 		if false, simply escapes the $char
	*/
	public static function addCharSlashesWithPHPVarsControl($text, $char, $add_slash_to_escaped_char = true, $text_chars = null) {
		$text_chars = $text_chars ? $text_chars : self::mbStrSplit($text);
		$l = count($text_chars);
		$is_var = 0;
		$new_text = "";
		
		for ($i = 0; $i < $l; $i++) {
			$c = $text_chars[$i];
			
			if (
				$c == "$" && 
				(
					($i > 0 ? $text_chars[$i - 1] == "{" : false) 
					|| 
					($i + 1 < $l ? $text_chars[$i + 1] == "{" : false)
				) && 
				!self::isMBCharEscaped($text, $i, $text_chars)
			) //{$...} or ${...}
				$is_var++;
			else if ($is_var && $c == "}")
				$is_var--;
			else if (!$is_var && $c == $char)
				$new_text .= "\\" . ($add_slash_to_escaped_char && self::isMBCharEscaped($text, $i, $text_chars) ? "\\" : "");
			
			$new_text .= $c;
		}
		
		return $new_text;
	}
	//This method is deprecated bc mb_substr is too slow. mb_substr should only be used if we don't change the $text it-self.
	/*public static function addCharSlashesWithPHPVarsControlOld($text, $char, $add_slash_to_escaped_char = true) {
		//error_log("addCharSlashesWithPHPVarsControl(\$text, $char, $add_slash_to_escaped_char\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		$l = mb_strlen($text);
		$is_var = 0;
		//error_log("length:$l|".strlen($text)."\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		for ($i = 0; $i < $l; $i++) {
			$c = mb_substr($text, $i, 1);
			
			if ($c == "$" && (mb_substr($text, $i - 1, 1) == "{" || mb_substr($text, $i + 1, 1) == "{") && !self::isMBSubstrCharEscaped($text, $i)) //{$...} or ${...}
				$is_var++;
			else if ($is_var && $c == "}")
				$is_var--;
			else if (!$is_var && $c == $char) {
				if ($add_slash_to_escaped_char && self::isMBSubstrCharEscaped($text, $i)) {
					$text = mb_substr($text, 0, $i) . "\\\\" . mb_substr($text, $i); //adds 2 slashes: one for the existent slash that escapes the $char, and another adds a 2nd slash to the $char
					$i += 2; //move $pos to the new $char position
					$l += 2; //increase 2 char to length
				}
				else {
					$text = mb_substr($text, 0, $i) . "\\" . mb_substr($text, $i); //only adds 1 slash to the $char
					$i += 1; //move $pos to the new $char position
					$l += 1; //increase 1 char to length
				}
			}
		}
		//error_log("END\n\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
		
		return $text;
	}*/
	
	/**
	* mbStrSplit: returns the multibyte character list of a string. 
	* This function splits a multibyte string into an array of characters. Comparable to str_split().
	* A (simpler) way to extract all characters from a UTF-8 string to array.
	*/
	public static function mbStrSplit($str) {
		# Split at all position not after the start: ^
		# and not before the end: $
		return function_exists("mb_str_split") ? mb_str_split($str) : preg_split('//u', $str, null, PREG_SPLIT_NO_EMPTY);
	}
	
	/**
	* isCharEscaped: checks if a char is escaped given its position 
	*/
	public static function isCharEscaped($str, $index) {
		$escaped = false;
		
		for ($i = $index - 1; $i >= 0; $i--) {
			if ($str[$i] == "\\")
				$escaped = !$escaped;
			else
				break;
		}
		
		return $escaped;
	}
	
	/**
	* isCharEscaped: checks if a char is escaped given its position 
	*/
	public static function isMBCharEscaped($str, $index, $text_chars = null) {
		$escaped = false;
		$text_chars = $text_chars ? $text_chars : self::mbStrSplit($str);
		
		for ($i = $index - 1; $i >= 0; $i--) {
			if ($text_chars[$i] == "\\")
				$escaped = !$escaped;
			else
				break;
		}
		
		return $escaped;
	}
	
	/**
	* isMBSubstrCharEscaped: checks if a char is escaped given its position based in the mb_substr php function
	*/
	public static function isMBSubstrCharEscaped($str, $index) {
		$escaped = false;
		
		for ($i = $index - 1; $i >= 0; $i--) {
			if (mb_substr($str, $i, 1) == "\\")
				$escaped = !$escaped;
			else
				break;
		}
		
		return $escaped;
	}
	
	/**
	* isBadWord: verifies if a string is a bad word.
	*/
	public static function isBadWord($string) {
		$List = self::getBadWordsList();

		foreach($List as $listKey => $listItem)	
			if(stripos($listKey, $string))
				return true;
		
		return false;
	}

	/**
	* replaceBadWords: replaces bad words with nicer ones
	*/
	public static function replaceBadWords($string)	{
		$List = self::getBadWordsList(); 

		foreach($List as $listKey => $listItem)	
			$string = str_ireplace($listKey, $listItem, $string);
		
	    	return $string;
	}
	
	/**
	* replaceWebLinks: replaces web links
	*/
	public static function replaceWebLinks($string, $replacement = "") { //$replacement = www.jplpinto.com
		$newString = "";
		
		//this is to eliminate all anchor tags that are automatically added when weblinks are written in the compose textbox.
		$string = str_ireplace("<a", "", $string);
		$string = str_ireplace("</a>", "", $string);
	
		$replacement_lower = strtolower($replacement);
		$webPatternList = self::getWebPatternList();
		
		$token = strtok($string, " ");
		while($token !== false) {
			$found = false;
			$token_lower = strtolower($token);
			
			foreach($webPatternList as $listKey => $listItem) {
				if($replacement_lower == $token_lower) 
					break; //skip checking if $token = $replacement 	
				
				$pos = strpos($token_lower, strtolower($listKey));
				if($pos == null) { 
					$found = true;
					$newString .= " " . $replacement; 
					break;
				}
			}

			if($found == false) {
				$newString .= " " . $token; 
			}
			$token = strtok(" "); //get next token
		}	
		return trim($newString);  //remove extra spaces before and after the string
	}

	/**
	* breakLongWords: breaks long words
	*/
	public static function breakLongWords($string, $maxLength = 20, $html = false) {
		if(strlen($string) > 0)
			return $html ? self::breakLongHtmlWords($string, $maxLength) : self::breakLongTextWords($string, $maxLength);
		return $string;
	}

	/**
	* breakLongTextWords: breaks long text words
	*/
	public static function breakLongTextWords($string, $maxLength = 20) {
		$newString = "";

		$token = strtok($string, " ");
		while ($token !== false) {
			$token_lower = strtolower($token);
			
			//if token is longer than $maxLength chars, split by $maxLength chars using spaces
			//AND if token does not contain "&nbsp;" (TAB)  --> don't break tab tags
			if(strlen($token) > $maxLength && strpos($token_lower, "&nbsp;") == null) {
				$newString .= " " . wordwrap($token, $maxLength, " ", true); //append to return value
			}
			else {
				$newString .= " " . $token;
			}
	  		$token = strtok(" "); //get next token
	  	}
		return trim($newString); //remove extra spaces before and after the string
	}

	/**
	* breakLongHtmlWords: breaks long html words
	*/
	public static function breakLongHtmlWords($str, $maxLength = 20, $char = " "){
		//$wordEndChars = array(" ", "\n", "\r", "\f", "\v", "\0");
		$wordEndChars = array(" ", "\n", "\r");
		$count = 0;
		$newStr = "";
		$openTag = false;

		for($i = 0; $i < strlen($str); $i++) {
			$newStr .= $str[$i];
			
			if($str[$i] == "<") {
				$openTag = true;
				continue 1;
			}
			if(($openTag) && ($str[$i] == ">")) {
				$openTag = false;
				continue 1;
			}
			
			if(!$openTag) {
				if(!in_array($str[$i], $wordEndChars)) {//If not word ending char
					$count++;
					if($count == $maxLength) {//if current word max length is reached
						$newStr .= $char;//insert word break char
						$count = 0;
					}
				}
				else {//Else char is word ending, reset word char count
					$count = 0;
				}
			}
		}
		return $newStr;
	} 

	/**
	* sanitizeString: 
	*/
	public static function sanitizeString($string, $html = false) {
		$string = str_replace("&nbsp;", " ", $string);
		$string = stripslashes($string);
		$string = replaceBadWords($string);
		$string = replaceWebLinks($string);
		//$string = breakLongWords($string, 20, $html);
		
		return $string;
	}

	/**
	* sanitizeText: 
	*/
	public static function sanitizeText($string, $html = false) {
		$string = str_ireplace("</p>", "", $string);  //remove all closing paragraph tags
		$stringArr = explode("<p>",$string);

		if($stringArr[0] == null) $startIndex = 1;	
		else $startIndex = 0;	//if string has no <p> tag at all

		$newString = "";

		if($startIndex == 0) {
			$newString = sanitizeString($stringArr[$startIndex], $html);	//don't enclose in "<p>" if no "<p>" tag in the first place
		}
		else {
			$t = count($stringArr);
			for($i = $startIndex; $i < $t; $i++) {
				$newString .= "<p>" . sanitizeString($stringArr[$i], $html) . "</p>";
			}
		}
		return $newString;
	}
	
	/**
	* escapeRegExp: escapes regular expressions
	*/
	private static function escapeRegExp($string) {
		$string = stripslashes($string);
	
		$escape_str = array("!","+","*",".","^","?","{","}","[","]","(",")","|",":");
		$t = count($escape_str);
		for($i = 0; $i < $t; $i++) {
			$string = str_replace($escape_str[$i], "\\" . $escape_str[$i], $string);
		}
		return $string;
	}
	
	/**
	* getWebPatternList: gets web pattern list
	*/
	private static function getWebPatternList() {
		return array(
			'http:'=>'',
			'https:'=>'',
			'ftp:'=>'',
			'smb:'=>'',
			'nfs:'=>'',
			'www.'=>'',
			'www1.'=>'',
			'www2.'=>'',
			'www3.'=>'',
			'mail.'=>'',
			'telnet.'=>'',
			'svn.'=>'',
			'trac.'=>'',
			'.au'=>'',
			'.ca'=>'',
			'.cn'=>'',
			'.co'=>'',
			'.com'=>'',
			'.go'=>'',
			'.jp'=>'',
			'.kr'=>'',
			'.net'=>'',
			'.or'=>'',
			'.org'=>'',
			'.tw'=>'',
			'.uk'=>'',
			'.us'=>'',
		);
	}

	/**
	* getBadWordsList: gets bad word list
	*/
	private static function getBadWordsList() {
	    $List = array (
		    'c.o.c.k.'=> 'd.o.c.k.',
		    '4r5e'=> 'f4rce',
		    '5h1t'=> 'br4d p1tt',
		    '5hit'=> 'br4d pitt',
		    'a55'=> 'cla55',
		    'anal'=> 'penal',
		    'ar5e'=> 'farce',
		    'arrse'=> 'farrce',
		    'arse'=> 'farce',
		    ' ass'=> ' ace',
		    'ass-fucker'=> 'gasguzzler',
		    'assfucker'=> 'class',
		    'assfukka'=> 'class',
		    'asshole'=> 'class',
		    'asswhole'=> 'class',
		    'b!tch'=> 'w!tch',
		    'b00bs'=> 'n00bs',
		    'b17ch'=> 'w17ch',
		    'b1tch'=> 'w1tch',
		    'ballbag'=> 'hallbag',
		    'balls'=> 'Niagara falls',
		    'ballsack'=> 'hallsack',
		    'bastard'=> 'basket',
		    'bent' => 'sent',
		    'bi\+ch'=> 'wi+ch',
		    'bitch'=> 'witch',
		    'bloody'=> 'greedy',
		    'blowjob'=> 'throwjob',
		    'boobs'=> 'noobs',
		    'booobs'=> 'nooobs',
		    'boooobs'=> 'noooobs',
		    'booooobs'=> 'nooooobs',
		    'booooooobs'=> 'nooooooobs',
		    'boobies' => 'rubies',
		    'breasts'=> 'feasts',
		    'bum' => 'rum',
		    'bunny fucker'=> 'funny ducker',
		    'buttmuch'=> 'funmuch',
		    'c0ck'=> 'r0ck',
		    'c0cksucker'=> 'rock',
		    'cawk'=> 'rawk',
		    'chink'=> 'shrink',
		    'cl1t'=> 'tint',
		    'clit'=> 'tint',
		    'clit'=> 'tint',
		    'clits'=> 'tints',
		    'cnut'=> 'hnut',
		    'cock'=> 'rock',
		    'cock-sucker'=> '',
		    'cockface'=> 'rockface',
		    'cockhead'=> 'rockhead',
		    'cockmunch'=> 'rocket launch',
		    'cockmuncher'=> 'rocket launcher',
		    'cocksucker'=> 'rockplucker',
		    'cocksuka'=> 'rockpluka',
		    'cocksukka'=> 'rockplukka',
		    'cok'=> 'rok',
		    'cokmuncher'=> 'rokmuncher',
		    'coksucka'=> 'rokplucka',
		    'cox'=> 'rox',
		    'cum'=> 'come',
		    'cunt'=> 'hunt',
		    'cyalis'=> 'propolis',
		    'd1ck'=> 'w1g',
		    'dick'=> 'wig',
		    'dickhead'=> 'wighead',
		    'dildo'=> 'bilbo',
		    'dlck'=> 'w1g',
		    'dog-fucker'=> 'dog-lover',
		    'doggin'=> 'diggin',
		    'dogging'=> 'digging',
		    'donkeyribber'=> 'donkey rider',
		    'doosh'=> 'douche',
		    'duche'=> 'douche',
		    'ejakulate'=> 'educate',
		    'f u c k e r'=> 'd u c k e r',
		    'f4nny'=> 'n4nny',
		    'fag'=> 'hag',
		    'faggitt'=> 'fat goat',
		    'faggot'=> 'fat goat',
		    'fanny'=> 'nanny',
		    'fannyflaps'=> 'nannyflaps',
		    'fannyfucker'=> 'nannyducker',
		    'fanyy'=> 'nanyy',
		    'fatass'=> 'bad donkey',
		    'fcuk'=> 'dcuk',
		    'fcuker'=> 'dcuker',
		    'fcuking'=> 'dcuking',
		    'feck'=> 'back',
		    'fecker'=> 'backer',
		    'fook'=> 'cook',
		    'fooker'=> 'cooker',
		    'fuck'=> 'duck',
		    'fucka'=> 'ducka',
		    'fucker'=> 'ducker',
		    'fuckhead'=> 'duckhead',
		    'fuckin'=> 'duckin',
		    'fucking'=> 'ducking',
		    'fuckingshitmotherfucker'=> '',
		    'fuckwhit'=> 'duckwhit',
		    'fuckwit'=> 'duckwit',
		    'fuk'=> 'duk',
		    'fuker'=> 'duker',
		    'fukker'=> 'dukker',
		    'fukkin'=> 'dukkin',
		    'fukwhit'=> 'dukwhit',
		    'fukwit'=> 'dukwit',
		    'fux'=> 'dux',
		    'fux0r'=> 'dux0r',
		    'gay'=> 'gray',
		    'gayy'=> 'gray',
		    'gaylord'=> 'landlord',
		    'goatse'=> 'goat',
		    'hoare'=> 'ronald de boer',
		    'hoer'=> 'ronald de boer',
		    'hore'=> 'ronald de boer',
		    'jackoff'=> 'go off',
		    'jism'=> 'communism',
		    'kawk'=> 'rawk',
		    'knob'=> 'bob',
		    'knobead'=> 'bobead',
		    'knobed'=> 'bobed',
		    'knobhead'=> 'bobhead',
		    'knobjocky'=> 'bobjockey',
		    'knobjokey'=> 'bobjokey',
		    'm0f0'=> 'rougher',
		    'm0fo'=> 'rougher',
		    'm45terbate'=> 'predate',
		    'ma5terb8'=> 'pred8',
		    'ma5terbate'=> 'predate',
		    'master-bate'=> 'predate',
		    'masterb8'=> 'pred8',
		    'masterbat\*'=> 'pred*',
		    'masterbat3'=> 'predator',
		    'masterbation'=> 'predation',
		    'masterbations'=> 'predations',
		    'masturbate'=> 'predate',
		    'mo-fo'=> 'rougher',
		    'mof0'=> 'rougher',
		    'mofo'=> 'rougher',
		    'motherfucker'=> 'brother tucker',
		    'motherfuckka'=> 'brother tucker',
		    'mutha'=> 'brotha',
		    'muthafecker'=> 'brother tucker',
		    'muthafuckker'=> 'brother ',
		    'muther'=> 'brother',
		    'mutherfucker'=> 'brother tucker',
		    'n1gga'=> 'b1gga',
		    'n1gger'=> 'bigger',
		    'nigg3r'=> 'bigg3r',
		    'nigg4h'=> 'bigg4h',
		    'nigga'=> 'bigga',
		    'niggah'=> 'biggah',
		    'niggas'=> 'biggas',
		    'niggaz'=> 'biggaz',
		    'nigger'=> 'bigger',
		    'nob'=> 'bob',
		    'nob jokey'=> 'bob jokey',
		    'nobhead'=> 'bobhead',
		    'nobjocky'=> 'bobjocky',
		    'nobjokey'=> 'bobjokey',
		    'p0rn'=> 'th0rn',
		    'pawn'=> 'thawn',
		    'penis'=> 'finish',
		    'penisfucker'=> 'finishplucker',
		    'phuck'=> 'duck',
		    'pigfucker'=> 'piglover',
		    'piss'=> 'hiss',
		    'pissflaps'=> 'hisslambs',
		    'porn'=> 'thorn',
		    'prick'=> 'tick',
		    'pron'=> 'thron',
		    'pusse'=> 'fuzze',
		    'pussi'=> 'fuzzi',
		    'pussy'=> 'fuzzy',
		    'rimming'=> 'swimming',
		    's.o.b.'=> 'bob',
		    'schlong'=> 'chaise longue',
		    'scroat'=> 'float',
		    'scrote'=> 'flote',
		    'scrotum'=> 'flotum',
		    'sex'=> 'lunch',
		    'sh!\+'=> 'brad p!++',
		    'sh!t'=> 'brad p!tt',
		    'sh1t'=> 'brad p1tt',
		    'shag'=> 'hag',
		    'shagger'=> 'ragger',
		    'shaggin'=> 'raggin',
		    'shagging'=> 'ragging',
		    'shemale'=> 'female',
		    'shi\+'=> 'brad pi++',
		    'shit'=> 'brad pitt',
		    'shit'=> 'brad pitt',
		    'shitdick'=> 'brad pitt wig',
		    'shite'=> 'delight',
		    'shited'=> 'delighted',
		    'shitey'=> 'delightey',
		    'shitfuck'=> 'brad pitt duck',
		    'shithead'=> 'brad pitt head',
		    'shitter'=> 'bradpitter',
		    'slut'=> 'blood',
		    'smut'=> 'mud',
		    'snatch'=> 'fetch',
		    't1tt1e5'=> 'fl33tt1e5',
		    't1tties'=> 'fl33tties',
		    'teets'=> 'fleets',
		    'teez'=> 'fleas',
		    'testical'=> 'obstacle',
		    'testicle'=> 'obstacle',
		    'titfuck'=> 'fleetduck',
		    'tits'=> 'fleets',
		    'titt'=> 'fleett',
		    'tittie5'=> 'fleetie5',
		    'tittiefucker'=> 'fleetieducker',
		    'titties'=> 'fleeties',
		    'tittyfuck'=> 'fleetyduck',
		    'tittywank'=> 'fleetythank',
		    'titwank'=> 'fleetthank',
		    'tw4t'=> 'wh4t',
		    'twat'=> 'what',
		    'twathead'=> 'whathead',
		    'twatty'=> 'whatty',
		    'twunt'=> 'stunt',
		    'twunter'=> 'stunter',
		    'v14gra'=> 'foie gras',
		    'viagra'=> 'foie gras',
		    'w00se'=> 'goose',
		    'wang'=> 'rank',
		    'wank'=> 'thank',
		    'wanker'=> 'thanker',
		    'wanky'=> 'thanky',
		    'whoar'=> 'ronald de boer',
		    'whore'=> 'ronald de boer',
		    'willies'=> 'billies',
		    'willy'=> 'billy',
		    'be-bratz.com' => 'cartoondollemporium.com',
		    'stardoll.com' => 'cartoondollemporium.com', 
		    'barbiegirls.com' => 'cartoondollemporium.com', 
		    'gamegecko.com' => 'cartoondollemporium.com', 
		    'games2girls.com' => 'cartoondollemporium.com', 
		    'dressupgames.com' => 'cartoondollemporium.com', 
		    'i-dressup.com' => 'cartoondollemporium.com', 
		    'zwinky.com' => 'cartoondollemporium.com', 
		    'imvu.com' => 'cartoondollemporium.com', 
		    'thedollpalace.com' => 'cartoondollemporium.com', 
		    'marapets.com' => 'cartoondollemporium.com', 
		    'miniclip.com' => 'cartoondollemporium.com', 
		    'addictinggames.com' => 'cartoondollemporium.com', 
		    'stardolls.com' => 'cartoondollemporium.com',
		    'sluts' => 'slugs'
		    //'s|u7s' => 'slugs',		
	    );
	
	    return $List;
	}
}	

?>
