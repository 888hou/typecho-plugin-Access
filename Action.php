<?php
require_once __DIR__ . '/Access_Bootstrap.php';

class Access_Action implements Widget_Interface_Do
{

    private $response;
    private $request;
    private $access;

    public function __construct()
    {
        $this->response = Typecho_Response::getInstance();
        $this->request = Typecho_Request::getInstance();
        $this->access = new Access_Core();
    }

    public function execute()
    {}

    public function action()
    {}

    public function writeLogs()
    {
        $image = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAQUAP8ALAAAAAABAAEAAAICRAEAOw==');
        $this->response->setContentType('image/gif');
        if ($this->access->config->writeType == 1) {
            $this->access->writeLogs(null, $this->request->u, $this->request->cid, $this->request->mid);
        }
        echo $image;
    }

    public function ip()
    {
        $this->response->setContentType('application/json');
        $ip = $this->request->get('ip');
        try {
            $this->checkAuth();
            $response = Access_Ip::find($ip);
            if (is_array($response)) {
                $response = array(
                    'code' => 0,
                    'data' => implode(' ', $response),
                );
            } else {
                throw new Exception('解析ip失败');
            }
        } catch (Exception $e) {
            try {
                $http = Typecho_Http_Client::get();
                $result = $http->send('https://tools.keycdn.com/geo.json?host=' . $ip);
                $result = Json::decode($result, true);
                if ($result['status'] == 'success') {
                    $response = array(
                        'code' => 0,
                        'data' => $result['data']['geo']['country_name'] . ' ' . $result['data']['geo']['city'],
                    );
                }
            } catch (Exception $e) {
                $response = array(
                    'code' => 100,
                    'data' => '很抱歉，ipip.net查询无结果，同时你的服务器无法连接fallback接口(tools.keycdn.com)',
                );
            }
        }
        exit(Json::encode($response));
    }

    public function deleteLogs()
    {
        try {
            $this->checkAuth();
            $data = @file_get_contents('php://input');
            $data = Json::decode($data, true);
            if (!is_array($data)) {
                throw new Exception('params invalid');
            }
            $this->access->deleteLogs($data);
            $response = array(
                'code' => 0,
            );

        } catch (Exception $e) {
            $response = array(
                'code' => 100,
                'data' => $e->getMessage(),
            );
        }

        $this->response->throwJson($response);
    }

    protected function checkAuth()
    {
        if (!$this->access->isAdmin()) {
            throw new Exception('Access Denied');
        }
    }

}
