<?php


namespace EasySwoole\WeChat\Kernel;


use EasySwoole\WeChat\Kernel\Contracts\AccessTokenInterface;
use EasySwoole\WeChat\Kernel\Contracts\ClientInterface;
use EasySwoole\WeChat\Kernel\Exceptions\HttpException;
use EasySwoole\WeChat\Kernel\Psr\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

abstract class AccessToken implements AccessTokenInterface
{
    /** @var ServiceContainer  */
    protected $app;

    /** @var string  */
    protected $cachePrefix = 'easywechat.kernel.access_token.'; //modify by zhao

    /** @var string  */
    protected $requestMethod = 'GET';

    /** @var string  */
    protected $responseTokenKey = 'access_token';

    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
    }

    /**
     * @param bool $autoRefresh
     * @return string|null
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function getToken(bool $autoRefresh = true):? string
    {
        $token = $this->getCache()->get($this->getCacheKey(), null);
        if (!empty($token) || false === $autoRefresh) {
            $ret = json_decode($token, true);
            return $ret['access_token'];
        }
        $this->refresh();

        $token = $this->getCache()->get($this->getCacheKey(), null);
        if (!empty($token)) {
            $ret = json_decode($token, true);
            return $ret['access_token'];
        }
    }

    /**
     * @return $this|AccessTokenInterface
     * @throws HttpException
     * @throws InvalidArgumentException
     */
    public function refresh(): AccessTokenInterface
    {
        $response = $this->sendRefreshRequest();
        $this->checkResponse($response, $jsonData);

        $value = [
            'access_token' => $jsonData[$this->responseTokenKey],
            'expires_in' => $jsonData['expires_in']
        ];

        $this->getCache()->set(
            $this->getCacheKey(),
            json_encode($value),
            $jsonData['expires_in'] ?? (7200 - 30)
        );

        return $this;
    }

    /**
     * @return string
     */
    protected function getCacheKey():string
    {
        return $this->cachePrefix. md5($this->getCredentials());
    }

    /**
     * @return ResponseInterface
     */
    protected function sendRefreshRequest(): ResponseInterface
    {
        if ($this->requestMethod === 'GET') {
            return $this->getClient()->setMethod($this->requestMethod)->send($this->getEndpoint());
        }
        return $this->getClient()->setMethod($this->requestMethod)
            ->setBody(new Stream($this->getCredentials()))->send($this->getEndpoint());
    }

    /**
     * @param ResponseInterface $response
     * @param $parseData
     * @return bool
     * @throws HttpException
     */
    protected function checkResponse(ResponseInterface $response, &$parseData)
    {
        if (200 !== $response->getStatusCode()) {
            throw new HttpException(
                $response->getBody()->__toString(),
                $response
            );
        }

        $data = $this->parseData($response);
        $parseData = $data;

        if (isset($data['errcode']) && (int)$data['errcode'] !== 0) {
            throw new HttpException(
                "refresh access_token fail, message: ({$data['errcode']}) {$data['errmsg']}",
                $response,
                $data['errcode']
            );
        }

        return true;
    }

    /**
     * @param ResponseInterface $response
     * @return array
     * @throws HttpException
     */
    protected function parseData(ResponseInterface $response):array
    {
        $data = json_decode($response->getBody()->__toString(), true);
        if (is_null($data) || (JSON_ERROR_NONE !== json_last_error())) {
            throw new HttpException("parse response body fail.", $response);
        }
        return $data;
    }

    /**
     * @return CacheInterface
     */
    protected function getCache():CacheInterface
    {
        return $this->app[ServiceProviders::Cache];
    }

    /**
     * @return ClientInterface
     */
    protected function getClient():ClientInterface
    {
        return $this->app[ServiceProviders::HttpClientManager]->getClient();
    }

    abstract protected function getEndpoint():string;

    abstract protected function getCredentials():string;

    abstract protected function getCredentialsQuery():string;
}