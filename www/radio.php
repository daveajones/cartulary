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
    <style>
        div.progContainer {
            position: relative:
        }
        hr.progBar {
            width: 100%;
            display: block;
            position: relative;
            border-color:#333;
            border-width:1px;
            z-index: 10;
        }
        img.progDot {
            display: block;
            z-index: 20;
            position: relative;
            top:-26px;
            left:0px;
        }
        div#upNext > ul > li {
            list-style-type: none;
            margin-top:8px;
        }
    </style>
    <script>
        <?include "$confroot/$scripts/subscribe.js"?>
    </script>
    <script src="/script/soundmanager2-nodebug-jsmin.js"></script>
    <script>
        var playlist;
        var plcurrent;
        var plcount;
        var currentSound = false;
        var soundId = 'mySoundId0';
        var initPlay = true;
        var gAutoplay = false;
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

                        console.log(playlist);

                        //Figure out what to play first
                        for( var i=0 ; i <= playlist.length ; i++ ) {
                            playUrlAtIndex(plcurrent, false);
                            break;
                        }

                        //Load the up-next list
                        for( var i=0 ; i < playlist.length ; i++ ) {
                            $('div#upNext > ul').append("<li data-index='"+i+"'><button class='playUrl' data-index='"+i+"'><i class='fa fa-play'></i></button> <a style='margin-left:8px;' target='_blank' href='"+playlist[i].url+"'>" + playlist[i].url + "</a></li>");
                        }
                    });

                function playUrlAtIndex(plindex, autoplay) {
                    var autoplay = (typeof autoplay === "undefined") ? true : autoplay;
                    console.log("Playing index [" +plindex+ "]")
                    var i = plindex;
                    plcurrent = i;
                    $('button#pausePlaying > i').removeClass('fa-pause').addClass('fa-play');

                    currentSound = soundManager.createSound({
                        id: soundId,
                        url: playlist[i].url,
                        autoLoad: true,
                        autoPlay: autoplay,
                        onload: function(bSuccess) {
                            if(bSuccess) {
                                $('div#nowPlaying').html(playlist[i].title + '<div class="progContainer"><hr class="progBar"><img class="progDot" src="/images/progDot.png" /></div>' + playlist[i].url);
                                $('div#upNext > ul li').css('font-weight', '').css('color', '');
                                $('div#upNext > ul li[data-index="'+i+'"]').css('font-weight', 'bold').css('color', '#0FF');
                            } else {
                                $('div#nowPlaying').html('Error! Skipping... ' + playlist[i].title);

                                playUrlAtNextIndex(autoplay);
                                currentSound.pause();
                            }
                        },
                        onpause: function() {
                            $('button#pausePlaying > i').removeClass('fa-pause').addClass('fa-play');
                        },
                        onstop: function () {
                            $('button#pausePlaying > i').removeClass('fa-pause').addClass('fa-play');
                        },
                        onplay: function() {
                            $('button#pausePlaying > i').removeClass('fa-play').addClass('fa-pause');
                        },
                        onresume: function() {
                            $('button#pausePlaying > i').removeClass('fa-play').addClass('fa-pause');
                        },
                        onsuspend: function() {
                            $('button#pausePlaying > i').removeClass('fa-pause').addClass('fa-play');
                        },
                        whileloading: function() {
                            var e, total = 0;
                            for (e=0, j=this.buffered.length; e<j; e++) {
                                // draw current range in progress bar UI?
                                drawProgressUI(this.buffered[e].start, this.buffered[e].end);
                                // sum of all ranges vs. whole file duration
                                total += (this.buffered[e].end - this.buffered[e].start);
                            }
                        },
                        whileplaying: function() {
                            $(".progContainer .progDot").css('left', ((this.position/this.duration) * 100) + '%');
                            if(currentSound.playState === 1) {
                                $('button#pausePlaying > i').removeClass('fa-play').addClass('fa-pause');
                            }
                        },
                        onfinish: function() {
                            playUrlAtNextIndex(autoplay);
                        },
                        volume: 70
                    });
                }

                $('button#skipBack').click(function() {
                    console.log(currentSound.position);
                    currentSound.setPosition(currentSound.position - 15000);
                    return false;
                });

                $('button#skipUrl').click(function() {
                    console.log(currentSound);
                    playUrlAtNextIndex(gAutoplay);
                    return false;
                });

                $('button#pausePlaying').click(function() {
                    pausePlaying();
                    return false;
                });

                $('button#skipForward').click(function() {
                    console.log(currentSound);
                    currentSound.setPosition(currentSound.position + 30000);
                    return false;
                });

                $(document).on('click', 'button.playUrl', function() {
                    soundManager.destroySound(soundId);
                    playUrlAtIndex($(this).data('index'), false);
                    pausePlaying();
                    return false;
                });

                function drawProgressUI(start, end) {
                    if(start === 0) start++;
                    start = start|0;
                    end = end|0;
                    barPercentage = (start/end) * 100;
                    console.log("DEBUG: ["+start+"] : " + barPercentage + " : ["+end+"] " + currentSound.durationEstimate);

                    //$('.progContainer .progBar').css('width', barPercentage + "%");
                }

                function playUrlAtNextIndex(autoplay) {
                    console.log("playUrlAtNextIndex("+autoplay+")");
                    var autoplay = (typeof autoplay === "undefined") ? true : autoplay;
                    plcurrent++;

                    if(plcurrent > plcount) {
                        plcurrent = 0;
                    }

                    //currentSound.unload();
                    //soundManager.unload(soundId);
                    soundManager.destroySound(soundId);

                    currentSound = false;

                    playUrlAtIndex(plcurrent, autoplay);
                }

                function pausePlaying() {
                    console.log("DEBUG: (currentSound.paused)" + currentSound.paused);
                    if(initPlay) {
                        currentSound.play();
                        gAutoplay = true;
                        $('button#pausePlaying > i').removeClass('fa-play').addClass('fa-pause');
                        initPlay = false;
                        return false;
                    }
                    if(currentSound.paused || currentSound.playState === 0) {
                        if(!initPlay) gAutoplay = true;
                        currentSound.play();
                    } else {
                        if(!initPlay) gAutoplay = false;
                        currentSound.pause();
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
    <button id="skipBack" style="padding: 8px;"><i class="fa fa-backward fa-2x"></i></button>
    <button id="pausePlaying" style="padding:8px;"><i class="fa fa-play fa-2x"></i></button>
    <button id="skipUrl" style="padding:8px;"><i class="fa fa-fast-forward fa-2x"></i></button>
    <button id="skipForward" style="padding: 8px;"><i class="fa fa-forward fa-2x"></i></button>
</center>

<label for="upNext">Up Next:</label>
<div id="upNext" style="background: white; min-height: 40px; padding:10px;"><ul class="list-unstyled"></ul></div>

<? //--- Include the footer bar html fragments -----------?>
<? include "$confroot/$templates/$template_html_footerbar" ?>
<script>
    <?//include "$confroot/$scripts/media.js"?>
</script>
</body>

<? include "$confroot/$templates/$template_html_postbody" ?>
</html>
