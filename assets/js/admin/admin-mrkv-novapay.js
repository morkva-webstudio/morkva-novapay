jQuery(window).on('load', function() {
	/**
	 * Convert checkbox to radio
	 * */
	function check_radio_checkbox_image(){
		var black_checked =  jQuery('#woocommerce_mrkv_novapay_novapay_image_type_black').is(':checked');
		var white_checked =  jQuery('#woocommerce_mrkv_novapay_novapay_image_type_white').is(':checked');
		var mini_checked =  jQuery('#woocommerce_mrkv_novapay_novapay_image_type_mini').is(':checked');

		if(black_checked && white_checked && mini_checked){
			jQuery('#woocommerce_mrkv_novapay_novapay_image_type_white').prop( "checked", false );
		}
	}

	// Convert
	check_radio_checkbox_image();

	jQuery('#woocommerce_mrkv_novapay_novapay_image_type_black').change(function(){
		if(jQuery(this).is(':checked')){
			jQuery('#woocommerce_mrkv_novapay_novapay_image_type_white').prop( "checked", false );
			jQuery('#woocommerce_mrkv_novapay_novapay_image_type_mini').prop( "checked", false );
		}
	});
	jQuery('#woocommerce_mrkv_novapay_novapay_image_type_white').change(function(){
		if(jQuery(this).is(':checked')){
			jQuery('#woocommerce_mrkv_novapay_novapay_image_type_black').prop( "checked", false );
			jQuery('#woocommerce_mrkv_novapay_novapay_image_type_mini').prop( "checked", false );
		}
	});
	jQuery('#woocommerce_mrkv_novapay_novapay_image_type_mini').change(function(){
		if(jQuery(this).is(':checked')){
			jQuery('#woocommerce_mrkv_novapay_novapay_image_type_black').prop( "checked", false );
			jQuery('#woocommerce_mrkv_novapay_novapay_image_type_white').prop( "checked", false );
		}
	});
});