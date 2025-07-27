jQuery(document).ready(function($){
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position){
            $('#svc_lat').val(position.coords.latitude);
            $('#svc_lng').val(position.coords.longitude);
        });
    }

    $('#svc-visit-form').on('submit', function(e){
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'svc_submit_visit');
        $.ajax({
            url: svc_form.ajax_url,
            method: 'POST',
            processData: false,
            contentType: false,
            data: formData,
            success: function(response){
                alert('Visit saved');
                $('#svc-visit-form')[0].reset();
            },
            error: function(){
                alert('Error saving visit');
            }
        });
    });
});
