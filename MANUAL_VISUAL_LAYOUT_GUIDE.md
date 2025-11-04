# Visual Layout Guide - Manual Page with PDF Download

## Desktop View Layout

```
┌──────────────────────────────────────────────────────────────────┐
│  Navbar (Red Gradient)                                           │
│  📚 Global Reciprocal Colleges - User Manuals                    │
│                        [📄 Download Complete PDF] [🖨️ Print]     │
└──────────────────────────────────────────────────────────────────┘
┌──────────────┬───────────────────────────────────────────────────┐
│   Sidebar    │           Main Content Area                       │
│              │                                                    │
│ 🔍 Search    │  📖 Welcome to GRC User Manuals                   │
│              │                                                    │
│ 👨‍💼 Admin    │  ┌────────────────────────────────────────────┐  │
│              │  │ 📄 COMPLETE PDF MANUAL AVAILABLE           │  │
│ 👨‍🏫 Professor│  │                                            │  │
│              │  │ Download the complete user manual with    │  │
│ 👨‍🎓 Student  │  │ all roles in a single PDF file.           │  │
│              │  │                                            │  │
│              │  │  [⬇️ Download Complete PDF]                │  │
│ ────────────│  └────────────────────────────────────────────┘  │
│              │                                                    │
│ Quick Links: │  ┌──────────┐ ┌──────────┐ ┌──────────┐         │
│ 📄 🖨️ ⛶ 🏠   │  │ 👨‍💼 Admin │ │👨‍🏫 Prof  │ │👨‍🎓Student│         │
│              │  │          │ │          │ │          │         │
│ Version 1.0  │  │  System  │ │Attendance│ │Enrollment│         │
│ Oct 2025     │  │   Mgmt   │ │& Classes │ │ Records  │         │
│              │  └──────────┘ └──────────┘ └──────────┘         │
└──────────────┴───────────────────────────────────────────────────┘
```

## Mobile View Layout (≤768px)

```
┌─────────────────────────────┐
│ Navbar                      │
│ 📚 GRC Manuals              │
│        [☰] [📄] [🖨️]        │
└─────────────────────────────┘
┌─────────────────────────────┐
│   📖 Welcome                │
│                             │
│ ┌─────────────────────────┐ │
│ │      📄                 │ │
│ │ COMPLETE PDF MANUAL     │ │
│ │                         │ │
│ │ Download the complete   │ │
│ │ manual with all roles   │ │
│ │                         │ │
│ │ [⬇️ Download PDF]        │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │  👨‍💼 Administrator       │ │
│ │  System Management      │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │  👨‍🏫 Professor          │ │
│ │  Attendance & Classes   │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │  👨‍🎓 Student            │ │
│ │  Enrollment & Records   │ │
│ └─────────────────────────┘ │
└─────────────────────────────┘
```

## Color Scheme

### PDF Download Notice Box
- **Background**: Orange/Amber gradient (#fff8e1 → #ffe0b2)
- **Border**: Orange (#ff9800)
- **Icon**: Orange (#ff9800)
- **Button**: Orange (#ff9800) → Darker on hover (#f57c00)
- **Shadow**: Soft orange glow

### Navbar Buttons
- **Download PDF**: White background, red text
- **Print**: Transparent white with border

### Overall Theme
- **Primary**: Red/Crimson (#F75270, #DC143C)
- **Accent**: Pink/Peach (#F7CAC9)
- **Light**: Cream (#FDEBD0)

## Interactive Elements

### Download Buttons (3 locations)
1. **Navbar Top-Right**
   - Large button: "Download Complete PDF"
   - Mobile: Icon only (📄)

2. **Welcome Screen Center**
   - Orange box with description
   - Large download button

3. **Sidebar Footer**
   - Quick link icon (📄)
   - Tooltip on hover

### Hover Effects
- Buttons lift up slightly (translateY -2px)
- Shadow intensifies
- Color changes (darker shade)
- Smooth 0.3s transition

### Animations
- Navbar: Slides down on load
- Sidebar: Slides right on load
- PDF Notice: Fades in and moves up
- Manual Cards: Bounce effect on icon
- Back to Top: Fades in when scrolling

## Accessibility Features
- Proper ARIA labels
- Keyboard shortcuts (Ctrl+P for print)
- High contrast colors
- Clear hover states
- Descriptive tooltips
- Screen reader friendly

## File Download Behavior
1. User clicks any download button
2. Browser initiates download
3. Filename: `GRC_Complete_User_Manual.pdf`
4. File source: `/manuals/USER MANUAL.pdf`
5. No page reload required
6. Works offline (once page is loaded)
