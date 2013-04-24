<?php
/**
 * Bootstrapping, setting up and loading the core.
 *
 * @package LydiaCore
 */

/**
 * Enable auto-load of class declarations.
 */
function autoload($aClassName) {
  $classFile = "/src/{$aClassName}/{$aClassName}.php";
	$file1 = LYDIA_SITE_PATH . $classFile;
	$file2 = LYDIA_INSTALL_PATH . $classFile;
	if(is_file($file1)) {
		require_once($file1);
	} elseif(is_file($file2)) {
		require_once($file2);
	}
}
spl_autoload_register('autoload');


/**
 * Set a default exception handler and enable logging in it.
 */
function exceptionHandler($e) {
  echo "Lydia: Uncaught exception: <p>" . $e->getMessage() . "</p><pre>" . $e->getTraceAsString(), "</pre>";
}
set_exception_handler('exceptionHandler');


/**
 * i18n, internationalization, send all strings though this function to enable i18n.
 * Inspired by Drupal´s t()-function.
 *
 * @param string $str the string to check up for translation.
 * @param array $args associative array with arguments to be replaced in the $str.
 *   - !variable: Inserted as is. Use this for text that has already been
 *     sanitized.
 *   - @variable: Escaped to HTML using htmlEnt(). Use this for anything
 *     displayed on a page on the site.
 * @return string the translated string.
 */
function t($str, $args = array()) {
  if(CLydia::Instance()->config['i18n']) {  
   $str = gettext($str);
  }

  // santitize and replace arguments
  if(!empty($args)) {
    foreach($args as $key => $val) {
      switch($key[0]) {
        case '@': $args[$key] = htmlEnt($val); break;
        case '!': 
        default: /* pass through */ break;
      }
    }
    return strtr($str, $args);
  }
  return $str;
}



/**
 * Check if the methodname is a localised one.
 *
 * @param string $key as the key for the method name to lookup.
 * @return string/boolean with the method name to call or false.
 */
function checkMethodL10n($key) {
  $cfg = CLydia::Instance()->config;
  
  if(!isset($cfg['method_L10n'][$cfg['language']])) {
    return false;
  }

  $cfgL10n = array_flip($cfg['method_L10n'][$cfg['language']]);
  return isset($cfgL10n[$key]) ? $cfgL10n[$key] : false;
}



/**
 * Create a localised name for a method, if it can.
 *
 * @param string $key as the key for the method name to lookup.
 * @return string/boolean with the method name to call or false.
 */
//function createMethodL10n($key) {
function m($key) {
  $cfg = CLydia::Instance()->config;
  
  if(!isset($cfg['method_L10n'][$cfg['language']])) {
    return $key;
  }

  $cfgL10n = $cfg['method_L10n'][$cfg['language']];
  return isset($cfgL10n[$key]) ? $cfgL10n[$key] : $key;
}




/**
 * Helper, include a file and store it in a string. Make $vars available to the included file.
 *
 * @param string $filename
 * @param array $vars a set of variables made available to process in the included file.
 * @return string with content from file, processed using variables.
 */
function getIncludeContents($filename, $vars=array()) {
  if(is_file($filename)) {
    extract($vars);
    ob_start();
    include $filename;
    return ob_get_clean();
  }
  return false;
}


/**
 * Helper, wrap htmlspecialchars with correct character encoding
 */
function htmlSpec($str, $flags = ENT_COMPAT) {
  return htmlspecialchars($str, $flags, CLydia::Instance()->config['character_encoding']);
}


/**
 * Helper, wrap html_entites with correct character encoding
 */
function htmlEnt($str, $flags = ENT_COMPAT) {
  return htmlentities($str, $flags, CLydia::Instance()->config['character_encoding']);
}


/**
 * Helper, unwrap html_entites with correct character encoding
 */
function htmlDent($str, $flags = ENT_COMPAT) {
  return html_entity_decode($str, $flags, CLydia::Instance()->config['character_encoding']);
}


/**
 * Format a date according the collate.
 *
 * @param string $date a date for format.
 * @return string for the formatted date.
 */
function formatDate($date) {
  $locale = CLydia::Instance()->config['language'];
  $ftm = new IntlDateFormatter($locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
  return $ftm->format(strtotime($date));
}


/**
 * Helper, interval formatting of times. Needs PHP5.3. 
 *
 * All times in database is UTC so this function assumes the starttime to be in UTC, if not otherwise
 * stated.
 *
 * Copied from http://php.net/manual/en/dateinterval.format.php#96768
 * Modified (mos) to use timezones.
 * A sweet interval formatting, will use the two biggest interval parts.
 * On small intervals, you get minutes and seconds.
 * On big intervals, you get months and days.
 * Only the two biggest parts are used.
 *
 * @param DateTime|string $start
 * @param DateTimeZone|string|null $startTimeZone
 * @param DateTime|string|null $end
 * @param DateTimeZone|string|null $endTimeZone
 * @return string
 */
function formatDateTimeDiff($start, $startTimeZone=null, $end=null, $endTimeZone=null) {
  if(!($start instanceof DateTime)) {
    if($startTimeZone instanceof DateTimeZone) {
      $start = new DateTime($start, $startTimeZone);
    } else if(is_null($startTimeZone)) {
      $start = new DateTime($start);
    } else {
      $start = new DateTime($start, new DateTimeZone($startTimeZone));
    }
  }
  
  if($end === null) {
    $end = new DateTime();
  }
  
  if(!($end instanceof DateTime)) {
    if($endTimeZone instanceof DateTimeZone) {
      $end = new DateTime($end, $endTimeZone);
    } else if(is_null($endTimeZone)) {
      $end = new DateTime($end);
    } else {
      $end = new DateTime($end, new DateTimeZone($endTimeZone));
    }
  }
  
  $interval = $end->diff($start);
  $doPlural = function($nb, $str1, $str2){return $nb>1?$str2:$str1;}; // adds plurals
  
  $format = array();
  if($interval->y !== 0) {
    $format[] = "%y ".$doPlural($interval->y, t('year'), t('years'));
  }
  if($interval->m !== 0) {
    $format[] = "%m ".$doPlural($interval->m, t('month'), t('months'));
  }
  if($interval->d !== 0) {
    $format[] = "%d ".$doPlural($interval->d, t('day'), t('days'));
  }
  if($interval->h !== 0) {
    $format[] = "%h ".$doPlural($interval->h, t('hour'), t('hours'));
  }
  if($interval->i !== 0) {
    $format[] = "%i ".$doPlural($interval->i, t('minute'), t('minutes'));
  }
  if(!count($format)) {
      return t('less than a minute');
  }
  if($interval->s !== 0) {
    $format[] = "%s ".$doPlural($interval->s, t('second'), t('seconds'));
  }
  
  if($interval->s !== 0) {
      if(!count($format)) {
          return t('less than a minute');
      } else {
          $format[] = "%s ".$doPlural($interval->s, t('second'), t('seconds'));
      }
  }
  
  // We use the two biggest parts
  if(count($format) > 1) {
      $format = array_shift($format)." and ".array_shift($format);
  } else {
      $format = array_pop($format);
  }
  
  // Prepend 'since ' or whatever you like
  return $interval->format($format);
}



/**
 * Helper, make clickable links from URLs in text.
 * @deprecated since v0.3.0.1, moved to CTextFilter
 */
function makeClickable($text) {
  return preg_replace_callback(
    '#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', 
    create_function(
      '$matches',
      'return "<a href=\'{$matches[0]}\'>{$matches[0]}</a>";'
    ),
    $text
  );
}


/**
 * Helper, BBCode formatting converting to HTML.
 *
 * @param string text The text to be converted.
 * @returns string the formatted text.
 * @deprecated since v0.3.0.1, moved to CTextFilter
 */
function bbcode2html($text) {
  $search = array( 
    '/\[b\](.*?)\[\/b\]/is', 
    '/\[i\](.*?)\[\/i\]/is', 
    '/\[u\](.*?)\[\/u\]/is', 
    '/\[img\](https?.*?)\[\/img\]/is', 
    '/\[url\](https?.*?)\[\/url\]/is', 
    '/\[url=(https?.*?)\](.*?)\[\/url\]/is' 
    );   
  $replace = array( 
    '<strong>$1</strong>', 
    '<em>$1</em>', 
    '<u>$1</u>', 
    '<img src="$1" />', 
    '<a href="$1">$1</a>', 
    '<a href="$1">$2</a>' 
    );     
  return preg_replace($search, $replace, $text);
}


/**
 * Create a slug of a string, to be used as url.
 *
 * @param string $str the string to format as slug.
 * @returns str the formatted slug. 
 */
function slugify($str) {
  $str = mb_strtolower(trim($str));
  $str = str_replace(array('å','ä','ö'), array('a','a','o'), $str);
  $str = preg_replace('/[^a-z0-9-]/', '-', $str);
  $str = trim(preg_replace('/-+/', '-', $str), '-');
  if(empty($str)) { return 'n-a'; }
  return $str;
}


/**
 * Check if string is slugified, containing [a-zA-Z0-9-].
 *
 * @param string $str the string to check.
 * @returns boolean true is slugified, else false.
 */
function is_slug($str) {
  return preg_match('/^[a-zA-Z0-9\-]+$/', $str);
}


/**
 * Get a smaller part of text, a teaser, break at space/word/dot.
 *
 * @param string $str string to get the first part from.
 * @param int $len maximum length to return, defaults to 200 characters.
 * @returns string.
 */
function teaser($str, $len=400) {
  if(mb_strlen($str) <= $len) { return($str); }
  $pos = mb_strpos($str, ' ', $len);
  $str = trim(mb_substr($str, 0, $pos));
  return $str;
}


/**
 * Returns the excerpt of the text with at most the specified amount of characters.
 * 
 * @param string $excerpt the original string.
 * @param int $chars the number of characters to return.
 * @param boolean $hard do a hard break at exactly $chars characters or find closest space.
 * @return string as the excerpt.
 */
function excerpt($excerpt, $chars=139, $hard=false) {
  if(strlen($excerpt) > $chars) {
    $excerpt   = substr($excerpt, 0, $chars-1);
  } else {
    return $excerpt;
  }

  if(!$hard) {
    $lastSpace = strrpos($excerpt, ' ');
    $excerpt   = substr($excerpt, 0, $lastSpace);
  }

  return $excerpt;
}
  


/**
 * Dump a array or object for debug.
 *
 * @param array/object $a.
 */
function dump($a) {
  echo '<pre>', print_r($a, true), '</pre>';
}



/**
 * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
 * keys to arrays rather than overwriting the value in the first array with the duplicate
 * value in the second array, as array_merge does. I.e., with array_merge_recursive,
 * this happens (documented behavior):
 *
 * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('org value', 'new value'));
 *
 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
 * Matching keys' values in the second array overwrite those in the first array, as is the
 * case with array_merge, i.e.:
 *
 * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
 *     => array('key' => array('new value'));
 *
 * Parameters are passed by reference, though only for performance reasons. They're not
 * altered by this function.
 *
 * @param array $array1
 * @param array $array2
 * @return array
 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
 */
function array_merge_recursive_distinct ( array &$array1, array &$array2 )
{
  $merged = $array1;

  foreach ( $array2 as $key => &$value )
  {
    if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
    {
      $merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
    }
    else
    {
      $merged [$key] = $value;
    }
  }

  return $merged;
}




