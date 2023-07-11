<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005 Markus Westphal
//    Copyright (C) 2007-2008 Malcolm Cowe
//    Copyright (C) 2010-2013 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

class SeedDMS_Version { /* {{{ */

	const _number = "6.0.24";
	const _string = "SeedDMS";

	function __construct() {
	}

	function version() { /* {{{ */
		return self::_number;
	} /* }}} */

	function majorVersion() { /* {{{ */
		$tmp = explode('.', self::_number, 3);
		return (int) $tmp[0];
	} /* }}} */

	function minorVersion() { /* {{{ */
		$tmp = explode('.', self::_number, 3);
		return (int) $tmp[1];
	} /* }}} */

	function subminorVersion() { /* {{{ */
		$tmp = explode('.', self::_number, 3);
		return (int) $tmp[2];
	} /* }}} */

	function banner() { /* {{{ */
		return self::_string .", ". self::_number;
	}

	/**
	 * Compare two version
	 *
	 * This functions compares the current version in the format x.x.x with
	 * the passed version
	 *
	 * @param string $ver
	 * @return int -1 if _number < $ver, 0 if _number == $ver, 1 if _number > $ver
	 */
	static public function cmpVersion($ver) { /* {{{ */
		$tmp1 = explode('.', self::_number);
		$tmp2 = explode('.', $ver);
		if(intval($tmp1[0]) < intval($tmp2[0])) {
			return -1;
		} elseif(intval($tmp1[0]) > intval($tmp2[0])) {
			return 1;
		} else {
			if(intval($tmp1[1]) < intval($tmp2[1])) {
				return -1;
			} elseif(intval($tmp1[1]) > intval($tmp2[1])) {
				return 1;
			} else {
				if(intval($tmp1[2]) < intval($tmp2[2])) {
					return -1;
				} elseif(intval($tmp1[2]) > intval($tmp2[2])) {
					return 1;
				} else {
					return 0;
				}
			}
		}
	} /* }}} */

} /* }}} */

