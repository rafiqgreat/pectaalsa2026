<?php
defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php include viewPath('admin/includes/header'); ?>

<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><?php echo lang('state') ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/state') ?>"> <?php echo lang('state_list') ?></a></li>
          <li class="breadcrumb-item active"> <?php echo lang('state_edit') ?></li>
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
      <h3 class="card-title"> <?php echo lang('state_edit') ?></h3>

      <div class="card-tools pull-right">
        <a href="<?php echo url('admin/state') ?>" class="btn btn-flat btn-default btn-sm"><i class="fa fa-arrow-left"></i> &nbsp;&nbsp;  <?php echo lang('state') ?></a>
      </div>

    </div>

    <?php echo form_open_multipart('admin/location/state_update/'.$state->state_id, [ 'class' => 'form-validate' ]); ?>
    <div class="card-body">
        <div class="row form-group">
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="state_name_en"> <?php echo lang('state_name') ?></label>
                <input type="text" class="form-control" name="state_name_en" id="state_name_en" required placeholder="Enter State Name" value="<?php echo $state->state_name_en ?>" autofocus />
            </div>
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="state_code"> <?php echo lang('state_code') ?></label>
                <input type="text" class="form-control" name="state_code" id="state_code" required placeholder="Enter State Code" value="<?php echo $state->state_code ?>" />
            </div>
        </div>
        <div class="row form-group">
            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <label for="state_order"> <?php echo lang('state_order') ?></label>
                <input type="number" class="form-control" name="state_order" id="state_order" required placeholder="Enter State Order" value="<?php echo $state->state_order ?>" />
            </div>
        </div>
    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <div class="row">
        <div class="col"><a href="<?php echo url('admin/location') ?>" onclick="return confirm('Are you sure you want to leave?')" class="btn btn-flat btn-danger"> <?php echo lang('cancel') ?></a></div>
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