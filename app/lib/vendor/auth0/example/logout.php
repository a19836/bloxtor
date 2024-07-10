<?php
require dirname(__DIR__) . '/autoload.php';
require __DIR__ . '/settings.php';

$domain = $_ENV['AUTH0_DOMAIN'];
$client_id = $_ENV['AUTH0_CLIENT_ID'];

$auth0 = new Auth0\SDK\Auth0([
    'domain' => $domain,
    'client_id' => $client_id,
    'redirect_uri' => $_ENV['AUTH0_CALLBACK_URL'],
]);

$auth_api = new \Auth0\SDK\API\Authentication( $domain, $client_id );

$auth0->logout();

setcookie("auth0_code", $code, time() - 10);
unset($_COOKIE["auth0_code"]);

$return_to = $_ENV['AUTH0_CALLBACK_URL'];
header('Location: ' . $auth_api->get_logout_link($return_to, $client_id));
die;
