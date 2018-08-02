<?php
class ControllerPaymentNihaoPayOnlineUnionPay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/nihaopay_online_unionpay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('nihaopay_online_unionpay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}
		
		$data['callback_url'] = HTTPS_CATALOG . 'index.php?route=payment/nihaopay_online_unionpay/callback';

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['token'])) {
			$data['error_token'] = $this->error['token'];
		} else {
			$data['error_token'] = '';
		}
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'] . '&type=payment', 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/nihaopay_online_unionpay', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('payment/nihaopay_online_unionpay', 'token=' . $this->session->data['token'], 'SSL');
		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'] . '&type=payment', 'SSL');

		if (isset($this->request->post['nihaopay_online_unionpay_token'])) {
			$data['nihaopay_online_unionpay_token'] = $this->request->post['nihaopay_online_unionpay_token'];
		} else {
			$data['nihaopay_online_unionpay_token'] = $this->config->get('nihaopay_online_unionpay_token');
		}


		if (isset($this->request->post['nihaopay_online_unionpay_server'])) {
			$data['nihaopay_online_unionpay_server'] = $this->request->post['nihaopay_online_unionpay_server'];
		} else {
			$data['nihaopay_online_unionpay_server'] = $this->config->get('nihaopay_online_unionpay_server');
		}

		if (isset($this->request->post['nihaopay_online_unionpay_total'])) {
			$data['nihaopay_online_unionpay_total'] = $this->request->post['nihaopay_online_unionpay_total'];
		} else {
			$data['nihaopay_online_unionpay_total'] = $this->config->get('nihaopay_online_unionpay_total');
		}

		if (isset($this->request->post['nihaopay_online_unionpay_order_status_id'])) {
			$data['nihaopay_online_unionpay_order_status_id'] = $this->request->post['nihaopay_online_unionpay_order_status_id'];
		} else {
			$data['nihaopay_online_unionpay_order_status_id'] = $this->config->get('nihaopay_online_unionpay_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['nihaopay_online_unionpay_geo_zone_id'])) {
			$data['nihaopay_online_unionpay_geo_zone_id'] = $this->request->post['nihaopay_online_unionpay_geo_zone_id'];
		} else {
			$data['nihaopay_online_unionpay_geo_zone_id'] = $this->config->get('nihaopay_online_unionpay_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['nihaopay_online_unionpay_status'])) {
			$data['nihaopay_online_unionpay_status'] = $this->request->post['nihaopay_online_unionpay_status'];
		} else {
			$data['nihaopay_online_unionpay_status'] = $this->config->get('nihaopay_online_unionpay_status');
		}

		if (isset($this->request->post['nihaopay_online_unionpay_sort_order'])) {
			$data['nihaopay_online_unionpay_sort_order'] = $this->request->post['nihaopay_online_unionpay_sort_order'];
		} else {
			$data['nihaopay_online_unionpay_sort_order'] = $this->config->get('nihaopay_online_unionpay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_extension'] = $this->language->get('text_extension');
		$data['text_success'] = $this->language->get('text_success');
		$data['text_test'] = $this->language->get('text_test');
		$data['text_live'] = $this->language->get('text_live');
		$data['text_authorization'] = $this->language->get('text_authorization');
		$data['text_capture'] = $this->language->get('text_capture');
		$data['text_nihaopay_ex'] = $this->language->get('text_nihaopay_ex');
		$data['entry_token'] = $this->language->get('entry_token');
		$data['entry_server'] = $this->language->get('entry_server');
		$data['entry_method'] = $this->language->get('entry_method');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['help_total'] = $this->language->get('help_total');
		$data['error_permission'] = $this->language->get('error_permission');
		$data['error_login'] = $this->language->get('error_login');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$this->response->setOutput($this->load->view('payment/nihaopay_online_unionpay.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/nihaopay_online_unionpay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['nihaopay_online_unionpay_token']) {
			$this->error['token'] = $this->language->get('error_token');
		}

		return !$this->error;
	}
}
