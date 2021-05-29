<?php

class Timeline_model extends CI_Model {

    public function getTimeline($id) {

        $query = $this->db->where(array("student_id" => $id, 'status' => 'yes'))->order_by("timeline_date", "asc")->get("student_timeline");
        return $query->result_array();
    }

}
