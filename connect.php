<?php
// get request token and try to authorize

session_start();
require_once(dirname(__FILE__) . '/classes/Config.php');
require_once(dirname(__FILE__) . '/classes/Twitter.php');

$twitter = new Twitter(Config::CONSUMER_KEY, Config::CONSUMER_KEY_SECRET);

$token = $twitter->getRequestToken(Config::OAUTH_CALLBACK);

$_SESSION['oauth_token'] = $token['oauth_token'];
$_SESSION['oauth_token_secret'] = $token['oauth_token_secret'];

if($twitter->getLastHttpCode() == 200)
{
    $twitter->authorize($token['oauth_token']);
}
else
{
    print "ERROR";
}