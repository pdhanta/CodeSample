<?php

if (!defined('BASEPATH'))
  exit('No direct script access allowed');

class CostYear extends CI_Model {

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
  public function check_and_update($key,$data){
	  $this->db->select("*");
    $this->db->from("variables");
    $this->db->where("key",$key);
    $query = $this->db->get();
    
    $row = ($query->num_rows() > 0) ? $query->row_array() : false;
	
   if(isset($row['id'])){
	   
	   $row['value']=$data;
	   $id=$row['id'];
	   unset($row['id']);
	   $this->db->update("variables",$row,"id=".$id);
   }else{
	   $row['key']=$key;
	   $row['value']=$data;
	   $this->db->insert("variables",$row);
   }
    
  }
  public function getVariableOf($key){
	  $this->db->select("*");
    $this->db->from("variables");
    $this->db->where("key",$key);
    $query = $this->db->get();
    
    $row= ($query->num_rows() > 0) ? $query->row_array() : false;
	
   if($row){
	   return $row['value'];
   }
   return false;
  }

  function delRows($params = array()) {
    if (array_key_exists("conditions", $params)) {
      foreach ($params['conditions'] as $key => $value) {
        $this->db->where($key, $value);
      }
    }
    $this->db->delete($this->userTbl);
    //  echo $this->db->last_query();die;
  }

  public function insert($data = array()) {
    if (isset($data['dbtbl'])) {
      $this->userTbl = $data['dbtbl'];
      unset($data['dbtbl']);
    }
    //add created and modified data if not included
    if (!array_key_exists("created", $data)) {
      $data['created'] = date("Y-m-d H:i:s");
    }
    if (!array_key_exists("modified", $data)) {
      $data['modified'] = date("Y-m-d H:i:s");
    }

    //insert user data to users table
    $insert = $this->db->insert($this->userTbl, $data);

    //return the status
    if ($insert) {
      return $this->db->insert_id();
      ;
    } else {
      return false;
    }
  }

  public function update($data = array()) {

    if (isset($data['dbtbl'])) {
      $this->userTbl = $data['dbtbl'];
      unset($data['dbtbl']);
    }
    //add created and modified data if not included

    if (!array_key_exists("modified", $data)) {
      $data['modified'] = date("Y-m-d H:i:s");
    }
    //echo $this->userTbl;
    $this->db->where('id', $data['id']);

    //insert user data to users table
    $update = $this->db->update($this->userTbl, $data);

    //return the status
    if ($update) {
      return true;
    } else {
      return false;
    }
  }
  function getCostsEntriesFor($fromYear, $toYear) {
    $this->db->select("dropdown.dropdown_cat_id,dropdown_categories.title as Label,dropdown.title,dropdown.id,GROUP_CONCAT(" . $this->userTbl . ".year,'='," . $this->userTbl . ".value) as year_value");
    $this->db->from($this->userTbl);
    $this->db->join("dropdown" . " as dropdown", "(" . $this->userTbl . ".cost_id=dropdown.id)", 'RIGHT');
    $this->db->join("dropdown_categories" . " as dropdown_categories", "(dropdown.dropdown_cat_id=dropdown_categories.id)", 'INNER');
    $this->db->group_by("dropdown.id");
    $this->db->where("(Year BETWEEN " . $fromYear . " AND " . $toYear . " OR year IS NULL)  AND dropdown.dropdown_cat_id IN (1,2,50,51,52) ");
    $this->db->order_by("dropdown.dropdown_cat_id");
    $query = $this->db->get();
    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    if ($allRows)
      return $allRows;
  }
  function getTeliaInfoPrices() {
    $this->db->select("dropdown.slug,reports_costs.value,reports_costs.year");
    $this->db->from("reports_costs");
    $this->db->join("dropdown", "(reports_costs.cost_id=dropdown.id AND dropdown.dropdown_cat_id IN (51))", 'INNER');
    $query = $this->db->get();
    //echo $this->db->last_query();
    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    $output = [];
	if($allRows){
		foreach ($allRows as $row) {
		  $output[$row['slug']][$row['year']] = $row['value'];
		}
	}
    return $output;
  }

  function calcProductPrice() { 
		$query = $this->db->query("SELECT sum((SELECT sum(contract_model.model_qty) FROM contract_model INNER JOIN dropdown ON (contract_model.model_id=dropdown.id AND dropdown.dropdown_cat_id=1) WHERE dropdown.is_calc=1 AND contract_model.contract_id=contract.id GROUP BY contract_model.contract_id) ) as alarm_total, YEAR(DATE(start_day)) as year,sum(contract.rent_per_month) as rent_per_month FROM contract WHERE 
(contract.contract_ends_extended!=1 OR    contract.contract_ends_extended IS NULL) and rent_per_month !='' and id in (select contract_id from contract_model INNER JOIN dropdown ON (contract_model.model_id=dropdown.id AND dropdown.dropdown_cat_id=1) WHERE dropdown.is_calc=1  GROUP BY contract_model.contract_id)  GROUP BY YEAR(DATE(start_day))
		");
		
    return ($query->num_rows()) ? $query->result_array() : FALSE;
  }

  function getAllContractsForFutureRevenue($toDate = '', $fromDate = '', $condition = '', $status = '') {
    $this->db->select(
            "contract.*,
			1 as is_system_extend,
			(SELECT GROUP_CONCAT(reports_costs.cost_id,'=',reports_costs.year,'@',reports_costs.value) FROM reports_costs INNER JOIN dropdown on dropdown.id=reports_costs.cost_id WHERE reports_costs.cost_id IN (SELECT model_id FROM contract_model WHERE contract_id=contract.id AND category_id=1 GROUP BY contract_id)) as model_pricing,
			(SELECT GROUP_CONCAT(reports_costs.cost_id,'=',reports_costs.year,'@',reports_costs.value) FROM reports_costs INNER JOIN dropdown on dropdown.id=reports_costs.cost_id WHERE reports_costs.cost_id IN (SELECT model_id FROM contract_model WHERE contract_id=contract.id AND invoice!=1 AND category_id=2 GROUP BY contract_id)) as lc_pricing,
                 (SELECT GROUP_CONCAT(model_id,'=',model_qty) FROM contract_model WHERE contract_id=contract.id AND category_id=1 GROUP BY contract_id) as models,
				 (SELECT GROUP_CONCAT(model_id,'=',model_qty) FROM contract_model WHERE contract_id=contract.id AND category_id=2 GROUP BY contract_id) as lcs,
                 
                 ");
    $this->db->from("contract");
    if ($condition != '') {
      $this->db->having($condition);
    }
    if ($toDate != '') {
      $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') <=", $toDate);
    }
    if ($fromDate != '') {
      $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') >=", $fromDate);
    }
    //$this->db->where("contract.status = 1");
   // if (!isset($status)) {
     // $this->db->where("contract.status = 1");
    //}else{
	//	$this->db->where("(contract.contract_ends_extended = 0 OR contract.contract_ends_extended IS NULL )");
	//}
    //$this->db->where("contract.id in(7643)");
    $query = $this->db->get();
	//echo $this->db->last_query();die;
	//die;
	
    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    if ($allRows)
      return $allRows;
  }

  function getEducationsPrices() {
    $this->db->select("dropdown.slug,reports_costs.value,reports_costs.year");
    $this->db->from("reports_costs");
    $this->db->join("dropdown", "(reports_costs.cost_id=dropdown.id AND dropdown.dropdown_cat_id IN (50))", 'INNER');
    $query = $this->db->get();

    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    $output = [];
	if($allRows){
		foreach ($allRows as $row) {
		  $output[$row['slug']][$row['year']] = $row['value'];
		}
	}
    return $output;
  }
  function getAllLarmCountByLC($toDate = '', $fromDate = '', $condition = '') {

    $this->db->select(
            "dropdown.title as lc_name,
			YEAR(STR_TO_DATE(added_date, '%Y-%m-%d')) as year,
			count(alarms.id) as TotalBYLC,
			dropdown.id as lcID
                 ");
    $this->db->from("alarms");
    $this->db->join("dropdown", "(alarms.alarm_central=dropdown.slug AND dropdown.dropdown_cat_id=2)", 'INNER');

    if ($condition != '') {
      $this->db->having($condition);
    }
    if ($toDate != '') {
      $this->db->where("STR_TO_DATE(added_date, '%Y-%m-%d') <=", $toDate);
    }
    if ($fromDate != '') {
      $this->db->where("STR_TO_DATE(added_date, '%Y-%m-%d') >=", $fromDate);
    }
  
    $this->db->group_by("alarms.alarm_central, YEAR(STR_TO_DATE(added_date, '%Y-%m-%d'))");
    //$this->db->where("contract.id=7507");
    $query = $this->db->get();
    // echo $this->db->last_query();
    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    if ($allRows)
      return $allRows;
  }

 function getTotalContracts($toDate = '', $fromDate = '', $condition = '',$status="",$where_condition="") {
		
    $this->db->select(
            " count(*) as total_count,is_extension_agreement,YEAR(STR_TO_DATE(start_day, '%Y-%m-%d')) as year, SUM((SELECT SUM(model_qty) FROM contract_model WHERE contract_id=contract.id GROUP BY contract_id)) as totalPeryear
                 ");
    $this->db->from("contract");    
	
    if ($condition != '') {
      $this->db->having($condition);
    }
    if ($toDate != '') {
      $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') <=", $toDate);
    }
    if ($fromDate != '') {
      $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') >=", $fromDate);
    }
	if ($status == '') {
		$this->db->where("contract.status = 1");
	}
	if ($where_condition != '') {
      $this->db->where($where_condition);
    }
	
    $this->db->group_by("YEAR(STR_TO_DATE(start_day, '%Y-%m-%d'))");
    //$this->db->where("contract.id=7507");
    $query = $this->db->get();
	//echo $this->db->last_query();
	$this->db->last_query();
    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    if ($allRows)
      return $allRows;
  }
  
  function getTotalModelContracts($toDate = '', $fromDate = '', $condition = '',$status="",$where_condition="") {
		
    $this->db->select(
            " count(*) as total_count,is_extension_agreement,YEAR(STR_TO_DATE(start_day, '%Y-%m-%d')) as year, SUM((SELECT SUM(model_qty) FROM contract_model WHERE contract_id=contract.id and category_id=1 GROUP BY contract_id)) as totalPeryear
                 ");
    $this->db->from("contract");    
	
    if ($condition != '') {
      $this->db->having($condition);
    }
    if ($toDate != '') {
      $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') <=", $toDate);
    }
    if ($fromDate != '') {
      $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') >=", $fromDate);
    }
	if ($status == '') {
		$this->db->where("contract.status = 1");
	}
	if ($where_condition != '') {
      $this->db->where($where_condition);
    }
	
    $this->db->group_by("YEAR(STR_TO_DATE(start_day, '%Y-%m-%d'))");
    //$this->db->where("contract.id=7507");
    $query = $this->db->get();
	//echo $this->db->last_query();
	$this->db->last_query();
    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    if ($allRows)
      return $allRows;
  }
  
  function getTotalAlarms($toDate = '', $fromDate = '', $condition = '',$status="",$where_condition="") {
		
    $this->db->select(
            " count(*) as total_count,is_extension_agreement,YEAR(STR_TO_DATE(added_date, '%Y-%m-%d')) as year");
    $this->db->from("alarms");    
	
   
    if ($toDate != '') {
      $this->db->where("STR_TO_DATE(added_date, '%Y-%m-%d') <=", $toDate);
    }
    if ($fromDate != '') {
      $this->db->where("STR_TO_DATE(added_date, '%Y-%m-%d') >=", $fromDate);
    }
	if ($status == '') {
		$this->db->where("alarms.status = 1");
	}
	if ($where_condition != '') {
      $this->db->where($where_condition);
    }
	
    $this->db->group_by("YEAR(STR_TO_DATE(added_date, '%Y-%m-%d'))");
	 if ($condition != '') {
      $this->db->having($condition);
    }
    //$this->db->where("contract.id=7507");
    $query = $this->db->get();
	//echo $this->db->last_query();
	$this->db->last_query();
    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    if ($allRows)
      return $allRows;
  }

  function getTotalKontants($toDate = '', $fromDate = '', $condition = '') {
    $this->db->select(
            "YEAR(STR_TO_DATE(added_date, '%Y-%m-%d')) as year, count(id) as totalPeryear
                 ");
    $this->db->from("alarms");
    //$this->db->join("dropdown", "(contract.contract_lc=dropdown.id AND dropdown.dropdown_cat_id=2)", 'INNER');

    if ($condition != '') {
      $this->db->having($condition);
    }
    if ($toDate != '') {
      $this->db->where("STR_TO_DATE(added_date, '%Y-%m-%d') <=", $toDate);
    }
    if ($fromDate != '') {
      $this->db->where("STR_TO_DATE(added_date, '%Y-%m-%d') >=", $fromDate);
    }
    $this->db->where("alarms.status = 1");
    $this->db->where("alarms.alarm_doc_type ",'larmunderlag-kontact');
    $this->db->group_by("YEAR(STR_TO_DATE(added_date, '%Y-%m-%d'))");
    //$this->db->where("contract.id=7507");
    $query = $this->db->get();
    // echo $this->db->last_query();
    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    if ($allRows)
      return $allRows;
  }
  
   function getAllContractsByLength($toDate = '', $fromDate = '', $condition = '') {
   $this->db->select(
            "YEAR(STR_TO_DATE(start_day, '%Y-%m-%d')) as year,
            count(contract.id) as count,
            contract.months
                 ");
    $this->db->from("contract");
    
    if ($condition != '') {
      $this->db->having($condition);
    }
    if ($toDate != '') {
      $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') <=", $toDate);
    }
    if ($fromDate != '') {
      $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') >=", $fromDate);
    }
    $this->db->where("contract.status = 1");
    $this->db->group_by("YEAR(STR_TO_DATE(start_day, '%Y-%m-%d')),months");
    //$this->db->where("contract.id=7507");
    $query = $this->db->get();
   //  echo $this->db->last_query();
	//die;
    $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
    if ($allRows)

      return $allRows;
  }
  
   function getAllContractsForCommission($toDate = '', $fromDate = '', $condition = [],$status=null,$pagination=null) {
	 $this->db->select(
			"contract.*,customers.name as custome_name_,departments.name as avdelning_,(SELECT GROUP_CONCAT(reports_costs.cost_id,'=',reports_costs.year,'@',reports_costs.value) FROM reports_costs INNER JOIN dropdown on dropdown.id=reports_costs.cost_id WHERE reports_costs.cost_id IN (SELECT model_id FROM contract_model WHERE contract_id=contract.id AND category_id=1 GROUP BY contract_id)) as model_pricing,
(SELECT GROUP_CONCAT(reports_costs.cost_id,'=',reports_costs.year,'@',reports_costs.value) FROM reports_costs INNER JOIN dropdown on dropdown.id=reports_costs.cost_id WHERE reports_costs.cost_id IN (SELECT model_id FROM contract_model WHERE contract_id=contract.id AND invoice!=1 AND category_id=2 GROUP BY contract_id)) as lc_pricing,
(SELECT GROUP_CONCAT(model_id,'=',model_qty) FROM contract_model WHERE contract_id=contract.id AND category_id=1 GROUP BY contract_id) as models,
(SELECT GROUP_CONCAT(model_id,'=',model_qty) FROM contract_model WHERE contract_id=contract.id AND category_id=2 GROUP BY contract_id) as lcs"); 
	$this->_get_datatables_query();	// With Searching field 			 
	//$this->db->from("contract");        
	$this->db->join('customers', ' customers.id = contract.customer_name', 'left');
	$this->db->join('departments', ' departments.id = contract.avdelning', 'left');
	
	/*if (!empty($condition)) {		
		$this->db->having($condition);
	}*/
	
	if(isset($_POST['length']) && $_POST['length'] != -1)  // Pagination Length per page
	$this->db->limit($_POST['length'], $_POST['start']);
	
	
	if (!empty($FilterData)) {
		foreach ($FilterData['conditions'] as $key => $data) {
			if (is_array($data)) {
				$this->db->where_in($key, $data);
			} else {
				$this->db->where($key, $data);
			}
		}
    }
	$f=0;
	$this->db->join('contract_commission', ' contract_commission.contract_id = contract.id', 'inner');
	/*if (!empty($condition)) {		
		$this->db->having($condition);
	}*/
	if ($toDate != '') {
		$this->db->where("STR_TO_DATE(contract_commission.paid_date,'%Y-%m-%d') <=", $toDate);
		$f=1;
	}
	if ($fromDate != '') {
		$this->db->where("STR_TO_DATE(contract_commission.paid_date,'%Y-%m-%d')>= ", $fromDate);
		$f=1;
	}
	if($f==1){
		$this->db->where("contract_commission.paid_date IS NOT NULL  ");
	}
	if(isset($condition['where']) && !empty($condition['where'])){
		//print_r($condition['where']);
		foreach($condition['where'] as $field=>$val){
			$this->db->where('contract.'.$field,$val);
		}
    }
	/* if (!empty($condition['having']['kontact_person_os'])) {		
		$this->db->having($condition['having']['kontact_person_os']);
	} */
	$this->db->group_by("contract_commission.contract_id");
	//$this->db->where("contract.signed_agreement=1 AND pdf!='' and contract.status = 1 ");
  $this->db->where("contract.signed_agreement=1 AND pdf!=''");
  $query = $this->db->get();
	//echo $this->db->last_query();
	//die;  
	$allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
	if ($allRows)
		return $allRows;
    }
	
	
	function getAllContractsForAveragePrice($toDate = '', $fromDate = '', $condition = '',$status=null) {
         $this->db->select(
                "contract.*,(SELECT GROUP_CONCAT(reports_costs.cost_id,'=',reports_costs.year,'@',reports_costs.value) FROM reports_costs INNER JOIN dropdown on dropdown.id=reports_costs.cost_id WHERE reports_costs.cost_id IN (SELECT model_id FROM contract_model WHERE contract_id=contract.id AND category_id=1 GROUP BY contract_id)) as model_pricing,
(SELECT GROUP_CONCAT(reports_costs.cost_id,'=',reports_costs.year,'@',reports_costs.value) FROM reports_costs INNER JOIN dropdown on dropdown.id=reports_costs.cost_id WHERE reports_costs.cost_id IN (SELECT model_id FROM contract_model WHERE contract_id=contract.id AND invoice!=1 AND category_id=2 GROUP BY contract_id)) as lc_pricing,
(SELECT GROUP_CONCAT(model_id, '=', model_qty) FROM contract_model  INNER JOIN dropdown on (dropdown.id=contract_model.model_id and dropdown.is_calc=1)  WHERE contract_id=contract.id AND category_id=1 GROUP BY contract_id) as models,
(SELECT GROUP_CONCAT(model_id,'=',model_qty) FROM contract_model WHERE contract_id=contract.id AND category_id=2 GROUP BY contract_id) as lcs"); 
				 
		$this->db->join('contract_model', ' contract_model.contract_id=contract.id AND contract_model.model_qty>0 AND category_id=1 ', 'INNER');		
        $this->db->from("contract");
        if (!empty($condition)) {			
            $this->db->having($condition);
        }
        if ($toDate != '') {
            $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') <=", $toDate);
        }
        if ($fromDate != '') {
            $this->db->where("STR_TO_DATE(start_day, '%Y-%m-%d') >=", $fromDate);
        }
		if (!isset($status)) {			
        $this->db->where("contract.status = 1");
		}
		//$this->db->where("contract.id IN (7507)");		
		 $this->db->having(" models > 0");
        $query = $this->db->get();
		$this->db->last_query();
		//die;
		
        $allRows = ($query->num_rows() > 0) ? $query->result_array() : false;
        if ($allRows)
            return $allRows;
    }
	
	function getProductStats($to=null,$from=null,$thisDate=null,$group_by=''){
		$this->db->select("dropdown.id,dropdown.title,dropdown.slug, count(dropdown.slug) as model_total,alarms.added_date");
		$this->db->from("dropdown");		
		$this->db->join('alarms', 'alarms.alarm_model = dropdown.slug', 'inner');
		$this->db->where('dropdown.dropdown_cat_id=1');	
		if($from!="")
		$this->db->where("STR_TO_DATE(alarms.added_date, '%Y-%m-%d') >= '$from'");
		if($to!="")
		$this->db->where("STR_TO_DATE(alarms.added_date, '%Y-%m-%d')<= '$to' AND alarms.added_date!=''");
	
		$this->db->where("alarms.alarm_doc_type != 'larmunderlag-kontact'");
		
		$this->db->group_by(' YEAR(STR_TO_DATE(added_date, "%Y-%m-%d")) '); 
		
		if(!empty($group_by))
		$this->db->group_by('dropdown.id');
		$query=$this->db->get();
		//$res=$query->result_array();				
		return ($query->num_rows()>0)?$query->result_array():FALSE; 
	}	
	
	function getExtensionPercentTypeA() {
	$this->db->select("contract.id");
	$this->db->from("contract");
	$this->db->where("status!=1");
	$query = $this->db->get();
	$this->db->last_query();
	$totalCancelled = $query->num_rows();

	$this->db->select("contract.id");
	$this->db->from("contract");
	
	$this->db->where("contract.contract_ends_extended=1");
	$query = $this->db->get();
	$this->db->last_query();
	$totalExtended = $query->num_rows();
		if ($totalExtended > 0 && $totalCancelled > 0) {
			return round($totalExtended / $totalCancelled * 100);
		} else {
			return 0;
		}
	}
	
  function getExtensionPercentTypeB() {
    $this->db->select("sum(contract.rent_per_month) as total_rent_for_cancelled");
    $this->db->from("contract");
    $this->db->where("status!=1 ");
    $query = $this->db->get();
    $result = ($query->num_rows() > 0) ? $query->row_array() : FALSE;
    if ($result) {
      $totalCancelled = $result['total_rent_for_cancelled'];
    } else {
      $totalCancelled = 0;
    }

    $this->db->select("sum(contract.rent_per_month) as total_rent_for_extended");
    $this->db->from("contract");
   
	$this->db->where("contract.contract_ends_extended=1 ");
    $query = $this->db->get();
    $result = ($query->num_rows() > 0) ? $query->row_array() : FALSE;
    if ($result) {
      $totalExtended = $result['total_rent_for_extended'];
    } else {
      $totalExtended = 0;
    }

    if ($totalExtended > 0 && $totalCancelled > 0) {
      return round($totalExtended / $totalCancelled * 100);
    } else {
      return 0;
    }
  }
  
  var $table = 'contract';
    var $column_order = array('customer_name','contract_number', 'months', 'avdelning_n', 'contract_number', 'signed_agreement', 'billed_by_os','end_date','salesperson_name','status', 'org_nr', 'previous_contract_number', 'address', 'postnummer', 'city', 'first_lastname', 'freetext', 'quantity', 'contract.email', 'setup_fee', 'months', 'years', 'rent_per_month', 'special_conditions', 'place_date', 'place_date1', 'renter', 'hirer', 'renter_signature', 'hirer_signature', 'name_enhancement', 'name_enhancement1', 'payment_cicle', 'pdf', 'expire', 'extra', 'contract.created', 'contract.modified'); 
	
	//set column field database for datatable orderable
	var $column_order_commission = array('customer_name','contract_number','months','','','start_day');	
    var $column_search = array('start_day', 'customer_name', 'contract_number', 'kontact_person_os', 'signed_agreement', 'end_date', 'renew_agreement', 'org_nr', 'previous_contract_number', 'address', 'postnummer', 'city', 'first_lastname', 'freetext', 'quantity', 'contract.email', 'setup_fee', 'months', 'years', 'rent_per_month', 'special_conditions', 'place_date', 'place_date1', 'renter', 'hirer', 'renter_signature', 'hirer_signature', 'name_enhancement', 'name_enhancement1', 'payment_cicle', 'pdf', 'expire', 'extra', 'contract.created', 'contract.modified');
    var $order = array('created' => 'asc'); // default order 
  
	private function _get_datatables_query(){
		$this->db->from($this->table);
	
		if ($this->input->post('kontact_person_os')) {
            $this->db->where('kontact_person_os', $this->input->post('kontact_person_os'));
        }		
		// Get only those contracts where payment commission percent is great 0
		$this->db->where("procent_provision >0");
		
		$i = 0;
		// loop column 
		foreach ($this->column_search as $item){
			// if datatable send POST for search
			if(isset($_POST['search']['value'])){	
				// first loop
				if($i===0){
					$this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
					$this->db->like($item, $_POST['search']['value']);
				}
				else{
					$this->db->or_like($item, $_POST['search']['value']);
				}
				if(count($this->column_search) - 1 == $i) //last loop
					$this->db->group_end(); //close bracket
			}
			$i++;
		}
		// here order processing
		if(isset($_POST['order'])) {
			$this->db->order_by($this->column_order_commission[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
		} 
		else if(isset($this->order)){
			$order = $this->order;
			$this->db->order_by(key($order), $order[key($order)]);
		}
	}

	function count_filtered($toDate = '', $fromDate = '', $condition = [],$status=null,$pagination=null){
		$this->_get_datatables_query();
		$f=0;
		$this->db->join('contract_commission', ' contract_commission.contract_id = contract.id', 'inner');
		/*if (!empty($condition)) {		
			$this->db->having($condition);
		}*/
		if ($toDate != '') {
			$this->db->where("STR_TO_DATE(contract_commission.paid_date,'%Y-%m-%d') <=", $toDate);
			$f=1;
		}
		if ($fromDate != '') {
			$this->db->where("STR_TO_DATE(contract_commission.paid_date,'%Y-%m-%d')>= ", $fromDate);
			$f=1;
		}
		if($f==1){
			$this->db->where("contract_commission.paid_date IS NOT NULL  ");
		}
		if(isset($condition['where']) && !empty($condition['where'])){		
			foreach($condition['where'] as $field=>$val){
				$this->db->where('contract.'.$field,$val);
			}
		}
		/* if (!empty($condition['having']['kontact_person_os'])) {		
			$this->db->having($condition['having']['kontact_person_os']);
		} */
		$this->db->group_by("contract_commission.contract_id");
		//$this->db->where("contract.signed_agreement=1 AND pdf!='' and contract.status = 1 ");
    $this->db->where("contract.signed_agreement=1 AND pdf!='' ");
    
		$query = $this->db->get();
		//echo $this->db->last_query();
		$rows=$query->num_rows();
		//print_r($rows);
		return $query->num_rows();
	}
	
}
