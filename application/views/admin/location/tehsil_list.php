<?php
defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php include viewPath('admin/includes/header'); ?>


<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><?php echo lang('tehsil_list') ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item active"><?php echo lang('tehsil_list') ?></li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex p-0">
            <h3 class="card-title p-3"><?php echo lang('tehsil_list') ?></h3>
            <div class="ml-auto p-2">
              <?php if (hasPermissions('tehsil_add')): ?>
                <a href="<?php echo url('admin/location/tehsil_add') ?>" class="btn btn-primary btn-sm"><span class="pr-1"><i class="fa fa-plus"></i></span> <?php echo lang('new_tehsil') ?></a>
              <?php endif ?>
            </div>
          </div>

          <!-- /.card-header -->
          <div class="card-body">
            <table id="example1" class="table table-bordered table-hover table-striped">
              <thead>
                <tr>
                  <th>Sr.</th>
                  <th>Code</th>
                  <th>Tehsil Name</th>
                  <th>District</th>
                  <th>State</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 0;
                foreach ($tehsils as $row): ?>
                  <tr>
                    <td><?php print ++$i; ?></td>
                    <td><?php echo $row->tehsil_code ?></td>
                    <td><?php echo $row->tehsil_name_en ?></td>
                    <td><?php echo $row->district_name_en ?></td>
                    <td><?php echo $row->state_name_en ?></td>
                    <td>
                      <input type="checkbox" name="my-checkbox" onchange="updateTehsilStatus('<?php echo $row->tehsil_id ?>', this.checked)" <?php echo ($row->tehsil_status == 1) ? 'checked' : ''; ?> data-bootstrap-switch data-off-color="secondary" data-on-color="success" data-off-text="<?php echo lang('lan_inactive') ?>" data-on-text="<?php echo lang('lan_active') ?>">
                    </td>
                    <td>
                      <?php if (hasPermissions('tehsil_edit')): ?>
                        <a href="<?php echo url('admin/location/tehsil_edit/' . $row->tehsil_id) ?>" class="btn btn-sm btn-default" title="<?php echo lang('tehsil_edit') ?>" data-toggle="tooltip"><i class="fas fa-edit"></i></a>
                      <?php endif ?>
                      <?php if (hasPermissions('tehsil_delete')): ?>
                        <a href="<?php echo url('admin/location/tehsil_delete/' . $row->tehsil_id) ?>" class="btn btn-sm btn-default" onclick='return confirm("Do you really want to delete this tehsil ? \nIt may cause errors where it is currently being used !!")' title="<?php echo lang('tehsil_delete') ?>" data-toggle="tooltip"><i class="fa fa-trash"></i></a>
                      <?php endif ?>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->
  </div>
  <!-- /.container-fluid -->
</section>
<!-- /.content -->



<?php include viewPath('admin/includes/footer'); ?>

<script>
  window.updateTehsilStatus = (tehsil_id, status) => {
    $.get('<?php echo url('location/change_tehsil_status') ?>/' + tehsil_id, {
      tehsil_status: status // Fix: Use correct parameter name
    }, (data, status) => {
      if (data == 'done') {
        // Success response
      } else {
        alert('<?php echo 'Unable to change Tehsil Status' ?>');
      }
    });
  };
</script>