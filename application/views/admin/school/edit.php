<?php
defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php include viewPath('admin/includes/header'); ?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><?php echo lang('school') ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/school') ?>"><?php echo lang('school_list') ?></a></li>
          <li class="breadcrumb-item active"><?php echo lang('school_edit') ?></li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="card">
    <div class="card-header with-border">
      <h3 class="card-title"><?php echo lang('school_edit') ?></h3>
      <div class="card-tools pull-right">
        <a href="<?php echo url('admin/school') ?>" class="btn btn-flat btn-default btn-sm"><i class="fa fa-arrow-left"></i> <?php echo lang('school') ?></a>
      </div>
    </div>

    <?php echo form_open('admin/school/school_update/' . $school->school_id, ['class' => 'form-validate']); ?>
    <div class="card-body">
      <div class="row form-group">
        <div class="col-md-3">
          <label for="username">Username</label>
          <input type="text" class="form-control" name="username" id="username" value="<?php echo htmlspecialchars($school->username); ?>" required>
        </div>
        <div class="col-md-3">
          <label for="password">Password</label>
          <input type="password" class="form-control" name="password" id="password" placeholder="Leave blank to keep current">
        </div>
        <div class="col-md-3">
          <label for="school_department">Department</label>
          <input type="text" class="form-control" name="school_department" id="school_department" value="<?php echo htmlspecialchars((string) $school->school_department); ?>">
        </div>
        <div class="col-md-3">
          <label for="school_name">School Name</label>
          <input type="text" class="form-control" name="school_name" id="school_name" value="<?php echo htmlspecialchars((string) $school->school_name); ?>" required>
        </div>
      </div>

      <div class="row form-group">
        <div class="col-md-3">
          <label for="school_code">School Code</label>
          <input type="text" class="form-control" name="school_code" id="school_code" value="<?php echo htmlspecialchars((string) $school->school_code); ?>">
        </div>
        <div class="col-md-3">
          <label for="school_lsacode">LSA Code</label>
          <input type="text" class="form-control" name="school_lsacode" id="school_lsacode" value="<?php echo htmlspecialchars((string) $school->school_lsacode); ?>">
        </div>
        <div class="col-md-3">
          <label for="school_area">Area</label>
          <input type="text" class="form-control" name="school_area" id="school_area" value="<?php echo htmlspecialchars((string) $school->school_area); ?>">
        </div>
        <div class="col-md-3">
          <label for="school_students">Students</label>
          <input type="number" class="form-control" name="school_students" id="school_students" min="0" value="<?php echo htmlspecialchars((string) $school->school_students); ?>">
        </div>
      </div>

      <div class="row form-group">
        <div class="col-md-3">
          <label for="school_state_id">State</label>
          <select class="form-control" name="school_state_id" id="school_state_id">
            <option value="">Select State</option>
            <?php foreach ($states as $state): ?>
              <option value="<?php echo $state->state_id; ?>" <?php echo ((int) $state->state_id === (int) $school->school_state_id) ? 'selected' : ''; ?>>
                <?php echo $state->state_name_en; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="school_district_id">District</label>
          <select class="form-control" name="school_district_id" id="school_district_id">
            <option value="">Select District</option>
            <?php foreach ($districts as $district): ?>
              <option value="<?php echo $district->district_id; ?>" <?php echo ((int) $district->district_id === (int) $school->school_district_id) ? 'selected' : ''; ?>>
                <?php echo $district->district_name_en; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="school_tehsil_id">Tehsil</label>
          <select class="form-control" name="school_tehsil_id" id="school_tehsil_id">
            <option value="">Select Tehsil</option>
            <?php foreach ($tehsils as $tehsil): ?>
              <option value="<?php echo $tehsil->tehsil_id; ?>" <?php echo ((int) $tehsil->tehsil_id === (int) $school->school_tehsil_id) ? 'selected' : ''; ?>>
                <?php echo $tehsil->tehsil_name_en; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="school_address">Address</label>
          <input type="text" class="form-control" name="school_address" id="school_address" value="<?php echo htmlspecialchars((string) $school->school_address); ?>">
        </div>
      </div>

      <div class="row form-group">
        <div class="col-md-3">
          <label for="school_level">Level</label>
          <select class="form-control" name="school_level" id="school_level">
            <option value="">Select Level</option>
            <?php foreach (['Primary', 'Middle', 'High', 'Higher Secondary'] as $level): ?>
              <option value="<?php echo $level; ?>" <?php echo ($school->school_level === $level) ? 'selected' : ''; ?>><?php echo $level; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="school_gender">Gender</label>
          <select class="form-control" name="school_gender" id="school_gender">
            <option value="">Select Gender</option>
            <?php foreach (['Male', 'Female', 'Both'] as $gender): ?>
              <option value="<?php echo $gender; ?>" <?php echo ($school->school_gender === $gender) ? 'selected' : ''; ?>><?php echo $gender; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label for="school_grade">Grade</label>
          <input type="number" class="form-control" name="school_grade" id="school_grade" min="0" value="<?php echo (int) $school->school_grade; ?>">
        </div>
        <div class="col-md-3">
          <label for="school_status">Status</label>
          <select class="form-control" name="school_status" id="school_status">
            <option value="1" <?php echo ((int) $school->school_status === 1) ? 'selected' : ''; ?>>Active</option>
            <option value="0" <?php echo ((int) $school->school_status === 0) ? 'selected' : ''; ?>>Inactive</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card-footer">
      <div class="row">
        <div class="col"><a href="<?php echo url('/admin/school') ?>" class="btn btn-flat btn-danger"><?php echo lang('cancel') ?></a></div>
        <div class="col text-right"><button type="submit" class="btn btn-flat btn-primary"><?php echo lang('submit') ?></button></div>
      </div>
    </div>
    <?php echo form_close(); ?>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>
<script type="text/javascript">
$('#school_state_id').on('change', function() {
  $('#school_district_id option:not(:first)').remove();
  $('#school_tehsil_id option:not(:first)').remove();

  if (!this.value) {
    return;
  }

  $.post('<?=base_url("admin/school/distirct_by_state")?>', {
    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>',
    state_id: this.value
  }, function(data) {
    var arr = $.parseJSON(data);
    $.each(arr, function(key, value) {
      $('#school_district_id').append($("<option></option>").attr("value", value.district_id).text(value.district_name_en));
    });
  });
});

$('#school_district_id').on('change', function() {
  $('#school_tehsil_id option:not(:first)').remove();

  if (!this.value) {
    return;
  }

  $.post('<?=base_url("admin/school/tehsil_by_district")?>', {
    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>',
    district_id: this.value
  }, function(data) {
    var arr = $.parseJSON(data);
    $.each(arr, function(key, value) {
      $('#school_tehsil_id').append($("<option></option>").attr("value", value.tehsil_id).text(value.tehsil_name_en));
    });
  });
});
</script>
