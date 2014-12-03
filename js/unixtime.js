$(function() {
    console.log($( ".unixtime" ).length);
    if ( $( ".unixtime" ).length ) {
        $(".unixtime").each(function () {
            var time_val =$(this).text();
            if (time_val) {
                var time = Number($(this).text() + '000');
                var d = new Date(time);
                //console.log(time);
                //console.log('d='+d);
                $(this).text(d);
            }
        });
    }
});