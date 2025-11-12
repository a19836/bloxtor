<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class PHP7Parser extends PhpParser\Parser\Php7 {
	/**
	  * Creates a parser instance.
	  *
	  * @param Lexer $lexer A lexer
     * @param $aux: It could be:
     * 	- an Options array. The boolean 'throwOnError' option determines whether an exception should be
     *                       thrown on first error, or if the parser should try to continue parsing the remaining code
     *                       and build a partial AST.
     * 	- or a PHP version to target, defaults to latest supported. This
     *                       option is best-effort: Even if specified, parsing will generally assume the latest
     *                       supported version and only adjust behavior in minor ways, for example by omitting
     *                       errors in older versions and interpreting type hints as a name or identifier depending
     *                       on version.
	  */
	public function __construct(PhpParser\Lexer $lexer, $aux = null) {
		if (!$aux && !is_array($aux) && (PHPPARSER_EMULATIVE_VERSION == "52_71" || PHPPARSER_EMULATIVE_VERSION == "52_82"))
			$aux = array();
		
		parent::__construct($lexer, $aux);
	}
}
?>
