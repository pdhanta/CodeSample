<script type="text/javascript" src="<?php echo base_url('assets/js/Chart.bundle.js'); ?>"></script>
<script type="text/javascript" src="<?php echo base_url('assets/js/tablecolumnslider.js'); ?>"></script>
<!--========Body content start here=====-->
<section class="bodybg">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-sm-8 col-xs-12">
                        <h1 class="mainHeading ">Framtida intäkter och förlängningsprocent </h1>
                        <p>Kundavtal</p>
                      

                    </div><!--.col-sm-8-->
                </div><!-----.row------->

                <hr class="logindark">
                <?php
                $notify_msg = $this->session->flashdata('notify_msg');
                if ($notify_msg) {
                  if ($notify_msg['error'] == 0) {
                    echo '<div  class="alert alert-success fade in"><a href="#" class="close" data-dismiss="alert">&times;</a> ' . $notify_msg['message'] . '</div> ';
                  } else {
                    echo '<div class="alert alert-danger">' . $notify_msg['message'] . '</div> ';
                  }
                }
                ?>
                <div class="">
                    <div class="row">

                        <div class="col-md-12 reports">
                            <div class="tab-container">
                                <ul>
									<li aria-controls="ui-id-22"><a href="<?php echo base_url('reports/reports/future_revenue/?extension_only=all&is_ajax=1'); ?>">Alla avtal</a></li>
                                    <li><a href="<?php echo base_url('reports/reports/future_revenue/?extension_only=1&is_ajax=1'); ?>">Ersättningsavtal</a></li>
                                    <li><a href="<?php echo base_url('reports/reports/future_revenue/?is_ajax=1'); ?>">Nya Avtal</a></li>
                                    

                                </ul>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">

  var barOptions = {
      events: false,
      showTooltips: true,
      tooltips: {
          enabled: true
      },
      layout: {
          padding: {
              left: 50,
              right: 0,
              top: 10,
              bottom: 0
          }
      },

      legend: {
          display: false,
          position: 'right'
      },
      // scaleLineColor: 'transparent',
      scales: {
          yAxes: [{
                  // display: true,
                  stacked: true,
                  ticks: {
                      padding: 10,
                      callback: function (value, index, values) {
                          return number_format(value, 0, ' ') + ' kr';
                      },
                      fontSize: 14,
                      fontColor: "black"
                  },
                  gridLines: {
                      tickMarkLength: 155,
                      drawBorder: false,

                  },
              }],
          xAxes: [{
                  scaleFontSize: 30,
                  stacked: true,
                  ticks: {
                      fontColor: "black",
                      fontSize: 14,
                      fontStyle: "bold",
                      stepSize: 2,
                      beginAtZero: true,

                  },
                  gridLines: {
                      display: false,
                      lineWidth: 0
                              //color: "rgba(0, 0, 0, 0)",
                  }
              }]
      },
      maintainAspectRatio: false,
	  
	  "animation": {
          "duration": 500,
          "onComplete": function () {
			   var width=0;
			   var prevX=0;
			   var isBarVisible=false;
              var chartInstance = this.chart;
              this.data.datasets.forEach(function (dataset, i) {
                  var meta = chartInstance.controller.getDatasetMeta(i);
                  meta.data.forEach(function (bar, index) {
					 if((dataset.data[index])>0){
						isBarVisible=true; 
					 }
				  if(prevX>0 && width<1){
					  width=bar._model.x-prevX;
				  }
					  
				  if(prevX<1){
					  prevX=bar._model.x;
				  }
					if(width>0){
						$(".tdVisible,.tdHidden").css("width",width+"px");
					}
					  
                  });
				  
				  if(!isBarVisible){
					  $(".row-heading").attr("style","width:250px!important;");
				  }else{
					   $(".row-heading").attr("style","width:275px!important;");
				  }
              });
			  $(".table-slider-container").slideDown();
          }
      }
  }
  function loadFutureRevenue(startYear, endYear, url, thisObj) {
      var data = {};
      $.ajax({
          type: 'post',
          url: url,
          data: {start: startYear, end: endYear},
          dataType: 'json',
          success: function (dataPoints) {
              var future_revenue_grpah = thisObj.closest(".graph-and-canvas").find(".future_revenue_grpah")[0].getContext("2d");
              drawChart(dataPoints, future_revenue_grpah);
          }
      });
  }
  function walkInJson(obj) {
      for (var key in obj) {
          if (obj.hasOwnProperty(key)) {
              var val = obj[key];
          }
      }
  }
  function drawChart(dataPoints, chartObject) {
      var myBarChart = new Chart(chartObject, {
          type: 'bar',
          data: dataPoints,
          "options": barOptions
      });
      $(".chart-legend").html(myBarChart.generateLegend());
  }
  $(function () {
      $(".tab-container").tabs({
          beforeLoad: function (event, ui) {
			  ui.panel.html('<img align="center" style="width:150px;display:block;margin:0 auto" src="<?php echo base_url("assets/images/loader.gif");?>" />');
              ui.jqXHR.fail(function () {
                  ui.panel.html(
                          "Ett fel uppstog! Vänligen försök igen");
              });
          }
      });
  });
  function loadSpecificPeriodData(formObj) {
      var FromMonth = $(this).find("input[name='from_month']").val();
      var ToMonth = $(this).find("input[name='to_month']").val();
      if (FromMonth != '' && ToMonth != '')
          $.ajax({
              url: $(formObj).attr("action"),
              type: $(formObj).attr("method"),
              data: $(formObj).serialize(),
              success: function (data) {
                  $(".tab-container").find("div.ui-tabs-panel[aria-hidden=false]").find(".container-for-ajax").html(data);
                  intializeSlider($(".tab-container").find("div.ui-tabs-panel[aria-hidden=false]"));
              }
          });
      else
          alert("Vänligen ange ett giltigt datum");
      return false;
  }
  function intializeSlider(obj) {


     obj.find(".myTableSlider").tableSlider({
          visibleClass: "tdVisible",
          hiddenClass: "tdHidden",
          numberOfColumn: 7,
          intialSlide: 1,
          steps: 7,
          slideOnChange: function (startRow, endRow) {

              loadFutureRevenue(startRow.attr("data-year"), endRow.attr("data-year"), "<?php echo base_url('reports/reports/load_future_revenue_graph_data_ajax?extension_only='); ?>" + obj.find(".extension_only").val(), startRow);
          }
      });
  }
  var number_format = function (number, decimals = 2, thousand_sep = ',') {
      var re = '\\d(?=(\\d{' + (3) + '})+' + (decimals > 0 ? '\\.' : '$') + ')';
      return number.toFixed(decimals).replace(new RegExp(re, 'g'), '$&' + thousand_sep);
  };
  
  
  
  var totalIntaker=0;
  var totalKostander=0;
  var resultant=0;
  var marginal=0;
  
function process(input) {
    var n = 0;
    return input.replace(/\./g, function() { return n++ > 0 ? '' : '.'; });
}
   $(document).ready(function(){
    
    $(document).on("change",".annual_additional_cost,.other_sales,.cost_other_sales",function(){
		$(this).val($(this).val().replace(/[^0-9\.]/g, ''));
       $(this).val(number_format(Number(process($(this).val().replace(/[^0-9\.]/g, ''))),0,' '));	
		updateValues();
		
     
      
    });
	
  })
  function updateValues(){
	   
      totalIntaker=0;
      var c=0;
      $(".annual_additional_cost_row").find("input").each(function(){
		  //console.log($(this).val().replace(" ",""));
        totalIntaker+= Number($(this).val().split(" ").join(""));
        c++;
      });
	  if(totalIntaker>0){
		//totalIntaker=totalIntaker/c;
	  }
      
      totalKostander=0;
      var c=0;
      $(".other_sales_row").find("input").each(function(){
        totalKostander+= Number($(this).val().split(" ").join(""));
        c++;
      });
	  if(totalKostander>0){
		//totalKostander=totalKostander/c;
	  }
      
      resultant=0;
      var c=0;
      $(".cost_other_sales_row").find("input").each(function(){
        resultant+= Number($(this).val().split(" ").join(""));
        c++;
      });
	  if(resultant>0){
		//resultant=resultant/c;
	  }
	  console.log(totalIntaker);
     var totalIntakerFinal=Number($(".total-order-value").attr("data-value"))+totalKostander;
     var totalKontanderFinal=Number($(".total-cost-value").attr("data-value"))+totalIntaker+resultant;
      $(".total-intaker-td").html( number_format(totalIntakerFinal, 0, ' ') + ' kr');
     $(".total-kostander-td").html(number_format(totalKontanderFinal, 0, ' ') + ' kr');
     $(".total-resultant-td").html(number_format(totalIntakerFinal-totalKontanderFinal, 0, ' ') + ' kr');
     $(".total-marginal-td").html(((totalIntakerFinal-totalKontanderFinal)/totalIntakerFinal*100).toFixed(0)+"%");
     $.ajax({
		 type:'post',
		 url:'<?php echo base_url('reports/reports/update_table_data');?>',
		 data:$("#form-table-data").serialize(),
		 success:function(){
		 }
	 });
console.log("yupdated");	 
  }
  

</script>

