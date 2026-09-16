<?php
/**
 * FLASH MESSAGE BANNER
 * ---------------------------------------------------------
 * Include this right after require '../includes/header.php';
 * on any page. It checks for a one-time message set by
 * setFlash() and displays it, styled by type (success/error).
 * ---------------------------------------------------------
 */
$flash = getFlash();
if ($flash):
    $isError = $flash['type'] === 'error';
?>
<div class="shell" style="padding-top:1.25rem;">
  <div role="<?= $isError ? 'alert' : 'status' ?>" aria-live="<?= $isError ? 'assertive' : 'polite' ?>"
       class="flash-banner <?= $isError ? 'flash-error' : 'flash-success' ?>">
    <?= h($flash['message']) ?>
  </div>
</div>
<?php endif; ?>
