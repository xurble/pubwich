<?php
	defined('PUBWICH') or die('No direct access allowed.');

	/**
	 * @classname Location
	 * @description Retrieves statuses from Twitter
	 * @version 1.1 (20090929)
	 * @author RŽmi PrŽvost (exomel.com)
	 * @methods TwitterUser TwitterSearch
	 */

	class Location {
		private $data,$points;
		function __construct() {	
			$this->points = array();
			$this->data = array();
		}
		
		public function addPoint($point) {
			// are we going to assume that this is a good point?
			// I think we should
			$this->points[count($this->points)] = $point;		
		}
		
		public function setPoints($points) {
			//set all the points in one go, accepts an array of points
			$this->points = $points;
		}
		
		public function getLocation() {
			$c = count($this->points);
			if ($c ==0)
				return null;
			else if ($c == 1)
				$pt = $this->points[0];
			else {
				// This is a region, but I don't want regions
				// find the centroid
				//http://stackoverflow.com/questions/2792443/finding-the-centroid-of-a-polygon
				
				$pt = array(0,0);
				$area = 0.0;
				$x0 = 0.0;
				$y0 = 0.0;
				$x1 = 0.0;
				$y1 = 0.0;
				
				for($i = 0;$i < $c-1;$i++) {
					$x0 = $this->points[$i][0];
					$y0 = $this->points[$i][1];
					$x1 = $this->points[$i+1][0];
					$y1 = $this->points[$i+1][1];
					$a = ($x0 * $y1) - ($x1 * $y0);
					$area  += $a;
					$pt[0] += ($x0 + $x1) * $a;
					$pt[1] += ($y0 + $y1) * $a;
				}
			

				$x0 = $this->points[$i][0];
				$y0 = $this->points[$i][1];
				$x1 = $this->points[0][0];
				$y1 = $this->points[0][1];
				$a = ($x0 * $y1) - ($x1 * $y0);
				$area  += $a;
				$pt[0] += ($x0 + $x1) * $a;
				$pt[1] += ($y0 + $y1) * $a;

				$area *= 0.5;
				$pt[0] /= (6 * $area);
				$pt[1] /= (6 * $area);


			}
			
			//	 I'd like to fuzz the location a little here
			//	 to prevent flags being on top of each other
			//	 but there's no stack overflow code to steal :)
			return $pt;
		
		}
		
		public function getType() {
			$c = count($this->points);
			if ($c==0)
				return "unset";
			else if ($c == 1)
				return "point";
			else 
				return "region";
		}
		
		public function getPoints() {
			return $this->points;
		}
		
		public function getData() {
			return $this->data;
		}		
		
		public function setData($data){
			$this->data = $data;
		}
	}
?>