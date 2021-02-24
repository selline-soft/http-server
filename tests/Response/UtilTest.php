<?php
namespace Selline\HttpServer\Tests\Response;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Selline\HttpServer\Response\Emitter;
use Selline\HttpServer\Tests\Response\Helpers\HeaderStack;
use Psr\Http\Message\StreamInterface;
use Selline\Http\Response;
use Selline\HttpServer\Response\Util;

class UtilTest extends TestCase
{
    use ProphecyTrait;

    protected Emitter $emitter;

    protected function setUp(): void
    {
        HeaderStack::reset();

        $this->emitter = new Emitter();
    }

    public function testEmitsResponseHeaders(): void
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        $response = Util::injectContentLength($response);

        \ob_start();
        $this->emitter->emit($response);
        \ob_end_clean();

        self::assertTrue(HeaderStack::has('HTTP/1.1 200 OK'));
        self::assertTrue(HeaderStack::has('Content-Type: text/plain'));
        self::assertTrue(HeaderStack::has('Content-Length: 8'));
    }

    public function testDoesNotInjectContentLengthHeaderIfStreamSizeIsUnknown(): void
    {
        $stream = $this->prophesize(StreamInterface::class);
        $stream->__toString()->willReturn('Content!');
        $stream->getSize()->willReturn(null);

        $response = (new Response())
            ->withStatus(200)
            ->withBody($stream->reveal());

        $response = Util::injectContentLength($response);

        \ob_start();
        $this->emitter->emit($response);
        \ob_end_clean();

        foreach (HeaderStack::stack() as $header) {
            self::assertStringNotContainsStringIgnoringCase('Content-Length:', $header['header']);
        }
    }

    public function testCloseOutputBuffersWithFlush(): void
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        \ob_start();
        $this->emitter->emit($response);

        self::assertSame(2, \ob_get_level());
        // flush
        Util::closeOutputBuffers(1, true);

        self::assertSame(1, \ob_get_level());
    }

    public function testCloseOutputBuffersWithClean(): void
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        \ob_start();
        $this->emitter->emit($response);

        $content = \ob_get_contents(); //'Content!'

        // clear
        Util::closeOutputBuffers(1, false);

        self::assertNotSame(\ob_get_contents(), $content);
    }
}