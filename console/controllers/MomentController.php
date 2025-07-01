<?php

namespace console\controllers;

use common\models\SuiteCorpConfig;
use common\models\SuiteCorpMoment;
use common\models\SuiteCorpMomentContents;
use common\services\OssService;
use common\services\Service;
use common\services\SuiteCorpMomentContentsService;
use common\services\SuiteCorpMomentService;
use common\services\SuiteService;

class MomentController extends BaseConsoleController
{

    /**
     * 朋友圈数据拉取 (每天凌晨2点执行)
     * 定时任务 php ./yii moment/pull 1
     * @param $all int 是否全量同步
     * @param int $all
     * @return true
     */
    public function actionPull(int $all = 0)
    {
        $time = time();
        $sub  = 24 * 60 * 60; // 默认查询1天数据
        // 如果全量查询30天数据
        if ($all) {
            $sub = 30 * 24 * 60 * 60;
        }
        $param = [
            'start_time' => $time - $sub,
            'end_time'   => $time
        ];
        try {
            $suiteList = SuiteCorpConfig::find()->asArray()->all();
            self::consoleLog('获取服务商配置数据');
            self::consoleLog($param);
            foreach ($suiteList as $suite) {
                self::consoleLog($suite);
                do {
                    if (!empty($nextCursor)) {
                        $param['cursor'] = $nextCursor;
                    }
                    $i          = 1;
                    $momentList = SuiteService::getExternalContactMomentList($suite['suite_id'], $suite['corp_id'], $param);
                    foreach ($momentList['moment_list'] as $moment) {
                        $moment['suite_id'] = $suite['suite_id'];
                        $moment['corp_id']  = $suite['corp_id'];
                        self::consoleLog('获取一条朋友圈数据');
                        self::consoleLog('企业ID:' . $suite['corp_id'] . ',第 ' . $i . ' 次');
                        self::consoleLog($moment);
                        try {
                            $moment['comments']      = SuiteService::getExternalContactMomentComments($suite['suite_id'], $suite['corp_id'], ['moment_id' => $moment['moment_id'], 'userid' => $moment['creator']]);
                            $moment['comment_count'] = empty($moment['comments']['comment_list']) ? 0 : count($moment['comments']['comment_list']);
                            $moment['like_count']    = empty($moment['comments']['like_list']) ? 0 : count($moment['comments']['like_list']);
                            SuiteCorpMomentService::create($moment);
                        } catch (\Exception $momentE) {
                            self::consoleLog($momentE->getMessage());
                        }
                        $i++;
                    }
                    $nextCursor = $momentList['next_cursor'] ?? '';
                } while ($nextCursor !== '');
            }
        } catch (\Exception $e) {
            self::consoleLog($e->getMessage());
        }
        return true;
    }

    /**
     * 朋友圈资源下载
     * php ./yii moment/download-resources
     *
     * Supervisor:aaw.moment.download-resources [ supervisorctl restart aaw.moment.download-resources:  ]
     * Supervisor Log:/var/log/supervisor/aaw.moment.download-resources.log
     * @return void
     */
    public function actionDownloadResources()
    {
        self::consoleLog('朋友圈资源下载:开始');
        $routingKey = SuiteCorpMomentContentsService::MQ_MOMENT_MEDIA_ROUTING_KEY;
        Service::consoleConsumptionMQ(SuiteCorpMomentContentsService::MQ_MOMENT_MEDIA_EXCHANGE, SuiteCorpMomentContentsService::MQ_MOMENT_MEDIA_QUEUE, function ($msg) {
            \Yii::$app->db->close();
            self::consoleLog($msg);
            try {
                $config = SuiteCorpMomentContents::find()->alias('a')
                                                 ->leftJoin(SuiteCorpMoment::tableName() . ' AS b', 'a.corp_moment_id = b.id')
                                                 ->select(['b.suite_id', 'b.corp_id'])
                                                 ->where(['a.id' => $msg['id']])
                                                 ->asArray()
                                                 ->limit(1)
                                                 ->one();

                self::consoleLog($config);

                $content = SuiteService::downloadMedia($config['suite_id'], $config['corp_id'], $msg['media_id']);
                $ext     = '';
                switch ($msg['type']) {
                    case 'image_media_id':
                    case 'video_thumb_media_id':
                        $ext = 'png';
                    break;
                    case 'video_media_id':
                        $ext = 'mp4';
                    break;
                }
                $ossPath = sprintf('%s/moment/%s/%s-%s.%s', \Yii::$app->params['APP_NAME'], date('Ymd'), $msg['media_id'], $msg['id'], $ext);
                OssService::uploadStreamFile($content, $ossPath);
                SuiteCorpMomentContentsService::update(['id' => $msg['id'], $msg['field'] => $ossPath]);
            } catch (\Exception $e) {
                self::consoleLog('朋友圈资源下载：' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ',跳过' . $e->getMessage());

                // 失败重新入队
                $routingKey = SuiteCorpMomentContentsService::MQ_MOMENT_MEDIA_ROUTING_KEY;
                Service::pushRabbitMQMsg(SuiteCorpMomentContentsService::MQ_MOMENT_MEDIA_EXCHANGE, SuiteCorpMomentContentsService::MQ_MOMENT_MEDIA_QUEUE, function ($mq) use ($msg, $routingKey) {
                    try {
                        $mq->publish(json_encode($msg, JSON_UNESCAPED_UNICODE), $routingKey);
                    } catch (\Exception $e) {
                        \Yii::warning($e->getMessage());
                    }
                }, $routingKey);
            }
        }, 1, $routingKey);
    }

}