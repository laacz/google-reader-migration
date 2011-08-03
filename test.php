<?php

require('config.php');
require('greader.php');

$source = new GReader($email, $password, true);
$destination = new GReader($email2, $password2, true);

$result = $source->getSubscriptions();
/**

// Subscribe

foreach ($result->subscriptions as $subscription) {
    $ret = $destination->editSubscription($subscription->id, $subscription->title, "user/-/label/{$tag->label}");
}

// Set tags

foreach ($result->subscriptions as $subscription) {
    if ($subscription->categories) {
        foreach ($subscription->categories as $tag) {
            $ret = $destination->editSubscription($subscription->id, false, "user/-/label/{$tag->label}", 'edit');
        }
    }
}

**/


$result = json_decode($source->request('http://www.google.com/reader/api/0/stream/contents/user/-/state/com.google/starred?output=json&n=100'));
foreach ($result as $row) {
    print_r($row);
    die();
}