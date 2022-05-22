<?php
$graphData = [];
$graphData["datasets"][0]["backgroundColor"][] = 'rgba(237,125,49, 1)';
$graphData["datasets"][0]["backgroundColor"][] = 'rgba(255,192,3, 1)';
$graphData['labels']=['Nytt ','Förlängt'];

if ($search_ajax != 'yes'): ?>
  <div class="whitebgDiv">
    <?php endif; ?>
    <?php if ($search_ajax != 'yes'  && $canvasId=='contract_stats_total'): ?>
      <div class="form-box">
          <form name="frm-ajax-search-stat" class="frm-static-search" method="post" action="<?php echo base_url("reports/reports/" . $canvasId); ?>" onsubmit ="return loadSpecificPeriodData(this);">
              <input type="hidden" name="search_ajax" value="yes" />
              <div class="row form-group margintop20">
                  <div class="col-sm-12">
                      <h2 class="stats-heading"><b>Visa statistik från specifik period</b></h2>
                  </div>
                  <div class="col-md-8 col-xs-12">
                      <div class="col-sm-4 col-md-3 col-xs-6">
                          <div class="form-group">
                              <input type="text" id="from_month_<?php echo $canvasId; ?>" placeholder="Från"  name="from_month"  class="form-control stat_from_dt_picker" value="" />

                          </div>
                      </div>

                      <div class="col-sm-4 col-md-3 col-xs-6">
                          <div class="form-group">
                              <?php $todayDate = new DateTime(); ?>
                              <input type="text" id="to_month_<?php echo $canvasId; ?>"  placeholder="Till" name="to_month" class="form-control stat_to_dt_picker" value="" />


                          </div>
                      </div>
                      <div class="col-sm-4 col-md-3 col-xs-12">
                          <button class="btn btn-orange btn-flat btn-wide btn-load-ajax btn-block" type="submit">Sök</button>
                      </div>
                  </div>

              </div>
          </form>
      </div>
      <script type="text/javascript">
        $(document).ready(function () {
            $("#from_month_<?php echo $canvasId; ?>").datepicker({
                dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true,
                onClose: function (dateText, inst) {
                    var toDateField = $(this).closest("form").find("input[name='to_month']");
                    var fromDateField = $(this);
                    if (toDateField.val() != '') {
                        if (fromDateField.datepicker("getDate") > toDateField.datepicker("getDate")) {
                            toDateField.datepicker("setDate", fromDateField.datepicker("getDate"));
                        }
                    }
                },
                onSelect: function (selectedDateTime) {
                    var toDateField = $(this).closest("form").find("input[name='to_month']");
                    var fromDateField = $(this);

                    toDateField.datetimepicker('option', 'minDate', fromDateField.datetimepicker('getDate'));
                }
            });
            $("#to_month_<?php echo $canvasId; ?>").datepicker({
                dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true,
                onClose: function (dateText, inst) {
                    var fromDateField = $(this).closest("form").find("input[name='from_month']");
                    var toDateField = $(this);


                    if (fromDateField.val() != '') {
                        var testStartDate = fromDateField.datetimepicker('getDate');
                        var testEndDate = toDateField.datetimepicker('getDate');
                        if (testStartDate > testEndDate)
                            fromDateField.datetimepicker('setDate', testEndDate);
                    } else {
                        fromDateField.val(dateText);
                    }
                },
                onSelect: function (selectedDateTime) {
                    var fromDateField = $(this).closest("form").find("input[name='from_month']");
                    var toDateField = $(this);
                    fromDateField.datetimepicker('option', 'maxDate', toDateField.datetimepicker('getDate'));
                }
            });
        })
      </script>
      <style>
          .tdpaddding0{
              padding:0!important;
          }
          .table-innertable{
              margin-bottom:0!important;
              border-bottom: 0!important;
          }
          .table-innertable tr td{
              border-top:0!important;
          }
      </style>
    <?php endif; ?>
    <div class="ajax-load-lc-data">
        <div class="row">
            <div class="col-md-6 col-xs-12">
				<div class="nested-tables">
				<div class="table-responsive">
					<table id="table_lc_larms" class="parent-table table table-condensed table-striped dataTable no-footer" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;">
						<thead>
							<tr role="row">
								<th>&nbsp;</th>
								<th class="navigator">Nytt</th>
								<th>Förlängt</th>
								<th>Totala</th>
							</tr>
						</thead>
						<tbody>
						<tr>
							<td class="tdpaddding0" colspan="4">
								<table class="child-table table table-innertable table-condensed  table-striped dataTable" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;"> 
								<tr>
						
						
							<td>Totalt</td>
							<td><?php echo $new['overall_total']; ?></td>
							<td><?php echo $extended['overall_total']; ?></td>
							<td><?php echo $total=$extended['overall_total'] + $new['overall_total']; ?></td>

						</tr>
						<tr>
							<td>Procent</td>
							<td><?php echo $newContractPercent=($new['overall_total'] > 0) ? round(($new['overall_total'] * 100 )/$total): 0; ?>%</td>
							<td><?php echo $extendedContractPercent=($extended['overall_total'] > 0) ? round(($extended['overall_total']) *100 / $total) : 0; ?>%</td>
							<td></td>
								<?php $graphData['datasets'][0]['data'] = [$newContractPercent,$extendedContractPercent]; ?>
						</tr>
						</table></td>
						</tr>
						<tr class="blank-row">
							  <td colspan="4"></td>
						</tr>
						<?php  for ($year = $to; $year >= $from; $year--): ?>
						<tr>
							<td class="tdpaddding0" colspan="4">
								<table class="child-table table table-innertable table-condensed  table-striped dataTable" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;"> 
						  <tr>
							  <td><?php echo $year; ?></td>
							  <td><?php echo $newContractThisYear = isset($new[$year]) ? $new[$year] : 0; ?></td>
							  <td><?php echo $extenedContractThisyear = isset($extended[$year]) ? $extended[$year] : 0; ?></td>
							  <td><?php echo $totalThisYear = (isset($new[$year]) ? $new[$year] : 0) + (isset($extended[$year]) ? $extended[$year] : 0); ?></td>
						  </tr>
						  <tr>
							  <td>Procent</td>
							  <td><?php echo ($newContractThisYear > 0) ? round($newContractThisYear / $totalThisYear * 100) : 0; ?>%</td>
							  <td><?php echo ($extenedContractThisyear > 0) ? round($extenedContractThisyear / $totalThisYear * 100) : 0; ?>%</td>
							  <td></td>
						  </tr>
						  </table>
						  </td>
						  </tr>
						  <tr class="blank-row">
								<td colspan="4"></td>
							</tr>
						<?php endfor;  ?>
					</tbody>
				</table>
			</div>
			</div>
		</div>
		<div class="col-md-6 col-xs-12">
			<div id="chart-legend_<?php echo $canvasId; ?>" class="chart-legend"></div>
			<div class="chart-container" style="width:400px;">
				<canvas id="lc_grpah_<?php echo $canvasId; ?>" width="100px" height="100px"></canvas>
			</div>
			
		</div>
	</div>
	<script type="text/javascript">

	  var chartObj = document.getElementById("lc_grpah_<?php echo $canvasId; ?>").getContext("2d");
	  var legendId=document.getElementById("chart-legend_<?php echo $canvasId; ?>");
	  drawChart(<?php echo json_encode($graphData) ?>, chartObj,legendId);

	</script>

</div>
<?php //if ($search_ajax != 'yes'): ?>
</div>
<?php // endif; ?>
