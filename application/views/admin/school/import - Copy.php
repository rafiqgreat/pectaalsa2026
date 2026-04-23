  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
      <div class="card card-default color-palette-bo">
        <div class="card-header">
          <div class="d-inline-block">
              <h3 class="card-title"> <i class="fa fa-plus"></i>
              Import School </h3>
          </div>
          <div class="btn-group margin-bottom-20"> 
				<a href="<?= base_url() ?>downloads/sample_file.xlsx" class="btn btn-secondary">Get Sample File</a>
          </div>
          <div class="d-inline-block">
            <a href="<?= base_url('admin/school'); ?>" class="btn btn-success"><i class="fa fa-list"></i>  Schools List</a>
          </div>
        </div>
        <div class="card-body">   
           <!-- For Messages -->
            <?php //$this->load->view('admin/includes/_messages.php') ?>
            <?php echo form_open(base_url('school/import'), 'class="form-horizontal" enctype="multipart/form-data"');  ?> 
              <div class="form-group">                         
                  <label for="import_file" class="col-sm-12 control-label">Select File</label>
                  <input type="file" name="import_file" class="form-control" id="import_file" placeholder="" >
              </div>
              <div class="form-group">
                <div class="col-md-12">
                  <input type="submit" name="submit" value="Add School" class="btn btn-info pull-right">
                </div>
              </div>
            <?php echo form_close( ); ?>
          <!-- /.box-body -->
        </div>
      </div>
    </section> 
  </div>
  <!-- DataTables -->
<script language="javascript" type="text/javascript">
$('#school_country_id').on('change', function() {
      $.post('<?=base_url("admin/school/states_by_country")?>',
    {
      '<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>',
      country_id : this.value
    },
    function(data){
      arr = $.parseJSON(data);     
      console.log(arr);     
      $('#school_state_id option:not(:first)').remove();
      $.each(arr, function(key, value) {           
     $('#school_state_id')
         .append($("<option></option>")
                    .attr("value", value.state_id)
                    .text(value.state_name_en)
                  ); 
        });   
    });
});
$('#school_state_id').on('change', function() {
      $.post('<?=base_url("admin/school/district_by_state")?>',
    {
      '<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>',
      state_id : this.value
    },
    function(data){
      arr = $.parseJSON(data);     
      console.log(arr);     
      $('#school_district_id option:not(:first)').remove();
      $.each(arr, function(key, value) {           
     $('#school_district_id')
         .append($("<option></option>")
                    .attr("value", value.district_id)
                    .text(value.district_name_en)
                  ); 
        });   
    });
});
$('#school_district_id').on('change', function() {
      $.post('<?=base_url("admin/school/tehsil_by_district")?>',
    {
      '<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>',
      district_id : this.value
    },
    function(data){
      arr = $.parseJSON(data);     
      console.log(arr);     
      $('#school_tehsil_id option:not(:first)').remove();
      $.each(arr, function(key, value) {           
     $('#school_tehsil_id')
         .append($("<option></option>")
                    .attr("value", value.tehsil_id)
                    .text(value.tehsil_name_en)
                  ); 
        });   
    });
});

	$('#school_emis').on('keypress', function (event) {
		var regex = new RegExp("^[a-zA-Z0-9]+$");
		var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
		if (!regex.test(key)) {
		   event.preventDefault();
		   return false;
		}
	});
	
	$( '#school_emis' ).on( 'blur', function () {
			$.post( '<?=base_url("admin/school/username_exist")?>', {
					'<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>',
					username: this.value
				},
				function ( data ) {
					if ( data == 1 ) {
						alert( 'Username already exist!' );
						$( '#school_emis' ).select();
					}
				})
		});
	/*
	$('#school_emis').keypress(function( e ) {
    if(e.which === 32) 
        return false;
});
*/
</script>
