<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class PHP8Parser extends PhpParser\Parser\Php8 {
	/**
	  * Creates a parser instance.
	  *
	  * @param Lexer $lexer A lexer
     * @param PhpVersion $phpVersion PHP version to target, defaults to latest supported. This
     *                       option is best-effort: Even if specified, parsing will generally assume the latest
     *                       supported version and only adjust behavior in minor ways, for example by omitting
     *                       errors in older versions and interpreting type hints as a name or identifier depending
     *                       on version.
	  */
	public function __construct(PhpParser\Lexer $lexer, ?PhpVersion $phpVersion = null) {
		parent::__construct($lexer, $phpVersion);
	}
}
?>
