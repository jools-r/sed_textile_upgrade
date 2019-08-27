<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ('abc' is just an example).
// Uncomment and edit this line to override:
# $plugin['name'] = 'abc_plugin';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 0;

$plugin['version'] = '0.2.1';
$plugin['author'] = 'Netcarver + jcr';
$plugin['author_uri'] = 'https://github.com/jools-r/sed_textile_upgrade';
$plugin['description'] = 'Facilitates textile upgrades to your Textpattern v4.7+ article body and excerpt texts';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = 1;

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and non-AJAX admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the non-AJAX admin side
// 4 = admin+ajax   : only on admin side
// 5 = public+admin+ajax   : on both the public and admin side
$plugin['type'] = 3;

// Plugin 'flags' signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use.
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = 2;

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().

if (!defined('txpinterface'))
    @include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h1. sed_textile_upgrade

*For Textpattern v4.7+ only*

Batch converts all your site articles marked as textile encoded, then self-terminates. Note, it makes a backup of your @textpattern@ table before it does the conversion.

To upgrade to a new version of textile:

* If required, update your textpattern installation's version of Textile in @/textpattern/vendors/Netcarver/Textile@.
* Install the plugin.
* Activate the plugin and navigate to any other tab.
* Wait while your articles are converted.

That's it. You should now:

* find a new table, @textpattern_pre_textile_upgrade@ which is a backup of your original @textpattern@ table
* have all your articles that were originally marked as using textile re-encoded using the latest version
* no longer have this plugin installed.

Post upgrade:

Please *check all is well with your freshly updated articles.* If not, please use phpmyadmin (or similar) to rename your textpattern table to something (don’t just drop it) and then rename @textpattern_pre_textile_update@ to @textpattern@.

*NB:* You won’t be able to run the script again until you remove the table @textpattern_pre_textile_upgrade@.

h2. Changelog

* *v0.20* – update for Textpattern v4.7+ new textile parser.

# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---
global $event;

if( @txpinterface === 'admin' )
{
	register_callback('sed_textile_upgrade_installed', 'plugin_lifecycle.sed_textile_upgrade', 'installed' );
}


#
#	Installation handler...
#
function sed_textile_upgrade_installed( $evt, $stp )
{
	echo<<<HTML
<html>
<head>
	<title>WARNING: Site content at risk.</title>
	<link href="admin-themes/hive/assets/css/textpattern.css" rel="stylesheet" type="text/css" />
	<style type="text/css">
		div { width:40em; margin:4em auto; padding:2em 2em 1em; border:0.1em solid #ccc; text-align:center }
		ul { margin:0 0 2em; list-style:none }
		li { margin:0 0 0.5em 0 }
	</style>
</head>
<body>
	<div>
		<h1>** WARNING: <em>sed_textile_upgrade attempts to re-process ALL articles marked as using textile.</em> **</h1>
		<p>If you make the sed_textile_upgrade plugin <strong>ACTIVE</strong> (at any time),
        a backup of your textpattern table will be made as textpattern_pre_textile_upgrade and then
		all your site content marked as using textile encoding will be run through textile.
		To make use of this feature to upgrade your site to a new version of textile, first overwrite your textpattern
		installation's old copy of textile with the newer version then ENABLE this plugin and navigate to any other tab.</p>
		<p>Clicking <strong>OK</strong> will take you back to the TXP Plugins tab -- please read the help page before activating.</p>
		<p><em>Decide what you want to do when you get there:</em></p>
		<ul>
			<li>Make the plugin <strong>ACTIVE</strong> when you are ready to re-encode your textile articles&#8230;</li>
			<li>OR <strong>DELETE</strong> the plugin to prevent any possible re-encoding of your content&#8230;</li>
			<li>OR leave the plugin <strong>INACTIVE</strong> to do nothing yet.</li>
		</ul>
		<form action="index.php" method="GET">
			<input type="hidden" name="event" value="plugin">
			<input value="OK" name="sed_textile_upgrade_warning" class="publish" type="submit">
		</form>
	</div>
</body>
</html>
HTML;
	exit(0);
}


if( @txpinterface === 'admin' && $event !== 'plugin' ) # Should only happen if plugin enabled.
{
	$debug = 0;

	#
	#		Backup the textpattern table (if possible)
	#
	$sql = "CREATE TABLE ".safe_pfx('textpattern_pre_textile_upgrade')." LIKE ".safe_pfx('textpattern').";";
	$result = safe_query( $sql, $debug );
	if( $result ) {
        $sql = "INSERT INTO ".safe_pfx('textpattern_pre_textile_upgrade')." SELECT * FROM ".safe_pfx('textpattern').";";
		$result = safe_query( $sql, $debug );
		if( $result ) {
			#
			#		Table backup went ok, so go ahead and re-textile all articles that need it...
			#
			$list = getRows( "
				SELECT
					`ID`, `Body`, `textile_body`, `Excerpt`, `textile_excerpt`, `Title`
				FROM
					textpattern
				WHERE
					'1' = `textile_body` or '1' = `textile_excerpt`
				", $debug );

			if( !empty( $list ) ) {
				$textile = new \Textpattern\Textile\Parser();
				foreach( $list as $info ) {
					if( 1 == $info['textile_body'] ) {
						$set[] = "Body_html    = '" . doSlash( $textile->parse( $info['Body']    ) ) . "'";
					}

					if( 1 == $info['textile_excerpt'] )
						$set[] = "Excerpt_html = '" . doSlash( $textile->parse( $info['Excerpt'] ) ) . "'";

					$set = implode( ', ', $set );
					$result = safe_update( 'textpattern', $set, "ID = {$info['ID']}", $debug );
					unset( $set );
				}
			} else {
				echo "No textiled articles or excerpts were found.";
			}
		} else {
			echo "Could not INSERT INTO...";
		}
	} else {
		echo "Could not CREATE TABLE...";
	}


	#
	# Finally, we self-destruct unless debugging and redirect to the article list tab...
	#
	safe_delete( 'txp_plugin', "`name`='sed_textile_upgrade'", $debug );
	if( !$debug ) {
		while( @ob_end_clean() );
		header('Location: '.ahu.'index.php?event=list');
		header('Connection: close');
		header('Content-Length: 0');
	}
}
# --- END PLUGIN CODE ---

?>
