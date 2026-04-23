<?php
defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php include viewPath('admin/includes/header'); ?>
<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><?php echo lang('district') ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/district') ?>"> <?php echo lang('district_list') ?></a></li>
          <li class="breadcrumb-item active"> <?php echo lang('district_add') ?></li>
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
      <h3 class="card-title"> <?php echo lang('district_add') ?></h3>

      <div class="card-tools pull-right">
        <a href="<?php echo url('admin/district') ?>" class="btn btn-flat btn-default btn-sm"><i class="fa fa-arrow-left"></i> &nbsp;&nbsp;  <?php echo lang('district') ?></a>
      </div>

    </div>

    <?php echo form_open_multipart('admin/location/save_district', [ 'class' => 'form-validate' ], ); ?>
    <div class="card-body">
        <div class="row form-group">
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="district_name_en"> <?php echo lang('district_name') ?></label>
                <input type="text" class="form-control" name="district_name_en" id="district_name_en" required placeholder="Enter District Name" autofocus />
            </div>
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="district_code"> <?php echo lang('district_code') ?></label>
        		<input type="text" class="form-control" name="district_code" id="district_code" required placeholder="Enter District Code" autofocus />
            </div>
        </div>
        <div class="row form-group">
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="district_order"> <?php echo lang('district_order') ?></label>
        		<input type="text" class="form-control" name="district_order" id="district_order" required placeholder="Enter District Order" autofocus />
            </div>
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="district_state_id"> <?php echo lang('state_name') ?></label>
        		<select class="form-control" name="district_state_id" id="district_state_id" required>
                    <option value="">Select State</option>
                    <?php
					   foreach($states as $state)
					  {
						echo '<option value="'.$state->state_id.'">'.$state->state_name_en.'</option>';
					  }
                  	?>
                </select>
            </div>
        </div>
        
    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <div class="row">
        <div class="col"><a href="<?php echo url('admin/location/district') ?>" onclick="return confirm('Are you sure you want to leave?')" class="btn btn-flat btn-danger"> <?php echo lang('cancel') ?></a></div>
        <div class="col text-right"><button type="submit" class="btn btn-flat btn-primary"> <?php echo lang('submit') ?></button></div>
      </div>
    </div>
    <!-- /.card-footer-->

    <?php echo form_close(); ?>

  </div>
  <!-- /.card -->

</section>
<!-- /.content -->



<?php include viewPath('admin/includes/footer'); ?>