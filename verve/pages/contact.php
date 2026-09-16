<?php
require_once __DIR__ . '/../config/config.php';

$pageTitle = 'Contact Us';
$topic = is_string($_GET['topic'] ?? null) ? $_GET['topic'] : 'general';
if (!in_array($topic, getContactEnquiryTypes(), true)) $topic = 'general';

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<section class="contact-studio">
  <div class="shell contact-welcome">
    <span class="nav-eyebrow">THE VERVE HELP DESK</span>
    <h1>A question? An idea?<br><em>Let’s talk.</em></h1>
    <p>From finding your next favourite to sorting out an order, you’re in the right place.</p>
  </div>
</section>

<div class="shell section contact-workspace">
  <aside class="contact-sidebar" aria-label="Ways we can help">
    <h2>A little direction.</h2>
    <p class="muted">Find an answer, check an order, or leave us a note.</p>
    <a class="contact-shortcut" href="<?= BASE_URL ?>/index.php#faq"><span class="contact-shortcut-icon" aria-hidden="true">?</span><span><strong>Quick answers</strong><small>Shipping, shopping &amp; more</small></span><span aria-hidden="true">&nearr;</span></a>
    <a class="contact-shortcut" href="<?= BASE_URL ?>/pages/account.php?tab=orders"><span class="contact-shortcut-icon" aria-hidden="true">&#9633;</span><span><strong>Your orders</strong><small>View details and order status</small></span><span aria-hidden="true">&nearr;</span></a>
    <a class="contact-shortcut" href="<?= BASE_URL ?>/pages/contact.php?topic=returns#contact-form"><span class="contact-shortcut-icon" aria-hidden="true">&#8634;</span><span><strong>Returns &amp; exchanges</strong><small>Let’s find the next step</small></span><span aria-hidden="true">&nearr;</span></a>
    <div class="contact-note">
      <span class="nav-eyebrow">A HANDY LITTLE TIP</span>
      <h3>Help us help you.</h3>
      <p>Asking about a purchase? Include your order number and a few details so we can find the right information.</p>
    </div>
  </aside>
  <div class="contact-form-wrap">
    <form action="<?= BASE_URL ?>/actions/contact.php" method="post" class="form-card" id="contact-form">
      <?= csrfField() ?>
      <div class="contact-form-heading"><span class="nav-eyebrow">DROP US A NOTE</span><h2>How can we help?</h2><p>Tell us what’s on your mind. Fields marked * are required.</p></div>
      <div class="field">
        <label for="enquiry_type">What's this about? *</label>
        <select id="enquiry_type" name="enquiry_type" required>
          <option value="general" <?= $topic === 'general' ? 'selected' : '' ?>>General question</option>
          <option value="order_issue" <?= $topic === 'order_issue' ? 'selected' : '' ?>>An existing order</option>
          <option value="product_question" <?= $topic === 'product_question' ? 'selected' : '' ?>>A product question</option>
          <option value="returns" <?= $topic === 'returns' ? 'selected' : '' ?>>Returns &amp; exchanges</option>
        </select>
      </div>
      <div class="form-grid">
        <div class="field">
          <label for="name">Your name *</label>
          <input type="text" id="name" name="name" autocomplete="name" minlength="2" maxlength="120" placeholder="Your full name" required>
        </div>
        <div class="field">
          <label for="email">Email address *</label>
          <input type="email" id="email" name="email" autocomplete="email" maxlength="254" placeholder="you@example.com" required>
        </div>
      </div>
      <div class="form-grid">
        <div class="field">
          <label for="phone">Phone (optional)</label>
          <input type="tel" id="phone" name="phone" autocomplete="tel">
        </div>
        <div class="field">
          <label for="order_reference">Order number (optional)</label>
          <input type="text" id="order_reference" name="order_reference">
        </div>
      </div>
      <div class="field">
        <label for="message">Message *</label>
        <textarea id="message" name="message" rows="6" minlength="10" maxlength="5000" required placeholder="Tell us a little about what you need help with..."></textarea>
      </div>
      <div class="contact-submit-row"><p>Your message goes to the Verve support team.</p><button type="submit" class="btn btn-primary">Send message <span aria-hidden="true">&nearr;</span></button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
