<?php
/**
  * The file that is called when the plugin is uninstalled
  */

//if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

$options = array("ss_sid", "ss_mode", "ss_async_post", "ss_timeout", "ss_sessid", "ss_ipaddress", "ss_domain",
                 "ss_dns_cache_refresh_interval", "ss_dns_cache_path", "ss_username", "ss_calltype", "validate_id",
                 "ss_other_headers", "ss_idn", "ss_ip_index", "ss_enable_endpoint_ssl", "ss_enable_log", "ss_dns_time",
                 "ss_enable_captcha", "ss_redirect_domain", "ss_enable_block", "ss_support_email", "ss_skip_url_list",
                 "ss_ip", "ss_content_list");

foreach ($options as $option) {
    delete_option($option);
}
