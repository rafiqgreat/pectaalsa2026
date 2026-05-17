(function ($) {
	'use strict';

	var pendingSubmit = null;

	function toast(type, msg) {
		if (window.toastr) {
			toastr.options = {
				closeButton: true,
				progressBar: true,
				timeOut: 4000
			};
			toastr[type](msg);
			return;
		}
		alert(msg);
	}

	function clearValidation($form) {
		$form.find('.is-invalid').removeClass('is-invalid');
		$form.find('.js-dyn-feedback').remove();
		$form.find('.invalid-feedback').not('.js-dyn-feedback').hide();
		$form.find('.js-upload-box.is-invalid').removeClass('is-invalid');
	}

	function applyValidation($form, errors) {
		if (!errors) return;
		Object.keys(errors).forEach(function (name) {
			var msg = errors[name];
			var $el = $form.find('[name="' + name + '"]');
			if ($el.length === 0) {
				// fall back for array fields
				$el = $form.find('[name="' + name + '[]"]');
			}
			if ($el.length) {
				$el.addClass('is-invalid');
				var $target = $el.last();
				$target.after('<div class="invalid-feedback js-dyn-feedback" style="display:block">' + msg + '</div>');
			}
		});
	}

	function validateRequiredInline($form) {
		var ok = true;
		$form.find(':input[required]').each(function () {
			var el = this;
			var $el = $(el);
			if ($el.is(':disabled')) return;
			// hidden inputs are not visible; only validate visible required fields here
			if (!$el.is(':visible')) return;

			if (typeof el.checkValidity === 'function' && !el.checkValidity()) {
				ok = false;
				$el.addClass('is-invalid');
				var $fb = $el.siblings('.invalid-feedback').first();
				if ($fb.length) {
					$fb.show();
				} else {
					$el.after('<div class="invalid-feedback js-dyn-feedback" style="display:block">This field is required.</div>');
				}
				return;
			}

			var val = ($el.val() || '').toString().trim();
			if (val === '') {
				ok = false;
				$el.addClass('is-invalid');
				var $fb2 = $el.siblings('.invalid-feedback').first();
				if ($fb2.length) {
					$fb2.show();
				} else {
					$el.after('<div class="invalid-feedback js-dyn-feedback" style="display:block">This field is required.</div>');
				}
			}
		});

		// Required uploads (hidden path inputs)
		$form.find('.js-required-upload').each(function () {
			var $path = $(this);
			var groupVisible = $path.closest('.form-group').is(':visible');
			if (!groupVisible) return;
			var v = ($path.val() || '').toString().trim();
			if (v === '') {
				ok = false;
				var $group = $path.closest('.form-group');
				$group.find('.js-upload-box').addClass('is-invalid');
				var label = $path.data('label') || 'Upload';
				var $meta = $group.find('.js-upload-meta').first();
				if ($meta.length) {
					$meta.html('<span class="label">File name:</span> <span class="text-danger">Required (' + label + ')</span>');
				}
			}
		});

		return ok;
	}

	function uploadFile($input) {
		var file = $input[0].files && $input[0].files[0] ? $input[0].files[0] : null;
		if (!file) return;

		if (file.size > 5 * 1024 * 1024) {
			toast('error', 'Max upload size is 5MB.');
			$input.val('');
			return;
		}

		var $group = $input.closest('.form-group');
		var $path = $group.find('.js-upload-path').first();
		var $meta = $group.find('.js-upload-meta').first();
		var field = $group.find('.js-upload-box').data('field') || 'file';

		var fd = new FormData();
		fd.append('file', file);
		fd.append('field', field);

		$meta.html('<span class="label">File name:</span> Uploading...');

		$.ajax({
			url: window.SIGNUP_WIZARD && window.SIGNUP_WIZARD.uploadUrl ? window.SIGNUP_WIZARD.uploadUrl : '',
			type: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			success: function (res) {
				if (!res || !res.success) {
					toast('error', (res && res.message) ? res.message : 'Upload failed.');
					$meta.html('<span class="label">File name:</span> -');
					return;
				}
				$path.val(res.file_path || '');
				$group.find('.js-upload-box').removeClass('is-invalid');
				$meta.html('<span class="label">File name:</span> ' + (res.file_name || file.name));
				var $rm = $group.find('.js-remove-upload').first();
				if ($rm.length) $rm.show();
				toast('success', 'File uploaded.');
			},
			error: function (xhr) {
				var msg = 'Upload failed.';
				if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
				toast('error', msg);
				$meta.html('<span class="label">File name:</span> -');
			}
		});
	}

	function submitCurrentStep($btn) {
		var $form = $('.signup-step-form').first();
		if ($form.length === 0) return;

		clearValidation($form);

		// Normalize experience currently_working to positional array to avoid index/key mismatch after add/remove.
		var stepNow = window.SIGNUP_WIZARD ? parseInt(window.SIGNUP_WIZARD.step, 10) : 0;
		if (stepNow === 4) {
			$form.find('.js-currently-pos').remove();
			$form.find('.js-repeat-wrap[data-repeat="experience"] .js-repeat-item').each(function () {
				var $item = $(this);
				var checked = $item.find('.js-currently-working').is(':checked') ? '1' : '0';
				$form.append('<input type="hidden" class="js-currently-pos" name="currently_working_pos[]" value="' + checked + '">');
			});
		}

		var action = $btn.data('action');
		var fd = new FormData($form[0]);

		$btn.prop('disabled', true).text('Please wait...');

		$.ajax({
			url: action,
			type: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			success: function (res) {
				$btn.prop('disabled', false);
				$btn.text($btn.data('final') === 1 || $btn.data('final') === '1' ? 'Signup' : 'Save & Next');

				if (!res || !res.success) {
					toast('error', (res && res.message) ? res.message : 'Save failed.');
					if (res && res.errors) applyValidation($form, res.errors);
					return;
				}

				// Final signup success modal
				if (res.completed) {
					var loginUrl = res.login_url || (window.SIGNUP_WIZARD ? window.SIGNUP_WIZARD.loginUrl : '');
					if (window.Swal && typeof Swal.fire === 'function') {
						Swal.fire({
							icon: 'success',
							title: 'Registration Successful',
							text: 'You have been registered successfully!',
							confirmButtonText: 'Go to Login',
							confirmButtonColor: '#16a34a',
							allowOutsideClick: false
						}).then(function () {
							if (loginUrl) window.location.href = loginUrl;
						});
						return;
					}
					if (loginUrl) window.location.href = loginUrl;
					return;
				}

				toast('success', res.message || 'Saved.');

				if (res.next_url) {
					window.location.href = res.next_url;
					return;
				}
			},
			error: function (xhr) {
				$btn.prop('disabled', false);
				$btn.text($btn.data('final') === 1 || $btn.data('final') === '1' ? 'Signup' : 'Save & Next');

				var res = xhr && xhr.responseJSON ? xhr.responseJSON : null;
				var msg = res && res.message ? res.message : 'Save failed.';
				toast('error', msg);
				if (res && res.errors) applyValidation($form, res.errors);
			}
		});
	}

	function validateCnicInline($input) {
		if ($input.length === 0) return true;
		var val = ($input.val() || '').trim();
		var ok = (/^\d{5}-\d{7}-\d$/).test(val);
		var $fb = $input.siblings('.invalid-feedback').first();
		if (!ok) {
			$input.addClass('is-invalid');
			if ($fb.length) $fb.show();
		} else {
			$input.removeClass('is-invalid');
			if ($fb.length) $fb.hide();
		}
		return ok;
	}

	function tryAutoResumeFromStep1() {
		var wiz = window.SIGNUP_WIZARD || {};
		if (parseInt(wiz.step, 10) !== 1) return;
		if (parseInt(wiz.userId, 10) > 0) return; // already in-session

		var $form = $('.signup-step-form').first();
		if ($form.length === 0) return;

		var $cnic = $form.find('.js-cnic').first();
		var $dob = $form.find('[name="dob"]').first();
		if ($cnic.length === 0 || $dob.length === 0) return;

		var cnicVal = ($cnic.val() || '').toString().trim();
		var dobVal = ($dob.val() || '').toString().trim();
		if (!cnicVal || !dobVal) return;

		if (!validateCnicInline($cnic)) return;
		if (!isValidYmd(dobVal)) return;

		var url = wiz.checkResumeUrl || '';
		if (!url) return;

		$.ajax({
			url: url,
			type: 'POST',
			data: { cnic: cnicVal, dob: dobVal },
			success: function (res) {
				if (!res || !res.success) return;
				if (!res.found) return;
				toast('success', res.message || 'Resumed registration.');
				if (res.resume_url) {
					window.location.href = res.resume_url;
				}
			},
			error: function () {}
		});
	}

	function normalizeIban(val) {
		return (val || '').toString().replace(/\s+/g, '').toUpperCase();
	}

	function validateIbanInline($input) {
		if ($input.length === 0) return true;
		var raw = $input.val();
		var norm = normalizeIban(raw);
		if (raw !== norm) $input.val(norm);
		var ok = (/^[A-Z0-9]{24}$/).test(norm);
		var $fb = $input.siblings('.invalid-feedback').first();
		if (!ok && norm !== '') {
			$input.addClass('is-invalid');
			if ($fb.length) $fb.show();
		} else if (ok) {
			$input.removeClass('is-invalid');
			if ($fb.length) $fb.hide();
		}
		return ok;
	}

	function educationListDegrees($container) {
		var degrees = [];
		$container.find('input[name="degree[]"]').each(function () {
			var v = ($(this).val() || '').toString().trim();
			if (v) degrees.push(v);
		});
		return degrees;
	}

	function educationClearEntryForm($form) {
		$form.find('[name="degree_new"]').val('');
		$form.find('[name="institute_new"]').val('');
		$form.find('[name="passing_year_new"]').val('');
		$form.find('[name="cgpa_percentage_new"]').val('');
		$form.find('[name="transcript_file_new"]').val('');
		$form.find('.js-upload-input').val('');
		$form.find('.js-upload-box').removeClass('is-invalid');
		var $meta = $form.find('.js-upload-meta').first();
		if ($meta.length) {
			$meta.html('<span class="label">File name:</span> - <span class="remove-link js-remove-upload" style="margin-left:10px;display:none">Remove</span>');
		}
		$form.find('.js-edu-cancel').hide();
		$form.find('.js-edu-add').text('Add');
		$form.data('editing', null);
		$form.data('editingData', null);
	}

	function educationSetEntryForm($form, data) {
		$form.find('[name="degree_new"]').val(data.degree || '');
		$form.find('[name="institute_new"]').val(data.institute || '');
		$form.find('[name="passing_year_new"]').val(data.passing_year || '');
		$form.find('[name="cgpa_percentage_new"]').val(data.cgpa_percentage || '');
		$form.find('[name="transcript_file_new"]').val(data.transcript_file || '');

		var $meta = $form.find('.js-upload-meta').first();
		if ($meta.length) {
			var base = (data.transcript_file || '').toString().split('/').pop().split('\\').pop();
			$meta.html('<span class="label">File name:</span> ' + (base || '-') + ' <span class="remove-link js-remove-upload" style="margin-left:10px;' + (base ? '' : 'display:none') + '">Remove</span>');
		}
		$form.find('.js-edu-cancel').show();
		$form.find('.js-edu-add').text('Edit');
	}

	function educationBuildItem(data) {
		var base = (data.transcript_file || '').toString().split('/').pop().split('\\').pop();
		var html = ''
			+ '<div class="border rounded p-3 mb-3 js-edu-item" data-degree="' + $('<div>').text(data.degree).html() + '">'
			+ '  <div class="d-flex justify-content-between align-items-center">'
			+ '    <div>'
			+ '      <div style="font-weight:700;">' + $('<div>').text(data.degree).html() + '</div>'
			+ '      <div class="text-muted" style="font-size:13px;">' + $('<div>').text(data.institute).html() + ' &middot; ' + $('<div>').text(data.passing_year).html() + ' &middot; ' + $('<div>').text(data.cgpa_percentage).html() + '</div>'
			+ '      <div class="text-muted" style="font-size:12px;">File: ' + $('<div>').text(base || '-').html() + '</div>'
			+ '    </div>'
			+ '    <div style="display:flex;gap:8px;">'
			+ '      <button type="button" class="btn btn-sm btn-outline-primary js-edu-edit">Edit</button>'
			+ '      <button type="button" class="btn btn-sm btn-outline-danger js-edu-remove">Remove</button>'
			+ '    </div>'
			+ '  </div>'
			+ '  <input type="hidden" name="degree[]" value="' + $('<div>').text(data.degree).html() + '">'
			+ '  <input type="hidden" name="institute[]" value="' + $('<div>').text(data.institute).html() + '">'
			+ '  <input type="hidden" name="passing_year[]" value="' + $('<div>').text(data.passing_year).html() + '">'
			+ '  <input type="hidden" name="cgpa_percentage[]" value="' + $('<div>').text(data.cgpa_percentage).html() + '">'
			+ '  <input type="hidden" name="transcript_file[]" value="' + $('<div>').text(data.transcript_file).html() + '">'
			+ '</div>';
		return $(html);
	}

	function experienceClearEntryForm($form) {
		$form.find('[name="department_new"]').val('');
		$form.find('[name="sector_new"]').val('');
		$form.find('[name="experience_type_new"]').val('');
		$form.find('[name="job_type_new"]').val('');
		$form.find('[name="start_date_new"]').val('');
		$form.find('[name="end_date_new"]').val('').prop('disabled', false);
		$form.find('[name="currently_working_new"]').prop('checked', false);
		$form.find('[name="teaching_level_new"]').val('');
		$form.find('[name="bps_new"]').val('');
		$form.find('.js-exp-bps-wrap').hide();
		$form.find('[name="document_file_new"]').val('');
		$form.find('.js-upload-input').val('');
		$form.find('.js-upload-box').removeClass('is-invalid');
		var $meta = $form.find('.js-upload-meta').first();
		if ($meta.length) {
			$meta.html('<span class="label">File name:</span> - <span class="remove-link js-remove-upload" style="margin-left:10px;display:none">Remove</span>');
		}
		$form.find('.js-exp-cancel').hide();
		$form.find('.js-exp-add').text('Add');
		$form.data('editing', null);
		$form.data('editingData', null);
	}

	function experienceSetEntryForm($form, data) {
		$form.find('[name="department_new"]').val(data.department || '');
		$form.find('[name="sector_new"]').val(data.sector || '');
		$form.find('[name="experience_type_new"]').val(data.experience_type || '');
		$form.find('[name="job_type_new"]').val(data.job_type || '');
		$form.find('[name="start_date_new"]').val(data.start_date || '');
		$form.find('[name="end_date_new"]').val(data.end_date || '');
		$form.find('[name="currently_working_new"]').prop('checked', data.currently_working_pos === '1');
		$form.find('[name="end_date_new"]').prop('disabled', data.currently_working_pos === '1');
		$form.find('[name="teaching_level_new"]').val(data.teaching_level || '');
		$form.find('[name="bps_new"]').val(data.bps || '');
		$form.find('.js-exp-bps-wrap').toggle((data.sector || '') === 'Government');
		$form.find('[name="document_file_new"]').val(data.document_file || '');

		var $meta = $form.find('.js-upload-meta').first();
		if ($meta.length) {
			var base = (data.document_file || '').toString().split('/').pop().split('\\').pop();
			$meta.html('<span class="label">File name:</span> ' + (base || '-') + ' <span class="remove-link js-remove-upload" style="margin-left:10px;' + (base ? '' : 'display:none') + '">Remove</span>');
		}
		$form.find('.js-exp-cancel').show();
		$form.find('.js-exp-add').text('Edit');
	}

	function experienceBuildItem(data) {
		var safe = function (v) { return $('<div>').text((v || '').toString()).html(); };
		var base = (data.document_file || '').toString().split('/').pop().split('\\').pop();
		var dateText = safe(data.start_date) + ' &rarr; ' + (data.currently_working_pos === '1' ? 'Present' : safe(data.end_date));
		var bpsText = ((data.sector || '') === 'Government' && (data.bps || '').toString().trim() !== '') ? (' &middot; BPS ' + safe(data.bps)) : '';
		var html = ''
			+ '<div class="border rounded p-3 mb-3 js-exp-item">'
			+ '  <div class="d-flex justify-content-between align-items-center">'
			+ '    <div>'
			+ '      <div style="font-weight:700;">' + safe(data.department) + ' <span class="text-muted" style="font-weight:400;">(' + safe(data.sector) + ')</span></div>'
			+ '      <div class="text-muted" style="font-size:13px;">' + safe(data.experience_type) + ' &middot; ' + safe(data.job_type) + ' &middot; ' + safe(data.teaching_level) + '</div>'
			+ '      <div class="text-muted" style="font-size:12px;">' + dateText + bpsText + '</div>'
			+ '      <div class="text-muted" style="font-size:12px;">File: ' + safe(base || '-') + '</div>'
			+ '    </div>'
			+ '    <div style="display:flex;gap:8px;">'
			+ '      <button type="button" class="btn btn-sm btn-outline-primary js-exp-edit">Edit</button>'
			+ '      <button type="button" class="btn btn-sm btn-outline-danger js-exp-remove">Remove</button>'
			+ '    </div>'
			+ '  </div>'
			+ '  <input type="hidden" name="department[]" value="' + safe(data.department) + '">'
			+ '  <input type="hidden" name="sector[]" value="' + safe(data.sector) + '">'
			+ '  <input type="hidden" name="experience_type[]" value="' + safe(data.experience_type) + '">'
			+ '  <input type="hidden" name="job_type[]" value="' + safe(data.job_type) + '">'
			+ '  <input type="hidden" name="start_date[]" value="' + safe(data.start_date) + '">'
			+ '  <input type="hidden" name="end_date[]" value="' + safe(data.currently_working_pos === '1' ? '' : data.end_date) + '">'
			+ '  <input type="hidden" name="currently_working_pos[]" value="' + safe(data.currently_working_pos) + '">'
			+ '  <input type="hidden" name="teaching_level[]" value="' + safe(data.teaching_level) + '">'
			+ '  <input type="hidden" name="bps[]" value="' + safe(data.bps) + '">'
			+ '  <input type="hidden" name="document_file[]" value="' + safe(data.document_file) + '">'
			+ '</div>';
		return $(html);
	}

	function nextIndex($wrap) {
		var max = -1;
		$wrap.find('.js-repeat-item').each(function () {
			var idx = parseInt($(this).attr('data-index'), 10);
			if (!isNaN(idx)) max = Math.max(max, idx);
		});
		return max + 1;
	}

	function addRepeat(type) {
		var tplId = '#tpl-' + type;
		var $tpl = $(tplId);
		if ($tpl.length === 0) return;

		var $wrap = $('.js-repeat-wrap[data-repeat="' + type + '"]').first();
		if ($wrap.length === 0) return;

		var idx = nextIndex($wrap);
		var html = $tpl.html().replace(/__INDEX__/g, String(idx));
		$wrap.append(html);
	}

	function wireExperienceItem($item) {
		var $cw = $item.find('.js-currently-working');
		var $end = $item.find('.js-end-date');
		if ($cw.is(':checked')) {
			$end.val('').prop('disabled', true);
		}
	}

	function markInvalid($el, message) {
		var $el = $($el);
		$el.addClass('is-invalid');
		var $fb = $el.siblings('.invalid-feedback').first();
		if ($fb.length) {
			$fb.text(message).show();
			return;
		}
		$el.after('<div class="invalid-feedback js-dyn-feedback" style="display:block">' + message + '</div>');
	}

	function isValidYmd(value) {
		if (!value) return false;
		return (/^\d{4}-\d{2}-\d{2}$/).test(value);
	}

	function validateDateRange($start, $end, opts) {
		opts = opts || {};
		var startVal = ($start.val() || '').toString().trim();
		var endVal = ($end.val() || '').toString().trim();
		if (!startVal) return false;
		if (opts.endRequired && !endVal) return false;
		if (endVal && !isValidYmd(endVal)) return false;
		if (!isValidYmd(startVal)) return false;
		if (!endVal) return true;
		return (startVal <= endVal); // Y-m-d lexicographic compare works
	}

	function validateExperienceDates($form) {
		var ok = true;
		$form.find('.js-repeat-wrap[data-repeat="experience"] .js-repeat-item').each(function () {
			var $item = $(this);
			var $start = $item.find('.js-start-date').first();
			var $end = $item.find('.js-end-date').first();
			var isCurrently = $item.find('.js-currently-working').is(':checked');
			if ($start.length === 0 || $end.length === 0) return;
			if (isCurrently) return;
			var startVal = ($start.val() || '').toString().trim();
			var endVal = ($end.val() || '').toString().trim();
			if (!startVal) return;

			// End is required when not currently working
			var endRequired = true;
			if (!endVal) {
				ok = false;
				markInvalid($end, 'End Date is required unless Currently Working is checked.');
				return;
			}
			if (!validateDateRange($start, $end, { endRequired: endRequired })) {
				ok = false;
				markInvalid($end, 'End Date must be greater than or equal to Start Date.');
			}
		});
		return ok;
	}

	function validateEmarkingDates($form) {
		var ok = true;
		$form.find('.js-repeat-wrap[data-repeat="emarking"] .js-repeat-item').each(function () {
			var $item = $(this);
			var $from = $item.find('.js-from-date').first();
			var $to = $item.find('.js-to-date').first();
			if ($from.length === 0 || $to.length === 0) return;
			var fromVal = ($from.val() || '').toString().trim();
			var toVal = ($to.val() || '').toString().trim();
			if (!fromVal || !toVal) return;
			if (!validateDateRange($from, $to, { endRequired: true })) {
				ok = false;
				markInvalid($to, 'To date must be greater than or equal to From date.');
			}
		});
		return ok;
	}

	$(function () {
		// CNIC mask + inline validation (step 1)
		if ($.fn.inputmask) {
			$('.js-cnic').inputmask('99999-9999999-9', { placeholder: '_____-_______-_' });
		}
		$(document).on('keyup blur change', '.js-cnic', function (e) {
			validateCnicInline($(this));
			// Only try auto-resume when leaving CNIC with both valid values present
			if (e && e.type === 'blur') tryAutoResumeFromStep1();
		});
		$(document).on('blur', '.signup-step-form [name="dob"]', function () {
			tryAutoResumeFromStep1();
		});

		// Clear inline validation once corrected
		$(document).on('input change blur', '.signup-step-form :input', function () {
			var $el = $(this);
			if ($el.is(':disabled')) return;
			if (!$el.hasClass('is-invalid')) return;

			// CNIC uses its own validator
			if ($el.hasClass('js-cnic')) return;
			// IBAN uses its own validator
			if ($el.hasClass('js-iban')) {
				validateIbanInline($el);
				return;
			}

			var el = this;
			var valid = true;
			if (typeof el.checkValidity === 'function') {
				valid = el.checkValidity();
			} else {
				var val = ($el.val() || '').toString().trim();
				valid = (val !== '');
			}
			if (!valid) return;

			$el.removeClass('is-invalid');
			$el.siblings('.invalid-feedback').hide();
			$el.siblings('.js-dyn-feedback').remove();
			// Also remove immediate next dynamic feedback if inserted after element
			if ($el.next('.js-dyn-feedback').length) $el.next('.js-dyn-feedback').remove();
		});

		// IBAN normalize + inline validation (step 5)
		$(document).on('input blur change', '.js-iban', function () {
			validateIbanInline($(this));
		});

		// Upload widget
		$(document).on('click', '.js-upload-box', function (e) {
			// Prevent recursion when the file input click bubbles back to the box.
			if ($(e.target).hasClass('js-upload-input')) return;
			e.preventDefault();
			e.stopPropagation();
			var input = $(this).find('.js-upload-input').get(0);
			if (input) input.click();
		});
		$(document).on('change', '.js-upload-input', function () {
			uploadFile($(this));
		});

		// Remove uploaded file
		$(document).on('click', '.js-remove-upload', function (e) {
			e.preventDefault();
			var $group = $(this).closest('.form-group');
			var $path = $group.find('.js-upload-path').first();
			var existing = ($path.val() || '').toString().trim();

			$path.val('');
			$group.find('.js-upload-box').removeClass('is-invalid');
			$group.find('.js-upload-input').val('');
			var $meta = $group.find('.js-upload-meta').first();
			if ($meta.length) {
				$meta.find('.text-danger').remove();
				$meta.html('<span class="label">File name:</span> - <span class="remove-link js-remove-upload" style="margin-left:10px;display:none">Remove</span>');
			}

			var delUrl = window.SIGNUP_WIZARD && window.SIGNUP_WIZARD.deleteUrl ? window.SIGNUP_WIZARD.deleteUrl : '';
			if (existing && delUrl) {
				$.ajax({
					url: delUrl,
					type: 'POST',
					data: { file_path: existing },
					success: function () {},
					error: function () {}
				});
			}
		});

		// Repeatables
		$(document).on('click', '.js-add-more', function () {
			var type = $(this).data('repeat');
			addRepeat(type);
		});
		$(document).on('click', '.js-remove-item', function () {
			$(this).closest('.js-repeat-item').remove();
		});

		// Experience rules
		$(document).on('change', '.js-no-experience', function () {
			$('.js-experience-wrap').toggle(!$(this).is(':checked'));
		});

		// Step 4 experience add/edit list
		if (window.SIGNUP_WIZARD && parseInt(window.SIGNUP_WIZARD.step, 10) === 4) {
			var $expForm = $('#experienceEntryForm');
			var $expList = $('#experienceList');

			$(document).on('change', '#experienceEntryForm .js-exp-sector', function () {
				var isGov = ($(this).val() || '') === 'Government';
				$expForm.find('.js-exp-bps-wrap').toggle(isGov);
				if (!isGov) $expForm.find('[name="bps_new"]').val('');
			});
			$(document).on('change', '#experienceEntryForm .js-exp-current', function () {
				var on = $(this).is(':checked');
				var $end = $expForm.find('[name="end_date_new"]');
				if (on) {
					$end.val('').prop('disabled', true);
				} else {
					$end.prop('disabled', false);
				}
			});
			$(document).on('change blur', '#experienceEntryForm .js-exp-start', function () {
				var startVal = ($expForm.find('[name="start_date_new"]').val() || '').toString().trim();
				if (startVal) $expForm.find('[name="end_date_new"]').attr('min', startVal);
			});

			$(document).on('click', '.js-exp-add', function () {
				var $mainForm = $('.signup-step-form').first();
				clearValidation($mainForm);

				var isGov = (($expForm.find('[name="sector_new"]').val() || '').toString() === 'Government');
				var currently = $expForm.find('[name="currently_working_new"]').is(':checked') ? '1' : '0';

				var data = {
					department: ($expForm.find('[name="department_new"]').val() || '').toString().trim(),
					sector: ($expForm.find('[name="sector_new"]').val() || '').toString().trim(),
					experience_type: ($expForm.find('[name="experience_type_new"]').val() || '').toString().trim(),
					job_type: ($expForm.find('[name="job_type_new"]').val() || '').toString().trim(),
					start_date: ($expForm.find('[name="start_date_new"]').val() || '').toString().trim(),
					end_date: ($expForm.find('[name="end_date_new"]').val() || '').toString().trim(),
					currently_working_pos: currently,
					teaching_level: ($expForm.find('[name="teaching_level_new"]').val() || '').toString().trim(),
					bps: ($expForm.find('[name="bps_new"]').val() || '').toString().trim(),
					document_file: ($expForm.find('[name="document_file_new"]').val() || '').toString().trim()
				};

				var ok = true;
				if (!data.department) { ok = false; markInvalid($expForm.find('[name="department_new"]'), 'Department is required.'); }
				if (!data.sector) { ok = false; markInvalid($expForm.find('[name="sector_new"]'), 'Sector is required.'); }
				if (!data.experience_type) { ok = false; markInvalid($expForm.find('[name="experience_type_new"]'), 'Experience Type is required.'); }
				if (!data.job_type) { ok = false; markInvalid($expForm.find('[name="job_type_new"]'), 'Job Type is required.'); }
				if (!data.start_date || !isValidYmd(data.start_date)) { ok = false; markInvalid($expForm.find('[name="start_date_new"]'), 'Start Date is required.'); }
				if (!data.teaching_level) { ok = false; markInvalid($expForm.find('[name="teaching_level_new"]'), 'Teaching Level is required.'); }
				if (isGov && !data.bps) { ok = false; markInvalid($expForm.find('[name="bps_new"]'), 'BPS is required for Government sector.'); }
				if (data.currently_working_pos !== '1') {
					if (!data.end_date) { ok = false; markInvalid($expForm.find('[name="end_date_new"]'), 'End Date is required unless Currently Working is checked.'); }
					else if (!isValidYmd(data.end_date)) { ok = false; markInvalid($expForm.find('[name="end_date_new"]'), 'Invalid End Date.'); }
					else if (data.end_date < data.start_date) { ok = false; markInvalid($expForm.find('[name="end_date_new"]'), 'End Date must be greater than or equal to Start Date.'); }
				}
				if (!data.document_file) {
					ok = false;
					$expForm.find('.js-upload-box').addClass('is-invalid');
					var $meta = $expForm.find('.js-upload-meta').first();
					if ($meta.length) $meta.html('<span class="label">File name:</span> <span class="text-danger">Required (Relevant Document)</span>');
				}
				if (!ok) {
					toast('error', 'Please fill all required fields.');
					return;
				}

				$expList.append(experienceBuildItem(data));
				experienceClearEntryForm($expForm);
				toast('success', 'Experience added.');
			});

			$(document).on('click', '.js-exp-edit', function () {
				var $item = $(this).closest('.js-exp-item');
				if ($item.length === 0) return;

				var data = {
					department: ($item.find('input[name="department[]"]').val() || '').toString().trim(),
					sector: ($item.find('input[name="sector[]"]').val() || '').toString().trim(),
					experience_type: ($item.find('input[name="experience_type[]"]').val() || '').toString().trim(),
					job_type: ($item.find('input[name="job_type[]"]').val() || '').toString().trim(),
					start_date: ($item.find('input[name="start_date[]"]').val() || '').toString().trim(),
					end_date: ($item.find('input[name="end_date[]"]').val() || '').toString().trim(),
					currently_working_pos: ($item.find('input[name="currently_working_pos[]"]').val() || '').toString().trim() || '0',
					teaching_level: ($item.find('input[name="teaching_level[]"]').val() || '').toString().trim(),
					bps: ($item.find('input[name="bps[]"]').val() || '').toString().trim(),
					document_file: ($item.find('input[name="document_file[]"]').val() || '').toString().trim()
				};

				$expForm.data('editing', '1');
				$expForm.data('editingData', data);
				experienceSetEntryForm($expForm, data);
				$item.remove();
				toast('info', 'Edit the fields and click Edit to update.');
			});

			$(document).on('click', '.js-exp-remove', function () {
				$(this).closest('.js-exp-item').remove();
			});

			$(document).on('click', '.js-exp-cancel', function () {
				var editingData = $expForm.data('editingData');
				if (editingData && editingData.department) {
					$expList.append(experienceBuildItem(editingData));
				}
				experienceClearEntryForm($expForm);
			});
		}

		// Emarking rules
		$(document).on('change', '.js-no-emarking', function () {
			$('.js-emarking-wrap').toggle(!$(this).is(':checked'));
		});
		$(document).on('change blur', '.js-from-date, .js-to-date', function () {
			var $item = $(this).closest('.js-repeat-item');
			var $from = $item.find('.js-from-date').first();
			var $to = $item.find('.js-to-date').first();
			if ($from.length && $to.length) {
				var fromVal = ($from.val() || '').toString().trim();
				if (fromVal) $to.attr('min', fromVal);
			}
		});

		// Save confirmation modal
		$(document).on('click', '.js-save-next', function () {
			var $form = $('.signup-step-form').first();
			if ($form.length) {
				clearValidation($form);

				// Step-specific toggles affect required rules
				var step = window.SIGNUP_WIZARD ? parseInt(window.SIGNUP_WIZARD.step, 10) : 0;
				if (step === 4 && $('.js-no-experience').is(':checked')) {
					// skip validation when section hidden
				} else if (step === 8 && $('.js-no-emarking').is(':checked')) {
					// skip validation when section hidden
				}

				if (!validateRequiredInline($form)) {
					toast('error', 'Please fill all required fields.');
					return;
				}

				if (step === 4 && !$('.js-no-experience').is(':checked')) {
					// Ensure at least one experience record has been added.
					if ($form.find('input[name="department[]"]').length < 1) {
						toast('error', 'Please add at least one experience record.');
						return;
					}
				}
				if (step === 8 && !$('.js-no-emarking').is(':checked')) {
					if (!validateEmarkingDates($form)) {
						toast('error', 'Please correct your e-marking dates.');
						return;
					}
				}

				if (step === 1) {
					var $cnic = $('.js-cnic').first();
					if ($cnic.length && !validateCnicInline($cnic)) {
						toast('error', 'Please enter valid CNIC.');
						return;
					}
				}
				if (step === 3) {
					// Ensure at least one education record has been added.
					if ($form.find('input[name="degree[]"]').length < 1) {
						toast('error', 'Please add at least one education record for SSC, HSSC, and Master / M.A / MSc. / BS (Hons) (16 Years) using the "Add More" button.');
						return;
					}
				}
				if (step === 5) {
					var $iban = $('.js-iban').first();
					if ($iban.length && !validateIbanInline($iban)) {
						toast('error', 'Please enter a valid 24-character IBAN.');
						return;
					}
				}
			}

			pendingSubmit = $(this);
			$('#confirmMoveModal').modal('show');
		});
		$(document).on('click', '.js-confirm-yes', function () {
			$('#confirmMoveModal').modal('hide');
			if (pendingSubmit) submitCurrentStep(pendingSubmit);
			pendingSubmit = null;
		});
		$(document).on('click', '.js-confirm-cancel', function () {
			pendingSubmit = null;
		});

		// Step 3 education add/edit list
		if (window.SIGNUP_WIZARD && parseInt(window.SIGNUP_WIZARD.step, 10) === 3) {
			var $eduForm = $('#educationEntryForm');
			var $eduList = $('#educationList');

			$(document).on('click', '.js-edu-add', function () {
				var $mainForm = $('.signup-step-form').first();
				clearValidation($mainForm);

				var data = {
					degree: ($eduForm.find('[name="degree_new"]').val() || '').toString().trim(),
					institute: ($eduForm.find('[name="institute_new"]').val() || '').toString().trim(),
					passing_year: ($eduForm.find('[name="passing_year_new"]').val() || '').toString().trim(),
					cgpa_percentage: ($eduForm.find('[name="cgpa_percentage_new"]').val() || '').toString().trim(),
					transcript_file: ($eduForm.find('[name="transcript_file_new"]').val() || '').toString().trim()
				};

				var ok = true;
				if (!data.degree) { ok = false; markInvalid($eduForm.find('[name="degree_new"]'), 'Degree is required.'); }
				if (!data.institute) { ok = false; markInvalid($eduForm.find('[name="institute_new"]'), 'Institute/University is required.'); }
				if (!data.passing_year) { ok = false; markInvalid($eduForm.find('[name="passing_year_new"]'), 'Passing Year is required.'); }
				if (!data.cgpa_percentage) { ok = false; markInvalid($eduForm.find('[name="cgpa_percentage_new"]'), 'CGPA/Percentage is required.'); }
				if (!data.transcript_file) {
					ok = false;
					$eduForm.find('.js-upload-box').addClass('is-invalid');
					var $meta = $eduForm.find('.js-upload-meta').first();
					if ($meta.length) $meta.html('<span class="label">File name:</span> <span class="text-danger">Required (Degree/Transcript)</span>');
				}
				if (!ok) {
					toast('error', 'Please fill all required fields.');
					return;
				}

				var degrees = educationListDegrees($eduList);
				var editing = $eduForm.data('editing');
				var already = degrees.indexOf(data.degree) !== -1;
				if (already && !editing) {
					toast('error', 'This degree is already added.');
					return;
				}
				if (already && editing) {
					// Editing item was removed from list, so duplicate means another item exists.
					toast('error', 'This degree is already added.');
					return;
				}

				var $item = educationBuildItem(data);
				$eduList.append($item);
				educationClearEntryForm($eduForm);
				toast('success', 'Degree added.');
			});

			$(document).on('click', '.js-edu-edit', function () {
				var $item = $(this).closest('.js-edu-item');
				if ($item.length === 0) return;

				var data = {
					degree: ($item.find('input[name="degree[]"]').val() || '').toString().trim(),
					institute: ($item.find('input[name="institute[]"]').val() || '').toString().trim(),
					passing_year: ($item.find('input[name="passing_year[]"]').val() || '').toString().trim(),
					cgpa_percentage: ($item.find('input[name="cgpa_percentage[]"]').val() || '').toString().trim(),
					transcript_file: ($item.find('input[name="transcript_file[]"]').val() || '').toString().trim()
				};

				$eduForm.data('editing', '1');
				$eduForm.data('editingData', data);
				educationSetEntryForm($eduForm, data);
				$item.remove();
				toast('info', 'Edit the fields and click Add to update.');
			});

			$(document).on('click', '.js-edu-remove', function () {
				$(this).closest('.js-edu-item').remove();
			});

			$(document).on('click', '.js-edu-cancel', function () {
				var editingData = $eduForm.data('editingData');
				if (editingData && editingData.degree) {
					// Restore the removed item back to the list
					$eduList.append(educationBuildItem(editingData));
				}
				educationClearEntryForm($eduForm);
			});
		}
	});
})(jQuery);
