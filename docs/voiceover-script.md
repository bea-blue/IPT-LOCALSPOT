# LocalSpot: Voiceover Script (about 11 to 12 minutes)

## How to record (read this first)
- Record **5 separate files**, one per section: `1-overview`, `2-framework`, `3-demo`, `4-database`, `5-conclusion`. Any format works (m4a from your phone, mp3, wav).
- Read only the text in quotation blocks (the lines starting with ">"). The **[On screen]** notes tell you what viewers will see. I add those visuals; you don't record anything on screen.
- **Pause about 2 seconds between paragraphs.** I use those pauses to line each scene up with your voice.
- Speak at a normal, relaxed pace. If you stumble, pause, then repeat the whole sentence; I'll cut the mistake.
- Use a quiet room and hold the phone about 20 cm from your mouth.

Times are estimates at a normal speaking pace. The total is about 11 to 12 minutes, which fits the required 10–15.

---

## 1-overview (about 2:15)

**[On screen: title slide]**
> Hi! I'm Bea Louise Uson from BSIT 3A, and this is my final project for Integrative Programming and Technologies: LocalSpot, a content management system where shop owners list their cafés and food spots around Angeles City. It's built on my own PHP framework, SpotCore.

**[On screen: problem and objectives slide]**
> When people look for a café or a place to eat around Angeles City, the details are spread across blogs, delivery apps and social media posts. Much of it is old, and nobody checks that a shop is real. LocalSpot gathers it in one place. My goals were to build the framework from scratch, let shop owners manage their own listings, approve a shop only with proof such as a business permit, help visitors filter places, and integrate a real external API.

**[On screen: Explore page, the categories, then one card]**
> This is the public side. Browsing never needs an account. Visitors see published places as cards, each with a photo, a category, an address, an estimated budget and a link to the menu.

**[On screen: scope slide, who does what]**
> There are three kinds of users. Visitors and members browse places, and with a free account they write, edit or delete their own reviews and can report a review. Shop owners list their own shop, set the prices, hours, payment, photos and menu, upload their proof, and submit it for approval. Staff check the proof and approve, reject or suspend a shop, always with a reason, and hide reported reviews. But staff never edit a shop or a review.

**[On screen: listing data slide]**
> The ten demo listings are real places in Angeles City, taken from public pages. Their real owners aren't on LocalSpot, so they're managed by a clearly labelled test owner account. Budgets are estimates marked "Est." with their basis, and an unknown budget is never treated as free.

---

## 2-framework (about 2:45)

**[On screen: architecture slide, core vs app folders]**
> Let's look at the framework. The core folder is SpotCore, my reusable framework. It has no LocalSpot code in it. The app folder is LocalSpot itself: nine controllers, ten models, five middleware, a Roles class that decides who may do what, and the weather service. Only the public folder is served; configuration, the database files, the logs and private uploads like business permits stay outside it.

**[On screen: code, public/index.php and bootstrap.php]**
> Every request enters through one file, public slash index dot php. This is called the front controller. It loads bootstrap dot php, which registers my own autoloader, reads the private dot env file and loads the configuration. I didn't use Composer's autoloader. The autoloader is a few lines that turn a class name into a file path.

**[On screen: code, routes/web.php]**
> All URLs are defined in one routes file. Each route connects an HTTP method and a path to a controller method. Routes are grouped: the staff panel needs the staff middleware, the owner pages and reviews need a signed-in account, and every form that changes data also needs the CSRF middleware.

**[On screen: code, Router compile function]**
> Inside the router, a path like places slash id becomes a regular expression. I made the id accept only positive whole numbers, so a word like "create" can never be mistaken for an id, and something like places slash zero just returns "not found". If the path exists but the method is wrong, the router answers with error 405.

**[On screen: code, App pipeline and CsrfMiddleware]**
> Middleware works like layers of an onion around the controller. The kernel builds those layers with array reduce. Each middleware can stop the request, for example by redirecting a guest to the login page, or pass it to the next layer. The CSRF middleware checks a secret token on every form. Without the right token, the request is rejected with error 419.

**[On screen: request lifecycle diagram]**
> Here's one complete request. The browser asks for a place page. The front controller creates a Request object. The router matches the route. The middleware runs. The controller asks the models for the place, its photos, menu and reviews, and the WeatherService for the weather. The view escapes every value so it can't run as code. Finally the Response is sent back to the browser.

---

## 3-demo (about 4:15)

**[On screen: search "sisig", then Food Spots, then a ₱400 budget]**
> Now the demo. I'll search for "sisig": the keyword matches the name or the address, so we get one result. The category filter shows three food spots. With a starting budget of four hundred pesos, a place matches when its lowest estimate fits, and places without a confirmed budget are left out, because an unknown price isn't the same as free.

**[On screen: Aling Lucing Sisig page, visit details, photos, menu]**
> This is a place's page. Visit details show the estimated budget and its basis, the hours, the payment options and an "Open in Google Maps" button. Below are the photos in a carousel, each credited, and the menu, where "See more" opens the rest. Menu photos open larger in a lightbox.

**[On screen: weather box, then "temporarily unavailable"]**
> At the side is the Angeles City area weather from the Open-Meteo API, with the time it was fetched and the required credit. It's cached for fifteen minutes and shared by every visitor. If the API is down, the page still works: it shows a recent copy, or says the weather is temporarily unavailable. It never invents weather.

**[On screen: sign-up, then the owner's form with hours and payment]**
> Now a shop owner. I sign up, and the password is stored only as a hash. I click "List your shop" and fill in the details: the prices, the opening hours and the payment options. This is the owner's job, not the staff's.

**[On screen: the rejected .jpg, then the private proof list]**
> To submit, I need proof: a business permit and a photo of the shop. If I upload a script renamed to dot jpg, it's rejected, because the server checks the file's real type from its first bytes. The real files are stored with random names outside the public folder, so only staff and the owner can open them.

**[On screen: photos, menu, then "Waiting for approval"]**
> I add a photo and a menu item, and submit. The shop is now waiting for approval and stays hidden from visitors.

**[On screen: Customer Service dashboard and the check note]**
> Now I'm Customer Service. I can open the permit and add a check note, like "permit name and address match the listing". But there's no approve button for me, and if I try anyway, the server answers 403.

**[On screen: manager decision, approved, then the shop on Explore]**
> A manager decides. Approving publishes the shop. Rejecting or suspending needs a written reason, which the owner sees and can fix. Now the shop is visible to everyone.

**[On screen: Bea's review with Edit and Delete, then reporting the spam review]**
> Back as a member. I write a review, and only I see Edit and Delete on it. There's also a spam review here, so I report it. Anyone signed in can report a review as spam, abusive or off-topic. The owner can report too, but can't delete reviews or review their own shop.

**[On screen: 403 page, then the Review reports page]**
> If a member opens the staff panel, they get "Not allowed". On the Review reports page, a manager hides the spam review or dismisses the report. Nothing is edited or deleted.

**[On screen: staff page and the who-can-do-what table]**
> Finally, the Staff page. Developers manage all staff. Managers can add or remove staff, but only below Developer: they can't create or remove a developer. And nobody can change their own role.

---

## 4-database (about 1:45)

**[On screen: roles slide, who can do what]**
> To sum up the roles: Developers, managers and Customer Service all see submissions, open the proof, add notes and see reports. Only developers and managers approve, reject, suspend or hide reviews. No staff role can create, edit or delete a shop or a review.

**[On screen: ERD]**
> The database has ten tables. Each account has a role. A place belongs to its owner and moves from draft to pending to published, or to rejected or suspended, with the staff reason stored on it. Proof files are recorded in place documents, staff notes in place checks, and reports in review reports. Everything is applied with a migration script, steps A to G, that can be reversed.

**[On screen: PHPUnit result, 46 tests passing]**
> For testing, I used PHPUnit 9.6, installed with Composer as a development tool only. All forty-six tests pass on my XAMPP PHP 8.0, including the roles, the upload checks and every weather failure case. I also ran sixty-four end-to-end checks of the owner and staff flow.

**[On screen: challenges slide]**
> My biggest challenges: my XAMPP runs PHP 8.0, so I avoided newer features. When I added new tables, my laptop showed errors until the migration ran, so I wrote a migration script that applies each change step by step and can undo it. And at first one admin edited every shop, which didn't feel right, because the owner knows the hours and prices best. So I moved control to the owners, and staff only check the proof and approve.

---

## 5-conclusion (about 0:45)

**[On screen: conclusion and future work slide]**
> Building SpotCore taught me what frameworks do behind the scenes. Routing is just pattern matching, middleware is functions wrapped around the controller, and security has to be in every layer, including checking who owns what. This project also taught me that every piece of data needs a source and a date.

> For future work: GCash or Maya payments inside the app, checking permits against the city business registry, emailing owners when their shop is approved or rejected, live open status from the shops, and an embedded map.

**[On screen: thank-you slide]**
> Thank you for watching!
