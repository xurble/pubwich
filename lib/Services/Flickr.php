<?php
	defined('PUBWICH') or die('No direct access allowed.');

	/**
	 * @classname Flickr
	 * @description Retreives photos from Flickr
	 * @version 1.1 (20090929)
	 * @author Rémi Prévost (exomel.com)
	 * @methods FlickrUser FlickrGroup FlickrTags
	 */

	class Flickr extends Service {

		public $compteur, $row, $sort;
		
		public $getgeodata, $photogeodata, $apikey;

		/**
		 * @constructor
		 */
		public function __construct( $config ) {
			parent::__construct( $config );
			$this->apikey = $config['key'];
		}

		/**
		 * Sets some common variables (row, sort, compteur) and item template
		 * @param array $config The config array
		 * @return void
		 */
		public function setVariables( $config ) {
			$this->row = $config['row'];
			$this->sort = isset( $config['sort'] ) ? $config['sort'] : 'date-posted-desc';
			$this->compteur = 0;
			$this->setItemTemplate('<li{{{classe}}}><a href="{{{link}}}"><img src="{{{photo}}}" alt="{{{title}}}" /></a></li>'."\n");
			$this->getgeodata = isset( $config['getgeodata'] ) ? $config['getgeodata'] : false ;
			
			$this->setMapItemTemplate('<div class="mapbox">{{{title}}}<br><a href="{{{link}}}"><img src="{{{photo}}}" alt="{{{title}}}" /></a></div>');

		}

		/**
		 * Return an array of key->value using the item data
		 * @return array
		 */
		public function populateItemTemplate( &$item ) {
			$this->compteur++;
			$path = $item['pathalias']!='' ? $item['pathalias'] : $item['owner'];
			return array(
						'link' => 'http://www.flickr.com/photos/'.$path.'/'.$item['id'].'/',
						'title' => htmlspecialchars( $item['title'] ),
						'photo' => $this->getAbsoluteUrl( $item ),
						'classe' => ($this->compteur % $this->row == 0 ) ? ' class="derniere"' : ''
			);
		}

		/**
		 * Return a Flickr photo URL
		 * @param array $photo Photo item
		 * @return string
		 */
		public function getAbsoluteUrl( $photo, $size= 's' ) {
			return sprintf( 'http://farm%d.static.flickr.com/%s/%s_%s_%s.jpg',
				$photo['farm'],
				$photo['server'],
				$photo['id'],
				$photo['secret'],
				$size
			);
		}

		/**
		 * @return Flickr
		 */
		public function init() {
			parent::init();
			if($this->getgeodata) {
				$this->buildPhotoGeoCache( false );
			}
			return $this;
		}


		/**
		 * Overcharge parent::getData()
		 * @return SimpleXMLElement
		 */
		public function getData() {
			$data = parent::getData();
			return $data->photos->photo;
		}


		public function populateMapData( &$item ) {
		
			if(!$this->getgeodata)
				return null;
		
			$id = $item['id']."";
			$geo = $this->photogeodata[$id];

			if ($geo->err["code"] != null )
				return null;
				
			$loc = new Location();
			$pt = array();
			$pt[0] = $geo->photo->location["longitude"];
			$pt[1] = $geo->photo->location["latitude"];
			$loc->addPoint($pt);
			$loc->setData($this->populateItemTemplate($item));
			
			return $loc;
		}


		/**
		 * @param string $url
		 * @return void
		 */
		public function buildCache() {
			parent::buildCache(); 
			if($this->getgeodata) {
				$this->buildPhotoGeoCache( true );
			}
		}

		/**
		 * @param bool $rebuildCache Force cache rebuild
		 * @return void
		 */
		public function buildPhotoGeoCache( $rebuildCache ) {
			$data = $this->getData();
			if ( $data ) {
				foreach ( $data as $photo ) {
					$this->fetchPhotoGeoData( $photo, $rebuildCache );
				}
			}
		}

		/**
		 * @param SimpleXMLElement $album
		 * [@param bool $rebuildCache]
		 * @return void
		 */
		public function fetchPhotoGeoData($photo, $rebuildCache=false) {
			$Cache_Lite = new Cache_Lite( parent::getCacheOptions() );
			$id = $photo["id"];
			if ( !$rebuildCache && $data = $Cache_Lite->get( $id ) ) {
				$this->photogeodata["$id"] = simplexml_load_string( $data );
			} else {
				$Cache_Lite->get( $id );
				PubwichLog::log( 2, Pubwich::_( 'Rebuilding geo cache for a Flickr Photo' ) );
				$url = sprintf( 'http://api.flickr.com/services/rest/?method=flickr.photos.geo.getLocation&api_key=%s&photo_id=%s', $this->apikey, $id);
				$fdata = FileFetcher::get( $url );
				$cacheWrite = $Cache_Lite->save( $fdata );
				if ( PEAR::isError($cacheWrite) ) {
					//var_dump( $cacheWrite );
				}
				$pdata = simplexml_load_string( $fdata );
				$this->photogeodata["$id"] = $pdata;
			}

		}


	}

	class FlickrUser extends Flickr {

		public function __construct( $config ) {
			parent::setVariables( $config );
			$this->setURL( sprintf( 'http://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=%s&user_id=%s&sort=%s&extras=owner_name,path_alias&per_page=%d', $config['key'], $config['userid'], $this->sort, $config['total'] ) );
			$this->setURLTemplate('http://www.flickr.com/photos/'.$config['username'].'/');
			parent::__construct( $config );
		}

	}

	class FlickrGroup extends Flickr {

		private $groupname;

		public function __construct( $config ) {
			parent::setVariables( $config );
			$this->groupname = $config['groupname'];
			$this->setURL( sprintf( 'http://api.flickr.com/services/rest/?method=flickr.groups.pools.getPhotos&api_key=%s&group_id=%s&extras=owner_name,path_alias&per_page=%d', $config['key'], $config['groupid'], $config['total'] ) );
			$this->setURLTemplate('http://www.flickr.com/groups/'.$config['groupname'].'/');
			parent::__construct( $config  );
		}

		public function populateItemTemplate( &$item ) {
			$path = $item['pathalias']!='' ? $item['pathalias'] : $item['owner'];
			$original = parent::populateItemTemplate( $item );
			$original['link'] = 'http://www.flickr.com/photos/'.$path.'/'.$item['id'].'/in/pool-'.$this->groupname.'/';
			return $original;
		}

	}

	class FlickrTags extends Flickr {

		public function __construct( $config ) {
			parent::setVariables( $config );

			if ( !is_array( $config['tags'] ) ) { $config['tags'] = explode( ',', $config['tags'] ); }
			$maintag = $config['tags'][0];
			$config['tags'] = implode( ',', $config['tags'] );

			$this->setURL( sprintf( 'http://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=%s&tags=%s&sort=%s&per_page=%d&extras=owner_name,path_alias', $config['key'], $config['tags'], $this->sort, $config['total'] ) );
			$this->setURLTemplate('http://www.flickr.com/photos/tags/'.$maintag.'/');
			parent::__construct( $config );
		}

	}
