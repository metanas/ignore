<?php

class ModelAccountAddress extends Model
{
    public function addAddress($customer_id, $data)
    {
        $this->db->query("INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . (int)$customer_id . "', firstname = '" . $this->db->escape((string)$data['firstname']) . "', lastname = '" . $this->db->escape((string)$data['lastname']) . "', address_1 = '" . $this->db->escape((string)$data['address_1']) . "', address_2 = '" . $this->db->escape((string)$data['address_2']) . "', postcode = '" . $this->db->escape((string)$data['postcode']) . "', city = '" . $this->db->escape((string)$data['city']) . "', telephone = '" . $this->db->escape((string)$data['telephone']) . "', country = '" . $this->db->escape((string)$data['country']) . "' , custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "'");

        $address_id = $this->db->getLastId();

        if (!empty($data['default'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
        }

        return $address_id;
    }

    public function editAddress($address_id, $data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "address SET firstname = '" . $this->db->escape((string)$data['firstname']) . "', lastname = '" . $this->db->escape((string)$data['lastname']) . "', address_1 = '" . $this->db->escape((string)$data['address_1']) . "', address_2 = '" . $this->db->escape((string)$data['address_2']) . "', postcode = '" . $this->db->escape((string)$data['postcode']) . "', city = '" . $this->db->escape((string)$data['city']) . "', telephone='" . $this->db->escape((string)$data['telephone']) . "', country = '" . $this->db->escape((string)$data['country']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "' WHERE address_id  = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

        if (!empty($data['default'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$this->customer->getId() . "'");
        }
    }

    public function deleteAddress($address_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
    }

    public function getAddress($address_id)
    {
        $address_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

        if ($address_query->num_rows) {

            $address_data = array(
                'address_id' => $address_query->row['address_id'],
                'firstname' => $address_query->row['firstname'],
                'lastname' => $address_query->row['lastname'],
                'address_1' => $address_query->row['address_1'],
                'address_2' => $address_query->row['address_2'],
                'postcode' => $address_query->row['postcode'],
                'city' => $address_query->row['city'],
                'country' => $address_query->row['country'],
                'telephone' => $address_query->row['telephone'],
                'custom_field' => json_decode($address_query->row['custom_field'], true)
            );

            return $address_data;
        } else {
            return false;
        }
    }

    public function getAddresses()
    {
        $address_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

        foreach ($query->rows as $result) {

            $address_data[$result['address_id']] = array(
                'address_id' => $result['address_id'],
                'firstname' => $result['firstname'],
                'lastname' => $result['lastname'],
                'address_1' => $result['address_1'],
                'address_2' => $result['address_2'],
                'postcode' => $result['postcode'],
                'city' => $result['city'],
                'country' => $result['country'],
                'telephone' => $result['telephone'],
                'custom_field' => json_decode($result['custom_field'], true)
            );
        }

        return $address_data;
    }

    public function getTotalAddresses()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

        return $query->row['total'];
    }
}
