<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $b = isset($bank) && is_array($bank) ? $bank : []; ?>

<form id="signupFormStep5" class="signup-step-form" autocomplete="off">
	<div class="form-row">
		<div class="form-group col-md-4">
			<label>Bank Name<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="bank_name" placeholder="e.g. UBL" value="<?php echo html_escape($b['bank_name'] ?? ''); ?>" required>
		</div>
		<div class="form-group col-md-4">
			<label>Branch Name<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="branch_name" placeholder="e.g. Garden Town" value="<?php echo html_escape($b['branch_name'] ?? ''); ?>" required>
		</div>
		<div class="form-group col-md-4">
			<label>Branch Code<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="branch_code" placeholder="e.g. 0622" value="<?php echo html_escape($b['branch_code'] ?? ''); ?>" required>
		</div>
	</div>

	<div class="form-row">
		<div class="form-group col-md-4">
			<label>Account Title<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="account_title" placeholder="e.g. Muhammad Ali" value="<?php echo html_escape($b['account_title'] ?? ''); ?>" required>
		</div>
		<div class="form-group col-md-5">
			<label>Account/IBAN Number<span class="text-danger">*</span></label>
			<input type="text" class="form-control" name="iban_account_no" placeholder="e.g. PK12XXXX0000000000" value="<?php echo html_escape($b['iban_account_no'] ?? ''); ?>" required>
		</div>
		<div class="form-group col-md-3 d-flex align-items-end">
			<div class="form-check">
				<input type="checkbox" class="form-check-input" id="international_user" name="international_user" value="1" <?php echo !empty($b['international_user']) ? 'checked' : ''; ?>>
				<label class="form-check-label" for="international_user">International User</label>
			</div>
		</div>
	</div>
</form>
