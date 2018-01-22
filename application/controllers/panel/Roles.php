<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends MY_Controller {

    public $user = '';
    protected $base_url = 'panel/roles';
    protected $home_url = 'panel/roles/roles_list';
    protected $config_info;

    public function __construct(){
        parent::__construct();
        $this->user = get_user(true);

        if(!has_permission('open_admin_panel',$this->user)){
            redirect(site_url() .'/panel/auth/login');
        }

        if(!has_permission('list_roles_permissions',$this->user)){
            redirect(site_url() .'/panel/');
        }

        $this->config_info = new Config_info('roles');
    }

    public function index(){

    }

    public function roles(){

        $data['page_title'] = LANG('page_title_roles_list');
        $data['base_link'] = $this->base_url;
        $data['current_link'] = site_url($this->base_url .'/roles');
        $data['edit_link'] = site_url($this->base_url .'/edit');
        $data['content'] = '/panel/default/default_list';

        $data['display_fields'] = $this->config_info->display_table_fields;
        $pagination_data = get_pagination_data();
        $data['sort'] = $pagination_data['sort'];

        $data['table_data'] = $this->role_model->get_all_roles($pagination_data);
        if(count($data['table_data']) == 0 && $pagination_data['page'] >1){
            $_link = make_link_by($data['current_link'],array('page'));
            $action_link = (substr_count($_link,'?') > 0)? $_link .'&':$_link .'/?';
            redirect($action_link .'page='.($pagination_data['page'] - 1));
        }

        $data['pagination'] = pagination('roles',array(),$pagination_data['page'],$pagination_data['count'],make_link_by($data['current_link'],array('page')));

        $this->load->view('panel/main_layout',$data);

    }

    public function edit($id = false,$close = false){

        $role = $this->role_model->get_role_by_id($id);

        if(!$role){
            add_system_message("danger", LANG("ntf_npt_roles_no_exist_title"), LANG("ntf_npt_roles_no_exist"));
            redirect($this->base_url .'/roles');
        }

        $this->_editor($role,$id,$close);
    }

    private function _editor($source,$role_id,$close){

        $data['page_title'] = LANG('page_title_roles_edit');
        $data['save_link'] = site_url($this->base_url .'/edit/'. $role_id);
        $data['save_close_link'] = site_url($this->base_url .'/edit/'.$role_id.'/close');
        $data['close_link'] = site_url($this->base_url .'/roles');
        $data['content'] = $this->base_url .'/roles_edit';

        $this->form_validation->set_rules('permissions[]', 'lang:label_permissions', 'required',array('required' => LANG('error_no_select_values_permissions')));
        $this->form_validation->data_id = $role_id;

        if ($this->form_validation->run() === true) {
            $_update_status = $this->role_model->update_role($this->input->post(), $role_id);
            $data['role'] =  new Role($this->input->post());
            if($_update_status){
                add_system_message('success', LANG('ntf_role_edited_title'), LANG('ntf_role_edited'));
            }else{
                add_system_message('danger', LANG('ntf_role_edit_error_title'), LANG('ntf_role_edit_error'));
            }
            if($close){
                redirect($data['close_link']);
            }
        } else {
            if (count($this->input->post()) > 0) {
                $data['role'] = new Role($this->input->post());
            } else {
                $data['role'] = $source;
            }
        }

        $data['permission_by_group'] = $this->permission_model->get_all_permissions_by_groups();

        $this->load->view('panel/main_layout',$data);
    }

}