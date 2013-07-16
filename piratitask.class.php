<?php

if(!defined('DOKU_INC')) die('no DOKU_INC defined!');
if(!defined('DOKU_TPL')) die('no DOKU_TPL defined!');
if(!defined('DOKU_PLUGIN')) die('no DOKU_PLUGIN defined');

include('database.class.php');
include('template.class.php');

class Piratitask {

     // system
     private $lang = array();
     private $conf = array();
     
     // parsed
     private $parsed = false;
     private $tag = null;
     private $namespace = 'unknow'; // not parsed
     private $taskid = 0;

     //
     private $db = null;
     private $template = null;
     private $helper = null;
     private $tag_helper = null;
     private $settings = null;
     private $groups = null;

     function __construct(){
          global $conf;
          
          // plugin lang
          $path = DOKU_PLUGIN.'piratitask/lang/';
          $lang = array();
          @include($path.'en/lang.php');
          if ($conf['lang'] != 'en') @include($path.$conf['lang'].'/lang.php');
          $this->lang = $lang;

          // plugin conf
          $path = DOKU_PLUGIN.'piratitask/conf/';
          $cnf = array();
          if(@file_exists($path.'default.php')){
               include($path.'default.php');
          }
          $this->conf = $conf;

          //
          //if($this->getHelper()->isAuth()) $this->settings = $this->getDb()->getSettings($this->getHelper()->getUserGaid());
     }

     ///////// ACTIONS

     public function boostmeAction(&$event, $param){
          if(
               ($this->validNamespace() and $this->getHelper()->isAction('show'))
               or
               ($this->validNamespace() and $this->getTag()=='task' and $this->getHelper()->isAction('edit'))
          ){
               // if not author disable editable
               if($this->getTag()=='task'){
                    $this->getHelper()->setInfo('editable',false);
                    if($this->isAuthor()) $this->getHelper()->setInfo('editable',true);
               }

               // prepare seed for js and css
               if($_SERVER['HTTP_HOST']!='www-beta.pirati.cz'){
                    $tseed = 0;
                    $depends = getConfigFiles('main');
                    foreach($depends as $f) {
                         $time = @filemtime($f);
                         if($time > $tseed) $tseed = $time;
                    }
                    $event->data['link'][] = array(
                        'type'	=>	'text/css',
                        'rel'	=>	'stylesheet',
                        'media'	=>	'all',
                        'href'	=>	DOKU_TPL.'styles/bootstrap.css?tseed='.$tseed
                    );
                    $event->data['link'][] = array(
                        'type'	=>	'text/css',
                        'rel'	=>	'stylesheet',
                        'media'	=>	'all',
                        'href'	=>	DOKU_TPL.'styles/bootstrap-datetimepicker.min.css?tseed='.$tseed
                    );

                    $event->data['script'][] = array(
                        'type'	=>	'text/javascript',
                        'charset'	=>	'utf-8',
                        'src'	=>	DOKU_TPL.'scripts/bootstrap.min.js?tseed='.$tseed
                    );
                    $event->data['script'][] = array(
                         'type'    =>   'text/javascript',
                         'charset' =>   'utf-8',
                         'src'     =>   DOKU_TPL.'scripts/bootstrap-datetimepicker.js?tseed='.$tseed
                    );
               }
	     }
    
     }

     public function boostjsAction(&$event, $param){
         $this->getHelper()->setJsUserInfo('gaid',$this->getHelper()->getUserInfo('id'));
         $this->gethelper()->setJsUserInfo('username',$this->getHelper()->getUserInfo('username'));
     }

     public function editAction(&$event, $param){
          if($this->validNamespace() and $this->getTag()=='task' and $this->getHelper()->isAction('edit')){
               
               $task = $this->getDb()->getTask($this->getTaskId());
               //$this->text = $task['content'];
               $this->getHelper()->setText($tesk['content']);

               $template = $this->getTemplate();
               $event->data = $template->renderEditForm($task);
         }

     }

     public function ajaxAction(&$event, $param){
          if(auth_quickaclcheck($this->getHelper()->getID()) < AUTH_READ) return false;
          if(!$this->validNamespace()) return false;
          $event->preventDefault();
          //$event->stopPropagation();
          
          switch($event->data){
               case 'piratitask_save': $this->ajaxActionSave(); break;
               case 'piratitask_update': $this->ajaxActionUpdate(); break;
               case 'piratitask_updateparam': $this->ajaxActionUpdateparam(); break;
               case 'piratitask_list': $this->ajaxActionList(); break;
               case 'piratitask_watch': $this->ajaxActionWatch(); break;
               case 'piratitask_work': $this->ajaxActionWork(); break;
               case 'piratitask_comments': $this->ajaxActionComments(); break;
               case 'piratitask_addcomm': $this->ajaxActionAddComment(); break;
               case 'piratitask_settings': $this->ajaxActionSettings(); break;
               //
               case 'piratitask_groups': $this->ajaxActionGroups(); break;
               case 'piratitask_newgroup': $this->ajaxActionAddGroup(); break;
               case 'piratitask_changegroup': $this->ajaxActionChangeGroup(); break;
          }
     }

     public function rssAction(&$event, $param){
          $userhash = $event->data['opt']['userhash'];
          $ns = $event->data['opt']['namespace'];
          
          $changes = $this->getAllChanges($userhash,$ns);
          $event->data['data'] = $changes;
     }

     ///////// ajax actions
     public function ajaxActionUpdate(){
          $title = $_POST['title'];
          $content = $_POST['content'];

          $status = 'error';
          $msg = '""';
          $errors = array();

          // validation
          if(empty($title)) $errors[] = $this->getLang('title_empty');
          if(mb_strlen($title)>255) $errors[] = $this->getLang('title_long');
          if(empty($content)) $errors[] = $this->getLang('content_empty');

          if(empty($errors)){
               if($this->isAuthor()){
                    
                    $res = $this->getDb()->updateTask($this->getTaskId(),$title,$content);
                    if($res!==false){
                         $status = 'ok';
                         saveWikiText($this->getNamespace().':'.$this->getTaskId(),con('',"<pirati task>\nnamespace ".$this->getNamespace()."\ntask ".$this->getTaskId()."\n====== ".$title." ======\n".$content."\n</pirati>",'',1),$this->getLang('modify'),false);
                         $tags_helper = $this->getTagsHelper();
                         if(!is_null($tags_helper)){
                              $tags_helper->updateTags(array(
                                   'page' => $this->getHelper()->getID()),
                              array(
                                   'category' => 1,
                                   'ekey' => $this->getTaskId()
                              ));
                         }

                         $res = $this->getDb()->addComment(
                              $this->getTaskId(),
                              $this->getHelper()->getUserGaid(),
                              $this->getHelper()->getUserFullname(),
                              date('Y-m-d H:i:s'),
                              $this->getLang('modify'),
                              2
                         );
                         // info
                         if($res!=false){
                              $this->sendNotification($this->getDb()->getLastId());
                         }

                    } else $errors[] = $this->getLang('newerror');

               } else $errors[] = $this->getLang('noedit');
          }

          echo '{"status":"'.$status.'","msg":'.$msg.',"errors":'.json_encode($errors).'}';
     }

     public function ajaxActionUpdateparam(){
          $type = $_POST['type'];
          $ids = $_POST['ids'];
          $term = $_POST['term'];

          $status = 'error'; // TODO: msg
          $msg = '""';
          if($this->getHelper()->isAuth()){
               switch($type){
                    case 'status':
                         if(preg_match('/^[0-3]$/',$ids)){
                              $task = $this->getDb()->getTask($this->getTaskId());
                              $res = $this->getDb()->updateStatus($this->getTaskId(),$ids);
                              if($res===false) $msg = '"error"'; // TODO: msg
                              else {
                                   $status = 'ok';
                                   $msg = '[';
                                   $msg .= '"'.$this->getStatus($ids).'",[';
                                   $first = true;
                                   foreach($this->getStatuses() as $sid=>$st){
                                        if($ids!=$sid){
                                             if(!$first) $msg .= ',';
                                             $msg .= '["'.$sid.'","'.$st.'"]';
                                             $first = false;
                                        }
                                   }
                                   $msg .= '] ]'; 

                                   // update comment
                                   $res = $this->getDb()->addComment(
                                        $this->getTaskId(),
                                        $this->getHelper()->getUserGaid(),
                                        $this->getHelper()->getUserFullname(),
                                        date('Y-m-d H:i:s'),
                                        sprintf($this->getLang('changestatus'),$this->getStatus($task['status']),$this->getStatus($ids)),
                                        1
                                   );
                                   if($res!=false){
                                        // TODO: send info
                                        $this->sendNotification($this->getDb()->getLastId());
                                   }
                              }
                         }
                         break;
                    case 'priority':
                         if(preg_match('/^[0-3]$/',$ids)){
                              $task = $this->getDb()->getTask($this->getTaskId());
                              $res = $this->getDb()->updatePriority($this->getTaskId(),$ids);
                              if($res===false) $msg = '"error"'; // TODO: msg
                              else {
                                   $status = 'ok';
                                   $msg = '[';
                                   $msg .= '"'.$this->getPriority($ids).'",[';
                                   $first = true;
                                   foreach($this->getPriorities() as $pid=>$pr){
                                        if($ids!=$pid){
                                             if(!$first) $msg .= ',';
                                             $msg .= '["'.$pid.'","'.$pr.'"]';
                                             $first = false;
                                        }
                                   }
                                   $msg .= '] ]';

                                   // update comment
                                   $res = $this->getDb()->addComment(
                                        $this->getTaskId(),
                                        $this->getHelper()->getUserGaid(),
                                        $this->getHelper()->getUserFullname(),
                                        date('Y-m-d H:i:s'),
                                        sprintf($this->getLang('changepriority'),$this->getPriority($task['priority']),$this->getPriority($ids)),
                                        1
                                   );
                                   if($res!=false){
                                        // TODO: send info
                                        $this->sendNotification($this->getDb()->getLastId());
                                   }
                              }
                         }
                         break;
                    case 'term':
                         if(preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})$/',$term,$matches)==1){
                              if(is_array($matches) and checkdate($matches[2],$matches[1],$matches[3])){
                                   $task = $this->getDb()->getTask($this->getTaskId());
                                   $t = mktime(0,0,0,$matches[2],$matches[1],$matches[3]);
                                   $res = $this->getDb()->updateTerm($this->getTaskId(),date('Y-m-d',$t));
                                   if($res===false) $msg = 'error';
                                   else {
                                        $status = 'ok';
                                        // update comment
                                        if(strtotime($task['term'])>0) $comm = sprintf($this->getLang('changeterm'),date('j.n.Y',strtotime($task['term'])),date('j.n.Y',$t));
                                        else $comm = sprintf($this->getLang('setterm'),date('j.n.Y',$t));
                                        $res = $this->getDb()->addComment(
                                             $this->getTaskId(),
                                             $this->getHelper()->getUserGaid(),
                                             $this->getHelper()->getUserFullname(),
                                             date('Y-m-d H:i:s'),
                                             $comm,
                                             1
                                        );
                                        if($res!=false){
                                             $this->sendNotification($this->getDb()->getLastId());
                                        }
                                   }
                              } else $msg = '"'.$this->getLang('term_format_txt').': '.$term.'"';
                         } else $msg = '"'.$this->getLang('term_format_txt').': '.$term.'"';
                         break;
                    default:
                        $msg = '"'.$this->getLang('badtype').'"'; 
               }
          } else $msg = '"'.$this->getLang('nolog').'"';
          echo '{"status":"'.$status.'","msg":'.$msg.'}';
     }

     public function ajaxActionSettings(){
          $mail = $_POST['mail'];

          if($mail=='ok') $this->getDb()->saveSettings(1,$this->getHelper()->getUserGaid());
          else $this->getDb()->saveSettings(2,$this->getHelper()->getUserGaid());
     }

     public function ajaxActionComments(){
          $comments = $this->getDb()->getComments($this->getTaskId());
          $template = $this->getTemplate();
          $template->renderTaskComments($comments);
     }

     public function ajaxActionAddComment(){
          $content = $_POST['content'];

          $status = 'error';
          $msg = '""';
          
          if($this->getHelper()->isAuth()){
               $res = $this->getDb()->addComment(
                    $this->getTaskId(),
                    $this->getHelper()->getUserGaid(),
                    $this->getHelper()->getUserFullname(),
                    date('Y-m-d H:i:s'),
                    $content,
                    0
               );
               if($res!=false){
                    $status = 'ok';
                    // send info to watchers and worker
                    $this->sendNotification($this->getDb()->getLastId());
               }
          } else $msg = $this->getLang('nolog');
          echo '{"status":"'.$status.'","msg":'.$msg.'}';
     }

     public function ajaxActionWatch(){
          $taskid = $_POST['taskid'];

          $status = 'error';
          $fullname = '';
          if(preg_match('/^([0-9])+$/',$taskid)){
               if($this->isWatch($taskid)){
                    $res = $this->getDb()->stopWatch($taskid);
                    if($res){
                         $status = 'off';
                         $fullname = $this->getHelper()->getUserFullname();
                    }
               } else {
                    $res = $this->getDb()->startWatch($taskid);
                    if($res){
                         $status = 'on';
                         $fullname = $this->getHelper()->getUserFullname();
                    }
               }
          }
          echo '{"status":"'.$status.'","fullname":"'.$fullname.'"}';
     }

     public function ajaxActionWork(){
          $taskid = $_POST['taskid'];

          $status = 'error';
          $fullname = '';
          if(preg_match('/^([0-9])+$/',$taskid)){
               $task = $this->getDb()->getTask($taskid);
               if($task['worker_gaid']==$this->getHelper()->getUserGaid()){
                    $res = $this->getDb()->stopWork($taskid);
                    if($res) $status='off';
               } else {
                    $res = $this->getDb()->startWork($taskid);
                    if($res){
                         $status = 'on';
                         $fullname = $this->getHelper()->getUserFullname();
                    }
               }
          }
          echo '{"status":"'.$status.'","fullname":"'.$fullname.'"}';
     }

     public function ajaxActionList(){ 
          $filter = $_POST['filter'];
          $page = $_POST['page'];
          $sort = $_POST['sort'];
          $tasks = $this->getDb()->getTasks($filter,$page,$sort);
          if(!empty($tasks)){
               $template = $this->getTemplate();
               $template->renderTasksList($tasks);
          }
     }

     public function ajaxActionSave(){
          $template = $this->getTemplate();
          if(!$this->getHelper()->isAuth()){ $template->renderErrorList($this->getLang('mustlog')); return false; }
          if(!checkSecurityToken()){ echo 'sectok error'; return false; }
          //
          $title = $_POST['title'];
          $priority = $_POST['priority'];
          $watchers = $_POST['watches'];
          $sponsor = $_POST['sponsor'];
          $term = $_POST['term'];
          $content = $_POST['content'];
          //
          $piratihelper = $this->getHelper();
          $all = array();
          $allgroups = array();
          $allusers = array();
          foreach($piratihelper->getGraphGroups() as $g) $allgroups[] = $g->username;
          foreach($piratihelper->getGraphUsers() as $u) $allusers[] = $u->username;
          foreach($this->getGroups() as $v) $allvolunteers_grp[] = $v['title'];
          $all = array_merge($allgroups,$allusers,$allvolunteers_grp);

          // validation
          $errors = array();
          if(empty($title)) $errors[] = $this->getLang('title_empty');
          if(mb_strlen($title)>255) $errors[] = $this->getLang('title_long');
          if($priority<0 or $priority>3) $errors[] = $this->getLang('priority_bad');
          if(!empty($watchers)) foreach($watchers as $w){
               if(!in_array($w,$all)) $errors[] = sprintf($this->getLang('usergroupno'),$w);
          }
          $matches = '';
          if(!empty($term) and false===preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})$/',$term,$matches)) $errors[] = $this->getLang('term_format');
          if(is_array($matches) and !checkdate($matches[2],$matches[1],$matches[3])) $errors[] = $this->getLang('term_format');
          if(empty($content)) $errors[] = $this->getLang('content_empty');
          if(!empty($sponsor) and !in_array($sponsor,$all)) $errors[] = sprintf($this->getLang('usergroupno'),$sponsor);

          // all ok save
          if(empty($errors)){
               $user_sponsor = $this->getHelper()->getGraphUserByUsername($sponsor);
               $res = $this->getDb()->saveTask(
                    date('Y-m-d H:i:s'),
                    $this->getHelper()->getUserGaid(),
                    $this->getHelper()->getUserFullname(),
                    $title,
                    (empty($user_sponsor)?'':$user_sponsor->id),
                    (empty($user_sponsor)?'':$this->getHelper()->getUserFullname($user_sponsor)),
                    '',
                    '',
                    $priority,
                    (empty($term)?'':date('Y-m-d',mktime(0,0,0,$matches[2],$matches[1],$matches[3]))),
                    $content,
                    0
               );
               if($res===false) $template->renderErrorList($this->getLang('newerror'));
               else {
                    $lastid = $this->getDb()->getLastId();

                    saveWikiText($this->getNamespace().':'.$lastid,con('',"<pirati task>\ntask ".$lastid."\n====== ".$title." ======\n".$content."\n</pirati>",'',1),$this->getLang('created'),false);
                    $tags_helper = $this->getTagsHelper();
                    if(!is_null($tags_helper)){
                         $tags_helper->updateTags(array(
                              'page' => $this->getHelper()->getID()),
                         array(
                              'category' => 1,
                              'ekey' => $this->getTaskId()
                         ));
                    }

                    // watchers
                    $this->getDb()->addWatchers($watchers,$allgroups,$allusers,$allvolunteers_grp);

                    $res = $this->getDb()->addComment(
                         $lastid,
                         $this->getHelper()->getUserGaid(),
                         $this->getHelper()->getUserFullname(),
                         date('Y-m-d H:i:s'),
                         $this->getLang('created'),
                         2
                    );

                    // inform new task
                    if($res!=false) $this->sendNotification($lastid);
               }
          }
          $template->renderErrorList($errors);
     }

     public function ajaxActionGroups(){
          $template = $this->getTemplate();
          $template->renderGroupsList();
     }

     public function ajaxActionAddGroup(){
          if(!$this->getHelper()->isAuth()) return false;

          if($this->isGroupsAdmin()){
               $title = $_POST['title'];
               $this->getDb()->addGroup($title);
          }
     }

     public function ajaxActionChangeGroup(){
          $status = 'error';
          $msg = '""';
          if($this->getHelper()->isAuth()){
               $groups = $_POST['groups'];
               foreach($groups as $g){
                    if(!preg_match('/^[0-9]+$/',$g)) return false;
               }
               $ret = $this->getDb()->changeGroups($groups);
               if($ret) $status='ok';
          }

          echo '{"status":"'.$status.'","msg":'.$msg.'}';
     }
 
     //////// SYNTAX
     public function renderTasksSyntax($renderer){
          $template = $this->getTemplate($renderer);
          $template->renderTasksSyntax();
     }

     public function renderTaskSyntax($renderer){
          $template = $this->getTemplate($renderer);
          $task = $this->getDb()->getTask($this->getTaskId());
          $watchers = $this->getDb()->getWatchers($this->getTaskId());
          $template->renderTaskSyntax($task,$watchers);
     }

     //////// HELPERS
 
     // other classes
     public function getDb(){
          if(!is_null($this->db)) return $this->db;
          $this->db = new PiratitaskDatabase($this);
          return $this->db;
     }
     public function getTemplate($renderer = null){
          if(!is_null($this->template)) return $this->template;
          $this->template = new PiratitaskTemplate($this);
          $this->template->setRenderer($renderer);
          return $this->template;
     }
     public function getHelper(){
          if(!is_null($this->helper)) return $this->helper;
          $this->helper = plugin_load('helper','piratihelper');
          return $this->helper;
     }
     public function getTagsHelper(){
          if(!is_null($this->tag_helper)) return $this->tag_helper;
          $this->tag_helper = plugin_load('helper','piratitag');
          return $this->tag_helper;
     }

     public function getSettings($what){
          if(!is_null($this->settings)) return $this->settings[$what];
          if($this->getHelper()->isAuth()){
               $this->settings = $this->getDb()->getSettings($this->getHelper()->getUserGaid());
               if($this->settings!==false) return $this->settings[$what];
          }
          return $this->settings;
     }

     public function isGroupsAdmin(){
          $gaid = $this->getHelper()->getUserGaid();
          $groups = $this->getConf('groupsadmins');
          if(
               $this->getHelper()->getInfo('isadmin') or 
               $this->getHelper()->isInGroup($gaid,$groups)
          ) return true;
          return false;
     }

     //public function isInGroup($groupid){
          //return $this->getDb()->isInGroup($groupid);
     //}

     // globals
     public function getLang($string){
          return $this->lang[$string];
     }
     public function getConf($string){
          return $this->conf[$string];
     }

     // parsed
     public function getNamespace(){
          //$this->parseContent();
          //return $this->namespace;
          return $this->getConf('namespace');
     }
     public function getTaskId(){
          $this->parseContent();
          return $this->taskid;
     }
     public function getTag(){
          $this->parseContent();
          return $this->tag;
     }

     // others
     public function setNamespace($ns){
          $this->namespace = $ns;
     }
     public function isAuthor(){
          $task = $this->getDb()->getTask($this->getTaskId());
          return ($task['author_gaid']==$this->getHelper()->getUserGaid()); // $this->info['editable'] = true;
     }
     
     //
     public function isWatch($taskid){
          return $this->getDb()->isWatch($taskid);
     }
     public function isWork($worker_gaid){
          return ($worker_gaid==$this->getHelper()->getUserGaid());
     }
     public function validNamespace(){
          //$this->parseContent();
          $n = ($this->getNamespace()==$this->getHelper()->getInfo('namespace'));
          if($n===false){
               return ($this->getNamespace()==$this->getHelper()->getID());
               //return false;
          } else return true;
          return false;
     }

     public function parseContent($data = null){
          if($this->parsed) return;

          if(!is_null($data) or $this->getHelper()->getInfo('exists')){
               if(is_null($data)){
                    $handle = fopen($this->getHelper()->getInfo('filepath'),'r');
                    $data = fread($handle,filesize($this->getHelper()->getInfo('filepath')));
               }
               foreach(explode("\n",$data) as $line){
                    list($name,$value) = explode(' ',$line);
                    if($name == '<pirati' and $value=='task>') $this->tag = 'task';
                    if($name == '<pirati' and $value=='tasks>') $this->tag = 'tasks';
                    //if($name == 'namespace') $this->namespace = $value;
                    if($name == 'task') $this->taskid = $value;
              }
          }

          $this->parsed = true;
     }

     public function getParsedData($data){
          $this->parseContent($data);
          return array(
               //'namespace' => $this->namespace,
               'task' => $this->taskid
          );
     }

     public function getPriorities(){
          return array(
               0 => $this->lang['low'],
               1 => $this->lang['middle'],
               2 => $this->lang['high'],
               3 => $this->lang['critical']
          );
     }

     public function getStatuses(){
          return array(
               0 => $this->lang['open'],
               1 => $this->lang['close'],
               2 => $this->lang['duplicate'],
               3 => $this->lang['invalid']
          );
     }

     public function getStatus($id){
          $s = $this->getStatuses();
          return $s[$id];
     }

     public function getPriority($id){
          $p = $this->getPriorities();
          return $p[$id];
     }

     public function getFilterColumns(){
          return array(
               'id' => '#ID',
               'status' => $this->lang['status'],
               'title' => $this->lang['title'],
               'priority' => $this->lang['priority'],
               //'worker_gaid' => $this->lang['assign'],
               'worker' => $this->lang['assign'],
               'term' => $this->lang['term']
          );
     }

     public function getAllChanges($userhash,$ns){
          $changes = array();

          $gaid = $this->getDb()->getUserGaidBySettingsHash($userhash);
          $tasks = $this->getDb()->getWatchTasks($gaid);
          foreach($tasks as $taskid){
               $task = $this->getDb()->getTask($taskid['id']);
               // comments
               $comments = $this->getDb()->getComments($task['id']);
               foreach($comments as $com){
                    if($com['author_gaid']!=$gaid){
                         if(isset($changes[strtotime($com['pdate'])])) $index = strtotime($com['pdate'])+(0.1);
                         else $index = strtotime($com['pdate']);

                         $changes[$index] = array(
                              'title' => '#'.$task['id'].' - '.substr($task['title'],0,100),
                              'description' => $com['content'],
                              'category' => 'comment',
                              'link' => $this->getTaskUrl($task['id']).'#'.$com['id'],
                              'guid' => $this->getTaskUrl($task['id']).'#'.$com['id'],
                              //'author' => $com['author'],
                              'date' => strtotime($com['pdate'])
                         );
                    }
               }
          }

          krsort($changes);
          return $changes;
     }

     public function sendNotification($id_comment){

          $mails = array();
          $watchers = $this->getDb()->getWatchers($this->getTaskId());
          foreach($watchers as $watch){
               if($watch['watcher_gaid']!=$this->getHelper()->getUserGaid()){
                    $settings = $this->getDb()->getSettings($watch['watcher_gaid']);
                    if($settings['mail']==1) $mails[] = $settings['email'];
               }
          }
          
          $comment = $this->getDb()->getComment($id_comment);

          $from = 'ukolovnik@pirati.cz';
          //$to = 'ukolovnik@pirati.cz';
          $to = null;
          $cc = '';
          $bcc = implode(',',$mails);
          $subject = 'pirati.cz: '.$this->getNamespace().' - #'.$comment['taskid'];
          $body = sprintf($this->getLang('issueupdate'),$comment['taskid'],$this->getTaskUrl($comment['taskid']))."\n\n\t".$comment['content']."\n\n -- ".date('j.n.Y G:i',strtotime($comment['pdate']))." - ".$comment['author'];

          if($comment['taskid']!=60) mail_send($to,$subject,$body,$from,$cc,$bcc);
     }

     public function getGroups(){
          if(!is_null($this->groups)) return $this->groups;
          $this->groups = $this->getDb()->getGroups();
          return $this->groups;
     }

     public function getTaskUrl($taskid){
          //return rtrim(DOKU_URL,'/')
          return wl($this->getNamespace().':'.$taskid);
     }
}
