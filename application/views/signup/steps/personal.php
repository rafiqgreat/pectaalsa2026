<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $u = isset($user) && is_object($user) ? $user : null; ?>
<?php $max_dob = date('Y-m-d', strtotime('-18 years')); ?>

<form id="signupFormStep1" class="signup-step-form" autocomplete="off">
	<div class="form-row">
		<div class="form-group col-md-3">
			<label>CNIC<span class="text-danger">*</span></label>
			<input
				type="text"
				class="form-control js-cnic"
				id="cnic"
				name="cnic"
				inputmode="numeric"
				autocomplete="off"
				placeholder="12345-1234567-1"
				maxlength="15"
				value="<?php echo html_escape($u->cnic ?? ''); ?>"
				required
			>
			<div class="invalid-feedback" style="display:none">CNIC must be in format 12345-1234567-1</div>
		</div>
		<div class="form-group col-md-3">
			<label>Date Of Birth<span class="text-danger">*</span></label>
			<input type="date" class="form-control" name="dob" max="<?php echo html_escape($max_dob); ?>" value="<?php echo html_escape($u->dob ?? ''); ?>" required>
		</div>
		<div class="form-group col-md-3">
			<label>Name<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="name" placeholder="Enter full name" value="<?php echo html_escape($u->name ?? ''); ?>" required>
		</div>
		<div class="form-group col-md-3">
			<label>Father Name<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="father_name" placeholder="Enter father name" value="<?php echo html_escape($u->father_name ?? ''); ?>" required>
		</div>
	</div>

	<div class="form-row">
		<div class="form-group col-md-4">
			<label>Gender<span class="text-danger">*</span></label>
			<?php $g = $u->gender ?? ''; ?>
			<select class="form-control" name="gender" required>
				<option value="">Select</option>
				<option value="Male" <?php echo ($g === 'Male') ? 'selected' : ''; ?>>Male</option>
				<option value="Female" <?php echo ($g === 'Female') ? 'selected' : ''; ?>>Female</option>
				<option value="Other" <?php echo ($g === 'Other') ? 'selected' : ''; ?>>Other</option>
			</select>
		</div>
		<div class="form-group col-md-4">
			<label>Phone Number<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="phone" placeholder="03XXXXXXXXX" value="<?php echo html_escape($u->phone ?? ''); ?>" required>
		</div>
		<div class="form-group col-md-4">
			<label>Email Address<span class="text-danger">*</span></label>
			<input type="email" class="form-control" name="email" placeholder="example@email.com" value="<?php echo html_escape($u->email ?? ''); ?>" required>
		</div>
	</div>

	<div class="form-row">
		<div class="form-group col-md-4">
			<label>Personal No/Employee Id<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="employee_no" placeholder="Employee ID / Personal No" value="<?php echo html_escape($u->employee_no ?? ''); ?>" required>
		</div>
	</div>

	<div class="form-group">
		<label>Upload Profile Picture<span class="text-danger">*</span></label>
		<div class="upload-box js-upload-box" data-field="profile_picture">
			<span>Upload Profile Picture</span>
			<input type="file" class="d-none js-upload-input" accept=".jpg,.jpeg,.png">
		</div>
		<input type="hidden" name="profile_picture_path" class="js-upload-path js-required-upload" data-label="Profile Picture" value="<?php echo html_escape($u->profile_picture ?? ''); ?>">
		<div class="upload-meta js-upload-meta">
			<?php if (!empty($u->profile_picture)): ?>
				<span class="label">File name:</span> <?php echo html_escape(basename($u->profile_picture)); ?>
			<?php else: ?>
				<span class="label">File name:</span> -
			<?php endif; ?>
			<span class="remove-link js-remove-upload" style="margin-left:10px;<?php echo empty($u->profile_picture) ? 'display:none' : ''; ?>">Remove</span>
		</div>
	</div>
</form>
