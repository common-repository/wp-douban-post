<?php
include "../../../wp-config.php";
require_once 'wp-douban-post-config.php';

if(!class_exists('DoubanOAuth')){
	include dirname(__FILE__).'/doubanOAuth.php';
}

$to = new DoubanOAuth($douban_consumer_key, $douban_consumer_secret);
	
$tok = $to->getRequestToken();

update_option("wdp_douban_request_token_secret",$tok['oauth_token_secret']);
update_option("wdp_oauth_access_token",'');
update_option("wdp_oauth_access_token_secret",'');

$request_link = $to->getAuthorizeURL($tok['oauth_token'])."&oauth_callback=".get_option('home')."/wp-content/plugins/wp-douban-post/close.php";

header('Location:'.$request_link);
?>
