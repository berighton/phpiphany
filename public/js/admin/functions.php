//*******************  UI  *******************//
$(function() {

	// Tabs
	$('#tabs').tabs();

	// Datepicker
	$('#datepicker').datepicker({
		inline: true
	});
	$('#inline-datepicker').datepicker({
		inline: true
	});

	// Slider
	$("#slider").slider(
			{
				slide: function(event, ui) {
					$("#amount").val("$" + ui.value);
				}
			}
	);

	$("#slider2").slider({
		value:100,
		min: 0,
		max: 500,
		step: 1,
		slide: function(event, ui) {
			$("#amount").val("$" + ui.value);
		}
	});
	$("#amount").val("$" + $("#slider").slider("value"));
	$("#slider-range").slider({
		range: true,
		min: 0,
		max: 500,
		values: [ 75, 300 ],
		slide: function(event, ui) {
			$("#amount2").val("$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ]);
		}
	});
	$("#amount2").val("$" + $("#slider-range").slider("values", 0) +
			" - $" + $("#slider-range").slider("values", 1));
	// setup graphic EQ
	$("#eq > span").each(function() {
		// read initial values from markup and remove that
		var value = parseInt($(this).text(), 10);
		$(this).empty().slider({
			value: value,
			range: "min",
			animate: true,
			orientation: "vertical"
		});
	});
	$("#slider-range-min").slider({
		range: "min",
		value: 23,
		min: 23,
		max: 500,
		slide: function(event, ui) {
			$("#amount3").val("$" + ui.value);
		}
	});
	$("#amount3").val("$" + $("#slider-range-min").slider("value"));
	$("#slider-range-max").slider({
		range: "max",
		value: 56,
		min: 1,
		max: 350,
		slide: function(event, ui) {
			$("#amount4").val("$" + ui.value);
		}
	});
	$("#amount4").val("$" + $("#slider-range-min").slider("value"));
	// Progressbar
	$("#progressbar").progressbar({
		value: 20
	});

	//hover states on the static widgets
	$('#dialog_link, ul#icons li').hover(
			function() {
				$(this).addClass('ui-state-hover');
			},
			function() {
				$(this).removeClass('ui-state-hover');
			}
	);

});


//*******************  Placeholder for all browsers  *******************//

$(function() {
	$("input").each(
			function() {
				if ($(this).val() == "" && $(this).attr("placeholder") != "") {
					$(this).val($(this).attr("placeholder"));
					$(this).focus(function() {
						if ($(this).val() == $(this).attr("placeholder")) $(this).val("");
					});
					$(this).blur(function() {
						if ($(this).val() == "") $(this).val($(this).attr("placeholder"));
					});
				}
			});

//*******************  Collapsing blocks jQuery  *******************//

	$(document).ready(function() {
		$('.title-grid').append('<span></span>');
		$('.grid-1 span').each(function() {
			var trigger = $(this), state = false, el = trigger.parent().next('.content-grid');
			trigger.click(function() {
				state = !state;
				el.slideToggle();
				trigger.parent().parent().toggleClass('inactive');
			});
		});
		$('.grid-2 span').each(function() {
			var trigger = $(this), state = false, el = trigger.parent().next('.content-grid');
			trigger.click(function() {
				state = !state;
				el.slideToggle();
				trigger.parent().parent().toggleClass('inactive');
			});
		});
		$('.grid-3 span').each(function() {
			var trigger = $(this), state = false, el = trigger.parent().next('.content-grid');
			trigger.click(function() {
				state = !state;
				el.slideToggle();
				trigger.parent().parent().toggleClass('inactive');
			});
		});
	});
	$('.grid-4 span').each(function() {
		var trigger = $(this), state = false, el = trigger.parent().next('.content-grid');
		trigger.click(function() {
			state = !state;
			el.slideToggle();
			trigger.parent().parent().toggleClass('inactive');
		});
	});
});



//*********************  FORMS   *********************//
//select
$(document).ready(function() {
	$(".chzn-select").chosen();
	$(".chzn-select-deselect").chosen({allow_single_deselect:true});
});

$(document).ready(function() {
	$("input[type=file]").change(function() {
		$(this).parents(".uploader").find(".filename").val($(this).val());
	});
	$("input[type=file]").each(function() {
		if ($(this).val() == "") {
			$(this).parents(".uploader").find(".filename").val("No file selected...");
		}
	});
});


//********************* auto-resize *********************//

$(document).ready(function() {
	$('textarea.resize-text').autoResize({});
});

//********************* Contact list *********************//	
$(document).ready(function() {
	$('#slider-contact').sliderNav();
});


//********************* Auto TAB (Input) *********************//
$(document).ready(function() {
	$('#autotab_example').submit(function() {
		return false;
	});
	$('#autotab_example :input').autotab_magic();
	// Number example
	$('#area_code, #number1, #number2').autotab_filter('numeric');
	$('#ssn1, #ssn2, #ssn3').autotab_filter('numeric');
	// Text example
	$('#text1, #text2, #text3').autotab_filter('text');
	// Alpha example
	$('#alpha1, #alpha2, #alpha3, #alpha4, #alpha5').autotab_filter('alpha');
	// Alphanumeric example
	$('#alphanumeric1, #alphanumeric2, #alphanumeric3, #alphanumeric4, #alphanumeric5').autotab_filter({ format: 'alphanumeric', uppercase: true });
	$('#regex').autotab_filter({ format: 'custom', pattern: '[^0-9\.]' });
});


//*********************   Server Live Chart   *********************//
$(function () {

	var cpu_data = [], ram_data = [], counter = 0;

	function get_data() {
		$.ajax({
			url: "<?php echo $_GET['hash']? base64_decode($_GET['hash']) : 'http://localhost' ?>",
			cache: false,
			method: 'GET',
			dataType: 'json',
			success: function(stats) {
				cpu_data.push([counter, stats.cpu]);
				ram_data.push([counter, stats.ram]);
				$('#cpu').html(stats.cpu);
				$('#ram').html(stats.ram);
			},
			error: function(data) { console.log('error happened during AJAX call: ' + data.cpu) }
		});

		if (cpu_data.length > 100) cpu_data = cpu_data.slice(1);
		if (ram_data.length > 100) ram_data = ram_data.slice(1);

		cpu.setData([ cpu_data ]);
		ram.setData([ ram_data ]);
		cpu.setupGrid();
		ram.setupGrid();
		cpu.draw();
		ram.draw();
		counter++;
		if (cpu_data.length > 1 && $("#cpu-loading").html()) $("#cpu-loading").html('');
		if (ram_data.length > 1 && $("#ram-loading").html()) $("#ram-loading").html('');
		setTimeout(get_data, update_interval);
	}

	// setup control widget
	var update_interval = 2000;
	$("#updateInterval").val(update_interval).change(function () {
		var v = $(this).val();
		if (v && !isNaN(+v)) {
			update_interval = +v;
			if (update_interval < 1)
				update_interval = 1;
			if (update_interval > 2000)
				update_interval = 2000;
			$(this).val("" + update_interval);
		}
	});

	// setup plot
	var options = {
		series: { shadowSize: 0 }, // drawing is faster without shadows
		yaxis: { min: 0, max: 100 },
		xaxis: { show: false },

		colors: ["#258dde"],
		series: {
			lines: {
				lineWidth: 1,
				fill: true,
				fillColor: { colors: [
					{ opacity: 0.5 },
					{ opacity: 1.0 }
				] },
				steps: false
			}
		}
	};

	//$("#cpu-loading").html('<img src="/images/loading.gif" alt="Loading" />');
	var cpu = $.plot($(".cpu-live"), [ [] ], options);
	var ram = $.plot($(".ram-live"), [ [] ], options);

	get_data();

});