<?php

/**
 * @package   Newscoop\IngestParserSuperdeskContentAPIBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2015 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt  GNU GENERAL PUBLIC LICENSE Version 3
 */

namespace Newscoop\IngestParserSuperdeskContentAPIBundle\Parsers\IngestAdapters;

use Newscoop\IngestPluginBundle\Parsers\AbstractParser;
use Newscoop\IngestPluginBundle\Entity\Feed;
use Newscoop\IngestParserSuperdeskContentAPIBundle\Client\GuzzleClient;
use Superdesk\ContentApiSdk\ContentApiSdk;
use Superdesk\ContentApiSdk\Client\ClientInterface;
use Superdesk\ContentApiSdk\Exception\ContentApiException;
use Superdesk\ContentApiSdk\Data\Package;
use Newscoop\NewscoopException;

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
     * Specify how the parser handles sections
     *
     * 0: Not at all (user must select one)
     * 1: Partial (parser tries to find one, uses user selection as fallback)
     * 2: Full (parser handles all, user cant select section)
     *
     * @var int
     */
    protected static $parserHandleSection = self::SECTION_HANDLING_PARTIAL;

    const DEFAULT_CATEGORY = 'main';
    const TYPE_TEXT = 'text';
    const TYPE_PICTURE = 'picture';

    /**
     * Most likely superdesk package
     *
     * @var \Superdesk\ContentApiSdk\Data\Package
     */
    private $package;

    private $sectionConversion = array(
        'politics (general)' => 10, // politics
        'economy, business and finance' => 20, // business
        'science and technology' => 30, // Sci/Tech
        'health' => 40, // Health
        'entertainment (general)' => 50, // Entertainment
        'sport' => 60, // Sport
    );

    /**
     * Get all feed entries as a parser instance
     *
     * @param \Newscoop\IngestPluginBundle\Entity\Feed $feedEntity Feed entity
     *
     * @return array Array should contain feed entries
     */
    public static function getStories(Feed $feedEntity)
    {
        $entries = array();
        $data = array();
        $clientConfig = array(
            'base_uri' => $feedEntity->getUrl(),
            'options' => ''
        );
        $client = new GuzzleClient($clientConfig);
        $sdk = new ContentApiSdk($client);
        $parameters = array(
            // 'start_date' => date('Y-m-d', strtotime('-7 days'))
            'start_date' => '2015-10-25'
        );

        try {
            $data = $sdk->getPackages($parameters, true);
        } catch(ContentApiException $e) {
            throw new NewscoopException($e->getMessage(), $e->getCode(), $e);
        }

        // Convert all $data into entryes
        foreach ($data as $package) {

            $entryPackage = new SuperdeskContentApiParser($package);
            $images = $entryPackage->getImages();

            if (
                property_exists($package->associations, 'main') &&
                empty($images)
            ) {
                continue;
            }

            $entries[] = $entryPackage;
        }

        return $entries;
    }

    /**
     * Initialize object with simpe pie entry
     *
     * @param \SimplePie_Item $feedEntry Feed entry
     */
    public function __construct(Package $package)
    {
        parent::__construct();
        $this->package = $package;
    }

    /**
     * Get ID for this entry (NewsItemIdentifier in \Article)
     *
     * @return string
     */
    public function getNewsItemId()
    {
        return $this->package->getId();
    }

    /**
     * Get date id for this article
     *
     * @return string
     */
    public function getDateId()
    {
        return '';
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->package->language;
    }

    /**
     * Get title (HeadLine in \Article)
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->package->headline;
    }

    /**
     * Get content (DataContent in \Article)
     *
     * @return string
     */
    public function getContent()
    {
        $content = '';
        $texts = $this->getAssociationsByType(self::DEFAULT_CATEGORY, self::TYPE_TEXT);

        if (is_array($texts)) {
            foreach ($texts as $textItem) {
                $content[] = $textItem->body_html;
            }
        }

        return (is_array($content)) ? implode('<hr>', $content) : $content;
    }

    /**
     * Get catchline (NewsLineText in \Article)
     *
     * @return string|null
     */
    public function getCatchline()
    {
        $catchline = '';
        $texts = $this->getAssociationsByType(self::DEFAULT_CATEGORY, self::TYPE_TEXT);

        if (is_array($texts) && count($texts) > 0) {
            $catchline = $texts[0]->headline;
        }

        return $catchline;
    }

    /**
     * Get summary (DataLead in \Article)
     *
     * @return string|null
     */
    public function getSummary()
    {
        $catchline = '';
        $texts = $this->getAssociationsByType(self::DEFAULT_CATEGORY, self::TYPE_TEXT);

        if (is_array($texts) && count($texts) > 0) {
            $catchline = $texts[0]->headline;
        }

        return $catchline;
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return new \DateTime($this->package->versioncreated);
    }

    /**
     * Get updated
     *
     * @return DateTime|null
     */
    public function getUpdated()
    {
        if ($this->package->version == 'v1') {
            return null;
        } else {
            return new \DateTime($this->package->versioncreated);
        }
    }

    /**
     * Get lift embargo date
     *
     * @return DateTime|null
     */
    public function getLiftEmbargo()
    {
        return null;
    }

    /**
     * Get published
     *
     * @return DateTime|null
     */
    public function getPublished()
    {
        return new \DateTime($this->package->versioncreated);
    }

    /**
     * Get product (NewsProduct in \Article)
     *
     * @return string|null
     */
    public function getProduct()
    {
        return null;
    }

    /**
     * Get instruction, mainly for NewsML, but can be implemented in other feed
     * types aswell. Allowed values are (should all be in lowercase):
     *     update, rectify, delete or the null value
     *
     * @return null|string
     */
    public function getInstruction()
    {
        switch ($this->getStatus()) {
            case 'cancelled':
                $instruction = 'delete';
                break;
            default:
                $instruction = null;
                break;
        }

        return $instruction;
    }

    /**
     * Get status
     *
     * @return string (Defaults to: usable)
     */
    public function getStatus()
    {
        return $this->package->pubstatus;
    }

    /**
     * Get priority (Urgency in \Article)
     *
     * @return string|int|null
     */
    public function getPriority()
    {
        return $this->package->urgency;
    }

    /**
     * Get keywords (Keywords in \Article)
     *
     * @return array Each entry in the array should be a seperate keyword
     */
    public function getKeywords()
    {
        $keywords = array();
        $subjects = $this->package->subject;

        if (is_array($subjects)) {
            foreach ($subjects as $subject) {
                $keywords[] = $subject->name;
            }
        }

        return $keywords;
    }

    /**
     * Get Section (Section in \Article). Through this method one can determine
     * the section for this entry by data in the feed. Return null if on needed,
     * then the secion will be determined on a higher level.
     *
     * @return null|\Newscoop\Entity\Section
     */
    public function getSection()
    {
        $section = null;
        $subjects = $this->package->subject;

        if (is_array($subjects)) {
            foreach ($subjects as $subject) {
                if (isset($this->sectionConversion[$subject->name])) {
                    $section = $this->sectionConversion[$subject->name];
                    break;
                }
            }
        }

        return $section;
    }

    /**
     * Get authors
     *
     * @return array The array must have 4 keys: firstname, lastename, email, link
     */
    public function getAuthors()
    {
        $authors = array();

        // Code below uses $this->package->person (rel = author)
        // if (is_array($this->package->person)) {
        //     foreach ($this->package->person as $person) {
        //         if ($person->rel == 'author') {
        //             $authors[] = $this->readName($person->name);
        //         }
        //     }
        // }

        // We decided to use the byline field for author information
        $texts = $this->getAssociationsByType(self::DEFAULT_CATEGORY, self::TYPE_TEXT);

        foreach ($texts as $textItem) {
            $authors[] = $this->readName($textItem->byline);
        }

        return $authors;
    }

    /**
     * Get images
     *
     * @return array Each entry should be an array. The array must have at least
     *               one key: location (to the image). Possible other keys are:
     *               description, copyright, photographer.
     */
    public function getImages()
    {
        $images = array();
        $imagesRaw = $this->getAssociationsByType(self::DEFAULT_CATEGORY, self::TYPE_PICTURE);

        foreach ($imagesRaw as $imageRaw) {

            if ($imageRaw->pubstatus != 'usable') continue;

            $images[] = array(
                'location' => $imageRaw->renditions->baseImage->href,
                'description' => $imageRaw->description_text,
                'copyright' => $imageRaw->byline,
                'photographer' => $imageRaw->byline
            );
        }

        return $images;
    }

    /**
     * Returns link to the Article
     *
     * @return string|null
     */
    public function getLink()
    {
        // Not usable in site, since its API url
        return $this->package->uri;
    }

    /**
     * Get associations by category and apply optional type filter
     *
     * @param  string $category Association category
     * @param  string|null $type Type filter for returned assiciations
     *
     * @return array List of associations
     */
    private function getAssociationsByType($category, $type=null)
    {
        $associations = array();

        if (property_exists($this->package, 'associations')) {
            if (property_exists($this->package->associations, $category)) {
                foreach ($this->package->associations->$category as $association) {
                    if (!is_null($association) && property_exists($association, 'type')) {
                        if (!is_null($type)) {
                            if ($association->type == $type) {
                                $associations[] = $association;
                            }
                        } else {
                            $associations[] = $association;
                        }
                    }
                }
            }
        }

        return $associations;
    }
}
