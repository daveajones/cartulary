<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
require_once "$confroot/$libraries/mail/PHPMailerAutoload.php";

//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    $urls = get_watched_urls();
    $count = count($urls);

    loggit(3, "Scanning: [$count] watched urls for changes.");

    $currentmod = gmdate('D, d M Y H:i:s \G\M\T',time()+60*60*60*60);

    foreach( $urls as $url ) {
        $uid = $url['uid'];
        $prefs = get_user_prefs($url['uid']);
        $u = get_final_url($url['url']);

        $lastmod = check_head_lastmod($u);
        loggit(3, "Checking: [$u]");
        if( ($url['lastmodified'] != $lastmod || empty($url['lastmodified'])) && ($lastmod != FALSE) ) {
            $content = fetchUrl($u);
            loggit(3, "DEBUG: Page at url: [".$u."] changed at: $lastmod");
            add_watched_url($url['rid'], $url['url'], $lastmod, $content);

            //Send an email if the url changed and imap is enabled
            if( imap_is_enabled($uid) && smtp_is_enabled($uid) && !empty($url['lastmodified']) ) {
                loggit(3, "Sending email.");
                send_url_change_email($url['uid'], $u, get_user_name_from_uid($url['uid']));
            }
        } else {
            loggit(3, "Content un-changed.");
        }

        //For servers not reporting lastmod time
        if( $lastmod == FALSE ) {
            $content = fetchUrl($u);
            //Check if the content changed
            $urlinfo = get_watched_url_by_url($url['url']);
            if( $content != $urlinfo['content'] || empty($urlinfo['content']) ) {
                loggit(3, "Content changed.");
                add_watched_url($url['rid'], $url['url'], $currentmod, $content);

                //Send an email if the url changed and imap is enabled
                if( imap_is_enabled($uid) && smtp_is_enabled($uid) && !empty($urlinfo['lastmodified']) ) {
                    loggit(3, "Sending email.");
                    send_url_change_email($url['uid'], $u, get_user_name_from_uid($url['uid']));
                }
            } else {
                loggit(3, "Content un-changed.");
            }
        }
    }

    //Remove the lock file
    cronHelper::unlock();
}

// Log and leave
loggit(3, "Done scanning watched urls.");
exit(0);