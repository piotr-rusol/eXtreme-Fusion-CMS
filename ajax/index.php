<?php
/*********************************************************
| eXtreme-Fusion 5
| Content Management System
|
| Copyright (c) 2005-2013 eXtreme-Fusion Crew
| http://extreme-fusion.org/
|
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
| 
**********************************************************
                ORIGINALLY BASED ON
---------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) 2002 - 2011 Nick Jones
| http://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Author: Nick Jones (Digitanium)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/

require_once '../config.php';
require_once DIR_SYSTEM.'sitecore.php';

// Wczytywanie g��wnej klasy
require_once DIR_CLASS.'Themes.php';

// Tworzenie emulatora statyczno�ci klasy OPT
TPL::build($_theme = new Theme($_sett, $_user));

$_tpl = new SiteAjax;

try
{
	$file = $_request->get('file')->strip();

	if (file_exists(DIR_AJAX.DS.$file.'.php'))
	{
		require_once DIR_AJAX.DS.$file.'.php';
	}
	else
	{
		throw new systemException('Plik '.$file.' nie zosta� odnaleziony w katalogu <span class="italic">/ajax/</span>.');
	}

	// Metoda za�aduje plik TPL je�li istnieje w szablonie lub katalogu ajax.
	// Je�li nie istnieje, to nie zwr�ci �adnej warto�ci.
	$_tpl->template($file.'.tpl', $_tpl->themeTplExists($file.'.tpl'));
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
