//Get URL Parameters
let getUrlParameter = function getUrlParameter(sParam) {
    let sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

function check_form() {
    let validator = jQuery('#contact-form').validate();
    let status = validator.form()
    if( status ) {
        submit_form()
    }

}

function submit_form() {
    jQuery('#submit-button').attr('disabled', 'disabled')
    jQuery('#submit-button-container').append(window.SETTINGS.spinner)

    let url = get_url()
    let $inputs = jQuery(':input');
    let data = {};
    $inputs.each(function() {
        data[this.name] = jQuery(this).val();
    });

    return jQuery.ajax({
        type: "POST",
        data: JSON.stringify(data),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        url: url + '/wp-json/dt-public/v1/webform/form_submit',
    })
        .done(function (data) {
            jQuery('#report').append( window.TRANSLATION.success + '<br>');
            jQuery('.input-text').val('').empty();
            jQuery('#submit-button').removeAttr('disabled')
            jQuery('.spinner').remove()
        })
        .fail(function (err) {
            jQuery('#report').html('Failed')
        });
}

function get_url() {
    return window.location.protocol + '//' + window.location.hostname
}

jQuery(document).ready(function () {

    let validator = jQuery('#contact-form').validate({
        errorPlacement: function(error, element) {
            error.appendTo( element.parent("p") );
        },
        rules: {
            name: {
                required: true,
                minlength: 2,
            },
            phone: {
                required: true,
                minlength: 10
            },
            l: {
                required: false,
                email: true

            }
        },
        messages: {
            name: {
                required: window.TRANSLATION.required,
                minlength: jQuery.validator.format(window.TRANSLATION.characters_required)
            },
            phone: {
                required: window.TRANSLATION.required,
                minlength: jQuery.validator.format(window.TRANSLATION.characters_required)
            }
        },
        submitHandler: function(form) {
            submit_form()
        }

    });
    validator.form()

    // This is a form delay to discourage robots
    let counter = 7;
    let myInterval = setInterval(function () {
        let button = jQuery('#submit-button')

        button.html( window.TRANSLATION.submit_in + ' ' + counter)
        --counter;

        if ( counter === 0 ) {
            clearInterval(myInterval);
            button.html( window.TRANSLATION.submit ).prop('disabled', false)
        }

    }, 1000);


})