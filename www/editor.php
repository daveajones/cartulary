<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_init.php" ?>
<? require_once "$confroot/$includes/net.php"; ?>
<?

// See if we have a valid article id or url to get source xml from
$datestamp = date('YmdHis');
$type = "";
$filename = "";
$url = "";
$redirect = "";
$aid = "";
$rhost = "";
$privateOutline = FALSE;
$ipfs = FALSE;

if (isset($_REQUEST['aid'])) {
    $aid = trim($_REQUEST['aid']);
}
if (isset($_REQUEST['versionid'])) {
    $versionid = $_REQUEST['versionid'];
}

if (!empty($aid)) {
    $type = 2;
    $opmldata = get_article_as_opml($aid, $g_uid);
} else {
    //This wasn't an article edit request, so let's try and pull an external url
    if (isset($_REQUEST['url'])) {
        $url = trim($_REQUEST['url']);
    }
    if (!empty($url)) {
        $filename = stripText(basename($url), FALSE, "\.");

        //Get opml data and clean it
        $protpos = stripos($url, 'http');
        if ($protpos <> 0 || $protpos === FALSE) {
            $protpos = stripos($url, 'ipfs');
            if ($protpos <> 0 || $protpos === FALSE) {
                $badurl = true;
            } else {
                $ipfs = FALSE;
            }
        } else {
            $opmldata = "";
        }

        //Get side info
        if($ipfs == FALSE) {
            //Was a certain version requested?
            if(isset($versionid) && !empty($versionid)) {
                $seenfile = get_recent_file_version_by_url($g_uid, $url, $versionid);
                $opmldata = $seenfile['content'];
            }
            $seenfile = get_recent_file_by_url($g_uid, $url);

            if($seenfile['private'] == 1) {
                $privateOutline = TRUE;
                $seenfile = get_recent_file_by_url($g_uid, $url, TRUE);
                $opmldata = $seenfile['content'];
                loggit(3, "DEBUG(private): ".print_r($seenfile, TRUE));
            }

            //Set the redirect host for this document
            loggit(3, "DEBUG: Url to open - [" . $url . "]");
            $lookurl = str_replace('/opml/', '/html/', $url);
            $lookurl = str_replace('.opml', '.html', $lookurl);
            loggit(3, "DEBUG: Redirect url to look for - [" . $lookurl . "]");
            $rhost = get_redirection_host_name_by_url($lookurl);
            if (empty($rhost) && preg_match('/http.*\.opml/i', $url)) {
                $nurl = preg_replace('/\.(opml)$/i', '.html', $url);
                $rhost = get_redirection_host_name_by_url($nurl);
                loggit(3, "DEBUG: $nurl");
            }
        }
    }
}

//Clean opml
if (!empty($opmldata)) {
    $opmldata = preg_replace("/\ +\n\n\ +/", "\n\n", $opmldata);
    $opmldata = preg_replace("/\n\ +\n/", "\n\n", $opmldata);
    $opmldata = preg_replace("/[\r\n]\n+/", "\n\n", $opmldata);
    $opmldata = preg_replace("/\r?\n/", "", $opmldata);
    $opmldata = preg_replace("/\n/", "", $opmldata);
    $opmldata = preg_replace("/\'/", "\\\'", $opmldata);
}

$section = "Editor";
$tree_location = "Edit Outline";
?>

<? include "$confroot/$templates/$template_html_prehead" ?>
<head>
    <? include "$confroot/$templates/$template_html_meta" ?>
    <title><? echo $tree_location ?></title>
    <? include "$confroot/$templates/$template_html_styles" ?>
    <? include "$confroot/$templates/$template_html_scripts" ?>
    <script src="/script/webaudio_tools.js"></script>
    <script>
        //Globals
        <?loggit(3, "DEBUG: seenfile = ".print_r(isset($seenfile), TRUE));?>
        var type = <?if     (isset($seenfile['type'])) { echo $seenfile['type']; }
                 else if (isset($_REQUEST['type']) && is_numeric($_REQUEST['type'])) { echo $_REQUEST['type']; }
                 else { echo "0"; }
               ?>;
        var url = '<?echo $url?>';
            <?if(!empty($aid)) { ?>var aid = '<?echo $aid?>';
            <?; } else { ?>var aid = false;
        <?}?>
        var htmlurl = "";
        var title = "";
        var redirect = '<?echo $rhost?>';
        var lasttitle = "";
        var filename = '<?echo $filename?>';
        var ownerName = '<?echo get_user_name_from_uid($g_uid)?>';
        var ownerEmail = '<?echo get_email_from_uid($g_uid)?>';
        var ownerId = '';
        var oldfilename = "";
        var bufilename = '<?echo time()."-".$default_opml_export_file_name;?>';
        var badurl = false;
        <?if( isset($opmldata) && !empty($opmldata) ) {?>
        var initialOpmlText = '<?echo $opmldata?>';
        <?} else {?>
        var initialOpmlText = initialOpmltext;
        <?}?>
        var versionRequest = <?if(empty($versionid)) { echo "false"; } else { echo "true"; }?>;
        var initialRssOpmlText = '<?echo $cg_rsseditoropmltemplate?>';
        var includeDisqus = <?if(!isset($seenfile) || $seenfile['disqus'] == 0) { echo "false"; } else { echo "true"; }?>;
        var wysiwygOn = <?if(!isset($seenfile) || $seenfile['wysiwyg'] == 0) { echo "false"; } else { echo "true"; }?>;
        var watchedOutline = <?if(!isset($seenfile) || $seenfile['watched'] == 0) { echo "false"; } else { echo "true"; }?>;
        var lockedOutline = <?if(!isset($seenfile) || $seenfile['locked'] == 0) { echo "false"; } else { echo "true"; }?>;
        var privateOutline = <?if(!isset($seenfile) || $seenfile['private'] == 0) { echo "false"; } else { echo "true"; }?>;
        var wasLocked = <?if(!isset($seenfile) || $seenfile['locked'] == 0) { echo "false"; } else { echo "true"; }?>;
        var ipfsHash = '<?if(!isset($seenfile) || $seenfile['ipfshash'] == "") { echo ""; } else { echo $seenfile['ipfshash']; }?>';
        var privtoken = '<?if(!isset($seenfile) || $seenfile['privtoken'] == "") { echo ""; } else { echo $seenfile['privtoken']; }?>';
        var redirectHits = <?if(empty($rhost)) { echo 0; } else { echo get_redirection_hit_count_by_host($rhost); }?>;
        <?if( isset($badurl) ) {?>
        badurl = true;
        <?}?>
        <?include "$confroot/$scripts/editor.js"?>
    </script>
</head>
<? include "$confroot/$templates/$template_html_posthead" ?>


<body id="bodyEditOutline">
<? //--- Include the logo and menu bar html fragments --?>
<? include "$confroot/$templates/$template_html_logotop" ?>
<? include "$confroot/$templates/$template_html_menubar" ?>

<? //--- Stuff between the title and content --?>
<? include "$confroot/$templates/$template_html_precontent" ?>
<div id="divEditSheetOpen" class="sheet">
    <a class="sheetclose pull-right" href="#"> X </a>

    <div class="openbyurl"><a class="openbyurl" href="#">Open</a> by url or...</div>
    <div class="list-container pre-scrollable">
        <ul class="recentfilesopen"></ul>
    </div>
</div>

<div id="divEditSheetInclude" class="sheet">
    <a class="sheetclose pull-right" href="#"> X </a>

    <div class="openbyurl"><a class="openbyurl" href="#">Include</a> by url or...</div>
    <ul class="templateopen"></ul>
</div>

<div id="divEditSheetImport" class="sheet">
    <a class="sheetclose pull-right" href="#"> X </a>

    <div class="openbyurl"><a class="openbyurl" href="#">Import</a> by url or...</div>
    <ul class="templateopen"></ul>
</div>

<div class="row" id="divEditOutline">
    <? if (s3_is_enabled($g_uid) || sys_s3_is_enabled()) { ?>
        <div class="divOutlineTitle">
            <input class="rendertitle" checked="checked" type="checkbox" title="Render title and byline in the HTML?"/>
            <input class="title" placeholder="Title" type="text"/>
        </div>
        <div class="loading" style="display:none;"><i class="fa fa-refresh fa-spin"></i> Loading...</div>
        <div class="outlineinfo pull-right"></div>

        <div class="divOutlinerContainer">
            <div id="outliner"></div>
        </div>
    <? } else { ?>
        <center>You must have S3 enabled on either your server or in your user <a href="<? echo $prefspage ?>">prefs</a>
            to use the editor.
        </center>
    <? } ?>
</div>

<div id="divEditorEnclosures" class="dropzone hide">
    <input type="hidden" name="datestamp" class="datestamp" value="<? echo $datestamp ?>"/>

    <div id="editor_queue"><span id="spnEditorQueueText">Drop file(s) here or press 'esc' to dismiss...</span></div>
    <input type="file" name="file_upload" id="editor_upload"/>
</div>

<? //--- Include the footer bar html fragments -----------?>
<? include "$confroot/$templates/$template_html_footerbar" ?>
</body>

<? include "$confroot/$templates/$template_html_postbody" ?>
</html>