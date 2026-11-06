# Developer Guide

This guide tells you **which file to open** for any change you want to make.

Line numbers are correct as of November 2026. If a line has moved, search
the file for the name shown in `code`.

Short names used below:

- **Plugin** = `plugins/maapkathi-core/`
- **Theme** = `themes/maapkathi-theme/`

---

## Part 1: The big idea (read this first)

The project has two halves:

| Half | What it does | Example |
|---|---|---|
| **Plugin** | Stores and manages content | "Save this project's location" |
| **Theme** | Shows content on the website | "Show the location under the title" |

**Rule:** if you're changing *what is saved*, edit the plugin.
If you're changing *how it looks*, edit the theme.

---

## Part 2: Try the admin panel first

Many changes need no code. Log in to WordPress and open the **Maapkathi**
menu on the left:

| You want to… | Go to |
|---|---|
| Change text or headings on the site | Maapkathi → **Site Text** |
| Change colours, fonts, dark mode or animations | Maapkathi → **Appearance** |
| Hide, show or reorder homepage sections | Maapkathi → **Sections** |
| Edit the footer (links, social icons) | Maapkathi → **Footer** |
| Change the big banner at the top of the homepage | Maapkathi → **Hero** |
| Add projects, services, team members, FAQs and so on | The matching item in the Maapkathi menu |

Only open the code if the admin panel can't do what you need.

---

## Part 3: "I want to change…" → open this file

### How the site looks

| I want to change… | Open this file |
|---|---|
| Colours and spacing (design tokens) | Theme `assets/css/tokens.css` (light mode at line 7, dark mode at line 50) |
| General styling (text, header, footer, layout) | Theme `assets/css/base.css` |
| Homepage section styling | Theme `assets/css/sections.css` |
| Animations | Theme `assets/css/motion.css` and `assets/js/motion-engine.js` |
| The header and menu | Theme `parts/header.php` and `parts/nav.php` |
| The footer | Theme `parts/footer.php` (it picks `parts/footer-classic.php` or `parts/footer-modern.php`) |
| The top banner (hero) | Theme `parts/hero.php` |

### Pages

| Page | Open this file (in Theme) |
|---|---|
| Homepage | `front-page.php` |
| About | `page-about.php` |
| Contact | `page-contact.php` |
| Services | `page-services.php` |
| Team | `page-team.php` |
| All projects | `archive-mk_project.php` |
| One project | `single-mk_project.php` |
| One service | `single-mk_service.php` |
| Blog list / blog post | `home.php` / `single.php` |
| Page not found | `404.php` |

### Homepage sections

Each homepage section has its own file in Theme `parts/sections/`:

`awards.php`, `categories.php`, `clients.php`, `cta.php`, `faq.php`,
`projects.php`, `services.php`, `stats.php`, `tagline.php`, `team.php`,
`testimonials.php`, `values.php`

For example, to change how the FAQ section looks, open
`parts/sections/faq.php`.

### Features

| Feature | Open this file (in Plugin) |
|---|---|
| Contact form | `src/Inquiries/Inquiries.php`, the `handle_submit()` function at line 36 |
| Emails sent by the site | `src/Mail/Mailer.php` |
| Newsletter sign-up | `src/Footer/Subscribers.php` |
| Map | `src/Map/MapSettings.php` |
| Icons | `src/Icons/IconLibrary.php` |
| Logos | `src/Support/Logos.php` |
| SEO (page titles, Google preview) | `src/Seo/Seo.php`, the `render_meta()` function at line 45 |
| Login security | `src/Support/Security.php` |
| User roles and permissions | `src/Roles/Roles.php` (permission names at lines 26–31) |
| The admin menu | `src/Admin/Menu.php` (menu items start at line 70) |
| File and video uploads | `src/Rest/UploadController.php` |
| Server settings (email, upload limits) | `wp-config.php` (see `docs/CONFIGURATION.md`) |

---

## Part 4: Step-by-step recipes

### Recipe A: Change some text on the site

1. Try **Maapkathi → Site Text** first.
2. If the text you want isn't listed there, open
   Plugin `src/Support/SiteText.php`. The list starts at line 29 and is
   grouped by page: **Home** at line 31, **Services** at line 107,
   **About** at line 117, **Contact** at line 145.
3. Add a new entry by copying an existing one:
   ```php
   array(
       'key'   => 'home_my_new_text',
       'label' => __( 'My new text', 'maapkathi' ),
   ),
   ```
4. Show it in a theme file:
   ```php
   <?php echo esc_html( mk_text( 'home_my_new_text' ) ); ?>
   ```

### Recipe B: Add a new field (for example, "Architect" on projects)

1. Open Plugin `src/Fields/MetaBoxes.php`.
2. Find your content type. Projects start at line 34, services at line 77,
   team members at line 106 and FAQs at line 231.
3. Add an entry to its list:
   ```php
   array(
       'key'   => 'mk_architect',
       'label' => __( 'Architect', 'maapkathi' ),
       'type'  => 'text',
   ),
   ```
   Allowed types are `text`, `textarea`, `checkbox`, `number`, `date`,
   `url`, `gallery`, `icon` and `media` (they're handled at lines 399–420).
4. The field now shows up on the edit screen and saves by itself.
5. Show it on the site, for example in Theme `single-mk_project.php`:
   ```php
   <?php echo esc_html( mk_meta( get_the_ID(), 'mk_architect' ) ); ?>
   ```

### Recipe C: Add a new content type (for example, "Careers")

1. Open Plugin `src/PostTypes/PostTypes.php`. The list starts at line 30.
2. Copy an existing entry, such as `mk_faq` at line 91, and rename it:
   ```php
   'mk_career' => array(
       'label'    => __( 'Careers', 'maapkathi' ),
       'singular' => __( 'Career', 'maapkathi' ),
       'slug'     => 'careers',
       'icon'     => 'dashicons-businessperson',
       'supports' => array( 'title', 'editor', 'custom-fields' ),
   ),
   ```
3. Add its fields (see Recipe B).
4. In WordPress, go to **Settings → Permalinks** and click **Save**.
   Without this step, the new pages show "not found".
5. It now appears in the Maapkathi menu.

### Recipe D: Add a new homepage section

1. Open Plugin `src/Sections/SectionRegistry.php`. The list starts at
   line 38. Copy an existing entry, such as `faq` at line 117.
2. Add a heading for it in Site Text (see Recipe A).
3. Create the file Theme `parts/sections/<your-id>.php`. The easiest start
   is to copy `parts/sections/faq.php` (it's short):
   - line 24 loads the content
   - line 30 opens the section
   - line 32 prints the heading
4. Add styles to Theme `assets/css/sections.css`.
5. Turn it on in **Maapkathi → Sections**.

How it works: Theme `front-page.php` line 27 loops through the sections
in the order you set in the admin panel, and loads each one's file.

### Recipe E: Add a new setting to the Appearance screen

1. Plugin `src/Theme/ThemeSettings.php`:
   - add the default value in `defaults()` at line 32
   - list the allowed values in `allowed_values()` at line 101
   - clean the input in `sanitize()` at line 181
2. Plugin `src/Theme/ThemeVarsBuilder.php`: in `build()` at line 33, turn
   the setting into a CSS variable.
3. Plugin `src/Admin/views/appearance.php`: add the control people will click.
4. In theme CSS, use the variable, for example `color: var(--mk-your-var);`.

### Recipe F: Add a new admin page

1. Create Plugin `src/Admin/Screens/MyScreen.php` (this saves the form).
2. Create Plugin `src/Admin/views/my-screen.php` (this is the HTML).
3. In Plugin `src/Admin/Menu.php`, add an `add_submenu_page(...)` line next
   to the others (from line 70), plus a matching `render_...()` function.

### Recipe G: Add new CSS or JavaScript files

1. Put the file in Theme `assets/css/` or `assets/js/`.
2. Load it in Theme `functions.php`, next to the existing files at line 65.
   Copy line 73 (CSS) or line 76 (JS), and use `mk_asset_url()` as they do.

### Recipe H: Add a whole new feature (a new PHP class)

1. Create your class in Plugin `src/YourFeature/YourFeature.php`, with a
   `register_hooks()` function.
2. Turn it on in Plugin `src/Plugin.php`, inside `boot()` at line 84.
   If it's admin-only, put it inside the `is_admin()` block at line 111.
   **If you skip this step, your code never runs.**

---

## Part 5: Helper functions you can use in theme files

All of these are defined in Plugin `src/Support/template-functions.php`:

| Function | What it gives you | Line |
|---|---|---|
| `mk_text( 'key' )` | Text from Site Text | 31 |
| `mk_setting( 'key' )` | A site setting | 68 |
| `mk_content( 'faqs' )` | A list of items (projects, FAQs and so on) | 86 |
| `mk_meta( $id, 'mk_key' )` | One field of one item | 122 |
| `mk_section( $id )` | Section info (title, visible or hidden) | 469 |
| `mk_the_section_heading( $id )` | Prints a section's heading | 522 |

---

## Part 6: Running the site on your computer

1. Install Docker.
2. Start a WordPress + MariaDB setup. **This repo doesn't include a
   `docker-compose.yml` yet**, so you'll need to write one (any standard
   WordPress compose file works).
3. Link these two folders into WordPress:
   - `plugins/maapkathi-core` → `wp-content/plugins/maapkathi-core`
   - `themes/maapkathi-theme` → `wp-content/themes/maapkathi-theme`
4. Turn on the plugin and theme, then add demo content:
   ```bash
   wp plugin activate maapkathi-core && wp theme activate maapkathi-theme
   ```
   ```bash
   wp maapkathi seed
   ```
5. To see errors, add `define( 'WP_DEBUG', true );` and
   `define( 'WP_DEBUG_LOG', true );` to `wp-config.php`, then read
   `wp-content/debug.log`.

For going live, see `docs/DEPLOYMENT.md`.

---

## Part 7: Don't break these

These rules each come from a real bug found during testing.

1. **Keep `edit_posts` in the roles.** It's at lines 67 and 79 of
   Plugin `src/Roles/Roles.php`. If you remove it, editors can't open the
   admin screens.
2. **Checkbox fields must stay type `boolean`**, or saving fails.
3. **Content types need `'custom-fields'`** in `supports` if their fields
   should be editable everywhere.
4. **Don't turn on `has_archive` for services.** The Services page is a
   normal page (`page-services.php`), not an automatic list.
5. **Login limits use `wp_login_failed`.** Don't move this to
   `authenticate`, because that counts attempts twice.
6. **Theme files must never save data.** Only the plugin saves.
7. **Don't install ACF.** Fields are built in on purpose (Recipe B).

---

## Part 8: Checklist before you finish

- [ ] Changed what's saved? → you edited the **plugin**.
- [ ] Changed how it looks? → you edited the **theme**.
- [ ] Added a new class? → you turned it on in `src/Plugin.php` (Recipe H).
- [ ] Added a content type? → you saved **Settings → Permalinks**.
- [ ] Printing text? → wrap it in `esc_html()` (or `esc_url()` for links)
      so it's safe.
- [ ] Made a form? → it checks a nonce and the user's permission.
