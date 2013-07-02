/**
 * @author ocbeta
 */
// generated Wed Aug 25 20:59:39 EDT 2010

var airportVal, closeImage = '<img class="close" src="/images/close-x-1833cc.gif" />', defaultBudgetValues = [
		0, 5000 ], defaultTempValues = [ -58, 120 ], lowtemp = -1000, hightemp = 1000, destinationMarkerClustererOpts = {
	gridSize : jq.browser.msie ? 45 : 30
}, extInfoWindowOpts = {
	beakAnchor : new GPoint(-15, 0),
	beakOffset : 7,
	paddingLeft : 80,
	paddingRight : 30,
	paddingTop : 114
}, fromVal, hotelMarkerCluster = null, hotelMarkers = [], hotelNights = 6, hotelStars = 3, markers = [], map, markerCluster = null, msgs = {
	flightInfo : function(fromVal, airportVal, toairport, toshortName) {
		return "" + fromVal + " (" + airportVal + ") to " + toairport + " ("
				+ toshortName + ")"
	},
	flightInfoShort : function(toairport, toshortName) {
		return "" + toairport + " (" + toshortName + ")"
	},
	hotelNightsFilter : function(hotelNights) {
		return hotelNights === 1 ? "" + hotelNights + " night" : ""
				+ hotelNights + " nights"
	},
	hotelStarsFilter : function(hotelStars) {
		return hotelStars === 1 ? "" + hotelStars + " star" : "" + hotelStars
				+ " stars"
	},
	hotelUserRating : function(rating, url, count) {
		return rating ? "<strong>" + rating
				+ "</strong>/5 user rating (<a href='" + url
				+ "' target='blank_'>" + count + " reviews</a>)"
				: "No user rating"
	},
	tempRange : function(low, high, unit) {
		return "" + low + " to " + high + " " + unit + ""
	}
}, originMarker = null, tempUnit = "&deg;F";
function hideTooltip_() {
	jq("#tooltip").remove()
}
function showTooltip_() {
	var extInfoWindow = this.map_.getExtInfoWindow();
	if (!extInfoWindow || extInfoWindow.getMarker() !== this) {
		var container = this.map_.getContainer(), tooltip = jq("<div/>", {
			id : "tooltip",
			html : this.getTooltipText_()
		}).appendTo(container)[0], anchor = this.getIcon().iconAnchor, width = this
				.getIcon().iconSize.width, tipHeight = tooltip.clientHeight, tipWidth = tooltip.clientWidth, pixel = this.map_
				.fromLatLngToContainerPixel(this.getLatLng()), x, y, w = jq(
				container).width();
		if (pixel.x > w - tipWidth) {
			x = pixel.x - anchor.x - tipWidth
		} else {
			x = pixel.x - anchor.x + width
		}
		if (pixel.y - 20 < tipHeight) {
			y = pixel.y - anchor.y + this.getIcon().iconSize.height
		} else {
			y = pixel.y - anchor.y - tipHeight
		}
		jq(tooltip).css( {
			left : x,
			top : y
		})
	}
}
jq.fn.checkboxGroup = function(fn, vals) {
	var group = this;
	if (!fn) {
		group.change(function() {
			if (jq(this).val() == "any") {
				jq(this).attr("checked", true);
				group.not(this).attr("checked", false)
			} else {
				group.first().attr("checked",
						group.filter(":checked").size() === 0)
			}
			addressUpdate();
			updateWhatStatus();
			filter()
		})
	} else {
		if (fn === "values") {
			if (vals) {
				var i, selector = [];
				for (i = 0; i < vals.length; i++) {
					selector.push("[value=" + vals[i] + "]")
				}
				group.attr("checked", false).filter(selector.join(",")).attr(
						"checked", true)
			} else {
				vals = [];
				group.filter(":checked").each(function() {
					vals.push(jq(this).val())
				});
				return vals
			}
		} else {
			if (fn === "labelValues") {
				vals = [];
				group.filter(":checked").each(function() {
					if (jq(this).val() === "any") {
						vals.push("any")
					} else {
						vals.push(jq.trim(jq(this).parent().text()))
					}
				});
				return vals
			}
		}
	}
};
function Cluster(markerClusterer) {
	var center_ = null;
	var centerAsDivPixels_ = [];
	var markers_ = [];
	var markerClusterer_ = markerClusterer;
	var map_ = markerClusterer.getMap_();
	var clusterMarker_ = null;
	var zoom_ = map_.getZoom();
	this.getMarkers = function() {
		return markers_
	};
	this.getPrice = function() {
		var prices = [];
		for ( var i = 0, length = markers_.length; i < length; i++) {
			prices.push(markers_[i].marker.getPrice())
		}
		return Math.min.apply(Math, prices)
	};
	this.isInBounds = function(bounds) {
		if (center_ === null) {
			return false
		}
		if (!bounds) {
			bounds = map_.getBounds()
		}
		var sw = map_.fromLatLngToDivPixel(bounds.getSouthWest());
		var ne = map_.fromLatLngToDivPixel(bounds.getNorthEast());
		var centerxy = map_.fromLatLngToDivPixel(center_);
		var inViewport = true;
		var gridSize = markerClusterer.getGridSize_();
		if (zoom_ !== map_.getZoom()) {
			var dl = map_.getZoom() - zoom_;
			gridSize = Math.pow(2, dl) * gridSize
		}
		if (ne.x !== sw.x
				&& (centerxy.x + gridSize < sw.x || centerxy.x - gridSize > ne.x)) {
			inViewport = false
		}
		if (inViewport
				&& (centerxy.y + gridSize < ne.y || centerxy.y - gridSize > sw.y)) {
			inViewport = false
		}
		return inViewport
	};
	this.getCenter = function() {
		return center_
	};
	this.getCenterAsDivPixel = function(zoom) {
		zoom = zoom || map_.getZoom();
		if (center_ !== null) {
			if (typeof centerAsDivPixels_[zoom] === "undefined") {
				centerAsDivPixels_[zoom] = map_.fromLatLngToDivPixel(center_)
			}
			return centerAsDivPixels_[zoom]
		} else {
			return null
		}
	};
	this.addMarker = function(marker) {
		if (center_ === null) {
			center_ = marker.marker.getLatLng()
		}
		markers_.push(marker)
	};
	this.removeMarker = function(marker) {
		for ( var i = 0; i < markers_.length; ++i) {
			if (marker === markers_[i].marker) {
				if (markers_[i].isAdded) {
					map_.removeOverlay(markers_[i].marker)
				}
				markers_.splice(i, 1);
				return true
			}
		}
		return false
	};
	this.getCurrentZoom = function() {
		return zoom_
	};
	this.redraw_ = function(isForce) {
		if (!isForce && !this.isInBounds()) {
			return
		}
		zoom_ = map_.getZoom();
		var i = 0;
		var mz = markerClusterer.getMaxZoom_();
		if (mz === null) {
			mz = map_.getCurrentMapType().getMaximumResolution()
		}
		if (zoom_ >= mz || this.getTotalMarkers() === 1) {
			for (i = 0; i < markers_.length; ++i) {
				if (markers_[i].isAdded) {
					if (markers_[i].marker.isHidden()) {
						markers_[i].marker.show()
					}
				} else {
					map_.addOverlay(markers_[i].marker);
					markers_[i].isAdded = true
				}
			}
			if (clusterMarker_ !== null) {
				clusterMarker_.hide()
			}
		} else {
			for (i = 0; i < markers_.length; ++i) {
				if (markers_[i].isAdded && (!markers_[i].marker.isHidden())) {
					markers_[i].marker.hide()
				}
			}
			if (clusterMarker_ === null) {
				clusterMarker_ = new markerClusterer_.ClusterMarker_(center_,
						this, markerClusterer_.getGridSize_());
				map_.addOverlay(clusterMarker_)
			} else {
				if (clusterMarker_.isHidden()) {
					clusterMarker_.show()
				}
				clusterMarker_.redraw(true)
			}
		}
	};
	this.clearMarkers = function() {
		if (clusterMarker_ !== null) {
			map_.removeOverlay(clusterMarker_)
		}
		for ( var i = 0; i < markers_.length; ++i) {
			if (markers_[i].isAdded) {
				map_.removeOverlay(markers_[i].marker)
			}
		}
		markers_ = []
	};
	this.getTotalMarkers = function() {
		return markers_.length
	};
	this.getMarkerClusterer_ = function() {
		return markerClusterer_
	}
}
function ClusterMarker_(latlng, cluster, padding) {
	this.cluster_ = cluster;
	this.latlng_ = latlng;
	this.padding_ = padding
}
ClusterMarker_.prototype = new GOverlay();
ClusterMarker_.prototype.copy = function() {
	return new ClusterMarker_(this.latlng_, this.cluster_, this.padding_)
};
ClusterMarker_.prototype.getLatLng = function() {
	return this.latlng_
};
ClusterMarker_.prototype.hide = function() {
	this.div_.style.display = "none"
};
ClusterMarker_.prototype.isHidden = function() {
	return this.div_.style.display === "none"
};
ClusterMarker_.prototype.remove = function() {
	this.div_.parentNode.removeChild(this.div_)
};
ClusterMarker_.prototype.show = function() {
	this.div_.style.display = ""
};
ClusterMarker_.prototype.zoom_ = function() {
	var map = this.cluster_.getMarkerClusterer_().getMap_();
	map.closeExtInfoWindow();
	var pos = map.fromLatLngToDivPixel(this.getLatLng());
	var sw = new GPoint(pos.x - this.padding_, pos.y + this.padding_);
	sw = map.fromDivPixelToLatLng(sw);
	var ne = new GPoint(pos.x + this.padding_, pos.y - this.padding_);
	ne = map.fromDivPixelToLatLng(ne);
	var zoom = map.getBoundsZoomLevel(new GLatLngBounds(sw, ne), map.getSize());
	map.setCenter(this.getLatLng(), zoom)
};
function DestinationMarker(dest) {
	this.dest_ = dest;
	this.latlng_ = new GLatLng(dest.airport.latitude, dest.airport.longitude)
}
DestinationMarker.prototype = new GOverlay();
DestinationMarker.prototype.height_ = 30;
DestinationMarker.prototype.width_ = 51;
DestinationMarker.prototype.icon_ = {
	iconAnchor : new GPoint(22, 26),
	iconSize : new GSize(DestinationMarker.prototype.width_,
			DestinationMarker.prototype.height_),
	infoWindowAnchor : new GPoint(22, 2)
};
DestinationMarker.prototype.bindExtInfoWindowEvents = function(overlay) {
	var that = this;
	jq("#pinWindow h3 a").click(function() {
		function _priceAlerts() {
			priceAlerts(overlay, that.dest_)
		}
		if (isAnon && !AjaxReg.alreadyRegistered) {
			AjaxReg.callbacksuccess = _priceAlerts;
			AjaxReg.showRegister()
		} else {
			_priceAlerts()
		}
		return false
	});
	jq("#pinWindowFlights a").click(function() {
		searchFlights(overlay, that.dest_);
		return false
	});
	jq("#pinWindowHotels a").click(function() {
		map.closeExtInfoWindow();
		fetchHotels(that.dest_.airport.shortName);
		return false
	})
};
DestinationMarker.prototype.closeExtInfoWindow = function() {
	map.closeExtInfoWindow()
};
DestinationMarker.prototype.copy = function() {
	return new DestinationMarker(this.dest_)
};
DestinationMarker.prototype.getDestination = function() {
	return this.dest_
};
DestinationMarker.prototype.getExtInfoWindowBody = function() {
	var d = this.dest_;
	if (!d.hotel) {
		jq.ajax( {
			async : false,
			url : "/h/explore/hotelavg",
			dataType : "json",
			data : {
				airport : d.airport.shortName
			},
			success : function(r) {
				d.hotel = r
			}
		})
	}
	var body = [
			'<div id="pinWindow">',
			closeImage,
			"<h3>",
			msgs.flightInfoShort(d.airport.name, d.airport.shortName),
			'<br/><a href="#">get price alert</a></h3>',
			'<p id="pinWindowFlights">',
			"<a href='#'>" + formatPrice(d.flight) + "+</a> per person<br/>"
					+ formatStops(d.flightMaxStops) + ", "
					+ formatDuration(d.flightMaxDuration) + "",
			'<br/><a href="#">check latest airfare</a>', "</p>",
			'<p id="pinWindowHotels">' ];
	var hotelRating = 3, hotelRate = d.hotel[hotelRating];
	if (hotelRate === -1) {
		for (hotelRating = 0; hotelRating <= 5; hotelRating++) {
			hotelRate = d.hotel[hotelRating];
			if (hotelRate === -1) {
				break
			}
		}
	}
	if (hotelRate === -1) {
		body.push("rates not available");
		hotelRating = 0
	} else {
		body.push("<a href='#'>" + formatPrice(hotelRate)
				+ "</a> avg per night")
	}
	body.push('<br/><img src="/images/kstar', hotelRating, '.gif"/>',
			'<br/><a href="#">show nearby hotels</a>', "</p>", "</div>");
	return body.join("")
};
DestinationMarker.prototype.getIcon = function() {
	return this.icon_
};
DestinationMarker.prototype.getLatLng = function() {
	return this.latlng_
};
DestinationMarker.prototype.getPrice = function() {
	return this.dest_.flight
};
DestinationMarker.prototype.getTemps = function() {
	return this.dest_.temp
};
DestinationMarker.prototype.hide = function() {
	this.div_.style.display = "none"
};
DestinationMarker.prototype.initialize = function(map) {
	this.map_ = map;
	var pos = map.fromLatLngToDivPixel(this.latlng_);
	var price = formatPrice(this.getPrice());
	pos.x -= this.icon_.iconAnchor.x;
	pos.y -= this.icon_.iconAnchor.y;
	var that = this;
	this.div_ = document.createElement("div");
	jq(this.div_).addClass("pin").click(function() {
		that.openExtInfoWindow()
	}).html("<div>" + price + "+</div>");
	if (price.length > 6) {
		jq(this.div_).addClass("widepin")
	}
	map.getPane(G_MAP_MAP_PANE).appendChild(this.div_)
};
DestinationMarker.prototype.isHidden = function() {
	return !this.div_ || this.div_.style.display === "none"
};
DestinationMarker.prototype.openExtInfoWindow = function() {
	if (arguments.length === 0) {
		GMarker.prototype.openExtInfoWindow.call(this, map, "commonWindow",
				this.getExtInfoWindowBody(), extInfoWindowOpts);
		this.bindExtInfoWindowEvents(this)
	} else {
		GMarker.prototype.openExtInfoWindow.apply(this, arguments)
	}
};
DestinationMarker.prototype.redraw = function() {
	var pos = this.map_.fromLatLngToDivPixel(this.latlng_);
	pos.x -= this.icon_.iconAnchor.x;
	pos.y -= this.icon_.iconAnchor.y;
	this.div_.style.top = pos.y + "px";
	this.div_.style.left = pos.x + "px"
};
DestinationMarker.prototype.remove = function() {
	GEvent.clearInstanceListeners(this.div_);
	if (this.div_.outerHTML) {
		this.div_.outerHTML = ""
	}
	if (this.div_.parentNode) {
		this.div_.parentNode.removeChild(this.div_)
	}
	this.div_ = null
};
DestinationMarker.prototype.show = function() {
	this.div_.style.display = ""
};
function DestinationClusterMarker_(latlng, cluster, padding) {
	ClusterMarker_.apply(this, arguments);
	this.text_ = formatPrice(cluster.getPrice()) + "+"
}
DestinationClusterMarker_.prototype = new ClusterMarker_();
DestinationClusterMarker_.prototype.height_ = 35;
DestinationClusterMarker_.prototype.width_ = 55;
DestinationClusterMarker_.prototype.icon_ = {
	iconAnchor : new GPoint(22, 30),
	iconSize : new GSize(DestinationClusterMarker_.prototype.width_,
			DestinationClusterMarker_.prototype.height_),
	infoWindowAnchor : new GPoint(22, 2)
};
DestinationClusterMarker_.prototype.closeExtInfoWindow = function() {
	map.closeExtInfoWindow()
};
DestinationClusterMarker_.prototype.copy = function() {
	return new DestinationClusterMarker_(this.latlng_, this.cluster_,
			this.padding_)
};
DestinationClusterMarker_.prototype.getIcon = function() {
	return this.icon_
};
DestinationClusterMarker_.prototype.initialize = function(map) {
	this.map_ = map;
	var latlng = this.latlng_;
	var pos = map.fromLatLngToDivPixel(latlng);
	pos.x -= this.icon_.iconAnchor.x;
	pos.y -= this.icon_.iconAnchor.y;
	var pin = document.createElement("div");
	pin.className = "cluster";
	pin.style.cssText = [ "left:" + pos.x + "px", "top:" + pos.y + "px" ]
			.join(";");
	var text = document.createElement("div");
	text.innerHTML = this.text_;
	if (this.text_.length > 6) {
		jq(pin).addClass("widecluster")
	}
	pin.appendChild(text);
	map.getPane(G_MAP_MAP_PANE).appendChild(pin);
	var that = this;
	jq(pin).click(function() {
		that.openExtInfoWindow()
	});
	this.div_ = pin
};
DestinationClusterMarker_.prototype.openExtInfoWindow = function() {
	if (arguments.length === 0) {
		var that = this;
		var map = this.map_;
		var markers = this.cluster_.getMarkers().slice(0), markersLength = markers.length;
		markers
				.sort(function(a, b) {
					var aMarker = a.marker, bMarker = b.marker;
					var aPrice = aMarker.getPrice(), bPrice = bMarker
							.getPrice();
					var priceCmp = (aPrice > bPrice) - (aPrice < bPrice);
					if (priceCmp === 0) {
						var aName = aMarker.getDestination().airport.name, bName = bMarker
								.getDestination().airport.name;
						return (aName > bName) - (aName < bName)
					} else {
						return priceCmp
					}
				});
		var body = [
				'<div id="clusterWindow">',
				closeImage,
				"<h3>",
				"" + markersLength + " Flights from " + fromVal + " ("
						+ airportVal + ")", "</h3>", "<ul>" ];
		for ( var i = 0, length = Math.min(5, markersLength); i < length; i++) {
			var m = markers[i].marker;
			body.push("<li><a href='#'> " + formatPrice(m.getPrice())
					+ "+</a> to <strong>" + m.getDestination().airport.name
					+ "</strong> (" + m.getDestination().airport.shortName
					+ ") <span>- "
					+ formatStops(m.getDestination().flightMaxStops) + ", "
					+ formatDuration(m.getDestination().flightMaxDuration)
					+ " </span></li>")
		}
		body
				.push('</ul><a id="clusterWindowZoom" href="#">Zoom in to see more</a></div>');
		GMarker.prototype.openExtInfoWindow.call(this, this.map_,
				"wideCommonWindow", body.join(""), extInfoWindowOpts);
		jq("#clusterWindow li a").each(
				function(i) {
					jq(this).click(
							function() {
								var m = markers[i].marker;
								that.openExtInfoWindow.call(that, that.map_,
										"commonWindow", m
												.getExtInfoWindowBody(),
										extInfoWindowOpts);
								m.bindExtInfoWindowEvents(that);
								return false
							})
				});
		jq("#clusterWindowZoom").click(function() {
			that.zoom_();
			return false
		})
	} else {
		GMarker.prototype.openExtInfoWindow.apply(this, arguments)
	}
};
DestinationClusterMarker_.prototype.redraw = function(force) {
	if (!force) {
		return
	}
	var pos = this.map_.fromLatLngToDivPixel(this.latlng_);
	pos.x -= this.icon_.iconAnchor.x;
	pos.y -= this.icon_.iconAnchor.y;
	this.div_.style.top = pos.y + "px";
	this.div_.style.left = pos.x + "px"
};
function DestinationMarkerClusterer() {
	this.ClusterMarker_ = DestinationClusterMarker_;
	MarkerClusterer.apply(this, arguments)
}
function ExtInfoWindow(marker, windowId, html, opt_opts) {
	this.html_ = html;
	this.marker_ = marker;
	this.infoWindowId_ = windowId;
	this.options_ = opt_opts || {};
	this.beakAnchor_ = this.options_.beakAnchor || new GPoint(0, 0);
	this.borderSize_ = this.options_.beakOffset || 0;
	this.paddingTop_ = this.options_.paddingTop ? this.options_.paddingTop
			+ this.borderSize_ : this.borderSize_;
	this.paddingRight_ = this.options_.paddingRight ? this.options_.paddingRight
			+ this.borderSize_
			: this.borderSize_;
	this.paddingBottom_ = this.options_.paddingBottom ? this.options_.paddingBottom
			+ this.borderSize_
			: this.borderSize_;
	this.paddingLeft_ = this.options_.paddingLeft ? this.options_.paddingLeft
			+ this.borderSize_ : this.borderSize_;
	this.map_ = null;
	this.container_ = document.createElement("div");
	this.container_.style.position = "relative";
	this.container_.style.display = "none";
	this.contentDiv_ = document.createElement("div");
	this.contentDiv_.id = this.infoWindowId_ + "_contents";
	this.contentDiv_.innerHTML = this.html_;
	this.contentDiv_.style.display = "block";
	this.contentDiv_.style.visibility = "hidden";
	this.wrapperDiv_ = document.createElement("div")
}
ExtInfoWindow.prototype = new GOverlay();
ExtInfoWindow.prototype.copy = function() {
	return new ExtInfoWindow(this.marker_, this.infoWindowId_, this.html_,
			this.options_)
};
ExtInfoWindow.prototype.getMarker = function() {
	return this.marker_
};
ExtInfoWindow.prototype.initialize = function(map) {
	this.map_ = map;
	this.defaultStyles = {
		containerWidth : map.getSize().width / 2,
		borderSize : 1
	};
	this.wrapperParts = {
		tl : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		},
		t : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		},
		tr : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		},
		l : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		},
		r : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		},
		bl : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		},
		b : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		},
		br : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		},
		beak : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		},
		close : {
			t : 0,
			l : 0,
			w : 0,
			h : 0,
			domElement : null
		}
	};
	for ( var i in this.wrapperParts) {
		var tempElement = document.createElement("div");
		tempElement.id = this.infoWindowId_ + "_" + i;
		tempElement.style.visibility = "hidden";
		document.body.appendChild(tempElement);
		tempElement = document.getElementById(this.infoWindowId_ + "_" + i);
		var tempWrapperPart = this.wrapperParts[i];
		tempWrapperPart.w = jq(tempElement).width();
		tempWrapperPart.h = jq(tempElement).height();
		document.body.removeChild(tempElement)
	}
	for ( var i in this.wrapperParts) {
		if (i == "close") {
			this.wrapperDiv_.appendChild(this.contentDiv_)
		}
		var wrapperPartsDiv = null;
		if (this.wrapperParts[i].domElement == null) {
			wrapperPartsDiv = document.createElement("div");
			this.wrapperDiv_.appendChild(wrapperPartsDiv)
		} else {
			wrapperPartsDiv = this.wrapperParts[i].domElement
		}
		wrapperPartsDiv.id = this.infoWindowId_ + "_" + i;
		wrapperPartsDiv.style.position = "absolute";
		wrapperPartsDiv.style.width = this.wrapperParts[i].w + "px";
		wrapperPartsDiv.style.height = this.wrapperParts[i].h + "px";
		wrapperPartsDiv.style.top = this.wrapperParts[i].t + "px";
		wrapperPartsDiv.style.left = this.wrapperParts[i].l + "px";
		this.wrapperParts[i].domElement = wrapperPartsDiv
	}
	this.map_.getPane(G_MAP_FLOAT_PANE).appendChild(this.container_);
	this.container_.id = this.infoWindowId_;
	var containerWidth = jq("#" + this.infoWindowId_).outerWidth();
	this.container_.style.width = containerWidth
			|| this.defaultStyles.containerWidth;
	this.map_.getContainer().appendChild(this.contentDiv_);
	var contentWidth = jq(this.container_).outerWidth();
	this.contentDiv_.style.width = contentWidth + "px";
	this.contentDiv_.style.position = "absolute";
	this.container_.appendChild(this.wrapperDiv_);
	GEvent.bindDom(this.container_, "mousedown", this, this.onClick_);
	GEvent.bindDom(this.container_, "dblclick", this, this.onClick_);
	GEvent.bindDom(this.container_, "DOMMouseScroll", this, this.onClick_);
	GEvent.trigger(this.map_, "extinfowindowopen")
};
ExtInfoWindow.prototype.onClick_ = function(e) {
	if (navigator.userAgent.toLowerCase().indexOf("msie") != -1 && document.all) {
		window.event.cancelBubble = true;
		window.event.returnValue = false
	} else {
		e.stopPropagation()
	}
};
ExtInfoWindow.prototype.redraw = function(force) {
	if (!force || this.container_ == null) {
		return
	}
	var contentHeight = this.contentDiv_.offsetHeight;
	this.contentDiv_.style.height = contentHeight + "px";
	this.contentDiv_.style.left = this.wrapperParts.l.w + "px";
	this.contentDiv_.style.top = this.wrapperParts.tl.h + "px";
	this.contentDiv_.style.visibility = "visible";
	contentHeight = jq(this.contentDiv_).outerHeight();
	var contentWidth = jq(this.contentDiv_).outerWidth();
	this.wrapperParts.tl.t = 0;
	this.wrapperParts.tl.l = 0;
	this.wrapperParts.t.l = this.wrapperParts.tl.w;
	this.wrapperParts.t.w = (this.wrapperParts.l.w + contentWidth + this.wrapperParts.r.w)
			- this.wrapperParts.tl.w - this.wrapperParts.tr.w;
	this.wrapperParts.t.h = this.wrapperParts.tl.h;
	this.wrapperParts.tr.l = this.wrapperParts.t.w + this.wrapperParts.tl.w;
	this.wrapperParts.l.t = this.wrapperParts.tl.h;
	this.wrapperParts.l.h = contentHeight;
	this.wrapperParts.r.l = contentWidth + this.wrapperParts.l.w;
	this.wrapperParts.r.t = this.wrapperParts.tr.h;
	this.wrapperParts.r.h = contentHeight;
	this.wrapperParts.bl.t = contentHeight + this.wrapperParts.tl.h;
	this.wrapperParts.b.l = this.wrapperParts.bl.w;
	this.wrapperParts.b.t = contentHeight + this.wrapperParts.tl.h;
	this.wrapperParts.b.w = (this.wrapperParts.l.w + contentWidth + this.wrapperParts.r.w)
			- this.wrapperParts.bl.w - this.wrapperParts.br.w;
	this.wrapperParts.b.h = this.wrapperParts.bl.h;
	this.wrapperParts.br.l = this.wrapperParts.b.w + this.wrapperParts.bl.w;
	this.wrapperParts.br.t = contentHeight + this.wrapperParts.tr.h;
	this.wrapperParts.close.l = this.wrapperParts.tr.l + this.wrapperParts.tr.w
			- this.wrapperParts.close.w - this.borderSize_;
	this.wrapperParts.close.t = this.borderSize_;
	this.wrapperParts.beak.l = this.borderSize_ + (contentWidth / 2)
			- (this.wrapperParts.beak.w / 2);
	this.wrapperParts.beak.t = this.wrapperParts.bl.t + this.wrapperParts.bl.h
			- this.borderSize_;
	for ( var i in this.wrapperParts) {
		if (i == "close") {
			this.wrapperDiv_.insertBefore(this.contentDiv_,
					this.wrapperParts[i].domElement)
		}
		var wrapperPartsDiv = null;
		if (this.wrapperParts[i].domElement == null) {
			wrapperPartsDiv = document.createElement("div");
			this.wrapperDiv_.appendChild(wrapperPartsDiv)
		} else {
			wrapperPartsDiv = this.wrapperParts[i].domElement
		}
		wrapperPartsDiv.id = this.infoWindowId_ + "_" + i;
		wrapperPartsDiv.style.position = "absolute";
		wrapperPartsDiv.style.width = this.wrapperParts[i].w + "px";
		wrapperPartsDiv.style.height = this.wrapperParts[i].h + "px";
		wrapperPartsDiv.style.top = this.wrapperParts[i].t + "px";
		wrapperPartsDiv.style.left = this.wrapperParts[i].l + "px";
		this.wrapperParts[i].domElement = wrapperPartsDiv
	}
	var currentMarker = this.marker_;
	var thisMap = this.map_;
	GEvent.addDomListener(this.wrapperParts.close.domElement, "click",
			function() {
				thisMap.closeExtInfoWindow()
			});
	var pixelLocation = this.map_
			.fromLatLngToDivPixel(this.marker_.getLatLng());
	this.container_.style.position = "absolute";
	var markerIcon = this.marker_.getIcon();
	this.container_.style.left = (pixelLocation.x - (contentWidth / 2)
			- markerIcon.iconAnchor.x + markerIcon.infoWindowAnchor.x - this.beakAnchor_.x)
			+ "px";
	this.container_.style.top = (pixelLocation.y - this.wrapperParts.bl.h
			- contentHeight - this.wrapperParts.tl.h - this.wrapperParts.beak.h
			- markerIcon.iconAnchor.y + markerIcon.infoWindowAnchor.y
			+ this.borderSize_ - this.beakAnchor_.y)
			+ "px";
	this.container_.style.display = "block";
	if (this.map_.getExtInfoWindow() != null) {
		this.repositionMap_()
	}
};
ExtInfoWindow.prototype.remove = function() {
	if (this.map_.getExtInfoWindow() != null) {
		GEvent.trigger(this.map_, "extinfowindowbeforeclose");
		GEvent.clearInstanceListeners(this.container_);
		if (this.container_.outerHTML) {
			this.container_.outerHTML = ""
		}
		if (this.container_.parentNode) {
			this.container_.parentNode.removeChild(this.container_)
		}
		this.container_ = null;
		GEvent.trigger(this.map_, "extinfowindowclose");
		this.map_.setExtInfoWindow_(null)
	}
};
ExtInfoWindow.prototype.repositionMap_ = function() {
	var mapNE = this.map_.fromLatLngToDivPixel(this.map_.getBounds()
			.getNorthEast());
	var mapSW = this.map_.fromLatLngToDivPixel(this.map_.getBounds()
			.getSouthWest());
	var markerPosition = this.map_.fromLatLngToDivPixel(this.marker_
			.getLatLng());
	var panX = 0;
	var panY = 0;
	var infoWindowAnchor = this.marker_.getIcon().infoWindowAnchor;
	var iconAnchor = this.marker_.getIcon().iconAnchor;
	var windowT = this.wrapperParts.t.domElement;
	var windowL = this.wrapperParts.l.domElement;
	var windowB = this.wrapperParts.b.domElement;
	var windowR = this.wrapperParts.r.domElement;
	var windowBeak = this.wrapperParts.beak.domElement;
	var offsetTop = markerPosition.y
			- (-infoWindowAnchor.y + iconAnchor.y + jq(windowBeak).height()
					+ jq(windowB).height() + jq(windowL).height()
					+ jq(windowT).height() + this.paddingTop_);
	if (offsetTop < mapNE.y) {
		panY = mapNE.y - offsetTop
	} else {
		var offsetBottom = markerPosition.y + this.paddingBottom_;
		if (offsetBottom >= mapSW.y) {
			panY = -(offsetBottom - mapSW.y)
		}
	}
	var offsetRight = Math.round(markerPosition.x + jq(this.container_).width()
			/ 2 + jq(windowR).width() + this.paddingRight_ + infoWindowAnchor.x
			- iconAnchor.x);
	if (offsetRight > mapNE.x) {
		panX = -(offsetRight - mapNE.x)
	} else {
		var offsetLeft = -(Math
				.round((jq(this.container_).width() / 2 - this.marker_
						.getIcon().iconSize.width / 2)
						+ jq(windowL).width()
						+ this.borderSize_
						+ this.paddingLeft_)
				- markerPosition.x - infoWindowAnchor.x + iconAnchor.x);
		if (offsetLeft < mapSW.x) {
			panX = mapSW.x - offsetLeft
		}
	}
	if (panX != 0 || panY != 0 && this.map_.getExtInfoWindow() != null) {
		this.map_.panBy(new GSize(panX, panY))
	}
};
GMap.prototype.ExtInfoWindowInstance_ = null;
GMap.prototype.ClickListener_ = null;
GMap.prototype.InfoWindowListener_ = null;
GMap.prototype.ZoomEndListener_ = null;
GMap2.prototype.getExtInfoWindow = function() {
	return this.ExtInfoWindowInstance_
};
GMap2.prototype.setExtInfoWindow_ = function(extInfoWindow) {
	this.ExtInfoWindowInstance_ = extInfoWindow
};
GMap2.prototype.closeExtInfoWindow = function() {
	if (this.getExtInfoWindow() != null) {
		this.ExtInfoWindowInstance_.remove()
	}
};
GMarker.prototype.openExtInfoWindow = function(map, cssId, html, opt_opts) {
	if (map == null) {
		throw "Error in GMarker.openExtInfoWindow: map cannot be null";
		return false
	}
	if (cssId == null || cssId == "") {
		throw "Error in GMarker.openExtInfoWindow: must specify a cssId";
		return false
	}
	map.closeInfoWindow();
	map.closeExtInfoWindow();
	if (map.getExtInfoWindow() == null) {
		map.setExtInfoWindow_(new ExtInfoWindow(this, cssId, html, opt_opts));
		if (map.ClickListener_ == null) {
			map.ClickListener_ = GEvent.addListener(map, "click", function(
					overlay) {
				if (!overlay) {
					map.closeExtInfoWindow()
				}
			})
		}
		if (map.InfoWindowListener_ == null) {
			map.InfoWindowListener_ = GEvent.addListener(map, "infowindowopen",
					function() {
						map.closeExtInfoWindow()
					})
		}
		if (map.ZoomEndListener_ == null) {
			map.ZoomEndListener_ = GEvent.addListener(map, "zoomend",
					function() {
						map.closeExtInfoWindow()
					})
		}
		map.addOverlay(map.getExtInfoWindow())
	}
};
GMarker.prototype.closeExtInfoWindow = function(map) {
	map.closeExtInfoWindow()
};
function HotelClusterMarker_(latlng, cluster, padding) {
	this.cluster_ = cluster;
	this.padding_ = padding;
	GMarker.call(this, latlng, {
		icon : HotelClusterMarker_.prototype.icon_
	});
	GEvent.addListener(this, "click", function() {
		hideTooltip_();
		this.zoom_()
	});
	GEvent.addListener(this, "mouseout", hideTooltip_);
	GEvent.addListener(this, "mouseover", showTooltip_)
}
HotelClusterMarker_.prototype = new GMarker(new GLatLng(0, 0));
HotelClusterMarker_.prototype.height_ = 35;
HotelClusterMarker_.prototype.width_ = 55;
HotelClusterMarker_.prototype.icon_ = new GIcon(G_DEFAULT_ICON);
HotelClusterMarker_.prototype.icon_.iconAnchor = new GPoint(12, 35);
HotelClusterMarker_.prototype.icon_.iconSize = new GSize(27, 35);
HotelClusterMarker_.prototype.icon_.image = "/images/mapicons/cluster.png";
HotelClusterMarker_.prototype.icon_.shadow = "/images/mapicons/cluster-shadow.png";
HotelClusterMarker_.prototype.icon_.shadowSize = new GSize(48, 35);
HotelClusterMarker_.prototype.copy = function() {
	return new HotelClusterMarker_(this.getLatLng(), this.cluster_,
			this.padding_)
};
HotelClusterMarker_.prototype.getTooltipText_ = function() {
	return "" + this.cluster_.getTotalMarkers() + " hotels"
};
HotelClusterMarker_.prototype.initialize = function(map) {
	this.map_ = map;
	GMarker.prototype.initialize.call(this, map)
};
HotelClusterMarker_.prototype.zoom_ = ClusterMarker_.prototype.zoom_;
function HotelMarker(hotel) {
	this.hotel_ = hotel;
	GMarker.call(this, new GLatLng(hotel.latitude, hotel.longitude), {
		icon : HotelMarker.prototype.icon_
	})
}
HotelMarker.prototype = new GMarker(new GLatLng(0, 0));
HotelMarker.prototype.icon_ = new GIcon(G_DEFAULT_ICON);
HotelMarker.prototype.icon_.iconAnchor = new GPoint(7, 22);
HotelMarker.prototype.icon_.iconSize = new GSize(17, 22);
HotelMarker.prototype.icon_.image = "/images/mapicons/hotel.png";
HotelMarker.prototype.icon_.shadow = "/images/mapicons/hotel-shadow.png";
HotelMarker.prototype.icon_.shadowSize = new GSize(30, 21);
HotelMarker.prototype.getExtInfoWindowBody = function() {
	return [
			'<div id="hotelWindow">',
			"<h3>",
			this.getRatingImg_(),
			'<a href="',
			this.hotel_.url,
			'" target="blank_">',
			this.hotel_.name,
			"</a>",
			"</h3>",
			'<a class="photo" href="',
			this.hotel_.url,
			'" target="blank_"><img src="',
			this.hotel_.picture,
			'" /></a>',
			'<ul class="float">',
			"<li>",
			this.hotel_.address.join("<br/>"),
			"</li>",
			"<li>",
			msgs.hotelUserRating(this.hotel_.userRating,
					this.hotel_.urlReviews, this.hotel_.userReviews),
			"</li>",
			'<li class="last">',
			'<a href="',
			this.hotel_.url,
			'" style="float: right;" target="blank_">check rates</a>',
			this.hotel_.rate === 0 ? "rate not available" : "<a href='"
					+ this.hotel_.url
					+ "' style='font-weight: bold;' target='blank_'>"
					+ formatPrice(this.hotel_.rate) + "</a> avg per night",
			"</li>", "</ul>", "</div>" ].join("")
};
HotelMarker.prototype.getHotel = function() {
	return this.hotel_
};
HotelMarker.prototype.getRatingImg_ = function() {
	return this.hotel_.rating !== "00" ? '<img src="/images/kstar' + this.hotel_.rating + '.gif" />'
			: ""
};
HotelMarker.prototype.getTooltipText_ = function() {
	var ratingImg = this.getRatingImg_();
	return "<strong>"
			+ this.hotel_.name
			+ "</strong><br/>"
			+ (ratingImg ? ratingImg + "<br/>" : "")
			+ (this.hotel_.rate === 0 ? "rate not available" : ""
					+ formatPrice(this.hotel_.rate) + " avg per night")
			+ "<br/>" + this.hotel_.address.join("<br/>")
};
HotelMarker.prototype.initialize = function(map) {
	this.map_ = map;
	GMarker.prototype.initialize.call(this, map);
	GEvent.addListener(this, "click", this.openExtInfoWindow);
	GEvent.addListener(this, "mouseout", hideTooltip_);
	GEvent.addListener(this, "mouseover", showTooltip_)
};
HotelMarker.prototype.openExtInfoWindow = function() {
	hideTooltip_();
	GMarker.prototype.openExtInfoWindow.call(this, this.map_, "commonWindow",
			this.getExtInfoWindowBody(), extInfoWindowOpts)
};
function HotelMarkerClusterer() {
	this.ClusterMarker_ = HotelClusterMarker_;
	MarkerClusterer.apply(this, arguments)
}
function MarkerClusterer(map, opt_markers, opt_opts) {
	var clusters_ = [];
	var map_ = map;
	var maxZoom_ = null;
	var me_ = this;
	var gridSize_ = 60;
	var leftMarkers_ = [];
	var mcfn_ = null;
	if (typeof opt_opts === "object" && opt_opts !== null) {
		if (typeof opt_opts.gridSize === "number" && opt_opts.gridSize > 0) {
			gridSize_ = opt_opts.gridSize
		}
		if (typeof opt_opts.maxZoom === "number") {
			maxZoom_ = opt_opts.maxZoom
		}
	}
	function addLeftMarkers_() {
		if (leftMarkers_.length === 0) {
			return
		}
		var leftMarkers = [];
		for (i = 0; i < leftMarkers_.length; ++i) {
			me_.addMarker(leftMarkers_[i], true, null, null, true)
		}
		leftMarkers_ = leftMarkers
	}
	this.clearMarkers = function() {
		for ( var i = 0; i < clusters_.length; ++i) {
			if (typeof clusters_[i] !== "undefined" && clusters_[i] !== null) {
				clusters_[i].clearMarkers()
			}
		}
		clusters_ = [];
		leftMarkers_ = [];
		GEvent.removeListener(mcfn_)
	};
	function isMarkerInViewport_(marker) {
		return map_.getBounds().containsLatLng(marker.getLatLng())
	}
	function reAddMarkers_(markers) {
		var len = markers.length;
		var clusters = [];
		for ( var i = len - 1; i >= 0; --i) {
			me_.addMarker(markers[i].marker, true, markers[i].isAdded,
					clusters, true)
		}
		addLeftMarkers_()
	}
	this.addMarker = function(marker, opt_isNodraw, opt_isAdded, opt_clusters,
			opt_isNoCheck, opt_zoom) {
		if (opt_isNoCheck !== true) {
			if (!isMarkerInViewport_(marker)) {
				leftMarkers_.push(marker);
				return
			}
		}
		var isAdded = opt_isAdded;
		var clusters = opt_clusters;
		var pos = map_.fromLatLngToDivPixel(marker.getLatLng());
		if (typeof isAdded !== "boolean") {
			isAdded = false
		}
		if (typeof clusters !== "object" || clusters === null) {
			clusters = clusters_
		}
		var length = clusters.length;
		var cluster = null;
		var zoom = opt_zoom || map_.getZoom();
		for ( var i = length - 1; i >= 0; i--) {
			cluster = clusters[i];
			var center = cluster.getCenterAsDivPixel(zoom);
			if (center === null) {
				continue
			}
			if (pos.x >= center.x - gridSize_ && pos.x <= center.x + gridSize_
					&& pos.y >= center.y - gridSize_
					&& pos.y <= center.y + gridSize_) {
				cluster.addMarker( {
					isAdded : isAdded,
					marker : marker
				});
				if (!opt_isNodraw) {
					cluster.redraw_()
				}
				return
			}
		}
		cluster = new Cluster(this, map);
		cluster.addMarker( {
			isAdded : isAdded,
			marker : marker
		});
		if (!opt_isNodraw) {
			cluster.redraw_()
		}
		clusters.push(cluster);
		if (clusters !== clusters_) {
			clusters_.push(cluster)
		}
	};
	this.removeMarker = function(marker) {
		for ( var i = 0; i < clusters_.length; ++i) {
			if (clusters_[i].remove(marker)) {
				clusters_[i].redraw_();
				return
			}
		}
	};
	this.redraw_ = function() {
		var clusters = this.getClustersInViewport_();
		for ( var i = 0; i < clusters.length; ++i) {
			clusters[i].redraw_(true)
		}
	};
	this.getClustersInViewport_ = function() {
		var clusters = [];
		var curBounds = map_.getBounds();
		for ( var i = 0; i < clusters_.length; i++) {
			if (clusters_[i].isInBounds(curBounds)) {
				clusters.push(clusters_[i])
			}
		}
		return clusters
	};
	this.getMaxZoom_ = function() {
		return maxZoom_
	};
	this.getMap_ = function() {
		return map_
	};
	this.getGridSize_ = function() {
		return gridSize_
	};
	this.getTotalMarkers = function() {
		var result = 0;
		for ( var i = 0; i < clusters_.length; ++i) {
			result += clusters_[i].getTotalMarkers()
		}
		return result
	};
	this.getTotalClusters = function() {
		return clusters_.length
	};
	this.resetViewport = function() {
		var clusters = this.getClustersInViewport_();
		var tmpMarkers = [];
		var removed = 0;
		for ( var i = 0; i < clusters.length; ++i) {
			var cluster = clusters[i];
			var oldZoom = cluster.getCurrentZoom();
			if (oldZoom === null) {
				continue
			}
			var curZoom = map_.getZoom();
			if (curZoom !== oldZoom) {
				var mks = cluster.getMarkers();
				for ( var j = 0; j < mks.length; ++j) {
					var newMarker = {
						isAdded : false,
						marker : mks[j].marker
					};
					tmpMarkers.push(newMarker)
				}
				cluster.clearMarkers();
				removed++;
				for (j = 0; j < clusters_.length; ++j) {
					if (cluster === clusters_[j]) {
						clusters_.splice(j, 1)
					}
				}
			}
		}
		reAddMarkers_(tmpMarkers);
		this.redraw_()
	};
	this.addMarkers = function(markers) {
		var zoom = map_.getZoom();
		for ( var i = 0; i < markers.length; ++i) {
			this.addMarker(markers[i], true, null, null, null, zoom)
		}
		this.redraw_()
	};
	if (typeof opt_markers === "object" && opt_markers !== null) {
		this.addMarkers(opt_markers)
	}
	mcfn_ = GEvent.addListener(map_, "moveend", function() {
		me_.resetViewport()
	})
}
function addressUpdate() {
	jq.address.parameter("a", jq("#activities input").checkboxGroup("values")
			.join(","));
	jq.address.parameter("d", jq("#flightMaxDuration").val());
	jq.address.parameter("fb", jq("#budgetSlider").rangeSlider("values").join(
			","));
	if (jq("#hotelBudgetSlider").hasClass("ui-slider")) {
		jq.address.parameter("hb", jq("#hotelBudgetSlider").rangeSlider(
				"values").join(","))
	}
	jq.address.parameter("l", jq("#languages input").checkboxGroup("values")
			.join(","));
	jq.address.parameter("ll", map.getCenter().toUrlValue());
	jq.address.parameter("ns", jq("#flightNonstopOnly").attr("checked") ? "y"
			: "n");
	jq.address.parameter("s", jq("#hotelStars").val());
	jq.address
			.parameter("t", jq("#tempSlider").rangeSlider("values").join(","));
	jq.address.parameter("w", jq("#month").val());
	jq.address.parameter("z", map.getZoom());
	jq.address.update()
}
function fetch(recenter, callback) {
	var data = {
		airport : airportVal,
		hotel : "n",
		v : 1
	};
	if (data.airport) {
		var dates = jq("#month").val();
		if (dates) {
			dates = dates.split(",");
			data.depart = dates[0];
			data["return"] = dates[1]
		}
		if (jq("#flightNonstopOnly").attr("checked")) {
			data.flightMaxStops = 0
		}
		jq("#loader").fadeIn();
		jq.address.path(airportVal).update();
		jq
				.getJSON(
						"/h/explore/api",
						data,
						function(r) {
							if (!fromVal) {
								fromVal = r.origin.name;
								jq("#from").val(
										fromVal + " - (" + r.origin.shortName
												+ ")")
							}
							markers = [];
							for ( var i = 0, destinations = r.destinations, length = destinations.length; i < length; i++) {
								markers.push(new DestinationMarker(
										destinations[i]))
							}
							moveOriginMarker(r.origin);
							if (recenter) {
								var center = new GLatLng(r.origin.latitude,
										r.origin.longitude);
								if (map.getZoom() === G_NORMAL_MAP
										.getMinimumResolution()) {
									map.panTo(center)
								} else {
									map.setCenter(center, G_NORMAL_MAP
											.getMinimumResolution())
								}
							}
							lowtemp = -1000;
							hightemp = 1000;
							updateBudgetSlider();
							updateTempSlider();
							filter();
							if (callback) {
								callback()
							} else {
								jq("#loader").fadeOut()
							}
						})
	} else {
		markers = [];
		lowtemp = -1000;
		hightemp = 1000;
		updateBudgetSlider();
		updateTempSlider();
		filter()
	}
}
function fetchDate(selector) {
	return jq.datepicker.formatDate("yymmdd", jq(selector)
			.datepicker("getDate"))
}
function fetchHotels(airport, callback) {
	jq("#loader").fadeIn();
	jq.address.path(airportVal + "/" + airport).update();
	jq
			.getJSON(
					"/h/explore/hotel",
					{
						airport : airport
					},
					function(r) {
						var lastCenter = map.getCenter(), lastOrigin = originMarker
								.getLatLng(), lastZoom = map.getZoom(), newOrigin = new GLatLng(
								r.origin.latitude, r.origin.longitude);
						if (hotelMarkerCluster !== null) {
							hotelMarkerCluster.clearMarkers()
						}
						var prices = [];
						hotelMarkers = [];
						for ( var i = 0; i < r.hotels.length; i++) {
							var h = r.hotels[i];
							prices.push(h.rate);
							hotelMarkers.push(new HotelMarker(h))
						}
						var sliderValues = [ Math.min.apply(Math, prices),
								Math.max.apply(Math, prices) ];
						if (hotelMarkerCluster === null) {
							jq("#hotelBudgetSlider").rangeSlider("destroy")
						}
						jq("#hotelBudgetSlider").rangeSlider( {
							min : sliderValues[0],
							max : sliderValues[1],
							values : sliderValues,
							step : 10,
							change : function(e, ui) {
								addressUpdate();
								if (typeof e.originalEvent !== "undefined") {
									filterHotels()
								}
							},
							slide : function(e, ui) {
								map.closeExtInfoWindow();
								updateHotelBudgetValues(ui.values)
							}
						});
						updateHotelBudgetValues(sliderValues);
						originMarker.setLatLng(newOrigin);
						hotelMarkerCluster = new HotelMarkerClusterer(map,
								hotelMarkers, {
									gridSize : 15
								});
						map.setCenter(newOrigin, 12, G_NORMAL_MAP);
						markerCluster.clearMarkers();
						if (callback) {
							callback()
						} else {
							jq("#loader").fadeOut()
						}
						jq("#controls").addClass("hidden");
						jq("#hotelControls").removeClass("hidden");
						jq("#hotelCrumbs")
								.html(
										"<strong>Explore from</strong> <a href='#'>"
												+ fromVal + " (" + airportVal
												+ ")</a> &raquo; Hotels near "
												+ r.origin.name + " ("
												+ r.origin.shortName + ")")
								.find("a")
								.click(
										function() {
											jq(".filterSet").addClass("hidden");
											jq("#hotelControls").addClass(
													"hidden");
											jq("#controls").removeClass(
													"hidden");
											hotelMarkerCluster.clearMarkers();
											originMarker.setLatLng(lastOrigin);
											map.setCenter(lastCenter, lastZoom,
													G_PHYSICAL_MAP);
											filter();
											jq.address.path(airportVal);
											addressUpdate();
											return false
										})
					})
}
function filter() {
	var budget = jq("#budgetSlider").slider("values");
	var filters = [ function(m) {
		return m.getPrice() >= budget[0]
	}, function(m) {
		return m.getPrice() <= budget[1]
	} ];
	var activities = jq("input[name=activities]").checkboxGroup("values");
	if (activities[0] != "any") {
		filters.push(function(m) {
			return hasAllValues(activities, m.getDestination().activities)
		})
	}
	var flightMaxDuration = parseInt(jq("#flightMaxDuration").val(), 10);
	if (flightMaxDuration) {
		filters.push(function(m) {
			return m.getDestination().flightMaxDuration <= flightMaxDuration
		})
	}
	var languages = jq("#languages input").checkboxGroup("values");
	if (languages[0] != "any") {
		filters.push(function(m) {
			return hasOneValue(languages, m.getDestination().languages)
		})
	}
	var temp = jq("#tempSlider").slider("values");
	filters.push(function(m) {
		var d = m.getDestination();
		var temps = m.getTemps();
		var mo = parseInt(d.depart.substring(4, 6), 10);
		var avg = temps[mo - 1];
		return avg[0] >= temp[0] && avg[1] <= temp[1]
	});
	if (markerCluster !== null) {
		markerCluster.clearMarkers()
	}
	var filtered = [];
	for ( var markers_i = 0, markers_length = markers.length; markers_i < markers_length; markers_i++) {
		var m = markers[markers_i];
		var passes = true;
		for ( var filters_i = 0, filters_length = filters.length; filters_i < filters_length; filters_i++) {
			if (!filters[filters_i](m)) {
				passes = false;
				break
			}
		}
		if (passes) {
			filtered.push(m)
		}
	}
	markerCluster = new DestinationMarkerClusterer(map, filtered,
			destinationMarkerClustererOpts)
}
function filterHotels() {
	var budget = jq("#hotelBudgetSlider").slider("values");
	var stars = parseInt(jq("#hotelStars").val(), 10);
	var filters = [ function(m) {
		return m.getHotel().rate >= budget[0]
	}, function(m) {
		return m.getHotel().rate <= budget[1]
	}, function(m) {
		return parseInt(m.getHotel().rating, 10) >= stars
	} ];
	if (hotelMarkerCluster !== null) {
		hotelMarkerCluster.clearMarkers()
	}
	var filtered = [];
	for ( var markers_i = 0, markers_length = hotelMarkers.length; markers_i < markers_length; markers_i++) {
		var m = hotelMarkers[markers_i];
		var passes = true;
		for ( var filters_i = 0, filters_length = filters.length; filters_i < filters_length; filters_i++) {
			if (!filters[filters_i](m)) {
				passes = false;
				break
			}
		}
		if (passes) {
			filtered.push(m)
		}
	}
	hotelMarkerCluster = new HotelMarkerClusterer(map, filtered, {
		gridSize : 15
	})
}
function formatDuration(n) {
	var q = n / 60, r = n % 60;
	if (r === 0) {
		return q + "hrs"
	} else {
		return Math.floor(q) + "h" + r + "m"
	}
}
function formatPrice(price) {
	return "$" + price + ""
}
function formatStops(n) {
	if (n === 0) {
		return "non-stop"
	} else {
		if (n === 1) {
			return "" + n + " stop"
		} else {
			return "" + n + " stops"
		}
	}
}
function getDate(d) {
	return jq.datepicker.formatDate("mm/dd/yy", jq.datepicker.parseDate(
			"yymmdd", d))
}
function getUSDate(d) {
	return jq.datepicker.formatDate("mm/dd/yy", jq.datepicker.parseDate(
			"yymmdd", d))
}
function hasOneValue(valuesToLookFor, testArray) {
	for ( var i = 0; i < valuesToLookFor.length; i++) {
		var v = valuesToLookFor[i];
		for ( var l = 0; l < testArray.length; l++) {
			if (v == testArray[l]) {
				return true
			}
		}
	}
	return false
}
function hasAllValues(valuesToLookFor, testArray) {
	for ( var i = 0; i < valuesToLookFor.length; i++) {
		var v = valuesToLookFor[i];
		var hasIt = false;
		for ( var l = 0; l < testArray.length; l++) {
			if (v == testArray[l]) {
				hasIt = true
			}
		}
		if (!hasIt) {
			return false
		}
	}
	return true
}
function moveOriginMarker(origin) {
	var latlng = new GLatLng(origin.latitude, origin.longitude);
	if (originMarker === null) {
		var icon = new GIcon();
		icon.iconSize = new GSize(16, 16);
		icon.iconAnchor = new GPoint(8, 8);
		icon.image = "/images/explore/origin.png";
		originMarker = new GMarker(latlng, icon, true);
		map.addOverlay(originMarker)
	} else {
		originMarker.setLatLng(latlng)
	}
}
function priceAlerts(overlay, dest) {
	var o = airportVal, d = dest.airport.shortName, body = [ "<div>", "<h3>",
			closeImage, "Create a Price Alert for " + o + " to " + d + "",
			"</h3>", '<form id="priceAlerts" action="/h/explore/alerts">',
			'<input type="hidden" name="o" value="', o, '" />',
			'<input type="hidden" name="d" value="', d, '" />', "<fieldset>",
			"<legend>Thinking about</legend>", '<select name="m">' ];
	for ( var i = 0; i < priceAlertMonths.length; i++) {
		body.push('<option value="', priceAlertMonths[i][0], '">',
				priceAlertMonths[i][1], "</option>")
	}
	body
			.push(
					"</select>",
					'<div class="type">',
					'<label><input type="checkbox" name="t" value="f" checked />include flight alerts</label>',
					'<select name="ns">',
					'<option value="0">Non-stop</option>',
					'<option value="1">1+ stops</option>',
					"</select>",
					"</div>",
					'<div class="type">',
					'<label><input type="checkbox" name="t" value="h" />include hotel alerts</label>',
					'<select name="hs">',
					'<option value="5">5 stars & up</option>',
					'<option value="4">4 stars & up</option>',
					'<option value="3">3 stars & up</option>',
					'<option value="2">2 stars & up</option>',
					'<option value="1">1 star & up</option>',
					"</select>",
					"</div>",
					"</fieldset>",
					'<fieldset id="priceAlertsFreq">',
					"<legend>Send by email</legend>",
					'<label><input type="radio" name="f" value="7" checked />Weekly</label>',
					'<label><input type="radio" name="f" value="1" />Daily</label>',
					'<div id="priceAlertsExplanation">Will be sent up to 14 times and can be renewed.</div>',
					"</fieldset>",
					'<input class="inputSubmit" type="submit" value="Create" />',
					"</form>", "</div>");
	overlay.openExtInfoWindow(map, "commonWindow", body.join(""),
			extInfoWindowOpts);
	var e = jq("#priceAlerts");
	e.submit(function() {
		var t = jq(this).find("input[name=t]");
		if (t.filter(":checked").size()) {
			jq.post(jq(this).attr("action"), jq(this).serialize());
			map.closeExtInfoWindow()
		} else {
			t.first().focus()
		}
		return false
	});
	var select = e.find("select[name=m]");
	var selected = select.find("option[value="
			+ jq("#month").val().substring(0, 6) + "]:first");
	if (!selected.size()) {
		selected = select.find("option:eq(2)")
	}
	selected.attr("selected", true);
	select.change(function() {
		var h = e.find(".type").last(), disabled = jq(this).val() == "";
		h.find("input,select").attr("disabled", disabled);
		if (disabled) {
			h.find("input").attr("checked", false)
		}
	}).change();
	e.find("select[name=ns]").val(
			jq("#flightNonstopOnly").attr("checked") ? 0 : 1)
}
function searchFlights(overlay, dest) {
	var isNonstop = jq("#flightNonstopOnly").attr("checked");
	var body = [
			"<div>",
			"<h3>",
			closeImage,
			msgs.flightInfo(fromVal.split(" -")[0], airportVal,
					dest.airport.name, dest.airport.shortName),
			"</h3>",
			'<form id="searchFlights" action="/s/search/air" target="_blank">',
			'<input type="hidden" name="d1" value="',
			getUSDate(dest.depart),
			'" />',
			'<input type="hidden" name="d2" value="',
			getUSDate(dest["return"]),
			'" />',
			'<input type="hidden" name="l1" value="',
			airportVal,
			'" />',
			'<input type="hidden" name="l2" value="',
			dest.airport.shortName,
			'" />',
			'<input type="hidden" name="ns" value="',
			isNonstop ? "y" : "n",
			'" />',
			'<input type="hidden" name="p" value="explore" />',
			"<table>",
			"<tr>",
			'<td><label class="calendar">Depart<input id="depart" class="inputText" /><span>&nbsp;</span></label></td>',
			'<td><label class="calendar">Return<input id="return" class="inputText" /><span>&nbsp;</span></label></td>',
			"</tr><tr>",
			'<td><select name="pa"><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option></select> Traveler</td>',
			'<td><input id="nonstop" type="checkbox" ',
			isNonstop ? "checked" : "", "/> Prefer Nonstop</td>", "</tr>",
			"</table>",
			'<input class="inputSubmit" type="submit" value="Search" />',
			"</form>", "</div>" ].join("");
	overlay.openExtInfoWindow(map, "commonWindow", body, extInfoWindowOpts);
	jq("#depart,#return").datepicker(
			{
				onSelect : function() {
					var thisDate = jq(this).datepicker("getDate");
					var thisDateFormatted = jq.datepicker.formatDate(
							"mm/dd/yy", thisDate);
					updateDatepickerSpan(jq(this));
					if (jq(this).attr("id") === "return") {
						jq("#searchFlights input[name=d2]").val(
								thisDateFormatted);
						if (thisDate < jq("#depart").datepicker("getDate")) {
							jq("#depart").datepicker("setDate", "").siblings(
									"span").text("")
						}
					} else {
						jq("#searchFlights input[name=d1]").val(
								thisDateFormatted)
					}
				}
			});
	updateDatepickerSpan(jq("#depart").datepicker("setDate",
			getDate(dest.depart)));
	updateDatepickerSpan(jq("#return").datepicker("setDate",
			getDate(dest["return"])));
	jq("#searchFlights").submit(function() {
		setTimeout(function() {
			map.closeExtInfoWindow()
		}, 500);
		return true
	});
	jq("#nonstop").change(
			function() {
				jq("#searchFlights input[name=ns]").val(
						jq(this).attr("checked") ? "y" : "n")
			})
}
function splitToInts(v) {
	return jq.map(v.split(","), function(x) {
		return parseInt(x, 10)
	})
}
function statusList(list, defaultStatus) {
	var filtered = [];
	for ( var i = 0, length = list.length; i < length; i++) {
		if (list[i]) {
			filtered.push(list[i])
		}
	}
	return filtered.length !== 0 ? filtered.join(", ") : defaultStatus
}
function updateBudgetSlider() {
	var prices = [];
	for ( var i = 0, length = markers.length; i < length; i++) {
		var price = markers[i].getPrice();
		if (price !== -1) {
			prices.push(price)
		}
	}
	if (prices.length === 0) {
		prices = defaultBudgetValues
	}
	var min = Math.min.apply(Math, prices), max = Math.max.apply(Math, prices), slider = jq("#budgetSlider"), values = slider
			.slider("values");
	if (values[0] < min || values[0] === slider.slider("option", "min")) {
		values[0] = min
	}
	if (values[1] > max || values[1] === slider.slider("option", "max")) {
		values[1] = max
	}
	if (min === max) {
		max += 10
	}
	slider.slider("option", {
		min : min,
		max : max,
		values : values
	});
	updateBudgetValues(values)
}
function updateTempSlider() {
	var alltemps = [];
	for ( var i = 0, length = markers.length; i < length; i++) {
		var temps = markers[i].getTemps();
		for ( var x = 0; x < temps.length; x++) {
			var apair = temps[x];
			for ( var a = 0; a < apair.length; a++) {
				if (apair[a] !== null) {
					alltemps.push(apair[a])
				}
			}
		}
	}
	if (alltemps.length === 0) {
		alltemps = defaultTempValues
	}
	var min = Math.min.apply(Math, alltemps), max = Math.max.apply(Math,
			alltemps), slider = jq("#tempSlider"), values = slider
			.slider("values");
	if (lowtemp === -1000 && hightemp === 1000) {
		lowtemp = min;
		hightemp = max
	}
	if (values[0] < min || values[0] === slider.slider("option", "min")) {
		values[0] = min
	}
	if (values[1] > max || values[1] === slider.slider("option", "max")) {
		values[1] = max
	}
	if (min === max) {
		max += 10
	}
	var slider = jq("#tempSlider");
	slider.slider("option", {
		min : min,
		max : max,
		values : values
	});
	updateTempValues(values)
}
function updateBudgetValues(values) {
	jq("#budgetValues").text(
			formatPrice(values[0]) + " - " + formatPrice(values[1]))
}
function updateDatepickerSpan(e) {
	var thisDate = e.datepicker("getDate");
	e.siblings("span").text(jq.datepicker.formatDate("DD, M d yy", thisDate))
}
function updateFlightStatus() {
	var stops = jq("#flightNonstopOnly").attr("checked") ? "non-stop" : null;
	var duration = parseInt(jq("#flightMaxDuration").val(), 10) / 60;
	if (isNaN(duration)) {
		duration = null;
		jq("#flightdurationopt").text("hours")
	} else {
		if (duration === 1) {
			duration = "max 1 hour";
			jq("#flightdurationopt").text("hour")
		} else {
			duration = "max " + duration + " hours";
			jq("#flightdurationopt").text("hours")
		}
	}
	jq("#flightStatus").text(
			statusList( [ stops, duration ], "max stops? flight duration?"))
}
function updateHotelBudgetValues(values) {
	jq("#hotelBudgetValues").text(
			formatPrice(values[0]) + " - " + formatPrice(values[1])
					+ " / night")
}
function updateTempValues(values) {
	jq("#tempValues").html(msgs.tempRange(values[0], values[1], tempUnit))
}
function updateWhatStatus() {
	var defaultStatus = "activities? temperature?";
	var activities = jq("#activities input").checkboxGroup("labelValues");
	activities = activities[0] !== "any" ? activities.join("/") : null;
	var temperature = jq("#tempSlider").slider("values");
	temperature = temperature[0] !== lowtemp || temperature[1] !== hightemp ? msgs
			.tempRange(temperature[0], temperature[1], tempUnit)
			: null;
	var languages = jq("#languages input").checkboxGroup("values");
	if (languages[0] !== "any") {
		var longLanguages = [];
		jq("#languages input:checked").each(
				function() {
					longLanguages.push(jq(this).parent().text().replace(
							/^\s+|\s+$/g, ""))
				});
		languages = longLanguages.join("/")
	} else {
		languages = null
	}
	jq("#whatStatus").empty().append(
			statusList( [ activities, temperature, languages ], defaultStatus))
}
jq.address
		.init(function(address) {
			jq.address.autoUpdate(false);
			if (GBrowserIsCompatible()) {
				G_PHYSICAL_MAP.getMaximumResolution = function() {
					return 12
				};
				G_PHYSICAL_MAP.getMinimumResolution = G_NORMAL_MAP.getMinimumResolution = function() {
					return 3
				};
				map = new GMap2(document.getElementById("map"), {
					mapTypes : [ G_PHYSICAL_MAP, G_NORMAL_MAP ]
				});
				map.setCenter(new GLatLng(0, 0), 3);
				map.addControl(new GLargeMapControl3D(), new GControlPosition(
						G_ANCHOR_TOP_LEFT, new GSize(10, 120)));
				map.enableScrollWheelZoom();
				GEvent.addListener(map, "extinfowindowbeforeclose", function() {
					jq.datepicker._hideDatepicker()
				});
				GEvent.addListener(map, "dragend", addressUpdate);
				GEvent.addListener(map, "zoomend", addressUpdate);
				jq(window).resize(
						function() {
							jq("#map").css(
									"height",
									jq(window).height()
											- jq("#disclaimer").outerHeight());
							map.checkResize()
						}).resize();
				jq("body").click(function() {
					jq(".filterSet").addClass("hidden")
				});
				jq("#activities input").checkboxGroup();
				jq("#budgetSlider").rangeSlider( {
					min : defaultBudgetValues[0],
					max : defaultBudgetValues[1],
					values : defaultBudgetValues,
					step : 10,
					change : function(e, ui) {
						addressUpdate();
						if (typeof e.originalEvent !== "undefined") {
							filter()
						}
					},
					slide : function(e, ui) {
						map.closeExtInfoWindow();
						updateBudgetValues(ui.values)
					},
					start : function() {
						jq(".filterSet").addClass("hidden")
					}
				});
				jq(
						"#commonWindow_contents img.close,#wideCommonWindow_contents img.close")
						.live("click", function() {
							map.closeExtInfoWindow()
						});
				jq("#controls").click(function() {
					map.closeExtInfoWindow()
				});
				jq("#from").smartbox("#controls input[name=airport]", {
					callback : function(data) {
						if (data) {
							fromVal = jq("#from").val().split(" -")[0];
							airportVal = data.id.substring(0, 3);
							jq(this).val(airportVal);
							fetch(true)
						}
					}
				});
				jq("#flightMaxDuration").change(function() {
					addressUpdate();
					updateFlightStatus();
					filter()
				});
				jq("#flightNonstopOnly").change(function() {
					addressUpdate();
					updateFlightStatus();
					fetch(false)
				});
				jq("#hotelStars").change(function() {
					addressUpdate();
					filterHotels()
				});
				jq("#languages input").checkboxGroup();
				jq("#month").change(
						function() {
							addressUpdate();
							jq("#whenStatus").text(
									jq(this).val() ? jq(this).find(
											"option:selected").text()
											: "any time");
							fetch(false)
						});
				jq("#tempSlider").rangeSlider( {
					min : defaultTempValues[0],
					max : defaultTempValues[1],
					values : defaultTempValues,
					change : function(e, ui) {
						addressUpdate();
						updateWhatStatus();
						filter()
					},
					slide : function(e, ui) {
						updateTempValues(ui.values)
					}
				});
				jq("#ui-datepicker-div, .filterSet").click(function(e) {
					e.stopPropagation()
				});
				jq(".filterSet .close").click(function() {
					jq(this).parents(".filterSet").addClass("hidden")
				});
				jq(".filterSets a.filterSetLink")
						.click(
								function() {
									map.closeExtInfoWindow();
									var jqThis = jq(this), e = jq(jqThis
											.attr("href")), show = e
											.hasClass("hidden");
									jq(".filterSet").addClass("hidden");
									if (show) {
										if (jqThis.hasClass("centered")) {
											var controls = jq("#controls"), delta = (controls
													.outerWidth() - e
													.outerWidth()) / 2;
											e.css("left", delta
													+ controls.offset().left);
											e.find(".arrow").css(
													"left",
													jqThis.position().left
															- delta + 12)
										} else {
											e.css("left", jqThis.offset().left)
										}
										e.removeClass("hidden");
										if (e.attr("id") === "share") {
											jq("#share input").val(
													document.location.href)
													.select()
										}
									}
									return false
								});
				airportVal = address.pathNames[0] || jq("#airport").val();
				if (address.parameters.a) {
					jq("#activities input").checkboxGroup("values",
							address.parameters.a.split(","))
				}
				if (address.parameters.d) {
					jq(
							"#flightMaxDuration option[value="
									+ address.parameters.d + "]").attr(
							"selected", true)
				}
				if (address.parameters.fb) {
					jq("#budgetSlider").rangeSlider("values",
							splitToInts(address.parameters.fb))
				}
				if (address.parameters.l) {
					jq("#languages input").checkboxGroup("values",
							address.parameters.l.split(","))
				}
				if (address.parameters.ns) {
					jq("#flightNonstopOnly").attr("checked",
							address.parameters.ns === "y")
				}
				if (address.parameters.s) {
					jq("#hotelStars option[value=" + address.parameters.s + "]")
							.attr("selected", true)
				}
				if (address.parameters.t) {
					var temps = splitToInts(address.parameters.t);
					jq("#tempSlider").rangeSlider("values", temps);
					updateTempValues(temps)
				}
				if (address.parameters.w) {
					jq("#month option[value=" + address.parameters.w + "]")
							.attr("selected", true)
				}
				updateFlightStatus();
				updateWhatStatus();
				fetch(
						true,
						function() {
							if (address.pathNames[1]) {
								fetchHotels(
										address.pathNames[1],
										function() {
											if (address.parameters.hb) {
												var values = splitToInts(address.parameters.hb);
												jq("#hotelBudgetSlider")
														.rangeSlider("values",
																values);
												updateHotelBudgetValues(values)
											}
											if (address.parameters.ll) {
												map
														.setCenter(GLatLng
																.fromUrlValue(address.parameters.ll))
											}
											if (address.parameters.z) {
												map.setZoom(parseInt(
														address.parameters.z,
														10))
											}
											jq("#loader").fadeOut()
										})
							} else {
								if (address.parameters.ll) {
									map
											.setCenter(GLatLng
													.fromUrlValue(address.parameters.ll))
								}
								if (address.parameters.z) {
									map.setZoom(parseInt(address.parameters.z,
											10))
								}
								jq("#loader").fadeOut()
							}
						})
			}
		});

var jsck_explore="v329";
// generation time: 17ms
