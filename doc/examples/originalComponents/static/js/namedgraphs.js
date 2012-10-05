$(document).ready(function(){
  $.ajax({
          url: "../../namedGraphs",
          dataType: "json",
          headers: {Accept: "application/json"},
          success: function(data){
                     $.each(data.graphs, function(i, item){
                                           $("#ng").append("<div><strong>"+item+"</strong></div>");
                     });
                   }
          });
});
