<?
//########################################################################################
// API for managing blog posts


//_______________________________________________________________________________________
//Check if an post is already in the post repository
function post_exists($url = NULL)
{
  //Check parameters
  if($url == NULL) {
    loggit(2,"The post url is blank or corrupt: [$url]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $sql=$dbh->prepare("SELECT id FROM $table_post WHERE url=?") or print(mysql_error());
  $sql->bind_param("s", $url) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"The post at url: [$url] does not exist in the repository.");
    return(FALSE);
  }
  $sql->bind_result($postid) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  loggit(1,"The post at url: [$url] is already in the repository.");
  return($postid);
}


//_______________________________________________________________________________________
//Retrieve an post from the repository
function get_post($id = NULL)
{
  //Check parameters
  if($id == NULL) {
    loggit(2,"The post id given is corrupt or blank: [$id]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $sql=$dbh->prepare("SELECT url,title,content FROM $table_post WHERE id=?") or print(mysql_error());
  $sql->bind_param("s", $id) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(2,"Failed to retrieve post content for post id: [$id]");
    return(FALSE);
  }
  $post = array();
  $sql->bind_result($post['url'],$post['title'],$post['content']) or print(mysql_error());
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  loggit(1,"Returning post content for post id: [$id]");
  return($post);
}



//_______________________________________________________________________________________
//Add an post to the post repository
function add_post($uid = NULL, $content = NULL, $url = NULL, $shorturl = FALSE, $enclosure = FALSE, $source = FALSE, $twitter = FALSE, $title = "", $timestamp = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }
  if($content == NULL) {
    loggit(2,"The post content is blank or corrupt: [$content]");
    return(FALSE);
  }
  if($url == NULL) {
    loggit(1,"The post url is blank or corrupt: [$url]. Setting it to blank.");
    $url = "";
  }
  if($shorturl == FALSE) {
    loggit(1,"The short url is blank or corrupt: [$shorturl]. Setting it to blank.");
    $shorturl = "";
  }
  if($enclosure == FALSE) {
    loggit(1,"The enclosure is blank or corrupt: [$enclosure]. Setting it to blank.");
    $enclosure = "";
  }
  if($source == FALSE) {
    loggit(1,"The source is blank or corrupt: [$source]. Setting it to blank.");
    $source = array();
    $source['url'] = "";
    $source['title'] = "";
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Timestamp
  $id = random_gen(128);
  if( empty($timestamp) ) {
    $createdon = time();
  } else {
    $createdon = $timestamp;
  }

  //Did the post go to twitter?
  if($twitter == TRUE) {
    $twitter = 1;
  } else {
    $twitter = 0;
  }

  //Now that we have a good id, put the post into the database
  $stmt = "INSERT INTO $table_post (id,url,content,createdon,shorturl,enclosure,sourceurl,sourcetitle,twitter,title) VALUES (?,?,?,?,?,?,?,?,?,?)";
  $sql=$dbh->prepare($stmt) or loggit(2, "SQL Error: [".$dbh->error."]");
  $sql->bind_param("ssssssssss", $id,$url,$content,$createdon,$shorturl,$enclosure,$source['url'],$source['title'],$twitter,$title) or loggit(2, "SQL Error: [".$dbh->error."]");
  loggit(1,"Executing SQL: [".$stmt."]");
  $sql->execute() or loggit(2, "SQL Error: [".$dbh->error."]");
  $sql->close() or loggit(2, "SQL Error: [".$dbh->error."]");

  //Now link the post's id to this user's id
  if($uid != FALSE) {
    loggit(1, "About to link post: [$id] to user: [$uid].");
    link_post_to_user($id, $uid);
  }

  //Log and return
  loggit(1,"Added a new post: [$content] for user: [$uid].");
  return($id);
}


//_______________________________________________________________________________________
//Link an post to a specific user account, making it private
function link_post_to_user($aid = NULL, $uid = NULL, $pub = FALSE)
{
  //Check parameters
  if($aid == NULL) {
    loggit(2,"The post id is blank or corrupt: [$aid]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  $tstamp = time();

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Check if public
  if($pub == TRUE) {
    $publ = 1;
  } else {
    $publ = 0;
  }

  //Link the post to the user
  $stmt = "INSERT INTO $table_mbcatalog (userid,postid,public,linkedon) VALUES (?,?,?,?)";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ssds", $uid,$aid,$publ,$tstamp) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Linked post: [$aid] with user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Does the user have permission to view this post
function user_can_view_post($aid = NULL, $uid = NULL)
{
  //Check parameters
  if($aid == NULL) {
    loggit(2,"The post id is blank or corrupt: [$aid]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $sql=$dbh->prepare("SELECT userid FROM $table_mbcatalog WHERE postid=? AND (userid=? OR public=1)") or print(mysql_error());
  $sql->bind_param("ss", $aid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"This user: [$uid] is not allowed to see post: [$aid].");
    return(FALSE);
  }
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  loggit(1,"User: [$uid] is allowed to see post: [$aid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Does this user own this post?
function user_owns_post($uid = NULL, $pid = NULL)
{
  //Check parameters
  if($pid == NULL) {
    loggit(2,"The post id is blank or corrupt: [$pid]");
    return(FALSE);
  }
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $sql=$dbh->prepare("SELECT userid FROM $table_mbcatalog WHERE postid=? AND userid=?") or print(mysql_error());
  $sql->bind_param("ss", $pid, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if the session is valid
  if($sql->num_rows() != 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"User: [$uid] does not own post: [$pid].");
    return(FALSE);
  }
  $sql->fetch() or print(mysql_error());
  $sql->close() or print(mysql_error());

  loggit(1,"User: [$uid] is the owner of post: [$pid].");
  return(TRUE);
}



//_______________________________________________________________________________________
//Retrieve a post from the repository
function get_blog_posts($uid = NULL, $max = NULL, $pub = FALSE, $archive = FALSE)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Is this a request for a certain month's worth of posts?
  if($archive != FALSE) {
    $mfirst = mktime( 0, 0, 0 );
    //loggit(3, "Timestamp of start of day: [".$mfirst."]");
    $mlast = mktime( 23, 59, 00 );
    //loggit(3, "Timestamp of end of day: [".$mlast."]");
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $sqltxt = "SELECT $table_post.id,
                    $table_post.title,
                    $table_post.url,
                    $table_post.shorturl,
                    $table_post.createdon,
                    $table_post.content,
		    $table_post.enclosure,
		    $table_post.sourceurl,
		    $table_post.sourcetitle,
		    $table_post.twitter,
                    $table_mbcatalog.linkedon
	     FROM $table_post,$table_mbcatalog
	     WHERE $table_mbcatalog.userid=?
             AND ($table_mbcatalog.postid=$table_post.id)";

  if($pub == TRUE) {
    $sqltxt .= " AND ($table_mbcatalog.postid=$table_post.id OR $table_mbcatalog.public=1)";
  }

  if($archive != FALSE) {
    $sqltxt .= " AND $table_post.createdon > $mfirst AND $table_post.createdon < $mlast";
  }

  $sqltxt .= " ORDER BY $table_post.createdon DESC";

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  } else {
    $sqltxt .= " LIMIT $default_max_list";
  }

  loggit(1, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("s", $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any posts for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"No posts returned for: [$uid] with the given criteria.");
    return(array());
  }

  $sql->bind_result($aid,$atitle,$aurl,$ashorturl,$acreatedon,$acontent,$aenclosure,$asourceurl,$asourcetitle,$tweeted,$clinkedon) or print(mysql_error());

  $posts = array();
  $count = 0;
  while($sql->fetch()){
    $posts[$count] = array( 'id' => $aid,
                            'title' => $atitle,
                            'url' => $aurl,
                            'shorturl' => $ashorturl,
                            'createdon' => $acreatedon,
                            'content' => $acontent,
			    'enclosure' => $aenclosure,
			    'sourceurl' => $asourceurl,
			    'sourcetitle' => $asourcetitle,
			    'tweeted' => $tweeted,
                            'linkedon' => $clinkedon );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] posts for user: [$uid]");
  return($posts);
}


//_______________________________________________________________________________________
//Retrieve the first post from the repository for this user
function get_first_blog_post($uid = NULL)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Build the query
  $sqltxt = "SELECT $table_post.id,
                    $table_post.title,
                    $table_post.url,
                    $table_post.shorturl,
                    $table_post.createdon,
                    $table_post.content,
		    $table_post.enclosure,
		    $table_post.sourceurl,
		    $table_post.sourcetitle,
		    $table_post.twitter,
                    $table_mbcatalog.linkedon
	     FROM $table_post,$table_mbcatalog
	     WHERE $table_mbcatalog.userid=?
             AND ($table_mbcatalog.postid=$table_post.id)";

  $sqltxt .= " ORDER BY $table_post.createdon ASC LIMIT 1";

  loggit(1, "[$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("s", $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any posts for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"This user has no posts: [$uid]");
    return(FALSE);
  }

  $sql->bind_result($aid,$atitle,$aurl,$ashorturl,$acreatedon,$acontent,$aenclosure,$asourceurl,$asourcetitle,$tweeted,$clinkedon) or print(mysql_error());

  $posts = array();
  $count = 0;
  while($sql->fetch()){
    $posts[$count] = array( 'id' => $aid,
                            'title' => $atitle,
                            'url' => $aurl,
                            'shorturl' => $ashorturl,
                            'createdon' => $acreatedon,
                            'content' => $acontent,
			    'enclosure' => $aenclosure,
			    'sourceurl' => $asourceurl,
			    'sourcetitle' => $asourcetitle,
			    'tweeted' => $tweeted,
                            'linkedon' => $clinkedon );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(3,"Earliest post for user: [$uid] is timestamp: [".$posts[0]['linkedon']."]");
  return($posts[0]);
}



//_______________________________________________________________________________________
//Search for posts that match query
function search_posts($uid = NULL, $query = NULL, $max = NULL, $pub = FALSE)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id given is corrupt or blank: [$uid]");
    return(FALSE);
  }
  if($query == NULL) {
    loggit(2,"The query given is corrupt or blank: [$query]");
    return(FALSE);
  }


  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  if($pub == TRUE) {
    $sqltxt="SELECT $table_post.id,$table_post.title,$table_post.url,$table_post.content
	     FROM $table_post,$table_mbcatalog
	     WHERE ($table_mbcatalog.userid=?
	     AND ($table_mbcatalog.postid=$table_post.id OR $table_mbcatalog.public=1))
	     AND ( $table_post.content LIKE CONCAT('%', ?, '%')
	     OR $table_post.title LIKE CONCAT('%', ?, '%')
	     OR $table_post.url LIKE CONCAT('%', ?, '%')
	     OR $table_post.sourcetitle LIKE CONCAT('%', ?, '%')
	     OR $table_post.enclosure LIKE CONCAT('%', ?, '%') )
    ";
  } else {
    $sqltxt="SELECT $table_post.id,$table_post.title,$table_post.url,$table_post.content
             FROM $table_post,$table_mbcatalog
             WHERE ($table_mbcatalog.userid=?
	     AND $table_mbcatalog.postid=$table_post.id)
             AND ( $table_post.content LIKE CONCAT('%', ?, '%')
             OR $table_post.title LIKE CONCAT('%', ?, '%')
             OR $table_post.url LIKE CONCAT('%', ?, '%')
	     OR $table_post.sourcetitle LIKE CONCAT('%', ?, '%')
             OR $table_post.enclosure LIKE CONCAT('%', ?, '%') )
    ";
  }

  if($max != NULL) {
    $sqltxt .= " LIMIT $max";
  }

  loggit(3, "DEBUG:  [$sqltxt]");
  $sql=$dbh->prepare($sqltxt) or print(mysql_error());
  $sql->bind_param("ssssss", $uid, $query, $query, $query, $query, $query) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());

  //See if there were any posts for this user
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"No posts returned for: [$uid] with the given criteria.");
    return(FALSE);
  }

  $sql->bind_result($id,$title,$url,$content) or print(mysql_error());

  $posts = array();
  $count = 0;
  while($sql->fetch()){
    if( empty($title) ) {
	$title = $content;
    }
    $posts[$count] = array( 'id' => $id, 'title' => $title, 'url' => $url );
    $count++;
  }

  $sql->close() or print(mysql_error());

  loggit(1,"Returning: [$count] posts for user: [$uid]");
  return($posts);
}


//_______________________________________________________________________________________
//Build an rss feed for the given user
function build_blog_rss_feed($uid = NULL, $max = NULL, $archive = FALSE, $posts = NULL, $nos3 = FALSE)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/s3/S3.php";

  $username = get_user_name_from_uid($uid);
  $prefs = get_user_prefs($uid);

  //If this user doesn't want his microblog feed public, then exit
  if($prefs['publicrss'] == 1) {
    loggit(3, "User: [$uid] want's their microblog feed to be private.");
    return(FALSE);
  }


  //Get a proper max value
  if($max == NULL) {
    if( !empty($prefs['maxlist']) ) {
      $max = $prefs['maxlist'];
    } else {
      $max = $default_max_rss_items;
    }
  }

  //Get the right timezone
  date_default_timezone_set('America/Chicago');

  //If the array of posts is being passed in as an argument we don't make this call
  if( $posts == NULL || !is_array($posts) ) {
    $posts = get_blog_posts($uid, $max, NULL, $archive);
  }

  //Get a correct title
  $title = get_microblog_title($uid);

  //Get the correct link
  if( $prefs['mblinkhome'] == 0 || empty($prefs['homepagelink']) ) {
    $feedlink = get_s3_url($uid, NULL, get_microblog_html_filename($uid));
  } else {
    $feedlink = $prefs['homepagelink'];
  }

  //Get the date of the user's earliest blog post
  $firstpost = get_first_blog_post($uid);
  if( empty($firstpost) ) {
    $firstpostDate = date('Y-m-d');
  } else {
    $firstpostDate = date('Y-m-d', $firstpost['createdon']);
  }

  //Get the date of the user's latest blog post
  if( empty($posts) ) {
    $lastpostDate = date('Y-m-d');
  } else {
    $lastpostDate = date('Y-m-d', $posts[0]['createdon']);
  }



  //The feed string
  $rss = '<?xml version="1.0"?>'."\n  <rss version=\"2.0\" xmlns:microblog=\"http://microblog.reallysimple.org/\" xmlns:sopml=\"$sopmlnamespaceurlv1\">\n    <channel>";

  $rss .= "\n
      <title>".htmlspecialchars($title)."</title>
      <link>".htmlspecialchars($feedlink)."</link>
      <description>$title</description>
      <language>en-us</language>
      <pubDate>".date("D, d M Y H:i:s O")."</pubDate>
      <lastBuildDate>".date("D, d M Y H:i:s O")."</lastBuildDate>
      <generator>$system_name, v$version</generator>
      <managingEditor>".get_email_from_uid($uid)." ($username)</managingEditor>";
  if( s3_is_enabled($uid) || sys_s3_is_enabled() ) {
      $rss .= "
      <microblog:archive>
          <microblog:url>".htmlspecialchars(get_s3_url($uid, "arc"))."/</microblog:url>
          <microblog:filename>".get_microblog_feed_filename($uid)."</microblog:filename>
          <microblog:startDay>$firstpostDate</microblog:startDay>
          <microblog:endDay>$lastpostDate</microblog:endDay>
      </microblog:archive>";
  }
  $rss .= "
      <microblog:localTime>".date('n/j/Y; g:i:s A')."</microblog:localTime>
      <cloud domain=\"".$rss_cloud_domain."\" port=\"".$rss_cloud_port."\" path=\"".$rss_cloud_notify_path."\" registerProcedure=\"\" protocol=\"".$rss_cloud_protocol."\" />\n";

  if( !empty($prefs['avatarurl']) ) {
    $rss .= "      <microblog:avatar>".$prefs['avatarurl']."</microblog:avatar>\n";
  }

  foreach( $posts as $post ) {
	if ($post['url'] == "") {
	  $rsslink = "";
          $linkfull = "";
          $guid = "        <guid isPermaLink=\"false\">".$post['id']."</guid>";
        } else {
	  if(!empty($post['shorturl'])) {
		$rssurl = htmlspecialchars($post['shorturl']);
                $rsslink = "        <link>$rssurl</link>";
                $guid = "        <guid>$rssurl</guid>";
                $linkfull = "        <microblog:linkFull>".htmlspecialchars($post['url'])."</microblog:linkFull>";
          } else {
                $rssurl = htmlspecialchars($post['url']);
                $rsslink = "        <link>$rssurl</link>";
                $guid = "        <guid>$rssurl</guid>";
          }
	}
        if( !empty($post['enclosure']) ) {
          $enclosures = unserialize($post['enclosure']);
        }
        $tweeted = '';
        if( $post['tweeted'] == 1 ) {
                $tweeted = "        <sopml:tweeted>true</sopml:tweeted>\n";
        }

       $rss .= "
      <item>\n";
        if( !empty($post['title']) ) {
          $rss .= "        <title>".htmlspecialchars(trim($post['title']))."</title>\n";
        }
        $rss .= "        <description><![CDATA[".trim( str_replace("\n", '', $post['content'] ) )."]]></description>
        <pubDate>".date("D, d M Y H:i:s O", $post['createdon'])."</pubDate>\n";
        $rss .= $guid."\n";
        if(!empty($rsslink)) { $rss .= $rsslink."\n"; }
        if(!empty($linkfull)) { $rss .= $linkfull."\n"; }
        if( isset($enclosures) ) {
          if( is_array($enclosures) && count($enclosures) > 0 ) {
            foreach($enclosures as $enclosure) {
              $elen = 'length="0"';
              $etyp = 'type="application/octet-stream"';
	      if( !empty($enclosure['length']) ) {
                $elen = 'length="'.$enclosure['length'].'"';
              }
	      if( !empty($enclosure['type']) ) {
                $etyp = 'type="'.$enclosure['type'].'"';
              }
              if( !empty($enclosure['url']) ) {
                $rss .= '        <enclosure url="'.htmlspecialchars(trim($enclosure['url'])).'" '.$elen.' '.$etyp.' />'."\n";
              }
            }
          }
        }
	if( !empty($post['sourceurl']) || !empty($post['sourcetitle']) ) {
          $rss .= '        <source url="'.htmlspecialchars(trim($post['sourceurl'])).'">'.htmlspecialchars(trim($post['sourcetitle'])).'</source>'."\n";
	}
      $rss .= "        <author>".get_email_from_uid($uid)."</author>\n";
      $rss .= $tweeted;
      $rss .= "      </item>\n";
  }

  $rss .= "\n    </channel>\n  </rss>";

  //If this user has S3 storage enabled, then do it
  if( (s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3 ) {
    //First we get all the key info
    $s3info = get_s3_info($uid);

    //Get the microblog feed file name
    $filename = get_microblog_feed_filename($uid);
    $arcpath = '';

    //Was this a request for a monthly archive?
    if( $archive != FALSE ) {
      $arcpath = "/arc/".date('Y')."/".date('m')."/".date('d');
      //loggit(3, "Archive path: [".$arcpath."]");
    }

    //Put the file
    $s3res = putInS3($rss, $filename, $s3info['bucket'].$arcpath, $s3info['key'], $s3info['secret'], "application/rss+xml");
    if(!$s3res) {
      loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
      //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
    } else {
      $s3url = get_s3_url($uid, $arcpath, $filename);
      loggit(1, "Wrote feed to S3 at url: [$s3url].");

      //Ping the rss cloud if this is not an archive AND rsscloud is enabled
      if($archive == FALSE && $enable_rsscloud == 1) {
        $resp = httpRequest($rss_cloud_domain, $rss_cloud_port, $rss_cloud_method, $rss_cloud_ping_path, array("url" => $s3url), $rss_cloud_timeout);
        loggit(1, "Pinged the rss cloud for feed: [$s3url].");
      }
    }
  }


  loggit(1, "Built blog rss feed for user: [$username | $uid].");
  return($rss);
}


//_______________________________________________________________________________________
//Unlink an post from the user's mbcatalog
function unlink_post($uid = NULL, $aid = NULL)
{
  //Check params
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }
  if($aid == NULL) {
    loggit(2,"The post id is blank or corrupt: [$aid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_mbcatalog WHERE userid=? AND postid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $uid, $aid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and leave
  loggit(1,"Deleted: [$delcount] post: [$aid] from user: [$uid].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Delete a post from the post table
function delete_post($pid = NULL)
{
  //Check params
  if($pid == NULL) {
    loggit(2,"The post id is blank or corrupt: [$pid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the id in the transaction table
  $stmt = "DELETE FROM $table_post WHERE id=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("s", $pid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $delcount = $sql->affected_rows or print(mysql_error());
  if( $delcount < 1) {
    loggit(2,"Failed to delete post: [$pid].");
    return(FALSE);
  }
  $sql->close() or print(mysql_error());

  //Log and leave
  loggit(1,"Deleted: [$delcount] post: [$pid].");
  return(TRUE);
}



//_______________________________________________________________________________________
//Build an rss feed for the given user
function build_blog_opml_feed($uid = NULL, $max = NULL, $archive = FALSE, $posts = NULL, $nos3 = FALSE)
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/s3/S3.php";

  $username = get_user_name_from_uid($uid);
  $prefs = get_user_prefs($uid);

  //Get a correct title
  if( !empty($prefs['microblogtitle']) ) {
    $title = $prefs['microblogtitle'];
  } else {
    $title = "What $username is saying.";
  }

  //Get a proper max value
  if($max == NULL) {
    if( !empty($prefs['maxlist']) ) {
      $max = $prefs['maxlist'];
    } else {
      $max = $default_max_rss_items;
    }
  }

  //Get the right timezone
  date_default_timezone_set('America/Chicago');

  //If the array of posts is being passed in as an argument we don't make this call
  if( $posts == NULL || !is_array($posts) ) {
    $posts = get_blog_posts($uid, $max, NULL, $archive);
  }

  //Get the correct dates
  if( empty($posts) ) {
    $dateCreated = date("D, d M Y H:i:s O");
    $dateModified = date("D, d M Y H:i:s O");
  } else {
    $dateCreated = date("D, d M Y H:i:s O", $posts[0]['createdon']);
    $dateModified = date("D, d M Y H:i:s O", $posts[0]['createdon']);
  }

  //The feed string
  $opml = '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n";
  $opml .= "<!-- OPML generated by ".$system_name." v".$version." on ".date("D, d M Y H:i:s O")." -->\n";
  $opml .= '<opml version="2.0">'."\n";

  $opml .= "
      <head>
        <title>".xmlentities($title)."</title>
        <dateCreated>$dateCreated</dateCreated>
        <dateModified>$dateModified</dateModified>
        <ownerName>".get_user_name_from_uid($uid)."</ownerName>
        <ownerId>".trim($uid)."</ownerId>
        <expansionState></expansionState>
        <expansionState></expansionState>
        <vertScrollState>1</vertScrollState>
        <windowTop>146</windowTop>
        <windowLeft>107</windowLeft>
        <windowBottom>468</windowBottom>
        <windowRight>560</windowRight>
      </head>\n";

  $opml .= "
      <body>";

  foreach( $posts as $post ) {
      $link = '';
      if( !empty($post['url']) ) {
        $link = '                      <outline type="link" url="'.xmlentities(trim($post['url'])).'" text="'.xmlentities(trim($post['url'])).'" />';
      }
      $title = '';
      if( !empty($post['title']) ) {
        $title = xmlentities(trim(str_replace(array("\r","\n"), '', $post['title'])));
      } else {
        $title = xmlentities(trim(str_replace(array("\r","\n"), '', $post['content'])));
      }
      $content = str_replace(array("\r", "\r\n", "\n"), '', $post['content']);
      $content = xmlentities($content);

      $opml .= "              <outline text=\"".$title."\">";

      $opml .= "
                      <outline text=\"".date("D, d M Y H:i", $post['createdon'])."\" />
                      <outline text=\"".$content."\" />\n".$link;


      $opml .= "	      </outline>\n";
  }

  $opml .= "
      </body>";

  $opml .= "</opml>";


  //If this user has S3 storage enabled, then do it
  if( (s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3 ) {
    //First we get all the key info
    $s3info = get_s3_info($uid);

    //Get the microblog feed file name
    $filename = get_microblog_opml_filename($uid);
    $arcpath = '';

    //Was this a request for a monthly archive?
    if( $archive != FALSE ) {
      $arcpath = "/arc/".date('Y')."/".date('m')."/".date('d');
      //loggit(3, "Archive path: [".$arcpath."]");
    }

    //Put the file
    $s3res = putInS3($opml, $filename, $s3info['bucket'].$arcpath, $s3info['key'], $s3info['secret'], "text/xml");
    if(!$s3res) {
      loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
      //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
    } else {
      $s3url = get_s3_url($uid, $arcpath, $filename);
      loggit(1, "Wrote feed to S3 at url: [$s3url].");

      //Ping the opml cloud
      //$resp = httpRequest($rss_cloud_domain, $rss_cloud_port, $rss_cloud_method, $rss_cloud_ping_path, array("url" => $s3url));
      //loggit(1, "Pinged the rss cloud for feed: [$s3url].");
    }
  }


  loggit(1, "Built blog opml feed for user: [$username | $uid].");
  return($opml);
}


//_______________________________________________________________________________________
//Get the file name of the microblog feed
function get_microblog_feed_filename($uid = NULL)
{
  //If uid is zero then balk
  if( empty($uid) ) {
    loggit(2,"Can't get the username from this uid: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the user's prefs
  $prefs = get_user_prefs($uid);
  if( empty($prefs['mbfilename']) ) {
    return($default_blog_feed_file_name);
  }

  //loggit(1,"Returning user name: [$username] for uid: [$uid]");
  return($prefs['mbfilename']);
}


//_______________________________________________________________________________________
//Get the file name of the microblog opml file
function get_microblog_opml_filename($uid = NULL)
{
  //If uid is zero then balk
  if( empty($uid) ) {
    loggit(2,"Can't get the username from this uid: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the user's prefs
  $prefs = get_user_prefs($uid);
  if( empty($prefs['mbfilename']) ) {
    return($default_blog_opml_file_name);
  }

  return( chop_extension($prefs['mbfilename']).".opml" );
}


//_______________________________________________________________________________________
//Get the file name of the microblog html file
function get_microblog_html_filename($uid = NULL)
{
  //If uid is zero then balk
  if( empty($uid) ) {
    loggit(2,"Can't get the username from this uid: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the user's prefs
  $prefs = get_user_prefs($uid);
  if( empty($prefs['mbfilename']) ) {
    return($default_blog_html_file_name);
  }

  return( chop_extension($prefs['mbfilename']).".html" );
}


//_______________________________________________________________________________________
//Get the file name of the microblog javascript widget file
function get_microblog_js_filename($uid = NULL)
{
  //If uid is zero then balk
  if( empty($uid) ) {
    loggit(2,"Can't get the username from this uid: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the user's prefs
  $prefs = get_user_prefs($uid);
  if( empty($prefs['mbfilename']) ) {
    return($default_blog_js_file_name);
  }

  return( chop_extension($prefs['mbfilename']).".js" );
}



//_______________________________________________________________________________________
//Get the title of the microblog
function get_microblog_title($uid = NULL)
{
  //If uid is zero then balk
  if( empty($uid) ) {
    loggit(2,"The user id given is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the user's prefs
  $prefs = get_user_prefs($uid);
  $username = get_user_name_from_uid($uid);

  //Get a correct title
  if( !empty($prefs['microblogtitle']) ) {
    return($prefs['microblogtitle']);
  } else {
    return("What $username is saying.");
  }

}



//_______________________________________________________________________________________
//Get the file name of the microblog archive
function get_microblog_archive_filename($uid = NULL)
{
  //If uid is zero then balk
  if( empty($uid) ) {
    loggit(2,"Can't get the username from this uid: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the user's prefs
  //$prefs = get_user_prefs($uid);
  //if( empty($prefs['mbfilename']) ) {
    return($default_blog_archive_file_name);
  //}

  //loggit(1,"Returning user name: [$username] for uid: [$uid]");
  //return($prefs['mbfilename']);
}


//_______________________________________________________________________________________
//Get the file name of the microblog widget
function get_microblog_archive_widget_filename($uid = NULL)
{
  //If uid is zero then balk
  if( empty($uid) ) {
    loggit(2,"Can't get the username from this uid: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get the user's prefs
  //$prefs = get_user_prefs($uid);
  //if( empty($prefs['mbfilename']) ) {
    return($default_blog_archive_widget_filename);
  //}

  //loggit(1,"Returning user name: [$username] for uid: [$uid]");
  //return($prefs['mbfilename']);
}


//_______________________________________________________________________________________
//Build an html archive for the given user
function build_blog_html_archive($uid = NULL, $max = NULL, $archive = FALSE, $posts = NULL, $nos3 = FALSE)
{
  //Check parameters
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/s3/S3.php";

  $username = get_user_name_from_uid($uid);
  $prefs = get_user_prefs($uid);

  //Get a proper max value
  if($max == NULL) {
    if( !empty($prefs['maxlist']) ) {
      $max = $prefs['maxlist'];
    } else {
      $max = $default_max_archive_items;
    }
  }

  //If the array of posts is being passed in as an argument we don't make this call
  if( $posts == NULL || !is_array($posts) ) {
    $posts = get_blog_posts($uid, $max, NULL, $archive);
  }

  //Get a correct title
  if( !empty($prefs['microblogtitle']) ) {
    $title = $prefs['microblogtitle'];
  } else {
    $title = "What $username is saying.";
  }

  //Get a stylesheet
  if( !empty($prefs['mbarchivecss']) ) {
    $css = $prefs['mbarchivecss'];
  } else {
    $css = $default_archive_style_filename;
  }

  //The feed string
  $html = '<!DOCTYPE html>'."\n";
  $html .= '<html><head>';

  $html .= "\n
      <title>$title</title>
      <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0\">
      <link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"$css\" />
      </head><body><div class=\"container\"><div class=\"row page-header\" id=\"divPageTitle\">";

  if( !empty($prefs['avatarurl']) ) {
    $html .= "      <img class=\"avatarheader\" alt=\"\" src=\"".$prefs['avatarurl']."\" />\n";
  }

  $html .= "\n
      <h1>$title <small>(<a href=\"".$prefs['homepagelink']."\">Homepage</a>)</small>";


  $html .= "</h1>
      <p class=\"byline\">Last built on: ".date("D, d M Y H:i")."
      by: $system_name, v$version
      for: ".get_email_from_uid($uid)." ($username)</p>";

  $html .= "</div>\n<div class=\"pageContentWrapper Archive\">\n<div class=\"row\" id=\"divArchive\">\n";

  foreach( $posts as $post ) {
	if ($post['url'] == "") {
	  $rsslink = "";
          $linkfull = "";
          $guid = "        <span class=\"guid\">".$post['id']."</span>";
        } else {
	  if(!empty($post['shorturl'])) {
		$rssurl = htmlspecialchars($post['shorturl']);
                $rsslink = "        <a class=\"shortlink\" href=\"$rssurl\">Short Link</a>";
                $guid = "        <span class=\"guid\">$rssurl</span>";
                $linkfull = "        <a class=\"linkfull\" href=\"".htmlspecialchars($post['url'])."\">Link</a>";
          } else {
                $rssurl = htmlspecialchars($post['url']);
                 $rsslink = "        <a class=\"linkfull\" href=\"$rssurl\">Link</a>";
                $guid = "        <span class=\"guid\">$rssurl</span>";
          }
	}
        if( !empty($post['enclosure']) ) {
          $enclosures = unserialize($post['enclosure']);
        }

       $html .= "
      <div class=\"item\">
        <p class=\"pubdate\">".date("D, d M Y H:i", $post['createdon'])."</p>\n        <div class=\"content\">";
        if( !empty($post['title']) ) {
		$html .= "        <h3>".xmlentities(trim($post['title']))."</h3>\n";
        }
        $html .= "        <p class=\"description\">".xmlentities(trim( str_replace("\n", '', $post['content'] ) ))."</p>\n";
        if( isset($enclosures) ) {
          if( is_array($enclosures) && count($enclosures) > 0 ) {
            foreach($enclosures as $enclosure) {
              if( strripos($enclosure['url'], ".jpg") !== FALSE || strripos($enclosure['url'], ".gif") !== FALSE || strripos($enclosure['url'], ".png") !== FALSE ) {
                $html .= '        <p class="enclosureview"><img class="enclosureimg" alt="" src="'.htmlspecialchars($enclosure['url']).'" /></p>'."\n";
              }
            }
          }
        }
        $html .= $guid."\n";
        if(!empty($rsslink)) { $html .= $rsslink."\n"; }
        if(!empty($linkfull)) { $html .= $linkfull."\n"; }
        if( isset($enclosures) ) {
          if( is_array($enclosures) && count($enclosures) > 0 ) {
            foreach($enclosures as $enclosure) {
              $html .= '        <a class="enclosure" href="'.htmlspecialchars($enclosure['url']).'" data-length="'.$enclosure['length'].'" data-type="'.$enclosure['type'].'">Enclosure</a>'."\n";
            }
          }
        }
	if( !empty($post['sourceurl']) || !empty($post['sourcetitle']) ) {
          $html .= '        Source: <a class="source" href="'.htmlspecialchars($post['sourceurl']).'">'.htmlspecialchars($post['sourcetitle']).'</a>'."\n";
	}
      $html .= "      </div></div>\n";
  }

  $html .= "\n    </div></div></div></body>\n  </html>";

  //If this user has S3 storage enabled, then do it
  if( (s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3 ) {
    //First we get all the key info
    $s3info = get_s3_info($uid);

    //Get the microblog feed file name
    $filename = get_microblog_html_filename($uid);
    $arcpath = '';

    //Was this a request for a monthly archive?
    if( $archive != FALSE ) {
      $arcpath = "/arc/".date('Y')."/".date('m')."/".date('d');
      //loggit(3, "Archive path: [".$arcpath."]");
    }

    //Put the file
    $s3res = putInS3($html, $filename, $s3info['bucket'].$arcpath, $s3info['key'], $s3info['secret'], "text/html");
    if(!$s3res) {
      loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
      //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
    } else {
      $s3url = get_s3_url($uid, $arcpath, $filename);
      loggit(1, "Wrote file to S3 at url: [$s3url].");
    }

    //Put the style file
    $s3res = putFileInS3("$confroot/$templates/$default_archive_style_filename",
                         $default_archive_style_filename,
                         $s3info['bucket'].$arcpath,
                         $s3info['key'], $s3info['secret'], "text/css");
    if(!$s3res) {
      loggit(2, "Could not create S3 file: [$default_archive_style_filename] for user: [$username].");
      //loggit(3, "Could not create S3 file: [$default_archive_style_filename] for user: [$username].");
    } else {
      $s3url = get_s3_url($uid, $arcpath, $default_archive_style_filename);
      loggit(1, "Wrote file to S3 at url: [$s3url].");
    }

  }


  loggit(1, "Built blog archive file for user: [$username | $uid].");
  return($html);
}

//_______________________________________________________________________________________
//Build a javascript linkblog widget for the given user
function build_blog_script_widget($uid = NULL, $max = NULL, $archive = FALSE, $posts = NULL, $nos3 = FALSE)
{
  //Check parameters
  if( empty($uid) ) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/s3/S3.php";

  $username = get_user_name_from_uid($uid);
  $prefs = get_user_prefs($uid);

  //Get a proper max value
  if($max == NULL) {
    if( !empty($prefs['maxlist']) ) {
      $max = $prefs['maxlist'];
    } else {
      $max = $default_max_widget_items;
    }
  }

  //If the array of posts is being passed in as an argument we don't make this call
  if( $posts == NULL || !is_array($posts) ) {
    $posts = get_blog_posts($uid, $max, NULL, $archive);
  }

  //Get a correct title
  if( !empty($prefs['microblogtitle']) ) {
    $title = $prefs['microblogtitle'];
  } else {
    $title = "What $username is saying.";
  }

  //The feed string
  $html =  'function newLineEl(elType,elClass,elHtml) { newel = document.createElement(elType); newel.class = elClass; newel.innerHTML = elHtml; return(newel); }'."\n\n";
  $html .= 'var mbwel = document.getElementById("divMicroblogWidget");'."\n\n";
  $html .= 'mbwelDIV = newLineEl("div", "divMiniLinkBlog", "");'."\n\n";
  $html .= 'mbwel.appendChild(mbwelDIV);'."\n\n";
  $html .= 'mbwelDIV.appendChild( newLineEl("p", "miniLinkBlogLastUpdate", "<b>Microblog updated</b>: '.date("D, d M Y H:i").'") );'."\n\n";
  $html .= 'mbwelUL = newLineEl("ul", "miniLinkBlog", "");mbwelDIV.appendChild(mbwelUL);'."\n\n";

  foreach( $posts as $post ) {
	if ($post['url'] == "") {
	  $rsslink = "";
          $rsslinkclose = "";
          $linkfull = "";
          $guid = "        <span class=\"guid\">".$post['id']."</span>";
        } else {
	  if(!empty($post['shorturl'])) {
		$rssurl = htmlspecialchars($post['shorturl']);
          } else {
                $rssurl = htmlspecialchars($post['url']);
          }
	  $rsslink = '<a href=\"'.$rssurl.'\">';
	  $rsslinkclose = '</a>';
	}

       if( !empty($post['title']) ) {
          $headline = $post['title'];
       } else {
	  $headline = $post['content'];
       }
       $headline = xmlentities(trim($headline));
       $headline = str_replace(array("\r", "\r\n", "\n"), '', $headline);
       $html .= 'lastli = mbwelUL.appendChild( newLineEl("li", "miniLinkBlogItem", "'.$rsslink.$headline.$rsslinkclose.'") );'."\n\n";

       if( $post['tweeted'] == 1 ) {
         $html .= 'lastli.classList.add("tweeted");'."\n\n";
       }
  }

  //If this user has S3 storage enabled, then do it
  if( (s3_is_enabled($uid) || sys_s3_is_enabled()) && !$nos3 ) {
    //First we get all the key info
    $s3info = get_s3_info($uid);

    //Get the microblog feed file name
    $filename = get_microblog_js_filename($uid);
    $arcpath = '';

    //Was this a request for a monthly archive?
    if( $archive != FALSE ) {
      $arcpath = "/arc/".date('Y')."/".date('m')."/".date('d');
      //loggit(3, "Archive path: [".$arcpath."]");
    }

    //Put the file
    $s3res = putInS3($html, $filename, $s3info['bucket'].$arcpath, $s3info['key'], $s3info['secret'], "application/javascript");
    if(!$s3res) {
      loggit(2, "Could not create S3 file: [$filename] for user: [$username].");
      //loggit(3, "Could not create S3 file: [$filename] for user: [$username].");
    } else {
      $s3url = get_s3_url($uid, $arcpath, $filename);
      loggit(1, "Wrote file to S3 at url: [$s3url].");
    }
  }


  loggit(1, "Built blog widget file for user: [$username | $uid].");
  return($html);
}

//########################################################################################
?>
