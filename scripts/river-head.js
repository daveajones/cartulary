<!--
//-----------------------------------------------------------------------------------
//Script support for the head of the river page
//-----------------------------------------------------------------------------------
        // Widget stuff
        function reloadMicroblogWidget() {
                $('#divMicroblogWidget').empty();

                var ts = Math.round((new Date()).getTime() / 1000);
                var parel = document.getElementById('divMicroblogWidget');
                var script = document.createElement('script');
                script.type = 'text/javascript';
                script.id = 'scrMicroblogWidget';
                script.src = '<?echo $s3widget?>?ts=' + ts;
                parel.appendChild(script);
        }

        function resetQuickBlogBox () {
                $('#chkTwitter').attr('checked', false);
                $('#imgTwitter').removeClass('icon-twitter').addClass('icon-notwitter');
                $('#txtSaySomething').val('');
                $('#txtSaySomething').switchClass('bigSaySomething', 'smallSaySomething', 300);
        }

        // River stuff
        <?if($platform == "mobile") {?>
	var jsonRiverData = <?echo get_river_as_json($uid, TRUE)?>;
	<?} else {?>
	var jsonRiverData = <?echo get_river_as_json($uid)?>;
	<?}?>
        var Hidebigpics = false;
        var Hidepics = false;
        <?if ( $prefs['mobilehidebigpics'] == 1 && $platform == "mobile" ) {?>
        Hidebigpics = true;
        <?}?>
        <?if ( $prefs['mobilehidepics'] == 1 && $platform == "mobile"  ) {?>
        Hidepics = true;
        <?}?>
        var River = River || {};
        River.settings = {
            url : '<?echo $jsonurl?>',
            callback : 'onGetRiverStream',
            social : false,
	    initialized : false
        };
-->
