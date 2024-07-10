<?php
require __DIR__ . '/../lib/bootstrap.php';

ini_set('xdebug.max_nesting_level', 2000);

// Disable XDebug var_dump() output truncation
/*ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
ini_set('xdebug.var_display_max_depth', -1);*/

$parser = new PhpParser\Parser(new PhpParser\Lexer\Emulative);
$prettyPrinter = new PhpParser\PrettyPrinter\Standard;

$code = '<?php
class C {
	private $x;
	
	public function __construct() {
		$this->x = 1;
	}
	
	public function getX() {return $this->x;}
}

$C = new C();
$y = $C->getX() + 1;
echo "y:$y\n";
?>';

$stmts = null;
try {
	$stmts = $parser->parse($code);
} catch (PhpParser\Error $e) {
	die("==> Parse Error: {$e->getMessage()}\n");
}

print_r($stmts);
echo "==> Pretty print:\n";
echo $prettyPrinter->prettyPrintFile($stmts), "\n";
?>
