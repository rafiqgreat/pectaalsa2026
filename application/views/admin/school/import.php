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
      <h3 class="card-title"> <?php echo 'Import School' ?></h3>
        
      <div class="card-tools pull-right">
        <a href="<?php echo url('admin/school') ?>" class="btn btn-flat btn-default btn-sm"><i class="fa fa-arrow-left"></i> &nbsp;&nbsp;  <?php echo lang('school') ?></a>
      </div>
		<div class="btn-group margin-bottom-20 pr-2" style="float:right"> 
        	<a href="<?= base_url() ?>downloads/sample_file.xlsx" class="btn btn-secondary">Get Sample File</a>
        </div>
    </div>

    <?php echo form_open_multipart('admin/school/import', [ 'class' => 'form-validate', 'enctype'=>"multipart/form-data" ], ); ?>
          <div class="card-body">    
              <div class="form-group">                         
                  <label for="import_file" class="col-sm-12 control-label">Select File</label>
                  <input type="file" name="import_file" class="form-control" id="import_file" placeholder="" >
              </div>
              <div class="form-group">
                <div class="col-md-12">
                  <input type="submit" name="submit" value="Import School" class="btn btn-info pull-right">
                </div>
              </div>
          </div>
    <?php echo form_close(); ?>
  </div>
</section>
<?php include viewPath('admin/includes/footer'); ?>