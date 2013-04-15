<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  // We have to have a valid article id to show
  $aid = $_REQUEST['aid'];
  if(empty($aid)) {
    header("Location: $errorpage?id=1");
    loggit(2,"There was no article id in the request: [$aid]");
    exit(0);
  }

  //Does the user have permission to see this article?
  if(!user_can_view_article($aid, $uid)) {
    header("Location: $errorpage?id=13");
    loggit(2,"The user tried to view a private article they weren't linked to: [$aid | $uid]");
    exit(0);
  }

  //Get the requested article
  $article = get_article($aid);

  $section = "Articles";
  $tree_location = "Show Article";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $article['title']?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>

<body id="bodyArticle">
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>

<div class="row" id="divPageArticle">
	<div id="article">
		<div id="headline"><h3><?echo $article['title']?></h3></div>
		<?if(!empty($article['sourceurl'])) {?>
                <div id="source">Source: <a href="<?echo $article['sourceurl']?>"><?echo $article['sourcetitle']?></a></div>
		<?}?>
		<div id="content"><?echo $article['content'];?></div>
		<div class="footer">
		<div class="divToolBox">
			<a class="opml" title="OPML Version" href="<?echo $showarticlepage.'-opml?aid='.$aid?>"><img class="icon-align-left" src="/images/blank.gif" alt="" /> </a>
			<a class="print" title="Printer Friendly" href="<?echo $showarticlepage.'-print?aid='.$aid?>"><img class="icon-print" src="/images/blank.gif" alt="" /> </a>

			<?$rturl = $article['url'];?>

			<?if ( !empty($prefs['linkblog']) ) {?>
			<a class="rt" title="Send to linkblog." href="<?echo $prefs['linkblog']?>/?description=<?echo urlencode($article['title'])?>&link=<?echo urlencode($rturl)?><?if(!empty($article['shorturl'])){echo '&shorturl='.urlencode($article['shorturl']);}?>"><img class="icon-retweet-1" src="/images/blank.gif" alt="" /> </a>
			<?} else {?>
			<a class="rt" title="Send to linkblog." href="<?echo $microblogpage?>?description=<?echo urlencode($article['title'])?>&link=<?echo urlencode($rturl)?><?if(!empty($article['sourceurl'])){echo '&source[url]='.urlencode($article['sourceurl']);}?><?if(!empty($article['sourcetitle'])){echo '&source[title]='.urlencode($article['sourcetitle']);}?><?if(!empty($article['shorturl'])){echo '&shorturl='.urlencode($article['shorturl']);}?>"><img class="icon-retweet-1" src="/images/blank.gif" alt="" /> </a>
	          	<?}?>
                        <a class="link" title="Link to original source url." href="<?echo $article['url'];?>"><img class="icon-hyperlink" src="/images/blank.gif" alt="" /> </a>
		</div>
                </div>
	</div>
</div>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
