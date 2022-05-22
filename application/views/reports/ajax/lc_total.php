<?php
$graphData = [];
$graphData["datasets"][0]["backgroundColor"] = 'rgba(237,125,49, 1)';
?>
<table id="table_lc_larms" class="table table-condensed table-bordered table-striped dataTable no-footer" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;">
    <thead>
        <tr role="row">
            <th class="navigator"></th>

            <?php foreach ($lcs as $lc): ?>
              <?php $graphData['labels'][] = $lc['name']; ?>
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
            <?php $percent=($lc['overall_total'] > 0)? round($lc['overall_total'] / $all * 100):0; ?>
            <?php $graphData['datasets'][0]['data'][] = $percent; ?>
              <td><?php echo $percent;?>%</td>
<?php endforeach; ?>
            <td></td>
        </tr>
    </tbody>
</table>
<div class="chart-container">
<canvas id="lc_grpah_yearly" width="100%" height="400px"></canvas>
</div>
<table id="table_lc_larms_yearly" class="table table-condensed table-bordered table-striped dataTable no-footer" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;">
    <thead>
        <tr role="row">
<?php $colSpan = 2; ?>
            <th class="navigator"></th>

            <?php foreach ($lcs as $lc): ?>
              <th><?php echo $lc['name']; ?></th>
              <?php $colSpan++; ?>
<?php endforeach; ?>
            <th class="navigator">Total</th>

        </tr>

    </thead>
    <tbody>
<?php for ($year = $to; $year >= $from; $year--): ?>
           
          <tr>
              <td><?php echo $year; ?></td>
              <?php $all = 0; ?>
              <?php foreach ($lcs as $lc): ?>
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
              <?php foreach ($lcs as $lc): ?>
                <td><?php if (isset($lc['lcs'][$year]) && $lc['lcs'][$year] > 0): ?> <?php echo round($lc['lcs'][$year] / $all * 100); ?><?php else: ?>0<?php endif; ?>%</td>
  <?php endforeach; ?>
              <td></td>
          </tr>
<?php endfor; ?>
    </tbody>
</table>
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
</style><pre>
<script type="text/javascript">
  var chartObj = document.getElementById("lc_grpah_yearly").getContext("2d");
  drawChart(<?php echo json_encode($graphData)?>, chartObj);
</script>