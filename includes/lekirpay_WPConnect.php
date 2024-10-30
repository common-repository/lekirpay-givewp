<?php

class lekirpayGiveWPConnect {
	
  public $url;
  public $header;
  private $end_point;

  public function __construct($end_point) {
	  
	  if($end_point == "disabled"){
		  
		  $this->current_url = 'https://matkilau.lekirpay.com';
		  
	  }else if($end_point == "enabled"){
		  
		  $this->current_url = 'https://app.lekirpay.com';
	  }
  }

  public function getToken($tokenParameter){
    $url = $this->current_url.'/oauth/token';
    $data = array(
      'grant_type' => 'client_credentials',
      'client_id' => $tokenParameter['client_id'],
      'client_secret' => $tokenParameter['secret_code']
      );
	  
        $wp_remote_data['body'] = http_build_query($data);
        $wp_remote_data['method'] = 'POST';
        $response = \wp_remote_post($url, $wp_remote_data);
        $body = \wp_remote_retrieve_body($response);
	  
	  
        $newbody = json_decode($body);

        $token = $newbody->access_token;

        return $token;
    }
	
	public function sentPaymentSecure($token, $paymentParameter){

        $url = $this->current_url.'/api/payments/payment';
		
		 $string = 'amount'.$paymentParameter['amount']
          .'|email'.$paymentParameter['email']
          .'|item'.$paymentParameter['item']
          .'|name'.$paymentParameter['name']
          .'|callback_url'.$paymentParameter['callback_url']
          .'|reference_no'.$paymentParameter['reference_no'];
		
		$checksum = hash_hmac('sha256', $string, $sonixSignature);
		
		$paymentParameter["checksum"] =  $checksum;

        $header = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token
        );

         $wp_remote_data['headers'] = $header;
         $wp_remote_data['body'] = http_build_query($paymentParameter);
         $wp_remote_data['method'] = 'POST';

         $response = \wp_remote_post($url, $wp_remote_data);
         $body = \wp_remote_retrieve_body($response);
		
// 		var_dump($body);
// 		exit();

         //$newbody = json_decode($body);
         $payment_url = $newbody->payment_url;
         $payment_id = $newbody->payment_id;
		
		


         return array($payment_url, $body);

    }


    public function sentPayment($token, $paymentParameter){

        $url = $this->current_url.'/api/payments/payment';

        $header = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token
        );

         $wp_remote_data['headers'] = $header;
         $wp_remote_data['body'] = http_build_query($paymentParameter);
         $wp_remote_data['method'] = 'POST';

         $response = \wp_remote_post($url, $wp_remote_data);
         $body = \wp_remote_retrieve_body($response);

         //$newbody = json_decode($body);
         $payment_url = $newbody->payment_url;
         $payment_id = $newbody->payment_id;


         return array($payment_url, $body);

    }

    public function getPaymentStatus($token, $payment_id){

         $url = $this->current_url.'/api/payments/'.$payment_id.'/status';

        $header = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token
        );

        $data = array(
            'payment_id' => $payment_id
        );

         $wp_remote_data['headers'] = $header;
         $wp_remote_data['method'] = 'GET';

         $response = \wp_remote_get($url, $wp_remote_data);
         $body = \wp_remote_retrieve_body($response);

         $newbody = json_decode($body);

         $payment_id = $newbody->payment_id;
         $status = $newbody->status;


        return array($payment_id, $status);
    }

    public static function afterPayment(){

      if(isset($_POST['payment_id']) && isset($_POST['reference_no']) && isset($_POST['status']) && isset($_POST['type'])) {

            $data = array(            
                'payment_id' => sanitize_text_field($_POST['payment_id']),
                'reference_no' => sanitize_text_field($_POST['reference_no']),
                'status' => sanitize_text_field($_POST['status']),
                'type' => sanitize_text_field($_POST['type']),
            );
		  
		  return $data;
        }else{
		  return false;
		}
		
    }
}