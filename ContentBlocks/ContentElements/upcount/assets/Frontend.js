$(document).ready(function(){
    if($('.jve_upcount-wrap').length) {
        // https://codepen.io/shivasurya/pen/yyBoJX
        var started = 0;
        easeScrollTo(40, 300);
        easeScrollTo(0, 250);
        $(window).scroll(function() {
            var oTop = $('.jve_upcount-wrap-last').offset().top - ( window.innerHeight) ;
            if (started == 0 && $(window).scrollTop() > oTop ) {
                $(".jve_upcount-wrap .jve_upcount").each(function(index, element) {
                    let counterElement = $(element);
                    let startValue = counterElement.data("value");
                    let targetValue = counterElement.data("target");


                    let increment = (targetValue - startValue) / (3000 / 100); //value to increment every 100ms
                    let currentVal = startValue;
                    let incrementOpac = 0.03;
                    let currentOpac = 0.10;
                    let incrementSize = 15;
                    let currentSize = 20;

                    let intervalId = setInterval(function() {
                        currentVal += increment;
                        currentOpac += incrementOpac;
                        currentSize += incrementSize;
                        if (  increment > 0 )  {
                            currentVal = Math.min(currentVal, targetValue); // To make sure we don't go over the target
                            if(  currentVal >= targetValue )  {
                                clearInterval(intervalId); // Stop the interval when reaching the target
                            }
                        } else {
                            currentVal = Math.max(currentVal, targetValue); // To make sure we don't go over the target
                            if(  currentVal <= targetValue )  {
                                clearInterval(intervalId); // Stop the interval when reaching the target
                            }
                        }
                        counterElement.css( "opacity" , currentOpac);
                        counterElement.css( "font-size" , currentSize + "%");
                        counterElement.html(Math.round(currentVal));


                    }, 100);
                });
                started = 1;
            }
        });
    }
    function easeScrollTo(position, duration) {
        $('html, body').animate({
            scrollTop: position
        }, duration);
    }
});


