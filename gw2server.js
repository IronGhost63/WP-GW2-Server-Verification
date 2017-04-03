jQuery(document).ready(function(){
	jQuery("#gw2-verify").click(function(e){
		e.preventDefault();
		jQuery(".gw2-verification .gw2-api-key").attr('disabled', true);
		jQuery(".gw2-verification .gw2-verify").attr('disabled', true);
		jQuery(".gw2-verification .message").text('Verifying ... ');

		var apikey = jQuery("#gw2-api-key").val();

		jQuery.getJSON( gw2.ajax + '&apikey=' + apikey, function(data){
			console.log(data);
			jQuery(".gw2-info .current-server").text(data.data.server);
			jQuery(".gw2-verification .message").text(data.data.message);

			jQuery(".gw2-verification .gw2-api-key").attr('disabled', false);
			jQuery(".gw2-verification .gw2-verify").attr('disabled', false);
		});
	});
});

