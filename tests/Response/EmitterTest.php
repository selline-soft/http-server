<?php
namespace Selline\HttpServer\Tests\Response;

use Selline\HttpServer\Response\Emitter;
use Selline\HttpServer\Tests\Response\Helpers\HeaderStack;

class EmitterTest extends AbstractEmitterTest
{
    protected function setUp(): void
    {
        HeaderStack::reset();
        $this->emitter = new Emitter();
    }
}