<?
  // This is how we start every standard shell script
  // We set up a minimal set of globals that we know every script will need

  // Includes
  require_once "$confroot/$includes/util.php";
  require_once "$confroot/$includes/auth.php";
  require_once "$confroot/$includes/admin.php";
  require_once "$confroot/$includes/feeds.php";
  require_once "$confroot/$includes/opml.php";
  require_once "$confroot/$includes/posts.php";
  require_once "$confroot/$includes/articles.php";

  //Set the right timezone
  date_default_timezone_set($default_timezone);
?>
