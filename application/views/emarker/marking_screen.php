<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php include viewPath('user/includes/header'); ?>

<?php
$item = $marking['item'];
$steps = $marking['steps'] ?? [];
$mark = $marking['mark'] ?? null;
$mark_steps = $marking['mark_steps'] ?? [];
$image_url = base_url((string) $item->image_path);
$total = (int) ($batch_total_items ?? 0);
$idx = (int) ($batch_current_index ?? 0);
$timer_seconds = (int) ($timer_seconds ?? 15);
if ($timer_seconds < 0) $timer_seconds = 0;
$is_urdu_subject = ((string) ($item->subject_code ?? '') === '2');
$rubric_title = trim((string) ($item->rubric_title ?? ''));
$panel_heading = $rubric_title !== '' ? $rubric_title : '';
$preload_image_paths = isset($preload_image_paths) && is_array($preload_image_paths) ? $preload_image_paths : [];
$preload_urls = [];
foreach ($preload_image_paths as $p) {
  $p = trim((string) $p);
  if ($p === '') continue;
  $preload_urls[] = base_url($p);
}
?>

<style>
  .emarking-topbar { background:#0b62d6; color:#fff; padding:10px 0; }
  .emarking-topbar a { color:#fff; }

  /* Left question header (match reference screenshot) */
  .emarking-qbar { background:#0b62d6; border-radius:12px; padding:10px 12px; color:#fff; box-shadow:0 6px 14px rgba(11, 98, 214, .25); }
  .emarking-qbar-row { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
  .emarking-qpill { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.25); font-weight:700; font-size:12px; }
  .emarking-qpill-muted { opacity:.95; font-weight:700; }
  .emarking-qtitle { margin-top:10px; background:#0b62d6; border-radius:12px; padding:12px 14px; color:#fff; box-shadow:0 6px 14px rgba(11, 98, 214, .25); font-weight:600; }

  /* Right-side marking panel only */
  .emarking-panel { border:1px solid #dfe3ea; border-radius:10px; box-shadow:0 10px 24px rgba(16, 24, 40, .10); overflow:hidden; background:#fff; }
  .emarking-panel .card-header { background:#fff; border-bottom:1px solid #eef2f7; padding:12px 14px; }
  .emarking-panel-title { font-size:14px; font-weight:700; color:#111827; margin:0; }
  /* Urdu font sizing: keep global urdufont-right default, but slightly smaller inside the marking panel */
  .emarking-panel .urdufont-right { font-size: 18px; }
  /* Ensure Urdu heading in the right marking panel is truly right-aligned (don't rely on Bootstrap utility class names). */
  #emarkingPanelTitle.urdufont-right { width:100%; text-align:right; }
  .rubric-detail { margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#f8fafc; color:#111827; font-size:13px; line-height:1.5; }
  .emarking-panel-body { padding:12px 14px; max-height:52vh; overflow:auto; }
  .emarking-panel-body::-webkit-scrollbar { width:8px; }
  .emarking-panel-body::-webkit-scrollbar-thumb { background:#d7dde6; border-radius:8px; }

  .rubric-card { border:0; padding:0; margin-bottom:12px; }
  .rubric-title { font-weight:700; font-size:12px; color:#6b7280; margin-bottom:8px; }

  .emarking-combined-wrap { margin-bottom:12px; }
  .emarking-combined-title { font-weight:700; font-size:14px; color:#111827; margin:0 0 10px 0; }
  .emarking-combined-sub { font-size:12px; color:#6b7280; margin-top:-6px; margin-bottom:10px; }

  .emarking-box-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:10px; }
  @media (max-width: 1199.98px) { .emarking-box-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 575.98px) { .emarking-box-grid { grid-template-columns:1fr; } }

  .emarking-box { border:1px solid #dfe3ea; border-radius:10px; background:#fff; padding:8px 10px; min-height:56px; cursor:pointer; transition:background .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; }
  .emarking-box:hover { box-shadow:0 8px 16px rgba(16, 24, 40, .08); transform:translateY(-1px); }
  .emarking-box.is-selected { border-color:#0d6efd; background:#eef4ff; }
  .emarking-box-label { font-size:11px; font-weight:600; color:#6b7280; line-height:1.2; width:100%; }
  .emarking-box-value { display:flex; align-items:center; justify-content:center; margin-top:8px; width:100%; }
  .emarking-box-dot { width:24px; height:24px; border-radius:999px; border:2px solid #cbd5e1; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:#111827; background:#fff; }
  .emarking-box.is-selected .emarking-box-dot { border-color:#0d6efd; background:#eef4ff; }

  .emarking-hide-input { position:absolute !important; left:-99999px !important; width:1px !important; height:1px !important; overflow:hidden !important; }

  .emarking-remarks-label { font-weight:800; font-size:11px; color:#6b7280; letter-spacing:.6px; margin-bottom:6px; }
  .emarking-remarks textarea.form-control { border-radius:10px; }

  .emarking-panel .card-footer { background:#fff; border-top:1px solid #eef2f7; padding:12px 14px; position:sticky; bottom:0; }
  .emarking-summary { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; }
  .emarking-timer-hint { display:block; margin-top:2px; font-size:12px; color:#6b7280; }

  .emarking-actions { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-top:10px; }
  .emarking-actions-left { display:flex; gap:8px; flex-wrap:wrap; }
  .emarking-actions-right { margin-left:auto; }
  .emarking-action-btn { border-radius:10px; padding:8px 14px; min-height:36px; font-weight:600; }
  .emarking-submit { background:#0d6efd; border-color:#0d6efd; color:#fff; }
  .emarking-submit:hover { background:#0b5ed7; border-color:#0b5ed7; color:#fff; }
  .emarking-links { display:flex; gap:14px; flex-wrap:wrap; align-items:center; font-size:12px; margin-top:8px; }
  .emarking-linkbtn { padding:0; border:0; background:transparent; color:#0d6efd; font-weight:600; }
  .emarking-linkbtn:hover { text-decoration:underline; color:#0b5ed7; }

  /* Combined view hides per-step blocks (inputs remain) */
  .emarking-panel.is-combined .rubric-card { display:none; }
</style>

<div class="emarking-topbar">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <strong>Exam Management System</strong> | <strong>PECTAA 2026</strong> |
        <a href="<?php echo base_url('user/dashboard'); ?>">Home</a>
      </div>
      <div>
        eMarker: <strong><?php echo html_escape((string) logged('name')); ?></strong>
      </div>
    </div>
  </div>
</div>

<section class="content" style="padding-top:12px;">
  <div class="container-fluid">

    <?php include viewPath('user/includes/notifications'); ?>

    <div class="row">
      <div class="col-lg-7">
        <div class="card">
          <div class="card-header">
            <?php
              $left_max_marks_val = (float) ($item->max_marks ?? 0);
              $left_max_marks_txt = (abs($left_max_marks_val - (int) $left_max_marks_val) < 0.000001) ? (string) ((int) $left_max_marks_val) : rtrim(rtrim(number_format($left_max_marks_val, 2, '.', ''), '0'), '.');
              $left_obtained_val = (float) (($mark->marks_obtained ?? 0));
              if ($left_obtained_val < 0) $left_obtained_val = 0;
              $left_obtained_txt = (abs($left_obtained_val - (int) $left_obtained_val) < 0.000001) ? (string) ((int) $left_obtained_val) : rtrim(rtrim(number_format($left_obtained_val, 2, '.', ''), '0'), '.');
            ?>
            <div class="emarking-qbar">
              <div class="emarking-qbar-row">
                <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap;">
                  <span class="emarking-qpill"><?php echo html_escape((string) $item->question_no); ?></span>
                  <span id="emarkingLeftScore" class="emarking-qpill emarking-qpill-muted" data-max="<?php echo html_escape($left_max_marks_txt); ?>"><?php echo html_escape($left_obtained_txt); ?>/<?php echo html_escape($left_max_marks_txt); ?></span>
                </div>
                <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap;">
                  <span class="emarking-qpill"><?php echo (int) $idx; ?>/<?php echo (int) $total; ?></span>
                </div>
              </div>
            </div>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <div class="emarking-qtitle <?php echo $is_urdu_subject ? 'urdufont-right' : ''; ?>" style="white-space:pre-wrap;"><?php echo html_escape((string) $item->question_title); ?></div>
            </div>

            <div class="mb-2">
              <div class="d-flex justify-content-between align-items-center">
                <div class="sr-only">Student Cropped Answer Image</div>
              </div>
              <div style="border:1px solid #e5e5e5; padding:8px; background:#fafafa;">
                <img src="<?php echo $image_url; ?>" alt="Answer Image" style="width:100%; height:auto;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <form method="post" action="<?php echo base_url('emarker/marking/save_marks'); ?>">
          <input type="hidden" name="batch_id" value="<?php echo (int) $item->batch_id; ?>">
          <input type="hidden" name="batch_item_id" value="<?php echo (int) $item->id; ?>">

          <?php
            $qt = trim((string) ($item->question_type ?? ''));
            $qtLabel = $qt !== '' ? ucwords(strtolower(str_replace(['_', '-'], ' ', $qt))) : 'Rubric';
            if (strtolower($qtLabel) === 'objective steps') $qtLabel = 'Answer Evaluation';
            $maxMarksLabel = htmlspecialchars((string) ((float) ($item->max_marks ?? 0)));
            // Rubric title overrides the panel heading when provided (configured on question edit screen).
            $panelHeading = $rubric_title !== '' ? $rubric_title : $qtLabel;
          ?>
          <?php
            // In combined mode, the option value represents "number of correct steps",
            // not total marks. This supports fractional step marks (e.g. 0.5 each).
            $combined_max = !empty($steps) ? count($steps) : 0;
            $combined_prefix = [0 => 0.0];
            if (!empty($steps)) {
              $running = 0.0;
              $i = 0;
              foreach ($steps as $cs) {
                $i++;
                $running += (float) ($cs->step_marks ?? 0);
                $combined_prefix[$i] = $running;
              }
            }
            $is_combined_supported = true;
            if (!empty($steps)) {
              foreach ($steps as $cs) {
                if ((string) $cs->marking_type !== 'ZERO_ONE') { $is_combined_supported = false; break; }
              }
            } else {
              $is_combined_supported = false;
            }
            if ($combined_max <= 0) $is_combined_supported = false;
          ?>
          <div class="card emarking-panel <?php echo $is_combined_supported ? 'is-combined' : ''; ?>" data-combined-max="<?php echo (int) $combined_max; ?>">
            <div class="card-header">
              <div class="d-flex justify-content-between align-items-center">
                <h3
                  class="emarking-panel-title <?php echo $is_urdu_subject ? 'urdufont-right' : ''; ?>"
                  id="emarkingPanelTitle"
                  data-max="<?php echo $maxMarksLabel; ?>"
                >
                  <?php echo html_escape($panelHeading); ?> (0/<?php echo $maxMarksLabel; ?>)
                </h3>
              </div>
            </div>
            <div class="card-body emarking-panel-body">

              <?php if (!empty($item->rubric_detail)): ?>
                <div class="rubric-detail <?php echo $is_urdu_subject ? 'urdufont-right' : ''; ?>">
                  <?php echo nl2br(html_escape((string) $item->rubric_detail)); ?>
                </div>
              <?php endif; ?>

              <?php if ($is_combined_supported): ?>
                <div class="emarking-combined-wrap" data-combined-wrap>
                  <div class="emarking-box-grid" data-mark-grid data-combined-grid>
                    <?php for ($v = $combined_max; $v >= 0; $v--): ?>
                      <?php
                        $label = '';
                        if ($combined_max === 3) {
                          if ($v === 3) $label = 'Three correct';
                          elseif ($v === 2) $label = 'Two correct';
                          elseif ($v === 1) $label = 'One correct';
                          else $label = 'All incorrect';
                        } else {
                          if ($v === 0) $label = 'All incorrect';
                          else $label = $v . ' correct';
                        }
                        $marksVal = (float) ($combined_prefix[$v] ?? 0.0);
                        $marksText = (abs($marksVal - (int) $marksVal) < 0.000001)
                          ? (string) ((int) $marksVal)
                          : rtrim(rtrim(number_format($marksVal, 2, '.', ''), '0'), '.');
                      ?>
                      <div class="emarking-box" role="button" tabindex="0" data-mark-option data-type="COMBINED" data-value="<?php echo (int) $v; ?>">
                        <div class="emarking-box-label"><?php echo html_escape($label); ?></div>
                        <div class="emarking-box-value">
                          <span class="emarking-box-dot"><?php echo html_escape($v === 0 ? '0' : $marksText); ?></span>
                        </div>
                      </div>
                    <?php endfor; ?>
                  </div>
                </div>
              <?php endif; ?>

              <?php if (!empty($steps)): ?>
                <?php foreach ($steps as $s): ?>
                  <?php
                  $existing = $mark_steps[(int) $s->id] ?? null;
                  $existingVal = $existing ? (string) $existing->selected_value : '';
                  $stepType = (string) $s->marking_type;
                  $stepMarks = (float) ($s->step_marks ?? 0);
                  $stepMin = (float) ($s->min_marks ?? 0);
                  $stepMax = (float) ($s->max_marks ?? 0);
                  ?>
                  <div class="rubric-card" data-step-id="<?php echo (int) $s->id; ?>" data-type="<?php echo html_escape($stepType); ?>" data-step-marks="<?php echo htmlspecialchars((string) $stepMarks); ?>" data-min="<?php echo htmlspecialchars((string) $stepMin); ?>" data-max="<?php echo htmlspecialchars((string) $stepMax); ?>">
                    <div class="rubric-title <?php echo $is_urdu_subject ? 'urdufont-right' : ''; ?>">
                      <?php echo html_escape((string) $s->step_title); ?>
                    </div>

                  <?php if ((string) $s->marking_type === 'RANGE'): ?>
                      <?php
                        $rawInterval = isset($s->interval) ? (float) $s->interval : 0.0;
                        $interval = (float) $rawInterval;
                        if ($interval <= 0) {
                          $interval = (($stepMax - $stepMin) <= 5.0) ? 0.5 : 1.0;
                        }
                        if ($interval <= 0) $interval = 1.0;

                        $minScaled = (int) round($stepMin * 100);
                        $maxScaled = (int) round($stepMax * 100);
                        $intScaled = (int) round($interval * 100);
                        if ($intScaled <= 0) $intScaled = 100;
                        if ($maxScaled < $minScaled) { $tmp = $maxScaled; $maxScaled = $minScaled; $minScaled = $tmp; }

                        $values = [];
                        $maxOpts = 250;
                        $count = 0;
                        for ($v = $minScaled; $v <= $maxScaled && $count < $maxOpts; $v += $intScaled) {
                          $values[] = $v;
                          $count++;
                        }
                        // Ensure max is included (if not already, due to rounding/step mismatch)
                        if (!empty($values) && end($values) !== $maxScaled && count($values) < $maxOpts) {
                          $values[] = $maxScaled;
                        }

                        // Display descending like existing UI
                        $values = array_reverse($values);

                        // On first load (no saved marks), don't preselect any RANGE value.
                        $currentRangeVal = $existingVal !== '' ? $existingVal : '';
                        $fmt = function ($n) {
                          $v = ((int) $n) / 100.0;
                          if (abs($v - (int) $v) < 0.000001) return (string) ((int) $v);
                          return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
                        };
                      ?>
                      <input type="number"
                        step="0.01"
                        min="<?php echo htmlspecialchars((string) $s->min_marks); ?>"
                        max="<?php echo htmlspecialchars((string) $s->max_marks); ?>"
                        class="form-control emarking-step-input emarking-hide-input original-radio"
                        name="steps[<?php echo (int) $s->id; ?>]"
                        value="<?php echo htmlspecialchars($currentRangeVal); ?>">

                      <div class="emarking-box-grid" data-mark-grid data-step-id="<?php echo (int) $s->id; ?>">
                        <?php foreach ($values as $vScaled): ?>
                          <?php
                            $label = '';
                            $valStr = $fmt($vScaled);
                            if ($minScaled === 0 && $maxScaled === 300 && $intScaled === 100) {
                              if ($valStr === '3') $label = 'Three correct';
                              elseif ($valStr === '2') $label = 'Two correct';
                              elseif ($valStr === '1') $label = 'One correct';
                              else $label = 'All incorrect';
                            } else {
                              $label = 'Marks';
                            }
                          ?>
                          <div class="emarking-box" role="button" tabindex="0" data-mark-option data-type="RANGE" data-value="<?php echo htmlspecialchars($valStr); ?>">
                            <div class="emarking-box-label"><?php echo html_escape($label); ?></div>
                            <div class="emarking-box-value">
                              <span class="emarking-box-dot"><?php echo html_escape($valStr); ?></span>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>

                    <?php elseif ((string) $s->marking_type === 'ZERO_ONE'): ?>
                      <div class="custom-control custom-radio emarking-hide-input">
                        <input class="custom-control-input emarking-step-input original-radio" type="radio" id="step_<?php echo (int) $s->id; ?>_c" name="steps[<?php echo (int) $s->id; ?>]" value="1" <?php echo ($existingVal === '1') ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="step_<?php echo (int) $s->id; ?>_c">Correct</label>
                      </div>
                      <div class="custom-control custom-radio emarking-hide-input">
                        <input class="custom-control-input emarking-step-input original-radio" type="radio" id="step_<?php echo (int) $s->id; ?>_w" name="steps[<?php echo (int) $s->id; ?>]" value="0" <?php echo ($existingVal === '0') ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="step_<?php echo (int) $s->id; ?>_w">Wrong</label>
                      </div>

                      <div class="emarking-box-grid" data-mark-grid data-step-id="<?php echo (int) $s->id; ?>">
                        <div class="emarking-box" role="button" tabindex="0" data-mark-option data-type="ZERO_ONE" data-target="#step_<?php echo (int) $s->id; ?>_c" data-value="1">
                          <div class="emarking-box-label">Correct</div>
                          <div class="emarking-box-value">
                            <span class="emarking-box-dot"><?php echo (int) $stepMarks; ?></span>
                          </div>
                        </div>
                        <div class="emarking-box" role="button" tabindex="0" data-mark-option data-type="ZERO_ONE" data-target="#step_<?php echo (int) $s->id; ?>_w" data-value="0">
                          <div class="emarking-box-label">Wrong</div>
                          <div class="emarking-box-value">
                            <span class="emarking-box-dot">0</span>
                          </div>
                        </div>
                      </div>

                    <?php else: ?>
                      <div class="text-muted">Fixed marks will be applied.</div>
                      <input type="hidden" class="emarking-step-input" name="steps[<?php echo (int) $s->id; ?>]" value="1">
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="alert alert-warning mb-0">No rubric steps configured for this question.</div>
              <?php endif; ?>

            </div>
            <div class="card-footer">
              <div class="emarking-summary mb-2">
                <div class="text-muted">
                  <strong>Obtained:</strong>
                  <span id="emarkingObtained" data-total="<?php echo htmlspecialchars((string) ((float) ($item->max_marks ?? 0))); ?>">0.00</span>
                  /
                  <?php
                    $max_marks_val = (float) ($item->max_marks ?? 0);
                    $max_marks_text = (abs($max_marks_val - (int) $max_marks_val) < 0.000001) ? (string) ((int) $max_marks_val) : rtrim(rtrim(number_format($max_marks_val, 2, '.', ''), '0'), '.');
                  ?>
                  <span id="emarkingTotal"><?php echo htmlspecialchars($max_marks_text); ?></span>
                </div>
                <div class="text-muted">
                  <strong>Timer:</strong>
                  <span id="emarkingTimer" data-seconds="<?php echo (int) $timer_seconds; ?>"><?php echo (int) $timer_seconds; ?>s</span>
                  <span id="emarkingTimerHint" class="emarking-timer-hint">Submit buttons enable when timer ends.</span>
                </div>
              </div>
              <div class="emarking-actions">
                <div class="emarking-actions-left">
                  <button type="submit" name="action" value="SKIPPED" class="btn btn-outline-info emarking-action-btn" disabled onclick="return confirm('Skip this image?');">Skip</button>
                  <button type="submit" name="action" value="NOT_ATTEMPTED" class="btn btn-outline-secondary emarking-action-btn" disabled onclick="return confirm('Mark as NOT ATTEMPTED?');">Not Attempted</button>
                </div>
                <div class="emarking-actions-right">
                  <button type="submit" name="action" value="MARKED" class="btn emarking-action-btn emarking-submit" disabled>Submit &amp; Next &rarr;</button>
                </div>
              </div>

              <div class="emarking-links">
                <?php if (!empty($item->sample_answer) || !empty($item->sample_answer_file)): ?>
                  <button type="button" class="emarking-linkbtn" data-toggle="modal" data-target="#sampleAnswerModal">Sample Answer</button>
                <?php endif; ?>
                <?php if (!empty($item->guide_text) || !empty($item->guide_file)): ?>
                  <button type="button" class="emarking-linkbtn" data-toggle="modal" data-target="#guideModal">Guide</button>
                <?php endif; ?>
                <?php if (!empty($item->question_paper_file)): ?>
                  <button type="button" class="emarking-linkbtn" data-toggle="modal" data-target="#questionPaperModal">Question Paper</button>
                <?php endif; ?>
              </div>
              <small class="text-muted d-block">Current item status: <?php echo html_escape((string) $item->status); ?></small>
            </div>
          </div>
        </form>
      </div>
    </div>

  </div>
</section>

<?php if (!empty($item->sample_answer) || !empty($item->sample_answer_file)): ?>
  <div class="modal fade" id="sampleAnswerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Sample Answer</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (!empty($item->sample_answer)): ?>
            <div class="<?php echo $is_urdu_subject ? 'urdufont-right' : ''; ?>" style="white-space:pre-wrap;">
              <?php
                $sample_answer_raw = (string) $item->sample_answer;
                $sample_answer_allowed_tags = '<u><b><i><br><p><div><span><strong><em><ul><ol><li><sub><sup>';
                $sample_answer_safe = strip_tags($sample_answer_raw, $sample_answer_allowed_tags);
                $sample_answer_safe = preg_replace('/<([a-z0-9]+)\\b[^>]*>/i', '<$1>', $sample_answer_safe);
                echo $sample_answer_safe;
              ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($item->sample_answer_file)): ?>
            <?php
              $sample_path = (string) $item->sample_answer_file;
              $sample_url = base_url($sample_path);
              $sample_ext = strtolower(pathinfo($sample_path, PATHINFO_EXTENSION));
              $is_image = in_array($sample_ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true);
            ?>
            <?php if ($is_image): ?>
              <div class="mt-3">
                <img src="<?php echo htmlspecialchars($sample_url); ?>" alt="Sample Answer" class="img-fluid" style="max-height:70vh;">
              </div>
            <?php else: ?>
              <div class="mt-3"><a href="<?php echo htmlspecialchars($sample_url); ?>" target="_blank" rel="noopener">Open sample answer file</a></div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($item->guide_text) || !empty($item->guide_file)): ?>
  <div class="modal fade" id="guideModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Guide</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (!empty($item->guide_text)): ?>
            <div style="white-space:pre-wrap;"><?php echo html_escape((string) $item->guide_text); ?></div>
          <?php endif; ?>
          <?php if (!empty($item->guide_file)): ?>
            <div class="mt-3"><a href="<?php echo base_url((string) $item->guide_file); ?>" target="_blank">Open guide file</a></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($item->question_paper_file)): ?>
  <div class="modal fade" id="questionPaperModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Question Paper</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php
            $paper_path = (string) $item->question_paper_file;
            $paper_url = base_url($paper_path);
            $paper_ext = strtolower(pathinfo($paper_path, PATHINFO_EXTENSION));
            $paper_is_image = in_array($paper_ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true);
            $paper_is_pdf = ($paper_ext === 'pdf');
          ?>

          <?php if ($paper_is_image): ?>
            <img src="<?php echo htmlspecialchars($paper_url); ?>" alt="Question Paper" class="img-fluid" style="max-height:75vh;">
          <?php elseif ($paper_is_pdf): ?>
            <iframe src="<?php echo htmlspecialchars($paper_url); ?>" style="width:100%;height:75vh;border:0;" title="Question Paper"></iframe>
          <?php else: ?>
            <a href="<?php echo htmlspecialchars($paper_url); ?>" target="_blank" rel="noopener">Open question paper file</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php include viewPath('user/includes/footer'); ?>

<script>
(function(){
  // Warm browser cache for likely next items in this batch (reduces perceived load time).
  try {
    var preload = <?php echo json_encode(array_values(array_slice($preload_urls, 0, 3))); ?>;
    if (Array.isArray(preload)) {
      preload.forEach(function(u){
        if (!u) return;
        var img = new Image();
        img.decoding = 'async';
        img.loading = 'eager';
        img.src = u;
      });
    }
  } catch (e) {}

  function closest(el, sel) {
    while (el && el !== document) {
      if (el.matches && el.matches(sel)) return el;
      el = el.parentNode;
    }
    return null;
  }

  function triggerInputChange(el) {
    if (!el) return;
    if (window.jQuery) {
      window.jQuery(el).trigger('input').trigger('change');
    }
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function syncGrid(grid) {
    if (!grid) return;
    var card = closest(grid, '.rubric-card[data-step-id]');
    if (!card) return;
    var type = (card.getAttribute('data-type') || '').toUpperCase();
    var selected = '';
    if (type === 'ZERO_ONE') {
      var checked = card.querySelector('input[type=\"radio\"][name^=\"steps[\"]:checked');
      selected = checked ? String(checked.value || '') : '';
    } else if (type === 'RANGE') {
      var inp = card.querySelector('input.emarking-step-input[name^=\"steps[\"]');
      selected = inp ? (parseFloat(String(inp.value || '').trim())) : NaN;
    } else {
      return;
    }

    Array.prototype.slice.call(grid.querySelectorAll('[data-mark-option]')).forEach(function(opt){
      var isSel = false;
      if (type === 'RANGE') {
        var ov = parseFloat(String(opt.getAttribute('data-value') || '').trim());
        isSel = !isNaN(selected) && !isNaN(ov) && Math.abs(ov - selected) < 0.000001;
      } else {
        isSel = String(opt.getAttribute('data-value') || '') === selected;
      }
      opt.classList.toggle('is-selected', isSel);
      opt.setAttribute('aria-pressed', isSel ? 'true' : 'false');
    });
  }

  document.addEventListener('click', function(e){
    var t = e && e.target ? e.target : null;
    if (!t) return;
    var opt = closest(t, '[data-mark-option]');
    if (!opt) return;

    var grid = closest(opt, '[data-mark-grid]');
    if (!grid) return;

    var type = (opt.getAttribute('data-type') || '').toUpperCase();
    if (type === 'COMBINED') {
      var panel = closest(opt, '.emarking-panel');
      if (!panel || !panel.classList.contains('is-combined')) return;
      var v = parseInt(opt.getAttribute('data-value') || '0', 10);
      if (isNaN(v) || v < 0) v = 0;

      // Set first v ZERO_ONE steps to correct (1), rest to wrong (0)
      var cards = Array.prototype.slice.call(panel.querySelectorAll('.rubric-card[data-type=\"ZERO_ONE\"][data-step-id]'));
      cards.forEach(function(card, idx){
        var wantCorrect = idx < v;
        var sel = wantCorrect ? ('#step_' + card.getAttribute('data-step-id') + '_c') : ('#step_' + card.getAttribute('data-step-id') + '_w');
        var radio = card.querySelector(sel);
        if (!radio) {
          // fallback: look for radio by value
          radio = card.querySelector('input[type=\"radio\"][name^=\"steps[\"][value=\"' + (wantCorrect ? '1' : '0') + '\"]');
        }
        if (!radio) return;
        if (window.jQuery) {
          window.jQuery(radio).prop('checked', true).trigger('change');
        } else {
          radio.checked = true;
          triggerInputChange(radio);
        }
      });

      // Visual sync for combined grid itself
      Array.prototype.slice.call(grid.querySelectorAll('[data-mark-option]')).forEach(function(o){
        var isSel = String(o.getAttribute('data-value') || '') === String(v);
        o.classList.toggle('is-selected', isSel);
        o.setAttribute('aria-pressed', isSel ? 'true' : 'false');
      });

      // Ensure summary updates even if jQuery-triggered events don't reach native listeners
      if (typeof computeObtained === 'function') computeObtained();
      return;
    }

    var card = closest(grid, '.rubric-card[data-step-id]');
    if (!card) return;
    if (type === 'ZERO_ONE') {
      var sel = opt.getAttribute('data-target');
      if (!sel) return;
      var radio = card.querySelector(sel);
      if (!radio) return;
      if (window.jQuery) {
        window.jQuery(radio).prop('checked', true).trigger('change');
      } else {
        radio.checked = true;
        triggerInputChange(radio);
      }
      syncGrid(grid);
      return;
    }

    if (type === 'RANGE') {
      var input = card.querySelector('input.emarking-step-input[name^=\"steps[\"]');
      if (!input) return;
      var v = opt.getAttribute('data-value');
      input.value = v;
      triggerInputChange(input);
      syncGrid(grid);
      return;
    }
  });

  document.addEventListener('keydown', function(e){
    var t = e && e.target ? e.target : null;
    if (!t) return;
    if (!(t.matches && t.matches('[data-mark-option]'))) return;
    if (e.key !== 'Enter' && e.key !== ' ') return;
    e.preventDefault();
    t.click();
  });

  var timerEl = document.getElementById('emarkingTimer');
  if (!timerEl) return;
  var seconds = parseInt(timerEl.getAttribute('data-seconds') || '15', 10);
  if (isNaN(seconds) || seconds < 0) seconds = 15;

  var buttons = Array.prototype.slice.call(document.querySelectorAll('.emarking-action-btn'));
  var obtainedEl = document.getElementById('emarkingObtained');
  var panelTitleEl = document.getElementById('emarkingPanelTitle');
  var combinedGridEl = document.querySelector('[data-combined-grid]');
  var leftScoreEl = document.getElementById('emarkingLeftScore');
  var anyChosen = false;

  function clamp(val, min, max) {
    val = parseFloat(val);
    if (isNaN(val)) val = 0;
    if (val < min) return min;
    if (val > max) return max;
    return val;
  }

  function computeObtained() {
    var total = 0;
    var correctCount = 0;
    anyChosen = false;
    var cards = Array.prototype.slice.call(document.querySelectorAll('.rubric-card[data-step-id]'));
    cards.forEach(function(card){
      if (!card) return;
      var type = (card.getAttribute('data-type') || '').toUpperCase();
      var stepMarks = parseFloat(card.getAttribute('data-step-marks') || '0') || 0;
      var min = parseFloat(card.getAttribute('data-min') || '0') || 0;
      var max = parseFloat(card.getAttribute('data-max') || '0') || 0;

      if (type === 'FIXED') {
        total += stepMarks;
        return;
      }
      if (type === 'RANGE') {
        var inp = card.querySelector('input[name^=\"steps[\"]');
        if (!inp) return;
        if (String(inp.value || '').trim() !== '') anyChosen = true;
        total += clamp(inp.value, min, max);
        return;
      }
      // ZERO_ONE
      var checked = card.querySelector('input[type=\"radio\"][name^=\"steps[\"]:checked');
      if (!checked) return;
      anyChosen = true;
      if ((checked.value || '') === '1') {
        total += stepMarks;
        correctCount += 1;
      }
    });

    if (obtainedEl) {
      var shownObt = total;
      if (Math.abs(shownObt - Math.round(shownObt)) < 1e-6) {
        obtainedEl.textContent = String(Math.round(shownObt));
      } else {
        obtainedEl.textContent = shownObt.toFixed(2);
      }
    }

    if (leftScoreEl) {
      var leftMax = (leftScoreEl.getAttribute('data-max') || '').trim();
      var leftShown = total;
      if (Math.abs(leftShown - Math.round(leftShown)) < 1e-6) leftShown = Math.round(leftShown);
      else leftShown = leftShown.toFixed(2);
      leftScoreEl.textContent = leftShown + '/' + leftMax;
    }

    if (panelTitleEl) {
      var maxMarks = parseFloat(panelTitleEl.getAttribute('data-max') || '0') || 0;
      var shown = total;
      // Screenshot style uses integers when possible
      if (Math.abs(shown - Math.round(shown)) < 1e-6) shown = Math.round(shown);
      var maxShown = maxMarks;
      if (Math.abs(maxShown - Math.round(maxShown)) < 1e-6) maxShown = Math.round(maxShown);
      var baseText = (panelTitleEl.textContent || '').replace(/\(\s*.*?\s*\)\s*$/, '').trim();
      panelTitleEl.textContent = baseText + ' (' + shown + '/' + maxShown + ')';
    }

    // Keep combined grid selection in sync (when enabled)
    if (combinedGridEl) {
      if (!anyChosen) {
        Array.prototype.slice.call(combinedGridEl.querySelectorAll('[data-mark-option]')).forEach(function(o){
          o.classList.remove('is-selected');
          o.setAttribute('aria-pressed', 'false');
        });
      } else {
        var v = correctCount;
        if (v < 0) v = 0;
        Array.prototype.slice.call(combinedGridEl.querySelectorAll('[data-mark-option]')).forEach(function(o){
          var isSel = String(o.getAttribute('data-value') || '') === String(v);
          o.classList.toggle('is-selected', isSel);
          o.setAttribute('aria-pressed', isSel ? 'true' : 'false');
        });
      }
    }
  }

  function setButtonsEnabled(enabled){
    buttons.forEach(function(b){
      if (!b) return;
      b.disabled = !enabled;
    });
  }

  // Start disabled, enable immediately if 0 seconds
  setButtonsEnabled(false);
  computeObtained();
  // Initialize selected states
  Array.prototype.slice.call(document.querySelectorAll('[data-mark-grid]')).forEach(function(g){ syncGrid(g); });

  // In combined mode: if user submits without selecting any option, default to 0 (all incorrect)
  if (combinedGridEl) {
    var formEl = closest(combinedGridEl, 'form');
    if (formEl) {
      formEl.addEventListener('submit', function(){
        if (anyChosen) return;
        var zeroOpt = combinedGridEl.querySelector('[data-mark-option][data-value=\"0\"]');
        if (zeroOpt) zeroOpt.click();
      });
    }
  }

  document.addEventListener('change', function(e){
    var t = e && e.target ? e.target : null;
    if (!t) return;
    if (t.classList && t.classList.contains('emarking-step-input')) computeObtained();
  });
  document.addEventListener('input', function(e){
    var t = e && e.target ? e.target : null;
    if (!t) return;
    if (t.classList && t.classList.contains('emarking-step-input')) computeObtained();
  });

  if (seconds <= 0) {
    timerEl.textContent = '0s';
    setButtonsEnabled(true);
    return;
  }

  function render(){
    timerEl.textContent = seconds + 's';
  }
  render();

  var interval = setInterval(function(){
    seconds -= 1;
    if (seconds <= 0) {
      seconds = 0;
      render();
      setButtonsEnabled(true);
      clearInterval(interval);
      return;
    }
    render();
  }, 1000);
})();
</script>
