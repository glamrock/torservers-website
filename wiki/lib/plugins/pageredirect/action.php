<?php 
/** 
 * Action Plugin:   Redirects page requests based on content 
 *  
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html) 
 * @author     David Lorentsen <zyberdog@quakenet.org>   
 */ 

if(!defined('DOKU_INC')) die(); 

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/'); 
require_once(DOKU_PLUGIN.'action.php'); 

/** 
 * All DokuWiki action plugins need to inherit from this class 
 */ 
class action_plugin_pageredirect extends DokuWiki_Action_Plugin { 

	/** 
	 * return some info 
	 */ 
	function getInfo(){ 
		return array( 
			'author' => 'David Lorentsen', 
			'email'  => 'zyberdog@quakenet.org', 
			'date'   => '2007-01-24', 
			'name'   => 'Page Redirect', 
			'desc'   => 'Redirects page requests based on content', 
			'url'    => 'http://wiki.splitbrain.org/plugin:page_redirector', 
		); 
	} 
	
	function register(&$controller) { 
		$controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handle_pageredirect_redirect');
		$controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'handle_pageredirect_note');
		$controller->register_hook('PARSER_METADATA_RENDER','BEFORE', $this, 'handle_pageredirect_metadata');
	}

	function handle_pageredirect_redirect(&$event, $param) { 
		global $ID, $ACT, $REV; 
		
		if (($ACT == 'show' || $ACT == '') && empty($REV)) { 
			$page = p_get_metadata($ID,'relation isreplacedby');
			
			// return if no redirection data
			if (empty($page)) { return; }
			
			if (isset($_GET['redirect'])) {
				// return if redirection is temporarily disabled, 
				// or we have been redirected 5 times in a row
				if ($_GET['redirect'] == 'no' || $_GET['redirect'] > 4) { return; }
				elseif ($_GET['redirect'] > 0) { $redirect = $_GET['redirect'] +1; }
				else { $redirect = 1; }
			} else {
				$redirect = 1;
			}
	
			// verify metadata currency 
			if (@filemtime(metaFN($ID,'.meta')) < @filemtime(wikiFN($ID))) { return; } 
			
			if (!headers_sent() && $this->getConf('show_note')) {
				// remember to show note about being redirected from another page
				session_start();
				$_SESSION[DOKU_COOKIE]['redirect'] = $ID;
			}
	
			// redirect
			header("Location: ".wl($page, Array('redirect' => $redirect), TRUE, '&')); 
			exit(); 
		} 
	}

	function handle_pageredirect_note(&$event, $param) {
		global $ID, $ACT;
		
		if ($ACT == 'show' || $ACT == '') {
			if (!$this->getConf('show_note')) { return; }
			if (isset($_GET['redirect']) && $_GET['redirect'] > 0 && $_GET['redirect'] < 6) {
				if (isset($_SESSION[DOKU_COOKIE]['redirect']) && $_SESSION[DOKU_COOKIE]['redirect'] != '') {
					// we were redirected from another page, show it!
					$page = $_SESSION[DOKU_COOKIE]['redirect'];
					echo '<div class="noteredirect">'.sprintf($this->getLang('redirected_from'), '<a href="'.wl(':'.$page, Array('redirect' => 'no'), TRUE, '&').'" class="wikilink1" title="'.$page.'">'.$page.'</a>').'</div>';
					unset($_SESSION[DOKU_COOKIE]['redirect']);
					
					return true;
				}
			}
		}
		
		return true;
	}

	function handle_pageredirect_metadata(&$event, $param) { 
		unset($event->data->meta['relation']['isreplacedby']); 
	}

}
