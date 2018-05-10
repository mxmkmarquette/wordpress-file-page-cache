<?php
namespace O10n;

/**
 * File System Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class File extends Controller implements Controller_Interface
{
    private $directory_paths = array(); // directory paths
    private $directory_urls = array(); // directory paths

    private $opcache_support = false;

    private $theme_directory; // theme directory

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
            'json',
            'url'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {

        // theme config chmod
        if (!defined('O10N_THEME_CHMOD_FILE')) {
            define('O10N_THEME_CHMOD_FILE', O10N_CACHE_CHMOD_FILE);
        }
        if (!defined('O10N_THEME_CHMOD_DIR')) {
            define('O10N_THEME_CHMOD_DIR', O10N_CACHE_CHMOD_DIR);
        }
        
        // verify cache path
        if (strrpos(O10N_CACHE_DIR, DIRECTORY_SEPARATOR) !== (strlen(O10N_CACHE_DIR) - 1)) {
            throw new Exception('Cache directory path does not contain trailing slash.', 'core');
        }

        // verify cache url
        if (strrpos(O10N_CACHE_URL, '/') !== (strlen(O10N_CACHE_URL) - 1)) {
            throw new Exception('Cache directory URL does not contain trailing slash.', 'core');
        }

        // initiate cache directory
        if (!is_dir(O10N_CACHE_DIR)) {

            // try to create cache directory
            try {
                $this->mkdir(O10N_CACHE_DIR, false, true);
            } catch (\Exception $e) {
                  
                // print fatal error to public
                wp_die(__('Failed to create Advanced Optimization cache directory. Please check the permissions of ' . $this->safe_path(O10N_CACHE_DIR), 'o10n') . '<hr />'.$e->getMessage());
            }
        }

        // stylesheet directory
        $this->theme_directory = $this->trailingslashit(get_stylesheet_directory());

        // detect PHP Opcache support
        if (function_exists('opcache_invalidate')) {
            $this->opcache_support = true;
        }
    }

    /**
     * Create directory
     *
     * @param  string $path      Directory path to create.
     * @param  octal  $mode      The permission mode for the directory.
     * @param  bool   $recursive Create path recursively.
     * @return bool   Created true/false.
     */
    final public function mkdir($path, $mode = false, $recursive = false)
    {
        // set default mode
        if (!$mode) {
            $mode = O10N_CACHE_CHMOD_DIR;
        }

        // Restrict access to /wp-content/...
        if (!$this->valid_path($path)) {
            throw new \Exception('Failed to create directory ' . $this->safe_path($path) . ' (Invalid directory path)');
        }

        if (!is_dir($path)) {
            @mkdir($path, $mode, $recursive);
        }
        if (is_dir($path)) {

            // set permissions
            @chmod($path, $mode);

            return true;
        }

        throw new \Exception('Failed to create directory ' . $this->safe_path($path));
    }

    /**
     * Remove directory
     *
     * @param  string $dir       The directory to delete/empty
     * @param  bool   $recursive Delete contents only (not the directory accessed)
     * @return bool   Deleted true/false.
     */
    final public function rmdir($path, $contentsOnly = false, $deleteNonEmpty = true)
    {
        if (!is_dir($path)) {
            return false;
        }

        // restrict access to /wp-content/...
        if (!$this->valid_path($path)) {
            throw new \Exception('Failed to remove directory ' . $this->safe_path($path) . ' (Invalid directory path)');
        }

        // delete files in directory
        $files = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);
        if (!$deleteNonEmpty && !empty($files)) {
            return false;
        }

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                $this->rmdir($fileinfo->getPathname());
            } else {
                @unlink($fileinfo->getPathname());
            }
        }

        // do not delete top-level directory
        if ($contentsOnly) {
            return true;
        }

        // delete directory
        return @rmdir($path);
    }

    /**
     * Store file contents
     *
     * @param  string $file     The file to store.
     * @param  string $contents The contents to store.
     * @param  octal  $mode     The permission mode for the file.
     * @return bool   File created true/false.
     */
    final public function put_contents($file, $contents, $mode = false, $flags = LOCK_EX)
    {
        // set default mode
        if (!$mode) {
            $mode = O10N_CACHE_CHMOD_FILE;
        }

        // initiate file to test for write access
        if (!file_exists($file)) {
            try {
                @touch($file);
            } catch (\Exception $err) {
                // ignore
            }
        }

        // check if file is writeable
        if (!is_writable($file)) {
            throw new \Exception('File is not writeable: ' . $this->safe_path($file));
        }

        // save file contents
        file_put_contents($file, $contents, $flags);

        // set permissions
        if (file_exists($file)) {
            @chmod($file, $mode);

            return true;
        }

        throw new \Exception('Failed to store file ' . $this->safe_path($file));
    }

    /**
     * Store PHP Opcache file contents
     *
     * @param  string $file     The file to store.
     * @param  string $contents The contents to store.
     * @param  octal  $mode     The permission mode for the file.
     * @return bool   File created true/false.
     */
    final public function put_opcache($file, $contents, $mode = false, $flags = LOCK_EX)
    {
        $result = $this->put_contents($file, '<?php return ' . var_export($contents, true) . ';', $mode, $flags);

        // clear opcache cache for file
        if ($this->opcache_support) {
            opcache_invalidate($file);
        }

        return $result;
    }

    /**
     * Get PHP Opcache file contents
     *
     * @param  string $file The file to get.
     * @return mixed  File contents.
     */
    final public function get_opcache($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        // get cache file contents
        try {
            $contents = include $file;
        } catch (\Exception $err) {
            return false;
        }

        return $contents;
    }

    /**
     * Clear PHP Opcache cache
     *
     * @param string $file The file to clear opcache for.
     */
    final public function clear_opcache($file)
    {
        // clear opcache cache for file
        if ($this->opcache_support) {
            opcache_invalidate($file);
        }
    }

    /**
     * Update file modified time
     *
     * @param string $file The file to touch.
     */
    final public function touch($file)
    {
        // update last modified time
        if (!@touch($file) && file_exists($file)) {
            try {
                // re-store file
                $this->put_contents($file, file_get_contents($file));
            } catch (Exception $e) {
                throw new \Exception('Failed to update file modified time for ' . $this->safe_path($file));
            }
        }

        return true;
    }

    /**
     * Get JSON file contents
     *
     * @param  string $file The file to load.
     * @return mixed  Decoded JSON.
     */
    final public function get_json($file, $assoc = false)
    {
        if (!file_exists($file)) {
            return false;
        }

        // get file contents
        $json = file_get_contents($file);

        //throw new \Exception('Failed to parse JSON in file ' . $this->safe_path($file) . '<pre>'.$err->getMessage().'</pre>', 'file');

        // decoded data
        return $this->json->parse($json, $assoc);
    }

    /**
     * Restrict access to Advanced Optimization applicable directories.
     *
     * @param  string $path The path to verify.
     * @return bool   Path valid true/false.
     */
    final private function valid_path($path)
    {

        // valid root paths
        $valid_root_paths = array(
            $this->trailingslashit(WP_CONTENT_DIR),
            O10N_CACHE_DIR,
            $this->theme_directory
        );

        foreach ($valid_root_paths as $root) {
            if (strpos($path, $root) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Safe path for public display / printing
     *
     * @param  string $path The path to convert.
     * @return string The path without document root.
     */
    final public function safe_path($path)
    {
        $strip = false;

        if (strpos($path, ABSPATH) !== false) {
            $strip = $this->trailingslashit(ABSPATH);
        } elseif (strpos($path, O10N_CACHE_DIR) !== false) {
            $strip = $this->trailingslashit(O10N_CACHE_DIR);
        } elseif (strpos($path, dirname(ABSPATH)) !== false) {
            $strip = $this->trailingslashit(dirname(ABSPATH));
        } elseif (isset($_SERVER['DOCUMENT_ROOT']) && strpos($path, $_SERVER['DOCUMENT_ROOT']) !== false) {
            $strip = $this->trailingslashit($_SERVER['DOCUMENT_ROOT']);
        }

        return ($strip) ? str_replace($strip, '/', $path) : $path; // no document root path found
    }

    /**
     * Return directory path
     *
     * @param  string $path   The sub directory path.
     * @param  string $type   Directory base: cache or theme
     * @param  bool   $create Create directory if it does not yet exist.
     * @return string The full path in the cache or theme directory.
     */
    final public function directory_path($path, $type = 'cache', $create = true)
    {
        // multiple directories
        if (is_array($path)) {
            $path = implode(DIRECTORY_SEPARATOR, $path);
        }

        // cached result
        $pathkey = $type . ':' . $path;
        if (isset($this->directory_paths[$pathkey])) {
            return $this->directory_paths[$pathkey];
        }

        switch ($type) {
            case "cache":

                // custom CSS cache directory
                if (defined('O10N_CACHE_DIR_CSS') && strpos($path, 'css/') === 0) {
                    $path = $this->trailingslashit(O10N_CACHE_DIR_CSS) . substr($path, 4);
                } else {
                    $path = $this->trailingslashit(O10N_CACHE_DIR . $path);
                }
                $chmod = O10N_CACHE_CHMOD_DIR;

            break;
            case "theme":

                $path = $this->trailingslashit($this->trailingslashit($this->theme_directory) . $path);
                $chmod = O10N_THEME_CHMOD_DIR;

            break;
            case "themecache":

                $path = $this->trailingslashit(O10N_CACHE_DIR . 'themes/' . $this->trailingslashit(basename($this->theme_directory)) . $path);
                $chmod = O10N_CACHE_CHMOD_DIR;

            break;
            default:
                throw new Exception('Invalid directory type.', 'core');
            break;
        }


        // directory exists
        if (is_dir($path)) {
            return $this->directory_paths[$pathkey] = $path;
        }

        // do not create directory
        if (!$create) {
            return false;
        }

        try {
            // create directory
            $this->mkdir($path, $chmod, true);
        } catch (\Exception $e) {
            throw new Exception(__('Failed to create '.$type.' directory ' . $this->safe_path($path), 'o10n') . '<pre>'.$e->getMessage() . '</pre>', 'core');
        }

        $this->directory_paths[$pathkey] = $path;

        return $path;
    }
    /**
     * Return directory URL
     *
     * @param  string $path The sub directory path.
     * @param  string $type Directory base: cache or theme
     * @return string The directory url.
     */
    final public function directory_url($path, $type = 'cache', $noHost = false)
    {

        // cached result
        $pathkey = $type . ':' . $path;
        if (!isset($this->directory_urls[$pathkey])) {
            switch ($type) {
                case "cache":
                    $this->directory_urls[$pathkey] = $this->trailingslashit(O10N_CACHE_URL . $path, '/');
                break;
                case "theme":
                    $this->directory_urls[$pathkey] = $this->trailingslashit($this->trailingslashit(get_stylesheet_directory_uri(), '/') . 'o10n/' . $path, '/');
                break;
                default:
                    throw new Exception('Invalid directory type.', 'core');
                break;
            }
        }

        if ($noHost) {
            return $this->url->remove_host($this->directory_urls[$pathkey]);
        }

        return $this->directory_urls[$pathkey];
    }

    /**
     * Faster trailingslashit
     *
     * @link https://codex.wordpress.org/Function_Reference/trailingslashit
     *
     * @param string $path The path to add a trailing slash.
     */
    final public function trailingslashit($path, $separator = DIRECTORY_SEPARATOR)
    {
        return (substr($path, -1) === $separator) ? $path : $path . $separator;
    }

    /**
     * Remove trailing slashes
     *
     * @param string $path The path to add a trailing slash.
     */
    final public function un_trailingslashit($path, $separator = DIRECTORY_SEPARATOR)
    {
        $trailingslash = substr($path, -1);
        while ($trailingslash === $separator) {
            $path = substr($path, 0, -1);
            $trailingslash = substr($path, -1);
        }

        return $path;
    }

    /**
     * Return theme directory
     */
    final public function theme_directory($path = false)
    {
        if ($path) {
            if (!is_array($path)) {
                $path = array($path);
            }

            return $this->directory_path($path, 'theme');
        }

        return $this->theme_directory;
    }
}
