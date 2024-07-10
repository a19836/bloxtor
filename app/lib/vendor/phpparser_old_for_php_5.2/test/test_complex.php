<?php
require __DIR__ . '/../lib/bootstrap.php';

ini_set('xdebug.max_nesting_level', 2000);

// Disable XDebug var_dump() output truncation
/*ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
ini_set('xdebug.var_display_max_depth', -1);*/

// MyNodeVisitor class:
use PhpParser\Node;

class MyNodeVisitor extends PhpParser\NodeVisitorAbstract
{
    public function leaveNode(Node $node) {
        if ($node instanceof Node\Scalar\String) {
            $node->value = 'foo';
        }
    }
}


$parser = new PhpParser\Parser(new PhpParser\Lexer\Emulative);
$traverser     = new PhpParser\NodeTraverser;
$prettyPrinter = new PhpParser\PrettyPrinter\Standard;

// add your visitor
$traverser->addVisitor(new MyNodeVisitor);

$code = '<?php
class C {
	private $x;
	
	public function __construct() {
		$this->x = "1";
	}
	
	public function getX() {return $this->x;}
}

$C = new C();
$y = $C->getX() + 1 * 100;
echo "y:$y\n";
?>';

$stmts = null;
try {
	 // parse
	$stmts = $parser->parse($code);

	// traverse
	//$stmts = $traverser->traverse($stmts);

	// pretty print
	//$code = $prettyPrinter->prettyPrintFile($stmts);
	
	// printing
	print_r($stmts);
	echo "==> Pretty print:\n";
	//echo $code . "\n";
	
	foreach ($stmts as $stmt) {
		if ($stmt->getType() == "Expr_Assign") {
			echo "NAME:" . $stmt->var->name . " == " . $stmt->expr->getType() . "\n";
			switch($stmt->expr->getType()) {
				case "Expr_New":
					print_r($stmt->expr->class->parts); 
					print_r($stmt->expr->args); 
					break;
				case "Expr_BinaryOp_Plus":
					echo "LEFT AND RIGHT:" . $stmt->expr->left->getType() . " == " . $stmt->expr->right->getType() . "\n";
					print_r($stmt->expr->left);
					print_r($stmt->expr->right);
					break;
			}
		}
		
		echo $prettyPrinter->prettyPrint(array($stmt)), "\n";
	}
} catch (PhpParser\Error $e) {
	die("==> Parse Error: {$e->getMessage()}\n");
}
?>
