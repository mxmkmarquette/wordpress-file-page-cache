<?php
namespace O10n;

/**
 * Global File Page Cache Admin Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminGlobalfilecache extends ModuleAdminController implements Module_Admin_Controller_Interface
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
            'client',
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
        // current url
        if (is_admin()
            || (defined('DOING_AJAX') && DOING_AJAX)
            || in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))
        ) {
            $currenturl = home_url();
        } else {
            $currenturl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }

        // get cache stats
        $stats = $this->cache->stats('filecache');
        if (!isset($stats['size']) || $stats['size'] === 0) {
            $cache_size = ' ('.__('Empty', 'o10n').')';
        } else {
            $cache_size = ' ('.size_format($stats['size'], 2).')';
        }

        // WPO plugin or more than 1 optimization module, add to optimization menu
        if (defined('O10N_WPO_VERSION') || count($this->core->modules()) > 1) {
            $admin_bar->add_menu(array(
                'parent' => 'o10n-cache',
                'id' => 'o10n-filecache-cache',
                'title' => 'File cache' . $cache_size,
                'href' => 'javascript:void(0);'
            ));

            $admin_base = 'admin.php';
        } else {
            $admin_bar->add_menu(array(
                'id' => 'o10n-filecache',
                'title' => '<span class="ab-label">' . __('Page Cache', 'o10n') . '</span>',
                'href' => add_query_arg(array( 'page' => 'o10n-filecache' ), admin_url('tools.php')),
                'meta' => array( 'title' => __('File Cache', 'o10n'), 'class' => 'ab-sub-secondary' )
            ));

            $admin_bar->add_menu(array(
                'parent' => 'o10n-filecache',
                'id' => 'o10n-filecache-cache',
                'title' => __('Cache', 'o10n') . $cache_size,
                'href' => '#',
                'meta' => array( 'title' => __('Plugin Cache Management', 'o10n'), 'class' => 'ab-sub-secondary', 'onclick' => 'return false;' )
            ));

            $admin_bar->add_menu(array(
                'parent' => 'o10n-filecache-cache',
                'id' => 'o10n-filecache-cache-reset',
                'title' => '<span class="dashicons dashicons-update o10n-menu-icon"></span> Update cache stats',
                'href' => $this->cache->flush_url('reset')
            ));

            $admin_base = 'themes.php';
        }

        // flush CSS cache
        $admin_bar->add_menu(array(
            'parent' => 'o10n-filecache-cache',
            'id' => 'o10n-cache-flush-filecache',
            'href' => $this->cache->flush_url('filecache'),
            'title' => '<span class="dashicons dashicons-trash o10n-menu-icon"></span> Flush file cache'
        ));
    }
}
