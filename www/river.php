<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  //It's important that the river page not cache in the browser
  header("Cache-Control: no-cache, max-age=0, must-revalidate, no-store");

  //Get feed locations
  if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {
    $s3blogfeed = get_s3_url($uid, NULL, get_microblog_feed_filename($uid));
    $s3cartfeed = get_s3_url($uid, NULL, get_cartulary_feed_filename($uid));
    $s3blogopml = get_s3_url($uid, NULL, get_microblog_opml_filename($uid));
    $s3cartopml = get_s3_url($uid, NULL, $default_opml_file_name);
    $s3sopml = get_s3_url($uid, NULL, $default_social_outline_file_name);
    $s3widget = get_s3_url($uid, NULL, get_microblog_js_filename($uid));
  }

  //Did they specify how many posts they wanted in the list?
  if( isset($_REQUEST['max']) ) {
    $max = $_REQUEST['max'];
  } else if ( isset($prefs['maxriveritems']) ){
    $max = $prefs['maxriveritems'];
  } else {
    $max = $default_max_river_items;
  }

  //Handle max all request
  if($max == 0) {
    $max = 9999;
  }

  //Get feed locations
  if( $platform == "mobile" ) {
    $rjfile = $default_river_json_mobile_file_name;
    $rjurl = $riverpagejsonmobile;
  } else {
    $rjfile = $default_river_json_file_name;
    $rjurl = $riverpagejson;
  }
  if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {
    $jsonurl = get_s3_url($uid, NULL, $rjfile);
  } else {
    $jsonurl = $rjurl;
  }

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
<?include "$confroot/$scripts/river-head.js"?>
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>
<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>



<!-- Page header and title area -->
<div class="row page-header" id="divPageTitle">
        <h1><?echo get_user_name_from_uid($uid)?></h1>
</div>



<!-- Page header and title area -->
<div class="row " id="divRiverBeta">
	<div id="divRiverStreamWrapper" class="span12">

	</div>

<?if($platform != "mobile") {?>
<div id="stream-sidebar-left">
  <div id="divMe" style="float:left;">
    <h3><u>Quick Blog</u></h3>
    <div id="divSaySomething">
      <form id="frmSaySomething" name="blogpost" action="/cgi/in/blogpost" method="POST"<?if($device=="android") {?> enctype="multipart/form-data"<?}?>>
      <fieldset>
      <div class="control-group">

      <!-- Universal post text box. -->
      <div id="divSaySomethingText">
        <textarea name="content" id="txtSaySomething" placeholder="Lay your thing down here..." class="smallSaySomething"></textarea>
      </div>

      <!-- The submit button box. -->
      <div id="divSaySomethingSubmit">
        <!-- Granular prefs this post. -->
	<?if( twitter_is_enabled($uid) ) {?>
	<div class="quickTweet">
          <input style="display:none;" id="chkTwitter" name="tweet" type="checkbox" />
          <span id="spnTwitter" >
            <img id="imgTwitter" class="icon-notwitter" title="Post to Twitter also?" src="/images/blank.gif" alt="" />
            <span id="spnTweetWarning">Tweet will be truncated...</span>
          </span>
	</div>
        <?}?>

        <input id="btnSaySomething" name="btnsaysomething" class="btn-primary" value="Post It!" type="submit" />
        <img id="imgSpinner" src="/images/spinner.gif" alt="" />
      </div>
    </div>
    </fieldset>
    </form>
  </div>

  <div id="divMicroblogWidget"><script id="scrMicroblogWidget" src="<?echo $s3widget?>?ts=<?echo time()?>"></script></div>
  </div>
</div>
<?}?>

<div id="stream-wrap">
  <div id="stream">
    <p class="notice"><img id="imgLoadRiverSpinner" class="imgSpinner" src="/images/spinner.gif" alt="" /> Loading river...</p>
  </div>
</div>

<?if($platform != "mobile" && $platform != "tablet") {?>
<div id="stream-sidebar-right">
        <div id="divMyFeeds" class="pull-right" style="float:right;">
	        <h3><u>My Feeds</u></h3>
	        <ul id="ulMyStuff">
        		<li><a href="<?echo $s3sopml?>">My Social Outline</a></li>
	                <li><a href="<?echo $s3blogfeed?>"><?echo get_microblog_title($uid)?></a></li>
	                <?if($prefs['publicdefault'] != 1) {?>
	                <li><a href="<?echo $s3cartfeed?>"><?echo get_cartulary_title($uid)?></a></li>
	                <?}?>
	                <?
	                  $pubfeeds = get_pub_feeds($uid);
	                  if(!empty($pubfeeds)) {
	                    foreach($pubfeeds as $pubfeed) {
	                      ?><li class="pubfeed" data-url="<?echo $pubfeed['url']?>" data-title="<?echo $pubfeed['title']?>">
	                        <a class="aDeletePubFeed" href='<?echo $delpubfeedcgi."?"."url=".urlencode($pubfeed['url']);?>'><img class="icon-remove-small" src="/images/blank.gif" alt="" /></a>
                                <a href="<?echo $pubfeed['url']?>"><?echo $pubfeed['title']?></a></li><?
	                    }
	                  }
	                ?>
	        </ul>
                <div id="divAddPubFeed">
	                <form id="frmAddPubFeed" name="feedadd" action="<?echo $addpubfeedcgi?>" method="POST">
        	                <input id="txtPubFeedUrl" type="text" name="url" placeholder="Paste your feed url here..." />
                                <input id="btnPubFeedSubmit" name="submit" class="btn-primary" type="submit" value="+" />
                                <img id="imgPubFeedSpinner" class="imgSpinner" src="/images/spinner.gif" alt="" />
                        </form>
                </div>
	</div>

	<div id="divBuddies" class="pull-right" style="float:right;">
  	<h3><u>Following</u></h3>
  	<?
    	$outlines = get_outlines($uid, $max);
    	foreach($outlines as $ou) {
      	  if($ou['type'] == "sopml") {
      	    ?><img class="minitar" src="<?echo (!empty($ou['avatarurl']) ? $ou['avatarurl'] : $default_avatar_url)?>" alt="" /><?
      	  }
    	}
  	?>
	</div>
</div>
<?}?>


<script id="template" type="text/x-jquery-tmpl">
<?include "$confroot/$scripts/river-temp.js"?>
</script>

<script>
<?include "$confroot/$scripts/river.js"?>
</script>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
<script>
<?include "$confroot/$scripts/river-foot.js"?>
</script>
</body>
<?include "$confroot/$templates/$template_html_postbody"?>
</html>
