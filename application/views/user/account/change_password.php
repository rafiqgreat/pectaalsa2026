<?php
defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php include viewPath('user/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url('/user/'); ?>"><?php echo lang('home'); ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('user/evaluator_profile'); ?>">Profile</a></li>
          <li class="breadcrumb-item active">Change Password</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2 class="mb-4" style="font-weight:600;">Change Password</h2>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="alert alert-info" style="border-radius:10px;">
          After you reset your password, you will be logged out and will need to login again using your new password.
        </div>
        <div class="card" style="border:1px solid #d6defa;border-radius:10px;">
          <div class="card-body" style="padding:35px;">

            <div class="d-flex justify-content-end mb-4">
              <a href="<?php echo url('user/profile/step/7'); ?>" style="font-weight:600;">Back to Security</a>
            </div>

            <?php echo form_open('user/profile/updatePassword', ['method' => 'POST', 'autocomplete' => 'off', 'class' => 'form-validate']); ?>
              <div class="form-row">
                <div class="form-group col-md-4">
                  <label for="old_password" style="font-weight:600;">Existing Password</label>
                  <input type="password" class="form-control" name="old_password" id="old_password" placeholder="********" required>
                </div>
                <div class="form-group col-md-4">
                  <label for="password" style="font-weight:600;">New Password</label>
                  <input type="password" class="form-control" name="password" id="password" placeholder="********" minlength="6" required>
                </div>
                <div class="form-group col-md-4">
                  <label for="password_confirm" style="font-weight:600;">Confirm Password</label>
                  <input type="password" class="form-control" name="password_confirm" id="password_confirm" placeholder="********" minlength="6" required>
                </div>
              </div>

              <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary" style="min-width:280px;background:#6c93c6;border-color:#6c93c6;">Confirm</button>
              </div>
            <?php echo form_close(); ?>

          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<script>
  $(document).ready(function() {
    $('.form-validate').each(function() {
      $(this).validate();
    });
  });
</script>

<?php include viewPath('user/includes/footer'); ?>
