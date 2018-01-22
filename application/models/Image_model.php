<?php


class Image_model extends MY_Model {

    protected $table_name = 'images';
    private $inner_dir = '';
    private $ignore_dir =array(
        'images/.quarantine'=>'images/.quarantine',
        'images/.tmb'=>'images/.tmb',
    );
    private $image_types = array(
        'jpeg'=>'jpeg',
        'jpg'=>'jpg',
        'png'=>'png',
    );

    public function __construct() {
        parent::__construct();
        $this->inner_dir = $_SERVER['DOCUMENT_ROOT'];
    }

    public function get_all_images($pagination = false) {

        if($pagination){
            $this->db->limit($pagination['count'],$pagination['count']*($pagination['page']-1));
            $_sort = str_replace("-"," ",$pagination['sort']);
            $this->db->order_by($_sort);
        }

        $result = [];
        $_data = $this->db->select('*, DATE_FORMAT(upload_time,"%d.%m.%Y %H:%i") as date, CONCAT("'. site_url() .'", id) as link')
                          ->from($this->table_name)
                          ->get()
                          ->result_array();
        foreach ($_data as $item) {
            $result[$item['id']] = $item;
        }
        return $result;
    }

    public function update_image_list($inner_dir=false){

        $inner_dir = ($inner_dir)? $inner_dir:$this->inner_dir;
        $parsed_data = $this->show_inner_dir($inner_dir,array(),$inner_dir);

        $_images = $this->get_all_images();

        $delete_arr = array();
        $update_arr = array();

        foreach($_images as $img){

            if(isset($parsed_data[$img['src']])){
                if($parsed_data[$img['src']]['filesize']  != $img['filesize'] ||
                   $parsed_data[$img['src']]['imagesize'] != $img['imagesize'] ||
                   $parsed_data[$img['src']]['type']      != $img['type']
                   ){
                        $update_arr[$img['src']] = $parsed_data[$img['src']];
                        $update_arr[$img['src']]['id'] = $img['id'];
                }
                unset($parsed_data[$img['src']]);
            }else{
                $delete_arr[$img['src']] = $img['src'];
            }
        }

        if(count($delete_arr) > 0){
            $this->db->where_in('src',$delete_arr)->delete($this->table_name);
        }

        if(count($update_arr) > 0){
            $this->db->update_batch($this->table_name,$update_arr,'id');
        }

        if(count($parsed_data) > 0){
            $this->db->insert_batch($this->table_name,$parsed_data);
        }

        $operations_status = array(
            'inserted'=>count($parsed_data),
            'updated'=>count($update_arr),
            'deleted'=>count($delete_arr),
            'status'=>true
        );

        return $operations_status;
    }

    private function show_inner_dir($dir,$data,$main_dir){

        if(isset($this->ignore_dir[$dir])){
            return $data;
        }

        $list = scandir($dir);
        if (!is_array($list)) {
            return $data;
        }

        $list = array_diff($list, array('.', '..'));
        if (!$list) {
            return $data;
        }

        foreach ($list as $name) {
            $path = $dir . '/' . $name;
            $is_dir = is_dir($path);
            if ($is_dir){
                $data = $this->show_inner_dir($path,$data,$main_dir);
            }else{
                $_str = str_replace($main_dir,'',$path);
                if(!isset($data[$_str])){

                    $_file = new SplFileInfo($path);
                    $data[$_str]['src'] = $_str;
                    $data[$_str]['name'] = $name;
                    $data[$_str]['type'] = $_file->getExtension();
                    $data[$_str]['mime_type'] = mime_content_type($path);
                    $data[$_str]['upload_time'] = date('Y-m-d H:i:s',$_file->getMTime());
                    $data[$_str]['filesize'] = round($_file->getSize()/1024000, 4, PHP_ROUND_HALF_UP);
                    if(isset($this->image_types[$data[$_str]['type']])){
                        $_img = getimagesize($path);
                        $data[$_str]['imagesize'] = $_img[0] .'x'. $_img[1];
                    }else{
                        $data[$_str]['imagesize'] = '';
                    }
                }
            }
        }

        return $data;
    }

}
