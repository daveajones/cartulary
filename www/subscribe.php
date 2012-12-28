<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  //We need a time stamp
  $tnow = time();

  //Get the feeds and outlines
  $outlines = get_outlines($uid, 9999, NULL, TRUE);
  $feeds = get_feeds($uid, 9999, TRUE);

  $section = "Subscribe";
  $tree_location = "Subscriptions";
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
<div class="row" id="divSubscribe">
  <form id="frmSubscribe" name="subscribe" action="<?echo $subscribecgi?>" method="POST">
  <fieldset>
  <div class="control-group">
    <div id="divSubscribeUrl"><input type="text" name="url" id="txtSubscribeUrl" placeholder="Paste in a feed, list or outline url..." />
      <div id="divSubscribeUpload">
        <input id="fileOpml" name="fileopml" class="btn btn-primary" type="file" value="Import" />
      </div>
    </div>
    <div id="divSubscribeButtons">
      <img class="imgSpinner" src="/images/spinner.gif" />
      <input id="btnSubscribe" name="submitSubscribe" class="btn btn-primary btnSubmit" type="submit" value="Subscribe" />
      <?if($platform != "mobile"){?>
      or <input id="btnFileShow" name="submitImport" class="btn btn-primary btnSubmit" type="button" value="Import..." />
      <?}?>
    </div>
  </div>
  </fieldset>
  </form>
</div>
<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->


<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->
<!-- ----- People:Start ----- -->
<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->
<div class="row" id="divSubscribePeople">
<div style="float:left;">
<h3>People I Follow</h3>
<ul class="ulPeople" style="list-style-type:none;display:inline;vertical-align:top;">
<?
foreach( $outlines as $ou ) {
  if($ou['type'] == 'sopml') {
  ?><li class="liPerson smallPerson person" id="<?echo $ou['id']?>" data-id="<?echo $ou['id']?>" data-title="<?echo $ou['title']?>">
    <img class="personimg" src="<?echo $ou['avatarurl']?>" />
    <span class="personcaption personcaption-block"><?echo $ou['ownername']?></span>
    <a class="persondrop aUnsubscribe"  href="#"><img src="/images/glyph/glyphicons_191_circle_minus-red.png" /></a>
    <ul class="personList" style="display:none;">
    <?
    foreach($ou['feeds'] as $ofd) {
	?><li data-id="<?echo $ofd?>">
        <?if( $feeds[$ofd]['errors'] > 50 ) {?><img title="This feed has had more than 50 errors." class="icon-warning" src="/images/blank.gif" /><?}?>
        <a class="nooverflow feed-title" href="<?echo $feeds[$ofd]['url']?>"><?echo $feeds[$ofd]['title']?></a>
	<span class="feed-properties">
	  <button name="sticky" type="button" class="<?echo ($feeds[$ofd]['sticky'] == 1 ? "btnFeedSticky" : "btnFeedNotSticky")?> stickybutton"></button>
          <button name="hidden" type="button" class="<?echo ($feeds[$ofd]['hidden'] == 1 ? "btnFeedHidden" : "btnFeedNotHidden")?> hiddenbutton"></button>
	</span>
        </li><?
    }
    ?>
    </ul>
    </li><?
  }
}
?>
</ul>
</div>
</div>
<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->


<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->
<!-- ----- Lists and Feeds:Start ----- -->
<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->
<div class="row" id="divSubscriptions">


<!-- ----- Button Bar:Start ----- -->
<div class="span10 mainControls">
<center class="btn-toolbar">
<img class="imgSpinner" src="/images/spinner.gif" />
<div class="btn-group">
<button id="btnUnsubscribe" title="Unsubscribe from checked feeds." class="btn btn-primary"> - </button>
<button id="btnRemoveFromList" title="Remove checked feeds from checked lists." class="btn btn-danger">&lt;&lt;</button>
<button id="btnAddToList" title="Add checked feeds to checked lists." class="btn btn-success">&gt;&gt;</button>
<button id="btnAddToNewList" title="Add checked feeds to a new list." class="btn btn-primary"> + </button>
</div>
</center>
</div>


<!-- ----- Feeds:Start ----- -->
<div class="span7">
<h3>Feeds I Follow <small>(<?echo count($feeds)?>)</small></h3>
<ul class="unstyled ulFeedsIFollow">
<?
foreach( $feeds as $fd ) {
  if( isset($outlines[$fd['oid']]) && $outlines[$fd['oid']]['type'] == 'sopml' ) {
    continue;
  }
  ?><li data-id="<?echo $fd['id']?>" class="nooverflow" title="<?if(strlen($fd['title'])>50){echo $fd['title'];}?>">
    <input type="checkbox" class="selector" data-id="<?echo $fd['id']?>" />
    <?if( $fd['errors'] > 50 ) {?><img title="This feed has had more than 50 errors." class="icon-warning" src="/images/blank.gif" /><?}?>
    <?if( ($tnow - $fd['linkedon']) < 10800 ) {?><span class="label label-success">New!</span><?}?>
    <a class="nooverflow" href="<?echo (empty($fd['link']) ? $fd['url'] : $fd['link'])?>"><?echo (empty($fd['title']) ? $fd['url'] : $fd['title'])?></a>
    <span class="feed-properties">
      <a class="aFeedRaw" href="<?echo $fd['url']?>"><img title="See the raw xml of this feed." class="icon-feed-raw" src="/images/blank.gif" /></a>
      <button name="sticky" title="Make this feed sticky." type="button" class="<?echo ($fd['sticky'] == 1 ? "btnFeedSticky" : "btnFeedNotSticky")?> stickybutton"></button>
      <button name="hidden" title="Hide this feed's posts." type="button" class="<?echo ($fd['hidden'] == 1 ? "btnFeedHidden" : "btnFeedNotHidden")?> hiddenbutton"></button>
    </span>
    </li><?
}
?>
</ul>
</div>


<!-- ----- Managed Lists:Start ----- -->
<div class="span3 divManagedLists">
<h3>Lists I Manage</h3>
<ul class="unstyled ulManagedLists">
<?
foreach( $outlines as $ou ) {
  if($ou['type'] == 'list' && $ou['control'] == 'local' && $ou['ownerid'] == $uid) {
  ?><li class="label liManagedList" id="<?echo $ou['id']?>" data-id="<?echo $ou['id']?>" data-title="<?echo $ou['title']?>">
      <input type="checkbox" class="selector" />
      <a class="manlistdrop aUnsubscribe" href="#"><img class="icon-remove-small" src="/images/blank.gif" /></a>
      <a href="<?echo $ou['url']?>"><?echo $ou['title']?></a>
      <ul class="outlineList ulListFeeds" style="display:none;">
      <?
      foreach($ou['feeds'] as $ofd) {
	?><li data-id="<?echo $ofd?>" class="nooverflow" style="color:black;">
        <input type="checkbox" class="selector" />
        <?if( $feeds[$ofd]['errors'] > 50 ) {?><img title="This feed has had more than 50 errors." class="icon-warning" src="/images/blank.gif" /><?}?>
        <a href="<?echo (empty($feeds[$ofd]['link']) ? $feeds[$ofd]['url'] : $feeds[$ofd]['link'])?>">
        <?echo (empty($feeds[$ofd]['title']) ? $feeds[$ofd]['url'] : $feeds[$ofd]['title'])?></a></li><?
      }
      ?>
      </ul>
    </li><?
  }
}
?>
</ul>
</div>

<br/>
<br/>

<!-- ----- External Lists:Start ----- -->
<div class="span3 divFollowedLists">
<h3>Lists I Follow</h3>
<ul class="unstyled ulOutsideLists">
<?
foreach( $outlines as $ou ) {
  if( $ou['type'] != 'sopml' && $ou['ownerid'] != $uid) {
  ?><li class="label liOutsideList" id="<?echo $ou['id']?>" data-id="<?echo $ou['id']?>" data-title="<?echo $ou['title']?>" style="background:#<?echo $ou['color']?>;margin:3px;color:black;">
    <a class="outlistdrop aUnsubscribe" href="#"><img class="icon-remove-small" src="/images/blank.gif" /></a><?echo $ou['title']?>
    <ul class="outlineList" style="display:none;">
    <?
    foreach($ou['feeds'] as $ofd) {
	?><li data-id="<?echo $ofd?>" class="nooverflow" style="color:black;"><a href="<?echo $feeds[$ofd]['url']?>"><?echo $feeds[$ofd]['title']?></a></li><?
    }
    ?>
    </ul>
  </li><?
  }
}
?>
</ul>
</div>




</div>
<!-- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- ----- -->

<?/*?>
<pre>
<h3>Outlines:</h3>
<?
	echo print_r($outlines, TRUE);
?>
<h3>Feeds:</h3>
<?
	echo print_r($feeds, TRUE);
?>
</pre>
<?*/?>


<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
