<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class AveragePrice extends CI_Model {

    private $userTbl;

    function __construct() {
        $this->userTbl = 'reports_costs';
    }

    /*
     * get rows from the users table
     */

    function getRows($params = array()) {
        $this->db->select('*');
        if (isset($params['tbl']))
            $this->userTbl = $params['tbl'];
        $this->db->from($this->userTbl);

        //fetch data by conditions
        if (array_key_exists("conditions", $params)) {
            foreach ($params['conditions'] as $key => $value) {
                $this->db->where($key, $value);
            }
        }

        if (array_key_exists("id", $params)) {
            $this->db->where('id', $params['id']);
            $query = $this->db->get();
            $result = $query->row_array();
        } else {
            //set start and limit
            if (array_key_exists("start", $params) && array_key_exists("limit", $params)) {
                $this->db->limit($params['limit'], $params['start']);
            } elseif (!array_key_exists("start", $params) && array_key_exists("limit", $params)) {
                $this->db->limit($params['limit']);
            }
            $query = $this->db->get();
            if (array_key_exists("returnType", $params) && $params['returnType'] == 'count') {
                $result = $query->num_rows();
            } elseif (array_key_exists("returnType", $params) && $params['returnType'] == 'single') {
                $result = ($query->num_rows() > 0) ? $query->row_array() : FALSE;
            } else {
                $result = ($query->num_rows() > 0) ? $query->result_array() : FALSE;
            }
        }

        //return fetched data
        return $result;
    }

    
    function getAllContracts(){
        //$this->db->select("*");
        //$this->db->from("contracts");
         //$this->db->join("dropdown" . " as dropdown", "(" . $this->userTbl . ".cost_id=dropdown.id)", 'RIGHT');
        
    }
	
	function calcPrice(){
	$query=$this->db->query("SELECT GROUP_CONCAT(contract_model.model_id, '-', (reports_costs.value/4)*contract_model.model_qty,'-',contract_model.model_qty) as model_cost_year_cotract,`contract`.* FROM `contract`
INNER JOIN `contract_model` as `contract_model` ON (`contract_model`.`contract_id`=contract.id)
INNER JOIN `reports_costs` as `reports_costs` ON (`reports_costs`.`cost_id`=`contract_model`.`model_id` AND `reports_costs`.`year`=YEAR(STR_TO_DATE(start_day, '%Y-%m-%d')) AND `contract_model`.model_qty>0) 
#NNER JOIN `reports_costs` as `reports_costs_for_lc` ON (`reports_costs_for_lc`.`cost_id`=`contract`.`contract_lc` AND `reports_costs_for_lc`.`year`=YEAR(STR_TO_DATE(start_day, '%Y-%m-%d')))
GROUP BY `contract`.`id`
");	
	return ($query->num_rows())?$query->result_array():FALSE;	
	}
	

}
