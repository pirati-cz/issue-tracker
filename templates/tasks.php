<div id="piratitask" class="tabbable">
     <ul class="nav nav-tabs">
          <li data-toggle="tab"><a href="#tab1"><i class="icon-star"></i> <?php echo $piratitask->getLang('newissue') ?></a></li>
          <li data-toggle="tab" class="active"><a href="#tab2"><i class="icon-tasks"></i> <?php echo $piratitask->getLang('issues') ?></a></li>
          <?php if($piratitask->getHelper()->isAuth()): ?>
               <li data-toggle="tab"><a href="#tab3"><i class="icon-wrench"></i> <?php echo $piratitask->getLang('mysettings') ?></a></li>
          <?php endif; ?>
          <?php /* <li class="pull-right" data-toggle="tab"><a href="#tab5"><i class="icon-tags"></i> <?php echo $this->getLang('tags') ?></a></li> */ ?>
          <li class="pull-right" data-toggle="tab"><a href="#tab4"><i class="icon-list"></i> <?php echo $piratitask->getLang('volunteergroups') ?></a></li>
     </ul>
     
     <div class="tab-content">
          <script type="text/javascript">
               var piratitask_typeahead_g = [
                    <?php foreach($piratitask->getHelper()->getGraphGroups() as $i=>$group): ?>
                         <?php if($i>0): ?>,<?php endif; ?>
                         '<?php echo $group->username ?>'
                    <?php endforeach; ?>
               ];
               var piratitask_typeahead_u = [
                    <?php foreach($piratitask->getHelper()->getGraphUsers() as $i=>$user): ?>
                         <?php if($i>0): ?>,<?php endif; ?>
                         '<?php echo $user->username ?>'
                    <?php endforeach; ?>
               ];
               var piratitask_typehead_v = [
                    <?php foreach($piratitask->getGroups() as $i=>$volunteer_grp): ?>
                         <?php if($i>0): ?>,<?php endif; ?>
                         '<?php echo $volunteer_grp['title']; ?>'
                    <?php endforeach; ?>
               ];
               var piratitask_typeahead = piratitask_typeahead_g.concat(piratitask_typeahead_u).concat(piratitask_typehead_v);
          </script>

          <!-- tab1 -->
          <div class="tab-pane" id="tab1">
               <?php if(!$piratitask->getHelper()->isAuth()): ?>
                    <p><?php echo $piratitask->getLang('mustlog') ?></p>
               <?php else: ?>
                    <div id="success" class="alert alert-success"><h4><?php echo $piratitask->getLang('succdesc') ?></h4></div>
                    <div id="alert" class="alert alert-error"><h4><?php echo $piratitask->getLang('errordesc') ?>:</h4>
                         <br><ul id="errorlist"></ul>
                    </div>
                    <p><?php echo $piratitask->getLang('mustfields') ?></p>
                    <form action="#" id="form-newissue" method="post" class="form-horizontal">
                         <input type="hidden" id="form-newissue-sectok" name="sectok" value="<?php echo getSecurityToken(); ?>">
                         <div class="control-group">
                              <label class="control-label required" for="title"><?php echo $piratitask->getLang('title') ?></label>
                              <div class="controls">
                                   <input name="title" id="title" type="text" placeholder="<?php echo $piratitask->getLang('issuetitle') ?>"  required="required">
                              </div>
                         </div>
                         <div class="control-group">
                              <label class="control-label required" for="priority"><?php echo $piratitask->getLang('priority') ?></label>
                              <div class="controls"><select name="priority" id="priority" required="required">
                                   <?php foreach($piratitask->getPriorities() as $value=>$name): ?>
                                        <option value="<?php echo $value ?>"<?php echo ($value==1?' selected="selected"':'') ?>><?php echo $name ?></option>
                                   <?php endforeach; ?>
                              </select></div>
                         </div>
                         <div class="control-group">
                              <label class="control-label" for="sponsor"><?php echo $piratitask->getLang('sponsor') ?></label>
                              <div class="controls">
                                   <input type="text" class="sponsor" name="sponsor" id="sponsor" placeholder="<?php echo $piratitask->getLang('groupsusers') ?>" requierd="required">
                              </div>
                         </div>
                         <div class="control-group">
                              <label class="control-label" for="warn"><?php echo $piratitask->getLang('warn') ?></label>
                              <div class="controls">
                                   <input type="text" class="warn" name="warn" id="warn" placeholder="<?php echo $piratitask->getLang('groupsusers') ?>">
                                        <ul id="warnlist"></ul>
                              </div>
                         </div>
                         <div class="control-group">
                              <label class="control-label" for="term"><?php echo $piratitask->getLang('term') ?></label>
                                   <div class="controls"><div id="datepicker" class="input-append"><input type="text" name="term" id="term" placeholder="dd.mm.yyyy"><span class="add-on"><i class="icon-calendar"></i></span></div></div>
                         </div>
                         <div class="control-group">
                              <label class="control-label required" for="wiki"><?php echo $piratitask->getLang('content') ?></label>
                              <div class="controls"><?php echo $piratitask->getHelper()->renderEditTextarea() ?></div>
                         </div>
                         <div class="control-group">
                              <label class="control-label">&nbsp;</label>
                              <div class="controls">
                                   <button id="submit" type="submit" class="btn btn-primary" data-loading-text="<?php echo $piratitask->getLang('creating') ?>"><?php echo $piratitask->getLang('create') ?></button>
                              </div>
                         </div>
                    </form>
               <?php endif; ?>
          </div>
          
          <!-- tab2 -->
          <div class="tab-pane active" id="tab2">
               <form class="form-horizontal">
                    <div class="control-group">
                    <label class="control-label"><?php echo $piratitask->getLang('quickfiltr') ?></label>
                         <div class="controls">
                         <?php if($piratitask->getHelper()->isAuth()): ?>
                              <div class="btn-group">
                                   <button class="btn" id="btn-filtr-my"><?php echo $piratitask->getLang('myissues') ?></button>
                                   <button class="btn" id="btn-filtr-watch"><?php echo $piratitask->getLang('watched') ?></button>
                              </div>
                         <?php endif; ?>
                              <div class="btn-group">
                                   <button class="btn" id="btn-filtr-open"><?php echo $piratitask->getLang('opened') ?></button>
                                   <button class="btn" id="btn-filtr-close"><?php echo $piratitask->getLang('closed') ?></button>
                              </div>
                         </div>
                    </div>
               </form>
               <form class="form-horizontal">
                    <div class="control-group">
                         <label class="control-label"><?php echo $piratitask->getLang('advancedfiltr') ?></label>
                         <div class="controls">
                              <div id="filtr"></div>
                              <button class="btn" id="btn-filtr-advanced" data-placement="bottom" data-toggle="tooltip" title="<?php echo $piratitask->getLang('help-btn-filtr-advanced') ?>"><i class="icon-plus"></i> <?php echo $piratitask->getLang('addcond') ?></button>
                         </div>
                    </div>

                    <!-- filtr dialog -->
                    <div>
                         <div class="modal hide fade" id="win-filter">
                              <div class="modal-header">
                                   <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                                   <h3><?php echo $piratitask->getLang('addnewcond') ?></h3>
                              </div>
                              <div class="modal-body">
                                   <p>
                                        <select id="fcol">
                                             <option value="0"><?php echo $piratitask->getLang('selcol') ?></option>
                                             <?php foreach($piratitask->getFilterColumns() as $col=>$colname): ?>
                                                  <option value="<?php echo $col ?>"><?php echo $colname ?></option>
                                             <?php endforeach; ?>
                                        </select>&nbsp;<span id="fafter"></span>
                                   </p>
                              </div>
                              <div class="modal-footer">
                                   <a href="#close" class="btn"><?php echo $piratitask->getLang('btn-close') ?></a>
                                   <a href="#addcond" class="btn btn-primary"><?php echo $piratitask->getLang('addcond') ?></a>
                              </div>
                         </div>
                    </div>
               </form>
               <div id="issues" class="issueload"><?php echo $piratitask->getLang('loading') ?></div>
          </div>

          <!-- tab3 -->
          <?php if($piratitask->getHelper()->isAuth()): ?>
               <div class="tab-pane" id="tab3">
                    <form class="form-horizontal">
                         <div class="control-group">
                              <label class="control-label"><?php echo $piratitask->getLang('warnemail') ?></label>
                              <div class="controls">
                                   <input id="mailme" name="mailme" type="checkbox" value="ok"<?php echo ($piratitask->getSettings('mail')==1?' checked':''); ?>>
                              </div>
                         </div>
                         <div class="control-group">
                              <label class="control-label">RSS</label>
                              <div class="controls">
                                   <a href="/feed.php?mode=piratitask&amp;ns=<?php echo $piratitask->getNamespace(); ?>&amp;type=rss1&amp;id=<?php echo $piratitask->getSettings('rss'); ?>"><img src="/_media/icons/rss_yellow.jpeg"> odkaz na vaše RSS</a>
                              </div>
                         </div>
                    </form>
               </div>
          <?php endif; ?>

          <!-- tab4 -->
          <div class="tab-pane" id="tab4">
               <?php if(!$piratitask->getHelper()->isAuth()): ?>
                    <p><?php echo $piratitask->getLang('mustloggroup') ?></p>
               <?php endif; ?>
               <p>
                    <?php if($piratitask->isGroupsAdmin()): ?>
                         <form class="form-horizontal" id="form-newgroup">
                              <div class="control-group">
                                   <label class="control-label"><?php echo $piratitask->getLang('newgroup'); ?></label>
                                   <div class="controls">
                                        <?php /*
                                        <select name="parent">
                                             <optgroup label="<?php echo $this->getLang('parent') ?>">
                                                  <option value="">-- žádná --</option>
                                                  <?php foreach($piratitask->getGroups() as $group): ?>
                                                       
                                                  <?php endforeach; ?>
                                             </optgroup>
                                        </select> */ ?>
                                        <input name="title" type="text" placeholder="<?php echo $piratitask->getLang('groupname'); ?>"> 
                                        <button class="btn btn-primary" type="submit"><?php echo $piratitask->getLang('add'); ?></button>
                                   </div>
                              </div>
                         </form>
                    <?php endif; ?>

                    <form action="#nope" method="post" class="form-horizontal">
                         <div class="control-group">
                              <div id="group_list" class="controls">
                                   <?php $this->renderGroupsList($piratitask->getGroups()); ?>
                              </div>
                         </div>
                    </form>
               </p>
          </div>

          <?php /*
          <!-- tab5 -->
          <div class="tab-pane" id="tab5">
               ---
               </div> */ ?>
     </div>
</div>

