<?php
declare(strict_types=1);

namespace TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;

/**
 * Test application for Inspector plugin tests.
 */
class Application extends BaseApplication
{
    /**
     * @return void
     */
    #[\Override]
    public function bootstrap(): void
    {
        $this->addPlugin('Crustum/Inspector', [
            'path' => ROOT . DS,
            'bootstrap' => true,
        ]);

        parent::bootstrap();
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue Middleware queue.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }
}
