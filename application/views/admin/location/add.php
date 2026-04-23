<?php
defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php include viewPath('admin/includes/header'); ?>
<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><?php echo lang('school') ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/school') ?>"> <?php echo lang('school_list') ?></a></li>
          <li class="breadcrumb-item active"> <?php echo lang('school_add') ?></li>
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
      <h3 class="card-title"> <?php echo lang('school_add') ?></h3>

      <div class="card-tools pull-right">
        <a href="<?php echo url('admin/school') ?>" class="btn btn-flat btn-default btn-sm"><i class="fa fa-arrow-left"></i> &nbsp;&nbsp;  <?php echo lang('school') ?></a>
      </div>

    </div>

    <?php echo form_open_multipart('admin/school/save', [ 'class' => 'form-validate' ], ); ?>
    <div class="card-body">
        <div class="row form-group">
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_name"> <?php echo lang('school_name') ?></label>
                <input type="text" class="form-control" name="school_name" id="school_name" required placeholder="Enter Name" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_password"> <?php echo lang('school_password') ?></label>
        		<input type="password" class="form-control" data-rule-remote="<?php echo url('permissions/checkIfUnique') ?>" name="password" id="password" required placeholder="Enter Password" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_department"> <?php echo lang('school_department') ?></label>
                <input type="text" class="form-control" name="school_department" id="school_department" required placeholder="Enter School Department" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_type"> <?php echo lang('school_type') ?></label>
        		<input type="text" class="form-control" name="school_type" id="school_type" required placeholder="Enter School Type" autofocus />
            </div>
        </div>
        <div class="row form-group">
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_state_id"> <?php echo lang('school_state') ?></label>
                <input type="text" class="form-control" name="school_state_id" id="school_state_id" required placeholder="Enter School State" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_district_id"> <?php echo lang('school_district') ?></label>
        		<input type="text" class="form-control" name="school_district_id" id="school_district_id" required placeholder="Enter School District" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_tehsil_id"> <?php echo lang('school_tehsil') ?></label>
                <input type="text" class="form-control" name="school_tehsil_id" id="school_tehsil_id" required placeholder="Enter School Tehsil" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_address"> <?php echo lang('school_address') ?></label>
        		<input type="text" class="form-control" name="school_address" id="school_address" required placeholder="Enter School Address" autofocus />
            </div>
        </div>
        <div class="row form-group">
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_uc"> <?php echo lang('school_uc') ?></label>
                <input type="text" class="form-control" name="school_uc" id="school_uc" required placeholder="Enter School UC" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_uc_name"> <?php echo lang('school_uc_name') ?></label>
        		<input type="text" class="form-control" name="school_uc_name" id="school_uc_name" required placeholder="Enter School UC Name" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_level"> <?php echo lang('school_level') ?></label>
                <input type="text" class="form-control" name="school_level" id="school_level" required placeholder="Enter School Level" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_gender"> <?php echo lang('school_gender') ?></label>
        		<input type="text" class="form-control" name="school_gender" id="school_gender" required placeholder="Enter School Gender" autofocus />
            </div>
        </div>
        
        <div class="row form-group">
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_email"> <?php echo lang('school_email') ?></label>
                <input type="text" class="form-control" name="school_email" id="school_email" required placeholder="Enter School Email" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_phone"> <?php echo lang('school_phone') ?></label>
        		<input type="text" class="form-control" name="school_phone" id="school_phone" required placeholder="Enter School Phone No." autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_contact_name"> <?php echo lang('school_contact_name') ?></label>
                <input type="text" class="form-control" name="school_contact_name" id="school_contact_name" required placeholder="Enter School Contact Name" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_contact_mobile"> <?php echo lang('school_contact_mobile') ?></label>
        		<input type="text" class="form-control" name="school_contact_mobile" id="school_contact_mobile" required placeholder="Enter School Contact Mobile" autofocus />
            </div>
        </div>
        
        <div class="row form-group">
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_status"> <?php echo lang('school_status') ?></label>
                <input type="text" class="form-control" name="school_status" id="school_status" required placeholder="Enter School Status" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_is_verify"> <?php echo lang('school_is_verify') ?></label>
        		<input type="text" class="form-control" name="school_is_verify" id="school_is_verify" required placeholder="School Verify/Not" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_chain_id"> <?php echo lang('school_chain_id') ?></label>
        		<input type="text" class="form-control" name="school_chain_id" id="school_chain_id" required placeholder="Enter School Chain" autofocus />
            </div>
            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-xs-12">
                <label for="school_logo"> <?php echo lang('school_logo') ?></label>
                <input type="text" class="form-control" name="school_logo" id="school_logo" required placeholder="Enter School Logo" autofocus />
            </div>
        </div>
    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <div class="row">
        <div class="col"><a href="<?php echo url('/admin/school') ?>" onclick="return confirm('Are you sure you want to leave?')" class="btn btn-flat btn-danger"> <?php echo lang('cancel') ?></a></div>
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