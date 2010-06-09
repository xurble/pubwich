<?php
	defined('PUBWICH') or die('No direct access allowed.');

	/**
	 * @classname Gowalla
	 * @description Get last check-ins from Gowalla
	 * @version 1.1 (20100210)
	 * @author Rémi Prévost (exomel.com)
	 * @methods GowallaUser GowallaUserStamps
	 */

	class Gowalla extends Service {

		public $base = 'http://gowalla.com';

		/**
		 * @constructor
		 */
		public function __construct( $config ) {
			$this->username = $config['username'];
			$this->setURLTemplate( $this->base.'/'.$config['username'].'/' );
			$this->callback_function = array( Pubwich, 'json_decode' );
			$this->http_headers = array(
				'Accept: application/json'
			);

			if ( $config['key'] ) {
				$this->http_headers[] = sprintf( 'X-Gowalla-API-Key: %s', $config['key'] );
			}

			$this->setMapItemTemplate('<div class="mapbox"><a href="{{{url}}}"><img src="{{{image}}}" width="40" style="float:right" alt="" ><strong>{{{name}}}</strong></a><br><small class="date">{{{date}}}</small></div>');


			parent::__construct( $config );
		}

		public function populateMapData( $item, $spot ) {
		
			if($spot->lat != null and $spot->lng != null)
			{

				$loc = new Location();
				$pt = array();
				$pt[0] = $spot->lng;
				$pt[1] = $spot->lat;
				$loc->addPoint($pt);
				$loc->setData($this->populateItemTemplate($item));
				
				return $loc;
			}
			else
				return null;
		}

	}

	class GowallaUser extends Gowalla {

		/**
		 * @constructor
		 */
		public function __construct( $config ) {
			$this->setURL( sprintf( 'http://%s:%s@api.gowalla.com/users/%s', $config['username'], $config['password'], $config['username'] ) );
			$this->setItemTemplate( '<li class="clearfix"><span class="date">{{{date}}}</span><a class="spot" href="{{{url}}}"><strong>{{{name}}}</strong> <img src="{{{image}}}" alt="" /></a><span class="comment">{{{comment}}}</span></li>'."\n" );
			parent::__construct( $config );
		}

		
		public function getData() {
			$data = parent::getData();
			return array ($data); // need to turn it into a single element array	
		}

		public function populateItemTemplate( &$item ) {
			$last_spot = $item->last_checkins[0];
			return array(
				'first_name' => $item->first_name,
				'last_name' => $item->last_name,
				'comment' => $last_spot->message,
				'date' => Pubwich::time_since( $last_spot->created_at ),
				'image' => $last_spot->spot->image_url,
				'name' => $last_spot->spot->name,
				'url' => $this->base.$last_spot->spot->url,
			);
		}

		public function populateMapData( &$item ) {
			return parent::populateMapData($item,$item->last_checkins[0]->spot);
		}
		
	}

	class GowallaUserStamps extends Gowalla {

		/**
		 * @constructor
		 */
		public function __construct( $config ) {
			$this->total = $config['total'];
			$this->setURL( sprintf( 'http://%s:%s@api.gowalla.com/users/%s/stamps?limit=%d', $config['username'], $config['password'], $config['username'], $config['total'] ) );
			$this->setItemTemplate( '<li><a href="{{{url}}}"><img src="{{{image}}}" width="20" alt="" /><strong>{{{name}}}</strong><small class="date">{{{date}}}</small></a></li>'."\n" );
			parent::__construct( $config );
		}

		public function getData() {
			return parent::getData()->stamps;
		}

		public function populateItemTemplate( &$item ) {
			return array(
				'date' => Pubwich::time_since( $item->last_checkin_at ),
				'image' => $item->spot->image_url,
				'name' => $item->spot->name,
				'url' => $this->base.$item->spot->url,
				'visits' => $item->checkins_count,
			);
		}

		public function populateMapData( &$item ) {
			return parent::populateMapData($item,$item->spot);
		}

	}

	class GowallaUserTopSpots extends Gowalla {

		/**
		 * @constructor
		 */
		public function __construct( $config ) {
			$this->total = $config['total'];
			$this->setURL( sprintf( 'http://%s:%s@api.gowalla.com/users/%s/top_spots', $config['username'], $config['password'], $config['username'] ) );
			$this->setItemTemplate( '<li class="clearfix"><span class="visits">{{{visits}}}</span><a class="spot" href="{{{url}}}"><strong>{{{name}}}</strong> <img src="{{{image}}}" alt="" /></a></li>'."\n" );
			parent::__construct( $config );
		}

		public function getData() {
			return parent::getData()->top_spots;
		}

		public function populateItemTemplate( &$item ) {
			return array(
				'image' => $item->image_url,
				'name' => $item->name,
				'url' => $this->base.$item->url,
				'visits' => $item->user_checkins_count,
			);
		}

		public function populateMapData( &$item ) {
			return parent::populateMapData($item,$item);
		}


	}
