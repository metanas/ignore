<?php

class ControllerAccountSuccess extends Controller
{
    public function index()
    {
        $this->load->language('account/success');

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->customer->isLogged()) {
            $data['text_message'] = sprintf($this->language->get('text_success'), $this->url->link('information/contact', 'language=' . $this->config->get('config_language')));
        } else if(isset($this->session->data['username'])) {
            $data['text_message'] = sprintf($this->language->get('text_approval'), $this->session->data['username'], $this->config->get('config_name'));
        }

        unset($this->session->data['username']);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('common/success', $data));
    }
}