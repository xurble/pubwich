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
				return $this->points[0];
			else 
				return $this->points[0]; // need to do that fancy thing that gets the centre
		
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