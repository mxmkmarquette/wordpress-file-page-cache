<?php
namespace O10n;

/**
 * Admin Help Tabs Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminHelp extends Controller implements Controller_Interface
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
            'env',
            'AdminView'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // add admin bar menu
        add_action('current_screen', array( $this, 'add_tabs' ), 50);
    }

    /**
     * Add Contextual help tabs.
     */
    public function add_tabs()
    {
        // on plugin page?
        if (!$this->AdminView->active()) {
            return;
        }

        // get current WP_Screen object
        $screen = get_current_screen();
        if (! $screen) {
            return;
        }

        // WPO package defaults
        $wpo_name = $module_name = __('Performance Optimization', 'o10n');
        $module_github = 'https://github.com/o10n-x/';
        $module_wordpress = 'https://profiles.wordpress.org/o10n';
        $module_docs = 'https://github.com/o10n-x/';

        // get view controller
        $view = $this->AdminView->active_controller();
        if (method_exists($view, 'help_tab')) {
            $module_help = $view->help_tab();
        } else {
            $module_help = false;
        }

        if ($module_help) {
            if (isset($module_help['name'])) {
                $module_name = $module_help['name'];
            }
            if (isset($module_help['github'])) {
                $module_github = $module_help['github'];
            }
            if (isset($module_help['wordpress'])) {
                $module_wordpress = $module_help['wordpress'];
            }
            if (isset($module_help['docs'])) {
                $module_docs = $module_help['docs'];
            }
        }

        // detect if plugin is used as module of WPO plugin
        if (defined('O10N_WPO_VERSION') && $module_name !== $wpo_name) {
            $install_type = 'module';
        } else {
            $install_type = 'plugin';
        }

        // add WPO help
        $screen->add_help_tab(array(
                'id' => 'o10n_support_tab',
                'title' => __('Help &amp; Support', 'o10n'),
                'content' =>
                    '<h2>' . __('Help &amp; Support', 'o10n') . '</h2>' .
                    '<p>' .
                        sprintf(__('For help using the %s %s, %splease read our documentation%s.', 'o10n'), $module_name, $install_type, '<a href="'.esc_url($module_docs).'" target="_blank" rel="noopener">', '</a>') .
                    '</p>' .
                    '<p>' .
                        sprintf(__('For further assistance with optimization you can use the %scommunity forum%s or consult a specialist.', 'o10n'), '<a href="'.esc_url(trailingslashit($module_github).'issues').'" target="_blank" rel="noopener">', '</a>') .
                    '</p>' .
                    '<p>' .
                        __('Before asking for help we recommend checking the community forum to see if the question has been asked before.', 'o10n') .
                    '</p>' .
                    '<p>' .
                        sprintf(__('%sDocumentation%s %sCommunity Forum%s', 'o10n'), '<a href="'.esc_url($module_docs).'" target="_blank" rel="noopener" class="button button-primary">', '</a>', '<a href="'.esc_url($module_github).'" target="_blank" rel="noopener" class="button">', '</a>') .
                    '</p>'
            ));

        $screen->add_help_tab(array(
                'id' => 'o10n_bugs_tab',
                'title' => __('Found a bug?', 'o10n'),
                'content' =>
                    '<h2>' . __('Found a bug?', 'o10n') . '</h2>' .
                    '<p>' .
                        sprintf(__('If you find a bug within the %s %s core you can create a ticket via %sGithub issues%s. To help us solve your issue, please be as descriptive as possible.', 'o10n'), $module_name, $install_type, '<a href="'.esc_url(trailingslashit($module_github).'issues').'" target="_blank" rel="noopener">', '</a>') .
                    '</p>' .
                    '<p>'  .
                        sprintf(__('%sReport a bug%s', 'o10n'), '<a href="'.esc_url(trailingslashit($module_github).'issues/new').'" target="_blank" rel="noopener" class="button button-primary">', '</a>') .
                    '</p>'

            ));

        $screen->set_help_sidebar(
                '<p><strong>' . __('For more information:', 'o10n') . '</strong></p>' .
                '<p><a href="' . esc_url('https://optimization.team/') . '" target="_blank" rel="noopener">' . __('Optimization.Team', 'o10n') . '</a></p>' .
                '<p><a href="' . esc_url('https://github.com/o10n-x/') . '" target="_blank" rel="noopener">' . __('Github Profile', 'o10n') . '</a></p>' .
                '<p><a href="' . esc_url($module_github) . '" target="_blank" rel="noopener">' . __('Github Project', 'o10n') . '</a></p>' .
                '<p><a href="' . esc_url($module_docs) . '" target="_blank" rel="noopener">' . __('Documentation', 'o10n') . '</a></p>'
            );
    }
}
