<?php
class PHPParserPrettyPrinterStandard extends PhpParser\PrettyPrinter\Standard {
	
	//used in the org.phpframework.layer.presentation.cms.CMSFileHandler file
	/**
     * Pretty prints an array of statements.
     *
     * @param Node[] $stmts Array of statements
     *
     * @return string Pretty printed statements
     */
	public function nativeStmtsPrettyPrint(array $stmts) {
		return parent::prettyPrint($stmts);
	}
}
?>
