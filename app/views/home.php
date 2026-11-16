<?php /* Homepage. Styles: the "hx-" block at the end of assets/css/app.css. The tracker check reuses the lp-scan animation in app.js. */ ?>
<section class="hx-hero">
  <div class="wrap hx-hero__grid">
    <div class="hx-hero__text">
      <h1>Your world, shared with <span class="em">the people you choose</span>.</h1>
      <p class="hx-hero__lede">
        A social network without ads, ad trackers or algorithms deciding what you see.
        Your messages, photos and files are end-to-end encrypted, so only the people you're talking to can read them.
      </p>
      <div class="hx-hero__cta">
        <a class="btn btn--primary btn--lg" href="<?= e(url('/login')) ?>">Create your free account</a>
        <a class="btn btn--ghost btn--lg" href="<?= e(url('/transparency')) ?>">How we protect you</a>
      </div>
      <ul class="hx-trust">
        <li><?= icon('check', '', 13) ?> No ads</li>
        <li><?= icon('check', '', 13) ?> No password needed</li>
        <li><?= icon('check', '', 13) ?> Built in North Macedonia</li>
      </ul>
    </div>

    <div class="hx-hero__media" aria-hidden="true">
      <div class="hx-window" data-reveal="1">
        <div class="hx-window__bar">
          <span class="hx-dots"><i></i><i></i><i></i></span>
          <span class="hx-window__title">Messages</span>
        </div>
        <div class="hx-chat">
          <div class="hx-chat__head">
            <span class="hx-ava hx-ava--a">A</span>
            <span><strong>Ana Petrova</strong><small>Active now</small></span>
          </div>
          <div class="hx-chat__body">
            <div class="hx-msg hx-msg--in">Are you coming to the lake on Saturday?</div>
            <div class="hx-msg hx-msg--out">Yes! Bringing the camera 📷</div>
            <div class="hx-photo hx-msg--out"><span class="hx-photo__sun"></span><span class="hx-photo__hill"></span></div>
            <div class="hx-msg hx-msg--in">That light is unreal. Save me a spot</div>
            <div class="hx-seen">Seen</div>
          </div>
          <div class="hx-chat__foot"><?= icon('lock', '', 12) ?> Messages, photos and files are end-to-end encrypted.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="hx-strip wrap" aria-label="EMChat Media in numbers">
  <div><b>0</b><span>ads, ever</span></div>
  <div><b>0</b><span>ad trackers or pixels</span></div>
  <div><b>100%</b><span>chronological feed</span></div>
  <div><b>E2E</b><span>encrypted messages</span></div>
</section>

<section class="hx-e2e wrap" data-reveal-scroll>
  <div class="hx-e2e__head">
    <p class="hx-eyebrow">End-to-end encryption</p>
    <h2>Private by design. Encrypted by default.</h2>
    <p>Every message, photo, video and file is locked on your device before it leaves. Our servers only ever hold the scrambled version, so not even we can read your conversations.</p>
    <p class="hx-e2e__links"><a href="https://github.com/emchatsocial/emchat" rel="noopener" target="_blank">Read the source code on GitHub &rarr;</a> <span aria-hidden="true">&middot;</span> <a href="<?= e(url('/transparency')) ?>">How it works</a></p>
  </div>
  <div class="hx-flow" aria-hidden="true">
    <div class="hx-flow__node">
      <span class="hx-flow__label">On your phone</span>
      <div class="hx-msg hx-msg--out">See you at 9 🎉</div>
    </div>
    <div class="hx-flow__line"><span></span></div>
    <div class="hx-flow__node hx-flow__node--server">
      <span class="hx-flow__label"><?= icon('lock', '', 12) ?> On our servers</span>
      <code class="hx-cipher">e2e2:K9fQx2hT0qLm7vZbR4nWe8sJd1YcUa6p…</code>
    </div>
    <div class="hx-flow__line"><span></span></div>
    <div class="hx-flow__node">
      <span class="hx-flow__label">On their phone</span>
      <div class="hx-msg hx-msg--in">See you at 9 🎉</div>
    </div>
  </div>
  <ul class="hx-e2e__points">
    <li><strong>On by default</strong><span>No setup, no switches. Chats, group chats and attachments are all covered.</span></li>
    <li><strong>We don't scan messages</strong><span>There's nothing to scan: we only store what we can't read.</span></li>
    <li><strong>Location removed from photos</strong><span>Hidden GPS data is stripped on your device, before encryption.</span></li>
    <li><strong>Your keys, your backup</strong><span>An optional backup PIN keeps your history safe if you switch browsers.</span></li>
  </ul>
</section>

<section class="hx-bento wrap">
  <h2 class="hx-section-title" data-reveal-scroll>Everything you need. Nothing that uses you.</h2>
  <div class="hx-bento__grid">
    <article class="hx-tile hx-tile--wide" data-reveal-scroll>
      <div class="hx-tile__text">
        <h3>A feed in real order</h3>
        <p>Posts from the people you follow, newest first. Nothing ranked to keep you scrolling, and nothing from strangers mixed in. When you've seen it all, you're done.</p>
      </div>
      <div class="hx-feed" aria-hidden="true">
        <div class="hx-post"><span class="hx-ava hx-ava--b">L</span><span class="hx-post__lines"><b>Luka</b><i style="width:82%"></i><i style="width:56%"></i></span><em>2m</em></div>
        <div class="hx-post"><span class="hx-ava hx-ava--c">M</span><span class="hx-post__lines"><b>Mira</b><i style="width:70%"></i></span><em>14m</em></div>
        <div class="hx-post"><span class="hx-ava hx-ava--a">A</span><span class="hx-post__lines"><b>Ana</b><i style="width:88%"></i><i style="width:40%"></i></span><em>1h</em></div>
      </div>
    </article>

    <article class="hx-tile" data-reveal-scroll>
      <div class="lp-scan hx-scan" data-scan>
        <div class="lp-scan__top"><span class="lp-scan__pulse"></span><span data-scan-label>Checking this page for ad tracking</span></div>
        <div class="lp-scan__bar"><i data-scan-bar></i></div>
        <ul class="lp-scan__list">
          <li data-scan-item><span class="lp-scan__mark"><?= icon('check', '', 12) ?></span> No Meta or TikTok pixels</li>
          <li data-scan-item><span class="lp-scan__mark"><?= icon('check', '', 12) ?></span> No advertising SDKs</li>
          <li data-scan-item><span class="lp-scan__mark"><?= icon('check', '', 12) ?></span> No ad network requests</li>
          <li data-scan-item><span class="lp-scan__mark"><?= icon('check', '', 12) ?></span> No fingerprinting</li>
        </ul>
        <div class="lp-scan__result"><span><b data-scan-n0>0</b> ad trackers</span><span><b data-scan-n1>1</b> analytics tool</span></div>
      </div>
      <h3>No tracking for ads</h3>
      <p>No ad pixels, no ad SDKs, no fingerprinting. One analytics tool counts visits, and that's it.</p>
    </article>

    <article class="hx-tile" data-reveal-scroll>
      <div class="hx-toggles" aria-hidden="true">
        <div class="hx-toggle"><span>Private account</span><span class="switch__track switch__track--on"></span></div>
        <div class="hx-toggle"><span>Discoverable</span><span class="switch__track"></span></div>
        <div class="hx-toggle"><span>This post</span><span class="hx-pill"><?= icon('users', '', 12) ?> Followers</span></div>
      </div>
      <h3>Privacy you control</h3>
      <p>Lock your account, approve followers, and choose who sees each post: everyone, followers, or only you.</p>
    </article>

    <article class="hx-tile" data-reveal-scroll>
      <div class="hx-files" aria-hidden="true">
        <div class="hx-file"><span>📷</span><b>lake-saturday.jpg</b><em><?= icon('lock', '', 11) ?></em></div>
        <div class="hx-file"><span>🎬</span><b>birthday.mp4</b><em><?= icon('lock', '', 11) ?></em></div>
        <div class="hx-file"><span>📄</span><b>trip-plan.pdf</b><em><?= icon('lock', '', 11) ?></em></div>
      </div>
      <h3>Group chats and files</h3>
      <p>Photos, videos and files in one-to-one and group chats, all encrypted. Message requests keep strangers out of your inbox.</p>
    </article>

    <article class="hx-tile" data-reveal-scroll>
      <div class="hx-profile" aria-hidden="true">
        <span class="hx-ava hx-ava--lg hx-ava--c">Y</span>
        <b>Your Name</b><small class="lp-mono">emchat.social/@you</small>
        <span class="hx-profile__stats"><span><b>128</b> posts</span><span><b>342</b> followers</span></span>
      </div>
      <h3>A profile worth sharing</h3>
      <p>Your photo, bio, links and posts on one clean page with its own address.</p>
    </article>
  </div>
</section>

<section class="hx-own wrap" data-reveal-scroll>
  <div>
    <h2>Your data stays yours</h2>
    <p>Export everything you've shared in one click, or delete your account and everything with it, any time, from Settings. No emails to support, no waiting period.</p>
  </div>
  <a class="btn btn--ghost" href="<?= e(url('/privacy')) ?>">Read our Privacy Policy</a>
</section>

<section class="hx-cta wrap" data-reveal-scroll>
  <div class="hx-cta__panel">
    <h2>Your people. Nothing else.</h2>
    <p>Free forever. Sign in with just your email, no password to remember.</p>
    <div class="hx-hero__cta hx-cta__buttons">
      <a class="btn btn--primary btn--lg" href="<?= e(url('/login')) ?>">Sign up now</a>
      <a class="btn btn--ghost btn--lg" href="<?= e(url('/explore')) ?>">Find people to follow</a>
    </div>
  </div>
</section>
