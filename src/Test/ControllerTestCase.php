<?php
namespace Api\Test;

use Api\Model\Travel\Category;

class ControllerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function buildUser()
    {
        $user = $this->getMockBuilder('Api\\Model\\User')
            ->setMethods(['getEmail', 'getPicture', 'getFirstName', 'getLastName', 'getId', 'isCreator', 'getCreated'])
            ->getMock();
        $user->method('getEmail')->willReturn('user1@example.com');
        $user->method('getPicture')->willReturn('http://example.com/user1.jpg');
        $user->method('getFirstName')->willReturn('User1');
        $user->method('getLastName')->willReturn('Tester');
        $user->method('getId')->willReturn(1);
        $user->method('isCreator')->willReturn(false);
        $user->method('getCreated')->willReturn(new \DateTime('2000-01-01'));
        return $user;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function buildCategory()
    {
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(1);
        $category->method('getName')->willReturn('test_category');
        return $category;
    }
}
