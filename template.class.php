<?php

class PiratitaskTemplate {

     private $piratitask = null;
     private $renderer = null;

     public function __construct($piratitask){
          $this->piratitask = $piratitask;
     }

     public function setRenderer($renderer){
          $this->renderer = $renderer;
     }

     /* SYNTAX */
     public function renderTasksSyntax(){
          $piratitask = $this->piratitask;
          ob_start();
          include('templates/tasks.php');
          $this->renderer->doc .= ob_get_clean();
     }

     public function renderTaskSyntax($task,$watchers){
          $piratitask = $this->piratitask;
          ob_start();
          include('templates/task.php');
          $this->renderer->doc .= ob_get_clean();
     }

     /* AJAX */

     public function renderBySort($col){
          if($col==$this->piratitask->getDb()->getSort()) return '&nbsp;<i class="icon-chevron-up"></i>';
          if($col.' DESC'==$this->piratitask->getDb()->getSort()) return '&nbsp;<i class="icon-chevron-down"></i>';
          return '';
     }

     public function renderGroupsList(/*$groups*/){
          $piratitask = $this->piratitask;
          $groups = $this->piratitask->getDb()->getGroups();
          include('templates/groups_list.php');
     }

     public function renderTasksList($tasks){
          $piratitask = $this->piratitask;
          include('templates/tasks_list.php');
     }
     public function renderErrorList($error){
          if(is_array($error)){
               foreach($error as $e) echo '<li>'.$e.'</li>';
          } else
               echo '<li>'.$error.'</li>';
     }
     public function renderTaskComments($comments){
          include('templates/comments.php');
     }

     /* EDIT */
     public function renderEditForm($task){
          $piratitask = $this->piratitask;
          $output = '<div id="piratitask">';
          if(!$this->piratitask->isAuthor()){
               $output .= '<h4>'.$piratitask->getLang('noedit').'</h4>';
          } else {
               $output .= '<div id="alert" class="alert alert-error"><h4>'.$piratitask->getLang('errordesc').':</h4><br><ul id="errorlist"></ul></div>';
               $output .= '<form action="#" id="form-editissue" method="post" class="form-horizontal">';
                    $output .= '<div class="control-group">';
                    $output .= '<label class="control-label required" for="title">'.$piratitask->getLang('title').'</label>';
                         $output .= '<div class="controls">';
                              $output .= '<input name="title" value="'.$task['title'].'" id="title" type="text" placeholder="'.$piratitask->getLang('issuetitle').'"  required="required">';
                         $output .= '</div>';
                    $output .= '</div>';
                    $output .= '<div class="control-group">';
                         $output .= '<label class="control-label required" for="wiki">'.$piratitask->getLang('content').'</label>';
                         $output .= '<div class="controls">'.$piratitask->getHelper()->renderEditTextarea($task['content']).'</div>';
                    $output .= '</div>';
                    $output .= '<div class="control-group">';
                         $output .= '<label class="control-label">&nbsp;</label>';
                         $output .= '<div class="controls">';
                              $output .= '<button id="submit" type="submit" class="btn btn-primary" data-loading-text="'.$piratitask->getLang('saving').'">'.$piratitask->getLang('savechanges').'</button>';
                         $output .= '</div>';
                    $output .= '</div>';
               $output .= '</form>';
          }
          $output .= '</div>';
          
          return $output;
     }

}
