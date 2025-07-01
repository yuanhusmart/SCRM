<?php
namespace common\helpers;

use Yii;

/**
 * Class AMQPHelper
 * @package common\helpers
 */
class AMQPHelper
{

    /**
     * 连接信息
     * @var string
     */
    public $host = '';
    public $port = '';
    public $vhost = '';
    public $login = '';
    public $password = '';
    public $readTimeout = 120;
    public $writeTimeout = 120;
    public $connectTimeout = 120;
    public $heartbeat = 3;

    /**
     * 连接
     * @var \AMQPConnection
     */
    public $connection;

    /**
     * 通道
     * @var \AMQPChannel
     */
    public $channel;

    /**
     * 队列
     * @var \AMQPQueue
     */
    public $queue;

    /**
     * 交换器
     * @var \AMQPExchange
     */
    public $exchange;

    /**
     * TTL 队列
     * @var \AMQPQueue
     */
    public $ttlQueue;

    /**
     * TTL 交换器
     * @var \AMQPExchange
     */
    public $ttlExchange;

    /**
     * 队列名称
     * @var string
     */
    public $queueName = '';

    /**
     * 交换器名称
     * @var string
     */
    public $exchangeName = '';

    /**
     * TTL 延迟时间，单位：秒
     * @var int
     */
    public $ttl = 0;

    /**
     * TTL 交换器名称
     * @var string
     */
    public $ttlExchangeName = '';

    /**
     * TTL 队列名称
     * @var string
     */
    public $ttlQueueName = '';

    /**
     * 一条消息消费失败后 sleep 的时间，单位：1/1000000 秒
     * @var int
     */
    public $usleep = 500000;

    /** @var null 路由键 */
    public $routingKey = null;

    /**
     * 实例化
     * AMQPHelper constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $default = [
            'host' => env('RABBITMQ_GOODS_CENTER_HOST'),
            'port' => env('RABBITMQ_GOODS_CENTER_PORT'),
            'login' => env('RABBITMQ_GOODS_CENTER_USER'),
            'password' => env('RABBITMQ_GOODS_CENTER_PASSWORD'),
            'vhost' => env('RABBITMQ_GOODS_CENTER_VHOST'),
            'exchange' => [
                [
                    'change' => env('RABBITMQ_GOODS_CENTER_TRADEMARK_CHANGE'),
                    'queue' =>  env('RABBITMQ_GOODS_CENTER_TRADEMARK_QUEUE'),
                    'x-message-ttl' => 5
                ]
            ],
            'heartbeat' => 10
        ];
        $params = array_merge($default, $params);
        foreach($params as $param => $value){
            if(property_exists($this, $param)){
                $this->$param = $value;
            }
        }
    }

    /**
     * 建立连接、建立通道、定义交换器、定义队列
     * @throws \Exception
     */
    public function connect()
    {
        $this->connection = new \AMQPConnection(array(
            'host' => $this->host,
            'port' => $this->port,
            'vhost' => $this->vhost,
            'login' => $this->login,
            'password' => $this->password,
            'read_timeout' => $this->readTimeout,
            'write_timeout' => $this->writeTimeout,
            'connect_timeout' => $this->connectTimeout,
            'heartbeat' => $this->heartbeat
        ));
        $this->connection->connect();
        $this->channel = new \AMQPChannel($this->connection);
        $this->channel->qos(0, 1);
        register_shutdown_function(function(){
            $this->closeChannel();
            $this->closeConnection();
        });
        if($this->exchangeName){
            $this->exchange = new \AMQPExchange($this->channel);
            $this->exchange->setName($this->exchangeName);
            $this->exchange->setType(AMQP_EX_TYPE_DIRECT);
            $this->exchange->setFlags(AMQP_DURABLE);
            $this->exchange->declareExchange();
        }
        if($this->queueName){
            $this->queue = new \AMQPQueue($this->channel);
            $this->queue->setName($this->queueName);
            $this->queue->setFlags(AMQP_DURABLE);
            $this->queue->declareQueue();
            if($this->exchangeName){
                $this->queue->bind($this->exchangeName, $this->routingKey);
            }
        }
        if($this->ttlExchangeName){
            $this->ttlExchange = new \AMQPExchange($this->channel);
            $this->ttlExchange->setName($this->ttlExchangeName);
            $this->ttlExchange->setType(AMQP_EX_TYPE_DIRECT);
            $this->ttlExchange->setFlags(AMQP_DURABLE);
            $this->ttlExchange->declareExchange();
        }
        if($this->ttlQueueName){
            $this->ttlQueue = new \AMQPQueue($this->channel);
            $this->ttlQueue->setName($this->ttlQueueName);
            $this->ttlQueue->setFlags(AMQP_DURABLE);
            if($this->ttl){
                $this->ttlQueue->setArgument('x-message-ttl', $this->ttl * 1000);
            }
            if($this->exchangeName){
                $this->ttlQueue->setArgument('x-dead-letter-exchange', $this->exchangeName);
            }
            $this->ttlQueue->declareQueue();
            if($this->ttlExchangeName){
                $this->ttlQueue->bind($this->ttlExchangeName, $this->routingKey);
            }
        }
    }

    /**
     * 发送一条消息进队列
     * @param string|array $message
     * @param string $content_type
     * @return bool
     * @throws \Exception
     */
    public function publish($message, $content_type = 'application/json')
    {
        if(!is_string($message)){
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        $attributes = [
            'content_type' => $content_type,
            'headers' => [
                'x-start' => time(),
                'x-retries' => 0,
                'x-max-retries' => 10000,
                'x-max-duration' => 24 * 3600
            ]
        ];
        if($this->ttlExchange){
            return $this->ttlExchange->publish($message, $this->routingKey, AMQP_NOPARAM, $attributes);
        }else{
            return $this->exchange->publish($message, $this->routingKey, AMQP_NOPARAM, $attributes);
        }
    }

    /**
     * 消费者进程，监听队列
     * @param callable $callback
     * @param callable|null $logger
     * @throws \Exception
     */
    public function consume($callback, $logger = null)
    {
        $consumer_tag = 'tag_'.date('YmdHis').'_'.uniqid().'_'.str_pad(strval(rand(0, 999999)), 6, STR_PAD_LEFT, '0');
        $this->queue->consume(function(\AMQPEnvelope $envelope, \AMQPQueue $queue) use($callback, $logger){
            if(!is_callable($logger)){
                $logger = function($message){};
            }
            $tag = $envelope->getDeliveryTag();
            $content_type = $envelope->getContentType();
            $body = $envelope->getBody();
            $json_type = 'application/json';
            if($content_type && substr($content_type, 0, strlen($json_type)) == $json_type){
                $body = json_decode($body, true);
            }
            try{
                $result = $callback($body);
            }catch(\Exception $e){
                $result = false;
                yii::warning($e);
                throw new \Exception('消息 回调 失败');
            }
            if($result){
                if($queue->ack($tag)){
                    $result = true;
                }else{
                    if($queue->reject($tag, AMQP_REQUEUE)){
                        $logger([
                            'tag' => 'ConsumeReject',
                            'reject' => $tag,
                            'body' => $body
                        ]);
                    }else{
                        $logger([
                            'tag' => 'ConsumeReject',
                            'reject' => $tag,
                            'body' => $body,
                            'message' => '消息 ACK 失败'
                        ]);
                        yii::warning([
                            'tag' => 'ConsumeReject',
                            'reject' => $tag,
                            'body' => $body,
                            'message' => '消息 ACK 失败'
                        ]);
                        throw new \Exception('消息 ACK 失败');
                    }
                }
            }else{
                usleep($this->usleep);
                if($queue->reject($tag, AMQP_REQUEUE)){
                    $logger([
                        'tag' => 'ConsumeReject',
                        'reject' => $tag,
                        'body' => $body
                    ]);
                }else{
                    $logger([
                        'tag' => 'ConsumeReject',
                        'reject' => $tag,
                        'body' => $body,
                        'message' => '消息 REJECT 失败'
                    ]);
                    throw new \Exception('消息 REJECT 失败');
                }
            }
        }, AMQP_NOPARAM, $consumer_tag);
    }

    /**
     * 关闭通道和连接
     */
    public function disconnect()
    {
        $this->closeChannel();
        $this->closeConnection();
        $this->queue = null;
        $this->exchange = null;
        $this->ttlQueue = null;
        $this->ttlExchange = null;
        $this->channel = null;
        $this->connection = null;
    }

    /**
     * 关闭通道
     */
    public function closeChannel()
    {
        try{
            if($this->channel){
                $this->channel->close();
            }
        }catch(\Exception $e){}
    }

    /**
     * 关闭连接
     */
    public function closeConnection()
    {
        try{
            if($this->connection){
                $this->connection->disconnect();
            }
        }catch(\Exception $e){}
    }

}