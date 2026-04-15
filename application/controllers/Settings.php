<?php
class Settings extends CI_Controller {
   private $upload_path;

public function __construct() {
    parent::__construct();

       $this->upload_path = 'uploads/gallery/'; 

    $this->load->model('Settings_model');
    $this->load->model('Jwt_model');
    $this->load->helper('response');
    $this->load->library('upload');
}


private function uploadImage($field, $name) {
    $config = [
        'upload_path'      => FCPATH . 'uploads/gallery/',
        'allowed_types'    => '*', 
        'file_name'        => time() . '_' . $name,
        'max_size'         => 2048,
        'detect_mime'      => FALSE,
        'file_ext_tolower' => TRUE,
        'remove_spaces'    => TRUE
    ];

    $this->upload->initialize($config);

    if ($this->upload->do_upload($field)) {
        $file = $this->upload->data();


        $allowed_ext = ['jpg','jpeg','png','webp','gif'];

        if (!in_array($file['file_ext'], array_map(fn($e)=>'.'.$e, $allowed_ext))) {
            unlink($file['full_path']); // delete file
            return ['error' => 'Invalid file type'];
        }

        return 'uploads/gallery/' . $file['file_name'];

    } else {
        return ['error' => strip_tags($this->upload->display_errors())];
    }
}
    public function index() {
    $user = $this->Jwt_model->verify_token();

    if (!$user) return unauthorized("Unauthorized");

    if (!in_array((int)$user->role, [2, 3], true)) {
    return unauthorized("Access denied");
}

    $data = $this->Settings_model->get();

    return success_response("Settings fetched", $data);
}

  public function save() {
    $user = $this->Jwt_model->verify_token();

    if (!$user) return unauthorized("Unauthorized");

  
   if ((int)$user->role !== 2) {
    return unauthorized("Access denied - Admin only");
}

    $post = $this->input->post();

    $data = [
        'name' => $post['name'],
        'email' => $post['email'],
        'phone' => $post['phone'],
        'place' => $post['place'],
        'store_hours' => $post['store_hours'],
        'store_closed' => $post['store_closed'],
        'low_stock_threshold' => $post['low_stock_threshold'],
        'our_story' => $this->input->post('our_story', false),  
        'our_mission' => $this->input->post('our_mission', false),
        'our_vision' => $this->input->post('our_vision', false),
        'Instagram' => $this->input->post('social_insta', false),
        'Facebook' => $this->input->post('social_facebook', false),
        'YouTube' => $this->input->post('social_youtube', false),
        'X' => $this->input->post('social_x', false),
        'updated_by' => $user->uid
    ];

    if (!is_dir($this->upload_path)) {
        mkdir($this->upload_path, 0777, true);
    }


   if (!empty($_FILES['logo']['name'])) {
    $res = $this->uploadImage('logo', 'logo');
    if (isset($res['error'])) return error_response($res['error']);
    $data['logo'] = $res;
}

if (!empty($_FILES['about_image']['name'])) {
    $res = $this->uploadImage('about_image', 'about');
    if (isset($res['error'])) return error_response($res['error']);
    $data['about_image'] = $res;
}

if (!empty($_FILES['hero_image']['name'])) {
    $res = $this->uploadImage('hero_image', 'hero');
    if (isset($res['error'])) return error_response($res['error']);
    $data['hero_image'] = $res;
}

    if (!$this->Settings_model->get()) {
        $data['created_by'] = $user->uid;
    }

    $this->Settings_model->save($data);

    return success_response("Settings saved", $data);
}
}