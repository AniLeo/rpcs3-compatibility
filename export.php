<?php
/*
		RPCS3.net Compatibility List (https://github.com/AniLeo/rpcs3-compatibility)
		Copyright (C) 2017 AniLeo
		https://github.com/AniLeo or ani-leo@outlook.com

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License along
		with this program; if not, write to the Free Software Foundation, Inc.,
		51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

// Calls for the file that contains the functions needed
if (!@include_once('functions.php')) throw new Exception("Compat: functions.php is missing. Failed to include functions.php");


function exportDatabase() {
	global $c_maintenance;

	$db = mysqli_connect(db_host, db_user, db_pass, db_name, db_port);
	mysqli_set_charset($db, 'utf8');

	$q_export = mysqli_query($db, "SELECT *
	FROM game_list; ");

	if (!$q_export || mysqli_num_rows($q_export) === 0) {
		$results['return_code'] = -1;
		return $results;
	}

	if ($c_maintenance) {
		$results['return_code'] = -2;
		return $results;
	}

	$results['return_code'] = 0;

	while ($row = mysqli_fetch_object($q_export)) {

		if (!empty($row->gid_EU)) {
			$results['results'][$row->gid_EU] = array(
			'status' => $row->status,
			'date' => $row->last_update
			);
		}
		if (!empty($row->gid_US)) {
			$results['results'][$row->gid_US] = array(
			'status' => $row->status,
			'date' => $row->last_update
			);
		}
		if (!empty($row->gid_JP)) {
			$results['results'][$row->gid_JP] = array(
			'status' => $row->status,
			'date' => $row->last_update
			);
		}
		if (!empty($row->gid_AS)) {
			$results['results'][$row->gid_AS] = array(
			'status' => $row->status,
			'date' => $row->last_update
			);
		}
		if (!empty($row->gid_KR)) {
			$results['results'][$row->gid_KR] = array(
			'status' => $row->status,
			'date' => $row->last_update
			);
		}
		if (!empty($row->gid_HK)) {
			$results['results'][$row->gid_HK] = array(
			'status' => $row->status,
			'date' => $row->last_update
			);
		}

	}

	mysqli_close($db);

	return $results;

}
