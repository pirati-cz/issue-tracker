<?php if(!empty($task)): ?>
     <div id="piratitask">
          <div id="piratitask-watchers-win" class="modal hide fade">
               <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4><?php echo $piratitask->getLang('watchers') ?></h4>
               </div>
               <div class="modal-body"><ol>
                    <?php foreach($watchers as $w): ?>
                         <li><?php echo hsc($w['watcher']) ?></li>
                    <?php endforeach; ?>
               </ol></div>
               <div class="modal-footer">
                    <a href="#close" class="btn"><?php echo $piratitask->getLang('btn-close') ?></a>
               </div>
          </div>

          <div id="piratitask-info" class="well well-small pull-right">
               <?php if($piratitask->getHelper()->isAuth()): ?>
                    <div class="pull-right">
                         <?php if($piratitask->isWatch($task['id'])): ?>
                              <button title="<?php echo $piratitask->getLang('stopwatch') ?>" onclick="btnwatch(<?php echo $task['id'] ?>,this)" class="btn btn-mini btn-info active"><i class="icon-eye-open icon-white"></i></button>&nbsp;
                         <?php else: ?>
                              <button title="<?php echo $piratitask->getLang('startwatch') ?>" onclick="btnwatch(<?php echo $task['id'] ?>,this)" class="btn btn-mini"><i class="icon-eye-open"></i></button>&nbsp;
                         <?php endif; ?>
                         
                         <?php if($piratitask->isWork($task['worker_gaid'])): ?>
                              <button onclick="btnwork(<?php echo $task['id'] ?>,this)" class="btn btn-mini btn-danger active"><i class="icon-flag icon-white"></i></button>
                         <?php elseif(empty($task['worker_gaid'])): ?>
                              <button onclick="btnwork(<?php echo $task['id'] ?>,this)" class="btn btn-mini"><i class="icon-flag"></i></button>
                         <?php endif; ?>
                    </div>
               <?php endif; ?>

               <strong>#<?php echo $task['id'] ?></strong>
               <table class="table table-condensed">
                    <tr><th><?php echo $piratitask->getLang('status') ?></th><td>
                    <?php if($piratitask->getHelper()->isAuth()): ?>
                         <div class="btn-group">
                              <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown"><?php echo $piratitask->getStatus($task['status']) ?> <span class="caret"></span></button>
                              <ul class="dropdown-menu">
                                   <?php foreach($piratitask->getStatuses() as $sid=>$status): ?>
                                        <?php if($sid!=$task['status']): ?>
                                             <li><a href="#" onclick="return piratitask_changeStatus(this,<?php echo $sid ?>)"><?php echo $status ?></a></li>
                                        <?php endif; ?>
                                   <?php endforeach; ?>
                              </ul>
                         </div>
                    <?php else: ?>
                         <?php echo $piratitask->getStatus($task['status']); ?>
                    <?php endif; ?>
                    </td></tr>
                    <tr><th><?php echo $piratitask->getLang('priority') ?></th><td>
                         <?php if($piratitask->getHelper()->isAuth()): ?>
                              <div class="btn-group">
                                   <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown"><?php echo $piratitask->getPriority($task['priority']) ?> <span class="caret"></span></button>
                                   <ul class="dropdown-menu">
                                        <?php foreach($piratitask->getPriorities() as $pid=>$priority): ?>
                                             <?php if($pid!=$task['priority']): ?>
                                             <li><a href="#" onclick="return piratitask_changePriority(this,<?php echo $pid ?>);"><?php echo $priority ?></a></li>
                                             <?php endif; ?>
                                        <?php endforeach; ?>
                                   </ul>
                              </div>
                         <?php else: ?>
                              <?php echo $piratitask->getPriority($task['priority']); ?>
                         <?php endif; ?>
                    </td></tr>
                    <tr><th><?php echo $piratitask->getLang('assign') ?></th><td id="piratitask-worker"><?php echo (empty($task['worker'])?'-':hsc($task['worker'])) ?></td></tr>
                    <tr><th><?php echo $piratitask->getLang('term') ?></th><td>
                         <?php if($piratitask->getHelper()->isAuth()): ?>
                              <div id="datepicker" class="input-append"><input type="hidden" id="term"><button class="btn btn-mini add-on"><i></i><?php echo (empty($task['term'])?'-':date('j.n.Y',strtotime($task['term']))) ?></button></div>
                         <?php else: ?>
                              <?php echo (empty($task['term'])?'-':date('j.n.Y',strtotime($task['term']))) ?>
                         <?php endif; ?>
                    </td></tr>
                    <tr class="piratitask-dlines"><th><?php echo $piratitask->getLang('sponsor') ?></th><td>
                         <?php echo (empty($task['sponsor'])?'-':hsc($task['sponsor'])) ?>
                    </td></tr>
                    <tr><th><?php echo $piratitask->getLang('created') ?></th><td><?php echo date('j.n.Y G:i',strtotime($task['created'])) ?></td></tr>
                    <tr><th><?php echo $piratitask->getLang('author') ?></th><td><?php echo hsc($task['author']) ?></td></tr>
                    <?php if(count($watchers>0)): ?>
                         <tr><th><?php echo $piratitask->getLang('watchers') ?></th><td id="piratitask-watchers"><button onclick="return piratitask_winWatchers();" class="btn btn-mini"><?php echo count($watchers); ?></button></td></tr>
                    <?php else: ?>
                         <tr><th><?php echo $piratitask->getLang('watchers') ?></th><td id="piratitask-watchers">0</td></tr>
                    <?php endif; ?>
               </table>
          </div>

          <div id="piratitask-content">
               <h1><?php echo hsc($task['title']) ?></h1>
               <?php echo p_render('xhtml',p_get_instructions($task['content']),$info); ?>
          </div>

          <div class="clearfix"></div>
          <div id="piratitask-comments">
               <?php if($piratitask->getHelper()->isAuth()): ?>
                    <form id="form-addcomment" method="post" action="#">
                         <div class="input-append">
                              <textarea rows="3" id="piratitask-commtext"></textarea>
                              <button class="btn btn-large" id="submit" data-loading-text="<?php echo $piratitask->getLang('addingcomm') ?>"><?php echo $piratitask->getLang('addcomm') ?></button>
                         </div>
                    </form>
               <?php endif; ?>
               <div id="piratitask-commlist"></div>
          </div>
                                        
     </div>
     <div class="clearfix"></div>
<?php else: ?>
     <h3><?php echo $piratitask->getLang('noexists') ?></h3>
<?php endif; 
