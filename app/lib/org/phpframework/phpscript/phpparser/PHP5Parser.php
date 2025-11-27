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

class PHP5Parser extends PhpParser\Parser\Php5 {
	/**
	  * Creates a parser instance.
	  *
	  * @param Lexer $lexer A lexer
     * @param array $options Options array. The boolean 'throwOnError' option determines whether an exception should be
     *                       thrown on first error, or if the parser should try to continue parsing the remaining code
     *                       and build a partial AST.
	  */
	public function __construct(PhpParser\Lexer $lexer, array $options = array()) {
		parent::__construct($lexer, $options);
	}
}
?>
