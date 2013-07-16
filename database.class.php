<?php

class PiratitaskDatabase {
     
     private $piratitask = null;
     private $db = null;
     private $task = null;
     private $watchers = array();
     private $taskid = 0;
     private $taskscnt = 0;
     private $filter_sql = '';
     private $filter = array();
     private $sort_sql = '';
     private $iswatch = null;
     private $settings = array();
     private $usrs_grp = array();

     private $sort = '';
     private $page = 0;
     private $pages = 0;
     private $perpage = 0;

     public function __construct($piratitask){
          $this->piratitask = $piratitask;
     }

     public function getDbPath(){
          @mkdir(DOKU_INC.'data/piratitask',0710,true); 
          return DOKU_INC.'data/piratitask/'.str_replace(array(':','/'),'_',$this->piratitask->getNamespace()).'_'.$this->piratitask->getConf('dbtasks');
     }

     public function getDb(){
          if(!is_null($this->db)) return $this->db;
          $this->db = new SQLite3($this->getDbPath());
          $this->db->busyTimeout(5000);
          $this->createTables();
          return $this->db;
     }

     public function closeDb(){
          //$this->db->close();
          //$this->db = null;
     }

     public function getComments($taskid){
          $stmt = $this->getDb()->prepare('SELECT * FROM comment WHERE taskid=:taskid ORDER BY pdate DESC');
          $stmt->bindValue(':taskid',$this->getDb()->escapeString($taskid),SQLITE3_INTEGER);
          $res = $stmt->execute();
          $rows = array();
          while($row = $res->fetchArray()){
               $rows[] = $row;
          }
          $this->closeDb();
          return $rows;
     }

     public function getComment($comment_id){
          $stmt = $this->getDb()->prepare('SELECT * FROM comment WHERE id = :comment_id');
          $stmt->bindValue(':comment_id',$this->getDb()->escapeString($comment_id),SQLITE3_INTEGER);
          $res = $stmt->execute();
          $ret = $res->fetchArray();
          $this->closeDb();
          return $ret;
     }

     public function addComment($taskid,$gaid,$fullname,$pdate,$content,$type){
          $stmt = $this->getDb()->prepare('INSERT INTO comment (taskid,author_gaid,author,pdate,content,type) VALUES (:taskid,:author_gaid,:author,:pdate,:content,:type);');
          $stmt->bindValue(':taskid',$this->getDb()->escapeString($taskid),SQLITE3_INTEGER);
          $stmt->bindValue(':author_gaid',$this->getDb()->escapeString($gaid),SQLITE3_TEXT);
          $stmt->bindValue(':author',$this->getDb()->escapeString($fullname),SQLITE3_TEXT);
          $stmt->bindValue(':pdate',$this->getDb()->escapeString($pdate),SQLITE3_TEXT);
          $stmt->bindValue(':content',$this->getDb()->escapeString($content),SQLITE3_TEXT);
          $stmt->bindValue(':type',$this->getDb()->escapeString($type),SQLITE3_INTEGER);
          $ret =  $stmt->execute();
          $this->closeDb();
          return $ret;
     }

     public function getSettings($gaid){
          if(!is_null($this->settings[$gaid])) return $this->settings[$gaid];
          $stmt = $this->getDb()->prepare('SELECT * FROM settings WHERE author_gaid=:author_gaid');
          $stmt->bindValue(':author_gaid',$this->getDb()->escapeString($gaid),SQLITE3_TEXT);
          $this->settings[$gaid] = $stmt->execute()->fetchArray();
          if($this->settings[$gaid]===false or empty($this->settings[$gaid]['email'])){
               $stmt = $this->getDb()->prepare('SELECT COUNT(*) AS cnt FROM settings WHERE rss=:rss');
               do {
                    $rsshash = sha1(rand(0,10000).microtime().time().'pirati');
                    $stmt->bindValue(':rss',$rsshash,SQLITE3_TEXT);
                    $res = $stmt->execute()->fetchArray();
                    if($res['cnt']==0){
                         //
                         // get user email
                         $helper = $this->piratitask->getHelper();
                         $user = $helper->getGraphUser($gaid);
                         if(empty($user)) $email = '';
                         else $email = $user->email;
                         //
                         $stmt2 = $this->getDb()->prepare('INSERT INTO settings (author_gaid,email,mail,rss) VALUES (:author_gaid,:email,:mail,:rss)');
                         $stmt2->bindValue(':author_gaid',$this->getDb()->escapeString($gaid),SQLITE3_TEXT);
                         $stmt2->bindValue(':email',$this->getDb()->escapeString($email),SQLITE3_TEXT);
                         $stmt2->bindValue(':mail',0,SQLITE3_INTEGER);
                         $stmt2->bindValue(':rss',$this->getDb()->escapeString($rsshash),SQLITE3_TEXT);
                         $stmt2->execute();
                    }
               } while($res['cnt']>0);
          }
          $this->closeDb();
          return $this->settings[$gaid];
     }

     public function saveSettings($value,$author_gaid){
          $stmt = $this->getDb()->prepare('UPDATE settings SET mail = :mail WHERE author_gaid = :author_gaid');
          $stmt->bindValue(':mail',$this->getDb()->escapeString($value),SQLITE3_INTEGER);
          $stmt->bindValue(':author_gaid',$this->getDb()->escapeString($author_gaid),SQLITE3_TEXT);
          $ret = $stmt->execute();
          $this->closeDb();
          return $ret;
     }

     public function getWatchers($taskid){
          if(!empty($this->watchers)) return $this->watchers;

          $stmt = $this->getDb()->prepare('SELECT * FROM watch WHERE id=:taskid');
          $stmt->bindValue(':taskid',$this->getDb()->escapeString($taskid),SQLITE3_INTEGER);
          $res = $stmt->execute();
          $this->watchers = array();
          while($row = $res->fetchArray()){
               $this->watchers[] = $row;
          }
          $this->closeDb();
          return $this->watchers;
     }

     public function getTask($taskid){
          if($this->taskid==$taskid) return $this->task;
          $this->task = $this->getDb()->query('SELECT * FROM task WHERE id='.$taskid)->fetchArray();
          $this->closeDb();
          return $this->task;
     }

     public function getTasks($filter,$page,$sort=null,$perpage=20){
          
          // filter
          $this->parseFilter($filter);

          // sort
          $this->parseSort($sort);

          // pager
          $this->page = $page;
          $this->perpage = $perpage;
          $cnt = $this->getTasksCount($filter);
          $this->pages = intval(ceil($cnt/$this->perpage));
          if($this->page<0) $this->page=0;
          if($this->page>=$this->pages) $this->page = $this->pages-1;
          $offset = $this->page*$this->perpage;

          $q = $this->getDb()->query('SELECT t.*,(SELECT CASE WHEN t.term="" THEN "x" ELSE t.term END) AS termin FROM task t'.(!empty($this->filter_sql)?' '.$this->filter_sql:'').' ORDER BY '.$this->sort_sql.' LIMIT '.$this->perpage.' OFFSET '.$offset);

          $rows = array();
          while($row = $q->fetchArray()){
               $rows[] = $row;
          }
          $this->closeDb();
          return $rows;
     }

     public function getPage(){
          return $this->page;
     }

     public function getPages(){
          return $this->pages;
     }

     public function getSort(){
          return $this->sort;
     }

     public function getLastId(){
          $lastid = $this->getDb()->lastInsertRowID();
          //$this->closeDb();
          return $lastid;
     }

     public function getTasksCount($filter){
          $this->parseFilter($filter);
          $cnt = $this->getDb()->querySingle('SELECT COUNT(*) FROM task t'.(!empty($this->filter_sql)?' '.$this->filter_sql:''));
          $this->closeDb();
          return $cnt;
     }

     public function getUserGaidBySettingsHash($userhash){
          $stmt = $this->getDb()->prepare('SELECT author_gaid FROM settings WHERE rss=:userhash');
          if($stmt!==false){
               $stmt->bindValue(':userhash',$this->getDb()->escapeString($userhash),SQLITE3_TEXT);
               $res = $stmt->execute();
               if($res!==false){
                    $arr = $res->fetchArray();
                    $this->closeDb();
                    return $arr['author_gaid'];
               }
          }
          $this->closeDb();
          return false;
     }

     public function getWatchTasks($gaid){
          $stmt = $this->getDb()->prepare('SELECT id FROM watch WHERE watcher_gaid=:gaid');
          if($stmt!==false){
               $stmt->bindValue(':gaid',$this->getDb()->escapeString($gaid),SQLITE3_TEXT);
               $res = $stmt->execute();
               if($res!==false){
                    $tasks = array();
                    while($row = $res->fetchArray()){
                         $tasks[] = $row;
                    }
                    $this->closeDb();
                    return $tasks;
               }
          }
          $this->closeDb();
          return false;
     }

     public function isWatch($taskid){
          //if(!is_null($this->iswatch)) return $this->iswatch;
          $stmt = $this->getDb()->prepare('SELECT COUNT(*) FROM watch WHERE id=:taskid AND watcher_gaid=:watcher_gaid');

          if($stmt!==false){
               $stmt->bindValue(':taskid',$this->getDb()->escapeString($taskid),SQLITE3_INTEGER);
               $stmt->bindValue(':watcher_gaid',$this->getDb()->escapeString($this->piratitask->getHelper()->getUserGaid()),SQLITE3_TEXT);
               $res = $stmt->execute();
               if($res!==false){
                    $cnt = $res->fetchArray();
                    if($cnt[0]>0) $this->iswatch = true; else $this->iswatch = false;
                    $this->closeDb();
                    return $this->iswatch;
               }
          }
          $this->closeDb();
          return 0;
     }

     public function createTables(){
          // tables?
          // task
          $table = $this->db->querySingle('SELECT name FROM sqlite_master WHERE name="task"');
          if(is_null($table)) $this->db->exec('CREATE TABLE task (id INTEGER PRIMARY KEY AUTOINCREMENT, created DATETIME NOT NULL, author_gaid VARCHAR(50) NOT NULL, author VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL, sponsor_gaid VARCHAR(50) NOT NULL, sponsor VARCHAR(100) NOT NULL, worker_gaid VARCHAR(50) NOT NULL, worker VARCHAR(100) NOT NULL, priority INTEGER NOT NULL, term DATE NOT NULL, content TEXT NOT NULL, status INTEGER NOT NULL)');

          // watch
          $table = $this->db->querySingle('SELECT name FROM sqlite_master WHERE name="watch"');
          if(is_null($table)) $this->db->exec('CREATE TABLE watch (id INTEGER NOT NULL, watcher_gaid VARCHAR(50) NOT NULL, watcher VARCHAR(100) NOT NULL)');

          // settings
          // 
          // mail - 0 = default
          //        1 = on
          //        2 = off
          //
          $table = $this->db->querySingle('SELECT name FROM sqlite_master WHERE name="settings"');
          if(is_null($table)) $this->db->exec('CREATE TABLE settings (author_gaid VARCHAR(50) NOT NULL, email VARCHAR(100) NOT NULL, mail INTEGER NOT NULL, rss VARCHAR(100) NOT NULL)');

          // comments
          //
          // type - 0 = comment
          //        1 = change status, priority, term
          //        2 = update task
          //
          $table = $this->db->querySingle('SELECT name FROM sqlite_master WHERE name="comment"');
          if(is_null($table)) $this->db->exec('CREATE TABLE comment (id INTEGER PRIMARY KEY AUTOINCREMENT, taskid INTEGER NOT NULL, author_gaid VARCHAR(50) NOT NULL, author VARCHAR(100) NOT NULL, pdate DATETIME NOT NULL, content TEXT NOT NULL, type INTEGER NOT NULL)');

          // categories
          //
          //
          $table = $this->db->querySingle('SELECT name FROM sqlite_master WHERE name="groups"');
          if(is_null($table)) $this->db->exec('CREATE TABLE groups (id INTEGER PRIMARY KEY AUTOINCREMENT, parent INTEGER NOT NULL, title VARCHAR(50) NOT NULL)');

          // groupie
          $table = $this->db->querySingle('SELECT name FROM sqlite_master WHERE name="group_user"');
          if(is_null($table)) $this->db->exec('CREATE TABLE group_user (group_id INTEGER NOT NULL, user_gaid VARCHAR(50) NOT NULL)');
     }

     public function saveTask($created,$author_gaid,$author,$title,$sponsor_gaid,$sponsor,$worker_gaid,$worker,$priority,$term,$content,$status){
          $stmt = $this->getDb()->prepare('INSERT INTO task (created,author_gaid,author,title,sponsor_gaid,sponsor,worker_gaid,worker,priority,term,content,status) VALUES (:created,:author_gaid,:author,:title,:sponsor_gaid,:sponsor,:worker_gaid,:worker,:priority,:term,:content,:status);');
          $stmt->bindValue(':created',$this->getDb()->escapeString($created),SQLITE3_TEXT);
          $stmt->bindValue(':author_gaid',$this->getDb()->escapeString($author_gaid),SQLITE3_TEXT);
          $stmt->bindValue(':author',$this->getDb()->escapeString($author),SQLITE3_TEXT);
          $stmt->bindValue(':title',$this->getDb()->escapeString($title),SQLITE3_TEXT);
          $stmt->bindValue(':sponsor_gaid',$this->getDb()->escapeString($sponsor_gaid),SQLITE3_TEXT);
          $stmt->bindValue(':sponsor',$this->getDb()->escapeString($sponsor),SQLITE3_TEXT);
          $stmt->bindValue(':worker_gaid',$this->getDb()->escapeString($worker_gaid),SQLITE3_TEXT);
          $stmt->bindValue(':worker',$this->getDb()->escapeString($worker),SQLITE3_TEXT);
          $stmt->bindValue(':priority',$this->getDb()->escapeString($priority),SQLITE3_INTEGER);
          $stmt->bindValue(':term',$this->getDb()->escapeString($term),SQLITE3_TEXT);
          $stmt->bindValue(':content',$this->getDb()->escapeString($content),SQLITE3_TEXT);
          $stmt->bindValue(':status',$this->getDb()->escapeString($status),SQLITE3_INTEGER);
          $ret = $stmt->execute();
          $this->closeDb();
          return $ret;
     }

     public function addWatchers($watchers,$allgroups,$allusers,$allvolunteers_grp){
          $lastid = $this->getLastId();
          $stmt = $this->getDb()->prepare('INSERT INTO watch (id,watcher_gaid,watcher) VALUES (:id,:watcher_gaid,:watcher)');
          if(is_array($watchers)){
               foreach($watchers as $w){
                    if(in_array($w,$allgroups)){
                         // ban groups?
                         $users = $this->piratitask->getHelper()->getGraphUsers($w);
                         foreach($users as $u){
                              if(!empty($u)){
                                   $stmt->bindValue(':id',$lastid,SQLITE3_INTEGER);
                                   $stmt->bindValue(':watcher_gaid',$this->getDb()->escapeString($u->id),SQLITE3_TEXT);
                                   $stmt->bindValue(':watcher',$this->getDb()->escapeString($this->piratitask->getFullname($u)),SQLITE3_TEXT);
                                   $stmt->execute();
                              }
                         }
                    } else if(in_array($w,$allusers)){
                         $user = $this->piratitask->getHelper()->getGraphUserByUsername($w);
                         if(!empty($user)){
                              $stmt->bindValue(':id',$lastid,SQLITE3_INTEGER);
                              $stmt->bindValue(':watcher_gaid',$this->getDb()->escapeString($user->id),SQLITE3_TEXT);
                              $stmt->bindValue(':watcher',$this->getDb()->escapeString($this->piratitask->getFullname($user)),SQLITE3_TEXT);
                              $stmt->execute();
                         }     
                    } else {
                         if(in_array($w,$allvolunteers_grp)){
                              $group = $this->getGroupByTitle($w);
                              $users = $this->getUsersByGroup($group['id']);
                              foreach($users as $u){
                                   if(!empty($u)){
                                        $stmt->bindValue(':id',$lastid,SQLITE3_INTEGER);
                                        $stmt->bindValue(':watcher_gaid',$this->getDb()->escapeString($u->id),SQLITE3_TEXT);
                                        $stmt->bindValue(':watcher',$this->getDb()->escapeString($this->piratitask->getFullname($u)),SQLITE3_TEXT);
                                        $stmt->execute();
                                   }
                              }
                         }
                    }
               }
          }
     }

     public function startWatch($taskid){
          $stmt = $this->getDb()->prepare('INSERT INTO watch (id,watcher_gaid,watcher) VALUES (:id,:watcher_gaid,:watcher)');
          if($stmt!==false){
               $stmt->bindValue(':id',$taskid,SQLITE3_INTEGER);
               $stmt->bindValue(':watcher_gaid',$this->getDb()->escapeString($this->piratitask->getHelper()->getUserGaid()),SQLITE3_TEXT);
               $stmt->bindValue(':watcher',$this->getDb()->escapeString($this->piratitask->getFullname()),SQLITE3_TEXT);
               $ret = $stmt->execute();
               $this->closeDb();
               return $ret;
          }
          $this->closeDb();
          return false;
     }

     public function stopWatch($taskid){
          $ret = $this->getDb()->exec('DELETE FROM watch WHERE id='.$taskid.' AND watcher_gaid="'.$this->piratitask->getHelper()->getUserGaid().'"');
          $this->closeDb();
          return $ret;
     }

     public function startWork($taskid){
          $stmt = $this->getDb()->prepare('UPDATE task SET worker_gaid=:worker_gaid,worker=:worker WHERE id=:taskid');
          $stmt->bindValue(':taskid',$taskid,SQLITE3_INTEGER);
          $stmt->bindValue(':worker_gaid',$this->piratitask->getHelper()->getUserGaid(),SQLITE3_TEXT);
          $stmt->bindValue(':worker',$this->getDb()->escapeString($this->piratitask->getFullname()),SQLITE3_TEXT);
          $ret = $stmt->execute();
          $this->closeDb();
          return $ret;
     }

     public function stopWork($taskid){
          $stmt = $this->getDb()->prepare('UPDATE task SET worker_gaid=:worker_gaid,worker=:worker WHERE id=:taskid');
          $stmt->bindValue(':taskid',$this->getDb()->escapeString($taskid),SQLITE3_INTEGER);
          $stmt->bindValue(':worker_gaid','',SQLITE3_TEXT);
          $stmt->bindValue(':worker','',SQLITE3_TEXT);
          $ret = $stmt->execute();
          $this->closeDb();
          return $ret;
     }

     public function updateStatus($taskid,$newstatus){
          $stmt = $this->getDb()->prepare('UPDATE task SET status=:status WHERE id=:taskid');
          $stmt->bindValue(':taskid',$this->getDb()->escapeString($taskid),SQLITE3_INTEGER);
          $stmt->bindValue(':status',$this->getDb()->escapeString($newstatus),SQLITE3_INTEGER);
          $ret = $stmt->execute();
          $this->closeDb();
          return $ret;
     }

     public function updatePriority($taskid,$newpriority){
          $stmt = $this->getDb()->prepare('UPDATE task SET priority=:priority WHERE id=:taskid');
          $stmt->bindValue(':taskid',$this->getDb()->escapeString($taskid),SQLITE3_INTEGER);
          $stmt->bindValue(':priority',$this->getDb()->escapeString($newpriority),SQLITE3_INTEGER);
          $ret = $stmt->execute();
          $this->closeDb();
          return $ret;
     }

     public function updateTerm($taskid,$newterm){
          $stmt = $this->getDb()->prepare('UPDATE task SET term=:term WHERE id=:taskid');
          $stmt->bindValue(':taskid',$this->getDb()->escapeString($taskid),SQLITE3_INTEGER);
          $stmt->bindValue(':term',$this->getDb()->escapeString($newterm),SQLITE3_TEXT);
          $ret = $stmt->execute();
          $this->closeDb();
          return $ret;
     }

     public function updateTask($taskid,$title,$content){
          $stmt = $this->getDb()->prepare('UPDATE task SET title=:title,content=:content WHERE id=:id');
          $stmt->bindValue(':id',$this->getDb()->escapeString($taskid),SQLITE3_INTEGER);
          $stmt->bindValue(':title',$this->getDb()->escapeString($title),SQLITE3_TEXT);
          $stmt->bindValue(':content',$this->getDb()->escapeString($content),SQLITE3_TEXT);
          $ret = $stmt->execute();
          $this->closeDb();
          return $ret;
     }

     public function parseFilter($filter){
          if(!empty($this->filter_sql)) return true;
          $f = explode(',',$filter);
          $t = 'col';
          $fcol = ''; $fcond  = ''; $fval = ''; $fclu = '';
          for($i=0;$i<sizeof($f);$i++){
               if($t=='col'){ $fcol=$f[$i]; $t='cond'; }
               else if($t=='cond'){ $fcond=$f[$i]; $t='val'; }
               else if($t=='val'){ $fval=$f[$i]; $t='clu'; }

               if($t=='clu'){
                    if($f[$i+1]=='or' or $f[$i+1]=='and') $fclu=$f[$i+1];
                    $t='col'; $i++;

                    switch($fcol){
                         case 'id':
                              if(preg_match('/^[0-9]+$/',$fval)){
                                   switch($fcond){
                                        case '<': $c='<'; break;
                                        case '>': $c='>'; break;
                                        default: $c='=';
                                   }
                                   if(!empty($this->filter)) $this->filter[] = 'AND';
                                   $this->filter[] = 't.id '.$c.''.$fval;
                              }
                              break;
                         case 'status':
                              if(preg_match('/^[0-3]$/',$fval)){
                                   if(!empty($this->filter)) $this->filter[] = 'AND';
                                   $this->filter[] = 't.status = '.$fval;
                              }
                              break;
                         case 'priority':
                              if(preg_match('/^[0-3]$/',$fval)){
                                   if(!empty($this->filter)) $this->filter[] = 'AND';
                                   $this->filter[] = 't.priority = '.$fval;
                              }
                              break;
                         case 'title':
                              if(!empty($fval)){
                                   if(!empty($this->filter)) $this->filter[] = 'AND';
                                   $this->filter[] = 't.title LIKE "%'.$this->getDb()->escapeString($fval).'%"';
                              }
                              break;
                         case 'worker':
                              if($fval=='-') $id='';
                              else {
                                   $helper = $this->piratitask->getHelper();
                                   $user = $helper->getGraphUserByUsername($fval);
                                   if(empty($user)) $id='0';
                                   else $id=$user->id;
                              }
                              if(!empty($this->filter)) $this->filter[] = 'AND';
                              $this->filter[] = 't.worker_gaid = "'.$id.'"';
                              break;
                         case 'watcher':
                              if($fval=='-') $id='';
                              else {
                                   $helper = $this->piratitask->getHelper();
                                   $user = $helper->getGraphUserByUsername($fval);
                                   if(empty($user)) $id='0';
                                   else $id=$user->id;
                              }
                              //if(!empty($this->filter)) $this->filter[] = 'OR';
                              $this->filter_sql = 'INNER JOIN watch w ON w.watcher_gaid = "'.$id.'" AND w.id = t.id';
                              break;
                         case 'term':
                              if(!empty($fval)){
                                   $d = preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})$/',$fval,$matches);
                                   $val = false;
                                   if($d!==false and checkdate($matches[2],$matches[1],$matches[3])) $val = date('Y-m-d',mktime(0,0,0,$matches[2],$matches[1],$matches[3]));
                                   if($val!=false){
                                        switch($fcond){
                                             case '<': $c='<'; break;
                                             case '>': $c='>'; break;
                                             default: $c='=';
                                        }
                                        if(!empty($this->filter)) $this->filter[] = 'AND';
                                        $this->filter[] = 't.term '.$c.' "'.$val.'"';
                                   }
                              }
                              break;
                    }
                    $fcol = ''; $fcond = ''; $fval = ''; $fclu = '';
               }
          }

          if(!empty($this->filter)) $this->filter_sql .= ' WHERE ';
          foreach($this->filter as $i=>$f){
               $this->filter_sql .= ' '.$f;
          }
          //var_dump($this->filter_sql);
     }

     public function parseSort($sort){
          switch($sort){
               case 'id': $this->sort = 'id'; $this->sort_sql = 't.id'; break;
               case 'id DESC': $this->sort = 'id DESC'; $this->sort_sql = 't.id DESC'; break;
               case 'title': $this->sort = 'title'; $this->sort_sql = 't.title'; break;
               case 'title DESC': $this->sort = 'title DESC'; $this->sort_sql = 't.title DESC'; break;
               case 'status': $this->sort = 'status'; $this->sort_sql = 't.status'; break;
               case 'status DESC': $this->sort = 'status DESC'; $this->sort_sql = 't.status DESC'; break;
               case 'priority': $this->sort = 'priority'; $this->sort_sql = 't.priority'; break;
               case 'priority DESC': $this->sort = 'priority DESC'; $this->sort_sql = 't.priority DESC'; break;
               case 'assign': $this->sort = 'assign'; $this->sort_sql = 't.worker'; break;
               case 'assign DESC': $this->sort = 'assign DESC'; $this->sort_sql = 't.worker DESC'; break;
               case 'term': $this->sort = 'term'; $this->sort_sql = 't.term'; break;
               case 'term DESC': $this->sort = 'term DESC'; $this->sort_sql = 't.term DESC'; break;
               default: $this->sort_sql = 'termin, t.priority DESC, t.created DESC';
          }
     }

     public function getGroups(){
          $stmt = $this->getDb()->prepare('SELECT * FROM groups ORDER BY title');
          $ret = array();
          if($stmt!==false){
               $res = $stmt->execute();
               if($res!==false){
                    while($row = $res->fetchArray()){
                         $ret[] = $row;
                    }
               }
          }
          $this->closeDb();
          return $ret;
     }

     public function addGroup($title){
          if(!empty($title)){
               $stmt = $this->getDb()->prepare('INSERT INTO groups (parent,title) VALUES (0,:title)');
               $stmt->bindValue(':title',$this->getDb()->escapeString($title),SQLITE3_TEXT);
               $ret = $stmt->execute();
               $this->closeDb();
               return $ret;
          }
          return false;
     }

     public function changeGroups($groups){
          $gaid = $this->piratitask->getHelper()->getUserGaid();
          if(!empty($gaid)){
               $this->getDb()->exec('DELETE FROM group_user WHERE user_gaid="'.$gaid.'"');
               $stmt = $this->getDb()->prepare('INSERT INTO group_user (group_id,user_gaid) VALUES (:group_id,:user_gaid)');
               foreach($groups as $g){
                    $stmt->bindValue(':group_id',$this->getDb()->escapeString($g),SQLITE3_INTEGER);
                    $stmt->bindValue(':user_gaid',$this->getDb()->escapeString($gaid),SQLITE3_TEXT);
                    $r = $stmt->execute();
                    //var_dump($this->getDb()->lastErrorMsg());
               }
               $this->closeDb();
               return true;
          }
          return false;
     }

     public function countGroupUsers($group_id){
          $stmt = $this->getDb()->prepare('SELECT COUNT(*) AS cnt FROM group_user WHERE group_id = :group_id');
          $stmt->bindValue(':group_id',$this->getDb()->escapeString($group_id),SQLITE3_INTEGER);
          $res = $stmt->execute()->fetchArray();
          $this->closeDb();
          return $res['cnt'];
     }

     public function isInGroup($group_id,$gaid=null){
          if(is_null($gaid)) $gaid = $this->piratitask->getHelper()->getUserGaid();

          $stmt = $this->getDb()->prepare('SELECT COUNT(*) AS cnt FROM group_user WHERE group_id = :group_id AND user_gaid = :user_gaid');
          $stmt->bindValue(':group_id',$this->getDb()->escapeString($group_id),SQLITE3_INTEGER);
          $stmt->bindValue(':user_gaid',$this->getDb()->escapeString($gaid),SQLITE3_TEXT);
          $res = $stmt->execute()->fetchArray();
          $this->closeDb();
          return $res['cnt'];
     }

     public function getUsersByGroup($group_id){
          if(!is_null($this->usrs_grp[$group_id])) return $this->usrs_grp[$group_id];

          $stmt = $this->getDb()->prepare('SELECT user_gaid FROM group_user WHERE group_id = :group_id');
          $stmt->bindValue(':group_id',$this->getDb()->escapeString($group_id),SQLITE3_INTEGER);
          $users = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
          $ret = array();
          $helper = $this->piratitask->getHelper();
          foreach($users as $u){
               $ret[] = $helper->getGraphUser($u);
          }
          $this->closeDb();
          $this->usrs_grp[$group_id] = $ret;
          return $ret;
     }

     public function getGroupByTitle($title){
          $stmt = $this->getDb()->prepare('SELECT * FROM groups WHERE title = :title');
          $stmt->bindValue(':title',$this->getDb()->escapeString($title),SQLITE3_TEXT);
          $ret = $stmt->execute()->fetchArray();
          $this->closeDb();
          return $ret;
     }
}

