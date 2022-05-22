<?php 
$graphData = [];
$graphData["datasets"][0]["backgroundColor"] = 'rgba(237,125,49, 1)';
?>
<div class="whitebgDiv">
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
<?php //echo "<pre>"; print_r($graphData); echo "</pre>";?>
 <div class="chart-container">
            <canvas id="model_grpah<?php echo $canvasId; ?>" width="100%" height="400px"></canvas>
        </div>
<table class="table table-condensed table-bordered table-striped dataTable no-footer"  >
    <thead>
        <tr>
            <th></th>			
			<?php $colSpan = 2; ?>	
            <?php foreach ($tillModelsCost as $key => $lable): ?>				
                <th><?php echo $lable['name']; ?></th>
				 <?php $colSpan++; ?>
			<?php endforeach; ?>
            <th>Total</th>	
        </tr>
    </thead>
    <tbody>
        <?php
        for ($year = $to; $year >= $from; $year--) {
            echo "<tr><td>" . $year . "</td>";
            $total = 0;
            foreach ($tillModelsCost as $k => $value){
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
			
            foreach ($tillModelsCost as $k => $value) {
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