<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>School Details</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/school'); ?>"><?php echo lang('school_list'); ?></a></li>
          <li class="breadcrumb-item active">View</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="card">
    <div class="card-header with-border">
      <h3 class="card-title"><?php echo htmlspecialchars((string) $school->school_name, ENT_QUOTES, 'UTF-8'); ?></h3>
      <div class="card-tools pull-right">
        <?php if (hasPermissions('school_edit')): ?>
          <a href="<?php echo url('admin/school/edit/' . $school->school_id); ?>" class="btn btn-primary btn-sm">
            <i class="fa fa-edit"></i> Edit
          </a>
        <?php endif; ?>
        <a href="<?php echo url('admin/school'); ?>" class="btn btn-default btn-sm">
          <i class="fa fa-arrow-left"></i> Back
        </a>
      </div>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <table class="table table-bordered">
            <tr><th>School ID</th><td><?php echo (int) $school->school_id; ?></td></tr>
            <tr><th>Username</th><td><?php echo htmlspecialchars((string) $school->username, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Department</th><td><?php echo htmlspecialchars((string) $school->school_department, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>School Code</th><td><?php echo htmlspecialchars((string) $school->school_code, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>LSA Code</th><td><?php echo htmlspecialchars((string) $school->school_lsacode, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Name</th><td><?php echo htmlspecialchars((string) $school->school_name, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Address</th><td><?php echo htmlspecialchars((string) $school->school_address, ENT_QUOTES, 'UTF-8'); ?></td></tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table table-bordered">
            <tr><th>State</th><td><?php echo !empty($state) ? htmlspecialchars((string) $state->state_name_en, ENT_QUOTES, 'UTF-8') : ''; ?></td></tr>
            <tr><th>District</th><td><?php echo !empty($district) ? htmlspecialchars((string) $district->district_name_en, ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) $school->school_district, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Tehsil</th><td><?php echo !empty($tehsil) ? htmlspecialchars((string) $tehsil->tehsil_name_en, ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) $school->school_tehsil, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Level</th><td><?php echo htmlspecialchars((string) $school->school_level, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Gender</th><td><?php echo htmlspecialchars((string) $school->school_gender, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Area</th><td><?php echo htmlspecialchars((string) $school->school_area, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Status</th><td><?php echo ((int) $school->school_status === 1) ? 'Active' : 'Inactive'; ?></td></tr>
          </table>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <table class="table table-bordered">
            <tr><th>Grade</th><td><?php echo (int) $school->school_grade; ?></td></tr>
            <tr><th>Students</th><td><?php echo ($school->school_students !== null && $school->school_students !== '') ? (int) $school->school_students : ''; ?></td></tr>
            <tr><th>Created</th><td><?php echo htmlspecialchars((string) $school->school_created, ENT_QUOTES, 'UTF-8'); ?></td></tr>
            <tr><th>Created By</th><td><?php echo htmlspecialchars((string) $school->school_createdby, ENT_QUOTES, 'UTF-8'); ?></td></tr>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>
