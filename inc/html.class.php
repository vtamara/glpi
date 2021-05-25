<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;
use Glpi\Console\Application;
use ScssPhp\ScssPhp\Compiler;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Html Class
 * Inpired from Html/FormHelper for several functions
**/
class Html {


   /**
    * Clean display value deleting html tags
    *
    * @param string  $value      string value
    * @param boolean $striptags  strip all html tags
    * @param integer $keep_bad
    *          1 : neutralize tag anb content,
    *          2 : remove tag and neutralize content
    * @return string
   **/
   static function clean($value, $striptags = true, $keep_bad = 2) {
      $value = Html::entity_decode_deep($value);

      // Change <email@domain> to email@domain so it is not removed by htmLawed
      // Search for strings that is an email surrounded by `<` and `>` but that cannot be an HTML tag:
      // - absence of quotes indicate that values is not part of an HTML attribute,
      // - absence of > ensure that ending `>` has not been reached.
      $regex = "/(<[^\"'>]+?@[^>\"']+?>)/";
      $value = preg_replace_callback($regex, function($matches) {
         return substr($matches[1], 1, (strlen($matches[1]) - 2));
      }, $value);

      // Clean MS office tags
      $value = str_replace(["<![if !supportLists]>", "<![endif]>"], '', $value);

      if ($striptags) {
         // Strip ToolTips
         $specialfilter = ['@<div[^>]*?tooltip_picture[^>]*?>.*?</div[^>]*?>@si',
                                '@<div[^>]*?tooltip_text[^>]*?>.*?</div[^>]*?>@si',
                                '@<div[^>]*?tooltip_picture_border[^>]*?>.*?</div[^>]*?>@si',
                                '@<div[^>]*?invisible[^>]*?>.*?</div[^>]*?>@si'];
         $value         = preg_replace($specialfilter, '', $value);

         $value = preg_replace("/<(p|br|div)( [^>]*)?".">/i", "\n", $value);
         $value = preg_replace("/(&nbsp;| |\xC2\xA0)+/", " ", $value);
      }

      $search = ['@<script[^>]*?>.*?</script[^>]*?>@si', // Strip out javascript
                      '@<style[^>]*?>.*?</style[^>]*?>@si', // Strip out style
                      '@<title[^>]*?>.*?</title[^>]*?>@si', // Strip out title
                      '@<!DOCTYPE[^>]*?>@si', // Strip out !DOCTYPE
                       ];
      $value = preg_replace($search, '', $value);

      // Neutralize not well formatted html tags
      $value = preg_replace("/(<)([^>]*<)/", "&lt;$2", $value);

      $config = Toolbox::getHtmLawedSafeConfig();
      $config['keep_bad'] = $keep_bad; // 1: neutralize tag and content, 2 : remove tag and neutralize content
      if ($striptags) {
         $config['elements'] = 'none';
      }

      $value = htmLawed($value, $config);

      // Special case : remove the 'denied:' for base64 img in case the base64 have characters
      // combinaison introduce false positive
      foreach (['png', 'gif', 'jpg', 'jpeg'] as $imgtype) {
         $value = str_replace('src="denied:data:image/'.$imgtype.';base64,',
               'src="data:image/'.$imgtype.';base64,', $value);
      }

      $value = str_replace(["\r\n", "\r"], "\n", $value);
      $value = preg_replace("/(\n[ ]*){2,}/", "\n\n", $value, -1);

      return trim($value);
   }


   /**
    * Recursivly execute html_entity_decode on an array
    *
    * @param string|array $value
    *
    * @return string|array
   **/
   static function entity_decode_deep($value) {

      return (is_array($value) ? array_map([__CLASS__, 'entity_decode_deep'], $value)
                               : html_entity_decode($value, ENT_QUOTES, "UTF-8"));
   }


   /**
    * Recursivly execute htmlentities on an array
    *
    * @param string|array $value
    *
    * @return string|array
   **/
   static function entities_deep($value) {

      return (is_array($value) ? array_map([__CLASS__, 'entities_deep'], $value)
                               : htmlentities($value, ENT_QUOTES, "UTF-8"));
   }


   /**
    * Convert a date YY-MM-DD to DD-MM-YY for calendar
    *
    * @param string       $time    Date to convert
    * @param integer|null $format  Date format
    *
    * @return null|string
    *
    * @see Toolbox::getDateFormats()
   **/
   static function convDate($time, $format = null) {

      if (is_null($time) || trim($time) == '' || in_array($time, ['NULL', '0000-00-00', '0000-00-00 00:00:00'])) {
         return null;
      }

      if (!isset($_SESSION["glpidate_format"])) {
         $_SESSION["glpidate_format"] = 0;
      }
      if (!$format) {
         $format = $_SESSION["glpidate_format"];
      }

      try {
         $date = new \DateTime($time);
      } catch (\Exception $e) {
         Toolbox::logWarning("Invalid date $time!");
         Session::addMessageAfterRedirect(
            sprintf(
               __('%1$s %2$s'),
               $time,
               _x('adjective', 'Invalid')
            )
         );
         return $time;
      }
      $mask = 'Y-m-d';

      switch ($format) {
         case 1 : // DD-MM-YYYY
            $mask = 'd-m-Y';
            break;
         case 2 : // MM-DD-YYYY
            $mask = 'm-d-Y';
            break;
      }

      return $date->format($mask);
   }


   /**
    * Convert a date YY-MM-DD HH:MM to DD-MM-YY HH:MM for display in a html table
    *
    * @param string       $time    Datetime to convert
    * @param integer|null $format  Datetime format
    *
    * @return null|string
   **/
   static function convDateTime($time, $format = null) {

      if (is_null($time) || ($time == 'NULL')) {
         return null;
      }

      return self::convDate($time, $format).' '. substr($time, 11, 5);
   }


   /**
    * Clean string for input text field
    *
    * @param string $string
    *
    * @return string
   **/
   static function cleanInputText($string) {
      return preg_replace( '/\'/', '&apos;', preg_replace('/\"/', '&quot;', $string));
   }


   /**
    * Clean all parameters of an URL. Get a clean URL
    *
    * @param string $url
    *
    * @return string
   **/
   static function cleanParametersURL($url) {

      $url = preg_replace("/(\/[0-9a-zA-Z\.\-\_]+\.php).*/", "$1", $url);
      return preg_replace("/\?.*/", "", $url);
   }


   /**
    * Recursivly execute nl2br on an array
    *
    * @param string|array $value
    *
    * @return string|array
   **/
   static function nl2br_deep($value) {

      return (is_array($value) ? array_map([__CLASS__, 'nl2br_deep'], $value)
                               : nl2br($value));
   }


   /**
    *  Resume text for followup
    *
    * @param string  $string  string to resume
    * @param integer $length  resume length (default 255)
    *
    * @return string
   **/
   static function resume_text($string, $length = 255) {

      if (Toolbox::strlen($string) > $length) {
         $string = Toolbox::substr($string, 0, $length)."&nbsp;(...)";
      }

      return $string;
   }


   /**
    *  Resume a name for display
    *
    * @param string  $string  string to resume
    * @param integer $length  resume length (default 255)
    *
    * @return string
    **/
   static function resume_name($string, $length = 255) {

      if (strlen($string) > $length) {
         $string = Toolbox::substr($string, 0, $length)."...";
      }

      return $string;
   }


   /**
    * Clean post value for display in textarea
    *
    * @param string $value
    *
    * @return string
   **/
   static function cleanPostForTextArea($value) {

      if (is_array($value)) {
         return array_map([__CLASS__, __METHOD__], $value);
      }
      $order   = ['\r\n',
                       '\n',
                       "\\'",
                       '\"',
                       '\\\\'];
      $replace = ["\n",
                       "\n",
                       "'",
                       '"',
                       "\\"];
      return str_replace($order, $replace, $value);
   }


   /**
    * Convert a number to correct display
    *
    * @param float   $number        Number to display
    * @param boolean $edit          display number for edition ? (id edit use . in all case)
    * @param integer $forcedecimal  Force decimal number (do not use default value) (default -1)
    *
    * @return string
   **/
   static function formatNumber($number, $edit = false, $forcedecimal = -1) {
      global $CFG_GLPI;

      // Php 5.3 : number_format() expects parameter 1 to be double,
      if ($number == "") {
         $number = 0;

      } else if ($number == "-") { // used for not defines value (from Infocom::Amort, p.e.)
         return "-";
      }

      $number  = doubleval($number);
      $decimal = $CFG_GLPI["decimal_number"];
      if ($forcedecimal>=0) {
         $decimal = $forcedecimal;
      }

      // Edit : clean display for mysql
      if ($edit) {
         return number_format($number, $decimal, '.', '');
      }

      // Display : clean display
      switch ($_SESSION['glpinumber_format']) {
         case 0 : // French
            return str_replace(' ', '&nbsp;', number_format($number, $decimal, '.', ' '));

         case 2 : // Other French
            return str_replace(' ', '&nbsp;', number_format($number, $decimal, ',', ' '));

         case 3 : // No space with dot
            return number_format($number, $decimal, '.', '');

         case 4 : // No space with comma
            return number_format($number, $decimal, ',', '');

         default: // English
            return number_format($number, $decimal, '.', ',');
      }
   }


   /**
    * Make a good string from the unix timestamp $sec
    *
    * @param int|float  $time         timestamp
    * @param boolean    $display_sec  display seconds ?
    * @param boolean    $use_days     use days for display ?
    *
    * @return string
   **/
   static function timestampToString($time, $display_sec = true, $use_days = true) {

      $time = (float)$time;

      $sign = '';
      if ($time < 0) {
         $sign = '- ';
         $time = abs($time);
      }
      $time = floor($time);

      // Force display seconds if time is null
      if ($time < MINUTE_TIMESTAMP) {
         $display_sec = true;
      }

      $units = Toolbox::getTimestampTimeUnits($time);
      if ($use_days) {
         if ($units['day'] > 0) {
            if ($display_sec) {
               //TRANS: %1$s is the sign (-or empty), %2$d number of days, %3$d number of hours,
               //       %4$d number of minutes, %5$d number of seconds
               return sprintf(__('%1$s%2$d days %3$d hours %4$d minutes %5$d seconds'), $sign,
                              $units['day'], $units['hour'], $units['minute'], $units['second']);
            }
            //TRANS:  %1$s is the sign (-or empty), %2$d number of days, %3$d number of hours,
            //        %4$d number of minutes
            return sprintf(__('%1$s%2$d days %3$d hours %4$d minutes'),
                           $sign, $units['day'], $units['hour'], $units['minute']);
         }
      } else {
         if ($units['day'] > 0) {
            $units['hour'] += 24*$units['day'];
         }
      }

      if ($units['hour'] > 0) {
         if ($display_sec) {
            //TRANS:  %1$s is the sign (-or empty), %2$d number of hours, %3$d number of minutes,
            //        %4$d number of seconds
            return sprintf(__('%1$s%2$d hours %3$d minutes %4$d seconds'),
                           $sign, $units['hour'], $units['minute'], $units['second']);
         }
         //TRANS: %1$s is the sign (-or empty), %2$d number of hours, %3$d number of minutes
         return sprintf(__('%1$s%2$d hours %3$d minutes'), $sign, $units['hour'], $units['minute']);
      }

      if ($units['minute'] > 0) {
         if ($display_sec) {
            //TRANS:  %1$s is the sign (-or empty), %2$d number of minutes,  %3$d number of seconds
            return sprintf(__('%1$s%2$d minutes %3$d seconds'), $sign, $units['minute'],
                           $units['second']);
         }
         //TRANS: %1$s is the sign (-or empty), %2$d number of minutes
         return sprintf(_n('%1$s%2$d minute', '%1$s%2$d minutes', $units['minute']), $sign,
                        $units['minute']);

      }

      if ($display_sec) {
         //TRANS:  %1$s is the sign (-or empty), %2$d number of seconds
         return sprintf(_n('%1$s%2$s second', '%1$s%2$s seconds', $units['second']), $sign,
                        $units['second']);
      }
      return '';
   }


   /**
    * Format a timestamp into a normalized string (hh:mm:ss).
    *
    * @param integer $time
    *
    * @return string
   **/
   static function timestampToCsvString($time) {

      if ($time < 0) {
         $time = abs($time);
      }
      $time = floor($time);

      $units = Toolbox::getTimestampTimeUnits($time);

      if ($units['day'] > 0) {
         $units['hour'] += 24*$units['day'];
      }

      return str_pad($units['hour'], 2, '0', STR_PAD_LEFT)
         . ':'
         . str_pad($units['minute'], 2, '0', STR_PAD_LEFT)
         . ':'
         . str_pad($units['second'], 2, '0', STR_PAD_LEFT);
   }


   /**
    * Extract url from web link
    *
    * @param string $value
    *
    * @return string
   **/
   static function weblink_extract($value) {

      $value = preg_replace('/<a\s+href\="([^"]+)"[^>]*>[^<]*<\/a>/i', "$1", $value);
      return $value;
   }


   /**
    * Redirection to $_SERVER['HTTP_REFERER'] page
    *
    * @return void
   **/
   static function back() {
      self::redirect(self::getBackUrl());
   }


   /**
    * Redirection hack
    *
    * @param $dest string: Redirection destination
    * @param $http_response_code string: Forces the HTTP response code to the specified value
    *
    * @return void
   **/
   static function redirect($dest, $http_response_code = 302) {

      $toadd = '';
      $dest = addslashes($dest);

      if (!headers_sent() && !Toolbox::isAjax()) {
          header("Location: $dest", true, $http_response_code);
          exit();
      }

      if (strpos($dest, "?") !== false) {
         $toadd = '&tokonq='.Toolbox::getRandomString(5);
      } else {
         $toadd = '?tokonq='.Toolbox::getRandomString(5);
      }

      echo "<script type='text/javascript'>
            NomNav = navigator.appName;
            if (NomNav=='Konqueror') {
               window.location='".$dest.$toadd."';
            } else {
               window.location='".$dest."';
            }
         </script>";
      exit();
   }

   /**
    * Redirection to Login page
    *
    * @param string $params  param to add to URL (default '')
    * @since 0.85
    *
    * @return void
   **/
   static function redirectToLogin($params = '') {
      global $CFG_GLPI;

      $dest     = $CFG_GLPI["root_doc"] . "/index.php";
      $url_dest = preg_replace(
         '/^' . preg_quote($CFG_GLPI["root_doc"], '/') . '/',
         '',
         $_SERVER['REQUEST_URI']
      );
      $dest    .= "?redirect=".rawurlencode($url_dest);

      if (!empty($params)) {
         $dest .= '&'.$params;
      }

      self::redirect($dest);
   }


   /**
    * Display common message for item not found
    *
    * @return void
   **/
   static function displayNotFoundError() {
      global $CFG_GLPI, $HEADER_LOADED;

      if (!$HEADER_LOADED) {
         if (!Session::getCurrentInterface()) {
            self::nullHeader(__('Access denied'));

         } else if (Session::getCurrentInterface() == "central") {
            self::header(__('Access denied'));

         } else if (Session::getCurrentInterface() == "helpdesk") {
            self::helpHeader(__('Access denied'));
         }
      }
      echo "<div class='center'><br><br>";
      echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='".__s('Warning')."'>";
      echo "<br><br><span class='b'>" . __('Item not found') . "</span></div>";
      self::nullFooter();
      exit ();
   }


   /**
    * Display common message for privileges errors
    *
    * @return void
   **/
   static function displayRightError() {
      self::displayErrorAndDie(__("You don't have permission to perform this action."));
   }


   /**
    * Display a div containing messages set in session in the previous page
   **/
   static function displayMessageAfterRedirect() {
      TemplateRenderer::getInstance()->display('components/messages_after_redirect_toasts.html.twig');
   }


   static function displayAjaxMessageAfterRedirect() {
      global $CFG_GLPI;

      echo Html::scriptBlock("
      displayAjaxMessageAfterRedirect = function() {
         $('.messages_after_redirect').remove();
         $.ajax({
            url:  '".$CFG_GLPI['root_doc']."/ajax/displayMessageAfterRedirect.php',
            success: function(html) {
               $('body').append(html);
            }
         });
      }");
   }


   /**
    * Common Title Function
    *
    * @param string        $ref_pic_link    Path to the image to display (default '')
    * @param string        $ref_pic_text    Alt text of the icon (default '')
    * @param string        $ref_title       Title to display (default '')
    * @param array|string  $ref_btts        Extra items to display array(link=>text...) (default '')
    *
    * @return void
   **/
   static function displayTitle($ref_pic_link = "", $ref_pic_text = "", $ref_title = "", $ref_btts = "") {

      $ref_pic_text = htmlentities($ref_pic_text, ENT_QUOTES, 'UTF-8');

      echo "<div class='btn-group mb-3'>";
      if ($ref_pic_link!="") {
         $ref_pic_text = self::clean($ref_pic_text);
         echo Html::image($ref_pic_link, ['alt' => $ref_pic_text]);
      }

      if ($ref_title != "") {
         echo "<span class='btn btn-secondary pe-none' aria-disabled='true'>
            $ref_title
         </span>";
      }

      if (is_array($ref_btts) && count($ref_btts)) {
         foreach ($ref_btts as $key => $val) {
            echo "<a class='btn btn-outline-secondary' href='".$key."'>".$val."</a>";
         }
      }
      echo "</div>";
   }


   /**
   * Clean Display of Request
   *
   * @since 0.83.1
   *
   * @param string $request  SQL request
   *
   * @return string
   **/
   static function cleanSQLDisplay($request) {

      $request = str_replace("<", "&lt;", $request);
      $request = str_replace(">", "&gt;", $request);
      $request = str_ireplace("UNION", "<br/>UNION<br/>", $request);
      $request = str_ireplace("UNION ALL", "<br/>UNION ALL<br/>", $request);
      $request = str_ireplace("FROM", "<br/>FROM", $request);
      $request = str_ireplace("WHERE", "<br/>WHERE", $request);
      $request = str_ireplace("INNER JOIN", "<br/>INNER JOIN", $request);
      $request = str_ireplace("LEFT JOIN", "<br/>LEFT JOIN", $request);
      $request = str_ireplace("ORDER BY", "<br/>ORDER BY", $request);
      $request = str_ireplace("SORT", "<br/>SORT", $request);

      return $request;
   }

   /**
    * Display Debug Information
    *
    * @param boolean $with_session with session information (true by default)
    * @param boolean $ajax         If we're called from ajax (false by default)
    *
    * @return void
   **/
   static function displayDebugInfos($with_session = true, $ajax = false, $rand = null) {
      global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST, $DEBUG_AUTOLOAD, $TIMER_DEBUG;

      // Only for debug mode so not need to be translated
      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) { // mode debug
         if ($rand === null) {
            $rand = mt_rand();
         }
         echo "<div id='debugpanel$rand' class='container-fluid card debug-panel ".($ajax?"debug_ajax":"")."'>";

         echo "<ul class='nav nav-tabs' data-bs-toggle='tabs'>";
         echo "<li class='nav-item'><a class='nav-link active' data-bs-toggle='tab' href='#debugsummary$rand'>SUMMARY</a></li>";
         if ($CFG_GLPI["debug_sql"]) {
            echo "<li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#debugsql$rand'>SQL REQUEST</a></li>";
         }
         if ($CFG_GLPI["debug_vars"]) {
            echo "<li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#debugautoload$rand'>AUTOLOAD</a></li>";
            echo "<li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#debugpost$rand'>POST VARIABLE</a></li>";
            echo "<li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#debugget$rand'>GET VARIABLE</a></li>";
            if ($with_session) {
               echo "<li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#debugsession$rand'>SESSION VARIABLE</a></li>";
            }
            echo "<li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#debugserver$rand'>SERVER VARIABLE</a></li>";
         }
         echo "<li class='nav-item ms-auto'><a class='nav-link' href='#' id='close_debug$rand'><i class='fa fa-2x fa-times'></i><span class='sr-only'>".__('Close')."</span></a></li>";
         echo "</ul>";

         echo "<div class='card-body'>";
         echo "<div class='tab-content'>";

         echo "<div id='debugsummary$rand' class='tab-pane active'>";
         echo "<dl class='row'>";
         echo "<dt class='col-sm-3'>Execution time</dt>";
         echo "<dd class='col-sm-9'>" . sprintf(_n('%s second', '%s seconds', $TIMER_DEBUG->getTime()), $TIMER_DEBUG->getTime()) . "</dd>";
         echo "<dt class='col-sm-3'>Memory usage</dt>";
         echo "<dd class='col-sm-9'>" . Toolbox::getSize(memory_get_usage()) . "</dd>";
         if ($CFG_GLPI["debug_sql"]) {
            $queries_duration = array_sum($DEBUG_SQL['times']);
            echo "<dt class='col-sm-3'>SQL queries count</dt>";
            echo "<dd class='col-sm-9'>{$SQL_TOTAL_REQUEST}</dd>";
            echo "<dt class='col-sm-3'>SQL queries duration</dt>";
            echo "<dd class='col-sm-9'>" . sprintf(_n('%s second', '%s seconds', $queries_duration), $queries_duration) . "</dd>";
         }
         echo "</dl>";
         echo "</div>";

         if ($CFG_GLPI["debug_sql"]) {
            echo "<div id='debugsql$rand' class='tab-pane'>";
            echo "<h1>".$SQL_TOTAL_REQUEST." Queries ";
            echo "took  ".array_sum($DEBUG_SQL['times'])."s</h1>";

            echo "<table class='sql-debug'><tr><th>N&#176; </th><th>Queries</th><th>Time</th>";
            echo "<th>Rows</th><th>Errors</th></tr>";

            foreach ($DEBUG_SQL['queries'] as $num => $query) {
               echo "<tr><td>$num</td><td>";
               echo self::cleanSQLDisplay($query);
               echo "</td><td>";
               echo $DEBUG_SQL['times'][$num];
               echo "</td><td>";
               echo $DEBUG_SQL['rows'][$num] ?? 0;
               echo "</td><td>";
               if (isset($DEBUG_SQL['errors'][$num])) {
                  echo $DEBUG_SQL['errors'][$num];
               } else {
                  echo "&nbsp;";
               }
               echo "</td></tr>";
            }
            echo "</table>";
            echo "</div>";
         }
         if ($CFG_GLPI["debug_vars"]) {
            echo "<div id='debugautoload$rand' class='tab-pane" . (!$CFG_GLPI["debug_sql"] ? 'active' : '') . "'>".implode(', ', $DEBUG_AUTOLOAD)."</div>";
            echo "<div id='debugpost$rand' class='tab-pane'>";
            self::printCleanArray($_POST, 0, true);
            echo "</div>";
            echo "<div id='debugget$rand' class='tab-pane'>";
            self::printCleanArray($_GET, 0, true);
            echo "</div>";
            if ($with_session) {
               echo "<div id='debugsession$rand' class='tab-pane'>";
               self::printCleanArray($_SESSION, 0, true);
               echo "</div>";
            }
            echo "<div id='debugserver$rand' class='tab-pane'>";
            self::printCleanArray($_SERVER, 0, true);
            echo "</div>";
         }
         echo "</div>";

         echo Html::scriptBlock("
            $('#close_debug$rand').click(function() {
                $('#debugpanel$rand').css('display', 'none');
            });"
         );

         echo "</div></div>";
      }
   }


   /**
    * Display a Link to the last page using http_referer if available else use history.back
   **/
   static function displayBackLink() {
      $url_referer = self::getBackUrl();
      if ($url_referer !== false) {
         echo "<a href='$url_referer'>".__('Back')."</a>";
      } else {
         echo "<a href='javascript:history.back();'>".__('Back')."</a>";
      }
   }

   /**
    * Return an url for getting back to previous page.
    * Remove `forcetab` parameter if exists to prevent bad tab display
    *
    * @param string $url_in optional url to return (without forcetab param), if empty, we will user HTTP_REFERER from server
    *
    * @since 9.2.2
    *
    * @return mixed [string|boolean] false, if failed, else the url string
    */
   static function getBackUrl($url_in = "") {
      if (isset($_SERVER['HTTP_REFERER'])
          && strlen($url_in) == 0) {
         $url_in = $_SERVER['HTTP_REFERER'];
      }
      if (strlen($url_in) > 0) {
         $url = parse_url($url_in);

         if (isset($url['query'])) {
            parse_str($url['query'], $parameters);
            unset($parameters['forcetab']);
            unset($parameters['tab_params']);
            $new_query = http_build_query($parameters);
            return str_replace($url['query'], $new_query, $url_in);
         }

         return $url_in;
      }
      return false;
   }


   /**
    * Simple Error message page
    *
    * @param string  $message  displayed before dying
    * @param boolean $minimal  set to true do not display app menu (false by default)
    *
    * @return void
   **/
   static function displayErrorAndDie ($message, $minimal = false) {
      global $HEADER_LOADED;

      if (!$HEADER_LOADED) {
         if ($minimal || !Session::getCurrentInterface()) {
            self::nullHeader(__('Access denied'), '');

         } else if (Session::getCurrentInterface() == "central") {
            self::header(__('Access denied'), '');

         } else if (Session::getCurrentInterface() == "helpdesk") {
            self::helpHeader(__('Access denied'), '');
         }
      }

      TemplateRenderer::getInstance()->display('display_and_die.html.twig', [
         'title'   => __('Access denied'),
         'message' => $message,
      ]);

      self::nullFooter();
      exit ();
   }


   /**
    * Add confirmation on button or link before action
    *
    * @param $string             string   to display or array of string for using multilines
    * @param $additionalactions  string   additional actions to do on success confirmation
    *                                     (default '')
    *
    * @return string
   **/
   static function addConfirmationOnAction($string, $additionalactions = '') {

      return "onclick=\"".Html::getConfirmationOnActionScript($string, $additionalactions)."\"";
   }


   /**
    * Get confirmation on button or link before action
    *
    * @since 0.85
    *
    * @param $string             string   to display or array of string for using multilines
    * @param $additionalactions  string   additional actions to do on success confirmation
    *                                     (default '')
    *
    * @return string confirmation script
   **/
   static function getConfirmationOnActionScript($string, $additionalactions = '') {

      if (!is_array($string)) {
         $string = [$string];
      }
      $string            = Toolbox::addslashes_deep($string);
      $additionalactions = trim($additionalactions);
      $out               = "";
      $multiple          = false;
      $close_string      = '';
      // Manage multiple confirmation
      foreach ($string as $tab) {
         if (is_array($tab)) {
            $multiple      = true;
            $out          .="if (window.confirm('";
            $out          .= implode('\n', $tab);
            $out          .= "')){ ";
            $close_string .= "return true;} else { return false;}";
         }
      }
      // manage simple confirmation
      if (!$multiple) {
            $out          .="if (window.confirm('";
            $out          .= implode('\n', $string);
            $out          .= "')){ ";
            $close_string .= "return true;} else { return false;}";
      }
      $out .= $additionalactions.(substr($additionalactions, -1)!=';'?';':'').$close_string;
      return $out;
   }


   /**
    * Manage progresse bars
    *
    * @since 0.85
    *
    * @param $id                 HTML ID of the progress bar
    * @param $options    array   progress status
    *                    - create    do we have to create it ?
    *                    - message   add or change the message
    *                    - percent   current level
    *
    *
    * @return void
    **/
   static function progressBar($id, array $options = []) {

      $params            = [];
      $params['create']  = false;
      $params['message'] = null;
      $params['percent'] = -1;
      $params['display'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $out = '';
      if ($params['create']) {
         $out = <<<HTML
            <div class="progress" style="height: 16px" id="{$id}">
               <div class="progress-bar progress-bar-striped bg-info" role="progressbar"
                     style="width: 0%;"
                     aria-valuenow="0"
                     aria-valuemin="0" aria-valuemax="100"
                     id="{$id}_text">
               </div>
            </div>
         HTML;
      }

      if ($params['message'] !== null) {
         $out .= Html::scriptBlock(self::jsGetElementbyID($id.'_text').".html(\"".
                                addslashes($params['message'])."\");");
      }

      if (($params['percent'] >= 0)
          && ($params['percent'] <= 100)) {
         $out .= Html::scriptBlock(self::jsGetElementbyID($id.'_text').".css('width', '".
                                $params['percent']."%' );");
      }

      if (!$params['display']) {
         return $out;
      }

      echo $out;
      if (!$params['create']) {
         self::glpi_flush();
      }
   }


   /**
    * Create a Dynamic Progress Bar
    *
    * @param string $msg  initial message (under the bar)
    *
    * @return void
    **/
   static function createProgressBar($msg = "&nbsp;") {

      $options = ['create' => true];
      if ($msg != "&nbsp;") {
         $options['message'] = $msg;
      }

      self::progressBar('doaction_progress', $options);
   }

   /**
    * Change the Message under the Progress Bar
    *
    * @param string $msg message under the bar
    *
    * @return void
   **/
   static function changeProgressBarMessage($msg = "&nbsp;") {

      self::progressBar('doaction_progress', ['message' => $msg]);
      self::glpi_flush();
   }


   /**
    * Change the Progress Bar Position
    *
    * @param float  $crt   Current Value (less then $tot)
    * @param float  $tot   Maximum Value
    * @param string $msg   message inside the bar (default is %)
    *
    * @return void
   **/
   static function changeProgressBarPosition($crt, $tot, $msg = "") {

      $options = [];

      if (!$tot) {
         $options['percent'] = 0;
      } else if ($crt>$tot) {
         $options['percent'] = 100;
      } else {
         $options['percent'] = 100*$crt/$tot;
      }

      if ($msg != "") {
         $options['message'] = $msg;
      }

      self::progressBar('doaction_progress', $options);
      self::glpi_flush();
   }


   /**
    * Display a simple progress bar
    *
    * @param integer $width       Width   of the progress bar
    * @param float   $percent     Percent of the progress bar
    * @param array   $options     possible options:
    *            - title : string title to display (default Progesssion)
    *            - simple : display a simple progress bar (no title / only percent)
    *            - forcepadding : boolean force str_pad to force refresh (default true)
    *
    * @return void
   **/
   static function displayProgressBar($width, $percent, $options = []) {
      global $CFG_GLPI;

      $param['title']        = __('Progress');
      $param['simple']       = false;
      $param['forcepadding'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      $percentwidth = floor($percent*$width/100);
      $output       = "<div class='center'><table class='tab_cadre' width='".($width+20)."px'>";

      if (!$param['simple']) {
         $output .= "<tr><th class='center'>".$param['title']."&nbsp;".$percent."%</th></tr>";
      }
      $output .= "<tr><td>
                  <table class='tabcompact'><tr><td class='center' style='background:url(".$CFG_GLPI["root_doc"].
                   "/pics/loader.png) repeat-x; padding: 0px;font-size: 10px;' width='".
                   $percentwidth." px' height='12'>";

      if ($param['simple']) {
         $output .= $percent."%";
      } else {
         $output .= '&nbsp;';
      }

      $output .= "</td></tr></table></td>";
      $output .= "</tr></table>";
      $output .= "</div>";

      if (!$param['forcepadding']) {
         echo $output;
      } else {
         echo Toolbox::str_pad($output, 4096);
         self::glpi_flush();
      }
   }


   /**
    * Include common HTML headers
    *
    * @param string $title   title used for the page (default '')
    * @param string $sector  sector in which the page displayed is
    * @param string $item    item corresponding to the page displayed
    * @param string $option  option corresponding to the page displayed
    *
    * @return void
   **/
   static function includeHeader($title = '', $sector = 'none', $item = 'none', $option = '') {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      // complete title with id if exist
      if (isset($_GET['id']) && $_GET['id']) {
         $title = sprintf(__('%1$s - %2$s'), $title, $_GET['id']);
      }

      // Send UTF8 Headers
      header("Content-Type: text/html; charset=UTF-8");
      // Allow only frame from same server to prevent click-jacking
      header('x-frame-options:SAMEORIGIN');

      // Send extra expires header
      self::header_nocache();

      $theme = $_SESSION['glpipalette'] ?? 'auror';

      $tpl_vars = [
         'lang'      => $CFG_GLPI["languages"][$_SESSION['glpilanguage']][3],
         'title'     => $title,
         'theme'     => $theme,
         'css_files' => [],
         'js_files'  => [],
      ];

      Html::requireJs('tabler');

      $tpl_vars['css_files'][] = 'public/lib/base.css';

      if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax']) {
         Html::requireJs('notifications_ajax');
      }

      $tpl_vars['css_files'][] = 'public/lib/leaflet.css';
      Html::requireJs('leaflet');

      $tpl_vars['css_files'][] = 'public/lib/flatpickr.css';
      if ($theme != "darker") {
         $tpl_vars['css_files'][] = 'public/lib/flatpickr/themes/light.css';
      } else {
         $tpl_vars['css_files'][] = 'public/lib/flatpickr/themes/dark.css';
      }
      Html::requireJs('flatpickr');

      //on demand JS
      if ($sector != 'none' || $item != 'none' || $option != '') {
         $jslibs = [];
         if (isset($CFG_GLPI['javascript'][$sector])) {
            if (isset($CFG_GLPI['javascript'][$sector][$item])) {
               if (isset($CFG_GLPI['javascript'][$sector][$item][$option])) {
                  $jslibs = $CFG_GLPI['javascript'][$sector][$item][$option];
               } else {
                  $jslibs = $CFG_GLPI['javascript'][$sector][$item];
               }
            } else {
               $jslibs = $CFG_GLPI['javascript'][$sector];
            }
         }

         if (in_array('planning', $jslibs)) {
            Html::requireJs('planning');
         }

         if (in_array('fullcalendar', $jslibs)) {
            $tpl_vars['css_files'][] = 'public/lib/fullcalendar.css';
            Html::requireJs('fullcalendar');
         }

         if (in_array('reservations', $jslibs)) {
            $tpl_vars['css_files'][] = 'css/standalone/reservations.scss';
            Html::requireJs('reservations');
         }

         if (in_array('gantt', $jslibs)) {
            $tpl_vars['css_files'][] = 'public/lib/jquery-gantt.css';
            Html::requireJs('gantt');
         }

         if (in_array('kanban', $jslibs)) {
            Html::requireJs('kanban');
         }

         if (in_array('rateit', $jslibs)) {
            $tpl_vars['css_files'][] = 'public/lib/jquery.rateit.css';
            Html::requireJs('rateit');
         }

         if (in_array('dashboard', $jslibs)) {
            $tpl_vars['css_files'][] = 'css/standalone/dashboard.scss';
            Html::requireJs('dashboard');
         }

         if (in_array('marketplace', $jslibs)) {
            $tpl_vars['css_files'][] = 'css/standalone/marketplace.scss';
            Html::requireJs('marketplace');
         }

         if (in_array('rack', $jslibs)) {
            Html::requireJs('rack');
         }

         if (in_array('gridstack', $jslibs)) {
            $tpl_vars['css_files'][] = 'public/lib/gridstack.css';
            Html::requireJs('gridstack');
         }

         if (in_array('sortable', $jslibs)) {
            Html::requireJs('sortable');
         }

         if (in_array('tinymce', $jslibs)) {
            Html::requireJs('tinymce');
         }

         if (in_array('clipboard', $jslibs)) {
            Html::requireJs('clipboard');
         }

         if (in_array('jstree', $jslibs)) {
            $tpl_vars['css_files'][] = 'public/lib/jstree.css';
            Html::requireJs('jstree');
         }

         if (in_array('charts', $jslibs)) {
            $tpl_vars['css_files'][] = 'public/lib/chartist.css';
            $tpl_vars['css_files'][] = 'css/standalone/chartist.scss';
            Html::requireJs('charts');
         }

         if (in_array('codemirror', $jslibs)) {
            $tpl_vars['css_files'][] = 'public/lib/codemirror.css';
            Html::requireJs('codemirror');
         }

         if (in_array('photoswipe', $jslibs)) {
            $tpl_vars['css_files'][] = 'public/lib/photoswipe.css';
            Html::requireJs('photoswipe');
         }
      }

      if (Session::getCurrentInterface() == "helpdesk") {
         $tpl_vars['css_files'][] = 'public/lib/jquery.rateit.css';
         Html::requireJs('rateit');
      }

      //file upload is required... almost everywhere.
      Html::requireJs('fileupload');

      // load fuzzy search everywhere
      Html::requireJs('fuzzy');

      // load glpi dailog everywhere
      Html::requireJs('glpi_dialog');

      // load log filters everywhere
      Html::requireJs('log_filters');

      $tpl_vars['css_files'][] = 'css/legacy/jquery-glpi.scss';
      if (CommonGLPI::isLayoutWithMain()
          && !CommonGLPI::isLayoutExcludedPage()) {
         $tpl_vars['css_files'][] = 'public/lib/scrollable-tabs.css';
      }

      //  CSS link
      if (isset($_SESSION['glpihighcontrast_css']) && $_SESSION['glpihighcontrast_css']) {
         $tpl_vars['css_files'][] = 'css/legacy/highcontrast.scss';
      }
      $tpl_vars['css_files'][] = 'css/palettes/' . $theme . '.scss';

      // Add specific css for plugins
      if (isset($PLUGIN_HOOKS['add_css']) && count($PLUGIN_HOOKS['add_css'])) {

         foreach ($PLUGIN_HOOKS["add_css"] as $plugin => $files) {
            if (!Plugin::isPluginActive($plugin)) {
               continue;
            }

            $plugin_web_dir  = Plugin::getWebDir($plugin, false);

            if (!is_array($files)) {
               $files = [$files];
            }

            foreach ($files as $file) {
               $tpl_vars['css_files'][] = "$plugin_web_dir/$file";
            }
         }
      }

      // AJAX library
      $tpl_vars['js_files'][] = 'public/lib/base.js';

      // Search
      $tpl_vars['js_modules'][] = 'js/modules/Search/ResultsView.js';
      $tpl_vars['js_modules'][] = 'js/modules/Search/Table.js';

      // layout
      if (CommonGLPI::isLayoutWithMain()
          && !CommonGLPI::isLayoutExcludedPage()) {
         $tpl_vars['js_files'][] = 'public/lib/scrollable-tabs.js';
      }

      TemplateRenderer::getInstance()->display('layout/parts/head.html.twig', $tpl_vars);

      self::glpi_flush();
   }


   /**
    * @since 0.90
    *
    * @return array
   **/
   static function getMenuInfos() {
      global $CFG_GLPI;

      $menu = [
         'assets' => [
            'title' => __('Assets'),
            'types' => array_merge([
               'Computer', 'Monitor', 'Software',
               'NetworkEquipment', 'Peripheral', 'Printer',
               'CartridgeItem', 'ConsumableItem', 'Phone',
               'Rack', 'Enclosure', 'PDU', 'PassiveDCEquipment', 'Unmanaged'
            ], $CFG_GLPI['devices_in_menu']),
            'default_dashboard' => '/front/dashboard_assets.php',
            'icon'    => 'fas fa-boxes'
         ],
         'helpdesk' => [
            'title' => __('Assistance'),
            'types' => [
               'Ticket', 'Problem', 'Change',
               'Planning', 'Stat', 'TicketRecurrent', 'RecurrentChange'
            ],
            'default_dashboard' => '/front/dashboard_helpdesk.php',
            'icon'    => 'fas fa-headset'
         ],
         'management' => [
            'title' => __('Management'),
            'types' => [
               'SoftwareLicense','Budget', 'Supplier', 'Contact', 'Contract',
               'Document', 'Line', 'Certificate', 'Datacenter', 'Cluster', 'Domain',
               'Appliance'
            ],
            'icon'  => 'fas fa-wallet'
         ],
         'tools' => [
            'title' => __('Tools'),
            'types' => [
               'Project', 'Reminder', 'RSSFeed', 'KnowbaseItem',
               'ReservationItem', 'Report', 'MigrationCleaner',
               'SavedSearch', 'Impact'
            ],
            'icon' => 'fas fa-briefcase'
         ],
         'plugins' => [
            'title' => _n('Plugin', 'Plugins', Session::getPluralNumber()),
            'types' => [],
            'icon'  => 'fa-puzzle-piece'
         ],
         'admin' => [
            'title' => __('Administration'),
            'types' => [
               'User', 'Group', 'Entity', 'Rule',
               'Profile', 'QueuedNotification', 'Glpi\\Event', 'Glpi\Inventory\Inventory'
            ],
            'icon'  => 'fas fa-user-shield'
         ],
         'config' => [
            'title' => __('Setup'),
            'types' => [
               'CommonDropdown', 'CommonDevice', 'Notification',
               'SLM', 'Config', 'FieldUnicity', 'CronTask', 'Auth',
               'MailCollector', 'Link', 'Plugin'
            ],
            'icon'  => 'fas fa-cogs'
         ],

         // special items
         'preference' => [
            'title'   => __('My settings'),
            'default' => '/front/preference.php',
            'icon'    => 'fas fa-user-cog',
            'display' => false,
         ],
      ];

      return $menu;
   }

   /**
    * Generate menu array in $_SESSION['glpimenu'] and return the array
    *
    * @since  9.2
    *
    * @param  boolean $force do we need to force regeneration of $_SESSION['glpimenu']
    * @return array          the menu array
    */
   static function generateMenuSession($force = false) {
      global $PLUGIN_HOOKS;
      $menu = [];

      if ($force
          || !isset($_SESSION['glpimenu'])
          || !is_array($_SESSION['glpimenu'])
          || (count($_SESSION['glpimenu']) == 0)) {

         $menu = self::getMenuInfos();

         // Permit to plugins to add entry to others sector !
         if (isset($PLUGIN_HOOKS["menu_toadd"]) && count($PLUGIN_HOOKS["menu_toadd"])) {

            foreach ($PLUGIN_HOOKS["menu_toadd"] as $plugin => $items) {
               if (!Plugin::isPluginActive($plugin)) {
                  continue;
               }
               if (count($items)) {
                  foreach ($items as $key => $val) {
                     if (is_array($val)) {
                        foreach ($val as $k => $object) {
                           $menu[$key]['types'][] = $object;
                        }
                     } else {
                        if (isset($menu[$key])) {
                           $menu[$key]['types'][] = $val;
                        }
                     }
                  }
               }
            }
            // Move Setup menu ('config') to the last position in $menu (always last menu),
            // in case some plugin inserted a new top level menu
            $categories = array_keys($menu);
            $menu += array_splice($menu, array_search('config', $categories, true), 1);
         }

         foreach ($menu as $category => $entries) {
            if (isset($entries['types']) && count($entries['types'])) {
               foreach ($entries['types'] as $type) {
                  if ($data = $type::getMenuContent()) {
                     // Multi menu entries management
                     if (isset($data['is_multi_entries']) && $data['is_multi_entries']) {
                        if (!isset($menu[$category]['content'])) {
                           $menu[$category]['content'] = [];
                        }
                        $menu[$category]['content'] += $data;
                     } else {
                        $menu[$category]['content'][strtolower($type)] = $data;
                     }
                     if (!isset($menu[$category]['title']) && isset($data['title'])) {
                        $menu[$category]['title'] = $data['title'];
                     }
                     if (!isset($menu[$category]['default']) && isset($data['default'])) {
                        $menu[$category]['default'] = $data['default'];
                     }
                  }
               }
            }
            // Define default link :
            if (! isset($menu[$category]['default']) && isset($menu[$category]['content']) && count($menu[$category]['content'])) {
               foreach ($menu[$category]['content'] as $val) {
                  if (isset($val['page'])) {
                     $menu[$category]['default'] = $val['page'];
                     break;
                  }
               }
            }
         }

         $allassets = [
            'Computer',
            'Monitor',
            'Peripheral',
            'NetworkEquipment',
            'Phone',
            'Printer'
         ];

         foreach ($allassets as $type) {
            if (isset($menu['assets']['content'][strtolower($type)])) {
               $menu['assets']['content']['allassets']['title']            = __('Global');
               $menu['assets']['content']['allassets']['shortcut']         = '';
               $menu['assets']['content']['allassets']['page']             = '/front/allassets.php';
               $menu['assets']['content']['allassets']['icon']             = 'fas fa-list';
               $menu['assets']['content']['allassets']['links']['search']  = '/front/allassets.php';
               break;
            }
         }

         $_SESSION['glpimenu'] = $menu;
         // echo 'menu load';
      } else {
         $menu = $_SESSION['glpimenu'];
      }

      return $menu;
   }

   /**
    * Returns menu sector corresponding to given itemtype.
    *
    * @param string $itemtype
    *
    * @return string|null
    */
   static function getMenuSectorForItemtype(string $itemtype): ?string {
      $menu = self::getMenuInfos();
      foreach ($menu as $sector => $params) {
         if (array_key_exists('types', $params) && in_array($itemtype, $params['types'])) {
            return $sector;
         }
      }
      return null;
   }


   /**
    * Print a nice HTML head for every page
    *
    * @param string $title   title of the page
    * @param string $url     not used anymore
    * @param string $sector  sector in which the page displayed is
    * @param string $item    item corresponding to the page displayed
    * @param string $option  option corresponding to the page displayed
   **/
   static function header($title, $url = '', $sector = "none", $item = "none", $option = "") {
      global $CFG_GLPI, $HEADER_LOADED, $DB;

      // If in modal : display popHeader
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         return self::popHeader($title, $url, false, $sector, $item, $option);
      }
      // Print a nice HTML-head for every page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;
      // Force lower case for sector and item
      $sector = strtolower($sector);
      $item   = strtolower($item);

      self::includeHeader($title, $sector, $item, $option);

      $tmp_active_item = explode("/", $item);
      $active_item     = array_pop($tmp_active_item);
      $menu            = self::generateMenuSession($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);
      $menu_active     = $menu[$sector]['content'][$active_item]['title'] ?? "";

      $menu = Plugin::doHookFunction("redefine_menus", $menu);

      $tpl_vars = [
         'menu'                   => $menu,
         'sector'                 => $sector,
         'item'                   => $item,
         'option'                 => $option,
         'menu_active'            => $menu_active,
      ];

      $help_url_key = Session::getCurrentInterface() === 'central' ? 'central_doc_url' : 'helpdesk_doc_url';
      $help_url = !empty($CFG_GLPI[$help_url_key]) ? $CFG_GLPI[$help_url_key] : 'http://glpi-project.org/help-central';

      $tpl_vars['help_url'] = $help_url;

      TemplateRenderer::getInstance()->display('layout/parts/page_header.html.twig', $tpl_vars);

      // TODO move to main_class
      $main_class = "layout_".$_SESSION['glpilayout'];
      if ((strpos($_SERVER['REQUEST_URI'], ".form.php") !== false)
          && isset($_GET['id']) && ($_GET['id'] > 0)) {
         if (!CommonGLPI::isLayoutExcludedPage()) {
            $main_class.= " form";
         } else {
            $main_class = "";
         }
      }
      echo "";

      if ($DB->isSlave()
          && !$DB->first_connection) {
         echo "<div id='dbslave-float'>";
         echo "<a href='#see_debug'>".__('SQL replica: read only')."</a>";
         echo "</div>";
      }

      // call static function callcron() every 5min
      CronTask::callCron();
   }


   /**
    * Print footer for every page
    *
    * @param $keepDB boolean, closeDBConnections if false (false by default)
   **/
   static function footer($keepDB = false) {
      global $CFG_GLPI, $FOOTER_LOADED, $TIMER_DEBUG;

      // If in modal : display popFooter
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         return self::popFooter();
      }

      // Print foot for every page
      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;

      echo self::getCoreVariablesForJavascript(true);

      $tpl_vars = [
         'execution_time'      => $TIMER_DEBUG->getTime(),
         'memory_usage'        => memory_get_usage(),
         'js_files'            => [],
      ];

      // On demand scripts
      foreach ($_SESSION['glpi_js_toload'] ?? [] as $scripts) {
         if (!is_array($scripts)) {
            $scripts = [$scripts];
         }
         foreach ($scripts as $script) {
            $tpl_vars['js_files'][] = $script;
         }
      }
      $_SESSION['glpi_js_toload'] = [];

      // Locales for js libraries
      if (isset($_SESSION['glpilanguage'])) {
         // select2
         $filename = sprintf(
            'public/lib/select2/js/i18n/%s.js',
            $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2]
         );
         if (file_exists(GLPI_ROOT.'/'.$filename)) {
            $tpl_vars['js_files'][] = $filename;
         }
      }

      $tpl_vars['js_files'][] = 'js/common.js';
      $tpl_vars['js_files'][] = 'js/misc.js';

      // TODO Add Ajax notifications script block
      // TODO Add plugins scripts

      TemplateRenderer::getInstance()->display('layout/parts/page_footer.html.twig', $tpl_vars);

      self::displayDebugInfos();

      if (!$keepDB) {
         closeDBConnections();
      }
   }


   /**
    * Display Ajax Footer for debug
   **/
   static function ajaxFooter() {

      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) { // mode debug
         $rand = mt_rand();
         echo "<div class='center d-none d-md-block btn-group' id='debugajax'>";
         echo "<a class='btn btn-sm btn-danger debug-float' href=\"javascript:showHideDiv('debugpanel$rand','','','');\">
            <i class='fas fa-bug'></i>
            <span>AJAX DEBUG</span>
         </a>";
         if (!isset($_GET['full_page_tab'])
             && strstr($_SERVER['REQUEST_URI'], '/ajax/common.tabs.php')) {
            echo "<a href='".$_SERVER['REQUEST_URI']."&full_page_tab=1' class='btn btn-sm btn-danger'>
               <i class='fas fa-external-link-alt'></i>
               <span>Display only tab for debug</span>
            </a>";
         }
         echo "</div>";
         self::displayDebugInfos(false, true, $rand);
         echo "</div>";
      }
   }


   /**
    * Print a simple HTML head with links
    *
    * @param string $title  title of the page
    * @param array  $links  links to display
   **/
   static function simpleHeader($title, $links = []) {
      global $HEADER_LOADED;

      // Print a nice HTML-head for help page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;

      self::includeHeader($title);

      // force layout to horizontal if not connected
      if (!Session::getLoginUserID()) {
         $_SESSION['glpipage_layout'] = "horizontal";
      }

      // construct menu from passed links
      $menu = [];
      foreach ($links as $label => $url) {
         $menu[] = [
            'title'   => $label,
            'default' => $url
         ];
      }

      TemplateRenderer::getInstance()->display('layout/parts/page_header.html.twig', [
         'menu' => $menu
      ]);

      CronTask::callCron();
   }


   /**
    * Print a nice HTML head for help page
    *
    * @param string $title  title of the page
    * @param string $url    not used anymore
   **/
   static function helpHeader($title, $url = '') {
      global $HEADER_LOADED, $PLUGIN_HOOKS;

      // Print a nice HTML-head for help page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;

      self::includeHeader($title, 'self-service');

      $menu = [
         'home' => [
            'default' => '/front/helpdesk.public.php',
            'title'   => __('Home'),
            'icon'    => 'fas fa-home',
         ],
      ];

      if (Session::haveRight("ticket", CREATE)) {
         $menu['create_ticket'] = [
            'default' => '/front/helpdesk.public.php?create_ticket=1',
            'title'   => __('Create a ticket'),
            'icon'    => 'fas fa-plus',
         ];
      }

      if (Session::haveRight("ticket", CREATE)
          || Session::haveRight("ticket", Ticket::READMY)
          || Session::haveRight("followup", ITILFollowup::SEEPUBLIC)
      ) {
         $menu['tickets'] = [
            'default' => '/front/ticket.php',
            'title'   => _n('Ticket', 'Tickets', Session::getPluralNumber()),
            'icon'    => Ticket::getIcon(),
         ];
      }

      if (Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
         $menu['reservation'] = [
            'default' => '/front/reservationitem.php',
            'title'   => _n('Reservation', 'Reservations', Session::getPluralNumber()),
            'icon'    => ReservationItem::getIcon(),
         ];
      }

      if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
         $menu['faq'] = [
            'default' => '/front/helpdesk.faq.php',
            'title'   => __('FAQ'),
            'icon'    => KnowbaseItem::getIcon(),
         ];
      }

      if (isset($PLUGIN_HOOKS["helpdesk_menu_entry"])
          && count($PLUGIN_HOOKS["helpdesk_menu_entry"])) {

         $menu['plugins'] = [
            'title' => __("Plugins"),
            'icon'  => Plugin::getIcon(),
         ];

         foreach ($PLUGIN_HOOKS["helpdesk_menu_entry"] as $plugin => $active) {
            if (!Plugin::isPluginActive($plugin)) {
               continue;
            }
            if ($active) {
               $infos = Plugin::getInfo($plugin);
               $link = "";
               if (is_string($PLUGIN_HOOKS["helpdesk_menu_entry"][$plugin])) {
                  $link = $PLUGIN_HOOKS["helpdesk_menu_entry"][$plugin];
               }
               $infos['page'] = $link;
               $infos['title'] = $infos['name'];
               $menu['plugins']['content'][$plugin] = $infos;
            }
         }
      }

      //$menu = Plugin::doHookFunction("redefine_menus", $menu);

      TemplateRenderer::getInstance()->display('layout/parts/page_header.html.twig', [
         'menu' => $menu
      ]);

      // call static function callcron() every 5min
      CronTask::callCron();
   }


   /**
    * Print footer for help page
   **/
   static function helpFooter() {
      if (!isCommandLine()) {
         self::footer();
      };
   }


   /**
    * Print a nice HTML head with no controls
    *
    * @param string $title  title of the page
    * @param string $url    not used anymore
   **/
   static function nullHeader($title, $url = '') {
      global $HEADER_LOADED;

      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;
      // Print a nice HTML-head with no controls

      // Detect root_doc in case of error
      Config::detectRootDoc();

      // Send UTF8 Headers
      header("Content-Type: text/html; charset=UTF-8");

      // Send extra expires header if configured
      self::header_nocache();

      if (isCommandLine()) {
         return true;
      }

      self::includeHeader($title);

      // Body with configured stuff
      echo "<body>";
      echo "<main role='main' id='page'>";
      echo "<br><br>";
      echo "<div id='bloc'>";
      echo "<div id='logo_bloc'></div>";
   }


   /**
    * Print footer for null page
   **/
   static function nullFooter() {
      if (!isCommandLine()) {
         self::footer();
      };
   }


   /**
    * Print a nice HTML head for modal window (nothing to display)
    *
    * @param string  $title    title of the page
    * @param string  $url      not used anymore
    * @param boolean $iframed  indicate if page loaded in iframe - css target
    * @param string  $sector    sector in which the page displayed is (default 'none')
    * @param string  $item      item corresponding to the page displayed (default 'none')
    * @param string  $option    option corresponding to the page displayed (default '')
   **/
   static function popHeader(
      $title,
      $url = '',
      $iframed = false,
      $sector = "none",
      $item = "none",
      $option = ""
   ) {
      global $HEADER_LOADED;

      // Print a nice HTML-head for every page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;

      self::includeHeader($title, $sector, $item, $option); // Body
      echo "<body class='".($iframed? "iframed": "")."'>";
      self::displayMessageAfterRedirect();
      echo "<div id='page'>"; // Force legacy styles for now
   }


   /**
    * Print footer for a modal window
   **/
   static function popFooter() {
      global $FOOTER_LOADED;

      if ($FOOTER_LOADED) {
          return;
      }
      $FOOTER_LOADED = true;

      // Print foot
      self::loadJavascript();
      echo "</body></html>";
   }


   /**
    * Flushes the system write buffers of PHP and whatever backend PHP is using (CGI, a web server, etc).
    * This attempts to push current output all the way to the browser with a few caveats.
    * @see https://www.sitepoint.com/php-streaming-output-buffering-explained/
   **/
   static function glpi_flush() {

      if (function_exists("ob_flush")
          && (ob_get_length() !== false)) {
         ob_flush();
      }

      flush();
   }


   /**
    * Set page not to use the cache
   **/
   static function header_nocache() {

      header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date du passe
   }



   /**
    * show arrow for massives actions : opening
    *
    * @param string  $formname
    * @param boolean $fixed     used tab_cadre_fixe in both tables
    * @param boolean $ontop     display on top of the list
    * @param boolean $onright   display on right of the list
    *
    * @deprecated 0.84
   **/
   static function openArrowMassives($formname, $fixed = false, $ontop = false, $onright = false) {
      global $CFG_GLPI;

      Toolbox::deprecated('openArrowMassives() method is deprecated');

      if ($fixed) {
         echo "<table class='tab_glpi' width='950px'>";
      } else {
         echo "<table class='tab_glpi' width='80%'>";
      }

      echo "<tr>";
      if (!$onright) {
         echo "<td><i class='fas fa-level-down-alt fa-flip-horizontal fa-lg mx-2'></i></td>";
      } else {
         echo "<td class='left' width='80%'></td>";
      }
      echo "<td class='center' style='white-space:nowrap;'>";
      echo "<a onclick= \"if ( markCheckboxes('$formname') ) return false;\"
             href='#'>".__('Check all')."</a></td>";
      echo "<td>/</td>";
      echo "<td class='center' style='white-space:nowrap;'>";
      echo "<a onclick= \"if ( unMarkCheckboxes('$formname') ) return false;\"
             href='#'>".__('Uncheck all')."</a></td>";

      if ($onright) {
         echo "<td><i class='fas fa-level-down-alt fa-lg mx-2'></i>";
      } else {
         echo "<td class='left' width='80%'>";
      }

   }


   /**
    * show arrow for massives actions : closing
    *
    * @param $actions array of action : $name -> $label
    * @param $confirm array of confirmation string (optional)
    *
    * @deprecated 0.84
   **/
   static function closeArrowMassives($actions, $confirm = []) {

      Toolbox::deprecated('closeArrowMassives() method is deprecated');

      if (count($actions)) {
         foreach ($actions as $name => $label) {
            if (!empty($name)) {

               echo "<input type='submit' name='$name' ";
               if (is_array($confirm) && isset($confirm[$name])) {
                  echo self::addConfirmationOnAction($confirm[$name]);
               }
               echo "value=\"".addslashes($label)."\" class='submit'>&nbsp;";
            }
         }
      }
      echo "</td></tr>";
      echo "</table>";
   }


   /**
    * Get "check All as" checkbox
    *
    * @since 0.84
    *
    * @param $container_id  string html of the container of checkboxes link to this check all checkbox
    * @param $rand          string rand value to use (default is auto generated)(default '')
    *
    * @return string
   **/
   static function getCheckAllAsCheckbox($container_id, $rand = '') {

      if (empty($rand)) {
         $rand = mt_rand();
      }

      $out  = "<div class='form-group-checkbox'>
                  <input title='".__s('Check all as')."' type='checkbox' class='new_checkbox' ".
                   "name='_checkall_$rand' id='checkall_$rand' ".
                    "onclick= \"if ( checkAsCheckboxes('checkall_$rand', '$container_id'))
                                                   {return true;}\">
                  <label class='label-checkbox' for='checkall_$rand' title='".__s('Check all as')."'>
                     <span class='check'></span>
                     <span class='box'></span>
                  </label>
               </div>";

      // permit to shift select checkboxes
      $out.= Html::scriptBlock("\$(function() {\$('#$container_id input[type=\"checkbox\"]').shiftSelectable();});");

      return $out;
   }


   /**
    * Get the jquery criterion for massive checkbox update
    * We can filter checkboxes by a container or by a tag. We can also select checkboxes that have
    * a given tag and that are contained inside a container
    *
    * @since 0.85
    *
    * @param array $options  array of parameters:
    *    - tag_for_massive tag of the checkboxes to update
    *    - container_id    if of the container of the checkboxes
    *
    * @return string  the javascript code for jquery criterion or empty string if it is not a
    *         massive update checkbox
   **/
   static function getCriterionForMassiveCheckboxes(array $options) {

      $params                    = [];
      $params['tag_for_massive'] = '';
      $params['container_id']    = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if (!empty($params['tag_for_massive'])
          || !empty($params['container_id'])) {
         // Filtering on the container !
         if (!empty($params['container_id'])) {
            $criterion = '#' . $params['container_id'] . ' ';
         } else {
            $criterion = '';
         }

         // We only want the checkbox input
         $criterion .= 'input[type="checkbox"]';

         // Only the given massive tag !
         if (!empty($params['tag_for_massive'])) {
            $criterion .= '[data-glpicore-cb-massive-tags~="' . $params['tag_for_massive'] . '"]';
         }

         // Only enabled checkbox
         $criterion .= ':enabled';

         return addslashes($criterion);
      }
      return '';
   }


   /**
    * Get a checkbox.
    *
    * @since 0.85
    *
    * @param array $options  array of parameters:
    *    - title         its title
    *    - name          its name
    *    - id            its id
    *    - value         the value to set when checked
    *    - readonly      can we edit it ?
    *    - massive_tags  the tag to set for massive checkbox update
    *    - checked       is it checked or not ?
    *    - zero_on_empty do we send 0 on submit when it is not checked ?
    *    - specific_tags HTML5 tags to add
    *    - criterion     the criterion for massive checkbox
    *
    * @return string  the HTML code for the checkbox
   **/
   static function getCheckbox(array $options) {
      global $CFG_GLPI;

      $params                    = [];
      $params['title']           = '';
      $params['name']            = '';
      $params['rand']            = mt_rand();
      $params['id']              = "check_".$params['rand'];
      $params['value']           = 1;
      $params['readonly']        = false;
      $params['massive_tags']    = '';
      $params['checked']         = false;
      $params['zero_on_empty']   = true;
      $params['specific_tags']   = [];
      $params['criterion']       = [];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $out = "<span class='form-group-checkbox'>";
      $out.= "<input type='checkbox' class='new_checkbox' ";

      foreach (['id', 'name', 'title', 'value'] as $field) {
         if (!empty($params[$field])) {
            $out .= " $field='".$params[$field]."'";
         }
      }

      $criterion = self::getCriterionForMassiveCheckboxes($params['criterion']);
      if (!empty($criterion)) {
         $out .= " onClick='massiveUpdateCheckbox(\"$criterion\", this)'";
      }

      if ($params['zero_on_empty']) {
         $out                               .= " data-glpicore-cb-zero-on-empty='1'";
         $CFG_GLPI['checkbox-zero-on-empty'] = true;

      }

      if (!empty($params['massive_tags'])) {
         $params['specific_tags']['data-glpicore-cb-massive-tags'] = $params['massive_tags'];
      }

      if (!empty($params['specific_tags'])) {
         foreach ($params['specific_tags'] as $tag => $values) {
            if (is_array($values)) {
               $values = implode(' ', $values);
            }
            $out .= " $tag='$values'";
         }
      }

      if ($params['readonly']) {
         $out .= " disabled='disabled'";
      }

      if ($params['checked']) {
         $out .= " checked";
      }

      $out .= ">";
      $out .= "<label class='label-checkbox' title=\"".$params['title']."\" for='".$params['id']."'>";
      $out .= " <span class='check'></span>";
      $out .= " <span class='box'";
      if (isset($params['onclick'])) {
         $params['onclick'] = htmlspecialchars($params['onclick'], ENT_QUOTES);
         $out .= " onclick='{$params['onclick']}'";
      }
      $out .= "></span>";
      $out .= "&nbsp;";
      $out .= "</label>";
      $out .= "</span>";

      if (!empty($criterion)) {
         $out .= Html::scriptBlock("\$(function() {\$('$criterion').shiftSelectable();});");
      }

      return $out;
   }


   /**
    * @brief display a checkbox that $_POST 0 or 1 depending on if it is checked or not.
    * @see Html::getCheckbox()
    *
    * @since 0.85
    *
    * @param $options   array
    *
    * @return void
   **/
   static function showCheckbox(array $options = []) {
      echo self::getCheckbox($options);
   }


   /**
    * Get the massive action checkbox
    *
    * @since 0.84
    *
    * @param string  $itemtype  Massive action itemtype
    * @param integer $id        ID of the item
    * @param array   $options
    *
    * @return string
   **/
   static function getMassiveActionCheckBox($itemtype, $id, array $options = []) {

      $options['checked']       = (isset($_SESSION['glpimassiveactionselected'][$itemtype][$id]));
      if (!isset($options['specific_tags']['data-glpicore-ma-tags'])) {
         $options['specific_tags']['data-glpicore-ma-tags'] = 'common';
      }

      // encode quotes and brackets to prevent maformed name attribute
      $id = htmlspecialchars($id, ENT_QUOTES);
      $id = str_replace(['[', ']'], ['&amp;#91;', '&amp;#93;'], $id);
      $options['name']          = "item[$itemtype][".$id."]";

      $options['zero_on_empty'] = false;

      return self::getCheckbox($options);
   }


   /**
    * Show the massive action checkbox
    *
    * @since 0.84
    *
    * @param string  $itemtype  Massive action itemtype
    * @param integer $id        ID of the item
    * @param array   $options
    *
    * @return void
   **/
   static function showMassiveActionCheckBox($itemtype, $id, array $options = []) {
      echo Html::getMassiveActionCheckBox($itemtype, $id, $options);
   }


   /**
    * Display open form for massive action
    *
    * @since 0.84
    *
    * @param string $name  given name/id to the form
    *
    * @return void
   **/
   static function openMassiveActionsForm($name = '') {
      echo Html::getOpenMassiveActionsForm($name);
   }


   /**
    * Get open form for massive action string
    *
    * @since 0.84
    *
    * @param string $name  given name/id to the form
    *
    * @return string
   **/
   static function getOpenMassiveActionsForm($name = '') {
      global $CFG_GLPI;

      if (empty($name)) {
         $name = 'massaction_'.mt_rand();
      }
      return  "<form name='$name' id='$name' method='post'
               action='".$CFG_GLPI["root_doc"]."/front/massiveaction.php'
               enctype='multipart/form-data'>";
   }


   /**
    * Display massive actions
    *
    * @since 0.84 (before Search::displayMassiveActions)
    * @since 0.85 only 1 parameter (in 0.84 $itemtype required)
    *
    * @todo replace 'hidden' by data-glpicore-ma-tags ?
    *
    * @param $options   array    of parameters
    * must contains :
    *    - container       : DOM ID of the container of the item checkboxes (since version 0.85)
    * may contains :
    *    - num_displayed   : integer number of displayed items. Permit to check suhosin limit.
    *                        (default -1 not to check)
    *    - ontop           : boolean true if displayed on top (default true)
    *    - forcecreate     : boolean force creation of modal window (default = false).
    *            Modal is automatically created when displayed the ontop item.
    *            If only a bottom one is displayed use it
    *    - check_itemtype   : string alternate itemtype to check right if different from main itemtype
    *                         (default empty)
    *    - check_items_id   : integer ID of the alternate item used to check right / optional
    *                         (default empty)
    *    - is_deleted       : boolean is massive actions for deleted items ?
    *    - extraparams      : string extra URL parameters to pass to massive actions (default empty)
    *                         if ([extraparams]['hidden'] is set : add hidden fields to post)
    *    - specific_actions : array of specific actions (do not use standard one)
    *    - add_actions      : array of actions to add (do not use standard one)
    *    - confirm          : string of confirm message before massive action
    *    - item             : CommonDBTM object that has to be passed to the actions
    *    - tag_to_send      : the tag of the elements to send to the ajax window (default: common)
    *    - display          : display or return the generated html (default true)
    *
    * @return bool|string     the html if display parameter is false, or true
   **/
   static function showMassiveActions($options = []) {
      global $CFG_GLPI;

      /// TODO : permit to pass several itemtypes to show possible actions of all types : need to clean visibility management after

      $p['ontop']             = true;
      $p['num_displayed']     = -1;
      $p['forcecreate']       = false;
      $p['check_itemtype']    = '';
      $p['check_items_id']    = '';
      $p['is_deleted']        = false;
      $p['extraparams']       = [];
      $p['width']             = 800;
      $p['height']            = 400;
      $p['specific_actions']  = [];
      $p['add_actions']       = [];
      $p['confirm']           = '';
      $p['rand']              = '';
      $p['container']         = '';
      $p['display_arrow']     = true;
      $p['title']             = _n('Action', 'Actions', Session::getPluralNumber());
      $p['item']              = false;
      $p['tag_to_send']       = 'common';
      $p['display']           = true;

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      $url = $CFG_GLPI['root_doc']."/ajax/massiveaction.php";
      if ($p['container']) {
         $p['extraparams']['container'] = $p['container'];
      }
      if ($p['is_deleted']) {
         $p['extraparams']['is_deleted'] = 1;
      }
      if (!empty($p['check_itemtype'])) {
         $p['extraparams']['check_itemtype'] = $p['check_itemtype'];
      }
      if (!empty($p['check_items_id'])) {
         $p['extraparams']['check_items_id'] = $p['check_items_id'];
      }
      if (is_array($p['specific_actions']) && count($p['specific_actions'])) {
         $p['extraparams']['specific_actions'] = $p['specific_actions'];
      }
      if (is_array($p['add_actions']) && count($p['add_actions'])) {
         $p['extraparams']['add_actions'] = $p['add_actions'];
      }
      if ($p['item'] instanceof CommonDBTM) {
         $p['extraparams']['item_itemtype'] = $p['item']->getType();
         $p['extraparams']['item_items_id'] = $p['item']->getID();
      }

      // Manage modal window
      if (isset($_REQUEST['_is_modal']) && $_REQUEST['_is_modal']) {
         $p['extraparams']['hidden']['_is_modal'] = 1;
      }

      $identifier = md5($url.serialize($p['extraparams']).$p['rand']);
      $max        = Toolbox::get_max_input_vars();
      $out = '';

      if (($p['num_displayed'] >= 0)
          && ($max > 0)
          && ($max < ($p['num_displayed']+10))) {
         if (!$p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
            $out .= "<span class='b'>";
            $out .= __('Selection too large, massive action disabled.')."</span>";
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
               $out .= __('To increase the limit: change max_input_vars or suhosin.post.max_vars in php configuration.');
            }
         }
      } else {
         // Create Modal window on top
         if ($p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
                $out .= "<div id='massiveactioncontent$identifier'></div>";

            if (!empty($p['tag_to_send'])) {
               $js_modal_fields  = "var items = $('";
               if (!empty($p['container'])) {
                  $js_modal_fields .= '#'.$p['container'].' ';
               }
               $js_modal_fields .= "[data-glpicore-ma-tags~=".$p['tag_to_send']."]').each(function( index ) {
                  fields[$(this).attr('name')] = $(this).attr('value');
                  if (($(this).attr('type') == 'checkbox') && (!$(this).is(':checked'))) {
                     fields[$(this).attr('name')] = 0;
                  }
               });";
            } else {
               $js_modal_fields = "";
            }

            $out .= Ajax::createModalWindow(
               'modal_massiveaction_window'.$identifier,
               $url,
               [
                  'title'           => $p['title'],
                  'container'       => 'massiveactioncontent'.$identifier,
                  'extraparams'     => $p['extraparams'],
                  'width'           => $p['width'],
                  'height'          => $p['height'],
                  'js_modal_fields' => $js_modal_fields,
                  'display'         => false
                  ]
            );
         }
         if ($p['display_arrow']) {
            $out .= "<i class='fas fa-level-".($p['ontop']?'down':'up')."-alt fa-flip-horizontal fa-lg ms-2 me-1 mt-2'></i>";
         }
         $out .= "<a title='".__('Massive actions')."'
                     data-bs-toggle='tooltip' data-bs-placement='top'
                     class='btn btn-sm btn-outline-secondary me-2' ";
         if (is_array($p['confirm'] || strlen($p['confirm']))) {
            $out .= self::addConfirmationOnAction($p['confirm'], "modal_massiveaction_window$identifier.show();");
         } else {
            $out .= "onclick='modal_massiveaction_window$identifier.show();'";
         }
         $out .= " href='#modal_massaction_content$identifier' title=\"".htmlentities($p['title'], ENT_QUOTES, 'UTF-8')."\">";
         $out .= $p['title']."</a>";

         if (!$p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
            // Clean selection
            $_SESSION['glpimassiveactionselected'] = [];
         }
      }

      if ($p['display']) {
         echo $out;
         return true;
      } else {
         return $out;
      }
   }


   /**
    * Display Date form with calendar
    *
    * @since 0.84
    *
    * @param string $name     name of the element
    * @param array  $options  array of possible options:
    *      - value        : default value to display (default '')
    *      - maybeempty   : may be empty ? (true by default)
    *      - canedit      :  could not modify element (true by default)
    *      - min          :  minimum allowed date (default '')
    *      - max          : maximum allowed date (default '')
    *      - showyear     : should we set/diplay the year? (true by default)
    *      - display      : boolean display of return string (default true)
    *      - calendar_btn : boolean display calendar icon (default true)
    *      - clear_btn    : boolean display clear icon (default true)
    *      - range        : boolean set the datepicket in range mode
    *      - rand         : specific rand value (default generated one)
    *      - yearrange    : set a year range to show in drop-down (default '')
    *      - required     : required field (will add required attribute)
    *      - placeholder  : text to display when input is empty
    *      - on_change    : function to execute when date selection changed
    *
    * @return integer|string
    *    integer if option display=true (random part of elements id)
    *    string if option display=false (HTML code)
   **/
   static function showDateField($name, $options = []) {
      global $CFG_GLPI;

      $p = [
         'value'        => '',
         'defaultDate'  => '',
         'maybeempty'   => true,
         'canedit'      => true,
         'min'          => '',
         'max'          => '',
         'showyear'     => false,
         'display'      => true,
         'range'        => false,
         'rand'         => mt_rand(),
         'calendar_btn' => true,
         'clear_btn'    => true,
         'yearrange'    => '',
         'multiple'     => false,
         'size'         => 10,
         'required'     => false,
         'placeholder'  => '',
         'on_change'    => '',
      ];

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      $required = $p['required'] == true
         ? " required='required'"
         : "";
      $disabled = !$p['canedit']
         ? " disabled='disabled'"
         : "";

      $calendar_btn = $p['calendar_btn']
         ? "<a class='input-button' data-toggle>
               <i class='far fa-calendar-alt fa-lg pointer'></i>
            </a>"
         : "";
      $clear_btn = $p['clear_btn'] && $p['maybeempty'] && $p['canedit']
         ? "<a data-clear  title='".__s('Clear')."'>
               <i class='fa fa-times-circle pointer'></i>
            </a>"
         : "";

      $mode = $p['range']
         ? "mode: 'range',"
         : "";

      $output = <<<HTML
      <div class="no-wrap flatpickr" id="showdate{$p['rand']}">
         <input type="text" name="{$name}" size="{$p['size']}"
                {$required} {$disabled} data-input placeholder="{$p['placeholder']}">
         $calendar_btn
         $clear_btn
      </div>
HTML;

      $date_format = Toolbox::getDateFormat('js');

      $min_attr = !empty($p['min'])
         ? "minDate: '{$p['min']}',"
         : "";
      $max_attr = !empty($p['max'])
         ? "maxDate: '{$p['max']}',"
         : "";
      $multiple_attr = $p['multiple']
         ? "mode: 'multiple',"
         : "";

      $value = is_array($p['value'])
         ? json_encode($p['value'])
         : "'{$p['value']}'";

      $locale = Locale::parseLocale($_SESSION['glpilanguage']);
      $js = <<<JS
      $(function() {
         $("#showdate{$p['rand']}").flatpickr({
            defaultDate: {$value},
            altInput: true, // Show the user a readable date (as per altFormat), but return something totally different to the server.
            altFormat: '{$date_format}',
            dateFormat: 'Y-m-d',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons
            weekNumbers: true,
            locale: getFlatPickerLocale("{$locale['language']}", "{$locale['region']}"),
            {$min_attr}
            {$max_attr}
            {$multiple_attr}
            {$mode}
            onChange: function(selectedDates, dateStr, instance) {
               {$p['on_change']}
            },
            allowInput: true,
            onClose(dates, currentdatestring, picker){
               picker.setDate(picker.altInput.value, true, picker.config.altFormat)
            }
         });
      });
JS;

      $output .= Html::scriptBlock($js);

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }


   /**
    * Display Color field
    *
    * @since 0.85
    *
    * @param string $name     name of the element
    * @param array  $options  array  of possible options:
    *   - value      : default value to display (default '')
    *   - display    : boolean display or get string (default true)
    *   - rand       : specific random value (default generated one)
    *
    * @return integer|string
    *    integer if option display=true (random part of elements id)
    *    string if option display=false (HTML code)
   **/
   static function showColorField($name, $options = []) {
      $p['value']      = '';
      $p['rand']       = mt_rand();
      $p['display']    = true;
      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }
      $field_id = Html::cleanId("color_".$name.$p['rand']);
      $output   = "<input type='color' id='$field_id' name='$name' value='".$p['value']."'>";

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }


   /**
    * Display DateTime form with calendar
    *
    * @since 0.84
    *
    * @param string $name     name of the element
    * @param array  $options  array  of possible options:
    *   - value      : default value to display (default '')
    *   - timestep   : step for time in minute (-1 use default config) (default -1)
    *   - maybeempty : may be empty ? (true by default)
    *   - canedit    : could not modify element (true by default)
    *   - mindate    : minimum allowed date (default '')
    *   - maxdate    : maximum allowed date (default '')
    *   - showyear   : should we set/diplay the year? (true by default)
    *   - display    : boolean display or get string (default true)
    *   - rand       : specific random value (default generated one)
    *   - required   : required field (will add required attribute)
    *   - on_change    : function to execute when date selection changed
    *
    * @return integer|string
    *    integer if option display=true (random part of elements id)
    *    string if option display=false (HTML code)
   **/
   static function showDateTimeField($name, $options = []) {
      global $CFG_GLPI;

      $p = [
         'value'      => '',
         'maybeempty' => true,
         'canedit'    => true,
         'mindate'    => '',
         'maxdate'    => '',
         'mintime'    => '',
         'maxtime'    => '',
         'timestep'   => -1,
         'showyear'   => true,
         'display'    => true,
         'rand'       => mt_rand(),
         'required'   => false,
         'on_change'  => '',
      ];

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      if ($p['timestep'] < 0) {
         $p['timestep'] = $CFG_GLPI['time_step'];
      }

      $date_value = '';
      $hour_value = '';
      if (!empty($p['value'])) {
         list($date_value, $hour_value) = explode(' ', $p['value']);
      }

      if (!empty($p['mintime'])) {
         // Check time in interval
         if (!empty($hour_value) && ($hour_value < $p['mintime'])) {
            $hour_value = $p['mintime'];
         }
      }

      if (!empty($p['maxtime'])) {
         // Check time in interval
         if (!empty($hour_value) && ($hour_value > $p['maxtime'])) {
            $hour_value = $p['maxtime'];
         }
      }

      // reconstruct value to be valid
      if (!empty($date_value)) {
         $p['value'] = $date_value.' '.$hour_value;
      }

      $required = $p['required'] == true
         ? " required='required'"
         : "";
      $disabled = !$p['canedit']
         ? " disabled='disabled'"
         : "";
      $clear    = $p['maybeempty'] && $p['canedit']
         ? "<a data-clear  title='".__s('Clear')."'>
               <i class='fa fa-times-circle pointer'></i>
            </a>"
         : "";

      $output = <<<HTML
         <div class="no-wrap flatpickr" id="showdate{$p['rand']}">
            <input type="text" name="{$name}" value="{$p['value']}"
                   {$required} {$disabled} data-input>
            <a class="input-button" data-toggle>
               <i class="far fa-calendar-alt fa-lg pointer"></i>
            </a>
            $clear
         </div>
HTML;

      $date_format = Toolbox::getDateFormat('js')." H:i:S";

      $min_attr = !empty($p['min'])
         ? "minDate: '{$p['min']}',"
         : "";
      $max_attr = !empty($p['max'])
         ? "maxDate: '{$p['max']}',"
         : "";

      $locale = Locale::parseLocale($_SESSION['glpilanguage']);
      $js = <<<JS
      $(function() {
         $("#showdate{$p['rand']}").flatpickr({
            altInput: true, // Show the user a readable date (as per altFormat), but return something totally different to the server.
            altFormat: "{$date_format}",
            dateFormat: 'Y-m-d H:i:S',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons)
            enableTime: true,
            enableSeconds: true,
            weekNumbers: true,
            locale: getFlatPickerLocale("{$locale['language']}", "{$locale['region']}"),
            minuteIncrement: "{$p['timestep']}",
            {$min_attr}
            {$max_attr}
            onChange: function(selectedDates, dateStr, instance) {
               {$p['on_change']}
            },
            allowInput: true,
            onClose(dates, currentdatestring, picker){
               picker.setDate(picker.altInput.value, true, picker.config.altFormat)
            }
         });
      });
JS;
      $output .= Html::scriptBlock($js);

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }

   /**
    * Display TimeField form
    *
    * @param string $name
    * @param array  $options
    *   - value      : default value to display (default '')
    *   - timestep   : step for time in minute (-1 use default config) (default -1)
    *   - maybeempty : may be empty ? (true by default)
    *   - canedit    : could not modify element (true by default)
    *   - mintime    : minimum allowed time (default '')
    *   - maxtime    : maximum allowed time (default '')
    *   - display    : boolean display or get string (default true)
    *   - rand       : specific random value (default generated one)
    *   - required   : required field (will add required attribute)
    *   - on_change  : function to execute when date selection changed
    * @return void
    */
   public static function showTimeField($name, $options = []) {
      global $CFG_GLPI;

      $p = [
         'value'      => '',
         'maybeempty' => true,
         'canedit'    => true,
         'mintime'    => '',
         'maxtime'    => '',
         'timestep'   => -1,
         'display'    => true,
         'rand'       => mt_rand(),
         'required'   => false,
         'on_change'  => '',
      ];

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      if ($p['timestep'] < 0) {
         $p['timestep'] = $CFG_GLPI['time_step'];
      }

      $hour_value = '';
      if (!empty($p['value'])) {
         $hour_value = $p['value'];
      }

      if (!empty($p['mintime'])) {
         // Check time in interval
         if (!empty($hour_value) && ($hour_value < $p['mintime'])) {
            $hour_value = $p['mintime'];
         }
      }
      if (!empty($p['maxtime'])) {
         // Check time in interval
         if (!empty($hour_value) && ($hour_value > $p['maxtime'])) {
            $hour_value = $p['maxtime'];
         }
      }
      // reconstruct value to be valid
      if (!empty($hour_value)) {
         $p['value'] = $hour_value;
      }

      $required = $p['required'] == true
         ? " required='required'"
         : "";
      $disabled = !$p['canedit']
         ? " disabled='disabled'"
         : "";
      $clear    = $p['maybeempty'] && $p['canedit']
         ? "<a data-clear  title='".__s('Clear')."'>
               <i class='fa fa-times-circle pointer'></i>
            </a>"
         : "";

      $output = <<<HTML
         <div class="no-wrap flatpickr" id="showtime{$p['rand']}">
            <input type="text" name="{$name}" value="{$p['value']}"
                   {$required} {$disabled} data-input>
            <a class="input-button" data-toggle>
               <i class="far fa-clock fa-lg pointer"></i>
            </a>
            $clear
         </div>
HTML;
      $locale = Locale::parseLocale($_SESSION['glpilanguage']);
      $js = <<<JS
      $(function() {
         $("#showtime{$p['rand']}").flatpickr({
            dateFormat: 'H:i:S',
            wrap: true, // permits to have controls in addition to input (like clear or open date buttons)
            enableTime: true,
            noCalendar: true, // only time picker
            enableSeconds: true,
            locale: getFlatPickerLocale("{$locale['language']}", "{$locale['region']}"),
            minuteIncrement: "{$p['timestep']}",
            onChange: function(selectedDates, dateStr, instance) {
               {$p['on_change']}
            }
         });
      });
JS;
      $output .= Html::scriptBlock($js);

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }

   /**
    * Show generic date search
    *
    * @param string $element  name of the html element
    * @param string $value    default value
    * @param $options   array of possible options:
    *      - with_time display with time selection ? (default false)
    *      - with_future display with future date selection ? (default false)
    *      - with_days display specific days selection TODAY, BEGINMONTH, LASTMONDAY... ? (default true)
    *
    * @return integer|string
    *    integer if option display=true (random part of elements id)
    *    string if option display=false (HTML code)
   **/
   static function showGenericDateTimeSearch($element, $value = '', $options = []) {
      global $CFG_GLPI;

      $p['with_time']          = false;
      $p['with_future']        = false;
      $p['with_days']          = true;
      $p['with_specific_date'] = true;
      $p['display']            = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $rand   = mt_rand();
      $output = '';
      // Validate value
      if (($value != 'NOW')
          && ($value != 'TODAY')
          && !preg_match("/\d{4}-\d{2}-\d{2}.*/", $value)
          && !strstr($value, 'HOUR')
          && !strstr($value, 'MINUTE')
          && !strstr($value, 'DAY')
          && !strstr($value, 'WEEK')
          && !strstr($value, 'MONTH')
          && !strstr($value, 'YEAR')) {

         $value = "";
      }

      if (empty($value)) {
         $value = 'NOW';
      }
      $specific_value = date("Y-m-d H:i:s");

      if (preg_match("/\d{4}-\d{2}-\d{2}.*/", $value)) {
         $specific_value = $value;
         $value          = 0;
      }
      $output    .= "<table width='100%'><tr><td width='50%'>";

      $dates      = Html::getGenericDateTimeSearchItems($p);

      $output    .= Dropdown::showFromArray("_select_$element", $dates,
                                                  ['value'   => $value,
                                                        'display' => false,
                                                        'rand'    => $rand]);
      $field_id   = Html::cleanId("dropdown__select_$element$rand");

      $output    .= "</td><td width='50%'>";
      $contentid  = Html::cleanId("displaygenericdate$element$rand");
      $output    .= "<span id='$contentid'></span>";

      $params     = ['value'         => '__VALUE__',
                          'name'          => $element,
                          'withtime'      => $p['with_time'],
                          'specificvalue' => $specific_value];

      $output    .= Ajax::updateItemOnSelectEvent($field_id, $contentid,
                                                  $CFG_GLPI["root_doc"]."/ajax/genericdate.php",
                                                  $params, false);
      $params['value']  = $value;
      $output    .= Ajax::updateItem($contentid, $CFG_GLPI["root_doc"]."/ajax/genericdate.php",
                                           $params, '', false);
      $output    .= "</td></tr></table>";

      if ($p['display']) {
         echo $output;
         return $rand;
      }
      return $output;
   }


   /**
    * Get items to display for showGenericDateTimeSearch
    *
    * @since 0.83
    *
    * @param array $options  array of possible options:
    *      - with_time display with time selection ? (default false)
    *      - with_future display with future date selection ? (default false)
    *      - with_days display specific days selection TODAY, BEGINMONTH, LASTMONDAY... ? (default true)
    *
    * @return array of posible values
    * @see self::showGenericDateTimeSearch()
   **/
   static function getGenericDateTimeSearchItems($options) {

      $params['with_time']          = false;
      $params['with_future']        = false;
      $params['with_days']          = true;
      $params['with_specific_date'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $dates = [];
      if ($params['with_time']) {
         $dates['NOW'] = __('Now');
         if ($params['with_days']) {
            $dates['TODAY'] = __('Today');
         }
      } else {
         $dates['NOW'] = __('Today');
      }

      if ($params['with_specific_date']) {
         $dates[0] = __('Specify a date');
      }

      if ($params['with_time']) {
         for ($i=1; $i<=24; $i++) {
            $dates['-'.$i.'HOUR'] = sprintf(_n('- %d hour', '- %d hours', $i), $i);
         }

         for ($i=1; $i<=15; $i++) {
            $dates['-'.$i.'MINUTE'] = sprintf(_n('- %d minute', '- %d minutes', $i), $i);
         }
      }

      for ($i=1; $i<=7; $i++) {
         $dates['-'.$i.'DAY'] = sprintf(_n('- %d day', '- %d days', $i), $i);
      }

      if ($params['with_days']) {
         $dates['LASTSUNDAY']    = __('last Sunday');
         $dates['LASTMONDAY']    = __('last Monday');
         $dates['LASTTUESDAY']   = __('last Tuesday');
         $dates['LASTWEDNESDAY'] = __('last Wednesday');
         $dates['LASTTHURSDAY']  = __('last Thursday');
         $dates['LASTFRIDAY']    = __('last Friday');
         $dates['LASTSATURDAY']  = __('last Saturday');
      }

      for ($i=1; $i<=10; $i++) {
         $dates['-'.$i.'WEEK'] = sprintf(_n('- %d week', '- %d weeks', $i), $i);
      }

      if ($params['with_days']) {
         $dates['BEGINMONTH']  = __('Beginning of the month');
      }

      for ($i=1; $i<=12; $i++) {
         $dates['-'.$i.'MONTH'] = sprintf(_n('- %d month', '- %d months', $i), $i);
      }

      if ($params['with_days']) {
         $dates['BEGINYEAR']  = __('Beginning of the year');
      }

      for ($i=1; $i<=10; $i++) {
         $dates['-'.$i.'YEAR'] = sprintf(_n('- %d year', '- %d years', $i), $i);
      }

      if ($params['with_future']) {
         if ($params['with_time']) {
            for ($i=1; $i<=24; $i++) {
               $dates[$i.'HOUR'] = sprintf(_n('+ %d hour', '+ %d hours', $i), $i);
            }
         }

         for ($i=1; $i<=7; $i++) {
            $dates[$i.'DAY'] = sprintf(_n('+ %d day', '+ %d days', $i), $i);
         }

         for ($i=1; $i<=10; $i++) {
            $dates[$i.'WEEK'] = sprintf(_n('+ %d week', '+ %d weeks', $i), $i);
         }

         for ($i=1; $i<=12; $i++) {
            $dates[$i.'MONTH'] = sprintf(_n('+ %d month', '+ %d months', $i), $i);
         }

         for ($i=1; $i<=10; $i++) {
            $dates[$i.'YEAR'] = sprintf(_n('+ %d year', '+ %d years', $i), $i);
         }
      }
      return $dates;

   }


    /**
    * Compute date / datetime value resulting of showGenericDateTimeSearch
    *
    * @since 0.83
    *
    * @param string         $val           date / datetime value passed
    * @param boolean        $force_day     force computation in days
    * @param integer|string $specifictime  set specific timestamp
    *
    * @return string  computed date / datetime value
    * @see self::showGenericDateTimeSearch()
   **/
   static function computeGenericDateTimeSearch($val, $force_day = false, $specifictime = '') {

      if (empty($specifictime)) {
         $specifictime = strtotime($_SESSION["glpi_currenttime"]);
      }

      $format_use = "Y-m-d H:i:s";
      if ($force_day) {
         $format_use = "Y-m-d";
      }

      // Parsing relative date
      switch ($val) {
         case 'NOW' :
            return date($format_use, $specifictime);

         case 'TODAY' :
            return date("Y-m-d", $specifictime);
      }

      // Search on begin of month / year
      if (strstr($val, 'BEGIN')) {
         $hour   = 0;
         $minute = 0;
         $second = 0;
         $month  = date("n", $specifictime);
         $day    = 1;
         $year   = date("Y", $specifictime);

         switch ($val) {
            case "BEGINYEAR":
               $month = 1;
               break;

            case "BEGINMONTH":
               break;
         }

         return date($format_use, mktime ($hour, $minute, $second, $month, $day, $year));
      }

      // Search on Last monday, sunday...
      if (strstr($val, 'LAST')) {
         $lastday = str_replace("LAST", "LAST ", $val);
         $hour   = 0;
         $minute = 0;
         $second = 0;
         $month  = date("n", strtotime($lastday));
         $day    = date("j", strtotime($lastday));
         $year   = date("Y", strtotime($lastday));

         return date($format_use, mktime ($hour, $minute, $second, $month, $day, $year));
      }

      // Search on +- x days, hours...
      if (preg_match("/^(-?)(\d+)(\w+)$/", $val, $matches)) {
         if (in_array($matches[3], ['YEAR', 'MONTH', 'WEEK', 'DAY', 'HOUR', 'MINUTE'])) {
            $nb = intval($matches[2]);
            if ($matches[1] == '-') {
               $nb = -$nb;
            }
            // Use it to have a clean delay computation (MONTH / YEAR have not always the same duration)
            $hour   = date("H", $specifictime);
            $minute = date("i", $specifictime);
            $second = 0;
            $month  = date("n", $specifictime);
            $day    = date("j", $specifictime);
            $year   = date("Y", $specifictime);

            switch ($matches[3]) {
               case "YEAR" :
                  $year += $nb;
                  break;

               case "MONTH" :
                  $month += $nb;
                  break;

               case "WEEK" :
                  $day += 7*$nb;
                  break;

               case "DAY" :
                  $day += $nb;
                  break;

               case "MINUTE" :
                  $format_use = "Y-m-d H:i:s";
                  $minute    += $nb;
                  break;

               case "HOUR" :
                  $format_use = "Y-m-d H:i:s";
                  $hour      += $nb;
                  break;
            }
            return date($format_use, mktime ($hour, $minute, $second, $month, $day, $year));
         }
      }
      return $val;
   }

   /**
    * Display or return a list of dates in a vertical way
    *
    * @since 9.2
    *
    * @param array $options  array of possible options:
    *      - title, do we need to append an H2 title tag
    *      - dates, an array containing a collection of theses keys:
    *         * timestamp
    *         * class, supported: passed, checked, now
    *         * label
    *      - display, boolean to precise if we need to display (true) or return (false) the html
    *      - add_now, boolean to precise if we need to add to dates array, an entry for now time
    *        (with now class)
    *
    * @return array of posible values
    *
    * @see self::showGenericDateTimeSearch()
   **/
   static function showDatesTimelineGraph($options = []) {
      $default_options = [
         'title'   => '',
         'dates'   => [],
         'display' => true,
         'add_now' => true
      ];
      $options = array_merge($default_options, $options);

      //append now date if needed
      if ($options['add_now']) {
         $now = time();
         $options['dates'][$now."_now"] = [
            'timestamp' => $now,
            'label' => __('Now'),
            'class' => 'now'
         ];
      }

      ksort($options['dates']);

      $out = "";
      $out.= "<div class='dates_timelines'>";

      // add title
      if (strlen($options['title'])) {
         $out.= "<h2 class='header'>".$options['title']."</h2>";
      }

      // construct timeline
      $out.= "<ul>";
      foreach ($options['dates'] as $key => $data) {
         if ($data['timestamp'] != 0) {
            $out.= "<li class='".$data['class']."'>&nbsp;";
            $out.= "<time>".Html::convDateTime(date("Y-m-d H:i:s", $data['timestamp']))."</time>";
            $out.= "<span class='dot'></span>";
            $out.= "<label>".$data['label']."</label>";
            $out.= "</li>";
         }
      }
      $out.= "</ul>";
      $out.= "</div>";

      if ($options['display']) {
         echo $out;
      } else {
         return $out;
      }
   }


   /**
    * Print the form used to select profile if several are available
    *
    * @param string $target  target of the form
    *
    * @return void
   **/
   static function showProfileSelecter($target) {
      global $CFG_GLPI;

      if (count($_SESSION["glpiprofiles"])>1) {
         echo '<li class="profile-selector"><form name="form" method="post" action="'.$target.'">';
         $values = [];
         foreach ($_SESSION["glpiprofiles"] as $key => $val) {
            $values[$key] = $val['name'];
         }

         Dropdown::showFromArray('newprofile', $values,
                                 ['value'     => $_SESSION["glpiactiveprofile"]["id"],
                                       'width'     => '150px',
                                       'on_change' => 'submit()']);
         Html::closeForm();
         echo '</li>';
      }

      if (Session::isMultiEntitiesMode()) {
         echo "<li class='profile-selector'>";
         Ajax::createModalWindow('modal_entity_window', $CFG_GLPI['root_doc']."/ajax/entitytree.php",
                                 ['title'       => __('Select the desired entity'),
                                       'extraparams' => ['target' => $target]]);
         echo "<a onclick='modal_entity_window.show();' href='#modal_entity_content' title=\"".
                addslashes($_SESSION["glpiactive_entity_name"]).
                "\" class='entity_select' id='global_entity_select'>".
                $_SESSION["glpiactive_entity_shortname"]."</a>";

         echo "</li>";
      }
   }


   /**
    * Show a tooltip on an item
    *
    * @param $content   string   data to put in the tooltip
    * @param $options   array    of possible options:
    *   - applyto : string / id of the item to apply tooltip (default empty).
    *                  If not set display an icon
    *   - title : string / title to display (default empty)
    *   - contentid : string / id for the content html container (default auto generated) (used for ajax)
    *   - link : string / link to put on displayed image if contentid is empty
    *   - linkid : string / html id to put to the link link (used for ajax)
    *   - linktarget : string / target for the link
    *   - popup : string / popup action : link not needed to use it
    *   - img : string / url of a specific img to use
    *   - display : boolean / display the item : false return the datas
    *   - autoclose : boolean / autoclose the item : default true (false permit to scroll)
    *
    * @return void|string
    *    void if option display=true
    *    string if option display=false (HTML code)
   **/
   static function showToolTip($content, $options = []) {
      $param['applyto']    = '';
      $param['title']      = '';
      $param['contentid']  = '';
      $param['link']       = '';
      $param['linkid']     = '';
      $param['linktarget'] = '';
      $param['awesome-class'] = 'fa-info';
      $param['popup']      = '';
      $param['ajax']       = '';
      $param['display']    = true;
      $param['autoclose']  = true;
      $param['onclick']    = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      // No empty content to have a clean display
      if (empty($content)) {
         $content = "&nbsp;";
      }
      $rand = mt_rand();
      $out  = '';

      // Force link for popup
      if (!empty($param['popup'])) {
         $param['link'] = '#';
      }

      if (empty($param['applyto'])) {
         if (!empty($param['link'])) {
            $out .= "<a id='".(!empty($param['linkid'])?$param['linkid']:"tooltiplink$rand")."'
                        class='dropdown_tooltip'";

            if (!empty($param['linktarget'])) {
               $out .= " target='".$param['linktarget']."' ";
            }
            $out .= " href='".$param['link']."'";

            if (!empty($param['popup'])) {
               $out .= " data-bs-toggle='modal' data-bs-target='#tooltippopup$rand' ";
            }
            $out .= '>';
         }
         if (isset($param['img'])) {
            //for compatibility. Use fontawesome instead.
            $out .= "<img id='tooltip$rand' src='".$param['img']."'>";
         } else {
            $out .= "<span id='tooltip$rand' class='fas {$param['awesome-class']} fa-fw'></span>";
         }

         if (!empty($param['link'])) {
            $out .= "</a>";
         }

         $param['applyto'] = "tooltip$rand";
      }

      if (empty($param['contentid'])) {
         $param['contentid'] = "content".$param['applyto'];
      }

      $out .= "<div id='".$param['contentid']."' class='tooltip-invisible'>$content</div>";
      if (!empty($param['popup'])) {
         $out .= Ajax::createIframeModalWindow('tooltippopup'.$rand,
                                               $param['popup'],
                                               ['display' => false,
                                                     'width'   => 600,
                                                     'height'  => 300]);
      }
      $js = "$(function(){";
      $js .= Html::jsGetElementbyID($param['applyto']).".qtip({
         position: { viewport: $(window) },
         content: {text: ".Html::jsGetElementbyID($param['contentid']);
      if (!$param['autoclose']) {
         $js .=", title: {text: ' ',button: true}";
      }
      $js .= "}, style: { classes: 'qtip-shadow qtip-bootstrap'}, hide: {
                  fixed: true,
                  delay: 200,
                  leave: false,
                  when: { event: 'unfocus' }
                }";
      if ($param['onclick']) {
         $js .= ",show: 'click', hide: false,";
      } else if (!$param['autoclose']) {
         $js .= ",show: {
                        solo: true, // ...and hide all other tooltips...
                },";
      }
      $js .= "});";
      $js .= "});";
      $out .= Html::scriptBlock($js);

      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }
   }


    /**
    * Show div with auto completion
    *
    * @param CommonDBTM $item    item object used for create dropdown
    * @param string     $field   field to search for autocompletion
    * @param array      $options array of possible options:
    *    - name    : string / name of the select (default is field parameter)
    *    - value   : integer / preselected value (default value of the item object)
    *    - size    : integer / size of the text field
    *    - entity  : integer / restrict to a defined entity (default entity of the object if define)
    *                set to -1 not to take into account
    *    - user    : integer / restrict to a defined user (default -1 : no restriction)
    *    - option  : string / options to add to text field
    *    - display : boolean / if false get string
    *    - type    : string / html5 field type (number, date, text, ...) defaults to 'text'
    *    - required: boolean / whether the field is required
    *    - rand    : integer / pre-exsting random value
    *    - attrs   : array of attributes to add (['name' => 'value']
    *
    * @return void|string
   **/
   static function autocompletionTextField(CommonDBTM $item, $field, $options = []) {
      global $CFG_GLPI;

      $params['name']   = $field;
      $params['value']  = '';

      if (array_key_exists($field, $item->fields)) {
         $params['value'] = $item->fields[$field];
      }
      $params['entity'] = -1;

      if (array_key_exists('entities_id', $item->fields)) {
         $params['entity'] = $item->fields['entities_id'];
      }
      $params['user']   = -1;
      $params['option'] = '';
      $params['type']   = 'text';
      $params['required']  = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $rand = (isset($params['rand']) ? $params['rand'] : mt_rand());
      $name    = "field_".$params['name'].$rand;

      // Check if field is allowed
      $field_so = $item->getSearchOptionByField('field', $field, $item->getTable());
      $can_autocomplete = array_key_exists('autocomplete', $field_so) && $field_so['autocomplete'];

      $output = '';
      if ($can_autocomplete && $CFG_GLPI["use_ajax_autocompletion"]) {
         $output .=  "<input ".$params['option']." id='text$name' type='{$params['type']}' name='".
                       $params['name']."' value=\"".self::cleanInputText($params['value'])."\"
                       class='autocompletion-text-field form-control form-control-sm'";

         if ($params['required'] == true) {
            $output .= " required='required'";
         }

         if (isset($params['attrs'])) {
            foreach ($params['attrs'] as $attr => $value) {
               $output .= " $attr='$value'";
            }
         }

         $output .= ">";

         $parameters['itemtype'] = $item->getType();
         $parameters['field']    = $field;

         if ($params['entity'] >= 0) {
            $parameters['entity_restrict']    = $params['entity'];
         }
         if ($params['user'] >= 0) {
            $parameters['user_restrict']    = $params['user'];
         }

         $js = "  $( '#text$name' ).autocomplete({
                        source: '".$CFG_GLPI["root_doc"]."/ajax/autocompletion.php?".Toolbox::append_params($parameters, '&')."',
                        minLength: 3,
                        });";

         $output .= Html::scriptBlock($js);

      } else {
         $output .=  "<input ".$params['option']." type='text' id='text$name' class='form-control form-control-sm' name='".$params['name']."'
                value=\"".self::cleanInputText($params['value'])."\">\n";
      }

      if (!isset($options['display']) || $options['display']) {
         echo $output;
      } else {
         return $output;
      }
   }


   /**
    * Init the Editor System to a textarea
    *
    * @param string  $name          name of the html textarea to use
    * @param string  $rand          rand of the html textarea to use (if empty no image paste system)(default '')
    * @param boolean $display       display or get js script (true by default)
    * @param boolean $readonly      editor will be readonly or not
    * @param boolean $enable_images enable image pasting in rich text
    *
    * @return void|string
    *    integer if param display=true
    *    string if param display=false (HTML code)
   **/
   static function initEditorSystem($name, $rand = '', $display = true, $readonly = false, $enable_images = true) {
      global $CFG_GLPI;

      // load tinymce lib
      Html::requireJs('tinymce');

      $language = $_SESSION['glpilanguage'];
      if (!file_exists(GLPI_ROOT."/public/lib/tinymce-i18n/langs/$language.js")) {
         $language = $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2];
         if (!file_exists(GLPI_ROOT."/public/lib/tinymce-i18n/langs/$language.js")) {
            $language = "en_GB";
         }
      }
      $language_url = $CFG_GLPI['root_doc'] . '/public/lib/tinymce-i18n/langs/' . $language . '.js';

      $skin_url = $CFG_GLPI['root_doc'] . '/public/lib/tinymce/skins/ui/oxide';
      if ($_SESSION['glpipalette'] === 'darker') {
         $skin_url = $CFG_GLPI['root_doc'] . '/public/lib/tinymce/skins/ui/oxide-dark';
      }
      // Apply all GLPI styles to editor content
      $content_css = preg_replace('/^.*href="([^"]+)".*$/', '$1', self::scss('css/palettes/' . $_SESSION['glpipalette'] ?? 'auror'))
         . ',' . preg_replace('/^.*href="([^"]+)".*$/', '$1', self::css('public/lib/base.css'));

      $cache_suffix = '?v='.GLPI_VERSION;
      $readonlyjs   = $readonly ? 'true' : 'false';

      $invalid_elements = 'applet,canvas,embed,form,object';
      if (!$enable_images) {
         $invalid_elements .= ',img';
      }

      $plugins = [
         'autoresize',
         'code',
         'colorpicker',
         'directionality',
         'fullscreen',
         'link',
         'lists',
         'paste',
         'searchreplace',
         'tabfocus',
         'table',
         'textcolor',
      ];
      if ($enable_images) {
         $plugins[] = 'image';
         $plugins[] = 'glpi_upload_doc';
      }
      $pluginsjs = json_encode($plugins);

      // init tinymce
      $js = <<<JS
         $(function() {
            // init editor
            tinyMCE.init({
               selector: '#{$name}',

               plugins: {$pluginsjs},

               // Appearance
               language: '{$language}',
               language_url: '{$language_url}',

               skin_url: '{$skin_url}',
               body_class: 'rich_text_container',
               content_css: '{$content_css}',

               min_height: '150px',
               resize: true,

               menubar: false,
               toolbar: 'styleselect | bold italic | forecolor backcolor | bullist numlist outdent indent | table link image | code fullscreen',

               // Content settings
               entity_encoding: 'raw',
               invalid_elements: '{$invalid_elements}',
               paste_data_images: true,
               readonly: {$readonlyjs},
               relative_urls: false,
               remove_script_host: false,

               // Misc options
               browser_spellcheck: true,
               cache_suffix: '{$cache_suffix}',

               setup: function(editor) {
                  // "required" state handling
                  if ($('#$name').attr('required') == 'required') {
                     $('#$name').removeAttr('required'); // Necessary to bypass browser validation

                     editor.on('submit', function (e) {
                        if ($('#$name').val() == '') {
                           alert(__('The description field is mandatory'));
                           evt.preventDefault();
                        }
                     });
                     editor.on('keyup', function (e) {
                        editor.save();
                        if ($('#$name').val() == '') {
                           $(editor.container).addClass('required');
                        } else {
                           $(editor.container).removeClass('required');
                        }
                     });
                     editor.on('init', function (e) {
                        if ($('#$name').val() == '') {
                           $(editor.container).addClass('required');
                        }
                     });
                     editor.on('paste', function (e) {
                        // Remove required on paste event
                        // This is only needed when pasting with right click (context menu)
                        // Pasting with Ctrl+V is already handled by keyup event above
                        $(editor.container).removeClass('required');
                     });
                  }

                  editor.on('SaveContent', function (contentEvent) {
                     contentEvent.content = contentEvent.content.replace(/\\r?\\n/g, '');
                  });
                  editor.on('Change', function (e) {
                     // Nothing fancy here. Since this is only used for tracking unsaved changes,
                     // we want to keep the logic in common.js with the other form input events.
                     onTinyMCEChange(e);
                  });
                  // ctrl + enter submit the parent form
                  editor.addShortcut('ctrl+13', 'submit', function() {
                     editor.save();
                     submitparentForm($('#$name'));
                  });
               }
            });
         });
JS;

      if ($display) {
         echo  Html::scriptBlock($js);
      } else {
         return  Html::scriptBlock($js);
      }
   }

   /**
    * Activate user mentions feature in rich text editor.
    *
    * @param string $editor_id
    *
    * @return void
    */
   public static function activateUserMentions(string $editor_id): void {
      global $CFG_GLPI;

      if (!$CFG_GLPI['use_notifications']) {
         return; // Do not enable this feature if notifications are not enabled
      }

      $current_entity = Session::getActiveEntity();
      $user_idor = Session::getNewIDORToken(
         User::class,
         [
            'right'           => 'all',
            'entity_restrict' => $current_entity,
         ]
      );

      echo Html::scriptBlock(<<<JAVASCRIPT
         $(
            function() {
               var user_mention = new GLPI.RichText.UserMention(
                  tinymce.get('{$editor_id}'),
                  {$current_entity},
                  '{$user_idor}'
               );
               user_mention.register();
            }
         );
JAVASCRIPT
      );
   }

   /**
    * Convert rich text content to simple text content
    *
    * @since 9.2
    *
    * @param $content : content to convert in html
    *
    * @return $content
   **/
   static function setSimpleTextContent($content) {

      $content = Html::entity_decode_deep($content);
      $content = Toolbox::convertImageToTag($content);

      // If is html content
      if ($content != strip_tags($content)) {
         $content = Toolbox::getHtmlToDisplay($content);
      }

      return $content;
   }

   /**
    * Convert simple text content to rich text content and init html editor
    *
    * @since 9.2
    *
    * @param string  $name     name of textarea
    * @param string  $content  content to convert in html
    * @param string  $rand     used for randomize tinymce dom id
    * @param boolean $readonly true will set editor in readonly mode
    *
    * @return $content
    *
    * @deprecated 10.0.0
   **/
   static function setRichTextContent($name, $content, $rand, $readonly = false) {

      Toolbox::deprecated();

      // Init html editor (if name of textarea is provided)
      if (!empty($name)) {
         Html::initEditorSystem($name, $rand, true, $readonly);
      }

      // Neutralize non valid HTML tags
      $content = Html::clean($content, false, 1);

      // If content does not contain <br> or <p> html tag, use nl2br
      if (!preg_match("/<br\s?\/?>/", $content) && !preg_match("/<p>/", $content)) {
         $content = nl2br($content);
      }
      return $content;
   }


   /**
    * Print Ajax pager for list in tab panel
    *
    * @param string  $title              displayed above
    * @param integer $start              from witch item we start
    * @param integer $numrows            total items
    * @param string  $additional_info    Additional information to display (default '')
    * @param boolean $display            display if true, return the pager if false
    * @param string  $additional_params  Additional parameters to pass to tab reload request (default '')
    *
    * @return void|string
   **/
   static function printAjaxPager($title, $start, $numrows, $additional_info = '', $display = true, $additional_params = '') {
      $list_limit = $_SESSION['glpilist_limit'];
      // Forward is the next step forward
      $forward = $start+$list_limit;

      // This is the end, my friend
      $end = $numrows-$list_limit;

      // Human readable count starts here
      $current_start = $start+1;

      // And the human is viewing from start to end
      $current_end = $current_start+$list_limit-1;
      if ($current_end > $numrows) {
         $current_end = $numrows;
      }
      // Empty case
      if ($current_end == 0) {
         $current_start = 0;
      }
      // Backward browsing
      if ($current_start-$list_limit <= 0) {
         $back = 0;
      } else {
         $back = $start-$list_limit;
      }

      if (!empty($additional_params) && strpos($additional_params, '&') !== 0) {
         $additional_params = '&' . $additional_params;
      }

      $out = '';
      // Print it
      $out .= "<div><table class='tab_cadre_pager'>";
      if (!empty($title)) {
         $out .= "<tr><th colspan='6'>$title</th></tr>";
      }
      $out .= "<tr>\n";

      // Back and fast backward button
      if (!$start == 0) {
         $out .= "<th class='left'><a href='javascript:reloadTab(\"start=0$additional_params\");'>
                     <i class='fa fa-step-backward' title=\"".__s('Start')."\"></i></a></th>";
         $out .= "<th class='left'><a href='javascript:reloadTab(\"start=$back$additional_params\");'>
                     <i class='fa fa-chevron-left' title=\"".__s('Previous')."\"></i></a></th>";
      }

      $out .= "<td width='50%' class='tab_bg_2'>";
      $out .= self::printPagerForm('', false, $additional_params);
      $out .= "</td>";
      if (!empty($additional_info)) {
         $out .= "<td class='tab_bg_2'>";
         $out .= $additional_info;
         $out .= "</td>";
      }
      // Print the "where am I?"
      $out .= "<td width='50%' class='tab_bg_2 b'>";
      //TRANS: %1$d, %2$d, %3$d are page numbers
      $out .= sprintf(__('From %1$d to %2$d of %3$d'), $current_start, $current_end, $numrows);
      $out .= "</td>\n";

      // Forward and fast forward button
      if ($forward < $numrows) {
         $out .= "<th class='right'><a href='javascript:reloadTab(\"start=$forward$additional_params\");'>
                     <i class='fa fa-chevron-right' title=\"".__s('Next')."\"></i></a></th>";
         $out .= "<th class='right'><a href='javascript:reloadTab(\"start=$end$additional_params\");'>
                     <i class='fa fa-step-forward' title=\"".__s('End')."\"></i></a></th>";
      }

      // End pager
      $out .= "</tr></table></div>";

      if ($display) {
         echo $out;
         return;
      }

      return $out;
   }


   /**
    * Clean Printing of and array in a table
    * ONLY FOR DEBUG
    *
    * @param array   $tab       the array to display
    * @param integer $pad       Pad used
    * @param boolean $jsexpand  Expand using JS ?
    *
    * @return void
   **/
   static function printCleanArray($tab, $pad = 0, $jsexpand = false) {

      if (count($tab)) {
         echo "<table class='array-debug'>";
         // For debug / no gettext
         echo "<tr><th>KEY</th><th>=></th><th>VALUE</th></tr>";

         foreach ($tab as $key => $val) {
            $key = Toolbox::clean_cross_side_scripting_deep($key);
            echo "<tr><td>";
            echo $key;
            $is_array = is_array($val);
            $rand     = mt_rand();
            echo "</td><td>";
            if ($jsexpand && $is_array) {
               echo "<a href=\"javascript:showHideDiv('content$key$rand','','','')\">";
               echo "=></a>";
            } else {
               echo "=>";
            }
            echo "</td><td>";

            if ($is_array) {
               echo "<div id='content$key$rand' ".($jsexpand?"style=\"display:none;\"":'').">";
               self::printCleanArray($val, $pad+1);
               echo "</div>";
            } else {
               if (is_bool($val)) {
                  if ($val) {
                     echo 'true';
                  } else {
                     echo 'false';
                  }
               } else {
                  if (is_object($val)) {
                     if (method_exists($val, '__toString')) {
                        echo (string) $val;
                     } else {
                        echo "(object) " . get_class($val);
                     }
                  } else {
                     echo htmlentities($val);
                  }
               }
            }
            echo "</td></tr>";
         }
         echo "</table>";
      } else {
         echo __('Empty array');
      }
   }



   /**
    * Print pager for search option (first/previous/next/last)
    *
    * @param integer        $start                   from witch item we start
    * @param integer        $numrows                 total items
    * @param string         $target                  page would be open when click on the option (last,previous etc)
    * @param string         $parameters              parameters would be passed on the URL.
    * @param integer|string $item_type_output        item type display - if >0 display export PDF et Sylk form
    * @param integer|string $item_type_output_param  item type parameter for export
    * @param string         $additional_info         Additional information to display (default '')
    *
    * @return void
    *
   **/
   static function printPager($start, $numrows, $target, $parameters, $item_type_output = 0,
                              $item_type_output_param = 0, $additional_info = '') {
      global $CFG_GLPI;

      $list_limit = $_SESSION['glpilist_limit'];
      // Forward is the next step forward
      $forward = $start+$list_limit;

      // This is the end, my friend
      $end = $numrows-$list_limit;

      // Human readable count starts here

      $current_start = $start+1;

      // And the human is viewing from start to end
      $current_end = $current_start+$list_limit-1;
      if ($current_end > $numrows) {
         $current_end = $numrows;
      }

      // Empty case
      if ($current_end == 0) {
         $current_start = 0;
      }

      // Backward browsing
      if ($current_start-$list_limit <= 0) {
         $back = 0;
      } else {
         $back = $start-$list_limit;
      }

      // Print it
      echo "<div><table class='tab_cadre_pager'>";
      echo "<tr>";

      if (strpos($target, '?') == false) {
         $fulltarget = $target."?".$parameters;
      } else {
         $fulltarget = $target."&".$parameters;
      }
      // Back and fast backward button
      if (!$start == 0) {
         echo "<th class='left'>";
         echo "<a href='$fulltarget&amp;start=0'>";
         echo "
               <i class='fa fa-step-backward' title=\"".__s('Start')."\"></i>";
         echo "</a></th>";
         echo "<th class='left'>";
         echo "<a href='$fulltarget&amp;start=$back'>";
         echo "<i class='fa fa-chevron-left' title=\"".__s('Previous')."\"></i>";
         echo "</a></th>";
      }

      // Print the "where am I?"
      echo "<td width='31%' class='tab_bg_2'>";
      self::printPagerForm("$fulltarget&amp;start=$start");
      echo "</td>";

      if (!empty($additional_info)) {
         echo "<td class='tab_bg_2'>";
         echo $additional_info;
         echo "</td>";
      }

      if (!empty($item_type_output)
          && isset($_SESSION["glpiactiveprofile"])
          && (Session::getCurrentInterface() == "central")) {

         echo "<td class='tab_bg_2 responsive_hidden' width='30%'>";
         echo "<form method='GET' action='".$CFG_GLPI["root_doc"]."/front/report.dynamic.php'>";
         echo Html::hidden('item_type', ['value' => $item_type_output]);

         if ($item_type_output_param != 0) {
            echo Html::hidden('item_type_param',
                              ['value' => Toolbox::prepareArrayForInput($item_type_output_param)]);
         }

         $parameters = trim($parameters, '&amp;');
         if (strstr($parameters, 'start') === false) {
            $parameters .= "&amp;start=$start";
         }

         $split = explode("&amp;", $parameters);

         $count_split = count($split);
         for ($i=0; $i < $count_split; $i++) {
            $pos    = Toolbox::strpos($split[$i], '=');
            $length = Toolbox::strlen($split[$i]);
            echo Html::hidden(Toolbox::substr($split[$i], 0, $pos), ['value' => urldecode(Toolbox::substr($split[$i], $pos+1))]);
         }

         Dropdown::showOutputFormat($item_type_output);
         Html::closeForm();
         echo "</td>";
      }

      echo "<td width='20%' class='tab_bg_2 b'>";
      //TRANS: %1$d, %2$d, %3$d are page numbers
      printf(__('From %1$d to %2$d of %3$d'), $current_start, $current_end, $numrows);
      echo "</td>\n";

      // Forward and fast forward button
      if ($forward<$numrows) {
         echo "<th class='right'>";
         echo "<a href='$fulltarget&amp;start=$forward'>
               <i class='fa fa-chevron-right' title=\"".__s('Next')."\">";
         echo "</a></th>\n";

         echo "<th class='right'>";
         echo "<a href='$fulltarget&amp;start=$end'>";
         echo "<i class='fa fa-step-forward' title=\"".__s('End')."\"></i>";
         echo "</a></th>\n";
      }
      // End pager
      echo "</tr></table></div>";
   }


   /**
    * Display the list_limit combo choice
    *
    * @param string  $action             page would be posted when change the value (URL + param) (default '')
    * @param boolean $display            display the pager form if true, return it if false
    * @param string  $additional_params  Additional parameters to pass to tab reload request (default '')
    *
    * ajax Pager will be displayed if empty
    *
    * @return void|string
   **/
   static function printPagerForm($action = "", $display = true, $additional_params = '') {

      if (!empty($additional_params) && strpos($additional_params, '&') !== 0) {
         $additional_params = '&' . $additional_params;
      }

      $out = '';
      if ($action) {
         $out .= "<form method='POST' action=\"$action\">";
         $out .= "<span class='responsive_hidden'>".__('Display (number of items)')."</span>&nbsp;";
         $out .= Dropdown::showListLimit("submit()", false);

      } else {
         $out .= "<form method='POST' action =''>\n";
         $out .= "<span class='responsive_hidden'>".__('Display (number of items)')."</span>&nbsp;";
         $out .= Dropdown::showListLimit("reloadTab(\"glpilist_limit=\"+this.value+\"$additional_params\")", false);
      }
      $out .= Html::closeForm(false);

      if ($display) {
         echo $out;
         return;
      }
      return $out;
   }


   /**
    * Create a title for list, as  "List (5 on 35)"
    *
    * @param $string String  text for title
    * @param $num    Integer number of item displayed
    * @param $tot    Integer number of item existing
    *
    * @since 0.83.1
    *
    * @return String
    **/
   static function makeTitle ($string, $num, $tot) {

      if (($num > 0) && ($num < $tot)) {
         // TRANS %1$d %2$d are numbers (displayed, total)
         $cpt = "<span class='primary-bg primary-fg count'>" .
            sprintf(__('%1$d on %2$d'), $num, $tot) . "</span>";
      } else {
         // $num is 0, so means configured to display nothing
         // or $num == $tot
         $cpt = "<span class='primary-bg primary-fg count'>$tot</span>";
      }
      return sprintf(__('%1$s %2$s'), $string, $cpt);
   }


   /**
    * create a minimal form for simple action
    *
    * @param $action   String   URL to call on submit
    * @param $btname   String   button name (maybe if name <> value)
    * @param $btlabel  String   button label
    * @param $fields   Array    field name => field  value
    * @param $btimage  String   button image uri (optional)   (default '')
    *                           If image name starts with "fa-", il will be turned into
    *                           a font awesome element rather than an image.
    * @param $btoption String   optional button option        (default '')
    * @param $confirm  String   optional confirm message      (default '')
    *
    * @since 0.84
   **/
   static function getSimpleForm($action, $btname, $btlabel, Array $fields = [], $btimage = '',
                                 $btoption = '', $confirm = '') {

      if (GLPI_USE_CSRF_CHECK) {
         $fields['_glpi_csrf_token'] = Session::getNewCSRFToken();
      }
      $fields['_glpi_simple_form'] = 1;
      $button                      = $btname;
      if (!is_array($btname)) {
         $button          = [];
         $button[$btname] = $btname;
      }
      $fields          = array_merge($button, $fields);
      $javascriptArray = [];
      foreach ($fields as $name => $value) {
         /// TODO : trouble :  urlencode not available for array / do not pass array fields...
         if (!is_array($value)) {
            // Javascript no gettext
            $javascriptArray[] = "'$name': '".urlencode($value)."'";
         }
      }

      $link = "<a ";

      if (!empty($btoption)) {
         $link .= ' '.$btoption.' ';
      }
      // Do not force class if already defined
      if (!strstr($btoption, 'class=')) {
         if (empty($btimage)) {
            $link .= " class='vsubmit' ";
         } else {
            $link .= " class='pointer' ";
         }
      }
      $action  = " submitGetLink('$action', {" .implode(', ', $javascriptArray) ."});";

      if (is_array($confirm) || strlen($confirm)) {
         $link .= self::addConfirmationOnAction($confirm, $action);
      } else {
         $link .= " onclick=\"$action\" ";
      }

      $link .= '>';
      if (empty($btimage)) {
         $link .= $btlabel;
      } else {
         if (substr($btimage, 0, strlen('fa-')) === 'fa-') {
            $link .= "<span class='fa $btimage' title='$btlabel'><span class='sr-only'>$btlabel</span>";
         } else {
            $link .= "<img src='$btimage' title='$btlabel' alt='$btlabel' class='pointer'>";
         }
      }
      $link .="</a>";

      return $link;

   }


   /**
    * create a minimal form for simple action
    *
    * @param $action   String   URL to call on submit
    * @param $btname   String   button name
    * @param $btlabel  String   button label
    * @param $fields   Array    field name => field  value
    * @param $btimage  String   button image uri (optional) (default '')
    * @param $btoption String   optional button option (default '')
    * @param $confirm  String   optional confirm message (default '')
    *
    * @since 0.83.3
   **/
   static function showSimpleForm($action, $btname, $btlabel, Array $fields = [], $btimage = '',
                                  $btoption = '', $confirm = '') {

      echo self::getSimpleForm($action, $btname, $btlabel, $fields, $btimage, $btoption, $confirm);
   }


   /**
    * Create a close form part including CSRF token
    *
    * @param $display boolean Display or return string (default true)
    *
    * @since 0.83.
    *
    * @return String
   **/
   static function closeForm ($display = true) {
      global $CFG_GLPI;

      $out = "\n";
      if (GLPI_USE_CSRF_CHECK) {
         $out .= Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()])."\n";
      }

      if (isset($CFG_GLPI['checkbox-zero-on-empty']) && $CFG_GLPI['checkbox-zero-on-empty']) {
         $js = "   $('form').submit(function() {
         $('input[type=\"checkbox\"][data-glpicore-cb-zero-on-empty=\"1\"]:not(:checked)').each(function(index){
            // If the checkbox is not validated, we add a hidden field with '0' as value
            if ($(this).attr('name')) {
               $('<input>').attr({
                  type: 'hidden',
                  name: $(this).attr('name'),
                  value: '0'
               }).insertAfter($(this));
            }
         });
      });";
         $out .= Html::scriptBlock($js)."\n";
         unset($CFG_GLPI['checkbox-zero-on-empty']);
      }

      $out .= "</form>\n";
      if ($display) {
         echo $out;
         return true;
      }
      return $out;
   }


   /**
    * Get javascript code for hide an item
    *
    * @param $id string id of the dom element
    *
    * @since 0.85.
    *
    * @return String
   **/
   static function jsHide($id) {
      return self::jsGetElementbyID($id).".hide();\n";
   }


   /**
    * Get javascript code for hide an item
    *
    * @param $id string id of the dom element
    *
    * @since 0.85.
    *
    * @return String
   **/
   static function jsShow($id) {
      return self::jsGetElementbyID($id).".show();\n";
   }


   /**
    * Clean ID used for HTML elements
    *
    * @param $id string id of the dom element
    *
    * @since 0.85.
    *
    * @return String
   **/
   static function cleanId($id) {
      return str_replace(['[',']'], '_', $id);
   }


   /**
    * Get javascript code to get item by id
    *
    * @param $id string id of the dom element
    *
    * @since 0.85.
    *
    * @return String
   **/
   static function jsGetElementbyID($id) {
      return "$('#$id')";
   }


   /**
    * Set dropdown value
    *
    * @param $id      string   id of the dom element
    * @param $value   string   value to set
    *
    * @since 0.85.
    *
    * @return string
   **/
   static function jsSetDropdownValue($id, $value) {
      return self::jsGetElementbyID($id).".trigger('setValue', '$value');";
   }

   /**
    * Get item value
    *
    * @param $id      string   id of the dom element
    *
    * @since 0.85.
    *
    * @return string
   **/
   static function jsGetDropdownValue($id) {
      return self::jsGetElementbyID($id).".val()";
   }


   /**
    * Adapt dropdown to clean JS
    *
    * @param $id       string   id of the dom element
    * @param $params   array    of parameters
    *
    * @since 0.85.
    *
    * @return String
   **/
   static function jsAdaptDropdown($id, $params = []) {
      global $CFG_GLPI;

      $width = '';
      if (isset($params["width"]) && !empty($params["width"])) {
         $width = $params["width"];
         unset($params["width"]);
      }

      $placeholder = '';
      if (isset($params["placeholder"])) {
         $placeholder = "placeholder: ".json_encode($params["placeholder"]).",";
      }

      $templateresult    = $params["templateResult"] ?? "templateResult";
      $templateselection = $params["templateSelection"] ?? "templateSelection";

      $js = "$(function() {
         $('#$id').select2({
            $placeholder
            width: '$width',
            dropdownAutoWidth: true,
            quietMillis: 100,
            minimumResultsForSearch: ".$CFG_GLPI['ajax_limit_count'].",
            matcher: function(params, data) {
               // store last search in the global var
               query = params;

               // If there are no search terms, return all of the data
               if ($.trim(params.term) === '') {
                  return data;
               }

               var searched_term = getTextWithoutDiacriticalMarks(params.term);
               var data_text = typeof(data.text) === 'string'
                  ? getTextWithoutDiacriticalMarks(data.text)
                  : '';
               var select2_fuzzy_opts = {
                  pre: '<span class=\"select2-rendered__match\">',
                  post: '</span>',
               };

               if (data_text.indexOf('>') !== -1 || data_text.indexOf('<') !== -1) {
                  // escape text, if it contains chevrons (can already be escaped prior to this point :/)
                  data_text = jQuery.fn.select2.defaults.defaults.escapeMarkup(data_text);
               }

               // Skip if there is no 'children' property
               if (typeof data.children === 'undefined') {
                  var match  = fuzzy.match(searched_term, data_text, select2_fuzzy_opts);
                  if (match == null) {
                     return false;
                  }
                  data.rendered_text = match.rendered_text;
                  data.score = match.score;
                  return data;
               }

               // `data.children` contains the actual options that we are matching against
               // also check in `data.text` (optgroup title)
               var filteredChildren = [];

               $.each(data.children, function (idx, child) {
                  var child_text = typeof(child.text) === 'string'
                     ? getTextWithoutDiacriticalMarks(child.text)
                     : '';

                  if (child_text.indexOf('>') !== -1 || child_text.indexOf('<') !== -1) {
                     // escape text, if it contains chevrons (can already be escaped prior to this point :/)
                     child_text = jQuery.fn.select2.defaults.defaults.escapeMarkup(child_text);
                  }

                  var match_child = fuzzy.match(searched_term, child_text, select2_fuzzy_opts);
                  var match_text  = fuzzy.match(searched_term, data_text, select2_fuzzy_opts);
                  if (match_child !== null || match_text !== null) {
                     if (match_text !== null) {
                        data.score         = match_text.score;
                        data.rendered_text = match_text.rendered;
                     }

                     if (match_child !== null) {
                        child.score         = match_child.score;
                        child.rendered_text = match_child.rendered;
                     }
                     filteredChildren.push(child);
                  }
               });

               // If we matched any of the group's children, then set the matched children on the group
               // and return the group object
               if (filteredChildren.length) {
                  var modifiedData = $.extend({}, data, true);
                  modifiedData.children = filteredChildren;

                  // You can return modified objects from here
                  // This includes matching the `children` how you want in nested data sets
                  return modifiedData;
               }

               // Return `null` if the term should not be displayed
               return null;
            },
            templateResult: $templateresult,
            templateSelection: $templateselection,
         })
         .bind('setValue', function(e, value) {
            $('#$id').val(value).trigger('change');
         })
         $('label[for=$id]').on('click', function(){ $('#$id').select2('open'); });
      });";
      return Html::scriptBlock($js);
   }


   /**
    * Create Ajax dropdown to clean JS
    *
    * @param $name
    * @param $field_id   string   id of the dom element
    * @param $url        string   URL to get datas
    * @param $params     array    of parameters
    *            must contains :
    *                if single select
    *                   - 'value'       : default value selected
    *                   - 'valuename'   : default name of selected value
    *                if multiple select
    *                   - 'values'      : default values selected
    *                   - 'valuesnames' : default names of selected values
    *
    * @since 0.85.
    *
    * @return String
   **/
   static function jsAjaxDropdown($name, $field_id, $url, $params = []) {
      global $CFG_GLPI;

      if (!array_key_exists('value', $params)) {
         $value = 0;
         $valuename = Dropdown::EMPTY_VALUE;
      } else {
         $value = $params['value'];
         $valuename = $params['valuename'];
      }
      $on_change = '';
      if (isset($params["on_change"])) {
         $on_change = $params["on_change"];
         unset($params["on_change"]);
      }
      $width = '80%';
      if (isset($params["width"])) {
         $width = $params["width"];
         unset($params["width"]);
      }

      $placeholder = $params['placeholder'] ?? '';
      $allowclear =  "false";
      if (strlen($placeholder) > 0 && !$params['display_emptychoice']) {
         $allowclear = "true";
      }

      unset($params['placeholder']);
      unset($params['value']);
      unset($params['valuename']);

      $options = [
         'id'        => $field_id,
         'selected'  => $value
      ];
      if (!empty($params['specific_tags'])) {
         foreach ($params['specific_tags'] as $tag => $val) {
            if (is_array($val)) {
               $val = implode(' ', $val);
            }
            $options[$tag] = $val;
         }
      }

      // manage multiple select (with multiple values)
      if (isset($params['values']) && count($params['values'])) {
         $values = array_combine($params['values'], $params['valuesnames']);
         $options['multiple'] = 'multiple';
         $options['selected'] = $params['values'];
      } else {
         $values = [];

         // simple select (multiple = no)
         if ($value !== null) {
            $values = ["$value" => $valuename];
         }
      }

      $output = '';

      $js = "
         var params_$field_id = {";
      foreach ($params as $key => $val) {
         // Specific boolean case
         if (is_bool($val)) {
            $js .= "$key: ".($val?1:0).",\n";
         } else {
            $js .= "$key: ".json_encode($val).",\n";
         }
      }
      $js.= "};

         $('#$field_id').select2({
            width: '$width',
            placeholder: '$placeholder',
            allowClear: $allowclear,
            minimumInputLength: 0,
            quietMillis: 100,
            dropdownAutoWidth: true,
            minimumResultsForSearch: ".$CFG_GLPI['ajax_limit_count'].",
            ajax: {
               url: '$url',
               dataType: 'json',
               type: 'POST',
               data: function (params) {
                  query = params;
                  return $.extend({}, params_$field_id, {
                     searchText: params.term,
                     page_limit: ".$CFG_GLPI['dropdown_max'].", // page size
                     page: params.page || 1, // page number
                  });
               },
               processResults: function (data, params) {
                  params.page = params.page || 1;
                  var more = (data.count >= ".$CFG_GLPI['dropdown_max'].");

                  return {
                     results: data.results,
                     pagination: {
                           more: more
                     }
                  };
               }
            },
            templateResult: templateResult,
            templateSelection: templateSelection
         })
         .bind('setValue', function(e, value) {
            $.ajax('$url', {
               data: $.extend({}, params_$field_id, {
                  _one_id: value,
               }),
               dataType: 'json',
               type: 'POST',
            }).done(function(data) {

               var iterate_options = function(options, value) {
                  var to_return = false;
                  $.each(options, function(index, option) {
                     if (option.hasOwnProperty('id')
                         && option.id == value) {
                        to_return = option;
                        return false; // act as break;
                     }

                     if (option.hasOwnProperty('children')) {
                        to_return = iterate_options(option.children, value);
                     }
                  });

                  return to_return;
               };

               var option = iterate_options(data.results, value);
               if (option !== false) {
                  var newOption = new Option(option.text, option.id, true, true);
                   $('#$field_id').append(newOption).trigger('change');
               }
            });
         });
         ";
      if (!empty($on_change)) {
         $js .= " $('#$field_id').on('change', function(e) {".
                  stripslashes($on_change)."});";
      }

      $js .= " $('label[for=$field_id]').on('click', function(){ $('#$field_id').select2('open'); });";

      $output .= Html::scriptBlock('$(function() {' . $js . '});');

      // display select tag
      $options['class'] = $params['class'] ?? 'form-select form-select-sm';
      $output .= self::select($name, $values, $options);

      return $output;
   }


   /**
    * Creates a formatted IMG element.
    *
    * This method will set an empty alt attribute if no alt and no title is not supplied
    *
    * @since 0.85
    *
    * @param string $path     Path to the image file
    * @param array  $options  array of HTML attributes
    *        - `url` If provided an image link will be generated and the link will point at
    *               `$options['url']`.
    * @return string completed img tag
   **/
   static function image($path, $options = []) {

      if (!isset($options['title'])) {
         $options['title'] = '';
      }

      if (!isset($options['alt'])) {
         $options['alt'] = $options['title'];
      }

      if (empty($options['title'])
          && !empty($options['alt'])) {
         $options['title'] = $options['alt'];
      }

      $url = false;
      if (!empty($options['url'])) {
         $url = $options['url'];
         unset($options['url']);
      }

      $class = "";
      if ($url) {
         $class = "class='pointer'";
      }

      $image = sprintf('<img src="%1$s" %2$s %3$s />', $path, Html::parseAttributes($options), $class);
      if ($url) {
         return Html::link($image, $url);
      }
      return $image;
   }


   /**
    * Creates a PhotoSwipe image gallery
    *
    *
    * @since 9.5.0
    *
    * @param array $imgs  Array of image info
    *                      - src The public path of img
    *                      - w   The width of img
    *                      - h   The height of img
    * @param array $options
    * @return string completed gallery
   **/
   static function imageGallery($imgs, $options = []) {
      $p = [
         'controls' => [
            'close'        => true,
            'share'        => true,
            'fullscreen'   => true,
            'zoom'         => true,
         ],
         'rand'               => mt_rand(),
         'gallery_item_class' => ''
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $out = "<div id='psgallery{$p['rand']}' class='pswp' tabindex='-1'
         role='dialog' aria-hidden='true'>";
      $out .= "<div class='pswp__bg'></div>";
      $out .= "<div class='pswp__scroll-wrap'>";
      $out .= "<div class='pswp__container'>";
      $out .= "<div class='pswp__item'></div>";
      $out .= "<div class='pswp__item'></div>";
      $out .= "<div class='pswp__item'></div>";
      $out .= "</div>";
      $out .= "<div class='pswp__ui pswp__ui--hidden'>";
      $out .= "<div class='pswp__top-bar'>";
      $out .= "<div class='pswp__counter'></div>";

      if (isset($p['controls']['close']) && $p['controls']['close']) {
         $out .= "<button class='pswp__button pswp__button--close' title='".__('Close (Esc)')."'></button>";
      }

      if (isset($p['controls']['share']) && $p['controls']['share']) {
         $out .= "<button class='pswp__button pswp__button--share' title='".__('Share')."'></button>";
      }

      if (isset($p['controls']['fullscreen']) && $p['controls']['fullscreen']) {
         $out .= "<button class='pswp__button pswp__button--fs' title='".__('Toggle fullscreen')."'></button>";
      }

      if (isset($p['controls']['zoom']) && $p['controls']['zoom']) {
         $out .= "<button class='pswp__button pswp__button--zoom' title='".__('Zoom in/out')."'></button>";
      }

      $out .= "<div class='pswp__preloader'>";
      $out .= "<div class='pswp__preloader__icn'>";
      $out .= "<div class='pswp__preloader__cut'>";
      $out .= "<div class='pswp__preloader__donut'></div>";
      $out .= "</div></div></div></div>";
      $out .= "<div class='pswp__share-modal pswp__share-modal--hidden pswp__single-tap'>";
      $out .= "<div class='pswp__share-tooltip'></div>";
      $out .= "</div>";
      $out .= "<button class='pswp__button pswp__button--arrow--left' title='".__('Previous (arrow left)')."'>";
      $out .= "</button>";
      $out .= "<button class='pswp__button pswp__button--arrow--right' title='".__('Next (arrow right)')."'>";
      $out .= "</button>";
      $out .= "<div class='pswp__caption'>";
      $out .= "<div class='pswp__caption__center'></div>";
      $out .= "</div></div></div></div>";

      $out .= "<div class='pswp-img{$p['rand']} {$p['gallery_item_class']}' itemscope itemtype='http://schema.org/ImageGallery'>";
      foreach ($imgs as $img) {
         if (!isset($img['thumbnail_src'])) {
            $img['thumbnail_src'] = $img['src'];
         }
         $out .= "<figure itemprop='associatedMedia' itemscope itemtype='http://schema.org/ImageObject'>";
         $out .= "<a href='{$img['src']}' itemprop='contentUrl' data-index='0'>";
         $out .= "<img src='{$img['thumbnail_src']}' itemprop='thumbnail'>";
         $out .= "</a>";
         $out .= "</figure>";
      }
      $out .= "</div>";

      // Decode images urls
      $imgs = array_map(function($img) {
         $img['src'] = html_entity_decode($img['src']);
         return $img;
      }, $imgs);

      $items_json = json_encode($imgs);
      $dltext = __('Download');
      $js = <<<JAVASCRIPT
      (function($) {
         var pswp = document.getElementById('psgallery{$p['rand']}');

         $('.pswp-img{$p['rand']}').on('click', 'figure', function(event) {
            event.preventDefault();

            var options = {
                index: $(this).index(),
                bgOpacity: 0.7,
                showHideOpacity: true,
                shareButtons: [
                  {id:'download', label:'{$dltext}', url:'{{raw_image_url}}', download:true}
                ]
            }

            var lightBox = new PhotoSwipe(pswp, PhotoSwipeUI_Default, {$items_json}, options);
            // TODO .glpi_tabs not exists anymore
            $(pswp).closest('.glpi_tabs').css('z-index', 50); // be sure that tabs are displayed above form in vsplit
            lightBox.init();

            lightBox.listen(
               'destroy',
               function() {
                  // TODO .glpi_tabs not exists anymore
                  $(this.container).closest('.glpi_tabs').css('z-index', ''); // restore z-index from CSS
               }
            );
        });
      })(jQuery);

JAVASCRIPT;

      $out .= Html::scriptBlock($js);

      return $out;
   }

   /**
    * Replace images by gallery component in rich text.
    *
    * @since 9.5.0
    *
    * @param string  $richtext
    *
    * @return string
    */
   static function replaceImagesByGallery($richtext) {

      $image_matches = [];
      preg_match_all(
         '/<a[^>]*>\s*<img[^>]*src=["\']([^"\']*document\.send\.php\?docid=([0-9]+)(?:&[^"\']+)?)["\'][^>]*>\s*<\/a>/',
         $richtext,
         $image_matches,
         PREG_SET_ORDER
      );
      foreach ($image_matches as $image_match) {
         $img_tag = $image_match[0];
         $docsrc  = $image_match[1];
         $docid   = $image_match[2];
         $document = new Document();
         if ($document->getFromDB($docid)) {
            $docpath = GLPI_DOC_DIR . '/' . $document->fields['filepath'];
            if (Document::isImage($docpath)) {
               $imgsize = getimagesize($docpath);
               $gallery = Html::imageGallery([
                  [
                     'src' => $docsrc,
                     'w'   => $imgsize[0],
                     'h'   => $imgsize[1]
                  ]
               ]);
               $richtext = str_replace($img_tag, $gallery, $richtext);
            }
         }
      }

      return $richtext;
   }


   /**
    * Creates an HTML link.
    *
    * @since 0.85
    *
    * @param string $text     The content to be wrapped by a tags.
    * @param string $url      URL parameter
    * @param array  $options  Array of HTML attributes:
    *     - `confirm` JavaScript confirmation message.
    *     - `confirmaction` optional action to do on confirmation
    * @return string an `a` element.
   **/
   static function link($text, $url, $options = []) {

      if (isset($options['confirm'])) {
         if (!empty($options['confirm'])) {
            $confirmAction  = '';
            if (isset($options['confirmaction'])) {
               if (!empty($options['confirmaction'])) {
                  $confirmAction = $options['confirmaction'];
               }
               unset($options['confirmaction']);
            }
            $options['onclick'] = Html::getConfirmationOnActionScript($options['confirm'],
                                                                      $confirmAction);
         }
         unset($options['confirm']);
      }
      // Do not escape title if it is an image or a i tag (fontawesome)
      if (!preg_match('/^<i(mg)?.*/', $text)) {
         $text = Html::cleanInputText($text);
      }

      return sprintf('<a href="%1$s" %2$s>%3$s</a>', Html::cleanInputText($url),
                     Html::parseAttributes($options), $text);
   }


   /**
    * Creates a hidden input field.
    *
    * If value of options is an array then recursively parse it
    * to generate as many hidden input as necessary
    *
    * @since 0.85
    *
    * @param string $fieldName  Name of a field
    * @param array  $options    Array of HTML attributes.
    *
    * @return string A generated hidden input
   **/
   static function hidden($fieldName, $options = []) {

      if ((isset($options['value'])) && (is_array($options['value']))) {
         $result = '';
         foreach ($options['value'] as $key => $value) {
            $options2          = $options;
            $options2['value'] = $value;
            $result           .= static::hidden($fieldName.'['.$key.']', $options2)."\n";
         }
         return $result;
      }
      return sprintf('<input type="hidden" name="%1$s" %2$s />',
                     Html::cleanInputText($fieldName), Html::parseAttributes($options));
   }


   /**
    * Creates a text input field.
    *
    * @since 0.85
    *
    * @param string $fieldName  Name of a field
    * @param array  $options    Array of HTML attributes.
    *
    * @return string A generated hidden input
   **/
   static function input($fieldName, $options = []) {
      $type = 'text';
      if (isset($options['type'])) {
         $type = $options['type'];
         unset($options['type']);
      }
      return sprintf('<input type="%1$s" name="%2$s" %3$s />',
                     $type, Html::cleanInputText($fieldName), Html::parseAttributes($options));
   }

   /**
    * Creates a select tag
    *
    * @since 9.3
    *
    * @param string $ame      Name of the field
    * @param array  $values   Array of the options
    * @param mixed  $selected Current selected option
    * @param array  $options  Array of HTML attributes
    *
    * @return string
    */
   static function select($name, array $values = [], $options = []) {
      $selected = false;
      if (isset($options['selected'])) {
         $selected = $options['selected'];
         unset ($options['selected']);
      }
      $select = sprintf(
         '<select name="%1$s" %2$s>',
         self::cleanInputText($name),
         self::parseAttributes($options)
      );
      foreach ($values as $key => $value) {
         $select .= sprintf(
            '<option value="%1$s"%2$s>%3$s</option>',
            self::cleanInputText($key),
            ($selected != false && (
               $key == $selected
               || is_array($selected) && in_array($key, $selected))
            ) ? ' selected="selected"' : '',
            Html::entities_deep($value)
         );
      }
      $select .= '</select>';
      return $select;
   }

   /**
    * Creates a submit button element. This method will generate input elements that
    * can be used to submit, and reset forms by using $options. Image submits can be created by supplying an
    * image option
    *
    * @since 0.85
    *
    * @param string $caption  caption of the input
    * @param array  $options  Array of options.
    *     - image : will use a submit image input
    *     - `confirm` JavaScript confirmation message.
    *     - `confirmaction` optional action to do on confirmation
    *
    * @return string A HTML submit button
   **/
   static function submit($caption, $options = []) {

      $image = false;
      if (isset($options['image'])) {
         if (preg_match('/\.(jpg|jpe|jpeg|gif|png|ico)$/', $options['image'])) {
            $image = $options['image'];
         }
         unset($options['image']);
      }

      // Set default class to submit
      if (!isset($options['class'])) {
         $options['class'] = 'btn';
      }
      if (isset($options['confirm'])) {
         if (!empty($options['confirm'])) {
            $confirmAction  = '';
            if (isset($options['confirmaction'])) {
               if (!empty($options['confirmaction'])) {
                  $confirmAction = $options['confirmaction'];
               }
               unset($options['confirmaction']);
            }
            $options['onclick'] = Html::getConfirmationOnActionScript($options['confirm'],
                                                                      $confirmAction);
         }
         unset($options['confirm']);
      }

      if ($image) {
         $options['title'] = $caption;
         $options['alt']   = $caption;
         return sprintf('<input type="image" src="%s" %s />',
               Html::cleanInputText($image), Html::parseAttributes($options));
      }

      $button = "<button type='submit' value='%s' %s>
               $caption
            </button>&nbsp;";

      return sprintf($button, Html::cleanInputText($caption), Html::parseAttributes($options));
   }


   /**
    * Creates an accessible, stylable progress bar control.
    * @since 9.5.0
    * @param int $max    The maximum value of the progress bar.
    * @param int $value    The current value of the progress bar.
    * @param array $params  Array of options:
    *                         - rand: Random int for the progress id. Default is a new random int.
    *                         - tooltip: Text to show in the tooltip. Default is nothing.
    *                         - append_percent_tt: If true, the percent will be appended to the tooltip.
    *                               In this case, it will also be automatically updated. Default is true.
    *                         - text: Text to show in the progress bar. Default is nothing.
    *                         - append_percent_text: If true, the percent will be appended to the text.
    *                               In this case, it will also be automatically updated. Default is false.
    * @return string     The progress bar HTML
    */
   static function progress($max, $value, $params = []) {
      $p = [
         'rand'            => mt_rand(),
         'tooltip'         => '',
         'append_percent'  => true
      ];
      $p = array_replace($p, $params);

      $tooltip = trim($p['tooltip'] . ($p['append_percent'] ? " {$value}%" : ''));
      // Hide element except when using a screen reader. This uses FontAwesome's sr-only class.
      $html = "<progress id='progress{$p['rand']}' class='sr-only' max='$max' value='$value'
            onchange='updateProgress(\"{$p['rand']}\")' title='{$tooltip}'></progress>";
      // Custom progress control. Should be hidden for screen readers.
      $html .= "<div aria-hidden='true' data-progressid='{$p['rand']}'
         data-append-percent='{$p['append_percent']}' class='progress' title='{$tooltip}'>";
      $calcWidth = ($value / $max) * 100;
      $html .= "<span aria-hidden='true' class='progress-fg' style='width: $calcWidth%'></span>";
      $html .= "</div>";
      return $html;
   }


   /**
    * Returns a space-delimited string with items of the $options array.
    *
    * @since 0.85
    *
    * @param $options Array of options.
    *
    * @return string Composed attributes.
   **/
   static function parseAttributes($options = []) {

      if (!is_string($options)) {
         $attributes = [];

         foreach ($options as $key => $value) {
            $attributes[] = Html::formatAttribute($key, $value);
         }
         $out = implode(' ', $attributes);
      } else {
         $out = $options;
      }
      return $out;
   }


   /**
    * Formats an individual attribute, and returns the string value of the composed attribute.
    *
    * @since 0.85
    *
    * @param string $key    The name of the attribute to create
    * @param string $value  The value of the attribute to create.
    *
    * @return string The composed attribute.
   **/
   static function formatAttribute($key, $value) {

      if (is_array($value)) {
         $value = implode(' ', $value);
      }

      return sprintf('%1$s="%2$s"', $key, Html::cleanInputText($value));
   }


   /**
    * Wrap $script in a script tag.
    *
    * @since 0.85
    *
    * @param string $script  The script to wrap
    *
    * @return string
   **/
   static function scriptBlock($script) {

      $script = "\n" . '//<![CDATA[' . "\n\n" . $script . "\n\n" . '//]]>' . "\n";

      return sprintf('<script type="text/javascript">%s</script>', $script);
   }


   /**
    * Returns one or many script tags depending on the number of scripts given.
    *
    * @since 0.85
    * @since 9.2 Path is now relative to GLPI_ROOT. Add $minify parameter.
    *
    * @param string  $url     File to include (relative to GLPI_ROOT)
    * @param array   $options Array of HTML attributes
    * @param boolean $minify  Try to load minified file (defaults to true)
    *
    * @return String of script tags
   **/
   static function script($url, $options = [], $minify = true) {
      $version = GLPI_VERSION;
      if (isset($options['version'])) {
         $version = $options['version'];
         unset($options['version']);
      }

      if ($minify === true) {
         $url = self::getMiniFile($url);
      }

      $url = self::getPrefixedUrl($url);

      if ($version) {
         $url .= '?v=' . $version;
      }

      return sprintf('<script type="text/javascript" src="%1$s"></script>', $url);
   }


   /**
    * Creates a link element for CSS stylesheets.
    *
    * @since 0.85
    * @since 9.2 Path is now relative to GLPI_ROOT. Add $minify parameter.
    *
    * @param string  $url     File to include (relative to GLPI_ROOT)
    * @param array   $options Array of HTML attributes
    * @param boolean $minify  Try to load minified file (defaults to true)
    *
    * @return string CSS link tag
   **/
   static function css($url, $options = [], $minify = true) {
      if ($minify === true) {
         $url = self::getMiniFile($url);
      }
      $url = self::getPrefixedUrl($url);

      return self::csslink($url, $options);
   }

   /**
    * Creates a link element for SCSS stylesheets.
    *
    * @since 9.4
    *
    * @param string  $url     File to include (relative to GLPI_ROOT)
    * @param array   $options Array of HTML attributes
    *
    * @return string CSS link tag
   **/
   static function scss($url, $options = []) {
      $prod_file = self::getScssCompilePath($url);

      if (file_exists($prod_file) && $_SESSION['glpi_use_mode'] != Session::DEBUG_MODE) {
         $url = self::getPrefixedUrl(str_replace(GLPI_ROOT, '', $prod_file));
      } else {
         $file = $url;
         $url = self::getPrefixedUrl('/front/css.php');
         $url .= '?file=' . $file;
         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            $url .= '&debug';
         }
      }

      return self::csslink($url, $options);
   }

   /**
    * Creates a link element for (S)CSS stylesheets.
    *
    * @since 9.4
    *
    * @param string $url      File to include (raltive to GLPI_ROOT)
    * @param array  $options  Array of HTML attributes
    *
    * @return string CSS link tag
   **/
   static private function csslink($url, $options) {
      if (!isset($options['media']) || $options['media'] == '') {
         $options['media'] = 'all';
      }

      $version = GLPI_VERSION;
      if (isset($options['version'])) {
         $version = $options['version'];
         unset($options['version']);
      }

      $url .= ((strpos($url, '?') !== false) ? '&' : '?') . 'v=' . $version;

      return sprintf('<link rel="stylesheet" type="text/css" href="%s" %s>', $url,
                     Html::parseAttributes($options));
   }

   /**
    * Display a div who reveive a list of uploaded file
    *
    * @since  version 9.2
    *
    * @param  array $options theses following keys:
    *                          - editor_id the dom id of the tinymce editor
    * @return string The Html
    *
    * @deprecated 10.0.0
    */
   static function fileForRichText($options = []) {
      Toolbox::deprecated();

      $p['editor_id']     = '';
      $p['name']          = 'filename';
      $p['filecontainer'] = 'fileupload_info';
      $p['display']       = true;
      $rand               = mt_rand();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $display = "";

      // display file controls
      $display .= __('Attach file by drag & drop or copy & paste in editor or ').
                  "<a href='' id='upload_link$rand'>".__('selecting them')."</a>".
                  "<input id='upload_rich_text$rand' class='upload_rich_text' type='file' />";

      $display .= Html::scriptBlock("
         var fileindex = 0;
         $(function() {
            $('#upload_link$rand').on('click', function(e){
               e.preventDefault();
               $('#upload_rich_text$rand:hidden').trigger('click');
            });

            $('#upload_rich_text$rand:hidden').change(function (event) {
               uploadFile($('#upload_rich_text$rand:hidden')[0].files[0],
                            tinyMCE.get('{$p['editor_id']}'),
                            '{$p['name']}');
            });
         });
      ");

      if ($p['display']) {
         echo $display;
      } else {
         return $display;
      }
   }


   /**
    * Creates an input file field. Send file names in _$name field as array.
    * Files are uploaded in files/_tmp/ directory
    *
    * @since 9.2
    *
    * @param $options       array of options
    *    - name                string   field name (default filename)
    *    - onlyimages          boolean  restrict to image files (default false)
    *    - filecontainer       string   DOM ID of the container showing file uploaded:
    *                                   use selector to display
    *    - showfilesize        boolean  show file size with file name
    *    - showtitle           boolean  show the title above file list
    *                                   (with max upload size indication)
    *    - enable_richtext     boolean  switch to richtext fileupload
    *    - editor_id           string   id attribute for the richtext editor
    *    - pasteZone           string   DOM ID of the paste zone
    *    - dropZone            string   DOM ID of the drop zone
    *    - rand                string   already computed rand value
    *    - display             boolean  display or return the generated html (default true)
    *    - only_uploaded_files boolean  show only the uploaded files block, i.e. no title, no dropzone
    *                                   (should be false when upload has to be enable only from rich text editor)
    *
    * @return void|string   the html if display parameter is false
   **/
   static function file($options = []) {
      global $CFG_GLPI;

      $randupload             = mt_rand();

      $p['name']                = 'filename';
      $p['onlyimages']          = false;
      $p['filecontainer']       = 'fileupload_info';
      $p['showfilesize']        = true;
      $p['showtitle']           = true;
      $p['enable_richtext']     = false;
      $p['pasteZone']           = false;
      $p['dropZone']            = 'dropdoc'.$randupload;
      $p['rand']                = $randupload;
      $p['values']              = [];
      $p['display']             = true;
      $p['multiple']            = false;
      $p['uploads']             = [];
      $p['editor_id']           = null;
      $p['only_uploaded_files'] = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $display = "";
      if ($p['only_uploaded_files']) {
         $display .= "<div class='fileupload only-uploaded-files'>";
      } else {
         $display .= "<div class='fileupload draghoverable' id='{$p['dropZone']}'>";

         if ($p['showtitle']) {
            $display .= "<b>";
            $display .= sprintf(__('%1$s (%2$s)'), __('File(s)'), Document::getMaxUploadSize());
            $display .= DocumentType::showAvailableTypesLink(['display' => false]);
            $display .= "</b>";
         }
      }

      $display .= self::uploadedFiles([
         'filecontainer' => $p['filecontainer'],
         'name'          => $p['name'],
         'display'       => false,
         'uploads'       => $p['uploads'],
      ]);

      $max_file_size  = $CFG_GLPI['document_max_size'] * 1024 * 1024;
      $max_chunk_size = round(Toolbox::getPhpUploadSizeLimit() * 0.9); // keep some place for extra data

      if (!$p['only_uploaded_files']) {
         // manage file upload without tinymce editor
         $display .= "<span class='b'>".__('Drag and drop your file here, or').'</span><br>';
      }
      $display .= "<input id='fileupload{$p['rand']}' type='file' name='".$p['name']."[]'
                      class='form-control form-control-sm'
                      data-url='".$CFG_GLPI["root_doc"]."/ajax/fileupload.php'
                      data-form-data='{\"name\": \"".$p['name']."\", \"showfilesize\": \"".$p['showfilesize']."\"}'"
                      .($p['multiple']?" multiple='multiple'":"")
                      .($p['onlyimages']?" accept='.gif,.png,.jpg,.jpeg'":"").">";

      $progressall_js = '';
      if (!$p['only_uploaded_files']) {
         $display .= "<div id='progress{$p['rand']}' style='display:none'>".
                 "<div class='uploadbar' style='width: 0%;'></div></div>";
         $progressall_js = "
            progressall: function(event, data) {
               var progress = parseInt(data.loaded / data.total * 100, 10);
               $('#progress{$p['rand']}').show();
               $('#progress{$p['rand']} .uploadbar')
                  .text(progress + '%')
                  .css('width', progress + '%')
                  .show();
            },
         ";
      }

      $display .= Html::scriptBlock("
      $(function() {
         var fileindex{$p['rand']} = 0;
         $('#fileupload{$p['rand']}').fileupload({
            dataType: 'json',
            pasteZone: ".($p['pasteZone'] !== false
                           ? "$('#{$p['pasteZone']}')"
                           : "false").",
            dropZone:  ".($p['dropZone'] !== false
                           ? "$('#{$p['dropZone']}')"
                           : "false").",
            acceptFileTypes: ".($p['onlyimages']
                                    ? "/(\.|\/)(gif|jpe?g|png)$/i"
                                 : DocumentType::getUploadableFilePattern()).",
            maxFileSize: {$max_file_size},
            maxChunkSize: {$max_chunk_size},
            done: function (event, data) {
               handleUploadedFile(
                  data.files, // files as blob
                  data.result.{$p['name']}, // response from '/ajax/fileupload.php'
                  '{$p['name']}',
                  $('#{$p['filecontainer']}'),
                  '{$p['editor_id']}'
               );
            },
            processfail: function (e, data) {
               $.each(
                  data.files,
                  function(index, file) {
                     if (file.error) {
                        $('#progress{$p['rand']}').show();
                        $('#progress{$p['rand']} .uploadbar')
                           .text(file.error)
                           .css('width', '100%');
                        return;
                     }
                  }
               );
            },
            messages: {
              acceptFileTypes: __('Filetype not allowed'),
              maxFileSize: __('File is too big'),
            },
            $progressall_js
         });
      });");

      $display .= "</div>"; // .fileupload

      if ($p['display']) {
         echo $display;
      } else {
         return $display;
      }
   }

   /**
    * Display an html textarea  with extended options
    *
    * @since 9.2
    *
    * @param  array  $options with these keys:
    *  - name (string):              corresponding html attribute
    *  - filecontainer (string):     dom id for the upload filelist
    *  - rand (string):              random param to avoid overriding between textareas
    *  - editor_id (string):         id attribute for the textarea
    *  - value (string):             value attribute for the textarea
    *  - enable_richtext (bool):     enable tinymce for this textarea
    *  - enable_images (bool):       enable image pasting in tinymce (default: true)
    *  - enable_fileupload (bool):   enable the inline fileupload system
    *  - display (bool):             display or return the generated html
    *  - cols (int):                 textarea cols attribute (witdh)
    *  - rows (int):                 textarea rows attribute (height)
    *  - required (bool):            textarea is mandatory
    *  - uploads (array):            uploads to recover from a prevous submit
    *
    * @return mixed          the html if display paremeter is false or true
    */
   static function textarea($options = []) {
      //default options
      $p['name']              = 'text';
      $p['filecontainer']     = 'fileupload_info';
      $p['rand']              = mt_rand();
      $p['editor_id']         = 'text'.$p['rand'];
      $p['value']             = '';
      $p['enable_richtext']   = false;
      $p['enable_images']     = true;
      $p['enable_fileupload'] = false;
      $p['display']           = true;
      $p['cols']              = 100;
      $p['rows']              = 15;
      $p['multiple']          = true;
      $p['required']          = false;
      $p['uploads']           = [];

      //merge default options with options parameter
      $p = array_merge($p, $options);

      $required = $p['required'] ? 'required="required"' : '';
      $display = '';
      $display .= "<textarea name='".$p['name']."' id='".$p['editor_id']."'
                             rows='".$p['rows']."' cols='".$p['cols']."' $required>".
                  $p['value']."</textarea>";

      if ($p['enable_richtext']) {
         $display .= Html::initEditorSystem($p['editor_id'], $p['rand'], false, false, $p['enable_images']);
      } else {
         $display .= Html::scriptBlock("
                        $(document).ready(function() {
                           $('#".$p['editor_id']."').autogrow();
                        });
                     ");
      }
      if (!$p['enable_fileupload'] && $p['enable_richtext'] && $p['enable_images']) {
         $p_rt = $p;
         $p_rt['display'] = false;
         $p_rt['only_uploaded_files'] = true;
         $display .= Html::file($p_rt);
      }

      if ($p['enable_fileupload']) {
         $p_rt = $p;
         unset($p_rt['name']);
         $p_rt['display'] = false;
         $display .= Html::file($p_rt);
      }

      if ($p['display']) {
         echo $display;
         return true;
      } else {
         return $display;
      }
   }


   /**
    * Display uploaded files area
    * @see displayUploadedFile() in fileupload.js
    *
    * @param $options       array of options
    *    - name                string   field name (default filename)
    *    - filecontainer       string   DOM ID of the container showing file uploaded:
    *    - editor_id           string   id attribute for the textarea
    *    - display             bool     display or return the generated html
    *    - uploads             array    uploads to display (done in a previous form submit)
    * @return void|string   the html if display parameter is false
    */
   private static function uploadedFiles($options = []) {
      global $CFG_GLPI;

      //default options
      $p['filecontainer']     = 'fileupload_info';
      $p['name']              = 'filename';
      $p['editor_id']         = '';
      $p['display']           = true;
      $p['uploads']           = [];

      //merge default options with options parameter
      $p = array_merge($p, $options);

      // div who will receive and display file list
      $display = "<div id='".$p['filecontainer']."' class='fileupload_info'>";
      if (isset($p['uploads']['_' . $p['name']])) {
         foreach ($p['uploads']['_' . $p['name']] as $uploadId => $upload) {
            $prefix  = substr($upload, 0, 23);
            $displayName = substr($upload, 23);

            // get the extension icon
            $extension = pathinfo(GLPI_TMP_DIR . '/' . $upload, PATHINFO_EXTENSION);
            $extensionIcon = '/pics/icones/' . $extension . '-dist.png';
            if (!is_readable(GLPI_ROOT . $extensionIcon)) {
               $extensionIcon = '/pics/icones/defaut-dist.png';
            }
            $extensionIcon = $CFG_GLPI['root_doc'] . $extensionIcon;

            // Rebuild the minimal data to show the already uploaded files
            $upload = [
               'name'    => $upload,
               'id'      => 'doc' . $p['name'] . mt_rand(),
               'display' => $displayName,
               'size'    => filesize(GLPI_TMP_DIR . '/' . $upload),
               'prefix'  => $prefix,
            ];
            $tag = $p['uploads']['_tag_' . $p['name']][$uploadId];
            $tag = [
               'name' => $tag,
               'tag'  => "#$tag#",
            ];

            // Show the name and size of the upload
            $display .= "<p id='" . $upload['id'] . "'>&nbsp;";
            $display .= "<img src='$extensionIcon' title='$extension'>&nbsp;";
            $display .= "<b>" . $upload['display'] . "</b>&nbsp;(" . Toolbox::getSize($upload['size']) . ")";

            $name = '_' . $p['name'] . '[' . $uploadId . ']';
            $display .= Html::hidden($name, ['value' => $upload['name']]);

            $name = '_prefix_' . $p['name'] . '[' . $uploadId . ']';
            $display .= Html::hidden($name, ['value' => $upload['prefix']]);

            $name = '_tag_' . $p['name'] . '[' . $uploadId . ']';
            $display .= Html::hidden($name, ['value' => $tag['name']]);

            // show button to delete the upload
            $getEditor = 'null';
            if ($p['editor_id'] != '') {
               $getEditor = "tinymce.get('" . $p['editor_id'] . "')";
            }
            $textTag = $tag['tag'];
            $domItems = "{0:'" . $upload['id'] . "', 1:'" . $upload['id'] . "'+'2'}";
            $deleteUpload = "deleteImagePasted($domItems, '$textTag', $getEditor)";
            $display .= '<span class="fa fa-times-circle pointer" onclick="' . $deleteUpload . '"></span>';

            $display .= "</p>";
         }
      }
      $display .= "</div>";

      if ($p['display']) {
         echo $display;
         return true;
      } else {
         return $display;
      }
   }


   /**
    * @since 0.85
    *
    * @return string
   **/
   static function generateImageName() {
      return 'pastedImage'.str_replace('-', '', Html::convDateTime(date('Y-m-d', time())));
   }


   /**
    * Display choice matrix
    *
    * @since 0.85
    * @param $columns   array   of column field name => column label
    * @param $rows      array    of field name => array(
    *      'label' the label of the row
    *      'columns' an array of specific information regaring current row
    *                and given column indexed by column field_name
    *                 * a string if only have to display a string
    *                 * an array('value' => ???, 'readonly' => ???) that is used to Dropdown::showYesNo()
    * @param $options   array   possible:
    *       'title'         of the matrix
    *       'first_cell'    the content of the upper-left cell
    *       'row_check_all' set to true to display a checkbox to check all elements of the row
    *       'col_check_all' set to true to display a checkbox to check all elements of the col
    *       'rand'          random number to use for ids
    *
    * @return integer random value used to generate the ids
   **/
   static function showCheckboxMatrix(array $columns, array $rows, array $options = []) {

      $param['title']                = '';
      $param['first_cell']           = '&nbsp;';
      $param['row_check_all']        = false;
      $param['col_check_all']        = false;
      $param['rotate_column_titles'] = false;
      $param['rand']                 = mt_rand();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      $number_columns = (count($columns) + 1);
      if ($param['row_check_all']) {
         $number_columns += 1;
      }

      // count checked
      foreach ($columns as $col_name => $column) {
         $nb_cb_per_col[$col_name] = [
            'total'   => 0,
            'checked' => 0
         ];
      }

      foreach ($rows as $row_name => $row) {
         if ((!is_string($row)) && (!is_array($row))) {
            continue;
         }

         if (!is_string($row)) {
            $nb_cb_per_row[$row_name] = [
               'total'   => 0,
               'checked' => 0
            ];

            foreach ($columns as $col_name => $column) {
               if (array_key_exists($col_name, $row['columns'])) {
                  $content = $row['columns'][$col_name];
                  if (is_array($content)
                      && array_key_exists('checked', $content)) {
                     $nb_cb_per_col[$col_name]['total'] ++;
                     $nb_cb_per_row[$row_name]['total'] ++;
                     if ($content['checked']) {
                        $nb_cb_per_col[$col_name]['checked'] ++;
                        $nb_cb_per_row[$row_name]['checked'] ++;
                     }
                  }
               }
            }
         }
      }

      TemplateRenderer::getInstance()->display('components/checkbox_matrix.html.twig', [
         'title'          => $param['title'],
         'columns'        => $columns,
         'rows'           => $rows,
         'param'          => $param,
         'number_columns' => $number_columns,
         'nb_cb_per_col'  => $nb_cb_per_col,
         'nb_cb_per_row'  => $nb_cb_per_row,
      ]);

      return $param['rand'];
   }



   /**
    * This function provides a mecanism to send html form by ajax
    *
    * @param string $selector selector of a HTML form
    * @param string $success  jacascript code of the success callback
    * @param string $error    jacascript code of the error callback
    * @param string $complete jacascript code of the complete callback
    *
    * @see https://api.jquery.com/jQuery.ajax/
    *
    * @since 9.1
   **/
   static function ajaxForm($selector, $success = "console.log(html);", $error = "console.error(html)", $complete = '') {
      echo Html::scriptBlock("
      $(function() {
         var lastClicked = null;
         $('input[type=submit], button[type=submit]').click(function(e) {
            e = e || event;
            lastClicked = e.target || e.srcElement;
         });

         $('$selector').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var formData = form.closest('form').serializeArray();
            //push submit button
            formData.push({
               name: $(lastClicked).attr('name'),
               value: $(lastClicked).val()
            });

            $.ajax({
               url: form.attr('action'),
               type: form.attr('method'),
               data: formData,
               success: function(html) {
                  $success
               },
               error: function(html) {
                  $error
               },
               complete: function(html) {
                  $complete
               }
            });
         });
      });
      ");
   }

   /**
    * In this function, we redefine 'window.alert' javascript function
    * by a jquery-ui dialog equivalent (but prettier).
    *
    * @since 9.1
   **/
   static function redefineAlert() {

      echo self::scriptBlock("
      window.old_alert = window.alert;
      window.alert = function(message, caption) {
         // Don't apply methods on undefined objects... ;-) #3866
         if(typeof message == 'string') {
            message = message.replace('\\n', '<br>');
         }
         caption = caption || '"._sn('Information', 'Information', 1)."';

         glpi_alert({
            title: caption,
            message: message,
         });
      };");
   }


   /**
    * Summary of confirmCallback
    * Is a replacement for Javascript native confirm function
    * Beware that native confirm is synchronous by nature (will block
    * browser waiting an answer from user, but that this is emulating the confirm behaviour
    * by using callbacks functions when user presses 'Yes' or 'No' buttons.
    *
    * @since 9.1
    *
    * @param $msg            string      message to be shown
    * @param $title          string      title for dialog box
    * @param $yesCallback    string      function that will be called when 'Yes' is pressed
    *                                    (default null)
    * @param $noCallback     string      function that will be called when 'No' is pressed
    *                                    (default null)
   **/
   static function jsConfirmCallback($msg, $title, $yesCallback = null, $noCallback = null) {
      return "glpi_confirm({
         title: '".Toolbox::addslashes_deep($title)."',
         message: '".Toolbox::addslashes_deep($msg)."',
         confirm_event: function() {
            ".($yesCallback!==null?'('.$yesCallback.')()':'')."
         },
         cancel_event: function() {
            ".($noCallback!==null?'('.$noCallback.')()':'')."
         },
      });
      ";
   }


   /**
    * In this function, we redefine 'window.confirm' javascript function
    * by a jquery-ui dialog equivalent (but prettier).
    * This dialog is normally asynchronous and can't return a boolean like naive window.confirm.
    * We manage this behavior with a global variable 'confirmed' who watchs the acceptation of dialog.
    * In this case, we trigger a new click on element to return the value (and without display dialog)
    *
    * @since 9.1
   */
   static function redefineConfirm() {

      echo self::scriptBlock("
      var confirmed = false;
      var lastClickedElement;

      // store last clicked element on dom
      $(document).click(function(event) {
          lastClickedElement = $(event.target);
      });

      // asynchronous confirm dialog with jquery ui
      var newConfirm = function(message, caption) {
         message = message.replace('\\n', '<br>');
         caption = caption || '';

         glpi_confirm({
            title: caption,
            message: message,
            confirm_callback: function() {
               confirmed = true;

               //trigger click on the same element (to return true value)
               lastClickedElement.click();

               // re-init confirmed (to permit usage of 'confirm' function again in the page)
               // maybe timeout is not essential ...
               setTimeout(function() {
                  confirmed = false;
               }, 100);
            }
         });
      };

      window.nativeConfirm = window.confirm;

      // redefine native 'confirm' function
      window.confirm = function (message, caption) {
         // if watched var isn't true, we can display dialog
         if(!confirmed) {
            // call asynchronous dialog
            newConfirm(message, caption);
         }

         // return early
         return confirmed;
      };");
   }


   /**
    * Summary of jsAlertCallback
    * Is a replacement for Javascript native alert function
    * Beware that native alert is synchronous by nature (will block
    * browser waiting an answer from user, but that this is emulating the alert behaviour
    * by using a callback function when user presses 'Ok' button.
    *
    * @since 9.1
    *
    * @param $msg          string   message to be shown
    * @param $title        string   title for dialog box
    * @param $okCallback   string   function that will be called when 'Ok' is pressed
    *                               (default null)
   **/
   static function jsAlertCallback($msg, $title, $okCallback = null) {
      return "glpi_alert({
         title: '".Toolbox::addslashes_deep( $title )."',
         message: '".Toolbox::addslashes_deep($msg)."',
         ok_callback: function() {
            ".($okCallback!==null?'('.$okCallback.')()':'')."
         },
      });";
   }


   /**
    * Get image html tag for image document.
    *
    * @param int    $document_id  identifier of the document
    * @param int    $width        witdh of the final image
    * @param int    $height       height of the final image
    * @param bool   $addLink      boolean, do we need to add an anchor link
    * @param string $more_link    append to the link (ex &test=true)
    *
    * @return string
    *
    * @since 9.4.3
   **/
   public static function getImageHtmlTagForDocument($document_id, $width, $height, $addLink = true, $more_link = "") {
      global $CFG_GLPI;

      $document = new Document();
      if (!$document->getFromDB($document_id)) {
         return '';
      }

      $base_path = $CFG_GLPI['root_doc'];
      if (isCommandLine()) {
         $base_path = parse_url($CFG_GLPI['url_base'], PHP_URL_PATH);
      }

      // Add only image files : try to detect mime type
      $ok   = false;
      $mime = '';
      if (isset($document->fields['filepath'])) {
         $fullpath = GLPI_DOC_DIR."/".$document->fields['filepath'];
         $mime = Toolbox::getMime($fullpath);
         $ok   = Toolbox::getMime($fullpath, 'image');
      }

      if (!($ok || empty($mime))) {
         return '';
      }

      $out = '';
      if ($addLink) {
         $out .= '<a '
                 . 'href="' . $base_path . '/front/document.send.php?docid=' . $document_id . $more_link . '" '
                 . 'target="_blank" '
                 . '>';
      }
      $out .= '<img ';
      if (isset($document->fields['tag'])) {
         $out .= 'alt="' . $document->fields['tag'] . '" ';
      }
      $out .= 'width="' . $width . '" '
              . 'src="' . $base_path . '/front/document.send.php?docid=' . $document_id . $more_link . '" '
              . '/>';
      if ($addLink) {
         $out .= '</a>';
      }

      return $out;
   }

   /**
    * Get copyright message in HTML (used in footers)
    * @since 9.1
    * @param boolean $withVersion include GLPI version ?
    * @return string HTML copyright
    */
   static function getCopyrightMessage($withVersion = true) {
      $message = "<a href=\"http://glpi-project.org/\" title=\"Powered by Teclib and contributors\" class=\"copyright\">";
      $message .= "GLPI ";
      // if required, add GLPI version (eg not for login page)
      if ($withVersion) {
          $message .= GLPI_VERSION . " ";
      }
      $message .= "Copyright (C) 2015-" . GLPI_YEAR . " Teclib' and contributors".
         "</a>";
      return $message;
   }

   /**
    * A a required javascript lib
    *
    * @param string|array $name Either a know name, or an array defining lib
    *
    * @return void
    */
   static public function requireJs($name) {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      if (isset($_SESSION['glpi_js_toload'][$name])) {
         //already in stack
         return;
      }
      switch ($name) {
         case 'tabler':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/tabler.js';
            break;
         case 'glpi_dialog':
            $_SESSION['glpi_js_toload'][$name][] = 'js/glpi_dialog.js';
            break;
         case 'clipboard':
            $_SESSION['glpi_js_toload'][$name][] = 'js/clipboard.js';
            break;
         case 'tinymce':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/tinymce.js';
            $_SESSION['glpi_js_toload'][$name][] = 'js/RichText/UserMention.js';
            break;
         case 'planning':
            $_SESSION['glpi_js_toload'][$name][] = 'js/planning.js';
            break;
         case 'flatpickr':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/flatpickr.js';
            if (isset($_SESSION['glpilanguage'])) {
               $filename = "public/lib/flatpickr/l10n/".
                  strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]).".js";
               if (file_exists(GLPI_ROOT . '/' . $filename)) {
                  $_SESSION['glpi_js_toload'][$name][] = $filename;
                  break;
               }
            }
            break;
         case 'fullcalendar':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/fullcalendar.js';
            if (isset($_SESSION['glpilanguage'])) {
               foreach ([2, 3] as $loc) {
                  $filename = "public/lib/fullcalendar/core/locales/".
                     strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][$loc]).".js";
                  if (file_exists(GLPI_ROOT . '/' . $filename)) {
                     $_SESSION['glpi_js_toload'][$name][] = $filename;
                     break;
                  }
               }
            }
            break;
         case 'jstree':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/jstree.js';
            break;
         case 'gantt':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/jquery-gantt.js';
            break;
         case 'kanban':
            $_SESSION['glpi_js_toload'][$name][] = 'js/kanban.js';
            $_SESSION['glpi_js_toload'][$name][] = 'lib/jqueryplugins/jquery.ui.touch-punch.js';
            break;
         case 'rateit':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/jquery.rateit.js';
            break;
         case 'fileupload':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/file-type.js';
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/jquery-file-upload.js';
            $_SESSION['glpi_js_toload'][$name][] = 'js/fileupload.js';
            break;
         case 'charts':
            $_SESSION['glpi_js_toload']['charts'][] = 'public/lib/chartist.js';
            break;
         case 'notifications_ajax';
            $_SESSION['glpi_js_toload']['notifications_ajax'][] = 'js/notifications_ajax.js';
            break;
         case 'fuzzy':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/fuzzy.js';
            $_SESSION['glpi_js_toload'][$name][] = 'js/fuzzysearch.js';
            break;
         case 'dashboard':
            $_SESSION['glpi_js_toload'][$name][] = 'js/dashboard.js';
            break;
         case 'marketplace':
            $_SESSION['glpi_js_toload'][$name][] = 'js/marketplace.js';
            break;
         case 'gridstack':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/gridstack.js';
            break;
         case 'masonry':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/masonry.js';
            break;
         case 'sortable':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/sortable.js';
            break;
         case 'rack':
            $_SESSION['glpi_js_toload'][$name][] = 'js/rack.js';
            $_SESSION['glpi_js_toload'][$name][] = 'lib/jqueryplugins/jquery.ui.touch-punch.js';
            break;
         case 'leaflet':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/leaflet.js';
            break;
         case 'log_filters':
            $_SESSION['glpi_js_toload'][$name][] = 'js/log_filters.js';
            break;
         case 'codemirror':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/codemirror.js';
            break;
         case 'photoswipe':
            $_SESSION['glpi_js_toload'][$name][] = 'public/lib/photoswipe.js';
         case 'reservations':
            $_SESSION['glpi_js_toload'][$name][] = 'js/reservations.js';
            break;
         default:
            $found = false;
            if (isset($PLUGIN_HOOKS['javascript']) && isset($PLUGIN_HOOKS['javascript'][$name])) {
               $found = true;
               $jslibs = $PLUGIN_HOOKS['javascript'][$name];
               if (!is_array($jslibs)) {
                  $jslibs = [$jslibs];
               }
               foreach ($jslibs as $jslib) {
                  $_SESSION['glpi_js_toload'][$name][] = $jslib;
               }
            }
            if (!$found) {
               Toolbox::logError("JS lib $name is not known!");
            }
      }
   }


   /**
    * Load javascripts
    *
    * @return void
    */
   static private function loadJavascript() {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      //load on demand scripts
      if (isset($_SESSION['glpi_js_toload'])) {
         foreach ($_SESSION['glpi_js_toload'] as $key => $script) {
            if (is_array($script)) {
               foreach ($script as $s) {
                  echo Html::script($s);
               }
            } else {
               echo Html::script($script);
            }
            unset($_SESSION['glpi_js_toload'][$key]);
         }
      }

      //locales for js libraries
      if (isset($_SESSION['glpilanguage'])) {
         // select2
         $filename = "public/lib/select2/js/i18n/".
                     $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2].".js";
         if (file_exists(GLPI_ROOT.'/'.$filename)) {
            echo Html::script($filename);
         }
      }

      // transfer core variables to javascript side
      echo self::getCoreVariablesForJavascript(true);

      // Some Javascript-Functions which we may need later
      echo Html::script('js/common.js');
      self::redefineAlert();
      self::redefineConfirm();

      if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax'] && !Session::isImpersonateActive()) {
         $options = [
            'interval'  => ($CFG_GLPI['notifications_ajax_check_interval'] ? $CFG_GLPI['notifications_ajax_check_interval'] : 5) * 1000,
            'sound'     => $CFG_GLPI['notifications_ajax_sound'] ? $CFG_GLPI['notifications_ajax_sound'] : false,
            'icon'      => ($CFG_GLPI["notifications_ajax_icon_url"] ? $CFG_GLPI['root_doc'] . $CFG_GLPI['notifications_ajax_icon_url'] : false),
            'user_id'   => Session::getLoginUserID()
         ];
         $js = "$(function() {
            notifications_ajax = new GLPINotificationsAjax(". json_encode($options) . ");
            notifications_ajax.start();
         });";
         echo Html::scriptBlock($js);
      }

      // add Ajax display message after redirect
      Html::displayAjaxMessageAfterRedirect();

      // Add specific javascript for plugins
      if (isset($PLUGIN_HOOKS['add_javascript']) && count($PLUGIN_HOOKS['add_javascript'])) {
         foreach ($PLUGIN_HOOKS["add_javascript"] as $plugin => $files) {
            $plugin_root_dir = Plugin::getPhpDir($plugin, true);
            $plugin_web_dir  = Plugin::getWebDir($plugin, false);
            if (!Plugin::isPluginActive($plugin)) {
               continue;
            }
            $version = Plugin::getInfo($plugin, 'version');
            if (!is_array($files)) {
               $files = [$files];
            }
            foreach ($files as $file) {
               if (file_exists($plugin_root_dir."/$file")) {
                  echo Html::script("$plugin_web_dir/$file", ['version' => $version]);
               } else {
                  Toolbox::logWarning("$file file not found from plugin $plugin!");
               }
            }
         }
      }

      if (file_exists(GLPI_ROOT."/js/analytics.js")) {
         echo Html::script("js/analytics.js");
      }
   }


   /**
    * transfer some var of php to javascript
    * (warning, don't expose all keys of $CFG_GLPI, some shouldn't be available client side)
    *
    * @param bool $full if false, don't expose all variables from CFG_GLPI (only url_base & root_doc)
    *
    * @since 9.5
    * @return string
    */
   static function getCoreVariablesForJavascript(bool $full = false) {
      global $CFG_GLPI;

      $cfg_glpi = "var CFG_GLPI  = {
         'url_base': '".(isset($CFG_GLPI['url_base']) ? $CFG_GLPI["url_base"] : '')."',
         'root_doc': '".$CFG_GLPI["root_doc"]."',
      };";

      if ($full) {
         $debug = (isset($_SESSION['glpi_use_mode'])
                   && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);
         $cfg_glpi = "var CFG_GLPI  = ".json_encode(Config::getSafeConfig(true), $debug ? JSON_PRETTY_PRINT : 0).";";
      }

      $plugins_path = [];
      foreach (Plugin::getPlugins() as $key) {
         $plugins_path[$key] = Plugin::getWebDir($key, false);
      }
      $plugins_path = 'var GLPI_PLUGINS_PATH = '.json_encode($plugins_path).';';

      return self::scriptBlock("
         $cfg_glpi
         $plugins_path
      ");
   }

   /**
    * Get a stylesheet or javascript path, minified if any
    * Return minified path if minified file exists and not in
    * debug mode, else standard path
    *
    * @param string $file_path File path part
    *
    * @return string
    */
   static private function getMiniFile($file_path) {
      $debug = (isset($_SESSION['glpi_use_mode'])
         && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? true : false);

      $file_minpath = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $file_path);
      if (file_exists(GLPI_ROOT . '/' . $file_minpath)) {
         if (!$debug || !file_exists(GLPI_ROOT . '/' . $file_path)) {
            return $file_minpath;
         }
      }

      return $file_path;
   }

   /**
    * Return prefixed URL
    *
    * @since 9.2
    *
    * @param string $url Original URL (not prefixed)
    *
    * @return string
    */
   static public final function getPrefixedUrl($url) {
      global $CFG_GLPI;
      $prefix = $CFG_GLPI['root_doc'];
      if (substr($url, 0, 1) != '/') {
         $prefix .= '/';
      }
      return $prefix . $url;
   }

   /**
    * Add the HTML code to refresh the current page at a define interval of time
    *
    * @param int|false   $timer    The time (in minute) to refresh the page
    * @param string|null $callback A javascript callback function to execute on timer
    *
    * @return string
    */
   static public function manageRefreshPage($timer = false, $callback = null) {
      if (!$timer) {
         $timer = $_SESSION['glpirefresh_views'] ?? 0;
      }

      if ($callback === null) {
         $callback = 'window.location.reload()';
      }

      $text = "";
      if ($timer > 0) {
         // set timer to millisecond from minutes
         $timer = $timer * MINUTE_TIMESTAMP * 1000;

         // call callback function to $timer interval
         $text = self::scriptBlock("window.setInterval(function() {
               $callback
            }, $timer);");
      }

      return $text;
   }

   /**
    * Manage events from js/fuzzysearch.js
    *
    * @since 9.2
    *
    * @param string $action action to switch (should be actually 'getHtml' or 'getList')
    *
    * @return string
    */
   static function fuzzySearch($action = '') {
      switch ($action) {
         case 'getHtml':
            $modal_header = __("Go to menu");
            $placeholder  = __("Start typing to find a menu");
            $alert        = sprintf(
               __("Tip: You can call this modal with %s keys combination"),
               "<kbd><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>G</kbd></kbd>"
            );

            $html = <<<HTML
               <div class="modal" tabindex="-1" id="fuzzysearch">
                  <div class="modal-dialog">
                     <div class="modal-content">
                        <div class="modal-header">
                           <h5 class="modal-title">
                              <i class="fas fa-arrow-right me-2"></i>
                              {$modal_header}
                           </h5>
                           <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                           <div class="alert alert-info d-flex" role="alert">
                              <i class="fas fa-exclamation-circle fa-2x me-2"></i>
                              <p>{$alert}</p>
                           </div>
                           <input type="text" class="form-control" placeholder="{$placeholder}">
                           <ul class="results list-group mt-2"></ul>
                        </div>
                     </div>
                  </div>
               </div>

HTML;
            return $html;
            break;

         default;
            $fuzzy_entries = [];

            // retrieve menu
            foreach ($_SESSION['glpimenu'] as $firstlvl) {
               if (isset($firstlvl['content'])) {
                  foreach ($firstlvl['content'] as $menu) {
                     if (isset($menu['title']) && strlen($menu['title']) > 0) {
                        $fuzzy_entries[] = [
                           'url'   => $menu['page'],
                           'title' => $firstlvl['title']." > ".$menu['title']
                        ];

                        if (isset($menu['options'])) {
                           foreach ($menu['options'] as $submenu) {
                              if (isset($submenu['title']) && strlen($submenu['title']) > 0) {
                                 $fuzzy_entries[] = [
                                    'url'   => $submenu['page'],
                                    'title' => $firstlvl['title']." > ".
                                               $menu['title']." > ".
                                               $submenu['title']
                                 ];
                              }
                           }
                        }
                     }
                  }
               }

               if (isset($firstlvl['default'])) {
                  if (strlen($menu['title']) > 0) {
                     $fuzzy_entries[] = [
                        'url'   => $firstlvl['default'],
                        'title' => $firstlvl['title']
                     ];
                  }
               }
            }

            // return the entries to ajax call
            return json_encode($fuzzy_entries);
            break;
      }
   }

   /**
    * Display GLPI top menu
    *
    * @param boolean $full True for full interface, false otherwise
    *
    * @return void
    */
   private static function displayTopMenu($full) {
      global $CFG_GLPI;

      /// Prefs / Logout link
      echo "<div id='c_preference' >";
      echo "<ul>";

      echo "<li id='deconnexion'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/logout.php";
            /// logout witout noAuto login for extauth
      if (isset($_SESSION['glpiextauth']) && $_SESSION['glpiextauth']) {
         echo "?noAUTO=1";
      }
      echo "' title=\"".__s('Logout')."\" class='fa fa-sign-out-alt'>";
      // check user id : header used for display messages when session logout
      echo "<span class='sr-only'>" . __s('Logout') . "></span>";
      echo "</a>";
      echo "</li>\n";

      $username = '';
      $title = __s('My settings');
      if (Session::getLoginUserID()) {
         $username = formatUserName(0, $_SESSION["glpiname"], $_SESSION["glpirealname"],
                                    $_SESSION["glpifirstname"], 0, 20);
         $title = sprintf(
            __s('%1$s - %2$s'),
            __s('My settings'),
            $username
         );
      }
      echo "<li id='preferences_link'><a href='".$CFG_GLPI["root_doc"]."/front/preference.php' title=\"".
                 $title."\" class='fa fa-cog'>";
      echo "<span class='sr-only'>" . __s('My settings') . "</span>";

      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         echo "<span id='myname'>{$username}</span>";
      }
      echo "</a></li>";

      if (Config::canUpdate()) {
         $is_debug_active = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
         $class = 'debug' . ($is_debug_active ? 'on' : 'off');
         $title = sprintf(
            __s('%1$s - %2$s'),
            __s('Change mode'),
            $is_debug_active ? __s('Debug mode enabled') : __s('Debug mode disabled')
         );
         echo "<li id='debug_mode'>";
         echo "<a href='{$CFG_GLPI['root_doc']}/ajax/switchdebug.php' class='fa fa-bug $class'
                title='$title'>";
         echo "<span class='sr-only'>" . __s('Change mode') . "</span>";
         echo "</a>";
         echo "</li>";
      }

      /// Bookmark load
      echo "<li id='bookmark_link'>";
      Ajax::createSlidePanel(
         'showSavedSearches',
         [
            'title'     => __('Saved searches'),
            'url'       => $CFG_GLPI['root_doc'] . '/ajax/savedsearch.php?action=show',
            'icon'      => '/pics/menu_config.png',
            'icon_url'  => SavedSearch::getSearchURL(),
            'icon_txt'  => __('Manage saved searches')
         ]
      );
      echo "<a href='#' id='showSavedSearchesLink' class='fa fa-star' title=\"".
             __s('Load a saved search'). "\">";
      echo "<span class='sr-only'>" . __('Saved searches')  . "</span>";
      echo "</a></li>";

      if (Session::getCurrentInterface() == 'central') {
         $url_help_link = (empty($CFG_GLPI["central_doc_url"])
            ? "http://glpi-project.org/help-central"
            : $CFG_GLPI["central_doc_url"]);
      } else {
         $url_help_link = (empty($CFG_GLPI["helpdesk_doc_url"])
            ? "http://glpi-project.org/help-central"
            : $CFG_GLPI["helpdesk_doc_url"]);
      }

      echo "<li id='help_link'>".
           "<a href='".$url_help_link."' target='_blank' title=\"".
                            __s('Help')."\" class='fa fa-question'>".
           "<span class='sr-only'>" . __('Help') . "</span>";
      echo "</a></li>";

      if (!GLPI_DEMO_MODE) {
         echo "<li id='language_link'><a href='".$CFG_GLPI["root_doc"].
                    "/front/preference.php?forcetab=User\$1' title=\"".
                    addslashes(Dropdown::getLanguageName($_SESSION['glpilanguage']))."\">".
                    Dropdown::getLanguageName($_SESSION['glpilanguage'])."</a></li>";
      } else {
         echo "<li id='language_link'><span>" .
            Dropdown::getLanguageName($_SESSION['glpilanguage']) . "</span></li>";
      }

      echo "<li id='c_recherche'>\n";
      if ($full === true) {
         /// Search engine
         if ($CFG_GLPI['allow_search_global']) {
            echo "<form role='search' method='get' action='".$CFG_GLPI["root_doc"]."/front/search.php'>\n";
            echo "<span id='champRecherche'>";
            echo "<input size='15' type='search' name='globalsearch' placeholder='". __s('Search')."' aria-labelledby='globalsearchglass'>";
            echo "<button type='submit' name='globalsearchglass' id='globalsearchglass'>";
            echo "<i class='fa fa-search'></i><span class='sr-only'>". __s('Search')."</span>";
            echo "</button>";
            echo "</span>";
            Html::closeForm();
         }
      }
      echo "</li>";

      echo "</ul>";
      echo "</div>\n";
   }

   /**
    * Display GLPI main menu
    *
    * @param boolean $full    True for full interface, false otherwise
    * @param array   $options Option
    *
    * @return void
    */
   private static function displayMainMenu($full, $options = []) {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      $sector = '';

      // Generate array for menu and check right
      if ($full === true) {
         $menu    = self::generateMenuSession($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE);
         $sector  = $options['sector'];
         $item    = $options['item'];
         $option  = $options['option'];
      } else {
         $menu = [];

         //  Create ticket
         if (Session::haveRight("ticket", CREATE)) {
            $menu['create_ticket'] = [
               'default'   => '/front/helpdesk.public.php?create_ticket=1',
               'title'     => __s('Create a ticket'),
               'content'   => [true]
            ];
         }

         //  Tickets
         if (Session::haveRight("ticket", CREATE)
            || Session::haveRight("ticket", Ticket::READMY)
            || Session::haveRight("followup", ITILFollowup::SEEPUBLIC)
         ) {
            $menu['tickets'] = [
               'default'   => '/front/ticket.php',
               'title'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
               'content'   => [true]
            ];
         }

         // Reservation
         if (Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
            $menu['reservation'] = [
               'default'   => '/front/reservationitem.php',
               'title'     => _n('Reservation', 'Reservations', Session::getPluralNumber()),
               'content'   => [true]
            ];
         }

         // FAQ
         if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
            $menu['faq'] = [
               'default'   => '/front/helpdesk.faq.php',
               'title'     => __s('FAQ'),
               'content'   => [true]
            ];
         }
      }

      $menu = Plugin::doHookFunction("redefine_menus", $menu);

      $already_used_shortcut = ['1'];

      echo "<div id='c_menu'>";
      echo "<ul id='menu'";
      if ($full === true) {
         echo " class='fullmenu'";
      }
      echo ">";

      // Get object-variables and build the navigation-elements
      $i = 1;
      foreach ($menu as $part => $data) {
         if (isset($data['content']) && count($data['content'])) {
            $menu_class = "";
            if (isset($menu[$sector]) && $menu[$sector]['title'] == $data['title']) {
               $menu_class = "active";
            }

            echo "<li id='menu$i' data-id='$i' class='$menu_class'>";
            $link = "#";

            if (isset($data['default']) && !empty($data['default'])) {
               $link = $CFG_GLPI["root_doc"].$data['default'];
            }

            echo "<a href='$link' class='itemP'>{$data['title']}</a>";
            if (!isset($data['content'][0]) || $data['content'][0] !== true) {
               echo "<ul class='ssmenu'>";

               // list menu item
               foreach ($data['content'] as $key => $val) {
                  $menu_class       = "";
                  $tmp_active_item  = explode("/", $item);
                  $active_item      = array_pop($tmp_active_item);
                  if (isset($menu[$sector]['content'])
                     && isset($menu[$sector]['content'][$active_item])
                     && isset($val['title'])
                     && ($menu[$sector]['content'][$active_item]['title'] == $val['title'])) {
                     $menu_class = "active";
                  }
                  if (isset($val['page'])
                     && isset($val['title'])) {
                     $shortcut_attr = "";
                     $title = $val['title'];

                     if (isset($val['shortcut']) && !empty($val['shortcut'])) {
                        if (!isset($already_used_accesskeyshortcut[$val['shortcut']])) {
                           $shortcut_attr = " ='".$val['shortcut']."'";
                           $already_used_shortcut[$val['shortcut']] = $val['shortcut'];
                        }
                        $title = Toolbox::shortcut($val['title'], $val['shortcut']);
                     }

                     $icon_cls = "";
                     if (isset($val['icon'])) {
                        $icon_cls = $val['icon'];
                     }
                     $icon = "<i class='fa-fw $icon_cls'></i>";

                     echo "<li class='$menu_class'>
                        <a href='".$CFG_GLPI["root_doc"].$val['page']."' $shortcut_attr>
                           $icon
                           $title
                        </a>
                     </li>";
                  }
               }
               echo "</ul>";
            }
            echo "</li>";
            $i++;
         }
      }

      if ($full === false) {
         // Plugins
         $menu['plugins'] = [
            'default'   => "#",
            'title'     => _sn('Plugin', 'Plugins', Session::getPluralNumber()),
            'content'   => []
         ];

         if (isset($PLUGIN_HOOKS["helpdesk_menu_entry"])
            && count($PLUGIN_HOOKS["helpdesk_menu_entry"])) {

            foreach ($PLUGIN_HOOKS["helpdesk_menu_entry"] as $plugin => $active) {
               if (!Plugin::isPluginActive($plugin)) {
                  continue;
               }
               if ($active) {
                  $infos = Plugin::getInfo($plugin);
                  $link = "";
                  if (is_string($PLUGIN_HOOKS["helpdesk_menu_entry"][$plugin])) {
                     $link = $PLUGIN_HOOKS["helpdesk_menu_entry"][$plugin];
                  }
                  $infos['page'] = $link;
                  $infos['title'] = $infos['name'];
                  $menu['plugins']['content'][$plugin] = $infos;
               }
            }
         }

         // Display plugins
         if (isset($menu['plugins']['content']) && count($menu['plugins']['content']) > 0) {
            asort($menu['plugins']['content']);
            echo "<li id='menu5' onmouseover=\"javascript:menuAff('menu5','menu');\">";
            echo "<a href='#' title=\"".
                  _sn('Plugin', 'Plugins', Session::getPluralNumber())."\" class='itemP'>".
                  __('Plugins')."</a>"; // default none
            echo "<ul class='ssmenu'>";

            // list menu item
            foreach ($menu['plugins']['content'] as $key => $val) {
               echo "<li><a href='".Plugin::getWebDir($key).$val['page']."'>".
                        $val["title"]."</a></li>";
            }
            echo "</ul></li>";
         }
      }

      echo "</ul>"; // #menu

      // End navigation bar
      // End headline

      //  Le fil d ariane
      echo "<div id='c_ssmenu2' >";
      echo "<ul>";

      // Display item
      $mainurl = ($full === true) ? 'central' : 'helpdesk.public';
      echo "<li class='breadcrumb_item'>".
           "<a href='".$CFG_GLPI["root_doc"]."/front/$mainurl.php' title=\"". __s('Home')."\">".
             __('Home')."</a></li>";

      if ($full === true) {
         if (isset($menu[$sector])) {
            $link = "/front/central.php";

            if (isset($menu[$sector]['default'])) {
               $link = $menu[$sector]['default'];
            }
            echo "<li class='breadcrumb_item'>".
               "<a href='".$CFG_GLPI["root_doc"].$link."' title=\"".$menu[$sector]['title']."\">".
                     $menu[$sector]['title']."</a></li>";
         }

         if (isset($menu[$sector]['content'][$item])) {
            // Title
            $with_option = false;

            if (!empty($option)
               && isset($menu[$sector]['content'][$item]['options'][$option]['title'])
               && isset($menu[$sector]['content'][$item]['options'][$option]['page'])) {

               $with_option = true;
            }

            if (isset($menu[$sector]['content'][$item]['page'])) {
               echo "<li class='breadcrumb_item'>".
                  "<a href='".$CFG_GLPI["root_doc"].$menu[$sector]['content'][$item]['page']."' ".
                        ($with_option?"":"class='here'")." title=\"".
                        $menu[$sector]['content'][$item]['title']."\" >".
                        "<i class='".($menu[$sector]['content'][$item]['icon'] ?? "")."'></i>&nbsp;".
                        $menu[$sector]['content'][$item]['title'].
                  "</a>".
                  "</li>";
            }

            if ($with_option) {
               echo "<li class='breadcrumb_item'>".
                  "<a href='".$CFG_GLPI["root_doc"].
                        $menu[$sector]['content'][$item]['options'][$option]['page'].
                        "' class='here' title=\"".
                        $menu[$sector]['content'][$item]['options'][$option]['title']."\" >";

               echo "<i class='".($menu[$sector]['content'][$item]['options'][$option]['icon'] ?? "")."'></i>&nbsp;";
               echo self::resume_name($menu[$sector]['content'][$item]['options'][$option]['title'],
                                    17);
               echo "</a></li>";
            }

            $links = [];
            // Item with Option case
            if (!empty($option)
               && isset($menu[$sector]['content'][$item]['options'][$option]['links'])
               && is_array($menu[$sector]['content'][$item]['options'][$option]['links'])) {
               $links = $menu[$sector]['content'][$item]['options'][$option]['links'];

            } else if (isset($menu[$sector]['content'][$item]['links'])
                     && is_array($menu[$sector]['content'][$item]['links'])) {
               // Without option case : only item links

               $links = $menu[$sector]['content'][$item]['links'];
            }

            // Add item
            echo "<li class='icons_block'>";
            echo "<span>";
            if (isset($links['add'])) {
               echo "<a href='{$CFG_GLPI['root_doc']}{$links['add']}' class='pointer'
                                 title='" . __s('Add') ."'><i class='fa fa-plus'></i>
                                 <span class='sr-only'>" . __('Add') . "</span></a>";
            } else {
               echo "<a href='#' class='pointer disabled' title='".__s('Add is disabled')."'>".
                  "<i class='fa fa-plus'></i>".
                  "<span class='sr-only'>" . __('Add is disabled') . "</span></a>";
            }
            echo "</span>";

            // Search Item
            echo "<span>";
            if (isset($links['search'])) {
               echo "<a href='{$CFG_GLPI['root_doc']}{$links['search']}' class='pointer'
                                 title='" . __s('Search') ."'><i class='fa fa-search'></i>
                                 <span class='sr-only'>" . __s('Search') . "</span></a>";
            } else {
               echo "<a href='#' class='pointer disabled' title='" . __s('Search is disabled')."'>".
                  "<i class='fa fa-search'></i>".
                  "<span class='sr-only'>" . __('Search is disabled') . "</span></a>";
            }
            echo "</span>";
            // Links
            if (count($links) > 0) {
               foreach ($links as $key => $val) {

                  switch ($key) {
                     case "add" :
                     case "search" :
                        break;

                     case "template" :
                        echo "<span>";
                        echo Html::link('<i class="pointer fa fa-layer-group"></i>',
                                        $CFG_GLPI["root_doc"].$val, [
                                          'title' => __('Manage templates...')
                                        ]);
                        echo "</span>";
                        break;

                     case "showall" :
                        echo "<span>";
                        echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_showall.png",
                                       ['alt' => __('Show all'),
                                             'url' => $CFG_GLPI["root_doc"].$val]);
                        echo "</span>";
                        break;

                     case "summary" :
                        echo "<span>";
                        echo Html::link('<i class="pointer fas fa-stream"></i>',
                                        $CFG_GLPI["root_doc"].$val, [
                                          'title' => __('Summary')
                                        ]);
                        echo "</span>";
                        break;

                     case "summary_kanban" :
                        echo "<span>";
                        echo Html::link('<i class="pointer fas fa-columns"></i>',
                           $CFG_GLPI["root_doc"].$val, [
                              'title' => __('Global Kanban')
                           ]);
                        echo "</span>";
                        break;

                     case "config" :
                        echo "<span>";
                        echo Html::image($CFG_GLPI["root_doc"] . "/pics/menu_config.png",
                                       ['alt' => __('Setup'),
                                             'url' => $CFG_GLPI["root_doc"].$val]);
                        echo "</span>";
                        break;

                     default :
                        echo "<span>".Html::link($key, $CFG_GLPI["root_doc"].$val, ['class' => 'pointer'])."</span>";
                        break;
                  }
               }
            }
            echo "</li>";

         } else {
            echo "<li>&nbsp;</li>";
         }
      } else {
         if (Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {
            $opt                              = [];
            $opt['reset']                     = 'reset';
            $opt['criteria'][0]['field']      = 55; // validation status
            $opt['criteria'][0]['searchtype'] = 'equals';
            $opt['criteria'][0]['value']      = TicketValidation::WAITING;
            $opt['criteria'][0]['link']       = 'AND';

            $opt['criteria'][1]['field']      = 59; // validation aprobator
            $opt['criteria'][1]['searchtype'] = 'equals';
            $opt['criteria'][1]['value']      = Session::getLoginUserID();
            $opt['criteria'][1]['link']       = 'AND';

            $url_validate = $CFG_GLPI["root_doc"]."/front/ticket.php?".
                           Toolbox::append_params($opt, '&amp;');
            $pic_validate = "<a href='$url_validate'>".
                           "<img title=\"".__s('Ticket waiting for your approval')."\" alt=\"".
                              __s('Ticket waiting for your approval')."\" src='".
                              $CFG_GLPI["root_doc"]."/pics/menu_showall.png' class='pointer'></a>";
            echo "<li class='icons_block'>$pic_validate</li>\n";
         }

         if (Session::haveRight('ticket', CREATE)
            && strpos($_SERVER['PHP_SELF'], "ticket")) {
            echo "<li class='icons_block'><a class='pointer' href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?create_ticket=1'title=\"".__s('Add')."\">";
            echo "<i class='fa fa-plus'></i><span class='sr-only'>".__s('Add')."</span></a></li>";
         }
      }

      // Add common items

      // Profile selector
      // check user id : header used for display messages when session logout
      if (Session::getLoginUserID()) {
         self::showProfileSelecter($CFG_GLPI["root_doc"]."/front/$mainurl.php");
      }
      echo "</ul>";
      echo "</div>";
   }

   /**
    * Invert the input color (usefull for label bg on top of a background)
    * inpiration: https://github.com/onury/invert-color
    *
    * @since  9.3
    *
    * @param  string  $hexcolor the color, you can pass hex color (prefixed or not by #)
    *                           You can also pass a short css color (ex #FFF)
    * @param  boolean $bw       default true, should we invert the color or return black/white function of the input color
    * @param  boolean $sb       default true, should we soft the black/white to a dark/light grey
    * @return string            the inverted color prefixed by #
    */
   static function getInvertedColor($hexcolor = "", $bw = true, $sbw = true) {
      if (strpos($hexcolor, '#') !== false) {
         $hexcolor = trim($hexcolor, '#');
      }
      // convert 3-digit hex to 6-digits.
      if (strlen($hexcolor) == 3) {
         $hexcolor = $hexcolor[0] + $hexcolor[0]
                   + $hexcolor[1] + $hexcolor[1]
                   + $hexcolor[2] + $hexcolor[2];
      }
      if (strlen($hexcolor) != 6) {
         throw new \Exception('Invalid HEX color.');
      }

      $r = hexdec(substr($hexcolor, 0, 2));
      $g = hexdec(substr($hexcolor, 2, 2));
      $b = hexdec(substr($hexcolor, 4, 2));

      if ($bw) {
         return ($r * 0.299 + $g * 0.587 + $b * 0.114) > 100
            ? ($sbw
               ? '#303030'
               : '#000000')
            : ($sbw
               ? '#DFDFDF'
               : '#FFFFFF');
      }
      // invert color components
      $r = 255 - $r;
      $g = 255 - $g;
      $b = 255 - $b;

      // pad each with zeros and return
      return "#"
         + str_pad($r, 2, '0', STR_PAD_LEFT)
         + str_pad($g, 2, '0', STR_PAD_LEFT)
         + str_pad($b, 2, '0', STR_PAD_LEFT);
   }

   /**
    * Compile SCSS styleshet
    *
    * @param array $args Arguments. May contain:
    *                      - v: version to append (will default to GLPI_VERSION)
    *                      - debug: if present, will not use Crunched formatter
    *                      - file: filerepresentation  to load
    *                      - reload: force reload and recache
    *                      - nocache: do not use nor update cache
    *
    * @return string
    */
   public static function compileScss($args) {
      global $CFG_GLPI, $GLPI_CACHE;

      $ckey = 'css_';
      $ckey .= isset($args['v']) ? $args['v'] : GLPI_SCHEMA_VERSION;

      $scss = new Compiler();
      if (isset($args['debug'])) {
         $ckey .= '_sourcemap';
         $scss->setSourceMap(Compiler::SOURCE_MAP_INLINE);
         $scss->setSourceMapOptions(
            [
               'sourceMapBasepath' => GLPI_ROOT . '/',
               'sourceRoot'        => $CFG_GLPI['root_doc'] . '/',
            ]
         );
      }

      $file = isset($args['file']) ? $args['file'] : 'css/styles';

      $ckey .= '_' . $file;

      if (!Toolbox::endsWith($file, '.scss')) {
         // Prevent include of file if ext is not .scss
         $file .= '.scss';
      }

      // Requested file path
      $path = GLPI_ROOT . '/' . $file;

      // Alternate file path (prefixed by a "_", i.e. "_highcontrast.scss").
      $pathargs = explode('/', $file);
      $pathargs[] = '_' . array_pop($pathargs);
      $pathalt = GLPI_ROOT . '/' . implode('/', $pathargs);

      if (!file_exists($path) && !file_exists($pathalt)) {
         Toolbox::logWarning('Requested file ' . $path . ' does not exists.');
         return '';
      }
      if (!file_exists($path)) {
         $path = $pathalt;
      }

      // Prevent import of a file from ouside GLPI dir
      $path = realpath($path);
      if (!Toolbox::startsWith($path, realpath(GLPI_ROOT))) {
         Toolbox::logWarning('Requested file ' . $path . ' is outside GLPI file tree.');
         return '';
      }

      $import = '@import "' . $file . '";';
      $fckey = 'css_raw_file_' . $file;
      $file_hash = self::getScssFileHash($path);

      //check if files has changed
      if (!isset($args['nocache']) && $GLPI_CACHE->has($fckey)) {
         if ($file_hash != $GLPI_CACHE->get($fckey)) {
            //file has changed
            $args['reload'] = true;
         }
      }

      $scss->addImportPath(GLPI_ROOT);

      // Enable imports of ".scss" files from "node_modules", when path starts with "~".
      $scss->addImportPath(
         function($path) {
            $file_chunks = [];
            if (!preg_match('/^~@?(?<directory>.*)\/(?<file>[^\/]+)(?:(\.scss)?)/', $path, $file_chunks)) {
               return null;
            }

            $possible_filenames = [
               sprintf('%s/css/lib/%s/%s.scss', GLPI_ROOT, $file_chunks['directory'], $file_chunks['file']),
               sprintf('%s/css/lib/%s/_%s.scss', GLPI_ROOT, $file_chunks['directory'], $file_chunks['file']),
            ];
            foreach ($possible_filenames as $filename) {
               if (file_exists($filename)) {
                  return $filename;
               }
            }

            return null;
         }
      );

      if (!isset($args['reload']) && !isset($args['nocache']) && $GLPI_CACHE->has($ckey)) {
         $css = $GLPI_CACHE->get($ckey);
      } else {
         try {
            Toolbox::logDebug("Compile $file");
            $css = $scss->compile($import);
            if (!isset($args['nocache'])) {
               $GLPI_CACHE->set($ckey, $css);
               $GLPI_CACHE->set($fckey, $file_hash);
            }
         } catch (\Throwable $e) {
            if (isset($args['debug'])) {
               $msg = 'An error occured during SCSS compilation: ' . $e->getMessage();
               $msg = str_replace(["\n", "\"", "'"], ['\00000a', '\0022', '\0027'], $msg);
               $css = <<<CSS
                  html::before {
                     background: #F33;
                     content: '$msg';
                     display: block;
                     padding: 20px;
                     position: sticky;
                     top: 0;
                     white-space: pre-wrap;
                     z-index: 9999;
                  }
CSS;
            }
            global $application;
            if ($application instanceof Application) {
               throw $e;
            }
         }
      }

      return $css;
   }

   /**
    * Returns SCSS file hash.
    * This function evaluates recursivly imports to compute a hash that represent the whole
    * contents of the final SCSS.
    *
    * @param string $filepath
    *
    * @return null|string
    */
   public static function getScssFileHash(string $filepath) {

      if (!is_file($filepath) || !is_readable($filepath)) {
         return null;
      }

      $contents = file_get_contents($filepath);
      $hash = md5($contents);

      $matches = [];
      if (!preg_match_all('/@import\s+[\'"](?<url>~?@?[^\'"]*)[\'"];/', $contents, $matches)) {
         return $hash;
      }

      foreach ($matches['url'] as $import_url) {
         $potential_paths = [];

         $has_extension   = preg_match('/\.s?css$/', $import_url);
         $is_from_lib     = preg_match('/^~/', $import_url);
         $import_dirname  = dirname(preg_replace('/^~?@?/', '', $import_url)); // Remove leading ~ and @ from lib path
         $import_filename = basename($import_url) . ($has_extension ? '' : '.scss');

         if ($is_from_lib) {
            // Search file in libs
            $potential_paths[] = GLPI_ROOT . '/css/lib/' . $import_dirname . '/' . $import_filename;
            $potential_paths[] = GLPI_ROOT . '/css/lib/' . $import_dirname . '/_' . $import_filename;
         } else {
            // Search using path relative to GLPI root
            $potential_paths[] = GLPI_ROOT . '/' . $import_dirname . '/' . $import_filename;
            $potential_paths[] = GLPI_ROOT . '/' . $import_dirname . '/_' . $import_filename;
            // Search using path relative to current file
            $potential_paths[] = dirname($filepath) . '/' . $import_dirname . '/' . $import_filename;
            $potential_paths[] = dirname($filepath) . '/' . $import_dirname . '/_' . $import_filename;
         }

         foreach ($potential_paths as $path) {
            if (is_file($path)) {
               $hash .= self::getScssFileHash($path);
               break;
            }
         }
      }

      return $hash;
   }

   /**
    * Get scss compilation path for given file.
    *
    * @return array
    */
   public static function getScssCompilePath($file) {
      $file = preg_replace('/\.scss$/', '', $file);

      return implode(
         DIRECTORY_SEPARATOR,
         [
            self::getScssCompileDir(),
            str_replace('/', '_', $file) . '.min.css',
         ]
      );
   }

   /**
    * Get scss compilation directory.
    *
    * @return string
    */
   public static function getScssCompileDir() {
      return GLPI_ROOT . '/css_compiled';
   }

   /**
    * Display impersonate banner if feature is currently used.
    *
    * @return void
    */
   public static function displayImpersonateBanner() {

      if (!Session::isImpersonateActive()) {
         return;
      }

      echo '<div class="banner-impersonate">';
      echo '<form name="form" method="post" action="' . User::getFormURL() . '">';
      echo sprintf(__('You are impersonating %s.'), $_SESSION['glpiname']);
      echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
      echo '<button type="submit" name="impersonate" class="btn-linkstyled" value="0">';
      echo __s('Stop impersonating');
      echo '</button>';
      echo '</form>';
      echo '</div>';
   }

   /**
    * Return a relative for the given timestamp
    *
    * @param mixed $ts
    * @return string
    */
   static function time2str($ts) {
      if (!ctype_digit($ts)) {
         $ts = strtotime($ts);
      }

      $diff = time() - $ts;
      if ($diff == 0) {
         return __('Now');
      } else if ($diff > 0) {
         $day_diff = floor($diff / 86400);
         if ($day_diff == 0) {
            if ($diff < 60) {
               return __('Just now');
            }
            if ($diff < 3600) {
               return  sprintf(__('%s minutes ago'), floor($diff / 60));
            }
            if ($diff < 86400) {
               return  sprintf(__('%s hours ago'), floor($diff / 3600));
            }
         }
         if ($day_diff == 1) {
            return __('Yesterday');
         }
         if ($day_diff < 7) {
            return sprintf(__('%s days ago'), $day_diff);
         }
         if ($day_diff < 31) {
            return sprintf(__('%s weeks ago'), ceil($day_diff / 7));
         }
         if ($day_diff < 60) {
            return __('Last month');
         }

         return date('F Y', $ts);
      } else {
         $diff     = abs($diff);
         $day_diff = floor($diff / 86400);
         if ($day_diff == 0) {
            if ($diff < 120) {
               return __('In a minute');
            }
            if ($diff < 3600) {
               return sprintf(__('In %s minutes'), floor($diff / 60));
            }
            if ($diff < 7200) {
               return __('In an hour');
            }
            if ($diff < 86400) {
               return sprintf(__('In %s hours'), floor($diff / 3600));
            }
         }
         if ($day_diff == 1) {
            return __('Tomorrow');
         }
         if ($day_diff < 4) {
            return date('l', $ts);
         }
         if ($day_diff < 7 + (7 - date('w'))) {
            return __('next week');
         }
         if (ceil($day_diff / 7) < 4) {
            return sprintf(__('In %s weeks'), ceil($day_diff / 7));
         }
         if (date('n', $ts) == date('n') + 1) {
            return __('next month');
         }
         return date('F Y', $ts);
      }

      return "";
   }
}
