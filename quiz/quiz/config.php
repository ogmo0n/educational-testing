<?php
/*
 *  rating - Rating: an example LTI tool provider
 *  Copyright (C) 2015  Stephen P Vickers
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: stephen@spvsoftwareproducts.com
 *
 *  Version history:
 *    1.0.00   2-Jan-13  Initial release
 *    1.0.01  17-Jan-13  Minor update
 *    1.1.00   5-Jun-13  Added Outcomes service option
 *    1.2.00  20-May-15  Changed to use class method overrides for handling LTI requests
 *                       Added support for Content-Item message
*/

/*
 * This page contains the configuration settings for the application.
 */


###
###  Application settings
###
  define('APP_NAME', 'Quizzes');
  define('SESSION_NAME', 'pre-post-q');
  define('LTI_FOLDER', 'lti/');

###
###  Database connection settings
###
  /*
  define('DB_NAME', 'mysql:dbname=xxxx;host=xxx');  // e.g. 'mysql:dbname=MyDb;host=localhost' or 'sqlite:php-rating.sqlitedb'
  define('DB_USERNAME', 'xxxx');
  define('DB_PASSWORD', 'xxxx');
  define('DB_TABLENAME_PREFIX', 'quiz_');
	*/
 
	define('DB_NAME', '');  // e.g. 'mysql:dbname=MyDb;host=localhost' or 'sqlite:php-rating.sqlitedb'
	define('DB_USERNAME', '');
	define('DB_PASSWORD', '');
	define('DB_TABLENAME_PREFIX', 'quiz_');
  
###
###  User settings
###

  $email = ''; // variable for administrator's email

?>
