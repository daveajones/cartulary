<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  //Did they specify how many articles they wanted in the list?
  if( isset($_REQUEST['max']) ) {
    $max = $_REQUEST['max'];
  } else if ( isset($prefs['maxlist']) ){
    $max = $prefs['maxlist'];
  } else {
    $max = $default_max_list;
  }

  $section = "Articles";
  $tree_location = "Articles";
?>
<?header("Content-Type: text/plain");?>
<?echo build_opml_feed($uid,$max)?>

