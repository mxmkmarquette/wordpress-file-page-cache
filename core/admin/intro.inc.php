<?php
namespace O10n;

/**
 * Intro admin template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */

if (!defined('ABSPATH')) {
    exit;
}

if (defined('O10N_WPO_VERSION')) {
    // redirect
}

$module_name = 'Performance Optimization';

?>
<div class="wrap">

	<div class="metabox-prefs">
		<div class="wrap about-wrap" style="position:relative;">
			<div style="float:right;">
			</div>
			<h1>Website Performance Optimization</h1>

			<p class="about-text" style="min-height:inherit;">Thank you for using performance optimization plugins by <a href="https://optimization.team/" target="_blank" rel="noopener" style="color:black;text-decoration:none;">Optimization.Team</a>.</p>

			<p class="about-text info_white" style="min-height:inherit;border-color:#0073aa;background:#f1faff;"><strong><span class="dashicons dashicons-welcome-comments" style="line-height: 32px;font-size: 34px;width: inherit;color:#0073aa;"></span></strong> The optimization plugins have been removed from WordPress.org. Read the story <a href="https://github.com/o10n-x/wordpress-css-optimization/issues/4" target="_blank">here</a>.</p>

			<br />

			<img src="<?php print O10N_CORE_URI; ?>admin/images/google-lighthouse.png" alt="Google Lighthouse" height="50" border="0">

			<p class="about-text" style="min-height:inherit;">You are using a selection of optimization plugins that automatically merge to achieve single plugin performance. The plugins are designed to achieve perfect <a href="https://developers.google.com/web/tools/lighthouse/" target="_blank">Google Lighthouse</a> scores.</p>

			<div style="float:left; margin-right:10px;">
				<a href="https://github.com/afragen/github-updater" target="_blank" rel="noopener"><img src="<?php print O10N_CORE_URI; ?>admin/images/github-updater.png" alt="Github Updater" width="180" height="180" border="0" style="float:right;"></a>
			</div>

			<p class="about-text" style="min-height:inherit;">You can install and update the optimization plugins via <a href="https://github.com/afragen/github-updater" target="_blank">GitHub Updater</a>.</p>
			<p class="about-text" style="min-height:inherit;">The plugins are located on our GitHub profile: <a href="https://github.com/o10n-x/" target="_blank">https://github.com/o10n-x/</a></p>

			<p class="about-text" style="min-height:inherit;">We are very interested to receive feedback and feature requests. The preferred way to send us feedback is using the <a href="https://github.com/o10n-x/" target="_blank" rel="noopener">Github community forums</a>.</p>
			</div>

		</div>
	</div>

</div>