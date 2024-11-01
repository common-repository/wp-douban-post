<?php
/*
 Plugin Name: WP-Douban-Post
 Plugin URI: http://wordpress.org/extend/plugins/wp-douban-post/
 Description: Send one my saying broadcast to douban when you publish the post.
 Version: 1.0.0
 Author: windlx ( Tony Luo )
 Author URI: http://blog.tech4k.com/about
 

 */
require_once 'doubanOAuth.php';
require_once 'wp-douban-post-config.php';

function wp_douban_post_options()
{
	global $douban_consumer_key, $douban_consumer_secret;
	//Doesn't has Access Token
	if (  ( get_option('wdp_oauth_access_token') == '' ) || (get_option('wdp_oauth_access_token_secret') == '' ) ) {

		if ( get_option('wdp_douban_request_token') != '' && get_option('wdp_douban_request_token_secret') != '' ) {
			//Get Access Token
			$douban = new DoubanOAuth( $douban_consumer_key ,
			$douban_consumer_secret, get_option('wdp_douban_request_token') , get_option('wdp_douban_request_token_secret') );
			$access_token = $douban->getAccessToken();

			//Get User Info, Verify Access Token
			$douban = new DoubanOAuth( $douban_consumer_key ,
			$douban_consumer_secret, $access_token['oauth_token'] , $access_token['oauth_token_secret'] );
			$doubanInfo = $douban->OAuthRequest('http://api.douban.com/people/%40me', array(), 'GET');

			if($doubanInfo == "no auth"){
				$message = __('Douban Authentication Failed', 'wp-douban-post');
			}
			else {
				update_option('wdp_oauth_access_token', $access_token['oauth_token']);
				update_option('wdp_oauth_access_token_secret', $access_token['oauth_token_secret']);
				$doubanInfo = simplexml_load_string($doubanInfo);
				$doubanInfo_ns = $doubanInfo->children('http://www.douban.com/xmlns/');
				$doubanInfo_id = $doubanInfo->title;
				update_option('wdp_douban_user_id', (string) $doubanInfo_id);
				$message = __('Douban Authentication Successful', 'wp-douban-post');
			}
			echo '<div class="updated"><strong><p>'. $message . '</p></strong></div>';
		}
	}
	$dc_url = WP_PLUGIN_URL.'/wp-douban-post/douban-start.php';
	?>
<div class=wrap>
<form method="post" action="" name="optionForm">
<h2><?php _e('Douban Post Options', 'wp-douban-post'); ?></h2>
<fieldset class="options">
<table>
<?php if (get_option('wdp_oauth_access_token') != '' && get_option('wdp_oauth_access_token_secret') != '' ) {?>
	<tr>
		<td valign="top" align="right"><?php _e('Douban ID:', 'wp-douban-post'); ?></td>
		<td><?php echo get_option('wdp_douban_user_id') ?></td>
	</tr>
	<tr>
		<td valign="top" align="right"><?php _e('Access Token:', 'wp-douban-post'); ?></td>
		<td><?php echo get_option('wdp_oauth_access_token') ?></td>
	</tr>
	<tr>
		<td valign="top" align="right"><?php _e('Access Token Secret:', 'wp-douban-post'); ?></td>
		<td><?php echo get_option('wdp_oauth_access_token_secret') ?></td>
	</tr>
	<?php } ?>
</table>
</fieldset>
<p class="submit"><input type="submit" name="authorize"
	value="Get Access Token"
	onclick="window.open('<?php echo $dc_url ?>','width=800,height=800,left=150,top=100,scrollbar=no,resize=no');return false;" />
</p>
</form>
</div>
	<?
}
function wp_douban_post_notify_douban($post_id){
	global $douban_consumer_key, $douban_consumer_secret;
	
	if (( get_option('wdp_oauth_access_token') == '' ) || (get_option('wdp_oauth_access_token_secret') == '' ) ) {
		return;
	}
	$post = get_post($post_id);

	// check for private posts
	if ($post->post_status == 'private') {
		return;
	}
	//TODO implement shorten link filter
	$url = apply_filters('douban_blog_post_url', get_permalink($post_id));
	//TODO implement formate of the message
	$content='<?xml version="1.0" encoding="UTF-8"?>'.
			'<entry xmlns:ns0="http://www.w3.org/2005/Atom" xmlns:db="http://www.douban.com/xmlns/">'.
			'<content>'.'I have published: '.$post->post_title.' '.$url.'</content>'.
			'</entry>';
	$douban = new DoubanOAuth($douban_consumer_key ,
	$douban_consumer_secret, get_option('wdp_oauth_access_token') ,
	get_option('wdp_oauth_access_token_secret') );
	$doubanInfo = $douban->OAuthRequest('http://api.douban.com/miniblog/saying', array(), 'POST', $content);
}
//Set option page for the plugin
function wp_douban_post_options_admin(){
	add_options_page('wp_douban_post', __('WP Douban Post', 'wp-douban－post'), 5,
	__FILE__,  'wp_douban_post_options');
}
//Activate Plugin, set default value for options
function wp_douban_post_activate() {
	update_option('wdp_douban_request_token','');
	update_option('wdp_douban_request_token_secret','');
	update_option('wdp_oauth_access_token','');
	update_option('wdp_oauth_access_token_secret','');
	update_option('wdp_douban_user_id','');
}

//Deactivate Plugin, remove options
function wp_douban_post_deactivate() {
	delete_option('wdp_douban_request_token');
	delete_option('wdp_douban_request_token_secret');
	delete_option('wdp_oauth_access_token');
	delete_option('wdp_oauth_access_token_secret');
	delete_option('wdp_douban_user_id');
}

register_activation_hook(__FILE__, 'wp_douban_post_activate');
register_deactivation_hook(__FILE__, 'wp_douban_post_deactivate');
add_action('admin_menu', 'wp_douban_post_options_admin');
add_action('publish_post','wp_douban_post_notify_douban');
?>
