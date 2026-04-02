<?php
class Reviews extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Review_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response');
    }

  
    public function submit() {
        $user = $this->Jwt_model->verify_token();
        if (!$user) return unauthorized("Login required");

        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['product_id']) || empty($input['rating'])) {
            return error_response("Missing fields");
        }

        $data = [
            'product_id' => $input['product_id'],
            'user_id' => $user->uid,
            'rating' => intval($input['rating']),
            'title' => $input['title'],
            'message' => $input['message'],
            'updatedby' => $user->uid
        ];

        $this->Review_model->upsert($data);

     
        $this->Review_model->update_product_rating($input['product_id']);

        return success_response("Review submitted");
    }


    public function get_by_product($product_id) {
        $data = $this->Review_model->get_by_product($product_id);
        return success_response("Reviews fetched", $data);
    }


public function get_all() {
    $data = $this->Review_model->get_all();
    return success_response("All reviews", $data);
}


public function toggle($id) {
    $user = $this->Jwt_model->verify_token();
    if (!$user) return unauthorized("Login required");

  
    $review = $this->db->where('id', $id)->get('reviews')->row();

    if (!$review) return error_response("Review not found");

    $this->Review_model->toggle_status($id, $user->uid);

   
    $this->Review_model->update_product_rating($review->product_id);

    return success_response("Status updated");
}

public function delete($id) {
    $user = $this->Jwt_model->verify_token();
    if (!$user) return unauthorized("Login required");


    $review = $this->db->where('id', $id)->get('reviews')->row();

    if (!$review) return error_response("Review not found");

    $this->Review_model->delete($id);

    $this->Review_model->update_product_rating($review->product_id);

    return success_response("Review deleted");
}

}