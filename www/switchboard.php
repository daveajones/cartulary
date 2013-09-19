<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  // Is this user inside or outside
  $location = inside_or_outside();

  // Temporary over-ride until the system goes live
  if(!allowed_inside($uid)) {
    $location = "outside";
  } else {
    $location = "inside";
  }

  // Figure out where we need to be sent
  $subpage = $_REQUEST['page'];

  // Send to the right place
  header("Location: /$subpage.$location");
?>
