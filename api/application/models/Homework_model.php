<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Homework_model extends CI_Model {

    private $current_session;

    public function __construct() {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
    }

    public function getStudentHomeworkPercentage($student_session_id, $class_id, $section_id) {
        $sql = "SELECT count(*) as total_homework,(SELECT COUNT(homework_evaluation.id) as `aa` FROM `homework` LEFT JOIN homework_evaluation on homework_evaluation.homework_id=homework.id and homework_evaluation.student_session_id= " . $this->db->escape($student_session_id) . " WHERE homework.class_id=" . $this->db->escape($class_id) . " AND homework.section_id=" . $this->db->escape($section_id) . " AND homework.session_id=" . $this->current_session . ") as `completed`  FROM `homework` WHERE class_id=" . $this->db->escape($class_id) . " AND section_id=" . $this->db->escape($section_id) . " AND session_id=" . $this->current_session;
        // $sql = "SELECTs count(*) as total_homework,(SELECT count(*) from homework_evaluation WHERE homework_evaluation.homework_id =homework.id AND homework_evaluation.student_session_id=" . $this->db->escape($student_id) . ") as `completed`  FROM `homework` WHERE class_id=" . $this->db->escape($class_id) . " AND section_id=" . $this->db->escape($section_id) . " AND session_id=" . $this->current_session . " AND evaluated_by=1";

        $query = $this->db->query($sql);
        return $query->row();
    }

    public function getStudentHomework($class_id, $section_id, $student_session_id) {
        $where_condition = array(
            'homework.class_id' => $class_id,
            'homework.section_id' => $section_id,
            'homework.session_id' => $this->current_session);

        $this->db->select("`homework`.*, `subjects`.`name`, `sections`.`section`, `classes`.`class`, `create_staff`.`name` as `staff_created`, IFNULL(`evaluate_staff`.`name`,0) as `staff_evaluated`,IFNULL( homework_evaluation.id, 0) as homework_evaluation_id");
        $this->db->join("classes", "classes.id = homework.class_id");
        $this->db->join("sections", "sections.id = homework.section_id");
        $this->db->join("subject_group_subjects", "subject_group_subjects.id = homework.subject_group_subject_id");
        $this->db->join("subjects", "subjects.id = subject_group_subjects.subject_id");
        $this->db->join('homework_evaluation', 'homework_evaluation.student_session_id=' . $student_session_id . ' and homework_evaluation.homework_id=homework.id', 'left');
        $this->db->join("staff create_staff", "create_staff.id = homework.created_by", 'left');
        $this->db->join("staff evaluate_staff", "evaluate_staff.id = homework.evaluated_by", 'left');
        $this->db->where($where_condition);
        $this->db->order_by('homework.homework_date', 'desc');
        $query = $this->db->get("homework");
        return $query->result_array();
    }

    // public function getEvaluationReportForStudent($id, $student_id)
    // {
    //     $query = $this->db->select("homework.*,homework_evaluation.student_id,homework_evaluation.id as evalid,homework_evaluation.date,homework_evaluation.status,homework_evaluation.student_id,classes.class,subjects.name,sections.section")->join("classes", "classes.id = homework.class_id")->join("sections", "sections.id = homework.section_id")->join("subjects", "subjects.id = homework.subject_id")->join("homework_evaluation", "homework.id = homework_evaluation.homework_id")->where("homework.id", $id)->get("homework");
    //     //->where("homework_evaluation.student_id", $student_id)
    //     $result = $query->result_array();
    //     foreach ($result as $key => $value) {
    //         if ($value["student_id"] == $student_id) {
    //             return $value;
    //         } else {
    //             $data = array('date' => $value["date"], 'status' => 'Incomplete');
    //             return $data;
    //         }
    //     }
    // }


    public function add($data) {

        $this->db->where('homework_id', $data['homework_id']);
        $this->db->where('student_id', $data['student_id']);
        $q = $this->db->get('submit_assignment');

        if ($q->num_rows() > 0) {
            $this->db->where('homework_id', $data['homework_id']);
            $this->db->where('student_id', $data['student_id']);
            $this->db->update('submit_assignment', $data);
        } else {

            $this->db->insert('submit_assignment', $data);
        }
    }

}
