<?php
/**
 * WooCommerce pages (shop, categories, single product).
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="container wc-main">
	<?php woocommerce_content(); ?>
</main>
<?php
get_footer();
