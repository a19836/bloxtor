<?php
//http://jplpinto.localhost/__system/test/tests/rss

//MyRSS test
include_once get_lib("org.phpframework.util.xml.MyRSS");
include_once get_lib("org.phpframework.util.xml.MyXMLArray");

$url = "https://rss.art19.com/apology-line";
$MyRSS = new MyRSS($url);
$raw = $MyRSS->getRSSObject(false);
$res = $MyRSS->getRSSObject();

$MyXMLArray = new MyXMLArray($raw);
echo "itunes:owner:email:" . $MyXMLArray->getNodeValue("rss/channel/itunes:owner/itunes:email") . "<br/>";
echo "itunes:keywords:" . $MyXMLArray->getNodeValue("rss/channel/itunes:keywords") . "<br/>";
echo "title 1:" . $MyXMLArray->getNodeValue("rss/channel/item[1]/title") . "<br/>";
echo "title 2:" . $MyXMLArray->getNodeValue("rss/channel/item[2]/title") . "<br/>";
echo "title 3:" . $MyXMLArray->getNodeValue("rss/channel/item[3]/title") . "<br/>";

$items = $MyXMLArray->getNodes("rss/channel/item/title");
$items = MyXML::complexChildsArrayToBasicArray($items, array("convert_without_attributes" => true));
echo "All rss/channel/item/title nodes:<pre>".print_r($items, 1)."</pre><br/>";

//echo "RAW:<pre>".print_r($raw, 1)."</pre><br/>";

$discard_nodes = array("copyright"); //this is only an example
$beautify_res = MyXML::complexArrayToBasicArray($raw, array("convert_without_attributes" => true, "discard_nodes" => $discard_nodes));
echo "RSS Obj without attributes and only values:<pre>".print_r($beautify_res, 1)."</pre><br/>";

echo "RSS Original Obj:<pre>".print_r($res, 1)."</pre><br/>";
?>
