# University Knowledge Network
## Brand & UI Design Guide

**Project:** University Knowledge Network (UKN)  
**Design Purpose:** Complete frontend UI/UX system for a university peer-learning and community platform  

---

# 1. Brand Concept

University Knowledge Network is a university peer-learning platform where students can:

- Learn skills from other students
- Teach skills as mentors
- Act as learner and mentor
- Find mentors
- Receive mentor recommendations
- Request learning sessions
- Earn points
- Rate mentors
- Join community discussions
- Create posts
- Comment and reply
- Vote
- Save content
- Follow users
- Receive notifications
- View leaderboards
- Explore skill relationships

The interface should feel like a real community application, not a corporate marketing website.

---

# 2. Core Visual Direction

The visual direction is:

> **Modern Reddit-inspired university community application + subtle vintage/grunge personality**

The interface should feel:

- Modern
- Academic
- Community-driven
- Intelligent
- Professional
- Youthful
- Functional
- Distinctive
- Clean
- Highly usable

Recommended balance:

- **70% Modern Community Application**
- **20% Vintage / Grunge Character**
- **10% Orange Brand Energy**

Reddit may be used as inspiration for:

- Feed hierarchy
- Community interactions
- Voting patterns
- Three-column structure
- Discovery sidebars

Do **not** visually copy Reddit.

---

# 3. Fixed Brand Colors

These are the primary locked colors.

## Black

`#000000`

Use for:

- Strong text
- Dark backgrounds
- Navigation emphasis
- Brand contrast

## White

`#FFFFFF`

Use for:

- Light background
- Cards
- Text on dark surfaces
- Clean content areas

## Orange

`#FF6215`

This is the primary accent color.

Use for:

- Primary CTA
- Active navigation
- Important links
- Selected states
- Notification badges
- Progress indicators
- Important icons
- Ranking highlights
- Key UI accents

Do not overuse orange.

## Dark Charcoal

`#1A1B1F`

Use for:

- Dark-theme surfaces
- Secondary dark backgrounds
- Cards in dark mode
- Navigation areas
- Elevated dark UI sections

---

# 4. Supporting Neutral Colors

Supporting neutral grays may be introduced when required for:

- Borders
- Secondary backgrounds
- Muted text
- Disabled states
- Dividers
- Form backgrounds

Supporting colors must not overpower the core:

**Black + White + Orange + Charcoal**

Avoid adding unrelated brand colors unless required for semantic status.

---

# 5. Semantic Status Colors

Status colors can be used only where they improve understanding.

Examples:

- Success = green
- Warning = amber/yellow
- Error/Danger = red
- Information = blue

Important:

Orange must not be the only indicator of a state.

Use text labels, icons, or patterns in addition to color.

---

# 6. Typography

## Primary Font

**Times New Roman**

Do not replace the primary typeface with:

- Inter
- Roboto
- Poppins
- Montserrat
- Arial
- Generic SaaS typography

The modern feeling should come from:

- Strong hierarchy
- Good spacing
- Appropriate line height
- Clear weight
- Consistent sizing
- Clean layout

Required typography styles:

- Display / Page Title
- H1
- H2
- H3
- H4
- Body
- Small Body
- Navigation
- Metadata
- Form Label
- Helper Text
- Button Text
- Badge / Tag Text

---

# 7. Vintage / Grunge Identity

The brand should include a subtle vintage printed character.

Allowed visual treatments:

- Fine black speckles
- Light grain
- Tiny distressed marks
- Slight scratches
- Ink imperfections
- Very subtle aged-print texture
- Distressed decorative separators

Use texture selectively.

Recommended texture strength:

## Subtle

Use on:

- Page background
- Large neutral surfaces
- Standard cards

## Medium

Use on:

- Selected feature blocks
- Leaderboard highlight areas
- Brand accent sections

## Strong

Use rarely for:

- Decorative/branding moments
- Empty-state illustration areas
- Special promotional/achievement elements

---

# 8. Grunge Restrictions

Do not:

- Put heavy texture over body text
- Reduce text readability
- Make forms dirty/noisy
- Apply strong texture to tables
- Cover charts with texture
- Add splatter everywhere
- Use excessive torn-paper effects
- Make the product look like an old newspaper
- Sacrifice usability for vintage styling

Core philosophy:

> **Modern interface with a vintage soul.**

---

# 9. Main Application Layout

Desktop application structure:

```text
HEADER
────────────────────────────────────────────────────────────
LEFT SIDEBAR    |    MAIN CONTENT    |    RIGHT SIDEBAR
```

## Header

Should contain:

- Logo
- University Knowledge Network
- Global Search
- Create button for logged-in users
- Login action for visitors
- Notifications
- Profile/avatar

## Left Sidebar

Purpose:

**Navigation**

It must be role-aware.

## Main Content

Purpose:

**Current task/page content**

It must always remain the visual priority.

## Right Sidebar

Purpose:

**Context, discovery, supporting information**

It must be contextual, not identical on every page.

---

# 10. Role-Aware Navigation

## Visitor

Possible navigation:

- Home
- Skills
- Find Mentors
- Login
- Register

## Learner

- Home
- Dashboard
- Find Mentors
- Skills
- My Learning
- Learning Goals
- Sessions
- Points
- Saved Posts
- Notifications
- Profile

## Mentor

- Home
- Dashboard
- Learner Requests
- Sessions
- Teaching Skills
- Availability
- Ratings
- Mentor Points
- Notifications
- Profile

## Dual Role

Show navigation based on the currently active role.

## Admin

Use a separate, more data-oriented administration navigation.

---

# 11. Contextual Right Sidebar

Examples:

## Home

- Top Skills
- Top Mentors
- Top Learners
- Trending Discussions

## Skills

- Popular Skills
- Skill Categories
- Trending Skills
- Most Requested Skills

## Find Mentors

- Top Rated Mentors
- Available Mentors
- Popular Mentor Skills

## Learner Dashboard

- Learning Summary
- Upcoming Sessions
- Current Goals
- Points

## Mentor Dashboard

- Mentor Stats
- Upcoming Sessions
- Rating
- Recent Learners

## Post Details

- About Author
- Related Skills
- Related Discussions

Some pages may have no right sidebar, such as:

- Settings
- Notifications
- Certain admin views

---

# 12. Mobile Design Rules

Do not simply shrink the desktop layout.

Recommended mobile structure:

```text
☰  LOGO / NAME      CREATE/LOGIN   🔔   PROFILE
───────────────────────────────────────────────
GLOBAL SEARCH
───────────────────────────────────────────────
MAIN CONTENT
```

Rules:

- Left navigation becomes offcanvas
- No permanent right sidebar
- Contextual data may become compact content cards
- Home discovery sections may appear above the feed
- Touch targets must remain comfortable
- Avoid horizontal layout dependence

Design should work at:

- 320px
- 360px
- 390px
- 430px

---

# 13. Light Theme

Use:

- White/light main background
- Black primary text
- White or very light cards
- Neutral borders
- Orange accent
- Extremely subtle texture

The interface should remain clean and academic.

---

# 14. Dark Theme

Use:

- Main background: `#000000`
- Surface/card background: `#1A1B1F`
- Primary text: `#FFFFFF`
- Accent: `#FF6215`

Dark mode must support:

- Forms
- Tables
- Feed
- Cards
- Dropdowns
- Modals
- Charts
- Sidebars
- Notifications
- Admin screens

Do not use gray text with insufficient contrast.

---

# 15. Buttons

Required states/types:

- Primary
- Secondary
- Outline
- Danger
- Icon
- Disabled
- Loading

## Primary Button

Use Orange `#FF6215`.

Use for important actions such as:

- Create
- Request Session
- Save Changes
- Publish
- Submit Rating

Do not use multiple primary buttons competing in the same small area.

---

# 16. Forms

Required controls:

- Text input
- Password input
- Search
- Select
- Multi-select
- Textarea
- Checkbox
- Radio
- Toggle
- Date
- Time

Required states:

- Default
- Focus
- Filled
- Disabled
- Error
- Success

Forms should be:

- Clean
- Highly readable
- Easy to scan
- Compatible with Bootstrap implementation

Do not apply strong grunge texture to form controls.

---

# 17. Card System

Maintain one coherent card system.

Required card types:

- Generic Card
- Post Card
- Mentor Card
- Learner Card
- Skill Card
- Session Card
- Learning Goal Card
- Stat Card
- Point Transaction
- Rating Item
- Notification Item
- Leaderboard Row
- Search Result Item

Use consistent:

- Padding
- Border
- Radius
- Hover behavior
- Typography
- Metadata hierarchy

Avoid oversized rounded SaaS-style cards.

---

# 18. Post Card

The post card is a core brand component.

Include:

- Author avatar
- Author name
- User role / metadata
- Timestamp
- Post title
- Content preview
- Skill tags
- Upvote
- Downvote
- Vote count
- Comment count
- Save
- More actions

Use Reddit as interaction inspiration only.

Do not copy Reddit's exact appearance.

---

# 19. Mentor Card

Include:

- Avatar
- Name
- Department
- Primary teaching skill
- Other skills
- Rating
- Completed sessions
- Mentor points
- Availability
- Match percentage when recommended
- View Profile
- Request Session

The card should be compact but informative.

---

# 20. Dashboard Cards

Reusable statistics cards should support:

- Learning Points
- Mentor Points
- Completed Sessions
- Upcoming Sessions
- Average Rating
- Pending Requests
- Current Goals
- Skills Learning

Dashboard areas should later support Chart.js without redesigning the layout.

---

# 21. Modals

Use one consistent modal system for:

- Create Post
- Edit Post
- Session Request
- Rating
- Delete Confirmation
- Role Switch

Modal requirements:

- Clear title
- Obvious close action
- Clear primary/secondary actions
- Responsive mobile behavior
- Visible focus state
- No excessive decoration

---

# 22. Loading / Empty / Error / Success States

Every major screen should have designed states.

## Loading

- Skeletons preferred for content-heavy areas
- Spinner where appropriate

## Empty

Use meaningful text such as:

- No mentors found.
- No saved posts yet.
- No upcoming sessions.

## Error

Example:

- Unable to load content.

## Success

Example:

- Session request sent successfully.

States should look like part of the same product.

---

# 23. Icon Style

Use one consistent icon language.

Icons should be:

- Simple
- Recognizable
- Moderate stroke/detail
- Functional
- Consistent in size

Do not mix several unrelated icon styles.

Icons should support navigation and actions, not dominate the interface.

---

# 24. Spacing & Layout

Use a consistent spacing system across:

- Cards
- Forms
- Sidebars
- Tables
- Modals
- Feed items
- Dashboards

Avoid:

- Random margins
- Uneven card padding
- Overly large empty spaces
- Dense text blocks without breathing room

Main content must remain highly readable.

---

# 25. Borders, Radius & Shadows

Use:

- Clear but restrained borders
- Moderate/small radius
- Minimal shadows
- Stronger visual separation primarily through spacing and borders

Avoid:

- Giant rounded cards
- Floating glass cards
- Heavy shadows
- Excessive depth effects

---

# 26. Interaction States

Every interactive component should visually support:

- Default
- Hover
- Focus
- Active
- Selected
- Disabled
- Loading where relevant

Examples:

- Navigation active state
- Upvoted post state
- Saved post state
- Follow/Following state
- Active tab
- Selected skill
- Selected role

---

# 27. Accessibility

Maintain:

- Clear color contrast
- Visible keyboard focus
- Readable font sizes
- Large enough click/touch targets
- Clear form labels
- Error messages near fields
- Obvious modal close controls
- More than color alone to indicate status

Accessibility must not be sacrificed for aesthetics.

---

# 28. Design Restrictions

Do **not** use:

- Glassmorphism
- Neon cyberpunk styling
- Excessive gradients
- Excessive shadows
- Giant rounded corners
- Generic SaaS dashboard aesthetics
- Corporate marketing landing-page patterns
- Heavy grunge on every surface
- Unrelated brand colors
- Overly decorative inputs
- Reddit branding or direct visual copying

Do not replace Times New Roman.

Do not change the locked orange `#FF6215`.

---

# 29. Realistic Content Direction

Do not rely on Lorem Ipsum.

Use realistic university-related sample content.

## Departments

Examples:

- Computer Science
- Business Administration
- Electrical Engineering
- English
- Architecture

## Skills

Examples:

- Python
- JavaScript
- React
- UI/UX Design
- Machine Learning
- Data Analysis
- Graphic Design
- Public Speaking

## User Names

Examples:

- Rahim Ahmed
- Sara Khan
- Hasan Mahmud
- Nabila Rahman
- Tanvir Hossain

## Community Posts

Examples:

- Things I Learned While Building My First React Project
- How I Started Learning Python for Data Analysis
- Need Help Understanding Database Normalization
- Best Resources for Learning UI/UX Design

Content should feel natural and concise.

---

# 30. Design Consistency Principle

The whole interface must look like **one product**.

Every screen must share:

- The same color system
- The same typography
- The same spacing logic
- The same card language
- The same button system
- The same form system
- The same icon language
- The same border philosophy
- The same texture philosophy
- The same responsive rules

Do not invent a different style for every page.

Reuse established components.

---

# 31. Developer Handoff

The final designs will be implemented with:

- HTML5
- CSS3
- Bootstrap 5
- Vanilla JavaScript
- PHP templates
- Chart.js
- Cytoscape.js

Therefore:

- Use technically practical layouts
- Prefer Bootstrap-compatible structures
- Avoid unnecessarily complex absolute positioning
- Make responsive behavior explicit
- Identify reusable components
- Keep desktop/mobile variants clear
- Avoid visual effects that are difficult to recreate in standard CSS
- Keep design names aligned with project file/component names

---

# 32. Final Design Principle

The desired result is:

> **A modern, functional university peer-learning community platform with a strong black/white/orange identity and subtle vintage/grunge character.**

The design must prioritize:

1. Usability
2. Information hierarchy
3. Consistency
4. Community interaction
5. Academic credibility
6. Responsive behavior
7. Distinct brand personality

Aesthetic decoration must never reduce usability.
