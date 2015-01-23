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
        if (!validate()) return;
        $('.b-form').css({ opacity: 0 })
        $('.b-progress').css({ opacity: 1 }).show()
        start($(this).data("provider"))
    })
    var progress_direction = false;
    var progress_interval = setInterval(function(){
        $('.b-progress h1').css({ opacity: progress_direction?1:0.25 });
        progress_direction = !progress_direction;
    }, 500);


    var userData = {};

    function start(provider) {
        // store range
        var value = $('#id-year').val();
        if ($('#id-range').data('value') == 'month')
            value = $('#id-month').val() + '/' + $('#id-month-year').val();
        if ($('#id-range').data('value') == 'recent')
            value = $('#id-recent').val();
        // go for user authentification hash and provider auth
        window.open('/auth?'+$.param({
            provider: provider,
            email: $('#id-email').val(),
            range: $('#id-range').data('value')+':'+value
        }));
        // TODO open a nice popup
    }

    function finish() {
        $('.b-form').css({ opacity: 0 })
        $('.b-progress').css({ opacity: 1 }).show()
        $('.b-progress h1').html('Your photos are ready!')
        $('.b-progress-hint').html('Thank you for this incredible experience.')
        clearInterval(progress_interval);
        $('.b-progress h1').css({ opacity: 1 });
    }

    function progress() {
        $.ajax({
            dataType: 'json',
            url: '/progress',
            success: function(data) {
                $('.b-progress-init').hide();
                $('.b-progress-contents').css({ display: 'block', opacity: 1 })
                var contents = []
                for (var i=0; i<data.photos.length; i++) {
                    var src='/img/progress.png';
                    if (data.photos[i].state == 'done')
                        src = data.photos[i].thumb;
                    contents.push('<img src="'+src+'">');
                }
                $('.js-progress-contents').html(contents.join(""))
                // todo update pictures based on data
                if (data.done) 
                    finish();
                else 
                    setTimeout(progress, 500);
            }
        })
    }
   
    window.onAuth = function(is_success) {
        if (is_success) progress()
        else {
            // we need to reset
            $('.b-form').css({ opacity: 1 })
            $('.b-progress').css({ opacity: 0 }).hide()
        }
       
    }

})