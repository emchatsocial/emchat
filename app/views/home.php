<?php /* Homepage. Styles: the "px-" block at the end of assets/css/app.css. No glows or floating elements by design. */
$gh = 'https://github.com/emchatsocial/emchat'; ?>
<section class="px-hero">
  <div class="wrap px-hero__text">
    <p class="px-kicker">End-to-end encrypted social network</p>
    <h1>Your world, shared with <span class="em">the people you choose</span>.</h1>
    <p class="px-hero__lede">No ads. No algorithm. No one reading your messages, not even us. EMChat is the private place for your friends, your family and your team.</p>
    <div class="px-cta">
      <a class="btn btn--primary btn--lg" href="<?= e(url('/login')) ?>">Create your free account</a>
      <a class="btn btn--ghost btn--lg" href="<?= e(url('/transparency')) ?>">How we protect you</a>
    </div>
    <p class="px-hero__note">Free forever. Sign in with your email, no password needed.</p>
  </div>

  <div class="wrap">
    <div class="px-app" aria-hidden="true" data-reveal-scroll>
      <div class="px-app__bar"><span class="px-dots"><i></i><i></i><i></i></span><span class="px-app__url"><?= icon('lock', '', 11) ?> emchat.social</span></div>
      <div class="px-app__body">
        <nav class="px-side">
          <span class="px-side__brand">EMCHAT</span>
          <span class="is-on"><?= icon('home', '', 16) ?> Home</span>
          <span><?= icon('compass', '', 16) ?> Explore</span>
          <span><?= icon('mail', '', 16) ?> Messages <b>2</b></span>
          <span><?= icon('bell', '', 16) ?> Notifications</span>
          <span><?= icon('user', '', 16) ?> Profile</span>
          <span><?= icon('settings', '', 16) ?> Settings</span>
        </nav>
        <div class="px-feed">
          <div class="px-feed__head">Following <small>Newest first</small></div>
          <article class="px-post">
            <span class="px-ava px-ava--a">M</span>
            <div><p class="px-post__who"><b>Mira Koleva</b> <span>@mira · 4m</span></p>
              <p>First swim of the season. The lake was freezing and absolutely worth it.</p>
              <div class="px-photo"><span class="px-photo__sun"></span><span class="px-photo__hill"></span></div>
              <p class="px-post__meta"><?= icon('heart', '', 14) ?> 24 <?= icon('comment', '', 14) ?> 6</p></div>
          </article>
          <article class="px-post">
            <span class="px-ava px-ava--b">L</span>
            <div><p class="px-post__who"><b>Luka Petrov</b> <span>@luka · 18m</span></p>
              <p>New studio is finally finished. Open house on Saturday from 4pm, everyone welcome.</p>
              <p class="px-post__meta"><?= icon('heart', '', 14) ?> 41 <?= icon('comment', '', 14) ?> 12</p></div>
          </article>
          <article class="px-post">
            <span class="px-ava px-ava--c">A</span>
            <div><p class="px-post__who"><b>Ana Dimitrova</b> <span>@ana · 1h</span></p>
              <p>Grandma's recipe, finally nailed it.</p>
              <p class="px-post__meta"><?= icon('heart', '', 14) ?> 18 <?= icon('comment', '', 14) ?> 3</p></div>
          </article>
        </div>
        <div class="px-chat">
          <div class="px-chat__head"><span class="px-ava px-ava--c">A</span><span><b>Ana Dimitrova</b><small>Active now</small></span></div>
          <div class="px-chat__body">
            <p class="px-msg px-msg--in">Are you coming on Saturday?</p>
            <p class="px-msg px-msg--out">Yes, I'll bring the cake</p>
            <p class="px-file"><?= icon('paperclip', '', 14) ?> <span><b>recipe.pdf</b><small>184 KB</small></span></p>
            <p class="px-msg px-msg--in">Perfect, see you at 9</p>
            <p class="px-seen">Seen</p>
          </div>
          <p class="px-chat__lock"><?= icon('lock', '', 12) ?> End-to-end encrypted</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="px-principles wrap" aria-label="What EMChat stands for">
  <div><?= icon('lock', '', 20) ?><h3>Encrypted by default</h3><p>Messages, photos and files are locked on your device before they leave it.</p></div>
  <div><?= icon('shield', '', 20) ?><h3>No ads, ever</h3><p>No ads in your feed, no profiling, no selling of data. Nothing to sell you.</p></div>
  <div><?= icon('home', '', 20) ?><h3>No algorithm</h3><p>The people you follow, newest first. When you've seen it all, you're done.</p></div>
  <div><?= icon('globe', '', 20) ?><h3>Open to inspect</h3><p>Our source code is public, so every promise on this page can be checked.</p></div>
</section>

<section class="px-feature wrap" data-reveal-scroll>
  <div class="px-feature__text">
    <p class="px-num">01 &mdash; Messaging</p>
    <h2>Every message encrypted. Even from us.</h2>
    <p>Chats, group chats, photos, videos and files are encrypted on your device before they're sent. Our servers only ever store the scrambled version, so no one at EMChat can read them, and we don't scan them.</p>
    <ul class="px-ticks">
      <li><?= icon('check', '', 14) ?> On by default, nothing to set up</li>
      <li><?= icon('check', '', 14) ?> Location data removed from photos before encryption</li>
      <li><?= icon('check', '', 14) ?> Optional backup PIN to restore your chats on a new browser</li>
    </ul>
  </div>
  <div class="px-visual">
    <div class="px-flow" aria-hidden="true">
      <div class="px-flow__step"><span class="px-flow__label">Your phone</span><p class="px-msg px-msg--out">See you at 9</p></div>
      <div class="px-flow__arrow"><?= icon('lock', '', 14) ?></div>
      <div class="px-flow__step"><span class="px-flow__label">Our servers</span><code>e2e2:K9fQx2hT0qLm7vZbR4nWe8sJd1YcUa6p…</code></div>
      <div class="px-flow__arrow"><?= icon('lock', '', 14) ?></div>
      <div class="px-flow__step"><span class="px-flow__label">Their phone</span><p class="px-msg px-msg--in">See you at 9</p></div>
    </div>
  </div>
</section>

<section class="px-feature px-feature--flip wrap" data-reveal-scroll>
  <div class="px-feature__text">
    <p class="px-num">02 &mdash; Feed</p>
    <h2>A feed that respects your time.</h2>
    <p>Your home feed shows the people you follow, in the order they posted. Nothing is ranked to keep you scrolling, and nothing from strangers is mixed in. It has an end.</p>
    <ul class="px-ticks">
      <li><?= icon('check', '', 14) ?> Every post reaches every follower</li>
      <li><?= icon('check', '', 14) ?> No suggested posts, no sponsored posts</li>
    </ul>
  </div>
  <div class="px-visual">
    <div class="px-card px-mini-feed" aria-hidden="true">
      <div class="px-mini"><span class="px-ava px-ava--b">L</span><span class="px-lines"><b>Luka</b><i style="width:80%"></i><i style="width:52%"></i></span><em>2m</em></div>
      <div class="px-mini"><span class="px-ava px-ava--a">M</span><span class="px-lines"><b>Mira</b><i style="width:66%"></i></span><em>14m</em></div>
      <div class="px-mini"><span class="px-ava px-ava--c">A</span><span class="px-lines"><b>Ana</b><i style="width:86%"></i><i style="width:38%"></i></span><em>1h</em></div>
      <p class="px-caught"><?= icon('check', '', 14) ?> You're all caught up</p>
    </div>
  </div>
</section>

<section class="px-feature wrap" data-reveal-scroll>
  <div class="px-feature__text">
    <p class="px-num">03 &mdash; Privacy</p>
    <h2>Privacy you set, post by post.</h2>
    <p>Make your account private and approve every follower. Choose who sees each post as you write it: everyone, your followers, or only you. Export or delete everything at any time.</p>
    <ul class="px-ticks">
      <li><?= icon('check', '', 14) ?> Stay out of search engines with one switch</li>
      <li><?= icon('check', '', 14) ?> Block, mute and report in one tap</li>
    </ul>
  </div>
  <div class="px-visual">
    <div class="px-card px-settings" aria-hidden="true">
      <div class="px-setting"><span><b>Private account</b><small>Approve new followers</small></span><span class="switch__track switch__track--on"></span></div>
      <div class="px-setting"><span><b>Discoverable</b><small>Appear in search and on Google</small></span><span class="switch__track"></span></div>
      <div class="px-setting"><span><b>Who can see this post</b><small>Chosen for each post</small></span><span class="px-pill"><?= icon('users', '', 12) ?> Followers</span></div>
    </div>
  </div>
</section>

<section class="px-feature px-feature--flip wrap" data-reveal-scroll>
  <div class="px-feature__text">
    <p class="px-num">04 &mdash; Groups</p>
    <h2>Your team, without WhatsApp.</h2>
    <p>Group chats for your family, your club or your staff, with photos, videos and documents, all encrypted. No private phone numbers to share, and admins decide who's in.</p>
    <ul class="px-ticks">
      <li><?= icon('check', '', 14) ?> Message requests keep strangers out of your inbox</li>
      <li><?= icon('check', '', 14) ?> "Seen" so you know when your message was read</li>
    </ul>
  </div>
  <div class="px-visual">
    <div class="px-card px-group" aria-hidden="true">
      <div class="px-group__head"><span class="px-stack"><span class="px-ava px-ava--a">M</span><span class="px-ava px-ava--b">L</span><span class="px-ava px-ava--c">A</span></span><span><b>Weekend crew</b><small>6 members</small></span></div>
      <p class="px-msg px-msg--in"><span class="px-msg__who">Mira</span>Who's driving on Saturday?</p>
      <p class="px-msg px-msg--in"><span class="px-msg__who">Luka</span>I can take four people</p>
      <p class="px-file px-file--out"><?= icon('paperclip', '', 14) ?> <span><b>route-plan.pdf</b><small>312 KB</small></span></p>
      <p class="px-msg px-msg--out">Sent the route, leaving at 9</p>
    </div>
  </div>
</section>

<section class="px-proof wrap" data-reveal-scroll>
  <div class="px-proof__text">
    <p class="px-num">Verifiable, not just promised</p>
    <h2>Read the code that protects you.</h2>
    <p>EMChat's source code is public. Anyone can check how messages are encrypted, what we store and what we don't, instead of taking our word for it.</p>
    <div class="px-cta">
      <a class="btn btn--ghost" href="<?= e($gh) ?>" rel="noopener" target="_blank">View on GitHub &rarr;</a>
      <a class="btn btn--ghost" href="<?= e(url('/transparency')) ?>">Transparency report</a>
    </div>
  </div>
  <div class="px-code" aria-hidden="true">
    <p class="px-code__file">public/assets/js/e2e.js</p>
<pre><code><span class="c">// encryptV2(): a new random key for every message</span>
<span class="k">var</span> raw = crypto.getRandomValues(<span class="k">new</span> Uint8Array(<span class="n">32</span>));
<span class="k">var</span> msgKey = <span class="k">await</span> subtle.importKey(<span class="s">'raw'</span>, raw, <span class="s">'AES-GCM'</span>, <span class="k">false</span>, [<span class="s">'encrypt'</span>]);
<span class="k">var</span> iv = crypto.getRandomValues(<span class="k">new</span> Uint8Array(<span class="n">12</span>));
<span class="k">var</span> ct = <span class="k">await</span> subtle.encrypt({ name: <span class="s">'AES-GCM'</span>, iv: iv,
  additionalData: aad2(cid, UID, s) }, msgKey,
  enc.encode(JSON.stringify(payload)));</code></pre>
  </div>
</section>

<section class="px-faq wrap" data-reveal-scroll>
  <h2>Questions, answered</h2>
  <div class="px-faq__list">
    <details><summary>Is EMChat really free?</summary><p>Yes. EMChat has no ads and no paid features. You can create an account and use everything at no cost.</p></details>
    <details><summary>Can EMChat read my messages?</summary><p>No. Messages, photos and files are end-to-end encrypted on your device, and we only store the encrypted version. If you report a message, your browser shares that message's text with us so we can review it.</p></details>
    <details><summary>What if I clear my browser or use a new one?</summary><p>Your encryption key lives in your browser. Set a backup PIN in Settings &rarr; Encryption, and you can restore your chats anywhere. Without one, you can start fresh, but earlier encrypted messages won't show on that browser.</p></details>
    <details><summary>Do you track me?</summary><p>Inside your account, no: no ad trackers, ad pixels or fingerprinting, and we never sell data. On our public pages, one analytics tool counts visits, and Google Ads cookies measure our own ads on Google only if you click Accept.</p></details>
    <details><summary>Who is behind EMChat?</summary><p>EMChat Media is built independently in North Macedonia by its founder, Egzon Mehmedi. You can reach him at egzon@emchat.social.</p></details>
    <details><summary>Can I take my data with me?</summary><p>Yes. Export everything you've shared, or delete your account and everything with it, from Settings at any time.</p></details>
  </div>
</section>

<section class="px-final wrap" data-reveal-scroll>
  <h2>Your people. Nothing else.</h2>
  <p>Join in a minute. No password, no ads, no algorithm.</p>
  <div class="px-cta px-cta--center">
    <a class="btn btn--primary btn--lg" href="<?= e(url('/login')) ?>">Sign up now</a>
    <a class="btn btn--ghost btn--lg" href="<?= e(url('/explore')) ?>">Find people to follow</a>
  </div>
</section>
