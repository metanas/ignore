<?php

class ControllerExtensionModuleLatest extends Controller
{
    public function index($setting)
    {
        $this->load->language('extension/module/latest');

        $this->load->model('catalog/product');

        $this->load->model('tool/image');

        $data['products'] = array();

        $filter_data = array(
            'sort' => 'p.date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' => $setting['limit']
        );

        $results = $this->model_catalog_product->getProducts($filter_data);

        if ($results) {
            foreach ($results as $result) {
                if ($result['image']) {
                    $image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
                }

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = false;
                }

                if ((float)$result['special']) {
                    $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $special = false;
                }

                if ($this->config->get('config_tax')) {
                    $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
                } else {
                    $tax = false;
                }

                if ($this->config->get('config_review_status')) {
                    $rating = $result['rating'];
                } else {
                    $rating = false;
                }

                $simulate = array();

                $results = $this->model_catalog_product->getProductImages($result['product_id']);

                foreach ($results as $r) {
                    $simulate[] = array(
                        'popup' => $this->model_tool_image->resize($r['image'], $setting['width'], $setting['height'])
                    );
                }

                $this->load->model('account/wishlist');

                if ($this->customer->isLogged()) {
                    if ($this->model_account_wishlist->isExist($result['product_id']) == 1) {
                        $favorite = $this->model_tool_image->resize("favoriteAdded.png", 100, 100);
                    } else $favorite = $this->model_tool_image->resize("favorite.png", 100, 100);
                } else {
                    if (isset($this->session->data['wishlist']))
                        if (in_array($result['product_id'], $this->session->data['wishlist'])) {
                            $favorite = $this->model_tool_image->resize("favoriteAdded.png", 100, 100);
                        } else $favorite = $this->model_tool_image->resize("favorite.png", 100, 100);
                    else $favorite = $this->model_tool_image->resize("favorite.png", 100, 100);
                }
                $data['products'][] = array(
                    'product_id' => $result['product_id'],
                    'thumb' => $image,
                    'name' => (strlen($result['name']) <= 12) ? $result['name'] : utf8_substr(trim(strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                    'price' => $price,
                    'special' => $special,
                    'favorite' => $favorite,
                    'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $result['product_id']),
                );

            }
            return $this->load->view('extension/module/latest', $data);
        }
    }
}
