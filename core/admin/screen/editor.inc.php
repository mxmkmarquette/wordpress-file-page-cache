<?php
namespace O10n;

/**
 * Form / JSON editor Screen Options HTML
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

// load themes
$themes = $controller->get_themes();

// editor
$editor_theme = get_user_meta($user->ID, 'o10n_editor_theme', true);
if (!$editor_theme) {
    $editor_theme = 'default';
}

// auto lint
$autolint = get_user_meta($user->ID, 'o10n_autolint', true);

?>
<fieldset>
<legend>Editor Theme</legend>
<div class="metabox-prefs">
    <div class="o10n_custom_fields">
        <select name="o10n_screen[editor_theme]" class="codemirror_theme_select">
<?php
    foreach ($themes as $file => $title) {
        print '<option value="' . esc_attr($file) . '"' . (($file === $editor_theme) ? ' selected' : '') . '>' . esc_html($title) . '</option>';
    }
?>
        </select>
    </div>
</div>
</fieldset>
<br class="clear">