<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
	$rows = isset($experiences) && is_array($experiences) && count($experiences) ? $experiences : [ [] ];
	$no = !empty($no_experience) ? 1 : 0;
?>

<form id="signupFormStep4" class="signup-step-form" autocomplete="off">
	<div class="form-group form-check">
		<input type="checkbox" class="form-check-input js-no-experience" id="no_experience" name="no_experience" value="1" <?php echo $no ? 'checked' : ''; ?>>
		<label class="form-check-label" for="no_experience">No Experience</label>
	</div>

	<div class="text-right text-muted mb-2" style="font-size:13px;">
		( Please provide all your experience details. You can add additional entries by clicking 'Add More'. )
	</div>

	<div class="js-experience-wrap" style="<?php echo $no ? 'display:none' : ''; ?>">
		<div class="js-repeat-wrap" data-repeat="experience">
			<?php foreach ($rows as $idx => $r): ?>
				<div class="border rounded p-3 mb-3 js-repeat-item" data-index="<?php echo (int) $idx; ?>">
					<div class="d-flex justify-content-end">
						<?php if ($idx > 0): ?><span class="remove-link js-remove-item">Remove</span><?php endif; ?>
					</div>

					<div class="form-row">
						<div class="form-group col-md-4">
							<label>Department<span class="text-danger">*</span></label>
							<input type="text" class="form-control" name="department[]" placeholder="e.g. Education" value="<?php echo html_escape($r['department'] ?? ''); ?>" required>
						</div>
						<div class="form-group col-md-4">
							<label>Sector<span class="text-danger">*</span></label>
							<?php $sector = $r['sector'] ?? ''; ?>
							<select class="form-control js-sector" name="sector[]" required>
								<option value="">Select</option>
								<option value="Government" <?php echo ($sector === 'Government') ? 'selected' : ''; ?>>Government</option>
								<option value="Private" <?php echo ($sector === 'Private') ? 'selected' : ''; ?>>Private</option>
								<option value="Semi Government" <?php echo ($sector === 'Semi Government') ? 'selected' : ''; ?>>Semi Government</option>
								<option value="Other" <?php echo ($sector === 'Other') ? 'selected' : ''; ?>>Other</option>
							</select>
						</div>
						<div class="form-group col-md-4">
							<label>Experience Type<span class="text-danger">*</span></label>
							<?php $etype = $r['experience_type'] ?? ''; ?>
							<select class="form-control" name="experience_type[]" required>
								<option value="">Select</option>
								<option value="Professional" <?php echo ($etype === 'Professional') ? 'selected' : ''; ?>>Professional</option>
								<option value="Academic/Teaching" <?php echo ($etype === 'Academic/Teaching') ? 'selected' : ''; ?>>Academic/Teaching</option>
							</select>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-4">
							<label>Job Type<span class="text-danger">*</span></label>
							<?php $jtype = $r['job_type'] ?? ''; ?>
							<select class="form-control" name="job_type[]" required>
								<option value="">Select</option>
								<option value="Regular" <?php echo ($jtype === 'Regular') ? 'selected' : ''; ?>>Regular</option>
								<option value="Contract" <?php echo ($jtype === 'Contract') ? 'selected' : ''; ?>>Contract</option>
								<option value="Visiting" <?php echo ($jtype === 'Visiting') ? 'selected' : ''; ?>>Visiting</option>
								<option value="Part-time" <?php echo ($jtype === 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
								<option value="Full-time" <?php echo ($jtype === 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
								<option value="Other" <?php echo ($jtype === 'Other') ? 'selected' : ''; ?>>Other</option>
							</select>
						</div>
						<div class="form-group col-md-2">
							<label>Start Date<span class="text-danger">*</span></label>
							<input type="date" class="form-control js-start-date" name="start_date[]" value="<?php echo html_escape($r['start_date'] ?? ''); ?>" required>
						</div>
						<div class="form-group col-md-2">
							<label>End Date</label>
							<input type="date" class="form-control js-end-date" name="end_date[]" value="<?php echo html_escape($r['end_date'] ?? ''); ?>">
							<div class="form-check mt-1">
								<?php $cw = !empty($r['currently_working']) ? 1 : 0; ?>
								<input type="checkbox" class="form-check-input js-currently-working" name="currently_working[<?php echo (int) $idx; ?>]" value="1" <?php echo $cw ? 'checked' : ''; ?>>
								<label class="form-check-label">Currently Working</label>
							</div>
						</div>
						<div class="form-group col-md-4">
							<label>Teaching Level<span class="text-danger">*</span></label>
							<?php $tlevel = $r['teaching_level'] ?? ''; ?>
							<select class="form-control" name="teaching_level[]" required>
								<option value="">Select</option>
								<option value="Under-graduate" <?php echo ($tlevel === 'Under-graduate') ? 'selected' : ''; ?>>Under-graduate</option>
								<option value="Post-graduate" <?php echo ($tlevel === 'Post-graduate') ? 'selected' : ''; ?>>Post-graduate</option>
								<option value="SSC/HSSC" <?php echo ($tlevel === 'SSC/HSSC') ? 'selected' : ''; ?>>SSC/HSSC</option>
							</select>
						</div>
					</div>

					<div class="form-row">
						<div class="form-group col-md-3 js-bps-wrap" style="<?php echo ($sector === 'Government') ? '' : 'display:none'; ?>">
							<label>BPS<span class="text-danger">*</span></label>
							<?php $bpsv = $r['bps'] ?? ''; ?>
							<select class="form-control js-bps" name="bps[]">
								<option value="">Select</option>
								<?php for ($b = 1; $b <= 22; $b++): ?>
									<option value="<?php echo $b; ?>" <?php echo ((string)$bpsv === (string)$b) ? 'selected' : ''; ?>><?php echo $b; ?></option>
								<?php endfor; ?>
							</select>
						</div>
					</div>

					<div class="form-group">
						<label>Upload Relevant Document<span class="text-danger">*</span></label>
						<div class="upload-box js-upload-box" data-field="experience_document">
							<span>Upload File</span>
							<input type="file" class="d-none js-upload-input" accept=".jpg,.jpeg,.png,.pdf">
						</div>
						<input type="hidden" name="document_file[]" class="js-upload-path js-required-upload" data-label="Relevant Document" value="<?php echo html_escape($r['document_file'] ?? ''); ?>">
						<div class="upload-meta js-upload-meta">
							<span class="label">File name:</span> <?php echo !empty($r['document_file']) ? html_escape(basename($r['document_file'])) : '-'; ?>
							<span class="remove-link js-remove-upload" style="margin-left:10px;<?php echo empty($r['document_file']) ? 'display:none' : ''; ?>">Remove</span>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="clearfix">
			<span class="add-more-link js-add-more" data-repeat="experience">Add More</span>
		</div>
	</div>
</form>

<template id="tpl-experience">
	<div class="border rounded p-3 mb-3 js-repeat-item" data-index="__INDEX__">
		<div class="d-flex justify-content-end">
			<span class="remove-link js-remove-item">Remove</span>
		</div>

		<div class="form-row">
			<div class="form-group col-md-4">
				<label>Department<span class="text-danger">*</span></label>
				<input type="text" class="form-control" name="department[]" placeholder="e.g. Education" value="" required>
			</div>
			<div class="form-group col-md-4">
				<label>Sector<span class="text-danger">*</span></label>
				<select class="form-control js-sector" name="sector[]" required>
					<option value="">Select</option>
					<option value="Government">Government</option>
					<option value="Private">Private</option>
					<option value="Semi Government">Semi Government</option>
					<option value="Other">Other</option>
				</select>
			</div>
			<div class="form-group col-md-4">
				<label>Experience Type<span class="text-danger">*</span></label>
				<select class="form-control" name="experience_type[]" required>
					<option value="">Select</option>
					<option value="Professional">Professional</option>
					<option value="Academic/Teaching">Academic/Teaching</option>
				</select>
			</div>
		</div>

		<div class="form-row">
			<div class="form-group col-md-4">
				<label>Job Type<span class="text-danger">*</span></label>
				<select class="form-control" name="job_type[]" required>
					<option value="">Select</option>
					<option value="Regular">Regular</option>
					<option value="Contract">Contract</option>
					<option value="Visiting">Visiting</option>
					<option value="Part-time">Part-time</option>
					<option value="Full-time">Full-time</option>
					<option value="Other">Other</option>
				</select>
			</div>
			<div class="form-group col-md-2">
				<label>Start Date<span class="text-danger">*</span></label>
				<input type="date" class="form-control js-start-date" name="start_date[]" value="" required>
			</div>
			<div class="form-group col-md-2">
				<label>End Date</label>
				<input type="date" class="form-control js-end-date" name="end_date[]" value="">
				<div class="form-check mt-1">
					<input type="checkbox" class="form-check-input js-currently-working" name="currently_working[__INDEX__]" value="1">
					<label class="form-check-label">Currently Working</label>
				</div>
			</div>
			<div class="form-group col-md-4">
				<label>Teaching Level<span class="text-danger">*</span></label>
				<select class="form-control" name="teaching_level[]" required>
					<option value="">Select</option>
					<option value="Under-graduate">Under-graduate</option>
					<option value="Post-graduate">Post-graduate</option>
					<option value="SSC/HSSC">SSC/HSSC</option>
				</select>
			</div>
		</div>

		<div class="form-row">
			<div class="form-group col-md-3 js-bps-wrap" style="display:none">
				<label>BPS<span class="text-danger">*</span></label>
				<select class="form-control js-bps" name="bps[]">
					<option value="">Select</option>
					<?php for ($b = 1; $b <= 22; $b++): ?>
						<option value="<?php echo $b; ?>"><?php echo $b; ?></option>
					<?php endfor; ?>
				</select>
			</div>
		</div>

		<div class="form-group">
			<label>Upload Relevant Document<span class="text-danger">*</span></label>
			<div class="upload-box js-upload-box" data-field="experience_document">
				<span>Upload File</span>
				<input type="file" class="d-none js-upload-input" accept=".jpg,.jpeg,.png,.pdf">
			</div>
			<input type="hidden" name="document_file[]" class="js-upload-path js-required-upload" data-label="Relevant Document" value="">
			<div class="upload-meta js-upload-meta">
				<span class="label">File name:</span> -
				<span class="remove-link js-remove-upload" style="margin-left:10px;display:none">Remove</span>
			</div>
		</div>
	</div>
</template>
