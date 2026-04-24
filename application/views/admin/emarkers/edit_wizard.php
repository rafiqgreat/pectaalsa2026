<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<?php include viewPath('admin/includes/header'); ?>
<?php
	$w = isset($wizard) && is_array($wizard) ? $wizard : [];
	$step = (int) ($w['step'] ?? 1);
	$user_id = (int) ($w['user_id'] ?? 0);
	$allowed_step = (int) ($w['allowed_step'] ?? 8);
	$step_titles = $w['step_titles'] ?? [];
	$step_keys = $w['step_keys'] ?? [];
	$steps_row = $w['steps_row'] ?? null;
	$step_view = (string) ($w['step_view'] ?? '');
	$form_action = (string) ($w['form_action'] ?? '');
	$form_data = $w['form_data'] ?? [];
?>

<style>
	.wizard-topbar { padding: 14px 12px; background: #f4f6f9; }
	.wizard-steps { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
	.wizard-steps li { display: flex; align-items: center; color: #6b7280; font-size: 14px; }
	.wizard-steps a { color: inherit; text-decoration: none; }
	.wizard-steps .sep { margin: 0 6px; color: #94a3b8; }
	.wizard-step-circle { width: 22px; height: 22px; border-radius: 999px; border: 1px solid #9ca3af; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 6px; }
	.wizard-step-active { color: #2563eb; font-weight: 600; }
	.wizard-step-active .wizard-step-circle { border-color: #2563eb; color: #2563eb; }
	.wizard-step-done { color: #1f2937; }
	.wizard-step-done .wizard-step-circle { border-color: #1f2937; }
	.wizard-step-disabled { color: #9ca3af; cursor: not-allowed; pointer-events: none; }
	.wizard-card { background: #fff; border: 1px solid rgba(15, 23, 42, 0.12); border-radius: 6px; padding: 22px; }
	.btn-primary-full { width: 100%; padding: 14px 10px; font-weight: 600; background: #1e5aa8; border-color: #1e5aa8; }
	.btn-primary-full:hover { background: #184a89; border-color: #184a89; }
	.upload-box { border: 2px dashed #2b6cb0; border-radius: 4px; padding: 16px; text-align: center; cursor: pointer; color: #1e5aa8; }
	.upload-box.is-invalid { border-color: #dc3545; color: #dc3545; }
	.upload-meta { margin-top: 10px; font-size: 13px; color: #111827; }
	.upload-meta .label { font-weight: 600; }
	.remove-link { color: #a61b1b; font-size: 13px; cursor: pointer; }
	.add-more-link { color: #1e5aa8; font-size: 13px; cursor: pointer; float: right; }
</style>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>Edit E-Marker Profile</h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
					<li class="breadcrumb-item"><a href="<?php echo url('admin/emarkers'); ?>">E-Markers</a></li>
					<li class="breadcrumb-item"><a href="<?php echo url('admin/emarkers/view/' . $user_id); ?>">View</a></li>
					<li class="breadcrumb-item active">Edit</li>
				</ol>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<div class="wizard-topbar">
			<ul class="wizard-steps">
				<?php foreach ($step_titles as $i => $title): ?>
					<?php
						$is_active = ($step === (int) $i);
						$is_allowed = ((int) $i <= (int) $allowed_step);
						$is_done = false;
						if (!empty($steps_row) && $user_id > 0) {
							$key = $step_keys[$i] ?? '';
							$flag = $key ? ($key . '_completed') : '';
							$is_done = $flag && !empty($steps_row->{$flag});
						}
						$cls = $is_active ? 'wizard-step-active' : ($is_done ? 'wizard-step-done' : '');
						$cls .= $is_allowed ? '' : ' wizard-step-disabled';
						$href = $is_allowed ? site_url('admin/emarkers/edit/' . $user_id . '/' . (int) $i) : 'javascript:void(0)';
					?>
					<li class="<?php echo trim($cls); ?>">
						<a href="<?php echo $href; ?>">
							<span class="wizard-step-circle"><?php echo (int) $i; ?></span>
							<?php echo html_escape($title); ?>
						</a>
						<?php if ((int) $i < 8): ?><span class="sep">&raquo;</span><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<div class="wizard-card mt-3">
			<?php if ($step_view): ?>
				<?php $this->load->view($step_view, $form_data); ?>
			<?php endif; ?>

			<div class="mt-4">
				<button
					type="button"
					class="btn btn-primary btn-primary-full js-save-next"
					data-step="<?php echo (int) $step; ?>"
					data-action="<?php echo html_escape($form_action); ?>"
					data-final="<?php echo ((int) $step === 8) ? '1' : '0'; ?>"
				><?php echo ((int) $step === 8) ? 'Save' : 'Save & Next'; ?></button>
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

<?php include viewPath('admin/includes/footer'); ?>

<script src="<?php echo $url->assets ?>plugins/inputmask/jquery.inputmask.min.js"></script>
<script>
	window.SIGNUP_WIZARD = {
		uploadUrl: "<?php echo html_escape((string) ($w['upload_url'] ?? '')); ?>",
		deleteUrl: "<?php echo html_escape((string) ($w['delete_url'] ?? '')); ?>",
		step: <?php echo (int) $step; ?>
	};
</script>
<script src="<?php echo base_url('assets/js/signup_wizard.js'); ?>"></script>

