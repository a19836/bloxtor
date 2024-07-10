<?php
use PhpParser\Node;

class PHPParserTraverserNodeVisitor extends PhpParser\NodeVisitorAbstract {

	public function leaveNode(Node $node) {
		$comments = $node->getAttribute("comments");
		
		if ($comments) {
			$node->setAttribute("comments", array());
			$node->setAttribute("my_comments", $comments);
		}
	}
}
?>
