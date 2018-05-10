<?php
namespace O10n;

/**
 * Admin Global Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminGlobal extends Controller implements Controller_Interface
{

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
            'cache'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // add admin bar menu
        add_action('admin_bar_menu', array( $this, 'admin_bar'), 100);
    }
     
    /**
     * Admin bar option
     *
     * @param  object       Admin bar object
     */
    final public function admin_bar($admin_bar)
    {
     
        // add optimization menu
        if (defined('O10N_WPO_VERSION') || count($this->core->modules()) > 1) {
            $admin_bar->add_menu(array(
                'id' => 'o10n',
                'title' => '<span class="ab-label">' . __('o10n', 'o10n') . '</span>',
                'href' => add_query_arg(array( 'page' => 'o10n' ), admin_url('admin.php')),
                'meta' => array( 'title' => __('Performance Optimization', 'o10n') ) // , 'class' => 'ab-sub-secondary'
            ));

            // get cache stats
            $stats = $this->cache->stats();
            if (!isset($stats['total']) || $stats['total']['size'] === 0) {
                $cache_size = ' ('.__('Empty', 'o10n').')';
            } else {
                $cache_size = ' ('.size_format($stats['total']['size'], 2).')';
            }

            $admin_bar->add_menu(array(
                'parent' => 'o10n',
                'id' => 'o10n-cache',
                'title' => __('Cache', 'o10n') . $cache_size,
                'href' => '#',
                'meta' => array( 'title' => __('Plugin Cache Management', 'o10n'), 'class' => 'ab-sub-secondary', 'onclick' => 'return false;' )
            ));

            $admin_bar->add_menu(array(
                'parent' => 'o10n-cache',
                'id' => 'o10n-cache-flush',
                'title' => '<span class="dashicons dashicons-trash o10n-menu-icon"></span> Flush all caches',
                'href' => $this->cache->flush_url()
            ));

            $admin_bar->add_menu(array(
                'parent' => 'o10n-cache',
                'id' => 'o10n-cache-reset',
                'title' => '<span class="dashicons dashicons-update o10n-menu-icon"></span> Update cache stats',
                'href' => $this->cache->flush_url('reset')
            ));
        }
    }
}
