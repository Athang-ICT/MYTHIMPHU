/*!
* Athang v1.0.0 (https://athang.com)
* @version 1.0.0
* @link https://athang.com
* Copyright 2021-2022 The Athang IC Tech
*/
$(document).ready(function(){
	/* ---------------------------------------------------------------------- */
	/*  Modal -- Bootstrap
	/* ---------------------------------------------------------------------- */
	$('body').on('hidden.bs.modal', '.modal', function () {
        $(this).removeData('bs.modal');
    });

	/* ---------------------------------------------------------------------- */
	/*  Chosen.jquery -- Bootstrap 3
	/* ---------------------------------------------------------------------- */
	$('form select').chosen();

	/* ---------------------------------------------------------------------- */
	/*  DatePicker Plugin -- Bootstrap Date-Picker 3
	/* ---------------------------------------------------------------------- */
	$('.date-picker').datepicker({
		autoclose: true,
		todayHighlight: true
	})
	//show datepicker when clicking on the icon
	.next().on(ace.click_event, function(){
		$(this).prev().focus();
	});
	//or change it into a date range picker
	$('.input-daterange').datepicker({autoclose:true});

	/* ---------------------------------------------------------------------- */
	/*  Tool-Tip
	/* ---------------------------------------------------------------------- */
	$('[data-rel=tooltip]').tooltip();
	
	/* ---------------------------------------------------------------------- */
	/*  DataTables - Setting Defaults
	/* ---------------------------------------------------------------------- */
	$.extend(true, $.fn.dataTable.defaults, {
		dom: 'Bfrtip',
		columnDefs: [ {
			visible: false
		} ],
		"paging":   false,
		"ordering": false,
		"info":     false,
		select: true,
		buttons: [
			{
				extend: 'copy',
				messageBottom: "Exported on "+new Date($.now()),
				exportOptions: {columns: ':visible'},
				text: '<i class="ace-icon fa fa-clone"></i> Copy',
				titleAttr: 'Copy'
			},
			{
				extend: 'csv',
				exportOptions: {columns: ':visible'},
				text: '<i class="ace-icon fa fa-file-text-o"></i> Csv',
				titleAttr: 'CSV'
			},
			{
				extend: 'excel',
				messageBottom: "Exported on "+new Date($.now()),
				exportOptions: {columns: ':visible'},
				text: '<i class="ace-icon fa fa-file-excel-o"></i> Excel',
				titleAttr: 'Excel'
			},
			{
				extend: 'pdfHtml5',
				messageBottom: "Exported on "+new Date($.now()),
				exportOptions: {columns: ':visible'},
				orientation: 'landscape',
				pageSize: 'LEGAL',
				text: '<i class="ace-icon fa fa-file-pdf-o"></i> Pdf',
				titleAttr: 'PDF'
			},
			{
				extend: 'print',
				messageBottom: "Exported on "+new Date($.now()),
				exportOptions: {columns: ':visible'},
				text: '<i class="ace-icon fa fa-print"></i> Print',
				titleAttr: 'Print'
			},	
		],
	});

	/* ---------------------------------------------------------------------- */
	/*  CanvasJS -- ColorSet
	/* ---------------------------------------------------------------------- */
	CanvasJS.addColorSet('skyColorSet',['#bccad6','#8d9db6','#667292','#f1e3dd','#cfe0e8','#b7d7e8','#87bdd8','#daebe8']);
	CanvasJS.addColorSet('sandColorSet',['#e0876a','#d9ad7c','#a2836e','#674d3c','#fbefcc','#f9ccac','#f4a688','#fff2df']);
	CanvasJS.addColorSet('rusticColorSet',['#8ca3a3','#c6bcb6','#96897f','#625750','#c8c3cc','#563f46','#484f4f','#e0e2e4']);
	CanvasJS.addColorSet('beachColorSet',['#96ceb4','#ffeead','#ffcc5c','#ff6f69','#588c7e','#f2e394','#f2ae72','#d96459']);
});