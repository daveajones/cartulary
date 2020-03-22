<?
  //Include admin api
  require_once "$confroot/$includes/admin.php";

  //Is this an admin feed?
  if( !is_admin($uid)) {
    header("Location: $startpage");
    exit(0);
  }
?>
