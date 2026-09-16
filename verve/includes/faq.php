<section class="home-faq section" id="faq" aria-labelledby="faq-title">
  <div class="shell faq-layout">
    <div class="faq-intro">
      <span class="nav-eyebrow">A LITTLE HELP, RIGHT HERE</span>
      <h2 id="faq-title">Good questions.<br><em>Simple answers.</em></h2>
      <p>Everything you need for a smoother shopping experience.</p>
      <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-outline btn-sm">Still need help? Contact us &rarr;</a>
    </div>
    <div class="faq-questions">
      <details>
        <summary>Do I need an account to shop?</summary>
        <div class="faq-answer"><p>You can browse, add items to your cart, and check out as a guest. Create an account to keep your cart and view your order history in one place.</p></div>
      </details>
      <details>
        <summary>How much does shipping cost?</summary>
        <div class="faq-answer"><p>Standard shipping is <?= money(FLAT_SHIPPING_FEE) ?>. Orders that meet the <?= money(FREE_SHIPPING_THRESHOLD) ?> free-shipping threshold qualify for free shipping. Your delivery charge is shown in the cart and at checkout.</p></div>
      </details>
      <details>
        <summary>Where can I see my orders?</summary>
        <div class="faq-answer"><p>Sign in and open <a href="<?= BASE_URL ?>/pages/account.php?tab=orders">My orders</a> to see orders placed with your account, their status, and their details. If you checked out as a guest, keep your receipt link so you can return to your order.</p></div>
      </details>
      <details>
        <summary>Can I change the items in my cart?</summary>
        <div class="faq-answer"><p>Yes. Open <a href="<?= BASE_URL ?>/pages/cart.php">your cart</a> to adjust quantities or remove an item before placing your order. For changes after ordering, <a href="<?= BASE_URL ?>/pages/contact.php?topic=order_issue#contact-form">contact us</a> with your order number.</p></div>
      </details>
      <details>
        <summary>How do I use a discount code?</summary>
        <div class="faq-answer"><p>Enter your code in the coupon field in your cart and select Apply. If the code is valid, the discount will appear in your order summary before checkout.</p></div>
      </details>
      <details>
        <summary>How do I request a return or exchange?</summary>
        <div class="faq-answer"><p>Use our <a href="<?= BASE_URL ?>/pages/contact.php?topic=returns#contact-form">returns and exchanges contact form</a>. Include your order number and the item you need help with so we can explain the next steps.</p></div>
      </details>
    </div>
  </div>
</section>
