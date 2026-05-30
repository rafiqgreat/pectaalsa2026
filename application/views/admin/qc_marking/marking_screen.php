<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<?php
$item = $marking['item'];
$steps = $marking['steps'] ?? [];
$mark = $marking['mark'] ?? null;
$mark_steps = $marking['mark_steps'] ?? [];
$image_url = base_url((string) $item->image_path);
$total = (int) ($batch_total_items ?? 0);
$idx = (int) ($batch_current_index ?? 0);
$timer_seconds = (int) ($timer_seconds ?? 15);
if ($timer_seconds < 0) $timer_seconds = 0;
$is_urdu_subject = ((string) ($item->subject_code ?? '') === '2');
$rubric_title = trim((string) ($item->rubric_title ?? ''));
?>

<section class="content">
	<div class="container-fluid">
		<?php include viewPath('admin/includes/notifications'); ?>

		<div class="row">
			<div class="col-lg-8">
				<div class="card">
					<div class="card-header">
						<strong>QC Marking</strong>
						<span class="text-muted ml-2"><?php echo htmlspecialchars((string) ($batch->batch_code ?? '')); ?></span>
						<span class="float-right text-muted">Item <?php echo (int) $idx; ?>/<?php echo (int) $total; ?></span>
					</div>
					<div class="card-body p-2">
						<img src="<?php echo $image_url; ?>" alt="Image" style="width:100%;height:auto;border:1px solid #e5e7eb;">
					</div>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="card">
					<div class="card-header">
						<strong><?php echo htmlspecialchars((string) ($rubric_title !== '' ? $rubric_title : 'Marking')); ?></strong>
					</div>
					<div class="card-body">
						<form method="post" action="<?php echo url('admin/qc_marking/save_marks'); ?>">
							<input type="hidden" name="batch_id" value="<?php echo (int) ($batch->id ?? 0); ?>">
							<input type="hidden" name="batch_item_id" value="<?php echo (int) ($item->id ?? 0); ?>">

							<div class="form-group">
								<label>Action</label>
								<select name="action" class="form-control">
									<option value="MARKED">Marked</option>
									<option value="SKIPPED">Skipped</option>
									<option value="NOT_ATTEMPTED">Not Attempted</option>
									<option value="RECHECK">Recheck</option>
								</select>
							</div>

							<div class="form-group">
								<label>Marks Obtained</label>
								<input type="number" step="0.01" name="marks_obtained" class="form-control" value="<?php echo htmlspecialchars((string) ($mark->marks_obtained ?? '0')); ?>">
								<small class="text-muted">Max: <?php echo htmlspecialchars((string) ($item->max_marks ?? '0')); ?></small>
							</div>

							<?php if (!empty($steps)): ?>
								<div class="form-group">
									<label>Rubric Steps</label>
									<?php foreach ($steps as $s): ?>
										<div class="border rounded p-2 mb-2">
											<div style="font-weight:600;"><?php echo htmlspecialchars((string) ($s->step_title ?? 'Step')); ?></div>
											<input type="text" class="form-control mt-1" name="steps[<?php echo (int) ($s->id ?? 0); ?>]" value="">
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<div class="form-group">
								<label>Remarks</label>
								<textarea name="remarks" class="form-control" rows="2"><?php echo htmlspecialchars((string) ($mark->remarks ?? '')); ?></textarea>
							</div>

							<button type="submit" class="btn btn-primary btn-block">Save & Next</button>
							<a href="<?php echo url('admin/qc_marking/view_batch/' . (int) ($batch->id ?? 0)); ?>" class="btn btn-outline-secondary btn-block mt-2">Back to Batch</a>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

