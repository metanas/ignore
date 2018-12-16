<?php

class ControllerAccountEdit extends Controller
{
    private $error = array();

    public function index()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/edit', array('action' => 'edit', 'language' => $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        $this->load->language('account/edit');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

        $this->load->model('account/customer');

        if (isset($this->session->data['error'])) {
            $this->error = $this->session->data['error'];

            unset($this->session->data['error']);
        }

        $data['error'] = $this->validate();

        $data['action_edit'] = $this->url->link('account/edit/edit_info', 'language=' . $this->config->get('config_language'));
        $data['action_password'] = $this->url->link('account/edit/edit_password', 'language=' . $this->config->get('config_language'));
        $data['action_email'] = $this->url->link('account/edit/edit_email', 'language=' . $this->config->get('config_language'));

        $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

        if (!empty($customer_info)) {
            $data['firstname'] = $customer_info['firstname'];
        } else {
            $data['firstname'] = '';
        }

        if (!empty($customer_info)) {
            $data['lastname'] = $customer_info['lastname'];
        } else {
            $data['lastname'] = '';
        }

        if (!empty($customer_info)) {
            $data['email'] = $customer_info['email'];
        } else {
            $data['email'] = '';
        }

        if (!empty($customer_info)) {
            $data['telephone'] = $customer_info['telephone'];
        } else {
            $data['telephone'] = '';
        }

        // Custom Fields
        $data['custom_fields'] = array();

        $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] == 'account') {
                $data['custom_fields'][] = $custom_field;
            }
        }

        if (isset($customer_info)) {
            $data['account_custom_field'] = json_decode($customer_info['custom_field'], true);
        } else {
            $data['account_custom_field'] = array();
        }

        $data['language'] = $this->config->get('config_language');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');

        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/edit', $data));
    }

    public function edit_info()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/edit', array('action' => 'edit', 'language' => $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate_info()) {
            $this->load->model('account/customer');

            $this->model_account_customer->editCustomer($this->customer->getId(), $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->session->data['redirect'] = $this->url->link('account/edit', array('language' => $this->config->get('config_language')));
        } else {
            $this->session->data['error'] = $this->error;
        }

        $this->response->redirect($this->url->link('account/edit', array('language' => $this->config->get('config_language'))));
    }

    public function edit_password()
    {

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/edit', array('language' => $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate_password()) {
            $this->load->model('account/customer');

            $this->model_account_customer->editPassword($this->customer->getEmail(), $this->request->post['password']);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('account/edit', array('language' => $this->config->get('config_language'))));
        } else {
            $this->session->data['error'] = $this->error;
        }

        $this->response->redirect($this->url->link('account/edit', array('language' => $this->config->get('config_language'))));
    }

    public function edit_email(){
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/edit', array('language' => $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        if(($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate_email()){
            $this->load->model('account/customer');

            $this->model_account_customer->editEmail($this->customer->getId(), $this->request->post['email']);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('account/edit', array('language' => $this->config->get('config_language'))));
        } else {
            $this->session->data['error'] = $this->error;
        }

        $this->response->redirect($this->url->link('account/edit', array('language' => $this->config->get('config_language'))));
    }

    protected function validate_info()
    {
        $this->load->language('account/edit');

        if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
            $this->error['firstname'] = $this->language->get('error_firstname');
        }

        if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
            $this->error['lastname'] = $this->language->get('error_lastname');
        }

        if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
            $this->error['telephone'] = $this->language->get('error_telephone');
        }

        // Custom field validation
        $this->load->model('account/custom_field');

        $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] == 'account') {
                if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                    $this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                    $this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
                }
            }
        }

        return !$this->error;
    }

    protected function validate_password()
    {
        $this->load->language('account/password');

        $this->load->model('account/customer');

        if(!password_verify($this->request->post['old-password'], $this->model_account_customer->getCustomer($this->customer->getId())['password'])){
            $this->error['old-password'] = $this->language->get('error_old_password');
        }

        if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
            $this->error['password'] = $this->language->get('error_password');
        }

        if ($this->request->post['confirm'] != $this->request->post['password']) {
            $this->error['confirm'] = $this->language->get('error_confirm');
        }

        return !$this->error;
    }

    protected function validate_email()
    {
        $this->load->language('account/edit');

        $this->load->model('account/customer');

        if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error['email'] = $this->language->get('error_email');
        }

        if (($this->customer->getEmail() != $this->request->post['email']) && $this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
            $this->error['warning'] = $this->language->get('error_exists');
        }

        if(!password_verify($this->request->post['email-password'], $this->model_account_customer->getCustomer($this->customer->getId())['password'])){
            $this->error['email_password'] = $this->language->get('error_old_password');
        }

        return !$this->error;
    }

    protected function validate()
    {
        $data = array();
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        }

        if (isset($this->error['firstname'])) {
            $data['error_firstname'] = $this->error['firstname'];
        }

        if (isset($this->error['lastname'])) {
            $data['error_lastname'] = $this->error['lastname'];
        }

        if (isset($this->error['email'])) {
            $data['error_email'] = $this->error['email'];
        }

        if (isset($this->error['telephone'])) {
            $data['error_telephone'] = $this->error['telephone'];
        }

        if (isset($this->error['custom_field'])) {
            $data['error_custom_field'] = $this->error['custom_field'];
        }

        if (isset($this->error['old_password'])) {
            $data['error_old_password'] = $this->error['old_password'];
        }

        if (isset($this->error['password'])) {
            $data['error_password'] = $this->error['password'];
        }

        if (isset($this->error['confirm'])) {
            $data['error_confirm'] = $this->error['confirm'];
        }

        if (isset($this->error['email_password'] )) {
            $data['error_email_password'] = $this->error['email_password'];
        }

        return $data;
    }

}
