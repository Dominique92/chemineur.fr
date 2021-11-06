<?php
/**
 * Geographic functions for the phpBB Forum
 *
 * @copyright (c) 2016 Dominique Cavailhez
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Dominique92\GeoBB\event;

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
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template,
		$phpbb_root_path
	) {
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->server = $this->request->get_super_global(\phpbb\request\request_interface::SERVER);
	}

	// List of hooks and related functions
	// We find the calling point by searching in the software of PhpBB 3.x: "event core.<XXX>"
	static public function getSubscribedEvents() {
		return [
			// Common
			'core.common' => 'common',

			// Index
			'core.index_modify_page_title' => 'index_modify_page_title',

			// Viewtopic
			'core.viewtopic_get_post_data' => 'viewtopic_get_post_data',
			'core.viewtopic_post_rowset_data' => 'viewtopic_post_rowset_data',
			'core.viewtopic_modify_post_row' => 'viewtopic_modify_post_row',

			// Posting
			'core.posting_modify_template_vars' => 'posting_modify_template_vars',
			'core.submit_post_modify_sql_data' => 'submit_post_modify_sql_data',

			// Adm
			'core.adm_page_header' => 'adm_page_header',
		];
	}

	/**
		COMMON
	*/
	function common($vars) {
		global $mapKeys;
		preg_match ('/Trident/', @$this->server['HTTP_USER_AGENT'], $match);

		$this->template->assign_vars ([
			'MAP_KEYS' => json_encode (@$mapKeys),
			'IS_IE' => $match ? 'IE' : '',
		]);
	}

	/**
		INDEX.PHP
	*/
	function index_modify_page_title($vars) {
		$this->template->assign_var ('MAP_TYPE', 'index');
	}

	/**
		VIEWTOPIC.PHP
	*/
	// Appelé avant la requette SQL qui récupère les données des posts
	function viewtopic_get_post_data($vars) {
		// Insère la conversion du champ geom en format geojson dans la requette SQL
		$sql_ary = $vars['sql_ary'];
		$sql_ary['SELECT'] .=
			', ST_AsGeoJSON(geom) AS geo_json'.
			', ST_AsGeoJSON(ST_Centroid(ST_Envelope(geom))) AS geo_center';
		$vars['sql_ary'] = $sql_ary;
	}

	// Called during first pass on post data that read phpbb-posts SQL data
	function viewtopic_post_rowset_data($vars) {
		// Conserve les valeurs spécifiques à chaque post du template
		$post_id = $vars['row']['post_id'];
		foreach ($vars['row'] AS $k=>$v)
			if (strpos ($k,'geo_') === 0)
				$this->geo_data[$post_id][$k] = str_replace ('~', '', $v);
	}

	// Modify the posts template block
	function viewtopic_modify_post_row($vars) {
		// Valeurs du post lues dans la table phpbb_posts
		$row = $vars['row'];
		$post_row = $vars['post_row'];
		$post_id = $row['post_id'];

		// Valeurs du topic
		$topic_data = $vars['topic_data'];
		$topic_first_post_id = $topic_data['topic_first_post_id'];

		// Valeurs à assigner à tout le template (topic)
		$topic_row = $this->geo_data[$post_id]; // The geo_ values
		$topic_row['topic_first_post_id'] = $topic_first_post_id;

		// How to display the topic
		//BEST map on all posts (":xxxxx")
		preg_match ('/([\.:])(point|line|poly)/', $topic_data['forum_desc'], $desc);
		if ($desc) {
			$view = $this->request->variable ('view', 'geo');
			$topic_row['body_class'] = $view.' '.$view.'_'.$desc[2];

			// Position
			preg_match ('/\[([-0-9\.]*)[, ]*([-0-9\.]*)\]/', $topic_row['geo_json'], $ll);
			if ($ll) {
				$topic_row['forum_image'] = $topic_data['forum_image'];
				$topic_row['map_type'] = $desc[2];
				$topic_row['geo_lon'] = $ll[1]; // For OSM search link
				$topic_row['geo_lat'] = $ll[2];
				$post_row['LON_LAT'] = $ll[0];

				// Calcul de l'altitude avec mapquest
				global $mapKeys;
				if (array_key_exists ('geo_altitude', $topic_row) && !$topic_row['geo_altitude'] &&
					@$mapKeys['keys-mapquest']) {
					$mapquest = 'http://open.mapquestapi.com/elevation/v1/profile?key='.
						$mapKeys['keys-mapquest'].
						'&latLngCollection='.
						$ll[2].','.$ll[1];
					preg_match('/"height":([-0-9]+)/', @file_get_contents ($mapquest), $match);

					// Update the template data
					$topic_row['geo_altitude'] = $match ? $match[1].'~' : '~';

					// Update the database for next time
					$sql = "UPDATE phpbb_posts SET geo_altitude = '{$topic_row['geo_altitude']}' WHERE post_id = $post_id";     
					$this->db->sql_query($sql);
				}

				// Détermination du massif par refuges.info
				if (array_key_exists ('geo_massif', $topic_row) && !$topic_row['geo_massif']) {
					$f_wri_export = 'http://www.refuges.info/api/polygones?type_polygon=1,10,11,17&bbox='.
						$ll[1].','.$ll[2].','.$ll[1].','.$ll[2];
					$wri_export = json_decode (@file_get_contents ($f_wri_export));
					// récupère tous les polygones englobantz
					if($wri_export->features)
						foreach ($wri_export->features AS $f)
							$ms [$f->properties->type->id] = $f->properties->nom;
					// Trie le type de polygone le plus petit
					if (isset ($ms))
						ksort ($ms);

					// Update the template data
					$topic_row['geo_massif'] = @$ms[array_keys($ms)[0]] ? $ms[array_keys($ms)[0]].'~' : '~';

					// Update the database for next time
					$sql = "UPDATE phpbb_posts SET geo_massif = '".addslashes ($topic_row['geo_massif'])."' WHERE post_id = $post_id";
					$this->db->sql_query($sql);
				}
			}

			// Calcul du cluster (managé par le serveur)
			if (array_key_exists ('geo_cluster', $topic_row) && !$topic_row['geo_cluster']) {
				$clusters_by_degree = 10;
				$geo_center = json_decode ($topic_row['geo_center'])->coordinates;
				$topic_row['geo_cluster'] =
					intval ((180 + $geo_center[0]) * $clusters_by_degree) * 360 * $clusters_by_degree +
					intval ((180 + $geo_center[1]) * $clusters_by_degree);

					// Update the database for next time
					$sql = "UPDATE phpbb_posts SET geo_cluster = '{$topic_row['geo_cluster']}' WHERE post_id = $post_id";
					$this->db->sql_query($sql);
			}
		}

		// remove the extra ~ before dispplay
		foreach ($topic_row AS $k=>$v)
			$topic_row[$k] = str_replace ('~', '', $v);

		if ($post_id == $topic_first_post_id)
			$this->template->assign_vars (array_change_key_case ($topic_row, CASE_UPPER));

		$vars['post_row'] = $post_row;
	}

	/**
		POSTING
	*/
	// Called when displaying the page
	function posting_modify_template_vars($vars) {
		$post_data = $vars['post_data'];
		preg_match ('/([\.:])(point|line|poly)/', $post_data['forum_desc'], $params);

		if ($params && (
				$params[1] == ':' || // Map on all posts
				@$post_data['post_id'] == @$post_data['topic_first_post_id'] // Only map on the first post
			)) {
			$this->template->assign_var ('MAP_TYPE', $params[2]);

			// Get translation of SQL space data
			if (@$post_data['post_id']) { // Only modification
				$sql = 'SELECT ST_AsGeoJSON(geom) AS geo_json, geo_altitude, geo_massif'.
					' FROM '.POSTS_TABLE.
					' WHERE post_id = '.$post_data['post_id'];
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);
				if ($row) {
					foreach ($row AS $k=>$v)
						if (strpos ($v, '~') !== false)
							$row[$k] = ''; // Erase the field if generated automatically
					$this->template->assign_vars (array_change_key_case ($row, CASE_UPPER));
				}
			}
		}
	}

	// Called when validating the data to be saved
	function submit_post_modify_sql_data($vars) {
		$sql_data = $vars['sql_data'];
		$post = $this->request->get_super_global(\phpbb\request\request_interface::POST);

		// Retrieves the values of the questionnaire, includes them in the phpbb_posts table
		if (@$post['geom']) {
			// Avoid wrap of the world
			$geom = preg_replace_callback(
				'/coordinates\"\:\[([0-9-.]+)/',
				function ($matches) {
					return 'coordinates":['.($matches[1] - round ($matches[1] / 360) * 360);
				},
				$post['geom']
			);
			$sql_data[POSTS_TABLE]['sql']['geom'] = "ST_GeomFromGeoJSON('$geom')";
		}

		$sql_data[POSTS_TABLE]['sql']['geo_altitude'] = @$post['geo_altitude'];
		$sql_data[POSTS_TABLE]['sql']['geo_massif'] = @$post['geo_massif'];

		$vars['sql_data'] = $sql_data; // Return data
	}

	/**
		ADM
	*/
	function adm_page_header() {
		$this->add_sql_column (POSTS_TABLE, 'geom', 'geometrycollection');
		$this->add_sql_column (POSTS_TABLE, 'geo_cluster', 'int');
		$this->add_sql_column (POSTS_TABLE, 'geo_massif', 'varchar(50)');
		$this->add_sql_column (POSTS_TABLE, 'geo_altitude', 'varchar(12)');

		//HACK (horrible !) to accept geom spatial feild
		$file_name = $this->phpbb_root_path."phpbb/db/driver/driver.php";
		$file_tag = "\n\t\tif (is_null(\$var))";
		$file_patch = "\n\t\tif (strpos(\$var, 'GeomFrom') !== false)\n\t\t\treturn \$var;";
		$file_content = file_get_contents ($file_name);
		if (strpos($file_content, '{'.$file_tag))
			file_put_contents ($file_name, str_replace ('{'.$file_tag, '{'.$file_patch.$file_tag, $file_content));
	}

	function add_sql_column ($table, $column, $type) {
		$result = $this->db->sql_query(
			"SHOW columns FROM $table LIKE '$column'"
		);
		if (!$this->db->sql_fetchrow($result))
			$this->db->sql_query(
				"ALTER TABLE $table ADD $column $type"
			);
		$this->db->sql_freeresult($result);
	}
}
