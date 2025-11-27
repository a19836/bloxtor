<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

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
