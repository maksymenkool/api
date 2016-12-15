<?php
namespace Api\Service;

use Api\Event\UpdatePicEvent;
use Api\Mapper\DB\UserMapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserPicUpdater implements EventSubscriberInterface
{
    /**
     * @var UserMapper
     */
    private $user_mapper;

    /**
     * @var ImageCopier
     */
    private $image_copier;

    /**
     * UserPicUpdater constructor.
     * @param UserMapper  $user_mapper
     * @param ImageCopier $image_copier
     */
    public function __construct(
        UserMapper $user_mapper,
        ImageCopier $image_copier
    ) {
        $this->user_mapper = $user_mapper;
        $this->image_copier = $image_copier;
    }

    /**
     * Update the user avatar.
     *
     * @param UpdatePicEvent $event
     */
    public function updateUserPic(UpdatePicEvent $event)
    {
        $this->user_mapper->updatePic($event->getUserId(), $this->image_copier->copyFrom($event->getPicUrl()));
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [UpdatePicEvent::UPDATE_USER_PIC => 'updateUserPic'];
    }
}
