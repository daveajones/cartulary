<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_page_init.php" ?>
<?

$section = "Radio";
$tree_location = "Radio";
?>

<? include "$confroot/$templates/$template_html_prehead" ?>
<head>
    <? include "$confroot/$templates/$template_html_meta" ?>
    <title><? echo $tree_location ?></title>
    <? include "$confroot/$templates/$template_html_styles" ?>
    <? include "$confroot/$templates/$template_html_scripts" ?>
    <script>
        <?include "$confroot/$scripts/subscribe.js"?>
    </script>
    <script src="/script/soundmanager2-nodebug-jsmin.js"></script>
    <script>
        var playlist;
        var plcurrent;
        var plcount;
        var paused = true;
        var currentSound = false;
        var soundId = 'mySoundId';
        var initPlay = true;
        soundManager.setup({
            url: '/swf/',
            flashVersion: 9, // optional: shiny features (default = 8)
            // optional: ignore Flash where possible, use 100% HTML5 mode
            // preferFlash: false,
            onready: function() {
                // Ready to use; soundManager.createSound() etc. can now be called.
                //Ajax call
                $.ajax({
                    method: "GET",
                    url: "/cgi/out/list.audio.json"
                })
                    .done(function (responseData) {
                        playlist = responseData.data.items;
                        plcount = playlist.length;
                        plcurrent = 0;
                        for(var i=0 ; i <= playlist.length ; i++ ) {
                            playUrlAtIndex(plcurrent, false);
                            break;
                        }
                    });

                function playUrlAtIndex(plindex, autoplay) {
                    var autoplay = (typeof autoplay === "undefined") ? true : autoplay;
                    console.log("Playing index [" +plindex+ "]")
                    var i = plindex;
                    plcurrent = i;

                    currentSound = soundManager.createSound({
                        id: soundId,
                        url: playlist[i].url,
                        autoLoad: true,
                        autoPlay: autoplay,
                        onload: function(bSuccess) {
                            if(bSuccess) {
                                $('div#nowPlaying').html(playlist[i].title + '<hr style="margin:4px;border-color:#888;">' + playlist[i].url);
                            } else {
                                $('div#nowPlaying').html('Error! Skipping... ' + playlist[i].title);

                                playUrlAtNextIndex(autoplay);
                            }
                        },
                        volume: 50
                    });
                }

                $('button#skipUrl').click(function() {
                    playUrlAtNextIndex();
                    return false;
                });

                $('button#pausePlaying').click(function() {
                    pausePlaying();
                    return false;
                });

                function playUrlAtNextIndex(autoplay) {
                    var autoplay = (typeof autoplay === "undefined") ? true : autoplay;
                    plcurrent++;

                    if(plcurrent > plcount) {
                        plcurrent = 0;
                    }

                    currentSound.destruct();
                    playUrlAtIndex(plcurrent, autoplay);
                }

                function pausePlaying() {
                    if(initPlay) {
                        currentSound.play();
                        paused = false;
                        $('button#pausePlaying > i').removeClass('fa-play').addClass('fa-pause');
                        initPlay = false;
                        return false;
                    }
                    if(paused) {
                        currentSound.resume(soundId);
                        paused = false;
                        $('button#pausePlaying > i').removeClass('fa-play').addClass('fa-pause');
                    } else {
                        currentSound.pause(soundId);
                        paused = true;
                        $('button#pausePlaying > i').removeClass('fa-pause').addClass('fa-play');
                    }
                }
            }
        });
    </script>
</head>
<? include "$confroot/$templates/$template_html_posthead" ?>
<body>
<? //--- Include the logo and menu bar html fragments --?>
<? include "$confroot/$templates/$template_html_logotop" ?>
<? include "$confroot/$templates/$template_html_menubar" ?>

<? //--- Stuff between the title and content --?>
<? include "$confroot/$templates/$template_html_precontent" ?>

<div class="row" id="divMedia"></div>

<label for="nowPlaying">Now Playing:</label>
<div id="nowPlaying" style="background: white; min-height: 40px; padding:10px;"></div>

<center style="margin:20px;">
    <button id="pausePlaying" style="padding:8px;"><i class="fa fa-play fa-2x"></i></button>
    <button id="skipUrl" style="padding:8px;"><i class="fa fa-fast-forward fa-2x"></i></button>
</center>

<? //--- Include the footer bar html fragments -----------?>
<? include "$confroot/$templates/$template_html_footerbar" ?>
<script>
    <?//include "$confroot/$scripts/media.js"?>
</script>
</body>

<? include "$confroot/$templates/$template_html_postbody" ?>
</html>
