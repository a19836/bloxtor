<?php
$p = $o = 0;

$func = function foo($x = 2) {
	$y = $x;
	return $y + $x;
}

class Foo implements Y, X extends W {
	var $x;
	private $bar1 = "asd \$o asd";
	public static $bar2;
	private static $bar3;
	const bar4 = 12;
	protected $a = array(1, ";", 2);
	
	public function __construct($d=123) {
		$this->bar1 = $d;
		
		self::$bar2 = 12;
		
		$test = $this->a = "bla \\\$this->x self::\$bar2 self::bar4 \$this->a \$d xxxx self::\$bar3 123213 \$this->bar1 ble self :: bar1() \$this -> getClass(\$c) \$GLOBALS[test]->getName() X::getName() \$as->getName()";
		$other = 'bla $this->x self::$bar2 self::bar4 $this->a $d xxxx self::$bar3 123213 $this->bar1 ble self :: bar1() $this -> getClass($c) $GLOBALS[test]->getName() X::getName() $as->getName()';
	}
	
	//some comments to be purged
	private function bar1($b) {
		return self::$bar3 + $b;
	}
	
	/* Some other comments */
	public function bar2 ($x=false) {
		return self::$bar2 + $x;
	}
	
	protected $p;
	
	/**
	 * Some DOC comments
	 */
	private function getClass() {
		$x = new X();
		
		return X::t();
	}
}

interface I {	
	public function setLogLevel($v471bf34077);
}

class X {
	private $y = 3;

	private function y($t) {
		$w = $i = $t;
		return $this->y ? $t : $this->funcWithEval($t);
	}
	
	public static function t() {
		return Foo::$bar2;
	}
	
	private function funcWithEval($t = array(23)) {
		global $func;
		
		$some = $var = 23;
		$other = 1;
		
		$this->y(isset($t[0]) ? $t[0] : null);
		
		eval("echo \$other . \$some " . ' $other $some;');
	
		
		function bar() {
			global $o, $p;
			
			$x = 0;
			return $func() . $o . $p;
		}

		$p = bar(); //Note that $p will be obfuscated, bc is local in this scope.
		
		$reflector = new ReflectionMethod($class, $method);
		$this->of($reflector);
		
		$this->y = array_map( function($ReflectionParameter) { 
		        return $ReflectionParameter->getName(); 
		   }, $reflector->getParameters());
		
		return d();
	}
	
	public function cloneTask() {
		$var_y = "y";
		echo $this->$var_y;
	
		eval ('$WorkFlowTaskClone = new ' . get_class($this) . '();');
		
		if (!empty($WorkFlowTaskClone)) {
			$WorkFlowTaskClone->setTaskClassInfo( $this->y );
			$WorkFlowTaskClone->y = $this->y;
		}
		
		if (!empty($WorkFlowTaskClone)) 
			return true;
		return $WorkFlowTaskClone;
	}
}

function d() {
	$d = "JP";
	global $p, $o;
?>
	<div class="something not a varialbe $asd">
		<span>
		<?= X::t($d) ?>
		</span>
	</div>
<?

	echo "bla $o asd:" . $p;
}

$w = $func() . $_SERVER["HTTP_HOST"];
$q = "<html> \$o
	<body>
		<h1>" . foo($w) . "</h1>
		<h4>$w</h4>
		<h5>{$w}</h5>
	</body>
</html>";
?>
