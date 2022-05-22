<div class="form-box">
    <form name="frm-ajax-search-stat" class="frm-static-search" method="post" action="<?php echo base_url("reports/reports/specific_period?extension_only=". $is_extension_value); ?>" onsubmit ="return loadSpecificPeriodData(this);">
        <input type="hidden" id="extension_only" class="extension_only" name="extension_only" value ="<?php echo $is_extension_value; ?>" />

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
            </div>

        </div>
        <div class="revenue-Percentage">
			<div class="revenueactualP">
				<label for="percentage-box" class="form-label precentbox-label">Ange förlängningsprocent:  </label>
				<span class="desc">
					<span data-toggle="tooltip" data-html="true" title="<em>Calculation</em> <b>Total Extended Contracts ( minimum 1 year longer) /Total Canceled Contracts ( minimum 1 year longer)  X 100</b>">(Faktisk förlängningsprocent-A: <strong><?php echo $calcPercentA; ?>%</strong>)</span>
				</span>
				<!--<span class="desc desc-type-B" style="top:55px;">
					<span data-toggle="tooltip" data-html="true" title="<em>Calculation</em><br> <b><u>Sum_of_first_month_revenue_of_total_extended_contracts  X 100 </u><br>Sum of first month revenue of total canceled contracts</b>"> (Faktisk förlängningsprocent-B: <strong><?php echo $calcPercentB; ?>%</strong>)</span>
				</span>-->
			</div>
			<div class="revenuePbox">
				<input type="text"  class="form-control percent-box " id="percentage-box" placeholder="X" name="percentage_box" />
				<span class="percent-span">%</span>
			</div>

        </div>
        <div class="revenue-extnsn">
            <div class="col-xs-8 col-sm-5 col-md-2">
                <label for="percentage-box" class="form-label label btn-block">Ange förlängning</label>
                <select name="extension_type" class="loaded-ajax-select">
                    <option value="automatic">Automatisk</option>
                    <option value="normal">Normal</option>
                </select>
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
        intializeSlider($(".tab-container").find("div.ui-tabs-panel[aria-hidden=false]"));
    })
	$(function () {
	  $('[data-toggle="tooltip"]').tooltip()
	})
</script>
