<?php
namespace O10n;

/**
 * Web Font optimization admin template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH') || !defined('O10N_ADMIN')) {
    exit;
}

$module_info = $view->module_tab_info();

?>
<form method="post" action="<?php print add_query_arg(array( 'page' => 'github-updater','tab' => 'github_updater_install_plugin' ), admin_url('options-general.php')); ?>">
<div class="wrap">

	<div class="metabox-prefs">
		<div class="wrap about-wrap" style="position:relative;">
			<h1><strong><?php print $module_info['name'];?></strong> is not installed</h1>

			<div style="float:right; margin-right:10px;">
				<a href="https://github.com/afragen/github-updater" target="_blank" rel="noopener"><img src="<?php print O10N_CORE_URI; ?>admin/images/github-updater.png" alt="Github Updater" width="180" height="180" border="0" style="float:right;"></a>
			</div>
<p class="about-text" style="min-height:inherit;">The <?php print $module_info['name'];?> plugin is not installed. You can install the plugin using <a href="https://github.com/afragen/github-updater" target="_blank">GitHub Updater</a>.</p>

<input type="hidden" name="option_page" value="github_updater_install">
<input type="hidden" name="action" value="update">
<input type="hidden" name="github_updater_branch" value="">
<input type="hidden" name="github_updater_api" value="github">
<input type="hidden" name="github_access_token" value="">
<input type="hidden" name="github_updater_repo" value="<?php print esc_attr($module_info['github']); ?>">
 <?php wp_nonce_field(); ?>
<p><button type="submit" class="button button-large button-primary" style="font-size:22px;line-height:40px;height:40px;">Install</button></p>
<p><a href="<?php print $module_info['github']; ?>" target="_blank">View plugin details</a></p>

		</div>
	</div>

</div>
</form
<?php

// print form header
