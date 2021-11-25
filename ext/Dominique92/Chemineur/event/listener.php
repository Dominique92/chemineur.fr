<?php
/**
 * Chemineur specific functions & style for the phpBB Forum
 *
 * @copyright (c) 2020 Dominique Cavailhez
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Dominique92\Chemineur\event;

if (!defined('IN_PHPBB'))
{
	exit;
}

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	// List of externals
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\auth\auth $auth,
		\phpbb\language\language $language
	) {
		$this->config = $config;
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->language = $language;
	}

	static public function getSubscribedEvents() {
		return [
			// All
			'core.page_header' => 'page_header',

			// Index
			'core.index_modify_page_title' => 'index_modify_page_title',

			// Viewtopic
			'core.viewtopic_before_f_read_check' => 'viewtopic_before_f_read_check',
			'core.viewtopic_modify_post_data' => 'viewtopic_modify_post_data',
			'core.viewtopic_modify_post_row' => 'viewtopic_modify_post_row',
			'core.parse_attachments_modify_template_data' => 'parse_attachments_modify_template_data',

			// posting
			'core.modify_posting_auth' => 'modify_posting_auth',
		];
	}

	/**
		ALL
	*/
	function page_header() {
		/* Includes language files of this extension */
		$ns = explode ('\\', __NAMESPACE__);
		$this->language->add_lang('common', $ns[0].'/'.$ns[1]);

		// Liste des catégories de points à ajouter
		$sql = "
			SELECT cat.forum_id AS category_id,
				cat.forum_name AS category_name,
				cat.forum_id,
				type.forum_id AS first_forum_id,
				type.parent_id AS category,
				type.forum_desc,
				type.forum_desc REGEXP '.point|.line' AS geo
			FROM ".FORUMS_TABLE." AS type
			JOIN ".FORUMS_TABLE." AS cat ON cat.forum_id = type.parent_id
			WHERE type.forum_type = ".FORUM_POST."
				AND cat.forum_type = ".FORUM_CAT."
				AND cat.parent_id = 0
			ORDER BY type.left_id
		";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
			if (!@$cat_done[$row['category_id']]++) // Seulement une fois par catégorie
				$this->template->assign_block_vars (
					'geo_categories',
					array_change_key_case (
						preg_replace ('/s( |$)/i', '$1', $row), // Enlève les s en fin de mot
						CASE_UPPER
					)
				);
		$this->db->sql_freeresult($result);
	}

	/**
		INDEX.PHP
	*/
	// Affiche les post les plus récents sur la page d'accueil
	function index_modify_page_title ($vars) {
		global $auth; //BEST intégrer aux variables du listener ($this->auth)

		$nouvelles = request_var ('nouvelles', 20);
		$this->template->assign_var ('PLUS_NOUVELLES', $nouvelles * 2);

		$sql = "
			SELECT t.topic_id, topic_title,
				t.forum_id, forum_name, forum_image,
				topic_first_post_id, p.post_id, p.post_attachment, topic_posts_approved,
				username, p.poster_id, p.post_time, p1.geo_massif
			FROM	 ".TOPICS_TABLE." AS t
				JOIN ".FORUMS_TABLE." AS f USING (forum_id)
				JOIN ".POSTS_TABLE ." AS p ON (p.post_id = t.topic_last_post_id)
				JOIN ".POSTS_TABLE ." AS p1 ON (p1.post_id = t.topic_first_post_id)
				JOIN ".USERS_TABLE."  AS u ON (p.poster_id = u.user_id)
			WHERE p.post_visibility = ".ITEM_APPROVED."
			ORDER BY p.post_time DESC
			LIMIT ".$nouvelles;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
			if ($auth->acl_get('f_read', $row['forum_id'])) {
				$row ['topic_comments'] = $row['topic_posts_approved'] - 1;
				$row ['post_time'] = $this->user->format_date ($row['post_time']);
				$row ['geo_massif'] = str_replace ('~', '', $row ['geo_massif']);
				$this->template->assign_block_vars('nouvelles', array_change_key_case ($row, CASE_UPPER));
			}
		$this->db->sql_freeresult($result);

		// Affiche un message de bienvenu dépendant du style pour ceux qui ne sont pas connectés
		// Le texte de ces messages sont dans les posts dont le titre est !style
		$sql = "SELECT post_text,bbcode_uid,bbcode_bitfield FROM ".POSTS_TABLE." WHERE post_subject LIKE '!{$this->user->style['style_name']}'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->template->assign_var ('GEO_PRESENTATION', generate_text_for_display($row['post_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], OPTION_FLAG_BBCODE, true));
		$this->db->sql_freeresult($result);
	}

	/**
		VIEWTOPIC.PHP
	*/
	public function viewtopic_before_f_read_check($vars) { // ligne 370
		// Supprime la pagination
		$this->config['posts_per_page'] = 1000;
	}

	// Modify the posts template block
	function viewtopic_modify_post_row($vars) {
		$post_row = $vars['post_row'];
		$topic_data = $vars['topic_data'];
		$row = $vars['row'];

		// Détermine si le titre du post est une réponse
		if ($row['post_id'] != $topic_data['topic_first_post_id'] &&
			strncasecmp ($row['post_subject'], 'Re: ', 4))
			$post_row['POST_SUBJECT_OPTIM'] = str_replace ('Re: ', '', $row['post_subject']);

		$vars['post_row'] = $post_row;
	}

	function viewtopic_modify_post_data($vars) {
		// Mem for parse_attachments_modify_template_data
		$this->attachments = $vars['attachments'];
	}

	function parse_attachments_modify_template_data($vars) {
		if (@$this->attachments) {
			$post_id = $vars['attachment']['post_msg_id'];

			//BEST déplacer dans ext/Dominique92/Images
			// Assigne les valeurs au template
			$this->block_array = $vars['block_array'];
			$this->block_array['TEXT_SIZE'] = strlen (@$this->post_data[$post_id]['post_text']) * count($this->attachments[$post_id]);
			$this->block_array['DATE'] = str_replace (' 00:00', '', $this->user->format_date($vars['attachment']['filetime']));
			$this->block_array['AUTEUR'] = $vars['row']['user_sig'];
			//BEST Retrouver le nom du "poster_id" : $vars['attachment']['poster_id'] ??
			$this->block_array['EXIF'] = $vars['attachment']['exif'];
			foreach ($vars['attachment'] AS $k=>$v)
				$this->block_array[strtoupper($k)] = $v;
			//BEST purger la base des liens photos externes
			$this->block_array['LOCATION'] =
				strncasecmp ($vars['attachment']['physical_filename'], 'http', 4)
				? 'local'
				: 'extern';
			$vars['block_array'] = $this->block_array;

			// Ceci va assigner un template à {postrow.attachment.DISPLAY_ATTACHMENT}
			$view = $this->request->variable ('view', 'geo');
			if ($view == 'geo')
				$this->template->set_filenames ([
					'attachment_tpl' => '@Dominique92_Chemineur/viewtopic_point_photo.html'
				]);
		}
	}

	/**
		POSTING.PHP
	*/
	function modify_posting_auth($vars) {
		global $root_path;
		require_once($root_path.'includes/functions_admin.php');

		// Popule le sélecteur de forum
		$sql = "SELECT forum_id, forum_name, parent_id, forum_type, forum_flags, forum_options, left_id, right_id, forum_desc
			FROM ".FORUMS_TABLE."
			WHERE forum_type = 1
			ORDER BY left_id ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
			$forum_list [] = '<option value="' . $row['forum_id'] . '"' .($row['forum_id'] == $vars['forum_id'] ? ' selected="selected"' : ''). '>' . $row['forum_name'] . '</option>';
		$this->db->sql_freeresult($result);

		if (isset ($forum_list))
			$this->template->assign_var (
				'S_FORUM_SELECT',
				implode ('', $forum_list)
			);

		// Assigne le nouveau forum pour la création
		$vars['forum_id'] = request_var('to_forum_id', $vars['forum_id']);

		// Le bouge
		if ($vars['mode'] == 'edit' && // S'il existe déjà !
			$vars['forum_id'] != $vars['forum_id'])
			move_topics([$vars['post_id']], $vars['forum_id']);
	}
}