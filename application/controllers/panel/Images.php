<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Images extends MY_Controller {

    public $user = '';
    protected $base_url = 'panel/images';
    protected $home_url = 'panel/roles/images_list';
    protected $config_info;

    public function __construct(){
        parent::__construct();
        $this->user = get_user(true);

        if(!has_permission('open_admin_panel',$this->user)){
            redirect(site_url() .'/panel/auth/login');
        }

        if(!has_permission('list_images_permissions',$this->user)){
            redirect(site_url() .'/panel/');
        }

        $this->config_info = new Config_info('images');
    }

    public function index(){

    }

    public function download($id = false){

        $id = (int)$id;
        if($id == 0){
            redirect('panel');
            return;
        }
        $error_message = array(
            'to_email' => DEFAULT_ADMIN_EMAIL,
            'subject' => LANG('label_error_to_take_image'),
            'template' => 'error_email',
            'message_data' => array(
                'id'=>$id,
            ),
        );

        $_img = $this->image_model->get_by_id($id);
        if(!$_img){
            $error_message['message_data']['error'] = LANG('label_error_no_image_in_db');
            $this->sender_model->message($error_message);
            echo 'no image';
            return;
        }

        $file = $_img['src'];

        if(file_exists(DEFAULT_UPLOAD_IMAGE_PATH . $file)){
            header("Content-Type:application/download");
            header("Content-disposition: attachment; filename=". $_img['name']);
            header("Pragma: no-cache");
            header("Content-length: ".filesize(DEFAULT_UPLOAD_IMAGE_PATH . $file));
            readfile(DEFAULT_UPLOAD_IMAGE_PATH . $file);
        }else{
            $error_message['message_data']['error'] = LANG('label_error_no_image_in_ftp');
            $error_message['message_data']['data'] = $_img;
            $this->sender_model->message($error_message);
            header("Content-Type:application/download");
            header("Content-disposition: attachment; filename=". DEFAULT_UPLOAD_IMAGE_PATH . $file);
            header("Pragma: no-cache");
            header("Content-length: 1");
            readfile('http://placehold.it/'. $_img['imagesize']);
        }
    }

    public function image(){

        $data['page_title'] = LANG('page_title_images_list');
        $data['base_link'] = $this->base_url;
        $data['current_link'] = site_url($this->base_url .'/image');
        $data['update_image_list'] = site_url('panel/images/update_images_list');
        $data['content'] = '/panel/default/image_list';
        $data['display_fields'] = $this->config_info->display_table_fields;

        $pagination_data = get_pagination_data();
        $pagination_data['sort'] = ($pagination_data['sort'] == 'id-asc')? 'date-desc':$pagination_data['sort'];

        $data['sort'] = $pagination_data['sort'];
        $data['table_data'] = $this->image_model->get_all_images($pagination_data);

        if(count($data['table_data']) == 0 && $pagination_data['page'] >1){
            $_link = make_link_by($data['current_link'],array('page'));
            $action_link = (substr_count($_link,'?') > 0)? $_link .'&':$_link .'/?';
            redirect($action_link .'page='.($pagination_data['page'] - 1));
        }

        $data['pagination'] = pagination('images',array(),$pagination_data['page'],$pagination_data['count'],make_link_by($data['current_link'],array('page')));

        $this->load->view('panel/main_layout',$data);

    }

    public function update_images_list(){
        $_ref = isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER']:'panel';
        $result = $this->image_model->update_image_list(DEFAULT_UPLOAD_IMAGE_PATH);
        if($result['status']){
            $_message = sprintf(LANG('ntf_image_statuses'), $result['inserted'],$result['updated'],$result['deleted']);
            add_system_message('success', LANG('ntf_image_updated_title') .' '. $_message, LANG('ntf_image_updated'));
        }else{
            add_system_message('danger', LANG('ntf_image_no_updated_title'), LANG('ntf_image_no_updated'));
        }
        redirect($_ref);

    }

}