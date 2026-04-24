<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
	$rows = isset($educations) && is_array($educations) && count($educations) ? $educations : [ [] ];
	$default_degree_options = [
		'PhD',
		'MPhil. / MS (18 years)',
		'Master / M.A/ MSc./ BS (Hons) (16 years)',
		'B.A / BSc. (14 years)',
		'HSSC',
		'SSC',
	];
	$degree_options = (isset($degree_options) && is_array($degree_options) && count($degree_options))
		? $degree_options
		: $default_degree_options;
?>

<form id="signupFormStep3" class="signup-step-form" autocomplete="off">
	<div class="text-right text-muted mb-2" style="font-size:13px;">
		( Please provide all your education details. You can add additional entries by clicking 'Add More'. )
	</div>

	<div class="js-repeat-wrap" data-repeat="education">
		<?php foreach ($rows as $idx => $r): ?>
			<div class="border rounded p-3 mb-3 js-repeat-item" data-index="<?php echo (int) $idx; ?>">
				<div class="d-flex justify-content-end">
					<?php if ($idx > 0): ?><span class="remove-link js-remove-item">Remove</span><?php endif; ?>
				</div>
				<div class="form-row">
					<div class="form-group col-md-4">
						<label>Degree<span class="text-danger">*</span></label>
						<?php $deg = (string) ($r['degree'] ?? ''); ?>
						<select class="form-control" name="degree[]" required>
							<option value="">Select Degree</option>
							<?php foreach ($degree_options as $opt): ?>
								<option value="<?php echo html_escape($opt); ?>" <?php echo ($deg === $opt) ? 'selected' : ''; ?>>
									<?php echo html_escape($opt); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group col-md-5">
						<label>Institute/University<span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="institute[]" placeholder="e.g. University name" value="<?php echo html_escape($r['institute'] ?? ''); ?>" required>
					</div>
					<div class="form-group col-md-3">
						<label>Passing Year<span class="text-danger">*</span></label>
						<select class="form-control" name="passing_year[]" required>
							<option value="">Select</option>
							<?php
								$selected = $r['passing_year'] ?? '';
								$year_now = (int) date('Y');
								for ($y = $year_now; $y >= $year_now - 60; $y--):
							?>
								<option value="<?php echo $y; ?>" <?php echo ((string)$selected === (string)$y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
							<?php endfor; ?>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group col-md-4">
						<label>CGPA/Percentage<span class="text-danger">*</span></label>
						<input type="text" class="form-control" name="cgpa_percentage[]" placeholder="e.g. 3.20 / 75%" value="<?php echo html_escape($r['cgpa_percentage'] ?? ''); ?>" required>
					</div>
				</div>

				<div class="form-group">
					<label>Upload Degree/Transcript<span class="text-danger">*</span></label>
					<div class="upload-box js-upload-box" data-field="transcript_file">
						<span>Upload File</span>
						<input type="file" class="d-none js-upload-input" accept=".jpg,.jpeg,.png,.pdf">
					</div>
					<input type="hidden" name="transcript_file[]" class="js-upload-path js-required-upload" data-label="Degree/Transcript" value="<?php echo html_escape($r['transcript_file'] ?? ''); ?>">
					<div class="upload-meta js-upload-meta">
						<span class="label">File name:</span> <?php echo !empty($r['transcript_file']) ? html_escape(basename($r['transcript_file'])) : '-'; ?>
						<span class="remove-link js-remove-upload" style="margin-left:10px;<?php echo empty($r['transcript_file']) ? 'display:none' : ''; ?>">Remove</span>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="clearfix">
		<span class="add-more-link js-add-more" data-repeat="education">Add More</span>
	</div>
</form>

<template id="tpl-education">
	<div class="border rounded p-3 mb-3 js-repeat-item" data-index="__INDEX__">
		<div class="d-flex justify-content-end">
			<span class="remove-link js-remove-item">Remove</span>
		</div>
		<div class="form-row">
			<div class="form-group col-md-4">
				<label>Degree<span class="text-danger">*</span></label>
				<select class="form-control" name="degree[]" required>
					<option value="">Select Degree</option>
					<?php foreach ($degree_options as $opt): ?>
						<option value="<?php echo html_escape($opt); ?>"><?php echo html_escape($opt); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-group col-md-5">
				<label>Institute/University<span class="text-danger">*</span></label>
				<input type="text" class="form-control" name="institute[]" placeholder="e.g. University name" value="" required>
			</div>
			<div class="form-group col-md-3">
				<label>Passing Year<span class="text-danger">*</span></label>
				<select class="form-control" name="passing_year[]" required>
					<option value="">Select</option>
					<?php $year_now = (int) date('Y'); for ($y = $year_now; $y >= $year_now - 60; $y--): ?>
						<option value="<?php echo $y; ?>"><?php echo $y; ?></option>
					<?php endfor; ?>
				</select>
			</div>
		</div>

		<div class="form-row">
			<div class="form-group col-md-4">
				<label>CGPA/Percentage<span class="text-danger">*</span></label>
				<input type="text" class="form-control" name="cgpa_percentage[]" placeholder="e.g. 3.20 / 75%" value="" required>
			</div>
		</div>

		<div class="form-group">
			<label>Upload Degree/Transcript<span class="text-danger">*</span></label>
			<div class="upload-box js-upload-box" data-field="transcript_file">
				<span>Upload File</span>
				<input type="file" class="d-none js-upload-input" accept=".jpg,.jpeg,.png,.pdf">
			</div>
			<input type="hidden" name="transcript_file[]" class="js-upload-path js-required-upload" data-label="Degree/Transcript" value="">
			<div class="upload-meta js-upload-meta">
				<span class="label">File name:</span> -
				<span class="remove-link js-remove-upload" style="margin-left:10px;display:none">Remove</span>
			</div>
		</div>
	</div>
</template>
