<?
  // Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  include "$confroot/$includes/util.php";
  include "$confroot/$includes/auth.php";
  include "$confroot/$includes/feeds.php";
  include "$confroot/$includes/opml.php";

  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

  //Let's not scan while other scripts are in process
  if( file_exists("$confroot/$run/backup.php.lock") ) {
    cronHelper::unlock();
    loggit(1, "OUTLINESCAN: Backup is in progress, so skipping this scan.");
    exit(0);
  }


  //Globals and flags
  $ouchange = FALSE;

  //Get the outline list
  $outlines = get_all_outlines();
  echo "Scanning ".count($outlines)." outlines.\n\n";

  // Walk the outline list and get new items in each
  foreach($outlines as $outline) {
    loggit(1, "Checking outline: [".$outline['title']." | ".$outline['url']."]...");
    echo "Checking outline: [".$outline['title']." | ".$outline['url']."]...\n";

    $subcount = get_outline_subscriber_count($outline['id']);
    loggit(1, "Outline: [".$outline['title']."] has: [$subcount] subscribers.");
    echo "    Outline: [".$outline['title']."] has: [$subcount] subscribers.\n";
    //echo print_r(get_outline_subscribers($outline['id']), true)."\n";

    //Set the purge bit on all of this outline's feeds to true
    $purgecount = mark_all_outline_feeds_to_purge($outline['id']);
    echo "    Marked $purgecount feeds from outline: [".$outline['title']."] to purge.\n\n";

    //We don't want to scan subscriberless outlines or local lists
    if($subcount > 0 && $outline['type'] != 'list') {
    	$result = get_outline_items($outline['id']);
	echo "    Got [".count($result)."] items back from the get_outline_items() call.\n";

    	if($result == -1) {
	       	loggit(2, "Error getting items for outline: [".$outline['title']." | ".$outline['url']."]");
	      	echo "    Error getting items for outline: [".$outline['title']." | ".$outline['url']."]\n";
	        unmark_all_outline_feeds_to_purge($outline['id']);
    	} else if ($result == -2) {
	      	loggit(1, "Outline: [".$outline['title']." | ".$outline['url']."] has no items.");
	      	echo "    Outline is empty.\n";
	        unmark_all_outline_feeds_to_purge($outline['id']);
    	} else if ($result == -3) {
	      	loggit(1, "Outline: [".$outline['title']." | ".$outline['url']."] is current.");
	      	echo "    Outline is current.\n";
	        unmark_all_outline_feeds_to_purge($outline['id']);
    	} else {
	      	loggit(1, "Outline: [".$outline['title']." | ".$outline['url']."] updated.");
	      	echo "    Outline updated.\n";

		//Extract the feeds from the outline based on type
		$count = 0;
		if($outline['type'] == "sopml") {
			$feeds = get_pub_feeds_from_outline(get_outline_content($outline['id']));
		} else {
			$feeds = get_feeds_from_outline(get_outline_content($outline['id']));
		}
		echo "Found ".count($feeds)." feeds in this ".$outline['type']." outline.\n";
		//echo print_r($feeds, true)."\n";

		//Loop through each feed and add it. Then link all subscribed users to it
		$newcount = 0;
		foreach($feeds as $feed) {
	        	$users = get_outline_subscribers($outline['id']);
			foreach($users as $user) {
				loggit(1, "Found feed: [$feed] in outline: [".$outline['title']."].");
				$feedid = feed_exists($feed);
				if($feedid == FALSE) {
					//Feed did not exist
					$ouchange = TRUE;
					$newcount++;
					$fid = add_feed($feed, $user, FALSE, $outline['id']);
					loggit(1, "Linking feed: [".$fid."] to outline: [".$outline['title']."] for user: [$user].");
					echo "       Linking feed: [".$fid."] to outline: [".$outline['title']."] for user: [$user].\n";
				} else {
					//Links to SOPML outlines should always take priority
					$oul = get_outline_info(get_feed_outline_by_user($feedid, $user));
					if( empty($oul) ) {
						loggit(1, "Linking feed: [".$feedid."] to outline: [".$outline['title']."] for user: [$user].");
						echo "       Linking feed: [".$feedid."] to outline: [".$outline['title']."] for user: [$user].\n";
						link_feed_to_outline($feedid, $outline['id'], $user);
					} else
					if( $oul['type'] != "sopml" && $outline['type'] == "sopml" ) {
						loggit(3, "Re-Linking feed: [".$feedid."] to SOPML outline: [".$outline['title']."] for user: [$user].");
						echo "       Re-Linking feed: [".$feedid."] to SOPML outline: [".$outline['title']."] for user: [$user].\n";
						link_feed_to_outline($feedid, $outline['id'], $user);
					}
					loggit(1, "Feed: [".$feedid."] links to outline: [".$outline['title']."] for user: [$user]. Type: [".$oul['type']." | ".$outline['type']."].");
					echo "       Feed: [".$feedid."] links to outline: [".$outline['title']."] for user: [$user].\n";
				}
				unmark_outline_feed_to_purge($outline['id'], $feedid);

			}
		}
	    	echo "    Outline: [".$outline['title']."] had $newcount new feeds.\n";

	}

	//If outline was a local list
	if($outline['type'] == 'list') {
	        unmark_all_outline_feeds_to_purge($outline['id']);
	}

	//Purge feeds that are no longer in the outline
	$delcount = purge_outline_feeds($outline['id']);
    	echo "    $delcount old feeds purged from outline: [".$outline['title']."].\n";
    	echo "\n";
        //if($delcount > 0) {
	//	$ouchange = TRUE;
        //}

    } else {
	loggit(1, "No subscribers for: [".$outline['title']."]. Skipping.");
	echo "    No subscribers. Skipping outline.\n";
    }

  }

  //Make sure that admin users are subscribed to the admin log feed
  $fid = add_feed($system_url.$adminlogfeed, NULL, FALSE);
  $users = get_admin_users();
  foreach($users as $user) {
    loggit(1, "DEBUG: Linking admin user: [".$user['name']."] to admin log feed: [".$system_url.$adminlogfeed."]");
    link_feed_to_user($fid, $user['id']);
    mark_feed_as_sticky($fid, $user['id']);
  }

  //Remove the lock file
  cronHelper::unlock();
  }

  // Log and leave
  return(TRUE);
?>
