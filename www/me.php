<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_init.php" ?>
<?
//Get feed locations
if (s3_is_enabled($uid) || sys_s3_is_enabled()) {
    $s3blogfeed = get_s3_url($uid, NULL, get_microblog_feed_filename($uid));
    $s3cartfeed = get_s3_url($uid, NULL, get_cartulary_feed_filename($uid));
    $s3blogopml = get_s3_url($uid, NULL, $default_blog_opml_file_name);
    $s3cartopml = get_s3_url($uid, NULL, $default_opml_file_name);
    $s3sopml = get_s3_url($uid, NULL, $default_social_outline_file_name);
    $s3readlist = get_s3_url($uid, NULL, $default_readinglist_file_name);
}

//Feed titles
if (!empty($prefs['microblogtitle'])) {
    $mbtitle = $prefs['microblogtitle'];
} else {
    $mbtitle = "What $username is saying.";
}
if (!empty($prefs['cartularytitle'])) {
    $cartitle = $prefs['cartularytitle'];
} else {
    $cartitle = "What $username is reading.";
}

$section = "Me";
$tree_location = "My Social Outline";
?>

<? include "$confroot/$templates/$template_html_prehead" ?>
<head>
    <? include "$confroot/$templates/$template_html_meta" ?>
    <title><? echo $tree_location ?></title>
    <? include "$confroot/$templates/$template_html_styles" ?>
    <? include "$confroot/$templates/$template_html_scripts" ?>
    <script>
        $(document).ready(function () {
            $('#frmAddPubFeed').ajaxForm({
                dataType: 'json',
                beforeSubmit: function () {
                    $('#imgSpinner').show();
                    $('#btnSubmit').attr("disabled", true);
                },
                success: function (data) {
                    $('#message').empty();
                    $('#message').append(data.description);
                    if (data.status == "false") {
                        $('#message').removeClass('msggood').addClass('msgbad');
                    } else {
                        $('#message').removeClass('msgbad').addClass('msggood');
                        $('#ulMyStuff').append('<li><a href="' + data.url + '">' + data.title + '</a></li>');
                    }
                    $('#message').show();
                    $('#imgSpinner').hide();
                    $('#btnSubmit').attr("disabled", false);
                }
            });
        });
    </script>
</head>
<? include "$confroot/$templates/$template_html_posthead" ?>
<? //--- The body tag and anything else needed ---?>
<? include "$confroot/$templates/$template_html_bodystart" ?>
<? //--- Include the logo and menu bar html fragments --?>
<? include "$confroot/$templates/$template_html_logotop" ?>
<? include "$confroot/$templates/$template_html_menubar" ?>

<div class="row" id="divMe">
    <div id="divSopmlVcard">

        <div id="divSopmlVcardInner">
            <img class="avatar"
                 src="<? echo(!empty($prefs['avatarurl']) ? $prefs['avatarurl'] : $default_avatar_url) ?>">
            <span class="nameoverlay"><? echo get_user_name_from_uid($uid) ?></span>
            <ul class="defaultopml">
                <li><a href="<? echo $s3sopml ?>">Social Outline Feed</a></li>
                <li><a href="<? echo $s3readlist ?>">Reading List Feed</a></li>
            </ul>
        </div>


        <div id="divSopmlVcardList">
            <div class="bodyspacer">&nbsp;</div>
            <h3>My Stuff</h3>
            <ul id="ulMyStuff">
                <li><a href="<? echo $s3blogfeed ?>"><? echo $mbtitle ?></a></li>
                <li><a href="<? echo $s3cartfeed ?>"><? echo $cartitle ?></a></li>
                <?
                $pubfeeds = get_pub_feeds($uid);
                foreach ($pubfeeds as $pubfeed) {
                    ?>
                    <li><a href="<? echo $pubfeed['url'] ?>"><? echo $pubfeed['title'] ?></a></li><?
                }
                ?>
            </ul>
            <div id="divAddPubFeed">
                <form id="frmAddPubFeed" name="feedadd" action="<? echo $addpubfeedcgi ?>" method="POST">
                    <input id="txtPubFeedUrl" type="text" name="url" placeholder="Paste your feed url here..."/>
                    <input id="btnPubFeedSubmit" name="submit" class="btn-primary" type="submit" value="+"/>
                    <img id="imgSpinner" src="/images/spinner.gif"/>
                </form>
            </div>

            <h3>My Buddies</h3>
            <ul class="buddylist">
                <?
                $outlines = get_outlines($uid);
                foreach ($outlines as $outline) {
                    if ($outline['type'] == "sopml") {
                        ?>
                        <li><img class="avatar64" src="<? echo $outline['avatarurl'] ?>" alt=""/><a
                            href="<? echo $outline['url'] ?>"><? echo $outline['ownername'] ?></a></li><?
                    }
                }
                ?>
            </ul>

            <h3>Stuff I Read</h3>
            <ul>
                <?
                $feeds = get_feeds($uid);
                foreach ($feeds as $feed) {
                    ?>
                    <li><a href="<? echo $feed['url'] ?>"><? echo $feed['title'] ?></a></li><?
                }
                ?>
            </ul>
        </div>
        <div style="clear:both;"></div>
    </div>

</div>

<? //--- Include the footer bar html fragments -----------?>
<? include "$confroot/$templates/$template_html_footerbar" ?>
</body>

<? include "$confroot/$templates/$template_html_postbody" ?>
</html>

<? build_social_outline($uid) ?>
<? build_opml_reading_list($uid) ?>
