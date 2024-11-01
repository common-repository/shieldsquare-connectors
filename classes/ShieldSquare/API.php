<?php
/**
 * Description of what this module (or file) is doing.
 *
 * Plugin Name: Connectors
 * Description: Shieldsquare Connectors
 * Version: 1.0.0
 *
 * @author  Narasimha Reddy <narasimha.m@shieldsquare.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class SS_API_response
{
    public $errorMessage = "";
    public $responsecode = 0;
}//end class


class SS_Response
{
    public $pid    = "";
    public $url    = "";
    public $reason = "";
    public $responsecode;
}//end class


class SS_Response_Codes
{
    public $ALLOW     = 0;
    public $MONITOR   = 1;
    public $CAPTCHA   = 2;
    public $BLOCK     = 3;
    public $FFD       = 4;
    public $ALLOW_EXP = -1;
}//end class


class SS_Valid_IP_Ranges
{
    public $minIP1 = '10.0.0.0';
    public $maxIP1 = '10.255.255.255';
    public $minIP2 = '172.16.0.0';
    public $maxIP2 = '172.31.255.255';
    public $minIP3 = '192.168.0.0';
    public $maxIP3 = '192.168.255.255';
    public $minIP4 = '127.0.0.0';
    public $maxIP4 = '127.255.255.255';
    public $minIP5 = '198.18.0.0';
    public $maxIP5 = '198.19.255.255';
    public $minIP6 = '100.64.0.0';
    public $maxIP6 = '100.127.255.255';
    public $minIP7 = '192.0.0.0';
    public $maxIP7 = '192.0.0.255';
}//end class

/**
 * Shielsquare_Query_Constants : contains strings which are used to create query string for
 * shieldsquare CAPTCHA and block redirect URL
 *
 * @category  Client
 * @package   ss2
 * @version   Release: <version 4.8>
 * @copyright 2017 ShieldSquare
 * @author    ShieldSquare
 */
class Shielsquare_Query_Constants
{
    public $digits      = '0123456789';
    public $charDigits  = '0123456789abcdef';
    public $charDigits1 = '0123456abcdefghkizlmp';
    public $charString  = 'abcdefghijk@lmnop';
    public $charDigits2 = 'pqrstuv234219993232@lmnop';
    /**
     * alternateUserAgent : contains alternate user agent string
     *
     * @var    array
     * @access public
     */
    public $alternateUserAgent = array(
        "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
        "Mozilla/4.0 (Windows NT 5.1) AppleWebKit/535.7 (KHTML,like zeco) Chrome/33.0.1750.154 Safari/536.7",
        "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1) Gecko/20100101 Firefox/39.0",
        "Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)",
        "Chrome/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7A341 Safari/528.16"
        );
}


/**
  * Ss2Config Class Doc Comment
  *
  * @category Class
  * @package  WordPress
  * @author   ShieldSquare
  * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
  * @link     https://www.shieldsquare.com/
  */
class ShieldSquare_API
{
    /**
     * The method that actually performs the latency benchmark tests
     *
     * @param string $username username for the traffic
     * @param int    $calltype type of the traffic
     *
     * @return object
     */
    public static $ipHeaders = array(
        'i0' => 'REMOTE_ADDR',
        'i1' => 'X-Forwarded-For',
        'i2' => 'HTTP_CLIENT_IP',
        'i3' => 'HTTP_X_FORWARDED_FOR',
        'i4' => 'x-real-ip',
        'i5' => 'HTTP_X_FORWARDED',
        'i6' => 'Proxy-Client-IP',
        'i7' => 'WL-Proxy-Client-IP',
        'i8' => 'True-Client-IP',
        'i9' => 'HTTP_X_CLUSTER_CLIENT_IP',
        'i10' => 'HTTP_FORWARDED_FOR',
        'i11' => 'HTTP_FORWARDED',
        'i12' => 'HTTP_VIA',
        'i13' => 'X-True-Client-IP',
        );

    public function validate_request($username, $calltype)
    {
        $requestTime    = time();
        $ssPacket       = array();
        $ssResponseCode = new SS_Response_Codes();
        $ssResponse     = new SS_Response();
        $configuration  = new ShieldSquare_Config();
        $configData     = $configuration->ss_get_config();
        $apiResponse    = new SS_API_response();
        $serviceUrl     = $this->ss_get_service_url($configData);
        $host           = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $path           = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $requestUrl     = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$host.$path;

        if (ShieldSquare_Utils::skip_request($requestUrl, $configData) === true) {
            $ssResponse->responsecode = 0;
            $ssResponse->reason = 'Request is not processed by ShieldSquare';
            return $ssResponse;
        }

        $pid = $this->ss_generate_pid($configData->sid);
        if ($pid === '') {
            ShieldSquare_Utils::logging("SID is not configured properly: ".$configData->sid, 'error');
            $ssResponse->responsecode = 0;
            $ssResponse->reason = 'Invalid SID';
            return $ssResponse;
        }
        if ($configData->enabledLog === true) {
            ShieldSquare_Utils::logging("Configurations: [ ".var_export($configData, true)." ]", 'info');
        }
        $this->ss_validate_cookies($ssPacket);
        $ssPacket['_zpsbd0'] = $configData->mode;
        $ssPacket['_zpsbd1'] = strtolower($configData->sid);
        $ssPacket['_zpsbd2'] = $pid;
        $ssPacket['_zpsbd3'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $ssPacket['_zpsbd4'] = $requestUrl;
        $ssPacket['_zpsbd5'] = isset($_COOKIE[$configData->sessId]) ? $_COOKIE[$configData->sessId] : '';

        if (($configData->ipHeader !== 'auto') && isset($_SERVER[$configData->ipHeader])) {
            $ssPacket['_zpsbd6'] = $_SERVER[$configData->ipHeader];
        } else {
            $ssPacket['_zpsbd6'] = $_SERVER['REMOTE_ADDR'];
        }
        $ssPacket['iSplitIP']  = $this->ss_get_valid_ip($ssPacket['_zpsbd6'], $configData->ipIndex);
        $ssPacket['_zpsbd7']   = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $ssPacket['_zpsbd8']   = $calltype;
        $ssPacket['_zpsbd9']   = $username;
        $ssPacket['_zpsbda']   = $requestTime;
        $ssPacket['_zpsbdp']   = (int) (isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : 70000 );
        $ssPacket['_zpsbdm']   = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        $ssPacket['_zpsbdxrw'] = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : '';

        if (function_exists('apache_request_headers')) {
            foreach (getallheaders() as $key => $value) {
                if (strtolower($key) == "proxy-authorization") {
                    if (isset($value)) {
                        $ssPacket['_zpsbdpa'] = $value;
                    }
                }
            }
        } else if (isset($_SERVER['HTTP_PROXY_AUTHORIZATION'])) {
            $ssPacket['_zpsbdpa'] = $_SERVER['HTTP_PROXY_AUTHORIZATION'];
        }

        $ssPacket['_zpsbdt'] = 'wordpress';
        if ($configData->betaHeaders === true) {
            $ssPacket['_zpsbdx'] = array();
            foreach ($_SERVER as $headerName => $headerValue) {
                $ssPacket['_zpsbdx'][$headerName] = (isset($headerValue) ? $headerValue : '');
            }
        }

        $ssPacket['idn'] = $configData->idn;
        $this->ss_get_ip_headers($ssPacket);

        $jsonData        = json_encode($ssPacket);
        if ($configData->enabledLog === true) {
            ShieldSquare_Utils::logging("API packet: [ ".var_export($jsonData, true)." ]", 'info');
        }
        $ssResponse->pid = $pid;
        $ssResponse->url = $configData->jsUrl;

        if ($configData->mode && ($calltype !== 4 && $calltype !== 5)) {
            $apiResponse = $this->ss_sync_request($serviceUrl, $jsonData, $configData->timeoutValue, $configData->enabledSSL);
            if ($apiResponse->responsecode === false) {
                $ssResponse->responsecode = $ssResponseCode->ALLOW_EXP;
                $ssResponse->reason       = $apiResponse->errorMessage;
                $ssResponse->dynamic_JS   = "var __uzdbm_c = 2+2";
            } else {
                $ResponseFromEndpoint   = json_decode($apiResponse->responsecode);
                $ssResponse->dynamic_JS = $ResponseFromEndpoint->dynamic_JS;
                switch (intval($ResponseFromEndpoint->ssresp)) {
                case 0:
                    $ssResponse->responsecode = $ssResponseCode->ALLOW;
                    break;
                case 1:
                    $ssResponse->responsecode = $ssResponseCode->MONITOR;
                    break;
                case 2:
                    $ssResponse->responsecode = $ssResponseCode->CAPTCHA;
                    break;
                case 3:
                    $ssResponse->responsecode = $ssResponseCode->BLOCK;
                    break;
                case 4:
                    $ssResponse->responsecode = $ssResponseCode->FFD;
                    break;
                default:
                    $ssResponse->responsecode = $ssResponseCode->ALLOW_EXP;
                    $ssResponse->reason       = $apiResponse->errorMessage;
                    break;
                }//end switch
            }//end if
        } else {
            if ($configData->asyncHttpPost === true) {
                $error_code = $this->ss_async_request($serviceUrl, $jsonData);
                if (!$error_code)
                $ssResponse->responsecode      = $ssResponseCode->ALLOW_EXP;
                else $ssResponse->responsecode = $ssResponseCode->ALLOW;
                $ssResponse->dynamic_JS        = "var __uzdbm_c = 2+2";
            } else {
                $apiResponse = $this->ss_sync_request($serviceUrl, $jsonData, $configData->timeoutValue);
                if ($apiResponse->responsecode === false) {
                    $ssResponse->responsecode = $ssResponseCode->ALLOW_EXP;
                    $ssResponse->reason       = $apiResponse->errorMessage;
                    $ssResponse->dynamic_JS   = "var __uzdbm_c = 2+2";
                } else {
                    $ssResponse->responsecode = $ssResponseCode->ALLOW;
                    $ResponseFromEndpoint     = json_decode($apiResponse->responsecode);
                    $ssResponse->dynamic_JS   = $ResponseFromEndpoint->dynamic_JS;
                }
            }//end if
        }//end if

        $respCode = $ssResponse->responsecode;
        header('ShieldSquare-Response: '.$respCode);    /* setting shieldsquare response*/
        $responsePage = '';

        if ($respCode === $ssResponseCode->CAPTCHA) {
            $responsePage = $configData->enabledCaptcha ? '/captcha' : '';
        } else if ($respCode === $ssResponseCode->BLOCK) {
            $responsePage = $configData->enabledBlock ? '/block' : '';
        }

        if ($responsePage !== '' && $configData->redirectDomain !== '') {
            $redirectURL = $this->ss_get_redirectional_url($ssPacket, $configData, $responsePage);
            if ($redirectURL !== '') {
                header('Location:'.$redirectURL);
                exit();
            }
        }
        return $ssResponse;

    }//end validate_request()

    /**
     * Custom wrapper for the get_option function
     *
     * @param object $ssConfig shieldsquare configuration settings
     *
     * @return string service url
     */
    function ss_get_service_url(&$ssConfig)
    {
        $schema = ($ssConfig->enabledSSL) ? "https://" : "http://";
        $ipAddress = "";

        if($ssConfig->domainTtl === -1){
            $ipAddress = $ssConfig->ss2Domain;
        } else {
            if(($prevTime = get_option("ss_dns_time")) !== false){
                if((time() - $prevTime) > $ssConfig->domainTtl) {
                    $ipAddress = $this->ss_get_ipaddress($ssConfig->ss2Domain);
                }
                $ipAddress = get_option("ss_ip");
            } else {
                $ipAddress = $this->ss_get_ipaddress($ssConfig->ss2Domain);
            }
        }

        $url = $schema.(($ipAddress !== "") ? $ipAddress : $ssConfig->ss2Domain).'/getRequestData';
        if ($ssConfig->enabledLog === true) {
            ShieldSquare_Utils::logging("URL: [ ".$url." ]", 'info');
        }
        return $url;

    }//end ss_get_service_url()

    /**
     * Get the ip address from hostname
     *
     * @param string $host  hostname
     *
     * @return string
     */
    function ss_get_ipaddress($host)
    {
        $ipAddress = gethostbyname($host);
        update_option("ss_ip", $ipAddress);
        update_option("ss_dns_time", time());
        return $ipAddress;
    }

    /**
     * Custom wrapper for the get_option function
     *
     * @param string $url     url to be sent to shieldsquare
     * @param string $payload Sending data to shieldsquare end-point
     *
     * @return mixed
     */
    function ss_async_request($url, $payload)
    {
        $cmd  = "curl -X POST -H 'Content-Type: application/json' --connect-timeout 1 -m 1";
        $cmd .= " -d '".urlencode($payload)."' "."'".$url."'";
        $cmd .= " > /dev/null 2>&1 &";
        exec($cmd, $output, $exit);
        return $exit === 0;

    }//end ss_async_request()


    /**
     * Custom wrapper for the get_option function
     *
     * @param string $url       url to be sent to shieldsquare
     * @param string $params    parameters
     * @param string $timeout   timeout value
     * @param string $timeoutMS timeout in milliseconds
     * @param string $enabledSSL enable endpoint ssl
     *
     * @return mixed
     */
    function ss_sync_request($url, $params, $timeout, $enabledSSL)
    {
        $curl         = curl_init($url);
        $postData     = urlencode($params);
        $apiResponse  = new SS_API_response();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length:'.strlen($postData)));
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeout);

        $apiResponse->responsecode = curl_exec($curl);
        $apiResponse->errorMessage = curl_error($curl);
        curl_close($curl);
        return $apiResponse;

    }//end ss_sync_request()


    /**
     * Custom wrapper for the get_option function
     *
     * @param string $sid subscriber id
     *
     * @return string returns pid
     */
    function ss_generate_pid($sid)
    {
        $t = explode(" ", microtime());
        $ls = explode("-", $sid);
        if(count($ls) == 5 && $ls[3] !== 'xxxx') {
            $minSid = hexdec($ls[3]);
        } else {
            return '';
        }

        return strtolower(
            sprintf(
                '%04x%04x-%04x-%04s-%04s-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                $minSid,
                substr("00000000".dechex($t[1]), -4),
                substr("0000".dechex(round($t[0] * 65536)), -4),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            )
        );

    }//end ss_generate_pid()


    /**
     * Custom wrapper for the get_option function
     *
     * @param string $ssPacket shieldsquare api packet
     *
     * @return none
     */
    function ss_validate_cookies(&$ssPacket)
    {
        $a           = 1;
        $b           = 3;
        $c           = 7;
        $d           = 1;
        $e           = 5;
        $f           = 10;
        $low         = 10000;
        $high        = 99999;
        $currentTime = time();
        $ssCookieExp = ($currentTime + 3600 * 24 * 365 * 10);

        if (isset($_COOKIE["__uzma"]) && isset($_COOKIE["__uzmb"])
            && isset($_COOKIE["__uzmc"]) && isset($_COOKIE["__uzmd"])
        ) {
            $uzma           = isset($_COOKIE["__uzma"]) ? $_COOKIE["__uzma"] : "";
            $uzmb           = isset($_COOKIE["__uzmb"]) ? $_COOKIE["__uzmb"] : 0;
            $uzmc           = isset($_COOKIE["__uzmc"]) ? $_COOKIE["__uzmc"] : 0;
            $lastaccesstime = isset($_COOKIE["__uzmd"]) ? $_COOKIE["__uzmd"] : 0;
            $uzmcCounter    = substr($uzmc, $e, (strlen($uzmc) - $f));
            $counter        = (((int) $uzmcCounter - $c) / $b + $d);
            $cookieTampered = false;
            if (!ctype_digit((string) $uzmc)
                || !ctype_digit((string) $uzmb)
                || strlen($uzmc) < 12 || strlen($uzmb) != 10
                || $counter < 1
                || ($counter != floor($counter))
                || !ctype_digit((string) $lastaccesstime)
                || empty($uzma)
            ) {
                $cookieTampered = true;
                $counter        = 1;
                $uzma           = uniqid('', true);
                $uzmb           = (string) $currentTime;
            }

            $uzmc = (string) mt_rand($low, $high).(string) ($c + $counter * $b).(string) mt_rand($low, $high);

            if ($cookieTampered) {
                setcookie("__uzma", $uzma, $ssCookieExp, '/', "");
                setcookie("__uzmb", $uzmb, $ssCookieExp, '/', "");
            }
        } else {
            $uzma           = uniqid('', true);
            $uzmb           = (string) $currentTime;
            $uzmc           = (string) mt_rand($low, $high).(string) ($c + $a * $b).(string) mt_rand($low, $high);
            $lastaccesstime = $uzmb;

            setcookie("__uzma", $uzma, $ssCookieExp, '/', "");
            setcookie("__uzmb", $currentTime, $ssCookieExp, '/', "");
        }//end if

        setcookie("__uzmc", $uzmc, $ssCookieExp, '/', "");
        setcookie("__uzmd", $currentTime, $ssCookieExp, '/', "");

        $ssPacket['__uzma'] = $uzma;
        $ssPacket['__uzmb'] = $uzmb;
        $ssPacket['__uzmc'] = $uzmc;
        $ssPacket['__uzmd'] = $lastaccesstime;

    }//end ss_validate_cookies()


    /**
     * Custom wrapper for the get_option function
     *
     * @param string $ssPacket shieldsquare api packet
     *
     * @return none
     */
    function ss_get_ip_headers(&$ssPacket)
    {
        foreach (self::$ipHeaders as $key => $value) {
            if(isset($_SERVER[$value]) && $_SERVER[$value] !== ''){
                $ssPacket[$key] = $_SERVER[$value];
                if($value === 'HTTP_X_FORWARDED_FOR') {
                    $ipSplits = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                    if ($ipSplits !== null) {
                        foreach ($ipSplits as $ip) {
                            $ip = trim($ip, "^ ");
                            if ($this->ss_is_valid_ip($ip)) {
                                $cntColon = substr_count($ip, ':');
                                if ($cntColon == 1) {
                                    $ip = $this->ss_ip_without_port($ip);
                                }
                                $ssPacket['ixff'] = $ip;
                                break;
                            }
                        }
                    }
                }
            }
        }

    }//end ss_get_ip_headers()


    /**
     * Custom wrapper for the get_option function
     *
     * @param string $ipToValidate  ip2long
     * @param string $ip2LongValues ip2long values
     *
     * @return boolean
     */
    function ss_check_ip_range($ipToValidateLong, $i, $ip2LongValues)
    {
        if (ceil((count($ip2LongValues) / 2) + 1) == $i) {
            return false;
        } else {
            $isValid = false;
            if (isset($ip2LongValues['minIP'.$i]) && isset($ip2LongValues['maxIP'.$i])) {
                $isValid = (($ipToValidateLong >= $ip2LongValues['minIP'.$i])
                            && ($ipToValidateLong <= $ip2LongValues['maxIP'.$i]));
            }
            return $isValid || $this->ss_check_ip_range($ipToValidateLong, ($i + 1), $ip2LongValues);
        }
    }//end ss_check_ip_range()


    /**
     * Custom wrapper for the get_option function
     *
     * @return string returns ip2long values
     */
    function ss_set_ip2long_values()
    {
        $ip2LongValues = array();
        $validIPRanges = new SS_Valid_IP_Ranges();
        foreach ($validIPRanges as $key => $value) {
            $ip2LongValues[$key] = ip2long($value);
        }

        return $ip2LongValues;

    }//end ss_set_ip2long_values()


    /**
     * Custom wrapper for the get_option function
     *
     * @param string $ipToValidate ip address to validate
     *
     * @return string returns ip address
     */
    function ss_ip_without_port($ipToValidate)
    {
        $ipWithoutPort = explode(':', $ipToValidate);
        return $ipWithoutPort[0];

    }//end ss_ip_without_port()


    /**
     * Custom wrapper for the get_option function
     *
     * @param string $zpsbd6  ip addresss
     * @param int    $ipIndex ip address index
     *
     * @return none
     */
    function ss_get_valid_ip($zpsbd6, $ipIndex)
    {
        $splitIp = explode(",", $zpsbd6);
        $validIP = "";

        if ($ipIndex >= 0) {
            // For positive index
            if ($ipIndex === 0) {
                $startIndex = 0;
            } else {
                $startIndex = ($ipIndex - 1);
            }

            for ($i = $startIndex; $i < count($splitIp); $i++) {
                $ip2check = trim($splitIp[$i], "^ ");
                if ($this->ss_is_valid_ip($ip2check)) {
                    $validIP = $ip2check;
                    break;
                }
            }
        } else {
            // For negative index
            for ($i = (count($splitIp) + $ipIndex); $i >= 0 ; $i--) {
                $ip2check = trim($splitIp[$i], "^ ");
                if ($this->ss_is_valid_ip($ip2check)) {
                    $validIP = $ip2check;
                    break;
                }
            }
        }//end if

        $cntColon = substr_count($validIP, ':');
        if ($cntColon === 1) {
            $validIP = $this->ss_ip_without_port($validIP);
        }

        return $validIP;

    }//end ss_get_valid_ip()

    /**
     * Custom wrapper for the get_option function
     *
     * @param string $ipAddress ip address to be validated
     *
     * @return boolean
     */
    function ss_is_valid_ip($ipAddress)
    {
        if (isset($ipAddress)) {
            $countColon  = substr_count($ipAddress, ':');
            $countPeriod = substr_count($ipAddress, '.');
            if (!($countColon > 1 || $countPeriod == 3)) {
                // Check for Undefind (Validation #1).
                return false;
            }

            if ($countColon > 1) {
                if (($ipAddress === '::1') || ($ipAddress === '0:0:0:0:0:0:0:0')
                    || ($ipAddress === '::') || ($ipAddress === '::/128')
                    || ($ipAddress === '0:0:0:0:0:0:0:1') || ($ipAddress === '::1/128')
                ) {
                    return false;
                } else if (preg_match('/^fd/', $ipAddress) === 1) {
                    // Check if value starts with fd (Validation #2).
                    return false;
                }
            } else if ($countPeriod == 3) {
                if ($countColon == 1) {
                    $ipAddress = $this->ss_ip_without_port($ipAddress);
                }

                $ip2long       = ip2long($ipAddress);
                $ip2LongValues = $this->ss_set_ip2long_values();
                if ($this->ss_check_ip_range($ip2long, 1, $ip2LongValues)) {
                    return false;
                }
            }//end if
        } else {
            return false;
        }//end if
        return true;

    }//end ss_is_valid_ip()

    function ss_get_redirectional_url($ssPacket, $configData, $responsePage)
    {
        $scheme = $configData->enabledSSL ? 'https://' : 'http://';
        $redirectURL = $scheme.$configData->redirectDomain.$responsePage;
        $custRespPage = preg_match('~validate.perfdrive.com~', $configData->redirectDomain) ? false : true;

        if ($custRespPage === true) {
            $ssa =  urlencode($ssPacket['_zpsbd4']);
            $ssb = hash('sha1', $ssPacket['_zpsbd1'].$ssPacket['_zpsbd4']);
            return $redirectURL."?ssa=$ssa&ssb=$ssb";
        }

        $redirectArr = array();
        $constants   = new Shielsquare_Query_Constants();
        $uzmc_sequence = substr($ssPacket['__uzmc'], 5, -5);
        $redirectArr['ssa'] = $this->ss_generate_pid($configData->sid);
        $redirectArr['ssb'] = $this->ss_gen_string(25, $constants->charDigits1);
        $redirectArr['ssc'] = urlencode($ssPacket['_zpsbd4']);
        $redirectArr['ssd'] = $this->ss_gen_string(15, $constants->digits);
        $redirectArr['sse'] = $this->ss_gen_string(15, $constants->charString);
        $redirectArr['ssf'] = $this->ss_gen_string(40, $constants->charDigits);
        $redirectArr['ssg'] = $this->ss_generate_alternate_pid();
        $redirectArr['ssh'] = $this->ss_generate_alternate_pid();
        $redirectArr['ssi'] = $ssPacket['_zpsbd2'];
        $redirectArr['ssj'] = $this->ss_generate_alternate_pid();
        $redirectArr['ssk'] = $configData->supportEmail;
        $redirectArr['ssl'] = $this->ss_gen_string(12, $constants->digits);
        $redirectArr['ssm'] = $this->ss_gen_string(17, $constants->digits).(string)$uzmc_sequence
                              .$this->ss_gen_string(13, $constants->digits);
        $inputDigest =  $ssPacket['_zpsbd1'].$ssPacket['_zpsbd5']
                        .urldecode($ssPacket['_zpsbd4']).(string)$uzmc_sequence
                        .$ssPacket['_zpsbd2'].$ssPacket['_zpsbd7']
                        .$configData->supportEmail.(isset($ssPacket['iSplitIP']) ? $ssPacket['iSplitIP'] : $ssPacket['_zpsbd6']);

        $digest = hash('sha1', $inputDigest);
        if (strlen($ssPacket['__uzma']) <= 20) {
            $first_part_uzma  = $ssPacket['__uzma'];
            $second_part_uzma = "";
        } else {
            $first_part_uzma  = substr($ssPacket['__uzma'], 0, 20);
            $second_part_uzma = substr($ssPacket['__uzma'], 20-strlen($ssPacket['__uzma']));
        }

        $redirectArr['ssn'] = $this->ss_gen_string(8, $constants->charDigits).substr($digest, 0, 20)
                              .$this->ss_gen_string(8, $constants->charDigits).$first_part_uzma
                              .$this->ss_gen_string(5, $constants->charDigits);
        $redirectArr['sso'] = $this->ss_gen_string(5, $constants->charDigits).$second_part_uzma
                              .$this->ss_gen_string(8, $constants->charDigits).substr($digest, -20)
                              .$this->ss_gen_string(8, $constants->charDigits);
        $redirectArr['ssp'] = $this->ss_gen_string(10, $constants->digits).substr($ssPacket['__uzmb'], 0, 5)
                              .$this->ss_gen_string(5, $constants->digits).substr($ssPacket['__uzmd'], 0, 5)
                              .$this->ss_gen_string(10, $constants->digits);
        $redirectArr['ssq'] = $this->ss_gen_string(7, $constants->digits).substr($ssPacket['__uzmd'], -5)
                              .$this->ss_gen_string(9, $constants->digits).substr($ssPacket['__uzmb'], -5)
                              .$this->ss_gen_string(15, $constants->digits);
        $redirectArr['ssr'] = base64_encode(isset($ssPacket['iSplitIP']) ? $ssPacket['iSplitIP'] : $ssPacket['_zpsbd6']);
        $alternate_ua = $constants->alternateUserAgent;
        $redirectArr['sss'] = $alternate_ua[array_rand($alternate_ua)];
        $redirectArr['sst'] = $ssPacket['_zpsbd7'];
        $redirectArr['ssu'] = $alternate_ua[array_rand($alternate_ua)];
        $redirectArr['ssv'] = $this->ss_gen_string(15, $constants->charDigits2);
        $redirectArr['ssw'] = $ssPacket['_zpsbd5'];
        $redirectArr['ssx'] = $this->ss_gen_string(15, $constants->digits);
        $redirectArr['ssy'] = $this->ss_gen_string(40, $constants->charString);
        $redirectArr['ssz'] = $this->ss_gen_string(15, $constants->charDigits);

        return add_query_arg($redirectArr, $redirectURL);
    }

    /**
     * This method generates string of set length from given characters.
     *
     * @param int    $length     : length of generated string
     * @param string $characters : used to create string
     *
     * @access public
     * @return string
     */
    function ss_gen_string($length, $characters)
    {
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $string;
    }

    /**
     * This method generates alterate PID.
     *
     * @access public
     * @return string
     */
    function ss_generate_alternate_pid()
    {
        $t = explode(" ", microtime());
        return sprintf(
            '%04x%04x-%04x-%04s-%04s-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            substr("00000000" . dechex($t[1]), -4),
            substr("0000" . dechex(round($t[0] * 65536)), -4),
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

}
