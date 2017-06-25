<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of product_model
 *
 * @author Krittkarin.C
 */
class Product_model extends MY_Model {

    public $table = 'products'; // you MUST mention the table name
    public $primary_key = 'product_id'; // you MUST mention the primary key
    public $fillable = array(); // If you want, you can set an array with the fields that can be filled by insert/update
    public $protected = array(); // ...Or you can set an array with the fields that cannot be filled by insert/update
    public $delete_cache_on_save = TRUE;

    public function __construct() {
        parent::__construct();
    }

    public function search($product_id = NULL,$search=NULL) {
        $where = " 1=1 ";
        if (!is_null($product_id)) {
            $where .= " AND `{$this->table}`.`{$this->primary_key}` = {$product_id} ";
        }
        

        if (!is_null($search)) {
            $where .= " AND (`{$this->table}`.`product_code` like '%{$search}%' "
            . "or `{$this->table}`.`product_name` like '%{$search}%' ) "
            . "and `{$this->table}`.`active` = 1";
        }

        $data = $this->db->query(
                " SELECT `{$this->table}`.*,`category`.`cat_desc` FROM `{$this->table}` "
                . "left join `category` on  `{$this->table}`.`cat_id` = `category`.`cat_id` and `category`.`active` = 1 "
                . "where {$where} "
        );
        return $data;
    }
    
    public function get($product_code='X') {
        $branchs = 1; //Change Branchs by User account
        $this->db->from($this->table);
        $this->db->join('category', "category.cat_id = {$this->table}.cat_id and category.active=1");
        $this->db->join('stock', "stock.product_id = {$this->table}.product_id and stock.active=1 and stock.branchs_id = {$branchs}");
        $this->db->where('product_code', $product_code);
        $this->db->where("{$this->table}.active", 1);
        $data = $this->db->get();
        return $data;
    }
    
    public function read($where = array(), $limit = NULL, $offet = 0) {
        $criteria = array('active' => MY_Model::FLAG_DATA_ACTIVE);

        if (!empty($where)) {
            foreach ($where as $f => $v) {
                $criteria[$f] = $v;
            }
        }

        $q = $this->order_by('product_name', 'ASC');

        if (!is_null($limit)) {
            $q->limit($limit, $offet);
        }

        $data = $q->get_all($criteria);
        //$data = $q->set_cache("customer_read", 500)->get_all($criteria);

        return $data;
    }
    
    public function addStock($data = null) {
        $this->db->trans_start();
        // product
        $this->db->insert("products",$data);
        $producID = $this->db->insert_id();
        
        //stock
        $this->db->insert("stock",array(
            "branchs_id" => $data['product_branch_origin'],
            "product_id" => $producID,
            "stock_qty_ori" =>$data['quantity'],
            "stock_qty_remaining"=>$data['quantity'],
            "active"=>1,

        ));
        $this->db->trans_complete();

        return $this->db->trans_status();
    }
    

    public function insert($data = null) {
        if ($this->db->insert($this->table, $data))
            return true;
        return false;
    }

    public function save($product_id = null, $data = null) {
        $this->db->where("{$this->primary_key}", $product_id);
        if ($this->db->update($this->table, $data))
            return true;
        return false;
    }

    public function toggle_status($product_id) {
        $q = "UPDATE `{$this->table}` SET `active` = NOT `active` where `{$this->primary_key}`={$product_id} ";
        if ($this->db->query($q))
            return true;
        return false;
    }

}
