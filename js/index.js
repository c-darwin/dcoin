
function fc_navigate (page, parameters) {

    $('#page-wrapper').spin();
    $.post("content.php?page="+page, { tpl_name: page, parameters: parameters },
        function(data) {
            $("#page-wrapper").spin(false);
            //console.log('$("#page-wrapper").spin(false)');
            $('#dc_content').html( data );
            window.scrollTo(0,0);
        }, "html");

}

function map_navigate (page) {

        $('#page-wrapper').spin();
        $( "#dc_content" ).load( "content.php", { tpl_name: page }, function() {
            $.getScript("https://maps.googleapis.com/maps/api/js?sensor=false&callback=initialize", function(){
                $('#page-wrapper').spin(false);
            });
        });
}

function load_menu() {
    $( "#dc_menu" ).load( "ajax/menu.php", { }, function() {
       // $( "#dc_content" ).load( "content.php", { }, function() {
            $.getScript("js/plugins/metisMenu/metisMenu.min.js", function(){
                $.getScript("js/sb-admin.js");
            });
        //});
    });
}

function login_ok (result) {

    if (result=='1') {

        $('#myModal').modal('hide');
        $('#myModalLogin').modal('hide');
        $('.modal-backdrop').remove();
        $('.modal-backdrop').css('display', 'none');

        var tpl_name = $('#tpl_name').val();
        if (!tpl_name || tpl_name=='install_step_0' || tpl_name=='install_step_6')
            tpl_name = 'home';

        $( "#dc_menu" ).load( "ajax/menu.php", { }, function() {
           $( "#dc_content" ).load( "content.php", { tpl_name: tpl_name}, function() {
                $.getScript("js/plugins/metisMenu/metisMenu.min.js", function() {
                    $.getScript("js/sb-admin.js");
                    $("#main-login").html('');
                    $("#page-wrapper").spin(false);
                });
            });
        });

    }
    else if (result=='not_available') {
        $("#modal_alert").html('<div id="alertModalPull" class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><p>'+$('#pool_is_full').val()+'</p></div>');
        $("#page-wrapper").spin(false);
    }
    else {
        $("#modal_alert").html('<div id="alertModalPull" class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><p>'+$('#incorrect_key_or_password').val()+'</p></div>');
        $("#page-wrapper").spin(false);
    }
}


function save_key () {
    var key = $("#modal_key").val();
    var password = $("#modal_password").val();

    $('#key').text( key );
    $('#password').text( password );
}


function logout () {

    $.get("ajax/logout.php",
        function() {
            fc_navigate ('login', '');
        });
}

var keyStr = "ABCDEFGHIJKLMNOP" +
    "QRSTUVWXYZabcdef" +
    "ghijklmnopqrstuv" +
    "wxyz0123456789+/" +
    "=";

function decode64(input) {
    var output = "";
    var chr1, chr2, chr3 = "";
    var enc1, enc2, enc3, enc4 = "";
    var i = 0;

    // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
    var base64test = /[^A-Za-z0-9\+\/\=]/g;
    if (base64test.exec(input)) {
        alert("There were invalid base64 characters in the input text.\n" +
            "Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\n" +
            "Expect errors in decoding.");
    }
    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

    do {
        enc1 = keyStr.indexOf(input.charAt(i++));
        enc2 = keyStr.indexOf(input.charAt(i++));
        enc3 = keyStr.indexOf(input.charAt(i++));
        enc4 = keyStr.indexOf(input.charAt(i++));

        chr1 = (enc1 << 2) | (enc2 >> 4);
        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
        chr3 = ((enc3 & 3) << 6) | enc4;

        output = output + String.fromCharCode(chr1);

        if (enc3 != 64) {
            output = output + String.fromCharCode(chr2);
        }
        if (enc4 != 64) {
            output = output + String.fromCharCode(chr3);
        }

        chr1 = chr2 = chr3 = "";
        enc1 = enc2 = enc3 = enc4 = "";

    } while (i < input.length);

    return unescape(output);
}



function map_init (lat, lng, map_canvas, drag) {

    $("#"+map_canvas).css("display", "block");

    var point = new google.maps.LatLng(lat, lng);
    var mapOptions = {
        center: point,
        zoom: 15,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        streetViewControl: false
    };
    map = new google.maps.Map(document.getElementById(map_canvas), mapOptions);

    var marker = new google.maps.Marker({
        position: point,
        map: map,
        draggable: drag,
        title: 'You'
    });

    google.maps.event.trigger(map, 'resize');

    google.maps.event.addListener(marker, "dragend", function() {

        var lat = marker.getPosition().lat();
        lat = lat.toFixed(5);
        var lng = marker.getPosition().lng();
        lng = lng.toFixed(5);
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;

    });
    marker.setMap(map);
}

function check_key_and_show_modal() {
    if ( $('#key').text().length < 256 ) {
        $('#myModal').modal({ backdrop: 'static' });
    }
}
