<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $s = isset($specialization) && is_array($specialization) ? $specialization : []; ?>
<?php $val = $s['specialization'] ?? ''; ?>

<form id="signupFormStep6" class="signup-step-form" autocomplete="off">
	<div class="form-group">
		<label>Based on your highest qualification what is your area of specialization?<span class="text-danger">*</span></label>
		<select class="form-control" name="specialization" required>
			<option value="">Select</option>
			<?php
				$options = [
					'ENGLISH',
					'URDU',
					'MATH',
					'SCIENCE',
				];
			?>
			<?php foreach ($options as $opt): ?>
				<option value="<?php echo $opt; ?>" <?php echo ($val === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</form>
