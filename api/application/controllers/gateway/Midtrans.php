<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Midtrans extends Admin_Controller {

    var $setting;
    var $payment_method;
    var $api_config;

    public function __construct() {
        parent::__construct();

        $this->setting = $this->setting_model->get();
        $this->payment_method = $this->paymentsetting_model->get();
        $this->api_config = $this->paymentsetting_model->getActiveMethod();
        $this->load->library('Midtrans_lib');
    }

    public function index() {

        $data = array();
        $data['params'] = $this->session->userdata('params');

        $data['setting'] = $this->setting;
        $data['api_error'] = array();
        $transaction = array(
            'transaction_details' => array(
                'order_id' => time(),
                'gross_amount' => round($data['params']['total']), // no decimal allowed
            ),
        );

        $snapToken = $this->midtrans_lib->getSnapToken($transaction, $this->api_config->api_secret_key);
        $data['snap_Token'] = $snapToken;
        $this->load->view('payment/midtrans/index', $data);
    }

    public function success() {

        $response = json_decode($_POST['result_data']);

        $payment_id = $response->transaction_id;
        $params = $this->session->userdata('params');

        $json_array = array(
            'amount' => $params['total'],
            'date' => date('Y-m-d'),
            'amount_discount' => 0,
            'amount_fine' => 0,
            'description' => "Online fees deposit through Midtrans TXN ID: " . $payment_id,
            'received_by' => '',
            'payment_mode' => 'midtrans',
        );
        $data = array(
            'student_fees_master_id' => $params['student_fees_master_id'],
            'fee_groups_feetype_id' => $params['fee_groups_feetype_id'],
            'amount_detail' => $json_array
        );
        $send_to = $params['guardian_phone'];
        $inserted_id = $this->studentfeemaster_model->fee_deposit($data, $send_to, '');
        echo $inserted_id;
    }

}
