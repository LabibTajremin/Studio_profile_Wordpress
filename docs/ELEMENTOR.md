# Elementor

## What works, and what Elementor's free version cannot do

The theme's homepage is assembled from its own section layout, not from
post content. Elementor edits the content of a post or a page, so it has
no way to reach that homepage. Rather than move the whole site into the
builder, every section is **also published as an Elementor widget**.

On the **free** version you get:

- All 12 homepage sections in the widget panel, under **Maapkathi
  Sections**
- Drag and drop them onto any page, in any order
- Add, remove and duplicate them
- Resize them by resizing the container or column they sit in
- Edit the surrounding page freely with Elementor's own widgets

What free Elementor **cannot** do, and no amount of theme code changes it:

- **Header and footer** are Theme Builder features, which are Pro-only.
  They stay under Appearance and Footer in the Maapkathi menu.
- **Archive and single-post templates** are likewise Pro.

The Theme Builder locations are registered anyway, so a site that later
adds Pro finds them already wired with no code change.

## Using a section in Elementor

1. Edit any page with Elementor.
2. In the widget panel, search for the section name, or scroll to
   **Maapkathi Sections**.
3. Drag it in.

A section's **words and items stay on the Maapkathi screens** — Services
are edited under Services, testimonials under Testimonials, and so on.
That is deliberate: duplicating those fields into the widget would give
two places to change one thing and no way to tell which one won. The
widget panel links through to the right screen.

Where a section type has more than one saved copy (Section Builder →
Duplicate), the widget offers a **Which copy** control so each copy keeps
its own heading and anchor.

## Building the homepage with Elementor

The theme keeps rendering its own homepage unless **both** of these are
true:

1. **Settings → Reading** is set to *A static page*, with a page chosen
   for *Homepage*; and
2. that page was built with Elementor.

Then `front-page.php` steps aside and the builder page renders instead.
Installing Elementor on its own never changes an existing homepage.

To move over without rebuilding from memory: create a page, open it in
Elementor, and drag in the sections in the order the Appearance screen
lists them. Set it as the homepage once it looks right — the old layout is
still stored, so clearing the static-homepage setting puts it back.

## Pages in general

The theme previously had no `page.php`, so a plain WordPress page fell
through to `index.php` and rendered the *blog loop* instead of its own
content. That is fixed: `page.php` renders page content properly, and
detects builder-built content to give it the full width with no theme
container wrapped around it.

## Styling

The public stylesheets are loaded inside the Elementor editor and its
preview iframe, so sections look in the editor the way they look on the
site. `$content_width` is set to 1200 to match the theme container, which
is what Elementor sizes its default container and uploaded images against.
