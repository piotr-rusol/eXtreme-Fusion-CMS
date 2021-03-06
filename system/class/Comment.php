<?php
/***********************************************************
| eXtreme-Fusion 5.0 Beta 5
| Content Management System
|
| Copyright (c) 2005-2012 eXtreme-Fusion Crew
| http://extreme-fusion.org/
|
| This product is licensed under the BSD License.
| http://extreme-fusion.org/ef5/license/
***********************************************************/

class Comment
{
	protected $_tpl;
	protected $_pdo;
	protected $_user;
	protected $_head;
	protected $_sett;
	protected $_sbb;


	public function __construct($_tpl, $_pdo, $_user, $_sett, $_sbb, $_head = NULL)
	{
		$this->_pdo = $_pdo;
		$this->_user = $_user;
		$this->_tpl = $_tpl;
		$this->_head = $_head;
		$this->_sett = $_sett;
		$this->_sbb = $_sbb;

		$this->_tpl->root = DIR_TEMPLATES.'pre'.DS;
		$this->_tpl->compile = DIR_CACHE;
	}

	public function get($type, $item, $rowstart = 0, $only_comments = FALSE)
	{
		if ($d = $this->getData($type, $item, $rowstart))
		{
			foreach ($d as &$val)
			{
				$val['datestamp'] = HELP::showDate('longdate', $val['datestamp']);

				if ($this->canEdit($val['id']))
				{
					$val['edit'] = TRUE;
				}

				if ($this->canDelete($val['id']))
				{
					$val['delete'] = TRUE;
				}

				$val['avatar'] = NULL;

				if ($val['author_type'] === 'u' || !$val['author_type'])
				{
					if (isNum($val['author']))
					{
						$id = $val['author'];
						$val['author'] = HELP::profileLink($this->_user->getByID($id, 'username'), $id);

						$user_data = $this->_user->getByID($id, array('avatar', 'username'));
						$val['avatar'] = $this->_user->getAvatarAddr($id);
						$val['avatar_desc'] = $user_data['username'];
					}
					else
					{
						$val['author'] = $val['author'];
						$val['avatar'] = $this->_user->getAvatarAddr();
					}
				}

				$val['post'] = $this->_sbb->parseAllTags(nl2br($val['post']));
			}
		}

		$this->_tpl->assignGroup(
			array(
				'comment' => $d,
				'type' => $type,
				'item' => $item,
				'only_comments' => $only_comments
			)
		);

		if ($this->_user->hasPermission('site.comment.add'))
		{
			$this->_tpl->assign('can_comment', TRUE);
		}

		$this->_tpl->assign('ADDR_AVATARS', ADDR_IMAGES.'avatars/');
		if ($this->_head)
		{
			$this->_head->set('<script src="'.ADDR_JS.'comments.js"></script>');
		}

		ob_start();
		$this->_tpl->template('comments.tpl');
		$data = ob_get_contents();

		ob_end_clean();

		return $data;
	}

	public function getByID($id)
	{
		if (HELP::isNum($id))
		{
			return $this->_pdo->getRow('SELECT * FROM [comments] WHERE id = '.$id);
		}

		return FALSE;
	}

	private function getData($type, $item, $rowstart)
	{
		return $this->_pdo->getData('SELECT * FROM [comments] WHERE content_type = :type AND content_id = :item ORDER BY `id` DESC LIMIT '.$rowstart.',10', array(
			array(':type', $type, PDO::PARAM_STR),
			array(':item', $item, PDO::PARAM_INT)
		));
	}


public function hasPermission($writer, $author)
	{
		if ($writer === 'user')
		{
			return ($this->_user->hasPermission('site.comment.edit') && $this->_user->get('id') === $author) || $this->_user->hasPermission('site.comment.edit.all') || $this->_user->hasPermission('site.comment.delete.all');
		}

		return
			($this->_user->hasPermission('site.comment.edit.all') || $this->_user->hasPermission('site.comment.delete.all'))
		||
			($author === $this->_user->getIP() && ($this->_user->hasPermission('site.comment.edit') || $this->_user->hasPermission('site.comment.delete')))
		;
	}

	// ID komentarza do usuni�cia
	public function delete($id)
	{
		if (isNum($id))
		{
			return $this->_pdo->exec('DELETE FROM [comments] WHERE id = :id', array(':id', $id, PDO::PARAM_INT));
		}

		throw new systemException('Wrong type of `$id` parameter');
	}

	// Ilo�� dni, przez kt�re komentarze moga znajdowa� si� w bazie
	public function deleteAll($time = NULL)
	{
		if ($time === NULL)
		{
			$count = $this->_pdo->exec('DELETE FROM [comments]');
		}
		else
		{
			if (isNum($time))
			{
				$count = $this->_pdo->exec('DELETE FROM [comments] WHERE datestamp < '.(time() - $time*24*60*60));
			}
			else
			{
				throw new systemException('Wrong type of `$time` parameter');
			}
		}

		return $count;
	}

	// ID komentarza, tre��, czas zapisu, autor (nick)
	public function update($id, $content, $time = NULL, $author = NULL)
	{
		if (isNum($id))
		{
			HELP::stripinput($content);
			HELP::stripinput($type);
			HELP::stripinput($author);

			if ($time !== NULL)
			{
				$time = ', datestamp = '.time();
			}

			if ($author !== NULL)
			{
				$count = $this->_pdo->exec('UPDATE [comments] SET content = :content, author = :author'.$time.' WHERE id = :id', array(
					array(':content', $content, PDO::PARAM_STR),
					array(':author', $author, PDO::PARAM_INT),
					array(':id', $id, PDO::PARAM_INT)
				));
			}
			else
			{
				$count = $this->_pdo->exec('UPDATE [comments] SET content = :content'.$time.' WHERE id = :id', array(
					array(':content', $content, PDO::PARAM_STR),
					array(':id', $id, PDO::PARAM_INT)
				));
			}

			return $count;
		}
		throw new systemException('Wrong type of `$id` parameter');
	}

	//$id - id komentarza
	public function canEdit($id)
	{
		if ($this->_user->iADMIN())
		{
			return TRUE;
		}
		else
		{
			if ($this->_user->hasPermission('site.comment.edit.all'))
			{
				return TRUE;
			}
			else
			{
				if ($this->_user->hasPermission('site.comment.edit'))
				{
					$d = $this->_pdo->getRow('SELECT author, author_type, ip FROM [comments] WHERE `id` = :id', array(':id', $id, PDO::PARAM_INT));

					if ($d['author_type'] === 'g')
					{
						return $d['ip'] === $this->_user->getIP();
					}

					return $d['author'] === $this->_user->get('id');
				}
			}
		}

		return FALSE;
	}

	public function canDelete($id)
	{
		if ($this->_user->iADMIN())
		{
			return TRUE;
		}
		else
		{
			if ($this->_user->hasPermission('site.comment.delete.all'))
			{
				return TRUE;
			}
			else
			{
				if ($this->_user->hasPermission('site.comment.delete'))
				{
					$d = $this->_pdo->getRow('SELECT author, author_type, ip FROM [comments] WHERE `id` = :id', array(':id', $id, PDO::PARAM_INT));

					if ($d['author_type'] === 'g')
					{
						return $d['ip'] === $this->_user->getIP();
					}

					return $d['author'] === $this->_user->get('id');
				}
			}
		}

		return FALSE;
	}

	public function canAdd()
	{
		return $this->_user->hasPermission('site.comment.add');
	}

	// Dodaje do bazy komentarz uprzednio sprawdzaj�c, czy uzytkownik ma prawo do zamieszczania komentarzy
	// Dane wej�ciowe nie s� przefiltrowane
	// Metoda przeznaczona do ��da� AJAX - nie rzuca wyj�tkami, a jedynie zwraca warto��
	public function addComment($post, $content_type, $content_id, $author = NULL)
	{
		if ($this->_user->hasPermission('site.comment.add'))
		{

			if ($this->_user->isLoggedIn())
			{
				$author = $this->_user->get('id');
				$author_type = 'u';
			}
			elseif ($author)
			{
				$author = HELP::strip($author);
				$author_type = 'g';
			}
			else
			{
				$author = __('Guest');
				$author_type = 'g';
			}

			return $this->_pdo->exec('
				INSERT INTO [comments] (content_id, post, content_type, author, author_type, datestamp, ip)
				VALUES (:content_id, :post, :type, :author, \''.$author_type.'\', '.time().', \''.$this->_user->getIP().'\')',
				array(
					array(':content_id', $content_id, PDO::PARAM_INT),
					array(':post', HELP::strip($post), PDO::PARAM_STR),
					array(':type', HELP::strip($content_type), PDO::PARAM_STR),
					array(':author', $author, PDO::PARAM_STR),
				)
			);
		}

		return FALSE;
	}
}