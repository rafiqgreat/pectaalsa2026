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

	<div class="cardx">
		<div class="alert alert-danger" style="border-radius:6px;">
			<h4 class="mb-2" style="font-weight:700;">Registration is closed.</h4>
			<?php if (!empty($close_at_display)): ?>
				<div>The last date for registration was: <?php echo html_escape((string) $close_at_display); ?></div>
			<?php endif; ?>
		</div>

		<div class="d-flex" style="gap:10px; flex-wrap:wrap;">
			<a class="btn btn-primary" href="<?php echo html_escape((string) $login_url); ?>">Login</a>
			<a class="btn btn-success" href="<?php echo html_escape((string) $resume_url); ?>">Resume Registration</a>
		</div>
	</div>
</div>

<script src="<?php echo $assets; ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo $assets; ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

