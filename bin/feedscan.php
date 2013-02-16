<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

  //Let's not scan while other scripts are in process
  if( file_exists("$confroot/$run/backup.php.lock") ) {
    cronHelper::unlock();
    loggit(1, "FEEDSCAN: Backup is in progress, so skipping this scan.");
    exit(0);
  }

  //Track the river scan time
  $tstart = time();

  //Get the feed list
  $feeds = get_all_feeds();
  $totalfeeds = count($feeds);

  //Only scan the top 25% of feeds per scan
  //$scancount = $totalfeeds;
  $scancount = ($totalfeeds * .20) + 1;

  loggit(3, " ----- Start scan of [$scancount] of [$totalfeeds] feeds.");
  echo "Scanning [$scancount] of [$totalfeeds] feeds.\n\n";

  // Walk the feed list and get new items in each
  $ccount = 0;
  $ncount = 0;
  $dcount = 0;
  $newitems = 0;
  foreach($feeds as $feed) {
    loggit(1, "Checking feed: [".$feed['title']." | ".$feed['url']."]...");
    echo "Checking feed: [".$feed['title']." | ".$feed['url']."]...\n";

    //Make a timestamp
    $fstart = time();

    $subcount = get_feed_subscriber_count($feed['id']);
    loggit(1, "Feed: [".$feed['title']."] has: [$subcount] subscribers.");
    echo "    Feed: [".$feed['title']."] has: [$subcount] subscribers.\n";

    //Let's not scan while these other scripts are in process
    for( $i=1 ; file_exists("$confroot/$run/outlinescan.php.lock") && $i < 9 ; $i++ ) {
      loggit(1, "FEEDSCAN: Sleeping($i) while outlinescan is running.");
      sleep(5);
    }

    if($subcount > 0) {
      	loggit(1, "Checking feed: [ $ccount | ".$feed['title']." | ".$feed['url']."].");
    	$result = get_feed_items($feed['id']);
    	if($result == -1) {
        	loggit(2, "Error getting items for feed: [".$feed['title']." | ".$feed['url']."]");
        	echo "    Error getting items for feed: [".$feed['title']." | ".$feed['url']."]\n";
    	} else if ($result == -2) {
	      	loggit(1, "Feed: [".$feed['title']." | ".$feed['url']."] has no items.");
      		echo "    Feed is empty.\n";
    	} else if ($result == -3) {
	      	loggit(1, "Feed: [".$feed['title']." | ".$feed['url']."] is current.");
      		echo "    Feed is current.\n";
    	} else {
	      	loggit(1, "Feed: [".$feed['title']." | ".$feed['url']."] updated.");
	      	echo "    Feed updated.\n";
		$ncount++;
                $newitems += $result;
    	}

        $ccount++;
    } else {
	loggit(3, "No subscribers for: [".$feed['title']." | ".$feed['id']."]. Deleting feed from the system.");
	echo "    No subscribers. Removing feed, items and catalog links.\n";
	delete_feed($feed['id']);
        $dcount++;
    }

    echo "      It took ".(time() - $fstart)." seconds to scan this feed.\n";
    echo "\n";

    //We stop scanning once we hit our 25% mark
    if($ccount >= $scancount) {
      break;
    }
  }
  echo "\n";

  //Calculate time took to scan the river
  loggit(3, "It took ".(time() - $tstart)." seconds to scan [$ccount] of [$totalfeeds] feeds.");
  loggit(3, "Total checked: [$ccount]. Updated: [$ncount]. Deleted: [$dcount]. New items: [$newitems].");
  echo "      It took ".(time() - $tstart)." seconds to scan [$ccount] of [$totalfeeds] feeds.\n";
  echo "Total checked: [$ccount]. Updated: [$ncount]. Deleted: [$dcount]. New items: [$newitems].\n";


  // Log and leave
  loggit(3, "Feedscan finished.");

  //Remove the lock file
  cronHelper::unlock();
  }
  exit(0);
?>
