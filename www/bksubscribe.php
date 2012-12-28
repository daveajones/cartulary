<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  $section = "Subscribe";
  $tree_location = "Feed Finder";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
<script>
<?include "$confroot/$scripts/subscribe.js"?>
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>
<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>


<!-- ----- Page header:Start ----- -->
<div class="row page-header" id="divPageTitle">
  <h1><?echo $tree_location?></h1>
</div>
<!-- ----- Page header:Stop  ----- -->


<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->
<!-- ----- Subscribe Form:Start ----- -->
<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->
<div class="row" id="divFeedFinder">
<?
if( isset($_REQUEST['url']) ) {
	//Can we get an alternate link from the html at this url?
	$url = trim($_REQUEST['url']);
        $url = get_final_url($url);
        $content = fetchUrl($url);
        $btnclass = "btn-warning";
        $btndesc = "Might be a feed.";
	$feedlocs = getAlternateLinkUrl($content);
	loggit(3, "DEBUG: ".print_r($feedlocs, TRUE));
	if( !empty($feedlocs) && $content != FALSE ) {
          ?><h2>Feeds found: <img class="imgSpinner" src="/images/spinner.gif" /></h2><?
          ?><ul class="unstyled"><?
          foreach($feedlocs as $feedloc) {
	    $url = absolutizeUrl($feedloc['url'], $url);
            if( !empty($feedloc['title']) ) {
              $linkdesc = $feedloc['title'];
            } elseif( !empty($feedloc['text']) ) {
              $linkdesc = $feedloc['text'];
            } else {
              $linkdesc = $url;
	    }
            loggit(3, "Built url: [$url] from the bookmarklet.");
            ?><br/><?
	    ?><li style="word-break:break-all;">
  		<form id="frmFeedFinderSubscribe" class="feedFinderForm" name="subscribe" action="<?echo $subscribecgi?>" method="POST">
   		  <fieldset>
  		    <div class="control-group">
    		      <input type="hidden" name="url" id="txtSubscribeUrl" value="<?echo $url?>" />
    		      <div id="divSubscribeButtons">
                        <? //----- We do a first pass to see if we can get a rough idea of what each feed is ----------
                        if( $feedloc['element'] == "link" && stripos($feedloc['type'], 'rss') !== FALSE && stripos($url, 'comments') === FALSE && stripos($feedloc['title'], 'comments') === FALSE ):
                          $btnclass = "btn-success";
                          $btndesc = "Main RSS Feed";
                        elseif( $feedloc['element'] == "link" && stripos($feedloc['type'], 'atom') !== FALSE && stripos($url, 'comments') === FALSE && stripos($feedloc['title'], 'comments') === FALSE ):
                          $btnclass = "btn-success";
                          $btndesc = "Main ATOM Feed";
                        elseif( $feedloc['element'] == "link" && (stripos($url, 'comments') !== FALSE || stripos($feedloc['title'], 'comments') !== FALSE) ):
                          $btnclass = "btn-warning";
                          $btndesc = "Looks like a comments feed.";
                        elseif( $feedloc['element'] == "link" && (stripos($feedloc['type'], 'opml') !== FALSE || stripos($url, 'opml') !== FALSE) ):
                          $btnclass = "btn-warning";
                          $btndesc = "Looks like an OPML outline.";
			else:
                          $btnclass = "btn-danger";
                          $btndesc = "Could be a feed, but not sure.";
			endif;
                        ?>
                        <? //----- Now we drill down and see if specific circumstances are met and adjust as necessary
                        if( stripos($feedloc['title'], '.92') !== FALSE ):
                          $btnclass = "btn-warning";
                          $btndesc = "An older format of RSS.";
                        elseif( stripos($feedloc['title'], '1.0') !== FALSE ):
                          $btnclass = "btn-warning";
                          $btndesc = "An older format of RSS.";
                        elseif( $feedloc['element'] != "link" && stripos($url, 'rss.xml') !== FALSE ):
                          $btnclass = "btn-warning";
                          $btndesc = "Really looks like an rss feed, but not sure of source.";
			endif;
                        ?>
      		        <h3><input id="btnSubscribe" name="submitSubscribe" class="btn <?echo $btnclass?> btnSubmit" type="submit" value="Subscribe" /> to [<a href="<?echo trim($url)?>"><?echo htmlspecialchars($linkdesc)?></a>] - <span><?echo $btndesc?></span>
                        </h3>
    		      </div>
  		    </div>
  		  </fieldset>
  		</form>
	    </li><?
          }
          ?><ul><?
        } else {
          ?><center><h2>We didn't find a feed in this page. :-(</h2></center><?
        }
} else {
	?><center><h2>You must provide a valid url to search.</h2></center><?
}
?>
</div>
<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->


<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
