<?php

class ControllerAccountAccount extends Controller
{
    public function index()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', array('action' => 'edit', 'language', $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        $this->load->language('account/account');

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if ($this->request->get['action'] === 'edit') {
            $data['content'] = $this->load->controller('account/edit');
        } elseif ($this->request->get['action'] === 'address') {
            $data['content'] = $this->load->controller('account/address');
        } elseif ($this->request->get['action'] === 'order') {
//            if(isset($this->request->get['page']))
            $data['content'] = $this->load->controller('account/order');
        }

        $data['edit'] = $this->url->link('account/account', array('action' => 'edit', 'language' => $this->config->get('config_language')));
        $data['address'] = $this->url->link('account/account', array('action' => 'address', 'language' . $this->config->get('config_language')));
        $data['wishlist'] = $this->url->link('account/wishlist', 'language=' . $this->config->get('config_language'));
        $data['order'] = $this->url->link('account/account', array('action' => 'order', 'language' => $this->config->get('config_language')));
        $data['return'] = $this->url->link('account/return', 'language=' . $this->config->get('config_language'));


        $data['transaction'] = $this->url->link('account/transaction', 'language=' . $this->config->get('config_language'));
        $data['newsletter'] = $this->url->link('account/newsletter', 'language=' . $this->config->get('config_language'));
        $data['recurring'] = $this->url->link('account/recurring', 'language=' . $this->config->get('config_language'));

        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/account', $data));
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
}
