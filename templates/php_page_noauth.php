<?
  // This is how we start pages that don't require a logged in session

  // Includes
  require_once "$confroot/$includes/util.php";
  require_once "$confroot/$includes/auth.php";
  require_once "$confroot/$includes/feeds.php";
  require_once "$confroot/$includes/opml.php";
  require_once "$confroot/$includes/posts.php";
  require_once "$confroot/$includes/admin.php";
  require_once "$confroot/$includes/articles.php";
  require_once "$confroot/$includes/subscribe.php";
  require_once "$confroot/$includes/outline.php";


  //Discover what kind of device this is
  $g_device = get_device_type();
  $device = $g_device;
  $g_platform = get_platform_type();

  //Platform over-rides?
  if( isset($_REQUEST['platform']) ) {
    $g_platform = $_REQUEST['platform'];
  }
  $platform = $g_platform;

  //Set the right timezone
  $g_timezone = $default_timezone;
  date_default_timezone_set($g_timezone);
?>
