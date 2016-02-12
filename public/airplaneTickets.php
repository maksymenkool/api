<?php
	include 'config.php';
	include 'travel_additions.php';
	include 'airplane.php';

	$latitude = $VARS['latitude'];
	$longitude = $VARS['longitude'];
	$toLatitude = $VARS['toLatitude'];
	$toLongitude = $VARS['toLongitude'];
	$when = $VARS['departure'];
	$return = $VARS['return'];
	$adults = $VARS['adults'];
	$children = $VARS['children'];
	$infants = $VARS['infants'];
	
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$bookings = array();		
		$from = airportCode($latitude,$longitude);
		$to = airportCode($toLatitude,$toLongitude);

		
		if(strlen($when) <= 0) {
			$when = "2016-05-23";
		}

		$finalCarrierName;
		$finalPrice = 999999;
		$originalData;
		$airpot;
		$fromResult;
		$toResult;

		// $handle = popen('php airplane_thread.php '.$from[$i]["code"].' '.$to[$j]["code"].' '.$when.' '.$return.' '.$adults.' '.$children.' '.$infants, 'r');
		
		for($i = 0; $i < count($from); ++$i) {
			$fromResult = $from[$i];
			for($j = 0; $j < count($to); ++$j) {
				$toResult = $to[$j];
				$planeDetails = airplineData($from[$i]["code"], $to[$j]["code"], $when, $return, $adults, $children, $infants);
				$carrierName = $planeDetails["trips"]["data"]["carrier"][0]["name"];
				$price = $planeDetails["trips"]["tripOption"][0]["saleTotal"];
				$price = substr($price,3);
				if(strlen($carrierName) <= 0 || $price <= 0) continue;
				// print $from[$i]["code"]." -> ".$to[$j]["code"].", Carrier:".$carrierName." , Price:".$price."\n";
				if($price < $finalPrice) {
					$originalData = $planeDetails["trips"]["tripOption"][0];
					$finalCarrierName = $carrierName;
					$finalPrice = $price;
					$airpot = $from[$i];
				}
			}
		}
		$rtn = array(
			'name' => $finalCarrierName,
			'price' => $finalPrice,
			'from' => $fromResult,
			'to' => $toResult,
			'original' => $originalData);

		response_code(200);
	    print json_encode($rtn);
	    return;
	}
	
	function airportCode($lat, $long) {

		//speedup hack
		if($lat > -39 && $lat < -36 &&
			$lon > -123 && $lon < -121) {
			return json_decode('[{"code":"SJC","lat":37.362598419189,"lon":-121.92900085449},{"code":"SFO","lat":37.618999481201,"lon":-122.375}]');
		}

		if($lat > -47 && $lat < -44 &&
			$lon > 8 && $lon < 11) {
			return json_decode('[{"code":"LIN","lat":45.445098877,"lon":9.27674007416},{"code":"MXP","lat":45.6305999756,"lon":8.72811031342}]');
		}
		//$succeed;
		//$lines = apc_fetch('lines',&$succeed);
			// phpinfo();

		// if(!$succeed) {
			$csv = file_get_contents('airports_large.csv');
			$lines = explode(PHP_EOL, $csv);
		// 	apcu_store('lines', $lines);
		// }


		$closestAirportCodes = array();
		$closestAirportLines = array();
		$distances = array();
		
		for($i = 0; $i < count($lines); ++$i) {
			$line = $lines[$i];
			if(count($lines) < 10) {
				continue;
			}

			$components = explode(',', $line);
			$lineLat = (float) str_replace('"','',$components[4]);
			$lineLon = (float) str_replace('"','',$components[5]);
			$type = str_replace('"','',$components[2]);
			$newDistance = distance($lat, $long, $lineLat, $lineLon);
			
			$distanceIndex = -1;
			$closestAirportCode;
			$closestComponents;

			$newCode = str_replace('"','',$components[13]);
			if(!strlen($newCode) || $type != "large_airport"){
				continue;
			}

			if (count($closestAirportCodes) < 2){
				$closestAirportCodes[] = $newCode;
				$closestAirportLines[] = array("code" => $newCode,
				"lat" => $lineLat,
				"lon" => $lineLon
			);
				$distances[] = $newDistance;
				continue;
			}
			
			for($j = 0; $j < count($closestAirportCodes); ++$j) {
				if($newDistance < $distances[$j]) {
					if($distanceIndex == -1 || $distances[$j] > $distances[$distanceIndex]) {
						$distanceIndex = $j;
					}
				}
			}
			
			if($distanceIndex >= 0){
				$closestAirportCodes[$distanceIndex] = $newCode;
				$distances[$distanceIndex] = $newDistance;
				$closestAirportLines[$distanceIndex] = array("code" => $newCode,
				"lat" => $lineLat,
				"lon" => $lineLon
			);
			}  			
			
		}	    

		return $closestAirportLines;
	}
	
	function distance($lat1, $lon1, $lat2, $lon2, $unit = "M") {
	  $theta = $lon1 - $lon2;
	  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	  $dist = acos($dist);
	  $dist = rad2deg($dist);
	  $miles = $dist * 60 * 1.1515;
	  $unit = strtoupper($unit);

	  if ($unit == "K") {
	    return ($miles * 1.609344);
	  } else if ($unit == "N") {
	      return ($miles * 0.8684);
	    } else {
	        return $miles;
	      }
	}
	
?>