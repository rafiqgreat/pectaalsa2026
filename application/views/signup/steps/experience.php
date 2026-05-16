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
		( Add one experience record, click <strong>Add</strong>. Added entries will appear below. )
	</div>

	<div class="js-experience-wrap" style="<?php echo $no ? 'display:none' : ''; ?>">
		<div class="border rounded p-3 mb-3" id="experienceEntryForm">
			<div class="form-row">
				<div class="form-group col-md-4">
					<label>Department<span class="text-danger">*</span></label>
					<input type="text" class="form-control" name="department_new" placeholder="e.g. Education" value="">
				</div>
				<div class="form-group col-md-4">
					<label>Sector<span class="text-danger">*</span></label>
					<select class="form-control js-exp-sector" name="sector_new">
						<option value="">Select</option>
						<option value="Government">Government</option>
						<option value="Private">Private</option>
						<option value="Semi Government">Semi Government</option>
						<option value="Other">Other</option>
					</select>
				</div>
				<div class="form-group col-md-4">
					<label>Experience Type<span class="text-danger">*</span></label>
					<select class="form-control" name="experience_type_new">
						<option value="">Select</option>
						<option value="Professional">Professional</option>
						<option value="Academic/Teaching">Academic/Teaching</option>
					</select>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-4">
					<label>Job Type<span class="text-danger">*</span></label>
					<select class="form-control" name="job_type_new">
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
					<input type="date" class="form-control js-exp-start" name="start_date_new" value="">
				</div>
				<div class="form-group col-md-2">
					<label>End Date</label>
					<input type="date" class="form-control js-exp-end" name="end_date_new" value="">
					<div class="form-check mt-1">
						<input type="checkbox" class="form-check-input js-exp-current" name="currently_working_new" value="1">
						<label class="form-check-label">Currently Working</label>
					</div>
				</div>
				<div class="form-group col-md-4">
					<label>Teaching Level<span class="text-danger">*</span></label>
					<select class="form-control" name="teaching_level_new">
						<option value="">Select</option>
						<option value="Under-graduate">Under-graduate</option>
						<option value="Post-graduate">Post-graduate</option>
						<option value="SSC/HSSC">SSC/HSSC</option>
					</select>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group col-md-3 js-exp-bps-wrap" style="display:none;">
					<label>BPS<span class="text-danger">*</span></label>
					<select class="form-control js-exp-bps" name="bps_new">
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
				<input type="hidden" name="document_file_new" class="js-upload-path" value="">
				<div class="upload-meta js-upload-meta">
					<span class="label">File name:</span> -
					<span class="remove-link js-remove-upload" style="margin-left:10px;display:none">Remove</span>
				</div>
			</div>

			<div class="d-flex align-items-center" style="gap:10px;">
				<button type="button" class="btn btn-success js-exp-add">Add</button>
				<button type="button" class="btn btn-secondary js-exp-cancel" style="display:none;">Cancel Edit</button>
			</div>
		</div>

		<div id="experienceList" class="mb-2">
			<?php foreach ($rows as $idx => $r): ?>
				<?php
					$dep = (string) ($r['department'] ?? '');
					$sec = (string) ($r['sector'] ?? '');
					$etype = (string) ($r['experience_type'] ?? '');
					$jtype = (string) ($r['job_type'] ?? '');
					$sd = (string) ($r['start_date'] ?? '');
					$ed = (string) ($r['end_date'] ?? '');
					$cw = !empty($r['currently_working']) ? 1 : 0;
					$tlevel = (string) ($r['teaching_level'] ?? '');
					$bps = (string) ($r['bps'] ?? '');
					$doc = (string) ($r['document_file'] ?? '');
					if ($dep === '' && $sec === '' && $etype === '' && $jtype === '' && $sd === '' && $ed === '' && $tlevel === '' && $doc === '') continue;
				?>
				<div class="border rounded p-3 mb-3 js-exp-item">
					<div class="d-flex justify-content-between align-items-center">
						<div>
							<div style="font-weight:700;"><?php echo html_escape($dep); ?> <span class="text-muted" style="font-weight:400;">(<?php echo html_escape($sec); ?>)</span></div>
							<div class="text-muted" style="font-size:13px;">
								<?php echo html_escape($etype); ?> &middot; <?php echo html_escape($jtype); ?> &middot; <?php echo html_escape($tlevel); ?>
							</div>
							<div class="text-muted" style="font-size:12px;">
								<?php echo html_escape($sd); ?> &rarr; <?php echo $cw ? 'Present' : html_escape($ed); ?>
								<?php if ($sec === 'Government' && $bps !== ''): ?> &middot; BPS <?php echo html_escape($bps); ?><?php endif; ?>
							</div>
							<div class="text-muted" style="font-size:12px;">File: <?php echo html_escape(basename($doc)); ?></div>
						</div>
						<div style="display:flex;gap:8px;">
							<button type="button" class="btn btn-sm btn-outline-primary js-exp-edit">Edit</button>
							<button type="button" class="btn btn-sm btn-outline-danger js-exp-remove">Remove</button>
						</div>
					</div>
					<input type="hidden" name="department[]" value="<?php echo html_escape($dep); ?>">
					<input type="hidden" name="sector[]" value="<?php echo html_escape($sec); ?>">
					<input type="hidden" name="experience_type[]" value="<?php echo html_escape($etype); ?>">
					<input type="hidden" name="job_type[]" value="<?php echo html_escape($jtype); ?>">
					<input type="hidden" name="start_date[]" value="<?php echo html_escape($sd); ?>">
					<input type="hidden" name="end_date[]" value="<?php echo html_escape($cw ? '' : $ed); ?>">
					<input type="hidden" name="currently_working_pos[]" value="<?php echo $cw ? '1' : '0'; ?>">
					<input type="hidden" name="teaching_level[]" value="<?php echo html_escape($tlevel); ?>">
					<input type="hidden" name="bps[]" value="<?php echo html_escape($bps); ?>">
					<input type="hidden" name="document_file[]" value="<?php echo html_escape($doc); ?>">
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</form>
