$(document).ready(function () {
    $('.js-select2').select2({
        placeholder: "Select a Hook",
        allowClear: true
    });
    $('.login-info-box').fadeOut();
    $('.login-show').addClass('show-log-panel');
    $("#backend-tab-gen .choice").each(function () {
        $(this).click(function () {
            let modal = $(this).data('show');
            if ($(this).hasClass('active')) {
                $('#' + modal).modal('show');
            }
        });
    });
    $("#backend-tab-adv .choice").each(function () {
        $(this).click(function () {
            let modal = $(this).data('show');
            if ($(this).hasClass('active')) {
                $('#' + modal).modal('show');
            }
        });
    });
    // $("#backend-tab .add-new").each(function () {
    //     $(this).click(function () {
    //         displayForm();
    //     });
    // });
    $(document).on("click", '#backend-tab-gen .remove', function () {
        $(this).closest('.snippet-fields').remove();
    });
    $(document).on("click", '#backend-tab-adv .remove', function () {
        $(this).closest('.snippet-fields').remove();
    })
    $(document).on("click", '.add-new', function () {
        let type = $(this).attr('data-type');
        displayForm(this, type);
    });
    function displayForm(elm, type) {

        let count = 1;
        if (typeof $(elm).attr('data-child') !== 'undefined') {
            count = $(elm).attr('data-child') ?? 1;
        }
        let modelCount = $(elm).attr('data-model');
        let CurentModelCount = 1;
        if (typeof $(elm).closest('.modal-body').find('.liste-box').attr('data-currentCount') !== 'undefined') {
            CurentModelCount = $(elm).closest('.modal-body').find('.liste-box').attr('data-currentCount');
        }

        if (type == 'command') {
            let html = '                <div class="snippet-fields row">\n' +
                '                    <div class="field-input col-xs-4">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="command_name-' + count + '" class="control-label">Class name</label>\n' +
                '                            <input value="" id="command-' + count + '" name="command_name_' + count + '" class="form-control" type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input col-xs-4">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="command_call-' + count + '" class="control-label">Command name</label>\n' +
                '                            <input value="" id="command_call-' + count + '" name="command_call_' + count + '" class="form-control" type="text"\n' +
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
            $(elm).closest('.modal-body').append(html);
            $(elm).attr('data-child', parseInt(count) + 1);
        }
        if (type == 'helper') {
            let html = '                <div class="snippet-fields row">\n' +
                '                    <div class="field-input col-xs-8">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="helper_name-' + count + '" class="control-label">Helper name</label>\n' +
                '                            <input value="" id="helper-' + count + '" name="helper_name_' + count + '" class="form-control" type="text"\n' +
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
            $(elm).closest('.modal-body').append(html);
            $(elm).attr('data-child', parseInt(count) + 1);
        }
        if (type == 'service') {
            let html = '                <div class="snippet-fields row">\n' +
                '                    <div class="field-input col-xs-8">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="service_name-' + count + '" class="control-label">service name</label>\n' +
                '                            <input value="" id="service-' + count + '" name="service_name_' + count + '" class="form-control" type="text"\n' +
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
            $(elm).closest('.modal-body').append(html);
            $(elm).attr('data-child', parseInt(count) + 1);
        }

        if (type == 'field') {
            let html = '           <div class="snippet-fields row">\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="field_name_' + count + '_' + CurentModelCount + '" class="control-label">field name</label>\n' +
                '                            <input value="" id="service_' + count + '_' + CurentModelCount + '" name="field_name_' + count + '_' + CurentModelCount + '" class="form-control" type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="field_type_' + count + '_' + CurentModelCount + '" class="control-label">Type</label>\n' +
                '                            <select class="form-control" name="field_type_' + count + '_' + CurentModelCount + '">\n' +
                '                                <option title="Un nombre entier de 4 octets. La fourchette des entiers relatifs est de -2 147 483 648 à 2 147 483 647. Pour les entiers positifs, c\\est de 0 à 4 294 967 295">\n' +
                '                                    INT\n' +
                '                                </option>\n' +
                '                                <option title="Une chaîne de longueur variable (0-65,535), la longueur effective réelle dépend de la taille maximale d\\une ligne">\n' +
                '                                    VARCHAR\n' +
                '                                </option>\n' +
                '                                <option title="Une colonne TEXT d\\une longueur maximale de 65 535 (2^16 - 1) caractères, stockée avec un préfixe de deux octets indiquant la longueur de la valeur en octets">\n' +
                '                                    TEXT\n' +
                '                                </option>\n' +
                '                                <optgroup label="Numérique">\n' +
                '                                    <option title="Un nombre entier de 1 octet. La fourchette des nombres avec signe est de _' + count + '28 à 127. Pour les nombres sans signe, c\\est de 0 à 255">\n' +
                '                                        TINYINT\n' +
                '                                    </option>\n' +
                '                                    <option title="Un nombre entier de 4 octets. La fourchette des entiers relatifs est de -2 147 483 648 à 2 147 483 647. Pour les entiers positifs, c\\est de 0 à 4 294 967 295">\n' +
                '                                        INT\n' +
                '                                    </option>\n' +
                '                                    <option title="Un nombre en virgule fixe (M, D). Le nombre maximum de chiffres (M) est de 65 (10 par défaut), le nombre maximum de décimales (D) est de 30 (0 par défaut)">\n' +
                '                                        DECIMAL\n' +
                '                                    </option>\n' +
                '                                    <option title="Un synonyme de TINYINT(1), une valeur de zéro signifie faux, une valeur non-zéro signifie vrai">\n' +
                '                                        BOOLEAN\n' +
                '                                    </option>\n' +
                '                                </optgroup>\n' +
                '                                <optgroup label="Date et l\\heure">\n' +
                '                                    <option title="Une date, la fourchette est de «1000-01-01» à «9999_' + count + '2-31»">DATE</option>\n' +
                '                                    <option title="Une combinaison date et heure, la fourchette est de « 1000-01-01 00:00:00 » à « 9999_' + count + '2-31 23:59:59 »">\n' +
                '                                        DATETIME\n' +
                '                                    </option>\n' +
                '                                </optgroup>\n' +
                '                                <optgroup label="Chaîne de caractères">\n' +
                '                                    <option title="Une chaîne de longueur variable (0-65,535), la longueur effective réelle dépend de la taille maximale d\\une ligne">\n' +
                '                                        VARCHAR\n' +
                '                                    </option>\n' +
                '\n' +
                '                                    <option title="Une colonne TEXT d\\une longueur maximale de 65 535 (2^16 - 1) caractères, stockée avec un préfixe de deux octets indiquant la longueur de la valeur en octets">\n' +
                '                                        TEXT\n' +
                '                                    </option>>\n' +
                '                                    <option title="Une colonne TEXT d\\une longueur maximale de 4 294 967 295 ou 4 GiB (2^32 - 1) caractères, stockée avec un préfixe de quatre octets indiquant la longueur de la valeur en octets">\n' +
                '                                        LONGTEXT\n' +
                '                                    </option>\n' +
                '                                </optgroup>\n' +
                '                                    <optgroup label="Specifique">\n' +
                '                                        <option>\n' +
                '                                            EMAIL\n' +
                '                                        </option>\n' +
                '                                        <option>\n' +
                '                                            HTML\n' +
                '                                        </option>\n' +
                '                                        <option>\n' +
                '                                            PERCENT\n' +
                '                                        </option>                                        \n' +
                '                                        <option>\n' +
                '                                            UnsignedInt\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '                            </select>\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="field_length_' + count + '_' + CurentModelCount + '" class="control-label">Length</label>\n' +
                '                            <input id="service_' + count + '_' + CurentModelCount + '" name="field_length_' + count + '_' + CurentModelCount + '" class="form-control" type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="is_nullable_' + count + '_' + CurentModelCount + '" class="control-label">Null</label>\n' +
                '                            <input value="1" id="service_' + count + '_' + CurentModelCount + '" name="is_nullable_' + count + '_' + CurentModelCount + '" class="form-control" type="checkbox"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="is_auto_increment_' + count + '_' + CurentModelCount + '" class="control-label">A.I</label>\n' +
                '                            <input value="1" id="service_' + count + '_' + CurentModelCount + '" name="is_auto_increment_' + count + '_' + CurentModelCount + '" class="form-control" type="checkbox"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="is_lang_' + count + '_' + CurentModelCount + '" class="control-label">Lang?</label>\n' +
                '                            <input value="1" id="service_' + count + '_' + CurentModelCount + '" name="is_lang_' + count + '_' + CurentModelCount + '" class="form-control" type="checkbox"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="is_shop_' + count + '_' + CurentModelCount + '" class="control-label">Shop?</label>\n' +
                '                            <input value="1" id="service_' + count + '_' + CurentModelCount + '" name="is_shop_' + count + '_' + CurentModelCount + '" class="form-control" type="checkbox"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="default_value_' + count + '_' + CurentModelCount + '" class="control-label">Default Value</label>\n' +
                '                            <input value="" id="service_' + count + '_' + CurentModelCount + '" name="default_value_' + count + '_' + CurentModelCount + '" class="form-control" type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <button type="button" class="btn btn-danger remove">\n' +
                '                                Remove\n' +
                '                            </button>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                </div>';
            $(elm).closest('.liste-box').append(html);
            $(elm).attr('data-child', parseInt(count) + 1);
        }
        if (type == 'object') {
            let html = '                <div class="liste-box">\n' +
                '                    <div class="snippet-fields row">\n' +
                '                        <div class="field-input col-xs-4">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="object_name" class="control-label">Model name</label>\n' +
                '                                <input value="" id="object" name="object_name_' + modelCount + '" class="form-control" type="text"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '\n' +
                '                        <div class="field-input col-xs-4">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="table_name" class="control-label">Table name</label>\n' +
                '                                <input value="" id="table" name="table_name_' + modelCount + '" class="form-control" type="text"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '\n' +
                '                    <div class="row">\n' +
                '                        <div class="field-input col-xs-8">\n' +
                '                        </div>\n' +
                '                        <div class="field-input col-xs-2">\n' +
                '                            <div class="form-group">\n' +
                '                                <button type="button" class="btn btn-default add-new" data-child="2" data-type="field">\n' +
                '                                    New field\n' +
                '                                </button>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '\n' +
                '                    <div class="snippet-fields row">\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="field_name" class="control-label">field name</label>\n' +
                '                                <input value="" id="service-1" name="field_name_' + count + '_' + modelCount + '" class="form-control" type="text"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="field_type-1" class="control-label">Type</label>\n' +
                '                                <select class="form-control" name="field_type_' + count + '_' + modelCount + '">\n' +
                '                                    <option title="Un nombre entier de 4 octets. La fourchette des entiers relatifs est de -2 147 483 648 à 2 147 483 647. Pour les entiers positifs, c\\est de 0 à 4 294 967 295">\n' +
                '                                        INT\n' +
                '                                    </option>\n' +
                '                                    <option title="Une chaîne de longueur variable (0-65,535), la longueur effective réelle dépend de la taille maximale d\\une ligne">\n' +
                '                                        VARCHAR\n' +
                '                                    </option>\n' +
                '                                    <option title="Une colonne TEXT d\\une longueur maximale de 65 535 (2^16 - 1) caractères, stockée avec un préfixe de deux octets indiquant la longueur de la valeur en octets">\n' +
                '                                        TEXT\n' +
                '                                    </option>\n' +
                '                                    <optgroup label="Numérique">\n' +
                '                                        <option title="Un nombre entier de 1 octet. La fourchette des nombres avec signe est de -128 à 127. Pour les nombres sans signe, c\\est de 0 à 255">\n' +
                '                                            TINYINT\n' +
                '                                        </option>\n' +
                '                                        <option title="Un nombre entier de 4 octets. La fourchette des entiers relatifs est de -2 147 483 648 à 2 147 483 647. Pour les entiers positifs, c\\est de 0 à 4 294 967 295">\n' +
                '                                            INT\n' +
                '                                        </option>\n' +
                '                                        <option title="Un nombre en virgule fixe (M, D). Le nombre maximum de chiffres (M) est de 65 (10 par défaut), le nombre maximum de décimales (D) est de 30 (0 par défaut)">\n' +
                '                                            DECIMAL\n' +
                '                                        </option>\n' +
                '                                        <option title="Un synonyme de TINYINT(1), une valeur de zéro signifie faux, une valeur non-zéro signifie vrai">\n' +
                '                                            BOOLEAN\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '                                    <optgroup label="Date et l\\heure">\n' +
                '                                        <option title="Une date, la fourchette est de «1000-01-01» à «9999-12-31»">\n' +
                '                                            DATE\n' +
                '                                        </option>\n' +
                '                                        <option title="Une combinaison date et heure, la fourchette est de « 1000-01-01 00:00:00 » à « 9999-12-31 23:59:59 »">\n' +
                '                                            DATETIME\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '                                    <optgroup label="Chaîne de caractères">\n' +
                '                                        <option title="Une chaîne de longueur variable (0-65,535), la longueur effective réelle dépend de la taille maximale d\\une ligne">\n' +
                '                                            VARCHAR\n' +
                '                                        </option>\n' +
                '\n' +
                '                                        <option title="Une colonne TEXT d\\une longueur maximale de 65 535 (2^16 - 1) caractères, stockée avec un préfixe de deux octets indiquant la longueur de la valeur en octets">\n' +
                '                                            TEXT\n' +
                '                                        </option>\n' +
                '                                        >\n' +
                '                                        <option title="Une colonne TEXT d\\une longueur maximale de 4 294 967 295 ou 4 GiB (2^32 - 1) caractères, stockée avec un préfixe de quatre octets indiquant la longueur de la valeur en octets">\n' +
                '                                            LONGTEXT\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '                                    <optgroup label="Specifique">\n' +
                '                                        <option>\n' +
                '                                            EMAIL\n' +
                '                                        </option>\n' +
                '                                        <option>\n' +
                '                                            HTML\n' +
                '                                        </option>\n' +
                '                                        <option>\n' +
                '                                            PERCENT\n' +
                '                                        </option>                                        \n' +
                '                                        <option>\n' +
                '                                            UnsignedInt\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '                            </select>\n' +
                '                                </select>\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="field_length-1" class="control-label">Length</label>\n' +
                '                                <input value="" id="service-1" name="field_length_' + count + '_' + modelCount + '" class="form-control" type="text"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="is_nullable-1" class="control-label">Null</label>\n' +
                '                                <input value="1" id="service-1" name="is_nullable_' + count + '_' + modelCount + '" class="form-control" type="checkbox"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="is_auto_increment-1" class="control-label">A.I</label>\n' +
                '                                <input value="1" id="service-1" name="is_auto_increment_' + count + '_' + modelCount + '" class="form-control"\n' +
                '                                       type="checkbox"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="is_lang-1" class="control-label">Lang?</label>\n' +
                '                                <input value="1" id="service-1" name="is_lang_' + count + '_' + modelCount + '" class="form-control"\n' +
                '                                       type="checkbox"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="is_shop-1" class="control-label">Shop?</label>\n' +
                '                                <input value="1" id="service-1" name="is_shop_' + count + '_' + modelCount + '" class="form-control"\n' +
                '                                       type="checkbox"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="default_value-1" class="control-label">Default Value</label>\n' +
                '                                <input value="1" id="service-1" name="default_value_' + count + '_' + modelCount + '" class="form-control" type="text"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <button type="button" class="btn btn-danger remove">\n' +
                '                                    Remove\n' +
                '                                </button>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                </div>';
            $(elm).closest('.modal-body').append(html);
            $(elm).attr('data-child', parseInt(count) + 1);
            $(elm).closest('.modal-body').find('.liste-box').attr('data-currentCount', modelCount)
            $(elm).attr('data-model', parseInt(modelCount) + 1);
        }
        if (type == 'custom-field') {
            let html = '<div class="liste-box" data-currentCount="1">\n' +
                '                    <div class="snippet-fields row">\n' +
                '                        <div class="field-input col-xs-8">\n' +
                '                            <div class="form-group label-floating">\n' +
                '                                <select name="object_model_name_' + modelCount + '" class="form-control" required>\n' +
                '                                    <option value="">Choose Object Model</option>\n' +
                '                                    <option value="Product">Product</option>\n' +
                '                                    <option value="Category">Category</option>\n' +
                '                                    <option value="Customer">Customer</option>\n' +
                '                                </select>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '\n' +
                '                    </div>\n' +
                '\n' +
                '                    <div class="row">\n' +
                '                        <div class="field-input col-xs-8">\n' +
                '                        </div>\n' +
                '                        <div class="field-input col-xs-2">\n' +
                '                            <div class="form-group">\n' +
                '                                <button type="button" id="field-count" class="btn btn-default add-new" data-child="2" data-type="custom-field-inputs">\n' +
                '                                    New field\n' +
                '                                </button>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '\n' +
                '                    <div class="snippet-fields row">\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="column_name-1" class="control-label">field name</label>\n' +
                '                                <input value="" id="service-1" name="column_name_' + count + '_' + modelCount + '" class="form-control" type="text"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="column_type-1" class="control-label">Type</label>\n' +
                '                                <select class="form-control" name="column_type_' + count + '_' + modelCount + '">\n' +
                '                                    <option title="Un nombre entier de 4 octets. La fourchette des entiers relatifs est de -2 147 483 648 à 2 147 483 647. Pour les entiers positifs, c\\est de 0 à 4 294 967 295">\n' +
                '                                        INT\n' +
                '                                    </option>\n' +
                '                                    <option title="Une chaîne de longueur variable (0-65,535), la longueur effective réelle dépend de la taille maximale d\\une ligne">\n' +
                '                                        VARCHAR\n' +
                '                                    </option>\n' +
                '                                    <option title="Une colonne TEXT d\\une longueur maximale de 65 535 (2^16 - 1) caractères, stockée avec un préfixe de deux octets indiquant la longueur de la valeur en octets">\n' +
                '                                        TEXT\n' +
                '                                    </option>\n' +
                '                                    <optgroup label="Numérique">\n' +
                '                                        <option title="Un nombre entier de 1 octet. La fourchette des nombres avec signe est de -128 à 127. Pour les nombres sans signe, c\\est de 0 à 255">\n' +
                '                                            TINYINT\n' +
                '                                        </option>\n' +
                '                                        <option title="Un nombre entier de 4 octets. La fourchette des entiers relatifs est de -2 147 483 648 à 2 147 483 647. Pour les entiers positifs, c\\est de 0 à 4 294 967 295">\n' +
                '                                            INT\n' +
                '                                        </option>\n' +
                '                                        <option title="Un nombre en virgule fixe (M, D). Le nombre maximum de chiffres (M) est de 65 (10 par défaut), le nombre maximum de décimales (D) est de 30 (0 par défaut)">\n' +
                '                                            DECIMAL\n' +
                '                                        </option>\n' +
                '                                        <option title="Un synonyme de TINYINT(1), une valeur de zéro signifie faux, une valeur non-zéro signifie vrai">\n' +
                '                                            BOOLEAN\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '\n' +
                '                                    <optgroup label="Date et l\\heure">\n' +
                '                                        <option title="Une date, la fourchette est de «1000-01-01» à «9999-12-31»">\n' +
                '                                            DATE\n' +
                '                                        </option>\n' +
                '                                        <option title="Une combinaison date et heure, la fourchette est de « 1000-01-01 00:00:00 » à « 9999-12-31 23:59:59 »">\n' +
                '                                            DATETIME\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '                                    <optgroup label="Chaîne de caractères">\n' +
                '                                        <option title="Une chaîne de longueur variable (0-65,535), la longueur effective réelle dépend de la taille maximale d\\une ligne">\n' +
                '                                            VARCHAR\n' +
                '                                        </option>\n' +
                '\n' +
                '                                        <option title="Une colonne TEXT d\\une longueur maximale de 65 535 (2^16 - 1) caractères, stockée avec un préfixe de deux octets indiquant la longueur de la valeur en octets">\n' +
                '                                            TEXT\n' +
                '                                        </option>\n' +
                '                                        >\n' +
                '                                        <option title="Une colonne TEXT d\\une longueur maximale de 4 294 967 295 ou 4 GiB (2^32 - 1) caractères, stockée avec un préfixe de quatre octets indiquant la longueur de la valeur en octets">\n' +
                '                                            LONGTEXT\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '                                    <optgroup label="Specifique">\n' +
                '                                        <option>\n' +
                '                                            EMAIL\n' +
                '                                        </option>\n' +
                '                                        <option>\n' +
                '                                            HTML\n' +
                '                                        </option>\n' +
                '                                        <option>\n' +
                '                                            PERCENT\n' +
                '                                        </option>\n' +
                '                                        <option>\n' +
                '                                            UnsignedInt\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '                                </select>\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="column_length-1" class="control-label">Length</label>\n' +
                '                                <input id="service-1" name="column_length_' + count + '_' + modelCount + '" class="form-control" type="text"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="is_nullable-1" class="control-label">Null</label>\n' +
                '                                <input value="1" id="service-1" name="is_column_nullable_' + count + '_' + modelCount + '" class="form-control" type="checkbox"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="is_auto_increment-1" class="control-label">Lang?</label>\n' +
                '                                <input value="1" id="service-1" name="is_column_lang_' + count + '_' + modelCount + '" class="form-control"\n' +
                '                                       type="checkbox"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="column-1" class="control-label">Shop?</label>\n' +
                '                                <input value="1" id="service-1" name="is_column_shop_' + count + '_' + modelCount + '" class="form-control"\n' +
                '                                       type="checkbox"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="add_to_listing-1" class="control-label">Add to listing?</label>\n' +
                '                                <input value="1" id="service-1" name="add_to_listing_' + count + '_' + modelCount + '" class="form-control"\n' +
                '                                       type="checkbox"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <label for="default_value-1" class="control-label">Default Value</label>\n' +
                '                                <input value="" id="service-1" name="default_column_value_' + count + '_' + modelCount + '" class="form-control" type="text"\n' +
                '                                       placeholder="">\n' +
                '                                <div class="help-block"></div>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                        <div class="field-input">\n' +
                '                            <div class="form-group">\n' +
                '                                <button type="button" class="btn btn-danger remove">\n' +
                '                                    Remove\n' +
                '                                </button>\n' +
                '                            </div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                </div>';
            $(elm).closest('.modal-body').append(html);
            // $(elm).attr('data-child', parseInt(count) + 1);
            $(elm).closest('.modal-body').find('.liste-box').attr('data-currentCount', modelCount)
            $(elm).attr('data-model', parseInt(modelCount) + 1);
        }
        if (type == 'custom-field-inputs') {
            let html = '           <div class="snippet-fields row">\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="column_name_' + count + '_' + CurentModelCount + '" class="control-label">field name</label>\n' +
                '                            <input value="" id="service_' + count + '_' + CurentModelCount + '" name="column_name_' + count + '_' + CurentModelCount + '" class="form-control" type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="column_type_' + count + '_' + CurentModelCount + '" class="control-label">Type</label>\n' +
                '                            <select class="form-control" name="column_type_' + count + '_' + CurentModelCount + '">\n' +
                '                                <option title="Un nombre entier de 4 octets. La fourchette des entiers relatifs est de -2 147 483 648 à 2 147 483 647. Pour les entiers positifs, c\\est de 0 à 4 294 967 295">\n' +
                '                                    INT\n' +
                '                                </option>\n' +
                '                                <option title="Une chaîne de longueur variable (0-65,535), la longueur effective réelle dépend de la taille maximale d\\une ligne">\n' +
                '                                    VARCHAR\n' +
                '                                </option>\n' +
                '                                <option title="Une colonne TEXT d\\une longueur maximale de 65 535 (2^16 - 1) caractères, stockée avec un préfixe de deux octets indiquant la longueur de la valeur en octets">\n' +
                '                                    TEXT\n' +
                '                                </option>\n' +
                '                                <optgroup label="Numérique">\n' +
                '                                    <option title="Un nombre entier de 1 octet. La fourchette des nombres avec signe est de _' + count + '28 à 127. Pour les nombres sans signe, c\\est de 0 à 255">\n' +
                '                                        TINYINT\n' +
                '                                    </option>\n' +
                '                                    <option title="Un nombre entier de 4 octets. La fourchette des entiers relatifs est de -2 147 483 648 à 2 147 483 647. Pour les entiers positifs, c\\est de 0 à 4 294 967 295">\n' +
                '                                        INT\n' +
                '                                    </option>\n' +
                '                                    <option title="Un nombre en virgule fixe (M, D). Le nombre maximum de chiffres (M) est de 65 (10 par défaut), le nombre maximum de décimales (D) est de 30 (0 par défaut)">\n' +
                '                                        DECIMAL\n' +
                '                                    </option>\n' +
                '                                    <option title="Un synonyme de TINYINT(1), une valeur de zéro signifie faux, une valeur non-zéro signifie vrai">\n' +
                '                                        BOOLEAN\n' +
                '                                    </option>\n' +
                '                                </optgroup>\n' +
                '                                <optgroup label="Date et l\\heure">\n' +
                '                                    <option title="Une date, la fourchette est de «1000-01-01» à «9999_' + count + '2-31»">DATE</option>\n' +
                '                                    <option title="Une combinaison date et heure, la fourchette est de « 1000-01-01 00:00:00 » à « 9999_' + count + '2-31 23:59:59 »">\n' +
                '                                        DATETIME\n' +
                '                                    </option>\n' +
                '                                </optgroup>\n' +
                '                                <optgroup label="Chaîne de caractères">\n' +
                '                                    <option title="Une chaîne de longueur variable (0-65,535), la longueur effective réelle dépend de la taille maximale d\\une ligne">\n' +
                '                                        VARCHAR\n' +
                '                                    </option>\n' +
                '\n' +
                '                                    <option title="Une colonne TEXT d\\une longueur maximale de 65 535 (2^16 - 1) caractères, stockée avec un préfixe de deux octets indiquant la longueur de la valeur en octets">\n' +
                '                                        TEXT\n' +
                '                                    </option>>\n' +
                '                                    <option title="Une colonne TEXT d\\une longueur maximale de 4 294 967 295 ou 4 GiB (2^32 - 1) caractères, stockée avec un préfixe de quatre octets indiquant la longueur de la valeur en octets">\n' +
                '                                        LONGTEXT\n' +
                '                                    </option>\n' +
                '                                </optgroup>\n' +
                '                                    <optgroup label="Specifique">\n' +
                '                                        <option>\n' +
                '                                            EMAIL\n' +
                '                                        </option>\n' +
                '                                        <option>\n' +
                '                                            HTML\n' +
                '                                        </option>\n' +
                '                                        <option>\n' +
                '                                            PERCENT\n' +
                '                                        </option>                                        \n' +
                '                                        <option>\n' +
                '                                            UnsignedInt\n' +
                '                                        </option>\n' +
                '                                    </optgroup>\n' +
                '                            </select>\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="column_length_' + count + '_' + CurentModelCount + '" class="control-label">Length</label>\n' +
                '                            <input id="service_' + count + '_' + CurentModelCount + '" name="column_length_' + count + '_' + CurentModelCount + '" class="form-control" type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="is_nullable_' + count + '_' + CurentModelCount + '" class="control-label">Null</label>\n' +
                '                            <input value="1" id="service_' + count + '_' + CurentModelCount + '" name="is_column_nullable_' + count + '_' + CurentModelCount + '" class="form-control" type="checkbox"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="is_lang_' + count + '_' + CurentModelCount + '" class="control-label">Lang?</label>\n' +
                '                            <input value="1" id="service_' + count + '_' + CurentModelCount + '" name="is_column_lang_' + count + '_' + CurentModelCount + '" class="form-control" type="checkbox"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="is_shop_' + count + '_' + CurentModelCount + '" class="control-label">Shop?</label>\n' +
                '                            <input value="1" id="service_' + count + '_' + CurentModelCount + '" name="is_column_shop_' + count + '_' + CurentModelCount + '" class="form-control" type="checkbox"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="add_to_listing_' + count + '_' + CurentModelCount + '" class="control-label">Add to listing?</label>\n' +
                '                            <input value="1" id="service_' + count + '_' + CurentModelCount + '" name="add_to_listing_' + count + '_' + CurentModelCount + '" class="form-control" type="checkbox"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="default_value_' + count + '_' + CurentModelCount + '" class="control-label">Default Value</label>\n' +
                '                            <input value="" id="service_' + count + '_' + CurentModelCount + '" name="default_column_value_' + count + '_' + CurentModelCount + '" class="form-control" type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <button type="button" class="btn btn-danger remove">\n' +
                '                                Remove\n' +
                '                            </button>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                </div>';
            $(elm).closest('.liste-box').append(html);
            $(elm).attr('data-child', parseInt(count) + 1);
        }
        if (type == 'bo-settings') {
            let html = '                    <div class="snippet-fields row">\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="setting_type-' + count + '" class="control-label">Setting type</label>\n' +
                '\n' +
                '                            <select name="setting_type_' + count + '" class="form-control" required>\n' +
                '                                <option value="" disabled selected>Choose Setting type</option>\n' +
                '                                <option value="switch">Switch</option>\n' +
                '                                <option value="text">Text</option>\n' +
                '                                <option value="select">Select</option>\n' +
                '                                <option value="textarea">Textarea</option>\n' +
                '                                <option value="category-tree">Category tree</option>\n' +
                '                                <option value="carrier-select">Carrier select</option>\n' +
                '                                <option value="product-select">Product select</option>\n' +
                '                            </select>\n' +
                '\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="setting_name-' + count + '" class="control-label">Setting name</label>\n' +
                '                            <input value="" id="setting_name-' + count + '" name="setting_name_' + count + '" class="form-control" type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="setting_label-' + count + '" class="control-label">Setting label</label>\n' +
                '                            <input value="" id="setting_label-' + count + '" name="setting_label_' + count + '" class="form-control" type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <label for="setting_description-' + count + '" class="control-label">Setting Description</label>\n' +
                '                            <input value="" id="setting_description-' + count + '" name="setting_description_' + count + '" class="form-control"\n' +
                '                                   type="text"\n' +
                '                                   placeholder="">\n' +
                '                            <div class="help-block"></div>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                    <div class="field-input">\n' +
                '                        <div class="form-group">\n' +
                '                            <button type="button" class="btn btn-danger remove">\n' +
                '                                Remove\n' +
                '                            </button>\n' +
                '                        </div>\n' +
                '                        </div>\n' +
                '                    </div>';
            $(elm).closest('.modal-body').append(html);
            $(elm).attr('data-child', parseInt(count) + 1);
        }
    }
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

