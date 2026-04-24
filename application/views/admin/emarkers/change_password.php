<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<?php include viewPath('admin/includes/header'); ?>
<?php $u = isset($user_row) && is_object($user_row) ? $user_row : null; ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<ol class="breadcrumb">
					<li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
					<li class="breadcrumb-item"><a href="<?php echo url('admin/emarkers'); ?>">Evaluator</a></li>
					<li class="breadcrumb-item"><a href="<?php echo url('admin/emarkers/view/' . (int) ($u->id ?? 0)); ?>">Profile</a></li>
					<li class="breadcrumb-item active">Change Password</li>
				</ol>
			</div>
			<div class="col-sm-6 text-right">
				<a href="<?php echo url('admin/emarkers/view/' . (int) ($u->id ?? 0)); ?>" style="font-weight:600;">Back to Profile</a>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<h2 class="mb-4" style="font-weight:600;">Change Password</h2>

		<?php if (!empty($this->session->flashdata('alert'))): ?>
			<div class="alert alert-<?php echo $this->session->flashdata('alert-type'); ?>">
				<?php echo $this->session->flashdata('alert'); ?>
			</div>
		<?php endif; ?>

		<div class="card" style="border:1px solid #d6defa;border-radius:10px;">
			<div class="card-body" style="padding:35px;">
				<div class="alert alert-info" style="border-radius:10px;">
					This will reset the evaluator password. Share the new password securely.
				</div>

				<?php echo form_open('admin/emarkers/update_password/' . (int) ($u->id ?? 0), ['method' => 'POST', 'autocomplete' => 'off', 'class' => 'form-validate']); ?>
					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="password" style="font-weight:600;">New Password</label>
							<input type="password" class="form-control" name="password" id="password" placeholder="********" minlength="6" required>
						</div>
						<div class="form-group col-md-6">
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
</section>

<?php include viewPath('admin/includes/footer'); ?>

<script>
	$(document).ready(function() {
		$('.form-validate').each(function() {
			$(this).validate();
		});
	});
</script>

