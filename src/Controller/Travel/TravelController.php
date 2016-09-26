<?php

namespace Api\Controller\Travel;

use Api\Controller\ApiController;
use Api\Exception\ApiException;
use Api\JSON\DataObject;
use Api\Mapper\DB\ActionMapper;
use Api\Mapper\DB\BannerMapper;
use Api\Mapper\DB\CategoryMapper;
use Api\Mapper\DB\TravelMapper;
use Api\Model\Travel\Action;
use Api\Model\Travel\Travel;
use Api\Model\User;
use Api\Security\Access\AccessManager;
use Api\Security\Access\Action as AccessAction;
use Symfony\Component\HttpFoundation\Request;

/**
 * Travel API controller
 */
class TravelController extends ApiController
{
    /**
     * @var TravelMapper
     */
    private $travel_mapper;

    /**
     * @var CategoryMapper
     */
    private $category_mapper;

    /**
     * @var ActionMapper
     */
    private $action_mapper;

    /**
     * @var BannerManager
     */
    private $banner_manager;

    /**
     * @var AccessManager
     */
    private $access_manager;

    /**
     * TravelController constructor.
     * @param TravelMapper   $travel_mapper
     * @param CategoryMapper $category_mapper
     * @param ActionMapper   $action_mapper
     * @param BannerMapper   $banner_mapper
     * @param AccessManager  $access_manager
     */
    public function __construct(
        TravelMapper $travel_mapper,
        CategoryMapper $category_mapper,
        ActionMapper $action_mapper,
        BannerMapper $banner_mapper,
        AccessManager $access_manager
    ) {
        $this->travel_mapper = $travel_mapper;
        $this->category_mapper = $category_mapper;
        $this->action_mapper = $action_mapper;
        $this->banner_manager = $banner_mapper;
        $this->access_manager = $access_manager;
    }

    /**
     * @param Request $request
     * @param User    $user
     * @return array
     */
    public function createTravel(Request $request, User $user): array
    {
        $json = DataObject::createFromString($request->getContent());

        $travel = new Travel();
        $travel->setAuthor($user);
        $travel->setTitle($json->getString('title'));
        $travel->setDescription($json->getString('description'));
        if ($json->has('image')) {
            $travel->setImage($json->get('image'));
        }
        if ($json->has('creation_mode')) {
            $travel->setCreationMode($json->get('creation_mode'));
        }
        $this->travel_mapper->insert($travel);
        
        $actions = $this->createActions((array) $json->get('content'), $travel->getId());
        $travel->setActions($actions);
        $this->action_mapper->insertActions($travel->getActions());

        if ($json->has('category_id')) { //TODO: remove in version 2.0 #126
            $ids = (array) $json->get('category_id');
            $this->category_mapper->setTravelCategories($travel->getId(), $ids);
        }
        if ($json->has('category_ids')) {
            $this->category_mapper->setTravelCategories($travel->getId(), $json->getArrayOf('integer', 'category_ids'));
        }
        if ($json->has('published')) {
            $travel->setPublished($json->get('published'));
        }

        return ['id' => $travel->getId()];
    }

    /**
     * @param int  $id
     * @param User $user
     * @return array
     * @throws ApiException
     */
    public function getTravel(int $id, User $user = null): array
    {
        $travel = $this->travel_mapper->fetchById($id);
        if (!$travel) {
            throw new ApiException('Travel not found', ApiException::RESOURCE_NOT_FOUND);
        }
        $favorite_ids = $user ? $this->travel_mapper->fetchFavoriteIds($user->getId()) : [];
        return $this->buildTravelView($travel, array_key_exists($travel->getId(), $favorite_ids));
    }

    /**
     * @param User $user
     * @param int  $limit
     * @param int  $offset
     * @return array
     */
    public function getMyTravels(User $user, int $limit = 10, int $offset = 0): array
    {
        $travels = $this->travel_mapper->fetchByAuthorId($user->getId(), $limit, $offset);
        return $this->buildTravelSetView($travels);
    }

    /**
     * @param int  $id
     * @param User $user
     * @return array
     */
    public function addFavorite(int $id, User $user): array
    {
        $this->travel_mapper->addFavorite($id, $user->getId());
        return [];
    }

    /**
     * @param int  $id
     * @param User $user
     * @return array
     */
    public function removeFavorite(int $id, User $user): array
    {
        $this->travel_mapper->removeFavorite($id, $user->getId());
        return [];
    }

    /**
     * @param User $user
     * @return array
     */
    public function getFavorites(User $user): array
    {
        $travels = $this->travel_mapper->fetchFavorites($user->getId());
        return $this->buildTravelSetView($travels);
    }

    /**
     * @return array
     */
    public function getFeatured(): array
    {
        $result = [
            'banners' => $this->banner_manager->fetchBanners()
        ];
        $featured_category_names = $this->category_mapper->fetchFeaturedCategoryNames();
        $featured_categories = [];
        foreach ($featured_category_names as $name) {
            $travels = $this->travel_mapper->fetchPublishedByCategory($name, 5, 0);
            $featured_categories[] = [
                'title'   => $name,
                'travels' => $this->buildTravelSetView($travels, [], true),
                'category' => $name,
            ];
        }
        $result['categories'] = $featured_categories;
        return $result;
    }

   /**
     * @param int   $author_id
     * @param User  $user
     * @param int   $limit
     * @param int   $offset
     * @return array
     */
    public function getPublishedByAuthor(int $author_id, User $user = null, int $limit = 10, int $offset = 0): array
    {
        $travels = $this->travel_mapper->fetchPublishedByAuthorId($author_id, $limit, $offset);
        $minimized = true;
        $favorite_ids = $user ? $this->travel_mapper->fetchFavoriteIds($user->getId()) : [];
        return $this->buildTravelSetView($travels, $favorite_ids, $minimized);
    }

    /**
     * @param string $name
     * @param User   $user
     * @param int    $limit
     * @param int    $offset
     * @return array
     */
    public function getTravelsByCategory(string $name, User $user = null, int $limit = 10, int $offset = 0): array
    {
        $travels = $this->travel_mapper->fetchPublishedByCategory($name, $limit, $offset);
        $favorite_ids = $user ? $this->travel_mapper->fetchFavoriteIds($user->getId()) : [];
        return $this->buildTravelSetView($travels, $favorite_ids);
    }

    /**
     * @param int     $id
     * @param Request $request
     * @param User    $user
     * @return array
     */
    public function updateTravel(int $id, Request $request, User $user): array
    {
        $travel = $this->getTravelForModification($id, $user);
        $json = DataObject::createFromString($request->getContent());
        if ($json->has('title')) {
            $travel->setTitle($json->getString('title'));
        }
        if ($json->has('description')) {
            $travel->setDescription($json->getString('description'));
        }
        if ($json->has('content')) {
            $actions = $this->createActions((array) $json->get('content'), $id);
            $travel->setActions($actions);
            $this->action_mapper->deleteTravelActions($id);
            $this->action_mapper->insertActions($travel->getActions());
        }
        if ($json->has('image')) {
            $travel->setImage($json->get('image'));
        }
        if ($json->has('published')) {
            $travel->setPublished($json->get('published'));
        }
        if ($json->has('creation_mode')) {
            $travel->setCreationMode($json->get('creation_mode'));
        }
        if ($json->has('category_id')) { //TODO: remove in version 2.0 #126
            $ids = (array) $json->get('category_id');
            $this->category_mapper->setTravelCategories($travel->getId(), $ids);
        }
        if ($json->has('category_ids')) {
            $this->category_mapper->setTravelCategories($travel->getId(), $json->getArrayOf('integer', 'category_ids'));
        }
        $this->travel_mapper->update($travel);

        return [];
    }


    /**
     * @param int  $id
     * @param User $user
     * @return array
     */
    public function deleteTravel(int $id, User $user): array
    {
        $travel = $this->getTravelForModification($id, $user);
        $this->travel_mapper->delete($travel->getId());
        return [];
    }

    /**
     * @param int  $id
     * @param User $user
     * @return Travel
     * @throws ApiException
     */
    private function getTravelForModification(int $id, User $user): Travel
    {
        $travel = $this->travel_mapper->fetchById($id);
        if (!$travel) {
            throw new ApiException('Travel not found', ApiException::RESOURCE_NOT_FOUND);
        }
        if ($this->access_manager->isGranted($user, AccessAction::WRITE, $travel)) {
            return $travel;
        }
        throw new ApiException('Access denied', ApiException::ACCESS_DENIED);
    }

    /**
     * @param Travel $travel
     * @param bool   $is_favorited
     * @param bool   $minimized
     * @return array
     */
    private function buildTravelView(Travel $travel, bool $is_favorited, bool $minimized = false): array
    {
        $view = [];
        $view['id'] = $travel->getId();
        $view['title'] = $travel->getTitle();
        if (!$minimized) {
            $actions = $travel->getActions();
            $view['description'] = $travel->getDescription();
            $view['content'] = count($actions) ? $this->buildActionsView($actions) : $travel->getContent();
            $view['created'] = $travel->getCreated()->format(self::DATETIME_FORMAT);
            $view['category'] = $travel->getCategoryIds() ? $travel->getCategoryIds()[0] : null;
            $view['category_ids'] = $travel->getCategoryIds();
            $view['published'] = $travel->isPublished();
            $view['creation_mode'] = $travel->getCreationMode();

            $author = $travel->getAuthor();
            if ($author) {
                $view['author'] = [
                    'id'        => $author->getId(),
                    'firstName' => $author->getFirstName(),
                    'lastName'  => $author->getLastName(),
                    'picture'   => $author->getPicture(),
                ];
            }
        }
        $view['is_favorited'] = $is_favorited;
        if (count($travel->getContent())) { // TODO Refactor this logic. Move to Travel?
            $travel->setActions($this->createActions($travel->getContent(), $travel->getId()));
        }
        $view['image'] = $travel->getImage();
        $view['places_count'] = count($travel->getActions());
        $view['days_count'] = $travel->getDaysCount();

        return $view;
    }

   /**
     * @param Action[] $actions
     * @return array[]
     */
    private function buildActionsView(array $actions): array
    {
        $actions_json = [];
        foreach ($actions as $action) {
            $actions_json[] = $this->buildActionView($action);
        }
        return $actions_json;
    }

    /**
     * @param Action $action
     * @return array
     */
    private function buildActionView(Action $action): array
    {
        return [
            'id'            => $action->getId(),
            'offsetStart'   => $action->getOffsetStart(),
            'offsetEnd'     => $action->getOffsetEnd(),
            'car'           => $action->getCar(),
            'airports'      => $action->getAirports(),
            'hotels'        => $action->getHotels(),
            'sightseeings'  => $action->getSightseeings(),
            'type'          => $action->getType()
        ];
    }

    /**
     * @param Travel[] $travels
     * @param array    $favorite_ids
     * @param bool     $minimized
     * @return array
     */
    private function buildTravelSetView(array $travels, array $favorite_ids = [], bool $minimized = false): array
    {
        $view = [];
        foreach ($travels as $travel) {
            $view[] = $this->buildTravelView($travel, array_key_exists($travel->getId(), $favorite_ids), $minimized);
        }
        return $view;
    }

    /**
     * @param DataObject[]  $action_objects
     * @param int           $travel_id
     * @return Action[]
     */
    private function createActions(array $action_objects, int $travel_id): array
    {
        $actions = [];
        foreach ($action_objects as $action) {
            $actions[] = $this->createAction(new DataObject($action), $travel_id);
        }
        return $actions;
    }

    /**
     * @param DataObject    $object
     * @param int           $travelId
     * @return Action
     */
    private function createAction(DataObject $object, int $travelId): Action
    {
        $action = new Action();
        $action->setTravelId($travelId);
        if ($object->has('offsetStart')) {
            $action->setOffsetStart($object->get('offsetStart', 'integer'));
        }
        if ($object->has('offsetEnd')) {
            $action->setOffsetEnd($object->get('offsetEnd', 'integer'));
        }
        if ($object->has('car')) {
            $action->setCar($object->get('car'));
        } else {
            $action->setCar(false);
        }
        if ($object->has('airports')) {
            $action->setAirports($object->get('airports'));
        }
        if ($object->has('hotels')) {
            $action->setHotels($object->get('hotels'));
        }
        if ($object->has('sightseeings')) {
            $action->setSightseeings($object->get('sightseeings'));
        }
        if ($object->has('type')) {
            $action->setType($object->get('type'));
        }
        return $action;
    }
}
