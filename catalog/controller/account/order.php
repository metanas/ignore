<?php
use Spipu\Html2Pdf\Html2Pdf;
class ControllerAccountOrder extends Controller
{
    public function index()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/order', array('language=' . $this->config->get('config_language')));

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        $this->load->language('account/order');

        $this->document->setTitle($this->language->get('heading_title'));

        $url = '';

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['orders'] = array();

        $this->load->model('account/order');

        $order_total = $this->model_account_order->getTotalOrders();

        $results = $this->model_account_order->getOrders(($page - 1) * 10, 10);

        foreach ($results as $result) {
            $product_total = $this->model_account_order->getTotalOrderProductsByOrderId($result['order_id']);
            $voucher_total = $this->model_account_order->getTotalOrderVouchersByOrderId($result['order_id']);

            $data['orders'][] = array(
                'order_id' => $result['order_id'],
                'name' => $result['firstname'] . ' ' . $result['lastname'],
                'status' => $result['status'],
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'products' => ($product_total + $voucher_total),
                'total' => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                'view' => $this->url->link('account/order/info', 'language=' . $this->config->get('config_language') . '&order_id=' . $result['order_id']),
            );
        }

        $pagination = new Pagination();
        $pagination->total = $order_total;
        $pagination->page = $page;
        $pagination->limit = 10;
        $pagination->url = $this->url->link('account/order', array('language' => $this->config->get('config_language'), 'page' => '{page}'));

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($order_total - 10)) ? $order_total : ((($page - 1) * 10) + 10), $order_total, ceil($order_total / 10));

        $data['continue'] = $this->url->link('account/edit', array('language' => $this->config->get('config_language')));

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/order_list', $data));
    }

    public function info()
    {
        $this->load->language('account/order');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/order/info', 'language=' . $this->config->get('config_language') . '&order_id=' . $order_id);

            $this->response->redirect($this->url->link('account/login', 'language=' . $this->config->get('config_language')));
        }

        $this->load->model('account/order');

        $order_info = $this->model_account_order->getOrder($order_id);

        if ($order_info) {
            $this->document->setTitle($this->language->get('text_order'));

            $url = '';

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            if (isset($this->session->data['error'])) {
                $data['error_warning'] = $this->session->data['error'];

                unset($this->session->data['error']);
            } else {
                $data['error_warning'] = '';
            }

            if (isset($this->session->data['success'])) {
                $data['success'] = $this->session->data['success'];

                unset($this->session->data['success']);
            } else {
                $data['success'] = '';
            }

            if ($order_info['invoice_no']) {
                $data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
            } else {
                $data['invoice_no'] = '';
            }

            $data['order_id'] = (int)$this->request->get['order_id'];
            $data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));

            $data['payment_method'] = $order_info['payment_method'];

            $format = '{firstname} {lastname}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . 'Tel: {telephone}' . "\n" . '{country}';


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

            $replace = array(
                'firstname' => $order_info['shipping_firstname'],
                'lastname' => $order_info['shipping_lastname'],
                'address_1' => $order_info['shipping_address_1'],
                'address_2' => $order_info['shipping_address_2'],
                'city' => $order_info['shipping_city'],
                'postcode' => $order_info['shipping_postcode'],
                'telephone' => $order_info['shipping_telephone'],
                'country' => $order_info['shipping_country'],
            );

            $data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

            $replace = array(
                'firstname' => $order_info['billing_firstname'],
                'lastname' => $order_info['billing_lastname'],
                'address_1' => $order_info['billing_address_1'],
                'address_2' => $order_info['billing_address_2'],
                'city' => $order_info['billing_city'],
                'postcode' => $order_info['billing_postcode'],
                'telephone' => $order_info['billing_telephone'],
                'country' => $order_info['billing_country'],
            );

            $data['billing_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

            $data['shipping_method'] = $order_info['shipping_method'];

            $this->load->model('catalog/product');
            $this->load->model('tool/upload');

            // Products
            $data['products'] = array();

            $products = $this->model_account_order->getOrderProducts($this->request->get['order_id']);

            foreach ($products as $product) {
                $option_data = array();

                $options = $this->model_account_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

                foreach ($options as $option) {
                    if ($option['type'] != 'file') {
                        $value = $option['value'];
                    } else {
                        $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                        if ($upload_info) {
                            $value = $upload_info['name'];
                        } else {
                            $value = '';
                        }
                    }

                    $option_data[] = array(
                        'name' => $option['name'],
                        'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                    );
                }

                $product_info = $this->model_catalog_product->getProduct($product['product_id']);

                if ($product_info) {
                    $reorder = $this->url->link('account/order/reorder', 'language=' . $this->config->get('config_language') . '&order_id=' . $order_id . '&order_product_id=' . $product['order_product_id']);
                } else {
                    $reorder = '';
                }

                $data['products'][] = array(
                    'name' => $product['name'],
                    'manufacturer' => $product['manufacturer'],
                    'link' => $this->url->link("product/product", array("language" => $this->config->get("config_language"), "product_id" => $product['product_id'])),
                    'option' => $option_data,
                    'quantity' => $product['quantity'],
                    'price' => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                    'total' => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
                    'reorder' => $reorder,
                    'return' => $this->url->link('account/return/add', 'language=' . $this->config->get('config_language') . '&order_id=' . $order_info['order_id'] . '&product_id=' . $product['product_id'])
                );
            }

            // Voucher
            $data['vouchers'] = array();

            $vouchers = $this->model_account_order->getOrderVouchers($this->request->get['order_id']);

            foreach ($vouchers as $voucher) {
                $data['vouchers'][] = array(
                    'description' => $voucher['description'],
                    'amount' => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value'])
                );
            }

            // Totals
            $data['totals'] = array();

            $totals = $this->model_account_order->getOrderTotals($this->request->get['order_id']);

            foreach ($totals as $total) {
                $data['totals'][] = array(
                    'title' => $total['title'],
                    'text' => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']),
                );
            }

            // History
            $data['histories'] = array();

            $results = $this->model_account_order->getOrderHistories($this->request->get['order_id']);

            foreach ($results as $result) {
                $data['histories'][] = array(
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'status' => $result['status'],
                    'comment' => $result['notify'] ? nl2br($result['comment']) : ''
                );
            }

            $data['download'] = $this->url->link('account/order/invoice', array('order_id' => $order_id, 'language' => $this->config->get('config_language')));

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('account/order_info', $data));
        } else {
            return new Action('error/not_found');
        }
    }

    public function reorder()
    {
        $this->load->language('account/order');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        $this->load->model('account/order');

        $order_info = $this->model_account_order->getOrder($order_id);

        if ($order_info) {
            if (isset($this->request->get['order_product_id'])) {
                $order_product_id = $this->request->get['order_product_id'];
            } else {
                $order_product_id = 0;
            }

            $order_product_info = $this->model_account_order->getOrderProduct($order_id, $order_product_id);

            if ($order_product_info) {
                $this->load->model('catalog/product');

                $product_info = $this->model_catalog_product->getProduct($order_product_info['product_id']);

                if ($product_info) {
                    $option_data = array();

                    $order_options = $this->model_account_order->getOrderOptions($order_product_info['order_id'], $order_product_id);

                    foreach ($order_options as $order_option) {
                        if ($order_option['type'] == 'select' || $order_option['type'] == 'radio' || $order_option['type'] == 'image') {
                            $option_data[$order_option['product_option_id']] = $order_option['product_option_value_id'];
                        } elseif ($order_option['type'] == 'checkbox') {
                            $option_data[$order_option['product_option_id']][] = $order_option['product_option_value_id'];
                        } elseif ($order_option['type'] == 'text' || $order_option['type'] == 'textarea' || $order_option['type'] == 'date' || $order_option['type'] == 'datetime' || $order_option['type'] == 'time') {
                            $option_data[$order_option['product_option_id']] = $order_option['value'];
                        } elseif ($order_option['type'] == 'file') {
                            $option_data[$order_option['product_option_id']] = $this->encryption->encrypt($this->config->get('config_encryption'), $order_option['value']);
                        }
                    }

                    $this->cart->add($order_product_info['product_id'], $order_product_info['quantity'], $option_data);

                    $this->session->data['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product_info['product_id']), $product_info['name'], $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language')));

                    unset($this->session->data['shipping_method']);
                    unset($this->session->data['shipping_methods']);
                    unset($this->session->data['payment_method']);
                    unset($this->session->data['payment_methods']);
                } else {
                    $this->session->data['error'] = sprintf($this->language->get('error_reorder'), $order_product_info['name']);
                }
            }
        }

        $this->response->redirect($this->url->link('account/order/info', 'language=' . $this->config->get('config_language') . '&order_id=' . $order_id));
    }

    public function invoice()
    {

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/order', array('language=' . $this->config->get('config_language')));

            $this->response->redirect("account/login", array('language' => $this->config->get("config_language")));
        }


        $this->load->language('account/order');

        $data['title'] = $this->language->get('text_order_detail');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];

            $this->load->model("account/order");

            $order_info = $this->model_account_order->getOrder($order_id);

            if ($order_info) {
                $this->load->model('setting/setting');

                $store_info = $this->model_setting_setting->getSetting('config', $order_info['store_id']);

                if ($store_info) {
                    $store_address = $store_info['config_address'];
                    $store_email = $store_info['config_email'];
                    $store_telephone = $store_info['config_telephone'];
                    $store_fax = $store_info['config_fax'];
                } else {
                    $store_address = $this->config->get('config_address');
                    $store_email = $this->config->get('config_email');
                    $store_telephone = $this->config->get('config_telephone');
                    $store_fax = $this->config->get('config_fax');
                }

                if ($order_info['invoice_no']) {
                    $invoice_no = $order_info['invoice_prefix'] . $order_info['invoice_no'];
                } else {
                    $invoice_no = '';
                }

                $format = '{firstname} {lastname}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{country}';

                $find = array(
                    '{firstname}',
                    '{lastname}',
                    '{address_1}',
                    '{address_2}',
                    '{city}',
                    '{postcode}',
                    '{country}'
                );

                $replace = array(
                    'firstname' => $order_info['shipping_firstname'],
                    'lastname' => $order_info['shipping_lastname'],
                    'address_1' => $order_info['shipping_address_1'],
                    'address_2' => $order_info['shipping_address_2'],
                    'city' => $order_info['shipping_city'],
                    'postcode' => $order_info['shipping_postcode'],
                    'country' => $order_info['shipping_country']
                );

                $shipping_address = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

                $product_data = array();

                $products = $this->model_account_order->getOrderProducts($order_id);

                foreach ($products as $product) {
                    $option_data = array();

                    $options = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

                    foreach ($options as $option) {
                        if ($option['type'] != 'file') {
                            $value = $option['value'];
                        }

                        $option_data[] = array(
                            'name' => $option['name'],
                            'value' => $value
                        );
                    }

                    $product_data[] = array(
                        'name' => $product['name'],
                        'manufacturer' => $product['manufacturer'],
                        'option' => $option_data,
                        'quantity' => $product['quantity'],
                        'price' => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                        'total' => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
                    );
                }

                $voucher_data = array();

                $vouchers = $this->model_account_order->getOrderVouchers($order_id);

                foreach ($vouchers as $voucher) {
                    $voucher_data[] = array(
                        'description' => $voucher['description'],
                        'amount' => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value'])
                    );
                }

                $total_data = array();

                $totals = $this->model_account_order->getOrderTotals($order_id);

                foreach ($totals as $total) {
                    $total_data[] = array(
                        'title' => $total['title'],
                        'text' => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])
                    );
                }

                $data['order'] = array(
                    'order_id' => $order_id,
                    'invoice_no' => $invoice_no,
                    'customer' => $order_info['firstname'] . " " . $order_info['lastname'],
                    'date_added' => date($this->language->get('date_format_short'), strtotime($order_info['date_added'])),
                    'store_name' => $order_info['store_name'],
                    'store_url' => rtrim($order_info['store_url'], '/'),
                    'store_address' => nl2br($store_address),
                    'store_email' => $store_email,
                    'store_telephone' => $store_telephone,
                    'store_fax' => $store_fax,
                    'email' => $order_info['email'],
                    'telephone' => $order_info['telephone'],
                    'shipping_address' => $shipping_address,
                    'shipping_method' => $order_info['shipping_method'],
                    'payment_method' => $order_info['payment_method'],
                    'product' => $product_data,
                    'voucher' => $voucher_data,
                    'total' => $total_data
                );
            }

            $pdf = new Html2Pdf('P', 'A4', 'fr');
            $pdf->writeHTML($this->load->view('account/order_invoice', $data));
            $pdf->Output($data['order']["customer"] . "-" . $data["order"]["date_added"]. ".pdf");
        }
    }
}