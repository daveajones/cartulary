<?
  // This is for cgi scripts that do not need authentication
  // We set up a minimal set of globals that we know will be needed

  // Includes
  require_once "$confroot/$includes/util.php";
  require_once "$confroot/$includes/auth.php";
  require_once "$confroot/$includes/admin.php";
  require_once "$confroot/$includes/feeds.php";
  require_once "$confroot/$includes/opml.php";
  require_once "$confroot/$includes/posts.php";
  require_once "$confroot/$includes/articles.php";
  require_once "$confroot/$includes/subscribe.php";
  require_once "$confroot/$includes/outline.php";


  //Discover what kind of device this is
  $g_device = get_device_type();
  if( isset($_REQUEST['device']) ) {
    $g_device = $_REQUEST['device'];
  }
  $device = $g_device;

  //What platform is this?
  $g_platform = get_platform_type();
  if( isset($_REQUEST['platform']) ) {
    $g_platform = $_REQUEST['platform'];
  }
  $platform = $g_platform;

  $g_timezone = $default_timezone;
  date_default_timezone_set($g_timezone);

?>
