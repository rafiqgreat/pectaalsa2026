<?php
defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1><?php echo lang('settings') ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/') ?>"><?php echo lang('home') ?></a></li>
          <li class="breadcrumb-item active"><?php echo lang('settings') ?></li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="row">
    <div class="col-sm-3">
      <?php include 'sidebar.php'; ?>
    </div>
    <div class="col-sm-9">
      <div class="card">
        <div class="card-header with-border">
          <h3 class="card-title">Registration</h3>
        </div>

        <?php echo form_open('admin/settings/registrationUpdate', [ 'class' => 'form-validate', 'autocomplete' => 'off', 'method' => 'post' ]); ?>
        <div class="card-body">
          <div class="form-group">
            <label for="registration_enabled">Enable Registration</label>
            <select name="registration_enabled" id="registration_enabled" class="form-control" required>
              <option value="1" <?php echo ((string) $registration_enabled === '1') ? 'selected' : ''; ?>>Yes</option>
              <option value="0" <?php echo ((string) $registration_enabled === '0') ? 'selected' : ''; ?>>No</option>
            </select>
          </div>

          <div class="form-group">
            <label for="registration_close_at">Registration Close Date/Time</label>
            <?php
              $close_at_value = trim((string) $registration_close_at);
              if ($close_at_value !== '' && strpos($close_at_value, 'T') === false) {
                $close_at_value = str_replace(' ', 'T', $close_at_value);
              }
            ?>
            <input type="datetime-local" step="1" class="form-control" name="registration_close_at" id="registration_close_at" value="<?php echo html_escape($close_at_value); ?>" placeholder="YYYY-MM-DD HH:MM:SS" />
            <small class="form-text text-muted">Leave empty to keep registration open until disabled.</small>
          </div>
        </div>

        <div class="card-footer">
          <button type="submit" class="btn btn-flat btn-primary"><?php echo lang('submit') ?></button>
        </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</section>

<script>
  $(document).ready(function() {
    $('.form-validate').validate();
    const closeAtInput = document.getElementById('registration_close_at');
    if (closeAtInput) {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      const seconds = String(now.getSeconds()).padStart(2, '0');
      closeAtInput.setAttribute('min', `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`);
    }
  });
</script>
