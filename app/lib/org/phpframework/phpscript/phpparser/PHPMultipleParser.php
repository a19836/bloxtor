<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class PHPMultipleParser {
	
	private $Parser;
	
	/**
     * Create a parser which will try multiple parsers in an order of preference.
     *
     * Parsers will be invoked in the order they're provided to the constructor. If one of the
     * parsers runs without errors, it's output is returned. Otherwise the errors (and
     * PhpParser\Error exception) of the first parser are used.
     */
	public function __construct() {
		if (!defined("PHPPARSER_EMULATIVE_VERSION"))
			$this->Parser = null;
		else if (PHPPARSER_EMULATIVE_VERSION == "52_84") {
			$this->Parser = (new PhpParser\ParserFactory())->createForNewestSupportedVersion();
		}
		else if (PHPPARSER_EMULATIVE_VERSION == "52_82" || PHPPARSER_EMULATIVE_VERSION == "52_71") {
			$PHPParser5 = new PHP5Parser(new PHPParserLexerEmulative);
			$PHPParser7 = new PHP7Parser(new PHPParserLexerEmulative);
			
			$this->Parser = new PhpParser\Parser\Multiple(array($PHPParser5, $PHPParser7));
		}
		elseif (PHPPARSER_EMULATIVE_VERSION == "52_56") {
			$this->Parser = new PHP4Parser(new PHPParserLexerEmulative);
		}
		else
			$this->Parser = null;
	}
	
	public function parse($code) {
		return $this->Parser ? $this->Parser->parse($code) : null;
	}
}
?>
