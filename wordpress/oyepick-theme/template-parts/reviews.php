<?php
/**
 * Homepage "Customer reviews" column — real, approved WooCommerce product reviews only.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_product' ) ) {
	return;
}

$reviews = get_comments(
	array(
		'type'      => 'review',
		'status'    => 'approve',
		'post_type' => 'product',
		'number'    => 6,
		'orderby'   => 'comment_date_gmt',
		'order'     => 'DESC',
	)
);

global $wpdb;
$stats = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"SELECT COUNT(*) AS total, AVG(CAST(m.meta_value AS DECIMAL(3,2))) AS avg
	 FROM {$wpdb->comments} c
	 JOIN {$wpdb->commentmeta} m ON m.comment_id = c.comment_ID AND m.meta_key = 'rating'
	 JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID AND p.post_type = 'product'
	 WHERE c.comment_approved = '1' AND c.comment_type = 'review' AND m.meta_value > 0"
);
$total = $stats ? (int) $stats->total : 0;
$avg   = $stats && $stats->avg ? round( (float) $stats->avg, 1 ) : 0;

// Where "Write a review" goes: the newest product's review form.
$latest      = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );
$review_link = $latest ? get_permalink( $latest[0]->get_id() ) . '#review_form_wrapper' : oyepick_shop_url();
?>
<section class="section" id="reviews" style="padding-top:0">
	<div class="container">
		<div class="sec-head reveal">
			<div><span class="eyebrow"><?php esc_html_e( 'Customer reviews', 'oyepick' ); ?></span><h2><?php esc_html_e( 'What our customers say', 'oyepick' ); ?></h2></div>
			<a href="<?php echo esc_url( $review_link ); ?>"><?php esc_html_e( 'Write a review →', 'oyepick' ); ?></a>
		</div>

		<div class="rv-wrap">
			<aside class="rv-summary reveal">
				<?php if ( $total ) : ?>
					<b class="rv-avg"><?php echo esc_html( number_format_i18n( $avg, 1 ) ); ?></b>
					<?php echo oyepick_stars( $avg ); // phpcs:ignore ?>
					<p><?php echo esc_html( sprintf( _n( 'Based on %d review', 'Based on %d reviews', $total, 'oyepick' ), $total ) ); ?></p>
				<?php else : ?>
					<b class="rv-avg">—</b>
					<?php echo oyepick_stars( 0 ); // phpcs:ignore ?>
					<p><?php esc_html_e( 'No reviews yet. Bought from us? Your review helps other shoppers.', 'oyepick' ); ?></p>
				<?php endif; ?>
				<ul class="rv-points">
					<li><?php echo oyepick_icon( 'check', 16 ); // phpcs:ignore ?><?php esc_html_e( 'Only real customer reviews', 'oyepick' ); ?></li>
					<li><?php echo oyepick_icon( 'check', 16 ); // phpcs:ignore ?><?php esc_html_e( '"Verified buyer" when you ordered the product', 'oyepick' ); ?></li>
					<li><?php echo oyepick_icon( 'check', 16 ); // phpcs:ignore ?><?php esc_html_e( 'Add photos of your parcel', 'oyepick' ); ?></li>
				</ul>
				<a class="btn btn-primary" href="<?php echo esc_url( $review_link ); ?>"><?php esc_html_e( 'Write a review', 'oyepick' ); ?></a>
			</aside>

			<div class="rv-list">
				<?php if ( $reviews ) : ?>
					<?php
					foreach ( $reviews as $r ) :
						$rating   = (int) get_comment_meta( $r->comment_ID, 'rating', true );
						$verified = function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $r->comment_ID );
						$is_demo  = (bool) get_comment_meta( $r->comment_ID, '_oyepick_demo', true );
						$prod     = wc_get_product( $r->comment_post_ID );
						$initial  = mb_strtoupper( mb_substr( $r->comment_author, 0, 1 ) );
						?>
						<article class="rv-card reveal">
							<div class="rv-head">
								<span class="rv-av"><?php echo esc_html( $initial ); ?></span>
								<div>
									<b><?php echo esc_html( $r->comment_author ); ?></b>
									<?php if ( $is_demo ) : ?><span class="rv-demo"><?php esc_html_e( 'Demo review', 'oyepick' ); ?></span><?php elseif ( $verified ) : ?><span class="rv-ver"><?php echo oyepick_icon( 'check', 12 ); // phpcs:ignore ?><?php esc_html_e( 'Verified buyer', 'oyepick' ); ?></span><?php endif; ?>
									<small><?php echo esc_html( sprintf( __( '%s ago', 'oyepick' ), human_time_diff( strtotime( $r->comment_date_gmt . ' GMT' ) ) ) ); ?></small>
								</div>
								<?php echo $rating ? oyepick_stars( $rating ) : ''; // phpcs:ignore ?>
							</div>
							<p><?php echo esc_html( wp_trim_words( $r->comment_content, 40, '…' ) ); ?></p>
							<?php if ( $prod ) : ?>
								<a class="rv-prod" href="<?php echo esc_url( get_permalink( $prod->get_id() ) ); ?>">
									<?php echo $prod->get_image( 'woocommerce_gallery_thumbnail' ); // phpcs:ignore ?>
									<span><?php echo esc_html( $prod->get_name() ); ?></span>
								</a>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="rv-empty reveal">
						<span class="ic"><?php echo oyepick_icon( 'star', 28 ); // phpcs:ignore ?></span>
						<h3><?php esc_html_e( 'Be the first to review', 'oyepick' ); ?></h3>
						<p><?php esc_html_e( 'Reviews from our customers will appear here as soon as they are published.', 'oyepick' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
