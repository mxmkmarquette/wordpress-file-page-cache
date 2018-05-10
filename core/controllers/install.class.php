<?php
namespace O10n;

/**
 * Install Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Install extends Controller implements Controller_Interface
{
    private $current_version; // current plugin version

    /**
     * Load controller
     *
     * @param  Core       $Core Core controller instance.
     * @return Controller Controller instance.
     */
    public static function &load(Core $Core)
    {
        return parent::construct($Core, array(
            'cache',
            'options'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        if (!defined('O10N_CORE_VERSION')) {
            throw new Exception('Installation error. Constant O10N_CORE_VERSION missing.', 'core');
        }

        // set current version
        $this->current_version = get_option('o10n_core_version', false);

        // upgrade/install hooks
        add_action('plugins_loaded', array($this, 'upgrade'), 10);

        // activate / deactivate hooks
        add_action('o10n_plugin_activate', array($this, 'activate'), 1);
        add_action('o10n_plugin_deactivate', array($this, 'deactivate'), 1);

        // add cron shedules
        add_filter('cron_schedules', array($this,'cron_schedules'));
    }

    /**
     * Activate plugin hook
     */
    final public function activate()
    {

        // create cache tables
        $this->create_cache_tables();
    }

    /**
     * Deactivate plugin hook
     */
    final public function deactivate()
    {

        // remove crons
        wp_clear_scheduled_hook('o10n_cron_prune_cache');
        wp_clear_scheduled_hook('o10n_cron_prune_expired_cache');
    }

    /**
     * Upgrade plugin
     */
    final public function upgrade()
    {

        // initiate cache tables
        $this->cache->create_tables();

        // upgrade
        if (O10N_CORE_VERSION !== $this->current_version) {

            // define install flag
            $options = $this->options->get();
            
            // update options?
            $update_options = false;

            // update current version option
            update_option('o10n_core_version', O10N_CORE_VERSION, false);

            // upgrade actions
            if (isset($options['update_count'])) {
                update_option('o10n_update_count', $options['update_count'], false);
                
                unset($options['update_count']);
                $update_options = true;
            }

            if (!$update_options) {

              // update options
                update_option('o10n', $options, true);
            }
        }
    }

    /**
     * Add cron shedules
     *
     * @param  array $schedules Cron schedules
     * @return array Modified cron schedules
     */
    final public function cron_schedules($schedules)
    {
        if (!isset($schedules["30min"])) {
            $schedules["30min"] = array(
                'interval' => 30 * 60,
                'display' => __('Once every 30 minutes'));
        }

        return $schedules;
    }
}
