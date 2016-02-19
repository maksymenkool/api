<?php
namespace Controller;

use Exception\ApiException;
use Model\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Test\ControllerTestCase;

class UserControllerTest extends ControllerTestCase
{
    private $userMapper;
    private $mailer;
    private $expStorage;

    /**
     * @var UserController
     */
    private $controller;

    private $testUser;

    public function setUp()
    {
        $this->userMapper = $this->getMockBuilder('Mapper\\DB\\UserMapper')
            ->setMethods(['insert', 'emailExists'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailer = $this->getMockBuilder('Service\\Mailer\\MailerService')
            ->setMethods(['sendAccountConfirmationMessage'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->expStorage = $this->getMockBuilder('ExpirableStorage')
            ->setMethods(['store'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new UserController(
            $this->userMapper,
            $this->mailer,
            $this->expStorage
        );

        $this->testUser = $this->buildUser();
    }

    /**
     * getUser
     */
    public function testGetUser()
    {
        $this->assertEquals(
            [
                'email' => 'user1@example.com',
                'picture' => 'http://example.com/user1.jpg',
                'firstName' => 'User1',
                'lastName' => 'Tester',
            ],
            $this->controller->getUser($this->testUser)
        );
    }

    /**
     * createUser
     */
    public function testCreateUser()
    {
        $json = json_encode([
            'email' => 'test@example.com',
            'password' => 'my_pass',
            'picture' => 'http://example.com/user.jpg',
            'firstName' => 'Simple',
            'lastName' => 'Tester',
        ]);

        $request = $this->getMock('Symfony\\Component\\HttpFoundation\\Request', ['getContent']);
        $request->method('getContent')->willReturn($json);

        $this->userMapper->method('emailExists')
            ->with('test@example.com')
            ->willReturn(true, false);

        $this->userMapper->expects($this->once())
            ->method('insert')
            ->with($this->callback(function(User $u) {
                return $u->getEmail() === 'test@example.com'
                    && $u->getFirstName() === 'Simple'
                    && $u->getLastName() === 'Tester'
                    && $u->getPicture() === 'http://example.com/user.jpg';
            }));

        $this->expStorage->expects($this->once())
            ->method('store')
            ->with('test@example.com')
            ->willReturn('test_token');

        $this->mailer->expects($this->once())
            ->method('sendAccountConfirmationMessage')
            ->with('test@example.com', 'test_token');

        try { // User exists
            $this->controller->createUser($request);
            $this->fail('No exception thrown');
        } catch (ApiException $e) {
            $this->assertEquals(ApiException::USER_EXISTS, $e->getCode());
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getHttpCode());
        }

        $this->assertEquals(new JsonResponse(), $this->controller->createUser($request));
    }

    public function testCreateUserValidation()
    {
        $json = json_encode([
            'ololo' => 'invalid stuff',
        ]);

        $request = $this->getMock('Symfony\\Component\\HttpFoundation\\Request', ['getContent']);
        $request->method('getContent')->willReturn($json);

        try {
            $this->controller->createUser($request);
            $this->fail();
        } catch (ApiException $e) {
            $this->assertEquals(ApiException::VALIDATION, $e->getCode());
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getHttpCode());
        }
    }
}
