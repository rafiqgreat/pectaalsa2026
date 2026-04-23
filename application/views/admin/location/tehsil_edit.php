<?php
defined('BASEPATH') OR exit('No direct script access allowed');?>

<?php include viewPath('admin/includes/header'); ?>
<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><?php echo lang('tehsil_edit') ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/tehsil') ?>"> <?php echo lang('tehsil_list') ?></a></li>
          <li class="breadcrumb-item active"> <?php echo lang('tehsil_edit') ?></li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
  <!-- Default card -->
  <div class="card">
    <div class="card-header with-border">
      <h3 class="card-title"> <?php echo lang('tehsil_edit') ?></h3>

      <div class="card-tools pull-right">
        <a href="<?php echo url('admin/tehsil') ?>" class="btn btn-flat btn-default btn-sm"><i class="fa fa-arrow-left"></i> &nbsp;&nbsp;  <?php echo lang('tehsil') ?></a>
      </div>
    </div>

    <?php echo form_open_multipart('admin/location/tehsil_update/' . $tehsil->tehsil_id, [ 'class' => 'form-validate' ], ); ?>
    <div class="card-body">
        <div class="row form-group">
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="tehsil_name_en"> <?php echo lang('tehsil_name') ?></label>
                <input type="text" class="form-control" name="tehsil_name_en" id="tehsil_name_en" required value="<?php echo $tehsil->tehsil_name_en; ?>" />
            </div>
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="tehsil_code"> <?php echo lang('tehsil_code') ?></label>
                <input type="text" class="form-control" name="tehsil_code" id="tehsil_code" required value="<?php echo $tehsil->tehsil_code; ?>" />
            </div>
        </div>
        <div class="row form-group">
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="tehsil_order"> <?php echo lang('tehsil_order') ?></label>
                <input type="text" class="form-control" name="tehsil_order" id="tehsil_order" required value="<?php echo $tehsil->tehsil_order; ?>" />
            </div>
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="tehsil_state_id"> <?php echo lang('state_name') ?></label>
                <select class="form-control" name="tehsil_state_id" id="tehsil_state_id" required>
                    <option value="">Select State</option>
                    <?php foreach($states as $state) { ?>
                        <option value="<?php echo $state->state_id; ?>" <?php echo ($state->state_id == $tehsil->tehsil_state_id) ? 'selected' : ''; ?>>
                            <?php echo $state->state_name_en; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="row form-group">
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="tehsil_district_id"> <?php echo lang('district_name') ?></label>
                <select class="form-control" name="tehsil_district_id" id="tehsil_district_id" required>
                    <option value="">Select District</option>
                    <?php
					foreach ($districts as $district) {
						$selectedText = '';
						if ($tehsil->tehsil_district_id == $district->district_id) { // Use object notation if $tehsil is an object
							$selectedText = ' selected="selected" ';
						}
						echo '<option value="'.$district->district_id.'" '.$selectedText.'>'.$district->district_name_en.'</option>';
					}
				  ?>
                </select>
            </div>
        </div>
    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <div class="row">
        <div class="col"><a href="<?php echo url('admin/location/tehsil') ?>" onclick="return confirm('Are you sure you want to leave?')" class="btn btn-flat btn-danger"> <?php echo lang('cancel') ?></a></div>
        <div class="col text-right"><button type="submit" class="btn btn-flat btn-primary"> <?php echo lang('submit') ?></button></div>
      </div>
    </div>
    <!-- /.card-footer-->

    <?php echo form_close(); ?>
  </div>
  <!-- /.card -->
</section>
<!-- /.content -->
<script type="text/javascript">
$('#tehsil_state_id').on('change', function() {
      $.post('<?=base_url("admin/location/distirct_by_state")?>',
    {
      '<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>',
      state_id : this.value
    },
    function(data){
      arr = $.parseJSON(data);     
      console.log(arr);     
      $('#tehsil_district_id option:not(:first)').remove();
      $.each(arr, function(key, value) {           
     $('#tehsil_district_id')
         .append($("<option></option>")
                    .attr("value", value.district_id)
                    .text(value.district_name_en)
                  ); 
        });   
    });
});

</script>
<?php include viewPath('admin/includes/footer'); ?>
