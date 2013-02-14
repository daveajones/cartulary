<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init_with_followup.php"?>
<?
  //Did they specify how many posts they wanted in the list?
  if( isset($_REQUEST['max']) ) {
    $max = $_REQUEST['max'];
  } else if ( isset($prefs['maxlist']) ){
    $max = $prefs['maxlist'];
  } else {
    $max = $default_max_list;
  }

  //Get parameters if any were set
  //loggit(3, print_r($_REQUEST, TRUE));
  $description = "";
  $title = "";
  $link = "";
  $enclosure = "";
  if( isset($_REQUEST['description']) ) {
    $description = $_REQUEST['description'];
    //If this is a twitter re-tweet, then prefix it appropriately
    if( isset($_REQUEST['source']['url']) && strpos($_REQUEST['source']['url'], "twitter.com") !== FALSE ) {
      $description = 'RT @'.$description;
    }
  }
  if( isset($_REQUEST['link']) ) {
    $link = $_REQUEST['link'];
  }
  if( isset($_REQUEST['enclosure']) ) {
    $enclosure = $_REQUEST['enclosure'];
  }

  //Check for show all
  if($max == 0) {
    $max = 9999;
  }

  //Clear out the spooler of any files by this user
  $spoolfiles = glob("$confroot/$spooldir/$uid"."_??????????????_*");
  array_walk($spoolfiles,'unlinkspool');
  function unlinkspool($t) { unlink($t); }

  //Get feed locations
  if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {
    $s3blogfeed = get_s3_url($uid, NULL, get_microblog_feed_filename($uid));
    $s3blogopml = get_s3_url($uid, NULL, get_microblog_opml_filename($uid));
    $s3bloghtml = get_s3_url($uid, NULL, get_microblog_html_filename($uid));
  }

  //Generate a datestamp
  $datestamp = date('YmdHis');

  $section = "Microblog";
  $tree_location = "Microblog";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>
<body ondragenter="javascript:showEnclosures();">
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>

<div class="row page-header" id="divPageTitle">
	<h1><?echo $tree_location?><small><span id="message"></span></small>
        </h1>
</div>

<div class="row" id="divBlogPost">
	<div class="blogPostWrapper">
	<form id="frmBlogPost" name="blogpost" action="/cgi/in/blogpost" method="POST"<?if($device=="android") {?> enctype="multipart/form-data"<?}?>>
        <fieldset>
        <div class="control-group">
		<!-- The submit button box. -->
		<div id="divPostSubmit">
                  	<!-- Granular prefs this post. -->
                        <?if( twitter_is_enabled($uid) ) {?>
                        <input id="chkTweet" name="tweet" type="checkbox" <?if( isset($_REQUEST['source']['url']) && strpos($_REQUEST['source']['url'], "twitter.com") !== FALSE ) {?>checked<?}?> /><span id="spnTweet"><img id="imgTweet" class="icon-notwitter" src="/images/blank.gif" alt="" /><span id="spnTweetWarning">Tweet will be truncated...</span></span>
			<?}?>

			<div id="divPostIt">
        		        <span id="spnCharCount"></span>
		                <input id="btnSubmit" name="submitbtn" class="btn btn-primary btn-large" type="submit" value="Post It!" /> <img id="imgSpinner" alt="" src="/images/spinner.gif" />
			</div>
                </div>

		<!-- Universal post text box. -->
                <div id="divPostText">
                  <textarea name="content" id="txtContent" placeholder="Lay your thing down here..."><?echo $description?></textarea>
                </div>

		<div id="divLower">

                <?if( (s3_is_enabled($uid) || sys_s3_is_enabled()) && ($device != "ipad" && $device != "iphone" && $device != "wphone") ) {?>
		<a id="btnAttachFile" title="Attach enclosures."><img class="icon-attach" src="/images/blank.gif" alt="" /></a>
		<?}?>

		<!-- Title box. -->
        	<div id="divTitle">
		<img class="icon-text-height" src="/images/blank.gif" /><input name="title" id="txtTitle" type="text" placeholder="Title your post..." value="<?echo $title?>" />
		</div>

		<!-- Link box. -->
        	<div id="divLink">
		<img class="icon-hyperlink-small" src="/images/blank.gif" /><input name="link" id="txtLink" type="text" placeholder="Paste a link here..." value="<?echo $link?>" />
		</div>

		</div>

		<!-- Enclosure drop zone. -->
                <?if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {?>
		<div id="divEnclosures" class="dropzone">
			<input type="hidden" name="datestamp" value="<?echo $datestamp?>" />
			<div id="queue"><span id="spnQueueText">Drop file(s) here...</span></div>
		</div>

		<!-- Uploadify controls. -->
		<div id="divUpload">
			<?if($device=="android") {?>
	        	<input type="file" name="file_mobile" id="fileMobile" />
			<?} else {?>
			You can drag and drop files into this page to attach them.<br/>
                        Or you can
	        	<input type="file" name="file_upload" id="file_upload" /> ... then ... <a id="aUploadTrigger" href="#"">Upload Them</a> if they don't upload automatically.
			<?}?>
		</div>
		<?}?>

		<!-- External enclosures box. -->
        	<div id="divExtEnclosures">
		<?if(!empty($_REQUEST['extenclosure'])) {
		  ?><ul><?
          	  $extenclosures = $_REQUEST['extenclosure'];
		  $eec = 0;
                  $lastencurl = "";
          	  foreach( $extenclosures as $extenclosure ) {
                    if( $lastencurl != $extenclosure['url'] ) {
		      $lastencurl = $extenclosure['url'];
            	      ?><li><a href="#" class="aRemoveListItem"><img class="icon-remove-small" src="/images/blank.gif" /></a>
		      <?if( url_is_a_picture($extenclosure['url']) || strpos($extenclosure['type'], "image") !== FALSE ) {?>
                      <img class="imgenclosure" src="<?echo $extenclosure['url']?>" />
                      <?}?>
                      External Enclosure:<br/>
                      <input type="text" name="extenclosure[<?echo $eec?>][url]" value="<?echo $extenclosure['url']?>" /><?
            	      if( !empty($extenclosure['type']) ){?><input type="text" class="hide" name="extenclosure[<?echo $eec?>][type]" value="<?echo $extenclosure['type']?>" /><?}
            	      if( !empty($extenclosure['length']) ){?><input type="text" class="hide" name="extenclosure[<?echo $eec?>][length]" value="<?echo $extenclosure['length']?>" /></li><?}
  		      $eec++;
                    }
          	  }
        	?></ul><?}?>
        	</div>

		<!-- RT source info. -->
        	<div id="divRtSource">
		<?if(!empty($_REQUEST['source'])) {
		  ?><ul><?
          	  $source = $_REQUEST['source'];
          	  ?><li><a href="#" class="aRemoveListItem"><img class="icon-remove-small" src="/images/blank.gif" /></a>
                  Source Attribution:<br/>
                  <input type="text" name="source[url]" value="<?echo trim($source['url'])?>" /><?
          	  ?><input type="text" name="source[title]" value="<?echo trim($source['title'])?>" /></li><?
        	  ?></ul><?}?>
        	</div>
	</div>
        </fieldset>
        </form>
        </div>
</div>

<div class="row statline">
  <div class="showall">
    <a href="?max=0">Show all</a>
  </div>
</div>

<script id="microblog-template" type="text/x-jquery-tmpl">
<?include "$confroot/$scripts/microblog-temp.js"?>
</script>

<div class="row" id="divPostList"></div>


<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
<script>
<?include "$confroot/$scripts/microblog-upload.js"?>
</script>
<script>
<?include "$confroot/$scripts/microblog.js"?>
</script>

<!-- The import feed modal dialog -->
<div id="mdlFeedImport" class="modal hide fade">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Enter the url of the RSS feed to import:</h3>
  </div>
  <div class="modal-body">
    <br/>
    <form id="frmFeedImport" name="feedimport" action="/cgi/in/import.feed" method="POST">
      URL: <input type="text" id="txtFeedImportUrl" class="urlinput" name="url" />
      <input id="btnFeedImportSubmit" class="btn-primary" name="btnFeedImportSubmit" type="submit" value="Import" />
    </form>
    <div id="divWaitMessage" class="hide pagination-centered"><img id="imgSpinner" alt="" src="/images/spinner.gif" /> This could take a while. Please wait...</div>
  </div>
</div>

</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
