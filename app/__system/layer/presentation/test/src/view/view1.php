<?php
$head =	'<title>View 1</title>';

$left_content = "LEFT CONTENT";
$main_content = "{$result}<br>";

/*if(isset($result[0]) && is_array($result[0])) {
	$main_content .= '<table>';
	$columns = array_keys($result[0]);
	$t = count($columns);
	for($i = 0; $i < $t; $i++) {
		$main_content .= '<th>'.$columns[$i].'</th>';
	}
	$t = count($result);
	for($i = 0; $i < $t; $i++) {
		$item = $result[$i];

		$main_content .= '<tr>';
		foreach($item as $key => $value) {
			$main_content .= '<td>'.$value.'</td>';
		}
		$main_content .= '</tr>';
	}
	$main_content .= '</table>';
}
else {
	var_dump($result);
	print_r($result);
}*/

$main_content .= "<hr/><h1>HOME</h1>";

$ws = new PresentationLayerWebService($PHPFrameWork, array("presentation_id" => "TEST", "url" => "home/name/pinto"));
$main_content .= $ws->callWebServicePage();

$main_content .= "<hr/>";
?>
