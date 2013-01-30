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
    $s3blogopml = get_s3_url($uid, NULL, $default_blog_opml_file_name);
    $s3bloghtml = get_s3_url($uid, NULL, $default_blog_archive_file_name);
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
	<h1><?echo $tree_location?> <a class="rss" href="<?if(!empty($s3blogfeed)) { echo $s3blogfeed; } else { echo $microblogpage."-rss"; }?>" label="RSS"><img class="icon-rss" src="/images/blank.gif" alt="" /></a><a class="html" href="<?if(!empty($s3bloghtml)) { echo $s3bloghtml; } else { echo $microblogpage."-html"; }?>" label="HTML"><img class="icon-html" src="/images/blank.gif" alt="" /></a><a class="opml" href="<?if(!empty($s3blogopml)) { echo $s3blogopml; } else { echo $microblogpage."-opml"; }?>" label="OPML"><img class="icon-opml" src="/images/blank.gif" alt="" /></a><a class="import" href="#mdlFeedImport" data-toggle="modal" label="Import Feed"><img class="icon-import" src="/images/blank.gif" alt="" /></a>
            <small><span id="message"></span></small>
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
		<!-- Title box. -->
        	<div id="divTitle">
		<img class="icon-text-height" src="/images/blank.gif" /><input name="title" id="txtTitle" type="text" placeholder="Title your post..." value="<?echo $title?>" />
		</div>

		<!-- Link box. -->
        	<div id="divLink">
		<img class="icon-hyperlink-small" src="/images/blank.gif" /><input name="link" id="txtLink" type="text" placeholder="Paste a link here..." value="<?echo $link?>" />
		</div>

                <?if( (s3_is_enabled($uid) || sys_s3_is_enabled()) && ($device != "ipad" && $device != "iphone" && $device != "wphone") ) {?>
		<a id="btnAttachFile" class="pull-right"><img class="icon-attach" src="/images/blank.gif" alt="" /></a>
		<?}?>
		</div>

		<!-- Enclosure drop zone. -->
                <?if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {?>
		<div id="divEnclosures">
			Files:
			<input type="hidden" name="datestamp" value="<?echo $datestamp?>" />
			<div id="queue">Drop file(s) here...</div>
		</div>

		<!-- Uploadify controls. -->
		<div id="divUpload">
			<?if($device=="android") {?>
			<input type="file" name="file_mobile" id="fileMobile" />
			<?} else {?>
	        	<input type="file" name="file_upload" id="file_upload" /><a id="aUploadTrigger" href="#"">Upload Files</a>
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
            	      ?><li>Enclosure: <input type="text" name="extenclosure[<?echo $eec?>][url]" value="<?echo $extenclosure['url']?>" /><?
            	      if( !empty($extenclosure['type']) ){?><input type="text" name="extenclosure[<?echo $eec?>][type]" value="<?echo $extenclosure['type']?>" /><?}
            	      if( !empty($extenclosure['length']) ){?><input type="text" name="extenclosure[<?echo $eec?>][length]" value="<?echo $extenclosure['length']?>" /></li><?}
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
          	  ?><li>Source: <input type="text" name="source[url]" value="<?echo trim($source['url'])?>" /><?
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
<?if($device!="android") {?>
$(function() {
    $('#file_upload').uploadifive({
        'auto'         : true,
        'method'       : 'post',
        'dnd'          : true,
        'queueID'      : 'queue',
        'uploadScript' : '/cgi/in/upload',
        'formData'     : {
          'datestamp'  : '<?echo $datestamp?>'
	},
        'onUploadFile' : function(file) {
            var filename = file.name.replace(/\s/g,"");
            $('#frmBlogPost').append('<input type="hidden" name="enclosure[]" value="<?echo $datestamp."_"?>' + filename.toLowerCase() + '" />');
        }
    })
});
<?}?>
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
      <img id="imgSpinner" alt="" src="/images/spinner.gif" />
    </form>
  </div>
</div>

</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
