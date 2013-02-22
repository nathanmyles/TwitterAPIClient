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

    $timeLine = $twitter->api('statuses/home_timeline');
?>

<html>
    <head>
        <title>Twitter API Client</title>
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/bootstrap-responsive.min.css">
        <link rel="stylesheet" href="css/styles.css"/>
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
        <script type="text/javascript" src="js/bootstrap.min.js"></script>
    </head>
    <body>
    <div class="navbar navbar-inverse navbar-fixed-top">
        <div class="navbar-inner">
            <div class="container">
                <a class="brand" href="#">Twitter API Client</a>
                <ul class="nav">
                    <li><a href="twitterbot.php">TwitterBot</a></li>
                    <li><a href="https://github.com/nathanmyles/TwitterAPIClient" target="_blank">View Source Code</a></li>
                </ul>
                <div class="pull-right">
                    <ul class="nav">
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="well">
            <h1>Your Connected!</h1>
            <hr/>
            <?php foreach($timeLine as $tweet){ ?>
            <div class="media">
                <a class="pull-left" href="#">
                    <img class="media-object" src="<?php echo $tweet["user"]["profile_image_url"] ?>">
                </a>
                <div class="media-body">
                    <p><?php echo $tweet['text'] ?></p>
                    <p><small class="text-info">By: @<?php echo $tweet['user']['screen_name'] . ' - ' . $tweet['created_at'] ?></small></p>
                </div>
            </div>
            <?php } ?>
        </div>
        <footer>
            <p>&copy; Nathan Myles 2013</p>
        </footer>
    </div>
    </body>
</html>

<?php }