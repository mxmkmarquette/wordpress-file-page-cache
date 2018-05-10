<?php
namespace O10n;

/**
 * File Cache Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Filecache extends Controller implements Controller_Interface
{
    private $cache_enabled = true;
    private $opcache_enabled = null;

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
            'url',
            'env',
            'options',
            'cache'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        if (!$this->env->is_optimization()) {
            return;
        }
        
        // File Page Cache requires SSL
        if ($this->options->bool('filecache.enabled')) {

            // verify if page is cached
            if ($this->cache_enabled && !defined('O10N_NO_PAGE_CACHE')) {
                $cachehash = md5($this->url->request());
                if ($this->cache->exists('filecache', 'page', $cachehash)) {

                    // print cached page
                    $this->print($cachehash);
                }
            }

            // add filter for page cache
            add_filter('o10n_html_final', array( $this, 'update_cache' ), 1000, 1);
        }
    }

    /**
     * Store page in cache
     *
     * @param string $buffer HTML buffer
     */
    final public function update_cache($buffer)
    {
        if (!$this->cache_enabled || defined('O10N_NO_PAGE_CACHE')) {
            return $buffer;
        }

        // request URL
        $url = $this->url->request();

        // verify cache policy
        $cache = true;

        if ($cache) {
            $cachehash = md5($url);

            // search & replace
            $replace = $this->options->get('filecache.replace');
            if (isset($replace) && is_array($replace) && !empty($replace)) {
                $rs = $rr = array();
                foreach ($replace as $object) {
                    if (!isset($object['search']) || trim($object['search']) === '') {
                        continue;
                    }

                    if (isset($object['regex']) && $object['regex']) {
                        $rs[] = $object['search'];
                        $rr[] = $object['replace'];
                    } else {
                        $s[] = $object['search'];
                        $r[] = $object['replace'];
                    }
                }

                if (!empty($s)) {
                    $buffer = str_replace($s, $r, $buffer);
                }
                if (!empty($rs)) {
                    try {
                        $buffer = @preg_replace($rs, $rr, $buffer);
                    } catch (\Exception $err) {
                        // @todo log error
                    }
                }
            }

            // opcache policy
            if (!is_null($this->opcache_enabled)) {
                $opcache = $this->opcache_enabled;
            } else {

                // apply opcache policy
                $opcache = $this->options->bool('filecache.opcache.enabled', false);
            }

            // store in cache
            $this->cache->put('filecache', 'page', $cachehash, $buffer, false, true, $opcache, array(
                time(),
                md5($buffer),
                $opcache
            ), true);

            header('X-O10n-Cache: MISS');
        }

        return $buffer;
    }

    /**
     * Enable/disable page cache
     *
     * @param bool $state Enabled state
     */
    final public function enable($state = true)
    {
        $this->cache_enabled = $state;
    }

    /**
     * Enable/disable PHP Opcache
     *
     * @param bool $state Enabled state
     */
    final public function boost($state = true)
    {
        $this->opcache_enabled = $state;
    }

    /**
     * Print cached page
     *
     * @param string $cachehash Cache hash
     */
    final public function print($cachehash)
    {
        $start = microtime(true);

        $pagemeta = $this->cache->meta('filecache', 'page', $cachehash, true);
        if (!$pagemeta) {
            return false;
        }

        $utf8 = apply_filters('o10n_page_cache_utf8', true);
        if ($utf8) {
            header("Content-type: text/html; charset=UTF-8");
        } else {
            header("Content-type: text/html");
        }

        header("Last-Modified: ".gmdate("D, d M Y H:i:s", $pagemeta[0])." GMT");
        header("Etag: " . $pagemeta[1]);
        header('Vary: Accept-Encoding');

        // verify 304 status
        if (function_exists('apache_request_headers')) {
            $request = apache_request_headers();
            $modified = (isset($request[ 'If-Modified-Since' ])) ? $request[ 'If-Modified-Since' ] : null;
        } else {
            if (isset($_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ])) {
                $modified = $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ];
            } else {
                $modified = null;
            }
        }
        $last_modified = gmdate("D, d M Y H:i:s", $pagemeta[0]).' GMT';

        if (
            ($modified && $modified == $last_modified)
            || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $pagemeta[1])
            ) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }

        // get compressed page data
        $gzipHTML = $this->cache->get('filecache', 'page', $cachehash, false, $pagemeta[2]);

        // detect gzip support
        if (!isset($_SERVER[ 'HTTP_ACCEPT_ENCODING' ]) || (isset($_SERVER[ 'HTTP_ACCEPT_ENCODING' ]) && strpos($_SERVER[ 'HTTP_ACCEPT_ENCODING' ], 'gzip') === false)) {

            // uncompress
            $gzipHTML = gzinflate($gzipHTML);
        } else {
            // output gzip
            ini_set("zlib.output_compression", "Off");
        
            header('Content-Encoding: gzip');
        }

        $end = microtime(true);
        header('X-O10n-Cache: ' . number_format((($end - $start) * 1000), 5).'ms');

        header('Content-Length: ' . (function_exists('mb_strlen') ? mb_strlen($gzipHTML, '8bit') : strlen($gzipHTML)));

        echo $gzipHTML;
        exit();
    }
}
