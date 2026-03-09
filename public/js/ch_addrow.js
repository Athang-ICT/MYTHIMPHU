//#### all the js functions to add new row to form (tabular forms)####
(function ($){
         //**********### VEHICLE MODULE ###**********/
	/*
	 * Vehicle/VehicleController
	 * addrepair-->Add repair and maintanese
	 * editrepair-->Edit repair and maintanese
	 */
	$.fn.repair = function (spec){
		var options = $.extend({
			getUom: '',
		},spec);
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			$(this).find('select.tr-service_item').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$.post(
						options.getUom,
						{
							item_id: $(this).val(),
						},
						function(data){
							//console.log(data);
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id).trigger('chosen:updated');
						},
						'json'
					);
				});
			});
			rm_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			rm_addrow(parentObj,options.getUom);			     
		});	
		rm_calculation(parentObj);			
	};	
	function rm_addrow(obj, getUom){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		rm_reset(clone);	        
		$('form select').chosen();//{ allow_single_deselect: true }
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-service_item').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$.post(
					getUom,
					{
						item_id: $(this).val(),
					},
					function(data){
						console.log(data);
						$('#uom-'+id).html(data.uom);
						$('#uom-'+id+' option:selected').removeAttr('selected');
						$('#uom-'+id).trigger('chosen:updated');
					},
					'json'
				);
			});
		});
		rm_delbtn(obj);		
		rm_calculation(obj);
	};	
	function rm_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){rm_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){rm_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function rm_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){rm_reset($(this).closest("tr"));});
			});
		}
		rm_calculation(tbody.parent());
	}	
	function rm_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#rate-'+id).on('change',function(){rm_calamt($(this).closest("tr"));});
			$('#quantity-'+id).on('change',function(){rm_calamt($(this).closest("tr"));});			
		});
		var netamt = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#rm_amount').val(($.isNumeric(value))? value:'0.00');
	};
	function rm_calamt(obj){
		var id = obj.closest("tr").attr('id');
		var rate = parseFloat($('#rate-'+id).val());
		var quantity = parseFloat($('#quantity-'+id).val());
		var amt = rate*quantity;
		$('#amount-'+id).val(parseFloat(amt).toFixed(2));
				var netamt = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#rm_amount').val(($.isNumeric(value))? value:'0.00');
	}		
	function rm_reset(obj){
		var id = obj.attr('id');
		$('#quantity-'+id).val('0.00');
		$('#rate-'+id).val('0.00');
		$('#amount-'+id).val('0.00');
		$("#quantity-"+id).attr("quantity", '0.000');
		$("#rate-"+id).attr("rate", '0.000');
		$("#uom-"+id).attr("input_uom", '0.000');
		$('#item-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#uom-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		rm_calculation(obj.parent().parent());
	}
	
	/*
	 * pol/PolController
	 * addcashmemo-->Add Cash Memo
	 * editcashmemo-->Edit Cash Memo
	 */
	$.fn.cashmemo = function (spec){
		var options = $.extend({
			getfuelliter: '',
		},spec);
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			cm_calculation(parentObj);
		});		
	};		
	function cm_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#fuel_rate-'+id).on('change',function(){cm_calamt($(this).closest("tr"));});			
			$('#engine_oil_amt-'+id).on('change',function(){cm_calamt($(this).closest("tr"));});			
			$('#brake_oil_amt-'+id).on('change',function(){cm_calamt($(this).closest("tr"));});			
			$('#gear_oil_amt-'+id).on('change',function(){cm_calamt($(this).closest("tr"));});			
			$('#distilled_water_amt-'+id).on('change',function(){cm_calamt($(this).closest("tr"));});			
		});
		var netqty = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netqty += parseFloat($('#total_amount-'+id).val());
		});
		var value = parseFloat(netqty).toFixed(2);
		$('#total_payment_amount').val(($.isNumeric(value))? value:'0.00');
	};
	
	function cm_calamt(obj){
		var id = obj.closest("tr").attr('id');
		var fuel_litter = parseFloat($('#fuel_litter-'+id).val()).toFixed(2);
		var fuel_rate = parseFloat($('#fuel_rate-'+id).val()).toFixed(2);
		var engine_oil_amt = parseFloat($('#engine_oil_amt-'+id).val());
		var brake_oil_amt = parseFloat($('#brake_oil_amt-'+id).val());
		var gear_oil_amt = parseFloat($('#gear_oil_amt-'+id).val());
		var distilled_water_amt = parseFloat($('#distilled_water_amt-'+id).val());
		
		fuel_litter = ($.isNumeric(fuel_litter))?fuel_litter:'';
		fuel_rate = ($.isNumeric(fuel_rate))?fuel_rate:'0.00';
	
		var amt = (fuel_litter * fuel_rate) + engine_oil_amt + brake_oil_amt + gear_oil_amt + distilled_water_amt;
		
		$('#total_amount-'+id).val(parseFloat(amt).toFixed(2));
		
		var netqty = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netqty += parseFloat($('#total_amount-'+id).val());
		});
		var value = parseFloat(netqty).toFixed(2);
		
		$('#total_payment_amount').val(($.isNumeric(value))? value:'0.00');
	}		
	function cm_reset(obj){
		var id = obj.attr('id');
		$('#fuel_litter-'+id).val('0.00');
		$("#fuel_litter-"+id).attr("fuel_litter", '0.000');
		$("#fuel_rate-"+id).attr("fuel_rate", '0.000');
		$("#engine_oil_amt-"+id).attr("engine_oil_amt", '0.000');
		$("#brake_oil_amt-"+id).attr("brake_oil_amt", '0.000');
		$("#brake_oil_amt-"+id).attr("brake_oil_amt", '0.000');
		$("#gear_oil_amt-"+id).attr("gear_oil_amt", '0.000');
		$("#distilled_water_amt-"+id).attr("distilled_water_amt", '0.000');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});		
		cm_calculation(obj.parent().parent());
	}
	
	/*
	 * Vehicle/VehicleController
	 * addrequisition-->Add requisition
	 * editrequisition-->Edit requisition
	 */
	$.fn.requisition = function (spec){
		var options = $.extend({
			getUom: '',
		},spec);
		
		
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			$(this).find('select.tr-service_item').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$.post(
						options.getUom,
						{
							item_id: $(this).val(),
						},
						function(data){
							//console.log(data);
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id).trigger('chosen:updated');
						},
						'json'
					);
				});
			});
			$(this).find('input.tr-quantity').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#quantity-"+id).attr("quantity", $(this).val());
				});
			});
			rq_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			rq_addrow(parentObj,options.getUom);			     
		});	
		rq_calculation(parentObj);			
	};	
	function rq_addrow(obj, getUom){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		rq_reset(clone);	        
		$('form select').chosen();//{ allow_single_deselect: true }
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-service_item').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$.post(
					getUom,
					{
						item_id: $(this).val(),
					},
					function(data){
						console.log(data);
						$('#uom-'+id).html(data.uom);
						$('#uom-'+id+' option:selected').removeAttr('selected');
						$('#uom-'+id).trigger('chosen:updated');
					},
					'json'
				);
			});
		});
		clone.find('input.tr-quantity').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#quantity-"+id).attr("quantity", $(this).val());
			});
		});
		rq_delbtn(obj);		
		rq_calculation(obj);
	};	
	function rq_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){rq_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){rq_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function rq_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){rq_reset($(this).closest("tr"));});
			});
		}
		rq_calculation(tbody.parent());
	}	
	function rq_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#quantity-'+id).on('change',function(){rq_calamt($(this).closest("tr"));});			
		});
		var netqty = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netqty += parseFloat($('#quantity-'+id).val());
		});
		var value = parseFloat(netqty).toFixed(2);
		$('#rq_quantity').val(($.isNumeric(value))? value:'0.00');
	};
	function rq_calamt(obj){
		var id = obj.closest("tr").attr('id');
		var quantity = parseFloat($('#quantity-'+id).val());
		var qty = quantity;
		$('#amount-'+id).val(parseFloat(qty).toFixed(2));
		var netqty = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netqty += parseFloat($('#quantity-'+id).val());
		});
		var value = parseFloat(netqty).toFixed(2);
		$('#rq_quantity').val(($.isNumeric(value))? value:'0.00');
	}		
	function rq_reset(obj){
		var id = obj.attr('id');
		$('#quantity-'+id).val('0.00');
		$("#quantity-"+id).attr("quantity", '0.000');
		$("#uom-"+id).attr("input_uom", '0.000');
		$('#item-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#uom-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		rq_calculation(obj.parent().parent());
	}
	/**********### PURCHASE MODULE ###**********/
	/*
	 * Purchase/PurchaseController
	 * addporder -->Add Purchase Order
	 * editporder -->Edit Purchase Order
	 */
	$.fn.porder = function (spec){
		alert('hi there');
		var options = $.extend({
			getItem:'',
			getUom: '',
			getUomChange:'',
		},spec);
		console.log('Received options:', options); // Log the received options object
		//alert(options);
		$("#activity, #supplier").change(function() {
		  console.log('Sending activity_id:', $('select#activity').val());
          console.log('Sending supplier_id:', $('select#supplier').val());
			$.post(
				options.getItem,
				{
					activity_id: $('select#activity').val(),
					supplier_id: $('select#supplier').val(),
				},
				function(data){
					console.log(data);
					$(".tr-item").html(data.stock_item);
					$(".tr-item").trigger('chosen:updated');
				},
				'json'
			);	
			$(".tr-item").each(function(){
				po_reset($(this).closest('tr'));
			});
		});
		
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        $(this).find('select.tr-item').each(function(){
				//var id = $(this).closest('tr').attr('id');
				$(this).bind('change',function(){
					var id = $(this).closest('tr').attr('id');
					var cur_val = $(this).val();
					$.when(
						$.post(
							options.getUom,
							{
								item_id: $(this).val(),
							},
							function(data){
								$('#uom-'+id).html(data.uoms);
								$('#uom-'+id).trigger('chosen:updated');
							},
							'json'
						)
					).done(function(){
						var cur_obj = $('#item-'+id).closest('tr');	
						$(document).find('select.tr-item').not('#item-'+id).each(function(){
							if(cur_val == $(this).val()){
								po_reset(cur_obj);
							}
						});
					});
				});
			});
	        $(this).find('select.tr-uom').each(function(){
	    		var id = $(this).closest('tr').attr('id');
	    		$(this).bind('change', function(){
	    			$.post(
						options.getUomChange,
						{
							item_id: $('#item-'+id).val(),
							uom_id: $(this).val(),
							input_uom: $('#uom-'+id).attr('input_uom'),
							quantity: $('#quantity-'+id).attr('quantity'),
							rate: $('#rate-'+id).attr('rate'),
						},
						function(data){
							$('#quantity-'+id).val(data.quantity);
							$('#rate-'+id).val(data.rate);
						},
						'json'
					);
	    		});
	    	});
			$(this).find('input.tr-quantity').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					$("#quantity-"+id).attr("quantity", $(this).val());
				});
			});
			$(this).find('input.tr-rate').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					$("#rate-"+id).attr("rate", $(this).val());
				});
			});
			po_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			po_addrow1(parentObj, options.getUom, options.getUomChange);			     
		});	
		po_calculation(parentObj);			
	};	
	function po_addrow1(obj, getUom, getUomChange){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		po_reset(clone);	        
		$('form select').chosen();//{ allow_single_deselect: true }
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-item').each(function(){
			//var id = $(this).closest('tr').attr('id');
			$(this).bind('change',function(){
				var id = $(this).closest('tr').attr('id');
				var cur_val = $(this).val();
				$.when(
					$.post(
						getUom,
						{
							item_id: $(this).val(),
						},
						function(data){
							$('#uom-'+id).html(data.uoms);
							$('#uom-'+id).trigger('chosen:updated');
						},
						'json'
					)
				).done(function(){
					var cur_obj = $('#item-'+id).closest('tr');	
					$(document).find('select.tr-item').not('#item-'+id).each(function(){
						if(cur_val == $(this).val()){
							po_reset(cur_obj);
						}
					});
				});
			});
		});
		clone.find('select.tr-uom').each(function(){
			var id = $(this).closest('tr').attr('id');
			//alert(id);
			$(this).bind('change', function(){
				$.post(
					getUomChange,
					{
						item_id: $('#item-'+id).val(),
						uom_id: $(this).val(),
						input_uom: $('#uom-'+id).attr('input_uom'),
						quantity: $('#quantity-'+id).attr('quantity'),
						rate: $('#rate-'+id).attr('rate'),
					},
					function(data){
						//console.log(data);
						$('#quantity-'+id).val(data.quantity);
						$('#rate-'+id).val(data.rate);
					},
					'json'
				);
			});
		});
		clone.find('input.tr-quantity').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#quantity-"+id).attr("quantity", $(this).val());
			});
		});
		clone.find('input.tr-rate').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#rate-"+id).attr("rate", $(this).val());
			});
		});
		po_delbtn(obj);		
		po_calculation(obj);
	};	
	function po_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){po_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){po_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function po_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){po_reset($(this).closest("tr"));});
			});
		}
		po_calculation(tbody.parent());
	}	
	function po_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#rate-'+id).on('change',function(){po_calamt($(this).closest("tr"));});
			$('#quantity-'+id).on('change',function(){po_calamt($(this).closest("tr"));});			
		});
		var netamt = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#po_amount').val(($.isNumeric(value))? value:'0.00');
	};
	function po_calamt(obj){
		var id = obj.closest("tr").attr('id');
		var rate = parseFloat($('#rate-'+id).val());
		var quantity = parseFloat($('#quantity-'+id).val());
		var amt = rate*quantity;
		$('#amount-'+id).val(parseFloat(amt).toFixed(2));
				var netamt = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#po_amount').val(($.isNumeric(value))? value:'0.00');
	}		
	function po_reset(obj){
		var id = obj.attr('id');
		$('#remarks-'+id).val('');
		$('#basic_uom-'+id).val('');
		$('#converison-'+id).val('');
		$('#quantity-'+id).val('0.00');
		$('#rate-'+id).val('0.00');
		$('#amount-'+id).val('0.00');
		$("#quantity-"+id).attr("quantity", '0.000');
		$("#rate-"+id).attr("rate", '0.000');
		$("#uom-"+id).attr("input_uom", '0.000');
		$('#item-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#uom-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		po_calculation(obj.parent().parent());
	}
	
/*
	 * Purchase/ReceiptController
	 * addreceipt --> Add Purchase Receipt
	 * editreceipt --> Edit Purchase Receipt
	 */
	$.fn.preceipt = function (spec){
		var options = $.extend({
			getUom: '',
			getUomChange:'',
		},spec);
		
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			
			$(this).find('select.tr-item').each(function(){
				var id = $(this).closest('tr').attr('id');
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#challan_qty-"+id).attr("challan", $('#challan_qty-'+id).val());
				$("#damage_qty-"+id).attr("damage", $("#damage_qty-"+id).val());
				$('#shortage_qty-'+id).attr("shortage", $('#shortage_qty-'+id).val());
				$("#accept_qty-"+id).attr("accept", $('#accept_qty-'+id).val());
				$("#sound_qty-"+id).attr("sound", $('#sound_qty-'+id).val());
			});
			
			$(this).find('select.tr-item').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$.post(
						options.getUom,
						{
							item_id: $(this).val(),
							po_id: $("#po_no").val(),
							//po_details_id : $("#po_details_id-"+id).val(),
						},
						function(data){
							$('#uom-'+id).html(data.uoms);
							$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id+' option[value='+data.po_uom+']').attr('selected', 'selected');
							$('#uom-'+id).trigger('chosen:updated');
							$('#po_qty-'+id).val(data.po_qty);
							$('#balance_qty-'+id).val(data.balance_qty);
							$("#po_details_id-"+id).val(data.po_details_id);
						},
						'json'
					);
				});
			});
			
	        $(this).find('select.tr-uom').each(function(){
	    		var id = $(this).closest('tr').attr('id');
	    		$(this).bind('change', function(){
					//console.log($('#challan_qty-'+id).attr('challan'));
	    			$.post(
						options.getUomChange,
						{
							item_id: $('#item-'+id).val(),
							uom_id: $(this).val(),
							po_id: $("#po_no").val(),
							po_details_id : $("#po_details_id-"+id).val(),
							challan_qty: $('#challan_qty-'+id).attr('challan'),
							damage_qty: $('#damage_qty-'+id).attr('damage'),
							shortage_qty: $('#shortage_qty-'+id).attr('shortage'),
							accept_qty: $('#accept_qty-'+id).attr('accept'),
							sound_qty: $('#sound_qty-'+id).attr('sound'),
							input_uom: $('#uom-'+id).attr('input_uom'),
						},
						function(data){
							//console.log(data);
							$('#po_qty-'+id).val(data.po_qty);
							$('#balance_qty-'+id).val(data.balance_qty);
							$('#challan_qty-'+id).val(data.challan_qty);
							$('#damage_qty-'+id).val(data.damage_qty);
							$('#shortage_qty-'+id).val(data.shortage_qty);
							$('#accept_qty-'+id).val(data.accept_qty);
							$('#sound_qty-'+id).val(data.sound_qty);
						},
						'json'
					);
	    		});
	    	});
			$(this).find('input.tr-challan_qty').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					
					$("#challan_qty-"+id).attr("challan", $(this).val());
					var accept_quantity = parseFloat($(this).val() - $('#shortage_qty-'+id).val());
					var sound_quantity = parseFloat($(this).val() - $('#shortage_qty-'+id).val() - $('#damage_qty-'+id).val());
					
					$("#accept_qty-"+id).attr("accept", accept_quantity);
					$("#sound_qty-"+id).attr("sound", sound_quantity);
				});
			});
			$(this).find('input.tr-damage_qty').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					
					$("#damage_qty-"+id).attr("damage", $(this).val());
					var sound_quantity = parseFloat($("#challan_qty-"+id).val() - $('#shortage_qty-'+id).val() - $(this).val());
					$("#sound_qty-"+id).attr("sound", sound_quantity);
				});
			});
			$(this).find('input.tr-shortage_qty').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					
					$("#shortage_qty-"+id).attr("shortage", $(this).val());
					
					var accept_quantity = parseFloat($("#challan_qty-"+id).val() - $(this).val());
					var sound_quantity = parseFloat($("#challan_qty-"+id).val() - $(this).val() - $('#damage_qty-'+id).val());
					
					$("#accept_qty-"+id).attr("accept", accept_quantity);
					$("#sound_qty-"+id).attr("sound", sound_quantity);
				});
			});
			pr_delbtn(parentObj);
		}); 
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			parentObj.parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			prn_addrow(parentObj,options.getUom,options.getUomChange);			     
		});	
		pr_calculation(parentObj);	
	};    
	function prn_addrow(obj,getUom,getUomChange){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		var retainVal = clone.find(':input#po_no-'+lastRow).val();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		pr_reset(clone);
		
		$('form select').chosen(); //{ allow_single_deselect: true }
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-item').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$.post(
					getUom,
					{
						item_id: $(this).val(),
						po_id: $("#po_no").val(),
						po_details_id : $("#po_details_id-"+id).val(),
					},
					function(data){
						$('#uom-'+id).html(data.uoms);
						$('#uom-'+id+' option:selected').removeAttr('selected');
						$('#uom-'+id+' option[value='+data.po_uom+']').attr('selected', 'selected');
						$('#uom-'+id).trigger('chosen:updated');
						$('#po_qty-'+id).val(data.po_qty);
						$('#balance_qty-'+id).val(data.balance_qty);
						$("#po_details_id-"+id).val(data.po_details_id);
					},
					'json'
				);
			});
		});
		clone.find('select.tr-uom').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$.post(
					getUomChange,
					{
						item_id: $('#item-'+id).val(),
						uom_id: $(this).val(),
						po_id: $("#po_no").val(),
						po_details_id : $("#po_details_id-"+id).val(),
						challan_qty: $('#challan_qty-'+id).attr('challan'),
						damage_qty: $('#damage_qty-'+id).attr('damage'),
						shortage_qty: $('#shortage_qty-'+id).attr('shortage'),
						accept_qty: $('#accept_qty-'+id).attr('accept'),
						sound_qty: $('#sound_qty-'+id).attr('sound'),
						input_uom: $('#uom-'+id).attr('input_uom'),
					},
					function(data){
						//console.log(data);
						$('#po_qty-'+id).val(data.po_qty);
						$('#balance_qty-'+id).val(data.balance_qty);
						$('#challan_qty-'+id).val(data.challan_qty);
						$('#damage_qty-'+id).val(data.damage_qty);
						$('#shortage_qty-'+id).val(data.shortage_qty);
						$('#accept_qty-'+id).val(data.accept_qty);
						$('#sound_qty-'+id).val(data.sound_qty);
					},
					'json'
				);
			});
		});
		clone.find('input.tr-challan_qty').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				
				$("#challan_qty-"+id).attr("challan", $(this).val());
				var accept_quantity = parseFloat($(this).val() - $('#shortage_qty-'+id).val());
				var sound_quantity = parseFloat($(this).val() - $('#shortage_qty-'+id).val() - $('#damage_qty-'+id).val());
				
				$("#accept_qty-"+id).attr("accept", accept_quantity);
				$("#sound_qty-"+id).attr("sound", sound_quantity);
			});
		});
		clone.find('input.tr-damage_qty').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#damage_qty-"+id).attr("damage", $(this).val());
				var sound_quantity = parseFloat($("#challan_qty-"+id).val() - $('#shortage_qty-'+id).val() - $(this).val());
				$("#sound_qty-"+id).attr("sound", sound_quantity);
			});
		});
		clone.find('input.tr-shortage_qty').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#shortage_qty-"+id).attr("shortage", $(this).val());
				
				var accept_quantity = parseFloat($("#challan_qty-"+id).val() - $(this).val());
				var sound_quantity = parseFloat($("#challan_qty-"+id).val() - $(this).val() - $('#damage_qty-'+id).val());
				
				$("#accept_qty-"+id).attr("accept", accept_quantity);
				$("#sound_qty-"+id).attr("sound", sound_quantity);
			});
		});
		
		pr_delbtn(obj);		
		pr_calculation(obj);
	};	
	function pr_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){pr_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){pr_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function pr_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			//console.log($(this));
			$('form').formValidation('removeField', $(this));
		});
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){pr_reset($(this).closest("tr"));});
			});
		}
		pr_calculation(tbody.parent());
	}	
	function pr_reset(obj){
		var id = obj.attr('id');
		$('#po_qty-'+id).val('0.00');
		$('#balance_qty-'+id).val('0.00');
		$('#challan_qty-'+id).val('0.00');
		$('#damage_qty-'+id).val('0.00');
		$('#shortage_qty-'+id).val('0.00');
		$('#accept_qty-'+id).val('0.00');
		$('#sound_qty-'+id).val('0.00');
		//$('#po_no-'+id).val('defaultSelected').trigger('chosen:updated');
		$('#item-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#uom-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		pr_calculation(obj.parent().parent());
	};
	function pr_calculation(obj) {
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#challan_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					$('#accept_qty-'+id).val(parseFloat($(this).val()) - parseFloat($('#shortage_qty-'+id).val()));
					var sum = parseFloat($("#damage_qty-"+id).val()) + parseFloat($('#shortage_qty-'+id).val());
					//sum = sum.toFixed(2);
					$('#sound_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()) - sum);
				}else{
					$(this).val("0.000");
	 				$("#accept_qty-"+id).val("0.000");
	 				$("#sound_qty-"+id).val("0.000");
				}
			});
			$('#shortage_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					$('#accept_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()) - parseFloat($(this).val()));
					var sum = parseFloat($("#damage_qty-"+id).val()) + parseFloat($('#shortage_qty-'+id).val());
					//sum = sum.toFixed(2);
					$('#sound_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()) - sum);
				}else{
					$(this).val("0.000");
				}
			});
			$('#damage_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					$('#sound_qty-'+id).val(parseFloat($("#accept_qty-"+id).val()) - parseFloat($(this).val()));
				}else{
					$(this).val("0.000");
				}
			});
		});
	};	
	
	/*
	 * Purchase/SupplierController
	 * addsupplierinv --> Add Suplier Invoice
	 * editsupplierinv -->Edit Supplier Invoice
	 */
	$.fn.supplierinvoice = function (spec){
		var options = $.extend({
			getUomChange:'',
		},spec);
		
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			sumAmt(parentObj);
	        $(this).find('select.tr-uom').each(function(){
				var id = $(this).closest('tr').attr('id');			
				$(this).bind('change', function(){
					$.post(
						options.getUomChange,
						{
							item_id: $('#item-'+id).val(),
							uom_id: $(this).val(),
							prn_id: $("#purchase_receipt").val(),
							prn_detail_id : $("#prn_details_id-"+id).val(),
							sinv_dtl_id : $("#sup_inv_details_id-"+id).val(),
							uom_task : $("#uom_task").val(),
						},
						function(data){
							//$('#unit_uom-'+id).val(data.unit_uom);
							//console.log(data);
							$('#quantity-'+id).val(data.quantity);
							$('#rate-'+id).val(data.rate);
						},
						'json'
					);
				});
				$('#rate-'+id).on('change',function(){
					if($.isNumeric($(this).val())){
						$amount = $(this).val() * $('#quantity-'+id).val();
			    		$('#amount-'+id).val(parseFloat($amount).toFixed(2));
			    		sumAmt(parentObj);
			    	}else{
			    		$('#amount-'+id).val("0.00");
			    		sumAmt(parentObj);
			    	}
			    });
				$('#quantity-'+id).on('change',function(){
					if($.isNumeric($(this).val())){
						$amount = $(this).val() * $('#rate-'+id).val();
			    		$('#amount-'+id).val(parseFloat($amount).toFixed(2));
			    		sumAmt(parentObj);
			    	}else{
			    		$('#amount-'+id).val("0.00");
			    		sumAmt(parentObj);
			    	}
			    });
	        });
		});
		$('#freight_charge').on('change',function(){
			if($.isNumeric($(this).val())){
				$("#net_inv_amount").val(parseFloat(parseFloat($("#purchase_amount").val()) + parseFloat($(this).val())).toFixed(3));
				$("#payable_amount").val(parseFloat(parseFloat($(this).val()) + parseFloat($("#purchase_amount").val()) - parseFloat($("#deduction_amount").val())).toFixed(3));
			}else{
				$("#net_inv_amount").val("0.00");
				$("#payable_amount").val("0.00");
			}
        });
		$("#deduction_amount").on("change", function(){ 
			if($.isNumeric($(this).val())){
				$("#payable_amount").val(parseFloat($("#net_inv_amount").val() - $(this).val()).toFixed(2));
			}else{
				$("#payable_amount").val("0.00");
			}
		});
	};
	function sumAmt(Obj){
		var pur_amt = 0.00;
		Obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			pur_amt += parseFloat($('#amount-'+id).val());
		});
		
		$('#purchase_amount').val(parseFloat(pur_amt).toFixed(2));
		var net_inv_amt = pur_amt + parseFloat($("#freight_charge").val());
		$("#net_inv_amount").val(parseFloat(net_inv_amt).toFixed(2));
		var payable_amt = pur_amt + parseFloat($("#freight_charge").val())-parseFloat($("#deduction_amount").val());
		$("#payable_amount").val(parseFloat(payable_amt).toFixed(2));
	};
	
		/*
	 * Purchase/PaymentController
	 * addpayment --> Add Payment
	 * editpayment -->Edit Payment
	 */
	$.fn.payment = function (spec){
		var options = $.extend({
			getParty:'',
			getInvoice:'',
			getInvAmt:'',
		},spec);
		
		$('#payment_type').change(function(){
			if($(this).val() == 4){
				$('#activity option:selected').removeAttr('selected');
				$('#activity option[value='+"-1"+']').attr('selected', 'selected');
				$('#activity').trigger('chosen:updated');
				$('form').formValidation('revalidateField','activity');
			}
			$.post(
    			options.getParty,
    			{
    				payment_type: $(this).val(),
    			},
    			function(data){
    				$("#party").html(data.payment_party);
					$("#party").trigger('chosen:updated');
    			},
				'json'
    		);
		});
		
		$("#party").change(function() {
			$.post(
    			options.getInvoice,
    			{
    				party: $(this).val(),
    				payment_type: $('#payment_type').val(),
    			},
    			function(data){
    				$(".tr-invoice").html(data.inv);
					$(".tr-invoice").trigger('chosen:updated');
    			},
				'json'
    		);
		});
		
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);	
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        
			$(this).find('select.tr-invoice').each(function(){
				var id = $(this).closest('tr').attr('id');			
				$(this).bind('change', function(){
					var selected_inv = $(this);
					$.post(
		    			options.getInvAmt,
		    			{
		    				payment_type: $('#payment_type').val(),
							invoice_id: $(this).val(),
		    			},
		    			function(data){
		    				$("#invoice_amount-"+id).val(data.amount);
		    				$('#deduction-'+id).val('0.00');
		    				payment_calamt(selected_inv.closest("tr"));
		    			},
						'json'
		    		);
				});
			});
			pay_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			pay_addrow(parentObj, options.getInvAmt);			     
		});	
		payment_calculation(parentObj);	
		payment_caldetail();
	};	
	function pay_addrow(obj, getInvAmt){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		pay_reset(clone);	        
		$('form select').chosen({ allow_single_deselect: true });
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		
		clone.find('select.tr-invoice').each(function(){
				var id = $(this).closest('tr').attr('id');			
				$(this).bind('change', function(){
					var selected_inv = $(this);
					$.post(
		    			getInvAmt,
		    			{
		    				payment_type: $('#payment_type').val(),
							invoice_id: $(this).val(),
		    			},
		    			function(data){
		    				$("#invoice_amount-"+id).val(data.amount);
		    				$('#deduction-'+id).val('0.00');
		    				payment_calamt(selected_inv.closest("tr"));
		    			},
						'json'
		    		);
				});
			});
		
		pay_delbtn(obj);		
		payment_calculation(obj);
	};	
	function pay_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){pay_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){pay_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function pay_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){pay_reset($(this).closest("tr"));});
			});
		}
		payment_calculation(tbody.parent());
	}
	
	function payment_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#invoice_amount-'+id).on('change',function(){payment_calamt($(this).closest("tr"));});
			$('#deduction-'+id).on('change',function(){payment_calamt($(this).closest("tr"));});
		});
		var netamt = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#payable_amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		//var tax = value * 2/100;
		//tax_value = parseFloat(tax).toFixed(2);
		$('#net_pay_amount').val(($.isNumeric(value))? value:'0.00');
		//$('#deduction').val(($.isNumeric(tax_value))? tax_value:'0.00');
		$('#payment_amount').val(parseFloat($('#net_pay_amount').val() - $('#deduction').val()).toFixed(2));
		payment_bankcharge($('#payment_amount').val());
	};
	
	function payment_calamt(obj){	
		var id = obj.closest("tr").attr('id');
		var inv_amt = parseFloat($('#invoice_amount-'+id).val()).toFixed(2);
		var deduction_amt = parseFloat($('#deduction-'+id).val()).toFixed(2);
		
		if ( deduction_amt == NaN) {  deduction_amt = 0.00;	}
		
		var pay_amt = inv_amt - deduction_amt; 
		$('#payable_amount-'+id).val(parseFloat(pay_amt).toFixed(2));
		
		var netamt = 0;
	    obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#payable_amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		//var tax = value * 2/100;
		//tax_value = parseFloat(tax).toFixed(2);
		$('#net_pay_amount').val(($.isNumeric(value))? value:'0.00');
		//$('#deduction').val(($.isNumeric(tax_value))? tax_value:'0.00');
		$('#payment_amount').val(parseFloat($('#net_pay_amount').val() - $('#deduction').val()).toFixed(2));
		payment_bankcharge($('#payment_amount').val());
	}

	function payment_caldetail(){
		$('#deduction').on('change',function(){
			$('#payment_amount').val(parseFloat($('#net_pay_amount').val() - $(this).val()).toFixed(2));
			payment_bankcharge($('#payment_amount').val());
		});
	}
	function payment_bankcharge(pay_amt){
		var bank_charge;
		if($.isNumeric(pay_amt) && pay_amt > 0.00 && pay_amt!=""){
			if(pay_amt <= 100000.00){
				bank_charge = 35.00;
			}else if(pay_amt >= 100001.00 && pay_amt<=1000000.00 ){
				bank_charge = 2000.00;
			}else{
				bank_charge = 15000.00;
			} 
		}else{
			bank_charge = "0.00";
		}
		//$('#bank_charge').val(bank_charge);
	}
	
	function pay_reset(obj){
		var id = obj.attr('id');
		
		$('#invoice_amount-'+id).val('0.00');
		$('#deduction-'+id).val('0.00');
		$('#payable_amount-'+id).val('0.00');
		$('#invoice-'+id).val('selectedIndex',0).trigger('chosen:updated');
		
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		payment_calculation(obj.parent().parent());
	}
	
	/*
	 * Purchase/PaymentController
	 * bookpayment --> Book Payment
	 */
	$.fn.bookpayment = function (spec){
		//set default option
		var options = $.extend({
				src: '',
				tot_debit: 0,
				tot_credit: 0

		},spec);
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='margin-bottom:5px; overflow-x:scroll;' id='jscroll_div'> </div>");		
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        $(this).find('select.tr-head').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$('#sub_head-'+id).load(options.src+'/'+$(this).val(), function(){
						$('#sub_head-'+id).trigger('chosen:updated');
					});
				});
			});
			bookpay_delbtn(parentObj);
			if($('#voucher_type').val() == '15'){
				$('form').formValidation('removeField','total_debit');
				$('form').formValidation('removeField','total_credit');
				$('form').formValidation('removeField','voucher_amount');
				$('#total_debit').parent().remove();
				$('#total_credit').parent().remove();
				$('#voucher_amount').parent().remove();
			}
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div>";
			$(this).closest('.row').append(addbutton);
		};		
		$("#addRow").click(function(){
			bookpay_addrow(parentObj,options);			     
		});	
		bookpay_calculation(parentObj);
		$('#voucher_type').change(function(){
			
			if($(this).val() == '15'){
				var rowCount =0;
				parentObj.find('tbody tr').each(function(){
					if(++rowCount > 1){
						$(this).find(':input[data-fv-field]').each(function() {
							$('form').formValidation('removeField',$(this));
						});
						$(this).remove();
					}
				});
				$('form').formValidation('removeField','total_debit');
				$('form').formValidation('removeField','total_credit');
				$('form').formValidation('removeField','voucher_amount');
				$('#total_debit').parent().remove();
				$('#total_credit').parent().remove();
				$('#voucher_amount').parent().remove();
			}else{
				var rowCount = parentObj.find('tbody tr').length;
				if(rowCount < 2){
					location.reload();
				}
			}
		});	
		//$('#jscroll_div').jScrollPane();		
	};
	function bookpay_addrow(obj, options){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		bookpay_reset(clone);	        
		$('form select').chosen();
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		obj.find('select.tr-head').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$('#sub_head-'+id).load(options.src+'/'+$(this).val(), function(){
						$('#sub_head-'+id).trigger('chosen:updated');
					});
				});
			});
		bookpay_delbtn(obj);		
		bookpay_calculation(obj);
	};
	function bookpay_delbtn(obj){
		var trlenght = ($('#voucher_type').val() == 15)? 1:2;
		if(obj.find("tbody tr").length > trlenght){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){bookpay_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){bookpay_reset($(this).closest('tr'));});	
			});
		}		
	};
	function bookpay_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			//$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		var trlenght = ($('#voucher_type').val() == 15)? 2:3;
		if(tr_length <= trlenght){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr').each(function(){$(this).find('td:last').html(delBtn)});
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){bookpay_reset($(this).closest("tr"));});
			});
		}
		bookpay_calculation(tbody.parent());
	}
	function bookpay_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#debit-'+id).on('change',function(){
				var id = $(this).closest('tr').attr('id');
				var value = parseFloat($(this).val()).toFixed(3);
				$(this).val(value = ($.isNumeric(value))? value:'0.000');
				if(value > 0){
					$('#credit-'+id).val('0.000')
				}
				bookpay_totamt(obj);
			});
			$('#credit-'+id).on('change',function(){
				var id = $(this).closest('tr').attr('id');
				var value = parseFloat($(this).val()).toFixed(3);
				$(this).val(value = ($.isNumeric(value))? value:'0.000');
				if(value > 0){
					$('#debit-'+id).val('0.000')
				}
				bookpay_totamt(obj);
			});			
		});
		bookpay_totamt(obj);
	};
	
	function bookpay_totamt(obj){
		var netdebit = 0;
		var netcredit = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netdebit += parseFloat($('#debit-'+id).val());
			netcredit += parseFloat($('#credit-'+id).val());
		});
		$('#total_debit').val(parseFloat(netdebit).toFixed(3));
		$('#total_credit').val(parseFloat(netcredit).toFixed(3));
		$('#voucher_amount').val(($('#total_debit').val() == $('#total_credit').val())? $('#total_credit').val():'0.000');
		
		$('form').find('input[data-fv-field="total_debit"]').each(function() {
			$('form').formValidation('revalidateField',$(this));
		});	
		$('form').find('input[data-fv-field="total_credit"]').each(function() {
			$('form').formValidation('revalidateField',$(this));
		});	
	};
	
	function bookpay_reset(obj){
		var id = obj.attr('id');
		$('#debit-'+id).val('0.000');
		$('#credit-'+id).val('0.000');
		$('#cheque_no-'+id).val('');
		$('#bank_ref_no-'+id).val('');
		$('#location-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#activity-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#activity-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#head-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#sub_head-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#sub_head-'+id).trigger('chosen:updated');
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		bookpay_calculation(obj.parent().parent());
	}
	/**********### STOCK MODULE ###**********/	
	/*
	 * Stock/DispatchController
	 * adddispatch 	-->Add Goods Dispatch
	 * editdispatch -->Edit Goods Dispatch
	 */
	$.fn.dispatch = function (spec){
		//set default option
		var options = $.extend({
				getToLocation: '',
				getItem: '',
				getDetails: '',
				getStockBalance: '',
				getConvertedQty: '',
				getBasicQty: '',
		},spec);
		/***** Get The Destination Locations From Source Location *****/
		/*$("#from_location").change(function() {
			$("#to_location").load(options.getToLocation+"/"+ $(this).val(), function() {
				$("#to_location").trigger("chosen:updated");
			});
		});*/
		/***** Get the Item of the Activity *****/
		$("#activity").change(function() {
			$.post(
				options.getItem,
				{
					activity: $(this).val(),
                                        source_loc: $('#from_location').val(),
				},
				function(data){
					//console.log(data);
					$('.tr-item').html(data.items);
					$('.tr-item').trigger('chosen:updated');
				},
				'json'
			);
			$(".tr-item").each(function(){
				dsp_reset($(this).closest('tr'));
			});
		});
		/**** Get Source and Destination Quantities ***/
		$('#from_location,#to_location').change(function(){
			var id = 1;
			$.post(
				options.getStockBalance,
				{
					batch_id: $('#batch-'+id).val(),
					source_loc: $('#from_location').val(),
					destination_loc: $('#to_location').val(),
					item_id: $('#item-'+id).val(),
				},
				function(data){
					//console.log(data);
					$('#from_balance-'+id).val(data.source_qty);
					$('#to_balance-'+id).val(data.destination_qty);
				},
				'json'
			);
		});
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			$(this).find('select.tr-item').each(function(){
				$(this).bind('change', function(){
					if($('#from_location').val()=="" || $('#to_location').val()==""){
						$('form').formValidation('revalidateField',$('#from_location'));
						$('form').formValidation('revalidateField',$('#to_location'));
					}
					var id = $(this).closest('tr').attr('id');
					//var cur_val = $(this).val();
					//$.when(
						$.post(
							options.getDetails,
							{
								item_id: $(this).val(),
								source_loc: $('#from_location').val(),
								destination_loc: $('#to_location').val(),
							},
							function(data){
								//console.log(data);
								$('#batch-'+id).html(data.batch);
								$('#batch-'+id+' option:selected').removeAttr('selected');
								$('#batch-'+id+' option[value='+data.latest_batch+']').attr('selected', 'selected');
								$('#batch-'+id).trigger('chosen:updated');
								$('#uom-'+id).html(data.uom);
								$('#uom-'+id+' option:selected').removeAttr('selected');
								$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
								$('#uom-'+id).trigger('chosen:updated');
								$('#from_balance-'+id).val(data.source_qty);
								$('#to_balance-'+id).val(data.destination_qty);
								$('#basic_uom-'+id).html(data.basic_uom);
								$('#basic_uom-'+id).trigger('chosen:updated');
								$('form').formValidation('revalidateField',$('#from_balance-'+id));
							},
							'json'
						)
					/*).done(function(){
						var cur_obj = $('#item-'+id).closest('tr');	
						$(document).find('select.tr-item').not('#item-'+id).each(function(){
							if(cur_val == $(this).val()){
								dsp_reset(cur_obj);
							}
						});
					});*/
				});
			});
	        $(this).find('select.tr-batch').each(function(){
				$(this).bind('change', function(){					
					var id = $(this).closest('tr').attr('id');
					if($('#from_location').val()=="" || $('#to_location').val()==""){
						$('form').formValidation('revalidateField',$('#from_location'));
						$('form').formValidation('revalidateField',$('#to_location'));
					}
					$.post(
						options.getStockBalance,
						{
							batch_id: $(this).val(),
							source_loc: $('#from_location').val(),
							destination_loc: $('#to_location').val(),
							item_id: $('#item-'+id).val(),
						},
						function(data){
							//console.log(data);
							$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id+' option[value='+data.batch_uom+']').prop('selected', 'selected');
							$('#uom-'+id).trigger('chosen:updated');
							$('#from_balance-'+id).val(data.source_qty);
							$('#to_balance-'+id).val(data.destination_qty);
						},
						'json'
					);
				});
			});
	        
	        $(this).find('select.tr-uom').each(function(){	        	
	        	$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					$.post(
						options.getConvertedQty,
						{	
							uom_id: $(this).val(),
							item_id: $('#item-'+id).val(),
							batch_id: $('#batch-'+id).val(),
							source_loc: $('#from_location').val(),
							destination_loc: $('#to_location').val(),
							dispatch_qty: $('#quantity-'+id).val(),
						},
						function(data){
							//console.log(data);
							$('#from_balance-'+id).val(data.source_qty);
							$('#to_balance-'+id).val(data.destination_qty);
							$('#basic_quantity-'+id).val(data.dispatch_basic_qty);
						},
						'json'
					);
				});
			});
			$(this).find('.tr-quantity').each(function(){
				$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					$.post(
						options.getBasicQty,
						{	
							dispatch_qty: $(this).val(),
							item_id: $('#item-'+id).val(),
							uom_id: $('#uom-'+id).val(),
						},
						function(data){
							//console.log(data);
							$('#basic_quantity-'+id).val(data.basic_qty);
						},
						'json'
					);
				});
			});
			dsp_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			dsp_addrow(parentObj, options.getDetails, options.getStockBalance, options.getConvertedQty,options.getBasicQty);			     
		});				
	};	
	function dsp_addrow(obj, getDetails, getStockBalance, getConvertedQty,getBasicQty){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		dsp_reset(clone);	        
		$('form select').chosen();
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('input.tr-from_balance').each(function(){
			var id = $(this).closest('tr').attr('id');
			var new_validator = {
				validators: {
					greaterThan: {
                        value: '1',
                        message: 'The source quantity must be greater than 0'
                    }
				}
			};
			$('form').formValidation('addField', $(this).attr('name'),new_validator);
		});
		clone.find('select.tr-item').each(function(){
			var id = $(this).closest('tr').attr('id');
			var new_validator = {
				validators: {
					notEmpty: {
						message: 'Please select transporter.'
					}
				}
			};
			$('form').formValidation('addField', $(this).attr('name'),new_validator);
		});
		clone.find('select.tr-item').each(function(){
			$(this).bind('change', function(){
				if($('#from_location').val()=="" || $('#to_location').val()==""){
					$('form').formValidation('revalidateField',$('#from_location'));
					$('form').formValidation('revalidateField',$('#to_location'));
				}
				var id = $(this).closest('tr').attr('id');
				//var cur_val = $(this).val();
				//$.when(
					$.post(
						getDetails,
						{
							item_id: $(this).val(),
							source_loc: $('#from_location').val(),
							destination_loc: $('#to_location').val(),
						},
						function(data){
							//console.log(data);
							$('#batch-'+id).html(data.batch);
							$('#batch-'+id+' option:selected').removeAttr('selected');
							$('#batch-'+id+' option[value='+data.latest_batch+']').attr('selected', 'selected');
							$('#batch-'+id).trigger('chosen:updated');
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
							$('#uom-'+id).trigger('chosen:updated');
							$('#from_balance-'+id).val(data.source_qty);
							$('#to_balance-'+id).val(data.destination_qty);
							$('#basic_uom-'+id).html(data.basic_uom);
							$('#basic_uom-'+id).trigger('chosen:updated');
							$('form').formValidation('revalidateField',$('#from_balance-'+id));
						},
						'json'
					)
				/*).done(function(){
					var cur_obj = $('#item-'+id).closest('tr');	
					$(document).find('select.tr-item').not('#item-'+id).each(function(){
						if(cur_val == $(this).val()){
							dsp_reset(cur_obj);
						}
					});
				});*/
			});
		});
		clone.find('select.tr-batch').each(function(){
			$(this).bind('change', function(){					
				var id = $(this).closest('tr').attr('id');
				if($('#from_location').val()=="" || $('#to_location').val()==""){
					$('form').formValidation('revalidateField',$('#from_location'));
					$('form').formValidation('revalidateField',$('#to_location'));
				}
				$.post(
					getStockBalance,
					{
						batch_id: $(this).val(),
						source_loc: $('#from_location').val(),
						destination_loc: $('#to_location').val(),
						item_id: $('#item-'+id).val(),
					},
					function(data){
						//console.log(data);
						$('#uom-'+id+' option:selected').removeAttr('selected');
					        $('#uom-'+id+' option[value='+data.batch_uom+']').prop('selected', 'selected');
						$('#uom-'+id).trigger('chosen:updated');
						$('#from_balance-'+id).val(data.source_qty);
						$('#to_balance-'+id).val(data.destination_qty);
					},
					'json'
				);
			});
		});
        
		clone.find('select.tr-uom').each(function(){	        	
        	$(this).bind('change', function(){
				var id = $(this).closest('tr').attr('id');
				$.post(
					getConvertedQty,
					{	
						uom_id: $(this).val(),
						item_id: $('#item-'+id).val(),
						batch_id: $('#batch-'+id).val(),
						source_loc: $('#from_location').val(),
						destination_loc: $('#to_location').val(),
						dispatch_qty: $('#quantity-'+id).val(),
					},
					function(data){
						//console.log(data);
						$('#from_balance-'+id).val(data.source_qty);
						$('#to_balance-'+id).val(data.destination_qty);
						$('#basic_quantity-'+id).val(data.dispatch_basic_qty);
					},
					'json'
				);
			});
		});
		clone.find('.tr-quantity').each(function(){
			$(this).bind('change', function(){
				var id = $(this).closest('tr').attr('id');
				$.post(
					getBasicQty,
					{	
						dispatch_qty: $(this).val(),
						item_id: $('#item-'+id).val(),
						uom_id: $('#uom-'+id).val(),
					},
					function(data){
						//console.log(data);
						$('#basic_quantity-'+id).val(data.basic_qty);
					},
					'json'
				);
			});
		});
		dsp_delbtn(obj);
	};	
	function dsp_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){dsp_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){dsp_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function dsp_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){dsp_reset($(this).closest("tr"));});
			});
		}
	}
	function dsp_reset(obj){
		
		var id = obj.attr('id');
		$('#batch-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#batch-'+id).trigger('chosen:updated');
		$('#uom-'+id+' option:selected').removeAttr('selected');
		$('#uom-'+id).trigger('chosen:updated');
		$('#item-'+id+' option:selected').removeAttr('selected');
		$('#item-'+id).trigger('chosen:updated');
		$('#from_balance-'+id).val('0.00');
		$('#to_balance-'+id).val('0.00');
		$('#basic_uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#basic_uom-'+id).trigger('chosen:updated');
		$('#basic_quantity-'+id).val('0.00');
		$('#quantity-'+id).val('0.00');
		$('#remarks-'+id).val('');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
	}
	
/*
 * Stock/DispatchController
 * receivedispatch 	-->Receive Goods Dispatch
 */
	$.fn.dispatchreceive = function (spec){
		var options = $.extend({
			getConvReceivedQty:'',
		},spec);
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			$(this).find('select.tr-uom').each(function(){	        	
	        	$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					$.post(
						options.getConvReceivedQty,
						{	
							uom_id: $(this).val(),
							item_id: $('#item-'+id).val(),
							to_qty: $('#to_qty-'+id).val(),
							dispatch_uom: $('#dispatched_uom-'+id).val(),
							dispatch_qty: $('#dispatched_qty-'+id).val(),
							damage_qty: $('#damage_qty-'+id).val(),
							shortage_qty: $('#shortage_qty-'+id).val(),
						},
						function(data){
							console.log(data);
							$('#challan_qty-'+id).val(data.dispatch_qty);
							$('#to_balance-'+id).val(data.to_qty);
							$('#accept_qty-'+id).val(data.accept_qty);
							$('#sound_qty-'+id).val(data.sound_qty);
						},
						'json'
					);
				});
			});
		});
		dispatchreceive_calculation(parentObj);
	};
	function dispatchreceive_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#shortage_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					$('#accept_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()).toFixed(2) - parseFloat($(this).val()).toFixed(2));
					var sum = parseFloat($("#damage_qty-"+id).val()) + parseFloat($('#shortage_qty-'+id).val());
					sum = sum.toFixed(2);
					$('#sound_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()).toFixed(2) - sum);
				}else{
					$(this).val("0.00");
				}
			});
			$('#damage_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					if($('#accept_qty-'+id).val() > 0.00){
						$('#sound_qty-'+id).val(parseFloat($("#accept_qty-"+id).val()).toFixed(2) - parseFloat($(this).val()).toFixed(2));
					}
					else{
						$('#sound_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()).toFixed(2) - parseFloat($(this).val()).toFixed(2));
					}
					
				}else{
					$(this).val("0.00");
				}
			});
		});
	};
	/*
	 * Stock/StockController **** UOM TABLE
	 * additem      -->Add Item 
	 * edititem 	-->Edit Item
	 */
	$.fn.uom = function (spec){
		var options = $.extend({
			getUomCode:'',
		},spec);
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			$(this).find('.tr-uom').each(function(){
				$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					if($('#standarduom-'+id).is(':checked')){
						$.post(
							options.getUomCode,
							{
								uom_id: $(this).val(),
							},
							function(data){
								$('#st_uom').html(data.uom);
								$('#st_uom').trigger('chosen:updated');
								$('form').formValidation('revalidateField',$('select#st_uom'));
							},
							'json'
						);
					}
				});
			});
			$(this).find('.tr-standarduom').each(function(){
				$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					$.post(
						options.getUomCode,
						{
							uom_id: $('#uom-'+id).val(),
						},
						function(data){
							$('#st_uom').html(data.uom);
							$('#st_uom').trigger('chosen:updated');
							$('form').formValidation('revalidateField',$('select#st_uom'));
						},
						'json'
						
					);
				});
			});				
			uom_delbtn(parentObj);
		});
		if($('#addRow1').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow1' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow1").click(function(){
			uom_addrow(parentObj,options.getUomCode);			     
		});				
	};	
	function uom_addrow(obj, getUomCode){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		uom_reset2(clone);	        
		$('form select').chosen({ allow_single_deselect: true });
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('.tr-uom').each(function(){
			$(this).bind('change', function(){
				var id = $(this).closest('tr').attr('id');
				if($('#standarduom-'+id).is(':checked')){
					$.post(
						getUomCode,
						{
							uom_id: $(this).val(),
						},
						function(data){
							$('#st_uom').html(data.uom);
							$('#st_uom').trigger('chosen:updated');
							$('form').formValidation('revalidateField',$('select#st_uom'));
						},
						'json'
					);
				}
			});
		});
		clone.find('.tr-standarduom').each(function(){
			$(this).bind('change', function(){
				var id = $(this).closest('tr').attr('id');
				$.post(
					getUomCode,
					{
						uom_id: $('#uom-'+id).val(),
					},
					function(data){
						$('#st_uom').html(data.uom);
						$('#st_uom').trigger('chosen:updated');
						$('form').formValidation('revalidateField',$('select#st_uom'));
					},
					'json'
					
				);
			});
		});		
		uom_delbtn(obj);
	};	
	function uom_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){uom_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){uom_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function uom_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		var id = obj.closest('tr').attr('id');
		if($('option:selected','#uom-'+id).attr('data_uom_type') == '1'){
			if($('#uom-'+id).val() == $('#st_uom').val()){
				$('#st_uom').html('');
				$('#st_uom').trigger('chosen:updated');
				$('form').formValidation('revalidateField',$('select#st_uom'));
			}
		}
		obj.closest("tr").remove();
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){uom_reset($(this).closest("tr"));});
			});
		}
	};
	function uom_reset(obj){
		var id = obj.attr('id');
		if($('option:selected','#uom-'+id).attr('data_uom_type') == '1'){
			if($('#uom-'+id).val() == $('#st_uom').val()){
				$('#st_uom').html('');
				$('#st_uom').trigger('chosen:updated');
				$('form').formValidation('revalidateField',$('select#st_uom'));
			}
		}
		$('#standarduom-'+id).removeAttr('checked');
		$('#conversion-'+id).val('');
		$('#uom-'+id).val('selectedIndex',0).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
	};
	function uom_reset2(obj){
		var id = obj.attr('id');
		$('#standarduom-'+id).removeAttr('checked');
		$('#conversion-'+id).val('');
		$('#uom-'+id).val('selectedIndex',0).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
	};
	
	/*
	 * Stock/StockController **** REORDER TABLE
	 * additem      -->Add Item 
	 * edititem 	-->Edit Item
	
	$.fn.reorder = function (){
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
				var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			reorder_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			reorder_addrow(parentObj);			     
		});				
	};	
	function reorder_addrow(obj, src){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		reorder_reset(clone);	        
		$('form select').chosen({ allow_single_deselect: true });
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		reorder_delbtn(obj);
	};	
	function reorder_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){reorder_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){reorder_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function reorder_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){reorder_reset($(this).closest("tr"));});
			});
		}
	};
	function reorder_reset(obj){
		var id = obj.attr('id');
		$('#re_order_level-'+id).val('');
		$('#min_stock_qty-'+id).val('');
		$('#location-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#request_type-'+id).val('selectedIndex',0).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
	};
	 */
	/*
	 * Stock/StockController **** MARGIN TABLE
	 * additem      -->Add Item 
	 * edititem 	-->Edit Item
	 */
	$.fn.marginloc = function (){
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
				var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
		});				
	};
	
	/*
	 * Stock/TransporterController
	 * edittranspcharge -->Edit Transporter Charge
	 */
	$.fn.transportationcharge = function (spec){
		var options = $.extend({
			getDetails:'',
			getSellingPrice: '',
			getConvertedrate: '',
		},spec);
		
		$('#hill_distance, #hill_rate, #qty, #plain_distance, #plain_rate').change(function(){
			var hill_distance = $('#hill_distance').val();
			var hill_rate = $('#hill_rate').val();
			var plain_distance = $('#plain_distance').val();
			var plain_rate = $('#plain_rate').val();
			var qty = $('#qty').val();
			
			var h_charge = hill_distance * hill_rate * qty;
			h_charge = parseFloat(h_charge).toFixed(5);
			$('#h_charge').val(($.isNumeric(h_charge))? h_charge:'0.00');
			
			var p_charge = plain_distance * plain_rate * qty;
			p_charge = parseFloat(p_charge).toFixed(5);
			$('#p_charge').val(($.isNumeric(p_charge))? p_charge:'0.00');
			
			var net_charge = parseFloat(h_charge) + parseFloat(p_charge);
			net_charge = parseFloat(net_charge).toFixed(5);
			$('#actual_tc').val(net_charge);
			
			var final_charge = net_charge - $('#net_transit_loss').val();
			final_charge = parseFloat(final_charge).toFixed(5);
			$('#transp_charge').val(final_charge);
		});
		
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			$(this).find('select.tr-item').each(function(){
				$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					var cur_val = $(this).val();
					$.when(
						$.post(
							options.getDetails,
							{
								item_id: $(this).val(),
								location: $('#location').val(),
							},
							function(data){
								//console.log(data);
								$('#batch-'+id).html(data.batch);
								$('#batch-'+id+' option:selected').removeAttr('selected');
								$('#batch-'+id+' option[value='+data.latest_batch+']').attr('selected', 'selected');
								$('#batch-'+id).trigger('chosen:updated');
								$('#uom-'+id).html(data.uom);
								$('#uom-'+id+' option:selected').removeAttr('selected');
								$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
								$('#uom-'+id).trigger('chosen:updated');
								$('#rate-'+id).val(data.selling_price);
							},
							'json'
						)
					).done(function(){
						var cur_obj = $('#item-'+id).closest('tr');	
						$(document).find('select.tr-item').not('#item-'+id).each(function(){
							if(cur_val == $(this).val()){
								tc_reset(cur_obj);
							}
						});
					});
				});
			});
	        $(this).find('select.tr-batch').each(function(){
	        	var id = $(this).closest('tr').attr('id');
	        	$(this).bind('change',function(){
	        		$.post(
	        			options.getSellingPrice,
	        			{
	        				batch_id: $(this).val(),
	        				item_id: $('#item-'+id).val(),
							location: $('#location').val(),
	        			},
	        			function(data){
	        				console.log(data);
	        				$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
							$('#uom-'+id).trigger('chosen:updated');
							$('#rate-'+id).val(data.selling_price);
	        			},
	        			'json'
	        		);
	        	});
	        });
	        $(this).find('select.tr-uom').each(function(){	        	
	        	$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					$.post(
						options.getConvertedrate,
						{	
							uom_id: $(this).val(),
							item_id: $('#item-'+id).val(),
							batch_id: $('#batch-'+id).val(),
							location: $('#location').val(),
						},
						function(data){
							//console.log(data);
							$('#rate-'+id).val(data.rate);
						},
						'json'
					);
				});
			});
			tc_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			tc_addrow(parentObj, options.getDetails, options.getSellingPrice, options.getConvertedrate);			     
		});	
		tc_calculation(parentObj);
	};	
	function tc_addrow(obj, getDetails, getSellingPrice, getConvertedrate){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		tc_reset(clone);	        
		$('form select').chosen();
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		
		clone.find('select.tr-item').each(function(){
			$(this).bind('change', function(){
				var id = $(this).closest('tr').attr('id');
				var cur_val = $(this).val();
				$.when(
					$.post(
						getDetails,
						{
							item_id: $(this).val(),
							location: $('#location').val(),
						},
						function(data){
							//console.log(data);
							$('#batch-'+id).html(data.batch);
							$('#batch-'+id+' option:selected').removeAttr('selected');
							$('#batch-'+id+' option[value='+data.latest_batch+']').attr('selected', 'selected');
							$('#batch-'+id).trigger('chosen:updated');
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
							$('#uom-'+id).trigger('chosen:updated');
							$('#rate-'+id).val(data.selling_price);
						},
						'json'
					)
				).done(function(){
					var cur_obj = $('#item-'+id).closest('tr');	
					$(document).find('select.tr-item').not('#item-'+id).each(function(){
						if(cur_val == $(this).val()){
							tc_reset(cur_obj);
						}
					});
				});
			});
		});
		clone.find('select.tr-batch').each(function(){
        	var id = $(this).closest('tr').attr('id');
        	$(this).bind('change',function(){
        		$.post(
        			getSellingPrice,
        			{
        				batch_id: $(this).val(),
        				item_id: $('#item-'+id).val(),
						location: $('#location').val(),
        			},
        			function(data){
        				console.log(data);
        				$('#uom-'+id+' option:selected').removeAttr('selected');
						$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
						$('#uom-'+id).trigger('chosen:updated');
						$('#rate-'+id).val(data.selling_price);
        			},
        			'json'
        		);
        	});
        });
		clone.find('select.tr-uom').each(function(){	        	
			$(this).bind('change', function(){
				var id = $(this).closest('tr').attr('id');
				$.post(
					getConvertedrate,
					{	
						uom_id: $(this).val(),
						item_id: $('#item-'+id).val(),
						batch_id: $('#batch-'+id).val(),
						location: $('#location').val(),
					},
					function(data){
						//console.log(data);
						$('#rate-'+id).val(data.rate);
					},
					'json'
				);
			});
		});
		tc_delbtn(obj);		
		tc_calculation(obj);
	};	
	function tc_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){tc_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){tc_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function tc_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){tc_reset($(this).closest("tr"));});
			});
		}
		tc_calculation(tbody.parent());
	}	
	function tc_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#rate-'+id).on('change',function(){tc_calamt($(this).closest("tr"));});
			$('#qty_loss-'+id).on('change',function(){tc_calamt($(this).closest("tr"));});			
			$('#recovery_qty-'+id).on('change',function(){tc_calamt($(this).closest("tr"));});
		});
		var netamt = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#net_transit_loss').val(($.isNumeric(value))? value:'0.00');
		
		var actual_transp_charge = $('#actual_tc').val() - value;
		var actual_transp_charge = parseFloat(actual_transp_charge).toFixed(2);
		$('#transp_charge').val(($.isNumeric(actual_transp_charge))? actual_transp_charge:$('#actual_tc').val());
		
	};
	function tc_calamt(obj){
		var id = obj.closest("tr").attr('id');
		var rate = parseFloat($('#rate-'+id).val()).toFixed(2);
		var qty_loss = parseFloat($('#qty_loss-'+id).val()).toFixed(2);
		var recovery_qty = parseFloat($('#recovery_qty-'+id).val()).toFixed(2);
		
		rate = ($.isNumeric(rate))? rate:'0.00';
		qty_loss = ($.isNumeric(qty_loss))? qty_loss:'0.00';
		recovery_qty = ($.isNumeric(recovery_qty))? recovery_qty:'0.00';
		
		var actual_qty = qty_loss - recovery_qty;
		var amt = actual_qty * rate;
		
		$('#amount-'+id).val(parseFloat(amt).toFixed(2));
		var netamt = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#net_transit_loss').val(($.isNumeric(value))? value:'0.00');
		
		var actual_transp_charge = $('#actual_tc').val() - value;
		var actual_transp_charge = parseFloat(actual_transp_charge).toFixed(2);
		$('#transp_charge').val(($.isNumeric(actual_transp_charge))? actual_transp_charge:'0.00');
	}
	function tc_reset(obj){
		var id = obj.attr('id');
		$('#qty_loss-'+id).val('0.00');
		$('#rate-'+id).val('0.00');
		$('#recovery_qty-'+id).val('0.00');
		$('#amount-'+id).val('0.00');
		$('#item-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#batch-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#batch-'+id).trigger('chosen:updated');
		$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#uom-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		tc_calculation(obj.parent().parent());
	};
	
	/*
	 * Stock/TransporterController
	 * addtransporterinv --> Add Transporter Invoice
	 * edittransporterinv -->Edit Transporter Invoice
	 */	
	$.fn.transinv = function (){
		var tr_id =0;
		var parentObj = $(this);
		//parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		var netpamt = 0.00;
		var netamt = 0.00;
		var netdeduction = 0.00;
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });

	        var id = $(this).attr('id');
			$('#deduction-'+id).on('change',function(){
				tpinv_calamt($(this).closest("tr"));
			});
			
			$('#transp_status-'+id).on('change',function(){
				if($(this).prop('checked') == true){
					$('#total_amount').val(parseFloat(parseFloat($('#total_amount').val()) + parseFloat($('#amount-'+id).val())).toFixed(2));
					$('#total_deduction').val(parseFloat(parseFloat($('#total_deduction').val()) + parseFloat($('#deduction-'+id).val())).toFixed(2));
					$('#total_payable_amount').val(parseFloat(parseFloat($('#total_payable_amount').val()) + parseFloat($('#payable_amount-'+id).val())).toFixed(2));
				}else{
					$('#total_amount').val(parseFloat(parseFloat($('#total_amount').val()) - parseFloat($('#amount-'+id).val())).toFixed(2));	
					$('#total_deduction').val(parseFloat(parseFloat($('#total_deduction').val()) - parseFloat($('#deduction-'+id).val())).toFixed(2));
					$('#total_payable_amount').val(parseFloat(parseFloat($('#total_payable_amount').val()) - parseFloat($('#payable_amount-'+id).val())).toFixed(2));
				}
				
			});
			netpamt += parseFloat($('#payable_amount-'+id).val());
			var value = parseFloat(netpamt).toFixed(2);
			$('#total_payable_amount').val(($.isNumeric(value))? value:'0.00');

			netamt += parseFloat($('#amount-'+id).val());
			var value = parseFloat(netamt).toFixed(2);
			$('#total_amount').val(($.isNumeric(value))? value:'0.00');
			
			netdeduction += parseFloat($('#deduction-'+id).val());
			var value = parseFloat(netdeduction).toFixed(2);
			$('#total_deduction').val(($.isNumeric(value))? value:'0.00');
		});	
		$('#selectAll').on('change',function(){
			if($(this).prop('checked') == true){
				tpinv_checkAll(parentObj);
			}else{
				//alert("UNCHECKED");
				$('#total_amount').val('0.00');
				$('#total_deduction').val('0.00');
				$('#total_payable_amount').val('0.00');
			}
			
		});
	};
	function tpinv_calamt(obj){
		var id = obj.closest("tr").attr('id');
		var amount = parseFloat($('#amount-'+id).val()).toFixed(2);
		var deduction = parseFloat($('#deduction-'+id).val()).toFixed(2);
		var amt = amount - deduction;
		$('#payable_amount-'+id).val(parseFloat(amt).toFixed(2));

		var netpamt = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netpamt += parseFloat($('#payable_amount-'+id).val());
		});
		var value = parseFloat(netpamt).toFixed(2);
		$('#total_payable_amount').val(($.isNumeric(value))? value:'0.00');

		var netded = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netded += parseFloat($('#deduction-'+id).val());
		});
		var value = parseFloat(netded).toFixed(2);
		$('#total_deduction').val(($.isNumeric(value))? value:'0.00');
	}
	function tpinv_checkAll(obj){
		var netamt = 0;
		var netded = 0;
		var netpay = 0;
		obj.find("tbody tr").each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
			netded += parseFloat($('#deduction-'+id).val());
			netpay += parseFloat($('#payable_amount-'+id).val());
		});
		var tot_amount = parseFloat(netamt).toFixed(2);
		$('#total_amount').val(($.isNumeric(tot_amount))? tot_amount:'0.00');
		var tot_deduction = parseFloat(netded).toFixed(2);
		$('#total_deduction').val(($.isNumeric(tot_deduction))? tot_deduction:'0.00');
		var tot_payable_amount = parseFloat(netpay).toFixed(2);
		$('#total_payable_amount').val(($.isNumeric(tot_payable_amount))? tot_payable_amount:'0.00');
	}
/*##################################################################
  #  function to appen new row in transaction and do calculations  #
  ##################################################################*/	  
	$.fn.transaction = function (spec){
		//set default option
		var options = $.extend({
				src: '',
				tot_debit: 0,
				tot_credit: 0

		},spec);
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='margin-bottom:5px; overflow-x:scroll;' id='jscroll_div'> </div>");		
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        $(this).find('select.tr-head').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$('#sub_head-'+id).load(options.src+'/'+$(this).val(), function(){
						$('#sub_head-'+id).trigger('chosen:updated');
					});
				});
			});
			trns_delbtn(parentObj, options);
			if($('#voucher_type').val() == '15'){
				$('form').formValidation('removeField','total_debit');
				$('form').formValidation('removeField','total_credit');
				$('form').formValidation('removeField','voucher_amount');
				$('#total_debit').parent().remove();
				$('#total_credit').parent().remove();
				$('#voucher_amount').parent().remove();
			}
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div>";
			$(this).closest('.row').append(addbutton);
		};		
		$("#addRow").click(function(){
			trns_addrow(parentObj, options);			     
		});	
		trns_calculation(parentObj, options);
		$('#voucher_type').change(function(){
			if($(this).val() == '15'){
				var rowCount =0;
				parentObj.find('tbody tr').each(function(){
					if(++rowCount > 1){
						$(this).find(':input[data-fv-field]').each(function() {
							$('form').formValidation('removeField',$(this));
						});
						$(this).remove();
					}
				});
				$('form').formValidation('removeField','total_debit');
				$('form').formValidation('removeField','total_credit');
				$('form').formValidation('removeField','voucher_amount');
				$('#total_debit').parent().remove();
				$('#total_credit').parent().remove();
				$('#voucher_amount').parent().remove();
			}else{
				var rowCount = parentObj.find('tbody tr').length;
				if(rowCount < 2){
					location.reload();
				}
			}
		});	
		//$('#jscroll_div').jScrollPane();		
	};
	function trns_addrow(obj, options){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		trns_reset(clone, options);	        
		$('form select').chosen();
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		obj.find('select.tr-head').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$('#sub_head-'+id).load(options.src+'/'+$(this).val(), function(){
					$('#sub_head-'+id).trigger('chosen:updated');
				});
			});
		});
		trns_delbtn(obj, options);		
		trns_calculation(obj, options);
	};
	function trns_delbtn(obj, options){
		var trlenght = 1; /*($('#voucher_type').val() == 15)? 1:2;*/
		if(obj.find("tbody tr td select.tr-location").length > trlenght){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr td select.tr-location").closest('tr').each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){trns_delrow($(this).closest('tr'), options);});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr td select.tr-location").closest('tr').each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){trns_reset($(this).closest('tr'), options);});	
			});
		}		
	};
	function trns_delrow(obj, options){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.closest('tbody').find('tr td select.tr-location').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		var trlength = 2;/*($('#voucher_type').val() == 15)? 2:3;*/
		if(tr_length <= trlength){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td select.tr-location').closest('tr').each(function(){$(this).find('td:last').html(delBtn)});
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){trns_reset($(this).closest("tr"), options);});
			});
		}
		trns_calculation(tbody.parent(), options);
	}
	function trns_calculation(obj, options){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#debit-'+id).on('change',function(){
				var id = $(this).closest('tr').attr('id');
				var value = parseFloat($(this).val()).toFixed(2);
				$(this).val(value = ($.isNumeric(value))? value:'0.00');
				if(value > 0){
					$('#credit-'+id).val('0.00')
				}
				trns_totamt(obj, options);
			});
			$('#credit-'+id).on('change',function(){
				var id = $(this).closest('tr').attr('id');
				var value = parseFloat($(this).val()).toFixed(2);
				$(this).val(value = ($.isNumeric(value))? value:'0.00');
				if(value > 0){
					$('#debit-'+id).val('0.00')
				}
				trns_totamt(obj, options);
			});			
		});
		trns_totamt(obj, options);
	};
	
	function trns_totamt(obj, options){
		var netdebit = parseFloat(options.tot_debit);
		var netcredit = parseFloat(options.tot_credit);
		obj.find('tbody tr td select.tr-location').each(function(){
			var id = $(this).closest('tr').attr('id');
			netdebit += parseFloat($('#debit-'+id).val());
			netcredit += parseFloat($('#credit-'+id).val());
		});
		$('#total_debit').val(parseFloat(netdebit).toFixed(2));
		$('#total_credit').val(parseFloat(netcredit).toFixed(2));
		$('#voucher_amount').val(($('#total_debit').val() == $('#total_credit').val())? $('#total_credit').val():'0.00');
		
		$('form').find('input[data-fv-field="total_debit"]').each(function() {
			$('form').formValidation('revalidateField',$(this));
		});	
		$('form').find('input[data-fv-field="total_credit"]').each(function() {
			$('form').formValidation('revalidateField',$(this));
		});	
	};
	
	function trns_reset(obj, options){
		var id = obj.attr('id');
		$('#debit-'+id).val('0.00');
		$('#credit-'+id).val('0.00');
		$('#id-'+id).each(function(){$(this).val('');});// ** always remember to reset id required in updation
		$('#cheque_no-'+id).val('');
		$('#location-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#activity-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#activity-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#head-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#sub_head-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#sub_head-'+id).trigger('chosen:updated');
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		trns_calculation(obj.parent().parent(), options);
	} 
/*##################################################################
  #  function to appen new row while booking salary                #
  ##################################################################*/  
	$.fn.salarytransaction = function (spec){
		//set default option
		var options = $.extend({
				src: '',
				tot_debit: 0,
				tot_credit: 0
		},spec);
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='margin-bottom:5px; overflow-x:scroll;' id='jscroll_div'> </div>");		
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        $(this).find('select.tr-head').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$('#sub_head-'+id).load(options.src+'/'+$(this).val(), function(){
						$('#sub_head-'+id).trigger('chosen:updated');
					});
				});
			});
			//salary_delbtn(parentObj);
			if($('#voucher_type').val() == '15'){
				$('form').formValidation('removeField','total_debit');
				$('form').formValidation('removeField','total_credit');
				$('form').formValidation('removeField','voucher_amount');
				$('#total_debit').parent().remove();
				$('#total_credit').parent().remove();
				$('#voucher_amount').parent().remove();
			}
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div>";
			$(this).closest('.row').append(addbutton);
		};		
		$("#addRow").click(function(){
			salary_addrow(parentObj, options);			     
		});	
		salary_calculation(parentObj, options);
		$('#voucher_type').change(function(){
			if($(this).val() == '15'){
				var rowCount =0;
				parentObj.find('tbody tr').each(function(){
					if(++rowCount > 1){
						$(this).find(':input[data-fv-field]').each(function() {
							$('form').formValidation('removeField',$(this));
						});
						$(this).remove();
					}
				});
				$('form').formValidation('removeField','total_debit');
				$('form').formValidation('removeField','total_credit');
				$('form').formValidation('removeField','voucher_amount');
				$('#total_debit').parent().remove();
				$('#total_credit').parent().remove();
				$('#voucher_amount').parent().remove();
			}else{
				var rowCount = parentObj.find('tbody tr').length;
				if(rowCount < 2){
					location.reload();
				}
			}
		});	
		//$('#jscroll_div').jScrollPane();		
	};
	function salary_addrow(obj, options){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		salary_reset(clone, options);	        
		$('form select').chosen();
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		obj.find('select.tr-head').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$('#sub_head-'+id).load(options.src+'/'+$(this).val(), function(){
					$('#sub_head-'+id).trigger('chosen:updated');
				});
			});
		});
		
		salary_delbtn(obj, options);		
		salary_calculation(obj, options);
	};
	function salary_delbtn(obj, options){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr:last").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){salary_delrow($(this).closest('tr'), options);});	
			});
	};
	function salary_delrow(obj, options){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		
		salary_calculation(tbody.parent(), options);
	}
	function salary_calculation(obj, options){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#debit-'+id).on('change',function(){
				var id = $(this).closest('tr').attr('id');
				var value = parseFloat($(this).val()).toFixed(2);
				$(this).val(value = ($.isNumeric(value))? value:'0.00');
				if(value > 0){
					$('#credit-'+id).val('0.00')
				}
				salary_totamt(obj, options);
			});
			$('#credit-'+id).on('change',function(){
				var id = $(this).closest('tr').attr('id');
				var value = parseFloat($(this).val()).toFixed(2);
				$(this).val(value = ($.isNumeric(value))? value:'0.00');
				if(value > 0){
					$('#debit-'+id).val('0.00')
				}
				salary_totamt(obj, options);
			});			
		});
		salary_totamt(obj, options);
	};
	
	function salary_totamt(obj, options){
		var netdebit = parseFloat(options.tot_debit);
		var netcredit = parseFloat(options.tot_credit);
		obj.find('tbody tr td select.tr-location').each(function(){
			var id = $(this).closest('tr').attr('id');
			netdebit += parseFloat($('#debit-'+id).val());
			netcredit += parseFloat($('#credit-'+id).val());
		});
		$('#total_debit').val(parseFloat(netdebit).toFixed(2));
		$('#total_credit').val(parseFloat(netcredit).toFixed(2));
		$('#voucher_amount').val(($('#total_debit').val() == $('#total_credit').val())? $('#total_credit').val():'0.000');
		
		$('form').find('input[data-fv-field="total_debit"]').each(function() {
			$('form').formValidation('revalidateField',$(this));
		});	
		$('form').find('input[data-fv-field="total_credit"]').each(function() {
			$('form').formValidation('revalidateField',$(this));
		});	
	};
	
	function salary_reset(obj, options){
		var id = obj.attr('id');
		$('#debit-'+id).val('0.000');
		$('#credit-'+id).val('0.000');
		$('#cheque_no-'+id).val('');
		$('#location-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#activity-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#activity-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#head-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#sub_head-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#sub_head-'+id).trigger('chosen:updated');
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		salary_calculation(obj.parent().parent(), options);
	}

/*##################################################################
  #  function to appen new row for adding subhead details          #
  ##################################################################*/
	$.fn.subhead = function (spec){
		//set default option
		var options = $.extend({
				src: '',
		},spec);
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='margin-bottom:5px; overflow-x:scroll;' id='jscroll_div'> </div>");		
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        $(this).find('select.tr-head_type').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$('#head-'+id).load(options.src+'/'+$(this).val(), function(){
						$('#head-'+id).trigger('chosen:updated');
					});
				});
			});
			sh_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div>";
			$(this).closest('.row').append(addbutton);
		};		
		$("#addRow").click(function(){
			sh_addrow(parentObj, options.src);			     
		});			
		//$('#jscroll_div').jScrollPane();		
	};
	function sh_addrow(obj, src){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		sh_reset(clone);	        
		$('form select').chosen();
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		obj.find('select.tr-head_type').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$('#head-'+id).load(src+'/'+$(this).val(), function(){
					$('#head-'+id).trigger('chosen:updated');
				});
			});
		});
		sh_delbtn(obj);	
	};
	function sh_delbtn(obj){
		var trlenght = 1;
		if(obj.find("tbody tr").length > trlenght){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){sh_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){sh_reset($(this).closest('tr'));});	
			});
		}		
	};
	function sh_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		var trlenght = 2;
		if(tr_length <= trlenght){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr').each(function(){$(this).find('td:last').html(delBtn)});
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){sh_reset($(this).closest("tr"));});
			});
		}
	}	
	function sh_reset(obj){
		var id = obj.attr('id');
		$('#head_type-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#head-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#head-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
	}		

	/*##################################################################
	   #             Batch Add Delete Rows		                      #
	##################################################################*/
	$.fn.batch = function (){
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        $(this).find('select.tr-item').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$('#uom-'+id).load(options.src+'/'+$(this).val(), function(){
						$('#uom-'+id).trigger('chosen:updated');
					});
				});
			});
			batch_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			batch_addrow(parentObj);			     
		});			
	};	
	function batch_addrow(obj){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		batch_reset(clone);	        
		$('form select').chosen({ allow_single_deselect: true });
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		batch_delbtn(obj);
	};
	function batch_reset(obj){
		var id = obj.attr('id');
		$('#detail_quantity-'+id).val('');
		$('#stock_balance-'+id).val('');
		$('#detail_landed_cost-'+id).val('0.000');
		$('#retail_price-'+id).val('0.000');
		$('#wholesale_price-'+id).val('0.000');
		$('#location-'+id).val('selectedIndex',0).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});
	}
	function batch_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){batch_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){batch_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function batch_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){batch_reset($(this).closest("tr"));});
			});
		}
	};
	
	/*
	 * Sales/SchemeController
	 * addsdetails -> Add new Scheme
	 * editsdetails -> Edit Scheme
	 */
	$.fn.schemej = function (spec){
		//set default option
		var options = $.extend({
				getSubhead: '',
				getItem: '',
				getItemUom: '',
		},spec);
		$('#head').change(function(){
			$('#sub_head').load(options.getSubhead+'/'+$(this).val(),function(){
				$('#sub_head').trigger('chosen:updated');
			});
		});
		$("#activity").change(function() {
			$.post(
				options.getItem,
				{
					activity: $(this).val(),
				},
				function(data){
					//console.log(data);
					$('#item').html(data.items);
					$('#item').trigger('chosen:updated');
					$(".tr-free_item").html(data.items);
					$('.tr-free_item').trigger('chosen:updated');
				},
				'json'
			);
			$(".tr-item").each(function(){
				po_reset($(this).closest('tr'));
			});
		});
		$("#item").change(function() {
			$.post(
				options.getItemUom,
				{
					item: $(this).val(),
				},
				function(data){
					//console.log(data);
					$(".tr-uom").html(data.uoms);
					$('.tr-uom').trigger('chosen:updated');
				},
				'json'
			);
		});
		var tr_id = 0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        $(this).find('select.tr-free_item').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$.post(
						options.getItemUom,
						{
							item: $(this).val(),
						},
						function(data){
							//console.log(data);
							$('#free_item_uom-'+id).html(data.uoms);
							$('#free_item_uom-'+id).trigger('chosen:updated');
						},
						'json'
					);
				});
			});
			sc_schemedtls();
			sc_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			sc_addrow(parentObj, options.getItemUom);			     
		});
		if($("#scheme_type").val() > 6){
			$('#addRow').hide();
		}else{
			$('#addRow').show();
		}
		$("#scheme_type").on('change', function(){
			if($(this).val() > 6){
				$('#addRow').hide();
			}else{
				$('#addRow').show();
			}
		});
	    //sc_delbtn(obj);
	};
	function sc_schemedtls(){
		$('#scheme_type').change(function(){
			var schemetype = $(this).val();
			switch(schemetype){
				case "1": 
					$('.unit_qty_sc').addClass('hidden');
					$('.qty_slab_sc').removeClass('hidden');
					$('.free_item_sc').removeClass('hidden');
					$('.unit_qty_sc').find('input').each(function(){
						$(this).val('');
						$(this).attr('disabled','disabled');
					});
					$('.qty_slab_sc').find('input').each(function(){
						$(this).removeAttr('disabled');
					});
					$('.free_item_sc').find('select').each(function(){
						$(this).removeAttr('disabled');
					});
				break;
				case "2":
				case "3":
					$('.unit_qty_sc').addClass('hidden');
					$('.free_item_sc').addClass('hidden');
					$('.qty_slab_sc').removeClass('hidden');
					$('.unit_qty_sc').find('input').each(function(){
						$(this).val('');
						$(this).attr('disabled','disabled');
					});
					$('.free_item_sc').find('select').each(function(){
						$(this).val('');
						$(this).attr('disabled','disabled');
					});
					$('.qty_slab_sc').find('input').each(function(){
						$(this).removeAttr('disabled');
					});
				break;
				case "4": 
					$('.unit_qty_sc').addClass('hidden');
					$('.qty_slab_sc').removeClass('hidden');
					$('.free_item_sc').removeClass('hidden');
					$('.unit_qty_sc').find('input').each(function(){
						$(this).val('');
						$(this).attr('disabled','disabled');
					});
					$('.qty_slab_sc').find('input').each(function(){
						$(this).removeAttr('disabled');
					});
					$('.free_item_sc').find('select').each(function(){
						$(this).removeAttr('disabled');
					});
				break;
				case "5":
				case "6":
					$('.unit_qty_sc').addClass('hidden');
					$('.free_item_sc').addClass('hidden');
					$('.qty_slab_sc').removeClass('hidden');
					$('.unit_qty_sc').find('input').each(function(){
						$(this).val('');
						$(this).attr('disabled','disabled');
					});
					$('.free_item_sc').find('select').each(function(){
						$(this).val('');
						$(this).attr('disabled','disabled');
					});
					$('.qty_slab_sc').find('input').each(function(){
						$(this).removeAttr('disabled');
					});
				break;
				case "7":
					$('.qty_slab_sc').addClass('hidden');
					$('.free_item_sc').removeClass('hidden');
					$('.unit_qty_sc').removeClass('hidden');
					$('.qty_slab_sc').find('input').each(function(){
						$(this).val('');
						$(this).attr('disabled','disabled');
					});
					$('.unit_qty_sc').find('input').each(function(){
						$(this).removeAttr('disabled');
					});
					$('.free_item_sc').find('select').each(function(){
						$(this).removeAttr('disabled');
					});
				break;
				case "8":
				case "9":
					$('.qty_slab_sc').addClass('hidden');
					$('.free_item_sc').addClass('hidden');
					$('.unit_qty_sc').removeClass('hidden');
					$('.qty_slab_sc').find('input').each(function(){
						$(this).val('');
						$(this).attr('disabled','disabled');
					});
					$('.free_item_sc').find('select').each(function(){
						$(this).val('');
						$(this).attr('disabled','disabled');
					});
					$('.unit_qty_sc').find('input').each(function(){
						$(this).removeAttr('disabled');
					});
				break;
			}
		});
	}
	function sc_addrow(obj, getItemUom){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		sc_reset(clone);	        
		$('form select').chosen({ allow_single_deselect: true });
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-free_item').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$.post(
					getItemUom,
					{
						item: $(this).val(),
					},
					function(data){
						//console.log(data);
						$('#free_item_uom-'+id).html(data.uoms);
						$('#free_item_uom-'+id).trigger('chosen:updated');
					},
					'json'
				);
			});
		});
		sc_delbtn(obj);
	};	
	function sc_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){sc_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){sc_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function sc_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){sc_reset($(this).closest("tr"));});
			});
		}
	}		
	function sc_reset(obj){
		var id = obj.attr('id');
		$('#qty_slab1-'+id).val('');
		$('#qty_slab2-'+id).val('');
		$('#discount_qty-'+id).val('');
		$('#unit_quantity-'+id).val('');
		$('#free_item_uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#free_item_uom-'+id).trigger('chosen:updated');
		//$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		//$('#uom-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});
	}

	/*
	 * Stock/FormulasheetController
	 * add --> Add Formula sheet
	 * edit --> Edit Formula sheet
	 */
		$.fn.formulasheet = function (spec){	
			var options = $.extend({
				getFormulaCode:'',
				},spec);
			var tr_id =0;
			var parentObj = $('#trdetails_table');
			parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
			parentObj.find("tbody tr").each(function(){
				$(this).attr('id',++tr_id);			
				$(this).find('div.chosen-search input').each(function(){
					$(this).addClass('ch-search');
				});
				$(this).find('div.chosen-container .chosen-drop').each(function(){
					$(this).css({'position':'relative'});
				});
				$(this).find(':input').not('input.ch-search').each(function(){
		            var old_id = $(this).attr('id');
		            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
		            $(this).attr('id',new_id+'-'+tr_id);
		            $(this).addClass('tr-'+new_id);
		        });			
				
				$(this).find('select.tr-costing_head').each(function(){
					var id = $(this).closest('tr').attr('id');
					$(this).bind('change', function(){						
						$.post(
								options.getFormulaCode,
								{
									costing_head: $(this).val(),								
								},								
								function(data){
									//console.log(data);
									$('#code-'+id).val(data);
									$('#order-'+id).val(id);									
								}
							);
					});
				});
				fs_delbtn(parentObj);
			});
			if($('#addRow').length == 0){
				var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
				parentObj.parent().parent().parent().append(addbutton);
			};		
			$("#addRow").click(function(){
				fs_addrow(parentObj, options.getFormulaCode);			     
			});
		};
		function fs_addrow(obj,getFormulaCode){			
			var lastRow = obj.find("tbody tr:last").attr('id');
			var tmplt_row = obj.find("tbody tr:last").clone(true);
			clone = tmplt_row.clone();		
			clone.find('div.chosen-container').each(function(){
				$(this).remove();
			});
			clone.attr('id',++lastRow);
			clone.find(':input').each(function(){
				var old_id = $(this).attr('id');
				var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
				$(this).attr('id',new_id+'-'+lastRow);
				$(this).addClass('tr-'+new_id);
				$('form').formValidation('addField', $(this));
			});
			$('#'+obj.attr('id')+' tbody').append(clone);
			fs_reset(clone);			
			$('form select').chosen({ allow_single_deselect: true });
			clone.find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			clone.find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			
			clone.find('select.tr-costing_head').each(function(){				
				var id = $(this).closest('tr').attr('id');				
				$(this).bind('change', function(){
					$.post(
							getFormulaCode,
							{
								costing_head: $(this).val(),								
							},							
							function(data){
								console.log(data);
								$('#code-'+id).val(data);
								$('#order-'+id).val(id);								
							}
						);
				});
			});
			
			fs_delbtn(obj);
		};	
		function fs_delbtn(obj){
			if(obj.find("tbody tr").length > 1){
				delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
				obj.find("tbody tr").each(function(){
					$(this).find("td:last").html(delBtn);			
				});
				obj.find("td a.delRow").each(function(){
					$(this).click(function(){fs_delrow($(this).closest('tr'));});	
				});
			}else{
				delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
				obj.find("tbody tr").each(function(){
					var las_td = $(this).find("td:last").html(delBtn);
				});
				obj.find("td a.resetRow").each(function(){
					$(this).click(function(){fs_reset($(this).closest('tr'));});	
				});
			}		
		};	
		function fs_delrow(obj){
			obj.find(':input[data-fv-field]').each(function() {
				//console.log($(this));
				$('form').formValidation('removeField', $(this));
			});
			var tr_length = obj.parent().find('tr').length;
			var tbody = obj.parent();
			obj.closest("tr").remove();	
			if(tr_length == 2){
				var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
				tbody.find('tr td:last').html(delBtn);
				tbody.find('tr td:last a.resetRow').each(function(){
					$(this).click(function(){fs_reset($(this).closest("tr"));});
				});
			}
		}	
		//Reset Button
		function fs_reset(obj){
			//alert('reset function');
			var id = obj.attr('id');
			$('#code-'+id).val('');
			$('#order-'+id).val('');
			$('#formula-'+id).val('');		
			obj.find(':input:hidden').each(function(){$(this).val('');});
			obj.find(':input[data-fv-field]').each(function() {
				$('form').formValidation('resetField',$(this));
			});	
		};

	/*
	 * Stock/SamController
	 * addstockadjust --> Add Adjustment
	 * editadjustment --> Edit Adjustment
	 */
		$.fn.stockadjust = function (spec){
			var options = $.extend({
					getItems:'',
					getitemsdtls:'',
					getStockBalance:'',
					getBasicQty:'',
					getConvertedQty:'',
			},spec);
			
			$("#activity").change(function() {
				$(".tr-item").load(options.getItems+'/'+ $("#activity").val(), function() {
					$(".tr-item").trigger("chosen:updated");
				});
			});
			var tr_id =0;
			var parentObj = $('#trdetails_table');
			parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
			parentObj.find("tbody tr").each(function(){
				$(this).attr('id',++tr_id);			
				$(this).find('div.chosen-search input').each(function(){
					$(this).addClass('ch-search');
				});
				$(this).find('div.chosen-container .chosen-drop').each(function(){
					$(this).css({'position':'relative'});
				});
				$(this).find(':input').not('input.ch-search').each(function(){
		            var old_id = $(this).attr('id');
		            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
		            $(this).attr('id',new_id+'-'+tr_id);
		            $(this).addClass('tr-'+new_id);
		        });				
				$(this).find('select.tr-item').each(function(){
					$(this).bind('change', function(){
						if($('#location').val()==""){
							$('form').formValidation('revalidateField',$('#location'));
						}
						var id = $(this).closest('tr').attr('id');
						//var cur_val = $(this).val();
						//$.when(
							$.post(
								options.getitemsdtls,
								{
									item_id: $(this).val(),
									source_loc: $('#location').val(),
								},
								function(data){
									//console.log(data);
									$('#batch-'+id).html(data.batch);
									$('#batch-'+id+' option:selected').removeAttr('selected');
									$('#batch-'+id+' option[value='+data.latest_batch+']').attr('selected', 'selected');
									$('#batch-'+id).trigger('chosen:updated');
									$('#uom-'+id).html(data.uom);
									$('#uom-'+id+' option:selected').removeAttr('selected');
									$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
									$('#uom-'+id).trigger('chosen:updated');
									$('#from_balance-'+id).val(data.source_qty);
									$('#basic_uom-'+id).html(data.basic_uom);
									$('#basic_uom-'+id).trigger('chosen:updated');
									$('form').formValidation('revalidateField',$('#from_balance-'+id));
								},
								'json'
							)
						/*).done(function(){
							var cur_obj = $('#item-'+id).closest('tr');	
							$(document).find('select.tr-item').not('#item-'+id).each(function(){
								if(cur_val == $(this).val()){
									dsp_reset(cur_obj);
								}
							});
						});*/
					});
			    });
	            $(this).find('select.tr-batch').each(function(){
					$(this).bind('change', function(){					
						var id = $(this).closest('tr').attr('id');
						if($('#location').val()==""){
							$('form').formValidation('revalidateField',$('#location'));
						}
						$.post(
							options.getStockBalance,
							{
								batch_id: $(this).val(),
								source_loc: $('#location').val(),
								item_id: $('#item-'+id).val(),
							},
							function(data){
								console.log(data);
								$('#uom-'+id+' option:selected').removeAttr('selected');
								$('#uom-'+id+' option[value='+data.batch_uom+']').prop('selected', 'selected');
								$('#uom-'+id).trigger('chosen:updated');
								$('#from_balance-'+id).val(data.source_qty);
							},
							'json'
						);
					});
				});
                $(this).find('select.tr-uom').each(function(){	        	
	        	$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					$.post(
						options.getConvertedQty,
						{	
							uom_id: $(this).val(),
							item_id: $('#item-'+id).val(),
							batch_id: $('#batch-'+id).val(),
							source_loc: $('#location').val(),
							sam_qty: $('#quantity-'+id).val(),
						},
						function(data){
							console.log(data);
							$('#from_balance-'+id).val(data.source_qty);
							$('#basic_quantity-'+id).val(data.sam_basic_qty);
						},
						'json'
					   );
				    });
			   });				
                $(this).find('.tr-quantity').each(function(){
				$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					$.post(
						options.getBasicQty,
						{	
							sam_qty: $(this).val(),
							item_id: $('#item-'+id).val(),
							uom_id: $('#uom-'+id).val(),
						},
						function(data){
							//console.log(data);
							$('#basic_quantity-'+id).val(data.basic_qty);
						},
						'json'
					);
				});
			});				
		sam_delbtn(parentObj);
	});
	if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			sam_addrow(parentObj, options.getItems,options.getitemsdtls, options.getStockBalance,options.getConvertedQty,options.getBasicQty);			     
		});				
	};	
	function sam_addrow(obj, getItems,getitemsdtls, getStockBalance,getConvertedQty,getBasicQty){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		sam_reset(clone);	        
		$('form select').chosen();
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-item').each(function(){
			$(this).bind('change', function(){
				if($('#location').val()==""){
					$('form').formValidation('revalidateField',$('#location'));
				}
				var id = $(this).closest('tr').attr('id');
				//var cur_val = $(this).val();
				//$.when(
					$.post(
						getitemsdtls,
						{
							item_id: $(this).val(),
							source_loc: $('#location').val(),
						},
						function(data){
							//console.log(data);
							$('#batch-'+id).html(data.batch);
							$('#batch-'+id+' option:selected').removeAttr('selected');
							$('#batch-'+id+' option[value='+data.latest_batch+']').attr('selected', 'selected');
							$('#batch-'+id).trigger('chosen:updated');
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
							$('#uom-'+id).trigger('chosen:updated');
							$('#from_balance-'+id).val(data.source_qty);
							$('#basic_uom-'+id).html(data.basic_uom);
							$('#basic_uom-'+id).trigger('chosen:updated');
							$('form').formValidation('revalidateField',$('#from_balance-'+id));
						},
						'json'
					)
				/*).done(function(){
					var cur_obj = $('#item-'+id).closest('tr');	
					$(document).find('select.tr-item').not('#item-'+id).each(function(){
						if(cur_val == $(this).val()){
							dsp_reset(cur_obj);
						}
					});
				});*/
			});
		});
		clone.find('select.tr-batch').each(function(){
			$(this).bind('change', function(){					
				var id = $(this).closest('tr').attr('id');
				if($('#location').val()==""){
					$('form').formValidation('revalidateField',$('#location'));
				}
				$.post(
					getStockBalance,
					{
						batch_id: $(this).val(),
						source_loc: $('#location').val(),
						item_id: $('#item-'+id).val(),
					},
					function(data){
						//console.log(data);
						$('#uom-'+id+' option:selected').removeAttr('selected');
					    $('#uom-'+id+' option[value='+data.batch_uom+']').prop('selected', 'selected');
						$('#uom-'+id).trigger('chosen:updated');
						$('#from_balance-'+id).val(data.source_qty);
					},
					'json'
				);
			});
		});
		clone.find('select.tr-uom').each(function(){	        	
        	$(this).bind('change', function(){
				var id = $(this).closest('tr').attr('id');
				$.post(
					getConvertedQty,
					{	
						uom_id: $(this).val(),
						item_id: $('#item-'+id).val(),
						batch_id: $('#batch-'+id).val(),
						source_loc: $('#location').val(),
						sam_qty: $('#quantity-'+id).val(),
					},
					function(data){
						//console.log(data);
						$('#from_balance-'+id).val(data.source_qty);
						$('#basic_quantity-'+id).val(data.sam_basic_qty);
					},
					'json'
				);
			});
		});
		clone.find('.tr-quantity').each(function(){
			$(this).bind('change', function(){
				var id = $(this).closest('tr').attr('id');
				$.post(
					getBasicQty,
					{	
						sam_qty: $(this).val(),
						item_id: $('#item-'+id).val(),
						uom_id: $('#uom-'+id).val(),
					},
					function(data){
						//console.log(data);
						$('#basic_quantity-'+id).val(data.basic_qty);
					},
					'json'
				);
			});
		});
		sam_delbtn(obj);
	};	
	function sam_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){dsp_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){sam_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function dsp_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){sam_reset($(this).closest("tr"));});
			});
		}
	}
	function sam_reset(obj){
		
		var id = obj.attr('id');
		$('#batch-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#batch-'+id).trigger('chosen:updated');
		$('#uom-'+id+' option:selected').removeAttr('selected');
		$('#uom-'+id).trigger('chosen:updated');
		$('#item-'+id+' option:selected').removeAttr('selected');
		$('#item-'+id).trigger('chosen:updated');
		$('#from_balance-'+id).val('0.00');
		$('#basic_uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#basic_uom-'+id).trigger('chosen:updated');
		$('#basic_quantity-'+id).val('0.00');
		$('#quantity-'+id).val('0.00');
		$('#remarks-'+id).val('');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
		$('form').formValidation('resetField',$(this));
		});	
	}
	
/*
 * Stock/GoodsrequestController
 * addgoodsrequest --> Add Goods Request
 * editgoodsrequest --> Edit Goods Request
 */
	$.fn.goodsrequest = function (spec){
		var options = $.extend({
				getItems:'',
				getUomStockQty: '',
				getUomChange: '',
		},spec);
		
		$("#activity").change(function() {
			$(".tr-item").load(options.getItems+'/'+ $("#activity").val(), function() {
				$(".tr-item").trigger("chosen:updated");
				$('.tr-uom').html('<option value=""></option>');
				$('.tr-uom').trigger("chosen:updated");
				$('.tr-stock_qty').val('0.00');
				$('.tr-quantity').val('0.00');
			});
		});
		$("#location").change(function() {
			$(".tr-item").load(options.getItems+'/'+ $("#activity").val(), function() {
				$(".tr-item").trigger("chosen:updated");
				$('.tr-uom').html('<option value=""></option>');
				$('.tr-uom').trigger("chosen:updated");
				$('.tr-stock_qty').val('0.00');
				$('.tr-quantity').val('0.00');
			});
		});
		var tr_id =0;
		var parentObj = $('#trdetails_table');
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			$(this).find('select.tr-item').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					if($('#location').val()=="" || $('#activity').val()==""){
						$('form').formValidation('revalidateField',$('#location'));
						$('form').formValidation('revalidateField',$('#activity'));
					}
					$.post(
						options.getUomStockQty,
						{
							item_id: $(this).val(),
							location: $('#location').val(),
						},
						function(data){
							//console.log(data);
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id+' option[value='+data.ba_mov_uom+']').prop('selected', 'selected');
							$('#uom-'+id).trigger('chosen:updated');
							$('#stock_qty-'+id).val(data.qty);
						},
						'json'
					);
				});
			});
			$(this).find('select.tr-uom').each(function(){
				$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					//alert($(this).val());
					$.post(
						options.getUomChange,
						{
							item_id: $('#item-'+id).val(),
							uom_id: $(this).val(),
							location: $('#location').val(),
						},
						function(data){
							//console.log(data);
							$('#stock_qty-'+id).val(data.qty);
						},
						'json'
					);	
				});
			});
			gr_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			parentObj.parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			gr_addrow(parentObj,options.getUomStockQty,options.getUomChange);			     
		});
	};
	function gr_addrow(obj,getUomStockQty,getUomChange){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		var retainVal = clone.find(':input#po_no-'+lastRow).val();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		gr_reset(clone);
		clone.find(':input#po_no-'+lastRow+' option').filter(function(){
			return $(this).val()==retainVal;
		}).prop('selected', true);
		$('form select').chosen({ allow_single_deselect: true });
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		
		clone.find('select.tr-item').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				if($('#location').val()=="" || $('#activity').val()==""){
					$('form').formValidation('revalidateField',$('#location'));
					$('form').formValidation('revalidateField',$('#activity'));
				}
				$.post(
					getUomStockQty,
					{
						item_id: $(this).val(),
						location: $('#location').val(),
					},
					function(data){
						//console.log(data);
						$('#uom-'+id).html(data.uom);
						$('#uom-'+id+' option:selected').removeAttr('selected');
						$('#uom-'+id+' option[value='+data.ba_mov_uom+']').prop('selected', 'selected');
						$('#uom-'+id).trigger('chosen:updated');
						$('#stock_qty-'+id).val(data.qty);
					},
					'json'
				);
			});
		});
		clone.find('select.tr-uom').each(function(){
			$(this).bind('change', function(){
				var id = $(this).closest('tr').attr('id');
				//alert($(this).val());
				$.post(
					getUomChange,
					{
						item_id: $('#item-'+id).val(),
						uom_id: $(this).val(),
						location: $('#location').val(),
					},
					function(data){
						//console.log(data);
						$('#stock_qty-'+id).val(data.qty);
					},
					'json'
				);	
			});
		});
		gr_delbtn(obj);
	};	
	function gr_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){gr_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){gr_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function gr_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			//console.log($(this));
			$('form').formValidation('removeField', $(this));
		});
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){gr_reset($(this).closest("tr"));});
			});
		}
	}	
	//Reset Button
	function gr_reset(obj){
		//alert('reset function');
		var id = obj.attr('id');
		$('#quantity-'+id).val('0.00');
		$('#stock_qty-'+id).val('0.00');
		$('#item-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#uom-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
	};
	
	/**********### STORE MODULE ###**********/
	/*
	 * Store/PurchaseController
	 * addporder -->Add Purchase Order
	 * editporder -->Edit Purchase Order
	 */
	$.fn.inporder = function (spec){
		var options = $.extend({
			getItem:'',
			getUom: '',
		},spec);
		//alert(options);//exit;
		$("#group").change(function() {
		//alert($('select#group').val());
			$.post(
				options.getItem,
				{
					group_id: $('select#group').val(),
				},
				function(data){
					//console.log(data);
					$(".tr-item").html(data.stock_items);
					$(".tr-item").trigger('chosen:updated');
				},
				'json'
			);	
			$(".tr-item").each(function(){
				po_reset($(this).closest('tr'));
			});
		});
		
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        $(this).find('select.tr-item').each(function(){
				//var id = $(this).closest('tr').attr('id');
				$(this).bind('change',function(){
					var id = $(this).closest('tr').attr('id');
					var cur_val = $(this).val();
					$.when(
						$.post(
							options.getUom,
							{
								item_id: $(this).val(),
							},
							function(data){
								console.log(data);
								$('#uom-'+id).html(data.uom);
								$('#uom-'+id).trigger('chosen:updated');
							},
							'json'
						)
					).done(function(){
						var cur_obj = $('#item-'+id).closest('tr');	
						$(document).find('select.tr-item').not('#item-'+id).each(function(){
							if(cur_val == $(this).val()){
								po_reset(cur_obj);
							}
						});
					});
				});
			});
			$(this).find('input.tr-quantity').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					$("#quantity-"+id).attr("quantity", $(this).val());
				});
			});
			$(this).find('input.tr-rate').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					$("#rate-"+id).attr("rate", $(this).val());
				});
			});
			po_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			po_addrow(parentObj, options.getUom);			     
		});	
		po_calculation(parentObj);			
	};	
	function po_addrow(obj, getUom){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		po_reset(clone);	        
		$('form select').chosen();//{ allow_single_deselect: true }
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-item').each(function(){
			//var id = $(this).closest('tr').attr('id');
			$(this).bind('change',function(){
				var id = $(this).closest('tr').attr('id');
				var cur_val = $(this).val();
				$.when(
					$.post(
						getUom,
						{
							item_id: $(this).val(),
						},
						function(data){
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id).trigger('chosen:updated');
						},
						'json'
					)
				).done(function(){
					var cur_obj = $('#item-'+id).closest('tr');	
					$(document).find('select.tr-item').not('#item-'+id).each(function(){
						if(cur_val == $(this).val()){
							po_reset(cur_obj);
						}
					});
				});
			});
		});
		clone.find('input.tr-quantity').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#quantity-"+id).attr("quantity", $(this).val());
			});
		});
		clone.find('input.tr-rate').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#rate-"+id).attr("rate", $(this).val());
			});
		});
		po_delbtn(obj);		
		po_calculation(obj);
	};	
	function po_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){po_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){po_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function po_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){po_reset($(this).closest("tr"));});
			});
		}
		po_calculation(tbody.parent());
	}	
	function po_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#rate-'+id).on('change',function(){po_calamt($(this).closest("tr"));});
			$('#quantity-'+id).on('change',function(){po_calamt($(this).closest("tr"));});			
		});
		var netamt = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#po_amount').val(($.isNumeric(value))? value:'0.00');
	};
	function po_calamt(obj){
		var id = obj.closest("tr").attr('id');
		var rate = parseFloat($('#rate-'+id).val());
		var quantity = parseFloat($('#quantity-'+id).val());
		var amt = rate*quantity;
		$('#amount-'+id).val(parseFloat(amt).toFixed(2));
				var netamt = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#po_amount').val(($.isNumeric(value))? value:'0.00');
	}		
	function po_reset(obj){
		var id = obj.attr('id');
		$('#remarks-'+id).val('');
		$('#uom-'+id).val('');
		$('#quantity-'+id).val('0.00');
		$('#rate-'+id).val('0.00');
		$('#amount-'+id).val('0.00');
		$("#quantity-"+id).attr("quantity", '0.000');
		$("#rate-"+id).attr("rate", '0.000');
		$("#uom-"+id).attr("input_uom", '0.000');
		$('#item-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#uom-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		po_calculation(obj.parent().parent());
	}
	
	/*
	 * Store/IssueController
	 * addissue 	-->Add Goods issue
	 * editissue -->Edit Goods issue
	 */
	$.fn.issue = function (spec){
		//set default option
		var options = $.extend({
				getItem: '',
				getDetails: '',
				getStockBalance: '',
		},spec);
		/***** Get the Item of the Activity *****/
		$("#item_group").change(function() {
			$.post(
				options.getItem,
				{
					item_group: $(this).val(),
                    source_loc: $('#from_location').val(),
				},
				function(data){
					//console.log(data);
					$('.tr-item').html(data.items);
					$('.tr-item').trigger('chosen:updated');
				},
				'json'
			);
			$(".tr-item").each(function(){
				issue_reset($(this).closest('tr'));
			});
		});
		/**** Get Source and Destination Quantities ***/
		$('#from_location,#to_location').change(function(){
			var id = 1;
			$.post(
				options.getStockBalance,
				{
					assetssp_id: $('#assetssp-'+id).val(),
					source_loc: $('#from_location').val(),
					destination_loc: $('#to_location').val(),
					item_id: $('#item-'+id).val(),
				},
				function(data){
					//console.log(data);
					$('#uom-'+id).html(data.uom);
					$('#uom-'+id).trigger('chosen:updated');
					$('#rate-'+id).val(data.rate);
					$('#from_balance-'+id).val(data.source_qty);
					$('#to_balance-'+id).val(data.destination_qty);
					$('form').formValidation('revalidateField',$('#assetssp-'+id));
				},
				'json'
			);
		});
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			$(this).find('select.tr-item').each(function(){
				$(this).bind('change', function(){
					if($('#from_location').val()=="" || $('#to_location').val()==""){
						$('form').formValidation('revalidateField',$('#from_location'));
						$('form').formValidation('revalidateField',$('#to_location'));
					}
				    var id = $(this).closest('tr').attr('id')
					$.post(
						options.getDetails,
						{
							item_id: $(this).val(),
							source_loc: $('#from_location').val(),
							destination_loc: $('#to_location').val(),
						},
						function(data){
							$('#assetssp-'+id).html(data.assetssp);
							$('#assetssp-'+id+' option:selected').removeAttr('selected');
							$('#assetssp-'+id).trigger('chosen:updated');
							$('form').formValidation('revalidateField',$('#assetssp-'+id));
						},
						'json'
					)
				});
			});
	        $(this).find('select.tr-assetssp').each(function(){
				$(this).bind('change', function(){
					var id = $(this).closest('tr').attr('id');
					if($('#from_location').val()=="" || $('#to_location').val()==""){
						$('form').formValidation('revalidateField',$('#from_location'));
						$('form').formValidation('revalidateField',$('#to_location'));
					}
					$.post(
						options.getStockBalance,
						{
							assetssp_id: $(this).val(),
							source_loc: $('#from_location').val(),
							destination_loc: $('#to_location').val(),
							item_id: $('#item-'+id).val(),
						},
						function(data){
							//console.log(data);
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id).trigger('chosen:updated');
							$('#rate-'+id).val(data.rate);
							$('#from_balance-'+id).val(data.source_qty);
							$('#to_balance-'+id).val(data.destination_qty);
						},
						'json'
					);
				});
			});
	        
			issue_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			issue_addrow(parentObj, options.getDetails, options.getStockBalance);			     
		});	
        issue_calculation(parentObj);					
	};	
	function issue_addrow(obj, getDetails, getStockBalance){
		
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		issue_reset(clone);	        
		$('form select').chosen();
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-item').each(function(){
		$(this).bind('change', function(){
			if($('#from_location').val()=="" || $('#to_location').val()==""){
				$('form').formValidation('revalidateField',$('#from_location'));
				$('form').formValidation('revalidateField',$('#to_location'));
			}
			var id = $(this).closest('tr').attr('id');
				$.post(
					getDetails,
					{
						item_id: $(this).val(),
						source_loc: $('#from_location').val(),
						destination_loc: $('#to_location').val(),
					},
					function(data){
						//console.log(data);
						$('#assetssp-'+id).html(data.assetssp);
						$('#assetssp-'+id+' option:selected').removeAttr('selected');
						$('#assetssp-'+id).trigger('chosen:updated');
					},
					'json'
				)
			});
		});
		clone.find('select.tr-assetssp').each(function(){
		$(this).bind('change', function(){					
			if($('#from_location').val()=="" || $('#to_location').val()==""){
				$('form').formValidation('revalidateField',$('#from_location'));
				$('form').formValidation('revalidateField',$('#to_location'));
			}
			var id = $(this).closest('tr').attr('id');
			var cur_val = $(this).val();
			var lastRow = $(this).closest("tbody").find("tr:last").attr('id');
			
			$.when(
				$.post(
					getStockBalance,
					{
						assetssp_id: $(this).val(),
						source_loc: $('#from_location').val(),
						destination_loc: $('#to_location').val(),
						item_id: $('#item-'+id).val(),
					},
					function(data){
						$('#uom-'+id).html(data.uom);
						$('#uom-'+id).trigger('chosen:updated');
						$('#rate-'+id).val(data.rate);
						$('#from_balance-'+id).val(data.source_qty);
						$('#to_balance-'+id).val(data.destination_qty);
					},
					'json'
				    )
				).done(function(){
					var cur_obj = $('#assetssp-'+id).closest('tr');		
					var flag = true;
					$(document).find('select.tr-assetssp').not('#assetssp-'+id).each(function(){
						if(cur_val == $(this).val()){
							flag = false;
						}
					});
					if(flag){
						if (id == lastRow){
							issue_addrow(parentObj);
						}
					}else{
						issue_reset(cur_obj);
					}
				});
			});
	    });
	    issue_delbtn(obj);
	    issue_calculation(obj);
	};	
	function issue_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){issue_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){issue_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function issue_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){issue_reset($(this).closest("tr"));});
			});
		}
		issue_calculation(tbody.parent());
	}
	
	function issue_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#rate-'+id).on('change',function(){issue_calamt($(this).closest("tr"));});
			$('#quantity-'+id).on('change',function(){issue_calamt($(this).closest("tr"));});			
		});
		var netamt = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#po_amount').val(($.isNumeric(value))? value:'0.00');
	};
	function issue_calamt(obj){
		var id = obj.closest("tr").attr('id');
		var rate = parseFloat($('#rate-'+id).val());
		var quantity = parseFloat($('#quantity-'+id).val());
		var amt = rate*quantity;
		$('#amount-'+id).val(parseFloat(amt).toFixed(2));
				var netamt = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#payable_amount').val(($.isNumeric(value))? value:'0.00');
	}		
	
	function issue_reset(obj){
		
		var id = obj.attr('id');
		$('#assetssp-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#assetssp-'+id).trigger('chosen:updated');
		$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#uom-'+id).trigger('chosen:updated');
		$('#item-'+id+' option:selected').removeAttr('selected');
		$('#item-'+id).trigger('chosen:updated');
		$('#from_balance-'+id).val('0.00');
		$('#to_balance-'+id).val('0.00');
		$('#quantity-'+id).val('0.00');
		$('#rate-'+id).val('0.00');
		$('#amount-'+id).val('0.00');
		$('#remarks-'+id).val('');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
	}
	
		
 /*
 * Store/IssueController
 * receiveissue 	-->Receive Goods Issue
 */
	$.fn.issuereceive = function (spec){
		var options = $.extend({
		},spec);
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
		});
		issuereceive_calculation(parentObj);
	};
	function issuereceive_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#shortage_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					$('#accept_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()).toFixed(2) - parseFloat($(this).val()).toFixed(2));
					var sum = parseFloat($("#damage_qty-"+id).val()) + parseFloat($('#shortage_qty-'+id).val());
					sum = sum.toFixed(2);
					$('#sound_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()).toFixed(2) - sum);
				}else{
					$(this).val("0.00");
				}
			});
			$('#damage_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					if($('#accept_qty-'+id).val() > 0.00){
						$('#sound_qty-'+id).val(parseFloat($("#accept_qty-"+id).val()).toFixed(2) - parseFloat($(this).val()).toFixed(2));
					}
					else{
						$('#sound_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()).toFixed(2) - parseFloat($(this).val()).toFixed(2));
					}
					
				}else{
					$(this).val("0.00");
				}
			});
		});
	};
	
	
    /*
	 * Store/PaymentController
	 * addpayment --> Add Payment
	 * editpayment -->Edit Payment
	 */
	$.fn.inpayment = function (spec){
		var options = $.extend({
			getInvoice:'',
			getInvAmt:'',
		},spec);
		
		$('#payment_type').change(function(){
			if($(this).val() == 4){
				$('#activity option:selected').removeAttr('selected');
				$('#activity option[value='+"-1"+']').attr('selected', 'selected');
				$('#activity').trigger('chosen:updated');
				$('form').formValidation('revalidateField','activity');
			}
			$.post(
    			options.getParty,
    			{
    				payment_type: $(this).val(),
    			},
    			function(data){
    				$("#party").html(data.payment_party);
					$("#party").trigger('chosen:updated');
    			},
				'json'
    		);
		});
		
		$("#party").change(function() {
			$.post(
    			options.getInvoice,
    			{
    				party: $(this).val(),
    				payment_type: $('#payment_type').val(),
    			},
    			function(data){
    				$(".tr-invoice").html(data.inv);
					$(".tr-invoice").trigger('chosen:updated');
    			},
				'json'
    		);
		});
		
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);	
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
	        
			$(this).find('select.tr-invoice').each(function(){
				var id = $(this).closest('tr').attr('id');			
				$(this).bind('change', function(){
					var selected_inv = $(this);
					$.post(
		    			options.getInvAmt,
		    			{
		    				payment_type: $('#payment_type').val(),
							invoice_id: $(this).val(),
		    			},
		    			function(data){
		    				$("#invoice_amount-"+id).val(data.amount);
		    				$('#deduction-'+id).val('0.00');
		    				payment_calamt(selected_inv.closest("tr"));
		    			},
						'json'
		    		);
				});
			});
			pay_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			pay_addrow(parentObj, options.getInvAmt);			     
		});	
		payment_calculation(parentObj);	
		payment_caldetail();
	};	
	function pay_addrow(obj, getInvAmt){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		pay_reset(clone);	        
		$('form select').chosen({ allow_single_deselect: true });
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		
		clone.find('select.tr-invoice').each(function(){
				var id = $(this).closest('tr').attr('id');			
				$(this).bind('change', function(){
					var selected_inv = $(this);
					$.post(
		    			getInvAmt,
		    			{
		    				payment_type: $('#payment_type').val(),
							invoice_id: $(this).val(),
		    			},
		    			function(data){
		    				$("#invoice_amount-"+id).val(data.amount);
		    				$('#deduction-'+id).val('0.00');
		    				payment_calamt(selected_inv.closest("tr"));
		    			},
						'json'
		    		);
				});
			});
		
		pay_delbtn(obj);		
		payment_calculation(obj);
	};	
	function pay_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){pay_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){pay_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function pay_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){pay_reset($(this).closest("tr"));});
			});
		}
		payment_calculation(tbody.parent());
	}
	
	function payment_calculation(obj){
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#invoice_amount-'+id).on('change',function(){payment_calamt($(this).closest("tr"));});
			$('#deduction-'+id).on('change',function(){payment_calamt($(this).closest("tr"));});
		});
		var netamt = 0;
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#payable_amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		//var tax = value * 2/100;
		//tax_value = parseFloat(tax).toFixed(2);
		$('#net_pay_amount').val(($.isNumeric(value))? value:'0.00');
		//$('#deduction').val(($.isNumeric(tax_value))? tax_value:'0.00');
		$('#payment_amount').val(parseFloat($('#net_pay_amount').val() - $('#deduction').val()).toFixed(2));
		payment_bankcharge($('#payment_amount').val());
	};
	
	function payment_calamt(obj){	
		var id = obj.closest("tr").attr('id');
		var inv_amt = parseFloat($('#invoice_amount-'+id).val()).toFixed(2);
		var deduction_amt = parseFloat($('#deduction-'+id).val()).toFixed(2);
		
		if ( deduction_amt == NaN) {  deduction_amt = 0.00;	}
		
		var pay_amt = inv_amt - deduction_amt; 
		$('#payable_amount-'+id).val(parseFloat(pay_amt).toFixed(2));
		
		var netamt = 0;
	    obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#payable_amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		//var tax = value * 2/100;
		//tax_value = parseFloat(tax).toFixed(2);
		$('#net_pay_amount').val(($.isNumeric(value))? value:'0.00');
		//$('#deduction').val(($.isNumeric(tax_value))? tax_value:'0.00');
		$('#payment_amount').val(parseFloat($('#net_pay_amount').val() - $('#deduction').val()).toFixed(2));
		payment_bankcharge($('#payment_amount').val());
	}

	function payment_caldetail(){
		$('#deduction').on('change',function(){
			$('#payment_amount').val(parseFloat($('#net_pay_amount').val() - $(this).val()).toFixed(2));
			payment_bankcharge($('#payment_amount').val());
		});
	}
	function payment_bankcharge(pay_amt){
		var bank_charge;
		if($.isNumeric(pay_amt) && pay_amt > 0.00 && pay_amt!=""){
			if(pay_amt <= 100000.00){
				bank_charge = 35.00;
			}else if(pay_amt >= 100001.00 && pay_amt<=1000000.00 ){
				bank_charge = 2000.00;
			}else{
				bank_charge = 15000.00;
			} 
		}else{
			bank_charge = "0.00";
		}
		//$('#bank_charge').val(bank_charge);
	}
	
	function pay_reset(obj){
		var id = obj.attr('id');
		
		$('#invoice_amount-'+id).val('0.00');
		$('#deduction-'+id).val('0.00');
		$('#payable_amount-'+id).val('0.00');
		$('#invoice-'+id).val('selectedIndex',0).trigger('chosen:updated');
		
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		payment_calculation(obj.parent().parent());
	}
	/*
	* Store/ReceiptController
	* addreceipt --> Add Purchase Receipt
	* editreceipt --> Edit Purchase Receipt
	*/
	 
	$.fn.inreceipt = function (spec){
		var options = $.extend({
			getpodetail: '',
		},spec);
		
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			
			$(this).find('select.tr-item').each(function(){
				var id = $(this).closest('tr').attr('id');
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#challan_qty-"+id).attr("challan", $('#challan_qty-'+id).val());
				$("#damage_qty-"+id).attr("damage", $("#damage_qty-"+id).val());
				$('#shortage_qty-'+id).attr("shortage", $('#shortage_qty-'+id).val());
				$("#accept_qty-"+id).attr("accept", $('#accept_qty-'+id).val());
				$("#sound_qty-"+id).attr("sound", $('#sound_qty-'+id).val());
			});
			
			$(this).find('select.tr-item').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$.post(
						options.getpodetail,
						{
							item_id: $(this).val(),
							po_id: $("#purchase_order_no").val(),
						},
						function(data){
							//console.log(data);
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id).trigger('chosen:updated');
							$('#po_qty-'+id).val(data.po_qty);
							$('#po_qty-'+id).trigger('chosen:updated');
							$('#rate-'+id).val(data.rate);
							$('#rate-'+id).trigger('chosen:updated');
							$("#po_details_id-"+id).val(data.po_details_id);
						},
						'json'
					);
				});
			});
			$(this).find('input.tr-challan_qty').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					
					$("#challan_qty-"+id).attr("challan", $(this).val());
					var accept_quantity = parseFloat($(this).val() - $('#shortage_qty-'+id).val());
					var sound_quantity = parseFloat($(this).val() - $('#shortage_qty-'+id).val() - $('#damage_qty-'+id).val());
					
					$("#accept_qty-"+id).attr("accept", accept_quantity);
					$("#sound_qty-"+id).attr("sound", sound_quantity);
				});
			});
			$(this).find('input.tr-damage_qty').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					
					$("#damage_qty-"+id).attr("damage", $(this).val());
					var sound_quantity = parseFloat($("#challan_qty-"+id).val() - $('#shortage_qty-'+id).val() - $(this).val());
					$("#sound_qty-"+id).attr("sound", sound_quantity);
				});
			});
			$(this).find('input.tr-shortage_qty').each(function(){
				var id = $(this).closest('tr').attr('id');
				$(this).bind('change', function(){
					$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
					
					$("#shortage_qty-"+id).attr("shortage", $(this).val());
					
					var accept_quantity = parseFloat($("#challan_qty-"+id).val() - $(this).val());
					var sound_quantity = parseFloat($("#challan_qty-"+id).val() - $(this).val() - $('#damage_qty-'+id).val());
					
					$("#accept_qty-"+id).attr("accept", accept_quantity);
					$("#sound_qty-"+id).attr("sound", sound_quantity);
				});
			});
			pr_delbtn(parentObj);
		}); 
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			parentObj.parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			pr_addrow(parentObj,options.getpodetail);			     
		});	
		pr_calculation(parentObj);	
	};    
	function pr_addrow(obj,getpodetail){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		var retainVal = clone.find(':input#po_no-'+lastRow).val();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		pr_reset(clone);
		
		$('form select').chosen(); //{ allow_single_deselect: true }
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-item').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$.post(
					getpodetail,
					{
						item_id: $(this).val(),
						po_id: $("#purchase_order_no").val(),
						po_details_id : $("#po_details_id-"+id).val(),
					},
					function(data){
						$('#uom-'+id).html(data.uom);
						$('#uom-'+id).trigger('chosen:updated');
						$('#po_qty-'+id).val(data.po_qty);
						$('#rate-'+id).val(data.rate);
						$("#po_details_id-"+id).val(data.po_details_id);
					},
					'json'
				);
			});
		});
		clone.find('input.tr-challan_qty').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				
				$("#challan_qty-"+id).attr("challan", $(this).val());
				var accept_quantity = parseFloat($(this).val() - $('#shortage_qty-'+id).val());
				var sound_quantity = parseFloat($(this).val() - $('#shortage_qty-'+id).val() - $('#damage_qty-'+id).val());
				
				$("#accept_qty-"+id).attr("accept", accept_quantity);
				$("#sound_qty-"+id).attr("sound", sound_quantity);
			});
		});
		clone.find('input.tr-damage_qty').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#damage_qty-"+id).attr("damage", $(this).val());
				var sound_quantity = parseFloat($("#challan_qty-"+id).val() - $('#shortage_qty-'+id).val() - $(this).val());
				$("#sound_qty-"+id).attr("sound", sound_quantity);
			});
		});
		clone.find('input.tr-shortage_qty').each(function(){
			var id = $(this).closest('tr').attr('id');
			$(this).bind('change', function(){
				$("#uom-"+id).attr("input_uom", $('#uom-'+id).val());
				$("#shortage_qty-"+id).attr("shortage", $(this).val());
				
				var accept_quantity = parseFloat($("#challan_qty-"+id).val() - $(this).val());
				var sound_quantity = parseFloat($("#challan_qty-"+id).val() - $(this).val() - $('#damage_qty-'+id).val());
				
				$("#accept_qty-"+id).attr("accept", accept_quantity);
				$("#sound_qty-"+id).attr("sound", sound_quantity);
			});
		});
		
		pr_delbtn(obj);		
		pr_calculation(obj);
	};	
	function pr_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){pr_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){pr_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function pr_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			//console.log($(this));
			$('form').formValidation('removeField', $(this));
		});
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){pr_reset($(this).closest("tr"));});
			});
		}
		pr_calculation(tbody.parent());
	}	
	function pr_reset(obj){
		var id = obj.attr('id');
		$('#po_qty-'+id).val('0.00');
		$('#challan_qty-'+id).val('0.00');
		$('#damage_qty-'+id).val('0.00');
		$('#shortage_qty-'+id).val('0.00');
		$('#accept_qty-'+id).val('0.00');
		$('#sound_qty-'+id).val('0.00');
		$('#item-'+id).val('selectedIndex',0).trigger('chosen:updated');
		$('#uom-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#uom-'+id).trigger('chosen:updated');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
		pr_calculation(obj.parent().parent());
	};
	
	function pr_calculation(obj) {
		obj.find('tbody tr').each(function(){
			var id = $(this).attr('id');
			$('#challan_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					$('#accept_qty-'+id).val(parseFloat($(this).val()) - parseFloat($('#shortage_qty-'+id).val()));
					var sum = parseFloat($("#damage_qty-"+id).val()) + parseFloat($('#shortage_qty-'+id).val());
					$('#sound_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()) - sum);
				}else{
					$(this).val("0.000");
	 				$("#accept_qty-"+id).val("0.000");
	 				$("#sound_qty-"+id).val("0.000");
				}
				pr_calamt($(this).closest("tr"));
			});
			$('#shortage_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					$('#accept_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()) - parseFloat($(this).val()));
					var sum = parseFloat($("#damage_qty-"+id).val()) + parseFloat($('#shortage_qty-'+id).val());
					$('#sound_qty-'+id).val(parseFloat($("#challan_qty-"+id).val()) - sum);
				}else{
					$(this).val("0.000");
				}
				pr_calamt($(this).closest("tr"));
			});
			$('#damage_qty-'+id).on('change',function(){
				if($.isNumeric($(this).val())){
					$('#sound_qty-'+id).val(parseFloat($("#accept_qty-"+id).val()) - parseFloat($(this).val()));
				}else{
					$(this).val("0.000");
				}
				pr_calamt($(this).closest("tr"));
			});	
		});
	};
    function pr_calamt(obj){
		var id = obj.closest("tr").attr('id');
		var rate = parseFloat($('#rate-'+id).val());
		var sound_quantity = parseFloat($('#sound_qty-'+id).val());
		var amt = rate*sound_quantity;
		$('#amount-'+id).val(parseFloat(amt).toFixed(2));
				var netamt = 0;
		obj.parent().find('tr').each(function(){
			var id = $(this).attr('id');
			netamt += parseFloat($('#amount-'+id).val());
		});
		var value = parseFloat(netamt).toFixed(2);
		$('#payable_amount').val(($.isNumeric(value))? value:'0.00');
	}
       /*
	 * Sales/SalesController
	 * addmarket 	-->Add Market Infromation
	 * editmarket -->Edit Market Infromation
	 */
	$.fn.market = function (spec){
		//set default option
		var options = $.extend({
				getItem: '',
				getDetails: '',
		},spec);
		/***** Get the Item of the Activity *****/
		$("#activity").change(function() {
			$.post(
				options.getItem,
				{
					activity_id: $(this).val(),
				},
				function(data){
					//console.log(data);
					$('.tr-item').html(data.stock_item);
					$('.tr-item').trigger('chosen:updated');
				},
				'json'
			);
			$(".tr-item").each(function(){
				do_reset($(this).closest('tr'));
			});
		});
		var tr_id =0;
		var parentObj = $(this);
		parentObj.wrap("<div style='overflow-x:scroll;margin-bottom:5px;'> </div>");
		parentObj.find("tbody tr").each(function(){
			$(this).attr('id',++tr_id);			
			$(this).find('div.chosen-search input').each(function(){
				$(this).addClass('ch-search');
			});
			$(this).find('div.chosen-container .chosen-drop').each(function(){
				$(this).css({'position':'relative'});
			});
			$(this).find(':input').not('input.ch-search').each(function(){
	            var old_id = $(this).attr('id');
	            var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
	            $(this).attr('id',new_id+'-'+tr_id);
	            $(this).addClass('tr-'+new_id);
	        });
			$(this).find('select.tr-item').each(function(){
				$(this).bind('change', function(){
					if($('location').val()==""){
						$('form').formValidation('revalidateField',$('#location'));
					}
					var id = $(this).closest('tr').attr('id');
						$.post(
							options.getDetails,
							{
								item_id: $(this).val(),
								source_loc: $('#location').val(),
							},
							function(data){
								console.log(data);
								$('#batch-'+id).html(data.batch);
								$('#batch-'+id+' option:selected').removeAttr('selected');
								$('#batch-'+id+' option[value='+data.latest_batch+']').attr('selected', 'selected');
								$('#batch-'+id).trigger('chosen:updated');
								$('#uom-'+id).html(data.uom);
								$('#uom-'+id+' option:selected').removeAttr('selected');
								$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
								$('#uom-'+id).trigger('chosen:updated');
							},
							'json'
						)
				});
			}); 
			do_delbtn(parentObj);
		});
		if($('#addRow').length == 0){
			var addbutton = "<div class='row'><div class='col-lg-12'><a value='Add' id='addRow' class='pull-right btn btn-success btn-xs' ><i class='fa fa-plus'></i> Add Row</a>&nbsp;&nbsp;&nbsp;&nbsp;</div></div>";
			$(this).parent().parent().parent().append(addbutton);
		};		
		$("#addRow").click(function(){
			do_addrow(parentObj, options.getDetails);			     
		});				
	};	
	function do_addrow(obj, getDetails){
		var lastRow = obj.find("tbody tr:last").attr('id');
		var tmplt_row = obj.find("tbody tr:last").clone(true);
		clone = tmplt_row.clone();
		clone.find('div.chosen-container').each(function(){
			$(this).remove();
		});
		clone.attr('id',++lastRow);
		clone.find(':input').each(function(){
			var old_id = $(this).attr('id');
			var new_id=(old_id.lastIndexOf('-') > 0)? old_id.substr(0, old_id.lastIndexOf('-')): old_id;
			$(this).attr('id',new_id+'-'+lastRow);
			$(this).addClass('tr-'+new_id);
			//$('form').formValidation('addField', $(this));
		});
		$('#'+obj.attr('id')+' tbody').append(clone);
		do_reset(clone);	        
		$('form select').chosen();
		clone.find('div.chosen-container .chosen-drop').each(function(){
			$(this).css({'position':'relative'});
		});
		clone.find('div.chosen-search input').each(function(){
			$(this).addClass('ch-search');
		});
		clone.find('select.tr-item').each(function(){
			$(this).bind('change', function(){
				if($('#from_location').val()=="" || $('#to_location').val()==""){
					$('form').formValidation('revalidateField',$('#from_location'));
					$('form').formValidation('revalidateField',$('#to_location'));
				}
				var id = $(this).closest('tr').attr('id');
				//var cur_val = $(this).val();
				//$.when(
					$.post(
						getDetails,
						{
							item_id: $(this).val(),
							source_loc: $('#from_location').val(),
							destination_loc: $('#to_location').val(),
						},
						function(data){
							//console.log(data);
							$('#batch-'+id).html(data.batch);
							$('#batch-'+id+' option:selected').removeAttr('selected');
							$('#batch-'+id+' option[value='+data.latest_batch+']').attr('selected', 'selected');
							$('#batch-'+id).trigger('chosen:updated');
							$('#uom-'+id).html(data.uom);
							$('#uom-'+id+' option:selected').removeAttr('selected');
							$('#uom-'+id+' option[value='+data.batch_uom+']').attr('selected', 'selected');
							$('#uom-'+id).trigger('chosen:updated');
						},
						'json'
					)
				/*).done(function(){
					var cur_obj = $('#item-'+id).closest('tr');	
					$(document).find('select.tr-item').not('#item-'+id).each(function(){
						if(cur_val == $(this).val()){
							dsp_reset(cur_obj);
						}
					});
				});*/
			});
		});
		do_delbtn(obj);
	};	
	function do_delbtn(obj){
		if(obj.find("tbody tr").length > 1){
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs delRow' style='margin-top:5px;' ><i class='fa fa-times'></i></a>";
			obj.find("tbody tr").each(function(){
				$(this).find("td:last").html(delBtn);			
			});
			obj.find("td a.delRow").each(function(){
				$(this).click(function(){do_delrow($(this).closest('tr'));});	
			});
		}else{
			delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			obj.find("tbody tr").each(function(){
				var las_td = $(this).find("td:last").html(delBtn);
			});
			obj.find("td a.resetRow").each(function(){
				$(this).click(function(){do_reset($(this).closest('tr'));});	
			});
		}		
	};	
	function do_delrow(obj){
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('removeField', $(this));
		});	
		var tr_length = obj.parent().find('tr').length;
		var tbody = obj.parent();
		obj.closest("tr").remove();	
		if(tr_length == 2){
			var delBtn = "<a value='Delete' class='btn btn-warning btn-xs resetRow' style='margin-top:5px;' ><i class='fa fa-refresh'></i></a>";
			tbody.find('tr td:last').html(delBtn);
			tbody.find('tr td:last a.resetRow').each(function(){
				$(this).click(function(){do_reset($(this).closest("tr"));});
			});
		}
	}
	function do_reset(obj){
		
		var id = obj.attr('id');
		$('#batch-'+id+' option').filter(function(){ return this.innerHTML !='';}).remove();
		$('#batch-'+id).trigger('chosen:updated');
		$('#uom-'+id+' option:selected').removeAttr('selected');
		$('#uom-'+id).trigger('chosen:updated');
		$('#item-'+id+' option:selected').removeAttr('selected');
		$('#item-'+id).trigger('chosen:updated');
		$('#rate-'+id).val('0.00');
		$('#remarks-'+id).val('');
		obj.find(':input:hidden').each(function(){$(this).val('');});
		obj.find(':input[data-fv-field]').each(function() {
			$('form').formValidation('resetField',$(this));
		});	
	}
	
}(jQuery));
