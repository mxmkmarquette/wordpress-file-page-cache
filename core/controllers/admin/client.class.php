<?php
namespace O10n;

/**
 * Admin Client Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminClient extends Controller implements Controller_Interface
{
    private $config_data = array(); // script config
    private $lg_data = array(); // language phrase config

    // index keys
    private $config_index = array();
    private $config_index_ref = array(); // key references
    private $lg_index = array();
    private $lg_index_ref = array(); // key references

    private $codemirror = false; // enqueue codemirror
    private $jsoneditor = false; // enqueue codemirror
 
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
            'file'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // load config index
        $index_file = O10N_CORE_PATH . 'includes/json/admin-config.json';
        try {
            $this->config_index = $this->file->get_json($index_file, true);
        } catch (\Exception $e) {
            throw new Exception('Failed to load client config index file: ' . $this->file->safe_path($index_file) . ' ' . $e->getMessage(), 'admin');
        }

        // set config index references
        $this->config_index_ref = array();
        foreach ($this->config_index as $position => $key) {
            $this->config_index_ref[$key] = $position;
        }

        // load language index
        $index_file = O10N_CORE_PATH . 'includes/json/admin-lg.json';
        try {
            $this->lg_index = $this->file->get_json($index_file, true);
        } catch (\Exception $e) {
            throw new Exception('Failed to load client language config index file: ' . $this->file->safe_path($index_file) . ' ' . $e->getMessage(), 'admin');
        }
        if (!$this->lg_index) {
            throw new Exception('Failed to load client config index file: ' . $this->file->safe_path($index_file), 'admin');
        }

        // set config index references
        $this->lg_index_ref = array();
        foreach ($this->lg_index as $position => $key) {
            $this->lg_index_ref[$key] = $position;
        }

        // enqueue styles and scripts
        add_action('wp_enqueue_scripts', array( $this, 'enqueue' ), 10);
        add_action('admin_enqueue_scripts', array( $this, 'enqueue' ), 10);

        // print script initiation in header
        add_action('wp_head', array( $this, 'client_init' ), 100);
        add_action('admin_head', array( $this, 'client_init' ), 100);
    }

    /**
     * Return index by key reference
     *
     * @param  string $key Reference key
     * @return int    Index key
     */
    final public function index($key, $lg = false)
    {
        if ($lg) {
            if (!isset($this->lg_index_ref[$key])) {
                throw new Exception('No language index for key ' . $key, 'admin');
            }

            return $this->lg_index_ref[$key];
        } else {
            if (!isset($this->config_index_ref[$key])) {
                throw new Exception('No config index for key ' . $key, 'admin');
            }

            return $this->config_index_ref[$key];
        }
    }

    /**
     * Add client config data
     *
     * @param string $key   Config key.
     * @param mixed  $value Config data.
     */
    final public function set_config($key, $value = false)
    {
        // multiple values
        if (is_array($key)) {
            foreach ($key as $config_key => $value) {
                $this->set_config($config_key, $value);
            }

            return;
        }

        // get index key
        $index = $this->index($key);

        // store value
        $this->config_data[$index] = $value;
    }

    /**
     * Add script language data
     *
     * @param string $key   Config key.
     * @param mixed  $value Config data.
     */
    final public function set_lg($key, $value = false)
    {

        // multiple values
        if (is_array($key)) {
            foreach ($key as $config_key => $value) {
                $this->set_lg($config_key, $value);
            }

            return;
        }

        // get language index key
        $index = $this->index($key, true);

        // store value
        $this->lg_data[$index] = $value;
    }
    
    /**
     * Print script initiation in header
     */
    final public function client_init()
    {
        // skip if user is not logged in
        if (!is_admin() && !is_user_logged_in()) {
            return;
        }
    }

    /**
     * Enqueue scripts and styles
     */
    final public function enqueue()
    {
        // not in /wp-admin/
        if (!is_admin()) {
            return;
        }
        
        // skip if user is not logged in
        if (!$user = wp_get_current_user()) {
            return;
        }

        // add language to client
        $lg_index = $this->index('lg');
        $this->config_data[$lg_index] = $this->lg_data;

        // set basic init variables
        $this->set_config('core_url', O10N_CORE_URI);

        // global admin CSS
        wp_enqueue_style('o10n_global', O10N_CORE_URI . 'admin/css/global.css', false, O10N_CORE_VERSION);

        // global admin script
        wp_enqueue_script('o10n_global', O10N_CORE_URI . 'admin/js/global.js', array( 'jquery' ), O10N_CORE_VERSION);

        // admin plugin page?
        if (!isset($_GET['page']) || strpos($_GET['page'], 'o10n') !== 0) {
            wp_add_inline_script('o10n_global', 'O10N['.$this->index('init').']('.json_encode($this->config_data).');');

            return;
        }

        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-widget');

        // jquery easing for switch checkbox
        wp_enqueue_script("jquery-effects-core");
        
        $plugin_dependencies = array(
            'o10n_global',
            'jquery-ui-core',
            'jquery-ui-widget'
        );

        // global admin script
        wp_enqueue_script('o10n_cp', O10N_CORE_URI . 'admin/js/cp.js', $plugin_dependencies, O10N_CORE_VERSION);

        // include CodeMirror
        if ($this->codemirror) {

            // include javascript
            if ($this->codemirror !== 'custom-theme-only') {
                wp_enqueue_script('o10n_codemirror', O10N_CORE_URI . 'admin/js/codemirror-'.$this->codemirror.'.js', array( 'o10n_cp' ), O10N_CORE_VERSION);
            }

            // custom theme
            $editor_theme = get_user_meta($user->ID, 'o10n_editor_theme', true);
            if ($editor_theme && $editor_theme !== 'default') {

                // set editor theme
                $this->set_config('editor_theme', str_replace('.css', '', $editor_theme));

                wp_enqueue_style('o10n_codemirror_theme', O10N_CORE_URI . 'admin/css/codemirror/' . $editor_theme, false, O10N_CORE_VERSION);
            }
        }

        // include JSONEditor
        if ($this->jsoneditor) {
            wp_enqueue_script('o10n_jsoneditor', O10N_CORE_URI . 'admin/js/json-editor.js', array( 'o10n_cp' ), O10N_CORE_VERSION);
        }


        // general admin CSS
        wp_enqueue_style('o10n_cp', O10N_CORE_URI . 'admin/css/cp.css', false, O10N_CORE_VERSION);

        wp_add_inline_script('o10n_global', 'O10N['.$this->index('init').']('.json_encode($this->config_data).');');
    }

    /**
     * Enqueue CodeMirror for form view
     */
    final public function preload_CodeMirror($type)
    {
        // check view
        $this->codemirror = $type;
    }

    /**
     * Enqueue JSON editor for JSON editor view
     */
    final public function preload_JSONEditor()
    {
        // check view
        $this->jsoneditor = true;
    }
}
