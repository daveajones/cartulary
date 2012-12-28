<?
  // Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  include "$confroot/$includes/util.php";
  include "$confroot/$includes/auth.php";
  include "$confroot/$includes/admin.php";
  include "$confroot/$includes/feeds.php";
  include "$confroot/$includes/opml.php";
  include "$confroot/$includes/posts.php";
  include "$confroot/$includes/articles.php";

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
        echo "Admin user not found.  Created: [cartulary@localhost] with password: [$newpassw].\n";
      }
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
