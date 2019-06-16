# sed_textile_upgrade

**For Textpattern v4.7+ only**

Batch converts all your site articles marked as textile encoded, then self-terminates. Note, it makes a backup of your textpattern table before it does the conversion.

To upgrade to a new version of textile:

- If required, update your textpattern installation's version of Textile in `/textpattern/vendors/Netcarver/Textile`.
- Install the plugin.
- Activate the plugin and navigate to any other tab.
- Wait while your articles are converted.

That's it. You should now:

- find a new table, `textpattern_pre_textile_upgrade` which is a backup of your original `textpattern` table
- have all your articles that were originally marked as using textile re-encoded using the latest version
- no longer have this plugin installed.

Post upgrade:

Please **check all is well with your freshly updated articles.** If not, please use phpmyadmin (or similar) to rename your textpattern table to something (don’t just drop it) and then rename `textpattern_pre_textile_update` to `textpattern`.</p>

**NB:** You won’t be able to run the script again until you remove the table textpattern_pre_textile_upgrade.

## Changelog

- *v0.20* – update for Textpattern v4.7+ new textile parser.
