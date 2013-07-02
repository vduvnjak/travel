<?php
class EAN_API
  {
  static public function make_xml_request($request,$use_https = false)
  {
  //$dom = dom_import_simplexml(simplexml_load_string($request))->ownerDocument;$dom->formatOutput = true;echo '<PRE style="color:#006600;padding:10px;text-align:left">',htmlspecialchars($dom->saveXML()),'</PRE>';
 if ($use_https)
  $request = urlencode($request);
 $curl_attempts = 0;
  do
  {
  $curl = curl_init();
  curl_setopt($curl,CURLOPT_URL,($use_https ? 'https' : 'http') . '://axml.travelnow.com/external/xmlinterface.jsp?cid=' . EAN_CID . '&resType=' . EAN_RES_TYPE
  . '&intfc=ws');
  curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
  curl_setopt($curl,CURLOPT_TIMEOUT,30);
  curl_setopt($curl,CURLOPT_POST,1);
  curl_setopt($curl,CURLOPT_POSTFIELDS,'xml=' . $request);
  curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-type:application/x-www-form-urlencoded'));
  $response = trim(curl_exec($curl));
  curl_close($curl);
  } while (strlen($response) == 0 && ++$curl_attempts < MAXIMUM_CURL_ATTEMPTS);
 if (strlen($response) == 0)
  $response = '<response><error>Error reaching XML gateway</error></response>';
 //$dom = dom_import_simplexml(simplexml_load_string($response))->ownerDocument;$dom->formatOutput = true;echo '<PRE style="color:#000066;padding:10px;text-align:left">',htmlspecialchars($dom->saveXML()),'</PRE>';
 return $response;
  }
 static public function string_clean_up($string)
  {
  return trim(str_replace(array('&amp;','&apos;'),array('&','\''),htmlspecialchars_decode($string)));
  }
 static public function get_hotels($destination,$currency,$language,$check_in,$check_out,$rooms,$room_breakdown,$children_breakdown,$amenities,$property_types,$hotel_name,$page,$sort,$address,$hotel_ids,$nearby_landmark)
  {
  if ($page == 1)
  $_SESSION['EAN_hotel_results'] = array();
 $results = array();
 $destination_id = '';
  if (preg_match('/([0-9A-F]{8})-([0-9A-F]{4})-([0-9A-F]{4})-([0-9A-F]{4})-([0-9A-F]{12})/',$destination))
  $destination_id = $destination;
 if ((strlen($destination_id) == 0 && strlen($hotel_ids) == 0) || strlen($address) > 0)
  {
  $request = '<?xml version="1.0" encoding="utf-8" ?>';
  $request .= '<HotelSessionRequest method="getLocationByDestinationString">';
  $request .= '<destinationString>';
  $request .= strlen($address) > 0 ? $_GET['destination'] : $destination;
  $request .= '</destinationString>';
  $request .= '</HotelSessionRequest>';
 $possible_locations = simplexml_load_string(EAN_API::make_xml_request($request));
 if (sizeof($possible_locations->LocationInfo) == 1)
  {
  $destination_id = (string)$possible_locations->LocationInfo->destinationId;
  if (strlen($address) > 0)
  {
  $city = (string)$possible_locations->LocationInfo->city;
  $state = (string)$possible_locations->LocationInfo->stateProvince;
  $country = (string)$possible_locations->LocationInfo->country;
  }
  }
  else if (sizeof($possible_locations->LocationInfo) > 1)
  {
  $results['locations'] = array();
  foreach ($possible_locations->LocationInfo as $location)
  {
  $results['locations'][] = array(
  'text' => str_replace(',,',',',(string)$location->code),
  'destination_id' => (string)$location->destinationId
  );
  }
  }
  else
  {
  $results['locations'] = array();
  $results['error'] = 'We could not find a destination with that name, please try again.';
  }
  }
 if (strlen($destination_id) > 0 || strlen($hotel_ids) > 0)
  {
  if (isset($_SESSION['EAN_hotel_results'][$page]))
  {
  $request = '<?xml version="1.0" encoding="utf-8" ?>';
  $request .= '<HotelSessionRequest method="runHotelAvailabilityListQuery">';
  $request .= '<HotelAvailabilityListQuery>';
  $request .= '<cacheKey>';
  $request .= $_SESSION['EAN_hotel_results'][$page]['cacheKey'];
  $request .= '</cacheKey>';
  $request .= '<cacheLocation>';
  $request .= $_SESSION['EAN_hotel_results'][$page]['cacheLocation'];
  $request .= '</cacheLocation>';
  $request .= '</HotelAvailabilityListQuery>';
  $request .= '</HotelSessionRequest>';
  }
  else
  {
  if (preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/',$check_in))
  $check_in = substr($check_in,5,2) . '/' . substr($check_in,8,2) . '/' . substr($check_in,0,4);
  if (preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/',$check_out))
  $check_out = substr($check_out,5,2) . '/' . substr($check_out,8,2) . '/' . substr($check_out,0,4);
 $request = '<?xml version="1.0" encoding="utf-8" ?>';
  $request .= '<HotelSessionRequest method="' . (strlen(trim($check_in)) > 0 && strlen(trim($check_out)) > 0 ? 'runHotelAvailabilityListQuery' : 'getHotelInfoList')
  . '">';
  $request .= '<' . (strlen(trim($check_in)) > 0 && strlen(trim($check_out)) > 0 ? 'HotelAvailabilityListQuery' : 'HotelInfoListQuery') . '>';
  if (strlen(trim($address)) > 0)
  {
  if (strlen(trim($city)))
  {
  $request .= '<city>';
  $request .= trim(htmlspecialchars($city));
  $request .= '</city>';
  }
  if (strlen(trim($state)))
  {
  $request .= '<stateProvince>';
  $request .= trim(htmlspecialchars($state));
  $request .= '</stateProvince>';
  }
  if (strlen(trim($country)))
  {
  $request .= '<country>';
  $request .= trim(htmlspecialchars($country));
  $request .= '</country>';
  }
  $request .= '<address>';
  $request .= trim(htmlspecialchars($address));
  $request .= '</address>';
  }
  else if (strlen(trim($hotel_ids)) > 0)
  {
  $request .= '<hotelIdList>';
  $request .= $hotel_ids;
  $request .= '</hotelIdList>';
  }
  else if (preg_match('/([0-9A-F]{8})-([0-9A-F]{4})-([0-9A-F]{4})-([0-9A-F]{4})-([0-9A-F]{12})/',$nearby_landmark))
  {
  $request .= '<destinationId>';
  $request .= $nearby_landmark;
  $request .= '</destinationId>';
  }
  else
  {
  $request .= '<destinationId>';
  $request .= $destination_id;
  $request .= '</destinationId>';
  }
  if (strlen(trim($check_in)) > 0 && strlen(trim($check_out)) > 0)
  {
  $request .= '<arrivalDate>';
  $request .= $check_in;
  $request .= '</arrivalDate>';
  $request .= '<departureDate>';
  $request .= $check_out;
  $request .= '</departureDate>';
  }
  $request .= '<locale>';
  $request .= substr($language,0,2);
  $request .= '</locale>';
  $request .= '<currencyCode>';
  $request .= $currency;
  $request .= '</currencyCode>';
  $request .= '<RoomGroup>';
  $room_breakdown_array = explode('|',$room_breakdown);
  $children_breakdown_array = explode('|',$children_breakdown);
  for ($room = 1;$room <= $rooms;$room++)
  {
  $request .= '<Room>';
  $request .= '<numberOfAdults>';
  $request .= substr($room_breakdown_array[$room - 1],0,strpos($room_breakdown_array[$room - 1],','));
  $request .= '</numberOfAdults>';
  if ((int)substr($room_breakdown_array[$room - 1],strpos($room_breakdown_array[$room - 1],',') + 1) > 0)
  {
  $request .= '<numberOfChildren>';
  $request .= substr($room_breakdown_array[$room - 1],0,strpos($room_breakdown_array[$room - 1],','));
  $request .= '</numberOfChildren>';
  $request .= '<childAges>';
  $request .= $children_breakdown_array[$room - 1];
  $request .= '</childAges>';
  }
  $request .= '</Room>';
  }
  $request .= '</RoomGroup>';
  $request .= '<numberOfResults>' . HOTEL_SEARCH_RESULTS_PER_PAGE . '</numberOfResults>';
  if (strlen(trim($amenities)) > 0)
  {
  $request .= '<amenities>';
  $request .= trim($amenities);
  $request .= '</amenities>';
  }
  $request.= '<propertyType>';
  if (trim($property_types) == 'H' || trim($property_types) == 'C')
  $request .= trim($property_types);
  else
  $request .= 'A';
  $request .= '</propertyType>';
  if (strlen(trim($hotel_name)) > 0)
  {
  $request .= '<propertyName>';
  $request .= trim($hotel_name);
  $request .= '</propertyName>';
  }
  if (trim($sort) == 'stars' || trim($sort) == 'name' || trim($sort) == 'price')
  {
  $request.= '<sortMethod>';
  if (trim($sort) == 'stars')
  $request .= 4;
  else if (trim($sort) == 'name')
  $request .= 5;
  else if (trim($sort) == 'price')
  $request .= 3;
  $request .= '</sortMethod>';
  }
  $request .= '</' . (strlen(trim($check_in)) > 0 && strlen(trim($check_out)) > 0 ? 'HotelAvailabilityListQuery' : 'HotelInfoListQuery') . '>';
  $request .= '</HotelSessionRequest>';
  }
 $search_results = simplexml_load_string(EAN_API::make_xml_request($request));
 if ($search_results->presentationMessage)
  $results['error'] = str_replace('TravelNow.com',WEBSITE_NAME,(string)$search_results->presentationMessage);
  else if (sizeof($search_results->Hotel) > 0)
  {
  if (strlen(trim($check_in)) > 0 && strlen(trim($check_out)) > 0)
  {
  if (!isset($_SESSION['EAN_hotel_results'][$page + 1]))
  {
  $_SESSION['EAN_hotel_results'][$page + 1] = array(
  'cacheKey' => (string)$search_results->cacheKey,
  'cacheLocation' => (string)$search_results->cacheLocation
  );
  }
  }
 $location_text = (string)$search_results->Hotel[0]->city . ', ';
  if (strlen($search_results->Hotel[0]->stateProvince) > 0)
  $location_text .= (string)$search_results->Hotel[0]->stateProvince . ', ';
  $location_text .= (string)$search_results->Hotel[0]->country;
 $current_hotel = 0;
  $results['hotels'] = array();
  foreach($search_results->Hotel as $hotel)
  {
  $current_hotel++;
  if (($current_hotel <= ($page * HOTEL_SEARCH_RESULTS_PER_PAGE) && $current_hotel >= (($page - 1) * HOTEL_SEARCH_RESULTS_PER_PAGE) - 1) || (strlen(trim($check_in)) > 0 && strlen(trim($check_out)) > 0))
  {
  if ($hotel->HotelProperty->RateInfo)
  {
  $hotel_price = number_format(((double)$hotel->HotelProperty->RateInfo->displayRoomRate - (double)$hotel->HotelProperty->RateInfo->taxesOnly) / sizeof($hotel->HotelProperty->RateInfo->DisplayNightlyRates->displayNightlyRate),2);
  $hotel_currency = (string)$hotel->HotelProperty->RateInfo->nativeCurrencyCode;
  }
  else
  {
  $hotel_price = number_format((double)$hotel->lowRate,2);
  $hotel_currency = (string)$hotel->rateCurrencyCode;
  }
 $hotel_data = array(
  'id' => (string)$hotel->hotelId,
  'name' => EAN_API::string_clean_up((string)$hotel->name),
  'address' => (string)$hotel->address1,
  'thumbnail_url' => 'http://images.travelnow.com' . (string)$hotel->thumbNailUrl,
  'star_rating' => str_replace(array('.5','.0'),array('-5',''),(string)$hotel->propertyRating),
  'location' => strlen((string)$hotel->location) > 0 ? EAN_API::string_clean_up((string)$hotel->location) : (string)$hotel->city,
  'price' => number_format($hotel_price,2),
  'currency' => $hotel_currency,
  'property_type' => (int)$hotel->propertyType == 2 ? 'Condo' : 'Hotel'
  );
 if (strlen((string)$hotel->HotelProperty->promoDescription) > 0 && (double)$hotel->HotelProperty->PromoRateInfo->displayRoomRate > 0)
  {
  $hotel_data['old_price'] = $hotel_data['price'];
  $hotel_data['price'] = number_format(((double)$hotel->HotelProperty->PromoRateInfo->displayRoomRate - (double)$hotel->HotelProperty->PromoRateInfo->taxesOnly)
  / sizeof($hotel->HotelProperty->RateInfo->DisplayNightlyRates->displayNightlyRate),2);
  $hotel_data['promotion'] = (string)$hotel->HotelProperty->promoDescription;
  }
 $geo_search_string = (string)$hotel->address1 . ' ' . (string)$hotel->city . ' ' . (string)$hotel->stateProvince . ' ' . (string)$hotel->country;
  if (strlen((string)$hotel->latitude) > 0 && strlen((string)$hotel->longitude) > 0)
  {
  $hotel_data['coordinates'] = array(
  'latitude' => (string)$hotel->latitude,
  'longitude' => (string)$hotel->longitude
  );
  }
  else
  $hotel_data['coordinates'] = Hotel::get_coordinates($hotel_data['id'],$geo_search_string);
 $results['hotels'][] = $hotel_data;
  }
  }
 $results['locations'] = array(
  array(
  'text' => isset($_GET['destination']) ? $_GET['destination'] : $location_text,
  'destination_id' => (string)$destination_id
  )
  );
  }
  }
 return $results;
  }
 static public function get_hotel_details($hotel_id,$language)
  {
  $request = '<?xml version="1.0" encoding="utf-8" ?>';
  $request .= '<HotelSessionRequest method="selectHotelInfoForHotel">';
  $request .= '<hotelId>';
  $request .= $hotel_id;
  $request .= '</hotelId>';
  $request .= '<locale>';
  $request .= substr($language,0,2);
  $request .= '</locale>';
  $request .= '</HotelSessionRequest>';
 $hotel_data = simplexml_load_string(EAN_API::make_xml_request($request));
 $hotel = array();
  if ($hotel_data->hotelId)
  {
  $hotel['id'] = (string)$hotel_data->hotelId;
  $hotel['name'] = EAN_API::string_clean_up((string)$hotel_data->hotelName);
  $hotel['star_rating'] = str_replace(array('.5','.0'),array('-5',''),(string)$hotel_data->hotelRating);
  $hotel['address'] = (string)$hotel_data->address1;
  $hotel['location'] = (string)$hotel_data->city;
  if (strlen((string)$hotel_data->stateProvince) > 0)
  $hotel['location'] .= ', ' . (string)$hotel_data->stateProvince;
  else if (strlen((string)$hotel_data->countryCode) > 0)
  $hotel['location'] .= ', ' . (string)$hotel_data->countryCode;
  $hotel['price'] = number_format((double)$hotel_data->lowRate,2);
  if (strlen((string)$hotel_data->rateCurrencyCode) > 0)
  $hotel['currency'] = (string)$hotel_data->rateCurrencyCode;
  else if (strlen((string)$hotel_data->nativeCurrency) > 0)
  $hotel['currency'] = (string)$hotel_data->nativeCurrency;
  else
  $hotel['currency'] = 'USD';
  $hotel['thumbnail_url'] = (string)$hotel_data->HotelImages->HotelImage[0]->thumbnailUrl;
  $hotel['description'] = EAN_API::string_clean_up((string)$hotel_data->propertyDescription);
  if (substr($hotel['description'],0,3) != '<p>')
  $hotel['description'] = '<p>' . $hotel['description'] . '</p>';
  $hotel['quick_facts'] = trim((string)$hotel_data->propertyInformation);
  }
  else
  $hotel['id'] = -1;
 $hotel['photos'] = array();
  foreach ($hotel_data->HotelImages->HotelImage as $hotel_image)
  {
  $hotel['photos'][] = array(
  'url' => (string)$hotel_image->url,
  'thumbnail_url' => (string)$hotel_image->thumbnailUrl,
  'name' => (string)$hotel_image->name
  );
  }
 $hotel['rooms'] = array();
  foreach ($hotel_data->RoomTypes->RoomType as $hotel_room)
  {
  $hotel['rooms'][] = array(
  'id' => (string)$hotel_room->roomTypeId,
  'code' => (string)$hotel_room->roomCode,
  'title' => (string)$hotel_room->description,
  'description' => (string)$hotel_room->descriptionLong,
  'amenities' => array()
  );
  }
 $hotel['amenities'] = array();
  foreach ($hotel_data->RoomAmenities->RoomAmenity as $hotel_amenity)
  {
  if (!in_array(trim((string)$hotel_amenity->amenity),$hotel['amenities']))
  $hotel['amenities'][] = trim((string)$hotel_amenity->amenity);
 for ($rooms_position = 0;$rooms_position < sizeof($hotel['rooms']);$rooms_position++)
  {
  if ($hotel['rooms'][$rooms_position]['id'] == (string)$hotel_amenity->roomTypeId)
  $hotel['rooms'][$rooms_position]['amenities'][] = trim((string)$hotel_amenity->amenity);
  }
  }
 $hotel['has_business_center'] = sizeof($hotel_data->HotelInfoAmenities->hasBusinessCenter) > 0 || sizeof($hotel_data->HotelInfoAmenities->hasMeetingRooms) > 0
  ? 'Y' : 'N';
  $hotel['has_fitness_center'] = sizeof($hotel_data->HotelInfoAmenities->hasFitnessFacility) > 0 ? 'Y' : 'N';
  $hotel['has_internet_access'] = sizeof($hotel_data->HotelInfoAmenities->hasDataPorts) > 0 ? 'Y' : 'N';
  $hotel['has_kitchen'] = 'N';
  $hotel['has_pets'] = sizeof($hotel_data->HotelInfoAmenities->hasPetsAllowed) > 0 ? ' Y' : 'N';
  $hotel['has_pool'] = sizeof($hotel_data->HotelInfoAmenities->hasIndoorPool) > 0 || sizeof($hotel_data->HotelInfoAmenities->hasOutdoorPool) > 0 ? 'Y' :
  'N';
  $hotel['has_restaurant'] = sizeof($hotel_data->HotelInfoAmenities->hasInHouseDining) > 0 || sizeof($hotel_data->HotelInfoAmenities->hasInHouseBar) > 0
  ? 'Y' : 'N';
  $hotel['has_breakfast'] = sizeof($hotel_data->HotelInfoAmenities->hasContinentalBreakfast) > 0 ? 'Y' : 'N';
  $hotel['has_babysitting'] = 'N';
  $hotel['has_jacuzzi'] = sizeof($hotel_data->HotelInfoAmenities->hasWhirlpool) > 0 | sizeof($hotel_data->HotelInfoAmenities->hasSauna) > 0 ? 'Y' : 'N';
  $hotel['has_parking'] = 'N';
  $hotel['has_business_center'] = sizeof($hotel_data->HotelInfoAmenities->hasValetParking) > 0 || sizeof($hotel_data->HotelInfoAmenities->hasParkingGarage) > 0
  ? 'Y' : 'N';
  $hotel['has_room_service'] = sizeof($hotel_data->HotelInfoAmenities->hasRoomService) > 0 ? 'Y' : 'N';
 foreach ($hotel['amenities'] as $amenity)
  {
  if (stripos($amenity,'business') !== false)
  $hotel['has_business_center'] = 'Y';
  if (stripos($amenity,'fitness') !== false)
  $hotel['has_fitness_center'] = 'Y';
  if (stripos($amenity,'internet') !== false)
  $hotel['has_internet_access'] = 'Y';
  if (stripos($amenity,'pets') !== false)
  $hotel['has_pets'] = 'Y';
  if (stripos($amenity,'pool') !== false)
  $hotel['has_pool'] = 'Y';
  if (stripos($amenity,'restaurant') !== false)
  $hotel['has_restaurant'] = 'Y';
  if (stripos($amenity,'breakfast') !== false)
  $hotel['has_breakfast'] = 'Y';
  if (stripos($amenity,'babysitting') !== false)
  $hotel['has_babysitting'] = 'Y';
  if (stripos($amenity,'jacuzzi') !== false)
  $hotel['has_jacuzzi'] = 'Y';
  if (stripos($amenity,'parking') !== false)
  $hotel['has_parking'] = 'Y';
  if (stripos($amenity,'room service') !== false)
  $hotel['has_room_service'] = 'Y';
  if (stripos($amenity,'kitchen') !== false)
  $hotel['has_kitchen'] = 'Y';
  }
 return $hotel;
  }
 static public function get_hotel_rates($hotel_id,$currency,$language,$check_in,$check_out,$rooms,$room_breakdown,$children_breakdown,$rooms_data)
  {
  if (preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/',$check_in))
  $check_in = substr($check_in,5,2) . '/' . substr($check_in,8,2) . '/' . substr($check_in,0,4);
  if (preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/',$check_out))
  $check_out = substr($check_out,5,2) . '/' . substr($check_out,8,2) . '/' . substr($check_out,0,4);
 $request = '<?xml version="1.0" encoding="utf-8" ?>';
  $request .= '<HotelSessionRequest method="getHotelRoomAvailabilities">';
  $request .= '<HotelRoomAvailabilityQuery>';
  $request .= '<hotelId>';
  $request .= $hotel_id;
  $request .= '</hotelId>';
  $request .= '<arrivalDate>';
  $request .= $check_in;
  $request .= '</arrivalDate>';
  $request .= '<departureDate>';
  $request .= $check_out;
  $request .= '</departureDate>';
  $request .= '<currencyCode>';
  $request .= $currency;
  $request .= '</currencyCode>';
  $request .= '<locale>';
  $request .= substr($language,0,2);
  $request .= '</locale>';
  $request .= '<RoomGroup>';
  $room_breakdown_array = explode('|',$room_breakdown);
  $children_breakdown_array = explode('|',$children_breakdown);
  for ($room = 1;$room <= $rooms;$room++)
  {
  $request .= '<Room>';
  $request .= '<numberOfAdults>';
  $request .= substr($room_breakdown_array[$room - 1],0,strpos($room_breakdown_array[$room - 1],','));
  $request .= '</numberOfAdults>';
  if ((int)substr($room_breakdown_array[$room - 1],strpos($room_breakdown_array[$room - 1],',') + 1) > 0)
  {
  $request .= '<numberOfChildren>';
  $request .= substr($room_breakdown_array[$room - 1],0,strpos($room_breakdown_array[$room - 1],','));
  $request .= '</numberOfChildren>';
  $request .= '<childAges>';
  $request .= $children_breakdown_array[$room - 1];
  $request .= '</childAges>';
  }
  $request .= '</Room>';
  }
  $request .= '</RoomGroup>';
  $request .= '</HotelRoomAvailabilityQuery>';
  $request .= '</HotelSessionRequest>';
 $rates_data = simplexml_load_string(EAN_API::make_xml_request($request));
 $rates = array();
 foreach ($rates_data->HotelRoomAvailabilityResult as $rate)
  {
  $room = -1;
  for ($rooms_data_position = 0;$rooms_data_position < sizeof($rooms_data);$rooms_data_position++)
  {
  if ($rooms_data[$rooms_data_position]['code'] == (string)$rate->roomTypeCode)
  $room = $rooms_data_position;
  }
 $rates[] = array(
  'title' => (string)$rate->rateDescription,
  'room_description' => $room > -1 ? $rooms_data[$room]['description'] : '',
  'room_amenities' => $room > -1 ? $rooms_data[$room]['amenities'] : array(),
  'cancellation_policy' => EAN_API::string_clean_up((string)$rate->cancellationPolicy),
  'nightly_rates' => array(),
  'price' => number_format(((double)$rate->RateInfo->displayRoomRate - (double)$rate->RateInfo->taxesOnly) / sizeof($rate->RateInfo->DisplayNightlyRates->displayNightlyRate),2),
  'currency' => (string)$rate->RateInfo->displayCurrencyCode,
  'supplierType' => (string)$rate->supplierType,
  'propertyType' => (string)$rate->propertyType,
  'hrnQuoteKey' => (string)$rate->hrnQuoteKey
  );
 $days_offset = 0;
  foreach ($rate->RateInfo->DisplayNightlyRates->displayNightlyRate as $nightly_rate)
  {
  $rates[sizeof($rates) - 1]['nightly_rates'][] = array(
  'price' => number_format((double)$nightly_rate,2),
  'weekday' => date('D',mktime(0,0,0,substr($check_in,0,2),substr($check_in,3,2),substr($check_in,6,4)) + (60 * 60 * 24 * $days_offset++))
  );
  }
 if (strlen((string)$rate->promoDescription) > 0 && (double)$rate->PromoRateInfo->displayRoomRate > 0)
  {
  $rates[sizeof($rates) - 1]['old_price'] = $rates[sizeof($rates) - 1]['price'];
  $rates[sizeof($rates) - 1]['price'] = number_format(((double)$rate->PromoRateInfo->displayRoomRate - (double)$rate->PromoRateInfo->taxesOnly) / sizeof($rate->RateInfo->DisplayNightlyRates->displayNightlyRate),2);
  $rates[sizeof($rates) - 1]['promotion'] = (string)$rate->promoDescription;
 $rate_position = -1;
  foreach ($rate->PromoRateInfo->DisplayNightlyRates->displayNightlyRate as $nightly_rate)
  {
  $rate_position++;
  $rates[sizeof($rates) - 1]['nightly_rates'][$rate_position]['old_price'] = $rates[sizeof($rates) - 1]['nightly_rates'][$rate_position]['price'];
  $rates[sizeof($rates) - 1]['nightly_rates'][$rate_position]['price'] = number_format((double)$nightly_rate,2);
  }
  }
 $book_url = BASE_URL_HOTEL_BOOK;
  $book_url .= '?hotel-id=' . trim((string)$rate->hotelId);
  $book_url .= '&check-in=' . $check_in;
  $book_url .= '&check-out=' . $check_out;
  $book_url .= '&supplier-type=' . trim((string)$rate->supplierType);
  $book_url .= '&property-type=' . trim((string)$rate->propertyType);
  $book_url .= '&rooms=' . $rooms;
  $book_url .= '&room-breakdown=' . $room_breakdown;
  $book_url .= '&children-breakdown=' . $children_breakdown;
  $book_url .= '&smoking-preferences=' . trim((string)$rate->smokingPreferences);
  $book_url .= '&bed-types=';
  foreach ($rate->bedTypes->bedType as $bed_type)
  $book_url .= trim((string)$bed_type->id) . '|' . urlencode(trim((string)$bed_type->description)) . ',';
  $book_url = substr($book_url,0,-1);
  $book_url .= '&room-type-code=' . trim((string)$rate->roomTypeCode);
  $book_url .= '&room-type-description=' . urlencode(trim((string)$rate->roomTypeDescription));
  $book_url .= '&rate-code=' . trim((string)$rate->rateCode);
  $book_url .= '&rate-description=' . urlencode(trim((string)$rate->rateDescription));
  $book_url .= '&hrn-quote-key=' . trim((string)$rate->hrnQuoteKey);
  $book_url .= '&guarantee=';
  if ((string)$rate->immediateChargeRequired == 'true')
  $book_url .= 'C';
  else if ((string)$rate->depositRequired == 'true')
  $book_url .= 'D';
  else if ((string)$rate->guaranteeRequired == 'true')
  $book_url .= 'Y';
  $book_url .= '&display-price=' . trim((string)$rate->RateInfo->displayRoomRate);
  $book_url .= '&display-nightly-rates=';
  foreach ($rate->RateInfo->DisplayNightlyRates->displayNightlyRate as $nightly_rate)
  $book_url .= (string)$nightly_rate . '|';
  $book_url = substr($book_url,0,-1);
  $book_url .= '&display-currency=' . trim((string)$rate->RateInfo->displayCurrencyCode);
  $book_url .= '&native-price=' . trim((string)$rate->RateInfo->nativeRoomRate);
  $book_url .= '&native-nightly-rates=';
  foreach ($rate->RateInfo->NativeNightlyRates->nativeNightlyRate as $nightly_rate)
  $book_url .= (string)$nightly_rate . '|';
  $book_url = substr($book_url,0,-1);
  $book_url .= '&native-currency=' . trim((string)$rate->RateInfo->nativeCurrencyCode);
  $book_url .= '&rate-frequency=' . trim((string)$rate->RateInfo->rateFrequency);
  $book_url .= '&taxes-and-fees=' . trim((string)$rate->RateInfo->chargeableRoomRateTaxesAndFees);
  $book_url .= '&total=' . trim((string)$rate->RateInfo->chargeableRoomRateTotal);
 $md5_hash = md5(urldecode($book_url));
  $book_url .= '&hash=' . $md5_hash;
 $rates[sizeof($rates) - 1]['book_url'] = $book_url;
 $_SESSION['EAN_cancellation_policy'][$md5_hash] = EAN_API::string_clean_up((string)$rate->cancellationPolicy);
  }
 return $rates;
  }
 static public function get_credit_card_types($currency,$language)
  {
  $request = '<?xml version="1.0" encoding="utf-8" ?>';
  $request .= '<HotelSessionRequest method="getPaymentInfo">';
  $request .= '<locale>';
  $request .= substr($language,0,2);
  $request .= '</locale>';
  $request .= '<currencyCode>';
  $request .= $currency;
  $request .= '</currencyCode>';
  $request .= '</HotelSessionRequest>';
 $credit_card_data = simplexml_load_string(str_replace('<code>MC</code>','<code>CA</code>',EAN_API::make_xml_request($request)));
 $results = array();
  foreach($credit_card_data->PaymentType as $payment_type)
  {
  $results[] = array(
  'code' => (string)$payment_type->code,
  'name' => (string)$payment_type->name
  );
  }
 return $results;
  }
 static public function make_reservation($hotel_id,$check_in,$check_out,$supplier_type,$property_type,$rooms,$room_breakdown,$children_breakdown,$room_type_code,$room_type_description,$rate_code,$rate_description,$hrn_quote_key,$guarantee,$display_price,$display_nightly_rates,$display_currency,$native_price,$native_nightly_rates,$native_currency,$rate_frequency,$taxes_and_fees,$total,$cancellation_policy,$reservation_form)
  {
  $request = '<?xml version="1.0" encoding="utf-8" ?>';
  $request .= '<ReservationRequest method="createNewHotelReservation">';
  $request .= '<hotelId>';
  $request .= $hotel_id;
  $request .= '</hotelId>';
  $request .= '<arrivalDate>';
  $request .= $check_in;
  $request .= '</arrivalDate>';
  $request .= '<departureDate>';
  $request .= $check_out;
  $request .= '</departureDate>';
  $request .= '<supplierType>';
  $request .= $supplier_type;
  $request .= '</supplierType>';
  $request .= '<propertyType>';
  $request .= $property_type;
  $request .= '</propertyType>';
  $request .= '<RoomGroup>';
  $room_breakdown_array = explode('|',$room_breakdown);
  $children_breakdown_array = explode('|',$children_breakdown);
  for ($room = 1;$room <= $rooms;$room++)
  {
  $request .= '<Room>';
  $request .= '<numberOfAdults>';
  $request .= substr($room_breakdown_array[$room - 1],0,strpos($room_breakdown_array[$room - 1],','));
  $request .= '</numberOfAdults>';
  if ((int)substr($room_breakdown_array[$room - 1],strpos($room_breakdown_array[$room - 1],',') + 1) > 0)
  {
  $request .= '<numberOfChildren>';
  $request .= substr($room_breakdown_array[$room - 1],0,strpos($room_breakdown_array[$room - 1],','));
  $request .= '</numberOfChildren>';
  $request .= '<childAges>';
  $request .= $children_breakdown_array[$room - 1];
  $request .= '</childAges>';
  }
  $request .= '<firstName>';
  $request .= $reservation_form['guest_details'][$room - 1]['first_name'];
  $request .= '</firstName>';
  $request .= '<lastName>';
  $request .= $reservation_form['guest_details'][$room - 1]['last_name'];
  $request .= '</lastName>';
  $request .= '<bedType>';
  $request .= $reservation_form['guest_details'][$room - 1]['bed_type'];
  $request .= '</bedType>';
  $request .= '<smokingPreference>';
  $request .= $reservation_form['guest_details'][$room - 1]['smoking_preference'];
  $request .= '</smokingPreference>';
  $request .= '</Room>';
  }
  $request .= '</RoomGroup>';
  $request .= '<roomTypeCode>';
  $request .= $room_type_code;
  $request .= '</roomTypeCode>';
  $request .= '<roomTypeDescription>';
  $request .= htmlspecialchars($room_type_description);
  $request .= '</roomTypeDescription>';
  $request .= '<rateCode>';
  $request .= $rate_code;
  $request .= '</rateCode>';
  $request .= '<rateDescription>';
  $request .= htmlspecialchars($rate_description);
  $request .= '</rateDescription>';
  $request .= '<hrnQuoteKey>';
  $request .= $hrn_quote_key;
  $request .= '</hrnQuoteKey>';
  $request .= '<guarantee>';
  $request .= $guarantee;
  $request .= '</guarantee>';
  $request .= '<cancellationPolicy>';
  $request .= htmlspecialchars($cancellation_policy);
  $request .= '</cancellationPolicy>';
  $request .= '<RateInfo>';
  $request .= '<displayRoomRate>';
  $request .= $display_price;
  $request .= '</displayRoomRate>';
  $request .= '<displayNightlyRates>';
  $request .= $display_nightly_rates;
  $request .= '</displayNightlyRates>';
  $request .= '<displayCurrencyCode>';
  $request .= $display_currency;
  $request .= '</displayCurrencyCode>';
  $request .= '<nativeRoomRate>';
  $request .= $native_price;
  $request .= '</nativeRoomRate>';
  $request .= '<nativeNightlyRates>';
  $request .= $native_nightly_rates;
  $request .= '</nativeNightlyRates>';
  $request .= '<nativeCurrencyCode>';
  $request .= $native_currency;
  $request .= '</nativeCurrencyCode>';
  $request .= '<rateFrequency>';
  $request .= $rate_frequency;
  $request .= '</rateFrequency>';;
  $request .= '<chargeableRoomRateTaxesAndFees>';
  $request .= $taxes_and_fees;
  $request .= '</chargeableRoomRateTaxesAndFees>';;
  $request .= '<chargeableRoomRateTotal>';
  $request .= $total;
  $request .= '</chargeableRoomRateTotal>';
  $request .= '</RateInfo>';
  $request .= '<ReservationInfo>';
  $request .= '<email>';
  $request .= $reservation_form['email_address'];
  $request .= '</email>';
  $request .= '<firstName>';
  $request .= $reservation_form['credit_card_first_name'];
  $request .= '</firstName>';
  $request .= '<lastName>';
  $request .= $reservation_form['credit_card_last_name'];
  $request .= '</lastName>';
  $request .= '<homePhone>';
  $request .= $reservation_form['telephone_number'];
  $request .= '</homePhone>';
  $request .= '<homePhone>';
  $request .= $reservation_form['telephone_number'];
  $request .= '</homePhone>';
  $request .= '<creditCardType>';
  $request .= $reservation_form['credit_card_type'];
  $request .= '</creditCardType>';
  $request .= '<creditCardNumber>';
  $request .= $reservation_form['credit_card_number'];
  $request .= '</creditCardNumber>';
  $request .= '<creditCardIdentifier>';
  $request .= $reservation_form['credit_card_security_code'];
  $request .= '</creditCardIdentifier>';
  $request .= '<creditCardExpirationMonth>';
  $request .= $reservation_form['credit_card_expiration_month'];
  $request .= '</creditCardExpirationMonth>';
  $request .= '<creditCardExpirationYear>';
  $request .= $reservation_form['credit_card_expiration_year'];
  $request .= '</creditCardExpirationYear>';
  $request .= '</ReservationInfo>';
  $request .= '<AddressInfo>';
  $request .= '<address1>';
  $request .= $reservation_form['credit_card_address'];
  $request .= '</address1>';
  $request .= '<city>';
  $request .= $reservation_form['credit_card_city'];
  $request .= '</city>';
  if (strlen($reservation_form['credit_card_state_au']) > 0)
  {
  $request .= '<stateProvince>';
  $request .= $reservation_form['credit_card_state_au'];
  $request .= '</stateProvince>';
  }
  else if (strlen($reservation_form['credit_card_state_ca']) > 0)
  {
  $request .= '<stateProvince>';
  $request .= $reservation_form['credit_card_state_ca'];
  $request .= '</stateProvince>';
  }
  else if (strlen($reservation_form['credit_card_state_us']) > 0)
  {
  $request .= '<stateProvince>';
  $request .= $reservation_form['credit_card_state_us'];
  $request .= '</stateProvince>';
  }
  $request .= '<country>';
  $request .= $reservation_form['credit_card_country'];
  $request .= '</country>';
  if (strlen($reservation_form['credit_card_zip_code']) > 0)
  {
  $request .= '<postalCode>';
  $request .= $reservation_form['credit_card_zip_code'];
  $request .= '</postalCode>';
  }
  $request .= '</AddressInfo>';
  $request .= '</ReservationRequest>';
 $reservation_data = simplexml_load_string(EAN_API::make_xml_request($request,true));
 if ($reservation_data->presentationMessage)
  {
  $results = array(
  'error' => str_replace('TravelNow.com',WEBSITE_NAME,EAN_API::string_clean_up((string)$reservation_data->presentationMessage))
  );
  }
  else if ($reservation_data->error)
  {
  $results = array(
  'error' => str_replace('TravelNow.com',WEBSITE_NAME,EAN_API::string_clean_up((string)$reservation_data->error))
  );
  }
  else
  {
  $results = array(
  'itinerary_number' => (string)$reservation_data->itineraryId,
  'confirmation_numbers' => array()
  );
 if ($reservation_data->ConfirmationNumbers && sizeof($reservation_data->ConfirmationNumbers->confirmationNumber) > 0)
  {
  for ($position = 0;$position < sizeof($reservation_data->ConfirmationNumbers->confirmationNumber);$position++)
  {
  $results['confirmation_numbers'][] = array(
  'confirmation_number' => (string)$reservation_data->ConfirmationNumbers->confirmationNumber[0],
  'guest_name' => (string)$reservation_data->GuestNames->guestName[0]
  );
  $position++;
  }
  }
  }
 return $results;
  }
 static public function get_reservation($itinerary_number,$email_address)
  {
  $request = '<?xml version="1.0" encoding="utf-8" ?>';
  $request .= '<HotelSessionRequest method="getItinerary">';
  $request .= '<itineraryId>';
  $request .= $itinerary_number;
  $request .= '</itineraryId>';
  $request .= '<email>';
  $request .= $email_address;
  $request .= '</email>';
  $request .= '</HotelSessionRequest>';
 $reservation_data = simplexml_load_string(EAN_API::make_xml_request($request));
 $results = array();
 if ($reservation_data->presentationMessage)
  $results['error'] = str_replace('TravelNow.com',WEBSITE_NAME,(string)$reservation_data->presentationMessage);
  else
  {
  $results['hotels'] = array();
 foreach ($reservation_data->Itinerary->HotelConfirmation as $hotel)
  {
  $results['hotels'][] = array(
  'confirmation_number' => (string)$hotel->confirmationNumber,
  'cancellation_number' => (string)$hotel->cancellationNumber,
  'check_in' => (string)$hotel->arrivalDate,
  'check_in_full' => (string)date('l, F j, Y',mktime(0,0,0,substr((string)$hotel->arrivalDate,0,2),substr((string)$hotel->arrivalDate,3,2),substr((string)$hotel->arrivalDate,6,4))),
  'check_out' => (string)$hotel->departureDate,
  'check_out_full' => date('l, F j, Y',mktime(0,0,0,substr((string)$hotel->departureDate,0,2),substr((string)$hotel->departureDate,3,2),substr((string)$hotel->departureDate,6,4))),
  'nights' => round(abs(mktime(0,0,0,substr((string)$hotel->arrivalDate,0,2),substr((string)$hotel->arrivalDate,3,2),substr((string)$hotel->arrivalDate,6,4)) -
  mktime(0,0,0,substr((string)$hotel->departureDate,0,2),substr((string)$hotel->departureDate,3,2),substr((string)$hotel->departureDate,6,4))) / (60 * 60 * 24)),
  'hotel_id' => EAN_API::string_clean_up((string)$hotel->Hotel->hotelId),
  'hotel_name' => EAN_API::string_clean_up((string)$hotel->Hotel->name),
  'hotel_address' => EAN_API::string_clean_up((string)$hotel->Hotel->address1),
  'hotel_city' => EAN_API::string_clean_up((string)$hotel->Hotel->city),
  'hotel_country' => EAN_API::string_clean_up((string)$hotel->Hotel->country),
  'guest_name' => (string)$hotel->ReservationGuest->firstName . ' ' . (string)$hotel->ReservationGuest->lastName,
  'rooms' => (string)$hotel->numberOfRooms,
  'adults' => (string)$hotel->numberOfAdults,
  'children' => (string)$hotel->numberOfChildren,
  'cancellation_policy' => EAN_API::string_clean_up((string)$hotel->cancellationPolicy),
  'taxes_and_fees' => (string)$hotel->nativeTaxesFees,
  'total' => (string)$hotel->nativeRate
  );
 $results['hotels'][sizeof($results['hotels']) - 1]['nightly_rates'] = array();
  if ($hotel->NativeNightlyRates->nativeNightlyRate)
  {
  $offset = 0;
  foreach ($hotel->NativeNightlyRates->nativeNightlyRate as $nightly_rate)
  {
  $results['hotels'][sizeof($results['hotels']) - 1]['nightly_rates'][] = array(
  'date' => (string)date('l, F j, Y',mktime(0,0,0,substr((string)$hotel->arrivalDate,0,2),substr((string)$hotel->arrivalDate,3,2),substr((string)$hotel->arrivalDate,6,4))
  + (60 * 60 * 24 * $offset)),
  'price' => number_format((double)$nightly_rate,2)
  );
  $offset++;
  }
  }
  }
  }
 return $results;
  }
 static public function cancel_reservation($language,$itinerary_number,$email_address,$confirmation_number,$reason)
  {
  $request = '<?xml version="1.0" encoding="utf-8" ?>';
  $request .= '<HotelSessionRequest method="cancelHotelReservation">';
  $request .= '<itineraryId>';
  $request .= $itinerary_number;
  $request .= '</itineraryId>';
  $request .= '<email>';
  $request .= $email_address;
  $request .= '</email>';
  $request .= '<confirmationNumber>';
  $request .= $confirmation_number;
  $request .= '</confirmationNumber>';
  $request .= '<locale>';
  $request .= substr($language,0,2);
  $request .= '</locale>';
  $request .= '<reason>';
  $request .= $reason;
  $request .= '</reason>';
  $request .= '</HotelSessionRequest>';
 $cancellation_data = simplexml_load_string(EAN_API::make_xml_request($request));
 return (string)$cancellation_data->cancellationNumber;
  }
  }
?>