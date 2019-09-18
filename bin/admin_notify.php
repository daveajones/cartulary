<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?

$args = getopt(null, ["content:","title:"]);

if( isset($args['content']) && isset($args['title']) ) {
    $content = $args['content'];
    $title = $args['title'];
} else {
    loggit(2, "Invalid arguments passed on command line:  [".print_r($args, TRUE)."]");
    echo "Invalid arguments. Must use the form --content='' --title=''"."\n";
    exit(1);
}

echo print_r($content, TRUE)."\n";
echo print_r($title, TRUE)."\n";

add_admin_log_item($content, $title);

exit(0);