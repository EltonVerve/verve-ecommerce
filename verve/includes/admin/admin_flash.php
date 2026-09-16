<?php
$flash = getFlash();
if ($flash):
    $isError = $flash['type'] === 'error';
?>
<div role="<?= $isError ? 'alert' : 'status' ?>" class="flash-banner <?= $isError ? 'flash-error' : 'flash-success' ?>" style="margin-bottom:1.5rem;">
  <?= h($flash['message']) ?>
</div>
<?php endif; ?>
