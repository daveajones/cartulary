<?
  //Include admin api
  require_once "$confroot/$includes/admin.php";

  //Is this an admin?
  if( !is_admin($uid)) {
    header("Location: $errorpage?code=9");
    exit(0);
  }
?>
