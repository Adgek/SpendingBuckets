# Phase 5: Front-End Integration — Feature List

> **Goal:** Transform the functional but plain Blade views into the polished, dark-themed UI from the `FrontEnd Demo/` React prototype — using Blade + Tailwind + Alpine.js (no React).

---

## Should You Provide Mockups?

**Yes — strongly recommended.** The agent can work from:
1. **Screenshots of the React demo** (run `cd "FrontEnd Demo" && npm run dev`, screenshot each view, drop PNGs into this repo). This is the fastest path since the prototype already exists.
2. **Figma/hand-drawn wireframes** if you want to deviate from the React demo's design.
3. **Annotated screenshots** — screenshot the current Blade UI and draw/annotate what should change (e.g., "this should be a drag handle", "dark background here").

The React demo already defines a clear design language (dark charcoal bg, gold accents, crimson "danger mode" for transfers, serif headings, progress bars). Providing those screenshots as reference gives the agent pixel-level guidance.

---

## Major Features Needed

### 1. Design System & Layout Overhaul
**What exists:** Generic white-bg Tailwind layout with simple nav links.
**What's needed:** Port the React demo's dark theme to the Blade layout.
- Dark charcoal (`#1a1a2e`-ish) background, warm-white text
- Narrow icon sidebar (like `Sidebar.tsx`) replacing the top nav bar
- Gold (`#C5A059`) accent color for primary actions
- Forest green for "complete/funded" states, crimson for danger/transfers
- Serif font for headings, clean sans-serif for body
- Define Tailwind custom colors in `tailwind.config.js` (or CSS variables): `charcoal`, `navy`, `elevated`, `surface`, `border`, `gold`, `forest`, `crimson`, `warm-white`, `muted`

### 2. Bucket Dashboard ("The Stack")
**What exists:** Simple card list showing name, type badge, target, cap, and balance.
**What's needed:** Port `BucketStack.tsx` + `BucketCard.tsx` design.
- Each bucket card shows: priority badge (numbered circle), name, current/target amounts, progress bar
- Progress bar with gold fill (switches to forest green when 100% funded), quarter-mark tick lines
- Check-circle icon when a bucket is fully funded
- Separate visual sections for **Fixed** buckets (the priority stack) and **Excess** buckets
- Total balance summary header (sum of all bucket balances)

### 3. Drag-and-Drop Priority Reordering
**What exists:** Priority is set via a number input on the edit form. No drag-and-drop.
**What's needed:** The headline Phase 5 feature per `ARCHITECTURE.md`.
- Grip handle on each bucket card (left side)
- HTML5 drag-and-drop (or Alpine.js + Sortable.js) to reorder fixed buckets
- On drop: send a PATCH/POST to a new endpoint that bulk-updates `priority_order` on all affected buckets
- **Backend:** New route + controller method (e.g., `PUT /buckets/reorder`) accepting an ordered array of bucket IDs
- Visual feedback: dragged card gets elevated shadow/opacity, drop target shows gold border indicator

### 4. Action Pane (Right Sidebar)
**What exists:** Deposit, Expense, and Transfer are three separate page routes with full-page forms.
**What's needed:** Port the `ActionPane.tsx` design — a persistent right sidebar with tabbed forms.
- Three tabs: **Deposit**, **Expense**, **Transfer** (pill-style tab switcher)
- Tab content switches inline (Alpine.js `x-show` or similar) — no page navigation
- Forms submit via standard POST (or AJAX with Alpine `fetch`) and update the bucket list
- **Deposit tab:** Large dollar input, date field, "Fund Next in Stack" button (gold)
- **Expense tab:** Bucket dropdown (showing available balance), amount, description, "Record Expense" button
- **Transfer tab:** "Danger mode" — crimson accent, warning banner ("Restricted Action"), from/to dropdowns, amount, "Execute Transfer" button
- After successful submission: flash message + bucket balances refresh

### 5. Bucket Detail / Transaction History View
**What exists:** Basic `show.blade.php` with metadata grid and plain transaction list.
**What's needed:**
- Dark-themed card matching the design system
- Transaction list with colored type badges (green=allocation, red=expense, yellow=transfer, blue=sweep)
- Running balance column (cumulative after each transaction)
- Filter/search by transaction type (Alpine.js dropdown filter)
- Pagination or infinite scroll for long histories

### 6. Bucket Create/Edit Forms
**What exists:** Functional white-bg forms with standard inputs.
**What's needed:**
- Restyle to dark theme with gold-accented inputs
- Conditional fields: show `monthly_target` + `priority_order` only when type=`fixed`; show `excess_percentage` only when type=`excess` (use Alpine.js `x-show`)
- Cap field with helper text ("Leave blank for no cap")
- Toggle switch for `sweeps_excess` and `is_primary_savings` booleans
- Inline validation feedback

### 7. Deposit History View
**What exists:** Simple white table on `deposits/index.blade.php`.
**What's needed:**
- Dark-themed table matching the design system
- Each deposit row expandable (click to see the allocation breakdown — which buckets received how much from that deposit)
- **Backend:** Eager-load `deposit.transactions` with bucket names for the breakdown

### 8. Dashboard Summary / Home Page
**What exists:** Laravel default `welcome.blade.php`.
**What's needed:**
- Replace with a real dashboard showing:
  - Total balance across all buckets
  - Count of fully-funded vs. underfunded fixed buckets
  - Latest deposit + its allocation summary
  - Quick-action buttons (New Deposit, Record Expense, Transfer)

### 9. End-of-Month Sweep UI
**What exists:** No UI — the sweep logic is in the engine but not exposed.
**What's needed:**
- A "Run Sweep" button (on dashboard or as a nav action)
- Confirmation modal ("This will sweep all eligible buckets to savings. Continue?")
- **Backend:** New `SweepController` + `RunSweepAction` triggering the sweep logic from Architecture §4
- Results page showing which buckets were swept and how much moved

### 10. Responsive / Mobile Considerations
**What exists:** Desktop-only layout.
**What's needed:**
- Sidebar collapses to bottom tab bar on mobile
- Action pane becomes a slide-up sheet or separate page on small screens
- Bucket stack remains a vertical list (works naturally on mobile)

---

## Implementation Priority (Suggested Order)

| Order | Feature | Complexity | Notes |
|-------|---------|-----------|-------|
| 1 | Design System & Layout | Medium | Foundation — everything else depends on this |
| 2 | Bucket Dashboard (The Stack) | Medium | Core view with progress bars |
| 3 | Drag-and-Drop Reordering | Medium | The key Phase 5 deliverable per ARCHITECTURE.md |
| 4 | Action Pane (Sidebar Forms) | High | Replaces 3 separate pages with tabbed sidebar |
| 5 | Bucket Create/Edit Forms | Low | Restyle existing forms |
| 6 | Bucket Detail View | Low | Restyle + add running balance |
| 7 | Dashboard Home Page | Medium | New view |
| 8 | Deposit History (expandable) | Medium | Backend change + UI |
| 9 | End-of-Month Sweep UI | Medium | New backend action + UI |
| 10 | Responsive/Mobile | Low–Med | Progressive enhancement |

---

## Tech Notes

- **No React.** The `FrontEnd Demo/` is a design reference only. All implementation uses **Blade + Tailwind CSS + Alpine.js**.
- Alpine.js is already in the AGENTS.md tech stack but not yet installed. Will need: `npm install alpinejs` + import in `resources/js/app.js`.
- For drag-and-drop, consider [SortableJS](https://sortablejs.github.io/Sortable/) (lightweight, framework-agnostic) with an Alpine wrapper.
- All monetary values stay in **cents** in the backend; convert to dollars only in Blade templates (`$amount / 100`).
