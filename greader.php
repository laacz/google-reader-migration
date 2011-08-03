<?php

class GReader {
    // CURL instance
    private $ch;
    var $ch_info;

    //
    var $userInfo;
    private $authToken;
    private $token;

    function __construct($email, $password, $debug = false) {
        $this->debug = $debug;
        $this->login($email, $password);
    }

    function __desctruct() {
        curl_close($this->ch);
    }

    function debug($str) {
        if ((isset($this) && $this->debug) || !isset($this)) {
            echo date('Y-m-d H:i:s ') . $str . "\n";
        }
    }

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

    function array_to_str($array) {
        $str = Array();
        foreach ($array as $k=>$v) {
            if ($k == 'Passwd') $v = '*****';
            $str[] = "$k=$v";
        }
        return '{' . join(', ', $str) . '}';
    }

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

    function getUserInfo() {
        return json_decode($this->request('https://www.google.com/reader/api/0/user-info?output=json'));
    }

    function getToken() {
        return trim($this->request('https://www.google.com/reader/api/0/token'));
    }

    function getSubscriptions() {
        return json_decode($this->request('https://www.google.com/reader/api/0/subscription/list?output=json'));
    }

    function getTags() {
        // Disallowed characters in tag titles: "<>?&/\^
        return json_decode($this->request('https://www.google.com/reader/api/0/tag/list?output=json'));
    }

    function editSubscription($id, $title = false, $label = false, $action = 'subscribe') {
        $post_fields = Array(
            's' => $id,
            'ac' => $action,
        );
        if ($title) $post_fields['t'] = $title;
        if ($label) $post_fields['a'] = $label;

        return json_decode($this->request('https://www.google.com/reader/api/0/subscription/edit?output=json', $post_fields));
    }

    function getItems($what, $limit = 1024) {
        $this->debug('Fetching items tagged as "' . $what . '" (' . ($limit ? 'limiting to ' . $limit : 'no limit') . ')');
        $continuation = '';
        $return = false;
        $count = ($limit >= 100 || $limit == 0) ? 100 : $limit;
        while (true) {
            $result = json_decode($this->request('http://www.google.com/reader/api/0/stream/contents/user/-/' . $what . '?output=json&n=' . $count . '&c=' . $continuation . '&client=scroll'));
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
     * $id String Entry id in (for example, tag:google.com,2005:reader/item/71d15223cd83b85d)
     * $feed_id String Feed's id, from which entry came (for example, feed/http://blog.martindoms.com/feed/)
     * $tag String Tag to assign to the entry (for example, user/-/state/com.google/starred or user/-/label/SuperLabel)
     */
    function setEntryTag($id, $feed_id, $tag) {
        $post_fields = Array(
            's' => $feed_id,
            'a' => $tag,
            'i' => $id
        );

        return json_decode($this->request('https://www.google.com/reader/api/0/edit-tag', $post_fields));
    }

    /**
     * Annotates (adds note) and shares (or not:) entry.
     *
     * $url String URL to share.
     * $annotation String Note to add.
     * $title String Title to assign to shared item.
     * $snippet String Content (or portion of it) to show.
     * $share Boolean If true, shares item to followers.
     */
    function annotateEntry($url, $annotation, $title, $snippet, $share = false) {
        $post_fields = Array(
            'url' => $url,
            'share' => $share ? 'true' : 'false',
            'annotation' => $annotation,
            'title' => $title,
            'snippet' => $snippet,
        );

        return json_decode($this->request('https://www.google.com/reader/api/0/item/edit', $post_fields));
    }

}
