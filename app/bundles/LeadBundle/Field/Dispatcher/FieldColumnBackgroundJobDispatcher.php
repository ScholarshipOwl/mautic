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
use Mautic\LeadBundle\Field\Event\AddColumnBackgroundEvent;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldColumnBackgroundJobDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param LeadField $leadField
     *
     * @throws AbortColumnCreateException
     * @throws NoListenerException
     */
    public function dispatchPreAddColumnEvent(LeadField $leadField)
    {
        $action = LeadEvents::LEAD_FIELD_PRE_ADD_COLUMN_BACKGROUND_JOB;

        if (!$this->dispatcher->hasListeners($action)) {
            throw new NoListenerException('There is no Listener for this event');
        }

        $event = new AddColumnBackgroundEvent($leadField);

        $this->dispatcher->dispatch($action, $event);

        if ($event->isPropagationStopped()) {
            throw new AbortColumnCreateException('Column cannot be created now');
        }
    }
}
