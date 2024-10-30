<?php

if (!defined('ABSPATH')) {
  exit;
}

class Give_lekirpay_Gateway {
  static private $instance;

  const QUERY_VAR           = 'lekirpay_givewp_return';
  const LISTENER_PASSPHRASE = 'lekirpay_givewp_listener_passphrase';

  private function __construct() {
    add_action('init', array($this, 'return_listener'));
    add_action('give_gateway_lekirpay', array($this, 'process_payment'));
    add_action('give_lekirpay_cc_form', array($this, 'give_lekirpay_cc_form'));
    add_filter('give_enabled_payment_gateways', array($this, 'give_filter_lekirpay_gateway'), 10, 2);

  }

  static function get_instance() {
    if (null === static::$instance) {
      static::$instance = new static();
    }

    return static::$instance;
  }

  public function give_filter_lekirpay_gateway($gateway_list, $form_id) {
    if ((false === strpos($_SERVER['REQUEST_URI'], '/wp-admin/post-new.php?post_type=give_forms'))
      && $form_id
      && !give_is_setting_enabled(give_get_meta($form_id, 'lekirpay_customize_lekirpay_donations', true, 'global'), array('enabled', 'global'))
    ) {
      unset($gateway_list['lekirpay']);
    }
    return $gateway_list;
  }

  private function create_payment($purchase_data) {


    // Setup the payment details.
	$payment_data = array(
		'price'           => $purchase_data['price'],
		'give_form_title' => $purchase_data['post_data']['give-form-title'],
		'give_form_id'    => intval( $purchase_data['post_data']['give-form-id'] ),
		'give_price_id'   => isset( $purchase_data['post_data']['give-price-id'] ) ? $purchase_data['post_data']['give-price-id'] : '',
		'date'            => $purchase_data['date'],
		'user_email'      => $purchase_data['user_email'],
		'purchase_key'    => $purchase_data['purchase_key'],
		'currency'        => give_get_currency( $purchase_data['post_data']['give-form-id'], $purchase_data ),
		'user_info'       => $purchase_data['user_info'],
		'status'          => 'pending',
		'gateway'         => 'lekirpay',
	);

	// record the pending payment
	return $payment = give_insert_payment( $payment_data );
  }

  private function get_lekirpay($purchase_data) {

    $form_id = intval($purchase_data['post_data']['give-form-id']);

    $custom_donation = give_get_meta($form_id, 'lekirpay_customize_lekirpay_donations', true, 'global');
    $status          = give_is_setting_enabled($custom_donation, 'enabled');
      
    if ($status) {
      return array(
        
        'description'         => give_get_meta($form_id, 'lekirpay_description', true, true),
        'client_id'           => give_get_meta($form_id, 'lekirpay_client_id',true),
        'lekirKey'             => give_get_meta($form_id,  'lekirpay_lekirKey',true),
        'sonix_signature_key'  => give_get_meta($form_id,  'lekirpay_sonix_signature_key',true),
        'group_id'            => give_get_meta($form_id,  'lekirpay_group_id',true),
        'end_point'           => give_get_meta($form_id, 'lekirpay_server_end_point', true),
      );
    }
    return array(
     
      'description'         => give_get_option('lekirpay_description', true),
      'client_id'           => give_get_option('lekirpay_client_id'),
      'lekirKey'             => give_get_option('lekirpay_lekirKey'),
      'sonix_signature_key'  => give_get_option('lekirpay_sonix_signature_key'),
      'group_id'            => give_get_option('lekirpay_group_id'),
      'end_point'           => give_get_option('lekirpay_server_end_point', true),
    );
  }

  public static function get_listener_url($form_id) {
    $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
    if (!$passphrase) {
      $passphrase = md5(site_url() . time());
      update_option(self::LISTENER_PASSPHRASE, $passphrase);
    }

    $arg = array(
      self::QUERY_VAR => $passphrase,
      'form_id'       => $form_id,
    );
    return add_query_arg($arg, site_url('/'));
  }

  public function process_payment($purchase_data) {

    // Validate nonce.
    give_validate_nonce($purchase_data['gateway_nonce'], 'give-gateway');

    $reference_no = $this->create_payment($purchase_data);

    // Check payment.
    if (empty($reference_no)) {
      // Record the error.
      give_record_gateway_error(__('Payment Error', 'give-lekirpay'), sprintf( /* translators: %s: payment data */
        __('Payment creation failed before sending donor to lekirpay. Payment data: %s', 'give-lekirpay'), json_encode($purchase_data)), $reference_no);
      // Problems? Send back.
      give_send_back_to_checkout();
    }
	  
	  give_update_meta( $reference_no, '_give_payment_donor_email', $purchase_data['user_email'] );
      

    $form_id     = intval($purchase_data['post_data']['give-form-id']);
    $lekirpay_key = $this->get_lekirpay($purchase_data);

    $name = $purchase_data['user_info']['first_name'] . ' ' . $purchase_data['user_info']['last_name'];

      $paymentParameter = array(
        'amount' => strval($purchase_data['price']),
        'reference_no' => $reference_no,
        'item'=> substr(trim($lekirpay_key['description']), 0, 120),
        'description' => 'Contribution',
        'email' => $purchase_data['user_email'],
        'name' => empty($name) ? $purchase_data['user_email'] : trim($name),
        'callback_url' => self::get_listener_url($form_id),
        'redirect_url' => self::get_listener_url($form_id),
        'cancel_url' => self::get_listener_url($form_id),
        );
	  
	  if(!empty($lekirpay_key['group_id'])){
				
		$paymentParameter['group_id'] = $lekirpay_key['group_id'];
				
	   }
	  

    $lekirpay_parameter = array(
      'client_id' => $lekirpay_key['client_id'],
      'lekirKey' => $lekirpay_key['lekirKey'],
      'sonix_signature_key' => $lekirpay_key['sonix_signature_key'],
      'group_id' => $lekirpay_key['group_id'],
      'end_point' => $lekirpay_key['end_point']);


     
    $connect = new lekirpayGiveWPConnect($lekirpay_key['end_point']);
    $lekirpay = new lekirpayGiveAPI($connect);

    $tokenParameter = array(
                'client_id' => $lekirpay_key['client_id'],
                'secret_code' => $lekirpay_key['lekirKey']
            );
	  
	  

    $token = $lekirpay->getToken($tokenParameter);
	  
	  
      
      if (!empty($lekirpay_key['sonix_signature_key'])) {
		  
		  list($payment_url, $rbody) = $lekirpay->sentPaymentSecure($token, $paymentParameter);
	  }else{
		  
		  list($payment_url, $rbody) = $lekirpay->sentPayment($token, $paymentParameter);
	  }
	  
	  
    

    $body = json_decode($rbody);
      
    if($body->status === 'created'){

    give_update_meta($form_id, 'lekirpay_id', $body->payment_id);
    give_update_meta($form_id, 'lekirpay_reference_no', $reference_no);

    wp_redirect($body->payment_url);
    exit;

    }else{

      // Record the error.
      give_record_gateway_error(__('Payment Error', 'give-lekirpay'), sprintf( /* translators: %s: payment data */
        __('Bill creation failed. Error message: %s', 'give-lekirpay'), json_encode($rbody)), $reference_no);
      // Problems? Send back.
      give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);



    }

  }

  public function give_lekirpay_cc_form($form_id) {
    ob_start();

    //Enable Default CC fields (billing info)
    $post_lekirpay_cc_fields       = give_get_meta($form_id, 'lekirpay_collect_billing', true);
    $post_billlz_customize_option = give_get_meta($form_id, 'lekirpay_customize_lekirpay_donations', true, 'global');

    $global_lekirpay_cc_fields = give_get_option('lekirpay_collect_billing');

    //Output CC Address fields if global option is on and user hasn't elected to customize this form's offline donation options
    if (
      (give_is_setting_enabled($post_billlz_customize_option, 'global') && give_is_setting_enabled($global_lekirpay_cc_fields))
      || (give_is_setting_enabled($post_billlz_customize_option, 'enabled') && give_is_setting_enabled($post_lekirpay_cc_fields))
    ) {
      give_default_cc_address_fields($form_id);
    }

    echo ob_get_clean();
  }

  private function publish_payment($payment_id_give, $data) {
    
	if ('publish' !== get_post_status($payment_id_give)) {
	  give_update_payment_status($payment_id_give, 'publish');
	  give_insert_payment_note($payment_id_give, "Bill ID: {$data['id']}.");
	}
  }

  public function return_listener() {
      
    if (!isset($_GET[self::QUERY_VAR])) {
      return;
    }

    $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
    if (!$passphrase) {
      return;
    }

    if ($_GET[self::QUERY_VAR] != $passphrase) {
      return;
    }

    if (!isset($_GET['form_id'])) {
      exit;
    }else{
	  $form_id = sanitize_text_field($_GET['form_id']);
	}

	  
	$custom_donation = give_get_meta($form_id, 'lekirpay_customize_lekirpay_donations', true, 'global');
    $status          = give_is_setting_enabled($custom_donation, 'enabled');
	    
    if ($status) {

	 $client_id = give_get_meta($form_id, 'lekirpay_client_id',true);
	 $lekirKey = give_get_meta($form_id,  'lekirpay_sonix_signature_key',true);
	 $end_point = give_get_meta($form_id, 'lekirpay_server_end_point', true);
		
    }else{
		
	 $client_id = give_get_option('lekirpay_client_id');
	 $lekirKey = give_get_option('lekirpay_lekirKey');
	 $end_point	= give_get_option('lekirpay_server_end_point', true);
		
	}
	  
	try {
         $data = lekirpayGiveWPConnect::afterpayment();
	} catch (Exception $e) {
		status_header(403);
		exit('Some required parameter not redirect');
	} 
	  
	  
    $payment_id = sanitize_text_field($data['payment_id']);
	  


    $connect = new lekirpayGiveWPConnect($end_point);
    $lekirpay = new lekirpayGiveAPI($connect);
	  
      $tokenParameter = array(
                'client_id' => $client_id,
                'secret_code' => $lekirKey
            );
	  
	  
    $token = $lekirpay->getToken($tokenParameter);
	  
    list($paymentID, $status) = $lekirpay->getPaymentStatus($token, $payment_id);
	  
    $payment_id_give = give_get_meta($form_id, 'lekirpay_reference_no', true);
	  
	if ($status == 'Paid') {
        $this->publish_payment($payment_id_give, $data);
    }
	  
	if ($data['type'] === 'redirect') {
		
		if ($status == 'Paid') {
			
		give_send_to_success_page();

      } else {
         $return = give_get_failed_transaction_uri('?payment-id=' . $payment_id_give);
		 wp_redirect($return);
      }
		
	}  
    exit;
  }
	
	
	
	 

	
	
	

}
Give_lekirpay_Gateway::get_instance();