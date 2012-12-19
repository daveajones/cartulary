<?

//Get the configuration variables
$envars = parse_ini_file(get_cfg_var("cartulary_conf")."/conf/cartulary.conf");

//Pass the array back
extract($envars);

?>
