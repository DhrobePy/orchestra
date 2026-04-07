# Layup — Sprint TODO (overnight Feb 23-24)

## Priority 1: Testing ✅ COMPLETE
- [x] Frontend/HTTP Tests (22 tests)
- [x] SafelistCollector Tests (16 tests)
- [x] Artisan Command Tests (4 tests)
- [x] ContentValidator Tests (14 tests)
- [x] Rendering Tests (28 tests)
- [x] PageTemplate Tests (7 tests)
- [x] Revision Tests (4 tests)

## Priority 2: Missing Divi Features ✅ COMPLETE

### Sections (wraps rows) ✅
- [x] `Section` view component — outermost wrapper around rows
- [x] Section settings: background image/video/gradient, parallax, fullscreen height
- [x] Update page content structure: supports both `{ sections: [...] }` and legacy `{ rows: [...] }`

### Design Tab Enhancements ✅
- [x] Text color picker, Font size selector, Text alignment
- [x] Border radius, Border (width/style/color), Box shadow presets
- [x] Opacity slider

### Responsive Visibility ✅
- [x] Show/hide per breakpoint toggle, generates hidden/block classes

### Animations/Transitions ✅
- [x] 6 entrance animations via Alpine x-intersect, configurable duration

## Priority 3: Builder UX Polish

### Widget Picker ✅ COMPLETE
- [x] Widget icons in picker modal
- [x] Recently used widgets
- [x] Drag from picker to canvas

### Builder Canvas ✅ COMPLETE
- [x] Live preview of widget content
- [x] Inline text editing
- [x] Visual column resize handles

### Page Management ✅ COMPLETE
- [x] Duplicate page, Bulk publish/unpublish, Export/Import JSON
- [x] Revision history (auto-save, prune, restore)
- [x] Revision browser UI in EditPage

## Priority 4: Portability & DX ✅ COMPLETE
- [x] Export/Import JSON, Page templates (5 built-in), Save as template
- [x] SEO: OG tags, Twitter Cards, canonical, JSON-LD breadcrumbs
- [x] Sitemap helper (Page::sitemapEntries())
- [x] layup:install command, Widget auto-discovery, Extra safelist classes
- [x] Structured data: WebPage, Article/BlogPosting, FAQPage schema.org types
- [x] `layup:make-widget` artisan command (scaffold custom widgets)
- [x] `layup:audit` command (page health check, widget usage stats)

## Priority 5: Widget Polish ✅ COMPLETE
- [x] Heading: link URL
- [x] Image: link URL, new tab
- [x] Button: custom bg/text colors, hover colors via Alpine
- [x] Video: privacy-enhanced mode (youtube-nocookie.com)
- [x] Blurb: text alignment, right layout support
- [x] CTA: button style, bg/text colors, new tab
- [x] Testimonial: star rating, company name
- [x] PricingTable: custom badge text
- [x] Map: map type (roadmap/satellite/terrain/hybrid)
- [x] SocialFollow: icon size
- [x] Gallery: lightbox + captions
- [x] Image: hover effects (zoom, grayscale→color, brighten, blur→clear)
- [x] Blurb: icon picker UI (90+ searchable heroicons)

## Summary (as of ~5:30am MST Feb 24)
- **852 tests, 1706 assertions** — all passing
- **75 widgets** total
- **Section component** with background image/video/gradient, parallax, overlay
- **Structured data** (WebPage, Article, FAQPage, BreadcrumbList)
- **Page templates** (5 built-in), revision history with browser UI
- **4 artisan commands**: layup:install, layup:safelist, layup:make-widget, layup:audit
- **Alpine.js components** for interactive widgets (accordion, tabs, countdown, slider, lightbox, etc.)
- **Full Design/Advanced tabs** on all widgets (colors, borders, spacing, animations, visibility)
- **Builder UX enhancements**: Widget icons (65+ heroicons), recently used widgets, drag from picker, improved previews, inline text editing
- **Revision browser**: Slideover modal with restore functionality, timestamps, author tracking

## Completed Tonight (Priority 3 UX Polish) — ALL 7 TASKS ✅
1. ✅ Widget icons in picker modal - 65+ heroicon SVGs render correctly per widget type
2. ✅ Recently used widgets - localStorage tracking, shows last 5 widgets at top of picker
3. ✅ Revision browser UI - Slideover with history, restore buttons, metadata display
4. ✅ Drag from picker to canvas - Drag widgets directly onto columns from picker
5. ✅ Live preview improvements - 20+ widget-specific preview formats with icons and context
6. ✅ Inline text editing - Double-click text/heading widgets to quick-edit content
7. ✅ Visual column resize handles - Drag handles between columns to adjust spans dynamically

## Future (post-sprint)
- More page templates
- Publish to Packagist
