<?php
/**
* Mailing List extension for the phpBB Forum Software package.
*
* @copyright (c) 2015 DavidIQ.com
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace davidiq\mailinglist\acp;

class mailinglist_module
{
   /** @var string */
   public $tpl_name;

   /** @var string */
   public $page_title;

   /** @var string */
	public $u_action;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var ContainerInterface */
	protected $phpbb_container;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/** @var \davidiq\mailinglist\managers\mailinglist_manager */
	protected $mailinglist_manager;

	/** @var array */
	protected $post_types = [1 => 'NEW_POSTS', 2 => 'NEW_TOPICS', 3 => 'NEW_POSTS_NEW_TOPICS'];

	function main($id, $mode)
	{
		global $user, $template, $phpbb_root_path, $phpEx, $phpbb_container, $request;

      $this->phpbb_container = $phpbb_container;
		$this->log = $this->phpbb_container->get('log');
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;
		$this->mailinglist_manager = $phpbb_container->get('davidiq.mailinglist.manager');

		$this->user->add_lang_ext('davidiq/mailinglist', 'mailinglist_acp');

		$this->tpl_name = 'mailinglist';
		$this->page_title = 'ACP_MAILINGLIST_SETTINGS';

		$errors = array();
		$form_name = 'acp_mailinglist';
		$action = $this->request->variable('action', '');

		switch ($action)
      {
         case 'add':
         case 'edit':

            add_form_key($form_name);

            $mailinglist_id = $this->request->variable('mailinglist_id', 0);
            $mailinglist_forum_ids = array();

            if ($this->request->is_set_post('submit'))
            {
               if (!check_form_key($form_name))
               {
                  trigger_error($this->user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
               }

               $mailinglist_email = $this->request->variable('mailinglist_email', '');
               $mailinglist_post_type = $this->request->variable('mailinglist_post_type', 0);
               $mailinglist_include_contents = $this->request->variable('mailinglist_include_contents', false);
               $mailinglist_unsubscribe = $this->request->variable('mailinglist_unsubscribe', '');
               $mailinglist_forum_ids = $this->request->variable('forum_id', []);
               $mailinglist_all_forums = $this->request->variable('all_forums', false);

               if (empty($mailinglist_email))
               {
                  $errors[] = 'MAILINGLIST_EMAIL_REQUIRED';
               }

               if (empty($mailinglist_forum_ids) && !$mailinglist_all_forums)
               {
                  $errors[] = 'MAILINGLIST_FORUM_OPTION_REQUIRED';
               }

               if (empty($errors))
               {
                  // Save

                  //$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_MAILINGLIST_UPDATED');
                  trigger_error($this->user->lang('MAILINGLIST_UPDATED') . adm_back_link($this->u_action));
               }
            }
            else if ($action === 'edit')
            {
               $mailinglist_data = $this->mailinglist_manager->get_mailing_list($mailinglist_id);
               $mailinglist_forum_ids = array_column($mailinglist_data['forums'], 'mailinglist_forum_id');

               $this->template->assign_vars([
                  'S_MAILINGLIST_EMAIL'            => $mailinglist_data['mailinglist_email'],
                  'S_ALL_FORUMS'                   => !count($mailinglist_forum_ids),
                  'S_MAILINGLIST_POST_TYPE'        => $mailinglist_data['mailinglist_post_type'],
                  'S_MAILINGLIST_INCLUDE_CONTENTS' => $mailinglist_data['mailinglist_include_contents'],
                  'S_MAILINGLIST_UNSUBSCRIBE'      => $mailinglist_data['mailinglist_unsubscribe'],
               ]);
            }

            $this->template->assign_vars([
               'S_ADD_MAILINGLIST'     => $action === 'add',
               'S_EDIT_MAILINGLIST'    => $action === 'edit',
               'S_FORUM_OPTIONS'		   => make_forum_select(count($mailinglist_forum_ids) ? $mailinglist_forum_ids : false, false, false, true),
               'S_ERROR'			=> (bool) count($errors),
               'ERROR_MSG'			=> count($errors) ? implode('<br>', $errors) : '',
               'U_ACTION'        => "{$this->u_action}&amp;action={$action}" . (($mailinglist_id) ? "&amp;mailinglist_id={$mailinglist_id}" : '')
            ]);
            break;

         case 'delete':
            break;

         default:
            $mailinglists_data = $this->mailinglist_manager->get_mailinglists_data();

            foreach ($mailinglists_data as $data)
            {
               $forum_names = array_column($data['forums'], 'forum_name');
               $this->template->assign_block_vars('mailinglists',
                  [
                     'EMAIL'              => $data['mailinglist_email'],
                     'FORUMS'             => count($data['forums']) ? implode(', ', $forum_names) : false,
                     'POST_TYPE'          => $this->post_types[$data['mailinglist_post_type']],
                     'INCLUDE_CONTENTS'   => $data['mailinglist_include_contents'] ? 'YES' : 'NO',
                     'UNSUBSCRIBE'        => !empty($data['mailinglist_unsubscribe']) ? 'YES' : 'NO',
                     'U_EDIT'			      => "{$this->u_action}&amp;action=edit&amp;mailinglist_id=" . $data['mailinglist_id'],
                     'U_DELETE'			   => "{$this->u_action}&amp;action=delete&amp;mailinglist_id=" . $data['mailinglist_id'],
                     'U_ADD'              => "{$this->u_action}&amp;action=add"
                  ]
               );
            }

            $this->template->assign_var('U_ACTION', $this->u_action);
      }
	}
}
