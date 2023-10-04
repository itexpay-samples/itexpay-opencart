<?php
class ControllerExtensionPaymentItexPay extends Controller {
  public function index() {

    $data['button_confirm'] = $this->language->get('button_confirm');

    $this->load->model('checkout/order');
    
    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    $environment = $this->config->get('payment_itexpay_environment');

    if($environment == "Live")
    { 
       $api_base_url = "https://api.itexpay.com/api/pay";
    }

    else
    { 
      $api_base_url = "https://staging.itexpay.com/api/pay";
    }
    
    
   
    $apikey = $this->config->get('payment_itexpay_api_key');

    $amount = $order_info['total'];
     

//Generating 12 unique random transaction id...
$transaction_id='';
$allowed_characters = array(1,2,3,4,5,6,7,8,9,0); 
for($i = 1;$i <= 12; $i++){ 
    $transaction_id .= $allowed_characters[rand(0, count($allowed_characters) - 1)];
 
} 


$firstname = $order_info['firstname']; 
 //Remove space between firstname...
$firstname = preg_replace('/\s+/', '', $firstname);

 $phonenumber = $order_info['telephone'];

//Remove first zero of number...
$phonenumber = ltrim($phonenumber, '0');

    //Casting number into integer...
    $phonenumber = (int)$phonenumber;

    //Customer International number...
    $phonenumber = $order_info['payment_postcode'].$phonenumber; 


$callback_url = html_entity_decode(
            $this->url->link(
                'extension/payment/itexpay/callback',
                'order_id=' . $order_info['order_id'] .
                '&token=' . $transaction_id.'&'
            )
        );

//itexpay Checkout Api Payload...
    $data = array(
    "amount"  => $amount,
    "currency" => $order_info['currency_code'],
     "redirecturl" => $callback_url,
     "customer" =>  array('email' => $order_info['email'], 
                        'first_name' =>  $firstname, 
                        'last_name' => $order_info['lastname'], 
                        'phone_number' => $phonenumber ),
          "reference" => $transaction_id,
     
);




//Encoding playload...
$json_data = json_encode($data);

//Api base URL...
 $url = $api_base_url;                                                                                                            
// Initialization of the request
$curl = curl_init();

// Definition of request's headers
curl_setopt_array($curl, array(
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYHOST => false,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_ENCODING => "json",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer ".$apikey."",
    "cache-control: no-cache",
    "content-type: application/json; charset=UTF-8",
    
  ),
   CURLOPT_POSTFIELDS => $json_data,
));

// Send request and show response
$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  //echo "API Error #:" . $err;
    //Api error if any...
    // return  $err;
     return "<h4 style=color:red>".$err."</h4>";
} else {

  
    $response_data = json_decode($response, true);
        

if (!isset($response_data['amount'])) {
    $response_data['amount'] = null;
}

else
{
    $amount = $response_data['amount'];
}

if (!isset($response_data['currency'])) {
    $response_data['currency'] = null;
}

else
{
    $currency = $response_data['currency'];
}


if (!isset($response_data['paid'])) {
    $response_data['paid'] = null;
}

else
{
    $paid = $response_data['paid'];
}

if (!isset($response_data['status'])) {
    $response_data['status'] = null;
}

else
{
    $status = $response_data['status'];
}



if (!isset($response_data['env'])) {
    $response_data['env'] = null;
}



else
{
    $env = $response_data['env'];
}

if (!isset($response_data['reference'])) {
    $response_data['reference'] = null;
}

else
{
    $reference = $response_data['reference'];
}


if (!isset($response_data['paymentid'])) {
    $response_data['paymentid'] = null;
}

else
{
    $paymentid = $response_data['paymentid'];
}

if (!isset($response_data['authorization_url'])) {
    $response_data['authorization_url'] = null;
}

else
{
    $authorization_url = $response_data['authorization_url'];
}

if (!isset($response_data['failure_message'])) {
    $response_data['failure_message'] = null;
}

else
{
    $failure_message = $response_data['failure_message'];
}



if($status == "successful" && $paid == false)
{ 

     
    //Redirect to checkout page...
    $data['checkout_url'] = $authorization_url;
    
       return '<a href="'.$authorization_url.'"><button  class="btn btn-primary">Confirm Order</button></a>';

}

   
    else
    {   
    
      return "<h4 style=color:red>".$failure_message."</h4>";
      
    }


  }

    return $this->load->view('extension/payment/itexpay', $data);
  }

  public function callback() {
      

    //Loading order model...
    $this->load->model('checkout/order');


    //Getting orderid from callback..
    if (!isset($this->request->get['order_id'])) {
        $this->request->get['order_id'] = null;
        }

        else
        {
            $order_id = $this->request->get['order_id'];
        }


    //Getting token from callbak...
    if (!isset($this->request->get['token'])) {
        $this->request->get['token'] = null;
        }

        else
        {
            $token = $this->request->get['token'];
        }

 
 //Getting code from callbak...
    if (!isset($this->request->get['code'])) {
        $this->request->get['code'] = null;
        }

        else
        {
            $code = $this->request->get['code'];
        }


        //Getting message from callbak...
    if (!isset($this->request->get['message'])) {
        $this->request->get['message'] = null;
        }

        else
        {
            $message = $this->request->get['message'];
        }


    if(empty($token) || empty($order_id))
    {
       
      die("<h2 style=color:red>Invalid Request ! </h2>");
    }

   
   //Getting order info...
   $order_info = $this->model_checkout_order->getOrder($order_id);


if($order_info)
{
    $this->check_transaction_status($token,$order_id);
      
} // end if order_info...

 else{
    die("<h2 style=color:red>Invalid Order ! </h2>");
 }

      } // end of callback...

      
    //Verifying payment from Gateway...
   public function check_transaction_status($reference_id,$order_id)
   {

         //Loading order model...
    $this->load->model('checkout/order');

    //Getting Environment from Gateway config..
     $environment = $this->config->get('payment_itexpay_environment');

     //Getting Api Key from Gateway config...
     $api_key = $this->config->get('payment_itexpay_api_key');

    if($environment == "Live")
    { 
        $status_check_base_url = 'https://api.itexpay.com/api/v1/transaction/status?merchantreference='.$reference_id;
    }

    else
    { 
      $status_check_base_url = 'https://staging.itexpay.com/api/v1/transaction/status?merchantreference='.$reference_id;
    }
    

// Initialize cURL session
$ch = curl_init();

 

// Set the cURL options
curl_setopt($ch, CURLOPT_URL, $status_check_base_url); // URL to send the request to
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout in seconds
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Overall timeout in seconds

// Set custom headers
$headers = array(
    'Authorization: Bearer '.$api_key.'', 
);

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Set the custom headers

// Execute the cURL request and get the response
$response = curl_exec($ch);

$response_data = json_decode($response, true );


// Check for cURL errors
if (curl_errno($ch)) {
   // echo 'cURL error: ' . curl_error($ch);
    die("<h2 style=color:red>" . curl_error($ch)." </h2>");

} else {



 if (!isset($response_data['code'])) {
     $transaction_code = null;
 }

 else
 {
     $transaction_code = $response_data['code'];
 }

 if (!isset($response_data['message'])) {
     $transaction_message = null;
 }

 else
 {
     $transaction_message = $response_data['message'];
 }


  //checking if transaction is successful
    if($transaction_code == "00")
    { 

       
       $this->model_checkout_order->addHistory($order_id, 5, $transaction_message, true);
       $this->response->redirect($this->url->link('checkout/success'), 301);
      

      
    }

   

else
{
  
    $this->model_checkout_order->addHistory($order_id, 10, $transaction_message, true);
    $this->response->redirect($this->url->link('checkout/checkout', '', true));
}



}

// Close the cURL session
curl_close($ch);


   } // end of check_transaction_status...



}

