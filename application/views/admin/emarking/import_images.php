<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('admin/includes/header'); ?>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark"><?php echo htmlspecialchars((string) ($page->title ?? 'Import Images')); ?></h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin'); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo base_url('admin/emarking/questions'); ?>">Questions</a></li>
          <li class="breadcrumb-item active">Import</li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">

    <?php include viewPath('admin/includes/notifications'); ?>

    <div class="alert alert-info">
      <div><strong>Rules enforced:</strong></div>
      <ul class="mb-0">
        <li>Tries to match source records where <code>paper_generated = 1</code> (if source columns exist)</li>
        <li>CRQ uses <code>paper_type_code = 1</code> (booklets tables) when validating source</li>
        <li>Dictation uses <code>paper_type_code = 12</code> (English subject_code 1) and <code>13</code> (Urdu subject_code 2) when validating source</li>
        <li>Folder structure supported:</li>
        <li class="ml-3"><code>{base}/{grade}/{subject_code}/{version}/{page_no}/{question_no}/{barcode}_1.jpg</code> (legacy)</li>
        <li class="ml-3"><code>{base}/{page_no}/{question_no}/{barcode}_1.jpg</code> where <code>{base} = storagebox/crqs/{grade}/{subject_code}/{version}</code> or <code>storagebox/dictations/{grade}/{subject_code}/{version}</code></li>
        <li class="ml-3"><code>{base}/{question_no}/{barcode}_1.jpg</code> where <code>{base} = storagebox/crqs/{grade}/{subject_code}/{version}/{page_no}</code> or <code>storagebox/dictations/{grade}/{subject_code}/{version}/{page_no}</code> (fastest for huge imports)</li>
        <li>If source validation fails, image is still imported with <code>source_paper_id = 0</code> (see Errors list)</li>
      </ul>
    </div>

    <div class="row">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title mb-0">Import CRQ Images</h3>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo base_url('admin/emarking/import_crq_images'); ?>">
              <div class="form-group">
                <label>Base Folder</label>
                <input type="text" name="base_folder" class="form-control" value="<?php echo htmlspecialchars((string) ($default_crq_path ?? 'storagebox/crqs')); ?>">
              </div>
              <div class="form-group">
                <label>Upload Batch No (optional)</label>
                <input type="text" name="upload_batch_no" class="form-control" placeholder="auto if empty">
              </div>
              <div class="form-group">
                <label>Live Chunk Size</label>
                <input type="number" class="form-control js-chunk-size" value="200" min="1" max="5000">
                <small class="text-muted">Smaller = faster live updates. Larger = faster overall (but each tick takes longer).</small>
              </div>
              <button type="submit" class="btn btn-primary">Import CRQ</button>
              <button type="button" class="btn btn-success ml-1 js-live-import" data-assessment="CRQ">Live Import CRQ</button>
            </form>

            <div class="mt-3 d-none" id="live-import-CRQ">
              <div class="d-flex align-items-center mb-2">
                <div class="mr-2"><strong>Status:</strong> <span class="js-status">Idle</span><span class="js-status-dots status-dots d-none" aria-hidden="true"></span></div>
                <div class="ml-auto">
                  <button type="button" class="btn btn-sm btn-outline-danger js-cancel">Cancel</button>
                </div>
              </div>
              <div class="progress mb-2" style="height: 18px;">
                <div class="progress-bar js-bar" role="progressbar" style="width: 0%;">0%</div>
              </div>
              <div class="small text-muted mb-2">
                <span><strong>Inserted:</strong> <span class="js-inserted">0</span></span>
                <span class="ml-3"><strong>Skipped:</strong> <span class="js-skipped">0</span></span>
                <span class="ml-3"><strong>Errors:</strong> <span class="js-errors-count">0</span></span>
                <span class="ml-3"><strong>Processed:</strong> <span class="js-cursor">0</span>/<span class="js-total">0</span></span>
              </div>
              <div class="table-responsive" style="max-height: 220px; overflow:auto;">
                <table class="table table-sm table-bordered mb-0">
                  <thead><tr><th>File</th><th>Reason</th></tr></thead>
                  <tbody class="js-errors-body">
                    <tr class="js-errors-empty"><td colspan="2" class="text-muted">No errors yet.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <?php if (!empty($result_crq)): ?>
              <hr>
              <div><strong>Result</strong></div>
              <div>Inserted: <strong><?php echo (int) ($result_crq['inserted'] ?? 0); ?></strong></div>
              <div>Skipped: <strong><?php echo (int) ($result_crq['skipped'] ?? 0); ?></strong></div>
              <?php if (!empty($result_crq['errors'])): ?>
                <div class="mt-2"><strong>Errors</strong></div>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>File</th><th>Reason</th></tr></thead>
                    <tbody>
                      <?php foreach ($result_crq['errors'] as $e): ?>
                        <tr>
                          <td style="white-space:normal; word-break:break-all;"><?php echo htmlspecialchars((string) ($e['file'] ?? '')); ?></td>
                          <td><?php echo htmlspecialchars((string) ($e['reason'] ?? '')); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title mb-0">Import Dictation Images</h3>
          </div>
          <div class="card-body">
            <form method="post" action="<?php echo base_url('admin/emarking/import_dictation_images'); ?>">
              <div class="form-group">
                <label>Base Folder</label>
                <input type="text" name="base_folder" class="form-control" value="<?php echo htmlspecialchars((string) ($default_dictation_path ?? 'storagebox/dictations')); ?>">
              </div>
              <div class="form-group">
                <label>Upload Batch No (optional)</label>
                <input type="text" name="upload_batch_no" class="form-control" placeholder="auto if empty">
              </div>
              <div class="form-group">
                <label>Live Chunk Size</label>
                <input type="number" class="form-control js-chunk-size" value="200" min="1" max="5000">
                <small class="text-muted">Smaller = faster live updates. Larger = faster overall (but each tick takes longer).</small>
              </div>
              <button type="submit" class="btn btn-primary">Import Dictation</button>
              <button type="button" class="btn btn-success ml-1 js-live-import" data-assessment="DICTATION">Live Import Dictation</button>
            </form>

            <div class="mt-3 d-none" id="live-import-DICTATION">
              <div class="d-flex align-items-center mb-2">
                <div class="mr-2"><strong>Status:</strong> <span class="js-status">Idle</span><span class="js-status-dots status-dots d-none" aria-hidden="true"></span></div>
                <div class="ml-auto">
                  <button type="button" class="btn btn-sm btn-outline-danger js-cancel">Cancel</button>
                </div>
              </div>
              <div class="progress mb-2" style="height: 18px;">
                <div class="progress-bar js-bar" role="progressbar" style="width: 0%;">0%</div>
              </div>
              <div class="small text-muted mb-2">
                <span><strong>Inserted:</strong> <span class="js-inserted">0</span></span>
                <span class="ml-3"><strong>Skipped:</strong> <span class="js-skipped">0</span></span>
                <span class="ml-3"><strong>Errors:</strong> <span class="js-errors-count">0</span></span>
                <span class="ml-3"><strong>Processed:</strong> <span class="js-cursor">0</span>/<span class="js-total">0</span></span>
              </div>
              <div class="table-responsive" style="max-height: 220px; overflow:auto;">
                <table class="table table-sm table-bordered mb-0">
                  <thead><tr><th>File</th><th>Reason</th></tr></thead>
                  <tbody class="js-errors-body">
                    <tr class="js-errors-empty"><td colspan="2" class="text-muted">No errors yet.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <?php if (!empty($result_dict)): ?>
              <hr>
              <div><strong>Result</strong></div>
              <div>Inserted: <strong><?php echo (int) ($result_dict['inserted'] ?? 0); ?></strong></div>
              <div>Skipped: <strong><?php echo (int) ($result_dict['skipped'] ?? 0); ?></strong></div>
              <?php if (!empty($result_dict['errors'])): ?>
                <div class="mt-2"><strong>Errors</strong></div>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>File</th><th>Reason</th></tr></thead>
                    <tbody>
                      <?php foreach ($result_dict['errors'] as $e): ?>
                        <tr>
                          <td style="white-space:normal; word-break:break-all;"><?php echo htmlspecialchars((string) ($e['file'] ?? '')); ?></td>
                          <td><?php echo htmlspecialchars((string) ($e['reason'] ?? '')); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<style>
  .status-dots::after {
    content: '';
    display: inline-block;
    width: 1.2em;
    text-align: left;
    animation: statusDots 1.1s infinite steps(4, end);
  }
  @keyframes statusDots {
    0% { content: ''; }
    25% { content: '.'; }
    50% { content: '..'; }
    75% { content: '...'; }
    100% { content: ''; }
  }
</style>

<script>
  (function () {
    function csrfPair() {
      var name = document.querySelector('meta[name="csrf_token_name"]');
      var hash = document.querySelector('meta[name="csrf_token_hash"]');
      if (!name || !hash) return {};
      var out = {};
      out[name.getAttribute('content')] = hash.getAttribute('content');
      return out;
    }

    function num(n) {
      n = parseInt(n, 10);
      return isNaN(n) ? 0 : n;
    }

    function pct(cursor, total) {
      if (!total) return 0;
      return Math.max(0, Math.min(100, Math.round((cursor / total) * 100)));
    }

    function setBar($root, cursor, total) {
      var p = pct(cursor, total);
      var $bar = $root.find('.js-bar');
      $bar.css('width', p + '%').text(p + '%');
    }

    function setStatus($root, text) {
      $root.find('.js-status').text(text);
      var running = (text || '').toString().toLowerCase().indexOf('running') !== -1;
      $root.find('.js-status-dots').toggleClass('d-none', !running);
    }

    function setCounts($root, progress) {
      $root.find('.js-inserted').text(num(progress.inserted));
      $root.find('.js-skipped').text(num(progress.skipped));
      $root.find('.js-errors-count').text(num(progress.errors_count));
      $root.find('.js-cursor').text(num(progress.cursor));
      $root.find('.js-total').text(num(progress.total));
      setBar($root, num(progress.cursor), num(progress.total));
    }

    function renderErrors($root, progress) {
      var $tbody = $root.find('.js-errors-body');
      $tbody.find('tr.js-error-row').remove();
      var last = Array.isArray(progress.last_errors) ? progress.last_errors : [];
      if (!last.length) {
        $tbody.find('.js-errors-empty').removeClass('d-none');
        return;
      }
      $tbody.find('.js-errors-empty').addClass('d-none');
      for (var i = Math.max(0, last.length - 50); i < last.length; i++) {
        var e = last[i] || {};
        var file = (e.file || '').toString();
        var reason = (e.reason || '').toString();
        var $tr = $('<tr class="js-error-row"></tr>');
        $tr.append($('<td style="white-space:normal; word-break:break-all;"></td>').text(file));
        $tr.append($('<td></td>').text(reason));
        $tbody.append($tr);
      }
    }

    function findForm(assessmentType) {
      var action = assessmentType === 'CRQ' ? 'admin/emarking/import_crq_images' : 'admin/emarking/import_dictation_images';
      var selector = 'form[action*="' + action + '"]';
      return $(selector).first();
    }

    function rootFor(assessmentType) {
      return $('#live-import-' + assessmentType);
    }

    function liveImportStart(assessmentType) {
      var $form = findForm(assessmentType);
      if (!$form.length) return;

      var $root = rootFor(assessmentType);
      $root.removeClass('d-none');
      $root.data('cancelled', false);

      var baseFolder = ($form.find('input[name="base_folder"]').val() || '').toString();
      var uploadBatchNo = ($form.find('input[name="upload_batch_no"]').val() || '').toString();
      var chunkSize = num($form.find('.js-chunk-size').val());
      if (chunkSize <= 0) chunkSize = 200;
      if (chunkSize > 5000) chunkSize = 5000;

      setStatus($root, 'Starting...');
      setCounts($root, { inserted: 0, skipped: 0, errors_count: 0, cursor: 0, total: 0 });
      renderErrors($root, { last_errors: [] });

      var startUrl = '<?php echo base_url('admin/emarking/import_async_start'); ?>';
      var tickUrl = '<?php echo base_url('admin/emarking/import_async_tick'); ?>';

      var startPayload = $.extend({}, csrfPair(), {
        assessment_type: assessmentType,
        base_folder: baseFolder,
        upload_batch_no: uploadBatchNo,
        chunk_size: chunkSize
      });

      $form.find('button, input, select, textarea').prop('disabled', true);

      $.ajax({
        url: startUrl,
        type: 'POST',
        dataType: 'json',
        data: startPayload
      }).done(function (resp) {
        if (!resp || !resp.ok || !resp.key) {
          setStatus($root, (resp && resp.error) ? resp.error : 'Unable to start import');
          $form.find('button, input, select, textarea').prop('disabled', false);
          return;
        }

        if (resp.upload_batch_no) {
          $form.find('input[name="upload_batch_no"]').val(resp.upload_batch_no);
        }

        var key = resp.key;
        setStatus($root, 'Running...');

        function tick() {
          if ($root.data('cancelled')) {
            setStatus($root, 'Cancelled (client-side).');
            $form.find('button, input, select, textarea').prop('disabled', false);
            return;
          }

          var tickPayload = $.extend({}, csrfPair(), { key: key });
          $.ajax({
            url: tickUrl,
            type: 'POST',
            dataType: 'json',
            data: tickPayload
          }).done(function (r) {
            var p = r && r.progress ? r.progress : null;
            if (!r || !r.ok || !p) {
              setStatus($root, (r && r.error) ? r.error : 'Import tick failed');
              $form.find('button, input, select, textarea').prop('disabled', false);
              return;
            }

            setCounts($root, p);
            renderErrors($root, p);

            if ((p.status || '') === 'done' || num(p.cursor) >= num(p.total)) {
              setStatus($root, 'Done.');
              $form.find('button, input, select, textarea').prop('disabled', false);
              return;
            }

            setTimeout(tick, 50);
          }).fail(function (xhr) {
            var msg = 'Import tick request failed';
            try {
              if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
                msg = xhr.responseJSON.error;
              } else if (xhr && xhr.status) {
                msg = 'Import tick request failed (' + xhr.status + ')';
              }
            } catch (e) {}
            setStatus($root, msg);
            $form.find('button, input, select, textarea').prop('disabled', false);
          });
        }

        tick();
      }).fail(function (xhr) {
        var msg = 'Start request failed';
        try {
          if (xhr && xhr.responseJSON && xhr.responseJSON.error) {
            msg = xhr.responseJSON.error;
            if (xhr.responseJSON.base_folder) msg += ' (' + xhr.responseJSON.base_folder + ')';
            if (xhr.responseJSON.manifest) msg += ' (' + xhr.responseJSON.manifest + ')';
            if (xhr.responseJSON.dir) msg += ' (' + xhr.responseJSON.dir + ')';
          } else if (xhr && xhr.responseText) {
            var t = xhr.responseText.toString();
            // Try to extract JSON error from responseText
            try {
              var j = JSON.parse(t);
              if (j && j.error) msg = j.error;
            } catch (e) {}
            if (msg === 'Start request failed') {
              msg = 'Start request failed (' + (xhr.status || '') + ')';
            }
          } else if (xhr && xhr.status) {
            msg = 'Start request failed (' + xhr.status + ')';
          }
        } catch (e) {}
        setStatus($root, msg);
        $form.find('button, input, select, textarea').prop('disabled', false);
      });
    }

    $(document).on('click', '.js-live-import', function () {
      var assessmentType = ($(this).data('assessment') || '').toString();
      if (!assessmentType) return;
      liveImportStart(assessmentType);
    });

    $(document).on('click', '#live-import-CRQ .js-cancel, #live-import-DICTATION .js-cancel', function () {
      var $root = $(this).closest('div[id^="live-import-"]');
      $root.data('cancelled', true);
    });
  })();
</script>

<?php include viewPath('admin/includes/footer'); ?>
