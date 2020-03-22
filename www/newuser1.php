<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_init.php" ?>
<?
$section = "Wizard";
$tree_location = "New User - Step 1";
?>

<? include "$confroot/$templates/$template_html_prehead" ?>
<head>
    <? include "$confroot/$templates/$template_html_meta" ?>
    <title><? echo $tree_location ?></title>
    <? include "$confroot/$templates/$template_html_styles" ?>
    <? include "$confroot/$templates/$template_html_scripts" ?>
    <script>
        function feedcurl() {
            var s3curlprefix = "<?echo $s3curlprefix?>";
            var bucketname = "[bucket name]/";
            var mbfilename = "<?echo get_microblog_feed_filename($uid)?>";
            var cartfilename = "<?echo get_cartulary_feed_filename($uid)?>";

            $('#s3curlA').empty();
            $('#s3curlB').empty();
            $('#s3curlC').empty();
            $('#s3curlD').empty();

            if ($('#s3bucket').val() != "") {
                $('#s3feednames').show();
                bucketname = $('#s3bucket').val() + '/';
            } else {
                $('#s3feednames').hide();
            }

            if ($('#s3cname').val() != "") {
                s3curlprefix = 'http://' + $('#s3cname').val();
                bucketname = '';
            } else {
                s3curlprefix = "<?echo $s3curlprefix?>";
                bucketname = $('#s3bucket').val() + '/';
            }

            $('#s3curlA').append(s3curlprefix + "/" + bucketname + cartfilename);
            $('#aS3curlA').attr('href', s3curlprefix + "/" + bucketname + cartfilename);
            $('#s3curlB').append(s3curlprefix + "/" + bucketname + mbfilename);
            $('#aS3curlB').attr('href', s3curlprefix + "/" + bucketname + mbfilename);
            $('#s3curlC').append(s3curlprefix + "/" + bucketname + "<?echo $default_opml_file_name?>");
            $('#aS3curlC').attr('href', s3curlprefix + "/" + bucketname + "<?echo $default_opml_file_name?>");
            $('#s3curlD').append(s3curlprefix + "/" + bucketname + "<?echo $default_blog_opml_file_name?>");
            $('#aS3curlD').attr('href', s3curlprefix + "/" + bucketname + "<?echo $default_blog_opml_file_name?>");

            return (true);
        }
        ;

        $(document).ready(function () {
            $('#frmPrefs').ajaxForm({
                dataType: 'json',
                success: function (data) {
                    $('#divPref .control-group').removeClass('error');
                    if (data.status == "false") {
                        showMessage(data.description, data.status, 5);
                        $('#divPref [name="' + data.prefname + '"]').parent().parent().addClass('error');
                    } else {
                        showMessage(data.description, data.status, 5);
                        window.location.href = '/newuser2.php';
                    }
                }
            });

            //Initialize the display value
            feedcurl('#s3curl');

            $('#s3bucket').bind('keyup', function () {
                feedcurl('#s3curl');
            });
            $('#s3cname').bind('keyup', function () {
                feedcurl('#s3curl');
            });
        });
    </script>
</head>
<? include "$confroot/$templates/$template_html_posthead" ?>
<body>
<? //--- Include the logo and menu bar html fragments --?>
<? include "$confroot/$templates/$template_html_logotop" ?>
<? include "$confroot/$templates/$template_html_menubar_postauth" ?>

<? //--- Stuff between the title and content --?>
<? include "$confroot/$templates/$template_html_precontent" ?>

<div class="row" id="divNewUser1">

    <h3>New User - Step 1</h3>

    <p><em>If you have an Amazon S3 account, put the credentials here. If not, you can just leave all this blank and click "Next". You'll be assigned a
            subfolder in the server operator's S3 bucket.</em></p>

    <br/><br/>

    <form name="prefs" id="frmPrefs" method="POST" action="/cgi/auth/setup1">
        <fieldset>
            <div id="divPref" class="control-group">

                <ul>
                    <div class="control-group">
                        <li>My Amazon S3 key is <input name="s3key" type="text" value="<? echo $prefs['s3key'] ?>"/>
                        </li>
                    </div>
                    <div class="control-group">
                        <li>My Amazon S3 secret is <input name="s3secret" type="text"
                                                          value="<? echo $prefs['s3secret'] ?>"/></li>
                    </div>
                    <div class="control-group">
                        <li>I want to store my feeds in an Amazon S3 bucket named: <input id="s3bucket" name="s3bucket"
                                                                                          type="text"
                                                                                          value="<? echo $prefs['s3bucket'] ?>"/>
                        </li>
                        <li>with a DNS cname of: <input id="s3cname" name="s3cname" type="text"
                                                        value="<? echo $prefs['s3cname'] ?>"/></li>
                        <ul id="s3feednames">
                            <li>Article feed will be: <a id="aS3curlA" href="#"><span id="s3curlA"> </span></a></li>
                            <li>Article opml will be: <a id="aS3curlC" href="#"><span id="s3curlC"> </span></a></li>
                            <li>Microblog feed will be: <a id="aS3curlB" href="#"><span id="s3curlB"> </span></a></li>
                            <li>Microblog opml will be: <a id="aS3curlD" href="#"><span id="s3curlD"> </span></a></li>
                        </ul>
                    </div>
                </ul>

                <div id="divPrefSubmit" class="pull-right"><span class="message"></span>
                    <button id="btnPrefSubmit" class="btn btn-primary" type="submit">Next</button>
                </div>

            </div>
        </fieldset>
    </form>

</div>

<? //--- Include the footer bar html fragments -----------?>
<? include "$confroot/$templates/$template_html_footerbar" ?>
</body>

<? include "$confroot/$templates/$template_html_postbody" ?>
</html>
