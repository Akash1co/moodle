# Week 7 Day 3 Evidence: Cynaris Stats Block

## Plugin structure

The block is located at `public/blocks/cynaris_stats/`, Moodle's plugin directory in this repository layout.

- `version.php` registers `block_cynaris_stats`, version `2026101400`, requiring Moodle `2026100500` or later.
- `block_cynaris_stats.php` defines the `block_cynaris_stats` class, initializes its localized title, and returns a localized placeholder message as block content.
- `lang/en/block_cynaris_stats.php` provides the plugin name and display message.

The block currently renders a readiness message; it does not query or expose user statistics.

## Upgrade and installation

From the Moodle repository root, with a configured Moodle site and database:

```powershell
php admin/cli/upgrade.php --non-interactive
```

The CLI script requires a repository-root `config.php`. This checkout currently has no root `config.php`, so do not run the command here unless the site's CLI configuration is set up at that expected path. Alternatively, on a configured site, sign in as an administrator and visit **Site administration > Notifications** to run the upgrade. Moodle discovers the new plugin during the upgrade. Confirm it is enabled under **Site administration > Plugins > Blocks > Manage blocks**.

## Dashboard verification

1. Sign in as an administrator and open the Moodle dashboard.
2. Turn editing on and choose **Add a block**.
3. Select **Cynaris Stats**.
4. Confirm the block title is **Cynaris Stats** and its body displays “Cynaris Stats is installed and ready for statistics.”
5. Turn editing off and reload the dashboard to confirm the block remains visible.

Record the test site's URL, Moodle version, upgrade result, and a dashboard screenshot here after performing the manual verification. No site database upgrade or UI verification was run as part of this scaffold change.