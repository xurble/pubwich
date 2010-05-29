<?php
	defined('PUBWICH') or die('No direct access allowed.');

	/**
	 * @classname Delicious
	 * @description Fetch Del.icio.us bookmarks
	 * @version 1.2 (20100421)
	 * @author Rémi Prévost (exomel.com)
	 * @methods None
	 */

	Pubwich::requireServiceFile( 'RSS' );
	class Delicious extends RSS {

		public function __construct( $config ){
			$config['link'] = 'http://delicious.com/'.$config['username'].'/';
			//$config['url'] = sprintf( 'http://feeds.delicious.com/v2/rss/%s?count=%s', $config['username'], $config['total'] );
			$config['url'] = 'http://feeds.feedburner.com/Delicious/xurble?format=xml';
			parent::__construct( $config );
			$this->setItemTemplate('<li class="link"><a href="{{{link}}}">{{{title}}}</a>{{{tags}}}</li>'."\n");
		}

		/**
		 * @return array
		 */
		public function populateItemTemplate( &$item ) {
			$comments_count = $item->children('http://purl.org/rss/1.0/modules/slash/')->comments;
			$content = $item->children('http://purl.org/rss/1.0/modules/content/')->encoded;

			$tags = ''; // pull tags out and make links of them
			foreach($item->category as $t)
			{
				$tags .= '<li class="tag"><a href="'.$t->attributes().htmlspecialchars($t).'">'.htmlspecialchars($t).'</a></li>';
			}
			if($tags != '')
			{
				$tags = '<ul class="tags">'.$tags.'<li>Tagged:</li></ul>';
			}

			return array(
						'link' => htmlspecialchars( $item->link ),
						'title' => trim( $item->title ),
						'date' => Pubwich::time_since( $item->pubDate ),
						'comments_link' => $item->comments,
						'comments_count' => $comments_count,
						'description' => $item->description,
						'content' => $content,
						'author' => $item->author,
						'tags' => $tags,
			);
		}


	}

