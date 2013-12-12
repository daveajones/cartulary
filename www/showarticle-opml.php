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
<?header("Content-Type: text/plain");?>
<?include "$confroot/$templates/$template_opml_head"?>
        <head>
                <title><?echo $article['title']?></title>
                <dateCreated><?echo date("D, d M Y H:i:s O", $articles[1]['createdon'])?></dateCreated>
                <dateModified><?echo date("D, d M Y H:i:s O", $articles[1]['createdon'])?></dateModified>
                <ownerName><?echo get_user_name_from_uid($uid)?></ownerName>
                <ownerId><?echo $uid?></ownerId>
                <expansionState></expansionState>
                <expansionState></expansionState>
                <vertScrollState>1</vertScrollState>
                <windowTop>146</windowTop>
                <windowLeft>107</windowLeft>
                <windowBottom>468</windowBottom>
                <windowRight>560</windowRight>
        </head>
        <body>
                <outline text="<?echo htmlspecialchars(trim(str_replace("\n", '', $article['title'])))?>">
                        <outline text="<?echo urlencode( $article['url'] )?>" />
                        <outline text="<?echo date("D, d M Y H:i:s O", $article['createdon'])?>" />
                        <outline text="<?echo urlencode($system_url.$showarticlepage."?"."aid=".$article['id']);?>" />
                        <outline text="Content">
<?foreach ( explode("</p>", trim( str_replace("\n", '', $article['content'] ))) as $line ) {?>
                                <outline text="<?echo htmlspecialchars(trim(str_replace("\n", '', $line)))?>" />
<?}?>
                        </outline>
                </outline>
        </body>
<?include "$confroot/$templates/$template_opml_feet"?>
