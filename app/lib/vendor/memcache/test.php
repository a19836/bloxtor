<?php
/**
 * Tests for nsmemcache
 *
 * @author Savu Andrei
 */

include 'NSMemcache.php';

$mc = new NSMemcache();

$mc->connect('localhost');

print "set: ns1(a)=1, ns2(b)=2\n";
$mc->ns_set('ns1', 'a', 1);
$mc->ns_set('ns2', 'b', 2);

print 'get: ns1(a)=';
var_dump($mc->ns_get('ns1', 'a'));

print 'get: ns2(b)=';
var_dump($mc->ns_get('ns2', 'b'));

print "flush: ns1\n";
$mc->ns_flush('ns1'); 

print 'get: ns1(a)=';
var_dump($mc->ns_get('ns1', 'a'));

print 'get: ns2(b)=';
var_dump($mc->ns_get('ns2', 'b'));


?>
