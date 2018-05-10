<?php
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

$module_name = $view->module->name();
$module_version = $view->module->version();

?>
<div class="wrap">

	<div class="metabox-prefs">
		<div class="wrap about-wrap" style="position:relative;">
			<div style="float:right;">
				<img src="<?php print $view->module->dir_url(); ?>admin/images/pagespeed.png" alt="File Page Cache" width="180" height="180" border="0" style="float:right;">
			</div>
			<h1><?php print $module_name; ?> <?php print $module_version; ?></h1>

			<p class="about-text" style="min-height:inherit;">Thank you for using the <?php print $module_name; ?> plugin by <a href="https://github.com/o10n-x/" target="_blank" rel="noopener" style="color:black;text-decoration:none;">Optimization.Team</a></p>
			
			<p class="about-text" style="min-height:inherit;">This plugin is a file page cache for WordPress.</p>

			<p class="about-text" style="min-height:inherit;">The plugin provides a PHP Opcache based boost function that has proven to be <a href="https://blog.graphiq.com/500x-faster-caching-than-redis-memcache-apc-in-php-hhvm-dcd26e8447ad" target="_blank">500x faster than Redis and Memcached</a> memory cache.</p>

			<!--p class="about-text info_white" style="min-height:inherit;border-color:#0073aa;background:#f1faff;"><strong><span class="dashicons dashicons-welcome-comments" style="line-height: 32px;font-size: 34px;width: inherit;color:#0073aa;"></span></strong> The optimization plugins have been removed from WordPress.org. Read the story <a href="https://github.com/o10n-x/wordpress-css-optimization/issues/4" target="_blank">here</a>.</p-->

			<p class="about-text" style="min-height:inherit;">If you are happy with the plugin, please consider to <span class="star" style="display:inline-block;vertical-align:middle;"><a class="github-button" data-manual="1" data-size="large" href="https://github.com/o10n-x/wordpress-file-page-cache" data-icon="octicon-star" data-show-count="true" aria-label="Star o10n-x/wordpress-file-page-cache on GitHub">Star</a></span> on Github.</p>
			
			<hr />

			<h3>WordPress WPO Collection</h3>

			<img src="<?php print O10N_CORE_URI; ?>admin/images/google-lighthouse.png" alt="Google Lighthouse" height="50" border="0">
			
			<p class="about-text" style="min-height:inherit;">This plugin is part of a <a href="https://github.com/o10n-x/" target="_blank">WPO plugin collection</a> that is designed to achieve perfect <a href="https://developers.google.com/web/tools/lighthouse/" target="_blank">Google Lighthouse</a> scores.</p>
			


		</div>
	</div>

</div>