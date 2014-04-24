<?php

require 'rb.php';
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

// 初始化数据库连接，请修改数据库名称和密码
R::setup('mysql:host=localhost;dbname=mysql','root','111111');
R::freeze(true);

$app = new \Slim\Slim();

// GET route
$app->get(
'/',
function () {
    echo '<p>Hello Slim</p>';
    echo '<p>Just For Test</p>';}
);

// GET /leds
$app->get('/leds/status', function () use ($app) { 
    
    // 设置分页
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $rows = isset($_GET['rows']) ? intval($_GET['rows']) : 10;
    $offset = ($page-1)*$rows;

    // 查找所有设备
    $sql = 'select * from leds limit ' . $offset . ', ' . $rows;
    
    // $led_array = R::getAll('select * from leds');
    $led_array = R::getAll($sql);
    $app->response()->header('Content-Type', 'application/json');
    // 按照JSON格式输出
    echo json_encode( $led_array , JSON_NUMERIC_CHECK);
});

// GET /leds/:id
$app->get('/leds/:id', function ($id) use ($app) { 

    try {
        // 查询数据库，只返回status状态
        $led_single = R::getRow('select status from leds where id = :id',array(':id'=>$id));
        
        if ($led_single) {
            $app->response()->header('Content-Type', 'application/json');
            // 按照JSON格式输出
            echo json_encode( $led_single, JSON_NUMERIC_CHECK);
        } 
        else {
            $app->response()->status(404);
        }
    } 
    catch (ResourceNotFoundException $e) {
        $app->response()->status(404);
    } 
    catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

// POST /leds
$app->post('/leds', function () use ($app) {    
    try {
        // get and decode JSON request body
        $request = $app->request();
        $body = $request->getBody();
        $input = json_decode($body); 
        
        // store led record
        $led = R::dispense('leds');
        //$led->id = (int)$input->id;
        $led->description = (string)$input->description;
        $led->status = (string)$input->status;
        $id = R::store($led);    
        
        // return JSON-encoded response body
        // $app->response()->header('Content-Type', 'application/json');
        // echo json_encode(R::exportAll($led));
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

// PUT /leds/:id
$app->put('/leds/:id/status', function ($id) use ($app) {    
    try {
        // 获得HTTP请求中的JSON数据包
        $request = $app->request();
        $body = $request->getBody();
        $input = json_decode($body); 
        
        // 查找编号为ID的记录
        $led = R::findOne('leds', 'id=?', array($id));  
        
        // 重新修改status状态，并保存
        if ($led) {      
            $led->status = (string)$input->status;
            R::store($led);    
            // 直接返回，不做任何处理
            echo json_encode( $input, JSON_NUMERIC_CHECK);
        } else {
            throw new ResourceNotFoundException();    
        }
    } catch (ResourceNotFoundException $e) {
        $app->response()->status(404);
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->run();

?>