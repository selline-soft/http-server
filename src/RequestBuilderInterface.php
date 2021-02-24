<?php
namespace Selline\HttpServer;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Interface RequestBuilderInterface
 *
 * @package Selline\HttpServer
 * @author Alexey Volkov <webwizardry@hotmail.com>
 */
interface RequestBuilderInterface
{
    /**
     * Создает новый объект запроса к серверу из текущих переменных среды.
     *
     * По умолчанию используется запрос GET, чтобы минимизировать риск исключения \InvalidArgumentException.
     * Включает заголовки текущего запроса, предоставленные сервером через getallheaders().
     * Если getallheaders() недоступен на текущем сервере, будет использован собственный метод getHeadersFromServer().
     * По умолчанию для тела запроса используется ввод php://.
     *
     * @throws InvalidArgumentException если не может быть определен действительный метод или URI
     * @return ServerRequestInterface
     */
    public function fromGlobals(): ServerRequestInterface;

    /**
     * Создает новый объект запроса к серверу из массивов.
     *
     * @param array $server $_SERVER или аналогичная структура
     * @param array $headers getallheaders() или аналогичная структура
     * @param array $cookie $_COOKIE или аналогичная структура
     * @param array $get $_GET или аналогичная структура
     * @param array|null $post $_POST или аналогичная структура, представляет тело запроса
     * @param array $files $_FILES или аналогичная структура
     * @param StreamInterface|resource|string|null $body поток ввода
     *
     * @throws InvalidArgumentException если не может быть определен действительный метод или URI
     * @return ServerRequestInterface
     */
    public function fromArrays(
        array $server,
        array $headers = [],
        array $cookie = [],
        array $get = [],
        ?array $post = null,
        array $files = [],
        $body = null
    ): ServerRequestInterface;

    /**
     * Get parsed headers from ($_SERVER) array.
     *
     * @param array $server typically $_SERVER or similar structure
     * @return array
     */
    public static function getHeadersFromServer(array $server): array;
}