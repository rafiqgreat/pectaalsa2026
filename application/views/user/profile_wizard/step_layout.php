<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
	// Inject page-specific scripts into shared footer.
	ob_start();
?>
<script src="<?php echo $assets; ?>plugins/inputmask/jquery.inputmask.min.js"></script>
<script>
	window.SIGNUP_WIZARD = {
		uploadUrl: "<?php echo site_url(rtrim($wizard_base, '/') . '/upload_file'); ?>",
		deleteUrl: "<?php echo site_url(rtrim($wizard_base, '/') . '/delete_file'); ?>",
		step: <?php echo (int) $step; ?>
	};
</script>
<script src="<?php echo base_url('assets/js/signup_wizard.js'); ?>"></script>
<?php
	$extra_scripts = ob_get_clean();
?>

<?php include viewPath('user/includes/header'); ?>

<style>
	.profile-wizard-top { margin-top: 6px; }
	.profile-banner { color:#2563eb; font-weight:600; text-align:center; padding: 10px 0; }
	.wizard-topbar { padding: 10px 8px; background: #f3f4f6; border-radius: 4px; }
	.wizard-steps { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
	.wizard-steps li { display: flex; align-items: center; color: #6b7280; font-size: 14px; }
	.wizard-steps a { color: inherit; text-decoration: none; }
	.wizard-steps .sep { margin: 0 6px; color: #94a3b8; }
	.wizard-step-circle { width: 22px; height: 22px; border-radius: 999px; border: 1px solid #9ca3af; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 6px; }
	.wizard-step-active { color: #2563eb; font-weight: 600; }
	.wizard-step-active .wizard-step-circle { border-color: #2563eb; color: #2563eb; }
	.wizard-step-done { color: #1f2937; }
	.wizard-step-done .wizard-step-circle { border-color: #1f2937; }

	.wizard-card { background: #fff; border: 1px solid rgba(37,99,235,0.25); border-radius: 6px; padding: 22px; }
	.btn-primary-full { width: 100%; padding: 14px 10px; font-weight: 600; background: #1e5aa8; border-color: #1e5aa8; }
	.btn-primary-full:hover { background: #184a89; border-color: #184a89; }

	.upload-box { border: 2px dashed #2b6cb0; border-radius: 4px; padding: 16px; text-align: center; cursor: pointer; color: #1e5aa8; }
	.upload-box.is-invalid { border-color: #dc3545; color: #dc3545; }
	.upload-meta { margin-top: 10px; font-size: 13px; color: #111827; }
	.upload-meta .label { font-weight: 600; }
	.remove-link { color: #a61b1b; font-size: 13px; cursor: pointer; }
	.add-more-link { color: #1e5aa8; font-size: 13px; cursor: pointer; float: right; }
</style>

<section class="content-header profile-wizard-top">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<ol class="breadcrumb mb-0">
					<li class="breadcrumb-item"><a href="<?php echo url('/user/'); ?>"><?php echo lang('home'); ?></a></li>
					<li class="breadcrumb-item">Evaluator Profile</li>
					<li class="breadcrumb-item active"><strong>View Profile</strong></li>
				</ol>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<div class="profile-banner">
			<i class="far fa-info-circle"></i> Your Profile will be reviewed again.
		</div>

		<div class="wizard-topbar">
			<ul class="wizard-steps">
				<?php foreach ($step_titles as $i => $title): ?>
					<?php
						$is_active = ((int)$step === (int)$i);
						$is_done = false;
						if (!empty($steps_row)) {
							$key = $step_keys[$i] ?? '';
							$flag = $key ? ($key . '_completed') : '';
							$is_done = $flag && !empty($steps_row->{$flag});
						}
						$cls = $is_active ? 'wizard-step-active' : ($is_done ? 'wizard-step-done' : '');
						$href = site_url(rtrim($wizard_base, '/') . '/step/' . $i);
					?>
					<li class="<?php echo trim($cls); ?>">
						<a href="<?php echo $href; ?>">
							<span class="wizard-step-circle"><?php echo (int) $i; ?></span>
							<?php echo html_escape($title); ?>
						</a>
						<?php if ($i < 8): ?><span class="sep">&raquo;</span><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="mt-3">
			<div class="wizard-card">
				<?php $this->load->view($step_view, $form_data); ?>
				<div class="mt-4">
					<button
						type="button"
						class="btn btn-primary btn-primary-full js-save-next"
						data-step="<?php echo (int) $step; ?>"
						data-action="<?php echo html_escape($form_action); ?>"
						data-final="<?php echo ((int)$step === 8) ? '1' : '0'; ?>"
					><?php echo ((int)$step === 8) ? 'Update' : 'Save & Next'; ?></button>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmMoveModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content" style="border-radius:8px;">
			<div class="modal-body text-center p-4">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position:absolute;right:14px;top:10px;">
					<span aria-hidden="true">&times;</span>
				</button>
				<h2 class="mb-2" style="font-size:34px;font-weight:600;">Confirmation</h2>
				<p class="mb-4" style="font-size:16px;color:#111827;">Are you sure this section is completed?</p>
				<div class="d-flex justify-content-center align-items-center" style="gap:26px;">
					<button type="button" class="btn btn-link text-secondary js-confirm-cancel" data-dismiss="modal">No, cancel</button>
					<button type="button" class="btn btn-primary px-4 js-confirm-yes" style="background:#1e5aa8;border-color:#1e5aa8;">Yes, Move Forward</button>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include viewPath('user/includes/footer'); ?>

