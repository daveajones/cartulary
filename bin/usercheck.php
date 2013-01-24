<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if(($pid = cronHelper::lock()) !== FALSE) {

    //Make sure that an admin user exists
    if(get_admin_users() == FALSE) {
      if( create_user('cartulary@localhost', TRUE, TRUE, TRUE, 'Cartulary Admin') ) {
        $newadmin = get_user_id_from_email('cartulary@localhost');
        $newpassw = random_gen(12);
        set_user_as_admin( $newadmin );
        set_password( $newadmin, $newpassw );
        loggit(2, "Admin user not found.  Created: [cartulary@localhost] with password: [$newpassw].");
        echo "  Username: cartulary@localhost\n";
        echo "  Password: $newpassw\n";

	//Let's subscribe this user to the default subscription list, just to get
        //some feeds and an outline in the system
        $oid = add_outline('http://localhost'.$default_subscription_list_url, $newadmin);
      }
    }

    //Make sure that admin users are subscribed to the admin log feed
    $fid = add_feed('http://localhost'.$adminlogfeed, NULL, TRUE, NULL);
    $users = get_admin_users();
    foreach($users as $user) {
      loggit(1, "Linking admin user: [".$user['name']."] to admin log feed: [http://localhost".$adminlogfeed."]");
      link_feed_to_user($fid, $user['id']);
      mark_feed_as_sticky($fid, $user['id']);
    }

    //Get the users list
    $users = get_users();

    //Reset badlogin counters for all users
    foreach( $users as $user ) {
      $uid = $user['id'];
      badlogin_reset( get_email_from_uid($uid) );
    }

  //Remove the lock file
  cronHelper::unlock();
  }

  // Log and leave
  return(TRUE);
?>
