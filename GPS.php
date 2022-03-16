<?php
  
  namespace classes;
  
  class GPS
  {
    public ?string $error = null;
    public ?array $distantLocation;
    public ?array $destinationLocation;
  
  
    public function __construct(public ?array $currentLocation = null, public float $searchRadius = 0.0, public bool $inMiles=true)
    {
      $this->searchRadius = self::checkMinimumRange($searchRadius);
    }
  
    public static function checkMinimumRange($range) {
      return ($range < 0.5) ? 0.5 : $range;
    }
    
    public function getSqlHavingDistanceTail(?array $currentLocation = null, ?float $range = null, ?bool $isRangeInMiles=null) :mixed
    {
      // Calculate distance and filter records by radius
      $returnable = [];
      $isRangeInMiles = $isRangeInMiles ?? $this->inMiles;
      $currentLocation = $currentLocation ?? $this->currentLocation;
      $range = self::checkMinimumRange($range) ?? $this->searchRadius;
      if (is_array($currentLocation)) {
        $latitude = $currentLocation["lat"];
        $longitude = $currentLocation["lng"];
        $radius_km =  $isRangeInMiles? self::mileToKm($range) : $range;
//        if (!empty($range) && !empty($latitude) && !empty($longitude)) {
        $sql_distance = " ,(((acos(sin((" . $latitude . "*pi()/180)) * sin((`p`.`latitude`*pi()/180))+cos((" . $latitude . "*pi()/180)) * cos((`p`.`latitude`*pi()/180)) * cos(((" . $longitude . "-`p`.`longitude`)*pi()/180))))*180/pi())*60*1.1515*1.609344) as distance ";
        $sql_having = " HAVING (distance <= $radius_km) ";
        $sql_orderBy = ' distance ASC ';
//        } else {
//          $sql_orderBy = ' _id DESC ';
//        }
        $returnable["sqlDistance"] = $sql_distance;
        $returnable["tail"] = $sql_having;
        $returnable["orderBy"] = $sql_orderBy;
        return $returnable;
      } else {
        $this->error = "Bad current Location";
        return false;
      }
    } // method
  
    public function getDistance (?array $location1=null, ?array $location2=null, ?bool $returnInMiles=null) {
      $returnInMiles = $isRangeInMiles ?? $this->inMiles;
      $location1 = $location1 ?? $this->currentLocation;
      $location2 = $location2 ?? $this->destinationLocation;
      // lats
			$lat1 = $location1["lat"];
      $lat2 = $location2["lat"];
      // lngs
			$lon1 = $location1["lng"];
      $lon2 = $location2["lng"];
			// dist
      $distance = 2*asin(sqrt((sin(($lat1-$lat2)/2))^2 + cos($lat1)*cos($lat2)*(sin(($lon1-$lon2)/2))^2)); // in radians
      return $returnInMiles ? self::kmToMile($distance * 6371) : $distance * 6371; // in miles: in km
    }
	
	
		function getDistance2 (?array $l1=null, ?array $l2=null, ?bool $returnInMiles=null)
		{
			$returnInMiles = $isRangeInMiles ?? $this->inMiles;
			$l1 = $l1 ?? $this->currentLocation;
			$l2 = $l2 ?? $this->destinationLocation;
			// lats
			$lat1 = deg2rad($l1["lat"]);
			$lat2 = deg2rad($l2["lat"]);
			// lngs
			$long1 = deg2rad($l1["lng"]);
			$long2 = deg2rad($l2["lng"]);
			//Haversine Formula
			$dlong = $long2 - $long1;
			$dlati = $lat2 - $lat1;
		
			$val = pow(sin($dlati/2),2)+cos($lat1)*cos($lat2)*pow(sin($dlong/2),2);
			$res = 2 * asin(sqrt($val));
			// $radius = $returnInMiles ? 3958.756 : 6371; //earth radius
			return ($res* ($returnInMiles ? 3958.756 : 6371));
		}

    public function getNewLocationBasedOnDistance ($currentLocation, $dx, $dy, ?bool $givenInMiles=null) : array {
      $givenInMiles = $isRangeInMiles ?? $this->inMiles;
      if ($givenInMiles) {
        $dx = self::mileToKm($dx);
        $dy = self::mileToKm($dy);
      }
      $lat = $currentLocation["lat"] + (180/pi())*($dy/6371);
      $lon = $currentLocation["lng"] + (180/pi())*($dx/6371)/cos($currentLocation["lat"]);
      $this->distantLocation = self::makeLocation($lat, $lon);
      return $this->distantLocation;
    }
    
    public static function makeLocation ($lat=null, $lon=null) :mixed {
      $returnable = [];
      if (!is_null($lat)) $returnable["lat"] = $lat;
      if (!is_null($lat)) $returnable["lng"] = $lon;
      return (sizeof($returnable)>1) ? $returnable : false;
    }
  
    public function setCurrentLocation ($lat, $lon) {
      $this->currentLocation = self::makeLocation($lat, $lon);
    }
    public function getCurrentLocation () {
      return $this->currentLocation;
    }
    public function setDestinationLocation ($lat, $lon) {
      $this->destinationLocation = self::makeLocation($lat, $lon);
    }
    public function getDestinationLocation () {
      return $this->destinationLocation;
    }
		
    public static function mileToKm($mile) {
      return 1.609344 * $mile;
    }
  
    public static function kmToMile($km) {
      return $km / 1.609344;
    }
    
  }
