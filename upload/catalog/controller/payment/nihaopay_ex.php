<?php
require_once(DIR_SYSTEM . 'library/log.php');
class ControllerPaymentNihaoPayEx extends Controller {
	public function index() {
		$this->load->language('payment/nihaopay_ex');

		$data['months'] = array();

		for ($i = 1; $i <= 12; $i++) {
			$data['months'][] = array(
				'text'  => strftime('%B', mktime(0, 0, 0, $i, 1, 2000)),
				'value' => sprintf('%02d', $i)
			);
		}

		$today = getdate();

		$data['year_expire'] = array();

		for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
			$data['year_expire'][] = array(
				'text'  => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
				'value' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i))
			);
		}
		$data['text_credit_card'] = $this->language->get('text_credit_card');
		$data['entry_cc_number'] = $this->language->get('entry_cc_number');
		$data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');
		$data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['entry_cc_owner'] = $this->language->get('entry_cc_owner');
		return $this->load->view('default/template/payment/nihaopay_ex.tpl', $data);
	}

	public function send() {
	    $url = 'https://api.nihaopay.com/v1.2/transactions/expresspay';
	    if ($this->config->get('nihaopay_ex_server') == 'test') {
	        $url = 'https://apitest.nihaopay.com/v1.2/transactions/expresspay';
	    }

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data = array();
		
		$token = $this->config->get('nihaopay_ex_token');
		
		//$data['client_ip'] = $this->request->server['REMOTE_ADDR'];
		$data['client_ip'] = "172.25.4.75";
		$data['description'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$amount=$this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000 , false);
		if($this->config->get('config_currency')=='JPY'){
		    $data['amount']=$amount;
		} else {
		    $data['amount'] = 100 * $amount;
		}
		$data['currency'] = $this->config->get('config_currency');
		$data['card_number'] = str_replace(' ', '', $this->request->post['cc_number']);
		$data['card_exp_month'] = $this->request->post['cc_expire_date_month'];
		$data['card_exp_year'] = $this->request->post['cc_expire_date_year'];
		$data['card_cvv'] = $this->request->post['cc_cvv2'];
		$data['reference'] = $this->session->data['order_id'];
		
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
		$log = new log('catalog_controller_nihaopay_ex.log');
		if (curl_error($curl)) {
			$json['error'] = 'CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl);

			$log->write('AUTHNET AIM CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl));
		} elseif ($response) {
		    
		    $results = json_decode($response,true);
		   
		    if ($httpCode == '200') {
		        if(isset($results['status'])&& $results['status']=='success'){
    		        //执行addOrderHistory之前，邮件服务是否设置？  ---会出错或无限等待
    		        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('nihaopay_ex_order_status_id'));
    		        
    		        $json['redirect'] = $this->url->link('checkout/success', '', true);
		        }else{
		            $json['error'] = $response;
		        }
		        
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
}