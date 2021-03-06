<?php
	defined('PUBWICH') or die('No direct access allowed.');

	/**
	 * @classname Service
	 */
	class Service {

		public $data, $cache_id, $cache_options, $title, $description, $urlTemplate, $username, $total, $method, $callback_function, $header_link, $http_headers;
		private $url, $itemTemplate, $tmpTemplate, $boxTemplate, $tmpBoxTemplate, $mapItemTemplate,$tmpMapItemTemplate;

		/**
		 * @constructor
		 */
		public function __construct( $config=null ) {
			PubwichLog::log( 2, sprintf( Pubwich::_("Creating an instance of %s"), get_class( $this ) ) );

			$this->title = $config['title'];
			$this->description = $config['description'];

			$id = md5( $this->getURL() );
			$this->cache_id = $id;

			if ( !$this->callback_function ) {
				$this->callback_function = 'simplexml_load_string';
			}

			$this->cache_options = array(
				'cacheDir' => CACHE_LOCATION,
				'lifeTime' => CACHE_LIMIT,
				'readControl' => true,
				'readControlType' => 'strlen',
				'errorHandlingAPIBreak' => true,
				'automaticSerialization' => false
			);

			$this->itemTemplate = new PubwichTemplate();
			if ( $this->tmpTemplate ) {
				$this->setItemTemplate( $this->tmpTemplate );
				$this->tmpTemplate = null;
			}

			$this->boxTemplate = new PubwichTemplate();
			if ( $this->tmpBoxTemplate ) {
				$this->setBoxTemplate( $this->tmpBoxTemplate );
				$this->tmpBoxTemplate = null;
			}
			
			$this->mapItemTemplate = new PubwichTemplate();
			if ( $this->tmpMapItemTemplate ) {
				$this->setMapItemTemplate( $this->tmpMapItemTemplate );
				$this->tmpMapItemTemplate = null;
			}
			
		}

		/**
		 * @return array
		 */
		public function getCacheOptions() {
			return $this->cache_options;
		}

		/**
		 * @return string
		 */
		public function getURL() {
			return $this->url;
		}

		/**
		 * @param string $url
		 * @return void
		 */
		public function setURL( $url ) {
			PubwichLog::log( 3, sprintf( Pubwich::_("Setting the URL for %s: %s"), get_class( $this ), $url ) );
			$this->url = $url;
		}

		/**
		 * @param string $url
		 * @return Service
		 */
		public function init() {
			PubwichLog::log( 2, sprintf( Pubwich::_("Initializing instance of %s"), get_class( $this ) ) );
			$url = $this->getURL();
			$Cache_Lite = new Cache_Lite( $this->cache_options );

			if ($data = $Cache_Lite->get( $this->cache_id) ) {
				libxml_use_internal_errors( true );
				$this->data = $data;
				if ( is_string( $data ) ) {
					$this->data = call_user_func( $this->callback_function, $this->data );
				}
				libxml_clear_errors();
			}
			else {
				$this->buildCache( $Cache_Lite );
			}
			return $this;
		}

		/**
		 * [@param Cache_Lite $Cache_Lite]
		 * @return void
		 */
		public function buildCache( $Cache_Lite = null ) {
			PubwichLog::log( 2, sprintf( Pubwich::_('Rebuilding the cache for %s service' ), get_class( $this ) ) );
			$url = $this->getURL();
			if ( !$Cache_Lite ) {
				$Cache_Lite = new Cache_Lite( $this->cache_options );
				$Cache_Lite->get( $this->cache_id );
			}
			if ( !$this->callback_getdata ) {
				$content = FileFetcher::get( $url, $this->http_headers );
			} else {
				$content = call_user_func( $this->callback_getdata[0], $this->callback_getdata[1] );
			}
			if ( $content !== false ) {
				$cacheWrite = $Cache_Lite->save( $content );
				libxml_use_internal_errors( true );
				$this->data = $content;
				if ( is_string( $this->data ) ) {
					$this->data = call_user_func( $this->callback_function, $this->data );
				}
			} else {
				$this->data = false;
			}
		}

		/**
		 * @return string
		 */
		public function getData() {
			return $this->data;
		}

		/**
		 * return string
		 */
		public function getVariable() {
			return $this->variable;
		}

		/**
		 * @param string $variable
		 * @return void
		 */
		public function setVariable( $variable ) {
			$this->variable = $variable;
		}

		/**
		 * @param string $template
		 * @return void
		 */
		public function setURLTemplate( $template ) {
			$this->urlTemplate = $template;
		}

		/**
		 * @param string $template
		 * @return void
		 */
		public function setItemTemplate( $template ) {
			if ( !$this->itemTemplate ) {
				$this->tmpTemplate = $template;
			} else {
				$this->itemTemplate->setTemplate( $template );
			}
		}

		/**
		 * @return PubwichTemplate
		 */
		public function getItemTemplate() {
			return $this->itemTemplate;
		}

		/**
		 * @param string $template
		 * @return void
		 */
		public function setMapItemTemplate( $template ) {
			if ( !$this->mapItemTemplate ) {
				$this->tmpMapItemTemplate = $template;
			} else {
				$this->mapItemTemplate->setTemplate( $template );
			}
		}

		
		/**
		 * @return PubwichTemplate
		 */
		public function getMapItemTemplate() {
			return $this->mapItemTemplate;
		}

		/**
		 * @param string $template
		 */
		public function setBoxTemplate( $template ) {
			if ( !$this->boxTemplate ) {
				$this->tmpBoxTemplate = $template;
			} else {
				$this->boxTemplate->setTemplate( $template );
			}
		}

		/**
		 * @return PubwichTemplate
		 */
		public function getBoxTemplate() {
			return $this->boxTemplate;
		}

		/**
		 * return @array
		 */
		public function populateBoxTemplate() {
			return array(
				'id' => $this->getVariable(),
				'url' => $this->urlTemplate,
				'title' => $this->title,
				'description' => $this->description,
			);
		}

		/*
		 * @return string
		 */
		public function renderBox( ) {

			$items = '';
			$classData = $this->getData();

			$htmlClass = strtolower(join($this->getClasses(),' '));
			
			if ( !$classData ) {
				$items = '<li class="nodata">'.sprintf( Pubwich::_('An error occured with the %s API. The data is therefore unavailable.'), get_class( $this ) ).'</li>';
				$htmlClass .= ' nodata';
			} else {
				foreach( $classData as $item ) {
					$compteur++;
					if ($this->total && $compteur > $this->total) { break; }  
					$populate = $this->populateItemTemplate( $item );

					if ( function_exists( get_class( $this ) . '_populateItemTemplate' ) ) {
						$populate = call_user_func( get_class( $this ) . '_populateItemTemplate', $item ) + $populate;
					}

					$this->getItemTemplate()->populate( $populate );
					$items .= $this->getItemTemplate()->output();
				}
			}

			$data = array(
				'class' => $htmlClass,
				'items' => $items
			);

			// Let the service override it
			$data = $this->populateBoxTemplate( $data ) + $data;

			// Let the theme override it
			if ( function_exists( 'populateBoxTemplate' ) ) {
				$data = call_user_func( 'populateBoxTemplate', $this, $data ) + $data;
			}

			$this->getBoxTemplate()->populate( $data );
			return $this->getBoxTemplate()->output();
		}
		
		
		public function getMapData() {
			$items = '';
			$classData = $this->getData();
			if (!$classData){
				return '';
			}
			else {
				foreach($classData as $item) {
					$compteur++;
					if ($this->total && $compteur > $this->total) { break; }  
					$location = $this->populateMapData( $item );
					if ($location != null) {
						// this is the theme overriding bit from the main boxes, will have to do map equivalent at some point
						//if ( function_exists( get_class( $this ) . '_populateItemTemplate' ) ) {
						//	$populate = call_user_func( get_class( $this ) . '_populateItemTemplate', $item ) + $populate;
						//}
						
						//s
	
						$this->getMapItemTemplate()->populate( $location->getData() );
						$itemcontent = $this->getMapItemTemplate()->output();
						// escape quotes for js
						$itemcontent = str_replace('"','\"',$itemcontent);
						$point = $location->getLocation();
						
						
						$items .= ', { lat: "'.$point[1].'", long:"'.$point[0].'",html:"'.$itemcontent.'"}'."\n";
					}
				}
			}
			
			return $items;
		}
		
		// Default implementation so that only map aware services have to override it
		public function populateMapData($item)	{
			return null;
		}

		public function setHeaderLink( $link ) {
			$this->header_link = $link;
		}

		public function getHeaderLink() {
			return $this->header_link;
		}

		//	 Get the full heirarchy of classes up to but not including 'Service'
		public function getClasses($class=null, $plist=array()) {
			if(!$class) {
				$plist[] = get_class($this);
			}
			$class = $class ? $class : $this;
			$parent = get_parent_class($class);
			if($parent && $parent != 'Service') {
				$plist[] = $parent;
				/*Do not use $this. Use 'self' here instead, or you
				* will get an infinite loop. */
				$plist = self::getClasses($parent, $plist);
			}
			return $plist;
		}

	}
