<?php
/**
 * Admin header template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

        ?>

<!-- header -->
<div class="wrap">

    <!-- hide notices from other plugins -->
    <div class="other-notices-notice" style="visibility:hidden"><strong class="count"></strong> notices from other plugins have been hidden. <a href="javascript:void(0);" class="show" data-hide="Hide notices">Show notices</a></div>
    <div class="other-notices" style="display:none;"><div class="wp-header-end"></div></div>

    <h1 class="o10n-title"><strong><?php if ($view->module) {
            print $view->module->name();
        } else {
            print 'WordPress WPO <em>- Website Performance Optimization</em>';
        } ?></strong></h1>

    <div id="o10n-notices"><?php
        /** Display optimization errors / notices */
        do_action('o10n_notices'); ?>
        </div>
</div>

<div class="wrap o10n-wrap" style="position:relative;">

<?php 
        // include navbar template
        require_once O10N_CORE_PATH . 'admin/header-navbar.inc.php';
?>
	<div id="poststuff">