
<div class="row margintop10 ">
	<hr class="logindark">
	<?php $curDate = getTimeObj();
	$currentYearRevenue=0;
	$nextYearRevenue=0;		
	$currentYearCost=0;
	$nextYearCost=0;
	$currentMonthRevenue=0;								
	$currentMonthCost=0;	
							
	$currentMonthTB=0;		
	/*  echo "<pre>";
	print_r($commission);
	echo "</pre>";  */   
	foreach($commission as $key=>$contract){
		
		$currentYearRevenue+=$contract[$curDate->format("Y")]['monthly_revenue'];		
		$nextYearRevenue+=isset($contract[($curDate->format("Y")+1)]['monthly_revenue'])?$contract[($curDate->format("Y")+1)]['monthly_revenue']:0;		
		$currentYearCost+=$contract[$curDate->format("Y")]['cost'];		
		$nextYearCost+=isset($contract[($curDate->format("Y")+1)]['cost'])?$contract[($curDate->format("Y")+1)]['cost']:0;			
		if(isset($contract[$curDate->format("Y")]['current_month_revenue'])){
			$currentMonthRevenue=$contract[$curDate->format("Y")]['current_month_revenue'];
			$currentMonthCost=$contract[$curDate->format("Y")]['current_month_cost'];
		}
	} 								
	$currentMonthTB=$currentMonthRevenue-$currentMonthCost;								
	$currentYearTB=$currentYearRevenue-$currentYearCost;
	$nextYearTB=$nextYearRevenue-$nextYearCost;		
	?>
	<div class="col-lg-4 col-sm-4">
		<span><strong><?php echo round(($currentMonthTB*0.1))." ".CURRENCY_NAME?></strong></span>
		<span>Innevarande månads provision</span>
	</div>
	<div class="col-lg-4 col-sm-4">
		<span><strong><?php echo round(($currentYearTB*0.1))." ".CURRENCY_NAME;?></strong></span>
		<span>Innevarande års totala provision</span>
	</div>
	<div class="col-lg-4 col-sm-4">
		<span><strong><?php echo round(($nextYearTB*0.1))." ".CURRENCY_NAME;?></strong></span>
		<span>Kommande års totala provision</span>
	</div>
	<div class="col-lg-12 col-sm-12 margintop10">
	<table class="table table-bordered table-striped table-colborder dataTable" >
		<thead>
			<tr>
				<th>Kund</th>
				<th>Avtalsnummer</th>
				<th>Antal år avtal</th>					
				<th>Total provision för avtalet</th>
				<th>Provision per år</th>
				<th>Betalas ut datum</th>
				<th>Godkänt av Ekonomi & uppladdat</th>
				<th>Kommentar</th>					
			</tr>
		</thead>
		<tbody>                                    
			<?php			
			foreach($commission as $key=>$contract){
				$monthly_revenue=0;
				$cost=0;
				$contract_id=$key;
				foreach($contract as $k=>$year){
					$monthly_revenue+=$year['monthly_revenue'];
					$cost+=$year['cost'];
				}
				$loopCount=0;
				foreach($contract as $key1=>$year){
					$signed_agreement=($year['signed_agreement']=="1")?"Ja":"Nej";
					$contractLength = (is_numeric($year['months'])) ? $year['months'] : 60;
					$numberOfYears=$contractLength/12;					
					$avdelning=$year['avdelning_'];				
					$tb1=$year['monthly_revenue']-$year['cost'];
					//echo "<br>";
					$totalCommission=($tb1*0.1);					
					$commissionPerYear=$totalCommission/$numberOfYears;
					echo '<tr class="commission-'.$contract_id.'"><td>'.$year['custome_name_'].'</td>';	
					echo '<td style="padding:0px 10px">'.$year['contract_number'].'</td>';
					echo '<td style="padding:0px 10px">'.($year['months']/12).'</td>';
					echo '<td style="padding:0px 10px">'.round($totalCommission)." ".CURRENCY_NAME.'</td>';
					echo '<td style="padding:0px 10px">'.round($commissionPerYear)." ".CURRENCY_NAME.'</td>';?>
					<?php $is_editable=($loopCount==0)?"editable":""; ?>
					<td class="row-<?php echo $contract_id;?>" ><span class="<?php echo $is_editable; ?>" data-class="<?php echo $key1 ?>" data-type="text" data-name="paid_date" data-pk="<?php echo $contract_id;?>" data-url="<?php echo base_url('contracts/paid_date'); ?>" data-title="Enter value" data-original-title="" title="" aria-describedby="popover105748"> <?php 
					if(empty($year['paid_date'])){
						echo ($loopCount==0)?"YYYY-MM-DD":"";
					}
					else{
						$paid_date = new DateTime($year['paid_date']);					
						$yearInterval = new DateInterval("P".($loopCount)."Y");
						$paid_date->add($yearInterval);
						echo ($loopCount==0)?$year['paid_date']:$paid_date->format("Y-m-25");
					}
					?></span></td>
					<?php echo '<td>'.$signed_agreement.'</td>';
					echo '<td>'.$year['os_kommentar'].'</td></tr>';
					$loopCount++;
				}
			}										
			?>
		</tbody>
	</table>
	</div>
</div>
<link href="<?php echo base_url('assets/css/bootstrap-editable.css'); ?>" rel="stylesheet"/>
<script src="<?php echo base_url('assets/js/bootstrap-editable.min.js'); ?>"></script>
<script>
$(".editable").editable({inputclass:'paid-date',emptytext:"YYYY-mm-dd"});
$(".editable").click(function(){
	$('.paid-date').datepicker({dateFormat: 'yy-mm-dd'});		
	$(".paid-date").focus(function() {
        $(".paid-date").datepicker("show");		
    });
	$(".paid-date").focus();	
})
$('.editable').on('save', function (e, params) { 
	
	paidDate=params.newValue.split("-");
	year=parseInt(paidDate[0])+1;
	$("tr.commission-"+$(this).attr("data-pk")).each(function(i, el){						
		if(i>0){			
			newPaidDate="";
			if(params.newValue!=="")
			newPaidDate=year+"-"+(paidDate[1])+"-"+"25";						
		
			$(this).find('td').eq(5).text(newPaidDate)			
			year=year+1;
		}
	})
	
	
	
	
});
</script>