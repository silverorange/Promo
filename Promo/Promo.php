<?php

require_once 'Store/Store.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * Container for package wide static methods
 *
 * @package   Promo
 * @copyright 2011-2014 silverorange
 */
class Promo
{
	// {{{ constants

	const GETTEXT_DOMAIN = 'promo';

	// }}}
	// {{{ public static function _()

	public static function _($message)
	{
		return Promo::gettext($message);
	}

	// }}}
	// {{{ public static function gettext()

	public static function gettext($message)
	{
		return dgettext(Promo::GETTEXT_DOMAIN, $message);
	}

	// }}}
	// {{{ public static function ngettext()

	public static function ngettext($singular_message,
		$plural_message, $number)
	{
		return dngettext(
			Promo::GETTEXT_DOMAIN,
			$singular_message,
			$plural_message,
			$number
		);
	}

	// }}}
	// {{{ public static function setupGettext()

	public static function setupGettext()
	{
		bindtextdomain(Promo::GETTEXT_DOMAIN, '@DATA-DIR@/Promo/locale');
		bind_textdomain_codeset(Promo::GETTEXT_DOMAIN, 'UTF-8');
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

Promo::setupGettext();

SwatUI::mapClassPrefixToPath('Promo', 'Promo');

SwatDBClassMap::addPath('Promo/dataobjects');
SwatDBClassMap::add('StoreCartEntry', 'PromoCartEntry');
SwatDBClassMap::add('StoreOrder',     'PromoOrder');
SwatDBClassMap::add('StoreOrderItem', 'PromoOrderItem');

// class-mapped clasess that are loaded with memcache need to be pre-required
// here to avoid "incomplete class" errors on unserialization
SwatDBClassMap::get('PromoPromotion');

?>
