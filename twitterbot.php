<?php
session_start();
require_once(dirname(__FILE__) . '/classes/Config.php');
require_once(dirname(__FILE__) . '/classes/TwitterBot.php');

// if we don't have a token, get them to connect
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    echo '<a href="connect.php">Connect With Twitter</a>';
}
else
{
    // we have access! lets do it!
    $access_token = $_SESSION['access_token'];
    $twitterBot = new TwitterBot(Config::CONSUMER_KEY, Config::CONSUMER_KEY_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

    // send a tweet
    if(!empty($_POST['submit_tweet']))
    {
        $twitterBot->tweet($_POST['tweet']);
    }
    // search tweets
    if(!empty($_POST['submit_search']))
    {
        $searchResults = $twitterBot->searchTweets($_POST['query']);
    }
    //follow a user
    if(!empty($_POST['submit_follow']))
    {
        $twitterBot->friendUserByScreenName('TheLucyGrays');
    }

    // get user by screen name
    $theLucyGrays = $twitterBot->getUserByScreenName('TheLucyGrays');

    // get friendship connection to user by screen name
    $lucyGraysFriendshipInfo = $twitterBot->getFriendshipsForUsersByScreenName('TheLucyGrays');

    // collect data
    $data = array();

    // get currently logged in user info
    $data['Current User Info'] = $twitterBot->getCurrentUser();

    // get user's home time line
    $data['Home TimeLine'] = $twitterBot->getHomeTimeLine();

    // get tweets where user was mentioned
    $data['Mentions TimeLine'] = $twitterBot->getMentions();

    // get user's time line
    $data['User TimeLine'] = $twitterBot->getUserTimeLine();

    // get user's friends
    $data['Friends'] = $twitterBot->getFriends();

    // get user's followers
    $data['Follower'] = $twitterBot->getFollowers();

    //if user has at least one friend, will show them a friends friend
    if(!empty($data['Friends']['users']))
    {
        $friend = $data['Friends']['users'][0];
        $data[$friend['name'].'\'s Friends'] = $twitterBot->getFriends(false, $friend['id']);
    }



?>

<html>
    <head>
        <title>Twitter API Client Bot</title>
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
                <a class="brand" href="#">Twitter API Client Bot</a>
                <ul class="nav">
                    <li><a href="index.php">Home</a></li>
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
        <h1>Welcome <?php echo $data['Current User Info']['name'] ?>, check out what you can do!</h1>
        <div class="well">
            <h4>Post a Tweet</h4>
            <form method="post" action="<?php  echo $_SERVER['PHP_SELF'] ?>" >
                <textarea name="tweet"></textarea>
                <input name="submit_tweet" type="submit" value="Post" />
            </form>
        </div>
        <div class="well">
            <h4>Search Twitter</h4>
            <form method="post" action="<?php  echo $_SERVER['PHP_SELF'] ?>">
                <input name="query" type="text" placeholder="Search for..." />
                <input name="submit_search" type="submit" value="Search" />
            </form>
            <?php if(!empty($searchResults)){ ?>
            <hr/>
            <h6>Results</h6>
            <pre>
            <?php var_dump($searchResults) ?>
            </pre>
            <?php } ?>
        </div>
        <div class="well">
            <?php if(in_array('following', $lucyGraysFriendshipInfo[0]['connections'])){ ?>
            <h1>You're following <a href="http://thelucygrays.com" target="_blank">The Lucy Grays</a>, you know what's up!</h1>
            <?php } else { ?>
            <h1>Follow The Lucy Grays</h1>
            <form method="post" action="<?php  echo $_SERVER['PHP_SELF'] ?>">
                <input name="submit_follow" type="submit" value="Follow" />
            </form>
            <?php } ?>
            <div class="media">
                <a class="pull-left" href="#">
                    <img class="media-object" src="<?php echo $theLucyGrays["profile_image_url"] ?>">
                </a>
                <div class="media-body">
                    <p><?php echo $theLucyGrays['status']['text'] ?></p>
                    <p><small class="text-info">at <?php echo $theLucyGrays['status']['created_at'] ?></small></p>
                </div>
            </div>
        </div>
        <div class="well">
            <h4>Sections</h4>
            <?php foreach($data as $title => $info){ ?>
            <div><a href="#<?php echo strtolower(str_replace(' ', '', $title)) ?>"><?php echo $title ?></a></div>
            <?php } ?>
        </div>
        <?php foreach($data as $title => $info){ ?>
        <a id="<?php echo strtolower(str_replace(' ', '', $title)) ?>"></a>
        <div class="well">
            <h4><?php echo $title?></h4>
            <hr/>
            <pre>
            <?php var_dump($info) ?>
            </pre>
        </div>
        <?php } ?>
        <footer>
            <p>&copy; Nathan Myles 2013</p>
        </footer>
    </div>
    </body>
</html>

<?php
}