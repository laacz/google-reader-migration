<?php

require('config.php');
require('greader.php');

$source = new GReader($email, $password, true);
$destination = new GReader($email2, $password2, true);

/**
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
GReader::debug('Moving starred items');
$result = $source->getItems('user/-/state/com.google/starred', 0);
$i = 0;
foreach ($result->items as $entry) {
    $i++;
    GReader::debug('Moving item ' . $i . ' of ' . count($result->items));
    $destination->setEntryTag($entry->id, $entry->origin->streamId, 'user/-/state/com.google/starred');
}

**/


/**
 * All annotated entries are shared entries, aye?
 * Not all shared entries have annotations.
 *
 * a) Check, if entry already not shared at destination.
 * d) If not, add proper tag (user/-/state/com.google/broadcast)
 **/


$result = $destination->getItems('user/-/state/com.google/broadcast', 0);
$already_shared = Array();
foreach ($result->items as $entry) {
    $already_shared[$entry->alternate[0]->href] = $entry;
    //GReader::debug($entry->alternate[0]->href);
}

GReader::debug('Resharing sharred items');
$result = $source->getItems('user/-/state/com.google/broadcast', 10);
// Let's do that, so we re-add shared items in reverse order.
$result->items = array_reverse($result->items);
$i = 0;
foreach ($result->items as $entry) {
    $i++;

    GReader::debug($entry->alternate[0]->href);

    if (isset($already_shared[$entry->alternate[0]->href])) {
        GReader::debug('Already transferred');
    } else {
        GReader::debug('Resharing');
        $ret = $destination->setEntryTag($entry->id, preg_replace('|^(user/)\d+(/.+)$|', '\1-\2', $entry->origin->streamId), "user/-/state/com.google.com/broadcast");
        print_r($ret);
    }
    break;
    /**
    $annotation = '';
    GReader::debug('Resharing item ' . $i . ' of ' . count($result->items));
    $shared = false;
    foreach ($entry->annotations as $v) {
        if ($source->userInfo->userId == $v->userId) {
            $destination->annotateEntry($entry->alternate[0]->href, $v->content, $entry->title, $entry->content->content, true);
            $shared = true;
        }
    }
    **/
}
