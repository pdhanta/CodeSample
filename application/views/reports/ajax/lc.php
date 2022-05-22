<?php
$graphData = [];
$graphData["datasets"][0]["backgroundColor"] = 'rgba(237,125,49, 1)';
?>
<?php if ($search_ajax != 'yes'): ?>
  <div class="whitebgDiv">
    <?php endif; ?>
    <?php if ($search_ajax != 'yes' && $canvasId == 'lc_total'): ?>
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
	<?php if(!empty($lcs)):?>
	<div class="table-responsive">
        <table id="table_lc_larms" class="table table-condensed table-bordered table-striped dataTable no-footer" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;">
            <thead>
                <tr role="row">
                    <th class="navigator"></th>

                    <?php foreach ($lcs as $lc): ?>
                      <th><?php echo $lc['name']; ?></th>
                    <?php endforeach; ?>
                    <th class="navigator">Total</th>

                </tr>

            </thead>
            <tbody>
                <tr>
                    <td>Totalt</td>
                    <?php $all = 0; ?>
                    <?php foreach ($lcs as $lc): ?>
                      <?php $all += $lc['overall_total']; ?>

                      <td><?php echo $lc['overall_total']; ?></td>
                    <?php endforeach; ?>
                    <td><?php echo $all; ?></td>
                </tr>
                <tr>
                    <td>Procent</td>
                    <?php foreach ($lcs as $lc): ?>
                      <?php $percent = ($lc['overall_total'] > 0) ? round($lc['overall_total'] / $all * 100) : 0; ?>
                        <?php $graphData['datasets'][0]['data'][] = $percent; ?>
						<?php $graphData['labels'][] = $lc['name']; ?>
                      <td><?php echo $percent; ?>%</td>
                    <?php endforeach; ?>
                    <td></td>
                </tr>
            </tbody>
        </table>
		</div>
        <div class="chart-container">
            <canvas id="lc_grpah_<?php echo $canvasId; ?>" width="100%" height="400px"></canvas>
        </div>
		<?php else:?>
		<div class="chart-container"> <p style="text-align:center;padding: 20px;">Det finns ingen statistik att visa för den här perioden.</p></div>
		<canvas id="lc_grpah_<?php echo $canvasId; ?>" width="100%" height="400px" style="display:none"></canvas>
		<?php endif;?>
        <?php if ($search_ajax != 'yes'): ?>
          <div class="nested-tables">
		  <div class="table-responsive">
              <table id="table_lc_larms_yearly" class="parent-table table table-condensed table-striped dataTable no-footer" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;">
                  <thead>
                      <tr role="row" class="mainRow">
                          <?php $colSpan = 2; ?>
                          <th width="10"  style="width:150px!important;" class="navigator">---</th>

                          <?php foreach ($Tlcs as $lc): ?>
                            <th style="width:150px!important;"><?php echo $lc['name']; ?></th>
                            <?php $colSpan++; ?>
                          <?php endforeach; ?>
                          <th class="navigator" style="width:150px!important;">Total</th>

                      </tr>

                  </thead>
                  <tbody>
                      <?php
                      for ($year = $to; $year >= $from; $year--):
                        ?>
                        <tr>
                            <td class="tdpaddding0" colspan="<?php echo $colSpan; ?>">
                                <table class="child-table table table-innertable table-condensed  table-striped dataTable" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;"> 
                                    <tr>
                                        <td width="10"><?php echo $year; ?></td>
                                        <?php $all = 0; ?>
                                        <?php foreach ($Tlcs as $lc): ?>
                                          <?php if (isset($lc['lcs'][$year])): ?>
                                            <?php $all += $lc['lcs'][$year]; ?>
                                            <td><?php echo $lc['lcs'][$year]; ?></td>
                                          <?php else: ?>
                                            <td>0</td>
                                          <?php endif; ?>
                                        <?php endforeach; ?>
                                        <td><?php echo $all; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Procent</td>
                                        <?php foreach ($Tlcs as $lc): ?>
                                          <td><?php if (isset($lc['lcs'][$year]) && $lc['lcs'][$year] > 0): ?> <?php echo round($lc['lcs'][$year] / $all * 100); ?><?php else: ?>0<?php endif; ?>%</td>
                                        <?php endforeach; ?>
                                        <td></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr class="blank-row">
                            <td colspan="<?php echo $colSpan; ?>"></td>
                        </tr>
                      <?php endfor; ?>
                  </tbody>
              </table></div>
          </div>
        <?php endif; ?>
        <script type="text/javascript">

          var chartObj = document.getElementById("lc_grpah_<?php echo $canvasId; ?>").getContext("2d");
          drawChart(<?php echo json_encode($graphData) ?>, chartObj);
          $(document).ready(function () {
              $(".child-table").find("tr:first").children("td").each(function (index) {
                  var idx = $(this).index();
                  $(this).attr("style","width:"+($(this).closest(".parent-table").find("tr.mainRow").children("th:eq(" + idx + ")").width()-2)+"px!important");
              })

          })
        </script>
    </div>
    <?php if ($search_ajax != 'yes'): ?>
  </div>
<?php endif; ?>