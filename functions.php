<?php
	
	if ( !function_exists( 'kwp_yape_peru_admin_script' ) ) {
		function kwp_yape_peru_admin_script() {

			if ( ! did_action( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}
			wp_enqueue_script( 'kwp-yape-peru-admin', plugins_url( '/assets/woopro.js', __FILE__ ), array( 'jquery' ), '1.1', false );
			wp_enqueue_style( 'kwp-yape-peru-admin', plugins_url( '/assets/woopro.css', __FILE__ ), array(), '1.1' );
		}
	}
	add_action( 'admin_enqueue_scripts', 'kwp_yape_peru_admin_script' );

	if ( !function_exists( 'kwp_yape_peru_payment_popup' ) ) {
		function kwp_yape_peru_payment_popup() {
			
			$options = get_option( 'woocommerce_wocommerce_yape_peru_settings' );
			?>
			<div class="popup-wrapper">
				<span class="helper"></span>
				<div class="popup-main-wrapper">
					<div class="popupCloseButton">&times;</div>
					<div class="first-step" data-price-limit="<?php echo ( isset( $options['limit_amount'] ) && !empty( $options['limit_amount'] ) ) ? esc_attr( $options['limit_amount'] ) : ''; ?>">
						<?php 
						if( isset( $options['preview_qr'] ) && !empty( $options['preview_qr'] ) ){
						?>
							<img src="<?php echo esc_url( $options['preview_qr'] ); ?>" class="popup-qr" />
							<?php if ( isset( $options['number_telephone'] ) && !empty( $options['number_telephone'] ) ) { ?>
								<span class="telephone-number"><a href="tel:<?php echo esc_attr( $options['number_telephone'] ); ?>"><?php echo __( 'Add Contact:', 'payment-qr-woo' ); ?> <?php echo esc_attr( $options['number_telephone'] ); ?></a></span>
							<?php } ?>
							<span class="price"><?php echo __( 'Amount to Pay', 'payment-qr-woo' ); ?><?php echo WC()->cart->get_cart_total(); ?></span>
							<?php if ( isset( $options['message_limit_amount'] ) && !empty( $options['message_limit_amount'] ) ) { ?>
								<p class="message-limit-amount"><?php echo esc_attr( $options['message_limit_amount'] ); ?></p>
							<?php } ?>
							<?php if ( isset( $options['front_description'] ) && !empty( $options['front_description'] ) ) { ?>
								<p><?php echo esc_html( $options['front_description'] ); ?></p>
							<?php } ?>
						<?php } ?>
						<div class="popup-price-wrapper"></div>
					</div>
					<div class="second-step">
						<form method="post" enctype="multipart/form-data" novalidate="" class="box has-advanced-upload">
							<div class="box__input">
								<input type="file" name="files" id="file" class="box__file" accept=".png, .jpg, .jpeg, .gif">
								<label for="file"><?php echo __( 'Drag and Drop File to Upload', 'payment-qr-woo' ); ?> <br/><br/> <?php echo __( 'or', 'payment-qr-woo' ); ?></label>
								<button type="submit" class="box__button"><?php echo __( 'Select File', 'payment-qr-woo' ); ?></button>
							</div>
							<input type="hidden" name="ajax" value="1">
						</form>
						<div class="error"><?php echo __( 'Please Upload Your Receipt', 'payment-qr-woo' ); ?></div>
						<img src="<?php echo plugins_url( '/assets/loader.gif', __FILE__ ) ?>" class="loader" />
						<input type="submit" name="final_order" class="finalized_order btn_submit" value="<?php echo __( 'Complete Purchase', 'payment-qr-woo' ); ?>">
					</div>
				</div>
			</div>
			<?php
		}
	}
	add_action( 'wp_footer', 'kwp_yape_peru_payment_popup' );

	if ( !function_exists( 'kwp_yape_peru_front_script' ) ) {
		function kwp_yape_peru_front_script() {

			wp_enqueue_script( 'kodewp_payment_qr', plugins_url( 'assets/woopro-front.js', __FILE__ ), array( 'jquery' ), '1.1', false );
			wp_enqueue_style( 'kodewp_payment_qr', plugins_url( 'assets/woopro-front.css', __FILE__ ) );
			wp_localize_script( 'kodewp_payment_qr', 'kwajaxurl', 
				array( 
					'ajaxurl' 	=> admin_url( 'admin-ajax.php' ),
				)
			);
			
			wp_localize_script('kodewp_payment_qr', 'kwp_translate',
				array(
					'kwp_pqr_btn_continue' => __('Continue', 'payment-qr-woo'),
					'kwp_pqr_upload_images' => __('Please only upload images', 'payment-qr-woo'),
				)
			);

		}
	}
	add_action( 'wp_enqueue_scripts', 'kwp_yape_peru_front_script' );

	function kwp_yape_peru_qr_code_upload_dir( $dir ) {

		$dir_name = 'yape-peru-qrcode';

		if ( !is_dir( $dir['basedir']."/".$dir_name ) ) {
			//Create our directory if it does not exist
			mkdir( $dir['basedir']."/".$dir_name );
			$createfile = fopen( $dir['basedir']."/".$dir_name.'/index.html', 'wb' );
		}

		return array(
			'path'	 => $dir['basedir'] . '/yape-peru-qrcode',
			'url'	 => $dir['baseurl'] . '/yape-peru-qrcode',
			'subdir' => '/yape-peru-qrcode',
		) + $dir;
	}

	if ( !function_exists( 'kwp_yape_peru_qr_code_callback' ) ) {
		function kwp_yape_peru_qr_code_callback() {

			if( ! isset( $_FILES ) ) {
				return;
			}
			
			session_start();

			foreach( $_FILES as $file ) {  
				if( is_array( $file ) ) {
			
					require_once( ABSPATH . 'wp-admin/includes/admin.php' );
					
					// Register our path override.
					add_filter( 'upload_dir', 'kwp_yape_peru_qr_code_upload_dir' );

    				$overrides = array( 'test_form' => false, 'mimes' => $allowed_file_types );

					// Do our thing. WordPress will move the file to 'uploads/yape-peru-qrcode'.
					$file_return = wp_handle_upload( $file, $overrides );

					// Set everything back to normal.
					remove_filter( 'upload_dir', 'kwp_yape_peru_qr_code_upload_dir' );
					
					if( isset( $file_return['url'] ) ) {
						$_SESSION['yape-peru-qrcode'] = $file_return['url'];
						echo 'yes';
						die();
					}
				}
			}
			echo 'no';
			die();
		}
	}
	add_action( 'wp_ajax_kwp_yape_peru_qr_code', 'kwp_yape_peru_qr_code_callback' );
	add_action( 'wp_ajax_nopriv_kwp_yape_peru_qr_code', 'kwp_yape_peru_qr_code_callback' );

	/* Add meta box for edit order */
	if ( !function_exists( 'kwp_yape_peru_meta_box' ) ) {
		function kwp_yape_peru_meta_box() {
			if (version_compare(WC_VERSION, '7.0.0', '>=')) {
				add_meta_box( 'kwp-yape-peru-meta-box', __( 'QR Code Payment Receipt', 'payment-qr-woo' ), 'kwp_yape_peru_meta_box_callback', 'woocommerce_page_wc-orders', 'normal' );
		    } else {
                 add_meta_box( 'kwp-yape-peru-meta-box', __( 'QR Code Payment Receipt', 'payment-qr-woo' ), 'kwp_yape_peru_meta_box_callback', 'shop_order', 'normal' );
		    }
		}
	}
	add_action( 'add_meta_boxes', 'kwp_yape_peru_meta_box' );

	/* Meta box callback */
	if ( !function_exists( 'kwp_yape_peru_meta_box_callback' ) ) {
		function kwp_yape_peru_meta_box_callback( $post ) {

			$yape_peru_qrcode = get_post_meta( $post->ID, 'yape-peru-qrcode', true );
			
			if ( ! empty( $yape_peru_qrcode ) && esc_url( $yape_peru_qrcode ) ) {
				echo '<a href="'.esc_url( $yape_peru_qrcode ).'" target="_blank">';
					echo '<img src="'.esc_url( $yape_peru_qrcode ).'" alt="" width="200" height="200" />';
				echo '</a>';
			}
		}
	}