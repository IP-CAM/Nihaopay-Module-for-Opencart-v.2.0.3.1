<?php
require_once(DIR_SYSTEM . 'library/log.php');
class ControllerPaymentNihaoPayOnlineWechatPay extends Controller {
	public function index() {
		$this->load->language('payment/nihaopay_online_wechatpay');
		$data['button_confirm'] = $this->language->get('button_confirm');
		return $this->load->view('default/template/payment/nihaopay_online_wechatpay.tpl', $data);
	}

	public function send() {
	    $url = 'https://api.nihaopay.com/v1.2/transactions/securepay';
	    if ($this->config->get('nihaopay_online_wechatpay_server') == 'test') {
	        $url = 'https://apitest.nihaopay.com/v1.2/transactions/securepay';
	    }

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data = array();
		
		$token = $this->config->get('nihaopay_online_wechatpay_token');
		
		$data['client_ip'] = $this->request->server['REMOTE_ADDR'];
		$data['description'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$amount=$this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000 , false);
		if($this->config->get('config_currency')=='JPY'){
		    $data['amount']=$amount;
		} else {
		    $data['amount'] = 100 * $amount;
		}
		$data['currency'] = $this->config->get('config_currency');
		$data['vendor'] = 'wechatpay';
		$data['reference'] = $this->session->data['order_id'];
		$data['callback_url'] = $this->url->link('payment/nihaopay_online_wechatpay/callback','',true);
		$data['ipn_url'] = $this->url->link('payment/nihaopay_online_wechatpay/ipn','',true);
		
		
		$curl = curl_init($url);

		curl_setopt_array($curl, array(
// 		    CURLOPT_URL => $url,
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_SSL_VERIFYPEER => false,
		    CURLOPT_ENCODING => "",
		    CURLOPT_MAXREDIRS => 10,
		    CURLOPT_TIMEOUT => 30,
		    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		    CURLOPT_CUSTOMREQUEST => "POST",
		    CURLOPT_POSTFIELDS => http_build_query($data, '', '&'),
		    CURLOPT_HTTPHEADER => array(
		        "authorization: Bearer " . $token,
		        "cache-control: no-cache",
		        "content-type: application/x-www-form-urlencoded"
		    ),
		));

		$response = curl_exec($curl);
    
		$httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
		
		$json = array();
		$log = new log('catalog_controller_nihaopay_wechatpay.log');
		if (curl_error($curl)) {
			$json['error'] = 'CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl);

			$log->write('AUTHNET AIM CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl));
		} elseif ($response) {
		    
		    $results = json_decode($response,true);
		   
		    if ($httpCode == '200') {
		        
		       $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('config_order_status_id'));
		        
		       $json['html'] = $response;
		        
		    } else {
		        $json['error'] = $response;
		    }
		} else {
			$json['error'] = 'Empty Gateway Response';

			$log->write('AUTHNET AIM CURL ERROR: Empty Gateway Response');
		}

		curl_close($curl);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function callback() {
	    $this->load->language('payment/nihaopay_online_wechatpay');
	    
	    $this->load->model('payment/nihaopay_online_wechatpay');
	    
	    $this->load->model('checkout/order');
	    
	    $data = $this->request->get;
	    
	    $token = $this->config->get('nihaopay_online_wechatpay_token');
	    
	    $verify_sign=$data['verify_sign'];
	    
	    foreach ($data as $key => $row){
	        $volume[$key]  = $key;
	    }
	    array_multisort($volume, SORT_ASC, $data);
	    $build='';
	    foreach($data as $key=>$value){
	        if($value!='null' && $key!='verify_sign' && $key!='route'){
	            $build = $build.$key.'='.$value.'&';
	        }
	    }
	    $log = new log('catalog_controller_nihaopay_wechatpay.log');
	    if($verify_sign == md5($build.md5($token))){
	        
	        if (isset($data['status']) && $data['status'] == 'success') {
	            
	            $order_id = $data['reference'];
	            $order_info = $this->model_checkout_order->getOrder($order_id);
	            
	            $this->load->model('payment/nihaopay_online_wechatpay');
	            
	            $message = "NihaoPay Payment accepted\n";
	            
	            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('nihaopay_online_wechatpay_order_status_id'), $message);
	            
	            $this->response->redirect($this->url->link('checkout/success', '', true));
	            
	        }else{
	            
	            $this->response->redirect($this->url->link('checkout/failure', '', true));
	            
	            $log->write(print_r($data,true));
	            
	        }
	    }else{
	        
	        $this->response->redirect($this->url->link('checkout/failure', '', true));
	        $log->write('Callback Signature authentication failed.');
	        $log->write(print_r($data,true));
	    }
	}
	
	public function ipn() {
	    
	    $data = $this->request->get;
	    
	    $token = $this->config->get('nihaopay_online_wechatpay_token');
	    
	    $verify_sign=$data['verify_sign'];
	    
	    foreach ($data as $key => $row){
	        $volume[$key]  = $key;
	    }
	    array_multisort($volume, SORT_ASC, $data);
	    $build='';
	    foreach($data as $key=>$value){
	        if($value!='null' && $key!='verify_sign' && $key!='route'){
	            $build = $build.$key.'='.$value.'&';
	        }
	    }
	    $log = new log('catalog_controller_nihaopay_wechatpay.log');
	    if($verify_sign == md5($build.md5($token))){
	        
	        if (isset($data['status']) && $data['status'] == 'success') {
	            
	            $order_id = $data['reference'];
	            
	            $this->load->model('checkout/order');
	            
	            $order_info = $this->model_checkout_order->getOrder($order_id);
	            
	            $message = "NihaoPay Payment accepted\n";
	            
	            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('nihaopay_online_wechatpay_order_status_id'), $message);
	            
	        }else{
	            $log->write(print_r($data,true));
	        }
	        
	    }else{
	        $log->write('IPN Signature authentication failed.');
	        $log->write(print_r($data,true));
	    }
	    return 'ok';
	}
}