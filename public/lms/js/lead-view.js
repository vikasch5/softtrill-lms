$(document).ready(function () {
    const quickUpdateAction = window.lmsConfig?.quickUpdateAction || '';
    const dialerCallAction = window.lmsConfig?.dialerCallAction || '';
    const dialerHangupAction = window.lmsConfig?.dialerHangupAction || '';
    const dialerStatusAction = window.lmsConfig?.dialerStatusAction || '';
    const csrfToken = window.lmsConfig?.csrfToken || '';

    function updateLeadStatusBadge() {
        const $statusBadge = $('#lead-status-badge');
        const $statusText = $('#lead-status-text');
        const $feedback = $('#lv-feedback');
        const feedbackText = $feedback.find('option:selected').text().trim().replace(/^—\s*|\s*—$/g, '');

        if (!$statusBadge.length || !$statusText.length || !$feedback.val() || !feedbackText) {
            return;
        }

        $statusText.text(feedbackText);

        const statusKey = feedbackText.toLowerCase();
        let badgeClass = 'lv-badge--neutral';

        if (['not interested', 'invalid', 'rejected', 'lost', 'closed lost', 'drop'].some(keyword => statusKey.includes(keyword))) {
            badgeClass = 'lv-badge--danger';
        } else if (['follow up', 'pending', 'callback', 'no answer', 'busy', 'reschedule'].some(keyword => statusKey.includes(keyword))) {
            badgeClass = 'lv-badge--warning';
        } else if (['new', 'open', 'fresh', 'unassigned'].some(keyword => statusKey.includes(keyword))) {
            badgeClass = 'lv-badge--info';
        } else if (['qualified', 'interested', 'won', 'enrolled', 'closed won', 'converted'].some(keyword => statusKey.includes(keyword))) {
            badgeClass = 'lv-badge--success';
        }

        $statusBadge.removeClass('lv-badge--neutral lv-badge--info lv-badge--warning lv-badge--success lv-badge--danger')
            .addClass(badgeClass);
    }

    $('#lv-feedback').on('change', function () {
        let feedbackId = $(this).val();

        $('#lv-sub-feedback').html('<option value="">Loading...</option>');

        if (!feedbackId) {
            $('#lv-sub-feedback').html('<option value="">— Select sub feedback —</option>');
            return;
        }

        $.ajax({
            url: '/feedbacks/sub-feedbacks/' + feedbackId,
            type: 'GET',
            success: function (response) {
                let options = '<option value="">— Select sub feedback —</option>';
                $.each(response, function (index, item) {
                    options += `<option value="${item.id}">${item.name}</option>`;
                });
                $('#lv-sub-feedback').html(options);
            },
            error: function () {
                $('#lv-sub-feedback').html('<option value="">No sub feedback found</option>');
            }
        });
    });

    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (settings.url === quickUpdateAction && xhr.responseJSON?.success) {
            updateLeadStatusBadge();
        }
    });

    $(document).on('click', '.dialer-call', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const phone = $btn.data('phone');
        const originalHtml = $btn.html();

        if (!phone) {
            notify_it('error', 'No phone number found for this lead.', '', 'toast');
            return;
        }

        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Calling...');

        $.ajax({
            url: dialerCallAction,
            method: 'POST',
            data: {
                _token: csrfToken,
                phone: phone,
            },
            success: function (response) {
                if (response.success) {
                    notify_it('success', 'Call initiated successfully for ' + phone, '', 'toast');
                } else {
                    notify_it('error', response.message || 'Dialer call failed.', '', 'toast');
                }
            },
            error: function (xhr) {
                let message = 'An unexpected error occurred while initiating the call.';
                if (xhr.responseJSON?.message) {
                    message = xhr.responseJSON.message;
                }
                notify_it('error', message, '', 'toast');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    $('#btn-dialer-disconnect').on('click', function () {
        triggerDialerHangup();
    });

    function triggerDialerHangup() {
        const $btn = $('#btn-dialer-disconnect');
        if (!$btn.length) return;

        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Disconnecting...');

        $.ajax({
            url: dialerHangupAction,
            method: 'POST',
            data: {_token: csrfToken},
            success: function (response) {
                if (response.success) {
                    notify_it('success', 'Call disconnected successfully.', '', 'toast');
                    $btn.closest('#dialer-disconnect-wrap').fadeOut(400);

                    setTimeout(function () {
                        $.ajax({
                            url: dialerStatusAction,
                            method: 'POST',
                            data: {_token: csrfToken},
                            success: function (statusResponse) {
                                if (!statusResponse.success) {
                                    console.warn('Dialer status update failed:', statusResponse.message);
                                }
                            },
                            error: function (xhr) {
                                console.error('Dialer status error:', xhr.responseJSON?.message);
                            },
                            complete: function () {
                                window.close();
                            }
                        });
                    }, 2000);

                } else {
                    notify_it('error', response.message || 'Hangup failed.', '', 'toast');
                    $btn.prop('disabled', false)
                        .html('<i class="ri-phone-off-line"></i> Disconnect Call');
                }
            },
            error: function (xhr) {
                const msg = 'An unexpected error occurred.';
                notify_it('error', msg, '', 'toast');
                $btn.prop('disabled', false)
                    .html('<i class="ri-phone-off-line"></i> Disconnect Call');
            }
        });
    }

    var noteEl = document.getElementById('lv-remarks');
    var countEl = document.getElementById('lv-note-count');
    if (noteEl && countEl) {
        noteEl.addEventListener('input', function () {
            countEl.textContent = this.value.length;
        });
    }
});
