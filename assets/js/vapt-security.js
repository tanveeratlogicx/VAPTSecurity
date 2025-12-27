/**
 * Handles AJAX form submission and other interactions.
 *
 * @package VAPT_Security
 */

jQuery(function ($) {
    var $form = $('#vapt-settings-form');
    var $submitBtn = $form.find('input[type="submit"]');
    var $response = $('#vapt-security-response');

    $form.on('submit', function (e) {
        e.preventDefault();

        // Add loading state
        $submitBtn.prop('disabled', true).val(VAPT_SECURITY.strings.saving || 'Saving...');
        var originalBtnText = $submitBtn.attr('value'); // Standard WP submit buttons use value attr

        // If VAPT_SECURITY.strings.saving is not defined, use a fallback
        if (!VAPT_SECURITY.strings) {
            VAPT_SECURITY.strings = { saving: 'Saving...' };
        }

        // Prepare payload matching PHP expectation (parse_str($_POST['data']))
        var payload = {
            action: 'vapt_save_settings',
            data: $form.serialize(),
            vapt_save_nonce: VAPT_SECURITY.vapt_save_nonce
        };

        $.post(VAPT_SECURITY.ajax_url, payload, function (resp) {
            if (resp.success) {
                $response.html('<div class="notice notice-success inline"><p>' + resp.data.message + '</p></div>').fadeIn();

                // Update Diagnostics if available
                if (resp.data.diagnostics) {
                    if (resp.data.diagnostics.cron_disabled) {
                        $('#vapt-diag-cron-status').html(resp.data.diagnostics.cron_disabled);
                    }
                    if (resp.data.diagnostics.cron_sim_count) {
                        $('#vapt_cron_sim_count').val(resp.data.diagnostics.cron_sim_count);
                    }
                }

                // Fade out after 3 seconds
                setTimeout(function () {
                    $response.fadeOut();
                }, 3000);
            } else {
                var errorMsg = resp.data.message || 'Unknown error occurred.';
                $response.html('<div class="notice notice-error inline"><p>' + errorMsg + '</p></div>').fadeIn();
            }
        })
            .fail(function () {
                $response.html('<div class="notice notice-error inline"><p>Server error. Please try again.</p></div>').fadeIn();
            })
            .always(function () {
                // Restore button
                $submitBtn.prop('disabled', false).val(originalBtnText);
                // Re-fetch value from attribute in case we overwrote it with "Saving..."
                // Actually, submit_button() generates input with value="Save Changes".
                // We should probably store the original text before changing it.
                $submitBtn.val($submitBtn.attr('data-original-value') || 'Save Changes');
            });
    });

    // Store original button text on load
    $submitBtn.attr('data-original-value', $submitBtn.val());

    /* ---------------------------------------------------------------------- */
    /*  Tabs Initialization                                                   */
    /* ---------------------------------------------------------------------- */
    if ($.fn.tabs) {
        $('#vapt-security-tabs').tabs({
            active: 0,
            activate: function (event, ui) {
                // Store the active tab in localStorage
                localStorage.setItem('vapt_security_active_tab', ui.newTab.index());
            },
            create: function (event, ui) {
                // Restore the active tab from localStorage
                var activeTab = localStorage.getItem('vapt_security_active_tab');
                if (activeTab !== null) {
                    $(this).tabs('option', 'active', parseInt(activeTab));
                }
            }
        });
    }

    /* ---------------------------------------------------------------------- */
    /*  Live Update: Simulation Count                                         */
    /* ---------------------------------------------------------------------- */
    $('input[name="vapt_security_options[rate_limit_max]"]').on('change keyup', function () {
        var newLimit = parseInt($(this).val()) || 15;
        if (newLimit < 1) newLimit = 15;
        var newSimCount = Math.ceil(newLimit * 1.5);
        $('#vapt_sim_count').val(newSimCount);
    });

    $('input[name="vapt_security_options[cron_rate_limit]"]').on('change keyup', function () {
        var newLimit = parseInt($(this).val()) || 60;
        if (newLimit < 1) newLimit = 60;
        var newSimCount = Math.ceil(newLimit * 1.25);
        $('#vapt_cron_sim_count').val(newSimCount);
        $('#vapt-display-cron-limit').text(newLimit);
    });

    /* ---------------------------------------------------------------------- */
    /*  Diagnostic Tool: General Rate Limit                                   */
    /* ---------------------------------------------------------------------- */
    $('#vapt-run-diagnostic').on('click', function () {
        var btn = $(this);
        var spinner = $('#vapt-diagnostic-spinner');
        var resultDiv = $('#vapt-diagnostic-result');

        btn.prop('disabled', true);
        spinner.addClass('is-active');
        resultDiv.html('');

        var blocked = false;
        var blockedCount = 0;

        var rateLimitSetting = VAPT_SECURITY.config.rate_limit;
        var totalRequests = parseInt($('#vapt_sim_count').val()) || VAPT_SECURITY.config.test_count;

        if (totalRequests < 1) {
            alert('Please enter a valid number of requests.');
            btn.prop('disabled', false);
            spinner.removeClass('is-active');
            return;
        }

        function sendRequest(i) {
            return $.ajax({
                url: VAPT_SECURITY.ajax_url,
                method: 'POST',
                data: {
                    action: 'vapt_form_submit',
                    nonce: 'dummy_nonce_diagnostic',
                    test_mode: true
                }
            }).always(function (data, textStatus, jqXHR) {
                if (jqXHR.status === 429 || (data && data.success === false && data.data && data.data.message && data.data.message.indexOf('Too many') !== -1)) {
                    blocked = true;
                    blockedCount++;
                }
            });
        }

        var promises = [];
        for (var i = 0; i < totalRequests; i++) {
            promises.push(sendRequest(i));
        }

        $.when.apply($, promises).always(function () {
            btn.prop('disabled', false);
            spinner.removeClass('is-active');

            if (blocked) {
                resultDiv.html('<div class="notice notice-success inline"><p><strong>' + VAPT_SECURITY.strings.success + '</strong> ' + VAPT_SECURITY.strings.rate_working + ' (' + blockedCount + ' blocked)</p></div>');
            } else {
                resultDiv.html('<div class="notice notice-error inline"><p><strong>' + VAPT_SECURITY.strings.warning + '</strong> ' + VAPT_SECURITY.strings.rate_failed + '</p></div>');
            }
        });
    });

    /* ---------------------------------------------------------------------- */
    /*  Diagnostic Tool: Cron Rate Limit                                      */
    /* ---------------------------------------------------------------------- */
    $('#vapt-reset-cron-limit').on('click', function () {
        var btn = $(this);
        btn.prop('disabled', true);
        $.post(VAPT_SECURITY.ajax_url, {
            action: 'vapt_reset_cron_limit',
            nonce: VAPT_SECURITY.vapt_save_nonce
        }, function (resp) {
            if (resp.success) {
                // Reset counters visually
                $('#vapt-count-total, #vapt-count-blocked, #vapt-count-allowed').text('0');
                $('#vapt-cron-diagnostic-result').html('<div class="notice notice-success inline"><p>' + resp.data.message + '</p></div>');
                setTimeout(function () { $('#vapt-cron-diagnostic-result').empty(); }, 3000);
            } else {
                alert(resp.data.message || 'Error resetting limit.');
            }
        }).always(function () {
            btn.prop('disabled', false);
        });
    });

    $('#vapt-run-cron-diagnostic').on('click', function () {
        var btn = $(this);
        var spinner = $('#vapt-cron-diagnostic-spinner');
        var resultDiv = $('#vapt-cron-diagnostic-result');
        var progressDiv = $('#vapt-cron-test-progress');
        var cronUrl = VAPT_SECURITY.config.cron_url + '?doing_wp_cron=' + Date.now() + '&vapt_test=1';

        btn.prop('disabled', true);
        spinner.addClass('is-active');
        resultDiv.html('');
        progressDiv.show();

        var totalCount = 0;
        var blockedCount = 0;
        var allowedCount = 0;

        // Reset UI counters
        $('#vapt-count-total').text('0');
        $('#vapt-count-blocked').text('0');
        $('#vapt-count-allowed').text('0');

        var blocked = false;
        var totalRequests = parseInt($('#vapt_cron_sim_count').val()) || VAPT_SECURITY.config.cron_test_count;

        function sendCronRequest(i) {
            return $.ajax({
                url: cronUrl,
                method: 'GET',
                cache: false
            }).done(function (data, textStatus, jqXHR) {
                totalCount++;
                $('#vapt-count-total').text(totalCount);
                // Success means 200 OK (Allowed)
                allowedCount++;
                $('#vapt-count-allowed').text(allowedCount);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                totalCount++;
                $('#vapt-count-total').text(totalCount);

                // Check for 429
                if (jqXHR.status === 429) {
                    blocked = true;
                    blockedCount++;
                    $('#vapt-count-blocked').text(blockedCount);
                } else {
                    // Other errors (500 etc) count as allowed for rate limit testing purposes
                    // or just ignored, but we'll flag them as allowed to keep counts matching
                    console.log('VAPT Cron Test Error: ' + jqXHR.status + ' ' + errorThrown);
                    allowedCount++;
                    $('#vapt-count-allowed').text(allowedCount);
                }
            });
        }

        var promises = [];
        for (var i = 0; i < totalRequests; i++) {
            promises.push(sendCronRequest(i));
        }

        $.when.apply($, promises).always(function () {
            btn.prop('disabled', false);
            spinner.removeClass('is-active');

            if (blocked) {
                resultDiv.html('<div class="notice notice-success inline"><p><strong>' + VAPT_SECURITY.strings.success + '</strong> ' + VAPT_SECURITY.strings.cron_working + ' (' + blockedCount + ' blocked)</p></div>');
            } else {
                resultDiv.html('<div class="notice notice-error inline"><p><strong>' + VAPT_SECURITY.strings.warning + '</strong> ' + VAPT_SECURITY.strings.cron_failed + totalRequests + VAPT_SECURITY.strings.cron_suggestion + '</p></div>');
            }
        });
    });

});
