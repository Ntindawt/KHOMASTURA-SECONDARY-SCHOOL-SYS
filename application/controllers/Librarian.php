<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');


class Librarian extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->database();
        $this->load->library('session');
        $this->load->model(array('Ajaxdataload_model' => 'ajaxload'));

       /*cache control*/
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');

    }

    public function index()
    {
        if ($this->session->userdata('librarian_login') != 1)
            redirect(site_url('login'), 'refresh');
        if ($this->session->userdata('librarian_login') == 1)
            redirect(site_url('librarian/dashboard'), 'refresh');
    }

    // LIBRARIAN DASHBOARD
    function dashboard()
    {
        if ($this->session->userdata('librarian_login') != 1)
            redirect(base_url(), 'refresh');

        $page_data['page_name']  = 'dashboard';
        $page_data['page_title'] = get_phrase('librarian_dashboard');
        $this->load->view('backend/index', $page_data);
    }

    // MANAGE LIBRARY/BOOKS
    function book($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('librarian_login') != 1)
            redirect('login', 'refresh');

        if ($param1 == 'create') {
            $data['name']        = html_escape($this->input->post('name'));
            $data['class_id']    = $this->input->post('class_id');
            if ($this->input->post('description') != null) {
               $data['description'] = html_escape($this->input->post('description'));
            }
            if ($this->input->post('price') != null) {
               $data['price'] = html_escape($this->input->post('price'));
            }
            if ($this->input->post('author') != null) {
               $data['author'] = html_escape($this->input->post('author'));
            }
            if(!empty($_FILES["file_name"]["name"])) {
                $data['file_name'] = $_FILES["file_name"]["name"];
            }

            $this->db->insert('book', $data);

            if(!empty($_FILES["file_name"]["name"])) {
                move_uploaded_file($_FILES["file_name"]["tmp_name"], "uploads/document/" . $_FILES["file_name"]["name"]);
            }

            $this->session->set_flashdata('flash_message', get_phrase('data_added_successfully'));
            redirect(site_url('librarian/book'), 'refresh');
        }

        if ($param1 == 'do_update') {
            $data['name']        = html_escape($this->input->post('name'));
            $data['class_id']    = $this->input->post('class_id');
            if ($this->input->post('description') != null) {
               $data['description'] = html_escape($this->input->post('description'));
            }
            else{
               $data['description'] = null;
            }
            if ($this->input->post('price') != null) {
               $data['price'] = html_escape($this->input->post('price'));
            }
            else{
                $data['price'] = null;
            }
            if ($this->input->post('author') != null) {
               $data['author'] = html_escape($this->input->post('author'));
            }
            else{
               $data['author'] = null;
            }
            if ($this->input->post('total_copies') != null) {
               $data['total_copies'] = html_escape($this->input->post('total_copies'));
            }
            else{
               $data['total_copies'] = null;
            }
            if(!empty($_FILES["file_name"]["name"])) {
                $data['file_name'] = $_FILES["file_name"]["name"];
            }

            $this->db->where('book_id', $param2);
            $this->db->update('book', $data);

            if(!empty($_FILES["file_name"]["name"])) {
                move_uploaded_file($_FILES["file_name"]["tmp_name"], "uploads/document/" . $_FILES["file_name"]["name"]);
            }

            $this->session->set_flashdata('flash_message' , get_phrase('data_updated'));
            redirect(site_url('librarian/book'), 'refresh');
        } else if ($param1 == 'edit') {
            $page_data['edit_data'] = $this->db->get_where('book', array('book_id' => $param2))->result_array();
        }

        if ($param1 == 'delete') {
            $this->db->where('book_id', $param2);
            $this->db->delete('book');

            $this->session->set_flashdata('flash_message' , get_phrase('data_deleted'));
            redirect(site_url('librarian/book'), 'refresh');
        }

        $page_data['books']      = $this->db->get('book')->result_array();
        $page_data['page_name']  = 'book';
        $page_data['page_title'] = get_phrase('manage_library_books');
        $this->load->view('backend/index', $page_data);
    }

    // MANAGE BOOK REQUESTS
    function book_request($param1 = "", $param2 = "")
    {
        if ($this->session->userdata('librarian_login') != 1)
        {
            $this->session->set_userdata('last_page', current_url());
            redirect(base_url(), 'refresh');
        }

        if ($param1 == "accept")
        {
            $data['status'] = 1;

            $this->db->update('book_request', $data, array('book_request_id' => $param2));

            // INCREMENT NUMBER OF ISSUED COPIES
            $book_id        = $this->db->get_where('book_request', array('book_request_id' => $param2))->row()->book_id;
            $issued_copies  = $this->db->get_where('book', array('book_id' => $book_id))->row()->issued_copies;

            $data2['issued_copies'] = $issued_copies + 1;

            $this->db->update('book', $data2, array('book_id' => $book_id));

            $this->session->set_flashdata('flash_message', get_phrase('request_accepted_successfully'));
            redirect(site_url('librarian/book_request'), 'refresh');
        }

        if ($param1 == "reject")
        {
            $data['status'] = 2;

            $this->db->update('book_request', $data, array('book_request_id' => $param2));

            $this->session->set_flashdata('flash_message', get_phrase('request_rejected_successfully'));
            redirect(site_url('librarian/book_request'), 'refresh');
        }

        $data['page_name']  = 'book_request';
        $data['page_title'] = get_phrase('book_request');
        $this->load->view('backend/index', $data);
    }

    // MANAGE OWN PROFILE AND CHANGE PASSWORD
    function manage_profile($param1 = '', $param2 = '', $param3 = '')
    {
        if ($this->session->userdata('librarian_login') != 1)
            redirect(site_url('login'), 'refresh');

        if ($param1 == 'update_profile_info') {
            $data['name']  = html_escape($this->input->post('name'));
            $data['email'] = html_escape($this->input->post('email'));

            $validation = email_validation_for_edit($data['email'], $this->session->userdata('librarian_id'), 'librarian');
            if($validation == 1){
                $this->db->where('librarian_id', $this->session->userdata('librarian_id'));
                $this->db->update('librarian', $data);
                $this->session->set_flashdata('flash_message', get_phrase('account_updated'));
            }
            else{
                $this->session->set_flashdata('error_message', get_phrase('this_email_id_is_not_available'));
            }
            redirect(site_url('librarian/manage_profile'), 'refresh');
        }

        if ($param1 == 'change_password') {
            $data['password']             = sha1($this->input->post('password'));
            $data['new_password']         = sha1($this->input->post('new_password'));
            $data['confirm_new_password'] = sha1($this->input->post('confirm_new_password'));

            $current_password = $this->db->get_where('librarian', array(
                'librarian_id' => $this->session->userdata('librarian_id')
            ))->row()->password;
            if ($current_password == $data['password'] && $data['new_password'] == $data['confirm_new_password']) {
                $this->db->where('librarian_id', $this->session->userdata('librarian_id'));
                $this->db->update('librarian', array(
                    'password' => $data['new_password']
                ));
                $this->session->set_flashdata('flash_message', get_phrase('password_updated'));
            } else {
                $this->session->set_flashdata('flash_message', get_phrase('password_mismatch'));
            }
            redirect(site_url('librarian/manage_profile'), 'refresh');
        }

        $page_data['page_name']  = 'manage_profile';
        $page_data['page_title'] = get_phrase('manage_profile');
        $page_data['edit_data']  = $this->db->get_where('librarian', array(
            'librarian_id' => $this->session->userdata('librarian_id')
        ))->result_array();
        $this->load->view('backend/index', $page_data);
    }

    function get_books() {
        if ($this->session->userdata('librarian_login') != 1)
            redirect(site_url('login'), 'refresh');

        $columns = array(
            0 => 'book_id',
            1 => 'name',
            2 => 'author',
            3 => 'description',
            4 => 'price',
            5 => 'class',
            6 => 'download',
            7 => 'options',
            8 => 'book_id'
        );

        $limit = $this->input->post('length');
        $start = $this->input->post('start');
        $order = $columns[$this->input->post('order')[0]['column']];
        $dir   = $this->input->post('order')[0]['dir'];

        $totalData = $this->ajaxload->all_books_count();
        $totalFiltered = $totalData;

        if(empty($this->input->post('search')['value'])) {
            $books = $this->ajaxload->all_books($limit,$start,$order,$dir);
        }
        else {
            $search = $this->input->post('search')['value'];
            $books =  $this->ajaxload->book_search($limit,$start,$search,$order,$dir);
            $totalFiltered = $this->ajaxload->book_search_count($search);
        }

        $data = array();
        if(!empty($books)) {
            foreach ($books as $row) {
                if ($row->file_name == null)
                    $download = '';
                else
                    $download = '<a href="'.site_url("uploads/document/$row->file_name").'" class="btn btn-blue btn-icon icon-left"><i class="entypo-download"></i>'.get_phrase('download').'</a>';

                $options = '<div class="btn-group"><button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                    Action <span class="caret"></span></button><ul class="dropdown-menu dropdown-default pull-right" role="menu"><li><a href="#" onclick="book_edit_modal('.$row->book_id.')"><i class="entypo-pencil"></i>&nbsp;'.get_phrase('edit').'</a></li><li class="divider"></li><li><a href="#" onclick="book_delete_confirm('.$row->book_id.')"><i class="entypo-trash"></i>&nbsp;'.get_phrase('delete').'</a></li></ul></div>';

                $nestedData['book_id'] = $row->book_id;
                $nestedData['name'] = $row->name;
                $nestedData['author'] = $row->author;
                $nestedData['description'] = $row->description;
                $nestedData['price'] = $row->price;
                $nestedData['class'] = $this->db->get_where('class', array('class_id' => $row->class_id))->row()->name;
                $nestedData['download'] = $download;
                $nestedData['options'] = $options;

                $data[] = $nestedData;
            }
        }

        $json_data = array(
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );

        echo json_encode($json_data);
    }
}
