<?php if($piratitask->getDb()->getPages()>1): ?>
     <div class="pagination pagination-centered"><ul>
          <?php if($piratitask->getDb()->getPage()>0): ?>
               <li><a href="#" onclick="piratitask_page(0)">|&lt;</a></li>
               <li><a href="#" onclick="piratitask_page(<?php echo ($piratitask->getDb()->getPage()-1) ?>)">&lt;</a></li>
          <?php endif; ?>

          <?php
               $start = $piratitask->getDb()->getPage()-2; if($start<0) $start=0;
               $end = $piratitask->getDb()->getPage()+3; if($end>$piratitask->getDb()->getPages()) $end=$piratitask->getDb()->getPages(); else $end = $piratitask->getDb()->getPages();
          ?>
          <?for($p=$start;$p<$end;$p++): ?>
               <li<?php echo ($p==$piratitask->getDb()->getPage()?' class="active"':'') ?>><a href="#" onclick="piratitask_page(<?php echo $p; ?>)"><?php echo ($p+1) ?></a></li>
          <?php endfor; ?>

          <?php if($piratitask->getDb()->getPage()<$piratitask->getDb()->getPages()-1): ?>
               <li><a href="#" onclick="piratitask_page(<?php echo ($piratitask->getDb()->getPage()+1) ?>)">&gt;</a></li>
               <li><a href="#" onclick="piratitask_page(<?php echo ($piratitask->getDb()->getPages()-1) ?>)">&gt;|</a></li>
          <?php endif; ?>
     </ul></div>
<?php endif; ?>

<table class="table table-hover table-condensed">
     <thead><tr>
          <th><a onclick="piratitask_sort('id',this)" href="#sort-by-id">#<?php echo $this->renderBySort('id'); ?></a></th>
          <th><a onclick="piratitask_sort('title',this)" href="#sort-by-title"><?php echo $piratitask->getLang('title').$this->renderBySort('title'); ?></a></th>
          <th><a onclick="piratitask_sort('status',this)" href="#sort-by-status"><?php echo $piratitask->getLang('status').$this->renderBySort('status'); ?></a></th>
          <th><a onclick="piratitask_sort('priority',this)" href="#sort-by-priority"><?php echo $piratitask->getLang('priority').$this->renderBySort('priority'); ?></a></th>
          <th><a onclick="piratitask_sort('assign',this)" href="#sort-by-assign"><?php echo $piratitask->getLang('assign').$this->renderBySort('assign'); ?></a></th>
          <th><a onclick="piratitask_sort('term',this)" href="#sort-by-term"><?php echo $piratitask->getLang('term').$this->renderBySort('term'); ?></a></th>
          <th>&nbsp;</th>
     </tr></thead><tbody>

     <?php foreach($tasks as $task): ?>

               <?php 
               $tagsHelper = $piratitask->getTagsHelper();
               $tags = array();
               if(!is_null($tagsHelper)){
                    $tags = $tagsHelper->getTags(array(
                         'category' => 1,
                         'ekey' => $task['id']
                    ));
               }

               switch($task['priority']){
                    case 1: $class='success'; break;
                    case 2: $class='warning'; break;
                    case 3: $class='error'; break;
                    default: $class='info';
               } ?>

          <tr class="<?php echo $class ?>">
               <td><?php echo $task['id'] ?></td>
               <td><?php echo html_wikilink(($piratitask->getNamespace().':'.$task['id']),$task['title']);
                    if(!empty($tags)) echo '<br/>'.$tagsHelper->renderTags($tags);
               ?></td>
               <td><?php echo $piratitask->getStatus($task['status']) ?></td>
               <td><?php echo $piratitask->getPriority($task['priority']) ?></td>
               <td class="piratitask-worker"><?php echo (empty($task['worker'])?'-':str_replace(' ','&nbsp;',$task['worker'])) ?></td>
               <td><?php echo (empty($task['term'])?'-':date('j.n.Y',strtotime($task['term']))) ?></td>
               <td>
                    <?php if($piratitask->getHelper()->isAuth()): ?>
                         <?php if($piratitask->isWatch($task['id'])): ?>
                              <button data-toggle="tooltip" data-placement="left" title="<?php echo $piratitask->getLang('stopwatch') ?>" onclick="btnwatch(<?php echo $task['id'] ?>,this)" class="btn btn-mini btn-info active task-tooltip"><i class="icon-eye-open icon-white"></i></button>&nbsp;
                         <?php else: ?>
                              <button data-toggle="tooltip" data-placement="left" title="<?php echo $piratitask->getLang('startwatch') ?>" onclick="btnwatch(<?php echo $task['id'] ?>,this)" class="btn btn-mini task-tooltip"><i class="icon-eye-open"></i></button>&nbsp;
                         <?php endif; ?>
                         <?php if($piratitask->isWork($task['worker_gaid'])): ?>
                              <button data-toggle="tooltip" data-placement="left" title="<?php echo $piratitask->getLang('stopwork') ?>" onclick="btnwork(<?php echo $task['id'] ?>,this)" class="btn btn-mini btn-danger active"><i class="icon-flag icon-white"></i></button></td>
                         <?php elseif(empty($task['worker_gaid'])): ?>
                              <button data-toggle="tooltip" data-placement="left" title="<?php echo $piratitask->getLang('startwork') ?>" onclick="btnwork(<?php echo $task['id'] ?>,this)" class="btn btn-mini task-tooltip"><i class="icon-flag"></i></button>
                         <?php endif; ?>
                    <?php endif; ?>
               </td>
          </tr>
     <?php endforeach; ?>
     </tbody>
</table>

<?php if($piratitask->getDb()->getPages()>1): ?>
     <div class="pagination pagination-centered"><ul>
          <?php if($piratitask->getDb()->getPage()>0): ?>
               <li><a href="#" onclick="piratitask_page(0)">|&lt;</a></li>
               <li><a href="#" onclick="piratitask_page(<?php echo ($piratitask->getDb()->getPage()-1) ?>)">&lt;</a></li>
          <?php endif; ?>

          <?php
               $start = $piratitask->getDb()->getPage()-2; if($start<0) $start=0;
               $end = $piratitask->getDb()->getPage()+3; if($end>$piratitask->getDb()->getPages()) $end=$piratitask->getDb()->getPages(); else $end = $piratitask->getDb()->getPages();
          ?>
          <?for($p=$start;$p<$end;$p++): ?>
               <li<?php echo ($p==$piratitask->getDb()->getPage()?' class="active"':'') ?>><a href="#" onclick="piratitask_page(<?php echo $p; ?>)"><?php echo ($p+1) ?></a></li>
          <?php endfor; ?>

          <?php if($piratitask->getDb()->getPage()<$piratitask->getDb()->getPages()-1): ?>
               <li><a href="#" onclick="piratitask_page(<?php echo ($piratitask->getDb()->getPage()+1) ?>)">&gt;</a></li>
               <li><a href="#" onclick="piratitask_page(<?php echo ($piratitask->getDb()->getPages()-1) ?>)">&gt;|</a></li>
          <?php endif; ?>
     </ul></div>
<?php endif;
