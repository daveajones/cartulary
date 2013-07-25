<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  if( $g_platform == "mobile" ) {
      $mobile = TRUE;
  } else {
      $mobile = FALSE;
  }

  if( isset($_REQUEST['url']) ) {
    $jsondata = @file_get_contents($_REQUEST['url']);
  } else {
    $jsondata = get_river_as_json($uid);
  }

  $section = "River";
  $tree_location = "River (JSON)";

?>
<?header("Content-Type: text/plain");?>
<?echo format_json( "onGetRiverStream($jsondata)" );?>
