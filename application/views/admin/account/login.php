<?php
defined('BASEPATH') or exit('No direct script access allowed');  ?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css'); ?>">
  <title>Login - PEF</title>
  <!-- Tailwind CSS via CDN -->
  <!-- Add this in the <head> section -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Custom CSS for Main Color and Urdu Font -->

</head>

<body class="bg-gray-100 flex flex-col min-h-screen">
  <!-- Header with Two Logos -->
  <header class="w-full bg-gray-300 text-dark px-3 py-2">
    <div class="container mx-auto flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <img src="<?= base_url('assets/images/Logo_1.png'); ?>" alt="PEIMA Logo" class="h-20" />
        <div class="flex flex-col">
          <h3 class="text-xl font-bold text-dark">PECTAA LSA 2026 - DIGITAL WEB SOLUTION</h3>
          <h4 class="text-lg text-dark">Digital Barcoding, Scanning, Emarking, AI Analysis - A Complete Solution</h4>
        </div>
      </div>
      <div class="space-x-2">
        <?php /*?><a href="./Login.html"
                    class="p-2 cursor-pointer bg-green-400 rounded-lg p-4 text-dark font-semibold">Login</a><?php */ ?>

        <?php /*?><a href="./Admin_login.html"
                    class="p-2 cursor-pointer bg-white bg-blue-500
                     p-4 rounded-lg text-dark font-semibold">Admin Login</a><?php */ ?>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="flex-grow container mx-auto mt-1 md:mt-1 px-2 py-4 ">

    <!-- Login Form Container -->
    <div class="grid grid-cols-1 md:grid-cols-[60%_40%] gap-8 items-center pr-5">

      <!-- Left Side: Image/Information -->
      <div class="hidden md:block">
        <img src="<?= base_url('assets/images/login_pics.jpg'); ?>" alt="PEIMA Initiative" class="rounded-lg shadow-md">
        <p class="mt-8 text-gray-600 text-sm italic">
          Empowering Education in Punjab. Join us in reshaping the future of our schools.
        </p>
      </div>

      <!-- Right Side: Login Form -->
      <div class="bg-white shadow-lg rounded-lg p-2 md:pr-12 pl-12 pt-4 pb-4">
        <?php if (!empty($this->session->flashdata('message'))): ?>
          <div class="alert alert-<?php echo $this->session->flashdata('message_type'); ?>">
            <p><?php echo $this->session->flashdata('message'); ?></p>
          </div>
        <?php endif; ?>
        <?php /*?><h2 class="text-3xl font-semibold text-main mb-6 text-center">Login</h2><?php */ ?>

        <!-- Urdu Instructions -->
        <div class="text-center mb-4">
          <h1 class="text-red-600">ADMINISTRATION LOGIN</h1>
        </div>

        <?php echo form_open('/admin/login/check', ['method' => 'POST', 'autocomplete' => 'off', 'class' => 'space-y-4']); ?>

        <!-- Username Field -->
        <div class="pl-10 pr-10">
          <label for="username" class="block font-bold text-gray-700 mb-2">Admin Username</label>
          <input type="text" name="username" id="username" class="form-control w-full p-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" placeholder="<?php echo 'username' ?>" value="<?php echo post('username') ?>" autofocus required>
          <?php echo form_error('username', '<span style="display:block" class="error invalid-feedback">', '</span>'); ?>
        </div>

        <!-- Password Field -->
        <div class="pl-10 pr-10">
          <label for="password" class="block font-bold text-gray-700 mb-2">Password:</label>
          <input type="password" id="password" name="password" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" required>
          <?php echo form_error('password', '<span style="display:block" class="error invalid-feedback">', '</span>'); ?>
        </div class="pl-10 pr-10">

        <!-- Login Button -->
        <div class="pl-10 pr-10 mt-4">
          <button type="submit" class="btn-main py-2 px-4 w-full rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 block text-center"><?php echo lang('signin') ?></button>
        </div>
        <!-- Register Link -->

        <?php /*?><p class="text-md text-gray-600 text-center">
                        Don't have an account? <a href="./register.html" class="text-main underline">Register here</a>.
                    </p><?php */ ?>
        <?php echo form_close(); ?>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-200 text-center py-4 mt-8">
    <p class="text-gray-600">© <?php echo date('Y'); ?> PECTAA</p>
  </footer>
</body>

<?php /*?><?php include 'includes/header.php' ?>

<div class="login-box">
  <div class="login-logo">
    <a href="<?php echo url('/admin/') ?>"><?php echo setting('company_name') ?></a>
  </div>
  <?php if(isset($message)): ?>
      <div class="alert alert-<?php echo $message_type ?>">
        <p><?php echo $message ?></p>
      </div>
    <?php endif; ?>

    <?php if(!empty($this->session->flashdata('message'))): ?>
      <div class="alert alert-<?php echo $this->session->flashdata('message_type'); ?>">
        <p><?php echo $this->session->flashdata('message') ?></p>
      </div>
    <?php endif; ?>
  <!-- /.login-logo -->
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg"><?php echo lang('sign_in_session') ?></p>

      <?php echo form_open('/login/check', ['method' => 'POST', 'autocomplete' => 'off']); ?> 
      <div class="input-group mb-3">
          <input type="text" name="username" required class="form-control" placeholder="<?php echo lang('username_or_email') ?>" value="<?php echo post('username') ?>" autofocus>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
          <?php echo form_error('username', '<span style="display:block" class="error invalid-feedback">', '</span>'); ?>
        </div>

        <div class="input-group mb-3">
          <input type="password" name="password" required class="form-control" placeholder="<?php echo lang('user_password') ?>">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
          <?php echo form_error('password', '<span style="display:block" class="error invalid-feedback">', '</span>'); ?>
        </div>

      <?php if (setting('google_recaptcha_enabled') == '1'): ?>
        
      <script src="https://www.google.com/recaptcha/api.js" async defer></script>
      
      <div class="form-group">
        <div class="g-recaptcha" data-sitekey="<?php echo setting('google_recaptcha_sitekey') ?>"></div>
        <?php echo form_error('g-recaptcha-response', '<span style="display:block" class="error invalid-feedback">', '</span>'); ?>
      </div>

      <?php endif ?>

      <div class="row">
        <div class="col-8">
          <div class="checkbox icheck">
            <label>
              <input type="checkbox" <?php echo post('remember_me')?'checked':'' ?> name="remember_me" /> <?php echo lang('remember_me') ?>
            </label>
          </div>
        </div>
        <!-- /.col -->

        <div class="col-4">
          <button type="submit" class="btn btn-primary btn-block btn-flat"><?php echo lang('signin') ?></button>
        </div>
        <!-- /.col -->
      </div>
    <?php echo form_close(); ?>


      <p class="mb-1">
        <a href="<?php echo url('admin/login/forget?username='.post('username')) ?>"><?php echo lang('forget_password_?') ?></a><br>
      </p>
      <p class="mb-0">
        <!-- <a href="register.html" class="text-center">Register a new membership</a> -->
      </p>
    </div>
    <!-- /.login-card-body -->
  </div>
</div><?php */ ?>
<!-- /.login-box -->


<?php include viewPath('admin/includes/footer'); ?>
