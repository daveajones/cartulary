<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
  //Let's not run twice
  if( ($pid = cronHelper::lock()) !== FALSE ) {

    //Let's not scan while other scripts are in process
    if( file_exists("$confroot/$run/backup.php.lock") ) {
      cronHelper::unlock();
      loggit(3, "ARCHIVE: Backup is in progress, so skipping this scan.");
      exit(0);
    }

    //Get the users list
    $users = get_users();

    //For each user that has S3 enabled, build the current monthly archives
    foreach( $users as $user ) {
      $uid = $user['id'];
      if( s3_is_enabled($uid) ) {
	//Build the cartulary archive
        build_rss_feed($uid, 9999, TRUE);
        build_opml_feed($uid, 9999, TRUE);

	//Build the microblog archive
	build_blog_rss_feed($uid, 9999, TRUE);
        build_blog_opml_feed($uid, 9999, TRUE);
        build_blog_html_archive($uid, 9999, TRUE);
        build_blog_script_widget($uid);

	//Build social outline
	build_social_outline($uid);

        loggit(1, "User: [$uid]'s static files were rebuilt.");
      }
    }

    //Release the lock
    cronHelper::unlock();
  }
  exit(0);
?>
