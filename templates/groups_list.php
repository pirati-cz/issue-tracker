<?php foreach($groups as $group): ?>
     <?php if($piratitask->getHelper()->isAuth()): ?>
          <?php $in = $piratitask->getDb()->isInGroup($group['id']); ?>
     <?php endif; ?>
     <label class="checkbox<?php echo ($in?' in':''); ?>">
          <?php if($piratitask->getHelper()->isAuth()): ?>
               <input type="checkbox" name="group" value="<?php echo $group['id']; ?>"<?php echo ($in?' checked="checked"':''); ?>>
          <?php endif; ?>
          (<span><?php echo $piratitask->getDb()->countGroupUsers($group['id']); ?></span>) <?php echo $group['title']; ?>
     </label>
<?php endforeach; 
