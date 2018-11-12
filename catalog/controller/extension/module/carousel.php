<?php

class ControllerExtensionModuleCarousel extends Controller
{
    public function index($setting)
    {
        static $module = 0;

        $this->load->model('design/banner');
        $this->load->model('tool/image');
        $this->load->model('account/wishlist');


        $this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
        $this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
        $this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.jquery.js');

        $this->load->language('extension/module/special');

        $this->load->model('catalog/product');

        $this->load->model('catalog/category');

        $this->load->model('tool/image');

        $data['products'] = array();

        $filter_data = array(
            'sort' => 'pd.name',
            'order' => 'ASC',
            'start' => 0,
            'limit' => 100
        );

        $category = $this->model_catalog_category->getCategories(0);

        if ($setting['name'] == "SpecialsCarousel") {
            $results = $this->model_catalog_product->getProductSpecials($filter_data);
            $data['heading_title'] = "Offres";
            $data['category_link'] = $this->url->link('product/category', array("path" => $category[0]['category_id'], "special" => "special"));
        } else {
            $results = $this->model_catalog_product->getProducts($filter_data);
            $data['heading_title'] = "Nouveauté";
            $data['category_link'] = $this->url->link('product/category', array("path" => $category[0]['category_id']));
        }

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

                $simulate = array();

                $results = $this->model_catalog_product->getProductImages($result['product_id']);

                foreach ($results as $r) {
                    $simulate[] = array(
                        'popup' => $this->model_tool_image->resize($r['image'], $setting['width'], $setting['height'])
                    );
                }
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

                $quantity = $this->model_catalog_product->getTotalQuantityProduct($result['product_id']);

                if ($quantity <= 0) {
                    $stock = "Out of Stock";
                } else {
                    $stock = '';
                }

                $data['products'][] = array(
                    'product_id' => $result['product_id'],
                    'thumb' => $image,
                    'manufacturer' => $result['manufacturer'],
                    'name' => (strlen($result['name']) <= 12) ? $result['name'] : utf8_substr(trim(strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                    "stock" => $stock,
                    'price' => $price,
                    'special' => $special,
                    'favorite' => $favorite,
                    'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $result['product_id'])
                );
            }
        }
        $data['module'] = $module++;

        return $this->load->view('extension/module/carousel', $data);
    }
}
