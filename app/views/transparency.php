<section class="wrap prose">
  <h1>Transparency</h1>
  <p class="prose__lede">This page summarises how EMChat Media handles your data, how the feed works and how the service is run, with references you can check for yourself.</p>

  <h2>Data practices</h2>
  <ul>
    <li>We do not use advertising trackers, advertising SDKs or ad targeting. Google Analytics is used only to measure visits and page popularity.</li>
    <li>We do not sell, rent or share your personal information.</li>
    <li>We do not carry out behavioural profiling. We do not scan messages, and private messages are never read for advertising or used to train models.</li>
  </ul>

  <h2>End-to-end encrypted messages</h2>
  <p>Since November 15, 2026, direct messages are end-to-end encrypted by default, including group chats and the photos, videos and files sent in them. They are encrypted in your browser before they are sent, and only the people in the conversation can decrypt them. Our servers store only the encrypted version.</p>
  <ul>
    <li>Each user has a key pair created in their browser. Messages use a new random key, which is shared with each participant using their public key (elliptic-curve Diffie-Hellman with AES-256-GCM encryption).</li>
    <li>Photos are re-encoded in your browser before they are encrypted, removing location and other metadata.</li>
    <li>An optional backup PIN lets you restore your keys after clearing your browser or on a new browser. The backup is encrypted in your browser with that PIN, and we never receive the PIN.</li>
    <li>A conversation is encrypted once everyone in it has opened EMChat Media since encryption was introduced. Messages sent earlier are not encrypted.</li>
    <li>When you report an encrypted message, your browser shares its text with us so we can review it.</li>
  </ul>

  <h2>How the feed works</h2>
  <p>Your home feed contains posts from accounts you follow, in reverse chronological order. No other content is added, and posts are not ranked by engagement.</p>

  <h2>Post visibility</h2>
  <p>A <strong>Public</strong> post can be viewed by anyone who visits your profile or opens a link to it, including people who do not follow you. A <strong>Followers only</strong> post is limited to your approved followers, and an <strong>Only me</strong> post is visible to you alone. Public posts are not added to other people's home feeds or listed on Explore.</p>
  <p>If your account is public and Discoverable is turned on, search engines may index your profile and your Public posts. Followers only and Only me posts are never indexed, and nothing from a private account is indexed. Turn off Discoverable in Settings to keep your profile and posts out of search engines.</p>

  <h2>Your data and your controls</h2>
  <ul>
    <li>Uploaded photos are re-encoded before storage, which removes embedded EXIF and GPS location data automatically. Photos in messages are processed in your browser, before encryption.</li>
    <li>You can export a copy of your data or permanently delete your account at any time from Settings, without contacting support. Deleting your account also removes your uploaded files and your encryption keys and backups.</li>
    <li>You can manage your encryption backup PIN and keys in Settings &rarr; Encryption.</li>
    <li>Private accounts, post visibility and follower approval are enforced on the server, not only hidden in the interface.</li>
  </ul>

  <h2>Source code</h2>
  <p>EMChat Media's source code is publicly available so that our privacy commitments can be independently verified. The code is source-available rather than open source: you may read it, learn from it and run it for personal or noncommercial purposes, but commercial use requires permission. The full terms are set out in the <a href="https://github.com/emchatsocial/emchat" rel="noopener">repository</a> and its licence.</p>

  <h2>Funding</h2>
  <p>EMChat Media is free to use and is not funded by advertising. There is currently no paid tier. Should paid features be introduced, they will be optional, clearly labelled, and will not involve selling data or displaying advertising.</p>

  <h2>Security</h2>
  <ul>
    <li>Uploaded files are validated by their actual content rather than their file name or extension.</li>
    <li>All traffic is served over HTTPS with modern security headers, including strict transport security, a restrictive content security policy and clickjacking protection.</li>
    <li>Posting and messaging are rate-limited to reduce automated spam and abuse, alongside content moderation.</li>
  </ul>

  <h2>Availability</h2>
  <p>We do not yet publish uptime statistics. Monitoring is being put in place so that this section can report measured figures. If the service is unavailable, visitors are shown a generic error page that does not expose internal details.</p>

  <h2>Contact</h2>
  <p>For questions about anything on this page, please contact <a href="mailto:egzon@emchat.social">egzon@emchat.social</a>.</p>
</section>
