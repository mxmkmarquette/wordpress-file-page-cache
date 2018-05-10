<?php
namespace O10n;

/**
 * Admin Editor Screen Options Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminScreenOptionsEditor extends AdminScreenOptionsBase
{
    private $themes; // available themes

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
        // load themes
        $theme_files = new \GlobIterator($this->file->trailingslashit(O10N_CORE_PATH) . 'admin/css/codemirror/*.css');
        if (empty($theme_files)) {
            throw new Exception('No themes found for CoreMirror editor.', 'admin');
        }

        $this->themes = array(
            'default' => __('Default', 'o10n')
        );
        foreach ($theme_files as $fileinfo) {
            $filename = $fileinfo->getFilename();
            $title = str_replace('.css', '', $filename);
            switch ($title) {
                case "paraiso-light":
                    $title = 'Paraíso (Light)';
                break;
                case "paraiso-dark":
                    $title = 'Paraíso (Dark)';
                break;
                case "duotone-light":
                    $title = 'DuoTone-Light';
                break;
                case "duotone-dark":
                    $title = 'DuoTone-Dark';
                break;
                case "mdn-like":
                    $title = 'MDN-LIKE';
                break;
                case "the-matrix":
                    $title = 'The Matrix';
                break;
                case "mbo":
                    $title = 'mbo';
                break;
                case "panda-syntax":
                    $title = 'Panda Syntax';
                break;
                default:
                    $title = implode('-', array_map('ucfirst', explode('-', $title)));
                break;
            }
            $this->themes[$filename] = $title;
        }
    }

    /**
     * Return themes
     */
    public function get_themes()
    {
        return $this->themes;
    }

    /**
     * Save screen options
     *
     * @param object $user    WordPress user
     * @param array  $options Input options (POST)
     */
    public function save(&$user, &$options)
    {
        // available themes
        $editor_theme = (isset($options['editor_theme'])) ? $options['editor_theme'] : 'default';

        if (isset($this->themes[$editor_theme])) {
            update_user_meta($user->ID, 'o10n_editor_theme', $editor_theme);
        }
    }
}
