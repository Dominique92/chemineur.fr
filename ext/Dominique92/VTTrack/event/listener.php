<?php
/**
 * Generate feed for VTTrack.fr
 *
 * @copyright (c) 2021 Dominique Cavailhez
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
//BEST GIS couches point d'eau plus représentative => Import WRI / OMS ?
//BEST Fusion points WRI non présents dans Chemineur

namespace Dominique92\VTTrack\event;

if (!defined('IN_PHPBB'))
{
	exit;
}

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	// List of externals
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request_interface $request
	) {
		$this->db = $db;
		$this->server = $request->get_super_global(\phpbb\request\request_interface::SERVER);
	}

	static public function getSubscribedEvents() {
		// List of hooks and related functions
		// We find the calling point by searching in the software of PhpBB 3.x: "event core.<XXX>"
		return [
			'core.feed_base_modify_item_sql' => 'feed_base_modify_item_sql',
		];
	}

	/**
		Feed
	*/
	function feed_base_modify_item_sql() {
		if (strpos ($this->server['REQUEST_URI'], 'poi.gml.php')) {
			echo '<?xml version="1.0" encoding="ISO-8859-1"?>'.PHP_EOL.
				'<FeatureCollection xmlns:gml="http://www.opengis.net/gml">';

			$poi = request_var ('poi', 'hebergement');
			$in = $poi[0] == 'h'
				? 'c.forum_id IN(3,8)' // Refuges & abris
				: 'f.forum_id IN(21)'; // Points d'eau
			$bboxs = explode (',', $bbox = request_var ('bbox', '-180,-90,180,90'));
			$bbox_sql =
				$bboxs[0].' '.$bboxs[1].','.
				$bboxs[2].' '.$bboxs[1].','.
				$bboxs[2].' '.$bboxs[3].','.
				$bboxs[0].' '.$bboxs[3].','.
				$bboxs[0].' '.$bboxs[1];
			$sql = "
				SELECT p.post_subject, p.post_id, ST_AsGeoJSON(geom) AS geo_json ,
					t.topic_id,
					f.forum_id, f.forum_name, f.forum_image
				FROM ".POSTS_TABLE." AS p
					LEFT JOIN ".TOPICS_TABLE." AS t ON(t.topic_id = p.topic_id)
					LEFT JOIN ".FORUMS_TABLE." AS f ON(f.forum_id = p.forum_id)
					LEFT JOIN ".FORUMS_TABLE." AS c ON(c.forum_id = f.parent_id)
				WHERE f.forum_image REGEXP '.png$'
					AND $in
					AND Intersects (GeomFromText ('POLYGON (($bbox_sql))',4326),geom)
				LIMIT 250
			";

			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result)) {
				preg_match ('/\[([0-9\.]+,[0-9\.]+)\]/', str_replace (' ', '', json_encode ($row['geo_json'])), $ll);
				preg_match ('/([a-z_]+).png/', $row['forum_image'], $icon);
				if ($ll && $icon)
					echo "
	<gml:featureMember>
		<point>
			<site>chemineur.fr</site>
			<type>../../ext/Dominique92/GeoBB/icones/{$icon[1]}</type>
			<gml:Point>
				<gml:coordinates decimal=\".\" cs=\",\" ts=\" \">{$ll[1]}</gml:coordinates>
			</gml:Point>
			<url>https://chemineur.fr/viewtopic.php?t={$row['topic_id']}</url>
		</point>
	</gml:featureMember>
";
			}
			$this->db->sql_freeresult($result);

			echo '</FeatureCollection>';
			exit; // Don't display phpBB feeds
		}
	}
}