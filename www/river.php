<?
  //It's important that the river page not cache in the browser
  header("Cache-Control: no-cache, max-age=0, must-revalidate, no-store");
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?

  //Get feed locations
  if( $g_platform == "mobile" ) {
    $rjfile = $default_river_json_mobile_file_name;
    $rjurl = $riverpagejsonmobile;
  } else {
    $rjfile = $default_river_json_file_name;
    $rjurl = $riverpagejson;
  }
/*
  if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {
    $jsonurl = get_s3_url($uid, NULL, $rjfile);
  } else {
    $jsonurl = $rjurl;
  }
*/

  $section = "River";
  $tree_location = "River";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
<script>
        var Hidebigpics = false;
        var Hidepics = false;
        <?if ( $prefs['mobilehidebigpics'] == 1 && $platform == "mobile" ) {?>
        Hidebigpics = true;
        <?}?>
        <?if ( $prefs['mobilehidepics'] == 1 && $platform == "mobile"  ) {?>
        Hidepics = true;
        <?}?>

</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>
<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
</div>
<div class="container-fluid">
<?include "$confroot/$templates/$template_html_menubar"?>


<?//--- Stuff between the title and content --?>
<?include "$confroot/$templates/$template_html_precontent"?>

<div class="row-fluid" id="divRiver">

<div id="stream-wrap" class="stream-wrap">
  <div id="stream" class="stream">
    <p class="notice"><img id="imgLoadRiverSpinner" class="imgSpinner" src="/images/spinner.gif" alt="" /> Loading river...</p>
	<div id="stream-items" class="stream-items"></div>
  </div>
</div>

<?if( $platform != "mobile" ) {?>
<div id="stream-sidebar-right">
	<div id="divActiveFeeds">
	<ul class="feedlist">
	</ul>
	</div>
</div>
<?}?>

<script id="template" type="text/x-jquery-tmpl">
<?include "$confroot/$scripts/river-temp-item.js"?>
</script>

<script id="template-sticky" type="text/x-jquery-tmpl">
<?include "$confroot/$scripts/river-temp-sticky.js"?>
</script>

<script id="template-subitem" type="text/x-jquery-tmpl">
<?include "$confroot/$scripts/river-temp-subitem.js"?>
</script>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
<script>
<?include "$confroot/$scripts/river-foot.js"?>
</script>
</body>
<?include "$confroot/$templates/$template_html_postbody"?>
</html>
