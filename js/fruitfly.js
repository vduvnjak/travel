var destinations = new Array();
var overlay;
// here we are creating global infowindow variable, cause we want to check later if it is instantiated, and therefore allow only one instance
var infowindow;
var infobox;
var globDestination;
var bounds = new google.maps.LatLngBounds();
var image = 'images/pin_r2.png';
var image_transp = 'images/pin_transp.png';
var image = 'pin.png';
var priceMin = 0;
var latlng = new google.maps.LatLng(35.960223,-99.140625);
var myOptions = {
		zoom: 4,
		center: latlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		disableDefaultUI: false,
		disableDoubleClickZoom: true
};

function dumpProps(obj, parent) {
	   // Go through all the properties of the passed-in object
	   for (var i in obj) {
	      // if a parent (2nd parameter) was passed in, then use that to
	      // build the message. Message includes i (the object's property name)
	      // then the object's property value on a new line
	      if (parent) { var msg = parent + "." + i + "\n" + obj[i]; } else { var msg = i + "\n" + obj[i]; }
	      // Display the message. If the user clicks "OK", then continue. If they
	      // click "CANCEL" then quit this level of recursion
	      if (!confirm(msg)) { return; }
	      // If this property (i) is an object, then recursively process the object
	      if (typeof obj[i] == "object") {
	         if (parent) { dumpProps(obj[i], parent + "." + i); } else { dumpProps(obj[i], i); }
	      }
	   }
	}

function initialize() {

	var map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);	
}

$(function() {
	$("#startDate").datepicker({ dateFormat: 'yy-mm-dd' });
});


$(document).ready(function(){
	
	$("#Submit").click(function(){
		$(".trip_main").remove('#trip_results');
		
		displayResults();
		ajaxCall('submit','');
		
		return false;
	});
	
	$("#Clear").click(function(){
		$(".pin").fadeOut("slow");
		$(".pin").remove();
		//infowindow.close(this.map_);
		return false;
	});
	
	 $("#slider").slider();
	 $("#slider" ).slider({ max: 3000 }); 
	 $("#slider" ).slider({ min: 0 });
	 $("#slider" ).slider({ step: 100 });
	 $("#slider" ).slider({ value: 1000 });
	 $("#slider" ).slider({ animate: 1000 });
	 $("#slider" ).slider({	 
		 	change: function(event, ui) { 
		 	var sliderValue = $("#slider").slider( "option", "value" );
		 	$("#budget").val(sliderValue);
	 		}
	 });
	 
	 $("#budget").keyup(function(){	 
		 var budgetValue = $("#budget").val();
		 $("#slider").slider( "option", "value", budgetValue );	
		 return false;
		});
	 
	 
	 $("#startLocation").autocomplete({
		 
		 autoFocus: true,
		 source: function( request, response ) {
				$.ajax({
					url: "hub_location.php",
					async: false,
					dataType: 'json',
					data: {
						featureClass: "P",
						style: "full",
						maxRows: 12,
						name_startsWith: request.term
						},
					success: function(json) {
						response(json.value);	
					}
				});
			},
		minLength: 2,
	    select: function(event, ui){
	        $('#startLocation').val(ui.item.value);
	        return false;
	    },
	    focus: function(event, ui){
	        $('#startLocation').val(ui.item.value);
	        return false;
	    }

	});
	 
	 
	 // here is the ajax event listener
	 function ajaxCall(event, destination)
	  {
		   $.ajaxSetup({
				cache:false,
				dataType: 'json',
				error: function(xhr, status, error)
				{
					//alert('An error occured ' + error);
				},
				//timeout: 1000,
				type: 'POST',
				url: 'hub_ajax_listener.php'
		    });
		    $.ajax({
				//data: $("#travelForm").serialize(),
				data: {event : event, destination : destination, form_data : $("#travelForm").serialize()},

				success: function(data) {
		    	//
		    	}
		    });
	  	
	  };


	
});   

function displayResults()
{	
	$.post('hub.php', $("#travelForm").serialize(), function(data){
		
		var map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);
		// we will try quickly to find a minimum price here and try to set global var minPrice, accessible from inside other functions
		
		var tmp_min=10000;
		$.each(data.destinations, function(i,destination){
			var tmp_price = destination.flight;
			if (tmp_price<tmp_min) tmp_min = tmp_price;
		});
		priceMin = tmp_min;
		  
		
		$.each(data.destinations, function(i,destination){
		
				latlng_ = new google.maps.LatLng(destination.city.latitude,destination.city.longitude);
			
				marker = new google.maps.Marker({
				position: latlng_,
				map: map,
				icon: image_transp
				});
				//marker.setMap(map);

				overlay = new addMarkerOverlay(map, destination, image);
				
				(function (i,marker) {
				
					google.maps.event.addListener(marker,'click',function(){
					
						if (infobox)
						{
							infobox.close(map);	
						}
						
						var infoBoxOptions = {
						       disableAutoPan: false,
						       maxWidth: 0,
						       pixelOffset: new google.maps.Size(-120, 0),
						       zIndex: null,
						       boxStyle: { 
						         opacity: 0.99,
						         width: "250px",
						         height: "236px"
						        },
						       closeBoxMargin: "20px 10px 2px 2px",
						       closeBoxURL: "css/images/close.gif",
						       infoBoxClearance: new google.maps.Size(1, 1),
						       isHidden: false,
						       pane: "floatPane",
						       enableEventPropagation: true
						};
						infobox = new InfoBox(infoBoxOptions);
						infobox.setPosition(latlng_);
						
						var infoboxContent = getExtInfoWindowBody(destination);
						infobox.setContent(infoboxContent);
						infobox.open(map,marker);
						
						
						// here is our internal listener for _tracking
						google.maps.event.addListener(infobox,'domready',function(){
					        $('#flight_link').click(function(){
					        	ajaxCallInt('flight_link', destination.airport.shortName );
					        });
					    }); 
						
						 function ajaxCallInt(event, destination)
						 {
							   $.ajaxSetup({
									cache:false,
									dataType: 'json',
									error: function(xhr, status, error)
									{
										//alert('An error occured ' + error);
									},
									type: 'POST',
									url: 'hub_ajax_listener.php'
							    });
							    $.ajax({
									data: {event : event, destination : destination, form_data : $("#travelForm").serialize()}
							    });
						  };
						 
				    });
					
				})(i,marker);
		
				
		}); 
	
	
		// after bounds got the value in addMarkerOverlay, we fit map to it
		// this is the part where we actually change the zoom through map.fitBounds and map.setCenter
		//map.fitBounds(bounds);
		//setTimeout( function() { map.fitBounds( bounds ); }, 1 );
		//map.setCenter(bounds.getCenter());
		
	}, 'json');
	
}

function addMarkerOverlay(map, destination, image)
{

	this.map_ = map;
	this.dest_ = destination;
	this.image_ = image;
	this.latlng_ = new google.maps.LatLng(destination.city.latitude,destination.city.longitude);
	this.div_= null;
	// trigger the call to the onAdd() method
	this.setMap(map);
	
	// added code for bounds so we adjust the map to the results
	bounds.extend(this.latlng_);
}

addMarkerOverlay.prototype = new google.maps.OverlayView();

addMarkerOverlay.prototype.onAdd = function() {
	
	// In the add() method, we create DOM objects and append them as children of the panes
	
	var that = this;
	//var price = formatPrice(this.dest_.flight);
	var numberPeople = document.getElementById('numberPeople').value;
	var numberNights = document.getElementById('numberNights').value;
	var budget = document.getElementById('budget').value;
	var price = (this.dest_.flight*numberPeople+numberNights*200);
	var price_f = formatPrice(price);
	
	var div = document.createElement("DIV");

	
	var coolnes = Math.floor(Math.random()*101);
	
	// here we look global var priceMin and our budget is priceMax - we put this in 5 equal slots, and assign color for them
	var priceMinimum = (priceMin*numberPeople+numberNights*200);
	var priceSpan = budget - priceMinimum;
	
	//var budPriceRatio = 100*((price)/budget).toFixed(2);
	
	// here we decide the color of the flag based on a budget and price span
	/*
	if ((price>0) && (price<=(priceMinimum+0.2*priceSpan))) pinclass='pin_4';
	else if ((price>(priceMinimum+0.2*priceSpan)) && (price<=(priceMinimum+0.4*priceSpan))) pinclass='pin_3';
	else if ((price>(priceMinimum+0.4*priceSpan)) && (price<=(priceMinimum+0.6*priceSpan))) pinclass='pin_2';
	else if ((price>(priceMinimum+0.6*priceSpan)) && (price<=(priceMinimum+0.8*priceSpan))) pinclass='pin_1';
	else pinclass='pin';*/
	pinclass='pin_2';

	$(div).addClass(pinclass).html("<div>$" + price + "<br/></div>");
	
	this.div_ = div;
	
	var pane = this.getPanes().overlayLayer;
    pane.appendChild(this.div_);
};

addMarkerOverlay.prototype.draw = function() {
	overlay = new google.maps.OverlayView();
    overlay.draw = function() {};
    overlay.setMap(this.map_);
    var pos = overlay.getProjection().fromLatLngToDivPixel(this.latlng_); 


	  // Resize the image's DIV to fit the indicated dimensions.
	  var div = this.div_;
	  //uncomment if using infowindow
	  pos.x=pos.x-25;
	  pos.y=pos.y-30;
	  
	  //pos.x=pos.x-55;
	  //pos.y=pos.y+40;
	  
	  div.style.left = pos.x + 'px';
	  div.style.top = pos.y + 'px';
	  
};


function getExtInfoWindowBody(destination)
{
	var d = destination;
	
	var dest_city = d.city.name;
	var dest_state = d.region.shortName;
	var dest_country = d.country.shortName;
	
	//dumpProps(d);
	
	var startLocationJS = document.getElementById('startLocation').value;
	startLocationJS = startLocationJS.substr(0,3);
	var startDateJS = document.getElementById('startDate').value;
	var budgetJS = document.getElementById('budget').value;
	var numberPeople = document.getElementById('numberPeople').value;
	var numberNights = document.getElementById('numberNights').value;
	var numroom = Math.ceil(numberPeople/2);
	
	//var endDateJS = document.getElementById('endDate').value;
	function addDays(myDate,days) 
	{
	    return new Date(myDate.getTime() + days*24*60*60*1000);
	}

	var startYearJS = startDateJS.substr(0,4);
	var startMonthJS = startDateJS.substr(5,2);
	var startDayJS = startDateJS.substr(8,2);

	var dt = addDays(new Date(startYearJS, startMonthJS, startDayJS,0,0,0),numberNights);
	
	var dtdate  = dt.getDate();
	var day = (dtdate < 10) ? '0' + dtdate : dtdate;
	var dtmonth = dt.getMonth();
	var month = (dtmonth < 10) ? '0' + dtmonth : dtmonth;

    var endDateJS = dt.getFullYear()+"/"+month+"/"+day;
	
	// date is in the yyyy-mm-dd -> yyyy.mm.dd+hh:ii:ss
	
	var startYearJS = startDateJS.substr(0,4);
	var startMonthJS = startDateJS.substr(5,2);
	var startDayJS = startDateJS.substr(8,2);
	var startDateJS_orbf = startYearJS+"."+startMonthJS+"."+startDayJS+"+00:00:00";
	var startDateJS_expf = checkinDateJS_orbhf= startMonthJS+"/"+startDayJS+"/"+startYearJS;
	
	
	var endYearJS = endDateJS.substr(0,4);
	var endMonthJS = endDateJS.substr(5,2);
	var endDayJS = endDateJS.substr(8,2);
	var endDateJS_orbf = endYearJS+"."+endMonthJS+"."+endDayJS+"+00:00:00";
	var endDateJS_expf = checkoutDateJS_orbhf = endMonthJS+"/"+endDayJS+"/"+endYearJS;
	

	var startDateJS_f = startDateJS.replace(/-/g, "/");
	var endDateJS_f = endDateJS.replace(/-/g, "/");
	
	var budgetFlightPerson = (d.flight);
	var budgetHotelPerDay = Math.round((budgetJS - budgetFlightPerson*numberPeople)/(numberNights*numberPeople));

	var budgetFlight_cents = 100*budgetFlightPerson;

	
	var linkSinergy = "http://click.linksynergy.com/fs-bin/click?id=yX0lNx6nVK0&subid=&offerid=192031.1&type=10&tmpid=2641&RD_PARM1=";
	var orbRequest_hotel = linkSinergy;
	
	orbRequest_hotel += "http://www.orbitz.com/shop/hotelsearch?hotel.hotelSearchDetails.checkinDate="+checkinDateJS_orbhf;
	orbRequest_hotel += "%2526hotel.hotelSearchDetails.rooms[0].numberOfAdults=1%2526strf=8%2526search=Continue%2526hotel.locationKeywordInput.key="+d.airport.shortName+"%2526hotel.typeOfSearch=keyword";
	orbRequest_hotel += "%2526rs=cli.linksynergy.com%2526hotel.hotelSearchDetails.checkoutDate="+checkoutDateJS_orbhf+"%2526type=hotel%2526hotel.hotelSearchDetails.numberOfRooms=1";

	var orbRequest_flight_link = "http://www.orbitz.com/App/SubmitOffSiteFlightSelection?sliceCount=2%2526nf=0%2526sgCt_sl_0=1%2526cc_sl_0_sg_0=CO";
	orbRequest_flight_link += "%2526fn_sl_0_sg_0=6768%2526oa_sl_0_sg_0="+startLocationJS+"%2526da_sl_0_sg_0="+d.airport.shortName+"%2526bc_sl_0_sg_0=T%2526cb_sl_0_sg_0=C";
	orbRequest_flight_link += "%2526dt_sl_0_sg_0="+startDateJS_orbf+"%2526sgCt_sl_1=1%2526cc_sl_1_sg_0=CO%2526fn_sl_1_sg_0=8211";
	orbRequest_flight_link += "%2526oa_sl_1_sg_0="+d.airport.shortName+"%2526da_sl_1_sg_0="+startLocationJS+"%2526bc_sl_1_sg_0=T%2526cb_sl_1_sg_0=C";
	orbRequest_flight_link += "%2526dt_sl_1_sg_0="+endDateJS_orbf;
	orbRequest_flight_link += "%2526adult="+numberPeople+"%2526senior=0%2526youth=0%2526child=0%2526infantWithSeat=0%2526infantWithoutSeat=0";
	orbRequest_flight_link += "%2526price=%2526advertisedPrice="+budgetFlight_cents+"%2526totalBF=%2526taxes=%2526sf=0%2526source=OSA_FRCST%2526searchHost=ITA%2526gcid=C11287x357%2526WT.mc_id=e98%2526WT.mc_ev=click%2526afsrc=1";

	
	var orbRequest_flight  = linkSinergy;
	orbRequest_flight += orbRequest_flight_link;
	
	globDestination = d.airport.shortName;
	var ExpDestId= getExpDestId(globDestination);
	
	
	var expRequest ="http://widgets.partners.expedia.com/TrackingRedirect.aspx?partner=cj&pid=4145858&widgetname=deeplink&products=FlightHotel&searchparams=%26frairport%3D"+startLocationJS+"%26destid%3D"+ExpDestId+"%26";
	expRequest += "destids%3D"+ExpDestId+"%26fromdate%3D"+startDateJS_expf+"%26todate%3D"+endDateJS_expf+"%26numadult%3D"+numberPeople+"%26numroom%3D1%26packagetype%3D2";
	
	//var expRequest ="submit_request.php?partner=cj&pid=4145858&widgetname=deeplink&products=FlightHotel&frairport="+startLocationJS+"&destid="+ExpDestId+"&";
	//expRequest += "destids="+ExpDestId+"&fromdate="+startDateJS_expf+"&todate="+endDateJS_expf+"&numadult="+numberPeople+"&numnights="+numberNights+"&numroom="+numroom+"&packagetype=2&dest_city="+dest_city+"&dest_state="+dest_state+"&dest_country="+dest_country+"&dest_city_code="+globDestination;
	
	
	// we want track this event of opening infobox
	ajaxCallInternal('infobox open', globDestination);
	

	var img_link= getImgSrc(d.airport.shortName);
	
	var starArray = "<img src='images/star_y.gif'></img><img src='images/star_y.gif'></img><img src='images/star_y.gif'></img><img src='images/star_y.gif'></img>";
	var price = (d.flight*numberPeople+numberNights*200);
	var price_f = formatPrice(price);
	var body = [
				"<div id='triangle_out' style='clear: both; width: 0; height: 0; border-left: 12px solid transparent; border-right: 12px solid transparent; border-bottom: 14px solid #666; position:absolute; top:0px;left:106px;'></div>",
				"<div id='triangle_in' style='clear: both; width: 0; height: 0; border-left: 10px solid transparent; border-right: 10px solid transparent; border-bottom: 12px solid #666; position:absolute; top:2px;left:108px; _border-left-color: pink;_border-bottom-color: pink;_border-right-color: pink;_filter: chroma(color=pink);'></div>",
				"<div id='pinWindow' style='clear: both; width:240px; height:196px; background: #ffffff url(css/images/ui-bg_highlight-soft_25_gra.png) repeat-x; z-index:-1;position:absolute; top:24px;left:0px; font-family:Tahoma; font-size: 13px; font-weight: 700; -moz-box-shadow: 0 0 3px 3px #666; -webkit-box-shadow: 0 0 3px 3px#666; box-shadow: 0 0 3px 3px #666;",
				"border:solid 3px;border-color:#666; border-radius: 5px; -moz-border-radius: 5px; text-align:center; margin: -10px 0px 0px 0px; padding: 3px 0 10px 0; overflow-y:auto;'>"+d.airport.name +"("+d.airport.shortName+")",
				
				"<br/><br/><table width=236 cellpadding=0 cellspacing=0 border=0>" +
				"<tr><td width=124 class=left><span style='font-family:Tahoma; font-size: 11px; font-weight: normal; text-align:left; overflow-y:auto;'>&nbsp;&nbsp;&nbsp;Airfare "+numberPeople+" ppl roundtrip<br>&nbsp;&nbsp;&nbsp;Hotel "+numberPeople+" ppl "+numberNights+" nights</span></td>",
				"<td width=32 rowspan=2><center><img src='images/arrow_right_bblue.png'></img></center></td>",
				"<td width=80 rowspan=2><center><span style='font-family:Tahoma; font-size: 24px; font-weight: normal; color:#000; text-align:center; overflow-y:auto;'>" + price_f + "</span><br><span style='font-family:Tahoma; font-size: 12px; text-align:center; overflow-y:auto;'><a id='flight_link' target='_blank' href="+"'"+expRequest+"'"+">Book Now</a></span></td></tr>",
				"<tr><td width=124 class=left>&nbsp;&nbsp;"+starArray+"</td></tr>",
				"<tr><td colspan=3 height=110>",
				"<center><span style='text-align:center; padding: 2px;'><a target='_blank' href="+"'"+orbRequest_flight+"'"+"><img width=220 height=100 border=0 src='" +img_link+"'</img></a></span></center>",
				"</td></tr></table></div>"
				];

	return body.join("");
	
	
	function getExpDestId(airport_code) 
	{
			var destId = "";
			$.ajax({
			  url: 'hub_ExpDestId.php',
			  async: false,
			  data: "airport_code=" + airport_code,
			  dataType: 'json',
			  success: function (json) {
				if (json.data.destId == false) 
				{
					destId="0";
				}
				else 
				{
					destId=(json.data.destId);
				}
			  }
			});
			
			return destId;
	}
	
	function getImgSrc(airport_code) 
	{
		var img_src = "";
		$.ajax({
			url: 'hub_image.php',
			async: false,
			data: "airport_code=" + airport_code,
			dataType: 'json',
			success: function (json) {
				if (json.image_data.city_image == false) 
				{
					img_src="fruitfly.png";
				}
				else 
				{
					img_src=(json.image_data.city_image);
				}
			}
		});
			
		var img_src_link="images_destinations/"+img_src;
		return img_src_link;
	}
	
	function ajaxCallInternal(event, destination)
	{
		   $.ajaxSetup({
				cache:false,
				dataType: 'json',
				error: function(xhr, status, error)
				{
					//alert('An error occured ' + error);
				},
				type: 'POST',
				url: 'hub_ajax_listener.php'
		    });
		    $.ajax({
				data: {event : event, destination : destination, form_data : $("#travelForm").serialize()},
				success: function(data) {
		    	//
		    	}
		    });
	 }

};


addMarkerOverlay.prototype.onRemove = function() {
	  this.div_.parentNode.removeChild(this.div_);
	  this.div_ = null;
	}

//Note that the visibility property must be a string enclosed in quotes
addMarkerOverlay.prototype.hide = function() {
  if (this.div_) {
    this.div_.style.visibility = "hidden";
  }
}

addMarkerOverlay.prototype.show = function() {
  if (this.div_) {
    this.div_.style.visibility = "visible";
  }
}

addMarkerOverlay.prototype.toggle = function() {
  if (this.div_) {
    if (this.div_.style.visibility == "hidden") {
      this.show();
    } else {
      this.hide();
    }
  }
}

addMarkerOverlay.prototype.toggleDOM = function() {
  if (this.getMap()) {
    this.setMap(null);
  } else {
    this.setMap(this.map_);
  }
}

function formatPrice(price) {
	return "$" + price + ""
}
