<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Bulk Auto Mark</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo url('/admin/'); ?>"><?php echo lang('home'); ?></a></li>
          <li class="breadcrumb-item"><a href="<?php echo url('admin/settings'); ?>">Settings</a></li>
          <li class="breadcrumb-item active">Mark</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="row">
    <div class="col-sm-3">
      <?php include 'sidebar.php'; ?>
    </div>
    <div class="col-sm-9">
      <?php include viewPath('admin/includes/notifications'); ?>

      <div class="card">
        <div class="card-header with-border">
          <h3 class="card-title">Bulk auto mark pending items</h3>
        </div>
        <?php
          $selected_emarker_id = (int) ($selected_emarker_id ?? 0);
          $selected_batch_id = (int) ($selected_batch_id ?? 0);
          $selected_batch = $selected_batch ?? null;
          $mark_options = isset($mark_options) && is_array($mark_options) ? $mark_options : [];
          $default_min_mark = (string) ($default_min_mark ?? '3');
          $default_max_mark = (string) ($default_max_mark ?? '5');
        ?>
        <?php echo form_open('admin/settings/markSubmit', ['class' => 'form-validate', 'autocomplete' => 'off', 'method' => 'post']); ?>
        <div class="card-body">
          <div class="form-group">
            <label for="emarker_id">eMarker</label>
            <select name="emarker_id" id="emarker_id" class="form-control select2" required>
              <option value="">Select eMarker</option>
              <?php foreach (($emarkers ?? []) as $u): ?>
                <?php
                  $name = trim((string) ($u->name ?? ''));
                  $username = trim((string) ($u->username ?? ''));
                  $label = trim($name . ' (' . $username . ')');
                ?>
                <option value="<?php echo (int) $u->id; ?>" <?php echo $selected_emarker_id === (int) $u->id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">Only active eMarkers are shown.</small>
          </div>

          <div class="form-group">
            <label for="batch_id">Pending / In Progress Batch</label>
            <select name="batch_id" id="batch_id" class="form-control" required <?php echo $selected_emarker_id > 0 ? '' : 'disabled'; ?>>
              <option value="">Select batch</option>
              <?php foreach (($batches ?? []) as $batch): ?>
                <?php
                  $batch_label = (string) $batch->batch_code
                    . ' | ' . (string) $batch->status
                    . ' | ' . (string) $batch->question_no
                    . ' | Pending: ' . (int) ($batch->pending_items ?? 0)
                    . ' | Max: ' . rtrim(rtrim(number_format((float) ($batch->max_marks ?? 0), 2, '.', ''), '0'), '.');
                ?>
                <option value="<?php echo (int) $batch->id; ?>" <?php echo $selected_batch_id === (int) $batch->id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($batch_label, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">Only batches with pending items are listed.</small>
          </div>

          <?php if ($selected_batch): ?>
            <div class="alert alert-info">
              <strong>Selected Batch:</strong>
              <?php echo htmlspecialchars((string) $selected_batch->batch_code, ENT_QUOTES, 'UTF-8'); ?>
              |
              <?php echo htmlspecialchars((string) $selected_batch->assessment_type, ENT_QUOTES, 'UTF-8'); ?>
              |
              Grade <?php echo (int) $selected_batch->grade; ?>
              |
              Subject <?php echo htmlspecialchars((string) $selected_batch->subject_code, ENT_QUOTES, 'UTF-8'); ?>
              |
              Question <?php echo htmlspecialchars((string) $selected_batch->question_no, ENT_QUOTES, 'UTF-8'); ?>
              |
              Pending <?php echo (int) ($selected_batch->pending_items ?? 0); ?>
              |
              Max Marks <?php echo htmlspecialchars(rtrim(rtrim(number_format((float) ($selected_batch->max_marks ?? 0), 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="min_mark">Minimum Random Mark</label>
              <select name="min_mark" id="min_mark" class="form-control" required <?php echo $selected_batch ? '' : 'disabled'; ?>>
                <option value="">Select minimum mark</option>
                <?php foreach ($mark_options as $option): ?>
                  <option value="<?php echo htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string) $option === $default_min_mark) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label for="max_mark">Maximum Random Mark</label>
              <select name="max_mark" id="max_mark" class="form-control" required <?php echo $selected_batch ? '' : 'disabled'; ?>>
                <option value="">Select maximum mark</option>
                <?php foreach ($mark_options as $option): ?>
                  <option value="<?php echo htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string) $option === $default_max_mark) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <small class="text-muted d-block">Each pending item in the selected batch will be marked as the selected eMarker with a random mark between the chosen minimum and maximum.</small>
        </div>
        <div class="card-footer">
          <button type="submit" class="btn btn-primary" <?php echo $selected_batch ? '' : 'disabled'; ?>>Bulk Auto Mark</button>
          <a href="<?php echo url('admin/settings/mark'); ?>" class="btn btn-secondary">Reset</a>
        </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</section>

<?php include viewPath('admin/includes/footer'); ?>

<script>
  $(function () {
    $('.form-validate').validate();

    if ($.fn.select2) {
      $('.select2').select2({
        width: '100%',
        placeholder: 'Select eMarker',
        allowClear: true
      });
    }

    $('#emarker_id').on('change', function () {
      var emarkerId = $(this).val() || '';
      var target = '<?php echo url('admin/settings/mark'); ?>';
      if (emarkerId !== '') {
        window.location.href = target + '?emarker_id=' + encodeURIComponent(emarkerId);
      } else {
        window.location.href = target;
      }
    });

    $('#batch_id').on('change', function () {
      var emarkerId = $('#emarker_id').val() || '';
      var batchId = $(this).val() || '';
      var target = '<?php echo url('admin/settings/mark'); ?>';
      if (emarkerId === '') {
        window.location.href = target;
        return;
      }
      if (batchId !== '') {
        window.location.href = target + '?emarker_id=' + encodeURIComponent(emarkerId) + '&batch_id=' + encodeURIComponent(batchId);
      } else {
        window.location.href = target + '?emarker_id=' + encodeURIComponent(emarkerId);
      }
    });

    $('form').on('submit', function (e) {
      var minMark = parseFloat($('#min_mark').val());
      var maxMark = parseFloat($('#max_mark').val());
      if (!isNaN(minMark) && !isNaN(maxMark) && minMark > maxMark) {
        e.preventDefault();
        alert('Minimum mark cannot be greater than maximum mark.');
      }
    });
  });
</script>
