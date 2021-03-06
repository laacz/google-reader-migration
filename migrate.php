<?php

require('config.php');
require('greader.php');

function log_msg($msg, $timestamp = true) {
    echo ($timestamp ? date('Y-m-d H:i:s ') : '') . $msg . "\n";
}

/** Parse commandline parameters **/

$options = Array(
    'subscriptions' => true,
    'starred' => true,
    'shared' => true,
    'liked' => true,
    'user-labels' => true,
    'unread' => true,
    'following' => true,
    'pretend' => false,
);

if (!in_array('--all', $argv)) {
    foreach ($options as $option=>$z) {
        $options[$option] = in_array('--' . $option, $argv);
    }
}

if (!in_array(true, $options, true)) {
    log_msg('Migrates all your Google Reader stuff between two Google accounts (even Apps)', false);
    log_msg('', false);
    log_msg('Usage: ' . $argv[0] . ' [options]', false);
    log_msg('', false);
    log_msg('  --subscriptions Subscriptions (and their labels)', false);
    log_msg('  --starred Starred items', false);
    log_msg('  --unread Sync unread states', false);
    log_msg('  --user-labels Item tags, if tag is not the same as feed category', false);
    log_msg('', false);
    log_msg('  --pretend Do not actually migrate anything, just output what\' being done.', false);

    log_msg('', false);
    log_msg('    +-=[ KEEP IN MIND ]=------------------------------------------------+', false);
    log_msg('    | Friends re-friending is not tested. Might not work. Reader\'s web |', false);
    log_msg('    | interface throws JS errors.                                       |', false);
    log_msg('    | Social integration in Reader is a mess.                           |', false);
    log_msg('    | What the hell. Reader API itself is a little messy.               |', false);
    log_msg('    +-------------------------------------------------------------------+', false);
    log_msg('', false);

    log_msg('  --all Implies all of the above, except --pretend', false);
    log_msg('', false);
    log_msg('Latest version can be found at Github: https://github.com/laacz/google-reader-migration', false);
    log_msg('(c) 2011, Kaspars Foigts <laacz@laacz.lv>', false);
    die();
}


$source = new GReader($email, $password, true);
$destination = new GReader($email2, $password2, true);

if ($options['subscriptions']) {

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
            if (!$options['pretend']) $destination->editSubscription($ssub->id, $ssub->title);
        }

        if ($categories) {
            log_msg('Syncing labels for ' . $ssub->id);
            foreach ($categories as $cat) {
                log_msg('    ' . $cat->id);
                if (!$options['pretend']) $ret = $destination->editSubscription($ssub->id, false, $cat->id, 'edit');
            }
        }

    }

}

if ($options['starred']) {
    /** Sync starred items **/
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
            if (!$options['pretend']) $destination->setEntryTag($sitem->id, $sitem->origin->streamId, 'user/-/state/com.google/starred');
        }

    }
}

if ($options['user-labels']) {
    /** Sync user defined labels **/

    /** Not transferring labels, which have feeds assigned to them. **/
    $subscriptions = $source->getSubscriptions();
    $slabels = $source->getTags();
    $labels = Array();
    foreach ($slabels->tags as $tag) {

        if (strpos($tag->id, 'state/com.google') !== false) {
            log_msg('Not fetching state ' . $tag->id);
            continue;
        }

        $tag->id = preg_replace('|user/\d+/|', 'user/-/', $tag->id);
        $labels[$tag->id] = $tag;
    }

    foreach ($subscriptions->subscriptions as $sub) {
        foreach ($sub->categories as $tag) {
            $tag->id = preg_replace('|user/\d+/|', 'user/-/', $tag->id);
            if (isset($labels[$tag->id])) {
                unset($labels[$tag->id]);
                log_msg('Skipping category "' . $tag->id . '", since it has feeds in it');
            }
        }
    }

    foreach ($labels as $tag) {
        log_msg('Moving tag: ' . $tag->id);

        $sitems = $source->getItems($tag->id, 0);
        $ditems = $destination->getItems($tag->id, 0);

        foreach ($sitems->items as $sitem) {
            $moved = false;
            foreach ($ditems->items as $ditem) {
                if ($ditem->id = $sitem->id) {
                    $moved = true;
                    break;
                }
            }
            if ($moved === false) {
                log_msg('Moving item "' . $sitem->id . '"');
                if (!$options['pretend']) $destination->setEntryTag($sitem->id, $sitem->origin->streamId, $tag->id);
            }
        }

    }

}


/** Mark all items as read, mark all unread items as unread. **/

if ($options['unread']) {

    $items = $source->getItems('user/-/state/com.google/reading-list', 0, 'user/-/state/com.google/read');
    if (!$options['pretend']) $destination->markAllAsRead();

    foreach ($items->items as $item) {
        if (!$options['pretend']) $destination->removeEntryTag($item->id, $item->origin->streamId, 'user/-/state/com.google/read');
    }

}


