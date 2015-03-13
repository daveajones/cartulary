<?
  // If the HOST header didn't contain our server's canonical url, we do a lookup
  // in the redirection table and meta-refresh redirect to that location

  // Includes
if ( isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != $system_fqdn ) {
    require_once "$confroot/$includes/util.php";
    require_once "$confroot/$includes/net.php";
    $newurl = get_redirection_url_by_host_name($_SERVER['HTTP_HOST']);
    if( !empty($newurl) ) {
        add_redirection_hit_by_url($newurl);
        header("Location: ".$newurl);
        return(0);
    }
}