<?php
$heroSlides = [
    ['The everyday edit', 'Good finds.', 'Great energy.', 'Your next favourite is right here. Discover tech, style and little upgrades for every day.', 'Shop the collection', '/pages/shop.php', 'home-shopping-background.png', 'Fresh picks. Your rules.', 'shop'],
    ['Turn up your everyday', 'Your sound.', 'Your world.', 'Find headphones, speakers and smart essentials for work, play and everything in between.', 'Explore electronics', '/pages/shop.php?cat=electronics', 'category-electronics.png', 'Plug into possibility.', 'tech'],
    ['Take the scenic route', 'Less scrolling.', 'More exploring.', 'Make room for fresh air and a new favourite trail. Your next adventure starts here.', 'Shop sports & outdoors', '/pages/shop.php?cat=sports', 'sports-outdoors-hero.png', 'Out there looks good on you.', 'outdoor'],
];
?>
<div class="shell carousel-shell">
  <section class="verve-carousel" aria-label="Featured collections" aria-roledescription="carousel" data-carousel>
    <div class="carousel-slides">
      <?php foreach ($heroSlides as $index => $slide): ?>
        <div class="carousel-slide carousel-<?= h($slide[8]) ?>" role="group" aria-roledescription="slide" aria-label="<?= $index + 1 ?> of <?= count($heroSlides) ?>: <?= h($slide[0]) ?>" <?= $index ? 'hidden' : '' ?>>
          <img class="carousel-photo" src="<?= BASE_URL ?>/public/assets/products/<?= h($slide[6]) ?>" alt="" <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
          <div class="carousel-copy">
            <span class="carousel-eyebrow"><?= h($slide[0]) ?></span>
            <h2><?= h($slide[1]) ?><br><em><?= h($slide[2]) ?></em></h2>
            <p><?= h($slide[3]) ?></p>
            <a class="btn btn-primary" href="<?= BASE_URL . h($slide[5]) ?>"><?= h($slide[4]) ?> <span aria-hidden="true">&nearr;</span></a>
          </div>
          <span class="carousel-stamp"><?= h($slide[7]) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="carousel-controls" hidden>
      <div class="carousel-arrows"><button type="button" data-prev aria-label="Previous slide">&larr;</button><button type="button" data-next aria-label="Next slide">&rarr;</button></div>
      <div class="carousel-dots" aria-label="Choose a slide">
        <?php foreach ($heroSlides as $index => $slide): ?>
          <button type="button" data-slide="<?= $index ?>" aria-label="Show <?= h($slide[0]) ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?>></button>
        <?php endforeach; ?>
      </div>
      <button type="button" class="carousel-pause" data-pause>Pause slideshow</button>
    </div>
    <span class="carousel-status" aria-live="polite" aria-atomic="true"></span>
  </section>
</div>
