<?php
/**
 * Product reviews — OyePick layout: rating summary + write-a-review card + review cards.
 *
 * @package OyePick
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! comments_open() ) {
	return;
}

$count   = (int) $product->get_review_count();
$average = (float) $product->get_average_rating();
$counts  = $product->get_rating_counts();
$ratings = wc_review_ratings_enabled();

$reviews = get_comments(
	array(
		'post_id' => $product->get_id(),
		'status'  => 'approve',
		'type'    => 'review',
		'orderby' => 'comment_date_gmt',
		'order'   => 'DESC',
	)
);
?>
<div id="reviews" class="woocommerce-Reviews op-rv">
	<div class="op-rv-head">
		<div>
			<span class="eyebrow"><?php esc_html_e( 'Customer reviews', 'oyepick' ); ?></span>
			<h2><?php esc_html_e( 'What buyers say', 'oyepick' ); ?></h2>
		</div>
		<?php if ( $count ) : ?><a class="btn btn-primary op-rv-jump" href="#review_form_wrapper"><?php esc_html_e( 'Write a review', 'oyepick' ); ?></a><?php endif; ?>
	</div>

	<div class="op-rv-top">
		<?php if ( $ratings ) : ?>
			<aside class="op-rv-summary">
				<div class="op-rv-score">
					<b><?php echo $count ? esc_html( number_format_i18n( $average, 1 ) ) : '0.0'; ?></b>
					<div>
						<?php echo oyepick_stars( $average ); // phpcs:ignore ?>
						<small>
							<?php
							echo $count
								? esc_html( sprintf( _n( 'Based on %d review', 'Based on %d reviews', $count, 'oyepick' ), $count ) )
								: esc_html__( 'No reviews yet', 'oyepick' );
							?>
						</small>
					</div>
				</div>
				<ul class="op-rv-bars">
					<?php
					for ( $star = 5; $star >= 1; $star-- ) :
						$n   = isset( $counts[ $star ] ) ? (int) $counts[ $star ] : 0;
						$pct = $count ? round( $n / $count * 100 ) : 0;
						?>
						<li>
							<span class="lbl"><?php echo (int) $star; ?> ★</span>
							<span class="bar"><i style="width:<?php echo (int) $pct; ?>%"></i></span>
							<span class="num"><?php echo (int) $n; ?></span>
						</li>
					<?php endfor; ?>
				</ul>
				<ul class="op-rv-notes">
					<li><?php echo oyepick_icon( 'check', 15 ); // phpcs:ignore ?><?php esc_html_e( 'Only real customer reviews', 'oyepick' ); ?></li>
					<li><?php echo oyepick_icon( 'shield', 15 ); // phpcs:ignore ?><?php esc_html_e( '"Verified buyer" shown when the reviewer ordered this product', 'oyepick' ); ?></li>
				</ul>
			</aside>
		<?php endif; ?>

		<div id="review_form_wrapper" class="op-rv-form">
			<?php if ( isset( $_GET['unapproved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification -- only shows a thank-you note ?>
				<div class="op-rv-thanks" role="status"><?php echo oyepick_icon( 'check', 16 ); // phpcs:ignore ?><span><?php esc_html_e( 'Thank you! Your review has been received and will appear after a quick check.', 'oyepick' ); ?></span></div>
			<?php endif; ?>
			<?php
			$can_review = 'no' === get_option( 'woocommerce_review_rating_verification_required' ) || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() );
			if ( $can_review ) :
				$commenter = wp_get_current_commenter();
				$req       = (bool) get_option( 'require_name_email', 1 );

				$fields = array(
					'author' => '<p class="comment-form-author"><label for="author">' . esc_html__( 'Your name', 'oyepick' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label><input id="author" name="author" type="text" autocomplete="name" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" ' . ( $req ? 'required' : '' ) . ' /></p>',
					'email'  => '<p class="comment-form-email"><label for="email">' . esc_html__( 'Email (not published)', 'oyepick' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label><input id="email" name="email" type="email" autocomplete="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30" ' . ( $req ? 'required' : '' ) . ' /></p>',
				);

				$rating_field = '';
				if ( $ratings ) {
					$rating_field = '<div class="comment-form-rating"><label for="rating" id="comment-form-rating-label">' . esc_html__( 'Your rating', 'oyepick' ) . ( wc_review_ratings_required() ? ' <span class="required">*</span>' : '' ) . '</label><select name="rating" id="rating" ' . ( wc_review_ratings_required() ? 'required' : '' ) . '>
						<option value="">' . esc_html__( 'Rate&hellip;', 'oyepick' ) . '</option>
						<option value="5">' . esc_html__( 'Perfect', 'oyepick' ) . '</option>
						<option value="4">' . esc_html__( 'Good', 'oyepick' ) . '</option>
						<option value="3">' . esc_html__( 'Average', 'oyepick' ) . '</option>
						<option value="2">' . esc_html__( 'Not that bad', 'oyepick' ) . '</option>
						<option value="1">' . esc_html__( 'Very poor', 'oyepick' ) . '</option>
					</select></div>';
				}

				comment_form(
					array(
						/* translators: %s: product name */
						'title_reply'          => $count ? esc_html__( 'Write a review', 'oyepick' ) : sprintf( esc_html__( 'Be the first to review %s', 'oyepick' ), '<span>' . esc_html( get_the_title() ) . '</span>' ),
						'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title">',
						'title_reply_after'    => '</h3>',
						'comment_notes_before' => '<p class="op-rv-hint">' . esc_html__( 'Share how the product looks, its quality and your delivery experience.', 'oyepick' ) . '</p>',
						'comment_notes_after'  => '',
						'label_submit'         => esc_html__( 'Submit review', 'oyepick' ),
						'class_submit'         => 'btn btn-primary',
						'logged_in_as'         => '',
						'fields'               => $fields,
						'comment_field'        => $rating_field . '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your review', 'oyepick' ) . ' <span class="required">*</span></label><textarea id="comment" name="comment" rows="4" placeholder="' . esc_attr__( 'What did you like or dislike?', 'oyepick' ) . '" required></textarea></p>'
								. '<div class="comment-form-photos"><span class="lbl">' . esc_html__( 'Add photos', 'oyepick' ) . ' <em>' . esc_html( sprintf( /* translators: %d: max photos */ __( '(optional, up to %d)', 'oyepick' ), OYEPICK_RV_MAX_PHOTOS ) ) . '</em></span>'
								. '<label class="op-ph-btn" for="op_review_photos">' . oyepick_icon( 'plus', 16 ) . '<span>' . esc_html__( 'Choose photos', 'oyepick' ) . '</span></label>'
								. '<input type="file" id="op_review_photos" name="op_review_photos[]" accept="image/jpeg,image/png,image/webp" multiple>'
								. '<div class="op-ph-prev" id="op_ph_prev"></div>'
								. '<small>' . esc_html__( 'Reviews with photos appear after a quick check by our team.', 'oyepick' ) . '</small></div>',
					)
				);
			else :
				?>
				<h3 class="comment-reply-title"><?php esc_html_e( 'Write a review', 'oyepick' ); ?></h3>
				<p class="woocommerce-verification-required"><?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'oyepick' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $reviews ) : ?>
	<div class="op-rv-list">
		<?php
		foreach ( $reviews as $r ) :
			$rating   = (int) get_comment_meta( $r->comment_ID, 'rating', true );
			$verified = wc_review_is_from_verified_owner( $r->comment_ID );
			$is_demo  = (bool) get_comment_meta( $r->comment_ID, '_oyepick_demo', true );
			?>
			<article class="op-rv-card" id="comment-<?php echo (int) $r->comment_ID; ?>">
				<div class="top">
					<span class="av"><?php echo esc_html( mb_strtoupper( mb_substr( $r->comment_author, 0, 1 ) ) ); ?></span>
					<div class="who">
						<b><?php echo esc_html( $r->comment_author ); ?></b>
						<small><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $r->comment_date ) ) ); ?></small>
					</div>
					<?php if ( $is_demo ) : ?><span class="rv-demo"><?php esc_html_e( 'Demo review', 'oyepick' ); ?></span><?php elseif ( $verified ) : ?><span class="ver"><?php echo oyepick_icon( 'check', 12 ); // phpcs:ignore ?><?php esc_html_e( 'Verified buyer', 'oyepick' ); ?></span><?php endif; ?>
				</div>
				<?php if ( $rating ) : ?><?php echo oyepick_stars( $rating ); // phpcs:ignore ?><?php endif; ?>
				<div class="txt"><?php echo wp_kses_post( wpautop( $r->comment_content ) ); ?></div>
					<?php echo oyepick_review_photos_html( $r->comment_ID ); // phpcs:ignore -- escaped inside ?>
			</article>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>
