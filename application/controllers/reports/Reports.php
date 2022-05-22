<?php

if (!defined('BASEPATH'))
  exit('No direct script access allowed');

/**
 * User Management class created by CodexWorld
 */
class Reports extends MY_Controller {

  private $curTimeObj;
  private $startYear;
  private $endYear;
  private $defaultPercent = '0';
  private $calcPercentA = 0;
  private $calcPercentB = 0;
  private $tillThisYear = 0;

  public function __construct() {
    parent::__construct();
    $this->curTimeObj = getTimeObj();
    $this->startYear = (($this->curTimeObj->format("Y") - YEAR_RANGE) > 2013) ? ($this->curTimeObj->format("Y") - YEAR_RANGE) : 2013;
    $this->endYear = $this->curTimeObj->format("Y") + YEAR_RANGE;
    $this->load->model('reports/costYear');
    $this->load->model('reports/costMonth');
    $this->defaultPercent = $this->calcPercentA = $this->costYear->getExtensionPercentTypeA();
    $this->calcPercentB = $this->costYear->getExtensionPercentTypeB();
  }

  public function future_revenue($doLoadForm = true, $from = '', $to = '') {
    if (!isset($_GET['is_ajax'])) {
      $this->load->view('templates/header');
      $this->load->view("reports/reports/future_revenue");
      $this->load->view('templates/footer');
      return;
    }
    $thisTime = ($from == '') ? getTimeObj() : new DateTime($from);
    if ($to == '') {
      $tillTime = getTimeObj();
      $interval = new DateInterval('P10Y');
      $tillTime->add($interval);
      $tillTime = new DateTime($tillTime->format("Y-12-31"));
    } else {
      $tillTime = new DateTime($to);
    }

    $data['from'] = $thisTime->format("Y");
    $data['to'] = $tillTime->format("Y");
    $data['addtional_future_data'] = json_decode($this->costYear->getVariableOf("addtional_future_data"), 1);

    if (isset($_GET['extension_only']) && $_GET['extension_only'] == 1) {
      $condition = "is_extension_agreement=1";
    } else if (isset($_GET['extension_only']) && $_GET['extension_only'] == 'all') {
      $condition = "";
    } else {
      $condition = "is_extension_agreement=0";
    }
    $extensionPercent = ($this->input->post('percentage_box')) ? $this->input->post('percentage_box') : 0;
    $extension_type = ($this->input->post('extension_type')) ? $this->input->post('extension_type') : 'normal';
    $data['calcPercentA'] = $this->calcPercentA;
    $data['calcPercentB'] = $this->calcPercentB;
    $data['doLoadForm'] = $doLoadForm;
    $allContracts = $this->costYear->getAllContractsForFutureRevenue('', '', $condition);
    $teliaInfoPrice = $this->costYear->getTeliaInfoPrices();
    $eduInfoPrice = $this->costYear->getEducationsPrices();
    $tableData = [];

    //print_r($allContracts);
    if (!empty($allContracts)) {
      foreach ($allContracts as $key => $contract) {
        $this->calculateFutureRevenue($contract, $teliaInfoPrice, $eduInfoPrice, $extension_type, $extensionPercent, (($from != '') ? $thisTime : false), $tillTime->format("Y-m"), $tableData);
      }
    } else {
      echo "We don't find any records having selected criteria";
      die;
    }

    while ($thisTime <= $tillTime) {
      $overallCost = 0;
      $overAllRevenue = 0;

      $lcCost = 0;
      $simCost = 0;
      $otherCost = 0;
      if (isset($tableData[$thisTime->format("Y")])) {
        $overAllRevenue += (isset($tableData[$thisTime->format("Y")]['normal']['monthly_revenue'])) ? $tableData[$thisTime->format("Y")]['normal']['monthly_revenue'] : 0;
        $overAllRevenue += (isset($tableData[$thisTime->format("Y")]['extended']['monthly_revenue'])) ? $tableData[$thisTime->format("Y")]['extended']['monthly_revenue'] : 0;

        $overallCost += (isset($tableData[$thisTime->format("Y")]['normal']['cost'])) ? $tableData[$thisTime->format("Y")]['normal']['cost'] : 0;
        $overallCost += (isset($tableData[$thisTime->format("Y")]['extended']['cost'])) ? $tableData[$thisTime->format("Y")]['extended']['cost'] : 0;
        /** Code for categorize cost * */
        $lcCost += (isset($tableData[$thisTime->format("Y")]['normal']['cateogry_cost']['lc_cost'])) ? $tableData[$thisTime->format("Y")]['normal']['cateogry_cost']['lc_cost'] : 0;
        $lcCost += (isset($tableData[$thisTime->format("Y")]['extended']['cateogry_cost']['lc_cost'])) ? $tableData[$thisTime->format("Y")]['extended']['cateogry_cost']['lc_cost'] : 0;

        $simCost += (isset($tableData[$thisTime->format("Y")]['normal']['cateogry_cost']['sim_cost'])) ? $tableData[$thisTime->format("Y")]['normal']['cateogry_cost']['sim_cost'] : 0;
        $simCost += (isset($tableData[$thisTime->format("Y")]['extended']['cateogry_cost']['sim_cost'])) ? $tableData[$thisTime->format("Y")]['extended']['cateogry_cost']['sim_cost'] : 0;

        $otherCost += (isset($tableData[$thisTime->format("Y")]['normal']['cateogry_cost']['others'])) ? $tableData[$thisTime->format("Y")]['normal']['cateogry_cost']['others'] : 0;
        $otherCost += (isset($tableData[$thisTime->format("Y")]['extended']['cateogry_cost']['others'])) ? $tableData[$thisTime->format("Y")]['extended']['cateogry_cost']['others'] : 0;
        /** END * */
      }
      $output[$thisTime->format("Y")]['yearly_revenue'] = round($overAllRevenue, 0);
      $output[$thisTime->format("Y")]['yearly_cost'] = round($overallCost, 0);
      /** Code for categorize cost * */
      $output[$thisTime->format("Y")]['sim'] = round($simCost, 0);
      $output[$thisTime->format("Y")]['lc'] = round($lcCost, 0);
      $output[$thisTime->format("Y")]['others'] = round($otherCost, 0);
      /** END * */
      $yearInterval = new DateInterval('P1Y');
      $thisTime->add($yearInterval);
    }

    $data['tableData'] = $output;
    $this->session->set_userdata("grah_data", json_encode($output));
    $data['is_extension_value'] = isset($_GET['extension_only']) ? $_GET['extension_only'] : 'no';
    $this->load->view("reports/reports/ajax/future_revenue_yearly", $data);
  }

  public function load_future_revenue_graph_data_ajax() {
    $startYear = ($this->input->post("start")) ? $this->input->post("start") : $this->startYear;
    $endYear = ($this->input->post("end")) ? $this->input->post("end") : $this->endYear;
    $output = json_decode($this->session->userdata("grah_data"), 1);

    $graphData['labels'] = range($startYear, $endYear);
    $graphData['datasets'][0] = [
        'data' => [],
        'backgroundColor' => 'rgba(255,192,3, 1)', //yellow
        'label' => 'Total kostnad per år'
    ];
    $graphData['datasets'][1] = [
        'data' => [],
        'backgroundColor' => 'rgba(237,125,49, 1)', //orange
        'label' => 'Total TB per år'
    ];
    foreach ($graphData['labels'] as $year) {
      $totalCost = 0;
      $totalMonthlyRevenue = 0;
      if (isset($output[$year])) {
        $graphData['datasets'][1]['data'][] = $output[$year]['yearly_revenue'] - $output[$year]['yearly_cost'];
        $graphData['datasets'][0]['data'][] = $output[$year]['yearly_cost'];
      }
      // $graphData['datasets'][1]['data'][] = $totalMonthlyRevenue;
      //$graphData['datasets'][0]['data'][] = $totalCost;
    }
    echo json_encode($graphData, 0);
    die;
  }

  public function specific_period() {
    if ($this->input->post("from_month") == '' && $this->input->post("to_month") == '') {
      $_GET['is_ajax'] = 1;
      $this->future_revenue(false);
    } else {
      $startDate = (isset($_POST['from_month']) && $_POST['from_month'] != '') ? $_POST['from_month'] : getTimeObj()->format("13-01-01");
      $toDate = (isset($_POST['to_month']) && $_POST['to_month'] != '') ? $_POST['to_month'] : getTimeObj()->format("Y-m-d");

      $tillTime = new DateTime($toDate);
      $thisTime = new DateTime($startDate);
      if ($tillTime->format("Y") - $thisTime->format("Y") > 2) {
        $_GET['is_ajax'] = 1;
        $this->future_revenue(false, $thisTime->format("Y-m-01"), $tillTime->format("Y-m-t"));
      } else {
        if (isset($_POST['extension_only']) && $_POST['extension_only'] == 1) {
          $condition = "is_extension_agreement=1";
        } else if (isset($_POST['extension_only']) && $_POST['extension_only'] == 'all') {
          $condition = "";
        } else {
          $condition = "is_extension_agreement=0";
        }
        $extensionPercent = ($this->input->post('percentage_box')) ? $this->input->post('percentage_box') : 0;
        $extension_type = ($this->input->post('extension_type')) ? $this->input->post('extension_type') : 'normal';

        $allContracts = $this->costYear->getAllContractsForFutureRevenue('', '', $condition);
        $teliaInfoPrice = $this->costYear->getTeliaInfoPrices();
        $eduInfoPrice = $this->costYear->getEducationsPrices();
        $tableData = [];
        if (!empty($allContracts)) {
          foreach ($allContracts as $key => $contract) {
            $this->calculateFutureRevenue($contract, $teliaInfoPrice, $eduInfoPrice, $extension_type, $extensionPercent, false, $tillTime->format("Y-m"), $tableData, false);
          }
        } else {
          echo "We don't find any records having selected criteria";
          die;
        }
        while ($thisTime <= $tillTime) {
          $overallCost = 0;
          $overAllRevenue = 0;
          $lcCost = 0;
          $simCost = 0;
          $otherCost = 0;
          if (isset($tableData[$thisTime->format("Yn")])) {
            $overAllRevenue += (isset($tableData[$thisTime->format("Yn")]['normal']['monthly_revenue'])) ? $tableData[$thisTime->format("Yn")]['normal']['monthly_revenue'] : 0;
            $overAllRevenue += (isset($tableData[$thisTime->format("Yn")]['extended']['monthly_revenue'])) ? $tableData[$thisTime->format("Yn")]['extended']['monthly_revenue'] : 0;

            $overallCost += (isset($tableData[$thisTime->format("Yn")]['normal']['cost'])) ? $tableData[$thisTime->format("Yn")]['normal']['cost'] : 0;
            $overallCost += (isset($tableData[$thisTime->format("Yn")]['extended']['cost'])) ? $tableData[$thisTime->format("Yn")]['extended']['cost'] : 0;
            /** Code for categorize cost * */
            $simCost += (isset($tableData[$thisTime->format("Yn")]['normal']['cateogry_cost']['sim_cost'])) ? $tableData[$thisTime->format("Yn")]['normal']['cateogry_cost']['sim_cost'] : 0;
            $simCost += (isset($tableData[$thisTime->format("Yn")]['extended']['cateogry_cost']['sim_cost'])) ? $tableData[$thisTime->format("Yn")]['extended']['cateogry_cost']['sim_cost'] : 0;

            $lcCost += (isset($tableData[$thisTime->format("Yn")]['normal']['cateogry_cost']['lc_cost'])) ? $tableData[$thisTime->format("Yn")]['normal']['cateogry_cost']['lc_cost'] : 0;
            $lcCost += (isset($tableData[$thisTime->format("Yn")]['extended']['cateogry_cost']['lc_cost'])) ? $tableData[$thisTime->format("Yn")]['extended']['cateogry_cost']['lc_cost'] : 0;

            $otherCost += (isset($tableData[$thisTime->format("Yn")]['normal']['cateogry_cost']['others'])) ? $tableData[$thisTime->format("Yn")]['normal']['cateogry_cost']['others'] : 0;
            $otherCost += (isset($tableData[$thisTime->format("Yn")]['extended']['cateogry_cost']['others'])) ? $tableData[$thisTime->format("Yn")]['extended']['cateogry_cost']['others'] : 0;
            /** END * */
          }

          $output[$thisTime->format("Yn")]['monthly_revenue'] = round($overAllRevenue, 0);
          $output[$thisTime->format("Yn")]['monthly_cost'] = round($overallCost, 0);
          /** Code for categorize cost * */
          $output[$thisTime->format("Yn")]['lc'] = round($lcCost, 0);
          $output[$thisTime->format("Yn")]['sim'] = round($simCost, 0);
          $output[$thisTime->format("Yn")]['others'] = round($otherCost, 0);
          /** END * */
          $yearInterval = new DateInterval('P1M');
          $thisTime->add($yearInterval);
        }
        $this->session->set_userdata("grah_data_monthly", json_encode($output));
        $data['start'] = $startDate;
        $data['to'] = $toDate;
        $data['tableData'] = $output;
        $this->load->view("reports/reports/ajax/future_revenue_monthly", $data);
      }
    }
  }

  public function load_future_revenue_monthly_graph_data_ajax() {
    $startDate = ($this->input->post("start")) ? $this->input->post("start") : $this->startYear;
    $toDate = ($this->input->post("end")) ? $this->input->post("end") : $this->endYear;

    $output = json_decode($this->session->userdata("grah_data_monthly"), 1);

    $graphData['datasets'][0] = [
        'data' => [],
        'backgroundColor' => 'rgba(255,192,3, 1)', //yellow
        'label' => 'Total kostnad per år'
    ];
    $graphData['datasets'][1] = [
        'data' => [],
        'backgroundColor' => 'rgba(237,125,49, 1)', //orange
        'label' => 'Total TB per år'
    ];

    $searchPeriodStart = new DateTime($startDate);
    $searchPeriodEnd = new DateTime($toDate);

    while ($searchPeriodEnd >= $searchPeriodStart) {
      $monthlyRevenue = isset($output[$searchPeriodStart->format("Yn")]['monthly_revenue']) ? $output[$searchPeriodStart->format("Yn")]['monthly_revenue'] : 0;
      $monthlyCost = isset($output[$searchPeriodStart->format("Yn")]['monthly_cost']) ? $output[$searchPeriodStart->format("Yn")]['monthly_cost'] : 0;
      $graphData['datasets'][1]['data'][] = $monthlyRevenue - $monthlyCost;
      $graphData['datasets'][0]['data'][] = $monthlyCost;
      $graphData['labels'][] = $searchPeriodStart->format("M Y");
      $monthInterval = new DateInterval('P1M');
      $searchPeriodStart->add($monthInterval);
    }
    echo json_encode($graphData, 0);
    die;
  }

  public function update_table_data() {
    $this->costYear->check_and_update("addtional_future_data", json_encode($_POST));
  }

  public function lc() {
    $this->load->view('templates/header');
    $this->load->view('reports/reports/lc');
    $this->load->view('templates/footer');
  }

  public function lc_total() {
    $startDay = $this->input->post("from_month");
    $endDay = $this->input->post("to_month");
    $allDocs = $this->costYear->getAllLarmCountByLC($endDay, $startDay);
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['canvasId'] = 'lc_total';
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['lcs'] = $this->generateYearlyFor($allDocs);
    $data['lcs'] = $data['Tlcs'] = $this->sortDesc($data['lcs']);
    $this->load->view('reports/reports/ajax/lc', $data);
  }

  public function lc_yearly() {
    $startDay = ($this->input->post("from_month") != '') ? $this->input->post("from_month") : getTimeObj()->format("Y-01-01");
    $endDay = ($this->input->post("to_month") != '') ? $this->input->post("to_month") : getTimeObj()->format("Y-12-31");
    $allDocs = $this->costYear->getAllLarmCountByLC($endDay, $startDay);

    $allDocsComplete = $this->costYear->getAllLarmCountByLC('', '');
    $data['Tlcs'] = $this->sortDesc($this->generateYearlyFor($allDocsComplete));

    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['canvasId'] = 'lc_yearly';
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['lcs'] = $this->generateYearlyFor($allDocs);
    $data['lcs'] = $this->sortDesc($data['lcs']);
    $this->load->view('reports/reports/ajax/lc', $data);
  }

  public function lc_monthly() {
    $startDay = ($this->input->post("from_month") != '') ? $this->input->post("from_month") : getTimeObj()->format("Y-m-01");
    $endDay = ($this->input->post("to_month") != '') ? $this->input->post("to_month") : getTimeObj()->format("Y-m-t");
    $allDocs = $this->costYear->getAllLarmCountByLC($endDay, $startDay);

    $allDocsComplete = $this->costYear->getAllLarmCountByLC('', '');
    $data['Tlcs'] = $this->sortDesc($this->generateYearlyFor($allDocsComplete));
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['canvasId'] = 'lc_monthly';
    $data['lcs'] = $this->generateYearlyFor($allDocs);
    $data['lcs'] = $this->sortDesc($data['lcs']);
    $this->load->view('reports/reports/ajax/lc', $data);
  }

  public function kontant_vs_contract() {
    $this->load->view('templates/header');
    $this->load->view('reports/reports/kontant_vs_contract');
    $this->load->view('templates/footer');
  }

  public function kontant_vs_contract_total() {
    $startDay = $this->input->post("from_month"); // != '') ? $this->input->post("from_month") : 
    $endDay = $this->input->post("to_month"); //!= '') ? $this->input->post("to_month") :    

    $contracts = $this->generateYearlyForContract($this->costYear->getProductStats($endDay, $startDay));

    $kontants = $this->generateYearlyForContractKontant($this->costYear->getTotalKontants($endDay, $startDay));
    $data['contracts'] = $contracts;
    $data['kontants'] = $kontants;
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['canvasId'] = 'kontant_vs_contract_total';
    $this->load->view('reports/reports/ajax/kontant_vs_contract', $data);
  }

  public function kontant_vs_contract_yearly() {
    $startDay = ($this->input->post("from_month") != '') ? $this->input->post("from_month") : getTimeObj()->format("Y-01-01");
    $endDay = ($this->input->post("to_month") != '') ? $this->input->post("to_month") : getTimeObj()->format("Y-12-31");
    $contracts = $this->generateYearlyForContract($this->costYear->getProductStats($endDay, $startDay));

    $kontants = $this->generateYearlyForContractKontant($this->costYear->getTotalKontants($endDay, $startDay));

    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = getTimeObj()->format("Y");
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['canvasId'] = 'kontant_vs_contract_yearly';
    $data['contracts'] = $contracts;
    $data['kontants'] = $kontants;
    $this->load->view('reports/reports/ajax/kontant_vs_contract', $data);
  }

  public function kontant_vs_contract_monthly() {
    $startDay = ($this->input->post("from_month") != '') ? $this->input->post("from_month") : getTimeObj()->format("Y-m-01");
    $endDay = ($this->input->post("to_month") != '') ? $this->input->post("to_month") : getTimeObj()->format("Y-m-t");
    $condition = "";
    $contracts = $this->generateYearlyForContract($this->costYear->getProductStats($endDay, $startDay));
    $kontants = $this->generateYearlyForContractKontant($this->costYear->getTotalKontants($endDay, $startDay));

    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = getTimeObj()->format("Y");
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['canvasId'] = 'kontant_vs_contract_monthly';
    $data['contracts'] = $contracts;
    $data['kontants'] = $kontants;
    $this->load->view('reports/reports/ajax/kontant_vs_contract', $data);
  }

  public function contract_length() {
    $this->load->view('templates/header');
    $this->load->view('reports/reports/contract_length');
    $this->load->view('templates/footer');
  }

  public function contract_length_total() {
    $startDay = $this->input->post("from_month"); // != '') ? $this->input->post("from_month") : getTimeObj()->format("Y-m-01");
    $endDay = $this->input->post("to_month"); //!= '') ? $this->input->post("to_month") : getTimeObj()->format("Y-m-t");
    //echo '<pre>';	
    //$contracts = $this->generateLenghtWise($this->costYear->getAllContractsByLength($endDay, $startDay));	
    $contracts = $this->sortDescKey($this->generateLenghtWise($this->costYear->getAllContractsByLength($endDay, $startDay)));
    $data['contracts'] = $data['contractsAll'] = $contracts;
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['canvasId'] = 'contract_length_total';
    $this->load->view('reports/reports/ajax/contract_length', $data);
  }

  public function contract_length_yearly() {
    $startDay = ($this->input->post("from_month") != '') ? $this->input->post("from_month") : getTimeObj()->format("Y-01-01");
    $endDay = ($this->input->post("to_month") != '') ? $this->input->post("to_month") : getTimeObj()->format("Y-12-31");

    $contracts = $this->sortDescKey($this->generateLenghtWise($this->costYear->getAllContractsByLength($endDay, $startDay)));

    $contractsAll = $this->sortDescKey($this->generateLenghtWise($this->costYear->getAllContractsByLength('', '')));
    $data['contractsAll'] = $contractsAll;

    $data['contracts'] = $contracts;
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['canvasId'] = 'contract_length_yearly';
    $this->load->view('reports/reports/ajax/contract_length', $data);
  }

  public function contract_length_monthly() {
    $startDay = ($this->input->post("from_month") != '') ? $this->input->post("from_month") : getTimeObj()->format("Y-m-01");
    $endDay = ($this->input->post("to_month") != '') ? $this->input->post("to_month") : getTimeObj()->format("Y-m-t");

    $contracts = $this->sortDescKey($this->generateLenghtWise($this->costYear->getAllContractsByLength($endDay, $startDay)));
    $contractsAll = $this->sortDescKey($this->generateLenghtWise($this->costYear->getAllContractsByLength('', '')));
    $data['contractsAll'] = $contractsAll;


    $data['contracts'] = $contracts;
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['canvasId'] = 'contract_length_monthly';
    $this->load->view('reports/reports/ajax/contract_length', $data);
  }

  public function average_price() {
    $data = array();
    $output = [];

    $data['from'] = $this->startYear;
    $data['to'] = $this->endYear;
    $results = $this->costYear->calcProductPrice();
    $ModelsIdWithoutTags = getModelsIdWithoutTags();
   

    if ($results) {
      foreach ($results as $key => $row) {
        $output[$row['year']]['montyly_price'] = ((isset($output[$row['year']]['montyly_price'])) ? $output[$row['year']]['montyly_price'] : 0) + $row['rent_per_month'];
        $output[$row['year']]['totalAlarms'] = ((isset($output[$row['year']]['totalAlarms'])) ? $output[$row['year']]['totalAlarms'] : 0) + $row['alarm_total'];
      }
    }
    //print_r($output);
    $data['avgPrice'] = array_reverse($output, true);
    $this->load->view('templates/header');
    $this->load->view("reports/reports/average_price", $data);
    $this->load->view('templates/footer');
  }

  public function order_value($doLoadForm = true, $from = '', $to = '') {
    $data = [];
    $condition = "";
    $status = "all";
    $tillTime = getTimeObj();
    $thisTime = ($from == '') ? new DateTime(START_YEAR) : new DateTime($from);  //START_YEAR=2013;
    $data['from'] = $thisTime->format("Y");
    $data['to'] = $tillTime->format("Y");
    if (isset($_REQUEST['select-box']) && !empty($_REQUEST['select-box'])) {
      $kontact_person_os = $_REQUEST['select-box'];
      $condition = "kontact_person_os=" . $kontact_person_os;
    }
    $extensionPercent = ($this->input->post('percentage_box')) ? $this->input->post('percentage_box') : $this->defaultPercent;
    $extension_type = ($this->input->post('extension_type')) ? $this->input->post('extension_type') : 'normal';
    $data['doLoadForm'] = $doLoadForm;
    $allContracts = $this->costYear->getAllContractsForFutureRevenue('', '', $condition, $status);
    $teliaInfoPrice = $this->costYear->getTeliaInfoPrices();
    $eduInfoPrice = $this->costYear->getEducationsPrices();
    $tableData = [];
    if (!empty($allContracts)) {
      foreach ($allContracts as $key => $contract) {
        $end_date = new DateTime($contract['end_date']);

        //$end_date->sub(new DateInterval('P1D'));

        $this->calculateOrderValue($contract, $teliaInfoPrice, $eduInfoPrice, $extension_type, $extensionPercent, (($from != '') ? $thisTime : false), $end_date->format("Y-m"), $tableData);
      }
    } else {
      echo "We don't find any records having selected criteria";
      die;
    }
    $maxDate = new DateTime($this->tillThisYear . "-12-31");
    $maxDate = new DateTime($this->tillThisYear . "-12-31");
    if ($to != '') {
      $maxDate = new DateTime($to);
    }
    while ($thisTime <= $maxDate) {
      $overallCost = $overAllRevenue = $extCost = $newCost = $extRevenue = $newRevenue = 0;
      if (isset($tableData[$thisTime->format("Y")])) {
        $newRevenue += (isset($tableData[$thisTime->format("Y")]['normal']['monthly_revenue'])) ? $tableData[$thisTime->format("Y")]
                ['normal']['monthly_revenue'] : 0;
        $extRevenue += (isset($tableData[$thisTime->format("Y")]['extended']['monthly_revenue'])) ? $tableData[$thisTime->format("Y")]['extended']['monthly_revenue'] : 0;
        $newCost += (isset($tableData[$thisTime->format("Y")]['normal']['cost'])) ? $tableData[$thisTime->format("Y")]['normal']['cost'] : 0;
        $extCost += (isset($tableData[$thisTime->format("Y")]['extended']['cost'])) ? $tableData[$thisTime->format("Y")]['extended']['cost'] : 0;
      }
      $output[$thisTime->format("Y")]['yearly_revenue'] = round($newRevenue + $extRevenue, 0);
      $output[$thisTime->format("Y")]['yearly_cost'] = round($newCost + $extCost, 0);
      $output[$thisTime->format("Y")]['yearly_ext_revenue'] = round($extRevenue, 0);
      $output[$thisTime->format("Y")]['yearly_new_revenue'] = round($newRevenue, 0);
      $output[$thisTime->format("Y")]['yearly_ext_cost'] = round($extCost, 0);
      $output[$thisTime->format("Y")]['yearly_new_cost'] = round($newCost, 0);

      $yearInterval = new DateInterval('P1Y');
      $thisTime->add($yearInterval);
    }
    $data['orderValueYearly'] = array_reverse($output, true);
    $data['is_extension_value'] = isset($_GET['extension_only']) ? $_GET['extension_only'] : 'no';
    if (!isset($_REQUEST['select-box'])) {
      $this->load->view('templates/header');
      $this->load->view('reports/reports/ajax/order_value_header', $data);
    }

    $this->load->view("reports/reports/order_value", $data);
    if (!isset($_REQUEST['select-box'])) {
      $this->load->view('reports/reports/ajax/order_value_footer', $data);
      $this->load->view('templates/footer');
    }
  }

  public function monthly_order_value() {
    $status = "all";
    if ($this->input->post("from_month") == '' && $this->input->post("to_month") == '') {
      $_GET['is_ajax'] = 1;
      $this->order_value(false);
    } else {
      $startDate = (isset($_POST['from_month']) && $_POST['from_month'] != '') ? $_POST['from_month'] : getTimeObj()->format("13-01-01");
      $toDate = (isset($_POST['to_month']) && $_POST['to_month'] != '') ? $_POST['to_month'] : getTimeObj()->format("Y-m-d");

      $tillTime = new DateTime($toDate);
      $thisTime = new DateTime($startDate);
      if ($tillTime->format("Y") - $thisTime->format("Y") > 2) {
        $_GET['is_ajax'] = 1;
        $this->order_value(false, $thisTime->format("Y-m-01"), $tillTime->format("Y-m-t"));
      } else {
        $condition = "";
        if (isset($_REQUEST['select-box']) && !empty($_REQUEST['select-box'])) {
          $kontact_person_os = $_REQUEST['select-box'];
          $condition = "kontact_person_os=" . $kontact_person_os;
        }
        $extensionPercent = ($this->input->post('percentage_box')) ? $this->input->post('percentage_box') : $this->defaultPercent;
        $extension_type = ($this->input->post('extension_type')) ? $this->input->post('extension_type') : 'normal';

        $allContracts = $this->costYear->getAllContractsForFutureRevenue('', '', $condition, $status);

        $teliaInfoPrice = $this->costYear->getTeliaInfoPrices();
        $eduInfoPrice = $this->costYear->getEducationsPrices();
        $tableData = [];
        if (!empty($allContracts)) {
          foreach ($allContracts as $key => $contract) {
            $end_date = new DateTime($contract['end_date']);
            //$end_date->sub(new DateInterval('P1D'));
            $this->calculateOrderValue($contract, $teliaInfoPrice, $eduInfoPrice, $extension_type, $extensionPercent, false, $end_date->format("Y-m"), $tableData, false);
          }
        } else {
          echo "We don't find any records having selected criteria";
          die;
        }

        while ($thisTime <= $tillTime) {
          $overallCost = $overAllRevenue = $extCost = $newCost = $extRevenue = $newRevenue = 0;
          if (isset($tableData[$thisTime->format("Yn")])) {
            $newRevenue += (isset($tableData[$thisTime->format("Yn")]['normal']['monthly_revenue'])) ? $tableData[$thisTime->format("Yn")]['normal']['monthly_revenue'] : 0;
            $extRevenue += (isset($tableData[$thisTime->format("Yn")]['extended']['monthly_revenue'])) ? $tableData[$thisTime->format("Yn")]['extended']['monthly_revenue'] : 0;
            $newCost += (isset($tableData[$thisTime->format("Yn")]['normal']['cost'])) ? $tableData[$thisTime->format("Yn")]['normal']['cost'] : 0;
            $extCost += (isset($tableData[$thisTime->format("Yn")]['extended']['cost'])) ? $tableData[$thisTime->format("Yn")]['extended']['cost'] : 0;
          }
          $output[$thisTime->format("Y-m-d")]['monthly_revenue'] = round($newRevenue + $extRevenue, 0);
          $output[$thisTime->format("Y-m-d")]['monthly_cost'] = round($newCost + $extCost, 0);
          $output[$thisTime->format("Y-m-d")]['monthly_ext_revenue'] = round($extRevenue, 0);
          $output[$thisTime->format("Y-m-d")]['monthly_new_revenue'] = round($newRevenue, 0);
          $output[$thisTime->format("Y-m-d")]['monthly_ext_cost'] = round($extCost, 0);
          $output[$thisTime->format("Y-m-d")]['monthly_new_cost'] = round($newCost, 0);

          $yearInterval = new DateInterval('P1M');
          $thisTime->add($yearInterval);
        }
        $data['start'] = $startDate;
        $data['to'] = $toDate;
        $data['orderValueMonthly'] = $output;
        $this->load->view("reports/reports/ajax/monthly_order_value", $data);
      }
    }
  }

	public function commission(){
		$data = [];
		$this->load->view('templates/header');
		$this->load->view('reports/reports/commission_dt',$data);
		$this->load->view('templates/footer');
	}

  private function dateIsInBetween($from, $to, $subject) {
    return $subject->getTimestamp() >= $from->getTimestamp() && $subject->getTimestamp() <= $to->getTimestamp() ? true : false;
  }
  public function ajax_list($doLoadForm = true, $from = '', $to = ''){
	$data = [];
	$condition = [];
	$status = "all";
	$isYearly=true;
	$tillTime = getTimeObj();
	$thisTime = new DateTime(START_YEAR); //START_YEAR=2013;
	$data['from'] = $thisTime->format("Y");
	$data['to'] = $tillTime->format("Y");
	$to_month = "";
	$from_month = "";
	if (isset($_POST['to_month'])) {
		$to_month = $_POST['to_month'];		
	}
	if (isset($_POST['from_month'])) {
		$from_month = $_POST['from_month'];		
	}
	
	if (isset($_POST['kontact_person_os']) && !empty($_POST['kontact_person_os'])) {
		$condition['having']['kontact_person_os'] = $_POST['kontact_person_os'];
	}
	
	if (isset($_POST['contract_number']) && !empty($_POST['contract_number'])) {
		$condition['where']['contract_number'] = $_POST['contract_number'];
	}	
	
	$account_type = get_account_type();
	$kontactPersonID=getKontactPersonID($this->session->userdata('userId'));

	if (($account_type == 'salj' && $kontactPersonID )){		
		$condition['where']['kontact_person_os'] = $kontactPersonID ;
	} 
	else if(($account_type == 'salj' && !$kontactPersonID)){		
		$condition['where']['kontact_person_os'] = 0 ;
	} 
	
	$extensionPercent = ($this->input->post('percentage_box')) ? $this->input->post('percentage_box') : $this->defaultPercent;
	$extension_type = ($this->input->post('extension_type')) ? $this->input->post('extension_type') : 'normal';
	$data['doLoadForm'] = $doLoadForm;
	$allContracts = $this->costYear->getAllContractsForCommission($to_month, $from_month, $condition, $status,$pagination=true);	
	$teliaInfoPrice = $this->costYear->getTeliaInfoPrices();
	$eduInfoPrice = $this->costYear->getEducationsPrices();
	$tableData = [];		
	$this->calculateCommission($allContracts, $teliaInfoPrice, $eduInfoPrice, $extension_type, $extensionPercent, $tillTime, $tableData,false);	
	$dtData=[];
	$count_all=0;
	//print_r($tableData);die;
	foreach($tableData as $key=>$contract){
		$totalCommission_all=0;
		foreach($contract as $k=>$year){
			
			$totalCommission_all+=$year['commission'];	
		} 
		$loopCount=0;
		$contract_id=$key;
		$per_year_commission_val=0;
		unset($row);
		foreach($contract as $key1=>$year){
				
			$signed_agreement=($year['signed_agreement']=="1")?"Ja":"Nej";
			$contractLength = (is_numeric($year['months'])) ? $year['months'] : 60;
			$is_editable=($loopCount==0)?"editable":""; 
			$edit_total_comm=($loopCount==0)?"editTotalCom":""; 
			$edit_per_year_comm=($loopCount==0)?"editYealyCom":""; 
			$start_day=$year['start_day'];
			$numberOfYears=$contractLength/12;					
			$avdelning=$year['avdelning_'];				
			$totalCommission=$year['commission'];	
			$commissionPerYear=$totalCommission;
			if($contractLength>12){
			//	$commissionPerYear=$totalCommission/$numberOfYears;
			}	

			$total_commission='<span class="'. $edit_total_comm.'" data-class="'.$start_day .'" data-type="text" data-name="total_commission"  data-pk="'. $contract_id.'" data-url="'.base_url('contracts/update_commission').'" data-title="Enter value"  data-original-title="" title="" aria-describedby="popover105748">';
			if($year['total_commission']<=0)
				$total_commission.=number_format(round($totalCommission_all),0,'.',' ')." ".CURRENCY_NAME;
			else 
				$total_commission.=$year['total_commission']." ".CURRENCY_NAME;			
			
			$row['total_commission']=$total_commission.="</span>";
			
			if(!empty($_POST['from_month']) && !empty($_POST['to_month'] )){
				$toMonth = new DateTime($_POST['to_month']);
				$fromMonth = new DateTime($_POST['from_month']);
				$DueDate=	new DateTime($year['comm_paid_date']);
				if(!$this->dateIsInBetween($fromMonth,$toMonth,$DueDate)){
					continue;
				}
				
			}
			if(isset($previousDue) && $previousDue!=$year['comm_paid_date']){
				$shouldChange=true;
			} 			
			$previousDue=$year['comm_paid_date'];
			
			if((isset($shouldChange) && $shouldChange) && isset($row['commission_per_year'])){
				
				$row['commission_per_year']=str_replace('{PER_YEAR_COMMISSION}',number_format($per_year_commission_val,0,'.',' ')." ".CURRENCY_NAME,$row['commission_per_year']);
				$row['commission_per_year_value']=$per_year_commission_val;//per_year_commission;
				$dtData[]=$row;
				$per_year_commission_val=0;
				$shouldChange=false;
					
			
			
			$count_all++;
			$loopCount++;
			}
			
			$row['id']= $key;	
			$row['from_month']= "";	
			$row['to_month']= "";	
			$row['custome_name_']=$year['custome_name_'];	
			$row['contract_number']=$year['contract_number'];			
			
			$oddMonths=""; 
			if(($year['months']%$year['payment_cicle'])!=0 && $year['months']>=12){
				$oddMonths=".".$year['months']%$year['payment_cicle']."  ";
			}	
			
			$payment_cicle=($year['payment_cicle']>=12)?" År":$year['payment_cicle']." Mån";
			if($year['months']>=12 && $year['payment_cicle']>=12 ){
				$months=floor(($year['months']/$year['payment_cicle'])).$oddMonths;
			}
			else if($year['months']>=12 && $year['payment_cicle']<12)
				$months=$year['months'];
			else if($year['months']<=12) 
				$months=$year['months']." Mån";			
			$row['total_years']=$months." / ".$payment_cicle;
			
			$per_year_commission='<span class="'. $edit_per_year_comm.'" data-class="'.$start_day .'" data-type="text" data-name="per_year_commission"  data-pk="'. $contract_id.'" data-url="'.base_url('contracts/update_yearly_commission').'" data-title="Enter value"  data-original-title="" title="" aria-describedby="popover105748">{PER_YEAR_COMMISSION}</span>';				
			if($year['per_year_commission']<=0){
				
				$per_year_commission_val+=$year['commission'];//0;//$totalCommission;
			}
			else {
				$per_year_commission_val=(float)trim($year['per_year_commission']);	
			}
			
			$row['commission_per_year']=$per_year_commission;			
			
						
			
			$row['signed_agreement']=$signed_agreement;
			
			
			$paid_date_html='<span class="'. $is_editable.'" data-class="'.$start_day .'" data-type="text" data-name="paid_date" data-pk="'. $contract_id.'" data-url="'.base_url('contracts/paid_date').'" data-title="Enter value" data-original-title="" title="" aria-describedby="popover105748">'; 
			
			$paid_date_html.=$year['comm_paid_date'];
			
					$paid_date_html.="</span>";
			$row['paid_date']=$paid_date_html;
			$row['os_kommentar']=$year['os_kommentar'];
		
		}
		unset($previousDue);
		
		if(isset($row['commission_per_year'])){
			$row['commission_per_year']=str_replace('{PER_YEAR_COMMISSION}',number_format($per_year_commission_val,0,'.',' ')." ".CURRENCY_NAME,$row['commission_per_year']);
			//$row['commission_per_year']=str_replace('{PER_YEAR_COMMISSION}',$per_year_commission_val." ".CURRENCY_NAME,$row['commission_per_year']);
			$row['commission_per_year_value']=$per_year_commission_val;//per_year_commission;
			$dtData[]=$row;
			
				
			
			
			$count_all++;
			$loopCount++;
		}
		
		$shouldChange=false;
		
		
	}
	//print_r($dtData);
	//echo $count_all; 
	$output = array(
		"draw" => $_POST['draw'],		
		"recordsTotal" => $this->costYear->count_filtered($to_month, $from_month, $condition, $status,$pagination=true),
		"recordsFiltered" => $this->costYear->count_filtered($to_month, $from_month, $condition, $status,$pagination=true),
		"data" => $dtData,
	);
	//output to json format
	echo json_encode($output); 	 
  }
  
  
    public function total_commission() {
		$commision="";
		$condition=[];
		$status = "all";
		$to_month = "";
		
		$from_month = "";
		$isYearly=true;
		if (isset($_POST['to_month'])) {
			$to_month = $_POST['to_month'];
			$isYearly=false;	 
		}
		if (isset($_POST['from_month'])) {
			 $from_month = $_POST['from_month'];
			 $isYearly=false;
		}
		if (isset($_POST['kontact_person_os']) && !empty($_POST['kontact_person_os'])) {
			$condition['having']['kontact_person_os'] = $_POST['kontact_person_os'];
		}
		
		if (isset($_POST['contract_number']) && !empty($_POST['contract_number'])) {
			$condition['where']['contract_number'] = $_POST['contract_number'];
		}	
		
		$account_type = get_account_type();
		$kontactPersonID=getKontactPersonID($this->session->userdata('userId'));

		if (($account_type == 'salj' && $kontactPersonID )){		
			$condition['where']['kontact_person_os'] = $kontactPersonID ;
		} 
		else if(($account_type == 'salj' && !$kontactPersonID)){		
			$condition['where']['kontact_person_os'] = 0 ;
		} 	
		
		if(isset($_POST['to_month']) && isset($_POST['from_month'])){
			$toMonth = new DateTime($_POST['to_month']);
			$fromMonth = new DateTime($_POST['from_month']);
			$inArray=array();
			while($toMonth>=$fromMonth)
			{
				$monthArr[]= $fromMonth->format("Yn");
				$monthInterval = new DateInterval('P1M');
				$fromMonth->add($monthInterval);
								
			}
			//print_r($inArray);
		}
		
		
		$tillTime = getTimeObj();
		$thisTime = new DateTime(START_YEAR); //START_YEAR=2013;
		$data['from'] = $thisTime->format("Y");
		$data['to'] = $tillTime->format("Y");
		
		$extensionPercent = ($this->input->post('percentage_box')) ? $this->input->post('percentage_box') : $this->defaultPercent;
		//$data['doLoadForm'] = $doLoadForm;
		$extension_type = ($this->input->post('extension_type')) ? $this->input->post('extension_type') : 'normal';
		$allContracts = $this->costYear->getAllContractsForCommission($to_month, $from_month, $condition, $status,$pagination=false);
		$teliaInfoPrice = $this->costYear->getTeliaInfoPrices();
		$eduInfoPrice = $this->costYear->getEducationsPrices();
		$tableData = [];		
		
		$this->calculateCommission($allContracts, $teliaInfoPrice, $eduInfoPrice, $extension_type, $extensionPercent, $tillTime, $tableData,false);
		//print_r($tableData);
		
		$curDate =getTimeObj();
		
			
		$currentYearCommission=0;
		$nextYearCommission=0;
		$currentMonthCommission=0;
		//print_r($monthArr);
		  
		//print_r($tableData);
		//echo $curDate->format("Y");
		foreach($tableData as $key=>$contract){
			
			 
				$shouldIncludeCurrent=true;
				$shouldIncludeNext=true;
				$shouldIncludeCurrentMonth=true;
				foreach($contract as $year){
				//for($i=1;$i<=12;$i++){
					
					
					$paidDateObj= new DateTime($year['comm_paid_date']);
					if(isset($year['commission']) && $paidDateObj->format("Y")==$curDate->format("Y")){
						if($year['per_year_commission']<=0){
							$currentYearCommission+=$year['commission'];
						}
						else {
							if($shouldIncludeCurrent){
								$currentYearCommission+=$year['per_year_commission'];
								$shouldIncludeCurrent=false;
							}								
						}
						//$currentYearCommission+=$year['commission'];
					}
					if(isset($year['commission']) && $paidDateObj->format("Y")==($curDate->format("Y")+1)){
						if($year['per_year_commission']<=0){
							$nextYearCommission+=$year['commission'];
						}
						else {
							if($shouldIncludeNext){
								$nextYearCommission+=$year['per_year_commission'];
								$shouldIncludeNext=false;							
							}
						}
						
					}
					if($paidDateObj->format("Yn")==($curDate->format("Yn"))){
						
						if($year['per_year_commission']<=0){
							$currentMonthCommission+=$year['commission'];
						}
						else {
							if($shouldIncludeCurrentMonth){
								$currentMonthCommission+=$year['per_year_commission'];	
								$shouldIncludeCurrentMonth=false;
							}
						}
					}
					
				}
		} 
		
		$total_commission=array(
		'currentMonthTB'=>number_format(round(($currentMonthCommission)),0,'.',' ')." ".CURRENCY_NAME,
		'currentYearTB'=>number_format(round(($currentYearCommission)),0,'.',' ')." ".CURRENCY_NAME,
		'nextYearTB'=>number_format(round(($nextYearCommission)),0,'.',' ')." ".CURRENCY_NAME
		);				
       echo json_encode($total_commission);
	  // print_r($tableData);
    }
  
  public function contract_stats() {
    $data = [];
    $this->load->view('templates/header');
    $this->load->view('reports/reports/contract_stats', $data);
    $this->load->view('templates/footer');
  }

  public function contract_stats_total() {
    $data = [];
    $startDay = $this->input->post("from_month");
    $endDay = $this->input->post("to_month");
    $where_condition = " is_extension_agreement='yes' ";
    $extended = $this->costYear->getTotalAlarms($endDay, $startDay, $condition = "", $status = 'all', $where_condition);
    $data['extended'] = $this->generateYearlyForContractStats($extended);
    $data['canvasId'] = 'contract_stats_total';
    $where_condition = "(is_extension_agreement!='yes') ";
    $new = $this->costYear->getTotalAlarms($endDay, $startDay, $condition = "", $status = 'all', $where_condition);
    $data['new'] = $this->generateYearlyForContractStats($new);
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $this->load->view('reports/reports/ajax/contract_stats_total', $data);
  }

  public function contract_stats_yearly() {
    $data = [];
    $startDay = ($this->input->post("from_month") != '') ? $this->input->post("from_month") : getTimeObj()->format("Y-01-01");
    $endDay = ($this->input->post("to_month") != '') ? $this->input->post("to_month") : getTimeObj()->format("Y-12-31");

    $where_condition = " is_extension_agreement='yes' ";
    $extended = $this->costYear->getTotalContracts($endDay, $startDay, $condition = "", $status = 'all', $where_condition);
    $data['extended'] = $this->generateYearlyForContractStats($extended);
    $data['canvasId'] = 'contract_stats_yearly';
    $where_condition = "(is_extension_agreement!='yes') ";
    $new = $this->costYear->getTotalContracts($endDay, $startDay, $condition = "", $status = 'all', $where_condition);
    $data['new'] = $this->generateYearlyForContractStats($new);
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = getTimeObj()->format("Y");
    $this->load->view('reports/reports/ajax/contract_stats_total', $data);
  }

  public function contract_stats_monthly() {
    $data = [];
    $startDay = ($this->input->post("from_month") != '') ? $this->input->post("from_month") : getTimeObj()->format("Y-m-01");

    $endDay = ($this->input->post("to_month") != '') ? $this->input->post("to_month") : getTimeObj()->format("Y-m-t");
    $where_condition = " is_extension_agreement='yes' ";
    $extended = $this->costYear->getTotalContracts($endDay, $startDay, $condition = "", $status = 'all', $where_condition);
    $data['extended'] = $this->generateYearlyForContractStats($extended);
    $data['canvasId'] = 'contract_stats_monthly';
    $where_condition = "(is_extension_agreement!='yes') ";
    $new = $this->costYear->getTotalContracts($endDay, $startDay, $condition = "", $status = 'all', $where_condition);

    $data['new'] = $this->generateYearlyForContractStats($new);
    $data['search_ajax'] = $this->input->post("search_ajax");
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = getTimeObj()->format("Y");
    $this->load->view('reports/reports/ajax/contract_stats_total', $data);
  }

  public function product_stats() {
    $data = array();
    $data['is_extension_value'] = "";
    $this->load->view('templates/header');
    $this->load->view('reports/reports/model_costs', $data);
    $this->load->view('templates/footer');
  }

  public function total_model_cost() {
    $data = array();
    $startDay = $this->input->post("from_month");
    $endDay = $this->input->post("to_month");
    $output = array();
    $group_by = 'dropdown.id';
    $data = $this->costYear->getProductStats($endDay, $startDay, $thisDate = null, $group_by);
    $data['models'] = $this->generateYearlyForModels($data);

    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['models'] = $this->sortDesc($data['models']);
    $data['canvasId'] = 'total_model_cost';

    $this->load->view('reports/reports/ajax/total_model_cost', $data);
  }

  public function year_model_cost() {
    $data = array();
    $thisTime = getTimeObj();
    $tillTime = getTimeObj();
    $output = array();
    $to = $thisTime->format("Y") . "-12-31";
    $from = $thisTime->format("Y") . "-1-1";
    $group_by = 'dropdown.id';
    $data = $this->costYear->getProductStats($to, $from, $thisTime, $group_by);
    $data['models'] = $this->generateYearlyForModels($data);
    $data['models'] = $this->sortDesc($data['models']);
    $tillCostTotal = $this->costYear->getProductStats();
    $data['tillModelsCost'] = $this->generateYearlyForModels($tillCostTotal);
    $data['tillModelsCost'] = $this->sortDesc($data['tillModelsCost']);

    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['canvasId'] = 'year_model_cost';
    $this->load->view('reports/reports/ajax/yearly_model_cost', $data);
  }

  public function monthly_model_cost() {
    $data = array();
    $thisTime = getTimeObj();
    $tillTime = getTimeObj();
    $output = array();
    $from = $thisTime->format("Y-m") . "-01";
    $to = $thisTime->format("Y-m-") . date('t');
    $group_by = 'dropdown.id';
    $data = $this->costYear->getProductStats($to, $from, $thisTime, $group_by);
    $data['models'] = $this->generateYearlyForModels($data);
    $data['models'] = $this->sortDesc($data['models']);

    $tillCostTotal = $this->costYear->getProductStats();
    $data['tillModelsCost'] = $this->generateYearlyForModels($tillCostTotal);
    $data['tillModelsCost'] = $this->sortDesc($data['tillModelsCost']);
    $data['to'] = getTimeObj()->format("Y");
    $data['from'] = 2013;
    $data['canvasId'] = 'monthly_model_cost';
    $this->load->view('reports/reports/ajax/yearly_model_cost', $data);
  }

  /** Helper functions * */
  private function generateYearlyForModels($modelInfo) {
    $output = [];
    if ($modelInfo) {
      foreach ($modelInfo as $key => $val) {
        $start_day = new DateTime($val['added_date']);
        $curDate = new DateTime();
        $output[$val['id']]['lcs'][$start_day->format("Y")] = $val['model_total'];
        $output[$val['id']]['name'] = $val['title'];
        if ($start_day <= $curDate) {
          if (isset($output[$val['id']]['overall_total']))
            $output[$val['id']]['overall_total'] += $val['model_total'];
          else
            $output[$val['id']]['overall_total'] = $val['model_total'];
        }
      }
    }
    return $output;
  }

  private function generateYearlyFor($lcInfo) {
    $output = [];
    if (is_array($lcInfo)) {
      foreach ($lcInfo as $key => $lc) {
        $output[$lc['lcID']]['lcs'][$lc['year']] = $lc['TotalBYLC'];
        $output[$lc['lcID']]['overall_total'] = (isset($output[$lc['lcID']]['overall_total']) ? $output[$lc['lcID']]['overall_total'] : 0) + $lc['TotalBYLC'];
        $output[$lc['lcID']]['name'] = $lc['lc_name'];
        $output[$lc['lcID']]['id'] = $lc['lcID'];
      }
    }
    return $output;
  }

  private function generateYearlyForContract($getProductStats) {
    $total = 0;
    foreach ($getProductStats as $key => $row) {
      $added_date = new DateTime($row['added_date']);
      $data[$added_date->format('Y')] = $row['model_total'];
      $total += $row['model_total'];
    }
    $data['overall_total'] = $total;
    return $data;
  }

  private function generateYearlyForContractKontant($data) {
    $output = ['overall_total' => 0];
    if (is_array($data)) {
      foreach ($data as $key => $row) {
        $output['overall_total'] = (isset($output['overall_total']) ? $output['overall_total'] : 0) + $row['totalPeryear'];
        $output[$row['year']] = $row['totalPeryear'];
      }
    }
    return $output;
  }

  private function generateLenghtWise($data) {
    $output = [];
    if (is_array($data)) {
      foreach ($data as $key => $row) {
        $row['year'] = ($row['year'] != '') ? $row['year'] : 'NONE';
        $months = "no string";
        if ($row['months'] != "-1") {
          if (strpos($row['months'], "-r") !== false) {
            $months = (int) filter_var($row['months'], FILTER_SANITIZE_NUMBER_INT);
            $months = $months * 12;
          } else if (strpos($row['months'], "-mn") !== false) {
            $months = (int) filter_var($row['months'], FILTER_SANITIZE_NUMBER_INT);
          }
        }
        $contractLength = (is_numeric($months)) ? $months : 60;
        $actualLength = round($contractLength / 12);
        //$keyText = ($actualLength >= 1) ? ($actualLength . ' år') : 'Under 1 år';
        $keyText = ($actualLength >= 1) ? ($actualLength ) : 0;
        $output[$keyText]['overall_total_length'] = (isset($output[$keyText]['overall_total_length']) ? $output[$keyText]['overall_total_length'] : 0) + ($row['count'] * $actualLength);
        $output[$keyText]['overall_total'] = (isset($output[$keyText]['overall_total']) ? $output[$keyText]['overall_total'] : 0) + $row['count'];
        $output[$keyText][$row['year']]['cnt'] = (isset($output[$keyText][$row['year']]['cnt']) ? $output[$keyText][$row['year']]['cnt'] : 0) + $row['count'];
        $output[$keyText][$row['year']]['length'] = (isset($output[$keyText][$row['year']]['length']) ? $output[$keyText][$row['year']]['length'] : 0) + ($row['count'] * $actualLength);
        //$output['total_length'][$row['year']] = (isset($output['total_length'][$row['year']]) ? $output['total_length'][$row['year']] : 0) + ($row['count']*$actualLength);
      }
    }
    return $output;
  }

  private function calculateFutureRevenue($contract, $teliaInfoPrice, $eduInfoPrice, $extension_type = 'automatic', $extensionPercent = 23, $fromYearMonth = false, $toYearMonth, &$finalOutput, $isYearly = true) {
    //$extensionPercent = 23;
    $startDay = new DateTime($contract['start_day']);
    if ($startDay->format("Y") < 2013) {
      return;
    }
    //print_r($contract);
    //$lcYearWisePrice=$this->getLcPriceInfo($contract['lc_prcing']);
    // Generate model pricing per year.
    $modelYearWisePrice = $this->getModelLCPriceInfo($contract['model_pricing']);
    $lcYearWisePrice = $this->getModelLCPriceInfo($contract['lc_pricing']);

    //print_r($modelYearWisePrice);
    $months = trim(str_replace(["-mn","-mnader","-mnad"], "", $contract['months']));
    $contractLength = (is_numeric($months)) ? $months : 60;

    $contractLengthIntial = $contractLength;

    $contractLengthInterval = new DateInterval('P' . $contractLength . 'M');

    $startDay = new DateTime($contract['start_day']);
    $originalContractStartDay = new DateTime($contract['start_day']);
    $untillDay = new DateTime(DateTime::createFromFormat("Y-m", $toYearMonth)->format("Y-m-t"));

    $oldYear = '';
    $extendedTimes = 0;
    while ($untillDay >= $startDay) {
      $curMonthyearKey = $startDay->format("Yn");


      $data = $this->calculateAllParmsForContract($lcYearWisePrice, $modelYearWisePrice, $teliaInfoPrice, $eduInfoPrice, $curMonthyearKey, $contract, $oldYear, $contractLengthInterval, $contractLength, $contractLengthIntial, $extendedTimes, $startDay, $originalContractStartDay, $extension_type);

      $data[$curMonthyearKey][$contract['id']]['commissionable_sum'] = ($data[$curMonthyearKey][$contract['id']]['model_revenue'] + $data[$curMonthyearKey][$contract['id']]['lc_revenue'] + $data[$curMonthyearKey][$contract['id']]['sim_kart_value'] + $data[$curMonthyearKey][$contract['id']]['education_value'] + $data[$curMonthyearKey][$contract['id']]['other_fixed_costs']);

      /** Code for categorize cost * */
      $output[$curMonthyearKey][$contract['id']]['cateogry_cost']['sim_cost'] = $data[$curMonthyearKey][$contract['id']]['sim_kart_value'];
      $output[$curMonthyearKey][$contract['id']]['cateogry_cost']['lc_cost'] = $data[$curMonthyearKey][$contract['id']]['lc_revenue'];
      
      /** END * */
      //Commission
      $data[$curMonthyearKey][$contract['id']]['commission'] = 0;
      if ($contract['is_extension_agreement'] == 1 && $extension_type == 'automatic') {
        $contract['procent_provision'] = 5;
      }
      $data[$curMonthyearKey][$contract['id']]['commission'] = ($data[$curMonthyearKey][$contract['id']]['monthly_revenue'] - $data[$curMonthyearKey][$contract['id']]['commissionable_sum']) * ($contract['procent_provision'] / 100);


      /** keep only required things * */
      $output[$curMonthyearKey][$contract['id']]['commission'] = $data[$curMonthyearKey][$contract['id']]['commission'];
      $output[$curMonthyearKey][$contract['id']]['monthly_revenue'] = $data[$curMonthyearKey][$contract['id']]['monthly_revenue'];
      $output[$curMonthyearKey][$contract['id']]['cost'] = $output[$curMonthyearKey][$contract['id']]['commission'] + $data[$curMonthyearKey][$contract['id']]['commissionable_sum'];
	  $output[$curMonthyearKey][$contract['id']]['cateogry_cost']['others'] = ($data[$curMonthyearKey][$contract['id']]['model_revenue'] + $data[$curMonthyearKey][$contract['id']]['education_value'] + $data[$curMonthyearKey][$contract['id']]['other_fixed_costs']) + $output[$curMonthyearKey][$contract['id']]['commission'];

      if ($contract['is_extension_agreement'] == 1 && $contract['is_system_extend'] == 0) {
        $output[$curMonthyearKey][$contract['id']]['monthly_revenue'] = ($output[$curMonthyearKey][$contract['id']]['monthly_revenue'] * $extensionPercent / 100);
        $output[$curMonthyearKey][$contract['id']]['cost'] = ($output[$curMonthyearKey][$contract['id']]['cost'] * $extensionPercent / 100);
		 /** Code for categorize cost * */
		$output[$curMonthyearKey][$contract['id']]['cateogry_cost']['sim_cost'] = ($output[$curMonthyearKey][$contract['id']]['cateogry_cost']['sim_cost']* $extensionPercent / 100);
		$output[$curMonthyearKey][$contract['id']]['cateogry_cost']['lc_cost'] = ($output[$curMonthyearKey][$contract['id']]['cateogry_cost']['lc_cost']* $extensionPercent / 100);
		$output[$curMonthyearKey][$contract['id']]['cateogry_cost']['others'] = ($output[$curMonthyearKey][$contract['id']]['cateogry_cost']['others']* $extensionPercent / 100);
		
        for ($cnt = 1; $cnt < $extendedTimes; $cnt++) {
          $output[$curMonthyearKey][$contract['id']]['monthly_revenue'] = ($output[$curMonthyearKey][$contract['id']]['monthly_revenue'] * $extensionPercent / 100);
          $output[$curMonthyearKey][$contract['id']]['cost'] = ($output[$curMonthyearKey][$contract['id']]['cost'] * $extensionPercent / 100);
		   /** Code for categorize cost * */
		  $output[$curMonthyearKey][$contract['id']]['cateogry_cost']['sim_cost'] = ($output[$curMonthyearKey][$contract['id']]['cateogry_cost']['sim_cost']* $extensionPercent / 100);
		  $output[$curMonthyearKey][$contract['id']]['cateogry_cost']['lc_cost'] = ($output[$curMonthyearKey][$contract['id']]['cateogry_cost']['lc_cost']* $extensionPercent / 100);
		  $output[$curMonthyearKey][$contract['id']]['cateogry_cost']['others'] = ($output[$curMonthyearKey][$contract['id']]['cateogry_cost']['others']* $extensionPercent / 100);
        }
      }
      $output[$curMonthyearKey][$contract['id']]['is_extension'] = $contract['is_extension_agreement'];
      $hasExtensionText = ($contract['is_extension_agreement'] == 1) ? 'extended' : 'normal';

      $finalKey = ($isYearly) ? $startDay->format("Y") : $startDay->format("Yn");


      /** Fix for issue while filtering in years * */
      if ($fromYearMonth == false || $startDay >= $fromYearMonth) {
        $finalOutput[$finalKey][$hasExtensionText]['commission'] = isset($finalOutput[$finalKey][$hasExtensionText]['commission']) ? $finalOutput[$finalKey][$hasExtensionText]['commission'] : 0;
        $finalOutput[$finalKey][$hasExtensionText]['monthly_revenue'] = isset($finalOutput[$finalKey][$hasExtensionText]['monthly_revenue']) ? $finalOutput[$finalKey][$hasExtensionText]['monthly_revenue'] : 0;
        $finalOutput[$finalKey][$hasExtensionText]['cost'] = isset($finalOutput[$finalKey][$hasExtensionText]['cost']) ? $finalOutput[$finalKey][$hasExtensionText]['cost'] : 0;

        $finalOutput[$finalKey][$hasExtensionText]['commission'] += $output[$curMonthyearKey][$contract['id']]['commission'];
        $finalOutput[$finalKey][$hasExtensionText]['monthly_revenue'] += $output[$curMonthyearKey][$contract['id']]['monthly_revenue'];
        $finalOutput[$finalKey][$hasExtensionText]['cost'] += $output[$curMonthyearKey][$contract['id']]['cost'];

        /** Code to categorise cost * */
        $finalOutput[$finalKey][$hasExtensionText]['cateogry_cost']['sim_cost'] = (isset($finalOutput[$finalKey][$hasExtensionText]['cateogry_cost']['sim_cost']) ? $finalOutput[$finalKey][$hasExtensionText]['cateogry_cost']['sim_cost'] : 0) + $output[$curMonthyearKey][$contract['id']]['cateogry_cost']['sim_cost'];

        $finalOutput[$finalKey][$hasExtensionText]['cateogry_cost']['lc_cost'] = (isset($finalOutput[$finalKey][$hasExtensionText]['cateogry_cost']['lc_cost']) ? $finalOutput[$finalKey][$hasExtensionText]['cateogry_cost']['lc_cost'] : 0) + $output[$curMonthyearKey][$contract['id']]['cateogry_cost']['lc_cost'];

        $finalOutput[$finalKey][$hasExtensionText]['cateogry_cost']['others'] = (isset($finalOutput[$finalKey][$hasExtensionText]['cateogry_cost']['others']) ? $finalOutput[$finalKey][$hasExtensionText]['cateogry_cost']['others'] : 0) + $output[$curMonthyearKey][$contract['id']]['cateogry_cost']['others'];
        /** END * */
      }

      unset($output);
      unset($data);
      $monthInterval = new DateInterval('P1M');
      $startDay->add($monthInterval);
    }
  }

  private function calculateOrderValue($contract, $teliaInfoPrice, $eduInfoPrice, $extension_type = 'automatic', $extensionPercent = 23, $fromYearMonth = false, $toYearMonth, &$finalOutput, $isYearly = true) {
    //$extensionPercent = 23;
    $startDay = new DateTime($contract['start_day']);
    if ($startDay->format("Y") < 2013) {
      return;
    }
    //print_r($contract);
    //$lcYearWisePrice=$this->getLcPriceInfo($contract['lc_prcing']);
    // Generate model pricing per year.
    $modelYearWisePrice = $this->getModelLCPriceInfo($contract['model_pricing']);
    $lcYearWisePrice = $this->getModelLCPriceInfo($contract['lc_pricing']);

    //print_r($modelYearWisePrice);
    $months = trim(str_replace(["-mn","-mnader","-mnad"], "", $contract['months']));
    $contractLength = (is_numeric($months)) ? $months : 60;

    $contractLengthIntial = $contractLength;

    $contractLengthInterval = new DateInterval('P' . $contractLength . 'M');

    $startDay = new DateTime($contract['start_day']);
    $originalContractStartDay = new DateTime($contract['start_day']);
    $untillDay = new DateTime(DateTime::createFromFormat("Y-m", $toYearMonth)->format("Y-m-t"));

    if ($startDay->format("Y") > $this->tillThisYear) {
      $this->tillThisYear = $startDay->format("Y");
    }

    $oldYear = '';
    $extendedTimes = 0;
    while ($untillDay >= $startDay) {
      $curMonthyearKey = $startDay->format("Yn");
      $data = $this->calculateAllParmsForContract($lcYearWisePrice, $modelYearWisePrice, $teliaInfoPrice, $eduInfoPrice, $curMonthyearKey, $contract, $oldYear, $contractLengthInterval, $contractLength, $contractLengthIntial, $extendedTimes, $startDay, $originalContractStartDay, $extension_type);

      $data[$curMonthyearKey][$contract['id']]['commissionable_sum'] = ($data[$curMonthyearKey][$contract['id']]['model_revenue'] + $data[$curMonthyearKey][$contract['id']]['lc_revenue'] + $data[$curMonthyearKey][$contract['id']]['sim_kart_value'] + $data[$curMonthyearKey][$contract['id']]['education_value'] + $data[$curMonthyearKey][$contract['id']]['other_fixed_costs']);

      //Commission
      $data[$curMonthyearKey][$contract['id']]['commission'] = 0;
      if ($contract['is_extension_agreement'] == 1 && $extension_type == 'automatic') {
        // $contract['procent_provision'] = 5;
      }
      $data[$curMonthyearKey][$contract['id']]['commission'] = ($data[$curMonthyearKey][$contract['id']]['monthly_revenue'] - $data[$curMonthyearKey][$contract['id']]['commissionable_sum']) * ($contract['procent_provision'] / 100);


      /** keep only required things * */
      $output[$curMonthyearKey][$contract['id']]['commission'] = $data[$curMonthyearKey][$contract['id']]['commission'];
      $output[$curMonthyearKey][$contract['id']]['monthly_revenue'] = $data[$curMonthyearKey][$contract['id']]['monthly_revenue'];
      $output[$curMonthyearKey][$contract['id']]['cost'] = $output[$curMonthyearKey][$contract['id']]['commission'] + $data[$curMonthyearKey][$contract['id']]['commissionable_sum'];
      if ($contract['is_extension_agreement'] == 1 && $contract['is_system_extend'] == 0) {
        $output[$curMonthyearKey][$contract['id']]['monthly_revenue'] = ($output[$curMonthyearKey][$contract['id']]['monthly_revenue'] * $extensionPercent / 100);
        $output[$curMonthyearKey][$contract['id']]['cost'] = ($output[$curMonthyearKey][$contract['id']]['cost'] * $extensionPercent / 100);

        for ($cnt = 1; $cnt < $extendedTimes; $cnt++) {
          $output[$curMonthyearKey][$contract['id']]['monthly_revenue'] = ($output[$curMonthyearKey][$contract['id']]['monthly_revenue'] * $extensionPercent / 100);
          $output[$curMonthyearKey][$contract['id']]['cost'] = ($output[$curMonthyearKey][$contract['id']]['cost'] * $extensionPercent / 100);
        }
      }
      $output[$curMonthyearKey][$contract['id']]['is_extension'] = $contract['is_extension_agreement'];
      $hasExtensionText = ($contract['is_extension_agreement'] == 1) ? 'extended' : 'normal';
      //$originalContractStartDay;
      //$finalKey = ($isYearly) ? $startDay->format("Y") : $startDay->format("Yn");
      $finalKey = ($isYearly) ? $originalContractStartDay->format("Y") : $originalContractStartDay->format("Yn");
      /** Fix for issue while filtering in years * */
      if ($fromYearMonth == false || $startDay >= $fromYearMonth) {
        $finalOutput[$finalKey][$hasExtensionText]['commission'] = isset($finalOutput[$finalKey][$hasExtensionText]['commission']) ? $finalOutput[$finalKey][$hasExtensionText]['commission'] : 0;
        $finalOutput[$finalKey][$hasExtensionText]['monthly_revenue'] = isset($finalOutput[$finalKey][$hasExtensionText]['monthly_revenue']) ? $finalOutput[$finalKey][$hasExtensionText]['monthly_revenue'] : 0;
        $finalOutput[$finalKey][$hasExtensionText]['cost'] = isset($finalOutput[$finalKey][$hasExtensionText]['cost']) ? $finalOutput[$finalKey][$hasExtensionText]['cost'] : 0;

        $finalOutput[$finalKey][$hasExtensionText]['commission'] += $output[$curMonthyearKey][$contract['id']]['commission'];
        $finalOutput[$finalKey][$hasExtensionText]['monthly_revenue'] += $output[$curMonthyearKey][$contract['id']]['monthly_revenue'];
        $finalOutput[$finalKey][$hasExtensionText]['cost'] += $output[$curMonthyearKey][$contract['id']]['cost'];
      }
      //print_r($lcYearWisePrice);
      //$finalOutput[$finalKey][$hasExtensionText]['data'][]=$output;
      // print_r($data);
      unset($output);
      unset($data);
      $monthInterval = new DateInterval('P1M');
      $startDay->add($monthInterval);
    }
  }

  private function calculateAllParmsForContract($lcYearWisePrice, $modelYearWisePrice, $teliaInfoPrice, $eduInfoPrice, $curMonthyearKey, &$contract, &$oldYear, &$contractLengthInterval, &$contractLength, $contractLengthIntial, &$extendedTimes, $startDay, $originalContractStartDay, $extension_type) {


    $contractStartEndDay = new DateTime($contract['start_day']);
    $contractStartEndDay->add($contractLengthInterval);
    $contract['now_status']=1;
    if ($startDay >= $contractStartEndDay) {
      if($contract['status']<1){

      $contract['now_status']=0;
      }

      $contract['start_day'] = $startDay->format("Y-m-d");
      if ($extension_type == "automatic") {
        $contractLength = 24;
        $contractLengthInterval = new DateInterval('P' . $contractLength . 'M');
      }
      $contract['is_extension_agreement'] = 1;
      $contract['is_system_extend'] = 0;
      $extendedTimes++;
    }

    $contractStart = new DateTime($contract['start_day']);

    //Model price Calculation.
    $ModelToltal = 0;
    $models = explode(",", $contract['models']);
    if (is_array($models)) {
      foreach ($models as $model) {
        $modelIdQty = explode("=", $model);
        if (isset($modelYearWisePrice[$contractStart->format("Y")][$modelIdQty[0]])) {
          $ModelToltal += (($modelYearWisePrice[$contractStart->format("Y")][$modelIdQty[0]] / 48) * $modelIdQty[1]);
        }
      }
    }
  $data[$curMonthyearKey][$contract['id']]['model_revenue']=$ModelToltal;
    $data[$curMonthyearKey][$contract['id']]['model_revenue'] = ($contract['now_status']<1)?0:$data[$curMonthyearKey][$contract['id']]['model_revenue'];

    //LC price Calculation
    $LCToltal = 0;
    if (isset($contract['lcs'])) {
      $lcs = explode(",", $contract['lcs']);
      if (is_array($lcs)) {
        foreach ($lcs as $lc) {
          $lcIdQty = explode("=", $lc);
          if (isset($lcYearWisePrice[$startDay->format("Y")][$lcIdQty[0]])) {
            $LCToltal += (($lcYearWisePrice[$startDay->format("Y")][$lcIdQty[0]] / 12) * $lcIdQty[1]);
          }
        }
      }
    }
  $data[$curMonthyearKey][$contract['id']]['lc_revenue'] = $LCToltal;

    $data[$curMonthyearKey][$contract['id']]['lc_revenue'] = ($contract['now_status']<1)?0:$data[$curMonthyearKey][$contract['id']]['lc_revenue'] ;

    // print_r($eduInfoPrice);

    //Education Price Calculations
    $data[$curMonthyearKey][$contract['id']]['education_value'] = 0;


    if ($contract['number_of_participants'] > 0) {
      if ($extension_type == "automatic") {
        $data[$curMonthyearKey][$contract['id']]['education_value'] += (isset($eduInfoPrice['utbildningar_report_number_of_participants'][$originalContractStartDay->format("Y")]) ? ($eduInfoPrice['utbildningar_report_number_of_participants'][$originalContractStartDay->format("Y")] / 12) : 0) * $contract['number_of_participants'];
      } else {
        $data[$curMonthyearKey][$contract['id']]['education_value'] += (isset($eduInfoPrice['utbildningar_report_number_of_participants'][$contractStart->format("Y")]) ? ($eduInfoPrice['utbildningar_report_number_of_participants'][$contractStart->format("Y")] / 12) : 0) * $contract['number_of_participants'];
      }
    }
  


    if ($contract['number_of_occasions'] > 0) {
      if ($extension_type == "automatic") {
        $data[$curMonthyearKey][$contract['id']]['education_value'] += (isset($eduInfoPrice['utbildningar_report_number_of_occasions'][$originalContractStartDay->format("Y")]) ? ($eduInfoPrice['utbildningar_report_number_of_occasions'][$originalContractStartDay->format("Y")] / 12) : 0) * $contract['number_of_occasions'];
      } else {
        $data[$curMonthyearKey][$contract['id']]['education_value'] += (isset($eduInfoPrice['utbildningar_report_number_of_occasions'][$contractStart->format("Y")]) ? ($eduInfoPrice['utbildningar_report_number_of_occasions'][$contractStart->format("Y")] / 12) : 0) * $contract['number_of_occasions'];
      }
    }
 
    if ($data[$curMonthyearKey][$contract['id']]['education_value'] > 0) {
      $data[$curMonthyearKey][$contract['id']]['education_value'] = $data[$curMonthyearKey][$contract['id']]['education_value'] / ($contractLengthIntial / 12);
    }

  $data[$curMonthyearKey][$contract['id']]['education_value']= ($contract['now_status']<1)?0:$data[$curMonthyearKey][$contract['id']]['education_value'];

    // SimKart
    $data[$curMonthyearKey][$contract['id']]['sim_kart_value'] = 0;
    if ($contract['telia'] > 0) {
      $data[$curMonthyearKey][$contract['id']]['sim_kart_value'] += (isset($teliaInfoPrice['sim_rep_telia'][$startDay->format("Y")]) ? ($teliaInfoPrice['sim_rep_telia'][$startDay->format("Y")] / 12) : 0) * $contract['telia'];
    }

    if ($contract['telenor'] > 0) {
      $data[$curMonthyearKey][$contract['id']]['sim_kart_value'] += (isset($teliaInfoPrice['sim_rep_telenor'][$startDay->format("Y")]) ? ($teliaInfoPrice['sim_rep_telenor'][$startDay->format("Y")] / 12) : 0) * $contract['telenor'];
    }

    if ($contract['telia2'] > 0) {
      $data[$curMonthyearKey][$contract['id']]['sim_kart_value'] += (isset($teliaInfoPrice['sim_rep_tele2'][$startDay->format("Y")]) ? ($teliaInfoPrice['sim_rep_tele2'][$startDay->format("Y")] / 12) : 0) * $contract['telia2'];
    }
    if ($contract['link'] > 0) {
      $data[$curMonthyearKey][$contract['id']]['sim_kart_value'] += (isset($teliaInfoPrice['sim_rep_link'][$startDay->format("Y")]) ? ($teliaInfoPrice['sim_rep_link'][$startDay->format("Y")] / 12) : 0) * $contract['link'];
    }

  $data[$curMonthyearKey][$contract['id']]['sim_kart_value']=($contract['now_status']<1)?0:$data[$curMonthyearKey][$contract['id']]['sim_kart_value'];

    // Other Fixed Costs 
    $data[$curMonthyearKey][$contract['id']]['other_fixed_costs'] = 0;
    if ($contract['is_extension_agreement'] != 1) {
      if ($contract['has_customers_access_price'] > 0) {
        $data[$curMonthyearKey][$contract['id']]['other_fixed_costs'] += ($contract['has_customers_access_price'] / ($contractLength));
      }
      if ($contract['other_fixed_cost'] > 0) {
        $data[$curMonthyearKey][$contract['id']]['other_fixed_costs'] += ($contract['other_fixed_cost'] / $contractLength);
      }
    }
    if ($contract['other_variable_cost'] > 0) {
      $data[$curMonthyearKey][$contract['id']]['other_fixed_costs'] += $contract['other_variable_cost'];
    }

    $data[$curMonthyearKey][$contract['id']]['other_fixed_costs']=($contract['now_status']<1)?0:$data[$curMonthyearKey][$contract['id']]['other_fixed_costs'];
    if ($oldYear != $startDay->format("Y")) {
      if ($oldYear != '') {
        if ($contract['revenue_list_percent'] < 1) {
          $contract['revenue_list_percent'] = 1;
        }
        $contract['rent_per_month'] = $contract['rent_per_month'] + (trim($contract['rent_per_month']) * $contract['revenue_list_percent'] / 100);
      }
      $oldYear = $startDay->format("Y");
    }

    // Monthly Revenue
    $data[$curMonthyearKey][$contract['id']]['monthly_revenue'] = 0;
    if ($contract['rent_per_month'] > 0) {
      $data[$curMonthyearKey][$contract['id']]['monthly_revenue'] += ($contract['rent_per_month'] );
    }
    if ($contract['setup_fee'] > 0) {
      $data[$curMonthyearKey][$contract['id']]['monthly_revenue'] += $contract['setup_fee'];
      $contract['setup_fee'] = 0;
    }

    $data[$curMonthyearKey][$contract['id']]['monthly_revenue']=($contract['now_status']<1)?0:$data[$curMonthyearKey][$contract['id']]['monthly_revenue'];
    return $data;
  }
  private function getLcPriceInfo($lcPricing) {
    // Generate lc pricing per year.
    $lcPriceInfo = explode(",", $lcPricing);
    $lcYearWisePrice = [];
    if (is_array($lcPriceInfo)) {
      foreach ($lcPriceInfo as $lcPriceInfoParts) {
        $lcPriceYearInfo = explode("-", $lcPriceInfoParts);
        if (isset($lcPriceYearInfo[0]) && isset($lcPriceYearInfo[1])) {
          $lcYearWisePrice[$lcPriceYearInfo[0]] = $lcPriceYearInfo[1];
        }
      }
    }
    return $lcYearWisePrice;
  }

  private function getModelLCPriceInfo($model_pricing) {
    $modelPriceInfo = explode(",", $model_pricing);
    $modelYearWisePrice = [];
    if (is_array($modelPriceInfo)) {
      foreach ($modelPriceInfo as $modelPriceInfoParts) {
        $modelPriceYearPrice = explode("@", $modelPriceInfoParts);
        if (is_array($modelPriceYearPrice)) {
          // echo $modelPriceYearPrice[0];
          $idYear = explode("=", $modelPriceYearPrice[0]);
          if (isset($idYear[1])) {
            $modelYearWisePrice[$idYear[1]][$idYear[0]] = isset($modelPriceYearPrice[1]) ? $modelPriceYearPrice[1] : 0;
          }
        }
      }
    }
    return $modelYearWisePrice;
  }

 private function calculateCommission($allContracts, $teliaInfoPrice, $eduInfoPrice, $extension_type = 'automatic', $extensionPercent = 23, $tillTime, &$finalOutput, $isYearly) {

        if (!empty($allContracts)) {
            foreach ($allContracts as $key => $contract) {                
                $end_date = new DateTime($contract['end_date']);
                //$end_date->sub(new DateInterval('P1D'));
				
                $startDay = new DateTime($contract['start_day']);
                if ($startDay->format("Y") < 2013) {
                    return;
                }
				
				$toYearMonth = $end_date->format("Y-m-d"); 
				 $lastDay = date('t',strtotime($end_date->format("Y-m-d")));
				
				// If any contract start in mid of month then commission will be until end of previous month.
				if($lastDay!=$end_date->format("d")){					
					$interval = new DateInterval("P1M");					
					$end_date->sub($interval);	
					$toYearMonth = $end_date->format("Y-m-d"); 
				}
                // Generate model pricing per year.
                $modelYearWisePrice = $this->getModelLCPriceInfo($contract['model_pricing']);
				$lcYearWisePrice=$this->getModelLCPriceInfo($contract['lc_pricing']);
                //print_r($modelYearWisePrice);
                $months = trim(str_replace(["-mn","-mnader","-mnad"], "", $contract['months']));
                $contractLength = (is_numeric($months)) ? $months : 60;

                $contractLengthIntial = $contractLength;
                $contractLengthInterval = new DateInterval('P' . $contractLength . 'M');
                $startDay = new DateTime($contract['start_day']);				
                $originalContractStartDay = new DateTime($contract['start_day']);
                $orgStartDate = new DateTime($contract['start_day']);
				
                $untillDay = new DateTime(DateTime::createFromFormat("Y-m-d", $toYearMonth)->format("Y-m-t"));				
				if(trim($contract['paid_date'])!=''){
					$nextPaymentDate = new DateTime($contract['paid_date']);
					$nextPaymentDateTEMP = new DateTime($contract['paid_date']);
				}
				else{
					$nextPaymentDate = new DateTime($contract['start_day']);
					$nextPaymentDateTEMP = new DateTime($contract['start_day']);
				}
				
				
                $oldYear = '';
                $extendedTimes = 0;
				$payment_cicle = trim(str_replace(["-mn","-mnader","-mnad"], "",$contract['payment_cicle']));
				$payment_cicle=(is_numeric($payment_cicle))?$payment_cicle:12;
				$counter=$payment_cicle;
				$loopCount=0;
				$paidDate="";
				$count=0;
                while ($untillDay >= $startDay) {
                    $curMonthyearKey = $startDay->format("Yn");
					
                    $data = $this->calculateAllParmsForContract($lcYearWisePrice, $modelYearWisePrice, $teliaInfoPrice, $eduInfoPrice, $curMonthyearKey, $contract, $oldYear, $contractLengthInterval, $contractLength, $contractLengthIntial, $extendedTimes, $startDay, $originalContractStartDay, $extension_type);
					$data[$curMonthyearKey][$contract['id']]['commissionable_sum']=0;
					if($contract['procent_provision']>0){
                    $data[$curMonthyearKey][$contract['id']]['commissionable_sum'] = ($data[$curMonthyearKey][$contract['id']]['model_revenue'] + $data[$curMonthyearKey][$contract['id']]['lc_revenue'] + $data[$curMonthyearKey][$contract['id']]['sim_kart_value'] + $data[$curMonthyearKey][$contract['id']]['education_value'] + $data[$curMonthyearKey][$contract['id']]['other_fixed_costs']);
					
					}

                    //Commission
                    $data[$curMonthyearKey][$contract['id']]['commission'] = 0;
                    if ($contract['is_extension_agreement'] == 1 && $extension_type == 'automatic') {
                       // $contract['procent_provision'] = 5;
                    }
                    $data[$curMonthyearKey][$contract['id']]['commission'] = ($data[$curMonthyearKey][$contract['id']]['monthly_revenue'] - $data[$curMonthyearKey][$contract['id']]['commissionable_sum']) * ($contract['procent_provision'] / 100);

                    /** keep only required things * */
                    $output[$curMonthyearKey][$contract['id']]['commission'] = $data[$curMonthyearKey][$contract['id']]['commission'];
					// monthly revenue will be 0 if commission percent is 0
                    $output[$curMonthyearKey][$contract['id']]['monthly_revenue'] = ($contract['procent_provision']>0)?$data[$curMonthyearKey][$contract['id']]['monthly_revenue']:0;
                    
					$output[$curMonthyearKey][$contract['id']]['cost'] = $output[$curMonthyearKey][$contract['id']]['commission'] + $data[$curMonthyearKey][$contract['id']]['commissionable_sum'];
									

                    $output[$curMonthyearKey][$contract['id']]['custome_name_'] = $contract['custome_name_'];
                    $output[$curMonthyearKey][$contract['id']]['avdelning_'] = $contract['avdelning_'];
                    $output[$curMonthyearKey][$contract['id']]['contract_number'] = $contract['contract_number'];
                    $output[$curMonthyearKey][$contract['id']]['start_day'] = $originalContractStartDay->format("Y-m-d");

                    if ($contract['is_extension_agreement'] == 1 && $extensionPercent>0) {
                        $output[$curMonthyearKey][$contract['id']]['monthly_revenue'] = ($output[$curMonthyearKey][$contract['id']]['monthly_revenue'] * $extensionPercent / 100);                      
                        for ($cnt = 1; $cnt < $extendedTimes; $cnt++) {
                            $output[$curMonthyearKey][$contract['id']]['monthly_revenue'] = ($output[$curMonthyearKey][$contract['id']]['monthly_revenue'] * $extensionPercent / 100);
                           
                        }
                    } 
					
                    $output[$curMonthyearKey][$contract['id']]['is_extension'] = $contract['is_extension_agreement'];
                    //$hasExtensionText = ($contract['is_extension_agreement'] == 1) ? 'extended' : 'normal';	
					
					
					
					
					
					if($count==0){
						if($contract['paid_date']!=''){
							$savedDateObj=date_create($contract['paid_date']);
							$paidDate=$savedDateObj->format("Y-m-d");//contract['paid_date'];
						}
						else{
							//if($nextPaymentDateTEMP->format("Y-m-d")==$originalContractStartDay->format("Y-m-25")){
								$intervalTEMP = new DateInterval("P1M");					
								$nextPaymentDateTEMP->add($intervalTEMP);
								$paidDate=$nextPaymentDateTEMP->format("Y-m-25");
							//}
						}
						//echo $nextPaymentDateTEMP->format("Y-m-d")." === ".$originalContractStartDay->format("Y-m-d");
						
					}
					else{
						$paidDate=$orgStartDate->format("Y-m-25");
					}
					
					if($counter<=0){		
						$interval = new DateInterval("P".$payment_cicle."M");					
						$nextPaymentDate->add($interval);
						$orgStartDate->add($interval);
						$counter=$payment_cicle;
						$count++;
					} 
					
					if($isYearly)
					$finalKey = ($isYearly) ? $nextPaymentDate->format("Y") : $nextPaymentDate->format("Yn");
					else 
					$finalKey = ($isYearly) ? $startDay->format("Y") : $startDay->format("Yn");
					
					$finalOutput[$contract['id']][$finalKey]['commissionable_sum'] = $data[$curMonthyearKey][$contract['id']]['commissionable_sum'];
					$finalOutput[$contract['id']][$finalKey]['procent_provision'] =$contract['procent_provision'];
					
                    $finalOutput[$contract['id']][$finalKey]['commission'] = isset($finalOutput[$contract['id']][$finalKey]['commission']) ? $finalOutput[$contract['id']][$finalKey]['commission'] : 0;

					$finalOutput[$contract['id']][$finalKey]['commission'] += $output[$curMonthyearKey][$contract['id']]['commission'];
                    
                    $finalOutput[$contract['id']][$finalKey]['custome_name_'] = $output[$curMonthyearKey][$contract['id']]['custome_name_'];
                    $finalOutput[$contract['id']][$finalKey]['avdelning_'] = $output[$curMonthyearKey][$contract['id']]['avdelning_'];
                    $finalOutput[$contract['id']][$finalKey]['contract_number'] = $output[$curMonthyearKey][$contract['id']]['contract_number'];
                    $finalOutput[$contract['id']][$finalKey]['start_day'] = $output[$curMonthyearKey][$contract['id']]['start_day'];
                    $finalOutput[$contract['id']][$finalKey]['rent_per_month'] = $contract['rent_per_month'];
					//echo $contract['rent_per_month'];
					
                    $finalOutput[$contract['id']][$finalKey]['months'] = $months;
                    $finalOutput[$contract['id']][$finalKey]['payment_cicle'] = $payment_cicle;
                    $finalOutput[$contract['id']][$finalKey]['total_commission'] = $contract['total_commission'];
					$finalOutput[$contract['id']][$finalKey]['per_year_commission'] = $contract['per_year_commission'];
                    $finalOutput[$contract['id']][$finalKey]['os_kommentar'] = $contract['os_kommentar'];
                    $finalOutput[$contract['id']][$finalKey]['signed_agreement'] = $contract['signed_agreement'];
                    $finalOutput[$contract['id']][$finalKey]['paid_date'] = $contract['paid_date'];
                    $finalOutput[$contract['id']][$finalKey]['comm_paid_date'] = $paidDate;
					$finalOutput[$contract['id']][$finalKey]['due_payment_date']=$nextPaymentDate->format("Yn");
                    
					$finalOutput[$contract['id']][$finalKey]['act_paid_date']=$nextPaymentDate->format("Y-m-d");					
                
							$finalOutput[$contract['id']][$startDay->format("Yn")]['current_month_commission']= $output[$curMonthyearKey][$contract['id']]['commission'] ;
						
					
                    unset($output);
                    unset($data);
                    $monthInterval = new DateInterval('P1M');
                    $startDay->add($monthInterval);
					$counter--;
                }
				//print_r($finalOutput);
            }
        } else {
           $finalOutput=array(); // "We don't find any records having selected criteria";           
        }
    }
	
 

  

  private function generateYearlyForContractStats($data) {
    $output = ['overall_total' => 0];
    if (is_array($data)) {
      foreach ($data as $key => $row) {
        $output['overall_total'] = (isset($output['overall_total']) ? $output['overall_total'] : 0) + $row['total_count'];
        $output[$row['year']] = $row['total_count'];
      }
    }
    return $output;
  }

  private function sortDesc($my_array) {
    do {
      $swapped = false;

      for ($i = 0; $i < count($my_array); $i++) {

        $keys = array_keys($my_array);
        if (isset($keys[$i + 1])) {
          if ($my_array[$keys[$i]]['overall_total'] < $my_array[$keys[$i + 1]]['overall_total']) {
            list($my_array[$keys[$i]], $my_array[$keys[$i + 1]]) = array($my_array[$keys[$i + 1]], $my_array[$keys[$i]]);
            $swapped = true;
          }
        }
      }

      if ($swapped == false)
        break;

      $swapped = false;

      for ($i = count($my_array) - 1; $i >= 0; $i--) {
        $keys = array_keys($my_array);
        if (isset($keys[$i - 1])) {
          if ($my_array[$keys[$i]]['overall_total'] > $my_array[$keys[$i - 1]]['overall_total']) {
            list($my_array[$keys[$i]], $my_array[$keys[$i - 1]]) = array($my_array[$keys[$i - 1]], $my_array[$keys[$i]]);
            $swapped = true;
          }
        }
      }
    } while ($swapped);

    return $my_array;
  }

  private function sortDescKey($my_array) {
    $lenth = array_keys($my_array);
    arsort($lenth);
    foreach ($lenth as $key => $val) {
      $keyText = ($val >= 1) ? ($val . ' år') : 'Under 1 år';
      $data[$keyText] = $my_array[$val];
    }
    return $data;
  }

}
