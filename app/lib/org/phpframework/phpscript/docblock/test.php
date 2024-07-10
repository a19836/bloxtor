<?php
//in terminal: php /home/jplpinto/Desktop/phpframework/trunk/app/lib/org/phpframework/phpscript/docblock/test.php
//include __DIR__ . "/../../util/import/lib.php"; //Only for testing purposes, if get_lib not defined
include get_lib("org.phpframework.phpscript.docblock.DocBlockParser");

// Define and get a list of all our test functions
/*
$fnsAll = get_defined_functions();
foo();
$fnsTest = get_defined_functions();
$fnsTest = array_diff($fnsTest['user'], $fnsAll['user']);

// Dump
$annotations = array_combine($fnsTest, array_map(array('DocBlockParser', 'ofFunction'), $fnsTest));
print_r($annotations["b"]);
*/

foo();
$DocBlockParser = new DocBlockParser();
$DocBlockParser->ofFunction("bar");
//print_r($DocBlockParser->getTags());
//print_r($DocBlockParser->getObjects());die();

$input = array(
	"id" => 1,
	"age" => "as",
	"full_name" => null,
	"options" => array(
		"no_cache" => null
	),
);
$output = "asd";
$status1 = $DocBlockParser->checkInputMethodAnnotations($input);
$status2 = $DocBlockParser->checkOutputMethodAnnotations($output);
echo "Status:$status1:$status2";
print_r($DocBlockParser->getObjects());
print_r($input);
print_r($DocBlockParser->getTagParamsErrors());
print_r($DocBlockParser->getTagReturnErrors());

function foo() {
 
  /**
   * The Bar function
   * @return bool|int Whether or not something is true
   * @params (
   * 	@param int id,
   * 	@param (name=age, tYPe=int, NotNull, @MinLength(1), default="18"),
   * 	@param (@Default(joao pinto), @NotNull),
   * 	@param (name=options[no_cache], type=bool, NotNull, default=false),
   * )
   */
  function bar ($id, $age, $full_name, $options = false) {}
  
  
  function a () {}
  
  /**
   * This is a test 
   * Bla bla
   * @param "joao"
   */
  /**
   * The B function
   * Theis is B function
   * @return
   * @return bool|mixed|string
   * @return bool Whether or not something is true
   * @param(na21me="joao", [1, 
   *	@foo string joaj 
   * ,12], data = {"a":1});

   * asdxas as 
   * @params (
   * 	@param string $param1,
   * 	@param (name=param2, tYPe=int, @NotNull, @MinLength(1), default="asd"),
   * 	@param (@Default(123))
   * )
   * 
   * @type [1, 2] 
   * @param String $var
   *        Here's a wee description about the variable.
   *        Grand.
   * @RequestMapping(value = "/url", method = RequestMethod.GET)
   * @Target({ElementType.FIELD, ElementType.PARAMETER})
   * @param (name=age, @Valid, @RequestBody, @NotNull, @MinLength(1), @MaxLength(20))
   */
  function b () {}
  
  /**
   * @param
   * @param String
   * @param String $var
   * @param String $var
   *        Here's a wee description about the variable.
   *        Grand.
   */
  function c () {}
  
  /**
   * This is a multiline description.
   * 
   * These are often used when the developer wants to go into slightly more
   * detail into how a method functions in particular circumstances or elaborate
   * on a particular outcome of calling this function.
   * 
   * @author Paul Scott <paul@duedil.com>
   */
  function d () {}
}

function get_lib($path) {
	$lib_path = dirname(dirname(dirname(dirname(__DIR__)))) . "/";
	return $lib_path . str_replace(".", "/", $path) . ".php";	
}

function launch_exception($e) {
	throw $e;
}
?>
