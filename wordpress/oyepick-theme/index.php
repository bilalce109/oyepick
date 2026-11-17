<?php
/**
 * Fallback template (blog, search, archives).
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="container page-main">
	<h1 class="page-title">
		<?php
		if ( is_search() ) {
			/* translators: %s: search query */
			printf( esc_html__( 'Search results for “%s”', 'oyepick' ), esc_html( get_search_query() ) );
		} elseif ( is_404() ) {
			esc_html_e( 'Page not found', 'oyepick' );
		} else {
			echo esc_html( wp_get_document_title() );
		}
		?>
	</h1>
	<?php if ( have_posts() ) : ?>
		<div class="post-list">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'post-item' ); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found. Try the shop instead.', 'oyepick' ); ?></p>
		<p><a class="btn btn-primary" href="<?php echo esc_url( oyepick_shop_url() ); ?>"><?php esc_html_e( 'Go to shop', 'oyepick' ); ?></a></p>
	<?php endif; ?>
</main>
<?php
get_footer();
