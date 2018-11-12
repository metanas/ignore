<?php

class ControllerCheckoutShippingAddress extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('checkout/checkout');

        if (isset($this->session->data['shipping_address'])) {
            $data['address_id'] = $this->session->data['shipping_address'];
        } else {
            $data['address_id'] = $this->customer->getAddressId();
        }

        $this->load->model('account/address');

        $format = '<b>{firstname} {lastname}</b>' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . "T: {telephone}" . "\n" . '{country}';

        $find = array(
            '{firstname}',
            '{lastname}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{postcode}',
            '{telephone}',
            '{country}'
        );

        $results = $this->model_account_address->getAddresses();
        foreach ($results as $result) {

            $replace = array(
                'firstname' => $result['firstname'],
                'lastname' => $result['lastname'],
                'address_1' => $result['address_1'],
                'address_2' => $result['address_2'],
                'city' => $result['city'],
                'postcode' => $result['postcode'],
                'telephone' => $result['telephone'],
                'country' => $result['country'],
            );

            $data['addresses'][] = array(
                'address_id' => $result['address_id'],
                'address' => str_replace(array("\r\n", "\r", "\n"), '<br/>', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br/>', trim(str_replace($find, $replace, $format))))
            );
        }

        if (isset($this->session->data['billing_address'])) {
            $data['billing_address'] = array(
                'firstname' => $this->session->data['billing_address']['firstname'],
                'lastname' => $this->session->data['billing_address']['lastname'],
                'address_1' => $this->session->data['billing_address']['address_1'],
                'address_2' => $this->session->data['billing_address']['address_2'],
                'city' => $this->session->data['billing_address']['city'],
                'postcode' => $this->session->data['billing_address']['postcode'],
                'telephone' => $this->session->data['billing_address']['telephone'],
                'country' => $this->session->data['billing_address']['country'],
            );
        }

        // Custom Fields
        $data['custom_fields'] = array();

        $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] == 'address') {
                $data['custom_fields'][] = $custom_field;
            }
        }

        $data['language'] = $this->config->get('config_language');

        return $this->load->view('checkout/shipping_address', $data);
    }

    public function add()
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

        if (!$this->validateForm()) {
            $this->load->model('account/address');

            if (isset($this->request->post['shipping_address']) && $this->request->post['shipping_address'] == 'existing') {
                if (empty($this->request->post['address_id'])) {
                    $json['error']['warning'] = $this->language->get('error_address');
                } elseif (!in_array($this->request->post['address_id'], array_keys($this->model_account_address->getAddresses()))) {
                    $json['error']['warning'] = $this->language->get('error_address');
                }

            } else {
                // Custom field validation
                $this->load->model('account/custom_field');

                if (!$json) {
                    $address_id = $this->model_account_address->addAddress($this->customer->getId(), $this->request->post);
                    $json = $this->request->post;
                    $json["address_id"] = $address_id;
                    // If no default address ID set we use the last address
                    if (!$this->customer->getAddressId()) {
                        $this->load->model('account/customer');

                        $this->model_account_customer->editAddressId($this->customer->getId(), $address_id);
                    }
                }
            }
        }else{
            $json['error'] = $this->error;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete()
    {
        $json = array();

        if (!$this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'));
        }

        $this->load->model('account/address');

        if (!$json) {
            $this->model_account_address->deleteAddress($this->request->post['address_id']);
            $json['success'] = true;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function save()
    {
        $json = array();

        if (!$this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'));
        }

        if ($this->request->post['address'] == "Add") {
            $json['not_selected'] = true;
        }

        $this->load->model('account/address');

        if (!$json) {
            $address = $this->model_account_address->getAddress($this->request->post['address']);
            if (!$address) {
                $json['not_found'] = true;
            }
        }
        if (!$json) {
            if (isset($this->request->post['billing']) && $this->request->post['billing'] == '0' && !$this->validateForm()) {
                $this->session->data['billing_address'] = array(
                    'firstname' => $this->request->post['firstname'],
                    'lastname' => $this->request->post['lastname'],
                    'address_1' => $this->request->post['address_1'],
                    'address_2' => $this->request->post['address_2'],
                    'postcode' => $this->request->post['postcode'],
                    'city' => $this->request->post['city'],
                    'telephone' => $this->request->post['telephone'],
                    'country' => $this->request->post['country'],
                );
            } else {
                unset($this->session->data['billing_address']);
            }

            if (!$json && !$this->error) {
                $this->session->data['shipping_address'] = $this->request->post['address'];
                $json['success'] = true;
            } else {
                $json['error'] = $this->error;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function update()
    {
        $json = array();

        if (!$this->customer->isLogged()) {
            $json['redirect'] = $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'));
        }

        if (filter_var((int)$this->request->post['address_id'], FILTER_VALIDATE_INT) && !$this->validateForm()) {
            $this->load->model('account/address');

            $this->model_account_address->editAddress($this->request->post['address_id'], $this->request->post);

            $json['success'] = true;
            $json['result'] = $this->request->post;
        } else {
            $json['error'] = $this->validateForm();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function info()
    {
        $json = array();

        if (filter_var($this->request->post['address_id'], FILTER_VALIDATE_INT)) {

            $this->load->model('account/address');
            $json['result'] = $this->model_account_address->getAddress($this->request->post['address_id']);
        } else {
            $json['error'] = true;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function validateForm()
    {
        if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
            $this->error['firstname'] = $this->language->get('error_firstname');
        }

        if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
            $this->error['lastname'] = $this->language->get('error_lastname');
        }

        if ((utf8_strlen(trim($this->request->post['address_1'])) < 3) || (utf8_strlen(trim($this->request->post['address_1'])) > 128)) {
            $this->error['address_1'] = $this->language->get('error_address_1');
        }

        if (!is_numeric($this->request->post['postcode'])) {
            $this->error['postcode'] = $this->language->get('error_postcode');
        }

        if ((utf8_strlen(trim($this->request->post['city'])) < 2) || (utf8_strlen(trim($this->request->post['city'])) > 128)) {
            $this->error['city'] = $this->language->get('error_city');
        }

        if ((utf8_strlen(trim($this->request->post['telephone'])) < 2) || (utf8_strlen(trim($this->request->post['telephone'])) > 128)) {
            $this->error['telephone'] = $this->language->get('error_telephone');
        }

        if(utf8_strlen(trim($this->request->post['country'])) != 5) {
            $this->error['country'] = $this->language->get('error_country');
        }

        return $this->error;
    }
}