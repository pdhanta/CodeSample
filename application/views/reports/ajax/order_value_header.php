<section class="bodybg reports">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-sm-8 col-xs-12">
                        <h1 class="mainHeading ">Ordervärde </h1>
                        <p>Kundavtal</p>
                    </div>
                    <!--.col-sm-8-->
                </div>
                <!-----.row------->
                <hr class="logindark"> 
				<div class="whitebgDiv">
				<?php if ($doLoadForm): ?>
					<?php $this->load->view('reports/reports/ajax/order_value_search_form',['is_extension_value'=>$is_extension_value]); ?>
				<?php endif; ?>
                             