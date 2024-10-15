<?php
class PHPParserNodeTraverser extends PhpParser\NodeTraverser {
	
	/**
     * Adds a visitor.
     *
     * @param NodeVisitor $visitor Visitor to add
     */
	public function addNodeTraverserVisitor(PhpParser\NodeVisitor $visitor) {
		parent::addVisitor($visitor);
	}
	
	/**
     * Traverses an array of nodes using the registered visitors.
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return Node[] Traversed array of nodes
     */
	public function nodesTraverse(array $nodes) {
		return parent::traverse($nodes);
	}
}
?>
