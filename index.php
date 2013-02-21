<?php
session_start();
require_once(dirname(__FILE__) . '/classes/Config.php');
require_once(dirname(__FILE__) . '/classes/Twitter.php');

// if we don't have a token, get them to connect
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    echo '<a href="connect.php">Connect With Twitter</a>';
}
else
{
    // we have access! lets get some info
    $access_token = $_SESSION['access_token'];

    $twitter = new Twitter(Config::CONSUMER_KEY, Config::CONSUMER_KEY_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

    echo '<h1>Your Connected!</h1>';

    echo '<p><a href="logout.php">logout</a></p>';

    $timeLine = $twitter->api('statuses/home_timeline');

    foreach($timeLine as $tweet) { ?>
        <div>
            <img src="<?php echo $tweet["user"]["profile_image_url"]; ?>" />
            <p><?php echo $tweet['text']; ?></p>
            <p>By: @<?php echo $tweet['user']['screen_name'] . ' - ' . $tweet['created_at'] ?></p>
        </div>
    <?php }
}