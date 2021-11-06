Dominique92.GeoBB
=================
- MyPhpBB extension adds some personal features to the phpBB forum software https://www.phpbb.com

DEPENDENCIES
============
* This package includes : https://github.com/Dominique92/MyOl
* This package is tested with : PhpBB 3.3.1

ADDITIONAL FUNCTIONS
====================
* define('MYPHPBB_CREATE_POST_BUTTON', true); // Add a post create button on index & viewforom forum description lines
* define('MYPHPBB_POST_EMPTY_TEXT', true); // Allows entering a POST with empty text
* define('MYPHPBB_POST_EMPTY_SUBJECT', true); // Prevent an empty title to invalidate the full page and input.
* define('MYPHPBB_LOG_EDIT', true); // Keep trace of values prior to modifications. Create a log file with the post existing data if there is none
* define('MYPHPBB_COUNTRY_CODES', ['FR, ...']); // Inhibits the registration of unauthorized countries list in MYPHPBB_COUNTRY_CODES ISO-3166 Country Codes
* define('MYPHPBB_DISABLE_VARNISH', true); // Disable Varnish cache
* define('MYPHPBB_DUMP_GLOBALS', '_SERVER'); // Dump global
* define('MYPHPBB_DUMP_TEMPLATE', true); // Dump templates variables

MYPHPBB_