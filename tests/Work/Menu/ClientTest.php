<?php
/**
 * Created by PhpStorm.
 * User: XueSi
 * Email: <1592328848@qq.com>
 * Date: 2021/4/25
 * Time: 1:17
 */

namespace EasySwoole\WeChat\Tests\Work\Menu;


use EasySwoole\WeChat\Kernel\ServiceContainer;
use EasySwoole\WeChat\Tests\Mock\Message\Status;
use EasySwoole\WeChat\Tests\TestCase;
use EasySwoole\WeChat\Work\Menu\Client;
use Psr\Http\Message\ServerRequestInterface;

class ClientTest extends TestCase
{
    public function testGet()
    {
        $response = $this->buildResponse(Status::CODE_OK, $this->readMockResponseJson('get.json'));

        $app = $this->mockAccessToken(new ServiceContainer([
            'corpId' => 'mock_corpId',
            'corpSecret' => 'mock_corpSecret',
            'agentId' => 'mock_agentId'
        ]));

        $app = $this->mockHttpClient(function (ServerRequestInterface $request) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertEquals('/cgi-bin/menu/get', $request->getUri()->getPath());
            $this->assertEquals('agentid=mock_agentId&access_token=mock_access_token', $request->getUri()->getQuery());
        }, $response, $app);

        $client = new Client($app);

        $this->assertIsArray($client->get());

        $this->assertSame(json_decode($this->readMockResponseJson('get.json'), true), $client->get());
    }

    public function testCreate()
    {
        $response = $this->buildResponse(Status::CODE_OK, '{"errcode":0,"errmsg":"ok"}');

        $app = $this->mockAccessToken(new ServiceContainer([
            'corpId' => 'mock_corpId',
            'corpSecret' => 'mock_corpSecret',
            'agentId' => 'mock_agentId'
        ]));

        $app = $this->mockHttpClient(function (ServerRequestInterface $request) {
            $this->assertEquals('POST', $request->getMethod());
            $this->assertEquals('/cgi-bin/menu/create', $request->getUri()->getPath());
            $this->assertEquals('agentid=mock_agentId&access_token=mock_access_token', $request->getUri()->getQuery());
        }, $response, $app);

        $client = new Client($app);

        $data1 = [
            'button' => [
                [
                    'type' => 'click',
                    'name' => '????????????',
                    'key' => 'V1001_TODAY_MUSIC',
                ],
                [
                    'name' => '??????',
                    'sub_button' => [
                        [
                            'type' => 'view',
                            'name' => '??????',
                            'url' => 'http://www.soso.com/'],
                        [
                            'type' => 'click',
                            'name' => '???????????????',
                            'key' => 'V1001_GOOD',
                        ]

                    ]
                ]
            ]
        ];

        $this->assertTrue($client->create($data1));

        $data2 = [
            'button' => [
                [
                    'name' => '??????',
                    'sub_button' => [
                        [
                            'type' => 'scancode_waitmsg',
                            'name' => '???????????????',
                            'key' => 'rselfmenu_0_0',
                            'sub_button' => []
                        ],
                        [
                            'type' => 'scancode_push',
                            'name' => '???????????????',
                            'key' => 'rselfmenu_0_1',
                            'sub_button' => []
                        ],
                        [
                            'type' => 'view_miniprogram',
                            'name' => '?????????',
                            'pagepath' => 'pages/lunar/index',
                            'appid' => 'wx4389ji4kAAA'
                        ]
                    ]
                ],
                [
                    'name' => '??????',
                    'sub_button' => [
                        [
                            'type' => 'pic_sysphoto',
                            'name' => '??????????????????',
                            'key' => 'rselfmenu_1_0',
                            'sub_button' => []
                        ],
                        [
                            'type' => 'pic_photo_or_album',
                            'name' => '????????????????????????',
                            'key' => 'rselfmenu_1_1',
                            'sub_button' => []
                        ],
                        [
                            'type' => 'pic_weixin',
                            'name' => '??????????????????',
                            'key' => 'rselfmenu_1_2',
                            'sub_button' => []
                        ]
                    ]
                ],
                [
                    'name' => '????????????',
                    'type' => 'location_select',
                    'key' => 'rselfmenu_2_0',
                ]
            ]
        ];

        $this->assertTrue($client->create($data2));
    }

    public function testDelete()
    {
        $response = $this->buildResponse(Status::CODE_OK, '{"errcode":0,"errmsg":"ok"}');

        $app = $this->mockAccessToken(new ServiceContainer([
            'corpId' => 'mock_corpId',
            'corpSecret' => 'mock_corpSecret',
            'agentId' => 'mock_agentId'
        ]));

        $app = $this->mockHttpClient(function (ServerRequestInterface $request) {
            $this->assertEquals('GET', $request->getMethod());
            $this->assertEquals('/cgi-bin/menu/delete', $request->getUri()->getPath());
            $this->assertEquals('agentid=mock_agentId&access_token=mock_access_token', $request->getUri()->getQuery());
        }, $response, $app);

        $client = new Client($app);

        $this->assertTrue($client->delete());
    }

    protected function readMockResponseJson(string $filename): string
    {
        return file_get_contents(__DIR__ . '/mock_data/' . $filename);
    }
}