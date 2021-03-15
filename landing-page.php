<?php
/**
 * Plugin Name:       Landing Page
 * Description:       Map additional domains to any WordPress post or page.
 * GitHub Plugin URI: https://github.com/janboddez/landing-page
 * Version:           0.1.1
 * Author:            Jan Boddez
 * Author URI:        https://janboddez.tech
 * License:           GNU General Public License v2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       landing-page
 *
 * @author  Jan Boddez <jan@janboddez.be>
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0
 * @package Landing_Page
 */

namespace Landing_Page;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load required classes.
require dirname( __FILE__ ) . '/includes/class-filters.php';
require dirname( __FILE__ ) . '/includes/class-landing-page.php';
require dirname( __FILE__ ) . '/includes/class-options-handler.php';

$landing_page = Landing_Page::get_instance();
$landing_page->register();
