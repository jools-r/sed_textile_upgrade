<?php
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
