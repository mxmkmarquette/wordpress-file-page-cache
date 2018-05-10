<?php
namespace O10n;

/**
 * Frontend Client Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Client extends Controller implements Controller_Interface
{
    // HTML to print at position
    private $at = array();
    private $after = array();

    // config replacement string
    private $replacement_string = 'O10N_JS';

    private $loaded_modules = array(); // modules to load
    private $loaded_config = array(); // client config

    // module key refereces
    private $modules = array(
        'localstorage',
        'proxy',
        'responsive',
        'inview',
        'timed-exec'
    );

    // automatically load dependencies
    private $module_dependencies = array();

    // javascript client config index
    private $config_index;
    private $subconfig_index;

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
            'env',
            'file',
            'url',
            'env',
            'cache',
            'options',
            'output'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // load index
        $index_file = O10N_CORE_PATH . 'includes/json/config-index.json';
        if (!file_exists($index_file)) {
            throw new Exception('Client config schema missing.', 'core');
        }

        // load index from cache
        $cachehash = md5('config-index:' . O10N_CORE_VERSION);
        if (!$this->env->is_dev() && $this->cache->exists('core', 'config_index', $cachehash)) {

            // preserve cache file based on access
            $this->cache->preserve('core', 'config_index', $cachehash, (time() - 3600));

            $config_index = $this->cache->get('core', 'config_index', $cachehash, false, true);
            if ($config_index && count($config_index) === 3 && $config_index[2] === filemtime($index_file)) {
                $this->config_index = $config_index[0];
                $this->subconfig_index = $config_index[1];
            }
        }

        // load index from JSON files
        if (!$this->config_index) {
            $config_index = $this->file->get_json($index_file, true);
            if ($config_index === false) {
                throw new Exception('Client config index contains invalid JSON', 'core');
            }

            // convert to key index
            $this->config_index = array();
            $this->subconfig_index = array();
            foreach ($config_index as $index => $key) {
                $subconfig_index_file = O10N_CORE_PATH . 'includes/json/config-'.strtolower($key).'-index.json';
                if (!file_exists($subconfig_index_file)) {
                    throw new Exception('Client config schema missing: '.esc_attr($key).'.', 'core');
                }
                $subconfig_index = $this->file->get_json($subconfig_index_file, true);
                if ($subconfig_index === false) {
                    throw new Exception('Client config index contains invalid JSON: '.esc_attr($key), 'core');
                }

                $key = strtoupper($key);

                $this->config_index[$key] = $index;
                $this->subconfig_index[$key] = array();

                foreach ($subconfig_index as $subindex => $subkey) {
                    $subkey = strtoupper($subkey);
                    $this->subconfig_index[$key][$subkey] = $subindex;
                }
            }

            // save cache (PHP 7 Opcache)
            $this->cache->put('core', 'config_index', $cachehash, array($this->config_index,$this->subconfig_index,filemtime($index_file)), false, false, true);
        }


        // disabled
        if (!$this->env->is_optimization()) {
            return;
        }

        // include client header slot in HTML
        $header_priority = apply_filters('o10n_header_priority', $this->first_priority);
        add_action('wp_head', array( $this, 'header' ), $header_priority);

        // include client footer slot in HTML
        $footer_priority = apply_filters('o10n_footer_priority', PHP_INT_MAX - 1);
        add_action('wp_footer', array( $this, 'footer' ), $footer_priority);

        // add client to HTML
        add_filter('o10n_html', array( $this, 'add_html' ), 10, 1);

        // set client config
        $cache_path = $this->url->remove_host($this->file->directory_url(''));
        if ($cache_path === '/wp-content/cache/o10n/') {
            $cache_path = '__O10N_NULL__'; // default directory
        }
        $this->set_config('global', 'cache_path', $cache_path);
    }

    /**
     * Add module definitions
     *
     * @param array $modules      Modules to add
     * @param array $dependencies Module dependencies
     */
    final public function add_module_definitions($modules, $dependencies)
    {
        $this->modules = array_merge($this->modules, $modules);
        $this->module_dependencies = array_merge($this->module_dependencies, $dependencies);
    }

    /**
     * Add client meta slot to HTML
     */
    final public function header()
    {
        print '<meta rel="o10n_head" />';
    }

    /**
     * Add client meta slot to HTML
     */
    final public function footer()
    {
        print '<script data-o10n>o10n.f();</script>';
    }

    /**
     * Add client to HTML
     *
     * @param  string $HTML HTML buffer.
     * @return string Modified HTML buffer.
     */
    final public function add_html($HTML)
    {
        // no <head> tag, skip
        if (stripos($HTML, '<head>') === false && stripos($HTML, '<head ') === false) {
            return $HTML;
        }

        // get client HTML
        $client_html = $this->get_html();

        // regex
        $head_meta_regex = '/(<head[^>]*>)(\s*<meta[^>]+charset\s*=[^>]+>)?/is';
        $o10n_slot_regex = '/<meta[^>]+o10n_head[^>]+>/is';
        $charset_meta_regex = '/(<meta[^>]+charset\s*=\s*("|\'|)([^"\'>]+)("|\'|\s|)[^>]*>)/i';

        // detect head
        if (preg_match($head_meta_regex, $HTML, $out)) {

            // detect charset from meta
            if (!isset($out[2]) || !$out[2]) {
                if (preg_match($charset_meta_regex, $HTML, $charset_out)) {
                    $charset = '<meta charset="' . $charset_out[3] . '">';
                } else {
                    $charset = '<meta charset="UTF-8">';
                }
            } else {
                $charset = '';
            }

            // escape regex results
            if (strpos($client_html, '$1')) {
                $client_html = preg_replace('|(\$\d+)|', '\\\\$1', $client_html);
            }

            // insert client after head
            $HTML = preg_replace($head_meta_regex, '$1$2' . $charset . $client_html, $HTML, 1);

            // remove o10n meta slot
            $this->output->add_search_replace($o10n_slot_regex, '', true);
        } elseif (preg_match($o10n_slot_regex, $HTML, $out)) { // try o10n meta slot
            $this->output->add_search_replace($out[0], $client_html);
        }
        
        return $HTML;
    }

    /**
     * Return javascript client HTML
     *
     * @return string Client HTML
     */
    final private function get_html()
    {
        // HTML to return for position
        $client_html = '';

        // print at critical-css position
        if (isset($this->at['critical-css']) && !empty($this->at['critical-css'])) {
            $client_html .= implode('', $this->at['critical-css']);
        }

        // print after critical-css position
        if (isset($this->after['critical-css']) && !empty($this->after['critical-css'])) {
            $client_html .= implode('', $this->after['critical-css']);
        }

        // construct client
        if (!empty($this->loaded_modules)) {
            ksort($this->loaded_modules);
 
            // debug mode
            $js_ext = (defined('O10N_DEBUG') && O10N_DEBUG) ? '.debug.js' : '.js';

            // base client
            $o10n_client_base = O10N_CORE_PATH . 'public/js/o10n' . $js_ext;
            if (!file_exists($o10n_client_base)) {
                throw new Exception('Client source not found: ' . $this->file->safe_path($o10n_client_base), 'core', true);
            }
            $o10n_client_base_hash = md5_file($o10n_client_base);

            $client_sources = array();
            $client_sources[O10N_CORE_VERSION] = array(
                $o10n_client_base
            );

            // load module sources
            foreach ($this->loaded_modules as $module) {
                if ($module[1] !== O10N_CORE_VERSION) {
                    if (!isset($client_sources[$module[1]])) {

                        // add base source for client version
                        $client_sources[$module[1]] = array(
                            $module[1] . 'public/js/o10n' . $js_ext
                        );
                    }
                }

                // add module to sources
                $client_sources[$module[1]][] = $module[2] . 'public/js/' . $module[0] . $js_ext;
            }

            // build IIFE from client sources
            $iife_clients = array();
            $multi_client_hint = (count($client_sources) > 1 && function_exists('is_user_logged_in') && is_user_logged_in());
            foreach ($client_sources as $version => $sources) {
                $iife_clients[$version] = '!function('.(($multi_client_hint) ? '/* '.$version.' */' : '').'){';
                foreach ($sources as $source) {
                    if (file_exists($source)) {
                        $iife_clients[$version] .= file_get_contents($source);
                    }
                }
                $iife_clients[$version] .= '}();';
            }

            // show hint when using multiple client versions
            $client_script = (($multi_client_hint) ? "/* @hint upgrade optimization plugins to reduce client size */\n" : '') . implode(" ", $iife_clients);

            // client config
            $client_config = $this->get_config();

            // link client as file
            if ($this->options->bool('client.link')) {
                $client_html .= '<script data-o10n=\''.str_replace('\'', '&#39;', $client_config).'\' src="'.$fileurl.'"></script>';
            } else {
                // add client inline
                $client_html .= '<script data-o10n=\''.str_replace('\'', '&#39;', $client_config).'\'>' . $client_script . '</script>';
            }
        }

        // print after client position
        if (isset($this->after['client']) && !empty($this->after['client'])) {
            $client_html .= implode('', $this->after['client']);
        }

        return $client_html;
    }

    final private function config_fill_empty($n, $index)
    {
        $empty_params = array();
        if ($n < $index) {
            $count = ($index - $n);
            if ($count > 2) { // more than 2 empty positions, use repeat compression
                $empty_params[] = '__O10N_NULL:'.$count.'__';
            } else {
                for ($i = 0; $i < $count; $i++) {
                    $empty_params[] = '__O10N_NULL__';
                }
            }
        }

        return $empty_params;
    }

    /**
     * Return client config parameter
     */
    final public function get_config()
    {

        // enable config extension
        do_action('o10n_client_config');

        // sort config by key
        ksort($this->loaded_config);

        // construct config parameter
        $config_param = array();
        $n = 0;
        foreach ($this->loaded_config as $index => $subconfig) {
            
            // sort config by key
            ksort($subconfig);

            // empty positions
            $empty = $this->config_fill_empty($n, $index);
            if (!empty($empty)) {
                $config_param = array_merge($config_param, $empty);
            }

            $config_index = count($config_param);
            $config_param[$config_index] = array();
            $nn = 0;
            foreach ($subconfig as $subindex => $settings) {

                // empty positions
                $empty = $this->config_fill_empty($nn, $subindex);
                if (!empty($empty)) {
                    $config_param[$config_index] = array_merge($config_param[$config_index], $empty);
                }

                if (is_bool($settings)) {
                    $settings = ($settings) ? '__O10N_TRUE__' : '__O10N_FALSE__';
                } elseif (is_array($settings)) {
                    foreach ($settings as $key => $value) {
                        if (is_bool($value)) {
                            $settings[$key] = ($value) ? '__O10N_TRUE__' : '__O10N_FALSE__';
                        }
                    }
                }

                $config_param[$config_index][] = $settings;
                $nn = ($subindex + 1);
            }
            $n = ($index + 1);
        }

        // compress config parameter
        $config_param = preg_replace(array('|"__O10N_NULL__"|','|"__O10N_NULL:(\d+)__"|','|"__O10N_TRUE__"|','|"__O10N_FALSE__"|'), array('ø', 'ø:$1','µ','¬'), json_encode($config_param, true));

        //$config_param = str_replace('\\', '\\\\', $config_param);

        return $config_param;
    }

    /**
     * Set configuration
     *
     * @param string $key   Config key
     * @param mixed  $value Config value
     */
    final public function set_config($key, $subkey, $value)
    {
        // config index key
        try {
            $config_index = $this->config_index($key);
            $subconfig_index = $this->config_index($key, $subkey);
        } catch (Exception $err) {
            return false;
        }

        if (!isset($this->loaded_config[$config_index])) {
            $this->loaded_config[$config_index] = array();
        }

        $this->loaded_config[$config_index][$subconfig_index] = $value;
    }

    /**
     * Load client module
     *
     * @param string $module Module to load
     */
    final public function load_module($module, $core_version = false, $module_path = false)
    {
        if (!$core_version) {
            $core_version = O10N_CORE_VERSION;
        }
        if (!$module_path) {
            $module_path = O10N_CORE_PATH;
        }

        // multiple modules
        if (is_array($module)) {
            foreach ($module as $mod) {
                $this->load_module($mod, $core_version, $module_path);
            }

            return;
        }

        // verify module
        if (!in_array($module, $this->modules)) {
            throw new Exception('Invalid client module key: ' . esc_html($module), 'client');
        }

        $module_index = array_search($module, $this->modules);

        // module already loaded
        if (isset($this->loaded_modules[$module_index])) {
            return;
        }

        // auto load module dependencies
        $dependencies = (isset($this->module_dependencies[$module])) ? $this->module_dependencies[$module] : false;
        if ($dependencies) {
            foreach ($dependencies as $dependency) {
                $this->load_module($dependency, $core_version, $module_path);
            }
        }

        // add module
        $this->loaded_modules[$module_index] = array(
            $module,
            $core_version,
            $module_path
        );
    }

    /**
     * Get config index
     *
     * @param string $key Config key
     * @param int  Config index
     */
    final public function config_index($key, $subkey = false)
    {
        // uppercase
        $key = strtoupper($key);
        if (!isset($this->config_index[$key]) || !isset($this->subconfig_index[$key])) {
            throw new Exception('Invalid client configuration key: ' . esc_html($key), 'client');
        }

        // return subkey
        if ($subkey) {
            $subkey = strtoupper($subkey);
            if (!isset($this->subconfig_index[$key][$subkey])) {
                throw new Exception('Invalid client configuration key: ' . esc_html($key . ':' . $subkey), 'client');
            }

            return $this->subconfig_index[$key][$subkey];
        }

        return $this->config_index[$key];
    }

    /**
     * Convert array keys to indexes
     *
     * @param  array $array Array
     * @param  array $keys  Config keys
     * @return array Array with keys replaced by indexes
     */
    final public function config_array_key_index($array, $keys, $recursive = false)
    {
        if (!is_array($array)) {
            throw new Exception('$array not array', 'client');
        }

        $result = array();

        foreach ($array as $index_key => $value) {

            // try keys
            foreach ($keys as $key) {
                $key = strtoupper($key);
                $subkey = strtoupper($index_key);
                if (isset($this->subconfig_index[$key]) && isset($this->subconfig_index[$key][$subkey])) {
                    $index_key = $this->subconfig_index[$key][$subkey];
                    break 1;
                }
            }

            // recursive
            if ($recursive && is_array($value)) {
                $value = $this->config_array_key_index($value, $keys, $recursive);
            }

            $result[$index_key] = $value;
        }

        return $result;
    }

    /**
     * Convert array value to indexes
     *
     * @param  array $array Array
     * @param  array $keys  Config keys
     * @return array Array with keys replaced by indexes
     */
    final public function config_array_value_index($array, $keys, $recursive = false)
    {
        if (!is_array($array)) {
            throw new Exception('$array not array', 'client');
        }

        $result = array();

        foreach ($array as $index_key => $value) {

            // recursive
            if ($recursive && is_array($value)) {
                $value = $this->config_array_value_index($value, $keys, $recursive);
            } elseif (is_string($value) || is_numeric($value)) {

                // try keys
                foreach ($keys as $key) {
                    $key = strtoupper($key);
                    $subkey = strtoupper((string)$value);
                    if (isset($this->subconfig_index[$key]) && isset($this->subconfig_index[$key][$subkey])) {
                        $value = $this->subconfig_index[$key][$subkey];
                        break 1;
                    }
                }
            }

            $result[$index_key] = $value;
        }

        return $result;
    }

    /**
     * Return configuration array
     *
     * @param mixed $value Config value
     * @param array $keys  Config keys
     */
    final public function config_array_data($value, $key, $subkeys, $ignoreNull = true)
    {
        if (!is_array($value)) {
            throw new Exception('Config value not array', 'client');
        }

        $data_row = array();
        foreach ($keys as $param => $row_key) {
            if ($row_key === 'JSONKEY') {
                $row_key = 'C_KEY_' . strtoupper($param);
            }

            $row_index_key = $this->config_index($key, $row_key);

            // ignore null
            if ($ignoreNull && !isset($value[$param])) {
                continue;
            }

            $data_row[$row_index_key] = (isset($value[$param])) ? $value[$param] : null;
        }

        return $data_row;
    }

    /**
     * Add client HTML at position
     *
     * @param string $position Position to insert
     * @param string $html     HTML to insert
     */
    final public function at($position, $html)
    {
        if (!isset($this->aat[$position])) {
            $this->at[$position] = array();
        }

        $this->at[$position][] = $html;
    }

    /**
     * Add client HTML after position
     *
     * @param string $position Position to insert
     * @param string $html     HTML to insert
     */
    final public function after($position, $html)
    {
        if (!isset($this->after[$position])) {
            $this->after[$position] = array();
        }

        $this->after[$position][] = $html;
    }

    /**
     * Add error to display in console
     *
     * @param string $category Error category
     * @param string $error    Error message
     */
    final public function print_exception($category, $error)
    {
        $config_index = $this->config_index('global', 'exceptions');

        if (!isset($this->loaded_config[$config_index])) {
            $this->loaded_config[$config_index] = array();
        }
        $this->loaded_config[$config_index][] = array($category,$error);
    }
}
