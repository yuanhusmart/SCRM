<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\crm\SuiteCorpCrmCustomerContactTag;
use common\models\SuiteCorpExternalContact;
use common\models\SuiteCorpExternalContactFollowUser;
use common\models\SuiteCorpExternalContactFollowUserRemarkMobiles;
use common\models\SuiteCorpExternalContactFollowUserTags;
use common\models\SuiteCorpGroupChat;
use common\models\SuiteCorpGroupChatMember;
use common\services\crm\SuiteCorpCrmCustomerContactTagService;

class SuiteCorpExternalContactService extends Service
{

    const EVENT_EXTERNAL_CONTACT            = 'change_external_contact';   // 主事件(Event)：变更企业联系人
    const CONTACT_CHANGE_TYPE_ADD           = 'add_external_contact';      // 子事件(ChangeType)：添加企业客户事件
    const CONTACT_CHANGE_TYPE_EDIT          = 'edit_external_contact';     // 子事件(ChangeType)：编辑企业客户事件
    const CONTACT_CHANGE_TYPE_DEL           = 'del_external_contact';      // 子事件(ChangeType)：删除企业客户事件
    const CONTACT_CHANGE_TYPE_DEL_FOLLOW    = 'del_follow_user';           // 子事件(ChangeType)：删除跟进成员事件
    const CONTACT_CHANGE_TYPE_ADD_HALF      = 'add_half_external_contact'; // 子事件(ChangeType)：外部联系人免验证添加成员事件
    const CONTACT_CHANGE_TYPE_TRANSFER_FAIL = 'transfer_fail';             // 子事件(ChangeType)：客户接替失败事件

    const EVENT_EXTERNAL_CHAT      = 'change_external_chat';   // 主事件(Event)：客户群事件
    const CHAT_CHANGE_TYPE_CREATE  = 'create';                 // 子事件(ChangeType)：客户群创建事件
    const CHAT_CHANGE_TYPE_UPDATE  = 'update';                 // 子事件(ChangeType)：客户群变更事件
    const CHAT_CHANGE_TYPE_DISMISS = 'dismiss';                // 子事件(ChangeType)：客户群解散事件

    const EVENT_EXTERNAL_TAG      = 'change_external_tag';    // 主事件(Event)：企业客户标签事件
    const TAG_CHANGE_TYPE_CREATE  = 'create';                 // 子事件(ChangeType)：企业客户标签创建事件
    const TAG_CHANGE_TYPE_UPDATE  = 'update';                 // 子事件(ChangeType)：企业客户标签变更事件
    const TAG_CHANGE_TYPE_DELETE  = 'delete';                 // 子事件(ChangeType)：企业客户标签删除事件
    const TAG_CHANGE_TYPE_SHUFFLE = 'shuffle';                // 子事件(ChangeType)：企业客户标签重排事件

    const EVENT_CHANGE_CONTACT        = 'change_contact';               // 主事件(Event)：通讯录变更通知事件
    const CHANGE_CONTACT_CREATE_PARTY = 'create_party';                 // 子事件(ChangeType)：新增部门事件
    const CHANGE_CONTACT_UPDATE_PARTY = 'update_party';                 // 子事件(ChangeType)：更新部门事件
    const CHANGE_CONTACT_DELETE_PARTY = 'delete_party';                 // 子事件(ChangeType)：删除部门事件
    const CHANGE_CONTACT_CREATE_USER  = 'create_user';                  // 子事件(ChangeType)：新增成员事件
    const CHANGE_CONTACT_UPDATE_USER  = 'update_user';                  // 子事件(ChangeType)：更新成员事件
    const CHANGE_CONTACT_DELETE_USER  = 'delete_user';                  // 子事件(ChangeType)：删除成员事件
    const CHANGE_CONTACT_UPDATE_TAG   = 'update_tag';                   // 子事件(ChangeType)：标签变更通知


    /**
     * @param $params
     * @return array
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        list($order_by, $sort) = self::getOrderInfo($params, ['a.id'], SORT_DESC);

        // 服务商ID
        $suiteId = self::getString($params, 'suite_id');
        // 企业ID
        $corpId = self::getString($params, 'corp_id');
        $accessControl = input('access_control', 1);

        if (!$suiteId || !$corpId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $query = SuiteCorpExternalContact::find()
                                         ->alias('a')
                                         ->when($accessControl, function($query){
                                            $query->andWhere([
                                                'exists',
                                                SuiteCorpExternalContactFollowUser::find()
                                                ->accessControl('suite_corp_external_contact_follow_user.userid', 'userid')
                                                ->andWhere('suite_corp_external_contact_follow_user.external_contact_id=a.id')
                                            ]);
                                         })
                                         ->andWhere(['a.suite_id' => $suiteId])
                                         ->andWhere(['a.corp_id' => $corpId]);

        // 外部联系人的userid
        if ($externalUserid = self::getString($params, 'external_userid')) {
            $query->andWhere(['a.external_userid' => $externalUserid]);
        }

        // 客户情况 1.流失 2.跟进
        if ($isLoss = self::getInt($params, 'is_loss')) {
            $queryExists = 'Exists';
            if ($isLoss == 1) {
                $queryExists = 'NOT Exists';
            }
            $query->andWhere([$queryExists,
                SuiteCorpExternalContactFollowUser::find()
                                                  ->andWhere(SuiteCorpExternalContactFollowUser::tableName() . ".external_contact_id=a.id")
                                                  ->andWhere(['deleted_at' => 0])
            ]);
        }

        // 外部联系人的名称
        if ($name = self::getString($params, 'name')) {
            $query->andWhere(['like', 'a.name', $name]);
        }

        $query->select(['a.id', 'a.external_userid', 'a.is_modify', 'a.name', 'a.avatar', 'a.type', 'a.corp_name', 'a.created_at']);

        $total  = $query->count();
        $order  = [$order_by => $sort, 'a.created_at' => SORT_DESC];
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy($order)->offset($offset)->limit($per_page)->asArray()->all();
        }
        return [
            'ExternalContact' => $resp,
            'pagination'      => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * @param $params
     * @return SuiteCorpExternalContact
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function create($params)
    {
        if (empty($params['external_contact']) || empty($params['follow_user'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $attributes = self::includeKeys($params['external_contact'], ['suite_id', 'corp_id', 'external_userid', 'name', 'avatar', 'type', 'gender', 'unionid', 'position', 'corp_name', 'created_at', 'updated_at', 'is_modify']);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $externalUserid = $attributes['external_userid'] ?? '';
        $corpId         = $attributes['corp_id'] ?? '';
        $suiteId        = $attributes['suite_id'] ?? '';
        $transaction    = \Yii::$app->db->beginTransaction();
        $isUpdate       = true; // 是否更新，true 是 false 否
        try {
            $externalContact = SuiteCorpExternalContact::findOne(['suite_id' => $suiteId, 'corp_id' => $corpId, 'external_userid' => $externalUserid]);
            if (empty($externalContact)) {
                $externalContact = new SuiteCorpExternalContact();
                $isUpdate        = false;
            }
            $externalContact->load($attributes, '');
            // 校验参数
            if (!$externalContact->validate()) {
                throw new ErrException(Code::PARAMS_ERROR, $externalContact->getError());
            }
            if (!$externalContact->save()) {
                throw new ErrException(Code::CREATE_ERROR, $externalContact->getError());
            }
            $externalContactId = $externalContact->getPrimaryKey();

            $crmContactNos = SuiteCorpCrmCustomerContactTagService::getContactNoByExternalUserId(['suite_id' => $suiteId, 'corp_id' => $corpId, 'external_userid' => $externalUserid,]);
            $oldTags       = [];
            if ($crmContactNos) {
                //获取该外部联系人所有标签
                $oldTags = SuiteCorpExternalContactFollowUserTags::find()
                                                                 ->select(['group_name', 'tag_name'])
                                                                 ->andWhere(['external_contact_id' => $externalContactId])
                                                                 ->groupBy(['group_name', 'tag_name'])
                                                                 ->asArray()
                                                                 ->all();
                foreach ($oldTags as $k => $oldTag) {
                    $oldTags[$k] = $oldTag['group_name'] . ':' . $oldTag['tag_name'];
                }
            }

            $suiteCorpExternalContactFollowUserTags          = [];
            $suiteCorpExternalContactFollowUserDel           = [];
            $suiteCorpExternalContactFollowUserRemarkMobiles = [];
            $suiteCorpExternalContactFollowUserRemarkDel     = [];
            $newTags                                         = [];
            foreach ($params['follow_user'] as $item) {
                $item['deleted_at']          = 0;
                $item['external_contact_id'] = $externalContactId;
                $externalContactFollowUserId = SuiteCorpExternalContactFollowUserService::create($item);
                if (!empty($item['tags'])) {
                    $isUpdate === true && $suiteCorpExternalContactFollowUserDel[] = $externalContactFollowUserId;
                    foreach ($item['tags'] as $tag) {
                        $suiteCorpExternalContactFollowUserTags[] = [
                            'external_contact_id'             => $externalContactId,
                            'external_contact_follow_user_id' => $externalContactFollowUserId,
                            'group_name'                      => $tag['group_name'] ?? '',
                            'tag_name'                        => $tag['tag_name'] ?? '',
                            'type'                            => $tag['type'] ?? 0,
                            'tag_id'                          => $tag['tag_id'] ?? '',
                        ];
                        $newTags[]                                = sprintf('%s:%s', ($tag['group_name'] ?? ''), ($tag['tag_name'] ?? ''));
                    }
                }
                if (!empty($item['remark_mobiles'])) {
                    $isUpdate === true && $suiteCorpExternalContactFollowUserRemarkDel[] = $externalContactFollowUserId;
                    foreach ($item['remark_mobiles'] as $mobileKeyValue) {
                        $suiteCorpExternalContactFollowUserRemarkMobiles[] = [
                            'external_contact_follow_user_id' => $externalContactFollowUserId,
                            'mobiles'                         => $mobileKeyValue,
                        ];
                    }
                }
            }
            $suiteCorpExternalContactFollowUserDel && SuiteCorpExternalContactFollowUserTags::deleteAll(['external_contact_follow_user_id' => $suiteCorpExternalContactFollowUserDel]);
            $suiteCorpExternalContactFollowUserTags && SuiteCorpExternalContactFollowUserTags::batchInsert($suiteCorpExternalContactFollowUserTags);
            $suiteCorpExternalContactFollowUserRemarkDel && SuiteCorpExternalContactFollowUserRemarkMobiles::deleteAll(['external_contact_follow_user_id' => $suiteCorpExternalContactFollowUserRemarkDel]);
            $suiteCorpExternalContactFollowUserRemarkMobiles && SuiteCorpExternalContactFollowUserRemarkMobiles::batchInsert($suiteCorpExternalContactFollowUserRemarkMobiles);

            //当该外部联系人存在crm联系人数据的时候处理crm联系人标签
            if ($crmContactNos) {
                $newTags = array_unique($newTags);
                $addTags = array_diff($newTags, $oldTags);//本次企业微信新增的标签(全局)
                $delTags = array_diff($oldTags, $newTags);//本次企业微信删除的标签(全局)
                //当前CRM联系人标签数据
                $crmContactTags       = SuiteCorpCrmCustomerContactTag::find()
                                                                      ->select(['id', 'contact_no', 'group_name', 'tag_name'])
                                                                      ->where([
                                                                          'suite_id'   => $suiteId,
                                                                          'corp_id'    => $corpId,
                                                                          'contact_no' => $crmContactNos
                                                                      ])
                                                                      ->asArray()
                                                                      ->all();
                $crmContactTags       = collect($crmContactTags)->groupBy(['contact_no'])->toArray();
                $crmContactTagDelIds  = [];//存储本次各个CRM联系人对应要删除的标签记录id
                $crmContactTagAddTags = [];//存储本次各个CRM联系人对应要新增的标签
                foreach ($crmContactNos as $contactNo) {
                    //得到当前CRM联系人对应的已有标签集合
                    $localTags = collect($crmContactTags[$contactNo] ?? [])
                        ->map(function ($item) {
                            return sprintf('%s:%s', $item['group_name'], $item['tag_name']);
                        })
                        ->values()
                        ->toArray();
                    // 将新增的标签增加到本地历史标签中
                    $localTags = array_unique(array_merge($localTags, $addTags));
                    // 遍历本次企微删除了哪些标签,在已有的crm联系人标签中查找，找到就要删除掉CRM联系人的标签，没找到就跳过
                    foreach ($delTags as $tag) {
                        if (($key = array_search($tag, $localTags)) !== false) {
                            if (isset($crmContactTags[$contactNo][$key]['id'])) {
                                $crmContactTagDelIds[] = $crmContactTags[$contactNo][$key]['id'];
                            }
                            unset($localTags[$key]);
                        }
                    }
                    // 通过剩下的CRM联系人标签跟之前的企微外部联系人标签对比，得到本次CRM联系人新增的标签
                    $localAddTags = array_diff(array_values($localTags), $oldTags);
                    foreach ($localAddTags as $localAddTag) {
                        $localAddTagDecode = explode(':', $localAddTag);//解码索引
                        //存储本次CRM联系人新增的标签数据
                        $crmContactTagAddTags[] = [
                            'suite_id'   => $suiteId,
                            'corp_id'    => $corpId,
                            'contact_no' => $contactNo,
                            'group_name' => $localAddTagDecode[0] ?? '',
                            'tag_name'   => $localAddTagDecode[1] ?? '',
                            'created_at' => time(),
                            'updated_at' => time(),
                        ];
                    }
                }
                $crmContactTagDelIds && SuiteCorpCrmCustomerContactTag::deleteAll(['id' => $crmContactTagDelIds]);
                $crmContactTagAddTags && SuiteCorpCrmCustomerContactTag::batchInsert($crmContactTagAddTags);
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $externalContactId;
    }

    /**
     * @param $params
     * @return SuiteCorpExternalContact
     * @throws ErrException
     */
    public static function update($params)
    {
        $id = self::getId($params);
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpExternalContact::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, ['external_userid', 'name', 'avatar', 'type', 'gender', 'unionid', 'position', 'corp_name', 'created_at', 'updated_at']);
        try {
            $data->attributes = $attributes;
            if (!$data->save()) {
                throw new ErrException(Code::UPDATE_ERROR, $data->getError());
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $data;
    }

    /**
     * 获取外部好友数量根据Userid
     * @param $params
     * @return bool|int|string|null
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function getExternalContactCountByUserid($params)
    {
        $userid = self::getString($params, 'userid');
        if (empty($userid)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        return SuiteCorpExternalContactFollowUser::find()
                                                 ->andWhere(['userid' => $userid])
                                                 ->andWhere(['deleted_at' => 0])
                                                 ->count();
    }

    /**
     * 继承-外部联系人列表
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function inheritItems($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        // 服务商ID
        $suiteId = self::getString($params, 'suite_id');
        // 企业ID
        $corpId = self::getString($params, 'corp_id');
        // 企业成员userid
        $userid = self::getString($params, 'userid');

        if (!$suiteId || !$corpId || !$userid) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $query = SuiteCorpExternalContact::find()
                                         ->alias('a')
                                         ->leftJoin(SuiteCorpExternalContactFollowUser::tableName() . ' AS b', 'a.id = b.external_contact_id')
                                         ->andWhere(['a.suite_id' => $suiteId])
                                         ->andWhere(['a.corp_id' => $corpId])
                                         ->andWhere(['b.userid' => $userid])
                                         ->andWhere(['b.deleted_at' => 0])
                                         ->select(['a.suite_id', 'a.corp_id', 'a.external_userid', 'a.name', 'a.avatar']);

        $total  = $query->count();
        $order  = ['b.createtime' => SORT_DESC];
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp     = $query->orderBy($order)->offset($offset)->limit($per_page)->asArray()->all();
            $chatInfo = SuiteCorpGroupChat::find()->alias('gc')
                                          ->leftJoin(SuiteCorpGroupChatMember::tableName() . ' AS gcm', 'gc.id = gcm.group_chat_id')
                                          ->andWhere(['gc.suite_id' => $suiteId])
                                          ->andWhere(['gc.corp_id' => $corpId])
                                          ->andWhere(['gc.owner' => $userid])
                                          ->andWhere(['gcm.type' => SuiteCorpGroupChatMember::GROUP_CHAT_TYPE_2])
                                          ->andWhere(['in', 'gcm.userid', array_column($resp, 'external_userid')])
                                          ->select('gcm.userid as external_userid,count(DISTINCT gc.chat_id) as external_group_count')
                                          ->groupBy('gcm.userid')
                                          ->asArray()
                                          ->indexBy('external_userid')
                                          ->all();
            foreach ($resp as &$v) {
                $v['external_group_count'] = empty($chatInfo[$v['external_userid']]) ? 0 : $chatInfo[$v['external_userid']]['external_group_count'];
            }
        }
        return [
            'ExternalContact' => $resp,
            'pagination'      => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * @param $params
     * @return array|\yii\db\ActiveRecord[]
     * @throws ErrException
     */
    public static function getNameById($params)
    {
        // 服务商ID
        $suiteId = self::getString($params, 'suite_id');
        // 企业ID
        $corpId = self::getString($params, 'corp_id');

        $externalUserid = self::getArray($params, 'external_userid');

        if (!$suiteId || !$corpId || !$externalUserid) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        return SuiteCorpExternalContact::find()
                                       ->select('id,external_userid,name,avatar,type,corp_name')
                                       ->andWhere(['suite_id' => $suiteId])
                                       ->andWhere(['corp_id' => $corpId])
                                       ->andWhere(['in', 'external_userid', $externalUserid])
                                       ->asArray()
                                       ->all();
    }

}
