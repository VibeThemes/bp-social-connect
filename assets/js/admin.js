jQuery(document).ready(function($){

	$('.add_new_map').click(function(){
		var cloned = $(this).next().find('.hide').clone();
		$(cloned).removeClass('hide');
		$(cloned).find('select').each(function(){
			$(this).attr('name',$(this).attr('rel-name'));
		});
		$(this).next().append(cloned);
	});

	
});

jQuery(document).on('click','.remove_field_map',function(){
	jQuery(this).parent().remove();
});