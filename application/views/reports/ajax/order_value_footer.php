</div>
        </div>
    </div>
</section>
<script>
 function loadSpecificPeriodData(formObj) {
        var FromMonth = $(this).find("input[name='from_month']").val();
        var ToMonth = $(this).find("input[name='to_month']").val();
		$(".loader").show();
		$(".order-value-monthly").html("");
        if (FromMonth != '' && ToMonth != '')
            $.ajax({
                url: $(formObj).attr("action"),
                type: $(formObj).attr("method"),
                data: $(formObj).serialize(),
                success: function (data) {
					$(".loader").hide();
                    $(".order-value-monthly").html(data);
                    //intializeSlider($(".tab-container").find("div.ui-tabs-panel[aria-hidden=false]"));
                }
            });
        else
            alert("Vänligen ange ett korrekt datum");
        return false;
    }
</script>
