<?php
/*---------------------------------------------------------------+
| eXtreme-Fusion - Content Management System - version 5         |
+----------------------------------------------------------------+
| Copyright (c) 2005-2012 eXtreme-Fusion Crew                	 |
| http://extreme-fusion.org/                               		 |
+----------------------------------------------------------------+
| This product is licensed under the BSD License.				 |
| http://extreme-fusion.org/ef5/license/						 |
+---------------------------------------------------------------*/
try
{
	require_once '../../../config.php';
	require DIR_SITE.'bootstrap.php';
	require_once DIR_SYSTEM.'admincore.php';
	$_locale->moduleLoad('admin', 'chat');

	if ( ! $_user->hasPermission('module.chat.admin'))
	{
		throw new userException(__('Access denied'));
	}

	$_tpl = new AdminModuleIframe('chat');

	$row = $_pdo->getRow('SELECT * FROM [chat_settings]');

	if ($_request->get(array('status', 'act'))->show())
	{
		$_tpl->logAndShow($_request->get('status')->show(), $_request->get('act')->show(), array(
			'update' => array(__('Data has been saved.'), __('Error! Data has not been saved.'))
		));
	}

	if ($_request->post('save')->show())
	{

		$count = $_pdo->exec('UPDATE [chat_settings] SET `refresh` = :refresh, `life_messages` = :life_messages, `panel_limit` = :panel_limit, `archive_limit` = :archive_limit',
			array(
				array(':refresh', $_request->post('refresh')->isNum(TRUE), PDO::PARAM_INT),
				array(':life_messages', $_request->post('life_messages')->isNum(TRUE), PDO::PARAM_INT),
				array(':panel_limit', $_request->post('panel_limit')->isNum(TRUE), PDO::PARAM_INT),
				array(':archive_limit', $_request->post('archive_limit')->isNum(TRUE), PDO::PARAM_INT),
			)
		);

		if ($count)
		{
			HELP::redirect(FILE_SELF.'?act=update&status=ok');
		}

		HELP::redirect(FILE_SELF.'?act=update&status=error');
	}

	$_tpl->assignGroup(array
		(
			'refresh' => $row['refresh'],
			'life_messages' => $row['life_messages'],
			'panel_limit' => $row['panel_limit'],
			'archive_limit' => $row['archive_limit']
		)
	);

	$_tpl->template('admin.tpl');

}
catch(optException $exception)
{
    optErrorHandler($exception);
}
catch(systemException $exception)
{
    systemErrorHandler($exception);
}
catch(userException $exception)
{
    userErrorHandler($exception);
}
catch(PDOException $exception)
{
    PDOErrorHandler($exception);
}