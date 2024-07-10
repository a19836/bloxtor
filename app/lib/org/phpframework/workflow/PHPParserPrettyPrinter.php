<?php
/*
Other methods that I use with this class but no need to replicate in it:
- PrettyPrinterAbstract->prettyPrint: is public method
- PrettyPrinterAbstract->prettyPrintExpr: is public method

Files that I use this class:
- lib/org/phpframework/workflow/WorkFlowTaskCodeParser.php
- lib/org/phpframework/layer/presentation/cms/CMSFileHandler.php
*/
	
class PHPParserPrettyPrinter extends PhpParser\PrettyPrinter\Standard {
	
	public function disableNoIndentToken() {
		$this->noIndentToken = "";
	}
	
	// Special nodes

	//overright the pStmts method so it can indent with tabs instead of 4 spaces.
	protected function pStmts(array $nodes, $indent = true) {
		$result = parent::pStmts($nodes, false);
		
		if ($indent)
			$result = preg_replace('~\n(?!$|' . $this->noIndentToken . ')~', "\n\t", $result);
		
		return $result;
	}
	
    	public function pParam(PhpParser\Node\Param $node) {
		return parent::pParam($node);
	}

	public function pArg(PhpParser\Node\Arg $node) {
		return parent::pArg($node);
	}

	public function pConst(PhpParser\Node\Const_ $node) {
		return parent::pConst($node);
	}
	
	// Names

	public function pName(PhpParser\Node\Name $node) {
		return parent::pName($node);
	}
	
	// Stmts
	
	public function prettyPrint(array $stmts, $with_comments = false) {
		if ($with_comments)
			$stmts = $this->getStmtsWithComments($stmts);
		//print_r($stmts);die();
		
		return parent::prettyPrint($stmts);
	}
	
	protected function getStmtsWithComments(array $nodes) {
		foreach ($nodes as $i => &$node) {
			if (is_array($node))
				$node = $this->getStmtsWithComments($node);
			else if ($node instanceof PhpParser\Node)
				$node = $this->getStmtWithComments($node);
		}
		
		return $nodes;
	}
	
	protected function getStmtWithComments(PhpParser\Node $node) {
		foreach ($node->getSubNodeNames() as $name) {
			$subNode =& $node->$name;

			if (is_array($subNode))
				$subNode = $this->getStmtsWithComments($subNode);
			else if ($subNode instanceof PhpParser\Node)
				$subNode = $this->getStmtWithComments($subNode);
		}
		
		//add comments again that were removed from the PHPParserTraverserNodeVisitor::leaveNode method
		$comments = $node->getAttribute("my_comments");
		
		if ($comments) {
			$node->setAttribute("comments", $comments);
			$node->setAttribute("my_comments", null);
		}
		
		return $node;
	}
}
?>
