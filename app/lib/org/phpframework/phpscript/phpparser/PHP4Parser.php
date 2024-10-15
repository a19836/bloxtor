<?php
class PHP4Parser extends PhpParser\Parser {
	
	/**
     * Creates a parser instance.
     *
     * @param Lexer $lexer A lexer
     */
	public function __construct(PhpParser\Lexer $lexer) {
		parent::__construct($lexer);
	}
}
?>
