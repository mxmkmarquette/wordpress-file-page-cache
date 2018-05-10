<?php
namespace O10n;

/**
 * Cache Directory Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Cache extends Controller implements Controller_Interface
{
    // module key index (used for integer cache database index)
    private $module_index = array(
        'core' => 1 // core module
    );

    // cache stores
    private $stores = array(
        'core' => array(
            'config_index' => array(
                'path' => 'config/',
                'file_ext' => '',
                'expire' => 86400
            )
        )
    );

    private $cachedir; // cache directory root
    private $cachedirs = array(); // cache directories

    private $table; // database table

    private $index_id_cache = array();
    private $stats_cache = array();

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
            'admin',
            'file',
            'options',
            'shutdown',
            'db',
            'env'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // verify cache directory
        if (!is_dir(O10N_CACHE_DIR)) {
            throw new Exception('Cache directory not available.', 'cache');
        }

        // add module cache stores
        $modules = $this->core->modules();
        if ($modules) {
            foreach ($modules as $module) {
                $cache_store_index = $module->cache_store_index();
                if ($cache_store_index) {
                    $module_key = $module->module_key();
                    $this->module_index[$module_key] = $cache_store_index;
                    $this->stores[$module_key] = $module->cache_stores();
                }
            }
        }

        // set store indexes
        foreach ($this->stores as $module => $store_index) {
            $i = 0;
            foreach ($store_index as $key => $store_settings) {
                $i++;
                $this->stores[$module][$key]['index'] = $i;
            }
        }

        // setup cache directory path
        $this->cachedir = $this->file->directory_path('', 'cache');

        // cache database table name
        $this->table = $this->wpdb->prefix . 'o10n__cache';

        if (is_admin()) {
            if (isset($_GET['flush'])) {
                add_action('o10n_setup_completed', array($this, 'init_flush'));
            }

            // setup crons
            if (function_exists('wp_next_scheduled')) {

                // cache cleanup cron
                if (!wp_next_scheduled('o10n_cron_prune_cache')) {
                    wp_schedule_event(current_time('timestamp'), '30min', 'o10n_cron_prune_cache');
                }
            }
        }
    }

    /**
     * Get data from cache
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @param  bool   $gzip      Gzip compress data.
     * @param  bool   $opcache   Load data using PHP Opcache memory cache
     * @param  string $alt_ext   Alternative file extension
     * @return mixed  Cache data
     */
    final public function get($module, $store_key, $hash, $gzip = false, $opcache = false, $alt_ext = false)
    {

        // store info
        $store = $this->store($module, $store_key);

        // hash file
        $hash_file = substr($hash, 6);

        // hash path
        $hash_path = $this->file->directory_path($store['path'] . $this->hash_path($hash), 'cache', false);

        // file extension
        $file_ext = ($alt_ext) ? $alt_ext : $store['file_ext'];

        // verify if file exists
        if (!$hash_path) {
            return false;
        }

        // convert hash to numeric ID
        if (isset($store['hash_id']) && $store['hash_id']) {

            // verify if hash file exists
            if (!file_exists($hash_path . $hash_file)) {
                return false;
            }

            // get index ID
            $index_id = $this->index_id($module, $store_key, $hash);

            // dynamic index ID
            if ($index_id && is_array($index_id)) {
                $suffix = $index_id[1];
                $index_id = $index_id[0];
            } else {
                $suffix = false;
            }

            // verify ID
            if (!$index_id || !is_numeric($index_id)) {
                return false;
            }
            
            // get index data path
            $path = $this->file->directory_path($store['id_dir'] . $this->index_path($index_id), 'cache', false);

            // index directory does not exist
            if (!$path) {
                return false;
            }

            // index file
            $index_file = $index_id . (($suffix) ? ':' . $suffix : '') . $file_ext;

            // set data file path
            $path = $path . $index_file;
        } else {
            $path = $hash_path . $hash_file . $file_ext;
        }

        // check if file exists
        if (!file_exists($path)) {
            return false;
        }

        // return opcache
        if ($opcache) {
            return $this->file->get_opcache($path);
        } elseif ($gzip) {
            return gzinflate(file_get_contents($path));
        } else {
            return file_get_contents($path);
        }
    }
    /**
     * Return cache file path
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @param  string $alt_ext   Alternative file extension
     * @return string Cache file path
     */
    final public function path($module, $store_key, $hash, $alt_ext = false)
    {

        // store info
        $store = $this->store($module, $store_key);

        // hash file
        $hash_file = substr($hash, 6);

        // hash path
        $hash_path = $this->file->directory_path($store['path'] . $this->hash_path($hash), 'cache', false);

        if (!$hash_path) {
            return false;
        }

        // file extension
        $file_ext = ($alt_ext) ? $alt_ext : $store['file_ext'];

        // convert hash to numeric ID
        if (isset($store['hash_id']) && $store['hash_id']) {

            // verify if hash file exists
            if (!file_exists($hash_path . $hash_file)) {
                return false;
            }

            // get index ID
            $index_id = $this->index_id($module, $store_key, $hash);

            // dynamic index ID
            if ($index_id && is_array($index_id)) {
                $suffix = $index_id[1];
                $index_id = $index_id[0];
            } else {
                $suffix = false;
            }

            // verify ID
            if (!$index_id || !is_numeric($index_id)) {
                return false;
            }

            // get index data path
            $index_path = $this->file->directory_path($store['id_dir'] . $this->index_path($index_id), 'cache', false);

            // index file
            $index_file = $index_id . (($suffix) ? ':' . $suffix : '') . $file_ext;

            // set data file path
            return $index_path . $index_file;
        } else {
            return $hash_path . $hash_file . $file_ext;
        }
    }

    /**
     * Return cache url
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @param  string $alt_ext   Alternative file extension
     * @return string URL to cache file.
     */
    final public function url($module, $store_key, $hash, $alt_ext = false)
    {

        // store info
        $store = $this->store($module, $store_key);

        // hash file
        $hash_file = substr($hash, 6);

        // file extension
        $file_ext = ($alt_ext) ? $alt_ext : ((isset($store['file_ext'])) ? $store['file_ext'] : '');

        // convert hash to numeric ID
        if (isset($store['hash_id']) && $store['hash_id']) {

            // get index ID
            $index_id = $this->index_id($module, $store_key, $hash);

            // dynamic index ID
            if ($index_id && is_array($index_id)) {
                $suffix = $index_id[1];
                $index_id = $index_id[0];
            } else {
                $suffix = false;
            }

            // verify ID
            if (!$index_id || !is_numeric($index_id)) {
                return false;
            }

            // get index data path
            $path = $store['id_dir'] . $this->index_path($index_id);

            // index file
            $index_file = $index_id . (($suffix) ? ':' . $suffix : '') . $file_ext;

            // set data file path
            return $this->file->directory_url($path, 'cache') . $index_file;
        } else {

            // hash path
            $path = $store['path'] . $this->hash_path($hash);
        
            return $this->file->directory_url($path, 'cache') . $hash_file . $file_ext;
        }
    }

    /**
     * Save data in cache
     *
     * @param  string $module            Module key
     * @param  string $store_key         Cache store key.
     * @param  string $hash              MD5 hash key.
     * @param  mixed  $data              Cache data to store.
     * @param  int    $suffix            Cache file name suffix.
     * @param  bool   $gzip              Gzip compress data.
     * @param  bool   $opcache           PHP opcache storage
     * @param  string $file_meta         File meta to store.
     * @param  string $file_meta_opcache PHP opcache meta storage.
     * @return bool   Status true or false.
     */
    final public function put($module, $store_key, $hash, $data, $suffix = false, $gzip = false, $opcache = false, $file_meta = false, $file_meta_opcache = false)
    {
        // verify hash
        if (!is_string($hash) || strlen($hash) !== 32) {
            throw new Exception('Invalid cache file hash.', 'cache');
        }

        // store info
        $store = $this->store($module, $store_key);
        $module_index = $this->module_index[$module];

        // hash file
        $hash_file = substr($hash, 6);

        // cache file path
        $hash_path = $this->file->directory_path($store['path'] . $this->hash_path($hash), 'cache');

        // file extension
        $file_ext = $store['file_ext'];

        // index id
        $index_id = false;

        // hash ID index based file path
        if (isset($store['hash_id']) && $store['hash_id']) {

            // query id in cache table
            $exists_id = $this->query_hash_id($module, $store_key, $hash);
            $exists_suffix = false;
            if ($exists_id && is_array($exists_id)) {
                $exists_suffix = $exists_id[1];
                $exists_id = $exists_id[0];
            }

            // verify if file exists
            if ($exists_id && $suffix === $exists_suffix && file_exists($hash_path . $hash_file)) {

                // verify index ID
                try {
                    // PHP opcache
                    $index_id = $this->file->get_opcache($hash_path . $hash_file);
                } catch (\Exception $err) {
                    $index_id = false;
                }

                // index ID exists and is valid
                if ($index_id) {
                    if (
                        $index_id === $exists_id
                        || ($suffix && is_array($index_id) && $index_id['id'] === $exists_id && $index_id['suffix'] === $suffix)
                    ) {

                        // update file modified time
                        try {
                            $this->file->touch($hash_path . $hash_file);
                        } catch (\Exception $err) {
                        }

                        // index id
                        $index_id = (is_array($index_id)) ? $index_id['id'] : $index_id;
                    } else {
                        $index_id = false;
                    }
                }
            }

            // create index id in cache table
            if (!$index_id) {
                $index_id = $this->create_hash_id($module, $store_key, $hash, $suffix);
            }

            // hash index data
            $hash_index_data = ($suffix) ? array('id' => $index_id, 'suffix' => $suffix) : $index_id;

            // store hash index file
            try {
                $this->file->put_opcache($hash_path . $hash_file, $hash_index_data);
            } catch (\Exception $err) {
                throw new Exception('Failed to store cache file ' . $this->file->safe_path($hash_path . $hash_file), 'cache');
            }

            // get index data path
            $index_path = $this->file->directory_path($store['id_dir'] . $this->index_path($index_id), 'cache');

            // index file
            $index_file = $index_id . (($suffix) ? ':' . $suffix : '') . $file_ext;

            // set data file path
            $path = $index_path . $index_file;

            $hash_file_path = $hash_path . $hash_file;
        } else {
            $path = $hash_path . $hash_file . $file_ext;
            $hash_file_path = $path;
        }

        // gzip compress data
        if ($gzip === true) {
            if (!is_string($data)) {
                throw new Exception('Cache data not string.', 'cache');
            }
            $data = gzdeflate($data, 9, FORCE_GZIP);
        }

        // write cache data to file
        try {
            // PHP opcache
            if ($opcache) {
                $this->file->put_opcache($path, $data);
            } else {
                $this->file->put_contents($path, $data);
            }
        } catch (\Exception $err) {
            throw new Exception('Failed to store cache file ' . $this->file->safe_path($path) . '<pre>'.$err->getMessage().'</pre>', 'cache');
        }

        // file time
        $time = filemtime($path);
        
        // write file meta
        if ($file_meta) {

            // verify meta data
            if (!$file_meta_opcache && !is_string($file_meta)) {
                throw new Exception('File meta should be a string.', 'cache');
            }

            try {
                // write meta file
                if ($file_meta_opcache) {
                    $this->file->put_opcache($hash_file_path . '.meta', $file_meta);
                } else {
                    $this->file->put_contents($hash_file_path . '.meta', $file_meta);
                }
            } catch (\Exception $err) {
                throw new Exception('Failed to store cache file meta ' . $this->file->safe_path($hash_file_path . '.meta') . ' <pre>'.$err->getMessage().'</pre>', 'cache');
            }
        } elseif (file_exists($hash_file_path . '.meta')) {

            // remove existing meta file
            @unlink($hash_file_path . '.meta');
        }

        if ($opcache) {
            $size = strlen(serialize($data));
        } else {
            // data size
            $size = strlen($data);
        }

        // static gzip for nginx gzip module
        // @link http://nginx.org/en/docs/http/ngx_http_gzip_static_module.html
        if ($gzip === 'static') {

            // gzip cache file path
            $gzpath = $path . '.gz';

            // gzip compress
            $gzdata = gzdeflate($data, 9);

            // size
            $gzsize = strlen($gzdata);

            try {
                // write gzip file
                $this->file->put_contents($gzpath, $gzdata);
            } catch (\Exception $err) {
                throw new Exception('Failed to store gzip cache file ' . $this->file->safe_path($gzpath) . ' <pre>'.$err->getMessage().'</pre>', 'cache');
            }

            $size += $gzsize;
        }

        // hash ID index based file path
        if ($index_id) {

            // update cache entry
            $this->db->query("UPDATE `".$this->index_table($module, $store_key)."` SET `date`=FROM_UNIXTIME('".$this->db->escape($time)."'), `size`='".(int)$size."',`suffix`='".$this->db->escape($suffix)."' WHERE `id`='".(int)$index_id."' LIMIT 1", array($this,'create_tables'));
        } else {
            
            // insert cache entry
            $this->db->query("REPLACE INTO `".$this->table."` (`module`,`store`,`hash`,`hash_a`,`hash_b`,`date`,`size`) 
                VALUES ('".(int)$module_index."', ".(int)$store['index'].",UNHEX('".$this->db->escape($hash)."'),
                    conv(substring(('".$this->db->escape($hash)."'),1,16),16,-10),
                    conv(right(('".$this->db->escape($hash)."'),16),16,-10),
                    FROM_UNIXTIME('".(int)$time."'),'".(int)$size."')", array($this,'create_tables'));
        }

        return $path;
    }

    /**
     * Preserve cache entry by updating expire time
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @param  bool   $minAge    Minimum age to update time stamp of file.
     * @return bool   Status true or false.
     */
    final public function preserve($module, $store_key, $hash, $minAge = false)
    {
        // verify hash
        if (!is_string($hash) || strlen($hash) !== 32) {
            throw new Exception('Invalid cache file hash.', 'cache');
        }

        // store info
        $store = $this->store($module, $store_key);
        $module_index = $this->module_index[$module];

        // hash file
        $hash_file = substr($hash, 6);

        // cache file path
        $hash_path = $this->file->directory_path($store['path'] . $this->hash_path($hash), 'cache');

        // file extension
        $file_ext = $store['file_ext'];

        // get hash index path
        if (isset($store['hash_id']) && $store['hash_id']) {
            $hash_file_path = $hash_path . $hash_file;
        } else {
            $hash_file_path = $hash_path . $hash_file . $file_ext;
        }

        // verify if file exists
        if (!file_exists($hash_file_path)) {
            return false;
        }

        // last modified time
        $filemtime = filemtime($hash_file_path);

        // preserve when older than minimum age
        if ($minAge && $filemtime > $minAge) {

            // file is withing minimum age
            return false;
        }

        // update last modified time
        try {
            $this->file->touch($hash_file_path);
        } catch (\Exception $err) {
        }

        $filemtime = filemtime($hash_file_path);

        // hash ID index based file path
        if (isset($store['hash_id']) && $store['hash_id']) {
            $table = $this->index_table($module, $store_key);
        } else {
            $table = $this->table;
        }

        // update cache entry
        $this->db->query("UPDATE `".$table."` SET `date`=FROM_UNIXTIME('".(int)$filemtime."') 
            WHERE `module`='".(int)$module_index."' 
            AND `store`='".(int)$store['index']."' 
            AND `hash_a`=conv(substring(('".$this->db->escape($hash)."'),1,16),16,-10)
            AND `hash_b`=conv(right(('".$this->db->escape($hash)."'),16),16,-10)
            LIMIT 1", array($this,'create_tables'));

        return true;
    }

    /**
     * Verify if cache file exists
     *
     * @param  string $module       Module key
     * @param  string $store_key    Cache store key.
     * @param  string $hash         MD5 hash key.
     * @param  int    $expire_check Verify expire date
     * @param  string $alt_ext      Alternative file extension
     * @return bool   Exists true or false.
     */
    final public function exists($module, $store_key, $hash, $expire_check = true, $alt_ext = false)
    {
        // verify hash
        if (!is_string($hash) || strlen($hash) !== 32) {
            throw new Exception('Invalid cache file hash.', 'cache');
        }

        // store info
        $store = $this->store($module, $store_key);

        // hash file
        $hash_file = substr($hash, 6);

        // cache file path
        $hash_path = $this->file->directory_path($store['path'] . $this->hash_path($hash), 'cache');

        // file extension
        $file_ext = ($alt_ext) ? $alt_ext : $store['file_ext'];

        // get hash index path
        if (isset($store['hash_id']) && $store['hash_id']) {
            $hash_file_path = $hash_path . $hash_file;
        } else {
            $hash_file_path = $hash_path . $hash_file . $file_ext;
        }

        // verify if file exists
        if (!file_exists($hash_file_path)) {
            return false;
        }

        // no expire check
        if ($expire_check && $store['expire']) {
            
            // last modified time
            $filemtime = filemtime($hash_file_path);

            // expired
            if ($filemtime + $store['expire'] < time()) {
                return false;
            }
        }

        // get hash index path
        if (isset($store['hash_id']) && $store['hash_id']) {
            $index_path = $this->path($module, $store_key, $hash, $alt_ext);

            // verify if index file exists
            if (!file_exists($index_path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return meta for cache file
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @param  bool   $opcache   Load meta using PHP Opcache memory cache
     * @return bool   Status true or false.
     */
    final public function meta($module, $store_key, $hash, $opcache = false)
    {
        // verify hash
        if (!is_string($hash) || strlen($hash) !== 32) {
            throw new Exception('Invalid cache file hash.', 'cache');
        }

        // store info
        $store = $this->store($module, $store_key);

        // hash file
        $hash_file = substr($hash, 6);

        // cache file path
        $hash_path = $this->file->directory_path($store['path'] . $this->hash_path($hash), 'cache');

        // file extension
        $file_ext = $store['file_ext'];

        // file extension
        $file_ext = $store['file_ext'];

        // get hash index path
        if (isset($store['hash_id']) && $store['hash_id']) {
            $hash_file_path = $hash_path . $hash_file;
        } else {
            $hash_file_path = $hash_path . $hash_file . $file_ext;
        }

        // get hash cache file path
        $meta_file_path = $hash_file_path . '.meta';
        
        // get file meta
        if (file_exists($hash_file_path . '.meta')) {
            try {
                // PHP opcache
                if ($opcache) {
                    $meta = $this->file->get_opcache($hash_file_path . '.meta');
                } else {
                    $meta = file_get_contents($hash_file_path . '.meta');
                }
            } catch (\Exception $err) {
                $meta = false;
            }

            return $meta;
        }

        return false;
    }

    /**
     * Delete cache entry
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @param  bool   $deleteDB  Delete entry from database
     * @return bool   Status true or false.
     */
    final public function delete($module, $store_key, $hash, $deleteDB = true)
    {
        // verify hash
        if (!is_string($hash) || strlen($hash) !== 32) {
            throw new Exception('Invalid cache file hash.', 'cache');
        }

        // store info
        $store = $this->store($module, $store_key);
        $module_index = $this->module_index[$module];

        // hash file
        $hash_file = substr($hash, 6);

        // cache file path
        $hash_path = $this->file->directory_path($store['path'] . $this->hash_path($hash), 'cache', false);

        // verify if file exists
        if ($hash_path && file_exists($hash_path . $hash_file)) {

            // hash ID index based file path
            if (isset($store['hash_id']) && $store['hash_id']) {
                $hash_file_path = $hash_path . $hash_file;

                // elete index file
                $this->delete_index_file($module, $store_key, $hash);
            } else {
                $hash_file_path = $hash_path . $hash_file . $store['file_ext'];
            }

            // delete cache file
            $this->delete_hash_file($module, $store_key, $hash_file_path);
        }

        // delete file from database
        if ($deleteDB) {

            // hash ID index based file path
            if (isset($store['hash_id']) && $store['hash_id']) {
                $table = $this->index_table($module, $store_key);
            } else {
                $table = $this->table;
            }

            // update cache entry
            $this->db->query("DELETE FROM `".$table."`
                WHERE `module`='".(int)$module_index."' 
                AND `store`='".(int)$store['index']."' 
                AND `hash_a`=conv(substring(('".$this->db->escape($hash)."'),1,16),16,-10)
                AND `hash_b`=conv(right(('".$this->db->escape($hash)."'),16),16,-10)
                LIMIT 1", array($this,'create_tables'));
        }

        return true;
    }

    /**
     * Delete hash cache files
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $file      Cache file to delete.
     * @return bool   Status true or false.
     */
    final public function delete_hash_file($module, $store_key, $file)
    {

        // store info
        $store = $this->store($module, $store_key);

        // delete file
        if (file_exists($file)) {
            @unlink($file);
        }

        // delete static gzip
        if (file_exists($file . '.gz')) {
            @unlink($file . '.gz');
        }

        // delete meta
        if (file_exists($file . '.meta')) {
            @unlink($file . '.meta');
        }

        // alternative extensions
        if (isset($store['alt_exts'])) {
            if (is_string($store['alt_exts'])) {
                switch ($store['alt_exts']) {
                    case "index_count":
                        $i = 1;
                        while (file_exists($file . '.' . $i)) {
                            @unlink($file . '.' . $i);
                            $i++;
                        }
                    break;
                    default:
                        throw new Exception('Invalid alt exts.', 'cache');
                    break;
                }
            } elseif (!empty($store['alt_exts'])) {
                foreach ($store['alt_exts'] as $ext) {

                // delete ext file
                    if (file_exists($file . $ext)) {
                        @unlink($file . $ext);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Delete index cache file
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @return bool   Status true or false.
     */
    final public function delete_index_file($module, $store_key, $hash)
    {
        // store info
        $store = $this->store($module, $store_key);

        // not an index store
        if (!isset($store['hash_id']) || !$store['hash_id']) {
            return;
        }

        // query id in cache table
        $exists_id = $this->query_hash_id($module, $store_key, $hash);
        if ($exists_id) {
            if (is_array($exists_id)) {
                $exists_suffix = $exists_id[1];
                $exists_id = $exists_id[0];
            }

            if (is_numeric($exists_id)) {

                // get index data path
                $index_path = $this->file->directory_path($store['id_dir'] . $this->index_path($index_id), 'cache', false);

                if ($index_path) {

                    // index file
                    $index_file = $index_id . (($suffix) ? ':' . $suffix : '') . $store['file_ext'];

                    // set data file path
                    if (file_exists($index_path . $index_file)) {
                        @unlink($index_path . $index_file);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Return database id for hash
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @return mixed  Hash id and optional suffix
     */
    final public function query_hash_id($module, $store_key, $hash)
    {
        // verify store
        $store = $this->store($module, $store_key);
        $module_index = $this->module_index[$module];

        if (!isset($store['hash_id']) || !$store['hash_id']) {
            throw new Exception('Cache store does not support hash index ID.', 'config');
        }

        // verify if hash file exists
        $cache_entry = $this->db->fetch("SELECT `id`,`suffix` FROM `".$this->index_table($module, $store_key)."` WHERE 
            `module`='".(int)$module_index."' 
            AND `store`='".(int)$store['index']."' 
            AND `hash_a`=conv(substring(('".$this->db->escape($hash)."'),1,16),16,-10)
            AND `hash_b`=conv(right(('".$this->db->escape($hash)."'),16),16,-10)
            LIMIT 1");

        // fetch result
        if ($cache_entry && $cache_entry['id']) {
            if (!empty($cache_entry['suffix'])) {
                return array($cache_entry['id'],$cache_entry['suffix']);
            }

            return $cache_entry['id'];
        }

        return false;
    }

    /**
     * Create database id for hash
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @return int    Hash id
     */
    final public function create_hash_id($module, $store_key, $hash, $suffix = '')
    {
        // verify store
        $store = $this->store($module, $store_key);
        $module_index = $this->module_index[$module];

        if (!isset($store['hash_id']) || !$store['hash_id']) {
            throw new Exception('Cache store does not support hash index ID.', 'config');
        }

        // create index row
        $index_id = $this->db->insert("REPLACE INTO `".$this->index_table($module, $store_key)."` (`module`,`store`,`hash`,`hash_a`,`hash_b`,`suffix`) 
                    VALUES (
                        '".(int)$module_index."', ".(int)$store['index'].",UNHEX('".$this->db->escape($hash)."'),
                        conv(substring(('".$this->db->escape($hash)."'),1,16),16,-10),
                        conv(right(('".$this->db->escape($hash)."'),16),16,-10),
                        '".$this->db->escape($suffix)."'
                    )", array($this,'create_tables'));

        if (!$index_id) {
            throw new Exception('Failed create index ID. ' . $this->db->last_error(), 'config');
        }
        
        return $index_id;
    }

    /**
     * Return store info
     *
     * @param  string $module    Module key
     * @param  string $store_key Store key.
     * @return array  Store details.
     */
    final public function store($module, $store_key)
    {
        // verify if store key is valid
        if (!isset($this->stores[$module]) || !isset($this->stores[$module][$store_key])) {
            throw new Exception('Invalid store key: '.$module.':' . $store_key, 'cache');
        }

        return $this->stores[$module][$store_key];
    }
    
    /**
     * Return hash directory path
     *
     * @param  string $hash Cache file hash.
     * @return string Cache file path.
     */
    final public function hash_path($hash)
    {
        // verify hash
        if (!is_string($hash) || strlen($hash) !== 32) {
            throw new Exception('Invalid cache file hash ' . esc_html($hash), 'cache');
        }

        // the path to return
        $path = '';

        // lowercase
        $hash = strtolower($hash);

        // create 3 levels of 2-char subdirectories, [a-z0-9]
        $dir_blocks = array_slice(str_split($hash, 2), 0, 3);
        foreach ($dir_blocks as $block) {
            $path .= $block  . '/';
        }

        return $path;
    }
    
    /**
     * Return index ID
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @param  string $hash      MD5 hash key.
     * @return string Cache file path.
     */
    final public function index_id($module, $store_key, $hash)
    {

        // try local cache
        $cache_key = $store_key . ':' . $hash;
        if (isset($this->index_id_cache[$cache_key])) {
            return $this->index_id_cache[$cache_key];
        }

        // verify store
        $store = $this->store($module, $store_key);

        if (!isset($store['hash_id']) || !$store['hash_id']) {
            throw new Exception('Cache store does not support hash index ID.', 'config');
        }
        
        // hash file
        $hash_file = substr($hash, 6);

        // hash path
        $hash_path = $this->file->directory_path($store['path'] . $this->hash_path($hash), 'cache', false);

        // query index ID from cache file
        try {
            // PHP opcache
            $index_id = $this->file->get_opcache($hash_path . $hash_file);
        } catch (\Exception $err) {
            return $this->index_id_cache[$cache_key] = false;
        }

        $suffix = false;

        // dynamic ID
        if ($index_id && is_array($index_id)) {
            if (!isset($index_id['id'])) {
                throw new Exception('Invalid cache index ID data (array).', 'cache');
            }
            if (isset($index_id['suffix'])) {
                $suffix = $index_id['suffix'];
            }

            $index_id = $index_id['id'];
        }

        // verify ID
        if (!$index_id || !is_numeric($index_id)) {
            return $this->index_id_cache[$cache_key] = false;
        }

        // verify hash
        if (!is_numeric($index_id)) {
            throw new Exception('Invalid cache file index ID.', 'cache');
        }

        if ($suffix) {
            return $this->index_id_cache[$cache_key] = array($index_id,$suffix);
        } else {
            return $this->index_id_cache[$cache_key] = $index_id;
        }
    }
    
    /**
     * Return index ID directory path
     *
     * @param  int    $index_id Index ID
     * @return string Cache file path.
     */
    final public function index_path($index_id)
    {

        // verify hash
        if (!is_numeric($index_id)) {
            throw new Exception('Invalid cache file index ID.', 'cache');
        }
        // the path to return
        $path = '';

        // 1m index
        $m_index = floor($index_id / 1000000);

        // if ($m_index > 0) {

        // 1m increments
        $path .= $m_index . '/';
        // }

        // 1k index
        $k_index = ceil(($index_id - ($m_index * 100000)) / 1000);

        // 1k increments
        $path .= $k_index . '/';

        return $path;
    }

    /**
     * Get cache stats
     *
     * @param  string $module Module key
     * @return array  Stats
     */
    final public function stats($module = false)
    {
        if ($module && !isset($this->stores[$module])) {
            throw new Exception('Invalid cache store.', 'cache');
        }

        // try options cache (5 minute age)
        if (empty($this->stats_cache)) {
            $stats_cache = get_option('o10n_cache_stats', false);
            if ($stats_cache && isset($stats_cache['t'])) {
                $this->stats_cache = $stats_cache;

                // update in the background once per minute
                if ($stats_cache['t'] < (time() - 60)) {
                    $this->shutdown->add(array($this,'update_stats'));
                }
            }
        }

        // return cached result
        if (empty($this->stats_cache)) {

            // update stats
            $this->update_stats();
        }

        if ($module) {
            return (isset($this->stats_cache[$module])) ? $this->stats_cache[$module] : array('count' => 0,'size' => 0);
        }

        return $this->stats_cache;
    }

    /**
     * Update cache stats
     *
     * @return array Stats
     */
    final public function update_stats()
    {
        $tables = array();

        // total cache stats
        $tables[] = $this->table;

        // add hash index tables for all modules
        $modules = array_keys($this->module_index);
        foreach ($modules as $modulekey) {
            if (isset($this->stores[$modulekey])) {
                foreach ($this->stores[$modulekey] as $store_key => $store) {
                    if (isset($store['hash_id']) && $store['hash_id']) {
                        $tables[] = $this->index_table($modulekey, $store_key);
                    }
                }
            }
        }


        // stats
        $stats = array(
            'total' => array(
                'count' => 0,
                'size' => 0
            )
        );

        $module_key_index = array_flip($this->module_index);

        foreach ($tables as $table) {
            
            // query database
            $this->db->query("SELECT count(*) as `count`,SUM(`size`) as `size`,`module` FROM ".$table." GROUP BY `module`", array($this,'create_tables'));
            
            while ($result = $this->db->fetch()) {
                $module_key = (isset($module_key_index[$result['module']])) ? $module_key_index[$result['module']] : false;

                if ($module_key) {
                    if (!isset($stats[$module_key])) {
                        $stats[$module_key] = array(
                            'count' => 0,
                            'size' => 0
                        );
                    }
                    $stats[$module_key]['count'] += $result['count'];
                    $stats[$module_key]['size'] += $result['size'];
                }

                $stats['total']['count'] += $result['count'];
                $stats['total']['size'] += $result['size'];
            }
            $this->db->free_result();
        }

        // save cache
        $stats['t'] = time();
        update_option('o10n_cache_stats', $stats, false);
        $this->stats_cache = $stats;

        return $stats;
    }

    /**
     * Delete empty cache directory
     *
     * @param string $dir Directory to clean.
     */
    final private function delete_empty_directory($dir)
    {
        if (strpos($dir, O10N_CACHE_DIR) !== 0) {
            throw new Exception('Cache directory to clean not within cache path', 'ccache');
        }

        // clean hash cache directory
        if (preg_match('|^((.*/[a-fA-F0-9]{2}/)[a-fA-F0-9]{2}/)[a-fA-F0-9]{2}/$|', $dir, $out)) {
            foreach ($out as $n => $hashdir) {
                if (!$this->file->rmdir($hashdir, false, false)) {
                    break;
                }
            }
        } else {
            $this->file->rmdir($dir, false, false);
        }
    }

    /**
     * Initiate cache flush
     */
    final public function init_flush()
    {
        // verify request
        if (!isset($_GET['flush']) || !wp_verify_nonce($_GET['flush'], 'flush')) {
            return;
        }

        $module = (isset($_GET['module'])) ? $_GET['module'] : false;
        $stores = ($module && isset($_GET['stores'])) ? $_GET['stores'] : false;
        if ($stores) {
            $stores = explode(',', $stores);
        }

        if ($module === 'reset') {
            $this->update_stats();
        } else {

            // exec flush
            try {
                if ($stores) {
                    foreach ($stores as $store) {
                        $this->flush($module, $store);
                    }
                } else {
                    $this->flush($module);
                }
            } catch (Exception $err) {
                wp_die($err->getMessage());
            }

            // add notice
            if ($module) {
                if ($stores) {
                    $message = 'The optimization cache for module <strong>'.$module.'</strong> stores <strong>'.implode(', ', $stores).'</strong> has been cleared.';
                } else {
                    $message = 'The optimization cache for module <strong>'.$module.'</strong> has been cleared.';
                }
            } else {
                $message = 'The optimization cache has been cleared.';
            }
            $this->admin->add_notice($message, 'cache', 'SUCCESS');
        }

        $return_url = (isset($_GET['return'])) ? $_GET['return'] : false;
        if ($return_url) {
            if (wp_redirect($return_url)) {
                exit;
            }
        }
    }

    /**
     * Flush
     *
     * @param string $module    Module key
     * @param string $store_key Cache store key.
     */
    final public function flush($module = false, $store_key = false)
    {
        if (($module && !isset($this->stores[$module])) || ($module && $store_key && !isset($this->stores[$module][$store_key]))) {
            throw new Exception('Invalid cache store.', 'cache');
        }

        $tables = array();

        // clear complete cache directory
        if (!$module) {
            $tables[] = $this->table;

            $modules = array_keys($this->module_index);
            foreach ($modules as $modulekey) {
                if (isset($this->stores[$modulekey])) {
                    foreach ($this->stores[$modulekey] as $store_key => $store) {
                        if (isset($store['hash_id']) && $store['hash_id']) {
                            $tables[] = $this->index_table($modulekey, $store_key);
                        }
                    }
                }
            }

            // delete cache directory contents
            $this->file->rmdir(O10N_CACHE_DIR, true);

            // empty database
            foreach ($tables as $table) {
                $this->db->query("TRUNCATE `" . $table . "`", array($this,'create_tables'));
            }
        } else {
            $module_index = $this->module_index[$module];
            
            if (!$store_key) {
                $stores = array_keys($this->stores[$module]);
            } else {
                $stores = array($store_key);
            }

            foreach ($stores as $store_key) {
                $store = $this->store($module, $store_key);
                $store_path = $this->file->directory_path($store['path'], 'cache');
                
                // delete store directory contents
                $this->file->rmdir($store_path, true);

                // hash ID index table
                if (isset($store['hash_id']) && $store['hash_id']) {
                    $table = $this->index_table($module, $store_key);

                    $this->db->query("TRUNCATE `" . $table . "`", array($this,'create_tables'));
                } else {
                    $table = $this->table;

                    // delete cache entries
                    $this->db->query("DELETE FROM `".$table."` WHERE `module`='".(int)$module_index."' AND `store`='".(int)$store['index']."'", array($this,'create_tables'));
                }
            }
        }

        // delete option
        delete_option('o10n_cache_stats');
    }

    /**
     * Return cache flush URL
     *
     * @param  string $module    Module key
     * @param  string $store_key Cache store key.
     * @return array  Stats
     */
    final public function flush_url($module = false, $store_key = false)
    {
        if ($module && $module !== 'reset' && !isset($this->stores[$module])) {
            throw new Exception('Invalid cache store.', 'cache');
        }

        $return_url = ($this->env->is_ssl() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $params = array(
            'page' => 'o10n'
        );

        if ($module) {
            $params['module'] = $module;

            if ($store_key) {
                if (is_array($store_key)) {
                    $store_key = implode(',', $store_key);
                }

                $params['stores'] = $store_key;
            }
        }

        $params['return'] = $return_url;

        return wp_nonce_url(add_query_arg($params, admin_url('admin.php')), 'flush', 'flush');
    }

    /**
     * Prune expired cache
     *
     * @param string $module    Module key
     * @param string $store_key Cache store key.
     */
    final public function prune($module = false, $store_key = false)
    {
        if (($module && !isset($this->stores[$module])) || ($module && $store_key && !isset($this->stores[$module][$store_key]))) {
            throw new Exception('Invalid cache store.', 'cache');
        }

        // start time
        $time = time();

        // prune all modules
        if (!$module) {
            $modules = array_keys($this->module_index);
        } else {
            $modules = array($module);
        }

        try {
            foreach ($modules as $module) {
                $module_index = $this->module_index[$module];
                if (!$store_key) {
                    $stores = array_keys($this->stores[$module]);
                } else {
                    $stores = array($store_key);
                }

                foreach ($stores as $module_store_key) {
                    $store = $this->store($module, $module_store_key);

                    // cache entries do not expire
                    if (!isset($store['expire']) || !$store['expire']) {
                        continue;
                    }

                    $store['expire'] = 0;

                    // hash ID index table
                    if (isset($store['hash_id']) && $store['hash_id']) {
                        $table = $this->index_table($module, $module_store_key);
                    } else {
                        $table = $this->table;
                    }

                    // query cache entries
                    $this->db->query("SELECT HEX(`hash`) as `hash` FROM `".$table."` WHERE `module`='".(int)$module_index."' AND `store`='".(int)$store['index']."' AND DATE_ADD(`date`, INTERVAL ".$store['expire']." SECOND) < NOW()", array($this,'create_tables'));
                    if ($this->db->num_rows()) {
                        $delete = array();
                        $delete_batch_size = apply_filters('o10n_cache_prune_batch_size', 100);
                        if (!is_numeric($delete_batch_size) || $delete_batch_size < 1) {
                            $delete_batch_size = 100;
                        }
                        $delete_batch_index = 0;
                        while ($row = $this->db->fetch()) {
                            if (!isset($delete[$delete_batch_index])) {
                                $delete[$delete_batch_index] = array();
                            }

                            // delete files
                            $this->delete($module, $module_store_key, $row['hash'], (($delete_batch_size === 1) ? true : false));

                            if ($delete_batch_size > 1) {
                                $delete[$delete_batch_index][] = $row['hash'];
                                if (count($delete[$delete_batch_index]) >= $delete_batch_size) {
                                    $delete_batch_index++;
                                }
                            }
                        }
                        $this->db->free_result();

                        // delete from database
                        if (!empty($delete)) {
                            foreach ($delete as $batch) {
                                $this->db->query("DELETE FROM `".$table."` WHERE `module`='".(int)$module_index."' AND `store`='".(int)$store['index']."' AND `hash` IN (UNHEX('".implode("'),UNHEX('", $batch)."')) LIMIT ".count($batch)."", array($this,'create_tables'));
                            }
                        }
                    }
                }
            }
        } catch (Exception $err) {
            wp_die($err->getMessage());
        }
    }

    /**
     * Return cached path
     */
    final private function cached_path($key, $path = -1)
    {

        // set path
        if ($path !== -1) {
            $this->cache_paths[$key] = $path;
        } else {
            return (isset($this->cache_paths[$key])) ? $this->cache_paths[$key] : false;
        }
    }

    /**
     * Return index table name
     */
    final private function index_table($module, $store_key)
    {
        $index_table = $module . '_' . $store_key;

        // verify module name
        if (!preg_match('|^[a-z0-9_]+$|i', $index_table)) {
            throw new Exception('Invalid module name for cache index table.', 'cache');
        }

        return $this->table . '_' . $index_table;
    }

    /**
     * Create cache tables
     */
    final public function create_tables()
    {
        try {

            // verify if cache table exists
            $table_exists = ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE '%s'", $this->table)) === $this->table);
            if (!$table_exists) {

                // create hash table
                $sql = "CREATE TABLE `".$this->table."` (
                    `module` int(10) UNSIGNED NOT NULL,
                    `store` int(10) UNSIGNED NOT NULL,
                    `hash` binary(16) NOT NULL,
                    `hash_a` bigint(20) NOT NULL,
                    `hash_b` bigint(20) NOT NULL,
                    `date` datetime DEFAULT NULL,
                    `size` int(10) UNSIGNED DEFAULT NULL,
                    PRIMARY KEY (`module`,`store`,`hash`),
                    UNIQUE KEY (`module`,`store`,`hash_a`,`hash_b`),
                    INDEX (`module`),
                    INDEX (`store`),
                    INDEX (`date`)
                ) ENGINE=InnoDB;";

                $this->db->query($sql);
            }

            // create hash ID index tables
            foreach ($this->stores as $module => $module_stores) {
                foreach ($module_stores as $store_key => $store) {
                    if (isset($store['hash_id']) && $store['hash_id']) {
                        $index_table = $this->table . '_' . $module . '_' . $store_key;

                        // verify if cache index table exists
                        $index_table = $this->index_table($module, $store_key);
                        $table_exists = ($this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE '%s'", $index_table)) === $index_table);
                        if (!$table_exists) {

                            // create hash ID index table
                            $sql = "CREATE TABLE `".$index_table."` (
                                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                `module` int(10) UNSIGNED NOT NULL,
                                `store` int(10) UNSIGNED NOT NULL,
                                `hash` binary(16) NOT NULL,
                                `hash_a` bigint(20) NOT NULL,
                                `hash_b` bigint(20) NOT NULL,
                                `date` datetime DEFAULT NULL,
                                `size` int(10) UNSIGNED DEFAULT NULL,
                                `suffix`    VARCHAR(100) NOT NULL,
                                PRIMARY KEY (`id`),
                                UNIQUE KEY (`module`,`store`,`hash`),
                                UNIQUE KEY (`module`,`store`,`hash_a`,`hash_b`),
                                INDEX (`module`),
                                INDEX (`store`),
                                INDEX (`date`)
                            ) ENGINE=InnoDB;";

                            $this->db->query($sql);
                        }
                    }
                }
            }
        } catch (Exception $err) {
            wp_die($err->getMessage());
        }
    }
}
