<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class customfield_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * This funtion takes id as a parameter and will fetch the record.
     * If id is not provided, then it will fetch all the records form the table.
     * @param int $id
     * @return mixed
     */
    // public function get($id = null)
    // {
    //     $this->db->select()->from('custom_fields');
    //     if ($id != null) {
    //         $this->db->where('custom_fields.id', $id);
    //     } else {
    //         $this->db->order_by('custom_fields.belong_to','asc');
    //         $this->db->order_by('custom_fields.weight','asc');
    //     }
    //     $query = $this->db->get();
    //     if ($id != null) {
    //         return $query->row();
    //     } else {
    //         return $query->result_array();
    //     }
    // }
    // public function getByBelong($belong_to)
    // {
    //     $this->db->from('custom_fields');
    //     $this->db->where('belong_to', $belong_to);
    //     $this->db->order_by('custom_fields.belong_to','asc');
    //     $this->db->order_by('custom_fields.weight','asc');
    //     $query  = $this->db->get();
    //     $result = $query->result_array();
    //     return $result;
    // }

    public function get_custom_fields($belongs_to, $display_table = 0) {

        $this->db->from('custom_fields');
        $this->db->where('belong_to', $belongs_to);

        if ($display_table) {
            $this->db->where('visible_on_table', $display_table);
        }
        $this->db->order_by("custom_fields.weight", "asc");
        $query = $this->db->get();
        $result = $query->result();
        return $result;
    }

    public function student_fields() {

        $fields = $this->get_custom_fields('students', 0);
        $new_object = array();
        if (!empty($fields)) {
            foreach ($fields as $field_key => $field_value) {
                $new_object[$field_value->name] = 1;
            }
        }
        return $new_object;
    }

}
