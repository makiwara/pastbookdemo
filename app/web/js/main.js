function strip(s) { return s.replace(/^\s+/,'').replace(/\s+$/,'') }
function is_email(s) { return strip(s).match(/^.*@.*\..*$/) }
function validate() {
    if (!is_email($('#id-email').val())) 
        $('#id-email').focus().addClass('m-error').select();
    else {
        $('#id-email').removeClass('m-error');
        return true;
    }
}
$(function(){
    $('#id-email').focus().blur(validate);
    $('.b-form-range-selected').click(function(){
        $('.b-form-range-values').toggleClass('display');
        return false;
    })
    $('.b-form-range-values .b-form-range-one').click(function(){
        $('.b-form-range-selected').html($(this).html())
        $('.b-form-range-selected').data('value', $(this).data('value'))
        $('.b-form-value-one').removeClass('display');
        $('.b-form-value-'+$(this).data('value')).addClass('display')
        $('.b-form-value-'+$(this).data('value')+' #id-'+$(this).data('value')).focus().select();
    })
    $('body').click(function(){ 
        $('.b-form-range-values').removeClass('display');
    })
    $('.js-start').click(function(){
        // if (!validate()) return;
        $('.b-form').css({ opacity: 0 })
        $('.b-progress').css({ opacity: 1 }).show()
        setTimeout(ajax_start, 2000);
        setTimeout(ajax_finish, 4000);
        //ajax_start();
    })
    var progress_direction = false;
    var progress_interval = setInterval(function(){
        $('.b-progress h1').css({ opacity: progress_direction?1:0.25 });
        progress_direction = !progress_direction;
    }, 500);

    function process( data ) {
        // todo rebuild photos
        // go for next ajax afterwards, or for ajax_finish
    }
    function ajax_start() {
        var value = $('#id-year').data('value');
        if ($('#id-range').data('value') == 'month')
            value = $('#id-month').data('value') + '/' + $('#id-month-year').data('value');
        if ($('#id-range').data('value') == 'recent')
            value = $('#id-recent').data('value');
        $.ajax({
            url: '/process',
            data: {
                email: $('#id-email').val(),
                range: $('#id-range').data('value'),
                value: value
            },
            success: function( data ){
                $('.b-progress-init').hide();
                $('.b-progress-contents').css({ display: 'block', opacity: 1 })
                process(data);
            }
        })
    }
    function ajax_finish() {
        $('.b-form').css({ opacity: 0 })
        $('.b-progress').css({ opacity: 1 }).show()
        $('.b-progress h1').html('Your photos are ready!')
        $('.b-progress-hint').html('Thank you for this incredible experience.')
        clearInterval(progress_interval);
    }

})