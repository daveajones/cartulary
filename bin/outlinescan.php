<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {


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


  //Flip through all of the user pub feeds and update them
  $users = get_users();
  foreach( $users as $u ) {
    $user = $u['id'];
    $feeds = get_pub_feeds($user);
    foreach( $feeds as $feed ) {
      $content = fetchUrl(get_final_url($feed['url']));
      if( $content != FALSE ) {
        $title = get_feed_title($content);
        $link = get_feed_link($content);
        if( $title != $feed['title'] || $link != $feed['link'] ) {
          update_pub_feed($user, $feed['url'], $title, $link);
          loggit(3, "DEBUG: Updating pub feed: [".$feed['url']."] for user: [$user] with title: [$title] and link: [$link].");
        }
      }
    }
  }


  //Remove the lock file
  cronHelper::unlock();
  }

  // Log and leave
  return(TRUE);
?>
