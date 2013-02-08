$(document).ready(function() {
$('ul.answers input[type=radio]').click(function vote() {
    var url = voteUrl;
    
    var rowId = $(this).attr("data-rowId");
    if(typeof rowId === "undefined")  return;
    var alternativeId = $(this).attr("data-alternativeId");
    var mid = $(this).attr("data-m");
    var data = {id:rowId, alternativeId:alternativeId};
    if(mid) {
    	data.m = mid;
    }
    
    var selected = $(this);
    $.ajax({
    	     type: "GET",
    	     url: url,
    	     data: data,
    	     dataType: 'json',
    	     success: function(result)
    	     { 
    	    	if(result.success == 'yes'){ 
    	    		selected.parent('li').effect("highlight", {}, 3000);
    	    	}
    	     },
    	     error: function()
    	     {
    	    	 
    	     }
    	   });
});
});