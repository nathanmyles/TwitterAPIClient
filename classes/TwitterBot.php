<?php
/**
 * TwitterBot.php - handles interaction with twitter
 *
 * User: nathanmyles
 * Date: 2013-01-20 9:38 PM
 *
 */

require_once(dirname(__FILE__) . '/Twitter.php');


class TwitterBot extends Twitter
{
    private $userId;
    private $userScreenName;

    const SEARCH_BY_ID = 'ID';
    const SEARCH_BY_SCREEN_NAME = 'SCREEN_NAME';

    public function __construct($key, $secret, $token = null, $tokenSecret = null)
    {
        parent::__construct($key, $secret, $token, $tokenSecret);
    }

    /**
     * Send tweet
     *
     * @param $message string message to tweet
     * @param $replyToTweet int id of tweet to reply to
     *
     * @return array|bool array of tweet details, false on error
     */
    public function tweet($message, $replyToTweet = null)
    {
        $params['status'] = $message;
        if(!empty($replyToTweet)) $params['in_reply_to_status_id'] = $replyToTweet;
        return (!empty($message)) ? $this->tweetWithParameters($params) : false;
    }

    /**
     * Send tweet with parameter array
     *
     * @param array $parameters
     *
     * Ex.  $parameters = array('status' => 'message', // required: message to tweet
     *                          'in_reply_to_status_id' // id of tweet to reply to
     *                          'lat' => 37.7821120598956 // latitude tweet refers to
     *                          'long' => -122.400612831116 // longitude tweet refers to
     *                          'place_id' => 'df51dec6f4ee2b2c' // a place id (ids can be retrieved from GET geo/reverse_geocode)
     *                          'display_coordinates' => true, // show location pin
     *                          'trim_user' => true); // returns user object including only the status authors numerical id
     *
     * @return array|bool array of tweet details, false on error
     */
    public function tweetWithParameters($parameters)
    {
        return (is_array($parameters)) ? $this->api('statuses/update', $parameters, 'POST') : false;
    }

    public function tweetWithImage($message, $file)
    {
        return (!empty($message) && file_exists($file)) ? $this->tweetWithParametersAndImage(array('status' => $message), $file) : false;
    }

    public function tweetWithParametersAndImage($parameters, $file)
    {
        return (is_array($parameters) && file_exists($file)) ? $this->api('statuses/update_with_media', $parameters, 'POST', $file) : false;
    }

    /**
     * Search tweets
     *
     * @param $query string query string to search for
     *
     * @return array|bool array of tweets, false on error
     */
    public function searchTweets($query)
    {
        return $this->searchTweetsWithParameters(array('q' => $query));
    }

    /**
     * Search tweets with parameter array
     *
     * Ex.  $parameters = array('q' => 'query string', // required: query string to search on
     *                          'geocode' => '37.781157,-122.398720,1mi', // latitude,longitude,radius(mi,km)
     *                          'lang' => 'en', // limit to specified language
     *                          'locale' => 'ja', // limit to country/region (not well supported)
     *                          'result_type' => 'mixed', // possible values: mixed, recent, popular
     *                          'count' => 25, // number of tweets per page (default:15, max:100)
     *                          'until' => '2012-09-01', // older than specified date (format:YYYY-MM-DD)
     *                          'since_id' => 12345, // minimum id
     *                          'max_id' => 54321, // maximum id
     *                          'include_entities' => false, // do not include entities
     *                          'callback' => 'processTweets'); // JSONP callback of specified name
     *
     * @param array $parameters
     *
     * @return array|bool array of tweets, false on error
     *
     */
    public function searchTweetsWithParameters($parameters)
    {
        return (is_array($parameters)) ? $this->api('search/tweets', $parameters) : false;
    }

    public function getFollowers($justIds = false, $userId = null)
    {
        $params['user_id'] = (empty($userId)) ? $this->getUserId() : $userId;
        if($justIds) $params['stringify_ids'] = true;
        return $this->getFollowersWithParameters($params, $justIds);
    }

    /**
     * Get people following a user
     *
     * Ex.  $parameters = array('screen_name' => 'screen name', // user's screen name
     *                          'user_id' => '12345', // user id
     *                          'cursor' => 12893764510938, // allows paging (response has next_cursor and prev_cursor fields, -1 for first page)
     *                          //only valid if $justIds is true
     *                          'stringify_ids' => true, // return ids as strings
     *                          //only valid if $justIds is false
     *                          'include_user_entities' => false, //  do not include user entities
     *                          'skip_status' => true); // do not include statuses
     *
     * @param array $parameters
     *
     * @param bool $justIds
     *
     * @return array|bool
     */

    public function getFollowersWithParameters($parameters = null, $justIds = false)
    {
        $type = ($justIds) ? 'ids' : 'list';
        return (is_array($parameters)) ? $this->api('followers/'.$type, $parameters) : false;
    }

    public function getFriends($justIds = false, $userId = null)
    {
        $params['user_id'] = (empty($userId)) ? $this->getUserId() : $userId;
        if($justIds) $params['stringify_ids'] = true;
        return $this->getFriendsWithParameters($params, $justIds);
    }

    /**
     * Get friends of a user
     *
     * Ex.  $parameters = array('screen_name' => 'screen name', // user's screen name
     *                          'user_id' => '12345', // user id
     *                          'cursor' => 12893764510938, // allows paging (response has next_cursor and prev_cursor fields, -1 for first page)
     *                          //only valid if $justIds is true
     *                          'stringify_ids' => true, // return ids as strings
     *                          //only valid if $justIds is false
     *                          'include_user_entities' => false, //  do not include user entities
     *                          'skip_status' => true); // do not include statuses
     *
     * @param array $parameters
     *
     * @param bool $justIds
     *
     * @return array|bool
     */
    public function getFriendsWithParameters($parameters = null, $justIds = false)
    {
        $type = ($justIds) ? 'ids' : 'list';
        return (is_array($parameters)) ? $this->api('friends/'.$type, $parameters) : false;
    }

    public function friendUserByScreenName($screenName)
    {
        return (!empty($screenName)) ? $this->friendUserWithParameters(array('screen_name' => $screenName, 'follow' => true)) : false;
    }

    public function friendUserById($userId)
    {
        return (!empty($userId)) ? $this->friendUserWithParameters(array('user_id' => $userId, 'follow' => true)) : false;
    }

    /**
     * Friend a user
     *
     * Ex.  $parameters = array('screen_name' => 'screen name', // user's screen name
     *                          'user_id' => '12345', // user id
     *                          'follow' => true); // follow user
     *
     * @param array $parameters
     *
     * @return array|bool
     */
    public function friendUserWithParameters($parameters)
    {
        return (is_array($parameters)) ? $this->api('friendships/create', $parameters, 'POST') : false;
    }

    public function friendFollowersById($userId = null)
    {
        $this->friendFollowers(TwitterBot::SEARCH_BY_ID, $userId);
    }

    public function friendFollowersByScreenName($screenName = null)
    {
        $this->friendFollowers(TwitterBot::SEARCH_BY_SCREEN_NAME, $screenName);
    }

    public function friendFollowers($searchBy = null, $query = null)
    {
        if(empty($searchBy))
        {
            $params = array('user_id' => $this->getUserId(), 'stringify_ids' => true);
        }
        elseif($searchBy === TwitterBot::SEARCH_BY_ID)
        {
            if(empty($query))
            {
                $query = $this->getUserId();
            }
            $params = array('user_id' => $query, 'stringify_ids' => true);
        }
        elseif($searchBy === TwitterBot::SEARCH_BY_SCREEN_NAME)
        {
            if(empty($query))
            {
                $query = $this->getUserScreenName();
            }
            $params = array('screen_name' => $query, 'stringify_ids' => true);
        }
        if(!empty($params)) $this->friendFollowersWithParameters($params);
    }

    /**
     * Friend all the users that are following a user
     *
     * Ex.  $parameters = array('screen_name' => 'screen name', // user's screen name
     *                          'user_id' => '12345', // user id
     *                          'cursor' => 12893764510938, // allows paging (response has next_cursor and prev_cursor fields, -1 for first page)
     *                          //only valid if $justIds is true
     *                          'stringify_ids' => true, // return ids as strings
     *                          //only valid if $justIds is false
     *                          'include_user_entities' => false, //  do not include user entities
     *                          'skip_status' => true); // do not include statuses
     *
     * @param array $parameters
     *
     *
     */
    public function friendFollowersWithParameters($parameters)
    {
        $friendIds = $this->getFriendsWithParameters($parameters, true);
        $followerIds = $this->getFollowersWithParameters($parameters, true);

        for($i = 0; $i < count($followerIds); $i++)
        {
            if(!in_array($followerIds[$i], $friendIds))
            {
                $this->friendUserById($followerIds[$i]);
            }
        }
    }

    /**
     * Get the authenticated users details
     *
     * Ex.  $parameters = array('include_entities' => false, //  do not include entities
     *                          'skip_status' => true); // do not include statuses
     *
     * @param array $parameters
     *
     * @return array|bool
     */
    public function getCurrentUser($parameters = null)
    {
        return (is_array($parameters)) ? $this->api('account/verify_credentials', $parameters) : $this->api('account/verify_credentials');
    }

    public function searchUser($query)
    {
        return $this->searchUserWithParameters(array('q' => $query));
    }

    public function searchUserWithParameters($parameters)
    {
        return (is_array($parameters)) ? $this->api('users/search', $parameters, 'POST') : false;
    }

    public function getUserByScreenName($screenName)
    {
        return (!empty($screenName)) ? $this->getUserWithParameters(array('screen_name' => $screenName)) : false;
    }

    public function getUserById($userId)
    {
        return (is_numeric($userId)) ? $this->getUserWithParameters(array('user_id' => $userId)) : false;
    }

    public function getUserWithParameters($parameters)
    {
        return (is_array($parameters)) ? $this->api('users/show', $parameters) : false;
    }

    public function getFriendshipsForUsersById($userIds)
    {
        $userIds = (is_array($userIds)) ? implode(',', $userIds) : $userIds;
        return (!empty($userIds)) ? $this->api('friendships/lookup', array('user_id' => $userIds)) : false;
    }

    public function getFriendshipsForUsersByScreenName($screenNames)
    {
        $screenNames = (is_array($screenNames)) ? implode(',', $screenNames) : $screenNames;
        return (!empty($screenNames)) ? $this->api('friendships/lookup', array('screen_name' => $screenNames)) : false;
    }

    public function getFriendshipsForUsersWithParameters($parameters)
    {
        return (is_array($parameters)) ? $this->api('friendships/lookup', $parameters) : false;
    }

    /**
     * Get a list of tweets the user was mentioned in
     *
     * Ex.  $parameters = array('count' => 25, // number of tweets per page (default:20, max:200)
     *                          'since_id' => 12345, // minimum id
     *                          'max_id' => 54321, // maximum id
     *                          'trim_user' => true, // returns user object including only the status authors numerical id
     *                          'contributor_details' => true, // get screen name of tweeter
     *                          'include_entities' => false, // do not include entities
     *
     * @param $parameters
     *
     * @return array array of list of tweets
     */
    public function getMentions($parameters = null)
    {
        return (is_array($parameters)) ? $this->api('statuses/mentions_timeline', $parameters) : $this->api('statuses/mentions_timeline');
    }

    public function getUserTimeLine($parameters = null)
    {
        return (is_array($parameters)) ? $this->api('statuses/user_timeline', $parameters) : $this->api('statuses/user_timeline');
    }

    public function getHomeTimeLine($parameters = null)
    {
        return (is_array($parameters)) ? $this->api('statuses/home_timeline', $parameters) : $this->api('statuses/home_timeline');
    }

    public function getAccessToken($oauthVerifier = false)
    {
        $token = parent::getAccessToken($oauthVerifier);

        $this->setUserId($token['user_id']);
        $this->setUserScreenName($token['screen_name']);
    }

    // setters & getters
    private function setUserId($userId)
    {
        return (is_numeric($userId)) ? $this->userId = $userId : false;
    }

    private function getUserId()
    {
        return $this->userId;
    }

    private function setUserScreenName($screenName)
    {
        return $this->userScreenName = $screenName;
    }

    private function getUserScreenName()
    {
        return $this->userScreenName;
    }
}
