<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?
require_once "$confroot/$libraries/mail/PHPMailerAutoload.php";

//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    $urls = get_watched_urls();
    $count = count($urls);
    $checked = array();
    $aprefs = array();

    loggit(3, "Scanning: [$count] watched urls for changes.");

    $currentmod = gmdate('D, d M Y H:i:s \G\M\T',time()+60*60*60*60);

    $c = 0;
    foreach( $urls as $url ) {
        $changed = "";

        //Have we already checked this url?
        echo "Checking url: ".$url['url']."\n";
        if( isset($checked[$url['url']]) ) {
            continue;
        } else {
            $checked[$url['url']] = TRUE;
        }

        //Fetch the url content and last mod
        $u = get_final_url($url['url']);
        $lastmod = check_head_lastmod($u);
        if( $lastmod == $url['lastmodified'] && !empty($url['lastmodified']) && ($lastmod != FALSE) ) {
            loggit(3, "Last-modified date unchanged: [$lastmod == ".$url['lastmodified']."].");
            continue;
        }

        $users = get_watched_url_users_by_url($url['url']);
        $urlinfo = get_watched_url_by_url($url['url']);

        loggit(3, "Checking: [$u]");
        if( $lastmod != FALSE ) {
            loggit(3, "DEBUG: Page at url: [".$u."] changed at: $lastmod");
            $content = fetchUrl($u);
            update_watched_url_content_by_url($url['url'], $lastmod, $content);

            if( is_outline($content) ) {
                $changed = transform_opml_to_html(diff_opml($urlinfo['content'], $content));
                loggit(3, "DIFF:\n[$changed].");
            }

            //Send an email if the url changed and imap is enabled
            foreach ($users as $user) {
                $uid = $user;
                if( imap_is_enabled($uid) && smtp_is_enabled($uid) && !empty($url['lastmodified']) ) {
                    loggit(3, "Sending watched url update email to [$uid].");
                    send_url_change_email($uid, $u, get_user_name_from_uid($uid), $changed);
                }
            }
        } else {
            //For servers not reporting lastmod time
            //Check if the content changed
            $content = fetchUrl($u);
            if( $content != $urlinfo['content'] && !empty($content) || empty($urlinfo['content']) ) {
                loggit(3, "Content changed.");
                update_watched_url_content_by_url($url['url'], $currentmod, $content);

                if( is_outline($content) ) {
                    $changed = transform_opml_to_html(diff_opml($urlinfo['content'], $content));
                    loggit(3, "DIFF:\n[$changed].");
                }

                //Send an email if the url changed and imap is enabled
                foreach ($users as $user) {
                    $uid = $user;
                    if( imap_is_enabled($uid) && smtp_is_enabled($uid) && !empty($url['lastmodified']) ) {
                        loggit(3, "Sending watched url update email to [$uid].");
                        send_url_change_email($uid, $u, get_user_name_from_uid($uid), $changed);
                    }
                }
            } else {
                loggit(3, "Content un-changed.");
            }
        }

        $checked[$c] = $url;
        $c++;
    }

    //Remove the lock file
    cronHelper::unlock();
}


// Log and leave
loggit(3, "Done scanning watched urls.");
exit(0);