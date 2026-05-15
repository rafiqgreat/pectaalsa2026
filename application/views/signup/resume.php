<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Teacher Registration</title>

	<link rel="stylesheet" href="<?php echo $assets; ?>plugins/fontawesome-free/css/all.min.css">
	<link rel="stylesheet" href="<?php echo $assets; ?>css/adminlte.min.css">

	<style>
		body { background: #f3f4f6; }
		.wrap { max-width: 900px; margin: 30px auto; padding: 0 10px; }
		.cardx { background: #fff; border: 1px solid rgba(15, 23, 42, 0.12); border-radius: 6px; padding: 22px; }
		.title { text-align:center; font-size:34px; font-weight:700; letter-spacing:0.5px; margin-bottom: 18px; }
	</style>
</head>
<body>

<div class="wrap">
	<div class="title">TEACHER'S REGISTRATION</div>
	<?php $max_dob = date('Y-m-d', strtotime('-18 years')); ?>

	<div class="cardx mb-3">
		<div class="alert alert-warning" style="border-radius:6px;">
			<strong>Integrity Affidavit Required:</strong>
			Please download the affidavit, sign it, and upload the signed copy in <strong>Step 7 (Security Setup)</strong> to complete registration.
			<a href="<?php echo base_url('assets/IntegrityAffidavit/IntegrityAffidavit.pdf'); ?>" target="_blank" style="font-weight:600;">Download IntegrityAffidavit.pdf</a>
		</div>

		<div class="row">
			<div class="col-md-6">
				<h4 style="font-weight:700;">Start New Registration</h4>
				<p class="text-muted mb-2">Begin a new registration process.</p>
				<a class="btn btn-primary" href="<?php echo site_url(rtrim($wizard_base, '/') . '/step/1'); ?>">Start</a>
			</div>

			<div class="col-md-6">
				<h4 style="font-weight:700;">Resume Registration</h4>
				<p class="text-muted mb-2">Enter CNIC and Date of Birth to continue your saved steps.</p>

				<?php if (!empty($resume_error)): ?>
					<div class="alert alert-danger"><?php echo html_escape($resume_error); ?></div>
				<?php endif; ?>

				<form method="post" action="<?php echo site_url('user/login/register/resume_submit'); ?>" autocomplete="off">
					<div class="form-group">
						<label>CNIC</label>
						<input type="text" class="form-control" name="cnic" placeholder="12345-1234567-1" value="<?php echo html_escape($resume_cnic ?? ''); ?>" required>
					</div>
					<div class="form-group">
						<label>Date of Birth</label>
						<input type="date" class="form-control" name="dob" max="<?php echo html_escape($max_dob); ?>" value="<?php echo html_escape($resume_dob ?? ''); ?>" required>
					</div>
					<button type="submit" class="btn btn-success">Resume</button>
				</form>
				<div class="text-muted" style="font-size:12px;margin-top:8px;">
					After resuming, you will be redirected to your last saved step and see a green “Resumed registration…” message.
				</div>
			</div>
		</div>
	</div>
</div>

<script src="<?php echo $assets; ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo $assets; ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
