<?php

/**
 * Class operates on google reader via its unofficial API on behalf of user.
 * Or on behalf of an app, which itself operates on behalf on the user.
 * Or an app, which operates on behalf of someone, who does this on behalf
 * of some user.
 *
 * @copyright Copyright (c) 2011, Kaspars Foigts
 * @license MIT
 * @author Kaspars Foigts <laacz@laacz.lv>
 * @link https://github.com/laacz
 * @
 */
class GReader {
    // Friend types
    const FRIEND_TYPE_FOLLOWER           = 0; // this person is following the user
    const FRIEND_TYPE_FOLLOWING          = 1; // the user is following this person
    const FRIEND_TYPE_CONTACT            = 3; // this person is in the user's contacts list
    const FRIEND_TYPE_PENDING_FOLLOWING  = 4; // the user is attempting to follow this person
    const FRIEND_TYPE_PENDING_FOLLOWER   = 5; // this person is attempting to follow this user
    const FRIEND_TYPE_ALLOWED_FOLLOWING  = 6; // the user is allowed to follow this person
    const FRIEND_TYPE_ALLOWED_COMMENTING = 7; // the user is allowed to comment on this person's shared items

    // Friend flags
    const FRIEND_FLAG_IS_ME            =   1; // represents the current user
    const FRIEND_FLAG_IS_HIDDEN        =   2; // current user has hidden this person from the list of people with shared items that show up people that they follow
    const FRIEND_FLAG_USES_READER      =   4; // this person uses reader
    const FRIEND_FLAG_IS_BLOCKED       =   8; // the user has blocked this person
    const FRIEND_FLAG_HAS_PROFILE      =  16; // this person has created a Google Profile
    const FRIEND_FLAG_IS_IGNORED       =  32; // this person has requested to follow the user, but the use has ignored the request
    const FRIEND_FLAG_IS_NEW_FOLLOWER  =  64; // this person has just begun to follow the user
    const FRIEND_FLAG_IS_ANONYMOUS     = 128; // this person doesn't have a display name set
    const FRIEND_FLAG_HAS_SHARED_ITEMS = 256; // this person has shared items in reader


    // CURL instance
    private $ch;
    var $ch_info;

    // Contains Google Reader user's info
    var $userInfo;

    // Auth and request tokens
    private $authToken;
    private $token;

    /**
     * Constructive constructor.
     *
     * @param string $email Google (apps) email.
     * @param string $password Password and keys to the place, where your moneys are.
     * @param string $debug False - no debug, true - yes debug.
     */
    function __construct($email, $password, $debug = false) {
        $this->debug = $debug;
        $this->login($email, $password);
    }

    /**
     * Umm. What's it called. De... destructor, ye!
     */
    function __desctruct() {
        curl_close($this->ch);
    }

    /**
     * Utility function to output debugging info, if debugging is on or called statically.
     *
     * @param string $str Message to output.
     *
     * @return void
     */
    function debug($str) {
        if ((isset($this) && $this->debug) || !isset($this)) {
            echo date('Y-m-d H:i:s ') . $str . "\n";
        }
    }

    /**
     * Heart. Performs HTTP request.
     *
     * @param string $url URL to request.
     * @param string $post_fields If specified, indicates that this is POST request.
     *
     * @return mixed Returns anything that google returns.
     */
    function request($url, $post_fields = false) {
        $this->debug('Perform ' . ($post_fields ? 'POST' : 'GET') . ' request on ' . $url . ($post_fields ? $this->array_to_str($post_fields) : ''));

        $this->ch = curl_init();

        curl_setopt($this->ch, CURLOPT_URL, $url);
        if ($post_fields) {
            if ($this->token) $post_fields['T'] = $this->token;
            curl_setopt($this->ch, CURLOPT_POST, true);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
        }

        if ($this->authToken) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, Array('Authorization: GoogleLogin auth=' . $this->authToken));
        }

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);

        $result = curl_exec($this->ch);
        $this->ch_info = curl_getinfo($this->ch);

        if ($this->ch_info['http_code'] != 200) {
            $this->debug('Not HTTP 200 OK: ' . $this->ch_info['http_code']);
            print_r($result);
        }

        return $result;
    }

    /**
     * Utility method dumps array as string.
     *
     * @param array $array Array to convert.
     *
     * @return string Ex-array as a string.
     */
    function array_to_str($array) {
        $str = Array();
        foreach ($array as $k=>$v) {
            if ($k == 'Passwd') $v = '*****';
            $str[] = "$k=$v";
        }
        return '{' . join(', ', $str) . '}';
    }

    /**
     * Performs login, populates userinfo and retreives tokens.
     *
     * @param string $email Your google/google apps email.
     * @param string $password Password for your account.
     *
     * @return void
     */
    function login($email, $password) {
        $post_fields = Array(
            'accountType' => 'GOOGLE',
            'Email' => $email,
            'Passwd' => $password,
            'source'=>'ReaderMigration',
            'service'=>'reader',
        );

        $result = $this->request('https://www.google.com/accounts/ClientLogin', $post_fields);

        $this->authToken = trim(substr(strstr($result, "Auth"), 5));
        $this->debug('Got auth token: ' . $this->authToken);

        $this->userInfo = $this->getUserInfo();
        $this->debug('User info: ' . $this->array_to_str($this->userInfo));

        $this->token = $this->getToken();
        $this->debug('Got token: ' . $this->token);
    }

    /**
     * Gets userinfo.
     *
     * @return object User info (who would think?)
     */
    function getUserInfo() {
        return json_decode($this->request('https://www.google.com/reader/api/0/user-info?output=json'));
    }

    /**
     * Gets token for POST requests.
     *
     * @return string token
     */
    function getToken() {
        return trim($this->request('https://www.google.com/reader/api/0/token'));
    }

    /**
     * Gets all subscriptions for current user.
     *
     * @return object something
     */
    function getSubscriptions() {
        return json_decode($this->request('https://www.google.com/reader/api/0/subscription/list?output=json'));
    }

    /**
     * Gets all friends for current user.
     *
     * @return object something
     */
    function getFriends() {
        return json_decode($this->request('https://www.google.com/reader/api/0/friend/list?output=json'));
    }

    /**
     * Gets friend groups for current user.
     *
     * @return object something
     */
    function getFriendsGroups() {
        return json_decode($this->request('https://www.google.com/reader/api/0/friend/groups?output=json'));
    }

    /**
     * Gets friend access lists for current user.
     *
     * @return object something
     */
    function getFriendsACL() {
        return json_decode($this->request('https://www.google.com/reader/api/0/friend/acl?output=json'));
    }

    /**
     * Gets all labels for current user.
     *
     * @return object list of labels.
     */
    function getTags() {
        // Disallowed characters in tag titles: "<>?&/\^
        return json_decode($this->request('https://www.google.com/reader/api/0/tag/list?output=json'));
    }

    /**
     * Edits item
     *
     * @param string $id Subscription id. Mostly 'feed/http...feed..addr'.
     * @param string $title Title for this subscription.
     * @param string $label Label name. For example, 'state/com.google/starred'.
     * @param string $action 'subscribe', 'unsubscribe'.
     *
     * @return object something.
     */
    function editSubscription($id, $title = false, $label = false, $action = 'subscribe') {
        $post_fields = Array(
            's' => $id,
            'ac' => $action,
        );
        if ($title) $post_fields['t'] = $title;
        if ($label) $post_fields['a'] = $label;

        return json_decode($this->request('https://www.google.com/reader/api/0/subscription/edit?output=json', $post_fields));
    }

    /**
     * Gets itemds for specified stream.
     *
     * @param string $what Stream name.
     * @param number $limit How many items to fetch. Try not to specify 0. That can lead to many, many, many items :)
     *
     * @return object (something)
     */
    function getItemIds($what, $limit = 1024) {
        $this->debug('Fetching items tagged as "' . $what . '" (' . ($limit ? 'limiting to ' . $limit : 'no limit') . ')');

        $result = json_decode($this->request('http://www.google.com/reader/api/0/stream/items/ids/?s=' . rawurldecode($what) . '&output=json&n=' . $limit . '&client=scroll'));

        return $result;

    }

    /**
     * Fetches items from a stream.
     *
     *  @param string $what Stream name. For example 'user/-/state/com.google/starred' or 'user/-/state/com.google/broadcast'.
     *  @param number $limit Number How many items to fetch. If zero (or false [or an empty array:>]), fetches all (beware!).
     *
     *  @return object (->items is an array with entries).
     */
    function getItems($what, $limit = 1024, $exclude = Array()) {
        $this->debug('Fetching items tagged as "' . $what . '" (' . ($limit ? 'limiting to ' . $limit : 'no limit') . ')');
        $continuation = '';
        $return = false;
        $count = ($limit >= 100 || !$limit) ? 100 : $limit;

        if ($exclude) {
            if (!is_array($exclude)) $exclude = Array($exclude);
            foreach ($exclude as $k=>$v) {
                $exclude[$k] = "xt=" . rawurlencode($v);
            }
        }

        while (true) {
            $result = json_decode($this->request('http://www.google.com/reader/api/0/stream/contents/' . rawurlencode($what) . (strpos($what, '?') === false ? '?' : '&') . 'output=json&n=' . $count . '&c=' . $continuation . '&client=scroll' . ($exclude ? '&' . join('&', $exclude) : '')));
            $this->debug('Got items: ' . count($result->items). ' pcs');

            if (!$return) {
                $return = $result;
            } else {
                foreach ($result->items as $item) {
                    $return->items[] = $item;
                }
            }

            // Are we done?
            $continuation = isset($result->continuation) ? $result->continuation : false;
            if (!$continuation) {
                break;
            }

            // Shall we decrease linmit in order not to fetch more than needed?
            if ($limit && ($count + count($return->items) >= $limit)) {
                $count = $limit - count($return->items);
            }

            if ($count == 0) break;

        }
        return $return;
    }

    /**
     * Sets entry tag (category, label). Works for user's tags and com.google (system) states
     *
     * @param string $id Entry id in (for example, tag:google.com,2005:reader/item/71d15223cd83b85d)
     * @param string $feed_id Feed's id, from which entry came (for example, feed/http://blog.martindoms.com/feed/)
     * @param string $tag String Tag to assign to the entry (for example, user/-/state/com.google/starred or user/-/label/SuperLabel)
     *
     * @return void
     */
    function setEntryTag($id, $feed_id, $tag) {
        $post_fields = Array(
            's' => $feed_id,
            'a' => $tag,
            'i' => $id
        );

        return json_decode($this->request('https://www.google.com/reader/api/0/edit-tag?output=json', $post_fields));
    }

    /**
     * Removes entry tag (category, label). Works for user's tags and com.google (system) states
     *
     * @param string $id Entry id in (for example, tag:google.com,2005:reader/item/71d15223cd83b85d)
     * @param string $feed_id Feed's id, from which entry came (for example, feed/http://blog.martindoms.com/feed/)
     * @param string $tag String Tag to unassign from the entry (for example, user/-/state/com.google/starred or user/-/label/SuperLabel)
     *
     * @return void
     */
    function removeEntryTag($id, $feed_id, $tag) {
        $post_fields = Array(
            's' => $feed_id,
            'r' => $tag,
            'i' => $id
        );

        return json_decode($this->request('https://www.google.com/reader/api/0/edit-tag?output=json', $post_fields));
    }

    /**
     * Annotates (adds note) and shares (or not:) entry.
     *
     * @param string $url URL to share.
     * @param string $annotation Note to add.
     * @param string $title Title to assign to shared item.
     * @param string $snippet Content (or portion of it) to show.
     * @param bool $share If true, shares item to followers.
     *
     * @return object something
     */
    function annotateEntry($url, $annotation, $title, $snippet, $share = false) {
        $post_fields = Array(
            'url' => $url,
            'share' => $share ? 'true' : 'false',
            'annotation' => $annotation,
            'title' => $title,
            'snippet' => $snippet,
        );

        return json_decode($this->request('https://www.google.com/reader/api/0/item/edit?output=json', $post_fields));
    }

    /**
     * Mark all items in a stream as read. Defaults to mark-all-everywhere-whenever-as-read.
     *
     * @param string $what Stream ID
     *
     * @return object whatever
     */
    function markAllAsRead($what = 'user/-/state/com.google/reading-list') {
        $post_fields = Array(
            's' => $what, // stream id
            'ts' => number_format(microtime(true) * 1000000, 0, '.', ''), // Timestamp in microseconds
        );

        return json_decode($this->request('https://www.google.com/reader/api/0/mark-all-as-read?output=json', $post_fields));
    }

    /**
     * Edit friend.
     *
     * @param string $userId User ID
     * @param string $profileUrl Link to user's profile
     * @param string $action 'addfollowing' to follow, 'removefollowing' to unfollow
     *
     * TODO: implement blocking unblocking?
     * Unblock:
     *     action:removefollowers
     *     u:07332626230056918067
     *     pu:http://www.google.com/profiles/112639243179322210394
     *     g:21
     *
     * Block:
     *     action:addfollowers
     *     u:07332626230056918067
     *     pu:http://www.google.com/profiles/112639243179322210394
     *     g:21
     *
     * @return smthn
     */
    function editFriend($u, $pu, $action) {
        $post_fields = Array(
            'u' => $u,
            'pu' => $pu,
            'action' => $action,
        );
        return json_decode($this->request('https://www.google.com/reader/api/0/friend/edit?output=json', $post_fields));
    }

    function peopleSearch($q) {
        return json_decode($this->request('https://www.google.com/reader/api/0/people/search?output=json&q=' . rawurlencode($q)));
    }

}
