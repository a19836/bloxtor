<?php
/*
Files that I use this class:
- lib/org/phpframework/workflow/WorkFlowTaskCodeParser.php
- lib/org/phpframework/layer/presentation/cms/CMSFileHandler.php
*/
	
class PHPParserPrettyPrinter extends PHPParserPrettyPrinterStandard {
	
	public function disableNoIndentToken() {
		if (PHPPARSER_EMULATIVE_VERSION == "52_56" || PHPPARSER_EMULATIVE_VERSION == "52_71")
			$this->noIndentToken = ""; //only applies in versions: 52_56 and 52_71. 
	}
	
	// Special nodes

	//overright the pStmts method so it can indent with tabs instead of 4 spaces.
	/**
     * Pretty prints an array of nodes (statements) and indents them optionally.
     *
     * @param Node[] $nodes  Array of nodes
     * @param bool   $indent Whether to indent the printed nodes
     *
     * @return string Pretty printed statements
     */
	protected function printStmts(array $nodes, $indent = true, $with_comments = false) {
		if ($with_comments)
			$stmts = $this->getStmtsWithComments($nodes);
		//print_r($stmts);die();
		
		$result = parent::pStmts($nodes, false);
		
		if ($indent) {
			if (PHPPARSER_EMULATIVE_VERSION == "52_56" || PHPPARSER_EMULATIVE_VERSION == "52_71")
				$token_to_replace = $this->noIndentToken;
			else
				$token_to_replace = str_repeat(' ', $this->indentLevel);
			
			$result = preg_replace('~\n(?!$|' . $token_to_replace . ')~', "\n\t", $result);
		}
		
		//revert comments attributes to empty array - note that objects are passed by referenced
		if ($with_comments)
			$this->getStmtsWithoutComments($stmts);
		
		return $result;
	}
	
 	public function printParam(PhpParser\Node\Param $node) {
		return parent::pParam($node);
	}

	public function printArg(PhpParser\Node\Arg $node) {
		return parent::pArg($node);
	}

	public function printConst(PhpParser\Node\Const_ $node) {
		return parent::pConst($node);
	}
	
	// Names

	public function printName(PhpParser\Node\Name $node) {
		return parent::pName($node);
	}

	public function printIdentifier(PhpParser\Node\Identifier $node) {
		return method_exists($this, 'pIdentifier') ? parent::pIdentifier($node) : null;
	}
	
	// Comments

	public function printComments(array $comments) {
		if (empty($this->nl))
			parent::resetState(); //init nl var, otherwise we get a php error
		
		return parent::pComments($comments);
	}
	
	// nl
	
	public function getNL() {
		if (empty($this->nl))
			parent::resetState(); //init nl var, otherwise we get a php error
		
		return $this->nl;
	}
	
	// Stmts
	
	/**
     * Pretty prints an array of statements.
     *
     * @param Node[] $stmts Array of statements
     *
     * @return string Pretty printed statements
     */
	public function stmtsPrettyPrint(array $stmts, $with_comments = false) {
		if ($with_comments)
			$stmts = $this->getStmtsWithComments($stmts);
		//print_r($stmts);die();
		
		$str = parent::prettyPrint($stmts);
		
		//revert comments attributes to empty array - note that objects are passed by referenced
		$this->getStmtsWithoutComments($stmts);
		
		return $str;
	}
	
	/**
     * Pretty prints an expression.
     *
     * @param Expr $node Expression node
     *
     * @return string Pretty printed node
     */
	public function nodeExprPrettyPrint(PhpParser\Node\Expr $node, $with_comments = false) {
		if ($with_comments)
			$node = $this->getStmtWithComments($node);
		//print_r($node);die();
		
		$str = parent::prettyPrintExpr($node);
		
		//revert comments attributes to empty array - note that objects are passed by referenced
		$this->getStmtWithoutComments($node);
		
		return $str;
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
		if ($node->hasAttribute("my_comments")) {
			$comments = $node->getAttribute("my_comments");
			
			if ($comments) {
				$node->setAttribute("comments", $comments);
				$node->setAttribute("my_comments", null);
			}
		}
		
		return $node;
	}
	
	protected function getStmtsWithoutComments(array $nodes) {
		foreach ($nodes as $i => &$node) {
			if (is_array($node))
				$node = $this->getStmtsWithoutComments($node);
			else if ($node instanceof PhpParser\Node)
				$node = $this->getStmtWithoutComments($node);
		}
		
		return $nodes;
	}
	
	protected function getStmtWithoutComments(PhpParser\Node $node) {
		foreach ($node->getSubNodeNames() as $name) {
			$subNode =& $node->$name;

			if (is_array($subNode))
				$subNode = $this->getStmtsWithoutComments($subNode);
			else if ($subNode instanceof PhpParser\Node)
				$subNode = $this->getStmtWithoutComments($subNode);
		}
		
		//add comments again that were removed from the PHPParserTraverserNodeVisitor::leaveNode method
		if ($node->hasAttribute("comments")) {
			$comments = $node->getAttribute("comments");
			
			if ($comments) {
				$node->setAttribute("my_comments", $comments);
				$node->setAttribute("comments", array());
			}
		}
		
		return $node;
	}
}
?>
