<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Event\AddColumnEvent;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Settings\BackgroundSettings;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldColumnDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var BackgroundSettings
     */
    private $backgroundSettings;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param BackgroundSettings       $backgroundSettings
     */
    public function __construct(EventDispatcherInterface $dispatcher, BackgroundSettings $backgroundSettings)
    {
        $this->dispatcher         = $dispatcher;
        $this->backgroundSettings = $backgroundSettings;
    }

    /**
     * @param LeadField $leadField
     *
     * @throws AbortColumnCreateException
     * @throws NoListenerException
     */
    public function dispatchPreAddColumnEvent(LeadField $leadField)
    {
        $action = LeadEvents::LEAD_FIELD_PRE_ADD_COLUMN;

        if (!$this->dispatcher->hasListeners($action)) {
            throw new NoListenerException('There is no Listener for this event');
        }

        $shouldProcessInBackground = $this->backgroundSettings->shouldProcessColumnChangeInBackground();

        $event = new AddColumnEvent($leadField, $shouldProcessInBackground);

        $this->dispatcher->dispatch($action, $event);

        if ($event->shouldProcessInBackground()) {
            throw new AbortColumnCreateException('Column change will be processed in background job');
        }
    }
}
