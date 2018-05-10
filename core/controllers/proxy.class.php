<?php
namespace O10n;

/**
 * Proxy Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Proxy extends Controller implements Controller_Interface
{

    // proxy types
    private $types = array('css','js');

    // time after which to update cache files
    private $updateInterval = 3600;

    // proxy cache files to update on shutdown
    private $proxyList = array();
    private $updateList = array();
    private $debugList = array();

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
            'cache',
            'client',
            'url',
            'http',
            'json',
            'shutdown',
            'client',
            'options',
            'env'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {

        // optimization enabled?
        if (!$this->env->is_optimization()) {
            return;
        }

        // load client module?
        $loadProxyModule = false;

        // CSS capture
        if ($this->options->bool('css.proxy.enabled') && $this->options->bool('css.proxy.capture.enabled')) {

            // proxy list
            $proxyList = $this->options->get('css.proxy.capture.list');

            // capture list
            $capture = array();
            $capture_match = array();
            if (is_array($proxyList)) {
                foreach ($proxyList as $url) {
                    if (is_array($url)) {
                        if (!isset($url['delete']) && !isset($url['rewrite']) && !isset($url['url'])) {
                            continue 1;
                        }

                        $regex = (isset($url['regex']) && $url['regex']);
                        $match = false;

                        // delete
                        if (isset($url['delete'])) {
                            $match = array($url['match'],1);
                            if ($regex) {
                                $match[] = 1;
                            }
                        } elseif (isset($url['rewrite'])) {
                            $match = array($url['match'],array($url['rewrite']));
                            if ($regex) {
                                $match[] = 1;
                            }
                        } elseif (isset($url['url'])) {
                            $proxyHash = $this->proxify('css', $url['url'], 'hash');
                            if ($proxyHash) {
                                $match = array($url['match'],$proxyHash);
                                if ($regex) {
                                    $match[] = 1;
                                }
                            }
                        }

                        if ($match) {
                            $capture_match[] = $match;
                        }
                    } else {
                        $proxyHash = $this->proxify('css', $url, 'hash');
                        if ($proxyHash) {
                            $capture[$url] = $proxyHash;
                        }
                    }
                }
            }

            if (!empty($capture)) {
                // set enabled state
                $this->client->set_config('css', 'proxy', $capture);

                // load client module
                $loadProxyModule = true;
            }
            if (!empty($capture_match)) {
                // set enabled state
                $this->client->set_config('css', 'proxy_match', $capture_match);

                // load client module
                $loadProxyModule = true;
            }
        }

        // Javascript proxy capture
        if ($this->options->bool('js.proxy.enabled') && $this->options->bool('js.proxy.capture.enabled')) {

            // proxy list
            $proxyList = $this->options->get('js.proxy.capture.list');

            // capture list
            $capture = array();
            $capture_match = array();
            if (is_array($proxyList)) {
                foreach ($proxyList as $url) {
                    if (is_array($url)) {
                        if (!isset($url['delete']) && !isset($url['rewrite']) && !isset($url['url'])) {
                            continue 1;
                        }
                       
                        $regex = (isset($url['regex']) && $url['regex']);
                        $match = false;

                        // delete
                        if (isset($url['delete'])) {
                            $match = array($url['match'],1);
                            if ($regex) {
                                $match[] = 1;
                            }
                        } elseif (isset($url['rewrite'])) {
                            $match = array($url['match'],array($url['rewrite']));
                            if ($regex) {
                                $match[] = 1;
                            }
                        } elseif (isset($url['url'])) {
                            $proxyHash = $this->proxify('js', $url['url'], 'hash');
                            if ($proxyHash) {
                                $match = array($url['match'],$proxyHash);
                                if ($regex) {
                                    $match[] = 1;
                                }
                            }
                        }

                        if ($match) {
                            $capture_match[] = $match;
                        }
                    } else {
                        $proxyHash = $this->proxify('js', $url, 'hash');
                        if ($proxyHash) {
                            $capture[$url] = $proxyHash;
                        }
                    }
                }
            }

            if (!empty($capture)) {

                // set enabled state
                $this->client->set_config('js', 'proxy', $capture);

                // load client module
                $loadProxyModule = true;
            }
            if (!empty($capture_match)) {

                // set enabled state
                $this->client->set_config('js', 'proxy_match', $capture_match);

                // load client module
                $loadProxyModule = true;
            }
        }

        // load client module
        if ($loadProxyModule) {
            $this->client->load_module('proxy');
        }
        
        // apply debug config
        if (defined('O10N_DEBUG') && O10N_DEBUG) {
            $this->debug_config();
        }
    }

    /**
     * Proxify url
     *
     * @param  string $type Asset type
     * @param  string $url  Asset URL
     * @return string Profixied URL.
     */
    final public function proxify($type, $url, $returnType = false)
    {
        // verify type
        if (!in_array($type, $this->types)) {
            throw new Exception('Invalid proxy type ' . esc_html($type), 'proxy');
        }

        $proxyUrl = $url;
        $proxyHash = false;

        // verify if URL is local
        if (!$this->url->is_local($url, false, false)) {

            // cache store
            $cacheStore = $type;

            // cache hash
            $cacheHash = $this->cache_hash($type, $url);

            // get cache file and verify if it exists
            $cacheFile = $this->cache->path($cacheStore, 'proxy', $cacheHash);

            // verify if proxy cache exists
            if ($cacheFile) {
                $meta = $this->cache->meta($cacheStore, 'proxy', $cacheHash, true);
                $update_interval = (isset($meta[3]) && is_numeric($meta[3]) && $meta[3] > 0) ? $meta[3] : 3600; // expire header

                // update proxy cache when older than 1 hour
                if (filemtime($cacheFile) < (time() - $update_interval)) {

                    // perform update on shutdown
                    if (empty($this->updateList) && empty($this->proxyList)) {
                        $this->shutdown->add(array($this,'shutdown_update'));
                    }

                    // add to update list
                    $this->updateList[$url] = array('type' => $type, 'hash' => $cacheHash);
                }

                if (defined('O10N_DEBUG') && O10N_DEBUG) {
                    $this->debugList[$url] = $this->cache->url($cacheStore, 'proxy', $cacheHash);
                }

                $proxyUrl = $this->cache->url($cacheStore, 'proxy', $cacheHash);
                $proxyHash = $cacheHash;
            } else {

                // proxy url on shutdown
                if (empty($this->updateList) && empty($this->proxyList)) {
                    $this->shutdown->add(array($this,'shutdown_update'));
                }

                // add to proxy list
                $this->proxyList[$url] = array('type' => $type, 'hash' => $cacheHash);

                if (defined('O10N_DEBUG') && O10N_DEBUG) {
                    $this->debugList[$url] = $this->cache->url($cacheStore, 'proxy', $cacheHash);
                }
            }
        }

        switch ($returnType) {
            case "hash":
                return $proxyHash;
            break;
            case "url":
                if ($proxyHash) {
                    return $this->cache->url($cacheStore, 'proxy', $proxyHash);
                } else {
                    return $url;
                }
            break;
            case "filedata":

                // proxy on the fly
                if (!$proxyHash) {
                    $this->proxy($type, $url, $cacheHash);
                }

                $meta = $this->cache->meta($cacheStore, 'proxy', $cacheHash, true);
                $data = $this->cache->get($cacheStore, 'proxy', $cacheHash);

                return array($data,$meta);
            break;
            default:
                return array($proxyHash,$proxyUrl);
            break;
        }
    }

    /**
     * Return cache hash
     *
     * @param  string $type Asset type
     * @param  string $url  Asset URL
     * @return string Cache hash
     */
    final public function cache_hash($type, $url)
    {
        return md5($type . ':' . $url);
    }

    /**
     * Return proxy asset meta
     *
     * @param  string $type      Asset type
     * @param  string $url       Asset URL
     * @param  string $cacheHash Precalculated cache hash
     * @return array  Proxy asset meta
     */
    final public function meta($type, $url, $cacheHash = false)
    {
        // cache hash
        if (!$cacheHash) {
            $cacheHash = $this->cache_hash($type, $url);
        }

        // cache store
        $cacheStore = $type;

        // return meta
        return $this->cache->meta($cacheStore, 'proxy', $cacheHash, true);
    }

    /**
     * Proxy asset
     *
     * @param string $type      Asset type
     * @param string $url       Asset URL
     * @param string $cacheHash Precalculated cache hash
     */
    final private function proxy($type, $url, $cacheHash = false)
    {
        // cache hash
        if (!$cacheHash) {
            $cacheHash = $this->cache_hash($type, $url);
        }

        // cache store
        $cacheStore = $type;

        // apply proxy URL filter
        $filteredUrl = apply_filters('o10n_proxy_url', $url, $type);
        if ($filteredUrl) {
            $url = $filteredUrl;
        }

        // get asset content
        try {
            $responseData = $this->http->get($url);
        } catch (HTTPException $e) {
            throw new Exception('Failed to proxy ' . esc_url($url) . ' Status: '.$e->getStatus().' Error: ' . $e->getMessage(), 'proxy');
        }

        // invalid status code
        if ($responseData[0] !== 200) {
            throw new Exception('Failed to proxy ' . esc_url($url) . ' Status: '.$responseData[0].'', 'proxy');
        }

        // get headers
        $headers = wp_remote_retrieve_headers($responseData[2]);

        // asset content
        $assetContent = trim($responseData[1]);

        // apply content filters
        $assetContent = apply_filters('o10n_proxy_' . $type. '_content', $assetContent, $url, $cacheHash);
        
        // file content hash
        $file_hash = md5($assetContent);

        $lastModified = ($headers && isset($headers['last-modified'])) ? $headers['last-modified'] : false;
        $ETag = ($headers && isset($headers['etag'])) ? $headers['etag'] : false;

        $expires = ($headers && isset($headers['expires'])) ? (strtotime($headers['expires']) - time()) : false;
        if ($expires && $expires <= 0) {
            $expires = false;
        }
        if (!$expires && $headers && isset($headers['cache-control']) && preg_match('|max-age=([0-9]+)|s', $headers['cache-control'], $out) !== false) {
            $expires = intval($out[1]);
            if ($expires < 60) {
                $expires = 60;
            }
        }

        // proxy comment
        $proxyComment = "/* @proxy ".$url." */\n";

        // detect if asset contains source map
        $sourcemapStr = '/*# sourceMappingURL=';
        $sourcemapPos = strpos($assetContent, $sourcemapStr);
        if ($sourcemapPos !== false) {
            $assetContent = substr_replace($assetContent, $proxyComment, $sourcemapPos, 0);
        } else {
            $assetContent .= "\n" . $proxyComment;
        }

        // apply proxy content filter
        $filteredContent = apply_filters('o10n_proxy_content', $assetContent, $url, $type);
        if ($filteredContent) {
            $assetContent = $filteredContent;
        }

        // save proxy cache file
        $this->cache->put($cacheStore, 'proxy', $cacheHash, $assetContent, false, false, false, array(
            $lastModified,
            $ETag,
            $file_hash,
            $expires
        ), true);
    }

    /**
     * Update cache files on shutdown
     */
    final public function shutdown_update()
    {
        // errors
        $errors = array();

        // update list
        if (!empty($this->updateList)) {
            $updateInterval = (time() - $this->updateInterval);

            foreach ($this->updateList as $url => $asset) {
                
                // cache store
                $cacheStore = $asset['type'];

                // get cache file meta
                $meta = $this->cache->meta($cacheStore, 'proxy', $asset['hash'], true);

                // update asset content?
                $updateContent = false;

                // use meta to verify update
                // 0 = last-modified, 1 = etag
                if ($meta && (isset($meta[0]) || isset($meta[1]))) {

                    // HTTP HEAD request
                    $headers = array();

                    // last-modified
                    if ($meta[0]) {
                        $headers['If-Modified-Since'] = $meta[0];
                    }

                    // etag
                    if ($meta[1]) {
                        $headers['If-None-Match'] = $meta[1];
                    }

                    try {
                        $headers = $this->http->head($url, array('headers' => $headers));
                    } catch (HTTPException $err) {
                        $errors[$url] = 'Failed to proxy (HEAD update) ' . esc_url($url) . ' Status: '.$err->getStatus().' Error: ' . $err->getMessage();
                        continue 1;
                    }

                    // status 304 Not Modified
                    if ($headers[0] !== 304) {
                        if (!$meta[0] && !$meta[1]) {
                            $updateContent = true;
                            // etag
                        } elseif ($meta[1] && (!isset($headers['etag']) || $headers['etag'] !== $meta[1])) {
                            // etag

                            $updateContent = true;
                        } elseif ($meta[0] && (!isset($headers['last-modified']) || $headers['last-modified'] !== $meta[0])) {
                            // last modified

                            $updateContent = true;
                        }
                    }
                } else {
                    $updateContent = true;
                }

                // update asset content
                if ($updateContent) {

                    // delete cache file
                    $this->cache->delete($cacheStore, 'proxy', $asset['hash']);

                    // add to proxy list
                    $this->proxyList[$url] = $asset;
                } else {

                    // preserve cache file
                    $this->cache->preserve($cacheStore, 'proxy', $asset['hash'], $updateInterval);
                }
            }
        }

        // proxy list
        if (!empty($this->proxyList)) {
            foreach ($this->proxyList as $url => $asset) {
                try {
                    $this->proxy($asset['type'], $url, $asset['hash']);
                } catch (HTTPException $err) {
                    $errors[$url] = 'Failed to proxy ' . esc_url($url) . ' Status: '.$err->getStatus().' Error: ' . $err->getMessage();
                } catch (Exception $err) {
                    $errors[$url] = 'Failed to proxy ' . esc_url($url) . ' Error: ' . $err->getMessage();
                }
            }
        }

        // throw exception for errors
        if (!empty($errors)) {
            throw new Exception('Error during proxy update (shutdown callback): <ol><li>'.implode('</li><li>', $errors).'</li></ol>', 'proxy');
        }
    }

    /**
     * Add debug references to client config
     */
    final public function debug_config()
    {
        $proxy_ref_list = array();
        if (!empty($this->debugList)) {
            foreach ($this->debugList as $url => $hash) {
                $proxy_ref_list[$hash] = $url;
            }
        }

        $this->client->set_config('proxy', 'debug_ref', $proxy_ref_list);
    }
}
