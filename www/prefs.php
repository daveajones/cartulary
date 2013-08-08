<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_page_init.php"?>
<?
  //Get this user's totp seed value
  $utps16 = get_totp_seed_from_uid($uid);

  $section = "Prefs";
  $tree_location = "Preferences";
?>

<?include "$confroot/$templates/$template_html_prehead"?>
<head>
<?include "$confroot/$templates/$template_html_meta"?>
<title><?echo $tree_location?></title>
<?include "$confroot/$templates/$template_html_styles"?>
<?include "$confroot/$templates/$template_html_scripts"?>
<script>
<?include "$confroot/$scripts/prefs.js"?>
</script>
</head>
<?include "$confroot/$templates/$template_html_posthead"?>

<body>
<?//--- Include the logo and menu bar html fragments --?>
<?include "$confroot/$templates/$template_html_logotop"?>
<?include "$confroot/$templates/$template_html_menubar"?>

<?//--- Stuff between the title and content --?>
<?include "$confroot/$templates/$template_html_precontent"?>

<div class="row" id="divPrefs">
<div id="divPrefsInner">
	<div id="divBookmarklets">
	<h3>Bookmarklets</h3>
	<ul>
		<li id="liCartBookmarklet"><a id="aCartBookmarklet" href="<?echo trim(sprintf($bookmarklet_url, $system_url))?>">Cartulize</a></li>
		<li id="liBlogBookmarklet"><a id="aBlogBookmarklet" href="<?echo trim(sprintf($bookmarklet_blog_url, $system_url))?>">Microblog</a></li>
		<li id="liSubscribeBookmarklet"><a id="aSubscribeBookmarklet" href="<?echo trim(sprintf($bookmarklet_subscribe, $system_url))?>">Subscribe</a> [beta]</li>
	</ul>
        </div>


        <div id="divSetpw" class="control-group">
	<form name="setpw" id="frmSetpw" method="POST" action="/cgi/auth/setpassword">
	<fieldset>
	<h3>Change Password</h3>
	<ul>
	 	<li>Password: <input name="password1" class="urlinput" type="text" value="" /></li>
		<li>Confirm: <input name="password2" class="urlinput" type="text" value="" /></li>
		<div class="buttonline">
		<img class="imgSpinner imgSpinnerPwd" src="/images/spinner.gif" />
		<button id="btnPwdSubmit" class="btn" type="submit">Change</button>
		</div>
	</ul>
	</fieldset>
	</form>
	</div>


	<form name="prefs" id="frmPrefs" method="POST" action="/cgi/auth/setprefs">
	<fieldset>
	<div id="divPref" class="control-group">

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefMe"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>Me</h3>
	<ul>
        <div class="control-group">
		<li>My name is: <input name="myname" class="urlinput" type="text" value="<?echo $username?>" /></li>
		<li>My email address is: <input name="myemail" class="urlinput" type="text" value="<?echo get_email_from_uid($uid)?>" /></li>
		<li>My homepage url is: <input name="homepagelink" class="urlinput" type="text" value="<?echo $prefs['homepagelink']?>" /></li>
	 	<li>The url of my avatar pic is: <input name="avatarurl" class="urlinput" type="text" value="<?echo $prefs['avatarurl']?>" />
		<?if( $device != "ipad" && $device != "iphone" && $device != "wphone") {?><br/>
                 or <button id="btnUploadAvatar" type="button" class="btn">Upload One</button><img class="imgSpinner imgSpinnerAvatar" src="/images/spinner.gif" /></li>
		<?}?>
	 	<li style="padding-top:10px;">My timezone is:
                    <select name="timezone" class="tzinput">
                    <?
                      $tzlist = timezone_identifiers_list();
                      foreach($tzlist as $tz) {
                      	?><option value="<?echo $tz?>" <?if($prefs['timezone'] == $tz) echo 'selected="selected"';?>><?echo $tz?></option><?
		      }
		    ?>
		    </select>
                </li>
		<li class="privacy"><input name="hideme" type="checkbox" <?if ($prefs['hideme'] == 1) echo "checked ";?>/> I don't want to be found in directory searches.</li>
        <li class="privacy"><input name="usetotp" type="checkbox" <?if ($prefs['usetotp'] == 1) echo "checked ";?>/> I want to use two-factor authentication to log in.</li>
            <?if ( $prefs['usetotp'] == 1 && !empty($utps16) ) {
                $b32 = new Base32;
                $utps32 = $b32->encode( $utps16, true);?>
                <br/>Google Authenticator:
                <br/><img src="/cgi/out/qrcode.totp?ts=".time() /> <?echo $utps32?>
                <br/>Current code: <large><?echo calculate_totp($utps16)?></large>
            <?}?>
	</ul>
	</div>
	</div>

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefAmazon"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>Amazon S3</h3>
	<ul>
        	<div class="control-group">
			<li>My Amazon S3 key is <input name="s3key" type="text" value="<?echo $prefs['s3key']?>" /></li>
        	</div>
        	<div class="control-group">
			<li>My Amazon S3 secret is <input name="s3secret" type="text" value="<?echo $prefs['s3secret']?>" /></li>
        	</div>
        	<div class="control-group">
			<li>I want to store my feeds in an Amazon S3 bucket named: <input id="s3bucket" name="s3bucket" type="text" value="<?echo $prefs['s3bucket']?>" /></li>
			<li>with a DNS cname of: <input id="s3cname" name="s3cname" type="text" value="<?echo $prefs['s3cname']?>" /></li>
			<ul id="s3feednames">
				<li>Article feed will be: <a id="aS3curlA" href="#"><span id="s3curlA"> </span></a></li>
				<li>Article opml will be: <a id="aS3curlC" href="#"><span id="s3curlC"> </span></a></li>
				<li>Microblog feed will be: <a id="aS3curlB" href="#"><span id="s3curlB"> </span></a></li>
				<li>Microblog opml will be: <a id="aS3curlD" href="#"><span id="s3curlD"> </span></a></li>
			</ul>
        	</div>
	</ul>
	</div>

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefRiver"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>Subscriptions</h3>
	<ul>
		<li class="privacy"><input name="opensubs" type="checkbox" <?if ($prefs['opensubs'] == 1) echo "checked ";?>/> Allow open subscriptions.</li>
	</ul>
	</div>

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefRiver"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>River</h3>
	<ul>
		<li>I want: <input id="rivercolumns" class="hourinput" name="rivercolumns" type="text" value="<?echo $prefs['rivercolumns']?>" /> columns on my river page. [0 for auto]</li>
		<li>I want: <input id="riverhours" class="hourinput" name="riverhours" type="text" value="<?echo $prefs['riverhours']?>" /> hours worth of items listed in my river.</li>
		<li>But, never show more than: <input class="hourinput" name="maxriversize" type="text" value="<?echo $prefs['maxriversize']?>" /> items at a time.</li>
		<li>And, never show more than: <input class="hourinput" name="maxriversizemobile" type="text" value="<?echo $prefs['maxriversizemobile']?>" /> items while on mobile.</li>
		<li><input name="riverheadlinecart" type="checkbox" <?if ($prefs['riverheadlinecart'] == 1) echo "checked ";?>/> I want headlines to cartulize by default in the river.</li>
		<li><input name="cartinriver" type="checkbox" <?if ($prefs['cartinriver'] == 1) echo "checked ";?>/> Don't use in-river cartulizing while on mobile.</li>
		<li><input name="mobilehidebigpics" type="checkbox" <?if ($prefs['mobilehidebigpics'] == 1) echo "checked ";?>/> Hide large image/video enclosures in the river when on mobile.</li>
		<li><input name="mobilehidepics" type="checkbox" <?if ($prefs['mobilehidepics'] == 1) echo "checked ";?>/> Hide body images/videos in the river when on mobile.</li>
		<li><input name="fulltextriver" type="checkbox" <?if ($prefs['fulltextriver'] == 1) echo "checked ";?>/> I want to see the full text of each item.</li>
		<li><input name="collapseriver" type="checkbox" <?if ($prefs['collapseriver'] == 1) echo "checked ";?>/> Collapse similar items into a threaded view.</li>
		<li class="privacy"><input id="chkPubriver" name="publicriver" type="checkbox" <?if ($prefs['publicriver'] == 1) echo "checked ";?>/> I want a public river.</li>
		<div id="divPubriver" class="hide">
			<li>My public river's file name should be: <input id="pubriverfile" name="pubriverfile" type="text" value="<?echo $prefs['pubriverfile']?>" /></li>
			<li>My custom public river template url is: <input id="pubrivertemplate" class="urlinput" name="pubrivertemplate" type="text" value="<?echo $prefs['pubrivertemplate']?>" /></li>
			<li>My public river title should be: <input id="pubrivertitle" class="urlinput" name="pubrivertitle" type="text" value="<?echo $prefs['pubrivertitle']?>" /></li>
		</div>
	</ul>
	</div>

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefMicroblog"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>Microblog</h3>
	<ul>
		<li>My microblog title should be: <input name="microblogtitle" class="urlinput" type="text" value="<?echo $prefs['microblogtitle']?>" /></li>
		<li>I want my microblog filename to be: <input id="mbfilename" name="mbfilename" type="text" value="<?echo $prefs['mbfilename']?>" /></li>
		<li>Microblog archives should use the stylesheet located at: <input name="mbarchivecss" class="urlinput" type="text" value="<?echo $prefs['mbarchivecss']?>" /> (url)</li>
	        <div class="control-group">
			<li>I'd rather use an external linkblog located at: <input name="linkblog" class="urlinput" type="text" value="<?echo $prefs['linkblog']?>" /> (url)</li>
		</div>
		<li><input name="mblinkhome" type="checkbox" <?if ($prefs['mblinkhome'] == 1) echo "checked ";?>/> I want my microblog RSS link element to point to my homepage.</li>
		<li class="privacy"><input name="publicrss" type="checkbox" <?if ($prefs['publicrss'] == 1) echo "checked ";?>/> I want my microblog RSS feed to be private.</li>
		<li><input name="mbreturnhome" type="checkbox" <?if ($prefs['mbreturnhome'] == 1) echo "checked ";?>/> I want to go back to the home page after posting.</li>
	</ul>
	</div>

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefArticles"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>Articles</h3>
	<ul>
		<li>My article archive title should be: <input name="cartularytitle" class="urlinput" type="text" value="<?echo $prefs['cartularytitle']?>" /></li>
		<li>I want my article archive feed filename to be: <input id="cartfilename" name="cartfilename" type="text" value="<?echo $prefs['cartfilename']?>" /></li>
		<li><input name="tweetcart" type="checkbox" <?if ($prefs['tweetcart'] == 1) echo "checked ";?>/> I want the articles I save to be tweeted.</li>
		<li><input name="shortcart" type="checkbox" <?if ($prefs['shortcart'] == 1) echo "checked ";?>/> I want articles that I cartulize to have their url's shortened also.</li>
		<li class="privacy"><input name="publicdefault" type="checkbox" <?if ($prefs['publicdefault'] == 1) echo "checked ";?>/> I want my article archive RSS feed to be private.</li>
		<li class="privacy"><input name="publicopml" type="checkbox" <?if ($prefs['publicopml'] == 1) echo "checked ";?>/> I want my article archive OPML file to be private.</li>
		<li><input name="staticarticles" type="checkbox" <?if ($prefs['staticarticles'] == 1) echo "checked ";?>/> I want a static version of each article stored in S3.</li>
	</ul>
	</div>

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefLinks"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>Links</h3>
	<ul>
		<li><input name="sourceurlrt" type="checkbox" <?if ($prefs['sourceurlrt'] == 1) echo "checked ";?>/> I want RT links to use the original source url.</li>
		<li><input name="sourceurlrss" type="checkbox" <?if ($prefs['sourceurlrss'] == 1) echo "checked ";?>/> I want RSS feed links to point to the original source url.</li>
        </ul>
	</div>

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefStyle"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>Style</h3>
	<ul>
	        <div class="control-group">
			<li>I want to override the default style with the stylesheet located at this url: <input name="stylesheet" type="text" value="<?echo $prefs['stylesheet']?>" /></li>
		</div>
	        <div class="control-group">
			<li>I want a maximum of <input name="maxlist" type="text" value="<?echo $prefs['maxlist']?>" /> items to be listed in feeds and on pages by default.</li>
	        </div>
	</ul>
	</div>

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefTwitter"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>Twitter <small>(Create app <a href="https://dev.twitter.com/apps/new">here</a>. Instructions <a href="https://groups.google.com/d/msg/sopml/fbMd0cWPzRk/84mMoDM4BEsJ">here</a>.)</small></h3>
	<ul>
        	<div class="control-group">
			<li>My Twitter oAuth consumer key is <input name="twitterkey" type="text" value="<?echo $prefs['twitterkey']?>" /></li>
        	</div>
        	<div class="control-group">
			<li>My Twitter oAuth consumer secret is <input name="twittersecret" type="text" value="<?echo $prefs['twittersecret']?>" /></li>
        	</div>
        	<div class="control-group">
			<li>My Twitter oAuth access token is <input name="twittertoken" type="text" value="<?echo $prefs['twittertoken']?>" /></li>
        	</div>
        	<div class="control-group">
			<li>My Twitter oAuth access secret is <input name="twittertokensecret" type="text" value="<?echo $prefs['twittertokensecret']?>" /></li>
        	</div>
	</ul>
	</div>

	<div class="divPrefPane">
	<div class="divPrefSubmit pull-right divPrefShortener"><img class="imgSpinner imgSpinnerSub" src="/images/spinner.gif" /><span class="message"></span><button id="btnPrefSubmit" class="btn btn-success" type="submit">Save</button></div>
	<h3>Url Shortener</h3>
	<ul>
	        <div class="control-group">
			<li>My shortening service is called with this url: <input name="urlshortener" type="text" value="<?echo $prefs['urlshortener']?>" /> (use %@ for the url variable)</li>
			<li>I'd rather shorten my urls in an S3 bucket named: <input id="s3shortbucket" name="s3shortbucket" type="text" value="<?echo $prefs['s3shortbucket']?>" /></li>
			<li>The last short url code used on my short domain was: <input id="lastshortcode" name="lastshortcode" type="text" value="<?echo $prefs['lastshortcode']?>" /></li>
		</div>
	</ul>

	</div>
	</div>
	</fieldset>
	</form>

        <div style="display:none;">
        <form id="frmUploadAvatar" name="uploadAvatar" action="/cgi/auth/setavatar" method="POST"<?if($device=="android") {?> enctype="multipart/form-data"<?}?>>
		<input id="fileAvatar" name="avatarFile" type="file" />
		<input id="btnAvatarUpload" name="avatarSubmit" type="submit" value="Post File" />
        </form>
        </div>

</div>
</div>

<?//--- Include the footer bar html fragments -----------?>
<?include "$confroot/$templates/$template_html_footerbar"?>
</body>

<?include "$confroot/$templates/$template_html_postbody"?>
</html>
