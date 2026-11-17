<?php
/**
 * Pages (cart, checkout, my account, about, contact…).
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="container page-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'page-content' ); ?>>
			<h1 class="page-title"><?php the_title(); ?></h1>
			<div class="entry"><?php the_content(); ?></div>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
