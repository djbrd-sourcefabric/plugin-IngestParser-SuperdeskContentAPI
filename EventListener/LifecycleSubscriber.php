<?php
/**
 * @package   Newscoop\IngestParserSuperdeskContentAPIBundle
 * @author    Mischa Gorinskat <mischa.gorinskat@sourcefabric.org>
 * @copyright 2015 Sourcefabric o.p.s.
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\IngestParserSuperdeskContentAPIBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Newscoop\EventDispatcher\Events\GenericEvent;
use Newscoop\Services\Plugins\PluginsService;

/**
 * Event lifecycle management
 */
class LifecycleSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Newscoop\Services\Plugins\PluginsService
     */
    private $pluginsService;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    private $translator;

    /**
     * @param EntityManager  $em
     * @param PluginsService $pluginsService
     * @param Translator     $translator
     */
    public function __construct(EntityManager $em, PluginsService $pluginsService, Translator $translator) {
        $this->em = $em;
        $this->pluginsService = $pluginsService;
        $this->translator = $translator;
    }

    public function install(GenericEvent $event)
    {
    }

    public function update(GenericEvent $event)
    {
    }

    public function remove(GenericEvent $event)
    {
    }

    public static function getSubscribedEvents()
    {
        return array(
            'plugin.install.newscoop_ingest_parser_superdesk_content_api_bundle' => array('install', 1),
            'plugin.update.newscoop_ingest_parser_superdesk_content_api_bundle' => array('update', 1),
            'plugin.remove.newscoop_ingest_parser_superdesk_content_api_bundle' => array('remove', 1),
        );
    }
}
