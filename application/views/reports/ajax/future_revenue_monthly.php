<div class="graph-and-canvas">
    <div id="js-legend" class="chart-legend"></div>
    <div class="whitebgDiv">

        <canvas class="future_revenue_grpah" width="100%" height="400px"></canvas>

    </div>
    <table class="myTableSlider_monthly table table-condensed table-bordered table-striped dataTable no-footer" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;">
        <tr>
            <th width="255"></th>
            <?php
            $searchPeriodStart = new DateTime($start);
            $searchPeriodEnd = new DateTime($to);
            ?>

            <?php while ($searchPeriodEnd->getTimeStamp() >= $searchPeriodStart->getTimeStamp()): ?>

              <th data-start-date="<?php echo $searchPeriodStart->format("Y-m-01"); ?>" data-end-date="<?php echo $searchPeriodStart->format("Y-m-t"); ?>"><?php echo $searchPeriodStart->format("M Y"); ?></th>
              <?php
              $monthInterval = new DateInterval('P1M');
              $searchPeriodStart->add($monthInterval);
              ?>
            <?php endwhile; ?>

        </tr>
        <tr>
            <?php $overAllRevenue = 0; ?>

            <td class="row-heading">Totalt Intäkter  </td>
            <?php
            $searchPeriodStart = new DateTime($start);
            $searchPeriodEnd = new DateTime($to);
            //echo '<pre>';print_r($tableData);echo '</pre>';die;
            ?>
            <?php while ($searchPeriodEnd >= $searchPeriodStart): ?>
              <?php $overAllRevenue += (isset($tableData[$searchPeriodStart->format("Yn")]['monthly_revenue'])) ? $tableData[$searchPeriodStart->format("Yn")]['monthly_revenue'] : 0; ?>
              <td><?php echo number_format(((isset($tableData[$searchPeriodStart->format("Yn")]['monthly_revenue'])) ? $tableData[$searchPeriodStart->format("Yn")]['monthly_revenue'] : '0'),0,'.',' ') ?> kr</td>

              <?php
              $monthInterval = new DateInterval('P1M');
              $searchPeriodStart->add($monthInterval);
              ?>
            <?php endwhile; ?>
        </tr>
		
		<tr class="small">
            

            <td class="row-heading">Totalt Avskrivningar  </td>
            <?php
            $searchPeriodStart = new DateTime($start);
            $searchPeriodEnd = new DateTime($to);
            //echo '<pre>';print_r($tableData);echo '</pre>';die;
            ?>
            <?php while ($searchPeriodEnd >= $searchPeriodStart): ?>
              <td><?php echo number_format(((isset($tableData[$searchPeriodStart->format("Yn")]['others'])) ? $tableData[$searchPeriodStart->format("Yn")]['others'] : '0'),0,'.',' ') ?> kr</td>
              <?php
              $monthInterval = new DateInterval('P1M');
              $searchPeriodStart->add($monthInterval);
              ?>
            <?php endwhile; ?>
        </tr>
	<tr class="small">
            

            <td class="row-heading">Totalt LC  </td>
            <?php
            $searchPeriodStart = new DateTime($start);
            $searchPeriodEnd = new DateTime($to);
            //echo '<pre>';print_r($tableData);echo '</pre>';die;
            ?>
            <?php while ($searchPeriodEnd >= $searchPeriodStart): ?>
              <td><?php echo number_format(((isset($tableData[$searchPeriodStart->format("Yn")]['lc'])) ? $tableData[$searchPeriodStart->format("Yn")]['lc'] : '0'),0,'.',' ') ?> kr</td>
              <?php
              $monthInterval = new DateInterval('P1M');
              $searchPeriodStart->add($monthInterval);
              ?>
            <?php endwhile; ?>
        </tr>
	<tr class="small">
            

            <td class="row-heading">Totalt SIM  </td>
            <?php
            $searchPeriodStart = new DateTime($start);
            $searchPeriodEnd = new DateTime($to);
            //echo '<pre>';print_r($tableData);echo '</pre>';die;
            ?>
            <?php while ($searchPeriodEnd >= $searchPeriodStart): ?>
              <td><?php echo number_format(((isset($tableData[$searchPeriodStart->format("Yn")]['sim'])) ? $tableData[$searchPeriodStart->format("Yn")]['sim'] : '0'),0,'.',' ') ?> kr</td>
              <?php
              $monthInterval = new DateInterval('P1M');
              $searchPeriodStart->add($monthInterval);
              ?>
            <?php endwhile; ?>
        </tr>

		
		
		
		
		
        <tr>
            <?php $overAllCost = 0; ?>
            <td class="row-heading">Totalt kostnaden</td>
            <?php
            $searchPeriodStart = new DateTime($start);
            $searchPeriodEnd = new DateTime($to);
            ?>
            <?php while ($searchPeriodEnd >= $searchPeriodStart): ?>
              <?php $overAllCost += (isset($tableData[$searchPeriodStart->format("Yn")]['monthly_cost'])) ? $tableData[$searchPeriodStart->format("Yn")]['monthly_cost'] : 0; ?>
              <td><?php echo number_format(((isset($tableData[$searchPeriodStart->format("Yn")]['monthly_cost'])) ? $tableData[$searchPeriodStart->format("Yn")]['monthly_cost'] : 0),0,'.',' '); ?> kr</td>
              <?php
              $monthInterval = new DateInterval('P1M');
              $searchPeriodStart->add($monthInterval);
              ?>
            <?php endwhile; ?>



        </tr>
        <tr>
            <td class="row-heading">Total TB </td>

            <?php
            $searchPeriodStart = new DateTime($start);
            $searchPeriodEnd = new DateTime($to);
            ?>
            <?php while ($searchPeriodEnd >= $searchPeriodStart): ?>
              <td><?php echo number_format(((isset($tableData[$searchPeriodStart->format("Yn")]['monthly_cost'])) ? ($tableData[$searchPeriodStart->format("Yn")]['monthly_revenue'] - $tableData[$searchPeriodStart->format("Yn")]['monthly_cost']) : 0),0,'.',' ') ?></td>
              <?php
              $monthInterval = new DateInterval('P1M');
              $searchPeriodStart->add($monthInterval);
              ?>
            <?php endwhile; ?>





        </tr>
        <tr>
            <td class="row-heading">Bruttomarginal </td>
            <?php
            $searchPeriodStart = new DateTime($start);
            $searchPeriodEnd = new DateTime($to);
            ?>
            <?php while ($searchPeriodEnd >= $searchPeriodStart): ?>
              <?php if (isset($tableData[$searchPeriodStart->format("Yn")]['monthly_revenue']) && $tableData[$searchPeriodStart->format("Yn")]['monthly_revenue'] > 0): ?>
                <td><?php echo (isset($tableData[$searchPeriodStart->format("Yn")]['monthly_cost'])) ? round((($tableData[$searchPeriodStart->format("Yn")]['monthly_revenue'] - $tableData[$searchPeriodStart->format("Yn")]['monthly_cost']) / $tableData[$searchPeriodStart->format("Yn")]['monthly_revenue']) * 100) : 0 ?> %</td>
              <?php else: ?>
                <td>0%</td>
              <?php endif; ?>
              <?php
              $monthInterval = new DateInterval('P1M');
              $searchPeriodStart->add($monthInterval);
              ?>
            <?php endwhile; ?>
        </tr>
    </table>

    <table class="total-table table-condensed table-bordered table-striped dataTable no-footer">
        <tr>
            <th width="255"></th>
            <th>Totalen</th>
        </tr>
        <tr>
            <td width="255">Totalt ordervärde</td>
            <td><?php echo number_format($overAllRevenue,0,'.',' '); ?> kr</td>
        </tr>
        <tr>
            <td width="255">Totalt kostnaden</td>
            <td><?php echo number_format($overAllCost,0,'.',' '); ?> kr</td>
        </tr>
        <tr>
            <td width="255">Total TB</td>
            <td><?php echo number_format(($overAllRevenue - $overAllCost),0,'.',' '); ?> kr</td>
        </tr>
        <tr>
            <td width="255">Bruttomarginal</td>
            <td>
                <?php if ($overAllRevenue > 0): ?>
                  <?php echo round(($overAllRevenue - $overAllCost) / $overAllRevenue * 100); ?>%
                <?php else: ?>
                  0%
                <?php endif; ?>
            </td>

        </tr>
    </table>
    <script type="text/javascript">
      $(".myTableSlider_monthly").tableSlider({
          visibleClass: "tdVisible",
          hiddenClass: "tdHidden",
          numberOfColumn: 7,
          intialSlide: 1,
          steps:7,
          slideOnChange: function (startRow, endRow) {


              loadFutureRevenue(startRow.attr("data-start-date"), endRow.attr("data-end-date"), "<?php echo base_url('reports/reports/load_future_revenue_monthly_graph_data_ajax'); ?>", endRow);
          }
      });
    </script>
    <style>
        .chart-legend li span{
            display: inline-block;
            width: 12px;
            height: 12px;
            margin-right: 5px;
			margin-bottom:0px;
        }
		.small td{
			padding-top:2px!important;
			padding-bottom:2px!important;
			font-style:italic;
		}
    </style>
</div>