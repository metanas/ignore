<?php

class ControllerCheckoutPaymentAddress extends Controller
{
    public function index()
    {

        $this->load->language('checkout/checkout');

        if (isset($this->session->data['payment_method'])) {
            $data['payment_method'] = $this->session->data['payment_method'];
        }

        $this->load->model('account/address');

        $data['total'] = $this->cart->getTotal();
        $data['currency'] = $this->session->data['currency'];

        $data['language'] = $this->config->get('config_language');

        return $this->load->view('checkout/payment_address', $data);
    }

    public function save()
    {
        $this->load->language('checkout/checkout');

        $json = array();

        // Validate if customer is logged in.
        if (!$this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'));
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'));
        }

        if (!$json) {
            if(isset($this->request->post['payment_method']) && filter_var((int)$this->request->post['payment_method'],FILTER_VALIDATE_INT)) {
                $this->session->data['payment_method'] = $this->request->post['payment_method'];
                $json['success'] = true;
            }else{
                $json['not_selected'] = true;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}