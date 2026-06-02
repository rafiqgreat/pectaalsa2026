<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">E-Marking Batches</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item active">Batches</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <?php include viewPath('admin/includes/notifications'); ?>

    <div class="card">
      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between">
          <h3 class="card-title mb-0">Batches</h3>
          <div>
            <?php if (in_array((int) logged('role'), [1, 17], true)): ?>
              <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('admin/emarking/emarker_timers'); ?>">
                <i class="fas fa-stopwatch"></i> eMarker Timers
              </a>
            <?php endif; ?>
            <a class="btn btn-primary btn-sm" href="<?php echo base_url('admin/emarking/create_batch'); ?>">
              <i class="fas fa-plus"></i> Create Batch
            </a>
          </div>
        </div>
      </div>
      <div class="card-body">
        <?php
          $subject_code_map = [
            '1' => 'ENGLISH',
            '2' => 'URDU',
            '3' => 'MATH',
            '4' => 'SCIENCE',
          ];
        ?>
        <form method="get" class="mb-3">
          <div class="form-row">
            <div class="col-md-2 mb-2">
              <select name="status" class="form-control">
                <option value="">Status (All)</option>
                <?php foreach (['PENDING','IN_PROGRESS','COMPLETED','FINALIZED'] as $st): ?>
                  <option value="<?php echo $st; ?>" <?php echo (($filters['status'] ?? '') === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <select name="assessment_type" class="form-control">
                <option value="">Assessment (All)</option>
                <option value="CRQ" <?php echo (($filters['assessment_type'] ?? '') === 'CRQ') ? 'selected' : ''; ?>>CRQ</option>
                <option value="DICTATION" <?php echo (($filters['assessment_type'] ?? '') === 'DICTATION') ? 'selected' : ''; ?>>Dictation</option>
              </select>
            </div>
            <div class="col-md-1 mb-2">
              <input type="hidden" name="grade" value="4">
              <select class="form-control" disabled>
                <option value="4" selected>Grade 4</option>
              </select>
            </div>
            <div class="col-md-2 mb-2">
              <?php $subject_code_val = (string) ($filters['subject_code'] ?? ''); ?>
              <select name="subject_code" class="form-control">
                <option value="">Subject (All)</option>
                <?php
                  $subject_options = isset($subject_options) && is_array($subject_options) ? $subject_options : [];
                  foreach ($subject_options as $code => $name):
                ?>
                  <option value="<?php echo (int) $code; ?>" <?php echo ($subject_code_val === (string) $code) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-1 mb-2">
              <input type="text" name="assigned_to" value="<?php echo htmlspecialchars((string) ($filters['assigned_to'] ?? '')); ?>" class="form-control" placeholder="eMarker ID">
            </div>
            <div class="col-md-1 mb-2">
              <input type="text" name="question_id" value="<?php echo htmlspecialchars((string) ($filters['question_id'] ?? '')); ?>" class="form-control" placeholder="Question ID">
            </div>
            <div class="col-md-1 mb-2">
              <?php $selected_per_page = (int) ($filters['per_page'] ?? 100); ?>
              <select name="per_page" class="form-control">
                <option value="100" <?php echo ($selected_per_page === 100) ? 'selected' : ''; ?>>100 / page</option>
                <option value="200" <?php echo ($selected_per_page === 200) ? 'selected' : ''; ?>>200 / page</option>
                <option value="500" <?php echo ($selected_per_page === 500) ? 'selected' : ''; ?>>500 / page</option>
              </select>
            </div>
            <div class="col-md-1 mb-2">
              <button type="submit" class="btn btn-secondary btn-block">Go</button>
            </div>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.href='<?php echo base_url('admin/emarking/batches'); ?>'">Reset</button>
        </form>

        <?php
          $total_rows = (int) ($total_rows ?? 0);
          $offset = (int) ($offset ?? 0);
          $current_count = is_array($batches ?? null) ? count($batches) : (is_iterable($batches ?? null) ? iterator_count($batches) : 0);
          $showing_from = $total_rows > 0 ? ($offset + 1) : 0;
          $showing_to = $total_rows > 0 ? min($offset + $current_count, $total_rows) : 0;
          $is_admin = ((int) logged('role') === 1);
          $transfer_action = base_url('admin/emarking/transfer_batch');
          $subject_transfer_emarkers = isset($subject_transfer_emarkers) && is_array($subject_transfer_emarkers) ? $subject_transfer_emarkers : [];
          $current_query = $_SERVER['QUERY_STRING'] ?? '';
          if ($current_query !== '') {
            $transfer_action .= '?' . $current_query;
          }
        ?>
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
          <div class="text-muted">
            Showing <?php echo $showing_from; ?> to <?php echo $showing_to; ?> of <?php echo $total_rows; ?> entries
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Batch Code</th>
                <th>Assessment</th>
                <th>G</th>
                <th>S</th>
                <th>V</th>
                <th>Question</th>
                <th>Assigned To</th>
                <th>Size</th>
                <th>Allotment</th>
                <th>Marked</th>
                <th>Status</th>
                <th>Deadline</th>
                <th>Created</th>
                <?php if ($is_admin): ?>
                  <th>Action</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($batches)): ?>
                <tr><td colspan="<?php echo $is_admin ? 15 : 14; ?>" class="text-center text-muted">No records</td></tr>
              <?php else: ?>
                <?php foreach ($batches as $b): ?>
                  <?php $can_transfer = in_array(strtoupper((string) ($b->status ?? '')), ['PENDING', 'IN_PROGRESS'], true); ?>
                  <tr>
                    <td><?php echo (int) $b->id; ?></td>
                    <td><?php echo htmlspecialchars((string) $b->batch_code); ?></td>
                    <td><?php echo htmlspecialchars((string) $b->assessment_type); ?></td>
                    <td><?php echo (int) $b->grade; ?></td>
                    <?php
                      $scode = trim((string) ($b->subject_code ?? ''));
                      $slabel = isset($subject_code_map[$scode]) ? $subject_code_map[$scode] : $scode;
                    ?>
                    <td><?php echo htmlspecialchars((string) $slabel); ?></td>
                    <td><?php echo htmlspecialchars((string) $b->version); ?></td>
                    <td>
                      <div><strong><?php echo htmlspecialchars((string) $b->question_no); ?></strong></div>
                      <div class="text-muted" style="max-width:340px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?php echo htmlspecialchars((string) $b->question_title); ?>
                      </div>
                    </td>
                    <td>
                      <?php echo htmlspecialchars((string) $b->emarker_name); ?>
                      <div class="text-muted"><?php echo htmlspecialchars((string) $b->emarker_username); ?> (<?php echo (int) $b->assigned_to; ?>)</div>
                    </td>
                    <td><?php echo (int) $b->batch_size; ?></td>
                    <td><?php echo (int) ($b->allotment ?? 0); ?></td>
                    <td><?php echo (int) ($b->marked ?? 0); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars((string) $b->status); ?></span></td>
                    <td><?php echo htmlspecialchars((string) $b->deadline); ?></td>
                    <td><?php echo htmlspecialchars((string) $b->created_at); ?></td>
                    <?php if ($is_admin): ?>
                      <td>
                        <?php if ($can_transfer): ?>
                          <button
                            type="button"
                            class="btn btn-outline-primary btn-sm js-transfer-batch"
                            data-toggle="modal"
                            data-target="#transferBatchModal"
                            data-batch-id="<?php echo (int) $b->id; ?>"
                            data-batch-code="<?php echo htmlspecialchars((string) $b->batch_code, ENT_QUOTES, 'UTF-8'); ?>"
                            data-current-emarker="<?php echo htmlspecialchars(trim((string) $b->emarker_name . ' (' . $b->emarker_username . ')'), ENT_QUOTES, 'UTF-8'); ?>"
                            data-current-emarker-id="<?php echo (int) $b->assigned_to; ?>"
                            data-subject-code="<?php echo htmlspecialchars((string) $b->subject_code, ENT_QUOTES, 'UTF-8'); ?>"
                            data-subject-label="<?php echo htmlspecialchars((string) $slabel, ENT_QUOTES, 'UTF-8'); ?>"
                            data-status="<?php echo htmlspecialchars((string) $b->status, ENT_QUOTES, 'UTF-8'); ?>"
                          >
                            Transfer
                          </button>
                        <?php else: ?>
                          <span class="text-muted">Locked</span>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php if (!empty($pagination_links)): ?>
      <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap">
        <div class="text-muted mb-2 mb-md-0">
          Showing <?php echo $showing_from; ?> to <?php echo $showing_to; ?> of <?php echo $total_rows; ?> entries
        </div>
        <?php echo $pagination_links; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php if ($is_admin): ?>
  <div class="modal fade" id="transferBatchModal" tabindex="-1" role="dialog" aria-labelledby="transferBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="post" action="<?php echo htmlspecialchars($transfer_action, ENT_QUOTES, 'UTF-8'); ?>" id="transferBatchForm">
          <div class="modal-header">
            <h5 class="modal-title" id="transferBatchModalLabel">Transfer Batch</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="batch_id" id="transfer-batch-id" value="">
            <div class="form-group">
              <label>Batch</label>
              <input type="text" class="form-control" id="transfer-batch-code" value="" readonly>
            </div>
            <div class="form-group">
              <label>Status</label>
              <input type="text" class="form-control" id="transfer-batch-status" value="" readonly>
            </div>
            <div class="form-group">
              <label>Subject</label>
              <input type="text" class="form-control" id="transfer-batch-subject" value="" readonly>
            </div>
            <div class="form-group">
              <label>Current eMarker</label>
              <input type="text" class="form-control" id="transfer-current-emarker" value="" readonly>
            </div>
            <div class="form-group">
              <label for="transfer-new-emarker">New eMarker</label>
              <select name="new_emarker_id" id="transfer-new-emarker" class="form-control select2" required>
                <option value="">Select approved eMarker</option>
              </select>
            </div>
            <div class="form-group mb-0">
              <label for="transfer-remarks">Remarks</label>
              <textarea name="remarks" id="transfer-remarks" class="form-control" rows="3" placeholder="Optional transfer note"></textarea>
            </div>
            <p class="text-muted small mt-3 mb-0">This will keep the batch status unchanged and only move it to a different active eMarker.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Confirm Transfer</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var triggerButtons = document.querySelectorAll('.js-transfer-batch');
      var batchIdInput = document.getElementById('transfer-batch-id');
      var batchCodeInput = document.getElementById('transfer-batch-code');
      var statusInput = document.getElementById('transfer-batch-status');
      var subjectInput = document.getElementById('transfer-batch-subject');
      var currentEmarkerInput = document.getElementById('transfer-current-emarker');
      var newEmarkerSelect = document.getElementById('transfer-new-emarker');
      var transferForm = document.getElementById('transferBatchForm');
      var subjectEmarkers = <?php echo json_encode($subject_transfer_emarkers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

      function renderEmarkerOptions(subjectCode, currentEmarkerId) {
        var options = ['<option value="">Select approved eMarker</option>'];
        var list = subjectEmarkers[String(subjectCode)] || [];

        for (var i = 0; i < list.length; i++) {
          var emarker = list[i] || {};
          if (String(emarker.id) === String(currentEmarkerId)) {
            continue;
          }

          var label = (emarker.name || '') + ' (' + (emarker.username || '') + ')';
          options.push('<option value="' + String(emarker.id) + '">' + label.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;') + '</option>');
        }

        newEmarkerSelect.innerHTML = options.join('');
      }

      for (var i = 0; i < triggerButtons.length; i++) {
        triggerButtons[i].addEventListener('click', function () {
          var subjectCode = this.getAttribute('data-subject-code') || '';
          var currentEmarkerId = this.getAttribute('data-current-emarker-id') || '';
          batchIdInput.value = this.getAttribute('data-batch-id') || '';
          batchCodeInput.value = this.getAttribute('data-batch-code') || '';
          statusInput.value = this.getAttribute('data-status') || '';
          subjectInput.value = this.getAttribute('data-subject-label') || '';
          currentEmarkerInput.value = this.getAttribute('data-current-emarker') || '';
          renderEmarkerOptions(subjectCode, currentEmarkerId);
          $(newEmarkerSelect).val('').trigger('change');
        });
      }

      transferForm.addEventListener('submit', function (event) {
        var currentEmarkerId = '';
        var activeButton = document.querySelector('.js-transfer-batch[data-batch-id="' + batchIdInput.value + '"]');
        if (activeButton) {
          currentEmarkerId = activeButton.getAttribute('data-current-emarker-id') || '';
        }

        if (!newEmarkerSelect.value || newEmarkerSelect.value === currentEmarkerId) {
          event.preventDefault();
          alert('Please select a different emarker');
          return false;
        }

        if (!window.confirm('Are you sure you want to transfer this batch to the selected eMarker?')) {
          event.preventDefault();
          return false;
        }
      });
    })();
  </script>

  <script>
    $(function () {
      if ($.fn.select2) {
        $('#transfer-new-emarker').select2({
          width: '100%',
          placeholder: 'Select approved eMarker',
          allowClear: true,
          dropdownParent: $('#transferBatchModal')
        });

        $('#transferBatchModal').on('shown.bs.modal', function () {
          $('#transfer-new-emarker').select2('open');
        });
      }
    });
  </script>
<?php endif; ?>

<?php include viewPath('admin/includes/footer'); ?>
