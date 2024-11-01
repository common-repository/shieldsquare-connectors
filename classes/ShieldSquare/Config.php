<?php
/**
 * Description of what this module (or file) is doing.
 *
 * @author  Narasimha Reddy <narasimha.m@shieldsquare.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */


 /**
  * Ss2Config Class Doc Comment
  *
  * @category Class
  * @package  WordPress
  * @author   ShieldSquare
  * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
  * @link     https://www.shieldsquare.com/
  */
class ShieldSquare_Config
{
    /**
     * The sid for uniquely identifying subscribers
     *
     * @var string $sid
     */
    public $sid = 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx';

    /**
     * The mode for setting traffic monitor or protect
     *
     * @var bool $mode
     */
    public $mode = false;

    /**
     * The asyncHttpPost for sending API packets to shieldsquare end-point in asyn mode
     *
     * @var bool $asyncHttpPost
     */
    public $asyncHttpPost = true;

    /**
     * The timeoutValue for shieldsquare end-point timeout value
     *
     * @var int $timeoutValue
     */
    public $timeoutValue;

    /**
     * The sessID for getting session id from http header parameters
     *
     * @var string $sessId
     */
    public $sessId;

    /**
     * The IP address for getting valid IP address from http header
     *
     * @var string $ipHeader
     */
    public $ipHeader;

    /**
     * The jsURL for sending js data
     *
     * @var string $jsUrl
     */
    public $jsUrl;

    /**
     * The ss2Domain for selecting near by end-point to customer server
     *
     * @var string $ss2Domain
     */
    public $ss2Domain;

    /**
     * The domain ttl for resolving ss2domain after refresh interval time expires
     *
     * @var int $domainTtl
     */
    public $domainTtl;

    /**
     * The domain cache file for saving resolved IP address
     *
     * @var string $domainCacheFile
     */
    public $domainCacheFile;

    /**
     * The beta headers for getting additional header parameters
     *
     * @var bool $betaHeaders
     */
    public $betaHeaders = false;

    /**
     * Used it for unique identification
     *
     * @var string $idn
     */
    public $idn;
    /**
     * IpIndex for finding valid IP address from the given index
     *
     * @var string $ipIndex
     */
    public $ipIndex;
    /**
     * It checks for SSL shieldsquare end-point
     *
     * @var string $enabledSSL
     */
    public $enabledSSL = false;
    /**
     * It checks log enabled
     *
     * @var bool $enabledLog
     */
    public $enabledLog = false;
    /**
     * It checks captcha enabled
     *
     * @var bool $enabledCaptcha
     */
    public $enabledCaptcha = true;
    /**
     * It checks block enabled
     *
     * @var bool $enabledBlock
     */ 
    public $enabledBlock = true;
    /**
     * It Is used for configuring customer specific domain
     *
     * @var string $redirectDomain
     */
    public $redirectDomain = 'validate.perfdrive.com';
    /**
     * Support email ID for CAPTCHA/Block pages
     *
     * @var string $supportEmail
     */
    public $supportEmail = '';
    /**
     * Set valid Header IP
     *
     * @var string $validHeader
     */
    public $validHeader = '';
    /**
     * Add the content filters in the list to skip the request
     *
     * @var string $contentList
     */
    public $contentList = '';
    /**
     * Add the URLs to skipUrlList to skip the request
     *
     * @var string $skipUrlList
     */
    public $skipUrlList = '';

    /**
     * Constructor initializes the configurations
     *
     * @return none
     */
    public function __construct()
    {
        $this->sid  = get_option('ss_sid');

        if (get_option('ss_mode') === 'Active') {
            $this->mode = true;
        }

        if (get_option('ss_async_post') === 'false') {
            $this->asyncHttpPost = false;
        }

        $this->timeoutValue = (int)get_option('ss_timeout');
        if($this->timeoutValue === 0) {
            $this->timeoutValue = 100;
        }

        $this->sessId = get_option('ss_sessid');
        // if (get_option('ss_sessid') === '')
            // $this->sessId = '';

        $this->ipHeader = get_option('ss_ipaddress');
        if ($this->ipHeader === '') {
            $this->ipHeader = 'REMOTE_ADDR';
        }

        $this->jsUrl = get_option("ss_domain").'/getData.php';

        $this->ss2Domain = get_option("ss_domain");
        if ($this->ss2Domain === '') {
            $this->ss2Domain = 'ss_sa.shieldsquare.net';
        }

        $this->domainTtl = (int) get_option("ss_dns_cache_refresh_interval");
        if ($this->domainTtl === 0) {
            $this->domainTtl = 3600;
        }

        $this->domainCacheFile = get_option("ss_dns_cache_path");
        if ($this->domainCacheFile === '') {
            $this->domainCacheFile = '/tmp/';
        }

        if (get_option("ss_other_headers") === 'true') {
            $this->betaHeaders = true;
        }

        $this->idn = get_option("ss_idn");
        if ($this->idn === '') {
            $this->idn = '1234';
        }

        $this->ipIndex = (int) get_option("ss_ip_index");
        if ($this->ipIndex === 0) {
            $this->ipIndex = 1;
        }

        if (get_option('ss_enable_endpoint_ssl') === 'true') {
            $this->enabledSSL = true;
        }

        if(get_option('ss_enable_log') === 'true') {
            $this->enabledLog = true;
        }

        if(get_option('ss_enable_captcha') === 'false') {
            $this->enabledCaptcha = false;
        }

        if(get_option('ss_enable_block') === 'false') {
            $this->enabledBlock = false;
        }

        $this->redirectDomain = get_option('ss_redirect_domain');
        if($this->redirectDomain === '') {
            $this->redirectDomain = 'validate.perfdrive.com';
        }

        $this->supportEmail = get_option('ss_support_email');
        if ($this->supportEmail === '') {
            $this->supportEmail = "support@shieldsquare.com";
        }

        if (get_option('ss_content_list') !== '') {
            $this->contentList = get_option('ss_content_list');
        }

        if (get_option('ss_skip_url_list') !== '') {
            $this->skipUrlList = get_option('ss_skip_url_list');
        }

    }//end __construct()


    /**
     * The method that actually performs the latency benchmark tests
     *
     * @return object
     */
    public function ss_get_config()
    {
        return (Object) array(
                         'sid'             => $this->sid,
                         'mode'            => $this->mode,
                         'asyncHttpPost'   => $this->asyncHttpPost,
                         'timeoutValue'    => $this->timeoutValue,
                         'sessId'          => $this->sessId,
                         'ipHeader'        => $this->ipHeader,
                         'jsUrl'           => $this->jsUrl,
                         'ss2Domain'       => $this->ss2Domain,
                         'domainTtl'       => $this->domainTtl,
                         'domainCacheFile' => $this->domainCacheFile,
                         'betaHeaders'     => $this->betaHeaders,
                         'idn'             => $this->idn,
                         'ipIndex'         => $this->ipIndex,
                         'enabledSSL'      => $this->enabledSSL,
                         'enabledLog'      => $this->enabledLog,
                         'enabledCaptcha'  => $this->enabledCaptcha,
                         'enabledBlock'    => $this->enabledBlock,
                         'redirectDomain'  => $this->redirectDomain,
                         'supportEmail'    => $this->supportEmail,
                         'contentList'     => $this->contentList,
                         'skipUrlList'     => $this->skipUrlList,
                        );

    }//end ss_get_config()


}//end class
