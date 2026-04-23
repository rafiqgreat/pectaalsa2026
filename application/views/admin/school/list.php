<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><?php echo lang('school_list'); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
          <li class="breadcrumb-item active"><?php echo lang('school_list'); ?></li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="card">
    <div class="card-header with-border">
      <h3 class="card-title"><?php echo lang('school_list'); ?></h3>
      <div class="card-tools pull-right">
        <?php if (hasPermissions('school_add')): ?>
          <a href="<?php echo url('admin/school/add'); ?>" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> <?php echo lang('new_school'); ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="dataTable1" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Sr.</th>
              <th>LSA Code</th>
              <th>School Code</th>
              <th>Name</th>
              <th>Department</th>
              <th>District</th>
              <th>Tehsil</th>
              <th><?php echo lang('action'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($schools)): ?>
              <?php $i = 1; ?>
              <?php foreach ($schools as $row): ?>
                <tr>
                  <td><?php echo $i++; ?></td>
                  <td><?php echo htmlspecialchars((string) $row->school_lsacode, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) $row->school_code, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) $row->school_name, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) $row->school_department, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) $row->district_name_en, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) $row->tehsil_name_en, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <a href="<?php echo url('admin/school/view/' . $row->school_id); ?>" class="btn btn-sm btn-default" title="View" data-toggle="tooltip">
                      <i class="far fa-eye"></i>
                    </a>
                    <?php if (hasPermissions('school_edit')): ?>
                      <a href="<?php echo url('admin/school/edit/' . $row->school_id); ?>" class="btn btn-sm btn-default" title="<?php echo lang('school_edit'); ?>" data-toggle="tooltip">
                        <i class="fas fa-edit"></i>
                      </a>
                    <?php endif; ?>
                    <?php if (hasPermissions('school_delete')): ?>
                      <a href="<?php echo url('admin/school/delete/' . $row->school_id); ?>" class="btn btn-sm btn-default" onclick="return confirm('Do you really want to delete this school?')" title="<?php echo lang('school_delete'); ?>" data-toggle="tooltip">
                        <i class="fa fa-trash"></i>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center">No schools found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

<script>
  $('#dataTable1').DataTable({
    pageLength: 25,
    ordering: true,
    order: [[3, 'asc']]
  });
</script>
