<?php

class ControllerExtensionTotalCoupon extends Controller
{
    public function index()
    {
        if ($this->config->get('total_coupon_status')) {
            $this->load->language('extension/total/coupon');

            if (isset($this->session->data['coupon'])) {
                $data['coupon'] = $this->session->data['coupon'];
            } else {
                $data['coupon'] = '';
            }

            $data['language'] = $this->config->get('config_language');

            return $this->load->view('extension/total/coupon', $data);
        }
    }

    public function coupon()
    {
        $this->load->language('extension/total/coupon');

        $json = array();

        unset($this->session->data['coupon']);

        if (isset($this->request->post['coupon'])) {
            $coupon = $this->request->post['coupon'];
        } else {
            $json['error'] = $this->language->get('error_empty');
        }

        if(!$this->customer->isLogged()){
            $json['error'] = "403";
        }

        if (!$json) {
            $this->load->model('extension/total/coupon');

            $coupon_info = $this->model_extension_total_coupon->getCoupon($coupon);

            if ($coupon_info) {

                $total = $this->cart->getTotal();

                $json['total'] = $this->currency->format($total - ($total * $coupon_info['discount']) / 100, $this->session->data['currency']);

                $json['discount'] = (int)$coupon_info['discount'] . "%";

                $json['success'] = $this->language->get('text_success');
                $this->session->data['coupon'] = $this->request->post['coupon'];

                $this->session->data['success'] = $this->language->get('text_success');
            } else {
                $json['error'] = $this->language->get('error_coupon');
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function remove(){
        $json = array();

        unset($this->session->data['coupon']);

        $json['success'] = true;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
