<?php
$revenue = $revenueExt = $revenueNew = $cost = $costNew = $costExt = 0;
foreach ($orderValueMonthly as $key => $year) {
	$revenue += $year['monthly_revenue'];
	$revenueNew += $year['monthly_new_revenue'];
	$revenueExt += $year['monthly_ext_revenue'];
	$cost += $year['monthly_cost'];
	$costNew += $year['monthly_new_cost'];
	$costExt += $year['monthly_ext_cost'];
}
?>

<table class="table table-bordered table-striped table-colborder dataTable" >
	<colgroup>
	   <col width="27%" span="1">
	   <col width="15%" span="3">
	</colgroup>
	<thead>
		<tr>
			<th>Total</th>
			<th>Förlängda</th>
			<th>Nya</th>
			<th>Total</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Totalt ordervärde</td>
			<td><?php echo  number_format($revenueExt,0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
			<td><?php echo  number_format($revenueNew,0,'.',' ') ?> <?php echo CURRENCY_NAME ?></td>
			<td><?php echo  number_format($revenue,0,'.',' ') ?> <?php echo CURRENCY_NAME ?></td>
		</tr>
		<tr>
			<td>Totala kostnader inkl. provision</td>
			<td><?php echo  number_format($costExt,0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
			<td><?php echo  number_format($costNew,0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
			<td><?php echo  number_format($cost,0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
		</tr>
		<tr>
			<td>Total TB</td>
			<td><?php echo  number_format($coverageExt = $revenueExt - $costExt,0,'.',' '); ?><?php echo CURRENCY_NAME ?></td>
			<td><?php echo  number_format($coverageNew = $revenueNew - $costNew,0,'.',' '); ?><?php echo CURRENCY_NAME ?></td>
			<td><?php echo number_format($coverage = $revenue - $cost,0,'.',' '); ?><?php echo CURRENCY_NAME ?></td>
		</tr>
		<tr>
			<td>Bruttomarginal</td>
			<td><?php echo ($revenueExt > 0) ? number_format($coverageExt / $revenueExt * 100, 0) . "%" : "-"; ?></td>
			<td><?php echo ($revenueNew > 0) ? number_format($coverageNew / $revenueNew * 100, 0) . "%" : "-"; ?></td>
			<td><?php echo ($revenue > 0) ? number_format($coverage / $revenue * 100, 0). "%" : "-"; ?></td>
		</tr>
	</tbody>
</table>
<?php 
foreach ($orderValueMonthly as $key => $row) { ?>
	<table class="table table-bordered table-striped table-colborder dataTable"  >
		<colgroup>
		   <col width="27%" span="1">
		   <col width="15%" span="3">
		</colgroup>
		<thead>
			<tr>
				<th><?php 
				$date=new DateTime($key);
				echo $date->format("M Y");
				?></th>
				<th>Förlängda</th>
				<th>Nya</th>
				<th>Total</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Totalt ordervärde</td>
				<td><?php echo  number_format($row['monthly_ext_revenue'],0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
				<td><?php echo  number_format($row['monthly_new_revenue'],0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
				<td><?php echo  number_format($row['monthly_revenue'],0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
			</tr>
			<tr>
				<td>Totala kostnader inkl. provision</td>
				<td><?php echo  number_format($row['monthly_ext_cost'],0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
				<td><?php echo  number_format($row['monthly_new_cost'],0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
				<td><?php echo  number_format($row['monthly_cost'],0,'.',' ') ?><?php echo CURRENCY_NAME ?></td>
			</tr>
			<tr>
				<td>Total TB</td>
				<td><?php echo  number_format($coverageExt = $row['monthly_ext_revenue'] - $row['monthly_ext_cost'],0,'.',' '); ?><?php echo CURRENCY_NAME ?></td>
				<td><?php echo  number_format($coverageNew = $row['monthly_new_revenue'] - $row['monthly_new_cost'],0,'.',' '); ?><?php echo CURRENCY_NAME ?></td>
				<td><?php echo  number_format($coverage = $row['monthly_revenue'] - $row['monthly_cost'],0,'.',' '); ?><?php echo CURRENCY_NAME ?></td>
			</tr>
			<tr>
				<td>Bruttomarginal</td>
				<td><?php echo ($row['monthly_ext_revenue'] > 0) ? number_format($coverageExt / $row['monthly_ext_revenue'] * 100, 0) . "%" : "-"; ?></td>
				<td><?php echo ($row['monthly_new_revenue'] > 0) ? number_format($coverageNew / $row['monthly_new_revenue'] * 100, 0) . "%" : "-"; ?></td>
				<td><?php echo ($row['monthly_revenue'] > 0) ? number_format($coverage / $row['monthly_revenue'] * 100, 0) . "%" : "-"; ?></td>
			</tr>
		</tbody>
	</table>
<?php } ?>