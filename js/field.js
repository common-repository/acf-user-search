jQuery(document).ready(function($) {
	$(document).live('acf/setup_fields', function(e, div){
		if( div.tagName == "TR" ){
			var multiple = $(div).find('select.select2search').hasClass('multiple');
			$(div).find('select.select2search').select2({
				placeholder: langs.placeholder,
				templateSelection: template,
				width: 'element',
				multiple: multiple
			});
		}else{
			$("select.select2search").each(function(index, el) {
				if( ! $(el).parents("td.sub_field").length ){
					var multiple = $(el).hasClass('multiple');
					$(el).select2({
						placeholder: langs.placeholder,
						templateSelection: template,
						width: 'element',
						multiple: multiple
					});
				}
			});
		}
	});

	function template(data, container) {
		return data.text.replace(/\s\(.*?\)/,"");
	}
});