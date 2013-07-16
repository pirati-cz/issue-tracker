<?php
/**
 *
 * Pirati: Task
 *
 * @author Vaclav Malek <vaclav.malek@pirati.cz>
 *
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');
require_once(DOKU_PLUGIN.'piratitask/piratitask.class.php');

class action_plugin_piratitask extends DokuWiki_Action_Plugin
{

     private $piratitask = null;

     function init(){
          if(is_null($this->piratitask)) $this->piratitask = new Piratitask();
     }

     function register(&$controller){
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'boostme');
        $controller->register_hook('DOKUWIKI_STARTED','AFTER',$this,'boostjs');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'ajax');
        $controller->register_hook('TPL_CONTENT_DISPLAY','BEFORE',$this,'edit');
        // rss
        $controller->register_hook('FEED_OPTS_POSTPROCESS', 'AFTER', $this, 'rss_opts', array());
        $controller->register_hook('FEED_MODE_UNKNOWN', 'BEFORE', $this, 'rss', array ());
        $controller->register_hook('FEED_ITEM_ADD', 'BEFORE', $this, 'rss_item', array());
     }

     public function rss_opts(&$event, $param){
          $opt =& $event->data['opt'];
          if($opt['feed_mode'] != 'piratitask') return;
          if(!isset($_GET['ns'])) return;

          $opt['userhash'] = $_GET['id'];
          $opt['namespace'] = $_GET['ns'];
     }

     public function rss_item(&$event, $param){
          $opt = $event->data['opt'];
          $ditem = $event->data['ditem'];
          if($opt['feed_mode'] !== 'piratitask') return;
          if(empty($opt['namespace'])) return;

          $event->data['item']->title = $ditem['title'];
          $event->data['item']->description = $ditem['description'];
          $event->data['item']->link = $ditem['link'];
          $event->data['item']->guid = $ditem['guid'];
          //$event->data['item']->author = $ditem['author'];
          $event->data['item']->date = $ditem['date'];
          $event->data['item']->category = $ditem['category'];
     }

     public function rss(&$event, $param){
          $opt = $event->data['opt'];
          if ($opt['feed_mode'] != 'piratitask') return;

          $event->preventDefault();
          $event->data['data'] = array();

          $this->init();
          $this->piratitask->setNamespace($opt['namespace']);
          $this->piratitask->rssAction($event, $param); 
     }
     public function boostme(&$event, $param){
          $this->init();
          $this->piratitask->boostmeAction($event, $param);
     }
     public function boostjs(&$event, $param){
          $this->init();
          $this->piratitask->boostjsAction($event, $param);
     }
     public function ajax(&$event, $param){
          global $ID;
          global $INFO;
          $ID = cleanID($_POST['id']);
          $INFO = pageinfo();
          $this->init();
          $this->piratitask->ajaxAction($event, $param);
     }
     public function edit(&$event, $param){
          $this->init();
          $this->piratitask->editAction($event,$param);
     }
}
