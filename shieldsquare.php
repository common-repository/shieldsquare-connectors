<?php
/*
 * Description of what this module (or file) is doing.
 *
 * Plugin Name: ShieldSquare
 * Description: ShieldSquare - Real-time Bot Management Solution for Web Apps, Mobile & APIs
 * Version: 1.1.0
 * @author  Narasimha Reddy <narasimha.m@shieldsquare.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

set_time_limit(0);
ini_set('max_execution_time', 0);

/*
 * Abort loading if WordPress is upgrading
 */
if (defined("WP_INSTALLING") && WP_INSTALLING)
    return;

if (defined('ABSPATH') !== true) {
    die('Nope, not accessing this');
}

define("SHIELDSQUARE_PLUGIN_NAME__", "ShieldSquare");
define("SHIELDSQUARE_PLUGIN_SLUG__", "__shieldsquare_");
define('SHIELDSQUARE_VERSION', '1.0.0');
define('SHIELDSQUARE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SHIELDSQUARE_PLUGIN_DIR', trailingslashit(plugin_dir_path(__FILE__)));
define('SHIELDSQUARE_LOG_FILENAME', 'shieldsquare.log');


class ShieldSquare
{
    /* magic function (triggered on initialization) */
    public function __construct()
    {
        /* hook register */
        register_activation_hook(__FILE__, array($this, 'ss_plugin_activation'));
        register_deactivation_hook(__FILE__, array($this, 'ss_plugin_deactivation'));

        /* add actions here */
        add_action('admin_menu', array($this, 'ss_settings_menu'));
        add_action('admin_init', array($this, 'ss_settings_view'));
        add_action('wp_enqueue_scripts', array($this, 'shieldsquare_init'));
        add_action('init', array($this, 'load_shieldsquare_api'));

        /* load classes */
        require_once SHIELDSQUARE_PLUGIN_DIR . "classes/shieldsquare-autoloader.php";
        if(class_exists('ShieldSquare_Autoloader')) {
            new ShieldSquare_Autoloader();
        }
    }

    public static function ss_plugin_activation() {
        /* Check the dependency packages */
        $curlCheck = false;
        $phpCheck = (version_compare(phpversion(), '5.0.0') >= 0);
        if(function_exists('curl_version')) {
            $curlInfo = curl_version();
            $curlCheck = (version_compare($curlInfo['version'], '7.16.2') >= 0);
        }

        if ($curlCheck === false || $phpCheck === false) {
            deactivate_plugins(__FILE__);
            $error_message("Please install all dependency packages with specified versions");
            die($error_message);
        }
    }

    public static function ss_plugin_deactivation() {}

    /**
     * The shieldsquare menu settings
     *
     * @return none
     */
    public function ss_settings_menu()
    {
        add_options_page('ShieldSquare Configuration',
                         'ShieldSquare Configuration',
                         'manage_options',
                         'shieldsquare_settings_view',
                         array($this, 'ss_settings_form'));
    }//end ss_settings_menu()

    /**
     * The ShieldSquare settings view
     *
     * @return none
     */
    public function ss_settings_view()
    {
        register_setting('shieldsquare_settings_fields', 'ss_sid');
        register_setting('shieldsquare_settings_fields', 'ss_mode');
        register_setting('shieldsquare_settings_fields', 'ss_async_post');
        register_setting('shieldsquare_settings_fields', 'ss_timeout');
        register_setting('shieldsquare_settings_fields', 'ss_domain');
        register_setting('shieldsquare_settings_fields', 'ss_dns_cache_refresh_interval');
        register_setting('shieldsquare_settings_fields', 'ss_dns_cache_path');
        register_setting('shieldsquare_settings_fields', 'ss_username');
        register_setting('shieldsquare_settings_fields', 'ss_calltype');
        register_setting('shieldsquare_settings_fields', 'ss_ipaddress');
        register_setting('shieldsquare_settings_fields', 'ss_sessid');
        register_setting('shieldsquare_settings_fields', 'ss_other_headers');
        register_setting('shieldsquare_settings_fields', 'ss_idn');
        register_setting('shieldsquare_settings_fields', 'ss_enable_log');
        register_setting('shieldsquare_settings_fields', 'ss_enable_captcha');
        register_setting('shieldsquare_settings_fields', 'ss_enable_block');
        register_setting('shieldsquare_settings_fields', 'ss_redirect_domain');
        register_setting('shieldsquare_settings_fields', 'ss_support_email');
        register_setting('shieldsquare_settings_fields', 'ss_ip_index');
        register_setting('shieldsquare_settings_fields', 'ss_enable_endpoint_ssl');
        register_setting('shieldsquare_settings_fields', 'ss_content_list');
        register_setting('shieldsquare_settings_fields', 'ss_skip_url_list');
    }//end ss_settings_view()

    /**
     * The shieldsquare load call types
     *
     * @return object
     */
    function load_shieldsquare_api()
    {
        if (is_admin()) {
            return false;
        }

        $userid = get_option('ss_username');
        $calltype = (get_option('ss_calltype') !== '') ? (int)get_option('ss_calltype') : 1;
        $ssAPI = new ShieldSquare_API();
        $ssResponse = $ssAPI->validate_request($userid, $calltype);
        return $ssResponse->responsecode;

    }//end load_shieldSquare_calltype()

    /**
     * The shieldsquare_init for registering scripts
     *
     * @return none
     */
    function shieldsquare_init()
    {
        if (!is_admin()) {
            wp_deregister_script('jquery');
            wp_register_script('jquery', false);
        }

    }//end shieldsquare_init()

    /**
     * The ss2_settings_form settings form
     *
     * @return none
     */
    function ss_settings_form()
    {
        ?>
        <style>
            .tooltip {
                position: relative;
                display: inline-block;
                /*border-bottom: 1px dotted black;*/
            }

            .tooltip .tooltiptext {
                visibility: hidden;
                width: 350px;
                background-color: #555;
                color: #fff;
                text-align: center;
                border-radius: 6px;
                padding: 5px 0;
                position: absolute;
                z-index: 1;
                bottom: 125%;
                left: 20%;
                margin-left: -70px;
                opacity: 0;
                transition: opacity 0.3s;
            }

            .tooltip .tooltiptext::after {
                content: "";
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border-width: 5px;
                border-style: solid;
                border-color: #555 transparent transparent transparent;
            }

            .tooltip:hover .tooltiptext {
                visibility: visible;
                opacity: 1;
            }
        </style>
        <div class='wrap'>
           <h2>ShieldSquare Configuration</h2>
           <form method="post" action="options.php">
              <?php settings_fields( 'shieldsquare_settings_fields' ); ?>
              <?php do_settings_sections('shieldsquare_settings_fields'); ?>
              <table class="form-table">
                 <tr valign="top">
                    <th scope="row">Subscriber ID</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_sid" value="<?php echo esc_attr( get_option('ss_sid', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx') ); ?>" /><span class="tooltiptext">Subscriber ID</span></span></td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Mode</th>
                    <td><span class="tooltip"><label>
                       <input type="radio" name="ss_mode" id="monitor" value="Monitor" <?php if( ( get_option('ss_mode') == "") || ( get_option('ss_mode') === "Monitor") ) { echo "checked='checked'";} ?>  onclick="showHideCaptchaConfig()" />Monitor
                       </label>
                       <label><input type="radio" name="ss_mode" id="active" value="Active" <?php if ( get_option('ss_mode') === "Active") { echo " checked='checked'";} ?>  onclick="showHideCaptchaConfig()" />Active</label><span class="tooltiptext">Monitor mode allows you to monitor the bot traffic on your website/mobile apps but you will not be able to take action against bots.

Active mode allows you to take required action ( ALLOW -0, CAPTCHA -2 , Block -3, Feed Fake Data- 4 ) against bots impacting your website and mobile app. </span></span><br>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Async Http Post</th>
                    <td><span class="tooltip"><input type="checkbox" name="ss_async_post" value="true" <?php checked('true', get_option('ss_async_post', 'true')); ?> /><span class="tooltiptext">This is used to post the data asynchronously. In the Monitor mode this has to be set as Yes.</span></span>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">API Timeout Value</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_timeout" value="<?php echo esc_attr( get_option('ss_timeout', 100) ); ?>" /><span class="tooltiptext">API Server request timeout value in milliseconds</span></span>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Enable API Sever SSL</th>
                    <td><span class="tooltip"><label>
                       <input type="radio"  name="ss_enable_endpoint_ssl" value="true" <?php if( get_option('ss_enable_endpoint_ssl') === "true") { echo "checked='checked'";} ?>/>Yes
                       </label>
                       <label><input type="radio"  name="ss_enable_endpoint_ssl" value="false" <?php if ( ( get_option('ss_enable_endpoint_ssl') == "") || ( get_option('ss_enable_endpoint_ssl') === "false") ) { echo " checked='checked'";} ?> />No</label><span class="tooltiptext">Set it to Yes to send secured content to API Server </span></span><br>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">API Server</th>
                    <td><span class="tooltip">
                      <select name="ss_domain" class="regular-text">
                        <option value="ss_scus.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_scus.shieldsquare.net"); ?>>Dallas, United States</option>
                        <option value="ss_neus.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_neus.shieldsquare.net"); ?>>Washington. D.C, United States</option>
                        <option value="ss_neus.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_neus.shieldsquare.net"); ?>>South Carolina, United States</option>
                        <option value="ss_nwus.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_nwus.shieldsquare.net"); ?>>Seattle, United States</option>
                        <option value="ss_swus.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_swus.shieldsquare.net"); ?>>San Jose, United States</option>
                        <option value="ss_sa.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_sa.shieldsquare.net"); ?>>Singapore City, Singapore</option>
                        <option value="ss_ew.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_ew.shieldsquare.net"); ?>>Paris, France</option>
                        <option value="ss_br.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_br.shieldsquare.net"); ?>>Sao Paulo, Brazil</option>
                        <option value="ss_au.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_au.shieldsquare.net"); ?>>Sydney, Australia</option>
                        <option value="ss_hk.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_hk.shieldsquare.net"); ?>>Hong Kong City, Hong Kong</option>
                        <option value="ss_in.shieldsquare.net" <?php selected(get_option('ss_domain'), "ss_in.shieldsquare.net"); ?>>Chennai, India</option>
                      </select><span class="tooltiptext">Select ShieldSquare API Endpoint server closest to your website’s server from the drop down.</span></span>
                    <br>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">API DNS Cache TTL</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_dns_cache_refresh_interval" value="<?php echo esc_attr( get_option('ss_dns_cache_refresh_interval', 3600) ); ?>" /><span class="tooltiptext">This parameter indicates the time after which ShieldSquare Endpoint DNS is resolved. (Minimum value: -1, Maximum value: 18000)</span></span>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">API DNS Cache File Path</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_dns_cache_path" value="<?php echo esc_attr( get_option('ss_dns_cache_path', '/tmp/')); ?>" /><span class="tooltiptext">This parameter is the path to store shieldsquare error logs.</span></span><br>
                    </td>
                 <tr valign="top">
                 <tr valign="top">
                    <th scope="row">User Name</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_username" value="<?php echo esc_attr( get_option('ss_username', '')); ?>" /><span class="tooltiptext">User Name</span></span><br>
                    </td>
                 <tr valign="top">
                 <tr valign="top">
                    <th scope="row">Call Type</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_calltype" value="<?php echo esc_attr( get_option('ss_calltype', 1)); ?>" /><span class="tooltiptext">Call Type</span></span><br>
                    </td>
                 <tr valign="top">
                    <th scope="row">IP Address Picker</th>
                    <td><span class="tooltip">
                       <select name="ss_ipaddress" class="regular-text">
                          <option value="auto" <?php selected(get_option('ss_ipaddress'), "auto"); ?>>Auto</option>
                          <option value="HTTP_CLIENT_IP" <?php selected(get_option('ss_ipaddress'), "HTTP_CLIENT_IP"); ?>>HTTP_CLIENT_IP</option>
                          <option value="HTTP_FORWARDED_FOR" <?php selected(get_option('ss_ipaddress'), "HTTP_FORWARDED_FOR"); ?>>HTTP_FORWARDED_FOR</option>
                          <option value="HTTP_VIA" <?php selected(get_option('ss_ipaddress'), "HTTP_VIA"); ?>>HTTP_VIA</option>
                          <option value="HTTP_X_CLUSTER_CLIENT_IP" <?php selected(get_option('ss_ipaddress'), "HTTP_X_CLUSTER_CLIENT_IP"); ?>>HTTP_X_CLUSTER_CLIENT_IP</option>
                          <option value="HTTP_X_FORWARDED" <?php selected(get_option('ss_ipaddress'), "HTTP_X_FORWARDED"); ?>>HTTP_X_FORWARDED</option>
                          <option value="HTTP_X_FORWARDED_FOR" <?php selected(get_option('ss_ipaddress'), "HTTP_X_FORWARDED_FOR"); ?>>HTTP_X_FORWARDED_FOR</option>
                          <option value="Proxy-Client-IP" <?php selected(get_option('ss_ipaddress'), "Proxy-Client-IP"); ?>>Proxy-Client-IP</option>
                          <option value="REMOTE_ADDR" <?php selected(get_option('ss_ipaddress'), "REMOTE_ADDR"); ?>>REMOTE_ADDR</option>
                          <option value="True-Client-IP" <?php selected(get_option('ss_ipaddress'), "True-Client-IP"); ?>>True-Client-IP</option>
                          <option value="WL-Proxy-Client-IP" <?php selected(get_option('ss_ipaddress'), "WL-Proxy-Client-IP"); ?>>WL-Proxy-Client-IP</option>
                          <option value="X-Forwarded-For" <?php selected(get_option('ss_ipaddress'), "X-Forwarded-For"); ?>>X-Forwarded-For</option>
                          <option value="x-real-ip" <?php selected(get_option('ss_ipaddress'), "x-real-ip"); ?>>x-real-ip</option>
                          <option value="X-True-Client-IP" <?php selected(get_option('ss_ipaddress'), "X-True-Client-IP"); ?>>X-True-Client-IP</option>
                       </select><span class="tooltiptext">This is set to the HTTP header which indicates the IP address of the user. The default field value is “auto”.If your servers are behind a firewall or an overlay network you might need to change the value to “X-Forwarded-For”. Also, you can select the right one from the drop down which is the right header as per your environment or you can add a new one.</span></span>
                    </td>
                    <br>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Session ID</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_sessid" value="<?php echo esc_attr( get_option('ss_sessid', 'PHPSESSID')); ?>" /><span class="tooltiptext">Assign the variable which contains the cookie being used to track the user session to Session ID parameter</span></span><br>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Advanced Bot Detection</th>
                    <td><span class="tooltip"><label>
                       <input type="radio"  name="ss_other_headers" value="true" <?php if( get_option('ss_other_headers') === "true") { echo "checked='checked'";} ?>/>Yes
                       </label>
                       <label><input type="radio"  name="ss_other_headers" value="false" <?php if ( ( get_option('ss_other_headers') == "") || ( get_option('ss_other_headers') === "false") ) { echo " checked='checked'";} ?> />No</label><span class="tooltiptext">This field is enabled to collect all the available headers in the API Call to ensure the bot detection is expedited and more effective.</span></span><br>
                    </td>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Deployment Number</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_idn" value="<?php echo esc_attr( get_option('ss_idn', '1234') ); ?>" /><span class="tooltiptext">Deployment Number</span></span><br>
                    </td>
                 </tr>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Valid IP Position</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_ip_index" value="<?php echo esc_attr( get_option('ss_ip_index', 1) ); ?>" /><span class="tooltiptext">This refers to the position of the Valid IP in the Header. When the header has multiple IPs, it is mandatory to update this field with the right index position of the valid IP.</span></span><br>
                    </td>
                 </tr>
                 </tr>
                 <tr valign="top">
                    <th scope="row">Enable Logs</th>
                    <td><span class="tooltip"><label>
                       <input type="radio" name="ss_enable_log" value="true" <?php if( get_option('ss_enable_log') === "true") { echo "checked='checked'";} ?>/>Yes
                       </label>
                       <label><input type="radio"  name="ss_enable_log" value="false" <?php if ( ( get_option('ss_enable_log') == "") || ( get_option('ss_enable_log') === "false") ) { echo " checked='checked'";} ?> />No</label><span class="tooltiptext">Enable ShieldSquare Logs for any debugging purpose</span></span><br>
                    </td>
                 </tr>
                 <tr valign="top" class="cb_link">
                    <th scope="row">ShieldSquare CAPTCHA</th>
                    <td><span class="tooltip"><label>
                       <input type="radio"  name="ss_enable_captcha" value="true" id="ss_cp_t" <?php if((get_option('ss_enable_captcha') == "") || (get_option('ss_enable_captcha') === "true")) { echo "checked='checked'";} ?> />Yes
                       </label>
                       <label><input type="radio"  name="ss_enable_captcha" value="false" id="ss_cp_f"<?php if ( get_option('ss_enable_captcha') === "false") { echo " checked='checked'";} ?> />No</label><span class="tooltiptext">Enable this if you want to use ShieldSquare's CAPTCHA page for Active Mode </span></span><br>
                    </td>
                 </tr>
                 <tr valign="top" class="cb_link">
                    <th scope="row">ShieldSquare Block</th>
                    <td><span class="tooltip"><label>
                       <input type="radio"  name="ss_enable_block" value="true" id="ss_bl_t" <?php if((get_option('ss_enable_block') == "") || (get_option('ss_enable_block') === "true")) { echo "checked='checked'";} ?> />Yes
                       </label>
                       <label><input type="radio"  name="ss_enable_block" value="false" id="ss_bl_t"<?php if ( get_option('ss_enable_block') === "false") { echo " checked='checked'";} ?> />No</label><span class="tooltiptext">Enable this if you want to use ShieldSquare's CAPTCHA page for Active Mode </span></span><br>
                    </td>
                 </tr>
                 <tr valign="top" class="cb_link">
                    <th scope="row">Redirect Domain</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_redirect_domain" id="ss_redirect_domain" value="<?php echo esc_attr( get_option('ss_redirect_domain', 'validate.perfdrive.com') ); ?>" /><span class="tooltiptext">Redirect Domain</span></span><br>
                    </td>
                 </tr>
                 <tr valign="top" class="cb_link">
                    <th scope="row">Support Email</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_support_email" value="<?php echo esc_attr( get_option('ss_support_email', 'support@shieldsquare.com') ); ?>" /><span class="tooltiptext">Provided support e-mail ID will be used in ShieldSquare CAPTCHA/Block page and it can be used later to revert a mail for any issues with CAPTCHA/Block page.

Please contact ShieldSquare support team if you want to configure your support email.</span></span><br>
                    </td>
                 </tr>
                 <tr valign="top" id="ss_content_list">
                    <th scope="row">Request Filter List</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_content_list" value="<?php echo esc_attr( get_option('ss_content_list', 'png|jpg|css|js|jpeg|gif|ico|ttf') ); ?>" /><span class="tooltiptext">Append the static resource extensions that have to be filtered to avoid API calls to ShieldSquare server. Separate each resource extension with “|” operator. All the content types added to this list are not processed by ShieldSquare module.</span></span><br>
                    </td>
                 </tr>
                 <tr valign="top" id="ss_skip_url_list">
                    <th scope="row">Skip URL List</th>
                    <td><span class="tooltip"><input type="text" class="regular-text" name="ss_skip_url_list" value="<?php echo esc_attr( get_option('ss_skip_url_list', '') ); ?>" /><span class="tooltiptext">Add URLs to be whitelisted. Ex: "url-1, url-2, url-3"</span></span><br>
                    </td>
                 </tr>
              </table>
              <?php submit_button(); ?>
           </form>
        </div>
        <script type="text/javascript">
            showHideCaptchaConfig();

            function showHideCaptchaConfig(){
                var style = (document.getElementById('active').checked) ? '' : 'none';
                var elements = document.getElementsByClassName('cb_link');
                for (var i=0; i<elements.length; i+=1){
                    elements[i].style.display = style;
                }
            }
        </script>
        <?php

    }//end ss_settings_form

}

$shieldsquare = new ShieldSquare();
