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

		public $getgeodata, $stampgeodata;


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


			$this->getgeodata = isset( $config['getgeodata'] ) ? $config['getgeodata'] : false ;
			
			$this->setMapItemTemplate('<div class="mapbox"><a href="{{{url}}}"><img src="{{{image}}}" width="40" style="float:right" alt="" ><strong>{{{name}}}</strong></a><br><small class="date">{{{date}}}</small></div>');


			parent::__construct( $config );
		}



		public function init() {
			parent::init();
			if($this->getgeodata) {
				$this->buildStampGeoCache( false );
			}
			return $this;
		}


		public function populateMapData( &$item, $id ) {
		
		
			if(!$this->getgeodata)
				return null;
		
			$geo = $this->stampgeodata["$id"];
			if($geo->lat != null and $geo->lng != null)
			{

				$loc = new Location();
				$pt = array();
				$pt[0] = $geo->lng;
				$pt[1] = $geo->lat;
				$loc->addPoint($pt);
				$loc->setData($this->populateItemTemplate($item));
				
				return $loc;
			}
			else
				return null;
		}


		/**
		 * @param string $url
		 * @return void
		 */
		public function buildCache() {
			parent::buildCache(); 
			if($this->getgeodata) {
				$this->buildStampGeoCache( true );
			}
		}

		/**
		 * @param bool $rebuildCache Force cache rebuild
		 * @return void
		 */
		public function buildStampGeoCache( $rebuildCache ) {
			// must override
		}

		/**
		 * @param SimpleXMLElement $album
		 * [@param bool $rebuildCache]
		 * @return void
		 */
		public function fetchStampGeoData($id, $rebuildCache=false) {
			$Cache_Lite = new Cache_Lite( parent::getCacheOptions() );
						
			if ( !$rebuildCache && $data = $Cache_Lite->get( $id ) ) {
				$this->stampgeodata["$id"] = Pubwich::json_decode( $data );
				

				
				
			} else {
				$Cache_Lite->get( $id );
				PubwichLog::log( 2, Pubwich::_( 'Rebuilding geo cache for a Gowalla Stamp' ) );
				$url = sprintf( 'http://api.gowalla.com%s', $id);
				$fdata = FileFetcher::get( $url , $this->http_headers);
				$cacheWrite = $Cache_Lite->save( $fdata );
				if ( PEAR::isError($cacheWrite) ) {
					//var_dump( $cacheWrite );
				}
				$pdata = Pubwich::json_decode( $fdata );
				$this->stampgeodata["$id"] = $pdata;
			}

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
			echo($data->last_checkins[0]->spot->url);
			return parent::populateMapData($item,$item->last_checkins[0]->spot->url);
		}

		/**
		 * @param bool $rebuildCache Force cache rebuild
		 * @return void
		 */
		public function buildStampGeoCache( $rebuildCache ) {
			$data = $this->getData();
			foreach ($data as $stamp ) {
				$this->fetchStampGeoData( $stamp->last_checkins[0]->spot->url, $rebuildCache );
			} 
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
			return parent::populateMapData($item,$item->spot->url);
		}

		/**
		 * @param bool $rebuildCache Force cache rebuild
		 * @return void
		 */
		public function buildStampGeoCache( $rebuildCache ) {
			$data = $this->getData();
			if ( $data ) {
				foreach ( $data as $stamp ) {
					$this->fetchStampGeoData( $stamp->spot->url, $rebuildCache );
				}
			}
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
			return parent::populateMapData($item,$item->url);
		}

		/**
		 * @param bool $rebuildCache Force cache rebuild
		 * @return void
		 */
		public function buildStampGeoCache( $rebuildCache ) {
			$data = $this->getData();
			if ( $data ) {
				foreach ( $data as $stamp ) {
					$this->fetchStampGeoData( $stamp->url, $rebuildCache );
				}
			}
		}


	}
