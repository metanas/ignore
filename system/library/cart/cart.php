<?php

namespace Cart;
class Cart
{
    private $data = array();

    public function __construct($registry)
    {
        $this->config = $registry->get('config');
        $this->customer = $registry->get('customer');
        $this->session = $registry->get('session');
        $this->db = $registry->get('db');
        $this->tax = $registry->get('tax');
        $this->weight = $registry->get('weight');

        // Remove all the expired carts with no customer ID
        $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE (api_id > '0' OR customer_id = '0') AND date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

        if ($this->customer->getId()) {
            // We want to change the session ID on all the old items in the customers cart
            $this->db->query("UPDATE " . DB_PREFIX . "cart SET session_id = '" . $this->db->escape($this->session->getId()) . "' WHERE api_id = '0' AND customer_id = '" . (int)$this->customer->getId() . "'");

            // Once the customer is logged in we want to update the customers cart
            $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '0' AND customer_id = '0' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

            foreach ($cart_query->rows as $cart) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart['cart_id'] . "'");

                // The advantage of using $this->add is that it will check if the products already exist and increaser the quantity if necessary.
                $this->add($cart['product_id'], $cart['quantity'], json_decode($cart['option']), $cart['recurring_id']);
            }
        }
    }

    public function getProducts()
    {
        if (!$this->data) {

            $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

            foreach ($cart_query->rows as $cart) {
                $stock = true;

                $product_query = $this->db->query("SELECT *,p.name as name, m.name as manufacturer, m.image as manufacturer_image, p.image as image FROM " . DB_PREFIX . "product_to_store p2s LEFT JOIN " . DB_PREFIX . "product p ON (p2s.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON(m.manufacturer_id = p.manufacturer_id) WHERE p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p2s.product_id = '" . (int)$cart['product_id'] . "' AND p.date_available <= NOW() AND p.status = '1'");

                if ($product_query->num_rows && ($cart['quantity'] > 0)) {
                    $option_price = 0;

                    $option_data = array();

                    foreach (json_decode($cart['option']) as $product_option_id => $value) {
                        $option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$product_option_id . "' AND po.product_id = '" . (int)$cart['product_id'] . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                        if ($option_query->num_rows) {
                            if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio' || $option_query->row['type'] == 'size') {
                                $option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                                if ($option_value_query->num_rows) {

                                    $option_data[] = array(
                                        'product_option_id' => $product_option_id,
                                        'product_option_value_id' => $value,
                                        'option_id' => $option_query->row['option_id'],
                                        'option_value_id' => $option_value_query->row['option_value_id'],
                                        'name' => $option_query->row['name'],
                                        'value' => $option_value_query->row['name'],
                                        'type' => $option_query->row['type'],
                                        'quantity' => $option_value_query->row['quantity'],
                                    );
                                }
                            } elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
                                foreach ($value as $product_option_value_id) {
                                    $option_value_query = $this->db->query("SELECT pov.option_value_id, pov.quantity, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                                    if ($option_value_query->num_rows) {

                                        $option_data[] = array(
                                            'product_option_id' => $product_option_id,
                                            'product_option_value_id' => $product_option_value_id,
                                            'option_id' => $option_query->row['option_id'],
                                            'option_value_id' => $option_value_query->row['option_value_id'],
                                            'name' => $option_query->row['name'],
                                            'value' => $option_value_query->row['name'],
                                            'type' => $option_query->row['type'],
                                            'quantity' => $option_value_query->row['quantity']
                                        );
                                    }
                                }
                            } elseif ($option_query->row['type'] == 'text' || $option_query->row['type'] == 'textarea' || $option_query->row['type'] == 'file' || $option_query->row['type'] == 'date' || $option_query->row['type'] == 'datetime' || $option_query->row['type'] == 'time') {
                                $option_data[] = array(
                                    'product_option_id' => $product_option_id,
                                    'product_option_value_id' => '',
                                    'option_id' => $option_query->row['option_id'],
                                    'option_value_id' => '',
                                    'name' => $option_query->row['name'],
                                    'value' => $value,
                                    'type' => $option_query->row['type'],
                                    'quantity' => '',
                                );
                            }
                        }
                    }

                    $price = $product_query->row['price'];
                    $old_price = 0;

                    // Product Discounts
                    $discount_quantity = 0;

                    foreach ($cart_query->rows as $cart_2) {
                        if ($cart_2['product_id'] == $cart['product_id']) {
                            $discount_quantity += $cart_2['quantity'];
                        }
                    }

                    $product_discount_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity <= '" . (int)$discount_quantity . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");

                    if ($product_discount_query->num_rows) {
                        $price = $product_discount_query->row['price'];
                    }

                    // Product Specials
                    $product_special_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");

                    if ($product_special_query->num_rows) {
                        $old_price = $product_query->row['price'];
                        $price = $product_special_query->row['price'];
                    }

                    $this->data[] = array(
                        'cart_id' => $cart['cart_id'],
                        'product_id' => $product_query->row['product_id'],
                        'name' => $product_query->row['name'],
                        'manufacturer' => $product_query->row['manufacturer'],
                        'ref' => $product_query->row['ref'],
                        'color' => $product_query->row['color'],
                        'shipping' => $product_query->row['shipping'],
                        'image' => $product_query->row['image'],
                        'option' => $option_data,
                        'quantity' => $cart['quantity'],
                        'minimum' => $product_query->row['minimum'],
                        'stock' => $stock,
                        'price' => ($price + $option_price),
                        'old_price' => isset($old_price) ? ($old_price + $option_price) : null,
                        'total' => ($price + $option_price) * $cart['quantity'],
                        'tax_class_id' => $product_query->row['tax_class_id'],
                    );
                } else {
                    $this->remove($cart['cart_id']);
                }
            }
        }

        return $this->data;
    }

    public function getProduct($cart_id)
    {
        $query = $this->db->query("SELECT p.price as old_price, ps.price FROM " . DB_PREFIX . "cart ct left Join " . DB_PREFIX . "product_special ps on(ps.product_id=ct.product_id) left JOIN " . DB_PREFIX ."product p on(p.product_id=ct.product_id) WHERE ct.cart_id='" . (int)$cart_id . "' AND ct.api_id='" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND ct.customer_id = '" . (int)$this->customer->getId() . "' AND ct.session_id = '" . $this->db->escape($this->session->getId()) . "'");

        return $query->row;

    }

    public function add($product_id, $quantity = 1, $option = array(), $recurring_id = 0)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");

        if (!$query->row['total']) {
            $this->db->query("INSERT " . DB_PREFIX . "cart SET api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', customer_id = '" . (int)$this->customer->getId() . "', session_id = '" . $this->db->escape($this->session->getId()) . "', product_id = '" . (int)$product_id . "', recurring_id = '" . (int)$recurring_id . "', `option` = '" . $this->db->escape(json_encode($option)) . "', quantity = '" . (int)$quantity . "', date_added = NOW()");
        }
//        else {
//            $this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = (quantity + " . (int)$quantity . ") WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
//        }

        $this->data = array();
    }

    public function update($cart_id, $quantity)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = '" . (int)$quantity . "' WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

        $this->data = array();
    }

    public function remove($cart_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

        $this->data = array();
    }

    public function clear()
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

        $this->data = array();
    }

    public function getRecurringProducts()
    {
        $product_data = array();

        foreach ($this->getProducts() as $value) {
            if ($value['recurring']) {
                $product_data[] = $value;
            }
        }

        return $product_data;
    }

    public function getWeight()
    {
        $weight = 0;

        foreach ($this->getProducts() as $product) {
            if ($product['shipping']) {
                $weight += $this->weight->convert($product['weight'], $product['weight_class_id'], $this->config->get('config_weight_class_id'));
            }
        }

        return $weight;
    }

    public function getSubTotal()
    {
        $total = 0;

        foreach ($this->getProducts() as $product) {
            $total += $product['total'];
        }

        return $total;
    }

    public function getTaxes()
    {
        $tax_data = array();

        foreach ($this->getProducts() as $product) {
            if ($product['tax_class_id']) {
                $tax_rates = $this->tax->getRates($product['price'], $product['tax_class_id']);

                foreach ($tax_rates as $tax_rate) {
                    if (!isset($tax_data[$tax_rate['tax_rate_id']])) {
                        $tax_data[$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $product['quantity']);
                    } else {
                        $tax_data[$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $product['quantity']);
                    }
                }
            }
        }

        return $tax_data;
    }

    public function getTotal()
    {
        $total = 0;

        foreach ($this->getProducts() as $product) {
            $total += $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'];
        }

        return $total;
    }

    public function countProducts()
    {
        $product_total = 0;

        $products = $this->getProducts();

        foreach ($products as $product) {
            $product_total += $product['quantity'];
        }

        return $product_total;
    }

    public function hasProducts()
    {
        return count($this->getProducts());
    }

    public function hasRecurringProducts()
    {
        return count($this->getRecurringProducts());
    }

    public function hasStock()
    {
        foreach ($this->getProducts() as $product) {
            if (!$product['stock']) {
                return false;
            }
        }

        return true;
    }

    public function hasShipping()
    {
        foreach ($this->getProducts() as $product) {
            if ($product['shipping']) {
                return true;
            }
        }

        return false;
    }

    public function getTotalQuantityProduct($product_id)
    {
        $query = $this->db->query("SELECT SUM(pov.quantity) AS total FROM " . DB_PREFIX . "product_option_value as pov left join " . DB_PREFIX . "option o on(o.option_id=pov.option_id) where o.type='size' and pov.product_id='" . (int)$product_id . "'");

        return $query->row['total'];
    }
}
