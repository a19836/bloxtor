<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
