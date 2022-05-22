<div class="graph-container">
    <?php if ($doLoadForm): ?>
      <?php $this->load->view('reports/reports/ajax/search-form-future-revenue', ['is_extension_value' => $is_extension_value, 'calcPercentA' => $calcPercentA, 'calcPercentB' => $calcPercentB]); ?>
    <?php endif; ?>
    <div class="container-for-ajax">
        <div class="graph-and-canvas">
            <div id="js-legend" class="chart-legend"></div>
            <div class="whitebgDiv">
                <canvas class="future_revenue_grpah" width="100%" height="400px"></canvas>
            </div>
            <?php $unique = rand(100, 5000); ?>
			<form name="frm-table-data" id="form-table-data" action="">
            <table class="myTableSlider table table-condensed table-bordered table-striped dataTable no-footer div-table" cellspacing="0" width="100%" aria-describedby="table_info" role="grid" style="width: 100%;">
                <tr>
                    <th class="row-heading" width="255"></th>

                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <th data-year="<?php echo $year; ?>"><?php echo $year; ?></th>
                    <?php endfor; ?>

                </tr>
                <tr class="clear"></tr>
                <tr>
                    <?php $overAllRevenue = 0; ?>
                    <td class="row-heading">Avtal: totalt Intäkter  </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <?php $overAllRevenue += (isset($tableData[$year]['yearly_revenue'])) ? $tableData[$year]['yearly_revenue'] : 0; ?>
                      <td><?php echo number_format(((isset($tableData[$year]['yearly_revenue'])) ? $tableData[$year]['yearly_revenue'] : 0), 0, '.', ' ') ?> kr</td>
                    <?php endfor; ?>

                </tr>
				<tr class="small">
                    <td class="row-heading">Totalt Avskrivningar  </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <td><?php echo number_format(((isset($tableData[$year]['others'])) ? $tableData[$year]['others'] : 0), 0, '.', ' ') ?> kr</td>
                    <?php endfor; ?>
                </tr>
				<tr class="small">
                    <td class="row-heading">Totalt LC  </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <td><?php echo number_format(((isset($tableData[$year]['lc'])) ? $tableData[$year]['lc'] : 0), 0, '.', ' ') ?> kr</td>
                    <?php endfor; ?>
                </tr>
				<tr class="small">
                    <td class="row-heading">Totalt SIM  </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <td><?php echo number_format(((isset($tableData[$year]['sim'])) ? $tableData[$year]['sim'] : 0), 0, '.', ' ') ?> kr</td>
                    <?php endfor; ?>
                </tr>
				
                <tr class="clear"></tr>
                <tr>
                    <?php $overAllCost = 0; ?>
                    <td class="row-heading">Avtal: totala kostnader </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <?php $overAllCost += (isset($tableData[$year]['yearly_cost'])) ? $tableData[$year]['yearly_cost'] : 0; ?>
                      <td><?php echo number_format(((isset($tableData[$year]['yearly_cost'])) ? $tableData[$year]['yearly_cost'] : 0), 0, '.', ' '); ?> kr</td>
                    <?php endfor; ?>

                </tr>
                <tr class="clear"></tr>
                <tr>
                    <td class="row-heading">Avtal: Totala TB </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <td><?php echo number_format(((isset($tableData[$year]['yearly_cost'])) ? $tableData[$year]['yearly_revenue'] - $tableData[$year]['yearly_cost'] : 0), 0, '.', ' '); ?> kr</td>
                    <?php endfor; ?>

                </tr>
                <tr class="clear"></tr>
                <tr>
                    <td class="row-heading">Avtal: bruttomarginal </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <td><?php echo (isset($tableData[$year]['yearly_cost']) && isset($tableData[$year]['yearly_revenue']) && $tableData[$year]['yearly_revenue'] > 0) ? round((($tableData[$year]['yearly_revenue'] - $tableData[$year]['yearly_cost']) / $tableData[$year]['yearly_revenue']) * 100) : 0 ?> %</td>
                    <?php endfor; ?>

                </tr>
                <tr class="clear"></tr>
                <tr class="clear"></tr>
                <tr class="clear"></tr>
                <tr class="addtional_inputs annual_additional_cost_row" style="margin-top:100px">
                    <td class="row-heading">Årlig övrig kostnad </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <td><input type="text" name="annual_additional_cost[<?php echo $year; ?>]" class="form-control annual_additional_cost" value="<?php echo isset($addtional_future_data['annual_additional_cost'][$year])?$addtional_future_data['annual_additional_cost'][$year]:'';?>" /> Kr</td>
                    <?php endfor; ?>
                </tr>
                <tr class="addtional_inputs other_sales_row">
                    <td class="row-heading">Övrig försäljning </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <td><input type="text" name="other_sales[<?php echo $year; ?>]" value="<?php echo isset($addtional_future_data['other_sales'][$year])?$addtional_future_data['other_sales'][$year]:'';?>" class="form-control other_sales" /> Kr</td>
                    <?php endfor; ?>

                </tr>
                <tr class="addtional_inputs cost_other_sales_row">
                    <td class="row-heading">Kostnader övrig försäljning </td>
                    <?php for ($year = $from; $year <= $to; $year++): ?>
                      <td><input type="text" name="cost_other_sales[<?php echo $year; ?>]" value="<?php echo isset($addtional_future_data['cost_other_sales'][$year])?$addtional_future_data['cost_other_sales'][$year]:'';?>" class="form-control cost_other_sales" /> Kr</td>
                    <?php endfor; ?>

                </tr>

            </table>
			</form>
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <table class="total-table table-condensed table-bordered table-striped dataTable no-footer">
                        <tr>
                            <th width="255"></th>
                            <th>Totalen</th>
                        </tr>
                        <tr>
                            <td width="255">Avtal: totalt ordervärde</td>
                            <td class="total-order-value" data-value="<?php echo $overAllRevenue; ?>"><?php echo number_format($overAllRevenue, 0, '.', ' '); ?> kr</td>
                        </tr>
                        <tr>
                            <td width="255">Avtal: totala kostnader</td>
                            <td  class="total-cost-value" data-value="<?php echo $overAllCost; ?>"><?php echo number_format($overAllCost, 0, '.', ' '); ?> kr</td>
                        </tr>
                        <tr>
                            <td width="255">Avtal: Totala TB</td>
                            <td  class="total-TB" data-value="<?php echo ($overAllRevenue - $overAllCost); ?>" > <?php echo number_format(($overAllRevenue - $overAllCost), 0, '.', ' '); ?> kr</td>
                        </tr>
                        <tr>
                            <td width="255">Avtal: bruttomarginal</td>
                            <td><?php echo round(($overAllRevenue - $overAllCost) / $overAllRevenue * 100); ?>%</td>

                        </tr>
                    </table>
                </div>
                <div class="col-md-6 col-sm-12">
                    <table class="total-table table-condensed table-bordered table-striped dataTable no-footer">
                        <tr>
                            <th width="255"></th>
                            <th>Totalen</th>
                        </tr>
                        <tr>
                            <td width="255">Totala intäkter</td>
                            <td class="total-intaker-td"><?php echo number_format($overAllRevenue, 0, '.', ' '); ?> kr</td>
                        </tr>
                        <tr>
                            <td width="255">Totala kostnader</td>
                            <td class="total-kostander-td"><?php echo number_format($overAllCost, 0, '.', ' '); ?> kr</td>
                        </tr>
                        <tr>
                            <td width="255">Resultat</td>
                            <td class="total-resultant-td"><?php echo number_format(($overAllRevenue - $overAllCost), 0, '.', ' '); ?> kr</td>
                        </tr>
                        <tr>
                            <td width="255">Marginal</td>
                            <td class="total-marginal-td"><?php echo round(($overAllRevenue - $overAllCost) / $overAllRevenue * 100); ?>%</td>

                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <style>
            .chart-legend li span{
                display: inline-block;
                width: 12px;
                height: 12px;
                margin-right: 5px;
                margin-bottom:0px;
            }
            .addtional_inputs .form-control{
                width:75%!important;
                display:inline!important;
            }

            @media screen and (min-width: 1199px) {

                .div-table tr,.div-table td,.div-table th{
                    display:block;
                }


                .div-table .tdHidden{
                    display:none;
                }
                .clear{
                    clear:both;
                }
                .div-table td,.div-table th{
                    float:left;
                    text-align:center;
                }
                .row-heading{
                    width:275px!important;
                    text-align:left!important;
                }
                .tdVisible{

                    padding: 8px 0px !important;
                    white-space:nowrap;
                }

            }
            .table-slider-container{
                display:none;
            }
		.small td{
			padding-top:2px!important;
			padding-bottom:2px!important;
			font-style:italic;
		}
        </style>
    </div>
</div>
<script type="text/javascript">
updateValues();
</script>
