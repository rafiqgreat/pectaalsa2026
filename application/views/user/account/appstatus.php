<?php
defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="<?php echo getUserlang() ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Status</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="card shadow-sm">
      <div class="card-body p-5 text-center">
        <h1 class="h3 mb-3">Legacy Status Lookup Removed</h1>
        <p class="mb-4">The previous application-status workflow depended on tables that have already been dropped. This page is now disabled as part of the project cleanup.</p>
        <a href="<?php echo base_url('user/login'); ?>" class="btn btn-primary">Back to Login</a>
      </div>
    </div>
  </div>
</body>
</html>
