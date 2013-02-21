<?
  // This is how we start every standard page that needs authentication
  // We set up a minimal set of globals that we know every page will need

  // Includes
  require_once "$confroot/$includes/util.php";
  require_once "$confroot/$includes/auth.php";
  require_once "$confroot/$includes/admin.php";
  require_once "$confroot/$includes/feeds.php";
  require_once "$confroot/$includes/opml.php";
  require_once "$confroot/$includes/posts.php";
  require_once "$confroot/$includes/articles.php";

  // Valid session?  If not, get lost
  if(!is_logged_in()) {
    header("Location: $loginpage");
    exit(0);
  }

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

  //What device version?
  $g_device_version = get_device_version();
  if( isset($_REQUEST['device_version']) ) {
    $g_device_version = $_REQUEST['device_version'];
  }

  //Who is this user?
  $g_uid = get_user_id_from_sid(get_session_id());
  if( isset($_REQUEST['uid']) && is_admin($g_uid) ) {
    $g_uid = $_REQUEST['uid'];
  }
  $uid = $g_uid;

  //Get the users prefs
  $g_prefs = get_user_prefs($g_uid);
  if(!$g_prefs) {
    $g_prefs = init_user_prefs($g_uid);
  }
  $prefs = $g_prefs;

  //Set the right timezone
  $g_timezone = $g_prefs['timezone'];
  if( !empty($g_timezone) ) {
    date_default_timezone_set($g_timezone);
  } else {
    date_default_timezone_set($default_timezone);
  }

  //Set name and email once. These are used a lot.
  $g_myname = get_user_name_from_uid($g_uid);
  $username = $g_myname;
  $g_myemail = get_email_from_uid($g_uid);
?>
