<?php
/**
 * Geographic functions for the phpBB Forum
 *
 * @copyright (c) 2016 Dominique Cavailhez
 * @license GNU General Public License, version 2 (GPL-2.0)
 */
//TODO BUG Entre point puis transforme en ligne -> Reste le point qu’on ne peut pas enlever !

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

		// Import WRI préparation
		if (defined('GEOBB_IMPORT_WRI') &&
			is_file('IMPORT/commentaires.json')
		) {
			$comments_a_importer = json_decode (
				file_get_contents(
				'IMPORT/commentaires.json'
				)
			);

			/*
			foreach ($comments_a_importer as $comment) {
				// Création fichiers pour chaque commentaire
				file_put_contents(
					'IMPORT_COM/'.$comment->id_point.'_'.$comment->id_commentaire.'.json', 
					json_encode($comment)
				);

				// Vérification présence fichier image
				$fiais = glob(
					__DIR__.'/../../../../files/'.
					$comment->id_commentaire.'*.*'
				);
				if($comment->photo && !count($fiais))
					echo __LINE__."Pas de photo point=$comment->id_point, ".
						"comment=$comment->id_commentaire<br/>";
			}
			exit;
			*/

			$sql = 'SELECT topic_id, forum_id, post_edit_reason FROM ' . POSTS_TABLE . "
				WHERE post_edit_reason LIKE 'Transfert WRI%'";
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result)) {
				preg_match_all('/[0-9]+/u', $row['post_edit_reason'], $rws);
				if(isset($rws[0][0]))
					$this->points_posted[$rws[0][0]] = $row['topic_id'];
				if(isset($rws[0][1]))
					$this->comments_posted[$rws[0][1]] = $row['topic_id'];
			}
			$db->sql_freeresult($result);

			$fcais = glob(__DIR__.'/../../../../IMPORT_COM/*.json');
			foreach ($fcais as $fc) {
				preg_match('/([0-9]+)_([0-9]+)/u', $fc, $fcs);
				if (!isset ($this->points_posted[$fcs[1]]))
					$this->points_to_post[$fcs[1]] = $fcs[1];
				elseif (!isset ($this->comments_posted[$fcs[2]]))
					$this->comments_to_post[$fcs[2]] = $fcs[1];
			}
			if (isset($this->comments_to_post))
				ksort($this->comments_to_post);
		}
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
		global $mapKeys, $myolName;
		$this->template->assign_var ('MAP_KEYS', json_encode (@$mapKeys));
		$this->template->assign_var ('MYOLNAME', $myolName ?: 'myol');
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
		global $user;

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
		global $user;

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
		$topic_row['topic_category'] = $topic_data['parent_id'];

		// How to display the topic
		//BEST map on all posts (":xxxxx")
		preg_match ('/([\.:])(point|line|poly)/', $topic_data['forum_desc'], $desc);
		if ($desc) {
			// Page style
			$view = $this->request->variable ('view', 'geo');
			$topic_row['body_class'] = $view.' '.$view.'_'.$desc[2];

			// Positions
			preg_match_all ('/\[(-?[0-9.]+), ?(-?[0-9.]+)\]/', @$topic_row['geo_json'], $lls);
			if ($lls[0]) {
				$topic_row['forum_image'] = $topic_data['forum_image'];
				$topic_row['map_type'] = $desc[2];
				$topic_row['geo_lon'] = $lls[1][0]; // For OSM search link
				$topic_row['geo_lat'] = $lls[2][0];
				$post_row['LON_LAT'] = $lls[0];

				// Altitude calculation
				if (array_key_exists ('geo_altitude', $topic_row) &&
					!@$topic_row['geo_altitude'] &&
					count($lls[2]) == 1 ) { // Trace : too large for GET parameter

					// Use free API
					$url = 'https://api.open-elevation.com/api/v1/lookup?locations=';
					$lat = $lls[2][0];
					$lon = $lls[1][0] -= round ($lls[1][0] / 360) * 360; // Avoid wrap
					@$ret = file_get_contents ($url.$lat.','.$lon);

					if ($ret) {
						$result = json_decode ($ret);
						$topic_row['geo_altitude'] = $result->results[0]->elevation.'~';

						// Update the database for the next time
						$sql = "UPDATE phpbb_posts SET geo_altitude = '{$topic_row['geo_altitude']}' WHERE post_id = $post_id";
						$this->db->sql_query($sql);
					}
				}

				// Détermination du massif par refuges.info
				if (array_key_exists ('geo_massif', $topic_row) && !$topic_row['geo_massif']) {
					$f_wri_export = 'https://www.refuges.info/api/polygones?type_polygon=1,10,11,17&bbox='.
						$lls[1][0].','.$lls[2][0].','.$lls[1][0].','.$lls[2][0];
					$wri_export = json_decode (@file_get_contents ($f_wri_export));

					// Récupère tous les polygones englobants
					if($wri_export->features)
						foreach ($wri_export->features AS $f)
							$ms [$f->properties->type->id] = $f->properties->nom;

					// Trie le type de polygone le plus petit
					if (isset ($ms)) {
						ksort ($ms);

						// Update the template data
						$topic_row['geo_massif'] = $ms[array_keys($ms)[0]].'~';
					} else
						$topic_row['geo_massif'] = '~';

					// Update the database for next time
					$sql = "UPDATE phpbb_posts SET geo_massif = '".addslashes ($topic_row['geo_massif'])."' WHERE post_id = $post_id";
					$this->db->sql_query($sql);
				}
			}
		}

		// Remove the extra ~ before display
		foreach ($topic_row AS $k=>$v)
			$topic_row[$k] = str_replace ('~', '', $v);

		if ($post_id == $topic_first_post_id)
			$this->template->assign_vars (array_change_key_case ($topic_row, CASE_UPPER));

		// Import WRI demande import commentaire
		if (defined('GEOBB_IMPORT_WRI') &&
			$user->data['user_id'] == 2 &&
			is_file('IMPORT/commentaires.json') &&
			isset($this->comments_to_post)
		) {
			$comment_id_to_post = array_keys($this->comments_to_post)[0];
			$point_id_to_post = $this->comments_to_post[$comment_id_to_post];
			$topic_id_where_post = $this->points_posted[$point_id_to_post];
			$this->template->assign_vars ([
				'AUTO_SUBMIT_TOPIC' => $topic_id_where_post,
				'AUTO_SUBMIT_POINT' => $point_id_to_post,
				'AUTO_SUBMIT_COMMENT' => $comment_id_to_post,
				'COMMENTS_TO_POST' => count($this->comments_to_post),
			]);
		}

		$vars['post_row'] = $post_row;
	}

	/**
		POSTING
	*/
	// Called when displaying the page
	function posting_modify_template_vars($vars) { // posting.php 2089
		global $request, $db, $user, $attachment_data;

		$post_data = $vars['post_data'];
		$page_data = $vars['page_data'];

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
				if ($row) {
					// Protect empty lines
					$geo_json = json_decode ($row['geo_json']);
					if (!$geo_json)
						$row['geo_json'] = '{"type":"GeometryCollection","geometries":[]}';

					foreach ($row AS $k=>$v)
						if (strpos ($v, '~') !== false)
							$row[$k] = ''; // Erase the field if generated automatically

					$this->template->assign_vars (array_change_key_case ($row, CASE_UPPER));
				}
				$this->db->sql_freeresult($result);
			}
		}

		// Imports WRI
		if (defined('GEOBB_IMPORT_WRI') &&
			$user->data['user_id'] == 2 &&
			is_file('IMPORT/commentaires.json')
		) {
			$points_a_importer = '{"features":[]}';

			// Sommets à importer
			// https://www.refuges.info/api/bbox?type_points=6&nb_points=all
			if($vars['mode'] == 'post' && $vars['forum_id'] == 24)
				$points_a_importer = file_get_contents('IMPORT/points_sommet.json');

			// Lacs à importer
			//https://www.refuges.info/api/bbox?type_points=16&nb_points=all
			if($vars['mode'] == 'post' && $vars['forum_id'] == 26)
				$points_a_importer = file_get_contents('IMPORT/points_lacs.json');

			// Import point WRI
			foreach (json_decode ($points_a_importer)->features AS $pai)
				if (!isset ($this->points_posted[$pai->id])) {
					$fn='https://dom.refuges.info/api/point'.
						'?format=geojson&format_texte=texte&detail=complet&id='.$pai->id;
					$fd = json_decode(file_get_contents($fn));
					$ff = $fd->features[0];
					$fp = $ff->properties;

					$this->template->assign_var ('GEO_JSON', json_encode($ff->geometry));
					$page_data['DRAFT_SUBJECT'] = $fp->nom;
					$page_data['S_EDIT_REASON'] = true;
					$page_data['EDIT_REASON'] = 'Transfert WRI refuges.info/point/'.$pai->id;
					$page_data['DRAFT_MESSAGE'] = '';
					$page_data['S_DISPLAY_USERNAME'] = true;
					$page_data['USERNAME'] = $fp->createur->nom ?: 'refuges.info';
					$page_data['AUTO_SUBMIT'] = true;
					$props = [
						'acces' => 'Accès',
						'remarque' => 'Remarques',
						'proprio' => 'Propriétaire',
					];
					foreach ($props AS $k => $p)
						if(trim($fp->$k->valeur))
							$page_data['DRAFT_MESSAGE'] .=
								'[b]'.$props[$k].'[/b]'.PHP_EOL
								.trim($fp->$k->valeur).PHP_EOL.PHP_EOL;
					$page_data['DRAFT_MESSAGE'] .= 'Importation de refuges.info/point/'.$pai->id;
					break;
				}

			// Import commentaire WRI
			$point_id = $request->variable('point_wri', '', 0);
			$comment_id = $request->variable('comment_wri', '', 0);
			if($vars['mode'] == 'reply' && $point_id && $comment_id) {
				$filename_comment_json = __DIR__.'/../../../../IMPORT_COM/'.$point_id.'_'.$comment_id.'.json';

				if (!is_file ($filename_comment_json)) {
					echo "Fichier inexistant : $filename_comment_json";
					exit;
				} else {
					$comment = json_decode (file_get_contents ($filename_comment_json));

					// Commentaire
					$page_data['DRAFT_MESSAGE'] = $comment->commentaire;
					$page_data['S_DISPLAY_USERNAME'] = true;
					$page_data['USERNAME'] = $comment->auteur;
					$page_data['S_EDIT_REASON'] = true;
					$page_data['EDIT_REASON'] = "Transfert WRI refuges.info/point/$point_id#C$comment_id";
					$page_data['AUTO_SUBMIT'] = true;

					// Image attachée
					$fiais = glob(__DIR__.'/../../../../files/'.$comment_id.'*.*');

					if($comment->photo && !count($fiais)) {
						echo "Pas de photo point=$point_id, comment=$comment_id<br/>";
						exit;
					}
					if(count($fiais)) {
						// Create attachment item in attachment table
						$sql_ary = array(
							'topic_id'			=> $post_data['topic_id'],
							'in_message'		=> 0,
							'poster_id'			=> 2,
							'is_orphan'			=> 1,
							'physical_filename'	=> basename($fiais[0]),
							'real_filename'		=> basename($fiais[0]),
							'attach_comment'	=> "Transfert WRI refuges.info/point/$point_id#C$comment_id",
							'extension'			=> pathinfo($fiais[0], PATHINFO_EXTENSION),
							'mimetype'			=> mime_content_type ($fiais[0]),
							'filesize' 			=> filesize ($fiais[0]),
						);
						$db->sql_query('INSERT INTO ' . ATTACHMENTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));

						// Add it to the message
						$attachment_data = [[
							'attach_id'			=> $db->sql_nextid(),
							'is_orphan'			=> 0,
							'real_filename'		=> basename($fiais[0]),
							'attach_comment'	=> "Transfert WRI refuges.info/point/$point_id#C$comment_id",
							'filesize'			=> filesize ($fiais[0]),
						]];
					}
				}
			}
		}

		$vars['post_data'] = $post_data;
		$vars['page_data'] = $page_data;
	}

	// Called when validating the data to be saved
	function submit_post_modify_sql_data($vars) {
		global $request, $user;

		$sql_data = $vars['sql_data'];
		$post = $this->request->get_super_global(\phpbb\request\request_interface::POST);

		// Retrieves the values of the questionnaire, includes them in the phpbb_posts table
		if (@$post['geom']) {
			// Avoid wrap of the world
			$geom = preg_replace_callback(
				'/coordinates\"\:\[(-?[0-9.]+)/',
				function ($matches) {
					return 'coordinates":['.($matches[1] - round ($matches[1] / 360) * 360);
				},
				$post['geom']
			);
			$sql_data[POSTS_TABLE]['sql']['geom'] = "ST_GeomFromGeoJSON('$geom')";
		}

		$sql_data[POSTS_TABLE]['sql']['geo_altitude'] = @$post['geo_altitude'];
		$sql_data[POSTS_TABLE]['sql']['geo_massif'] = @$post['geo_massif'];

		// Import WRI / mise à jour infos au moment de la création du post
		if (defined('GEOBB_IMPORT_WRI') &&
			$user->data['user_id'] == 2 &&
			is_file('IMPORT/commentaires.json')
		) {
			$sql_data[POSTS_TABLE]['sql']['post_edit_reason'] = $request->variable('edit_reason', '', true);
			$sql_data[POSTS_TABLE]['sql']['poster_id'] = 1;
			$vars['username'] =
			$sql_data[POSTS_TABLE]['sql']['post_username'] =
				$request->variable('username', '', true);
		}

		$vars['sql_data'] = $sql_data; // Return data
	}

	/**
		ADM
	*/
	function adm_page_header() {
		$this->add_sql_column (POSTS_TABLE, 'geom', 'geometrycollection');
		$this->add_sql_column (POSTS_TABLE, 'geo_massif', 'varchar(50)');
		$this->add_sql_column (POSTS_TABLE, 'geo_altitude', 'text');

		//HACK (horrible !) to accept geom spatial feild
		$file_name = $this->phpbb_root_path."phpbb/db/driver/driver.php";
		$file_tag = "\n\t\tif (is_null(\$var))";
		$file_patch = "\n\t\tif (strpos(\$var, 'GeomFrom') !== false)\n\t\t\treturn \$var;";
		$file_content = file_get_contents ($file_name);
		if (strpos($file_content, '{'.$file_tag))
			file_put_contents ($file_name, str_replace ('{'.$file_tag, '{'.$file_patch.$file_tag, $file_content));
	}

	function add_sql_column ($table, $column, $type) {
		$sql ="SHOW columns FROM $table LIKE '$column'" ;
		$result = $this->db->sql_query($sql);
		if (!$this->db->sql_fetchrow($result))
			$this->db->sql_query(
				"ALTER TABLE $table ADD $column $type"
			);
		$this->db->sql_freeresult($result);
	}
}
