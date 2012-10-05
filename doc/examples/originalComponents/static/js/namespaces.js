$(document).ready(function(){
  $(".edit-button").on("click", function(target){
    $("#prefix").val($(this).attr("data-prefix"));
    $("#namespace").val($(this).attr("data-ns"));
$('html, body').stop().animate({
            scrollTop: $('body').offset().top
        }, 1000);
  })
});
