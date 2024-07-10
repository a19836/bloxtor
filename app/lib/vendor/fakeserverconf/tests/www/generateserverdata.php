<?php

$domainName = 'http://testapp.local';
header('Content-type: text/plain');

$urls = array(
    array('/index.php', '/'),
    array('/index.php', '/index.php'),
    array('/index.php', '/index.php/foo/bar'),
    array('/index.php', '/index.php/foo/bar?baz=2'),
    array('/index.php', '/index'),
    array('/index.php', '/index/foo/bar'),
    array('/index.php', '/index/foo/bar?baz=2'),
    array('/info/index.php', '/info/'),
    array('/info/index.php', '/info/index.php'),
    array('/info/index.php','/info/index.php/foo/bar'),
    array('/info/index.php','/info/index.php/foo/bar?baz=2'),
    array('/info/index.php','/info/index'),
    array('/info/index.php','/info/index/foo/bar'),
    array('/info/index.php', '/info/index/foo/bar?baz=2'),
    array('/index.php', '/index', 'post', array('aaa'=>'bbb')),
    array('/index.php', '/index/foo/bar', 'post',array('aaa'=>'bbb')),
 );

echo '/*
system: '.php_uname().'
SAPI: '.php_sapi_name().'
PHP VERSION: '.phpversion().'
*/
';

foreach ($urls as $k=>$url) {
    $isbody = count($url) > 2;
    if ($isbody) {
        list($scriptName, $path, $method, $body) = $url;
    }
    else {
        list($scriptName, $path) = $url;
    }
    
    $ch = curl_init($domainName.$path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($isbody) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
    }
    $result = curl_exec($ch);
    curl_close($ch);
    if ($isbody) {
        echo '
        function testUrl'.($k+1).'() {
            $server = $this->getFakeServer(\''.$scriptName.'\',\''.$domainName.$path.'\',\''.$method.'\',\''.http_build_query($body).'\');
'.$result.'
        }
        ';
    }
    else {
        echo '
        function testUrl'.($k+1).'() {
            $server = $this->getFakeServer(\''.$scriptName.'\',\''.$domainName.$path.'\');
'.$result.'
        }
        ';
    }
}

