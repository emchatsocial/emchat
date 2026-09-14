<section class="wrap prose">
  <h1>About EMChat Media</h1>
  <p class="prose__lede">EMChat Media is a social network built on a simple trade: you get a place to share and connect, and we don't get to turn your life into an ad-targeting profile.</p>

  <h2>Why we built it</h2>
  <p>Mainstream social platforms make money by keeping you scrolling and selling predictions about your behaviour. That business model shapes every product decision — the ranking, the notifications, the endless feed. We wanted the opposite: software that respects your attention and your data.</p>

  <h2>How EMChat is different</h2>
  <ul>
    <li><strong>No ad trackers.</strong> No Meta Pixel, no advertising SDKs, no selling or sharing your data with advertisers. We use basic visit analytics to see how the site is doing, never to build an ad profile of you.</li>
    <li><strong>No engagement ranking.</strong> Your timeline is the people you follow, newest first.</li>
    <li><strong>Passwordless.</strong> You sign in with a one-time email link, so there's no password to breach.</li>
    <li><strong>Photo privacy by default.</strong> Uploaded images are re-encoded server-side, which strips EXIF and GPS metadata.</li>
    <li><strong>Real controls.</strong> Private accounts, per-post visibility, block, mute, full data export, and one-click account deletion.</li>
  </ul>

  <h2>Profile cards</h2>
  <p>Every member gets a rich profile card — profile photo, a bio with working links and mentions, follower and following counts, and a follow button — that you can share as its own page at <code>emchat.social/@yourname/card</code>.</p>

  <h2>Where we're based</h2>
  <p>EMChat Media is built and run independently from North Macedonia by founder <strong>Egzon Mehmedi</strong>. There's no outside ad network or data broker behind it — just a privacy-first, independently-run product built to treat your data as yours.</p>

  <h2>Contact</h2>
  <p>Questions, feedback, or press: <a href="mailto:egzon@emchat.social">egzon@emchat.social</a>.</p>

  <h2>Frequently asked questions</h2>
  <div class="faq">
    <?php foreach ($faqs as [$q, $a]): ?>
      <details class="faq__item">
        <summary><?= e($q) ?></summary>
        <p><?= e($a) ?></p>
      </details>
    <?php endforeach; ?>
  </div>

  <p><a class="btn btn--primary btn--lg" href="<?= e(url('/login')) ?>">Join EMChat Media</a></p>
</section>
