<?php
	include '../api/config.php';
	include '../api/travel_additions.php';

	date_default_timezone_set('America/Dawson_Creek');
	$ts_code = "a6e36";
	$key = "119d6e90e2e6e9cf818c";

	$locationID = $VARS['location'];
	$hotel_id = $VARS['hotel_id'];
	$hotelID = $VARS['hotel'];
	$checkin = $VARS['checkin'];
	$checkout = $VARS['checkout'];
	$adults = $VARS['adults'];
	$children = $VARS['children'];
	$infants = $VARS['infants'];

	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$query= "SELECT * FROM hotels WHERE id = {$hotel_id}";
		$hotels = mysql_query($query) or die(mysql_error());
		$hotel = mysql_fetch_array($hotels, MYSQL_ASSOC);

		$priceIndex = $hotelID%5;
		$price = 100 + 20*$priceIndex;
		
		sleep($priceIndex%3);
		$response = array('room_rates' => array(array('price_str' => "".$price)), 'name' => $hotel['name']);

		response_code(200);
	    print json_encode($response);
	    return;
	}
	
	function getLocations($text) {
	    global $key, $ts_code;
		$text = urlencode($text);
		$url = 'http://api.wego.com/hotels/api/locations/search?q='.$text.'&key='.$key.'&ts_code='.$ts_code;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$json = json_decode(trim($result), true);
		curl_close($ch);

		return $json["locations"];
	}
	
	function startSearch($locationID, $startDate = 0, $endDate = 0) {
	    global $key, $ts_code, $adults, $children, $infants;
		if(!$startDate || !strlen($startDate)) {
			$date=strtotime(date('Y-m-d'));
			$startDate = date('Y-m-d',strtotime('+6 months',$date));
		}
		if(!$endDate || !strlen($endDate)) {
			$date=strtotime(date('Y-m-d'));
			$endDate = date('Y-m-d',strtotime('+6 months 2 days',$date));
		}
		$rooms = ceil($adults/2);
		$guests = $adults+$children;
		$url = 'http://api.wego.com/hotels/api/search/new?location_id='.$locationID.'&check_in='.$startDate.'&check_out='.$endDate.'&rooms='.$rooms.'&guests='.$guests.'&user_ip=direct&key='.$key.'&ts_code='.$ts_code;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$json = json_decode(trim($result), true);

		curl_close($ch);

		return $json["search_id"];
	}
	
	function getSearchResults($searchID) {
	    global $key, $ts_code;
		$url = 'http://api.wego.com/hotels/api/search/'.$searchID.'?key='.$key.'&ts_code='.$ts_code;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$json = json_decode(trim($result), true);
		curl_close($ch);

		return $json;
	}

	function getHotelPrice($searchID, $hotelID) {
	    global $key, $ts_code;
		$url = 'http://api.wego.com/hotels/api/search/show/'.$searchID.'?hotel_id='.$hotelID.'&key='.$key.'&ts_code='.$ts_code;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$json = json_decode(trim($result), true);
		curl_close($ch);
		return $json;
	}
?>