<?php
//http://localhost/springloops/phpframework/trunk/tests/xml_array

//MyXMLArray test
include_once get_lib("org.phpframework.util.xml.MyXMLArray");
$xml = "
<RESULT>
	<TEST name=\"test_node_ehe\">
		<Y id=\"1012\">
			<X id=\"1\">JP1</X>
			<X id=\"2\">JP2</X>
			<X id=\"3\">JP3</X>
		</Y>
	</TEST>
	<TEST>
		<Y id=\"2225\">
			<X id=\"1\">LP1</X>
			<X id=\"2\">LP2</X>
		</Y>
	</TEST>
</RESULT>";
$MyXML = new MyXML($xml);
$MyXMLArray = new MyXMLArray($MyXML->toArray());
$node = $MyXMLArray->getNodes("RESULT/TEST[1]/Y/X[@id=2 or @id=3]");
//$node = $MyXMLArray->getNodes('RESULT/TEST[1]/Y[ @id != 2225 ]/X[@id=2]');
//$node = $MyXMLArray->getNodes('RESULT/TEST/Y[ @id >= 1000 and @id < 5000 ]');

echo "<pre>";
print_r($node);
echo "</pre><br><br>";
?>
