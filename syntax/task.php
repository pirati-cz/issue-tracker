<?php
/**
*
* Pirati: Task
* 
* @author      Vaclav Malek <vaclav.malek@pirati.cz>
*/

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'piratitask/piratitask.class.php');

/**
* All DokuWiki plugins to extend the parser/rendering mechanism
* need to inherit from this class
*/
class syntax_plugin_piratitask_task extends DokuWiki_Syntax_Plugin {

     private $started = false;

     function getType(){ return 'formatting'; }
     //function getAllowedTypes(){ return array('container','baseonly','formatting', 'substition', 'protected', 'disabled', 'paragraphs'); }
     function getAllowedTypes(){ return array(); }
     function getSort(){ return 190; }
     function accepts($mode){
          if($mode==substr(get_class($this),7)) return true;
          return parent::accepts($mode);
     }
     function connectTo($mode){ $this->Lexer->addEntryPattern('<pirati task>',$mode,'plugin_piratitask_task'); }
     function postConnect() { $this->Lexer->addExitPattern('</pirati>','plugin_piratitask_task'); }

     /**
      * Handle the match
      */
     function handle($match, $state, $pos, &$handler){
          switch ($state) {
              case DOKU_LEXER_ENTER:
                   return array($state, $match);
              case DOKU_LEXER_UNMATCHED:
               $piratitask = new Piratitask();
               $data = $piratitask->getParsedData($match);
               return array($state, $data);
              case DOKU_LEXER_EXIT:
               return array($state, $match);
          }
          return array();
     }

     /**
      * Create output
     */
     function render($mode, &$renderer, $data) {
          $renderer->info['cache'] = false;
/*          if(isset($_GET['test'])){
               global $PARSER_MODES;
               var_dump($PARSER_MODES);
          }
          if(isset($_GET['test'])) return;
 */
          if($mode == 'xhtml'){
               list($state, $match) = $data;
               switch($state){
                    case DOKU_LEXER_ENTER:
                         $this->started = true;
                         break;
                    case DOKU_LEXER_UNMATCHED:
                         if($this->started){
                              $piratitask = new Piratitask();
                              $piratitask->renderTaskSyntax($renderer);
                              $this->started = false;     
                         }
                         break;
                    case DOKU_LEXER_EXIT:
                         return true;
                         break;
               }
          }
     }
}
