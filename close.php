<?php
include "../../../wp-config.php";

update_option("wdp_douban_request_token",$_GET['oauth_token']); 

?>
<h4>Douban Authentication Complete.</h4>
<button onclick="opener.location.reload(); window.close();">close</button>