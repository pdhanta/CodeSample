<?php 
$graphData = [];
$graphData["datasets"][0]["backgroundColor"] = 'rgba(237,125,49, 1)';

?>
<div class="whitebgDiv">
<div class="form-box">
	<form name="frm-ajax-search-stat" class="frm-static-search" method="post" action="<?php echo base_url("reports/reports/total_model_cost"); ?>" onsubmit ="return loadSpecificPeriodData(this);">
		<input type="hidden" name="extension_only" value ="<?php //echo $is_extension_value;  ?>" />
		<div class="row form-group margintop20">
			<div class="col-sm-12">
				<h2 class="stats-heading"><b>Visa statistik från specifik period</b></h2>								
			</div>
			<div class="col-md-8 col-xs-12">
				<div class="col-sm-4 col-md-3 col-xs-6">
					<div class="form-group">
						<input type="text" placeholder="Från"  name="from_month"  class="form-control stat_from_dt_picker" value="" />
					</div>
				</div>
				<div class="col-sm-4 col-md-3 col-xs-6">
					<div class="form-group">
						<?php $todayDate = new DateTime(); ?>
						<input type="text" placeholder="Till" name="to_month" class="form-control stat_to_dt_picker" value="" />
					</div>
				</div>								
				<div class="col-sm-4 col-md-3 col-xs-12">
					<button class="btn btn-orange btn-flat btn-wide btn-load-ajax btn-block" type="submit">Sök</button>
				</div>
				<div class="loader"></div>
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
	$(document).ready(function () {
		$("input[name='from_month']").datepicker({
			dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true,
			beforeShowDay: function (date) {

				/* Check for the first day */
				if (date.getDate() == 1) {
					return [true, ''];
				} else {
					return [false, '', 'Unavailable'];
				}
			},
			onClose: function (dateText, inst) {
				var toDateField = $(this).closest("form").find("input[name='to_month']");
				if (toDateField.val() != '') {
					var curFromDate = $.datepicker.parseDate(inst.settings.dateFormat, dateText, inst.settings);
					curFromDate.setMonth(curFromDate.getMonth() + 1);
					if (curFromDate > toDateField.datepicker("getDate")) {
						var lastDayOfMonth = new Date(curFromDate.getFullYear(), curFromDate.getMonth() + 1, 0);
						toDateField.datepicker("setDate", lastDayOfMonth);
					}
				}
			},
			onSelect: function (dateText, inst) {
				var toDateField = $(this).closest("form").find("input[name='to_month']");
				var curFromDate = $.datepicker.parseDate(inst.settings.dateFormat, dateText, inst.settings);
				curFromDate.setMonth(curFromDate.getMonth() + 1);
				var lastDayOfMonth = new Date(curFromDate.getFullYear(), curFromDate.getMonth() + 1, 0);
				toDateField.datetimepicker('option', 'minDate', curFromDate);
			}
		});
		$("input[name='to_month']").datepicker({
			dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true,
			beforeShowDay: function (date) {
				var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
				/* Check for the first day */
				if (date.getDate() == lastDay.getDate()) {
					return [true, ''];
				} else {
					return [false, '', 'Unavailable'];
				}
			},
			onClose: function (dateText, inst) {
				var fromDateField = $(this).closest("form").find("input[name='from_month']");
				var toDateField = $(this);

				var curToDate = $.datepicker.parseDate(inst.settings.dateFormat, dateText, inst.settings);
				curToDate.setMonth(curToDate.getMonth() - 1);

				if (fromDateField.val() != '') {
					var testStartDate = fromDateField.datetimepicker('getDate');
					//var testEndDate = toDateField.datetimepicker('getDate');
					if (testStartDate > curToDate)
						fromDateField.datetimepicker('setDate', curToDate);
				} else {
					curToDate.setDate(1);
					fromDateField.datetimepicker('setDate', curToDate)
				}
			},
			onSelect: function (dateText, inst) {
				var fromDateField = $(this).closest("form").find("input[name='from_month']");
				var curToDate = $.datepicker.parseDate(inst.settings.dateFormat, dateText, inst.settings);
				curToDate.setMonth(curToDate.getMonth() - 1);


				fromDateField.datetimepicker('option', 'maxDate', curToDate);
			}
		});
		$(".loaded-ajax-select").select2({width: "100%", containerCssClass: ':all:'});
		//intializeSlider($(".tab-container").find("div.ui-tabs-panel[aria-hidden=false]"));
	})
</script>
<div class="ajax-load-model-data">

<div class="table-responsive">
<table class="table table-condensed table-bordered table-striped dataTable no-footer"  >
    <thead>
        <tr>
            <th></th>				
            <?php foreach ($models as $key => $row): ?>
                <th><?php echo $row['name']; ?></th>
            <?php $graphData['labels'][] = $row['name'];
			endforeach; ?>
            <th>Total</th>	
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><b>Totalt</b></td>
            <?php
            $total_mc = 0;			
            foreach ($models as $key => $row):
                ?>
                <td><?php echo $row['overall_total'] ?></td>
                <?php $total_mc += $row['overall_total'];
				
            endforeach;
            ?>
            <td><?php echo $total_mc; ?></td>
        </tr>
        <tr>
            <td><b>Percent</b></td>
            <?php foreach ($models as $key => $row): ?>
                <td><?php echo $percent=number_format(($row['overall_total'] / $total_mc) * 100, 0) ?>%</td>
				<?php $graphData['datasets'][0]['data'][] = $percent;?>
<?php endforeach; ?>
            <td></td>
        </tr>
    </tbody>
</table> 
</div>
<?php //echo "<pre>"; print_r($graphData); echo "</pre>";?>
 <div class="chart-container">
			<?php if(isset($graphData['labels'])):?>
            <canvas id="model_grpah<?php echo $canvasId; ?>" width="100%" height="400px"></canvas>
			<?php endif;?>
        </div>
		
<div class="table-responsive">
<table class="table table-condensed table-bordered table-striped dataTable no-footer"  >

    <thead>
        <tr>
            <th></th>				
			<?php $colSpan = 2; ?>
            <?php foreach ($models as $key => $lable): ?>
                <th><?php echo $lable['name']; ?></th>
				 <?php $colSpan++; ?>
<?php endforeach; ?>
            <th>Total</th>	
        </tr>
    </thead>
    <tbody>
        <?php
        for ($year = $to; $year >= $from; $year--) {?>

            <?php echo "<tr><td>" . $year . "</td>";
            $total = 0;
            foreach ($models as $k => $value){
                echo "<td>";
                if (isset($value['lcs'][$year]) && $value['lcs'][$year]) {
                    echo $value['lcs'][$year];
                    $total += $value['lcs'][$year];
                } else
                    echo "0";
                echo "</td>";
            }
            echo "<td>" . $total . "</td>";
            echo "</tr><tr><td>Percent</td>";
            foreach ($models as $k => $value) {
                echo "<td>";
                if (isset($value['lcs'][$year]) && $value['lcs'][$year]) {
                    echo number_format(($value['lcs'][$year] / $total) * 100, 0);
                } else
                    echo "0";
                echo "%";
                echo "</td>";
            }
            echo "<td></td></tr><tr class='blank-row'>
								  <td colspan='".$colSpan."'></td>
							  </tr>";
        }
        ?>					
    </tbody>
</table>
</div>
</div>
</div>
<style>
.blank-row:last{display:none;}
</style>
 <script type="text/javascript">
          var chartObj = document.getElementById("model_grpah<?php echo $canvasId; ?>").getContext("2d");
		  
          drawChart(<?php echo json_encode($graphData) ?>, chartObj);
          $(document).ready(function () {
              $(".child-table").find("tr:first").children("td").each(function (index) {
                  var idx = $(this).index();
                  $(this).width($(this).closest(".parent-table").find("tr.mainRow").children("th:eq(" + idx + ")").width());
              })

          })
        </script>
</div>