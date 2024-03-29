<?php
// Routing

$to_date = function (string $date) {
    return new DateTime($date);
};

$to_int = function (string $val) {
    return intval($val);
};

/** @var $app Api\Application */

$app->get('/user/{author_id}/travels', 'controller.travel:getPublishedByAuthor')
    ->convert('author_id', $to_int)
    ->bind('travel-published-by-author');

$app->post('/user', 'controller.user:createUser')
    ->bind('create-user');

$app->get('/user', 'controller.user:getUser');

$app->post('/email/confirm/{token}', 'controller.user:confirmEmail')
    ->bind('confirm-email');

$app->post('/password/reset/{token}', 'controller.user:resetPassword')
    ->bind('reset-password');

$app->post('/password/link/{email}', 'controller.user:sendPasswordResetLink')
    ->bind('send-password-reset-link');

$app->post('/token', 'controller.auth:create')
    ->bind('create-token');

$app->get('/uber/price/{lat1}/{lon1}/{lat2}/{lon2}', 'controller.uber:getPriceEstimate');

$app->get('/stats', 'controller.booking:getStats');

$app->post('/category', 'controller.categories:createCategory')
    ->bind('create-category');

$app->get('/categories', 'controller.categories:getCategories') //TODO : remove in version 3.0
    ->bind('travel-category');

$app->get('/travel/categories', 'controller.categories:getCategories')
    ->bind('travel-category-new');

$app->get('/travel/by-user', 'controller.travel:getMyTravels');

$app->get('/travel/by-category/{name}', 'controller.travel:getTravelsByCategory')
    ->bind('travel-by-category');

$app->get('/travel/featured', 'controller.travel:getFeatured');

$app->get('/travel/favorite', 'controller.travel:getFavorites');

$app->post('/travel/{id}/favorite', 'controller.travel:addFavorite')
    ->convert('id', $to_int);

$app->delete('/travel/{id}/favorite', 'controller.travel:removeFavorite')
    ->convert('id', $to_int);

$app->post('/travel/comment/{id}/flag', function () {
    return [];
})// TODO Implement flagging
->convert('id', $to_int);

$app->get('/travel/{id}/comments', 'controller.comment:getAllByTravelId')
    ->convert('id', $to_int)
    ->bind('travel-comment');

$app->post('/travel/{id}/comment', 'controller.comment:createTravelComment')
    ->convert('id', $to_int);

$app->post('/travel/{id}/book', 'controller.booking:registerBooking')
    ->convert('id', $to_int);

$app->get('/travel/search', 'controller.travel:searchTravels')
    ->bind('travel-search');

$app->delete('/travel/comment/{id}', 'controller.comment:deleteById')
    ->convert('id', $to_int);

$app->get('/travel/{id}', 'controller.travel:getTravel')
    ->convert('id', $to_int)
    ->bind('travel-by-id');

$app->put('/travel/{id}', 'controller.travel:updateTravel')
    ->convert('id', $to_int);

$app->delete('/travel/{id}', 'controller.travel:deleteTravel')
    ->convert('id', $to_int);

$app->post('/travel', 'controller.travel:createTravel');

$app->post('/image', 'controller.image:upload');

$app->post('/hotel/search/{location}/{in}/{out}/{rooms}', 'controller.wego:startSearch')
    ->convert('in', $to_date)
    ->convert('out', $to_date)
    ->convert('rooms', $to_int);

$app->get('/hotel/search-results/{id}/{page}', 'controller.wego:getSearchResults')
    ->convert('page', $to_int);

$app->get('/healthCheck', 'controller.health:healthCheck')
    ->bind('health-check');

$app->get('/version/{version}', 'controller.client:version')
    ->bind('version');
