<?php
//http://localhost/springloops/phpframework/trunk/tests/my_array
//http://jplpinto.localhost/__system/test/tests/my_array

//MyArray test
include_once get_lib("org.phpframework.util.MyArray");

$data = array('table1' => array());
$data['table1'][] = array("name" => "Sebastian", "age" => 18, "male" => true);
$data['table1'][] = array("name" => "Lawrence",  "age" => 16, "male" => true);
$data['table1'][] = array("name" => "Olivia",    "age" => 10, "male" => false);
$data['table1'][] = array("name" => "Dad",       "age" => 50, "male" => true);
$data['table1'][] = array("name" => "Mum",       "age" => 40, "male" => false);
$data['table1'][] = array("name" => "Sebastian", "age" => 56, "male" => true);
$data['table1'][] = array("name" => "Lawrence",  "age" => 19, "male" => true);
$data['table1'][] = array("name" => "Olivia",    "age" => 24, "male" => false);
$data['table1'][] = array("name" => "Dad",       "age" => 10, "male" => true);
$data['table1'][] = array("name" => "Mum",       "age" => 70, "male" => false);

$keys = array(array('key'=>'name', 'case_sensitive'=>1), array('key'=>'age', 'sort'=>'desc'));

$res = MyArray::multisort($data['table1'], $keys);

echo "<pre>";
print_r($res);
echo "</pre><br><br>";
?>
