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
class ShieldSquare_Utils
{
    /**
     * The google_captcha_script registers the script
     *
     * @param string $msg  log message
     * @param string $type log message type
     *
     * @return none
     */
    public static function logging($msg, $type='Debug')
    {
        $date = gmdate('d.m.Y h:i:s');
        $log  = "[".$date."] [client ".$_SERVER['REMOTE_ADDR']."] [ShieldSquare:".$type."] ".$msg."\n";
        error_log($log, 3, '/tmp/shieldsquare.log');

    }//end logging()

    public static function skip_request($reqUrl, $configData)
    {
        /* Verify request is filter */
        if ($configData->contentList !== '' && (preg_match("/.(".$configData->contentList.")$/", $reqUrl))) {
            if ($configData->enabledLog === true) {
                ShieldSquare_Utils::logging("Request not processed as extension added in request list: [$reqUrl]", 'info');
            }
            return true;
        }

        /* Verify request is filter from escape list */
        if ($configData->skipUrlList !== '') {
            $skipUrlList = explode(',', $configData->skipUrlList);
            if (!empty($skipUrlList)) {
                foreach ($skipUrlList as $pattern) {
                    if (preg_match("~".trim($pattern)."~", $reqUrl)) {
                        if ($configData->enabledLog === true) {
                            ShieldSquare_Utils::logging("Request not processed as it added in skip list: [$reqUrl]", 'info');
                        }
                        return true;
                    }
                }
            }
        }
        return false;
    }

}
