<?php

class ControllerExtensionModuleAccount extends Controller
{
    public function index()
    {
        $this->load->language('extension/module/account');

        $data['edit'] = $this->url->link('account/edit', array('language' => $this->config->get('config_language')));
        $data['address'] = $this->url->link('account/address', array('language' . $this->config->get('config_language')));
        $data['wishlist'] = $this->url->link('account/wishlist', 'language=' . $this->config->get('config_language'));
        $data['order'] = $this->url->link('account/order', array('language' => $this->config->get('config_language')));
        $data['return'] = $this->url->link('account/return', 'language=' . $this->config->get('config_language'));

        return $this->load->view('extension/module/account', $data);
    }
}