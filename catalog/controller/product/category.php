<?php

class ControllerProductCategory extends Controller
{
    public function index()
    {
        $this->load->language('product/category');

        $this->load->model('catalog/category');

        $this->load->model('catalog/product');

        $this->load->model('tool/image');

        $this->load->model('account/wishlist');


        $this->document->addScript('catalog/view/javascript/filter.js');
        $this->document->addScript('catalog/view/javascript/loading.js');
        $this->document->addStyle('catalog/view/theme/default/stylesheet/loading.css');

        $filter = array();

        if (isset($this->request->get['manufacture'])) {
            $filter['manufacture'] = explode("_",  $this->request->get['manufacture']);
        }

        if (isset($this->request->get['color'])) {
            $filter['color'] = explode("_", $this->request->get['color']);
        }

        if (isset($this->request->get['size'])) {
            $filter['size'] = explode("_", str_replace(".", " ", $this->request->get['size']));
        }

        if (isset($this->request->get['price-min'])) {
            $filter['price']['min'] = $this->request->get['price-min'];
        }

        if (isset($this->request->get['special'])) {
            $filter['special'] = $this->request->get['special'];
        }

        if (isset($this->request->get['price-max'])) {
            $filter['price']['max'] = $this->request->get['price-max'];
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'p.sort_order';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        if (isset($this->request->get['limit'])) {
            $limit = (int)$this->request->get['limit'];
        } else {
            $limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
        }

        if (isset($this->request->get['path'])) {
            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            $path = '';

            $parts = explode('_', (string)$this->request->get['path']);

            $category_id = (int)array_pop($parts);

        } else {
            $category_id = 0;
        }

        $category_info = $this->model_catalog_category->getCategory($category_id);

        if ($category_info) {
            $data['heading_title'] = $category_info['name'];

            $this->document->setTitle($category_info['name']);

            if ($category_info['image']) {
                $data['thumb'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
            } else {
                $data['thumb'] = '';
            }

            $data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            if (isset($this->request->get['manufacture'])) {
                $url .= '&manufacture=' . $this->request->get['manufacture'];
            }

            if (isset($this->request->get['color'])) {
                $url .= '&color=' . $this->request->get['color'];
            }

            if (isset($this->request->get['size'])) {
                $url .= '&size=' . $this->request->get['size'];
            }

            if (isset($this->request->get['price-min'])) {
                $url .= '&price-min=' . $this->request->get['price-min'];
            }

            if (isset($this->request->get['price-max'])) {
                $url .= '&price-max=' . $this->request->get['price-max'];
            }

            $data['categories'] = array();

            $results = $this->model_catalog_category->getCategories($category_id);

            foreach ($results as $result) {
                $filter_data = array(
                    'filter_category_id' => $result['category_id'],
                    'filter_sub_category' => true
                );

                $data['categories'][] = array(
                    'name' => $result['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                    'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '_' . $result['category_id'] . $url)
                );
            }

            $data['products'] = array();

            $filter_data = array(
                'filter_category_id' => $category_id,
                'filter_sub_category' => $this->config->get('config_product_category') ? true : false,
                'filter_filter' => $filter,
                'sort' => $sort,
                'order' => $order,
                'start' => ($page - 1) * $limit,
                'limit' => $limit
            );

            $data['filter'] = $filter;

            $product_total = $this->model_catalog_product->getTotalProducts($filter_data);

            $data['product_total'] = $product_total;

            $filter['category'] = $category_id;

            $products_option = $this->model_catalog_product->getFilterProducts($filter);

            $manufactures = array();
            $sizes = array();
            $colors = array();
            $price = array();
            foreach ($products_option as $option) {
                $manufactures[] = $option['manufacture'];
                $sizes[] = $option['size'];
                $colors[] = $option['color'] . "$" . $option['color_hex'];
                $price[] = $option['price'];
            }

            $data['products_manufacture'] = array_unique($manufactures);
            $data['products_size'] = array_unique($sizes);
            $data['products_color'] = array_unique($colors);
            sort($data['products_size']);
            sort($data['products_color']);
            sort($data['products_manufacture']);


            if (!empty($price)) {
                $data['price_max'] = (int)max($price);
                $data['price_min'] = (int)min($price);
            }

            $results = $this->model_catalog_product->getProducts($filter_data);

            foreach ($results as $result) {
                if ($result['image']) {
                    $image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
                }

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = false;
                }

                if ((float)$result['special']) {
                    $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $discount = intval((($result['price'] - $result['special']) * 100) / $result['price']);
                } else {
                    $special = false;
                    $discount = false;
                }

                if ($this->customer->isLogged()) {
                    if ((int)$this->model_account_wishlist->isExist($result['product_id']) == true) {
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
                    'price' => $price,
                    'discount' => $discount,
                    'stock' => $stock,
                    'special' => $special,
                    'favorite' => $favorite,
                    'minimum' => $result['minimum'] > 0 ? $result['minimum'] : 1,
                    'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'] . $url)
                );
            }

            $url = '';

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            if (isset($this->request->get['manufacture'])) {
                $url .= '&manufacture=' . $this->request->get['manufacture'];
            }

            if (isset($this->request->get['color'])) {
                $url .= '&color=' . $this->request->get['color'];
            }

            if (isset($this->request->get['size'])) {
                $url .= '&size=' . $this->request->get['size'];
            }

            if (isset($this->request->get['price-min'])) {
                $url .= '&price-min=' . $this->request->get['price-min'];
            }

            if (isset($this->request->get['price-max'])) {
                $url .= '&price-max=' . $this->request->get['price-max'];
            }

            $data['sorts'] = array();

            $data['sorts'][] = array(
                'text' => $this->language->get('text_default'),
                'value' => 'p.sort_order-ASC',
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '&sort=p.sort_order&order=ASC' . $url)
            );

            $data['sorts'][] = array(
                'text' => $this->language->get('text_price_asc'),
                'value' => 'p.price-ASC',
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '&sort=p.price&order=ASC' . $url)
            );

            $data['sorts'][] = array(
                'text' => $this->language->get('text_price_desc'),
                'value' => 'p.price-DESC',
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '&sort=p.price&order=DESC' . $url)
            );

            $pagination = new Pagination();
            $pagination->total = $product_total;
            $pagination->page = $page;
            $pagination->limit = $limit;
            $pagination->url = $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . $url . '&page={page}');

            $data['pagination'] = $pagination->render();

            $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

            // http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
            if ($page == 1) {
                $this->document->addLink($this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id']), 'canonical');
            } else {
                $this->document->addLink($this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id'] . '&page=' . $page), 'canonical');
            }

            if ($page > 1) {
                $this->document->addLink($this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id'] . (($page - 2) ? '&page=' . ($page - 1) : '')), 'prev');
            }

            if ($limit && ceil($product_total / $limit) > $page) {
                $this->document->addLink($this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id'] . '&page=' . ($page + 1)), 'next');
            }

            $data['sort'] = $sort;
            $data['order'] = $order;
            $data['limit'] = $limit;

            $data['currency'] = $this->session->data['currency'];

            $data['content_top'] = $this->load->controller('common/content_top');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');

            $data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

            $content = $this->load->view('product/category', $data);

            $data = array();

            $data['content'] = $content;

            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('common/home', $data));
        } else {
            $this->document->setTitle($this->language->get('text_error'));

            $data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['content'] = $this->load->controller('error/not_found');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    public function filter()
    {
        $this->load->language('product/category');

        $this->load->model('catalog/category');

        $this->load->model('catalog/product');

        $this->load->model('tool/image');

        $this->load->model('account/wishlist');

        $filter = array();

        if (isset($this->request->get['manufacture'])) {
            $filter['manufacture'] = explode("_", $this->request->get['manufacture']);
        }

        if (isset($this->request->get['color'])) {
            $filter['color'] = explode("_", $this->request->get['color']);
        }

        if (isset($this->request->get['size'])) {
            $filter['size'] = explode("_", $this->request->get['size']);
        }

        if (isset($this->request->get['special'])) {
            $filter['special'] = $this->request->get['special'];
        }

        if (isset($this->request->get['price-min'])) {
            $filter['price']['min'] = $this->request->get['price-min'];
        }

        if (isset($this->request->get['price-max'])) {
            $filter['price']['max'] = $this->request->get['price-max'];
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'p.sort_order';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        if (isset($this->request->get['limit'])) {
            $limit = (int)$this->request->get['limit'];
        } else {
            $limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
        }

        if (isset($this->request->get['path'])) {
            $parts = explode('_', (string)$this->request->get['path']);

            $category_id = (int)array_pop($parts);

            $filter_count = array('filter_category_id' => $category_id, 'filter_sub_category' => true);

            $countProd = $this->model_catalog_product->getTotalProducts($filter_count);

            $data['count'] = $countProd;

        } else {
            $category_id = 0;
        }

        $category_info = $this->model_catalog_category->getCategory($category_id);

        if ($category_info) {
            $data['heading_title'] = $category_info['name'];

            $this->document->setTitle($category_info['name']);

            $data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

            if ($category_info['image']) {
                $data['thumb'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
            } else {
                $data['thumb'] = '';
            }

            $data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
            $data['compare'] = $this->url->link('product/compare', 'language=' . $this->config->get('config_language'));

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            if (isset($this->request->get['manufacture'])) {
                $url .= '&manufacture=' . $this->request->get['manufacture'];
            }

            if (isset($this->request->get['color'])) {
                $url .= '&color=' . $this->request->get['color'];
            }

            if (isset($this->request->get['size'])) {
                $url .= '&size=' . $this->request->get['size'];
            }

            if (isset($this->request->get['price-min'])) {
                $url .= '&price-min=' . $this->request->get['price-min'];
            }

            if (isset($this->request->get['price-max'])) {
                $url .= '&price-max=' . $this->request->get['price-max'];
            }

            $data['categories'] = array();

            $results = $this->model_catalog_category->getCategories($category_id);

            foreach ($results as $result) {
                $filter_data = array(
                    'filter_category_id' => $result['category_id'],
                    'filter_sub_category' => true
                );

                $data['categories'][] = array(
                    'name' => $result['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
                    'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '_' . $result['category_id'] . $url)
                );
            }

            $data['products'] = array();

            $filter_data = array(
                'filter_category_id' => $category_id,
                'filter_sub_category' => $this->config->get('config_product_category') ? true : false,
                'filter_filter' => $filter,
                'sort' => $sort,
                'order' => $order,
                'start' => ($page - 1) * $limit,
                'limit' => $limit
            );

            $data['filter'] = $filter;
            $product_total = $this->model_catalog_product->getTotalProducts($filter_data);

            $data['product_total'] = $product_total;
            $filter['category'] = $category_id;

            $products_option = $this->model_catalog_product->getFilterProducts($filter);

            $manufactures = array();
            $sizes = array();
            $colors = array();
            $price = array();
            foreach ($products_option as $option) {
                $manufactures[] = $option['manufacture'];
                $sizes[] = $option['size'];
                $colors[] = $option['color'] . "$" . $option['color_hex'];
                $price[] = $option['price'];
            }

            $data['products_manufacture'] = array_unique($manufactures);
            $data['products_size'] = array_unique($sizes);
            $data['products_color'] = array_unique($colors);
            sort($data['products_size']);
            sort($data['products_color']);
            sort($data['products_manufacture']);

            if (!empty($price)) {
                $data['price_max'] = (int)max($price);
                $data['price_min'] = (int)min($price);
            }

            $results = $this->model_catalog_product->getProducts($filter_data);
            foreach ($results as $result) {
                if ($result['image']) {
                    $image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
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

                if ($this->customer->isLogged()) {
                    if ((int)$this->model_account_wishlist->isExist($result['product_id']) == true) {
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
                    'price' => $price,
                    'stock' => $stock,
                    'special' => $special,
                    'favorite' => $favorite,
                    'minimum' => $result['minimum'] > 0 ? $result['minimum'] : 1,
                    'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'] . $url)
                );
            }
            $data['currency'] = $this->session->data['currency'];

            $url = '';

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            if (isset($this->request->get['manufacture'])) {
                $url .= '&manufacture=' . $this->request->get['manufacture'];
            }

            if (isset($this->request->get['color'])) {
                $url .= '&color=' . $this->request->get['color'];
            }

            if (isset($this->request->get['size'])) {
                $url .= '&size=' . $this->request->get['size'];
            }

            if (isset($this->request->get['price-min'])) {
                $url .= '&price-min=' . $this->request->get['price-min'];
            }

            if (isset($this->request->get['price-max'])) {
                $url .= '&price-max=' . $this->request->get['price-max'];
            }

            $data['sorts'] = array();

            $data['sorts'][] = array(
                'text' => $this->language->get('text_default'),
                'value' => 'p.sort_order-ASC',
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '&sort=p.sort_order&order=ASC' . $url)
            );

            $data['sorts'][] = array(
                'text' => $this->language->get('text_price_asc'),
                'value' => 'p.price-ASC',
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '&sort=p.price&order=ASC' . $url)
            );

            $data['sorts'][] = array(
                'text' => $this->language->get('text_price_desc'),
                'value' => 'p.price-DESC',
                'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '&sort=p.price&order=DESC' . $url)
            );

            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['limit'])) {
                $url .= '&limit=' . $this->request->get['limit'];
            }

            if (isset($this->request->get['manufacture'])) {
                $url .= '&manufacture=' . $this->request->get['manufacture'];
            }

            if (isset($this->request->get['color'])) {
                $url .= '&color=' . $this->request->get['color'];
            }

            if (isset($this->request->get['size'])) {
                $url .= '&size=' . $this->request->get['size'];
            }

            if (isset($this->request->get['price-min'])) {
                $url .= '&price-min=' . $this->request->get['price-min'];
            }

            if (isset($this->request->get['price-max'])) {
                $url .= '&price-max=' . $this->request->get['price-max'];
            }

            $pagination = new Pagination();
            $pagination->total = $product_total;
            $pagination->page = $page;
            $pagination->limit = $limit;
            $pagination->url = $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . $url . '&page={page}');

            $data['pagination'] = $pagination->render();

            $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

            // http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
            if ($page == 1) {
                $this->document->addLink($this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id']), 'canonical');
            } else {
                $this->document->addLink($this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id'] . '&page=' . $page), 'canonical');
            }

            if ($page > 1) {
                $this->document->addLink($this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id'] . (($page - 2) ? '&page=' . ($page - 1) : '')), 'prev');
            }

            if ($limit && ceil($product_total / $limit) > $page) {
                $this->document->addLink($this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_info['category_id'] . '&page=' . ($page + 1)), 'next');
            }

            $data['sort'] = $sort;
            $data['order'] = $order;
            $data['limit'] = $limit;

            $data['content_top'] = $this->load->controller('common/content_top');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');

            $data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

            $this->response->setOutput($this->load->view('product/category', $data));
        } else {

            $this->document->setTitle($this->language->get('text_error'));

            $data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['content'] = $this->load->controller('error/not_found');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }

    public function simulate()
    {
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $product_similar = $this->model_catalog_product->getSimilarProduct($this->request->get['product_id']);

        foreach ($product_similar as $result) {

            $this->model_catalog_product->getProduct($result);

            if ($result['image']) {
                $image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
            } else {
                $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
            }

            $data[] = array(
                'product_id' => $result['product_id'],
                'thumb' => $image,
                'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'])
            );
        }

        if (isset($data))
            $this->response->setOutput(json_encode($data));
    }
}
