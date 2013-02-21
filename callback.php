<?php

// start session and load api client
session_start();
require_once(dirname(__FILE__) . '/classes/Config.php');
require_once(dirname(__FILE__) . '/classes/Twitter.php');

if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
    header('Location: logout.php');
}

$twitter = new Twitter(Config::CONSUMER_KEY, Config::CONSUMER_KEY_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

$token = $twitter->getAccessToken($_REQUEST['oauth_verifier']);

$_SESSION['access_token'] = $token;

// save access token to db for later use

unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);

if ($twitter->getLastHttpCode() == 200) {
    header('Location: index.php');
} else {
    header('Location: logout.php');
}