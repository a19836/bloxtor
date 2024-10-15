<?php
use PhpParser\Node;

class PHPParserTraverserNodeVisitor extends PHPParserNodeVisitorAbstract {

	public function leaveNode(Node $node) {
		$comments = $node->getAttribute("comments");
		
		if ($comments) {
			$node->setAttribute("comments", array());
			$node->setAttribute("my_comments", $comments);
		}
		
		//return null: $node stays as-is
		//return $node: $node is set to the return value
		return null; //or return $node;
	}
}
?>
