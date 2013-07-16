<dl>
     <?php foreach($comments as $com): ?>
          <dt class="well well-small"><?php echo date('j.n.Y G:i',strtotime($com['pdate'])) ?> - <?php echo $com['author'] ?></dt>
          <dd>
               <?php if($com['type']==0): ?>
                    <?php echo p_render('xhtml',p_get_instructions($com['content']),$info); ?>
               <?php else: ?>
                    <code><?php echo nl2br(hsc($com['content'])) ?></code>
               <?php endif; ?>
          </dd>
     <?php endforeach; ?>
</dl>
