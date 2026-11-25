<?php
/**
 * Customer photos on product reviews.
 *
 *  - Up to 3 photos per review (JPG / PNG / WebP, max 5 MB each).
 *  - Only real images are accepted; each one is turned the right way up, made smaller
 *    (max 1600 px) and saved again, which also removes hidden phone data such as GPS location.
 *  - A review WITH photos waits for approval (Products → Reviews / Comments) before it shows.
 *    Reviews without photos work as before.
 *  - Deleting a review also deletes its photos.
 *
 * @package OyePick
 */

defined( 'ABSPATH' ) || exit;

const OYEPICK_RV_MAX_PHOTOS = 3;
const OYEPICK_RV_MAX_MB     = 5;

/** Photos posted with the review form, as a list of single-file arrays. */
function oyepick_rv_files() {
	// phpcs:disable WordPress.Security.NonceVerification -- comment form (no nonce for guests); files are validated below.
	if ( empty( $_FILES['op_review_photos'] ) || ! is_array( $_FILES['op_review_photos']['name'] ) ) {
		return array();
	}
	$f   = $_FILES['op_review_photos']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	// phpcs:enable
	$out = array();
	foreach ( $f['name'] as $i => $name ) {
		if ( '' === $name || UPLOAD_ERR_NO_FILE === (int) $f['error'][ $i ] ) {
			continue;
		}
		$out[] = array(
			'name'     => (string) $name,
			'type'     => (string) $f['type'][ $i ],
			'tmp_name' => (string) $f['tmp_name'][ $i ],
			'error'    => (int) $f['error'][ $i ],
			'size'     => (int) $f['size'][ $i ],
		);
	}
	return $out;
}

function oyepick_rv_is_product_review( $data ) {
	return ! empty( $data['comment_post_ID'] ) && 'product' === get_post_type( (int) $data['comment_post_ID'] ) && empty( $data['comment_parent'] );
}

/** Real image type of an uploaded file ('' if it is not a JPG/PNG/WebP image). */
function oyepick_rv_mime( $tmp ) {
	$info = @getimagesize( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$mime = $info ? $info['mime'] : '';
	return in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ? $mime : '';
}

/* 1. Check the photos before the review is saved. */
add_filter(
	'preprocess_comment',
	function ( $data ) {
		if ( ! oyepick_rv_is_product_review( $data ) ) {
			return $data;
		}
		$files = oyepick_rv_files();
		if ( ! $files ) {
			return $data;
		}
		$back = array( 'back_link' => true, 'response' => 400 );
		if ( count( $files ) > OYEPICK_RV_MAX_PHOTOS ) {
			/* translators: %d: max number of photos */
			wp_die( esc_html( sprintf( __( 'You can add up to %d photos to a review.', 'oyepick' ), OYEPICK_RV_MAX_PHOTOS ) ), esc_html__( 'Review photos', 'oyepick' ), $back );
		}
		foreach ( $files as $f ) {
			$ok = UPLOAD_ERR_OK === $f['error'] && is_uploaded_file( $f['tmp_name'] ) && $f['size'] <= OYEPICK_RV_MAX_MB * MB_IN_BYTES && oyepick_rv_mime( $f['tmp_name'] );
			if ( ! $ok ) {
				/* translators: 1: file name, 2: max size in MB */
				wp_die( esc_html( sprintf( __( '"%1$s" could not be added. Please use JPG, PNG or WebP photos of up to %2$d MB each.', 'oyepick' ), $f['name'], OYEPICK_RV_MAX_MB ) ), esc_html__( 'Review photos', 'oyepick' ), $back );
			}
		}
		return $data;
	},
	5
);

/* 2. A review with photos waits for approval (unless the shop owner posts it). */
add_filter(
	'pre_comment_approved',
	function ( $approved, $data ) {
		if ( 1 === $approved || '1' === $approved ) {
			if ( oyepick_rv_is_product_review( $data ) && oyepick_rv_files() && ! current_user_can( 'moderate_comments' ) ) {
				return 0;
			}
		}
		return $approved;
	},
	20,
	2
);

/** Remove hidden data (EXIF / GPS) from a saved image file. */
function oyepick_rv_strip_meta( $file, $mime ) {
	if ( class_exists( 'Imagick' ) ) {
		try {
			$im = new Imagick( $file );
			$im->stripImage();
			$im->writeImage( $file );
			$im->clear();
			return;
		} catch ( Exception $e ) {
			unset( $e ); // fall back to GD below
		}
	}
	if ( 'image/jpeg' === $mime && function_exists( 'imagecreatefromjpeg' ) ) {
		$gd = @imagecreatefromjpeg( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( $gd ) {
			imagejpeg( $gd, $file, 85 ); // GD never writes EXIF
			imagedestroy( $gd );
		}
	}
}

/* 3. Save the photos once the review exists. */
add_action(
	'comment_post',
	function ( $comment_id, $approved, $data ) {
		if ( 'spam' === $approved || 'trash' === $approved || ! oyepick_rv_is_product_review( $data ) ) {
			return;
		}
		$files = oyepick_rv_files();
		if ( ! $files ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$exts = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' );
		$ids  = array();
		foreach ( array_slice( $files, 0, OYEPICK_RV_MAX_PHOTOS ) as $n => $f ) {
			$mime = oyepick_rv_mime( $f['tmp_name'] );
			if ( ! $mime ) {
				continue;
			}
			$f['name'] = 'review-' . (int) $comment_id . '-' . ( $n + 1 ) . '.' . $exts[ $mime ];
			$f['type'] = $mime;
			$up        = wp_handle_upload(
				$f,
				array(
					'test_form' => false,
					'mimes'     => array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ),
				)
			);
			if ( ! empty( $up['error'] ) || empty( $up['file'] ) ) {
				continue;
			}
			// Right way up, max 1600 px, smaller file.
			$ed = wp_get_image_editor( $up['file'] );
			if ( ! is_wp_error( $ed ) ) {
				$ed->maybe_exif_rotate();
				$size = $ed->get_size();
				if ( $size['width'] > 1600 || $size['height'] > 1600 ) {
					$ed->resize( 1600, 1600, false );
				}
				$ed->set_quality( 82 );
				$ed->save( $up['file'], $mime );
			}
			oyepick_rv_strip_meta( $up['file'], $mime );

			$aid = wp_insert_attachment(
				array(
					'post_mime_type' => $mime,
					/* translators: 1: photo number, 2: review id */
					'post_title'     => sprintf( __( 'Review photo %1$d (review #%2$d)', 'oyepick' ), $n + 1, $comment_id ),
					'post_status'    => 'inherit',
					'post_author'    => 0,
				),
				$up['file'],
				(int) $data['comment_post_ID']
			);
			if ( ! $aid || is_wp_error( $aid ) ) {
				wp_delete_file( $up['file'] );
				continue;
			}
			wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $up['file'] ) );
			update_post_meta( $aid, '_op_review_photo', (int) $comment_id );
			$ids[] = (int) $aid;
		}
		if ( $ids ) {
			update_comment_meta( $comment_id, 'op_photos', $ids );
		}
	},
	10,
	3
);

/* 4. Deleting a review deletes its photos too. */
add_action(
	'delete_comment',
	function ( $comment_id ) {
		foreach ( (array) get_comment_meta( $comment_id, 'op_photos', true ) as $aid ) {
			if ( (int) $aid && (int) get_post_meta( (int) $aid, '_op_review_photo', true ) === (int) $comment_id ) {
				wp_delete_attachment( (int) $aid, true );
			}
		}
	}
);

/** Thumbnails for a review (click opens the big photo). */
function oyepick_review_photos_html( $comment_id ) {
	$ids = array_filter( array_map( 'intval', (array) get_comment_meta( $comment_id, 'op_photos', true ) ) );
	if ( ! $ids ) {
		return '';
	}
	$h = '<div class="op-rv-photos">';
	foreach ( $ids as $aid ) {
		$thumb = wp_get_attachment_image_url( $aid, 'thumbnail' );
		$big   = wp_get_attachment_image_url( $aid, 'large' );
		if ( $thumb ) {
			$h .= '<a class="op-rv-ph" href="' . esc_url( $big ? $big : $thumb ) . '" target="_blank" rel="noopener"><img src="' . esc_url( $thumb ) . '" alt="' . esc_attr__( 'Customer photo', 'oyepick' ) . '" width="150" height="150" loading="lazy"></a>';
		}
	}
	return $h . '</div>';
}

/* 5. Show the photos in the admin review / comment list, so they can be checked before approving. */
add_filter(
	'comment_text',
	function ( $text, $comment = null ) {
		if ( is_admin() && $comment instanceof WP_Comment ) {
			$html = oyepick_review_photos_html( $comment->comment_ID );
			if ( $html ) {
				$text .= str_replace( '<div class="op-rv-photos">', '<div class="op-rv-photos" style="display:flex;gap:8px;margin-top:8px">', str_replace( 'width="150" height="150"', 'width="80" height="80" style="border-radius:8px;object-fit:cover"', $html ) );
			}
		}
		return $text;
	},
	10,
	2
);

/* 6. Product page: file field helper script (form upload type, previews, limits) and a simple photo viewer. */
add_action(
	'wp_footer',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$max  = OYEPICK_RV_MAX_PHOTOS;
		$mb   = OYEPICK_RV_MAX_MB;
		$msg1 = esc_js( sprintf( /* translators: %d: max photos */ __( 'You can add up to %d photos.', 'oyepick' ), $max ) );
		$msg2 = esc_js( sprintf( /* translators: %d: max MB */ __( 'Each photo can be up to %d MB.', 'oyepick' ), $mb ) );
		$msg3 = esc_js( __( 'Please choose photos only (JPG, PNG or WebP).', 'oyepick' ) );
		?>
		<script>
		(function () {
			var form = document.getElementById('commentform');
			var input = document.getElementById('op_review_photos');
			if (form && input) {
				form.setAttribute('enctype', 'multipart/form-data');
				var prev = document.getElementById('op_ph_prev');
				input.addEventListener('change', function () {
					var files = [].slice.call(input.files || []);
					var err = files.length > <?php echo (int) $max; ?> ? '<?php echo $msg1; // phpcs:ignore ?>'
						: files.some(function (f) { return !/^image\/(jpeg|png|webp)$/.test(f.type); }) ? '<?php echo $msg3; // phpcs:ignore ?>'
						: files.some(function (f) { return f.size > <?php echo (int) $mb; ?> * 1048576; }) ? '<?php echo $msg2; // phpcs:ignore ?>' : '';
					if (err) { alert(err); input.value = ''; files = []; }
					prev.innerHTML = '';
					files.forEach(function (f) {
						var img = document.createElement('img');
						img.src = URL.createObjectURL(f); img.alt = '';
						prev.appendChild(img);
					});
				});
			}
			document.addEventListener('click', function (e) {
				var a = e.target.closest && e.target.closest('.op-rv-ph');
				if (!a) return;
				e.preventDefault();
				var box = document.createElement('div');
				box.className = 'op-lightbox';
				box.innerHTML = '<img alt=""><button type="button" aria-label="Close">✕</button>';
				box.querySelector('img').src = a.getAttribute('href');
				var close = function () { box.remove(); document.removeEventListener('keydown', esc); };
				var esc = function (k) { if (k.key === 'Escape') close(); };
				box.addEventListener('click', close);
				document.addEventListener('keydown', esc);
				document.body.appendChild(box);
			});
		})();
		</script>
		<?php
	},
	30
);
