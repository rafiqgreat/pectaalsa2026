<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
	$sec = isset($security) && is_array($security) ? $security : [];
	$u = isset($user) && is_object($user) ? $user : null;
	$prefill_id = $sec['identification_number'] ?? ($u->cnic ?? '');
?>

<form id="profileFormStep7" class="signup-step-form" autocomplete="off">
	<div class="form-row">
		<div class="form-group col-md-4">
			<label>Document Type<span class="text-danger">*</span></label>
			<?php $dt = $sec['document_type'] ?? ''; ?>
			<select class="form-control" name="document_type" required>
				<option value="">Select</option>
				<option value="CNIC" <?php echo ($dt === 'CNIC') ? 'selected' : ''; ?>>CNIC</option>
				<option value="Passport" <?php echo ($dt === 'Passport') ? 'selected' : ''; ?>>Passport</option>
			</select>
		</div>
		<div class="form-group col-md-4">
			<label>Identification Number<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="identification_number" placeholder="Enter ID number" value="<?php echo html_escape($prefill_id); ?>" required>
		</div>
		<div class="form-group col-md-4">
			<label>Expiry Date<span class="text-danger">*</span></label>
			<input type="date" class="form-control" name="expiry_date" value="<?php echo html_escape($sec['expiry_date'] ?? ''); ?>" required>
		</div>
	</div>

	<div class="form-group">
		<label>Upload Document<span class="text-danger">*</span></label>
		<div class="upload-box js-upload-box" data-field="security_document">
			<span>Upload File</span>
			<input type="file" class="d-none js-upload-input" accept=".jpg,.jpeg,.png,.pdf">
		</div>
		<input type="hidden" name="document_file" class="js-upload-path js-required-upload" data-label="Document" value="<?php echo html_escape($sec['document_file'] ?? ''); ?>">
		<div class="upload-meta js-upload-meta">
			<span class="label">File name:</span> <?php echo !empty($sec['document_file']) ? html_escape(basename($sec['document_file'])) : '-'; ?>
			<span class="remove-link js-remove-upload" style="margin-left:10px;<?php echo empty($sec['document_file']) ? 'display:none' : ''; ?>">Remove</span>
		</div>
	</div>

	<div class="alert alert-info" style="margin-top:10px;">
		Leave password blank if you don’t want to change it.
	</div>

	<div class="form-row">
		<div class="form-group col-md-6">
			<label>Password</label>
			<input type="password" class="form-control" name="password" placeholder="New password (optional)" value="">
		</div>
		<div class="form-group col-md-6">
			<label>Confirm Password</label>
			<input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" value="">
		</div>
	</div>
</form>

