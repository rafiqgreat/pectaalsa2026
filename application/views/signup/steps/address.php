<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $a = isset($address) && is_array($address) ? $address : []; ?>

<form id="signupFormStep2" class="signup-step-form" autocomplete="off">
	<div class="form-group">
		<label>Address<span class="text-danger">*</span></label>
		<textarea class="form-control" name="address" rows="3" placeholder="House no, street, area" required><?php echo html_escape($a['address'] ?? ''); ?></textarea>
	</div>

	<div class="form-row">
		<div class="form-group col-md-3">
			<label>District</label>
			<input type="text" class="form-control" name="district" placeholder="e.g. Lahore" value="<?php echo html_escape($a['district'] ?? ''); ?>">
		</div>
		<div class="form-group col-md-3">
			<label>City<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="city" placeholder="e.g. Lahore" value="<?php echo html_escape($a['city'] ?? ''); ?>" required>
		</div>
		<div class="form-group col-md-3">
			<label>Province<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="province" placeholder="e.g. Punjab" value="<?php echo html_escape($a['province'] ?? ''); ?>" required>
		</div>
		<div class="form-group col-md-3">
			<label>Country<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="country" placeholder="e.g. Pakistan" value="<?php echo html_escape($a['country'] ?? 'Pakistan'); ?>" required>
		</div>
	</div>
</form>
