<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Auth_model extends CI_Model {

    public $client_service = "smartschool";
    public $auth_key = "schoolAdmin@";

    public function __construct() {
        parent::__construct();
        $this->load->model(array('user_model', 'setting_model', 'student_model'));
    }

    public function check_auth_client() {

        $client_service = $this->input->get_request_header('Client-Service', true);
        $auth_key = $this->input->get_request_header('Auth-Key', true);
        if ($client_service == $this->client_service && $auth_key == $this->auth_key) {
            return true;
        } else {
            return json_output(401, array('status' => 401, 'message' => 'Unauthorized.'));
        }
    }

    public function login($username, $password, $app_key) {

        $this->db->select('id, username, password,role,is_active');
        $this->db->from('users');
        $this->db->where('username', $username);
        $this->db->where('password', $password);
        $this->db->limit(1);
        $q = $this->db->get();

        if ($q->num_rows() == 0) {
            return array('status' => 401, 'message' => 'Invalid Username or Password');
        } else {
            $q = $q->row();

            if ($q->is_active == "yes") {
                if ($q->role == "student") {

                    $result = $this->user_model->read_user_information($q->id);
                    if ($result != false) {
                        $setting_result = $this->setting_model->get();

                        if ($result->role == "student") {

                            $last_login = date('Y-m-d H:i:s');
                            $token = $this->getToken();
                            $expired_at = date("Y-m-d H:i:s", strtotime('+8760 hours'));
                            $this->db->trans_start();
                            $this->db->insert('users_authentication', array('users_id' => $q->id, 'token' => $token, 'expired_at' => $expired_at));

                            $updateData = array(
                                'app_key' => $app_key,
                            );

                            $this->db->where('id', $result->user_id);
                            $this->db->update('students', $updateData);

                            $session_data = array(
                                'id' => $result->id,
                                'student_id' => $result->user_id,
                                'role' => $result->role,
                                'username' => $result->firstname . " " . $result->lastname,
                                'class' => $result->class,
                                'section' => $result->section,
                                'date_format' => $setting_result[0]['date_format'],
                                'currency_symbol' => $setting_result[0]['currency_symbol'],
                                'timezone' => $setting_result[0]['timezone'],
                                'sch_name' => $setting_result[0]['name'],
                                'language' => array('lang_id' => $setting_result[0]['lang_id'], 'language' => $setting_result[0]['language']),
                                'is_rtl' => $setting_result[0]['is_rtl'],
                                'theme' => $setting_result[0]['theme'],
                                'image' => $result->image,
                            );
                            $this->session->set_userdata('student', $session_data);
                            if ($this->db->trans_status() === false) {
                                $this->db->trans_rollback();

                                return array('status' => 500, 'message' => 'Internal server error.');
                            } else {
                                $this->db->trans_commit();
                                return array('status' => 200, 'message' => 'Successfully login.', 'id' => $q->id, 'token' => $token, 'role' => $q->role, 'record' => $session_data);
                            }
                        }
                    }
                } else if ($q->role == "parent") {
                    $login_post = array(
                        'username' => $username,
                        'password' => $password,
                    );

                    $result = $this->user_model->checkLoginParent($login_post);
                    $setting_result = $this->setting_model->get();
                    if ($result[0]->role == "parent") {

                        $last_login = date('Y-m-d H:i:s');
                        $token = $this->getToken();
                        $expired_at = date("Y-m-d H:i:s", strtotime('+8760 hours'));

                        $this->db->insert('users_authentication', array('users_id' => $q->id, 'token' => $token, 'expired_at' => $expired_at));

                        if ($result[0]->guardian_relation == "Father") {
                            $image = $result[0]->father_pic;
                        } else if ($result[0]->guardian_relation == "Mother") {
                            $image = $result[0]->mother_pic;
                        } else {
                            $image = $result[0]->guardian_pic;
                        }

                        $session_data = array(
                            'id' => $result[0]->id,
                            'role' => $result[0]->role,
                            'username' => $result[0]->guardian_name,
                            'date_format' => $setting_result[0]['date_format'],
                            'timezone' => $setting_result[0]['timezone'],
                            'sch_name' => $setting_result[0]['name'],
                            'currency_symbol' => $setting_result[0]['currency_symbol'],
                            'language' => array('lang_id' => $setting_result[0]['lang_id'], 'language' => $setting_result[0]['language']),
                            'is_rtl' => $setting_result[0]['is_rtl'],
                            'theme' => $setting_result[0]['theme'],
                            'image' => $image,
                        );

                        $user_id = ($result[0]->id);
                        $students_array = $this->student_model->read_siblings_students($user_id);
                        $child_student = array();
                        $update_student = array();
                        foreach ($students_array as $std_key => $std_val) {
                            $child = array(
                                'student_id' => $std_val->id,
                                'class' => $std_val->class,
                                'section' => $std_val->section,
                                'class_id' => $std_val->class_id,
                                'section_id' => $std_val->section_id,
                                'name' => $std_val->firstname . " " . $std_val->lastname,
                                'image' => $std_val->image,
                            );
                            $child_student[] = $child;
                            $stds = array(
                                'id' => $std_val->id,
                                'parent_app_key' => $app_key,
                            );
                            $update_student[] = $stds;
                        }
                        if (!empty($update_student)) {
                            $this->db->update_batch('students', $update_student, 'id');
                        }

                        $session_data['parent_childs'] = $child_student;
                        $this->session->set_userdata('student', $session_data);

                        return array('status' => 200, 'message' => 'Successfully login.', 'id' => $q->id, 'token' => $token, 'role' => $q->role, 'record' => $session_data);
                    }
                }
            } else {
                return json_output(401, array('status' => 401, 'message' => 'Your account is disabled please contact to administrator'));
            }
        }
    }

    public function getToken($randomIdLength = 10) {
        $token = '';
        do {
            $bytes = rand(1, $randomIdLength);
            $token .= str_replace(
                    ['.', '/', '='], '', base64_encode($bytes)
            );
        } while (strlen($token) < $randomIdLength);
        return $token;
    }

    public function logout($deviceToken) {
        $users_id = $this->input->get_request_header('User-ID', true);
        $token = $this->input->get_request_header('Authorization', true);
        $this->session->unset_userdata('student');
        $this->session->sess_destroy();
        $this->db->where('app_key', $deviceToken)->update('students', array('app_key' => NULL));
        $this->db->where('users_id', $users_id)->where('token', $token)->delete('users_authentication');
        return array('status' => 200, 'message' => 'Successfully logout.');
    }

    public function auth() {
        $users_id = $this->input->get_request_header('User-ID', true);
        $token = $this->input->get_request_header('Authorization', true);
        $q = $this->db->select('expired_at')->from('users_authentication')->where('users_id', $users_id)->where('token', $token)->get()->row();
        if ($q == "") {
            return json_output(401, array('status' => 401, 'message' => 'Unauthorized.'));
        } else {
            if ($q->expired_at < date('Y-m-d H:i:s')) {
                return json_output(401, array('status' => 401, 'message' => 'Your session has been expired.'));
            } else {
                $updated_at = date('Y-m-d H:i:s');
                $expired_at = date("Y-m-d H:i:s", strtotime('+8760 hours'));
                $this->db->where('users_id', $users_id)->where('token', $token)->update('users_authentication', array('expired_at' => $expired_at, 'updated_at' => $updated_at));
                return array('status' => 200, 'message' => 'Authorized.');
            }
        }
    }

}
