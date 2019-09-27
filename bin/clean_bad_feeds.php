<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?


//TODO: turn this back on in cron job


//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    //Check for command line switch whatif
    $doit = TRUE;
    if (in_array("whatif", $argv) || in_array("-whatif", $argv) || in_array("--whatif", $argv) || in_array("/whatif", $argv)) {
        $doit = FALSE;
        echo "Test mode.\n\n";
        loggit(3, "Cleaning bad/dead feeds - TEST MODE.");
    } else {
        loggit(3, "Cleaning bad/dead feeds.");
    }
    echo "Cleaning bad/dead feed urls from the database.\n";

    $allfeeds = get_all_feeds(NULL, TRUE, TRUE);
    $totalfeeds = count($allfeeds);
    $count = 0;
    $dcount = 0;
    foreach ($allfeeds as $feed) {
        $fi = get_feed_info($feed['id']);
        $id = $feed['id'];
        $url = $fi['url'];
        $link = $fi['link'];
        $subcount = $fi['subscribercount'];

        //Check for feeds that have zero subscribers and remove them
        if ($subcount === 0) {
            if ($doit) {
                delete_feed($id);
                echo "Deleted feed: [$url] because it had no subscribers.\n";
                loggit(2, "Deleted feed: [$url] because it had no subscribers.");
            } else {
                echo "Would have deleted feed: [$url] because it had no subscribers.\n";
                loggit(2, "Would have deleted feed: [$url] because it had no subscribers.");
            }

            $dcount++;
            continue;
        }

        //Delete feeds if they have more than 1000 errors, the last http status is 400+
        //and the last good http status was over 30 days ago
        if ( $fi['errors'] > 1000 && $fi['lasthttpstatus'] >= 400 && $fi['lastgoodhttpstatus'] < (time() - (86400 * 30)) && $fi['lastupdate'] < (time() - (86400 * 30)) ) {
            if ($doit) {
                delete_feed($id);
                echo "Deleted feed: [$url] because it's now a 404 page.\n";
                loggit(2, "Deleted feed: [$url] because it's now a 404 page.");
            } else {
                echo "Would have deleted feed: [$url] because it's now a 404 page.\n";
                loggit(2, "Would have deleted feed: [$url] because it's now a 404 page.");
            }

            $dcount++;
            continue;
        }

        //Mark feeds as dead if they have more than 1000 errors, the last http status is 400+
        //and the last good http status was over 15 days ago
        if ( $fi['errors'] > 1000 && $fi['lasthttpstatus'] >= 400 && $fi['lastgoodhttpstatus'] < (time() - (86400 * 15)) && $fi['lastupdate'] < (time() - (86400 * 15)) ) {
            if ($doit) {
                mark_feed_as_dead($id);
                echo "Marked feed: [$url] as dead" . "\n";
                loggit(2, "Marked feed: [$url] as dead.");
            } else {
                echo "Would have marked feed: [$url] as dead" . "\n";
                loggit(2, "Would have marked feed: [$url] as dead.");
            }

            $count++;
        }
    }

    //This is the new API for identifying poor quality feeds
    $poorfeeds = get_poor_feeds();
    $totalfeeds = $totalfeeds + count($poorfeeds);
    foreach( $poorfeeds as $feed) {
        $id = $feed['id'];
        $url = $feed['url'];

        if ($doit) {
            mark_feed_as_dead($id);
            echo "Marked feed: [$url] as dead" . "\n";
            loggit(2, "Marked feed: [$url] as dead.");
        } else {
            echo "Would have marked feed: [$url] as dead" . "\n";
            loggit(2, "Would have marked feed: [$url] as dead.");
        }

        $count++;
    }


    if ($count > 0) {
        if ($doit) {
            echo "Marked: [$count] feeds as dead.\n";
            loggit(3, "Marked: [$count] feeds as dead.");
        } else {
            echo "Would have marked: [$count] feeds as dead.\n";
            loggit(3, "Would have marked: [$count] feeds as dead.");
        }
    } else {
        echo "No bad/dead feeds found.\n";
        loggit(3, "No bad/dead feeds found.");
    }

    if ($dcount > 0) {
        if ($doit) {
            echo "Deleted: [$dcount] feeds that had zero subscribers.\n";
            loggit(3, "Deleted: [$dcount] feeds that had zero subscribers.");
        } else {
            echo "Would have deleted: [$dcount] feeds that had zero subscribers.\n";
            loggit(3, "Would have deleted: [$dcount] feeds that had zero subscribers.");
        }
    }


    //Release the lock
    cronHelper::unlock();
}
exit(0);