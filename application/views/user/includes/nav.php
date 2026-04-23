<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav nav-pills nav-sidebar flex-column nav-legacy" data-widget="treeview" role="menu" data-accordion="false">
  <li class="nav-item">
    <a href="<?php echo url('user/dashboard'); ?>" class="nav-link <?php echo ($page->menu == 'dashboard') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-tachometer-alt"></i>
      <p><?php echo lang('dashboard'); ?></p>
    </a>
  </li>
  <li class="nav-item">
    <a href="<?php echo url('user/profile'); ?>" class="nav-link <?php echo ($page->menu == 'profile') ? 'active' : ''; ?>">
      <i class="nav-icon fas fa-user"></i>
      <p>Profile</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="<?php echo url('user/logout'); ?>" class="nav-link">
      <i class="nav-icon fas fa-sign-out-alt"></i>
      <p>Logout</p>
    </a>
  </li>
</ul>
