<?php

require('config.php');
require('greader.php');

$source = new GReader($email, $password, true);
$destination = new GReader($email2, $password2, true);

$source->debug = $destination->debug = false;

$result = $source->getSubscriptions();

// Move subscriptions and labels
GReader::debug('Moving subscriptions and labels');
$i = 0;
foreach ($result->subscriptions as $subscription) {
    $i++;
    GReader::debug('Feed ' . $i . ' of ' . count($result->subscriptions) . ' (' . $subscription->id . ')');
    $ret = $destination->editSubscription($subscription->id, $subscription->title);
    if ($subscription->categories) {
        $tags = Array();
        foreach ($subscription->categories as $tag) {
            GReader::debug('    Applying label: ' . $tag->label);
            $ret = $destination->editSubscription($subscription->id, false, "user/-/label/{$tag->label}", 'edit');
        }
    }
}


// Move starred items
$continuation = '';
GReader::debug('Moving starred items');
while (true) {
    $i = 0;
    $result = json_decode($source->request('http://www.google.com/reader/api/0/stream/contents/user/-/state/com.google/starred?output=json&n=100&pos=10&c=' . $continuation . '&ck=' . time() . '&client=scroll'));
    echo "Got starred items: "  . count($result->items) . " pcs\n";
    GReader::debug('Got ' . count($result->items) . ' item(s)' . (isset($result->continuation) ? ' (more pending)' : ''));
    foreach ($result->items as $entry) {
        $i++;
        GReader::debug('Moving item ' . $i . ' of ' . count($result->items));
        $destination->editEntry($entry->id, $entry->origin->streamId, 'user/-/state/com.google/starred');
    }
    if (!isset($result->continuation)) {
        break;
    }
    $continuation = $result->continuation;
}
