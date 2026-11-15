<?php
return [
    'title'       => 'How to Remove Location Data From Your Photos Before You Share Them',
    'description' => 'Most phone photos quietly record where they were taken. Here is how to check for that hidden location data and remove it on iPhone, Android, Windows and Mac.',
    'date'        => '2026-11-13',
    'body'        => <<<'HTML'
<p class="prose__lede">Most photos you take on a phone carry a hidden label saying exactly where they were taken, often accurate to a few metres. If you share the original file, you may be sharing your home address with it.</p>

<h2>What is photo location data?</h2>
<p>When your camera saves a photo, it also saves a block of extra information inside the file, called <strong>EXIF metadata</strong>. It usually includes the date and time, the phone model, camera settings and, if location access is turned on for the camera, the <strong>GPS coordinates</strong> of the spot where you took it.</p>
<p>You don't see any of this when you look at the picture, but anyone who gets the original file can read it with free tools or the "info" panel of almost any photo app. A picture of your new sofa, your kids in the garden or your car outside your house can point straight to where you live.</p>

<h2>Does social media remove it for you?</h2>
<p>Many large platforms strip location data from photos when you upload them, but not every app, website, forum or messaging service does. Files sent as attachments by email, or as "documents" in chat apps, are often passed along untouched. The safest habit is to <strong>remove location data yourself</strong> before you share anything you wouldn't want mapped.</p>

<h2>On iPhone</h2>
<p><strong>Remove location from a photo you're sharing:</strong></p>
<ol>
  <li>Open the photo in the Photos app and tap the <strong>Share</strong> button.</li>
  <li>Tap <strong>Options</strong> at the top of the share sheet.</li>
  <li>Turn off <strong>Location</strong>, then tap Done and share as normal.</li>
</ol>
<p><strong>Stop recording location for new photos:</strong> go to <strong>Settings &rarr; Privacy &amp; Security &rarr; Location Services &rarr; Camera</strong> and choose <strong>Never</strong>.</p>

<h2>On Android</h2>
<p>Menus differ a little between phone makers, but in <strong>Google Photos</strong>:</p>
<ol>
  <li>Open the photo and swipe up (or tap the three-dot menu) to see its details.</li>
  <li>Find the location section, tap the edit (pencil) icon and choose <strong>Remove location</strong>.</li>
</ol>
<p><strong>Stop recording location for new photos:</strong> open your <strong>Camera</strong> app's settings and turn off the option called something like <em>Location tags</em>, <em>Save location</em> or <em>GPS tag</em>.</p>

<h2>On Windows</h2>
<ol>
  <li>Right-click the photo file and choose <strong>Properties</strong>.</li>
  <li>Open the <strong>Details</strong> tab and click <strong>Remove Properties and Personal Information</strong> at the bottom.</li>
  <li>Choose <strong>Create a copy with all possible properties removed</strong> and click OK.</li>
</ol>
<p>Share the new copy, not the original.</p>

<h2>On Mac</h2>
<ol>
  <li>Open the photo in <strong>Preview</strong>.</li>
  <li>Choose <strong>Tools &rarr; Show Inspector</strong>, then click the info (i) tab.</li>
  <li>If there's a <strong>GPS</strong> tab, open it and click <strong>Remove Location Info</strong>, then save.</li>
</ol>

<h2>How to check that it worked</h2>
<p>Open the cleaned copy and look at its details again: on iPhone swipe up on the photo, on Android open the details panel, on Windows look at the Details tab, on Mac use Preview's Inspector. If there's no map, no GPS section and no coordinates, the location is gone.</p>

<h2>Other things a photo can give away</h2>
<p>Removing GPS data doesn't hide what's <em>in</em> the picture. Before you post, glance at the background for:</p>
<ul>
  <li>House numbers, street signs and car number plates</li>
  <li>Letters, parcels or screens with your name or address on them</li>
  <li>School logos and uniforms, if the photo includes children</li>
  <li>Distinctive views from your window that make your home easy to find</li>
</ul>

<h2>How EMChat Media handles this</h2>
<p>On EMChat Media, every photo you upload, in a post or in a message, is rebuilt on our server before it's stored. That process throws away the EXIF metadata, including GPS coordinates, so the copy other people see doesn't contain your location. Videos and other files you attach to messages are sent as they are, so it's still worth cleaning those yourself.</p>
HTML,
];
