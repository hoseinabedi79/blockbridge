# BlockBridge

A lightweight engine for building ACF-powered custom blocks in WordPress.

Register a block by creating a folder. No build step, no npm, no React. Works
in classic themes and in headless setups, where parsed block data is served
over REST, GraphQL, or both.

---

## Why this exists

Building Gutenberg blocks normally means a JavaScript toolchain. BlockBridge
replaces that with two files per block: a `block.json` and a PHP template.

It also solves the problem that appeared in WordPress 7.1, where the post
editor is always rendered inside an iframe and theme styles no longer leak
into the editor canvas. BlockBridge pipes your front-end stylesheet into the
canvas so block previews look like the live site.

---

## Requirements

| Requirement | Version | Notes |
|---|---|---|
| WordPress | 6.3 or higher | 7.1+ recommended |
| ACF PRO | 6.6 or higher | 6.8+ recommended. ACF Blocks are a PRO feature |
| PHP | 7.4 or higher | 8.1+ recommended |
| WPGraphQL | any recent | Only for the GraphQL layer |

ACF Blocks v3 requires ACF PRO 6.6. Inline editing requires 6.7.

---

## Installation

1. Copy the `blockbridge` folder into `wp-content/plugins/`.
2. Activate **BlockBridge** from the Plugins screen.
3. Set the configuration constants (see below).

If you cloned from Git, the `acf-json` folder must exist. Git does not track
empty folders, so it ships with a `.gitkeep` file. If the folder is missing,
create it manually or field groups will silently fail to save.

After activating on a site that already has field group JSON files, go to
**ACF → Field Groups** and use the **Sync** tab to import them.

---

## Configuration

Five constants control the plugin. Defaults live in `config.php`, but every
one can be overridden from `wp-config.php`, which keeps environment-specific
values out of version control.

Add overrides above the `/* That's all, stop editing! */` line:

```php
define( 'BB_FRONT_CSS', 'https://example.com/wp-content/themes/mytheme/dist/app.css' );
define( 'BB_ENABLE_REST', true );
```

| Constant | Default | What it does |
|---|---|---|
| `BB_FRONT_CSS` | `''` | Absolute URL of the stylesheet loaded into the editor canvas. Empty disables it |
| `BB_CATEGORY_SLUG` | `blockbridge` | Block category slug. Must match `category` in every `block.json` |
| `BB_CATEGORY_TITLE` | `Site Blocks` | Category label shown in the editor inserter |
| `BB_ENABLE_REST` | `false` | Loads the REST layer. Headless projects only |
| `BB_ENABLE_GRAPHQL` | `false` | Loads the GraphQL layer. Headless projects only |

`BB_FRONT_CSS` must be an absolute URL. `wp-config.php` loads before
WordPress, so template functions like `get_stylesheet_directory_uri()` are
not available there.

When no stylesheet is configured, an admin notice appears as a reminder.
Block previews still work, they are just unstyled.

---

## Folder structure

```
blockbridge/
├── blockbridge.php          Plugin header, constants, file loading
├── config.php               Per-project settings. The only file you edit
├── README.md
│
├── includes/                The engine. Written once, never touched
│   ├── acf-json.php         Keeps block field groups in files, not the database
│   ├── block-category.php   Registers the block category
│   ├── register-blocks.php  Auto-registers every block folder
│   ├── editor-styles.php    Loads the front-end stylesheet into the canvas
│   ├── block-parser.php     Turns post content into clean block data
│   ├── api-rest.php         REST endpoints. Conditional
│   └── api-graphql.php      GraphQL types and fields. Conditional
│
├── blocks/                  One folder per block
│   └── _example/            Starter block. Copy it to create a new one
│
├── acf-json/                Field group definitions, tracked in Git
│
└── assets/
    └── editor-base.css      Neutral preview styles for headless projects
```

Only `config.php`, `blocks/`, and `acf-json/` change between projects.

---

## Creating a block

Three steps.

### 1. Create the folder

Copy `blocks/_example/` and rename it. Then edit `block.json`:

```json
{
	"$schema": "https://schemas.wp.org/trunk/block.json",
	"apiVersion": 3,
	"name": "acf/hero",
	"title": "Hero",
	"description": "Full-width intro section.",
	"category": "blockbridge",
	"icon": "cover-image",
	"editorStyle": "file:./editor.css",
	"supports": {
		"align": false,
		"anchor": true,
		"html": false
	},
	"acf": {
		"blockVersion": 3,
		"renderTemplate": "render.php"
	}
}
```

The `name` must include the `acf/` prefix. Registering a block without it
breaks every page that already uses the block, because the stored block name
in post content will no longer match.

### 2. Create the field group

In **ACF → Field Groups**, add a group named `Block: Hero` and set its
location rule to **Block is equal to Hero**.

The JSON file is written to `acf-json/` automatically, so the block and its
fields travel together.

Keep names consistent: folder `hero`, block name `acf/hero`, field group
`Block: Hero`.

### 3. Write the template

`render.php` outputs the block's markup. Copy the HTML from your front-end
team verbatim and replace the static text with field calls.

```php
<?php
$title = get_field( 'title' );
$image = get_field( 'image' );
?>

<section class="hero">
	<?php if ( $title ) : ?>
		<h1 class="hero__title"><?php echo esc_html( $title ); ?></h1>
	<?php endif; ?>

	<?php if ( is_array( $image ) && ! empty( $image['url'] ) ) : ?>
		<img
			class="hero__image"
			src="<?php echo esc_url( $image['url'] ); ?>"
			alt="<?php echo esc_attr( $image['alt'] ?? '' ); ?>"
		/>
	<?php endif; ?>
</section>
```

Wrap every field in a check. In the editor, fields are empty before the user
fills them in.

For fields that return arrays (Image, Gallery, File, Link, Group), use
`is_array()` rather than a plain truthiness check. With `autoInlineEditing`
enabled, ACF renders the template once with placeholder strings in place of
real values, and a plain check will hit a fatal error.

---

## Block files

Each block folder supports five files. Only the first two are required.

| File | Required | Purpose |
|---|---|---|
| `block.json` | Yes | Block definition |
| `render.php` | Yes | Markup for the front end and the editor preview |
| `editor.css` | No | Styles that apply only inside the editor |
| `api.php` | No | Reshapes this block's API data |
| `graphql.php` | No | Registers this block's GraphQL type |

---

## Styling

### Classic projects

The theme owns the design. Write your section styles where you always have,
and point `BB_FRONT_CSS` at the theme's compiled stylesheet:

```php
define( 'BB_FRONT_CSS', 'https://example.com/wp-content/themes/mytheme/dist/app.css' );
```

One file, two places: the theme loads it on the front end, BlockBridge loads
it into the editor canvas. You do not need a `style` entry in `block.json`.

Copy your front-end team's class names exactly. With Tailwind, only classes
that appear in scanned source files end up in the compiled CSS, and
`render.php` is not scanned by default. Either reuse existing classes or ask
the front-end team to add the plugin's `blocks/` folder to their Tailwind
source paths.

### Headless projects

There is no theme, so ask the front-end team for a compiled stylesheet, drop
it in `assets/`, and point at it:

```php
define( 'BB_FRONT_CSS', 'https://cms.example.com/wp-content/plugins/blockbridge/assets/front.css' );
```

That file is a copy, so it drifts as the front end changes. Treat refreshing
it as part of your release process, or link to a stable URL on the deployed
front end if one exists.

If matching the real design is not worth the maintenance, use the bundled
neutral stylesheet instead:

```php
define( 'BB_FRONT_CSS', 'https://cms.example.com/wp-content/plugins/blockbridge/assets/editor-base.css' );
```

It styles blocks by class name pattern (`__title`, `__text`, `__image`,
`__item`, `__button`) and draws a dashed outline around each block, which
signals to editors that they are looking at a preview rather than the
finished page. It expects the `bb-<block>__<element>` naming convention.

### editor.css

Keep it short. It exists for the handful of places where the editor differs
from the front end:

- a block with no content yet collapsing to zero height (`min-height`)
- styles that depend on a wrapper element the editor does not render
- animations or `position: fixed` that fight with editing

If `editor.css` grows past twenty lines, the design belongs in the front-end
stylesheet instead.

### Do not load front-end JavaScript into the editor

CSS only. An accordion or slider script will fight the editor when a user
clicks to edit text. The preview should be static.

---

## Inline editing

Add to the `acf` object in `block.json`:

```json
"acf": {
	"blockVersion": 3,
	"renderTemplate": "render.php",
	"autoInlineEditing": true
}
```

Text and Text Area fields become directly typable in the preview. Most other
field types open a floating toolbar on click. Repeater and Flexible Content
are not inline editable and stay in the sidebar or the Expanded Editor.

Two caveats:

- ACF renders the template an extra time to detect editable elements, so the
  editor is slower and array-returning fields need `is_array()` guards.
- Not recommended for blocks with conditional logic. Use ACF's manual helper
  functions there instead.

---

## REST API

Enable with `BB_ENABLE_REST`.

### Endpoints

```
GET /wp-json/blockbridge/v1/blocks/<id>
GET /wp-json/blockbridge/v1/blocks/<post-type>/<slug>
```

Only published posts are returned. Anything else responds with 404, so
unpublished content never leaks.

### Response

```json
{
  "id": 12,
  "slug": "about-us",
  "title": "About us",
  "type": "page",
  "blocks": [
    {
      "name": "core/paragraph",
      "type": "core",
      "html": "<p class=\"wp-block-paragraph\">Intro copy.</p>"
    },
    {
      "name": "hero",
      "type": "acf",
      "data": {
        "title": "Power your space",
        "image": {
          "url": "https://example.com/wp-content/uploads/hero.png",
          "alt": "Product lineup"
        }
      }
    }
  ]
}
```

ACF blocks carry structured `data`. Core blocks carry rendered `html`. The
`type` key tells the front end which to expect. The `acf/` prefix is stripped
from ACF block names so the front end can map `name` straight to a component.

No per-block code is needed. New blocks appear in the response automatically.

---

## GraphQL API

Enable with `BB_ENABLE_GRAPHQL`. Requires WPGraphQL.

A `blocks` field is added to every post type exposed in GraphQL. Because
blocks have different shapes, they share the `BbBlock` interface and each
block gets its own object type.

### Query

```graphql
{
  page(id: "about-us", idType: URI) {
    blocks {
      name
      type
      ... on BbBlockHero {
        title
        image {
          url
          alt
        }
      }
      ... on BbBlockCore {
        html
      }
    }
  }
}
```

### Registering a block type

Unlike REST, GraphQL needs to know each block's fields ahead of time. Add a
`graphql.php` to the block folder:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bb_register_hero_block_graphql_type() {
	if ( ! function_exists( 'register_graphql_object_type' ) ) {
		return;
	}

	register_graphql_object_type(
		'BbBlockHero',
		[
			'description' => 'The Hero block.',
			'interfaces'  => [ 'BbBlock' ],
			'fields'      => [
				'title' => [
					'type'    => 'String',
					'resolve' => function ( $block ) {
						return $block['data']['title'] ?? null;
					},
				],
				'image' => [
					'type'    => 'BbImage',
					'resolve' => function ( $block ) {
						return $block['data']['image'] ?? null;
					},
				],
			],
		]
	);
}
add_action( 'graphql_register_types', 'bb_register_hero_block_graphql_type' );
```

The type name is derived from the block name: `hero-banner` becomes
`BbBlockHeroBanner`. Dashes and underscores are removed and each word is
capitalised.

A block without a `graphql.php` falls back to `BbBlockCore` rather than
breaking the query. It will return `name` and `type` only, with `html` as
`null`. If a block returns nulls unexpectedly, its type is probably not
registered.

`BbImage` is a shared type with `url` and `alt`, matching what the parser
produces for image fields.

---

## Shaping a block's API output

Add an `api.php` to the block folder to change one block's data before it is
served. The change applies to REST and GraphQL alike, since it happens in the
parser.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bb_hero_block_data( $data, $block, $post_id ) {
	// Rename a field.
	if ( isset( $data['heading'] ) ) {
		$data['title'] = $data['heading'];
		unset( $data['heading'] );
	}

	// Add something that is not an ACF field.
	$data['publishedAt'] = get_the_date( 'c', $post_id );

	return $data;
}
add_filter( 'bb_block_data_hero', 'bb_hero_block_data', 10, 3 );
```

The hook name is `bb_block_data_{block-name}`, using the name from
`block.json` without the `acf/` prefix. A block named `acf/_example` uses
`bb_block_data__example`, with two underscores.

A `bb_block_data` filter also runs for every block, for rules that should
apply site-wide.

If you rename a field here, use the new name in `graphql.php` too.

Rules that depend on a field type rather than a specific block belong in
`block-parser.php` instead. Image trimming lives there, which is why every
image field in every block returns just `url` and `alt` without per-block
code.

---

## Common mistakes

**Block does not appear in the inserter.** The `category` in `block.json`
must match `BB_CATEGORY_SLUG`. Also check that `block.json` is valid JSON and
that every file it references exists, since registration fails silently
otherwise.

**"No block types exist" in the field group location rules.** No block is
registered yet. Check that `register-blocks.php` is required from
`blockbridge.php`.

**Fields are missing after moving the plugin to a new site.** The field group
JSON did not travel. Confirm `acf-json/` is committed and use ACF's Sync tab.

**Editor preview has no styling.** `BB_FRONT_CSS` is empty or points at a URL
that does not resolve. An admin notice appears when it is unset.

**CSS changes do not show up.** Browser cache. Bump `BB_VERSION` in
`blockbridge.php` or hard-refresh with `Ctrl + Shift + R`.

**Fatal error on an image field.** Use `is_array()` before reading array
keys. Required when `autoInlineEditing` is on.

**`config.php` reset after an update.** Do not overwrite it when updating the
plugin on an existing site, or move your values into `wp-config.php` so the
plugin folder stays identical to the repository.

**Slow editor.** Server-rendered blocks make a request per change. Check the
PHP version and turn off Xdebug before blaming the plugin. Avoid heavy
queries in `render.php`.

---

## Notes on compatibility

The engine uses long-standing WordPress and ACF APIs, so it runs on
WordPress 6.3 and up. What changes across versions is the editing experience,
not the code.

`blockVersion: 3` means blocks are always in preview mode. The old inline
edit form, where the field form replaced the block in the canvas, was removed
in ACF Blocks v3 and cannot be restored on WordPress 7.1, where the iframed
editor is mandatory. Inline editing is its replacement.

On ACF versions below 6.6, `blockVersion: 3` is ignored and blocks fall back
to v2 behaviour. On versions below 6.7, `autoInlineEditing` is ignored.
Neither case throws an error.

---

## License

GPL-2.0-or-later
