<?php
	defined('PUBWICH') or die('No direct access allowed.');

	/**
	 * @classname Delicious
	 * @description Fetch your google buzz filtering out any buzzes imported from other services
	 * @version 1.0 (20100421)
	 * @author Gareth Simpson (xurble.org)
	 * @methods None
	 */

	Pubwich::requireServiceFile( 'Atom' );
	class Buzz extends Atom {
	
		public function __construct( $config ){
			$config['link'] = 'http://www.google.com/profiles/'.$config['username'].'#buzz';
			$config['url'] = 'http://buzz.googleapis.com/feeds/'.$config['username'].'/public/posted';
 			
			parent::__construct( $config );
			$this->setItemTemplate('<li><div>{%content%}</div><div style="text-align:right"><small><a href="{%link%}"> {%date%}</a></small></div></li>'."\n");
		}
		
		/**
		 * @return string
		 */	
		public function getData() {
			
			$a = array();
			$i = 0;
			foreach($this->data->entry as $d)
			{
				if(strstr($d->title ,"from Mobile") || strstr($d->title ,"from Buzz")) // filter on original Buzz Posts or we will dupe our flickr and twitter
					$a[$i++]= $d;
			}
			return $a;
		}

	}

