<?php

class ControllerAccountAddress extends Controller
{
    private $error = array();

    public function index()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', array('action' => 'address', 'language' => $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        $this->load->language('account/address');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('account/address');

        return $this->getList();
    }

    public function add()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', array('action' => 'address', 'language' => $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        $this->load->language('account/address');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

        $this->load->model('account/address');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_account_address->addAddress($this->customer->getId(), $this->request->post);

            $this->session->data['success'] = $this->language->get('text_add');

            $this->response->redirect($this->url->link('account/account', array('action' => 'address', 'language' => $this->config->get('config_language'))));
        }

        $this->getForm();
    }

    public function edit()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', array('action' => 'address', 'language' => $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        $this->load->language('account/address');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

        $this->load->model('account/address');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_account_address->editAddress($this->request->get['address_id'], $this->request->post);

            // Default Shipping Address
            if (isset($this->session->data['shipping_address']['address_id']) && ($this->request->get['address_id'] == $this->session->data['shipping_address']['address_id'])) {
                $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->request->get['address_id']);

                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
            }

            // Default Payment Address
            if (isset($this->session->data['payment_address']['address_id']) && ($this->request->get['address_id'] == $this->session->data['payment_address']['address_id'])) {
                $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->request->get['address_id']);

                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
            }

            $this->session->data['success'] = $this->language->get('text_edit');

            $this->response->redirect($this->url->link('account/account', array('action' => 'address', 'language' => $this->config->get('config_language'))));
        }

        $this->getForm();
    }

    public function delete()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', array('action' => 'address', 'language' => $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        $this->load->language('account/address');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('account/address');

        if (isset($this->request->get['address_id']) && $this->validateDelete()) {
            $this->model_account_address->deleteAddress($this->request->get['address_id']);

            // Default Shipping Address
            if (isset($this->session->data['shipping_address']['address_id']) && ($this->request->get['address_id'] == $this->session->data['shipping_address']['address_id'])) {
                unset($this->session->data['shipping_address']);
                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
            }

            // Default Payment Address
            if (isset($this->session->data['payment_address']['address_id']) && ($this->request->get['address_id'] == $this->session->data['payment_address']['address_id'])) {
                unset($this->session->data['payment_address']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
            }

            $this->session->data['success'] = $this->language->get('text_delete');

            $this->response->redirect($this->url->link('account/account', array('action' => 'address', 'language' => $this->config->get('config_language'))));
        }

        $this->getList();
    }

    protected function getList()
    {
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['addresses'] = array();

        $results = $this->model_account_address->getAddresses();

        foreach ($results as $result) {
            $format = '<b>{firstname} {lastname}</b>' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . "T: {telephone}" . "\n" . '{country}';

            $find = array(
                '{firstname}',
                '{lastname}',
                '{address_1}',
                '{address_2}',
                '{city}',
                '{telephone}',
                '{postcode}',
                '{country}'
            );

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
                'address' => str_replace(array("\r\n", "\r", "\n"), '<br/>', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br/>', trim(str_replace($find, $replace, $format)))),
                'update' => $this->url->link('account/address/edit', 'language=' . $this->config->get('config_language') . '&address_id=' . $result['address_id']),
                'delete' => $this->url->link('account/address/delete', 'language=' . $this->config->get('config_language') . '&address_id=' . $result['address_id'])
            );
        }

        $data['add'] = $this->url->link('account/address/add', 'language=' . $this->config->get('config_language'));
        $data['back'] = $this->url->link('account/account', 'language=' . $this->config->get('config_language'));

        $data['language'] = $this->config->get('config_language');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');

        return $this->load->view('account/address_list', $data);
    }

    protected function getForm()
    {
        $data['text_address'] = !isset($this->request->get['address_id']) ? $this->language->get('text_address_add') : $this->language->get('text_address_edit');

        if (isset($this->error['firstname'])) {
            $data['error_firstname'] = $this->error['firstname'];
        } else {
            $data['error_firstname'] = '';
        }

        if (isset($this->error['lastname'])) {
            $data['error_lastname'] = $this->error['lastname'];
        } else {
            $data['error_lastname'] = '';
        }

        if (isset($this->error['address_1'])) {
            $data['error_address_1'] = $this->error['address_1'];
        } else {
            $data['error_address_1'] = '';
        }

        if (isset($this->error['city'])) {
            $data['error_city'] = $this->error['city'];
        } else {
            $data['error_city'] = '';
        }

        if (isset($this->error['postcode'])) {
            $data['error_postcode'] = $this->error['postcode'];
        } else {
            $data['error_postcode'] = '';
        }

        if (isset($this->error['country'])) {
            $data['error_country'] = $this->error['country'];
        } else {
            $data['error_country'] = '';
        }

        if (isset($this->error['telephone'])) {
            $data['error_telephone'] = $this->error['telephone'];
        } else {
            $data['error_telephone'] = '';
        }


        if (isset($this->error['custom_field'])) {
            $data['error_custom_field'] = $this->error['custom_field'];
        } else {
            $data['error_custom_field'] = array();
        }

        if (!isset($this->request->get['address_id'])) {
            $data['action'] = $this->url->link('account/address/add', 'language=' . $this->config->get('config_language'));
        } else {
            $data['action'] = $this->url->link('account/address/edit', 'language=' . $this->config->get('config_language') . '&address_id=' . $this->request->get['address_id']);
        }

        if (isset($this->request->get['address_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $address_info = $this->model_account_address->getAddress($this->request->get['address_id']);
        }

        if (isset($this->request->post['firstname'])) {
            $data['firstname'] = $this->request->post['firstname'];
        } elseif (!empty($address_info)) {
            $data['firstname'] = $address_info['firstname'];
        } else {
            $data['firstname'] = '';
        }

        if (isset($this->request->post['lastname'])) {
            $data['lastname'] = $this->request->post['lastname'];
        } elseif (!empty($address_info)) {
            $data['lastname'] = $address_info['lastname'];
        } else {
            $data['lastname'] = '';
        }

        if (isset($this->request->post['address_1'])) {
            $data['address_1'] = $this->request->post['address_1'];
        } elseif (!empty($address_info)) {
            $data['address_1'] = $address_info['address_1'];
        } else {
            $data['address_1'] = '';
        }

        if (isset($this->request->post['address_2'])) {
            $data['address_2'] = $this->request->post['address_2'];
        } elseif (!empty($address_info)) {
            $data['address_2'] = $address_info['address_2'];
        } else {
            $data['address_2'] = '';
        }

        if (isset($this->request->post['postcode'])) {
            $data['postcode'] = $this->request->post['postcode'];
        } elseif (!empty($address_info)) {
            $data['postcode'] = $address_info['postcode'];
        } else {
            $data['postcode'] = '';
        }

        if (isset($this->request->post['city'])) {
            $data['city'] = $this->request->post['city'];
        } elseif (!empty($address_info)) {
            $data['city'] = $address_info['city'];
        } else {
            $data['city'] = '';
        }

        if (isset($this->request->post['telephone'])) {
            $data['telephone'] = $this->request->post['telephone'];
        } elseif (!empty($address_info)) {
            $data['telephone'] = $address_info['telephone'];
        } else {
            $data['telephone'] = '';
        }

        if (isset($this->request->post['country'])) {
            $data['country'] = $this->request->post['country'];
        } elseif (!empty($address_info)) {
            $data['country'] = $address_info['country'];
        } else {
            $data['country'] = '';
        }

        // Custom fields
        $data['custom_fields'] = array();

        $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] == 'address') {
                $data['custom_fields'][] = $custom_field;
            }
        }

        if (isset($this->request->post['custom_field']['address'])) {
            $data['address_custom_field'] = $this->request->post['custom_field']['address'];
        } elseif (isset($address_info)) {
            $data['address_custom_field'] = $address_info['custom_field'];
        } else {
            $data['address_custom_field'] = array();
        }

        if (isset($this->request->post['default'])) {
            $data['default'] = $this->request->post['default'];
        } elseif (isset($this->request->get['address_id'])) {
            $data['default'] = $this->customer->getAddressId() == $this->request->get['address_id'];
        } else {
            $data['default'] = false;
        }

        $data['back'] = $this->url->link('account/account', array('action' => 'address', 'language=' . $this->config->get('config_language')));

        $data['language'] = $this->config->get('config_language');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/address_form', $data));
    }

    protected function validateForm()
    {
        var_dump($this->request->post);
        exit();
        if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
            $this->error['firstname'] = $this->language->get('error_firstname');
        }

        if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
            $this->error['lastname'] = $this->language->get('error_lastname');
        }

        if ((utf8_strlen(trim($this->request->post['address_1'])) < 3) || (utf8_strlen(trim($this->request->post['address_1'])) > 128)) {
            $this->error['address_1'] = $this->language->get('error_address_1');
        }

        if ((utf8_strlen(trim($this->request->post['city'])) < 2) || (utf8_strlen(trim($this->request->post['city'])) > 128)) {
            $this->error['city'] = $this->language->get('error_city');
        }

        if ((utf8_strlen(trim($this->request->post['city'])) < 2) || (utf8_strlen(trim($this->request->post['city'])) > 128)) {
            $this->error['city'] = $this->language->get('error_city');
        }

        if ((utf8_strlen(trim($this->request->post['telephone'])) < 2) || (utf8_strlen(trim($this->request->post['telephone'])) > 128)) {
            $this->error['telephone'] = $this->language->get('error_telephone');
        }

        if (utf8_strlen(trim($this->request->post['country'])) != 5) {
            $this->error['country'] = $this->language->get('error_country');
        }

        // Custom field validation
        $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] == 'address') {
                if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                    $this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                    $this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                }
            }
        }

        return !$this->error;
    }

    protected function validateDelete()
    {
        if ($this->model_account_address->getTotalAddresses() == 1) {
            $this->error['warning'] = $this->language->get('error_delete');
        }

        if ($this->customer->getAddressId() == $this->request->get['address_id']) {
            $this->error['warning'] = $this->language->get('error_default');
        }

        return !$this->error;
    }
}