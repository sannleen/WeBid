<?php
/***************************************************************************
 *   copyright				: (C) 2008 - 2012 WeBid
 *   site					: http://www.webidsupport.com/
 ***************************************************************************/

/***************************************************************************
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version. Although none of the code may be
 *   sold. If you have been sold this script, get a refund.
 ***************************************************************************/

define('InAdmin', 1);
$current_page = 'fees';
$extraJs = ';js/calendar.php';
include '../common.php';
include $include_path . 'functions_admin.php';
include $include_path . 'dates.inc.php';
include 'loggedin.inc.php';

unset($ERR);

// get form variables
$list_type = isset($_GET['type']) ? intval($_GET['type']) : 'a';
$from_date = isset($_GET['from_date']) ? intval($_GET['from_date']) : 0;
$to_date = isset($_GET['to_date']) ? intval($_GET['to_date']) : 0;

// Set offset and limit for pagination
if (isset($_GET['PAGE']) && is_numeric($_GET['PAGE']))
{
	$PAGE = intval($_GET['PAGE']);
	$OFFSET = ($PAGE - 1) * $system->SETTINGS['perpage'];
}
elseif (isset($_SESSION['RETURN_LIST_OFFSET']) && $_SESSION['RETURN_LIST'] == 'accounts.php')
{
	$PAGE = intval($_SESSION['RETURN_LIST_OFFSET']);
	$OFFSET = ($PAGE - 1) * $system->SETTINGS['perpage'];
}
else
{
	$OFFSET = 0;
	$PAGE = 1;
}

$where_sql = '';
if ($from_date != 0)
{
	$where_sql = 'paid_date > \'' . FormatTimeStamp($from_date, '-') . '\'';
}
if ($to_date != 0)
{
	if (!empty($where_sql))
	{
		$where_sql .= ' AND ';
	}
	$where_sql .= 'paid_date < \'' . FormatTimeStamp($to_date, '-') . '\'';
}

if ($list_type == 'm' || $list_type == 'w' || $list_type == 'd')
{
	$OFFSET = 0;
	$PAGE = 1;
	$PAGES = 1;
	$show_pagnation = false;
	if ($list_type == 'm')
	{
		$query = "SELECT *, SUM(amount) As total FROM " . $DBPrefix . "accounts
				" . ((!empty($where_sql)) ? ' WHERE ' . $where_sql : '') . "
				GROUP BY month, year ORDER BY year, month";
	}
	elseif ($list_type == 'w')
	{
		$query = "SELECT *, SUM(amount) As total FROM " . $DBPrefix . "accounts
				" . ((!empty($where_sql)) ? ' WHERE ' . $where_sql : '') . "
				GROUP BY week, year ORDER BY year, week";
	}
	else
	{
		$query = "SELECT *, SUM(amount) As total FROM " . $DBPrefix . "accounts
				" . ((!empty($where_sql)) ? ' WHERE ' . $where_sql : '') . "
				GROUP BY day, year ORDER BY year, day";
	}
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);

	$bg = '';
	while ($row = mysql_fetch_assoc($res))
	{
		if ($list_type == 'm')
		{
			$date = $MSG['MON_0' . $row['month'] . 'E'] . ', ' . $row['year'];
		}
		elseif ($list_type == 'w')
		{
			$date = $MSG['828'] . ' ' . $row['week'] . ', ' . $row['year'];
		}
		else
		{
			$date = FormatDate($row['paid_date']);
		}
		$template->assign_block_vars('accounts', array(
				'DATE' => $date,
				'AMOUNT' => $system->print_money($row['amount'], true, false),
				'BG' => $bg
				));
		$bg = ($bg == '') ? 'class="bg"' : '';
	}
}
else
{
	$_SESSION['RETURN_LIST'] = 'accounts.php';
	$_SESSION['RETURN_LIST_OFFSET'] = $PAGE;
	$show_pagnation = true;

	$query = "SELECT COUNT(id) As accounts FROM " . $DBPrefix . "accounts" . ((!empty($where_sql)) ? ' WHERE ' . $where_sql : '');
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);
	$num_accounts = mysql_result($res, 0, 'accounts');
	$PAGES = ($num_accounts == 0) ? 1 : ceil($num_accounts / $system->SETTINGS['perpage']);
	$query = "SELECT * FROM " . $DBPrefix . "accounts
			" . ((!empty($where_sql)) ? ' WHERE ' . $where_sql : '') . " ORDER BY paid_date LIMIT " . $OFFSET . ", " . $system->SETTINGS['perpage'];
	$res = mysql_query($query);
	$system->check_mysql($res, $query, __LINE__, __FILE__);

	$bg = '';
	while ($row = mysql_fetch_assoc($res))
	{
		$template->assign_block_vars('accounts', array(
				'ID' => $row['id'],
				'NICK' => $row['nick'],
				'RNAME' => $row['name'],
				'DATE' => ArrangeDateNoCorrection($row['paid_date']),
				'AMOUNT' => $system->print_money($row['amount'], true, false),
				'TEXT' => $row['text'],
				'BG' => $bg
				));
		$bg = ($bg == '') ? 'class="bg"' : '';
	}
}

// get pagenation
$PREV = intval($PAGE - 1);
$NEXT = intval($PAGE + 1);
if ($PAGES > 1)
{
	$LOW = $PAGE - 5;
	if ($LOW <= 0) $LOW = 1;
	$COUNTER = $LOW;
	while ($COUNTER <= $PAGES && $COUNTER < ($PAGE + 6))
	{
		$template->assign_block_vars('pages', array(
				'PAGE' => ($PAGE == $COUNTER) ? '<b>' . $COUNTER . '</b>' : '<a href="' . $system->SETTINGS['siteurl'] . 'admin/listauctions.php?PAGE=' . $COUNTER . '"><u>' . $COUNTER . '</u></a>'
				));
		$COUNTER++;
	}
}

$template->assign_vars(array(
		'TYPE' => $list_type,
		'FROM_DATE' => ($from_date == 0) ? '' : $from_date,
		'TO_DATE' => ($to_date == 0) ? '' : $to_date,

		'PAGNATION' => $show_pagnation,
		'PREV' => ($PAGES > 1 && $PAGE > 1) ? '<a href="' . $system->SETTINGS['siteurl'] . 'admin/listauctions.php?PAGE=' . $PREV . '"><u>' . $MSG['5119'] . '</u></a>&nbsp;&nbsp;' : '',
		'NEXT' => ($PAGE < $PAGES) ? '<a href="' . $system->SETTINGS['siteurl'] . 'admin/listauctions.php?PAGE=' . $NEXT . '"><u>' . $MSG['5120'] . '</u></a>' : '',
		'PAGE' => $PAGE,
		'PAGES' => $PAGES
		));

$template->set_filenames(array(
		'body' => 'accounts.tpl'
		));
$template->display('body');
?>
