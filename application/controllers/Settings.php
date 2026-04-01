<?php
class Settings extends CI_Controller {
        private $upload_path = './uploads/gallery/';

    public function __construct() {
        parent::__construct();
        $this->load->model('Settings_model');
        $this->load->model('Jwt_model');
        $this->load->helper('response');
    }

  public function save() {
    $user = $this->Jwt_model->verify_token();

    if (!$user) return unauthorized("Unauthorized");

  
    if ($user->role != 2) {
        return unauthorized("Access denied - Admin only");
    }

    $post = $this->input->post();

    $data = [
        'name' => $post['name'],
        'email' => $post['email'],
        'phone' => $post['phone'],
        'low_stock_threshold' => $post['lowStockThreshold'],
        'description' => $post['description'],
        'updated_by' => $user->uid
    ];

    if (!is_dir($this->upload_path)) {
        mkdir($this->upload_path, 0777, true);
    }


    if (!empty($_FILES['logo']['name'])) {
        $config = [
            'upload_path' => $this->upload_path,
            'allowed_types' => 'jpg|jpeg|png|webp',
            'file_name' => time() . '_logo',
        ];

        $this->load->library('upload', $config);

        if ($this->upload->do_upload('logo')) {
            $file = $this->upload->data();
            $data['logo'] = str_replace('./', '', $this->upload_path) . $file['file_name'];
        }
    }


    if (!empty($_FILES['about_image']['name'])) {
        $config['file_name'] = time() . '_about';
        $this->upload->initialize($config);

        if ($this->upload->do_upload('about_image')) {
            $file = $this->upload->data();
            $data['about_image'] = str_replace('./', '', $this->upload_path) . $file['file_name'];
        }
    }

    if (!$this->Settings_model->get()) {
        $data['created_by'] = $user->uid;
    }

    $this->Settings_model->save($data);

    return success_response("Settings saved", $data);
}
}