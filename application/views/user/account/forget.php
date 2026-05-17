<?php
defined('BASEPATH') or exit('No direct script access allowed');  ?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css'); ?>">
  <title>Login - PECTAA</title>
  <!-- Tailwind CSS via CDN -->
  <!-- Add this in the <head> section -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- jQuery CDN (latest version) -->
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

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
        <a href="<?php echo url('user/login/register') ?>"
          class="p-2 cursor-pointer bg-white rounded-lg p-4 text-dark font-semibold">Register New Account</a>
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
        <p class="mt-2 text-gray-600 text-sm italic">
          Empowering Education in Punjab. Join us in reshaping the future of our schools.
        </p>
      </div>

      <!-- Right Side: Login Form -->
      <div class="bg-white shadow-lg rounded-lg p-2 md:pr-12 pl-12 pt-3 pb-2">
        <?php if (!empty($this->session->flashdata('message'))): ?>
          <div class="alert alert-<?php echo $this->session->flashdata('message_type'); ?>">
            <p><?php echo $this->session->flashdata('message'); ?></p>
          </div>
        <?php endif; ?>
        <div class="text-center font-weight-bold display-6 pb-4">
          Reset Password
        </div>

        <?php /*?><h2 class="text-3xl font-semibold text-main mb-6 text-center">Login</h2><?php */ ?>

        <!-- Urdu Instructions -->


        <?php echo form_open('/user/login/reset_password', ['method' => 'POST', 'autocomplete' => 'off']); ?>
        <div class="input-group mb-3 pl-10 pr-10 pb-2">
          <input type="text" class="form-control" placeholder="<?php echo lang('username_or_email') ?>" value="<?php echo !empty(post('username')) ? post('username') : get('username')  ?>" name="username" autofocus />

          <?php echo form_error('username', '<span style="display:block" class="error invalid-feedback">', '</span>'); ?>
        </div>
        <div class="row mb-3 pl-10 pr-10 pb-2">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block" style="width:100%"><?php echo lang('request_password') ?></button>
          </div>
          <!-- /.col -->
        </div>
        <?php echo form_close(); ?>

        <p class="mt-3 mb-3 pl-10 pr-10 pb-2">
          For signin click here : <a href="<?php echo url('user/login') ?>"><?php echo lang('signin') ?></a>
        </p>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-200 text-center py-4 mt-6">
    <p class="text-gray-600">© <?php echo date('Y'); ?> PECTAA</p>
  </footer>
</body>
