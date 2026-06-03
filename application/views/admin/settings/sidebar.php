<?php
defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Default card -->
  <div class="card">
    <div class="card-header with-border">
      <h3 class="card-title"><?php echo lang('settings') ?></h3>
    </div>
    <ul class="list-group">
     
      <?php if (hasPermissions('general_settings')): ?>
        <a class="list-group-item list-group-item-action <?php echo ($page->submenu=='general')?'active':'' ?>" href="<?php echo url('admin/settings/general') ?>"><?php echo lang('general_setings') ?></a>
      <?php endif ?>
      <?php if (hasPermissions('general_settings')): ?>
        <a class="list-group-item list-group-item-action <?php echo ($page->submenu=='registration')?'active':'' ?>" href="<?php echo url('admin/settings/registration') ?>">Registration</a>
      <?php endif ?>
      <?php if (hasPermissions('general_settings')): ?>
        <a class="list-group-item list-group-item-action <?php echo ($page->submenu=='sync_eng_crqs')?'active':'' ?>" href="<?php echo url('admin/settings/sync_eng_crqs') ?>">Synchronize Eng CRQs</a>
      <?php endif ?>
      <?php if (hasPermissions('general_settings')): ?>
        <a class="list-group-item list-group-item-action <?php echo ($page->submenu=='sync_urdu_crqs')?'active':'' ?>" href="<?php echo url('admin/settings/sync_urdu_crqs') ?>">Synchronize Urdu CRQs</a>
      <?php endif ?>
      <?php if (hasPermissions('general_settings')): ?>
        <a class="list-group-item list-group-item-action <?php echo ($page->submenu=='sync_math_crqs')?'active':'' ?>" href="<?php echo url('admin/settings/sync_math_crqs') ?>">Synchronize Math CRQs</a>
      <?php endif ?>
      <?php if (hasPermissions('general_settings')): ?>
        <a class="list-group-item list-group-item-action <?php echo ($page->submenu=='sync_science_crqs')?'active':'' ?>" href="<?php echo url('admin/settings/sync_science_crqs') ?>">Synchronize Science CRQs</a>
      <?php endif ?>
      <?php if (hasPermissions('company_settings')): ?>
        <a class="list-group-item list-group-item-action <?php echo ($page->submenu=='company')?'active':'' ?>" href="<?php echo url('admin/settings/company') ?>"><?php echo lang('company_setings') ?></a>
      <?php endif ?>
     
      <?php if (hasPermissions('email_templates')): ?>
        <a class="list-group-item list-group-item-action <?php echo ($page->submenu=='email_templates')?'active':'' ?>" href="<?php echo url('admin/settings/email_templates') ?>"><?php echo lang('email_templates') ?></a>
      <?php endif ?>
      
    </ul>
  </div>
