<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    if(!can_connect_to_database()) {
        loggit(2, "Feedscan can't connect to database.");
        //Remove the lock file
        cronHelper::unlock();
        exit(1);
    }

    //Globals
    $maxprocess = 10;

    //Track the time spent
    $tstart = time();

    //Get arguments from the command line
    $rebuild = TRUE;
    $force = FALSE;
    if (in_array("norebuild", $argv)) {
        $rebuild = FALSE;
    }
    if (in_array("force", $argv)) {
        $force = TRUE;
    }

    $feeds = get_updated_feeds();

    $totalfeeds = count($feeds);
    $scancount = $totalfeeds;
    $errorfeeds = [];

    loggit(3, " ----- Start scan of [$scancount] of [$totalfeeds] feeds.");
    echo "Scanning [$scancount] of [$totalfeeds] feeds.\n\n";

    // Walk the feed list and get new items in each
    $ccount = 0;
    $execute = 0;
    foreach ($feeds as $feed) {

        $pid = pcntl_fork();

        if ($pid == -1) {
            //TODO: this is a bad way to handle
            loggit(2, "Feed scanner process could not fork!!");
            die("Could not fork!!!");
        } elseif ($pid) {
            $execute++;
            echo "Current Process Count: $execute\n";
            if ($execute >= $maxprocess){
                pcntl_wait($status);
                $execute--;
            }
        } else {
            //echo "I am the child, $ccount pid = $pid \n";

            //Get the feed
            $feedurl = $feed['url'];
            if (empty($feedurl)) {
                loggit(2, "Feed url was corrupt or blank: [$feedurl].");
                echo "Feed url was corrupt or blank: [$feedurl].\n";
                exit(1);
            }

            //Get new items in the feed
            $newitems = 0;

            echo "Checking feed: [" . $feed['title'] . " | " . $feed['url'] . "]...\n";

            //Make a timestamp
            $fstart = time();

            //Parse the feed and add new items to the database
            //loggit(3, "Checking feed: [ $feedcount | " . $feed['title'] . " | " . $feed['url'] . "].");
            $result = get_feed_items($feed['id'], NULL, FALSE);

            if ($result == -1) {
                loggit(2, "Error getting items for feed: [" . $feed['title'] . " | " . $feed['url'] . "]");
                echo "    Error getting items for feed: [" . $feed['title'] . " | " . $feed['url'] . "]\n";
                $errorfeeds[] = $feed['url'];
            } else if ($result == -2) {
                loggit(1, "Feed: [" . $feed['title'] . " | " . $feed['url'] . "] has no items.");
                //echo "    Feed is empty.\n";
            } else if ($result == -3) {
                loggit(1, "Feed: [" . $feed['title'] . " | " . $feed['url'] . "] is current.");
                //echo "    Feed is current.\n";
            } else {
                loggit(1, "Feed: [" . $feed['title'] . " | " . $feed['url'] . "] updated.");
                echo "    Feed updated.\n";
                $newitems += $result;
            }

            //Calculate time took to scan
            $ftime = time() - $fstart;
            loggit(3, "  FEEDSCAN RESULTS: [$feedurl] - [$ftime] seconds - [$newitems] new items.");
            echo "  FEEDSCAN RESULTS: [$feedurl] - [$ftime] seconds - [$newitems] new items.\n";

            echo "  -- Child: [$ccount] finished.\n";
            //loggit(3, "  -- Child: [$ccount] finished.");
            exit(0);
        }

        $ccount++;
    }

    //Calculate time took to scan
    loggit(3, "It took " . (time() - $tstart) . " seconds to scan [$ccount] of [$totalfeeds] feeds.");
    loggit(3, "TAIL -- Total checked: [$ccount].");
    echo "      It took " . (time() - $tstart) . " seconds to scan [$ccount] of [$totalfeeds] feeds.\n";
    echo "Total checked: [$ccount].\n";

    $rstart = time();

    //Rebuild the v2 search map counts if v2 enabled
    if ($cg_search_v2_enable && $totalfeeds > 0 && $rebuild) {
        echo "Rebuilding search word counts\n";
        loggit(3, "Rebuilding search word counts.");
        calculate_map_word_counts();
        calculate_map_word_today_counts();
    }

    loggit(3, "It took " . (time() - $rstart) . " seconds to rebuild search words.");
    echo "      It took " . (time() - $rstart) . " seconds to rebuild search words.\n";


    // Log and leave
    loggit(3, " ----- Feedscan finished.");

    echo "\n";

    //Remove the lock file
    cronHelper::unlock();
}
exit(0);