(function ($) {
	$.fn.gentcpdf = function (spec) {
		/** set default option */
		var options = $.extend(spec);
		var baseUrl = options.src.substring(0, options.src.lastIndexOf('/'));
		$(this).click(function(){
			var container =	"<form id='report_form' method='post' action='"+options.src+"' target='iframe'>"+
						"<input type='hidden' id='dom' name='dom' value='Could not get content...'/>"+
						"<input type='hidden' id='title' name='title' value='report' />"+
						"<input type='hidden' id='orentation' name='orentation' value='"+options.orentation+"' />"+
						"<input type='hidden' id='size' name='size' value='"+options.size+"' />"+
						"</form>";
			var reportdom =$(options.content).clone(true);
			$.when($('body').append(container)).done(function(){
				$.when(
					reportdom.find('a').each(function(){
						$(this).remove();
					}),
					reportdom.find('td, th').each(function(){
						var $this = $(this);
						if($this.hasClass('no-print')){
							$this.remove();
						}
					}),
					reportdom.find('div').each(function(){
						var $this = $(this);
						if($this.hasClass('space')){
							if($this.html().replace(/\s|&nbsp;/g, '').length == 0)
								$this.remove();
						}
						$this.has('div').contents().unwrap();
					}),
					reportdom.find('span').each(function(){
						$(this).removeClass().removeAttr('style');
					}),
					reportdom.contents().filter(function() { return (this.nodeType == 3 && !/\S/.test(this.nodeValue)); }).remove(),
					$('form#report_form #dom').val($.trim(reportdom.html())),
					$('form#report_form #title').val($(document).find("title").text())
				).done(function(){
					//console.log($.trim(reportdom.html()));
					$('form#report_form').submit(), $('form#report_form').submit(function(event){
						event.preventDefault();
					});	
				});
			});
		});
	}
	$.fn.gentcpdfsales = function (spec) {
		/** set default option */
		var options = $.extend(spec);
		var baseUrl = options.src.substring(0, options.src.lastIndexOf('/'));
		$(this).click(function(){
			var container =	"<form id='report_form' method='post' action='"+options.src+"' target='iframe'>"+
						"<input type='hidden' id='dom' name='dom' value='Could not get content...'/>"+
						"<input type='hidden' id='title' name='title' value='report' />"+
						"<input type='hidden' id='orentation' name='orentation' value='"+options.orentation+"' />"+
						"<input type='hidden' id='size' name='size' value='"+options.size+"' />"+
						"</form>";
			var reportdom =$(options.content).clone(true);
			$.when($('body').append(container)).done(function(){
				$.when(
					reportdom.find('a').each(function(){
						$(this).remove();
					}),
					reportdom.find('.no-print').each(function() {
						$(this).remove();
					}),
					reportdom.find('table').addClass('table'),
					reportdom.find('div#convert-tb0').wrap('<table width="100%" id="nivoice-tab0"><tr></tr></table>'),
					reportdom.find('div#convert-tb1').wrap('<td style="text-align:left"></td>'),
					reportdom.find('div#convert-tb2').wrap('<td style="text-align:center"></td>'),
					reportdom.find('div#convert-tb3').wrap('<td style="text-align:right"></td>'),
					reportdom.find('div').each(function(){
						var $this = $(this);
						if(!$this.hasClass('spaced')){
							if($this.html().replace(/\s|&nbsp;/g, '').length == 0)
								$this.remove();
						}
						$this.has('div').contents().unwrap();
					}),
					reportdom.find('span').each(function(){
						$(this).removeClass().removeAttr('style');
					}),
					reportdom.contents().filter(function() { return (this.nodeType == 3 && !/\S/.test(this.nodeValue)); }).remove(),
					$('form#report_form #dom').val($.trim(reportdom.html())),
					$('form#report_form #title').val($(document).find("title").text())
				).done(function(){
					//console.log($.trim(reportdom.html()));
					$('form#report_form').submit(), $('form#report_form').submit(function(event){
						event.preventDefault();
					});	
				});
			});
		});
	}
	$.fn.gentcpdftds = function (spec) {
		/** set default option */
		var options = $.extend(spec);
		var baseUrl = options.src.substring(0, options.src.lastIndexOf('/'));
		$(this).click(function(){
			var container =	"<form id='report_form' method='post' action='"+options.src+"' target='iframe'>"+
						"<input type='hidden' id='dom' name='dom' value='Could not get content...'/>"+
						"<input type='hidden' id='title' name='title' value='report' />"+
						"<input type='hidden' id='orentation' name='orentation' value='"+options.orentation+"' />"+
						"<input type='hidden' id='size' name='size' value='"+options.size+"' />"+
						"</form>";
			var reportdom =$(options.content).clone(true);
			$.when($('body').append(container)).done(function(){
				$.when(
					reportdom.find('a').each(function(){
						$(this).remove();
					}),
					reportdom.find('.no-print').each(function() {
						$(this).remove();
					}),
					reportdom.find('table').addClass('table'),
					reportdom.find('div#convert-tb0').wrap('<table width="auto" id="nivoice-tab0"><tr></tr></table>'),
					reportdom.find('div#convert-tb1').wrap('<td style="text-align:left"></td>'),
					reportdom.find('div#convert-tb2').wrap('<td style="text-align:center"></td>'),
					reportdom.find('div#convert-tb3').wrap('<td style="text-align:right"></td>'),
					reportdom.find('div').each(function(){
						var $this = $(this);
						if(!$this.hasClass('spaced')){
							if($this.html().replace(/\s|&nbsp;/g, '').length == 0)
								$this.remove();
						}
						$this.has('div').contents().unwrap();
					}),
					reportdom.find('span').each(function(){
						$(this).removeClass().removeAttr('style');
					}),
					reportdom.contents().filter(function() { return (this.nodeType == 3 && !/\S/.test(this.nodeValue)); }).remove(),
					$('form#report_form #dom').val($.trim(reportdom.html())),
					$('form#report_form #title').val($(document).find("title").text())
				).done(function(){
					// Adjust column widths based on content length
					reportdom.find('table').each(function(){
						var $table = $(this);

						// Loop through each column
						$table.find('tr').each(function(){
							$(this).find('td, th').each(function(index){
								var maxLength = 0;

								// Find the maximum length of content in this column
								$table.find('tr').each(function(){
									var cellContent = $(this).find('td').eq(index).text().trim();
									if(cellContent.length > maxLength){
										maxLength = cellContent.length;
									}
								});

								// Set the column width based on max content length (approximated to character length)
								$(this).css('width', (maxLength * 100) + 'px'); // 10px per character as an approximation
							});
						});
					});
					//console.log($.trim(reportdom.html()));
					$('form#report_form').submit(), $('form#report_form').submit(function(event){
						event.preventDefault();
					});	
				});
			});
		});
	}
}(jQuery));