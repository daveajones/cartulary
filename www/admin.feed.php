<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?include "$confroot/$templates/php_page_admin.php"?>
<?
  //Get all of the feeds in the system
  $feeds = get_all_feeds_with_stats(50);
  $feedcount = count($feeds);

  $section = "Admin-feed";
  $tree_location = "Feed Administration";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
<script>
<?include "$confroot/$scripts/admin.js"?>
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>

<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>

<div class="row page-header" id="divPageTitle">
	<h1><?echo $tree_location?> <small>(<?echo $feedcount?> feeds.)</small></h1>
</div>

<?//--- Stuff between the title and content --?>
<?include "$confroot/$templates/$template_html_precontent"?>

<div class="row" id="divAdmin">

		<div id="divFeedAdmin">
                <!----- Feed management table ----->
                <table id="feedlist" class="table table-hover">
		  <thead>
		    <tr>
                      <th>&nbsp;</th>
                      <th title="The title of the feed.">Title</th>
		      <th title="Subscriber count.">Subscribers</th>
		      <?if($platform != "mobile") {?>
		      <th title="Consecutive error count.">Errors</th>
		      <th title="The date this feed was first subscribed to.">Born</th>
                      <th title="The last time this feed changed.">Modified</th>
                      <th title="Average number of new items per scan.">Avg.New</th>
		      <th title="Average time it takes to scan this feed.">Avg.Time</th>
		      <th title="Average checks between new items.">Avg.Interval</th>
		      <?}?>
                    </tr>
		  </thead>
		  <tbody>
                    <? $count = 0; ?>
                    <? foreach($feeds as $feed) { ?>
                    <? $rowclass = (($count % 2) == 0) ? "odd" : "even"; ?>
		      <tr class="<?echo $rowclass?>" id="tr_feed_<?echo $feed['id']?>">
                        <td>
                          <a id="delete_<?echo $feed['id']?>" class="aDeletefeed" title="Delete feed from the system." href="#"><img id="imgDeletefeed" class="icon-delete" src="/images/blank.gif" /></a>
                          <a id="reset_<?echo $feed['id']?>" class="aResetfeed" title="Force a scan of this feed." href="#"><img id="imgResetfeed" class="icon-reset" src="/images/blank.gif" /></a>
                        </td>
			<td><a href="<?echo $feed['url']?>"><?echo $feed['title']?></a></td>
                        <td><?echo $feed['subscribers']?></td>
             		<?if($platform != "mobile") {?>
                        <td><?echo $feed['errors']?></td>
                        <td><?echo date('Y/n/j\<\b\r\/\>g:i A',$feed['createdon'])?></td>
                        <td><?echo date('Y/n/j\<\b\r\/\>g:i A',$feed['lastmod'])?></td>
                        <td><?echo $feed['avgnewitems']?></td>
                        <td><?echo $feed['avgchecktime']?></td>
                        <td><?echo $feed['avgnewinterval']?></td>
		        <?}?>
                      </tr>
		    <? $count++; ?>
                    <? } ?>
		  </tbody>
		</table>
                </div>
                <!--------------------------------->
</div>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>

