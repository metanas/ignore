<?php

class ControllerCheckoutCheckout extends Controller
{
    public function index()
    {
        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers']))) {
            $this->response->redirect($this->url->link('checkout/cart', 'language=' . $this->config->get('config_language')));
        }

        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $this->response->redirect($this->url->link('checkout/cart', 'language=' . $this->config->get('config_language')));
            }
        }

        $this->load->language('account/register');
        $this->load->language('account/login');
        $this->load->language('checkout/checkout');


        $this->document->setTitle($this->language->get('heading_title'));

        $data['title'] = $this->document->getTitle();

        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addScript('catalog/view/javascript/loading.js');
        $this->document->addStyle('catalog/view/theme/default/stylesheet/loading.css');
        $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

        // Required by klarna
        if ($this->config->get('payment_klarna_account') || $this->config->get('payment_klarna_invoice')) {
            $this->document->addScript('http://cdn.klarna.com/public/kitt/toc/v1.0/js/klarna.terms.min.js');
        }

        $data['text_checkout_option'] = sprintf($this->language->get('text_checkout_option'), 1);
        $data['text_checkout_account'] = sprintf($this->language->get('text_checkout_account'), 2);
        $data['text_checkout_payment_address'] = sprintf($this->language->get('text_checkout_payment_address'), 2);
        $data['text_checkout_shipping_address'] = sprintf($this->language->get('text_checkout_shipping_address'), 3);
        $data['text_checkout_shipping_method'] = sprintf($this->language->get('text_checkout_shipping_method'), 4);

        if ($this->cart->hasShipping()) {
            $data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 5);
            $data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 6);
        } else {
            $data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 3);
            $data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 4);
        }

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        $data['logged'] = $this->customer->isLogged();

        if (isset($this->session->data['account'])) {
            $data['account'] = $this->session->data['account'];
        } else {
            $data['account'] = '';
        }

        if (!$this->customer->isLogged()) {
            $data['step'] = $this->load->controller('checkout/login');
            $this->response->setOutput($this->load->controller('checkout/login'));
            $data['step_1'] = "disabled";
            $data['step_2'] = "disabled";
            $data['step_3'] = "disabled";

        } else {
            $data['step'] = $this->load->controller('checkout/shipping_address');
            $data['step_1'] = "active";
            $data['step_2'] = "disabled";
            $data['step_3'] = "disabled";
        }

        $data['shipping_required'] = $this->cart->hasShipping();

        $data['language'] = $this->config->get('config_language');
        $data['home'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

        $this->response->setOutput($this->load->view('checkout/checkout', $data));
    }

    public function country()
    {
        $json = array();

        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

        if ($country_info) {
            $this->load->model('localisation/zone');

            $json = array(
                'country_id' => $country_info['country_id'],
                'name' => $country_info['name'],
                'iso_code_2' => $country_info['iso_code_2'],
                'iso_code_3' => $country_info['iso_code_3'],
                'address_format' => $country_info['address_format'],
                'postcode_required' => $country_info['postcode_required'],
                'zone' => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
                'status' => $country_info['status']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function customfield()
    {
        $json = array();

        $this->load->model('account/custom_field');

        // Customer Group
        if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
            $customer_group_id = $this->request->get['customer_group_id'];
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        $custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

        foreach ($custom_fields as $custom_field) {
            $json[] = array(
                'custom_field_id' => $custom_field['custom_field_id'],
                'required' => $custom_field['required']
            );
        }
        $data['motion_legal'] = $this->url->link('information/information', 'language=' . $this->config->get('config_language') . '&information_id=' . 13);
        $data['terms_private'] = $this->url->link('information/information', 'language=' . $this->config->get('config_language') . '&information_id=' . 14);
        $data['contact'] = $this->url->link('information/contact', 'language=' . $this->config->get('config_language'));

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getStep()
    {
        if ((int)$this->request->post['step_id'] === 2 && isset($this->session->data['customer_id'])) {
            $this->response->setOutput($this->load->controller('checkout/shipping_address'));
        } elseif ((int)$this->request->post['step_id'] === 3 && isset($this->session->data['customer_id']) && isset($this->session->data['shipping_address'])) {
            $this->response->setOutput($this->load->controller('checkout/payment_address'));
        } elseif ((int)$this->request->post['step_id'] === 4 && isset($this->session->data['customer_id']) && isset($this->session->data['shipping_address']) && isset($this->session->data['payment_method'])) {
            $this->response->setOutput($this->load->controller('checkout/confirm'));
        }else{
            $this->response->setOutput($this->load->controller('checkout/checkout'));
        }
    }
}