<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1><?php echo htmlspecialchars((string) ($page->title ?? 'QC - Create Batch')); ?></h1>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<?php include viewPath('admin/includes/notifications'); ?>

		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Create QC Batch (10 images per question)</h3>
			</div>
			<div class="card-body">
				<form method="post" action="<?php echo url('admin/qc_marking/create_batch'); ?>">
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label>Assessment Type</label>
								<select name="assessment_type" class="form-control" required>
									<option value="CRQ">CRQ</option>
									<option value="DICTATION">Dictation</option>
								</select>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Grade</label>
								<input type="number" name="grade" class="form-control" value="4" required>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Subject</label>
								<select name="subject_code" class="form-control" required>
									<?php foreach (($subject_options ?? []) as $code => $name): ?>
										<option value="<?php echo htmlspecialchars((string) $code); ?>"><?php echo htmlspecialchars((string) $name); ?> (<?php echo htmlspecialchars((string) $code); ?>)</option>
									<?php endforeach; ?>
								</select>
								<small class="text-muted">SS must have this subject in their assigned subjects.</small>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label>Version</label>
								<input type="text" name="version" class="form-control" placeholder="blank = all versions">
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label>Assign To (SS)</label>
								<select name="assigned_to" class="form-control" required>
									<option value="">Select SS</option>
									<?php foreach (($ss_options ?? []) as $u): ?>
										<option value="<?php echo (int) ($u->id ?? 0); ?>"><?php echo htmlspecialchars((string) ($u->name ?? '')); ?> (<?php echo htmlspecialchars((string) ($u->username ?? '')); ?>)</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>
					<button type="submit" class="btn btn-primary">Create QC Batch</button>
				</form>
			</div>
		</div>
	</div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

