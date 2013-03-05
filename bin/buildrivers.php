<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

  	loggit(1, "Building rivers...");

  	//If there are any updated feeds, spawn a scan for them
  	$ufeeds = get_updated_feeds();
  	if( ($ufeeds != FALSE) && (count($ufeeds) > 0) ) {
          loggit(1, "Updated feeds found. Spawning a scan.");
    	  updateMarkedFeeds($ufeeds);
  	}

  	//Build the individual rivers for each user in the system
  	$tstart = time();
  	$users = get_users();
  	echo "Building ".count($users)." rivers.\n\n";
  	foreach($users as $user) {
	  if( river_updated($user['id']) ) {
     	    build_river_json( $user['id'], NULL, TRUE );
	  } else {
     	    build_river_json( $user['id'] );
	  }
    	  echo "Built river for user: [".$user['id']." | ".$user['name']."].\n";
    	  loggit(1, "Built river for user: [".$user['id']." | ".$user['name']."].");
  	}

  	//Calculate how long it took to build the rivers
  	$took = time() - $tstart;
  	loggit(1, "It took: [$took] seconds to build: [".count($users)."] rivers.");

  	loggit(1, "Done.");

        //Release the lock
  	cronHelper::unlock();
  }
  exit(0);


function updateMarkedFeeds($feeds = NULL) {
  //Get the feed list
  loggit(1, "Scanning ".count($feeds)." updated feeds.");

  // Walk the feed list and get new items in each
  foreach($feeds as $feed) {
    //Make a timestamp
    $fstart = time();

    $subcount = get_feed_subscriber_count($feed['id']);
    loggit(1, "Feed: [".$feed['title']."] has: [$subcount] subscribers.");

    //Set the purge bit on all of this feeds items to true
    //$purgecount = mark_all_feed_items_to_purge($feed['id']);
    //echo "    Marked $purgecount items to purge.\n\n";

    if($subcount > 0) {
        $result = get_feed_items($feed['id']);
        if($result == -1) {
                loggit(2, "Error getting items for feed: [".$feed['title']." | ".$feed['url']."]");
                echo "    Error getting items for feed: [".$feed['title']." | ".$feed['url']."]\n";
                //unmark_all_feed_items_to_purge($feed['id']);
        } else if ($result == -2) {
                loggit(1, "Feed: [".$feed['title']." | ".$feed['url']."] has no items.");
                echo "    Feed is empty.\n";
                //unmark_all_feed_items_to_purge($feed['id']);
        } else if ($result == -3) {
                loggit(1, "Feed: [".$feed['title']." | ".$feed['url']."] is current.");
                echo "    Feed is current.\n";
                //unmark_all_feed_items_to_purge($feed['id']);
        } else {
                loggit(1, "Feed: [".$feed['title']." | ".$feed['url']."] updated.");
                echo "    Feed updated.\n";
        }
    } else {
        loggit(1, "No subscribers for: [".$feed['title']."]. Skipping.");
        echo "    No subscribers. Skipping feed.\n";
    }

    //Mark all purge items as old
    //$oldcount = flip_purge_to_old($feed['id']);
    //echo "    Marked $oldcount items as old.\n";
    echo "      It took ".(time() - $fstart)." seconds to scan this feed.\n";
    echo "\n";

    //Pause for a second.  Let's not get greedy with cpu time
    //sleep(1);
  }
  echo "\n";
}
?>
