GHR AGENT WEBSITE: BLUEHOST SETUP

Files (all go in the domain's public_html folder):
  index.html
  .htaccess             compression, caching, optional HTTPS redirect
  api/listings.php      live MLS® listings from gethomerealty.ca
  api/projects.php      GHR featured pre-construction projects
  api/lead.php          emails consultation-form enquiries to the agent
  images/               agent photo, GHR logos, city photos
Needs PHP 7.4+ with cURL (standard on Bluehost).

UPLOAD
1. Bluehost > Websites > (the domain) > Settings > File Manager (or cPanel > File Manager).
2. Open public_html. Back up or remove any old default files (e.g. a placeholder index.html / index.php).
3. Upload anand-pericherla-website-bluehost.zip, right-click it > Extract into public_html, then delete the zip.
   index.html must sit directly in public_html (not in a sub-folder).
4. File Manager > Settings > "Show hidden files" to confirm .htaccess is there.
5. Bluehost > Advanced > MultiPHP Manager: set the domain to PHP 8.1 or newer.

SSL / HTTPS
6. Bluehost > Websites > Settings > Security > Free SSL: make sure it is On / Active.
7. Then edit .htaccess and remove the "#" from the 3 RewriteEngine/RewriteCond/RewriteRule lines.

CONSULTATION FORM EMAIL
8. Bluehost > Email: create noreply@<domain> (any password; it only sends).
9. In api/lead.php set FROM_EMAIL = 'noreply@<domain>' (TO_EMAIL is already Anand's address).
10. Submit a test enquiry on the live site and check Anand's inbox (and spam folder).

CHECK THE LIVE DATA
11. Open https://<domain>/api/listings.php?city=Mississauga  -> should show JSON with "items".
12. Open https://<domain>/api/projects.php                   -> should show JSON with "items".
    If either shows an error, gethomerealty.ca is blocking server requests; ask the
    gethomerealty.ca developer / AgentRoof to allow the agent domain. Until then the site
    shows the saved listings/projects instead.

PROFILE
Edit DEFAULT_AGENT near the top of the <script> in index.html
(name, phone, email, photo, cities, languages, about text, reviews).

COMPLIANCE
Listings: active only (For Sale / For Lease); sold data is never requested.
Each card shows the listing brokerage and links to the full listing on
gethomerealty.ca/property-details/<slug>?source=<agent-slug>.
Before launch, confirm with TRREB/PropTx (dataagreements@trreb.ca) and AgentRoof
that showing listings on agent domains is covered by GHR's data agreements.
