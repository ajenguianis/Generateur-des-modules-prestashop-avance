$(document).ready(function () {
    $('.login-info-box').fadeOut();
    $('.login-show').addClass('show-log-panel');
    $("#backend-tab .choice").each(function () {
        $(this).click(function () {
            let modal=$(this).data('show');
           if($(this).hasClass('active')){
               $('#'+modal).modal('show');
           }
        });
    });
    $("#backend-tab .add-new").each(function () {
        $(this).click(function () {
            let count=$(this).attr('data-child');
            let type=$(this).data('type');
            if(type=='command'){
             let html='                <div class="snippet-fields row">\n' +
                 '                    <div class="field-input col-xs-4">\n' +
                 '                        <div class="form-group">\n' +
                 '                            <label for="command_name-'+count+'" class="control-label">Class name</label>\n' +
                 '                            <input value="" id="command-'+count+'" name="command_name_'+count+'" class="form-control" type="text"\n' +
                 '                                   placeholder="">\n' +
                 '                            <div class="help-block"></div>\n' +
                 '                        </div>\n' +
                 '                    </div>\n' +
                 '                    <div class="field-input col-xs-4">\n' +
                 '                        <div class="form-group">\n' +
                 '                            <label for="command_call-'+count+'" class="control-label">Command name</label>\n' +
                 '                            <input value="" id="command_call-'+count+'" name="command_call_'+count+'" class="form-control" type="text"\n' +
                 '                                   placeholder="">\n' +
                 '                            <div class="help-block">call name exemple product:import</div>\n' +
                 '                        </div>\n' +
                 '                    </div>\n' +
                 '                    <div class="field-input col-xs-2">\n' +
                 '                        <div class="form-group">\n' +
                 '                            <button type="button" class="btn btn-danger remove">\n' +
                 '                                Remove\n' +
                 '                            </button>\n' +
                 '                        </div>\n' +
                 '                    </div>\n' +
                 '                </div>';
                $(this).closest('.modal-body').append(html);
                $(this).attr('data-child', parseInt(count) +1);
            }
            if(type=='helper'){
                let html='                <div class="snippet-fields row">\n' +
                    '                    <div class="field-input col-xs-8">\n' +
                    '                        <div class="form-group">\n' +
                    '                            <label for="helper_name-'+count+'" class="control-label">Helper name</label>\n' +
                    '                            <input value="" id="helper-'+count+'" name="helper_name_'+count+'" class="form-control" type="text"\n' +
                    '                                   placeholder="">\n' +
                    '                            <div class="help-block"></div>\n' +
                    '                        </div>\n' +
                    '                    </div>\n' +
                    '\n' +
                    '                    <div class="field-input col-xs-2">\n' +
                    '                        <div class="form-group">\n' +
                    '                            <button type="button" class="btn btn-danger remove">\n' +
                    '                                Remove\n' +
                    '                            </button>\n' +
                    '                        </div>\n' +
                    '                    </div>\n' +
                    '                </div>';
                $(this).closest('.modal-body').append(html);
                $(this).attr('data-child', parseInt(count) +1);
            }
            if(type=='service'){
                let html='                <div class="snippet-fields row">\n' +
                    '                    <div class="field-input col-xs-8">\n' +
                    '                        <div class="form-group">\n' +
                    '                            <label for="service_name-'+count+'" class="control-label">service name</label>\n' +
                    '                            <input value="" id="service-'+count+'" name="service_name_'+count+'" class="form-control" type="text"\n' +
                    '                                   placeholder="">\n' +
                    '                            <div class="help-block"></div>\n' +
                    '                        </div>\n' +
                    '                    </div>\n' +
                    '\n' +
                    '                    <div class="field-input col-xs-2">\n' +
                    '                        <div class="form-group">\n' +
                    '                            <button type="button" class="btn btn-danger remove">\n' +
                    '                                Remove\n' +
                    '                            </button>\n' +
                    '                        </div>\n' +
                    '                    </div>\n' +
                    '                </div>';
                $(this).closest('.modal-body').append(html);
                $(this).attr('data-child', parseInt(count) +1);
            }
        });
    });
    $(document).on("click", '#backend-tab .remove', function () {
        $(this).closest('.snippet-fields').remove();
    });
});


$('.login-reg-panel input[type="radio"]').on('change', function () {
    if ($('#log-login-show').is(':checked')) {
        $('.register-info-box').fadeOut();
        $('.login-info-box').fadeIn();

        $('.white-panel').addClass('right-log');
        $('.register-show').addClass('show-log-panel');
        $('.login-show').removeClass('show-log-panel');

    } else if ($('#log-reg-show').is(':checked')) {
        $('.register-info-box').fadeIn();
        $('.login-info-box').fadeOut();

        $('.white-panel').removeClass('right-log');

        $('.login-show').addClass('show-log-panel');
        $('.register-show').removeClass('show-log-panel');
    }
});

