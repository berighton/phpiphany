<?php
/**
 * Form validator class. Includes validation of:
 * - email
 * - postal code (validation + formatting)
 * - telephone (as well as formatting the output of a phone number)
 * - username
 * - password
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package system/classes
 * @since 1.0
 *
 */

class validator {

	/**
	 * Validates an email address.
	 *
	 * @param string $email Email address.
	 * @return bool
	 */
	static function valid_email($email) {
		// First, we check that there's one @ symbol, and that the lengths are right.
		if (!preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email)) {
			// Email invalid because wrong number of characters in one section or wrong number of @ symbols.
			return false;
		}
		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); ++$i) {
			if (!preg_match("@^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$@", $local_array[$i])) {
				return false;
			}
		}
		// Check if domain is IP. If not, it should be a valid domain name
		if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $email_array[1])) {
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) {
				return false; // Not enough parts to domain
			}
			for ($i = 0; $i < sizeof($domain_array); ++$i) {
				if (!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i])) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Checks if there are 7 or 10 digits, and if so returns formatted string
	 *
	 * @param string $phone The phone number to validate
	 * @return bool
	 */
	static function format_phone($phone) {

		$phone = preg_replace("/[^0-9]/", "", $phone);
		//eg 259-1127
		if (strlen($phone) == 7)
			return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone);
		//eg 416-259-1127
		elseif (strlen($phone) == 10)
			return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "$1-$2-$3", $phone);
		//eg 1-800-259-1127
		elseif (strlen($phone) == 11)
			return preg_replace("/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1-$2-$3-$4", $phone);
		//eg 011-7-495-259-1127
		elseif (strlen($phone) == 14)
			return preg_replace("/([0-9]{3})([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1-$2-$3-$4-$5", $phone);
		else
			return $phone;
	}

	/**
	 * Validates a phone number by checking the amount digits present
	 *
	 * @param string $number The phone number to check
	 * @return bool Returns true on success, false on mismatch
	 */
	static function valid_phone($number) {
		$formats = array('###-###-####', '##########', '### ### ####', '###.###.####', '(###)###-####');
		$format = trim(preg_replace('/[0-9]/', '#', $number));
		return (in_array($format, $formats)) ? true : false;
	}

	/**
	 * Validate a postal code
	 *
	 * @param string $postal_code The postal code that needs to be checked
	 * @param string $province Province, if known. Optional
	 * @return boolean Returns true or false if the postal code in question is valid
	 */
	static function valid_postal_code($postal_code, $province = '') {
		$postal_code = strtolower($postal_code);
		$first_letter = substr($postal_code, 0, 1);
		$province = strtolower($province);

		$provinces = array(
			'nl' => 'a',
			'ns' => 'b',
			'pe' => 'c',
			'nb' => 'e',
			'qc' => array('g', 'h', 'j'),
			'on' => array('k', 'l', 'm', 'n', 'p'),
			'mb' => 'r',
			'sk' => 's',
			'ab' => 't',
			'bc' => 'v',
			'nt' => 'x',
			'nu' => 'x',
			'yt' => 'y'
		);

		if (preg_match('/[abceghjlkmnprstvxy]/', $first_letter) && !preg_match('/[dfioqu]/', $postal_code) && preg_match('/^[a-z]{1}\d{1}[a-z]{1}[- ]?\d{1}[a-z]{1}\d{1}$/', $postal_code)) {
			if (!empty($province) && array_key_exists($province, $provinces)) {
				if (is_array($provinces[$province]) && in_array($first_letter, $provinces[$province])) {
					return true;
				} elseif (is_string($provinces[$province]) && $first_letter == $provinces[$province]) {
					return true;
				}
			} elseif (empty($province)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Formats a postal code in a format N2N 2N2
	 *
	 * @param string $pc The postal code that needs to be beautified
	 * @return string N2N 2N2
	 */
	static function format_postal_code($pc) {
		$pc = strtoupper($pc);
		if (strlen($pc) == 6) $pc = substr($pc, 0, 3) . ' ' . substr($pc, 3, 3);
		return $pc;
	}

	/**
	 * Simple function which ensures that a username contains only valid characters.
	 *
	 * @param string $username
	 * @return mixed|null
	 */
	static function valid_username($username) {

		if (strlen($username) < 4) return 'Username too short';

		// Blacklist for bad characters (partially nicked from mediawiki)
		$blacklist = '/[' .
			'\x{0080}-\x{009f}' . # iso-8859-1 control chars
			'\x{00a0}' .          # non-breaking space
			'\x{2000}-\x{200f}' . # various whitespace
			'\x{2028}-\x{202f}' . # breaks and control chars
			'\x{3000}' .          # ideographic space
			'\x{e000}-\x{f8ff}' . # private use
			']/u';

		if (preg_match($blacklist, $username)) return 'Username is invalid';

		// Belts and braces TODO: Tidy into main unicode
		$blacklist2 = '/\\"\*& ?#%^(){}[]~?<>;|¬`@+=';
		for ($n=0; $n < strlen($blacklist2); $n++) {
			if (strpos($username, $blacklist2[$n]) !== false) return 'Username is invalid';
		}

		return true;
	}

	/**
	 * Simple function which ensures that a username contains only valid characters.
	 *
	 * @param string $password
	 * @return mixed|null
	 */
	static function valid_password($password) {

		if (strlen($password) < 6) return 'Password too short';

		// Password must include at least one letter and one number or a symbol
		if (preg_match("/^(?=.*[a-zA-Z])((?=.*\d)|(?=.*[-+_!@#$%^&*.,?])).+$/", $password)) return true;
		else return 'Password is invalid';

		// Password must include at least one letter
		//if (!preg_match("#[a-z]+#", $password)) return 'Password is invalid';

		// Password must include at least one CAPS
		//if (!preg_match("#[A-Z]+#", $password)) return 'Password is invalid';

		// Password must include at least one number
		//if (!preg_match("#[0-9]+#", $password)) return 'Password is invalid';

		// Password must include at least one symbol
		//if (!preg_match("#\W+#", $password)) return 'Password is invalid';
	}

	/**
	 * Formats a given number into a currency format
	 * Usage: to print a british pound value of a number, issue the following command
	 * validator::format_currency('$42180.8751', false, true, 'uk');
	 * or simply with $number as the only argument to have everything set to default
	 *
	 * @static
	 * @param string $number The number to format
	 * @param bool $stripped If set to true, will only output the stripped down version of the number (useful for DB inserts)
	 * @param bool $prepend_currency_sign Set to true if a currency sign needs to be printed in front of the number
	 * @param string $currency_sign The actual currency sign. Use 'euro' or 'pound' to simplify the process
	 * @return mixed|string The formatted number string
	 */
	static function format_currency($number, $stripped = false, $prepend_currency_sign = false, $currency_sign = '$'){
		$number = preg_replace("/[^0-9\.]/", "", $number);
		if ($stripped) return $number;
		elseif ($prepend_currency_sign){
			$number = number_format($number, 2);
			$currency_sign = strtolower($currency_sign);
			if ($currency_sign == '$' or $currency_sign == 'dollar' or $currency_sign == 'us') {
				return '$' . $number;
			} elseif ($currency_sign == '€' or $currency_sign == 'euro') {
				return '€' . $number;
			} elseif ($currency_sign == '£' or $currency_sign == 'pound' or $currency_sign == 'uk') {
				return '£' . $number;
			} elseif ($currency_sign == '¥' or $currency_sign == 'yen' or $currency_sign == 'yuan' or $currency_sign == 'china' or $currency_sign == 'japan') {
				return '¥' . $number;
			} elseif ($currency_sign == '¢' or $currency_sign == 'cent') {
				return '¢' . $number;
			} else {
				return '¤' . $number;
			}
		} else {
			return $number;
		}
	}

}
