<?php

/**
 * @category  IngestPlugin
 * @package   Newscoop\IngestPluginBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt  GNU GENERAL PUBLIC LICENSE Version 3
 */

namespace Newscoop\IngestParserSuperdeskContentAPIBundle\Parsers\IngestAdapters;

use Newscoop\IngestPluginBundle\Parsers\AbstractParser;

/**
 * Parser for the Superdesk Content API
 */
class SuperdeskContentApiParser extends AbstractParser
{
    /**
     * Parser name
     *
     * @var string
     */
    protected static $parserName = 'Superdesk Content API';

    /**
     * Parser description
     *
     * @var string
     */
    protected static $parserDescription = 'This parser can be used for interacting with a Superdesk Content API instance and works via the Ninjs format.';

    /**
     * Parser domain, can use basic regexp for matching
     *
     * @var string
     */
    protected static $parserDomain = '*';

    /**
     * Most likely superdesk package
     *
     * @var mixed
     */
    private $entry;

    //
}
