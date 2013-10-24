<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {


    //Globals and flags
    $ouchange = FALSE;
    $servcheck = array();

    //Get the outline list
    $outlines = get_all_outlines();
    echo "Scanning " . count($outlines) . " outlines.\n\n";

    // Walk the outline list and get new items in each
    foreach ($outlines as $outline) {
        loggit(1, "Checking outline: [" . $outline['title'] . " | " . $outline['url'] . "]...");
        echo "Checking outline: [" . $outline['title'] . " | " . $outline['url'] . "]...\n";

        //How many people subscribe to this outline?
        $subcount = get_outline_subscriber_count($outline['id']);
        loggit(1, "Outline: [" . $outline['title'] . "] has: [$subcount] subscribers.");
        echo "    Outline: [" . $outline['title'] . "] has: [$subcount] subscribers.\n";

        //Skip outlines with zero subscribers
        if ($subcount == 0) {
            continue;
        }

        //Set the purge bit on all of this outline's feeds to true
        $purgecount = mark_all_outline_feeds_to_purge($outline['id']);
        echo "    Marked $purgecount feeds from outline: [" . $outline['title'] . "] to purge.\n\n";

        //Get the new content from the outline
        $result = get_outline_items($outline['id']);
        echo "    Got [" . count($result) . "] items back from the get_outline_items() call.\n";
        if ($result == -1) {
            loggit(2, "Error getting items for outline: [" . $outline['title'] . " | " . $outline['url'] . "]");
            echo "    Error getting items for outline: [" . $outline['title'] . " | " . $outline['url'] . "]\n";
            unmark_all_outline_feeds_to_purge($outline['id']);
            continue;
        } else if ($result == -2) {
            loggit(1, "Outline: [" . $outline['title'] . " | " . $outline['url'] . "] has no items.");
            echo "    Outline is empty.\n";
            unmark_all_outline_feeds_to_purge($outline['id']);
            continue;
        } else if ($result == -3) {
            loggit(1, "Outline: [" . $outline['title'] . " | " . $outline['url'] . "] is current.");
            echo "    Outline is current.\n";
            unmark_all_outline_feeds_to_purge($outline['id']);
            continue;
        } else {
            loggit(1, "Outline: [" . $outline['title'] . " | " . $outline['url'] . "] updated.");
            echo "    Outline updated.\n";
        }


        // ---------- Social Outlines ------------
        switch ($outline['type']) {
            case 'sopml':
                //Extract the feeds from the outline based on type
                $count = 0;
                $feeds = get_pub_feeds_from_outline(get_outline_content($outline['id']));

                echo "Found " . count($feeds) . " pub feeds in this social outline.\n";

                //Loop through each feed and add it. Then link all subscribed users to it
                $newcount = 0;
                foreach ($feeds as $feed) {
                    $users = get_outline_subscribers($outline['id']);
                    foreach ($users as $user) {
                        loggit(1, "Found feed: [$feed] in outline: [" . $outline['title'] . "].");
                        $feedid = feed_exists($feed);
                        if ($feedid == FALSE) {
                            //Feed did not exist
                            $ouchange = TRUE;
                            $newcount++;
                            $fid = add_feed($feed, $user, FALSE, $outline['id']);
                            loggit(1, "Linking feed: [" . $fid . "] to outline: [" . $outline['title'] . "] for user: [$user].");
                            echo "       Linking feed: [" . $fid . "] to outline: [" . $outline['title'] . "] for user: [$user].\n";
                        } else {
                            //Links to SOPML outlines should always take priority
                            $oul = get_outline_info(get_feed_outline_by_user($feedid, $user));
                            if (empty($oul) || $oul['type'] != "sopml") {
                                loggit(1, "Linking feed: [" . $feedid . "] to outline: [" . $outline['title'] . "] for user: [$user].");
                                echo "       Linking feed: [" . $feedid . "] to outline: [" . $outline['title'] . "] for user: [$user].\n";
                                link_feed_to_outline($feedid, $outline['id'], $user);
                            } else {
                                loggit(1, "Feed: [" . $feedid . "] links to outline: [" . $outline['title'] . "] for user: [$user]. Type: [" . $oul['type'] . " | " . $outline['type'] . "].");
                                echo "       Feed: [" . $feedid . "] links to outline: [" . $outline['title'] . "] for user: [$user].\n";
                            }
                        }
                        unmark_outline_feed_to_purge($outline['id'], $feedid);
                    }
                }
                echo "    Outline: [" . $outline['title'] . "] had $newcount new feeds.\n";

                //Check for a server declaration
                $server = get_server_address_from_outline(get_outline_content($outline['id']));
                if (!empty($server)) {
                    $server = trim($server, '/');
                    if (!isset($servcheck[$server])) {
                        $serverinfo = fetchUrl(trim(get_final_url('http://' . $server), '/') . $getserverinfocgi . '?addr=' . $system_fqdn . '&guid=' . $cg_main_serverguid);
                        if ($serverinfo != FALSE) {
                            $serverinfo = json_decode($serverinfo, TRUE);
                            loggit(3, print_r($serverinfo, TRUE));
                            $serverguid = $serverinfo['guid'];
                            if (!empty($serverguid) && $serverguid != $cg_main_serverguid) {
                                update_server_address($serverinfo['guid'], $server);
                            }
                        }
                        $servcheck[$server] = 1;
                    } else {
                        loggit(3, "Already polled server: [$server].");
                    }
                }

                //Purge feeds that are no longer in the outline
                $delcount = purge_outline_feeds($outline['id']);
                echo "    $delcount old feeds purged from outline: [" . $outline['title'] . "].\n";
                echo "\n";
                break;


            // ---------- Plain OPML files ------------
            case 'opml':
                //For plain opml documents, we need to find the diff between old content and new
                //and create a new pseudo-document off of the difference
                loggit(1, "The outline type is blank. Skipping.");
                echo "The outline type is blank. Skipping.\n";
                break;


            // ---------- OPML reading lists ----------
            case 'list':
                //Extract the feeds from the outline
                $count = 0;
                $feeds = get_feeds_from_outline(get_outline_content($outline['id']));
                echo "Found " . count($feeds) . " feeds in this reading list.\n";

                //Loop through each feed and add it. Then link all subscribed users to it
                $newcount = 0;
                foreach ($feeds as $feed) {
                    $users = get_outline_subscribers($outline['id']);
                    foreach ($users as $user) {
                        loggit(1, "Found feed: [$feed] in outline: [" . $outline['title'] . "].");
                        $feedid = feed_exists($feed);
                        if ($feedid == FALSE) {
                            //Feed did not exist
                            $ouchange = TRUE;
                            $newcount++;
                            $fid = add_feed($feed, $user, FALSE, $outline['id']);
                            loggit(1, "Linking feed: [" . $fid . "] to outline: [" . $outline['title'] . "] for user: [$user].");
                            echo "       Linking feed: [" . $fid . "] to outline: [" . $outline['title'] . "] for user: [$user].\n";
                        } else {
                            //If this feed also exists in an SOPML outline then don't link it again
                            //We want SOPML outlines to always take priority in linking
                            $oul = get_outline_info(get_feed_outline_by_user($feedid, $user));
                            if (empty($oul) && $oul['ownerid'] != $user) {
                                loggit(1, "Linking feed: [" . $feedid . "] to outline: [" . $outline['title'] . "] for user: [$user].");
                                echo "       Linking feed: [" . $feedid . "] to outline: [" . $outline['title'] . "] for user: [$user].\n";
                                link_feed_to_outline($feedid, $outline['id'], $user);
                            } else {
                                loggit(1, "Feed: [" . $feedid . "] links to outline: [" . $outline['title'] . "] for user: [$user]. Type: [" . $oul['type'] . " | " . $outline['type'] . "].");
                                echo "       Feed: [" . $feedid . "] links to outline: [" . $outline['title'] . "] for user: [$user].\n";
                            }
                        }
                        unmark_outline_feed_to_purge($outline['id'], $feedid);
                    }
                }
                echo "    Outline: [" . $outline['title'] . "] had $newcount new feeds.\n";
                unmark_all_outline_feeds_to_purge($outline['id']);
                break;


            // ---------- Unknown Outline Type ----------
            default:
                loggit(1, "The outline type is blank. Skipping.");
                echo "The outline type is blank. Skipping.\n";
                echo "\n";
                break;
        }
        // -- End Switch --
    }


    //Flip through all of the user pub feeds and update them
    $users = get_users();
    foreach ($users as $u) {
        $user = $u['id'];
        $feeds = get_pub_feeds($user);
        if (empty($feeds)) {
            continue;
        }
        foreach ($feeds as $feed) {
            $content = fetchUrl(get_final_url($feed['url']));
            if ($content != FALSE) {
                $title = get_feed_title($content);
                $link = get_feed_link($content);
                if ($title != $feed['title'] || $link != $feed['link']) {
                    update_pub_feed($user, $feed['url'], $title, $link);
                    loggit(3, "DEBUG: Updating pub feed: [" . $feed['url'] . "] for user: [$user] with title: [$title] and link: [$link].");
                }
            }
        }
    }


    //Remove the lock file
    cronHelper::unlock();
}

// Log and leave
return (TRUE);
?>
