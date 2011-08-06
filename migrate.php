<?php

require('config.php');
require('greader.php');

function log_msg($msg) {
    echo date('Y-m-d H:i:s ') . $msg . "\n";
}

$source = new GReader($email, $password, true);
$destination = new GReader($email2, $password2, true);

/** Sync subscriptions and labels (a.k.a. categories, subscription tags) **/
$src_subs = $source->getSubscriptions();
$dst_subs = $destination->getSubscriptions();

log_msg('Moving subscriptions and labels');
log_msg('Source has ' . count($src_subs->subscriptions) . ' and destination has ' . count($dst_subs->subscriptions));
foreach ($src_subs->subscriptions as $sidx=>$ssub) {

    $sub_found = false;

    // Check if subscription already @destination
    foreach ($dst_subs->subscriptions as $idx=>$dsub) {
        if ($dsub->id == $ssub->id) {
            $sub_found = $idx;
            break;
        }
    }

    // Build categories array
    $categories = Array();
    foreach ($ssub->categories as $cat) {
        $cat->id = preg_replace('|user/\d+/|', 'user/-/', $cat->id);
        $categories[$cat->id] = $cat;
    }

    if ($sub_found !== false) {
        // If subscription there, check if categories are the same
        foreach ($dsub->categories as $cat) {
            $cat->id = preg_replace('|user/\d+/|', 'user/-/', $cat->id);
            if (isset($categories[$cat->id])) {
                unset($categories[$cat->id]);
            }
        }
    }

    if ($sub_found === false) {
        log_msg('Subscribing to ' . $ssub->id);
        $destination->editSubscription($ssub->id, $ssub->title);
    }

    if ($categories) {
        log_msg('Syncing labels for ' . $ssub->id);
        foreach ($categories as $cat) {
            log_msg('    ' . $cat->id);
            $ret = $destination->editSubscription($ssub->id, false, $cat->id, 'edit');
        }
    }

}


/** Sync stgarred items **/
$src_starred = $source->getItems('user/-/state/com.google/starred', 0);
$dst_starred = $destination->getItems('user/-/state/com.google/starred', 0);
log_msg('Moving starred items');
log_msg('Source has ' . count($src_starred->items) . ' and destination has ' . count($dst_starred->items));

foreach ($src_starred->items as $sitem) {
    //print_r($sitem);
    $moved = false;
    foreach ($dst_starred->items as $ditem) {
        if ($ditem->id == $sitem->id) {
            $moved = true;
            break;
        }
    }

    if ($moved === false) {
        log_msg('Moving item "' . $sitem->id . '"');
        $destination->setEntryTag($sitem->id, $sitem->origin->streamId, 'user/-/state/com.google/starred');
    }

}
