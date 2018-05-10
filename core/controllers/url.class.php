<?php
namespace O10n;

/**
 * URL Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Url extends Controller implements Controller_Interface
{
    private $global_cdn = null; // global CDN config

    private $root_path; // root WordPress path
    private $nowww_serverhost; // server host without www

    /**
     * Load controller
     *
     * @param  Core       $Core Core controller instance.
     * @return Controller Controller instance.
     */
    public static function &load(Core $Core)
    {
        // instantiate controller
        return parent::construct($Core, array(
            'file',
            'env',
            'options'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
    }

    /**
     * Verify protocol
     *
     * @param  string $url URL to verify.
     * @return bool   Valid true/false
     */
    final public function valid_protocol($url, $protocols = array('http:','https:'))
    {
        foreach ($protocols as $protocol) {
            if (strpos($url, $protocol) === 0) {
                return true; // valid
            }
        }
        
        return false; // invalid
    }

    /**
     * Translate protocol relative url
     *
     * @param  string $url URL to translate
     * @return string Translated url.
     */
    final public function translate_protocol($url)
    {
        if (substr($url, 0, 2) === '//') {
            return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https:' : 'http:') . $url;
        }

        return $url;
    }

    /**
     * Return hostname without www
     *
     * @param  string $hostname Hostname
     * @return string Hostname without www.
     */
    final public function nowww($hostname)
    {
        if (stripos($hostname, 'www.') === 0) {
            return substr($hostname, 4);
        }

        return $hostname;
    }

    /**
     * Remove host from URL
     *
     * @param  string $url The URL to parse.
     * @return string URL without host name.
     */
    final public function remove_host($url)
    {

        // remove host
        $slashPos = strpos($url, '//');
        if ($slashPos !== false) {
            $slashPos = strpos($url, '/', ($slashPos + 2));

            return substr($url, $slashPos);
        }

        return $url;
    }

    /**
     * Return root WordPress path
     */
    final public function root_path()
    {
        if (!$this->root_path) {
            $this->root_path = $this->remove_host($this->file->trailingslashit(home_url(), '/'));
        }

        return $this->root_path;
    }

    /**
     * Detect if URL is local file
     *
     * @param  string  $url             URL to verify
     * @param  bool    $returnPath      Return file path.
     * @param  bool    $checkFileExists Check if file exists
     * @return boolean Local URL
     */
    final public function is_local($url, $returnPath = false, $checkFileExists = true)
    {

        // nowww host
        if (!isset($this->nowww_serverhost)) {
            $this->nowww_serverhost = $this->nowww($_SERVER['HTTP_HOST']);
        }

        // http(s):// based file, match host with server host
        if (strpos($url, '://') !== false) {
            $prefix_match = substr($url, 0, 6);
            if ($prefix_match === 'https:') {
                
                // HTTPS
                $http_prefix = 'https://';
            } elseif ($prefix_match === 'http:/') {

                // HTTPS
                $http_prefix = 'http://';
            } else {

                // invalid protocol
                return false;
            }

            // parse url
            $parsed_url = parse_url($url);

            // match against server host
            if ($this->nowww($parsed_url['host']) === $this->nowww_serverhost) {

                // local file
                $url = str_ireplace($http_prefix . $parsed_url['host'], '', $url);

                // return host name
                if ($returnPath) {
                    return $url;
                }
            }
        } elseif ($returnPath) {
            return $url;
        }

        // local file
        if (strpos($url, '://') === false) {

            // remove query string
            if (strpos($url, '?') !== false) {
                $url = substr($url, 0, strpos($url, "?"));
            }

            // remove hash
            if (strpos($url, '#') !== false) {
                $url = substr($url, 0, strpos($url, "#"));
            }

            // get real path for url
            if (substr($url, 0, 1) === '/') {
                $url = substr($url, 1);
            }

            // convert to file path
            $resource_path = realpath(ABSPATH . $url);

            // detect if file is in WordPress root
            if (!$this->file->safe_path($resource_path)) {
                return false;
            }

            // check if file exists
            if ($checkFileExists && !file_exists($resource_path) && !is_dir($resource_path)) {
                return false;
            }

            return $resource_path;
        }

        // remote file
        return false;
    }

    /**
     * Apply CDN to url
     */
    final public function cdn($url, $cdnConfig = false)
    {
        // detect if local URL
        $path = $this->is_local($url, true);
        if (!$path) {
            return $url;
        }

        // setup global CDN
        if (is_null($this->global_cdn)) {
            if ($this->options->bool('cdn')) {

                // global CDN config
                $this->global_cdn = array(
                    $this->options->get('cdn.url'),
                    $this->options->get('cdn.mask')
                );

                if (!is_array($this->global_cdn) || empty($this->global_cdn)) {
                    $this->global_cdn = false;
                }
            } else {
                $this->global_cdn = false;
            }
        }

        // no CDN config
        if (!$cdnConfig && !$this->global_cdn) {
            return $url;
        }

        // use global CDN config
        if (!$cdnConfig) {
            $cdnConfig = $this->global_cdn;
        }

        // apply CDN mask
        if (isset($cdnConfig[1]) && !empty($cdnConfig[1])) {
            $masklen = strlen($cdnConfig[1]);
            if (substr($path, 0, $masklen) === $cdnConfig[1]) {
                $path = substr($path, ($masklen - 1));
            }
        }

        // add slash to path
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }

        // remove traling slashes
        return $this->file->un_trailingslashit($cdnConfig[0], '/') . $path;

        // add global CDN
        //if () {
        //    $cdnConfig[] = $this->global_cdn;
        // }
    }

    /**
     * Return current request URL
     *
     * @return string URL of requested page.
     */
    final public function request()
    {
        $ssl = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
        $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port = $_SERVER['SERVER_PORT'];
        $port = ((! $ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':'.$port;
        $host = $this->get_host() . $port;

        $url = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];

        return $url; // return URL
    }

    /**
     * Get host
     *
     * @param bool $use_forwarded_host Use forwarded host.
     */
    final private function get_host($use_forwarded_host = false)
    {
        $host = ($use_forwarded_host && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
        $host = isset($host) ? $host : $_SERVER['SERVER_NAME'];

        return $host;
    }


    /**
     * Rebase relative URI
     */
    final public function rebase($url, $base_href)
    {
        // url decode
        if (strpos($url, '%') !== false) {
            $url = urldecode($url);
        }
        
        // normalize
        if (strpos($url, '//') === 0) {
            if ($this->env->is_ssl()) {
                $url = "https:" . $url;
            } else {
                $url = "http:" . $url;
            }
        }

        // include Net_URL2
        // @link http://pear.php.net/package/Net_URL2/
        if (!class_exists('O10n\Net_URL2')) {
            require_once O10N_CORE_PATH . 'lib/Net_URL2.php';
        }

        try {
            $base = new Net_URL2($base_href);
            $abs = (string)$base->resolve($url);
        } catch (\Exception $err) {
            throw new Exception('Net_URL2 (rebase): '. $err->getMessage(), 'url');
        }

        return $abs;
    }
}
