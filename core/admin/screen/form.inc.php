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

// editor
$editor = get_user_meta($user->ID, 'o10n_editor', true);
if (!$editor) {
    $editor = 'form';
}

// auto save
$autosave = get_user_meta($user->ID, 'o10n_autosave', true);

?>
<fieldset>
<legend>Default Editor</legend>
<div class="metabox-prefs">
    <div class="o10n_custom_fields">
        <select name="o10n_screen[editor]">
            <option value="form"<?php print(($editor === 'form') ? ' selected' : ''); ?>>Form</option>
            <option value="json"<?php print(($editor === 'json') ? ' selected' : ''); ?>>JSON Tree</option>
            <option value="json-code"<?php print(($editor === 'json-code') ? ' selected' : ''); ?>>JSON Code</option>
        </select>
    </div>
</div>
</fieldset>
<br class="clear">
<div>
    <label><input type="checkbox" name="o10n_screen[autosave]" value="1" <?php print(($autosave) ? ' checked' : ''); ?> /> Auto-save profile on changes.</label>
</div>
<br class="clear">