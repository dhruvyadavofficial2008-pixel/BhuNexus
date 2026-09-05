# Real-Time National Land Acquisition & Management System

A working prototype built exactly on your stack: **HTML/CSS/JS + PHP + MySQL**,
with **Leaflet.js** for the GIS map, **Chart.js** for the admin dashboard,
and a transparent **AI-assisted risk scoring** feature.

---

## 0. What changed in this update

- **New theme**: `assets/css/style.css` was redesigned (Poppins/Inter fonts, gradient navbar with active-link highlighting, card shadows/hover states, refined badges, forms, and tables). No page markup structure was broken — all existing pages automatically pick up the new look.
- **New page — `add_parcel.php`**: lets a logged-in user (citizen or admin) add their own parcel and draw its boundary directly on a Leaflet map. Area (in acres) and coordinates are calculated automatically from the drawn shape, but remain editable. Linked from the navbar ("➕ Add Parcel") and from both dashboards.
- **`assets/js/add_parcel.js`**: the map-drawing logic (Leaflet.draw), including a free place-search (OpenStreetMap Nominatim) to jump to a location before drawing.
- **`migration.sql`**: optional database migration that adds a `boundary_geojson` column to `parcels` so the drawn shape itself (not just its centroid) is saved and re-displayed on the GIS map. The app auto-detects whether this column exists, so everything still works if you skip it — you'll just get the point + area without the saved shape outline.
- `map.php`/`map.js` now render a parcel's drawn boundary polygon (if saved) in addition to its marker.

### WAMP setup notes
This project was originally written against XAMPP's MySQL defaults, which are **identical** to WAMP's defaults (`root` user, empty password, `localhost`, port 3306) — so `config.php` needs **no changes** to run under WAMP.

In phpMyAdmin, you only need to:
1. Import `schema.sql` once (Database → SQL tab → paste contents → Go) if you haven't already — this creates `land_acquisition_db` with all tables and demo data.
2. **Optional:** run `migration.sql` the same way to add the `boundary_geojson` column and enable saving drawn parcel boundaries on the map.

If your WAMP MySQL uses a different root password, update `$DB_PASS` in `config.php` accordingly.

---

## 1. What's in this folder

```
land-acquisition-system/
├── schema.sql                 # MySQL database + demo data (import this first)
├── config.php                 # DB connection (edit if your MySQL password differs)
├── index.php                  # Login page
├── register.php               # Citizen registration
├── logout.php
├── navbar.php                 # Shared navigation bar
├── dashboard.php              # Citizen dashboard (their parcels, status)
├── admin_dashboard.php        # Admin dashboard (stats + Chart.js graphs)
├── parcel_details.php         # Parcel info, timeline, documents, compensation, grievances
├── map.php                    # Interactive Leaflet/OpenStreetMap GIS view
├── grievance.php              # Grievance list (citizen: own; admin: all + resolve)
├── risk_analysis.php          # AI-assisted risk scoring screen
├── api/
│   ├── get_parcels.php        # JSON feed for the map
│   └── get_stats.php          # JSON feed for the admin charts
├── assets/
│   ├── css/style.css
│   └── js/map.js, dashboard-charts.js
├── ai/
│   └── risk_service.py        # OPTIONAL separate Python microservice version
└── uploads/                   # Where uploaded documents would be stored
```

**Login credentials (all demo passwords are `password123`):**
- Admin: `admin@land.gov`
- Citizen: `ramesh@example.com` or `sita@example.com`

---

## 2. Which apps to install, and how to use them

You only need **three programs** on your laptop. Install them in this order.

### Step 1 — XAMPP (your local server + MySQL)
1. Download from **apachefriends.org** and install it (Windows/Mac/Linux).
2. Open the **XAMPP Control Panel** and click **Start** next to both
   **Apache** and **MySQL**. Both rows should turn green.
3. Find your XAMPP install folder, and inside it open the `htdocs` folder
   (e.g. `C:\xampp\htdocs` on Windows).
4. Copy the entire `land-acquisition-system` folder into `htdocs`.

### Step 2 — phpMyAdmin (comes bundled with XAMPP — sets up your database)
1. With Apache + MySQL running, open a browser and go to:
   `http://localhost/phpmyadmin`
2. Click the **Import** tab.
3. Click **Choose File**, select `schema.sql` from this project, then click **Go**.
4. You should now see a new database called `land_acquisition_db` in the left
   sidebar, with tables already filled with demo data.
   *(If you prefer a desktop GUI instead of the browser, MySQL Workbench works
   the same way — connect to `localhost`, then run schema.sql as a script.)*

### Step 3 — VS Code (to view/edit the code)
1. Download from **code.visualstudio.com** and install it.
2. In VS Code, go to **File → Open Folder** and open your copy of
   `land-acquisition-system` inside `htdocs`.
3. Install the **"PHP Intelephense"** extension (optional, for autocomplete).
4. You don't need to "run" anything in VS Code — Apache (from XAMPP) is what
   actually serves the pages. VS Code is just for editing.

### Step 4 — Open the website
1. In your browser, go to: `http://localhost/land-acquisition-system/`
2. You should land on the login page. Log in with one of the demo accounts above.
3. That's it — the whole system is now running on your laptop.

### Optional — the standalone Python AI service
Only do this if you specifically want to demo a *separate* AI microservice
(instead of the built-in PHP scoring, which already works with zero setup):
1. Install Python from **python.org** if you don't have it.
2. Open a terminal in the `ai/` folder and run:
   ```
   pip install flask
   python risk_service.py
   ```
3. It starts at `http://localhost:5000`. You would then modify
   `risk_analysis.php` to call it with `curl` instead of computing the score
   directly — the file has example code in its comments.
4. **For a hackathon, skip this** unless a judge specifically asks "is there
   a real separate AI component?" — the built-in version already demonstrates
   the same decision-support logic and is much less likely to break on stage.

### GitHub (version control)
1. Create a free account at **github.com**.
2. Create a new repository, e.g. `land-acquisition-system`.
3. In VS Code's left sidebar, click the **Source Control** icon, click
   **Initialize Repository**, then **Publish Branch** — it will push your
   code to GitHub. (Alternatively install **Git** from git-scm.com and use
   `git init`, `git add .`, `git commit -m "initial"`, `git push`.)

### Figma (for planning screens before/alongside coding)
1. Create a free account at **figma.com** (browser-based, no install needed).
2. Create a new design file and sketch your key screens (login, dashboard,
   map, timeline) as simple boxes/wireframes before or alongside building
   the real HTML/CSS — useful for team alignment, not required for the demo.

---

## 3. Deployment plan (for judges / going beyond localhost)

For a hackathon, **running it on your own laptop via XAMPP is normal and
expected** — you don't need to deploy it publicly. If you do want a live link:

| Option | What it needs | Notes |
|---|---|---|
| **InfinityFree / 000webhost** | Free PHP+MySQL hosting | Upload files via their File Manager or FTP; import schema.sql via their phpMyAdmin. Easiest free option for a PHP+MySQL app. |
| **Railway / Render** | Docker or PHP buildpack | More setup, but gives a real public URL and logs. |
| **A cheap VPS (DigitalOcean, etc.)** | Manual LAMP stack setup | Only worth it if you need it running long-term after the hackathon. |

For a same-day hackathon demo, **localhost + XAMPP is the safest choice** —
no internet dependency, no risk of a host going down mid-demo.

---

## 4. PPT tools & how to use them

Pick **one** of these depending on how much time you have left:

1. **Canva** (recommended, fastest) — canva.com → search "Hackathon Pitch
   Deck" template → replace text/images → Share → Download as PowerPoint or PDF.
2. **Microsoft PowerPoint / Google Slides** — use a clean, minimal built-in
   theme; avoid busy templates so screenshots of your app stand out.
3. **Gamma.app** — type a short outline of your project and it auto-generates
   a slide deck you can then edit; fastest option if you're short on time.

### Suggested slide order
1. **Title** — project name, team name, one-line pitch.
2. **Problem** — land acquisition today is slow, disputes are opaque, and
   citizens can't track their own case.
3. **Solution overview** — one architecture diagram: Citizen/Admin → HTML/CSS/JS
   → PHP → MySQL (+ optional Python risk service).
4. **Key features** — the 8-item feature list from your spec, as icons/bullets.
5. **AI-assisted risk analysis** — show the formula and one example output
   (screenshot of the risk_analysis.php result card).
6. **Live demo** — a single slide that just says "Live Demo" (switch to the
   browser here, following the flow in section 5 below).
7. **Tech stack** — the table from your spec, simplified into a single row of
   logos (VS Code, XAMPP, MySQL, PHP, Leaflet, Chart.js, Python, GitHub, Figma).
8. **Impact / next steps** — what a real deployment (SMS notifications, real
   ML risk model, state-wide rollout) would look like.

Keep text minimal on every slide — the live demo does the heavy lifting.

---

## 5. Presentation / demo guidance

Follow the flow you already outlined — it's a good one. Concretely, with this
build:

1. **Login as admin** → `admin@land.gov` / `password123`.
2. **Show the admin dashboard** → point out the stat cards and the three
   Chart.js graphs (parcels by stage, compensation status, grievance status).
3. **Open the GIS map** (`map.php`) → click a marker → narrate what the
   popup shows (owner, project, stage).
4. Click **"View full details"** from the popup → lands on `parcel_details.php`
   → walk through owner/project/status.
5. **Show the acquisition timeline** on the same page (Identify → Survey →
   Notify → Verify → Acquire → Compensate → Monitor) — point out which
   stages are marked done.
6. **Show compensation and grievances** — still the same page, scroll down.
7. **Go to Risk Analysis** (`risk_analysis.php`) → select a project → click
   **Run Risk Analysis** → narrate the formula as it runs.
8. **Show the recommendation** that appears (e.g. "HIGH RISK — prioritize
   dispute resolution...").
9. **Log out, log back in as a citizen** (`ramesh@example.com`) → show
   `dashboard.php` → open the same parcel from the citizen's side to show
   it's the same underlying record, viewed with restricted access.

### Presentation tips
- Rehearse the click-path once beforehand so you're not hunting for buttons live.
- Have the demo running locally *before* you start talking — don't start
  XAMPP on stage.
- If Wi-Fi is unreliable, note that Leaflet's map tiles need internet
  (OpenStreetMap) — everything else works fully offline on localhost.
- Say **"AI-assisted decision support"**, not "AI prediction" — you're using
  a transparent scoring formula, not a trained model, and being precise about
  that reads as more credible to judges, not less.
- Keep the PPT for the 60 seconds before/after the demo (problem statement
  and impact) — let the software speak for itself in the middle.
