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
		( Add one education record, click <strong>Add</strong>. Added degrees will appear below. )
	</div>

	<div class="border rounded p-3 mb-3" id="educationEntryForm">
		<div class="form-row">
			<div class="form-group col-md-4">
				<label>Degree<span class="text-danger">*</span></label>
				<select class="form-control" name="degree_new">
					<option value="">Select Degree</option>
					<?php foreach ($degree_options as $opt): ?>
						<option value="<?php echo html_escape($opt); ?>"><?php echo html_escape($opt); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-group col-md-5">
				<label>Institute/University<span class="text-danger">*</span></label>
				<input type="text" class="form-control" name="institute_new" placeholder="e.g. University name" value="">
			</div>
			<div class="form-group col-md-3">
				<label>Passing Year<span class="text-danger">*</span></label>
				<select class="form-control" name="passing_year_new">
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
				<input type="text" class="form-control" name="cgpa_percentage_new" placeholder="e.g. 3.20 / 75%" value="">
			</div>
		</div>

		<div class="form-group">
			<label>Upload Degree/Transcript<span class="text-danger">*</span></label>
			<div class="upload-box js-upload-box" data-field="transcript_file">
				<span>Upload File</span>
				<input type="file" class="d-none js-upload-input" accept=".jpg,.jpeg,.png,.pdf">
			</div>
			<input type="hidden" name="transcript_file_new" class="js-upload-path" value="">
			<div class="upload-meta js-upload-meta">
				<span class="label">File name:</span> -
				<span class="remove-link js-remove-upload" style="margin-left:10px;display:none">Remove</span>
			</div>
		</div>

		<div class="d-flex align-items-center" style="gap:10px;">
			<button type="button" class="btn btn-success js-edu-add">Add</button>
			<button type="button" class="btn btn-secondary js-edu-cancel" style="display:none;">Cancel Edit</button>
			<div class="text-muted" style="font-size:13px;">No duplicates by Degree.</div>
		</div>
	</div>

	<div id="educationList" class="mb-2">
		<?php foreach ($rows as $idx => $r): ?>
			<?php
				$deg = (string) ($r['degree'] ?? '');
				$inst = (string) ($r['institute'] ?? '');
				$yr = (string) ($r['passing_year'] ?? '');
				$cg = (string) ($r['cgpa_percentage'] ?? '');
				$file = (string) ($r['transcript_file'] ?? '');
				if ($deg === '' && $inst === '' && $yr === '' && $cg === '' && $file === '') continue;
			?>
			<div class="border rounded p-3 mb-3 js-edu-item" data-degree="<?php echo html_escape($deg); ?>">
				<div class="d-flex justify-content-between align-items-center">
					<div>
						<div style="font-weight:700;"><?php echo html_escape($deg); ?></div>
						<div class="text-muted" style="font-size:13px;">
							<?php echo html_escape($inst); ?> &middot; <?php echo html_escape($yr); ?> &middot; <?php echo html_escape($cg); ?>
						</div>
						<div class="text-muted" style="font-size:12px;">File: <?php echo html_escape(basename($file)); ?></div>
					</div>
					<div style="display:flex;gap:8px;">
						<button type="button" class="btn btn-sm btn-outline-primary js-edu-edit">Edit</button>
						<button type="button" class="btn btn-sm btn-outline-danger js-edu-remove">Remove</button>
					</div>
				</div>
				<input type="hidden" name="degree[]" value="<?php echo html_escape($deg); ?>">
				<input type="hidden" name="institute[]" value="<?php echo html_escape($inst); ?>">
				<input type="hidden" name="passing_year[]" value="<?php echo html_escape($yr); ?>">
				<input type="hidden" name="cgpa_percentage[]" value="<?php echo html_escape($cg); ?>">
				<input type="hidden" name="transcript_file[]" value="<?php echo html_escape($file); ?>">
			</div>
		<?php endforeach; ?>
	</div>
	<div class="text-muted" style="font-size:13px;">
		Required: <strong>Master (16 years)</strong>, <strong>HSSC</strong>, <strong>SSC</strong>.
	</div>

</form>
