<?php

namespace app\controller;

use common\models\SuiteCorpConfig;
use common\services\BusinessOpportunitiesAnalysisService;
use PHPUnit\Framework\TestCase;

class OpportunityTest extends TestCase
{
    /**
     * 商机分析检查
     */
    public function testAnalyzeCheck()
    {
        $data = ['id' => 16];

        $corp = SuiteCorpConfig::findOne(16);

        $result = BusinessOpportunitiesAnalysisService::checkSchedule($data);

        var_dump($result);

        foreach($result['data'] as $item){
            $result = BusinessOpportunitiesAnalysisService::main($corp, $item);
            var_dump($result);
        }




        $this->assertTrue(true);
    }
}