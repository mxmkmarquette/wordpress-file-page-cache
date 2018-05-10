<?php
namespace O10n;

/**
 * Link Filter Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminMenu extends Controller implements Controller_Interface
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
            'AdminView'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {

        // admin options page
        add_action('admin_menu', array($this, 'admin_menu'), 30);

        // reorder menu
        add_filter('custom_menu_order', array($this, 'reorder_menu'), 100);
    }
    
    /**
     * Admin menu option
     */
    public function admin_menu()
    {
        global $submenu;

        // more than 1 optimization module, add to optimization menu
        if (count($this->core->modules()) > 1) {
            add_menu_page(
                __('Performance Optimization', 'o10n'),
                __('Optimization', 'o10n'),
                'manage_options',
                'o10n',
                array(
                    &$this->AdminView,
                    'display'
                ),
                $this->admin_icon(),
                80
            );
            add_submenu_page('o10n', __('JSON Profile Editor', 'o10n'), __('JSON Settings', 'o10n'), 'manage_options', 'o10n-settings', array(
                 &$this->AdminView,
                 'display'
             ));
            remove_submenu_page('o10n', 'o10n');
        } else {
            add_submenu_page(
                null,   //or 'options.php'
               __('Performance Optimization', 'o10n'),
                __('Optimization', 'o10n'),
                'manage_options',
                'o10n',
                array(
                    &$this->AdminView,
                    'display'
                )
            );

            add_submenu_page(null, __('JSON Profile Editor', 'o10n'), __('JSON Settings', 'o10n'), 'manage_options', 'o10n-settings', array(
                 &$this->AdminView,
                 'display'
             ));
        }
    }

    /**
     * Reorder menu
     */
    public function reorder_menu($menu_order)
    {
        global $submenu;

        // remove o10n submenu
        if (isset($submenu['o10n'])) {
            foreach ($submenu['o10n'] as $key => $item) {
                if ($item[2] === 'o10n') {
                    unset($submenu['o10n'][$key]);
                }
            }
        }
    }
    
    /**
     * Return admin panel SVG icon
     *
     * @param  string $color HEX color code
     * @return string SVG icon data-uri
     */
    final public function admin_icon($color = false)
    {
        $icon = file_get_contents(O10N_CORE_PATH.'public/100.svg');
        $icon = 'data:image/svg+xml;base64,'.base64_encode($this->menu_svg_color($icon, $color));

        return $icon;
    }

    /**
     * Fills menu page inline SVG icon color.
     */
    final private function menu_svg_color($svg, $color = false)
    {
        if ($color) {
            $use_icon_fill_color = $color;
        } else {
            if (!($color = get_user_option('admin_color'))) {
                $color = 'fresh';
            }

            /**
             * WordPress admin icon color schemes.
             */
            $wp_admin_icon_colors = [
                'fresh' => ['base' => '#999999', 'focus' => '#2EA2CC', 'current' => '#FFFFFF'],
                'light' => ['base' => '#999999', 'focus' => '#CCCCCC', 'current' => '#CCCCCC'],
                'blue' => ['base' => '#E5F8FF', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'],
                'midnight' => ['base' => '#F1F2F3', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'],
                'sunrise' => ['base' => '#F3F1F1', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'],
                'ectoplasm' => ['base' => '#ECE6F6', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'],
                'ocean' => ['base' => '#F2FCFF', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'],
                'coffee' => ['base' => '#F3F2F1', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'],
            ];

            if (empty($wp_admin_icon_colors[$color])) {
                return $svg;
            }
            $icon_colors = $wp_admin_icon_colors[$color];
            $use_icon_fill_color = $icon_colors['base']; // Default base.

            $current_pagenow = !empty($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '';
            $current_page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : '';

            if ($current_page && strpos($_GET['page'], 'o10n') === 0) {
                $use_icon_fill_color = $icon_colors['current'];
            }
        }
        
        return preg_replace('|(\s)fill="#000000"|Ui', '$1fill="'.esc_attr($use_icon_fill_color).'"', $svg);
    }
}
