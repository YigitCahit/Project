<?php $flashes = consume_flashes(); ?>
<?php if (!empty($flashes)): ?>
    <div class="flash-wrapper">
        <?php foreach ($flashes as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="flash flash-<?php echo e($type); ?>"><?php echo e($message); ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
