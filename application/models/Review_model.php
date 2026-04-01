<?php
class Review_model extends CI_Model {


public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }


    public function upsert($data) {
        // Check existing
        $exists = $this->db
            ->where('product_id', $data['product_id'])
            ->where('user_id', $data['user_id'])
            ->get('reviews')
            ->row();

        if ($exists) {
   
            $this->db->where('id', $exists->id)->update('reviews', $data);
        } else {
        
            $this->db->insert('reviews', $data);
        }
    }

    public function get_by_product($product_id) {
        return $this->db
            ->select('reviews.*, users.name as name')
            ->from('reviews')
            ->join('users', 'users.id = reviews.user_id', 'left')
            ->where('reviews.product_id', $product_id)
            ->where('reviews.is_enabled', 1)
            ->order_by('reviews.id', 'DESC')
            ->get()
            ->result();
    }

  
    public function update_product_rating($product_id) {
        $result = $this->db
            ->select('AVG(rating) as avg_rating, COUNT(*) as total')
            ->where('product_id', $product_id)
            ->where('is_enabled', 1)
            ->get('reviews')
            ->row();

        $this->db->where('id', $product_id)->update('products', [
            'rating' => round($result->avg_rating, 1),
            'review_count' => $result->total
        ]);
    }

    public function get_all() {
    $this->db->select('r.*, u.name as customerName, u.email as customerEmail, p.name as productName');
    $this->db->from('reviews r');
    $this->db->join('users u', 'u.id = r.user_id');
    $this->db->join('products p', 'p.id = r.product_id');
    $this->db->order_by('r.id', 'DESC');
    return $this->db->get()->result();
}

public function toggle_status($id, $user_id) {
    $this->db->set('is_enabled', '1 - is_enabled', FALSE);
    $this->db->set('updatedby', $user_id);
    $this->db->where('id', $id);
    $this->db->update('reviews');
}

public function delete($id) {
    $this->db->where('id', $id);
    $this->db->delete('reviews');
}
}