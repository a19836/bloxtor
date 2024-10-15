<?php
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
