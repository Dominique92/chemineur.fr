<?php
/**
 * @package Images
 * @copyright (c) 2016 Dominique Cavailhez
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * download/file.php?id=<ID>&size=<MAX_PIXELS> resize to fit into a MAX_PIXELS square
 */

//BEST Template miniatures pour réorganisation

namespace Dominique92\Images\event;

if (!defined('IN_PHPBB'))
{
	exit;
}

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template
	) {
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->ns = explode ('\\', __NAMESPACE__);
	}

	// Liste des hooks et des fonctions associées
	static public function getSubscribedEvents() {
		return [
			// Change template
			'core.viewtopic_assign_template_vars_before' => 'viewtopic_assign_template_vars_before',
			'core.page_footer' => 'page_footer',

			//Slideshow
			'core.viewtopic_modify_post_data' => 'viewtopic_modify_post_data',

			// Image resize
			'core.download_file_send_to_browser_before' => 'download_file_send_to_browser_before',

			// Adm
			'core.adm_page_header' => 'adm_page_header',
		];
	}

//BEST n'affiche pas la première image au début du chargement (wait !)
	function viewtopic_assign_template_vars_before($vars) {
		$view = $this->request->variable ('view', 'diapo');

		// Change template if '*slideshow' is in the forum descriptor
		if ($view == 'diapo')
			$this->my_template = $this->request->variable (
				'template',
				strpos ($vars['topic_data']['forum_desc'], '*slideshow')
					? "@{$this->ns[0]}_{$this->ns[1]}/viewtopic.html"
					: ''
			);
	}

	function page_footer($vars) {
		// Assign a template
		if (@$this->my_template)
			$this->template->set_filenames([
				'body' => $this->my_template,
			]);
	}

	function viewtopic_modify_post_data($vars) {
		$previous_post = 0;
		foreach ($vars['attachments'] AS $post_id => $post_attachments) {
			$row = $vars['rowset'][$post_id];
			$row['previous_post'] = $previous_post;
			$previous_post = $post_id;

			// BBCodes
			$row['message'] = generate_text_for_display(
				$row['post_text'],
				$row['bbcode_uid'], $row['bbcode_bitfield'],
				OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES + OPTION_FLAG_LINKS
			);

			$this->template->assign_block_vars (
				'post',
				array_change_key_case ($row, CASE_UPPER)
			);

			$previous_attach = 0;
			foreach ($post_attachments AS $attachment) {
				$row['previous_attach'] = $previous_attach;
				$previous_attach = $attachment['attach_id'];
				$data = array_merge ($attachment, $row);

				// Caractères indésirables
				foreach ($data AS $k=>$v)
					$data[$k] = preg_replace (
						['/[\x00-\x1f]/s', '/"/'],
						[' ', '&quot;'],
						$v
					);

				$this->template->assign_block_vars (
					'post.slide',
					array_change_key_case ($data, CASE_UPPER)
				);
			}
		}
	}

	function download_file_send_to_browser_before($vars) {
		$attachment = $vars['attachment'];
		if (!is_dir ('../cache/images/'))
			mkdir ('../cache/images/');

		// Cas des fichiers hérités de chem2
		$attachment ['real_filename'] = str_replace ('http://v2.chemineur.fr', 'chem2', $attachment ['real_filename']);

		if (is_file('../'.$attachment['real_filename'])) // Fichier relatif à la racine du site
			$attachment ['physical_filename'] = '../'.$attachment ['real_filename']; // script = download/file.php

//BEST seulement quand l'info n'est pas dans la base / ne pas oublier d'effacer !
//BEST Date des clichés < 1970 ??? (pas d'UNIX time) => Utiliser la date EXIF (éditée) pour les clichés ???
		if ($exif = @exif_read_data ('../files/'.$attachment['physical_filename'])) {
			$fls = explode ('/', @$exif ['FocalLength']);
			if (count ($fls) == 2)
				$info[] = round($fls[0]/$fls[1]).'mm';

			$aps = explode ('/', @$exif ['FNumber']);
			if (count ($aps) == 2)
				$info[] = 'f/'.round($aps[0]/$aps[1], 1).'';

			$exs = explode ('/', @$exif ['ExposureTime']);
			if (count ($exs) == 2)
				$info[] = '1/'.round($exs[1]/$exs[0]).'s';

			if (@$exif['ISOSpeedRatings'])
				$info[] = $exif['ISOSpeedRatings'].'ASA';

			if (@$exif ['Model']) {
				if (@$exif ['Make'] &&
					strpos ($exif ['Model'], $exif ['Make']) === false)
					$info[] = $exif ['Make'];
				$info[] = $exif ['Model'];
			}

			$this->db->sql_query (implode (' ', [
				'UPDATE '.ATTACHMENTS_TABLE,
				'SET exif = "'.implode (' ', $info ?: ['~']).'",',
					'filetime = '.(strtotime(@$exif['DateTimeOriginal']) ?: @$exif['FileDateTime'] ?: @$attachment['filetime']),
				'WHERE attach_id = '.$attachment['attach_id']
			]));
		}

		// Reduction de la taille de l'image
		if ($max_size = request_var('size', 0)) {
			$img_size = @getimagesize ('../files/'.$attachment['physical_filename']);
			$isx = $img_size [0]; $isy = $img_size [1];
			$reduction = max ($isx / $max_size, $isy / $max_size);
			if ($reduction > 1) { // Il faut reduire l'image
				$pn = pathinfo ($attachment['physical_filename']);
				$temporaire = '../cache/images/'.$pn['basename'].'.'.$max_size.@$pn['extension'];

				// Si le fichier temporaire n'existe pas, il faut le creer
				if (!is_file ($temporaire)) {
					$mimetype = explode('/',$attachment['mimetype']);

					// Get source image
					$imgcreate = 'imagecreatefrom'.$mimetype[1]; // imagecreatefromjpeg / imagecreatefrompng / imagecreatefromgif
					$image_src = $imgcreate ('../files/'.$attachment['physical_filename']);

					// Detect orientation
					$angle = [
						3 => 180,
						6 => -90,
						8 =>  90,
					];
					$a = @$angle [$exif ['Orientation']];
					if ($a)
						$image_src = imagerotate ($image_src, $a, 0);
					if (abs ($a) == 90) {
						$tmp = $isx;
						$isx = $isy;
						$isy = $tmp;
					}

					// Build destination image
					$image_dest = imagecreatetruecolor ($isx / $reduction, $isy / $reduction);
					imagecopyresampled ($image_dest, $image_src, 0,0, 0,0, $isx / $reduction, $isy / $reduction, $isx, $isy);

					// Convert image
					$imgconv = 'image'.$mimetype[1]; // imagejpeg / imagepng / imagegif
					$imgconv ($image_dest, $temporaire);

					// Cleanup
					imagedestroy ($image_dest);
					imagedestroy ($image_src);
				}
				$attachment['physical_filename'] = $temporaire;
			}
		}

		$vars['attachment'] = $attachment;
	}

	/**
		ADM
	*/
	function adm_page_header() {
		$this->add_sql_column (POSTS_TABLE, 'sort', 'varchar(8)');
		$this->add_sql_column (ATTACHMENTS_TABLE, 'sort', 'varchar(8)');
		$this->add_sql_column (ATTACHMENTS_TABLE, 'exif', 'text');
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