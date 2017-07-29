<?php

/**
 * Container for package wide static methods
 *
 * @package   Promo
 * @copyright 2011-2017 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class Promo
{
	// {{{ constants

	const GETTEXT_DOMAIN = 'promo';

	// }}}
	// {{{ private properties

	/**
	 * Whether or not this package is initialized
	 *
	 * @var boolean
	 */
	private static $is_initialized = false;

	// }}}
	// {{{ public static function _()

	public static function _($message)
	{
		return self::gettext($message);
	}

	// }}}
	// {{{ public static function gettext()

	public static function gettext($message)
	{
		return dgettext(self::GETTEXT_DOMAIN, $message);
	}

	// }}}
	// {{{ public static function ngettext()

	public static function ngettext(
		$singular_message,
		$plural_message,
		$number
	) {
		return dngettext(
			self::GETTEXT_DOMAIN,
			$singular_message,
			$plural_message,
			$number
		);
	}

	// }}}
	// {{{ public static function setupGettext()

	public static function setupGettext()
	{
		bindtextdomain(self::GETTEXT_DOMAIN, '@DATA-DIR@/Promo/locale');
		bind_textdomain_codeset(self::GETTEXT_DOMAIN, 'UTF-8');
	}

	// }}}
	// {{{ public static function init()

	public static function init()
	{
		if (self::$is_initialized) {
			return;
		}

		Swat::init();
		Site::init();
		Store::init();
		Admin::init();

		self::setupGettext();

		SwatUI::mapClassPrefixToPath('Promo', 'Promo');

		SwatDBClassMap::addPath('Promo/dataobjects');
		SwatDBClassMap::add('StoreCartEntry', 'PromoCartEntry');
		SwatDBClassMap::add('StoreOrder', 'PromoOrder');
		SwatDBClassMap::add('StoreOrderItem', 'PromoOrderItem');

		// class-mapped classes that are loaded with memcache need to be
		// pre-required here to avoid "incomplete class" errors on
		// unserialization
		SwatDBClassMap::get('PromoPromotion');

		self::$is_initialized = true;
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Prevent instantiation of this static class
	 */
	private function __construct()
	{
	}

	// }}}
}

?>
