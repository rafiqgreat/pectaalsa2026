<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
	$rows = isset($emarking) && is_array($emarking) && count($emarking) ? $emarking : [ [] ];
	$no = !empty($no_emarking_experience) ? 1 : 0;
?>

<form id="signupFormStep8" class="signup-step-form" autocomplete="off">
	<div class="form-group form-check">
		<input type="checkbox" class="form-check-input js-no-emarking" id="no_emarking_experience" name="no_emarking_experience" value="1" <?php echo $no ? 'checked' : ''; ?>>
		<label class="form-check-label" for="no_emarking_experience">No Experience</label>
	</div>

	<div class="text-right text-muted mb-2" style="font-size:13px;">
		( Please provide all your emarking experience details. You can add additional entries by clicking 'Add More'. )
	</div>

	<div class="js-emarking-wrap" style="<?php echo $no ? 'display:none' : ''; ?>">
		<div class="js-repeat-wrap" data-repeat="emarking">
			<?php foreach ($rows as $idx => $r): ?>
				<div class="border rounded p-3 mb-3 js-repeat-item" data-index="<?php echo (int) $idx; ?>">
					<div class="d-flex justify-content-end">
						<?php if ($idx > 0): ?><span class="remove-link js-remove-item">Remove</span><?php endif; ?>
					</div>
					<div class="form-row">
						<div class="form-group col-md-6">
							<label>Department<span class="text-danger">*</span></label>
							<input type="text" class="form-control" name="department[]" placeholder="e.g. PECTAA" value="<?php echo html_escape($r['department'] ?? ''); ?>" required>
						</div>
						<div class="form-group col-md-3">
							<label>From<span class="text-danger">*</span></label>
							<input type="date" class="form-control js-from-date" name="from_date[]" value="<?php echo html_escape($r['from_date'] ?? ''); ?>" required>
						</div>
						<div class="form-group col-md-3">
							<label>To<span class="text-danger">*</span></label>
							<input type="date" class="form-control js-to-date" name="to_date[]" value="<?php echo html_escape($r['to_date'] ?? ''); ?>" required>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="clearfix">
			<span class="add-more-link js-add-more" data-repeat="emarking">Add More</span>
		</div>
	</div>
</form>

<template id="tpl-emarking">
	<div class="border rounded p-3 mb-3 js-repeat-item" data-index="__INDEX__">
		<div class="d-flex justify-content-end">
			<span class="remove-link js-remove-item">Remove</span>
		</div>
		<div class="form-row">
			<div class="form-group col-md-6">
				<label>Department<span class="text-danger">*</span></label>
				<input type="text" class="form-control" name="department[]" placeholder="e.g. PECTAA" value="" required>
			</div>
			<div class="form-group col-md-3">
				<label>From<span class="text-danger">*</span></label>
				<input type="date" class="form-control js-from-date" name="from_date[]" value="" required>
			</div>
			<div class="form-group col-md-3">
				<label>To<span class="text-danger">*</span></label>
				<input type="date" class="form-control js-to-date" name="to_date[]" value="" required>
			</div>
		</div>
	</div>
</template>
