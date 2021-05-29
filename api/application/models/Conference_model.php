<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Conference_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
    }

    public function getByStudentClassSection($class_id, $section_id) {
        $this->db->select('conferences.*,classes.class,sections.section,staff.name as `staff_name`,staff.surname as `staff_surname`')->from('conferences');
        $this->db->join('classes', 'classes.id = conferences.class_id');
        $this->db->join('sections', 'sections.id = conferences.section_id');
        $this->db->join('staff', 'staff.id = conferences.staff_id');
        $this->db->where('conferences.class_id', $class_id);
        $this->db->where('conferences.section_id', $section_id);
        $this->db->where('conferences.session_id', $this->current_session);
        $this->db->order_by('DATE(`conferences`.`date`)', 'DESC');
        $this->db->order_by('conferences.date', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    public function updatehistory($data) {
        $this->db->trans_start();
        $this->db->trans_strict(false);

        $this->db->where('conference_id', $data['conference_id']);
        $this->db->where('student_id', $data['student_id']);

        $q = $this->db->get('conferences_history');

        if ($q->num_rows() > 0) {
            $row = $q->row();
            $total_hit = $row->total_hit + 1;
            $data['total_hit'] = $total_hit;
            $this->db->where('id', $row->id);
            $this->db->update('conferences_history', $data);
        } else {

            $this->db->insert('conferences_history', $data);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {

            $this->db->trans_rollback();
            return false;
        } else {
            return true;
        }
    }

}
