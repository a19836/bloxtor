<?php
require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/settings.php';

$auth0 = new Auth0\SDK\Auth0([
    'domain' => $_ENV['AUTH0_DOMAIN'],
    'client_id' => $_ENV['AUTH0_CLIENT_ID'],
    'client_secret' => $_ENV['AUTH0_CLIENT_SECRET'],
    'redirect_uri' => $_ENV['AUTH0_CALLBACK_URL'],
]);

$code = $_GET['code'] ? $_GET['code'] : $_COOKIE["auth0_code"];

// Handle errors sent back by Auth0.
if (! empty($_GET['error']) || ! empty($_GET['error_description'])) {
    $error_html = "Error: \n" . htmlspecialchars($_GET['error_description']);
}
else if ($code) { 
	try {
		$userInfo = $auth0->getUser();
		
		setcookie("auth0_code", $code);
	}
	catch (Exception $e) {
		$error_html = $e->getMessage() . "\n" . $e->getTraceAsString();
	}
}
?>
<html>
    <head>
        <script src="//code.jquery.com/jquery-3.1.0.min.js" type="text/javascript"></script>

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- font awesome from BootstrapCDN -->
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
        <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css" rel="stylesheet">

        <link href="public/app.css" rel="stylesheet">

    </head>
    <body class="home">
        <div class="container">
            <pre class="text-left"><?php echo $error_html; ?></pre>
            
            <div class="login-page clearfix">
              <?php if(!$userInfo): ?>
              <div class="login-box auth0-box before">
                <!--img src="https://i.cloudup.com/StzWWrY34s.png" />
                <h3>Auth0 Example</h3>
                <p>Zero friction identity infrastructure, built for developers</p-->
                <a id="qsLoginBtn" class="btn btn-primary btn-lg btn-login btn-block" href="login.php">Sign In</a>
              </div>
              <?php else: ?>
              <div class="logged-in-box auth0-box logged-in" id="profileDropDown">
                <!--h1 id="logo"><img src="//cdn.auth0.com/samples/auth0_logo_final_blue_RGB.png" /></h1-->
                <img class="avatar" src="<?php echo $userInfo['picture'] ?>"/>
                <h2>Welcome <span class="nickname"><?php echo $userInfo['nickname'] ?></span></h2>
                
                <a id="qsLogoutBtn" class="btn btn-warning btn-logout" href="logout.php">Logout</a>
                <pre class="text-left" style="margin-top:20px;"><?php print_r($_GET);print_r($userInfo); ?></pre>
              </div>
              <?php endif ?>
            </div>
        </div>
    </body>
</html>
