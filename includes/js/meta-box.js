jQuery( function ( $ ) {
  init_lekirpay_meta();
  $(".lekirpay_customize_lekirpay_donations_field input:radio").on("change", function() {
    init_lekirpay_meta();
  });
	
  function init_lekirpay_meta(){
    if ("enabled" === $(".lekirpay_customize_lekirpay_donations_field input:radio:checked").val()){
      $(".lekirpay_client_id_field").show();
      $(".lekirpay_lekirKey_field").show();
      $(".lekirpay_sonix_signature_key_field").show();
      $(".lekirpay_group_id_field").show();
      $(".lekirpay_server_end_point_field").show();
    } else {
      $(".lekirpay_client_id_field").hide();
      $(".lekirpay_lekirKey_field").hide();
      $(".lekirpay_sonix_signature_key_field").hide();
      $(".lekirpay_group_id_field").hide();
      $(".lekirpay_server_end_point_field").hide();
    }
  }
});