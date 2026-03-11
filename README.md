# Vet CPD Directory

A WordPress plugin for managing Veterinary Continuing Professional Development (CPD) events.

## Description

This plugin replaces The Events Calendar suite for veterinary CPD websites. It provides a streamlined system for listing CPD events, venues, organizers, instructors, and series without the complexity of recurring events or ticketing systems.

## Features

- **CPD Events** - List and manage veterinary CPD events
- **Venues** - Store venue information with map support
- **People** - Combined organizers and instructors with role selection
- **Series** - Group related CPDs (Part 1, Part 2, etc.)
- **Categories** - Hierarchical subject categories (39 veterinary specialties)
- **Tags** - Status tags: upcoming, on-demand, online, free
- **Auto-tagging** - Automatically tags events as "upcoming" or "on-demand" based on date
- **Shortcodes** - Display CPD lists with filters

## Installation

1. Upload `vet-cpd-directory` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Run migration (see below)

## Migration from The Events Calendar

### Prerequisites
- Keep The Events Calendar plugins active during migration
- Ensure Vet CPD Directory plugin is activated

### Running Migration

**Option 1: Via Browser (Recommended)**
1. Log in as admin
2. Visit: `https://yoursite.com/wp-content/plugins/vet-cpd-directory/migration.php`
3. Watch the on-screen progress
4. Verify migration completed successfully

**Option 2: Via WP-CLI**
```bash
wp eval-file wp-content/plugins/vet-cpd-directory/migration.php
```

### What the Migration Does

1. Creates system tags: upcoming, on-demand, online, free
2. Migrates venues (tribe_venue → cpd_venue)
3. Migrates organizers (tribe_organizer → cpd_person with organizer role)
4. Migrates instructors (tribe_ext_instructor → cpd_person with instructor role)
5. Migrates series (tribe_event_series → cpd_series)
6. Migrates events (tribe_events → cpd_event)
7. Converts 4 status categories to tags
8. Preserves 39 subject categories
9. Fixes shortcodes in posts/pages

### Post-Migration Steps

1. **Test the site:**
   - Visit /cpd/ to check event listing
   - Check category archives
   - Verify shortcodes work on listing pages

2. **Deactivate old plugins:**
   - The Events Calendar
   - The Events Calendar Pro
   - Custom Label extension
   - Instructor Linked Post Type extension

3. **Delete migration file:**
   ```bash
   rm wp-content/plugins/vet-cpd-directory/migration.php
   ```

## Shortcodes

- `[cpd_list]` - Display all CPD events
- `[cpd_list tag="free"]` - Display free CPDs
- `[cpd_list tag="upcoming"]` - Display upcoming CPDs
- `[cpd_list tag="on-demand"]` - Display on-demand CPDs
- `[cpd_list tag="online"]` - Display online CPDs
- `[cpd_list category="canine"]` - Display CPDs by category

## URL Changes

| Old | New |
|-----|-----|
| /events/ | /cpd/ |
| /events/category/ | /cpd-category/ |
| /event/ | /cpd/ |
| /venues/ | /venue/ |
| /organizers/ | /person/ |
| /instructors/ | /person/ |

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Classic Editor (meta boxes optimized for classic editor)

## Changelog

### 1.0.0
- Initial release
- Migration from The Events Calendar
- CPD Events, Venues, People, Series CPTs
- Auto-tagging for upcoming/on-demand
- Frontend templates and shortcodes

## Credits

Developed for Wendy Nevins veterinary CPD directory.
