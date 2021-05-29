<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'third_party/stripe/init.php';

class Payment extends Admin_Controller {

    public $payment_method;
    public $school_name;
    public $school_setting;
    public $setting;

    public function __construct() {
        parent::__construct();
        $this->payment_method = $this->paymentsetting_model->get();
        $this->setting = $this->setting_model->get();
    }

    public function index($student_fees_master_id, $fee_groups_feetype_id, $student_id) {



        $this->session->unset_userdata("params");

        if (!empty($this->payment_method)) {
            $data = array();
            $data['fee_groups_feetype_id'] = $fee_groups_feetype_id;
            $data['student_fees_master_id'] = $student_fees_master_id;
            $result = $this->studentfeemaster_model->studentDeposit($data);
            $amount_balance = 0;
            $amount = 0;
            $amount_fine = 0;
            $amount_discount = 0;
            $amount_detail = json_decode($result->amount_detail);
            if (is_object($amount_detail)) {
                foreach ($amount_detail as $amount_detail_key => $amount_detail_value) {
                    $amount = $amount + $amount_detail_value->amount;
                    $amount_discount = $amount_discount + $amount_detail_value->amount_discount;
                    $amount_fine = $amount_fine + $amount_detail_value->amount_fine;
                }
            }

            $amount_balance = $result->amount - ($amount + $amount_discount);
            $student_record = $this->student_model->get($student_id);
            $pay_method = $this->paymentsetting_model->getActiveMethod();

            if ($pay_method->payment_type == "stripe") {
                if ($pay_method->api_secret_key == "" || $pay_method->api_publishable_key == "") {

                    $this->session->set_flashdata('error', 'Stripe settings not available');
                    $this->load->view('payment/error');
                } else {
                    $payment_details = $this->feegrouptype_model->getFeeGroupByID($fee_groups_feetype_id);
                    $page = new stdClass();
                    $page->symbol = $this->setting[0]['currency_symbol'];
                    $page->currency_name = $this->setting[0]['currency'];
                    $params = array(
                        'key' => $pay_method->api_secret_key,
                        'api_publishable_key' => $pay_method->api_publishable_key,
                        'invoice' => $page,
                        'total' => $amount_balance,
                        'student_session_id' => $student_record->student_session_id,
                        'email' => $student_record->email,
                        'guardian_phone' => $student_record->guardian_phone,
                        'name' => $student_record->firstname . " " . $student_record->lastname,
                        'student_fees_master_id' => $student_fees_master_id,
                        'fee_groups_feetype_id' => $fee_groups_feetype_id,
                        'student_id' => $student_id,
                        'payment_detail' => $payment_details,
                    );

                    $this->session->set_userdata("params", $params);

                    redirect("gateway/stripe", 'refresh');
                }
            } else if ($pay_method->payment_type == "payu") {
                $payment_details = $this->feegrouptype_model->getFeeGroupByID($fee_groups_feetype_id);
                $page = new stdClass();
                $page->symbol = $this->setting[0]['currency_symbol'];
                $page->currency_name = $this->setting[0]['currency'];

                $params = array(
                    'key' => $pay_method->api_secret_key,
                    'salt' => $pay_method->salt,
                    'invoice' => $page,
                    'total' => $amount_balance,
                    'student_session_id' => $student_record->student_session_id,
                    'name' => $student_record->firstname . " " . $student_record->lastname,
                    'email' => $student_record->email,
                    'guardian_phone' => $student_record->guardian_phone,
                    'address' => $student_record->permanent_address,
                    'student_fees_master_id' => $student_fees_master_id,
                    'fee_groups_feetype_id' => $fee_groups_feetype_id,
                    'student_id' => $student_id,
                    'payment_detail' => $payment_details,
                );

                $this->session->set_userdata("params", $params);
                redirect(base_url("gateway/payu"));
            } else if ($pay_method->payment_type == "paypal") {

                if ($pay_method->api_username == "" || $pay_method->api_password == "" || $pay_method->api_signature == "") {

                    $this->session->set_flashdata('error', 'Paypal settings not available');
                    $this->load->view('payment/error');
                } else {
                    $payment_details = $this->feegrouptype_model->getFeeGroupByID($fee_groups_feetype_id);
                    $page = new stdClass();
                    $page->symbol = $this->setting[0]['currency_symbol'];
                    $page->currency_name = $this->setting[0]['currency'];

                    $params = array(
                        'api_username' => $pay_method->api_username,
                        'api_password' => $pay_method->api_password,
                        'api_signature' => $pay_method->api_signature,
                        'invoice' => $page,
                        'total' => $amount_balance,
                        'student_session_id' => $student_record->student_session_id,
                        'name' => $student_record->firstname . " " . $student_record->lastname,
                        'email' => $student_record->email,
                        'guardian_phone' => $student_record->guardian_phone,
                        'address' => $student_record->permanent_address,
                        'student_fees_master_id' => $student_fees_master_id,
                        'fee_groups_feetype_id' => $fee_groups_feetype_id,
                        'student_id' => $student_id,
                        'payment_detail' => $payment_details,
                    );

                    $this->session->set_userdata("params", $params);
                    redirect("gateway/paypal", 'refresh');
                }
            } else if ($pay_method->payment_type == "instamojo") {

                if ($pay_method->api_secret_key == "" || $pay_method->salt == "" || $pay_method->api_publishable_key == "") {

                    $this->session->set_flashdata('error', 'Instamojo settings not available');
                    $this->load->view('payment/error');
                } else {
                    $payment_details = $this->feegrouptype_model->getFeeGroupByID($fee_groups_feetype_id);
                    $page = new stdClass();
                    $page->symbol = $this->setting[0]['currency_symbol'];
                    $page->currency_name = $this->setting[0]['currency'];

                    $params = array(
                        'api_secret_key' => $pay_method->api_secret_key,
                        'salt' => $pay_method->salt,
                        'api_publishable_key' => $pay_method->api_publishable_key,
                        'invoice' => $page,
                        'total' => $amount_balance,
                        'student_session_id' => $student_record->student_session_id,
                        'name' => $student_record->firstname . " " . $student_record->lastname,
                        'email' => $student_record->email,
                        'guardian_phone' => $student_record->guardian_phone,
                        'address' => $student_record->permanent_address,
                        'student_fees_master_id' => $student_fees_master_id,
                        'fee_groups_feetype_id' => $fee_groups_feetype_id,
                        'student_id' => $student_id,
                        'payment_detail' => $payment_details,
                    );

                    $this->session->set_userdata("params", $params);
                    redirect("gateway/Instamojo", 'refresh');
                }
            } else if ($pay_method->payment_type == "razorpay") {

                if ($pay_method->api_secret_key == "") {

                    $this->session->set_flashdata('error', 'Razorpay settings not available');
                    $this->load->view('payment/error');
                } else {
                    $payment_details = $this->feegrouptype_model->getFeeGroupByID($fee_groups_feetype_id);
                    $page = new stdClass();
                    $page->symbol = $this->setting[0]['currency_symbol'];
                    $page->currency_name = $this->setting[0]['currency'];

                    $params = array(
                        'api_secret_key' => $pay_method->api_secret_key,
                        'salt' => $pay_method->salt,
                        'api_publishable_key' => $pay_method->api_publishable_key,
                        'invoice' => $page,
                        'total' => $amount_balance,
                        'student_session_id' => $student_record->student_session_id,
                        'name' => $student_record->firstname . " " . $student_record->lastname,
                        'email' => $student_record->email,
                        'guardian_phone' => $student_record->guardian_phone,
                        'address' => $student_record->permanent_address,
                        'student_fees_master_id' => $student_fees_master_id,
                        'fee_groups_feetype_id' => $fee_groups_feetype_id,
                        'student_id' => $student_id,
                        'payment_detail' => $payment_details,
                    );

                    $this->session->set_userdata("params", $params);
                    redirect("gateway/Razorpay", 'refresh');
                }
            } else if ($pay_method->payment_type == "paystack") {

                if ($pay_method->api_secret_key == "") {

                    $this->session->set_flashdata('error', 'Paystack settings not available');
                    $this->load->view('payment/error');
                } else {
                    $payment_details = $this->feegrouptype_model->getFeeGroupByID($fee_groups_feetype_id);
                    $page = new stdClass();
                    $page->symbol = $this->setting[0]['currency_symbol'];
                    $page->currency_name = $this->setting[0]['currency'];

                    $params = array(
                        'api_secret_key' => $pay_method->api_secret_key,
                        'salt' => $pay_method->salt,
                        'api_publishable_key' => $pay_method->api_publishable_key,
                        'invoice' => $page,
                        'total' => $amount_balance,
                        'student_session_id' => $student_record->student_session_id,
                        'name' => $student_record->firstname . " " . $student_record->lastname,
                        'email' => $student_record->email,
                        'guardian_phone' => $student_record->guardian_phone,
                        'address' => $student_record->permanent_address,
                        'student_fees_master_id' => $student_fees_master_id,
                        'fee_groups_feetype_id' => $fee_groups_feetype_id,
                        'student_id' => $student_id,
                        'payment_detail' => $payment_details,
                    );

                    $this->session->set_userdata("params", $params);
                    redirect("gateway/Paystack", 'refresh');
                }
            } else if ($pay_method->payment_type == "paytm") {

                if ($pay_method->api_secret_key == "") {

                    $this->session->set_flashdata('error', 'paytm settings not available');
                    $this->load->view('payment/error');
                } else {
                    $payment_details = $this->feegrouptype_model->getFeeGroupByID($fee_groups_feetype_id);
                    $page = new stdClass();
                    $page->symbol = $this->setting[0]['currency_symbol'];
                    $page->currency_name = $this->setting[0]['currency'];

                    $params = array(
                        'api_secret_key' => $pay_method->api_secret_key,
                        'salt' => $pay_method->salt,
                        'api_publishable_key' => $pay_method->api_publishable_key,
                        'invoice' => $page,
                        'total' => $amount_balance,
                        'student_session_id' => $student_record->student_session_id,
                        'name' => $student_record->firstname . " " . $student_record->lastname,
                        'email' => $student_record->email,
                        'guardian_phone' => $student_record->guardian_phone,
                        'address' => $student_record->permanent_address,
                        'student_fees_master_id' => $student_fees_master_id,
                        'fee_groups_feetype_id' => $fee_groups_feetype_id,
                        'student_id' => $student_id,
                        'payment_detail' => $payment_details,
                    );

                    $this->session->set_userdata("params", $params);
                    redirect("gateway/Paytm", 'refresh');
                }
            } else if ($pay_method->payment_type == "midtrans") {

                if ($pay_method->api_secret_key == "") {

                    $this->session->set_flashdata('error', 'midtrans settings not available');
                    $this->load->view('payment/error');
                } else {
                    $payment_details = $this->feegrouptype_model->getFeeGroupByID($fee_groups_feetype_id);
                    $page = new stdClass();
                    $page->symbol = $this->setting[0]['currency_symbol'];
                    $page->currency_name = $this->setting[0]['currency'];

                    $params = array(
                        'api_secret_key' => $pay_method->api_secret_key,
                        'salt' => $pay_method->salt,
                        'api_publishable_key' => $pay_method->api_publishable_key,
                        'invoice' => $page,
                        'total' => $amount_balance,
                        'student_session_id' => $student_record->student_session_id,
                        'name' => $student_record->firstname . " " . $student_record->lastname,
                        'email' => $student_record->email,
                        'guardian_phone' => $student_record->guardian_phone,
                        'address' => $student_record->permanent_address,
                        'student_fees_master_id' => $student_fees_master_id,
                        'fee_groups_feetype_id' => $fee_groups_feetype_id,
                        'student_id' => $student_id,
                        'payment_detail' => $payment_details,
                    );

                    $this->session->set_userdata("params", $params);
                    redirect("gateway/midtrans", 'refresh');
                }
            } else {
                $this->session->set_flashdata('error', 'Oops! An error occurred with this payment, Please contact to administrator');
                $this->load->view('payment/error');
            }
        }
    }

    // public function stripe()
    // {
    //     $data = array();
    //     $params = array(
    //         "testmode"         => "on",
    //         "private_live_key" => "sk_live_xxxxxxxxxxxxxxxxxxxxx",
    //         "public_live_key"  => "pk_live_xxxxxxxxxxxxxxxxxxxxx",
    //         "private_test_key" => "sk_test_YLQh86Az2IdcuqfQQOx47yam",
    //         "public_test_key"  => "pk_test_nYHEZ1mJ8FpaoXV4KVxQs7qR",
    //     );
    //     if ($params['testmode'] == "on") {
    //         \Stripe\Stripe::setApiKey($params['private_test_key']);
    //         $pubkey = $params['public_test_key'];
    //     } else {
    //         \Stripe\Stripe::setApiKey($params['private_live_key']);
    //         $pubkey = $params['public_live_key'];
    //     }
    //     if ($this->input->server('REQUEST_METHOD') == 'POST') {
    //         if (isset($_POST['stripeToken'])) {
    //             $invoiceid   = "14526321"; // Invoice ID
    //             $description = "Invoice #" . $invoiceid . " - " . $invoiceid;
    //             try {
    //                 $charge = \Stripe\Charge::create(array(
    //                     'amount'      => 5000,
    //                     "currency"    => "usd",
    //                     "source"      => $_POST['stripeToken'],
    //                     "description" => $description)
    //                 );
    //                 // Payment has succeeded, no exceptions were thrown or otherwise caught
    //                 $result = "success";
    //                 print_r($charge);exit;
    //             } catch (\Stripe\Error\Card $e) {
    //                 // Since it's a decline, \Stripe\Error\Card will be caught
    //                 $body    = $e->getJsonBody();
    //                 $err     = $body['error'];
    //                 $error[] = $err['message'];
    //             } catch (\Stripe\Error\RateLimit $e) {
    //                 // Too many requests made to the API too quickly
    //                 $error[] = $e->getMessage();
    //             } catch (\Stripe\Error\InvalidRequest $e) {
    //                 // Invalid parameters were supplied to Stripe's API
    //                 $error[] = $e->getMessage();
    //             } catch (\Stripe\Error\Authentication $e) {
    //                 // Authentication with Stripe's API failed
    //                 // (maybe you changed API keys recently)
    //                 $error[] = $e->getMessage();
    //             } catch (\Stripe\Error\ApiConnection $e) {
    //                 // Network communication with Stripe failed
    //                 $error[] = $e->getMessage();
    //             } catch (\Stripe\Error\Base $e) {
    //                 // Display a very generic error to the user, and maybe send
    //                 // yourself an email
    //                 $error[] = $e->getMessage();
    //             } catch (Exception $e) {
    //                 // Something else happened, completely unrelated to Stripe
    //                 $error[] = $e->getMessage();
    //             }
    //             echo "<BR>Stripe Payment Status : " . $result;
    //             echo "<BR>Stripe Response : ";
    //             print_r($error);
    //             exit();
    //         }
    //     }
    //     $data['params'] = $params;
    //     $this->load->view('payment/stripe/pay', $data);
    // }
//     public function payu()
    //     {
//         // Merchant key here as provided by Payu
    //         $data['MERCHANT_KEY'] = "fBLf6dLh";
//         // Merchant Salt as provided by Payu
    //         $SALT = "kYVrn8cGnP";
//         // End point - change to https://secure.payu.in for LIVE mode
    //         $PAYU_BASE_URL = "https://secure.payu.in";
//         $data['action'] = '';
//         $posted = array();
    //         if ($this->input->server('REQUEST_METHOD') == 'POST') {
//             if (!empty($_POST)) {
    //                 print_r($_POST);
    //                 foreach ($_POST as $key => $value) {
//                     echo $posted[$key] = $value;
//                 }
    //             }
    //         }
    //         $data['posted']    = $posted;
    //         $data['formError'] = 0;
    //         $data['NAME']      = "RAM";
//         $data['Email'] = "liberoram@gmail.com";
//         if (empty($posted['txnid'])) {
    //             // Generate random transaction id
    //             $data['txnid'] = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
    //         } else {
    //             $data['txnid'] = $posted['txnid'];
    //         }
    //         $data['hash'] = '';
    // // Hash Sequence
    //     $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
    //         if (empty($posted['hash']) && sizeof($posted) > 0) {
//             if (
    //                 empty($posted['key'])
    //                 || empty($posted['txnid'])
    //                 || empty($posted['amount'])
    //                 || empty($posted['firstname'])
    //                 || empty($posted['email'])
    //                 || empty($posted['phone'])
    //                 || empty($posted['productinfo'])
    //                 || empty($posted['surl'])
    //                 || empty($posted['furl'])
    //                 || empty($posted['service_provider'])
    //             ) {
    //                 $formError = 1;
    //             } else {
//                 $hashVarsSeq = explode('|', $hashSequence);
    //     $hash_string = '';
    //     foreach($hashVarsSeq as $hash_var) {
    //       $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
    //       $hash_string .= '|';
    //     }
//     $hash_string .= $SALT;
//     $data['hash'] = strtolower(hash('sha512', $hash_string));
    //     $data['action'] = $PAYU_BASE_URL . '/_payment';
//             }
    //         } elseif (!empty($posted['hash'])) {
    //             $data['hash']   = $posted['hash'];
    //             $data['action'] = $PAYU_BASE_URL . '/_payment';
    //         }
//         $this->load->view('payment/payu/index', $data);
//     }

    public function paymentfailed() {

        $data = array();
        // $setting_result = $this->setting_model->get();
        // $data['settinglist'] = $setting_result;

        $this->load->view('payment/paymentfailed', $data);
    }

    public function successinvoice($invoice_id, $sub_invoice_id) {
        $data = array();
        $data['title'] = 'Invoice';
        $setting_result = $this->setting_model->get();
        $data['settinglist'] = $setting_result;
        $studentfee = $this->studentfeemaster_model->getFeeByInvoice($invoice_id, $sub_invoice_id);
        $a = json_decode($studentfee->amount_detail);
        $record = $a->{$sub_invoice_id};

        $data['studentfee'] = $studentfee;
        $data['studentfee_detail'] = $record;

        $this->load->view('payment/invoice', $data);
    }

}
