<?php /** @var array $preview */ ?>
<section class="lp-hero">
  <div class="wrap lp-hero__grid">
    <div class="lp-hero__text">
      <h1>Your world, shared with <span class="em">the people you choose</span>.</h1>
      <p class="lp-hero__lede">
        EMChat Media is a social network without ad trackers, behavioural profiling,
        or an algorithm deciding what you see. Post, message and follow the people who
        matter, and take everything with you whenever you like.
      </p>
      <div class="lp-hero__cta">
        <a class="btn btn--primary btn--lg" href="<?= e(url('/login')) ?>">Create your account</a>
        <a class="btn btn--ghost btn--lg" href="<?= e(url('/explore')) ?>">Browse public posts</a>
      </div>
      <p class="lp-hero__note">Free to use. Sign in with your email, no password required.</p>
      <p class="lp-hero__origin">Built independently in North Macedonia.</p>
    </div>

    <div class="lp-hero__media" aria-hidden="true">
      <div class="lp-stage" data-stage>
        <article class="lp-card lp-scan" data-scan data-reveal="1">
          <div class="lp-scan__top">
            <span class="lp-scan__pulse"></span>
            <span data-scan-label>Checking this page for trackers</span>
          </div>
          <div class="lp-scan__bar"><i data-scan-bar></i></div>
          <ul class="lp-scan__list">
            <li data-scan-item><span class="lp-scan__mark"><?= icon('check', '', 12) ?></span> No analytics scripts</li>
            <li data-scan-item><span class="lp-scan__mark"><?= icon('check', '', 12) ?></span> No advertising SDKs</li>
            <li data-scan-item><span class="lp-scan__mark"><?= icon('check', '', 12) ?></span> No third-party requests</li>
            <li data-scan-item><span class="lp-scan__mark"><?= icon('check', '', 12) ?></span> No fingerprinting</li>
          </ul>
          <div class="lp-scan__result">
            <span><b data-scan-n0>0</b> trackers</span>
            <span><b data-scan-n1>0</b> requests to other sites</span>
          </div>
        </article>

        <div class="lp-chip" data-reveal="2">
          <?= icon('lock', '', 14) ?> Your feed never leaves EMChat
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($preview)): ?>
<section class="lp-live wrap">
  <div class="lp-live__head">
    <h2>Happening now</h2>
    <a href="<?= e(url('/explore')) ?>">See more</a>
  </div>
  <div class="lp-live__grid">
    <?php foreach (array_slice($preview, 0, 4) as $post): ?>
      <?= $view->partial('post', ['post' => $post, 'viewer' => null]) ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="lp-features wrap">
  <h2 class="sr-only">Everything EMChat Media gives you</h2>
  <div class="lp-feature" data-reveal-scroll>
    <h3>Profile cards</h3>
    <p>A full profile page with your photo, bio, links and follower counts. Share it on its own at <span class="lp-mono">emchat.social/@you</span>.</p>
  </div>
  <div class="lp-feature" data-reveal-scroll style="transition-delay:.06s">
    <h3>A feed you control</h3>
    <p>Posts from the people you follow, in the order they were shared. Nothing ranked to keep you scrolling, and no ads.</p>
  </div>
  <div class="lp-feature" data-reveal-scroll style="transition-delay:.12s">
    <h3>Photos and messages</h3>
    <p>Up to four images per post, processed to remove hidden location data. Direct messages carry photos, video and files.</p>
  </div>
  <div class="lp-feature" data-reveal-scroll style="transition-delay:.18s">
    <h3>Privacy that means something</h3>
    <p>Private accounts, per post visibility, follower approval and a one click export or deletion of everything you have shared.</p>
  </div>
</section>

<section class="lp-privacy wrap">
  <div class="lp-privacy__grid">
    <div class="lp-privacy__media" aria-hidden="true" data-reveal-scroll>
      <div class="lp-card lp-privacy__card">
        <div class="lp-privacy__row">
          <span class="lp-privacy__row-text"><strong>Private account</strong><small>Only approved followers can see your posts, followers and following.</small></span>
          <span class="switch__track switch__track--on"></span>
        </div>
        <div class="lp-privacy__row">
          <span class="lp-privacy__row-text"><strong>Discoverable</strong><small>Show up in search and the public sitemap.</small></span>
          <span class="switch__track"></span>
        </div>
        <div class="lp-privacy__row lp-privacy__row--last">
          <span class="lp-privacy__row-text"><strong>This post</strong><small>Choose who sees it as you write it.</small></span>
          <span class="lp-privacy__pill"><?= icon('users', '', 12) ?> Followers</span>
        </div>
      </div>
    </div>
    <div class="lp-privacy__text" data-reveal-scroll style="transition-delay:.1s">
      <h2>Privacy that's actually yours to set</h2>
      <p>Every control lives one tap away in Settings, not buried in a menu you'll never find. Lock your whole account, or choose the audience for each post individually: public, followers, or just you.</p>
      <ul class="lp-privacy__list">
        <li><span class="lp-privacy__mark"><?= icon('check', '', 12) ?></span> Private accounts require your approval before anyone can follow.</li>
        <li><span class="lp-privacy__mark"><?= icon('check', '', 12) ?></span> Turn off "Discoverable" and you vanish from search and the sitemap while staying fully usable.</li>
        <li><span class="lp-privacy__mark"><?= icon('check', '', 12) ?></span> Export everything or delete your account for good, any time, from Settings.</li>
      </ul>
    </div>
  </div>
</section>

